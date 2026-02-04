import { useState, useEffect, useMemo } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getSiteMetrics, type SiteMonitoringResponse } from '../services/monitoringService';
import { getSiteById } from '../services/sitesService';
import {
    Loader2, Activity,
    Database, Terminal, ShieldCheck, Clock, X,
    FolderOpen, FileText, ChevronDown, ChevronRight,
    AlertTriangle, Layers, Copy, Trash2
} from 'lucide-react';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { AreaChart, Area, ResponsiveContainer, CartesianGrid, XAxis, YAxis } from 'recharts';
import { useRef } from 'react';
import AlertManager from '../components/AlertManager';

function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// --- Utils ---

// --- Time Ago Component ---
const TimeAgo = ({ timestamp }: { timestamp: string }) => {
    const [timeAgo, setTimeAgo] = useState('');

    useEffect(() => {
        const update = () => {
            if (!timestamp) return;
            // Try parsing YYYY-MM-DD HH:mm:ss or similar
            const date = new Date(timestamp);
            if (isNaN(date.getTime())) {
                setTimeAgo('');
                return;
            }
            const now = new Date();
            const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

            if (diff < 60) {
                setTimeAgo(`${diff}s ago`);
            } else if (diff < 3600) {
                setTimeAgo(`${Math.floor(diff / 60)}m ago`);
            } else if (diff < 86400) {
                setTimeAgo(`${Math.floor(diff / 3600)}h ${Math.floor((diff % 3600) / 60)}m ago`);
            } else {
                setTimeAgo(`${Math.floor(diff / 86400)}d ${Math.floor((diff % 86400) / 3600)}h ago`);
            }
        };
        update();
        const output = setInterval(update, 60000); // Update every minute
        return () => clearInterval(output);
    }, [timestamp]);

    return <span>{timeAgo}</span>;
};

// --- Daily/Hourly Parser ---
const parseBackupStatus = (raw: string | undefined) => {
    if (!raw) return { name: '-', time: null, raw: '-' };

    let name = raw;
    let timeStr = '';

    // 1. Try split by pipe
    if (raw.includes('|')) {
        const parts = raw.split('|');
        name = parts[0].trim();
        timeStr = parts[1].trim();
    } else {
        // If no pipe, try to find a date/duration pattern in the whole string
        // extracting it might be hard, so let's check if the *whole* string is just a date? 
        // Unlikely given "trae el nombre... y el tiempo".
        // Let's assume the END of the string might be the time if no pipe.
        timeStr = raw;
    }

    // 2. Try Standard Timestamp (YYYY-MM-DD HH:mm:ss)
    const dateMatch = timeStr.match(/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/);
    if (dateMatch) {
        return { name, time: dateMatch[1], raw };
    }

    // 3. Try Duration Format (e.g. "1d 2h 30m 10s" or similar)
    // We convert duration to a past timestamp so TimeAgo can count up from it.
    // Regex looks for groups of digits followed by d/h/m/s
    const durRegex = /(?:(\d+)\s*d)?\s*(?:(\d+)\s*h)?\s*(?:(\d+)\s*m)?\s*(?:(\d+)\s*s)?/;
    const durMatch = timeStr.match(durRegex);

    // Check if we actually matched something relevant (at least one unit)
    if (durMatch && (durMatch[1] || durMatch[2] || durMatch[3] || durMatch[4])) {
        const days = parseInt(durMatch[1] || '0');
        const hours = parseInt(durMatch[2] || '0');
        const mins = parseInt(durMatch[3] || '0');
        const secs = parseInt(durMatch[4] || '0');

        if (days + hours + mins + secs > 0) {
            const now = new Date();
            const totalSeconds = (days * 86400) + (hours * 3600) + (mins * 60) + secs;
            const pastDate = new Date(now.getTime() - (totalSeconds * 1000));

            // Return ISO string for TimeAgo to consume
            // Note: If 'name' was just the raw string and we found a duration inside it, 
            // we might want to clean 'name' to remove the duration part? 
            // For now, keeping name as is or split part is safer.
            return { name, time: pastDate.toISOString(), raw };
        }
    }

    // 4. Try X-HH:MM:SS Format (e.g. "0-10:37:04")
    const customMatch = timeStr.match(/(\d+)-(\d{2}):(\d{2}):(\d{2})/);
    if (customMatch) {
        const days = parseInt(customMatch[1]);
        const hours = parseInt(customMatch[2]);
        const mins = parseInt(customMatch[3]);
        const secs = parseInt(customMatch[4]);

        const now = new Date();
        const totalSeconds = (days * 86400) + (hours * 3600) + (mins * 60) + secs;
        const pastDate = new Date(now.getTime() - (totalSeconds * 1000));

        // Clean name by removing the time part
        const cleanName = name.replace(customMatch[0], '').trim();
        return { name: cleanName, time: pastDate.toISOString(), raw, formattedAge: `${days > 0 ? days + 'd ' : ''}${hours}h ${mins}m ${secs}s` };
    }

    return { name, time: null, raw };
};

const HighlightedContent = ({ content }: { content: string }) => {
    if (!content) return <span className="text-slate-400 italic font-medium">No hay salida disponible.</span>;

    const lines = content.split('\n');

    return (
        <div className="flex flex-col gap-1">
            {lines.map((line, lineIdx) => {
                if (!line.trim()) return <div key={lineIdx} className="h-4"></div>;

                const fragments = line.split(/\b(error|warning|danger|panic|critical|failed|success|active|online|offline|JAMSCluster|JAMSRouter)\b/gi);

                // Detect duration pattern (MM:SS or HH:MM:SS) in column 3 (common for active scripts)
                const parts = line.split(/\s+/).filter(Boolean);
                let durationPart = "";
                let isWarning = false;
                let isDanger = false;

                if (parts.length >= 3) {
                    const timeStr = parts[2];
                    const m = timeStr.match(/(?:(\d+):)?(\d+):(\d+)/);
                    if (m) {
                        durationPart = timeStr;
                        const hours = m[3] ? parseInt(m[1]) : 0;
                        const mins = m[3] ? parseInt(m[2]) : parseInt(m[1]);
                        const secs = m[3] ? parseInt(m[3]) : parseInt(m[2]);
                        const total = (hours * 3600) + (mins * 60) + secs;

                        if (total > 300) isWarning = true;
                        if (total > 1800) isDanger = true;
                    }
                }

                if (durationPart) {
                    const startIndex = line.indexOf(durationPart);
                    const before = line.substring(0, startIndex);
                    const after = line.substring(startIndex + durationPart.length);

                    return (
                        <div key={lineIdx} className="hover:bg-white/5 rounded px-1 transition-all text-slate-200 group flex flex-wrap items-center font-mono text-xs">
                            <span className="opacity-80 group-hover:opacity-100">{before}</span>
                            <span className={clsx(
                                "font-bold px-1.5 py-0.5 rounded shadow-sm mx-0.5 transition-all text-[10px]",
                                isDanger ? "bg-red-500 text-white animate-pulse" :
                                    (isWarning ? "bg-yellow-400 text-slate-900" : "bg-emerald-500/20 text-emerald-300 border border-emerald-500/30")
                            )}>
                                {durationPart}
                            </span>
                            <span className="opacity-80 group-hover:opacity-100">{after}</span>
                        </div>
                    );
                }

                if (line.trim().startsWith('>')) {
                    return (
                        <div key={lineIdx} className="hover:bg-white/5 rounded px-1 transition-all text-emerald-400 font-bold group flex flex-wrap items-center font-mono text-xs mt-4 mb-1">
                            {line}
                        </div>
                    );
                }

                return (
                    <div key={lineIdx} className="hover:bg-white/5 rounded px-1 transition-all text-slate-200 group flex flex-wrap items-center font-mono text-xs">
                        {fragments.map((part, i) => {
                            const lower = part.toLowerCase();
                            if (/error|danger|panic|critical|failed/i.test(lower)) {
                                return <span key={i} className="bg-red-500/10 text-red-400 font-bold px-1 rounded border border-red-500/20">{part}</span>;
                            }
                            if (/warning/i.test(lower)) {
                                return <span key={i} className="bg-yellow-500/10 text-yellow-400 font-bold px-1 rounded border border-yellow-500/20">{part}</span>;
                            }
                            if (/success|active|online/i.test(lower)) {
                                return <span key={i} className="bg-emerald-500/10 text-emerald-400 font-bold px-1 rounded border border-emerald-500/20">{part}</span>;
                            }
                            if (/JAMSCluster|JAMSRouter/i.test(lower)) {
                                return <span key={i} className="bg-yellow-500/10 text-yellow-300 font-bold px-1 rounded border border-yellow-500/20">{part}</span>;
                            }
                            return <span key={i}>{part}</span>;
                        })}
                    </div>
                );
            })}
        </div>
    );
};

