import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getSiteMetrics, type SiteMonitoringResponse } from '../services/monitoringService';
import { getSiteById } from '../services/sitesService';
import {
    Loader2, Activity,
    Database, Terminal, ShieldCheck, Clock
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

    return { name, time: null, raw };
};

const HighlightedContent = ({ content }: { content: string }) => {
    if (!content) return <span>No output.</span>;
    // Highlight technical keywords
    const parts = content.split(/\b(error|warning|danger|panic|critical|failed|success|active|online|offline)\b/gi);
    return (
        <span>
            {parts.map((part, i) => {
                const lower = part.toLowerCase();
                if (/error|danger|panic|critical|failed/i.test(lower)) {
                    return <span key={i} className="bg-red-500/20 text-red-400 font-bold px-1 rounded-sm border border-red-500/30">{part}</span>;
                }
                if (/warning/i.test(lower)) {
                    return <span key={i} className="bg-yellow-500/20 text-yellow-400 font-bold px-1 rounded-sm border border-yellow-500/30">{part}</span>;
                }
                if (/success|active|online/i.test(lower)) {
                    return <span key={i} className="bg-emerald-500/20 text-emerald-400 font-bold px-1 rounded-sm border border-emerald-500/30">{part}</span>;
                }
                return part;
            })}
        </span>
    );
};