// --- File Explorer Helper ---
const FileTree = ({ rawData }: { rawData: string | undefined }) => {
    const tree = useMemo(() => {
        if (!rawData) return null;

        const parseTree = (raw: string) => {
            const items = raw.split('\n').filter(l => l.trim()).map(line => {
                const parts = line.trim().split(/\s+/);
                const size = parts[0];
                const fullPath = parts[1] || 'unknown';
                return { size, fullPath };
            });

            const root: any = { name: '/', children: {}, type: 'dir', fullPath: '/' };

            items.forEach(item => {
                const parts = item.fullPath.split('/').filter(p => p);
                let current = root;
                let pathAcc = '';
                parts.forEach((part, i) => {
                    pathAcc += '/' + part;
                    if (i === parts.length - 1) {
                        current.children[part] = { name: part, size: item.size, type: 'file', fullPath: item.fullPath };
                    } else {
                        if (!current.children[part]) {
                            current.children[part] = { name: part, children: {}, type: 'dir', fullPath: pathAcc };
                        }
                        current = current.children[part];
                    }
                });
            });
            return root;
        };
        return parseTree(rawData);
    }, [rawData]);

    if (!tree) return <div className="text-slate-400 text-xs italic p-4">No se detectaron archivos pesados.</div>;

    const RenderNode = ({ node, depth = 0 }: { node: any, depth?: number }) => {
        const [isOpen, setIsOpen] = useState(true);

        return (
            <div className="select-none">
                <div
                    onClick={() => node.type === 'dir' && setIsOpen(!isOpen)}
                    className={cn(
                        "flex items-center gap-2 py-1 px-2 rounded-md transition-colors cursor-pointer text-xs",
                        node.type === 'dir' ? "text-emerald-700 hover:bg-emerald-50" : "text-slate-600 hover:bg-slate-50"
                    )}
                    style={{ paddingLeft: `${depth * 16 + 8}px` }}
                >
                    {node.type === 'dir' ? (
                        <>
                            {isOpen ? <ChevronDown size={14} className="text-emerald-500" /> : <ChevronRight size={14} className="text-emerald-500" />}
                            <FolderOpen size={14} className="text-emerald-500 fill-emerald-50" />
                            <span className="font-bold">{node.name}</span>
                        </>
                    ) : (
                        <>
                            <div className="w-[14px]"></div>
                            <FileText size={14} className="text-slate-400" />
                            <span className="flex-1 truncate">{node.name}</span>
                            <span className="text-[10px] font-black text-slate-400 tabular-nums bg-slate-100 px-1.5 rounded-full">({node.size})</span>
                        </>
                    )}
                </div>
                {node.type === 'dir' && isOpen && (
                    <div>
                        {Object.values(node.children).map((child: any) => (
                            <RenderNode key={child.fullPath} node={child} depth={depth + 1} />
                        ))}
                    </div>
                )}
            </div>
        );
    };

    return (
        <div key={rawData} className="p-2 overflow-auto max-h-[500px] custom-scrollbar">
            {Object.values(tree.children).map((child: any) => (
                <RenderNode key={child.fullPath} node={child} />
            ))}
        </div>
    );
};

// --- Database Section Components ---
const DBHealthCard = ({ label, value, sublabel, color, icon: Icon, onClick }: any) => {
    const isAlert = color.bg?.includes('red') || color.bg?.includes('amber');

    return (
        <div
            onClick={onClick}
            className={cn(
                "rounded-xl border p-2.5 flex flex-col justify-between shadow-sm min-h-[64px] transition-all",
                isAlert ? `${color.bg} border-transparent` : "bg-white border-slate-100",
                onClick ? "cursor-pointer hover:border-blue-200 hover:shadow-md active:scale-95" : ""
            )}
        >
            <div className="flex justify-between items-start mb-1">
                <span className={cn("text-[8px] font-black uppercase tracking-widest", isAlert ? "text-white/70" : "text-slate-400")}>{label}</span>
                <div className={cn("p-1 rounded-md", isAlert ? "bg-white/20 text-white" : `${color.bg} ${color.text}`)}><Icon size={12} /></div>
            </div>
            <div className="flex items-baseline gap-1.5 min-w-0">
                <span className={cn("text-sm font-black truncate", isAlert ? "text-white" : color.text)}>{value}</span>
                {sublabel && <span className={cn("text-[8px] font-bold truncate tracking-tighter", isAlert ? "text-white/80" : "text-slate-400")}>{sublabel}</span>}
            </div>
        </div>
    );
};

const SchemaBackupCard = ({ secondaryServer }: any) => {
    const metricObj = secondaryServer?.app?.['db.schema.date'];
    const dateStr = metricObj?.metric_value; // Expected: "YYYY-MM-DD HH:mm:ss"
    const [elapsed, setElapsed] = useState('');

    useEffect(() => {
        const update = () => {
            if (!dateStr) return;
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return;

            const now = new Date();
            const diff = Math.floor((now.getTime() - date.getTime()) / 1000);

            if (diff < 60) setElapsed(`${diff}s`);
            else if (diff < 3600) setElapsed(`${Math.floor(diff / 60)}m ${diff % 60}s`);
            else if (diff < 86400) setElapsed(`${Math.floor(diff / 3600)}h ${Math.floor((diff % 3600) / 60)}m ${diff % 60}s`);
            else setElapsed(`${Math.floor(diff / 86400)}d ${Math.floor((diff % 86400) / 3600)}h ${Math.floor((diff % 3600) / 60)}m ${diff % 60}s`);
        };
        update();
        const timer = setInterval(update, 1000); // Update every second
        return () => clearInterval(timer);
    }, [dateStr]);

    // Status comes from DB evaluation (Single Source of Truth)
    const status = metricObj?.status || 'ok';
    const isDanger = status === 'danger';
    const isWarning = status === 'warning';

    if (!dateStr) return <DBHealthCard label="Schema Backup" value="N/A" sublabel="SCHEMA" color={{ bg: 'bg-indigo-50', text: 'text-indigo-600' }} icon={Clock} />;

    return (
        <DBHealthCard
            label="Schema Backup"
            value={elapsed || '...'}
            sublabel={dateStr}
            color={isDanger ? { bg: 'bg-red-600', text: 'text-white' } :
                isWarning ? { bg: 'bg-amber-400', text: 'text-amber-950' } :
                    { bg: 'bg-indigo-50', text: 'text-indigo-600' }}
            icon={Clock}
        />
    );
};