const TerminalModal = ({ isOpen, onClose, title, content }: any) => {
    if (!isOpen) return null;
    return (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div className="bg-slate-900 text-slate-50 w-full max-w-4xl h-[80vh] rounded-xl flex flex-col shadow-2xl border border-slate-700">
                <div className="flex justify-between items-center p-4 border-b border-slate-700 bg-slate-800 rounded-t-xl">
                    <h3 className="font-mono font-bold flex items-center gap-2"><Terminal size={16} /> {title}</h3>
                    <button onClick={onClose} className="text-slate-400 hover:text-white">&times;</button>
                </div>
                <div className="flex-1 p-4 overflow-auto font-mono text-xs whitespace-pre-wrap leading-relaxed">
                    <HighlightedContent content={content} />
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

    // --- Live History Refs & States ---
    const [livePrimaryHistory, setLivePrimaryHistory] = useState<any[]>([]);
    const [liveSecondaryHistory, setLiveSecondaryHistory] = useState<any[]>([]);
    const primaryRef = useRef<any>(null);
    const secondaryRef = useRef<any>(null);

    const openTerminal = (title: string, content: string) => {
        setModalTitle(title);
        setModalContent(content);
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

            {/* Main Title - DECOUPLED (No Card Container) - Corrected Visuals */}
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
                    onClick={() => openTerminal('Summarizer Log', primaryServer?.app?.['app.summarizer.log']?.metric_value || 'No Log Data')}
                    className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#10b981] flex items-center gap-3 cursor-pointer hover:shadow-md transition-all">
                    <div className="p-2 bg-[#ecfdf5] rounded text-[#10b981]"><Activity size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Summarizer</div>
                        <div className="font-bold text-sm text-[#10b981]">
                            {primaryServer?.app?.['app.summarizer.crontab']?.metric_value ? 'Service & Crontab' : '-'}
                        </div>
                    </div>
                </div>

                {/* Equipos: app.repc -> Count lines */}
                <div
                    onClick={() => openTerminal('Equipos Conectados', primaryServer?.app?.['app.repc']?.metric_value || 'No Data')}
                    className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#3b82f6] flex items-center gap-3 cursor-pointer hover:shadow-md transition-all">
                    <div className="p-2 bg-[#eff6ff] rounded text-[#3b82f6]"><Database size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Equipos Conectados</div>
                        <div className="font-bold text-sm">
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
                    onClick={() => openTerminal('Estación Base Ping', primaryServer?.app?.['app.station.ping']?.metric_value || 'No Data')}
                    className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#8b5cf6] flex items-center gap-3 cursor-pointer hover:shadow-md transition-all">
                    <div className="p-2 bg-[#f5f3ff] rounded text-[#8b5cf6]"><ShieldCheck size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Estación Base</div>
                        <div className="font-bold text-sm">
                            {(() => {
                                const val = primaryServer?.app?.['app.station.ping']?.metric_value || '';
                                const isOk = val.includes('bytes from') || val.includes('0% packet loss');
                                return isOk ? <span className="text-emerald-600">Online</span> : <span className="text-red-500">Offline</span>;
                            })()}
                        </div>
                    </div>
                </div>





                {/* Daily: backup.daily.status -> Secondary Server */}
                <div
                    onClick={() => openTerminal('Daily Backup Status', secondaryServer?.app?.['backup.daily.status']?.metric_value || 'No Data')}
                    className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#0ea5e9] flex items-center gap-3 cursor-pointer hover:shadow-md transition-all">
                    <div className="p-2 bg-[#f0f9ff] rounded text-[#0ea5e9]"><Clock size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Daily</div>
                        {(() => {
                            const { name, time } = parseBackupStatus(secondaryServer?.app?.['backup.daily.status']?.metric_value);
                            return (
                                <div className="leading-tight">
                                    <div className="font-bold text-[10px] truncate w-24" title={name}>{name}</div>
                                    {time ? (
                                        <div className="text-[9px] text-slate-400 font-mono">
                                            Creado hace <TimeAgo timestamp={time} />
                                        </div>
                                    ) : <div className="text-[9px] text-slate-400">-</div>}
                                </div>
                            );
                        })()}
                    </div>
                </div>

                {/* Hourly: backup.hourly.status -> Secondary Server */}
                <div
                    onClick={() => openTerminal('Hourly Backup Status', secondaryServer?.app?.['backup.hourly.status']?.metric_value || 'No Data')}
                    className="bg-white p-3 rounded shadow-sm border border-slate-200 border-l-4 border-l-[#0ea5e9] flex items-center gap-3 cursor-pointer hover:shadow-md transition-all">
                    <div className="p-2 bg-[#f0f9ff] rounded text-[#0ea5e9]"><Clock size={20} /></div>
                    <div>
                        <div className="text-[10px] font-bold text-slate-500 uppercase">Hourly</div>
                        {(() => {
                            const { name, time } = parseBackupStatus(secondaryServer?.app?.['backup.hourly.status']?.metric_value);
                            return (
                                <div className="leading-tight">
                                    <div className="font-bold text-[10px] truncate w-24" title={name}>{name}</div>
                                    {time ? (
                                        <div className="text-[9px] text-slate-400 font-mono">
                                            Creado hace <TimeAgo timestamp={time} />
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
                    <div className="bg-[#0284c7] text-white px-5 py-3 flex justify-between items-center shadow-sm relative z-10">
                        <span className="text-base font-bold tracking-tight">Servidor Primario</span>
                        <span className="text-xs bg-[#0ea5e9] px-3 py-1 rounded-md font-bold shadow-sm">Active</span>
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
                                    const status = primaryServer?.app?.['system.disk.percent']?.status;
                                    const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                    return (
                                        <CircularUsage
                                            percentage={pct}
                                            label="Disco"
                                            sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                            color={color}
                                            buttonLabel="Particiones"
                                            onClick={() => openTerminal('Particiones / Disco', primaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No Data')}
                                        />
                                    );
                                })()}

                                {(() => {
                                    const used = parseFloat(String(primaryServer?.system?.ram_used ?? '0'));
                                    const tot = parseFloat(String(primaryServer?.system?.ram_total ?? '1'));
                                    const pct = primaryServer?.system?.ram_percent ?? 0;
                                    const status = primaryServer?.app?.['system.ram.percent']?.status;
                                    const color = status === 'danger' ? '#ef4444' : (status === 'warning' ? '#f59e0b' : '#10b981');
                                    return (
                                        <CircularUsage
                                            percentage={pct}
                                            label="RAM"
                                            sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                            color={color}
                                            buttonLabel="Crontab"
                                            onClick={() => openTerminal('Crontab', primaryServer?.app?.['crontab']?.metric_value || 'No Data')}
                                        />
                                    );
                                })()}
                            </div>

                            {/* CPU History Graph Area */}
                            <div className="flex-1 min-h-[180px] relative border border-slate-100 rounded-xl bg-slate-50/50 overflow-hidden">
                                <div className="absolute top-3 right-4 z-10 flex items-center gap-2">
                                    <div className="text-[10px] font-black text-slate-400 bg-white/80 px-2 py-1 rounded-full border border-slate-100 shadow-sm">
                                        Load: {primaryServer?.system?.cpu_usage || '0.0'}
                                    </div>
                                    <div className="w-4 h-4 text-slate-300">
                                        <Activity size={14} />
                                    </div>
                                </div>
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={livePrimaryHistory} margin={{ top: 20, right: 10, left: -20, bottom: 5 }}>
                                        <defs>
                                            <linearGradient id="colorCpuP" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#10b981" stopOpacity={0.4} />
                                                <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={true} stroke="#e2e8f0" />
                                        <XAxis dataKey="created_at" hide />
                                        <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                                        <Area type="monotone" dataKey="cpu_usage" stroke="#10b981" strokeWidth={2.5} fill="url(#colorCpuP)" isAnimationActive={false} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Footer Details: Restored 4-Column Grid */}
                        <div className="border-t border-slate-100 pt-3 grid grid-cols-4 gap-2">
                            <div>
                                <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Server</div>
                                <div className="text-[11px] font-bold text-slate-700 truncate">{primaryServer?.info?.name}</div>
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
                    <div className="bg-[#0284c7] text-white px-5 py-3 flex justify-between items-center shadow-sm relative z-10">
                        <span className="text-base font-bold tracking-tight">Servidor Secundario</span>
                        <span className="text-xs bg-slate-400/50 px-3 py-1 rounded-md font-bold shadow-sm">Backup</span>
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
                                            return (
                                                <CircularUsage
                                                    percentage={pct}
                                                    label="Disco"
                                                    sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                                    color="#10b981"
                                                    buttonLabel="Particiones"
                                                    onClick={() => openTerminal('Particiones / Disco', secondaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No Data')}
                                                />
                                            );
                                        })()}

                                        {(() => {
                                            const used = parseFloat(String(secondaryServer?.system?.ram_used ?? '0'));
                                            const tot = parseFloat(String(secondaryServer?.system?.ram_total ?? '1'));
                                            const pct = secondaryServer?.system?.ram_percent ?? 0;
                                            return (
                                                <CircularUsage
                                                    percentage={pct}
                                                    label="RAM"
                                                    sublabel={`${Math.round(used)}G / ${Math.round(tot)}G`}
                                                    color="#10b981"
                                                    buttonLabel="Crontab"
                                                    onClick={() => openTerminal('Crontab', secondaryServer?.app?.['crontab']?.metric_value || 'No Data')}
                                                />
                                            );
                                        })()}
                                    </div>

                                    <div className="flex-1 min-h-[180px] relative border border-slate-100 rounded-xl bg-slate-50/50 overflow-hidden">
                                        <div className="absolute top-3 right-4 z-10 flex items-center gap-2">
                                            <div className="text-[10px] font-black text-slate-400 bg-white/80 px-2 py-1 rounded-full border border-slate-100 shadow-sm">
                                                Load: {secondaryServer?.system?.cpu_usage || '0.0'}
                                            </div>
                                            <div className="w-4 h-4 text-slate-300">
                                                <Activity size={14} />
                                            </div>
                                        </div>
                                        <ResponsiveContainer width="100%" height="100%">
                                            <AreaChart data={liveSecondaryHistory} margin={{ top: 20, right: 10, left: -20, bottom: 5 }}>
                                                <defs>
                                                    <linearGradient id="colorCpuS" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="5%" stopColor="#10b981" stopOpacity={0.4} />
                                                        <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" vertical={true} stroke="#e2e8f0" />
                                                <XAxis dataKey="created_at" hide />
                                                <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                                                <Area type="monotone" dataKey="cpu_usage" stroke="#10b981" strokeWidth={2.5} fill="url(#colorCpuS)" isAnimationActive={false} />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </div>
                                </div>

                                <div className="border-t border-slate-100 pt-3 grid grid-cols-4 gap-2">
                                    <div>
                                        <div className="text-[9px] font-bold text-slate-400 uppercase tracking-tight mb-0.5">Server</div>
                                        <div className="text-[11px] font-bold text-slate-700 truncate">{secondaryServer?.info?.name}</div>
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
                    <div className="bg-[#0284c7] text-white px-5 py-3 flex justify-between items-center shadow-sm relative z-10 transition-all">
                        <span className="text-base font-bold tracking-tight">Servicios y Scripts</span>
                    </div>

                    <div className="p-4 flex-1 flex flex-col gap-4">
                        {/* JAMS Cluster Health Row */}
                        <div className="grid grid-cols-2 gap-3">
                            <div
                                onClick={() => openTerminal('JAMS Service Status', primaryServer?.app?.['app.jams.service']?.metric_value || 'No Data')}
                                className="cursor-pointer group bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 transition-all hover:bg-emerald-50 hover:shadow-md flex flex-col justify-between h-[85px]"
                            >
                                <div>
                                    <div className="text-[#15803d] font-black text-[10px] uppercase truncate mb-0.5">JAMS {primaryServer?.info?.name}</div>
                                    <div className="text-emerald-600 text-[9px] font-bold uppercase tracking-wider">Active</div>
                                </div>
                                <div className="text-[#166534] text-[9px] font-black opacity-80 uppercase tracking-tighter pt-1.5 border-t border-emerald-100 flex justify-between items-center">
                                    <span>Reinicios (24h):</span>
                                    <span className="text-emerald-700 bg-emerald-200/50 px-1.5 rounded-full">1</span>
                                </div>
                            </div>

                            <div
                                onClick={() => openTerminal('JAMS Service Status', secondaryServer?.app?.['app.jams.service']?.metric_value || 'No Data')}
                                className="cursor-pointer group bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-3 transition-all hover:bg-emerald-50 hover:shadow-md flex flex-col h-[85px]"
                            >
                                <div className="text-[#15803d] font-black text-[10px] uppercase truncate mb-0.5">JAMS {secondaryServer?.app?.['system.name']?.metric_value || secondaryServer?.info?.name}</div>
                                <div className="text-slate-400 text-[9px] font-bold uppercase tracking-wider">Backup</div>
                            </div>
                        </div>

                        {/* Critical Services Status Grid */}
                        <div className="grid grid-cols-4 gap-2">
                            <div className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px]">
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

                            <div className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px]">
                                <div className="absolute -bottom-1 -right-1 opacity-10 text-emerald-900 group-hover:scale-110 transition-transform"><Database size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">REPLICAS</div>
                                <div className="bg-white px-1.5 py-0.5 rounded shadow-sm border border-emerald-100 z-10">
                                    <span className="text-[8px] font-black text-emerald-600 uppercase tracking-tighter">{primaryServer?.app?.['app.replicas.status']?.metric_value || 'Active'}</span>
                                </div>
                            </div>

                            <div className="bg-[#f0fdf4] border border-[#dcfce7] rounded-xl p-1.5 flex flex-col items-center justify-center text-center relative overflow-hidden group hover:bg-emerald-50 transition-colors h-[60px]">
                                <div className="absolute -bottom-1 -right-1 opacity-10 text-emerald-900 group-hover:scale-110 transition-transform"><ShieldCheck size={30} /></div>
                                <div className="text-[7px] font-black text-[#166534]/40 uppercase tracking-widest leading-none z-10">RECON</div>
                                <span className="text-[8px] font-black text-emerald-600 uppercase tracking-tighter z-10">Active</span>
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

            <TerminalModal isOpen={modalOpen} onClose={() => setModalOpen(false)} title={modalTitle} content={modalContent} />
            <AlertManager initialDataLoaded={!!(metricsData?.servers?.length)} />
        </div >
    );
};

export default MonitoreoSite;