const TerminalModal = ({ isOpen, onClose, title, content, serverId, metricKey, metricsData }: any) => {
    if (!isOpen) return null;

    // Resolve "live" content if possible
    let liveContent = content;
    if (metricsData && serverId && metricKey) {
        const srv = metricsData.servers.find((s: any) => s.info.id === serverId);
        if (srv && srv.app && srv.app[metricKey]) {
            liveContent = srv.app[metricKey].metric_value;

            // Special case for JAMS Service on Active server: Append app.jams.restarts_log
            if (metricKey === 'app.jams.service' && srv.info.is_primary == 1) {
                const restartData = srv.app['app.jams.restarts_log']?.metric_value ||
                    srv.app['app.jams.restart']?.metric_value;
                if (restartData) {
                    liveContent = liveContent.trim() + "\n\n" + "> reinicios del jams\n" + restartData;
                }
            }
        }
    }

    return (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm animate-in fade-in duration-200">
            <div className="bg-[#0f172a] text-slate-50 min-w-fit max-w-[95vw] md:min-w-[896px] h-[80vh] rounded-2xl flex flex-col shadow-2xl border border-slate-700 overflow-hidden animate-in zoom-in-95 duration-200">
                <div className="flex justify-between items-center p-5 border-b border-slate-800 bg-slate-900/50">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-emerald-500/10 rounded-lg text-emerald-400">
                            <Terminal size={18} />
                        </div>
                        <h3 className="font-bold text-slate-100 tracking-tight font-mono">{title}</h3>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 hover:bg-white/10 rounded-full transition-colors text-slate-400 hover:text-white"
                    >
                        <X size={20} />
                    </button>
                </div>
                <div className="flex-1 p-6 overflow-auto custom-scrollbar bg-[#0f172a]">
                    <HighlightedContent content={liveContent} />

                    {/* Utility Footer for Idle Queries (KILL Commands) */}
                    {metricKey === 'db.idle_queries' && liveContent && liveContent.length > 5 && (
                        <div className="mt-8 border-t border-slate-800 pt-6">
                            <h4 className="flex items-center gap-2 text-rose-400 font-bold mb-4 text-xs uppercase tracking-widest">
                                <Trash2 size={14} />
                                Comandos para Limpieza de Hilos (KILL)
                            </h4>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                {liveContent.split('\n').filter((l: string) => l.includes('|')).map((line: string, idx: number) => {
                                    const pid = line.split('|')[0]?.trim();
                                    if (!pid || isNaN(Number(pid))) return null;
                                    return (
                                        <div key={idx} className="bg-slate-900 border border-slate-800 rounded-lg p-3 group hover:border-rose-500/50 transition-colors">
                                            <div className="text-[10px] text-slate-500 font-mono mb-2 uppercase">PID: {pid}</div>
                                            <div className="flex items-center justify-between gap-2">
                                                <code className="text-[10px] text-rose-300 font-mono truncate bg-rose-500/5 px-1.5 rounded grayscale group-hover:grayscale-0">KILL {pid};</code>
                                                <button
                                                    onClick={() => {
                                                        navigator.clipboard.writeText(`KILL ${pid};`);
                                                        alert(`Copiado: KILL ${pid};`);
                                                    }}
                                                    className="p-1.5 bg-slate-800 hover:bg-rose-600 rounded text-slate-300 hover:text-white transition-colors"
                                                    title="Copiar comando"
                                                >
                                                    <Copy size={12} />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
                <div className="p-4 border-t border-slate-800 bg-slate-900/30 flex justify-between items-center">
                    <div className="text-[10px] text-slate-500 font-mono italic">
                        {metricKey === 'db.idle_queries' ? '* Copia el comando KILL y ejecútalo en tu gestor SQL para matar hilos específicos.' : ''}
                    </div>
                    <button
                        onClick={onClose}
                        className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-100 rounded-lg font-bold text-sm transition-all shadow-sm border border-slate-700"
                    >
                        Cerrar Detalle
                    </button>
                </div>
            </div>
        </div>
    );
};

// --- Circular Gauge Component (Thicker, Pixel-Perfect for Site 7) ---
const CircularUsage = ({ percentage, label, sublabel, color, onClick, buttonLabel }: any) => {
    const radius = 45;
    const stroke = 8;
    const normalizedRadius = radius - (stroke / 2);
    const circumference = normalizedRadius * 2 * Math.PI;
    const strokeDashoffset = circumference - (percentage / 100) * circumference;

    return (
        <div className="flex flex-col items-center justify-center">
            <div className="relative flex items-center justify-center mb-2">
                <svg height={radius * 2} width={radius * 2} className="transform -rotate-90">
                    <circle
                        stroke="#f1f5f9"
                        strokeWidth={stroke}
                        fill="transparent"
                        r={normalizedRadius}
                        cx={radius}
                        cy={radius}
                    />
                    <circle
                        stroke={color}
                        strokeWidth={stroke}
                        strokeDasharray={circumference + ' ' + circumference}
                        style={{ strokeDashoffset }}
                        strokeLinecap="round"
                        fill="transparent"
                        r={normalizedRadius}
                        cx={radius}
                        cy={radius}
                        className="transition-all duration-1000 ease-out"
                    />
                </svg>
                <span className="absolute text-base font-black text-slate-800" style={{ color }}>{Math.round(percentage)}%</span>
            </div>
            <div className="text-center flex flex-col items-center">
                <div className="font-bold text-[11px] text-slate-500 uppercase tracking-tight leading-none mb-1">
                    {label === 'Disco' ? 'Disco Duro' : label}
                </div>
                <div className="text-[10px] font-black text-slate-700 leading-none mb-2">{sublabel}</div>
                {onClick && (
                    <button
                        onClick={onClick}
                        className="px-3 py-1 bg-white text-[#0ea5e9] text-[10px] font-bold rounded border border-[#e0f2fe] hover:bg-[#f0f9ff] transition-colors shadow-sm uppercase tracking-tighter"
                    >
                        {buttonLabel === 'Particiones' ? 'Ver Particiones' : (buttonLabel === 'Crontab' ? 'Ver Crontab' : buttonLabel)}
                    </button>
                )}
            </div>
        </div>
    );
};


const MonitoreoSite = () => {
    const { id } = useParams();
    const siteId = parseInt(id || '0');
    const [modalOpen, setModalOpen] = useState(false);
    const [modalTitle, setModalTitle] = useState('');
    const [modalContent, setModalContent] = useState('');
    const [selectedMetricKey, setSelectedMetricKey] = useState<string | null>(null);
    const [selectedServerId, setSelectedServerId] = useState<number | null>(null);

    // --- Live History Refs & States ---
    const [livePrimaryHistory, setLivePrimaryHistory] = useState<any[]>([]);
    const [liveSecondaryHistory, setLiveSecondaryHistory] = useState<any[]>([]);
    const primaryRef = useRef<any>(null);
    const secondaryRef = useRef<any>(null);

    const openTerminal = (title: string, content: string, serverId: number | null = null, metricKey: string | null = null) => {
        setModalTitle(title);
        setModalContent(content);
        setSelectedServerId(serverId);
        setSelectedMetricKey(metricKey);
        setModalOpen(true);
    };

    const { data: site } = useQuery({ queryKey: ['site', siteId], queryFn: () => getSiteById(siteId), enabled: siteId > 0 });
    const { data: metricsData, isLoading } = useQuery<SiteMonitoringResponse>({
        queryKey: ['siteMetrics', siteId],
        queryFn: () => getSiteMetrics(siteId, 'fms'),
        refetchInterval: 5000,
        enabled: !!siteId
    });

    const primaryServer = metricsData?.servers.find((s: any) => s.info.is_primary === 1) || metricsData?.servers?.[0];
    const secondaryServer = metricsData?.servers.find((s: any) => s.info.id !== primaryServer?.info?.id);

    // Sync refs for the timer closure
    useEffect(() => {
        if (primaryServer) {
            primaryRef.current = primaryServer;
            if (livePrimaryHistory.length === 0 && primaryServer.cpu_history) {
                setLivePrimaryHistory(primaryServer.cpu_history.map((h: any) => ({
                    cpu_usage: parseFloat(h.cpu_usage),
                    created_at: new Date(h.created_at).getTime()
                })));
            }
        }
        if (secondaryServer) {
            secondaryRef.current = secondaryServer;
            if (liveSecondaryHistory.length === 0 && secondaryServer.cpu_history) {
                setLiveSecondaryHistory(secondaryServer.cpu_history.map((h: any) => ({
                    cpu_usage: parseFloat(h.cpu_usage),
                    created_at: new Date(h.created_at).getTime()
                })));
            }
        }
    }, [primaryServer, secondaryServer]);

    // --- Robust Live History Manager (Independent Timer) ---
    useEffect(() => {
        const interval = setInterval(() => {
            const time = new Date().getTime();

            // Primary Update
            if (primaryRef.current) {
                const sys = primaryRef.current.system;
                const baseLoad = parseFloat(sys?.cpu_usage || sys?.['system.cpu.load'] || '0');
                // Micro-jitter (0.005) to ensure unique points and smooth scrolling even if logic is static
                const load = baseLoad + (Math.random() * 0.01 - 0.005);

                setLivePrimaryHistory(prev => {
                    if (prev.length === 0) {
                        return Array(30).fill(null).map((_, i) => ({ cpu_usage: load, created_at: time - (30 - i) * 1500 }));
                    }
                    const next = [...prev, { cpu_usage: load, created_at: time }];
                    return next.slice(-40);
                });
            }

            // Secondary Update
            if (secondaryRef.current) {
                const sys = secondaryRef.current.system;
                const baseLoad = parseFloat(sys?.cpu_usage || sys?.['system.cpu.load'] || '0');
                const load = baseLoad + (Math.random() * 0.01 - 0.005);

                setLiveSecondaryHistory(prev => {
                    if (prev.length === 0) {
                        return Array(30).fill(null).map((_, i) => ({ cpu_usage: load, created_at: time - (30 - i) * 1500 }));
                    }
                    const next = [...prev, { cpu_usage: load, created_at: time }];
                    return next.slice(-40);
                });
            }
        }, 1500); // 1.5 second frequency for smoother movement

        return () => clearInterval(interval);
    }, []); // Run only once

    if (isLoading || !site || !metricsData) return <div className="flex justify-center items-center h-screen"><Loader2 className="animate-spin w-8 h-8" /></div>;


    // --- Static Header & Summary Row (Emergency Request) ---
    return (
        <div className="min-h-screen bg-[#f8fafc] p-4 text-slate-800 font-sans">
            {/* Top Banner Warning (Yellow) */}
            <div className="bg-[#fcd34d] text-slate-900 text-xs font-bold text-center py-1 rounded-t-md mb-4 shadow-sm">
                Entorno de desarrollo del sistema de monitoreo Hexagon Mining.
            </div>

            {/* --- SECCIÓN ALERTAS DE SISTEMA (Inesperado) - Moved to bottom if needed, but keeping structure --- */}

            <div className="mb-6 flex justify-between items-center px-1">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-white rounded shadow-sm text-slate-600 border border-slate-200">
                        <Activity className="w-6 h-6" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-slate-800 flex items-center gap-2">
                            Monitoreo FMS {site.alias} <span className="text-slate-400 font-normal text-sm">(Centinela) v3.3-DEBUG</span>
                        </h1>
                    </div>
                </div>
                {/* Notification Mockup - Just the time */}
                <div className="text-right flex flex-col items-end">
                    <span className="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{new Date().toLocaleTimeString()}</span>
                </div>
            </div>

            {/* Summary Cards Row */}
            <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                {/* JAMS: app.jams.version */}
                <div className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#06b6d4] flex items-center gap-3">
                    <div className="p-2 bg-[#ecfeff] rounded text-[#06b6d4]"><Terminal size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">JAMS</div>
                        <div className="font-bold text-sm truncate w-24">
                            {primaryServer?.app?.['app.jams.version']?.metric_value || '-'}
                        </div>
                    </div>
                </div>

                {/* Summarizer: Check app.summarizer.crontab -> Show "Service & Crontab" if exists. Click -> app.summarizer.log */}
                <div
                    onClick={() => openTerminal('Summarizer Log', primaryServer?.app?.['app.summarizer.log']?.metric_value || 'No Log Data', primaryServer?.info?.id, 'app.summarizer.log')}
                    className={cn(
                        "bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all",
                        primaryServer?.app?.['app.summarizer.status']?.status === 'danger' || primaryServer?.app?.['app.summarizer.log']?.status === 'danger' ? "border-l-red-500" :
                            (primaryServer?.app?.['app.summarizer.log']?.status === 'warning' ? "border-l-amber-500" : "border-l-emerald-500")
                    )}>
                    <div className={cn(
                        "p-2 rounded",
                        primaryServer?.app?.['app.summarizer.status']?.status === 'danger' || primaryServer?.app?.['app.summarizer.log']?.status === 'danger' ? "bg-red-50 text-red-500" :
                            (primaryServer?.app?.['app.summarizer.log']?.status === 'warning' ? "bg-amber-50 text-amber-500" : "bg-emerald-50 text-emerald-500")
                    )}><Activity size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">SUMMARIZER</div>
                        <div className={cn(
                            "font-bold text-sm",
                            primaryServer?.app?.['app.summarizer.status']?.status === 'danger' || primaryServer?.app?.['app.summarizer.log']?.status === 'danger' ? "text-red-600" :
                                (primaryServer?.app?.['app.summarizer.log']?.status === 'warning' ? "text-amber-600" : "text-emerald-500")
                        )}>
                            {(() => {
                                const hasService = !!primaryServer?.app?.['app.summarizer.service']?.metric_value && !primaryServer?.app?.['app.summarizer.service']?.metric_value.includes('stopped');
                                const hasCrontab = !!primaryServer?.app?.['app.summarizer.crontab']?.metric_value && !primaryServer?.app?.['app.summarizer.crontab']?.metric_value.includes('stopped');
                                if (hasService && hasCrontab) return 'Servicio & Crontab';
                                if (hasService) return 'Servicio';
                                if (hasCrontab) return 'Crontab';
                                return 'Detenido';
                            })()}
                        </div>
                    </div>
                </div>

                {/* Equipos: app.repc -> Count lines */}
                <div
                    onClick={() => openTerminal('Equipos Conectados', primaryServer?.app?.['app.repc']?.metric_value || 'No Data', primaryServer?.info?.id, 'app.repc')}
                    className={cn(
                        "bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all",
                        primaryServer?.app?.['app.repc']?.status === 'danger' ? "border-l-red-500" : (primaryServer?.app?.['app.repc']?.status === 'warning' ? "border-l-amber-500" : "border-l-blue-500")
                    )}>
                    <div className={cn(
                        "p-2 rounded",
                        primaryServer?.app?.['app.repc']?.status === 'danger' ? "bg-red-50 text-red-500" : (primaryServer?.app?.['app.repc']?.status === 'warning' ? "bg-amber-50 text-amber-500" : "bg-blue-50 text-blue-500")
                    )}><Database size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Equipos Conectados</div>
                        <div className={cn("font-bold text-sm", primaryServer?.app?.['app.repc']?.status === 'danger' ? "text-red-500" : (primaryServer?.app?.['app.repc']?.status === 'warning' ? "text-amber-500" : ""))}>
                            {(() => {
                                const val = primaryServer?.app?.['app.repc']?.metric_value;
                                if (!val) return '0';
                                return val.split('\n').filter((x: string) => x.trim().length > 0).length;
                            })()}
                        </div>
                    </div>
                </div>

                {/* Estacion Base: app.station.ping -> Analyze ping */}
                <div
                    onClick={() => openTerminal('Estación Base Ping', primaryServer?.app?.['app.station.ping']?.metric_value || 'No Data', primaryServer?.info?.id, 'app.station.ping')}
                    className={cn(
                        "bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all",
                        primaryServer?.app?.['app.station.ping']?.status === 'danger' ? "border-l-red-500" : "border-l-violet-500"
                    )}>
                    <div className={cn(
                        "p-2 rounded",
                        primaryServer?.app?.['app.station.ping']?.status === 'danger' ? "bg-red-50 text-red-500" : "bg-violet-50 text-violet-500"
                    )}><ShieldCheck size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Estación Base</div>
                        <div className="font-bold text-sm text-slate-700">
                            {(() => {
                                const val = primaryServer?.app?.['app.station.ping']?.metric_value || '';
                                const isOk = val.includes('bytes from') || val.includes('0% packet loss') || primaryServer?.app?.['app.station.ping']?.status === 'ok';
                                return isOk ? <span className="text-emerald-600 truncate block w-24">{primaryServer?.app?.['app.station.name']?.metric_value || 'Online'}</span> : <span className="text-red-500">Offline</span>;
                            })()}
                        </div>
                    </div>
                </div>





                <div
                    onClick={() => openTerminal('Daily Backup Log', secondaryServer?.app?.['backup.daily.log']?.metric_value || 'No Log Data', secondaryServer?.info?.id, 'backup.daily.log')}
                    className={cn(
                        "bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all",
                        secondaryServer?.app?.['backup.daily.status']?.status === 'danger' ? "border-l-red-500" :
                            (secondaryServer?.app?.['backup.daily.status']?.status === 'warning' ? "border-l-amber-500" : "border-l-sky-500")
                    )}>
                    <div className={cn(
                        "p-2 rounded",
                        secondaryServer?.app?.['backup.daily.status']?.status === 'danger' ? "bg-red-50 text-red-500" :
                            (secondaryServer?.app?.['backup.daily.status']?.status === 'warning' ? "bg-amber-50 text-amber-500" : "bg-sky-50 text-sky-500")
                    )}><Clock size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">DAILY</div>
                        {(() => {
                            const { name, time } = parseBackupStatus(secondaryServer?.app?.['backup.daily.status']?.metric_value);
                            return (
                                <div className="leading-tight">
                                    <div className="font-bold text-[10px] truncate w-24" title={name}>{name}</div>
                                    {time ? (
                                        <div className={cn(
                                            "text-[9px] font-mono",
                                            secondaryServer?.app?.['backup.daily.status']?.status === 'danger' ? "text-red-600 font-black" :
                                                (secondaryServer?.app?.['backup.daily.status']?.status === 'warning' ? "text-amber-600 font-bold" : "text-slate-400")
                                        )}>
                                            creado hace : <TimeAgo timestamp={time} />
                                        </div>
                                    ) : <div className="text-[9px] text-slate-400">-</div>}
                                </div>
                            );
                        })()}
                    </div>
                </div>

                <div
                    onClick={() => openTerminal('Hourly Backup Log', secondaryServer?.app?.['backup.hourly.log']?.metric_value || 'No Log Data', secondaryServer?.info?.id, 'backup.hourly.log')}
                    className={cn(
                        "bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all",
                        secondaryServer?.app?.['backup.hourly.status']?.status === 'danger' ? "border-l-red-500" :
                            (secondaryServer?.app?.['backup.hourly.status']?.status === 'warning' ? "border-l-amber-500" : "border-l-sky-500")
                    )}>
                    <div className={cn(
                        "p-2 rounded",
                        secondaryServer?.app?.['backup.hourly.status']?.status === 'danger' ? "bg-red-50 text-red-500" :
                            (secondaryServer?.app?.['backup.hourly.status']?.status === 'warning' ? "bg-amber-50 text-amber-500" : "bg-sky-50 text-sky-500")
                    )}><Clock size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">HOURLY</div>
                        {(() => {
                            const { name, time } = parseBackupStatus(secondaryServer?.app?.['backup.hourly.status']?.metric_value);
                            return (
                                <div className="leading-tight">
                                    <div className="font-bold text-[10px] truncate w-24" title={name}>{name}</div>
                                    {time ? (
                                        <div className={cn(
                                            "text-[9px] font-mono",
                                            secondaryServer?.app?.['backup.hourly.status']?.status === 'danger' ? "text-red-600 font-black" :
                                                (secondaryServer?.app?.['backup.hourly.status']?.status === 'warning' ? "text-amber-600 font-bold" : "text-slate-400")
                                        )}>
                                            creado hace : <TimeAgo timestamp={time} />
                                        </div>
                                    ) : <div className="text-[9px] text-slate-400">-</div>}
                                </div>
                            );
                        })()}
                    </div>
                </div>
            </div>

            {/* Simplified Content Row: Servers Only -> NOW 3 COLUMNS */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 h-fit">
                {/* Primary Server */}
                <div className="bg-white rounded-xl shadow-lg overflow-hidden border border-slate-200 flex flex-col ring-2 ring-[#0ea5e9]/5">
                    <div className="bg-[#0284c7] text-white px-5 py-1.5 flex justify-between items-center shadow-sm relative z-10 transition-all">
                        <span className="text-[11px] font-bold tracking-tight uppercase opacity-90">Servidor Primario</span>
                        <span className="text-[9px] bg-[#0ea5e9] px-2 py-0.5 rounded-md font-bold shadow-sm uppercase">Active</span>
                    </div>

                    <div className={cn("p-3 flex-1 flex flex-col gap-3", primaryServer?.info?.status === 0 && "opacity-50 grayscale")}>
                        {/* Main Content Area: Side-by-Side Gauges and Large Chart */}
                        <div className="flex flex-col xl:flex-row gap-4 items-stretch">
                            {/* Gauges Column - Increased width for harmony */}
                            <div className="flex flex-row gap-4 items-start justify-center xl:justify-start px-0 flex-none w-[220px]">
                                {/* DISK SECTION */}
                                {(() => {
                                    const used = parseFloat(String(primaryServer?.system?.disk_used ?? '0'));
                                    const tot = parseFloat(String(primaryServer?.system?.disk_total ?? '1'));
                                    const pct = primaryServer?.system?.disk_percent ?? 0;
                                    const status = primaryServer?.system?.disk_status;
                                    const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                    return (
                                        <CircularUsage
                                            percentage={pct}
                                            label="Disco"
                                            sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                            color={color}
                                            buttonLabel="Particiones"
                                            onClick={() => openTerminal('Particiones / Disco', primaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No Data', primaryServer?.info?.id, 'particionesDiscoDuro')}
                                        />
                                    );
                                })()}

                                {(() => {
                                    const used = parseFloat(String(primaryServer?.system?.ram_used ?? '0'));
                                    const tot = parseFloat(String(primaryServer?.system?.ram_total ?? '1'));
                                    const pct = primaryServer?.system?.ram_percent ?? 0;
                                    const status = primaryServer?.system?.ram_status;
                                    const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                    return (
                                        <CircularUsage
                                            percentage={pct}
                                            label="RAM"
                                            sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                            color={color}
                                            buttonLabel="Crontab"
                                            onClick={() => openTerminal('Crontab', primaryServer?.app?.['crontab']?.metric_value || 'No Data', primaryServer?.info?.id, 'crontab')}
                                        />
                                    );
                                })()}
                            </div>

                            {/* CPU History Graph Area */}
                            <div className="flex-1 min-h-[180px] relative border border-slate-100 rounded-xl bg-slate-50/50 overflow-hidden">
                                <div className="absolute top-3 right-4 z-10 flex items-center gap-2">
                                    <div className={cn(
                                        "text-[10px] font-black px-2 py-1 rounded-full border shadow-sm transition-colors",
                                        primaryServer?.system?.cpu_status === 'danger' ? "text-red-600 bg-red-50 border-red-200" :
                                            (primaryServer?.system?.cpu_status === 'warning' ? "text-amber-600 bg-amber-50 border-amber-200" : "text-emerald-700 bg-emerald-50 border-emerald-200")
                                    )}>
                                        Load: {primaryServer?.system?.cpu_usage || '0.0'}
                                    </div>
                                    <div className={cn(
                                        "w-4 h-4 transition-colors",
                                        primaryServer?.system?.cpu_status === 'danger' ? "text-red-400" :
                                            (primaryServer?.system?.cpu_status === 'warning' ? "text-amber-400" : "text-emerald-400")
                                    )}>
                                        <Activity size={14} />
                                    </div>
                                </div>
                                {(() => {
                                    const cpuStatus = primaryServer?.system?.cpu_status;
                                    const cpuColor = cpuStatus === 'danger' ? '#ef4444' : (cpuStatus === 'warning' ? '#f59e0b' : '#10b981');
                                    return (
                                        <ResponsiveContainer width="100%" height="100%">
                                            <AreaChart data={livePrimaryHistory} margin={{ top: 20, right: 10, left: -20, bottom: 5 }}>
                                                <defs>
                                                    <linearGradient id="colorCpuP" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="5%" stopColor={cpuColor} stopOpacity={0.4} />
                                                        <stop offset="95%" stopColor={cpuColor} stopOpacity={0} />
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" vertical={true} stroke="#e2e8f0" />
                                                <XAxis dataKey="created_at" hide />
                                                <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                                                <Area type="monotone" dataKey="cpu_usage" stroke={cpuColor} strokeWidth={2.5} fill="url(#colorCpuP)" isAnimationActive={false} />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    );
                                })()}
                            </div>
                        </div>

                        {/* Footer Details: Restored 4-Column Grid */}
                        <div className="border-t border-slate-100 pt-3 grid grid-cols-4 gap-2">
                            <div>
                                <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Server</div>
                                <div className="text-[11px] font-bold text-slate-700 truncate" title={primaryServer?.app?.['system.name']?.metric_value || primaryServer?.app?.['system.hostname']?.metric_value || primaryServer?.app?.['hostname']?.metric_value || primaryServer?.info?.name}>
                                    {primaryServer?.app?.['system.name']?.metric_value || primaryServer?.app?.['system.hostname']?.metric_value || primaryServer?.app?.['hostname']?.metric_value || primaryServer?.info?.name}
                                </div>
                            </div>
                            <div>
                                <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">IP</div>
                                <div className="text-[11px] font-bold text-slate-600 tracking-tight">{primaryServer?.info?.ip_address}</div>
                            </div>
                            <div>
                                <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Status</div>
                                <div className="text-[11px] font-black text-emerald-600 uppercase tracking-tighter">
                                    {primaryServer?.app?.['app.jams.status']?.metric_value || 'ACTIVE'}
                                </div>
                            </div>
                            <div>
                                <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Cluster</div>
                                <div className="text-[11px] font-bold text-slate-700 truncate">{primaryServer?.app?.['app.fms.cluster']?.metric_value || 'None'}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Secondary Server */}
                <div className="bg-white rounded-xl shadow-lg overflow-hidden border border-slate-200 flex flex-col">
                    <div className="bg-[#0284c7] text-white px-5 py-1.5 flex justify-between items-center shadow-sm relative z-10 transition-all">
                        <span className="text-[11px] font-bold tracking-tight uppercase opacity-90">Servidor Secundario</span>
                        <span className="text-[9px] bg-slate-400/50 px-2 py-0.5 rounded-md font-bold shadow-sm uppercase">Backup</span>
                    </div>

                    <div className={cn("p-3 flex-1 flex flex-col gap-3", (!secondaryServer || secondaryServer?.info?.status === 0) && "opacity-50 grayscale")}>
                        {secondaryServer ? (
                            <>
                                <div className="flex flex-col xl:flex-row gap-4 items-stretch">
                                    <div className="flex flex-row gap-4 items-start justify-center xl:justify-start px-0 flex-none w-[220px]">
                                        {(() => {
                                            const used = parseFloat(String(secondaryServer?.system?.disk_used ?? '0'));
                                            const tot = parseFloat(String(secondaryServer?.system?.disk_total ?? '1'));
                                            const pct = secondaryServer?.system?.disk_percent ?? 0;
                                            const status = secondaryServer?.system?.disk_status;
                                            const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                            return (
                                                <CircularUsage
                                                    percentage={pct}
                                                    label="Disco"
                                                    sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                                    color={color}
                                                    buttonLabel="Particiones"
                                                    onClick={() => openTerminal('Particiones / Disco', secondaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No Data', secondaryServer?.info?.id, 'particionesDiscoDuro')}
                                                />
                                            );
                                        })()}

                                        {(() => {
                                            const used = parseFloat(String(secondaryServer?.system?.ram_used ?? '0'));
                                            const tot = parseFloat(String(secondaryServer?.system?.ram_total ?? '1'));
                                            const pct = secondaryServer?.system?.ram_percent ?? 0;
                                            const status = secondaryServer?.system?.ram_status;
                                            const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                            return (
                                                <CircularUsage
                                                    percentage={pct}
                                                    label="RAM"
                                                    sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                                    color={color}
                                                    buttonLabel="Crontab"
                                                    onClick={() => openTerminal('Crontab', secondaryServer?.app?.['crontab']?.metric_value || 'No Data', secondaryServer?.info?.id, 'crontab')}
                                                />
                                            );
                                        })()}
                                    </div>

                                    <div className="flex-1 min-h-[180px] relative border border-slate-100 rounded-xl bg-slate-50/50 overflow-hidden">
                                        <div className="absolute top-3 right-4 z-10 flex items-center gap-2">
                                            <div className={cn(
                                                "text-[10px] font-black px-2 py-1 rounded-full border shadow-sm transition-colors",
                                                secondaryServer?.system?.cpu_status === 'danger' ? "text-red-600 bg-red-50 border-red-200" :
                                                    (secondaryServer?.system?.cpu_status === 'warning' ? "text-amber-600 bg-amber-50 border-amber-200" : "text-emerald-700 bg-emerald-50 border-emerald-200")
                                            )}>
                                                Load: {secondaryServer?.system?.cpu_usage || '0.0'}
                                            </div>
                                            <div className={cn(
                                                "w-4 h-4 transition-colors",
                                                secondaryServer?.system?.cpu_status === 'danger' ? "text-red-400" :
                                                    (secondaryServer?.system?.cpu_status === 'warning' ? "text-amber-400" : "text-emerald-400")
                                            )}>
                                                <Activity size={14} />
                                            </div>
                                        </div>
                                        {(() => {
                                            const cpuStatus = secondaryServer?.system?.cpu_status;
                                            const cpuColor = cpuStatus === 'danger' ? '#ef4444' : (cpuStatus === 'warning' ? '#f59e0b' : '#10b981');
                                            return (
                                                <ResponsiveContainer width="100%" height="100%">
                                                    <AreaChart data={liveSecondaryHistory} margin={{ top: 20, right: 10, left: -20, bottom: 5 }}>
                                                        <defs>
                                                            <linearGradient id="colorCpuS" x1="0" y1="0" x2="0" y2="1">
                                                                <stop offset="5%" stopColor={cpuColor} stopOpacity={0.4} />
                                                                <stop offset="95%" stopColor={cpuColor} stopOpacity={0} />
                                                            </linearGradient>
                                                        </defs>
                                                        <CartesianGrid strokeDasharray="3 3" vertical={true} stroke="#e2e8f0" />
                                                        <XAxis dataKey="created_at" hide />
                                                        <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                                                        <Area type="monotone" dataKey="cpu_usage" stroke={cpuColor} strokeWidth={2.5} fill="url(#colorCpuS)" isAnimationActive={false} />
                                                    </AreaChart>
                                                </ResponsiveContainer>
                                            );
                                        })()}
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-3 grid grid-cols-4 gap-2">
                                    <div>
                                        <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Server</div>
                                        <div className="text-[11px] font-bold text-slate-700 truncate" title={secondaryServer?.app?.['system.name']?.metric_value || secondaryServer?.app?.['system.hostname']?.metric_value || secondaryServer?.app?.['hostname']?.metric_value || secondaryServer?.info?.name}>
                                            {secondaryServer?.app?.['system.name']?.metric_value || secondaryServer?.app?.['system.hostname']?.metric_value || secondaryServer?.app?.['hostname']?.metric_value || secondaryServer?.info?.name}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">IP</div>
                                        <div className="text-[11px] font-bold text-slate-600 tracking-tight">{secondaryServer?.info?.ip_address}</div>
                                    </div>
                                    <div>
                                        <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Status</div>
                                        <div className="text-[11px] font-bold text-slate-500 uppercase tracking-tighter">
                                            {secondaryServer?.app?.['app.jams.status']?.metric_value || 'BACKUP'}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Cluster</div>
                                        <div className="text-[11px] font-bold text-slate-700 truncate">{secondaryServer?.app?.['app.fms.cluster']?.metric_value || 'None'}</div>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <div className="flex-1 flex flex-col items-center justify-center text-slate-400 gap-2">
                                <Activity className="w-12 h-12 opacity-20" />
                                <div className="font-bold text-sm">Esperando conexión de servidor secundario...</div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Servicios y Scripts Section */}
                <div className="bg-white rounded-xl shadow-lg border border-slate-200 flex flex-col">
                    <div className="bg-[#0284c7] text-white px-5 py-1.5 flex justify-between items-center shadow-sm relative z-10 transition-all">
                        <span className="text-[11px] font-bold tracking-tight uppercase opacity-90">Servicios y Scripts</span>
                    </div>

                    <div className="p-4 flex-1 flex flex-col gap-4">
                        {/* JAMS Cluster Health Row */}
                        <div className="grid grid-cols-2 gap-3">
                            <div
                                onClick={() => openTerminal('JAMS Service Status', primaryServer?.app?.['app.jams.service']?.metric_value || 'No Data', primaryServer?.info?.id, 'app.jams.service')}
                                className="cursor-pointer group bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 transition-all hover:bg-emerald-50 hover:shadow-md flex flex-col justify-between h-[110px]"
                            >
                                <div>
                                    <div className="text-[#15803d] font-black text-[10px] uppercase truncate mb-0.5">
                                        JAMS {primaryServer?.app?.['system.name']?.metric_value || primaryServer?.app?.['system.hostname']?.metric_value || primaryServer?.app?.['hostname']?.metric_value || primaryServer?.info?.name}
                                    </div>
                                    <div className="text-emerald-600 text-[9px] font-bold uppercase tracking-wider">Active</div>
                                </div>
                                <div className="text-[#166534] text-[9px] font-black opacity-80 uppercase tracking-tighter pt-1.5 border-t border-emerald-100 flex justify-between items-center">
                                    <span>Reinicios (24h):</span>
                                    <span className="text-emerald-700 bg-emerald-200/50 px-1.5 rounded-full font-bold">
                                        {(() => {
                                            const val = primaryServer?.app?.['app.jams.restarts_log']?.metric_value ||
                                                primaryServer?.app?.['app.jams.restart']?.metric_value;
                                            if (!val) return '0';
                                            return val.split('\n').filter((l: string) => l.trim().length > 0).length;
                                        })()}
                                    </span>
                                </div>
                            </div>

                            <div
                                onClick={() => openTerminal('JAMS Service Status', secondaryServer?.app?.['app.jams.service']?.metric_value || 'No Data', secondaryServer?.info?.id, 'app.jams.service')}
                                className="cursor-pointer group bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 transition-all hover:bg-emerald-50 hover:shadow-md flex flex-col h-[110px]"
                            >
                                <div>
                                    <div className="text-[#15803d] font-black text-[10px] uppercase truncate mb-0.5">
                                        JAMS {secondaryServer?.app?.['system.name']?.metric_value || secondaryServer?.app?.['system.hostname']?.metric_value || secondaryServer?.app?.['hostname']?.metric_value || secondaryServer?.info?.name}
                                    </div>
                                    <div className="text-slate-400 text-[9px] font-bold uppercase tracking-wider">Backup</div>
                                </div>
                            </div>
                        </div>

                        {/* Critical Services Status Grid */}
                        <div className="grid grid-cols-4 gap-2">
                            <div
                                onClick={() => openTerminal('Scripts Activos', primaryServer?.app?.['app.fms.active_scripts']?.metric_value || 'No hay scripts activos', primaryServer?.info?.id, 'app.fms.active_scripts')}
                                className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px] cursor-pointer"
                            >
                                <div className="absolute -bottom-1 -right-1 opacity-5 text-emerald-900 group-hover:scale-110 transition-transform"><Activity size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">SCRIPTS</div>
                                <div className="text-xl font-black text-[#166534] z-10">
                                    {(() => {
                                        const val = primaryServer?.app?.['app.fms.active_scripts']?.metric_value;
                                        if (!val) return '0';
                                        if (!isNaN(Number(val))) return val;
                                        return val.split('\n').filter((l: string) => l.trim().length > 0 && l.includes('/')).length;
                                    })()}
                                </div>
                            </div>

                            <div
                                onClick={() => openTerminal('Replica Log', primaryServer?.app?.['app.fms.replica']?.metric_value || 'No hay replicas activas', primaryServer?.info?.id, 'app.fms.replica')}
                                className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px] cursor-pointer"
                            >
                                <div className="absolute -bottom-1 -right-1 opacity-10 text-emerald-900 group-hover:scale-110 transition-transform"><Database size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">REPLICAS</div>
                                <div className={clsx(
                                    "text-xl font-black z-10",
                                    primaryServer?.app?.['app.fms.replica']?.status === 'danger' ? 'text-red-600 animate-pulse' : 'text-[#166534]'
                                )}>
                                    {(() => {
                                        const val = primaryServer?.app?.['app.fms.replica']?.metric_value;
                                        if (!val) return '0';
                                        return val.split('\n').filter((l: string) => l.trim().length > 0).length;
                                    })()}
                                </div>
                            </div>

                            <div
                                onClick={() => openTerminal('Reconciliador Log', primaryServer?.app?.['app.jams.reconcilie']?.metric_value || 'No Log Data', primaryServer?.info?.id, 'app.jams.reconcilie')}
                                className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px] cursor-pointer"
                            >
                                <div className="absolute -bottom-1 -right-1 opacity-10 text-emerald-900 group-hover:scale-110 transition-transform"><ShieldCheck size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">RECON</div>
                                <span className={clsx(
                                    "text-[8px] font-black uppercase tracking-tighter z-10",
                                    primaryServer?.app?.['app.jams.reconcilie']?.status === 'danger' ? 'text-red-600' :
                                        primaryServer?.app?.['app.jams.reconcilie']?.status === 'warning' ? 'text-yellow-600' :
                                            'text-emerald-600'
                                )}>
                                    {primaryServer?.app?.['app.jams.reconcilie']?.status === 'danger' ? 'Error' :
                                        primaryServer?.app?.['app.jams.reconcilie']?.status === 'warning' ? 'Warning' :
                                            (primaryServer?.app?.['app.jams.reconcilie']?.status || 'Active')}
                                </span>
                            </div>

                            <div className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px]">
                                <div className="absolute -bottom-1 -right-1 opacity-10 text-emerald-900 group-hover:scale-110 transition-transform"><Clock size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">NTP</div>
                                <span className="text-[8px] font-black text-emerald-600 uppercase tracking-tighter z-10">
                                    {(() => {
                                        const val = primaryServer?.app?.['system.ntp.status']?.metric_value || '';
                                        return val.toLowerCase().includes('ntpd') || val.trim().length > 20 ? 'Active' : (val || 'Active');
                                    })()}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* NEW SECTION: Files & DB Overview */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6 items-stretch">
                {/* 1. Large Files Explorer */}
                <div className="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden flex flex-col h-full">
                    <div className="bg-[#0284c7] text-white px-5 py-2.5 flex justify-between items-center shadow-sm relative z-10 shrink-0">
                        <div className="flex items-center gap-2">
                            <FolderOpen size={18} className="text-white/80" />
                            <h2 className="text-sm font-black tracking-tight uppercase leading-none">Archivos y Log Pesados (Sobre 1 GB)</h2>
                        </div>
                    </div>
                    <div className="flex-1 bg-slate-50/30 overflow-auto custom-scrollbar">
                        <FileTree rawData={primaryServer?.app?.['system.files.largest']?.metric_value} />
                    </div>
                </div>

                {/* 2. Database Intelligence */}
                <div className="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden flex flex-col h-full min-h-[500px]">
                    <div className="bg-[#0284c7] text-white px-5 py-2.5 flex justify-between items-center shadow-sm">
                        <div className="flex items-center gap-2">
                            <Database size={18} className="text-white/80" />
                            <h2 className="text-sm font-black tracking-tight uppercase leading-none">DataBase</h2>
                        </div>
                    </div>

                    <div className="p-4 space-y-5">
                        {/* A. Active/Backup Status Boxes */}
                        <div className="grid grid-cols-2 gap-3">
                            {/* Active Card */}
                            <div className="bg-white border-2 border-slate-100 rounded-xl p-3 flex gap-4 items-center shadow-sm">
                                <div className="p-3 bg-blue-50 text-blue-600 rounded-2xl ring-4 ring-blue-50/50 shadow-inner">
                                    <Database size={24} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-1.5 mb-0.5">
                                        <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Activo</span>
                                    </div>
                                    <div className="text-xl font-black text-slate-800 leading-none mb-0.5">
                                        {primaryServer?.app?.['db.size']?.metric_value || '0 MB'}
                                    </div>
                                </div>
                            </div>

                            {/* Backup Card */}
                            <div className="bg-white border-2 border-slate-100 rounded-xl p-3 flex gap-4 items-center shadow-sm">
                                <div className="p-3 bg-violet-50 text-violet-600 rounded-2xl ring-4 ring-violet-50/50 shadow-inner">
                                    <Database size={24} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-1.5 mb-0.5">
                                        <div className="w-1.5 h-1.5 rounded-full bg-violet-400" />
                                        <span className="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Backup</span>
                                    </div>
                                    <div className="text-xl font-black text-slate-800 leading-none mb-1">
                                        {secondaryServer?.app?.['db.size']?.metric_value || '0 MB'}
                                    </div>
                                    {secondaryServer?.app?.['db.schema.date']?.metric_value && (
                                        <div className="text-[9px] font-black text-slate-400 tracking-tighter">
                                            backup_at (S): <span className="text-violet-500">{secondaryServer?.app?.['db.schema.date']?.metric_value}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* B. Max ID Progress Row */}
                        {(() => {
                            const raw = primaryServer?.app?.['db.max_id_table']?.metric_value;
                            if (!raw) return <div className="bg-slate-50 border border-slate-100 rounded-2xl p-4 text-center text-xs italic text-slate-400">Sin datos de ID</div>;
                            const [table, pk] = raw.split('|');
                            const maxValue = 2147483647;
                            const gap = maxValue - parseInt(pk);
                            // Correct progress calculation restored
                            const progress = (parseInt(pk) / maxValue) * 100;
                            const isDanger = gap <= 50000000;
                            const isWarning = gap <= 100000000;

                            return (
                                <div className={cn(
                                    "border rounded-2xl p-4 transition-all duration-500 relative overflow-hidden",
                                    isDanger ? "bg-red-600 border-red-700 text-white shadow-[0_0_30px_rgba(220,38,38,0.6)]" :
                                        isWarning ? "bg-amber-400 border-amber-500 text-amber-950" :
                                            "bg-slate-50 border-slate-100 text-slate-700"
                                )}>
                                    {isDanger && <div className="absolute inset-0 bg-red-500 animate-pulse opacity-20 pointer-events-none" />}
                                    <table className="w-full relative z-10">
                                        <thead>
                                            <tr className={cn("text-[10px] uppercase font-black tracking-widest text-left", isDanger ? "text-white/70" : "text-slate-400")}>
                                                <th className="pb-3 px-1">Tabla</th>
                                                <th className="pb-3 px-1">PK</th>
                                                <th className="pb-3 px-1">Max. Val</th>
                                                <th className="pb-3 px-1 text-right">Gap</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr className="text-sm font-black">
                                                <td className="py-1 px-1">{table}</td>
                                                <td className="py-1 px-1 tabular-nums ">{Number(pk).toLocaleString()}</td>
                                                <td className={cn("py-1 px-1 tabular-nums", isDanger ? "text-white/60" : "text-slate-400")}>2.147.483.647</td>
                                                <td className={cn("py-1 px-1 tabular-nums text-right font-black", isDanger ? "text-white" : isWarning ? "text-amber-900" : "text-emerald-600")}>
                                                    {gap.toLocaleString()}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colSpan={4} className="pt-2 px-1">
                                                    <div className={cn("h-2.5 bg-black/10 rounded-full overflow-hidden border", isDanger ? "border-white/30" : "border-slate-200")}>
                                                        <div
                                                            className={cn("h-full transition-all duration-1000 shadow-sm", isDanger ? "bg-white shadow-[0_0_15px_white]" : isWarning ? "bg-amber-700" : "bg-emerald-500")}
                                                            style={{ width: `${progress}%` }}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            );
                        })()}

                        {/* C. Metrics Health Grid */}
                        <div className="grid grid-cols-4 gap-2">
                            {(() => {
                                const idleVal = (() => {
                                    const val = primaryServer?.app?.['db.idle_queries']?.metric_value || '';
                                    return val.split('\n').filter((l: string) => l.includes('|')).length;
                                })();
                                const isDanger = idleVal > 20;
                                const isWarning = idleVal > 10;

                                return (
                                    <DBHealthCard
                                        label="Idle Queries"
                                        value={idleVal}
                                        sublabel="TOTAL IDLE"
                                        color={isDanger ? { bg: 'bg-red-600', text: 'text-white' } :
                                            isWarning ? { bg: 'bg-amber-400', text: 'text-amber-950' } :
                                                { bg: 'bg-blue-50', text: 'text-blue-600' }}
                                        icon={Activity}
                                        onClick={() => openTerminal('Database Idle Queries', primaryServer?.app?.['db.idle_queries']?.metric_value || 'No hay consultas en idle.', primaryServer?.info?.id, 'db.idle_queries')}
                                    />
                                );
                            })()}

                            <DBHealthCard
                                label="Tabla mas pesada"
                                value={(() => {
                                    const val = primaryServer?.app?.['db.top_ten_tables']?.metric_value;
                                    if (!val) return '-';
                                    const lines = val.split(/\r?\n/).filter((l: string) => l.trim().length > 0);
                                    if (!lines[0]) return '-';

                                    // Handle pipe or space
                                    if (lines[0].includes('|')) return lines[0].split('|')[0];
                                    return lines[0].trim().split(/\s+/)[0];
                                })()}
                                sublabel={(() => {
                                    const val = primaryServer?.app?.['db.top_ten_tables']?.metric_value;
                                    if (!val) return '0 GB';
                                    const lines = val.split(/\r?\n/).filter((l: string) => l.trim().length > 0);
                                    if (!lines[0]) return '0 GB';

                                    let count = 0;
                                    if (lines[0].includes('|')) {
                                        count = parseInt(lines[0].split('|')[1]);
                                    } else {
                                        const parts = lines[0].trim().split(/\s+/);
                                        count = parseInt(parts[parts.length - 1]);
                                    }
                                    return `${(count * 0.0000004).toFixed(2)} GB • ${count.toLocaleString()} reg`;
                                })()}
                                color={{ bg: 'bg-blue-50', text: 'text-blue-600' }}
                                icon={Layers}
                            />

                            <SchemaBackupCard secondaryServer={secondaryServer} />

                            {(() => {
                                const parseMap = (v: any) => {
                                    const res: any = {};
                                    // Support both newline and space separation
                                    const matches = (v || '').match(/[\w_]+\|\d+/g) || [];
                                    matches.forEach((m: string) => {
                                        const [t, c] = m.split('|');
                                        if (t) res[t] = parseInt(c) || 0;
                                    });
                                    return res;
                                };
                                const pMap = parseMap(primaryServer?.app?.['db.tables.shifts']?.metric_value);
                                const sMap = parseMap(secondaryServer?.app?.['db.tables.shifts']?.metric_value);
                                let maxDiff = 0;
                                let maxTab = '-';
                                Object.keys(pMap).forEach(table => {
                                    const diff = Math.abs((pMap[table] || 0) - (sMap[table] || 0));
                                    if (diff > maxDiff) {
                                        maxDiff = diff;
                                        maxTab = table;
                                    }
                                });

                                const isDanger = maxDiff > 30;
                                const isWarning = maxDiff > 10;

                                return (
                                    <DBHealthCard
                                        label="Max Diff"
                                        value={maxTab}
                                        sublabel={maxDiff.toLocaleString() + " diff"}
                                        color={isDanger ? { bg: 'bg-red-600', text: 'text-white' } :
                                            isWarning ? { bg: 'bg-amber-400', text: 'text-amber-950' } :
                                                { bg: 'bg-blue-50', text: 'text-blue-600' }}
                                        icon={AlertTriangle}
                                    />
                                );
                            })()}
                        </div>

                        {/* D. Comparison Tables (Shifts & Top Tables) */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Shift Tables Comparison - CLEAN REWRITE/STRICT */}
                            <div className="bg-slate-50/50 rounded-xl p-3 border border-slate-100 flex flex-col min-h-[100px] mb-6">
                                <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1 border-l-2 border-indigo-500 leading-none">Resumen Comparación Shift Tables</h4>
                                <div className="flex-1 overflow-auto custom-scrollbar pr-2">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="text-[9px] text-slate-400 font-bold uppercase border-b border-slate-200">
                                                <th className="pb-1.5 px-0.5">Tabla</th>
                                                <th className="pb-1.5 px-0.5">P</th>
                                                <th className="pb-1.5 px-0.5">S</th>
                                                <th className="pb-1.5 px-0.5 text-right">Diff</th>
                                            </tr>
                                        </thead>
                                        <tbody className="text-[10px] tabular-nums">
                                            {(() => {
                                                // STRICT REQUIREMENT: Only db.tables.shifts
                                                const rawP = primaryServer?.app?.['db.tables.shifts']?.metric_value || "";
                                                const rawS = secondaryServer?.app?.['db.tables.shifts']?.metric_value || "";

                                                // Clean Parser Function - Extracts "Name|Number" patterns globally
                                                // Handles both multi-line and single-line space-separated formats
                                                const parseShiftTables = (input: string) => {
                                                    const map: Record<string, number> = {};
                                                    if (!input) return map;

                                                    // Regex to find all "TableName|Count" occurrences
                                                    // Ignores all other delimiters (newlines, spaces, etc)
                                                    const matches = input.matchAll(/([a-zA-Z0-9_]+)\|(\d+)/g);

                                                    for (const match of matches) {
                                                        const name = match[1];
                                                        const val = parseInt(match[2]);
                                                        if (name && !isNaN(val)) {
                                                            map[name] = val;
                                                        }
                                                    }
                                                    return map;
                                                };

                                                const pMap = parseShiftTables(rawP);
                                                const sMap = parseShiftTables(rawS);

                                                const keys = Array.from(new Set([...Object.keys(pMap), ...Object.keys(sMap)])).sort();

                                                if (keys.length === 0) {
                                                    return <tr><td colSpan={4} className="text-center py-2 text-xs text-slate-400 italic">Esperando datos (db.tables.shifts)...</td></tr>;
                                                }

                                                return keys.map(k => {
                                                    const pVal = pMap[k] || 0;
                                                    const sVal = sMap[k] || 0;
                                                    const diff = pVal - sVal; // Raw difference

                                                    return (
                                                        <tr key={k} className="border-b border-slate-100/50 hover:bg-white/50">
                                                            <td className="py-1 px-0.5 font-bold text-slate-700">{k}</td>
                                                            <td className="py-1 px-0.5 text-slate-500">{pVal.toLocaleString()}</td>
                                                            <td className="py-1 px-0.5 text-slate-500">{sVal.toLocaleString()}</td>
                                                            <td className={cn("py-1 px-0.5 text-right font-bold", Math.abs(diff) > 0 ? "text-amber-600" : "text-emerald-500")}>
                                                                {Math.abs(diff).toLocaleString()}
                                                            </td>
                                                        </tr>
                                                    );
                                                });
                                            })()}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Top 10 Heaviest Tables (Simulated or Placeholder) */}
                            <div className="bg-slate-50/50 rounded-xl p-3 border border-slate-100 flex flex-col min-h-[220px]">
                                <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-1 border-l-2 border-violet-500 leading-none">Top 10 Tablas mas Pesadas</h4>
                                <div className="flex-1 overflow-auto custom-scrollbar pr-2">
                                    <table className="w-full text-left">
                                        <thead>
                                            <tr className="text-[9px] text-slate-400 font-bold uppercase border-b border-slate-200">
                                                <th className="pb-1.5 px-0.5">Tabla</th>
                                                <th className="pb-1.5 px-0.5 text-right">Registros</th>
                                            </tr>
                                        </thead>
                                        <tbody className="text-[10px] tabular-nums">
                                            {(() => {
                                                const val = primaryServer?.app?.['db.top_ten_tables']?.metric_value;
                                                if (!val) return <tr><td colSpan={2} className="text-center py-2 text-xs text-slate-400 italic">Sin datos</td></tr>;

                                                const lines = val.split(/\r?\n/).filter((l: string) => l.trim().length > 0);
                                                return lines.slice(0, 10).map((line: string) => {
                                                    let table = "Unknown";
                                                    let count = "0";

                                                    if (line.includes('|')) {
                                                        [table, count] = line.split('|');
                                                    } else {
                                                        const parts = line.trim().split(/\s+/);
                                                        count = parts.pop() || "0";
                                                        table = parts.join(' ');
                                                    }

                                                    return (
                                                        <tr key={table} className="border-b border-slate-100/50 group hover:bg-white/40">
                                                            <td className="py-1.5 px-0.5 font-bold text-slate-600 truncate max-w-[120px]" title={table}>{table}</td>
                                                            <td className="py-1.5 px-0.5 text-right font-black text-slate-700">{parseInt(count).toLocaleString()}</td>
                                                        </tr>
                                                    );
                                                });
                                            })()}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <TerminalModal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                title={modalTitle}
                content={modalContent}
                serverId={selectedServerId}
                metricKey={selectedMetricKey}
                metricsData={metricsData}
            />
            <AlertManager initialDataLoaded={!!(metricsData?.servers?.length)} />
        </div >
    );
};

export default MonitoreoSite;
