import { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { getSiteMetrics, type SiteMonitoringResponse } from '../services/monitoringService';
import { getSiteById } from '../services/sitesService';
import { Loader2, Server, Database, Activity, Clock, ShieldCheck, Eye } from 'lucide-react';
import { AreaChart, Area, CartesianGrid, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const COLORS = ['#10b981', '#e5e7eb']; // Emerald-500, Gray-200
const COLORS_WARN = ['#f59e0b', '#e5e7eb'];
const COLORS_DANGER = ['#ef4444', '#e5e7eb'];

// Helper Component for Real-time Time Ago
const TimeAgo = ({ filename }: { filename?: string }) => {
    const [timeString, setTimeString] = useState<string>('Calculando...');

    useEffect(() => {
        if (!filename) return;

        const match = filename.match(/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/);
        // Also try Daily format: jmineops-2026-01-21.tgz 0-06:13:51
        // Or if filename IS the timestamp? No, it's usually "jmineops..."

        let targetDate: Date | null = null;

        if (match) {
            // YYYY-MM-DD_HH-MM-SS
            const parts = match[1].split('_');
            const datePart = parts[0];
            const timePart = parts[1].replace(/-/g, ':');
            targetDate = new Date(`${datePart}T${timePart}`);
        } else {
            // Try daily format YYYY-MM-DD
            const dayMatch = filename.match(/(\d{4}-\d{2}-\d{2})/);
            if (dayMatch) {
                targetDate = new Date(`${dayMatch[1]}T00:00:00`);
            }
        }

        if (!targetDate || isNaN(targetDate.getTime())) {
            if (filename.includes('Ago')) {
                setTimeString(filename); // Fallback if it's already parsed?
                return;
            }
            setTimeString('-');
            return;
        }

        const update = () => {
            const now = new Date();
            const diff = Math.floor((now.getTime() - targetDate!.getTime()) / 1000);

            if (diff < 0) {
                setTimeString('Recién creado');
                return;
            }

            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;

            setTimeString(`${h}h ${m}m ${s}s`);
        };

        update();
        const interval = setInterval(update, 1000);
        return () => clearInterval(interval);
    }, [filename]);

    return <span className="font-mono">{timeString}</span>;
};

const getNTPStatus = (val?: string) => {
    if (!val) return 'No Data';
    if (val.includes('0% packet loss')) return 'Active';
    return 'Warning';
};

const getReplicasCount = (val?: string) => {
    if (!val) return '-';
    // Logic: check process count? Or just "Active" if string exists?
    if (val.includes('sql_backup')) return 'Active';
    return '-';
};

const MonitoreoSite = () => {
    const { id } = useParams<{ id: string }>();
    const [searchParams] = useSearchParams();
    const type = searchParams.get('type') || 'fms';
    const siteId = Number(id);

    const { data: site } = useQuery({
        queryKey: ['site', siteId],
        queryFn: () => getSiteById(siteId),
        enabled: !!siteId
    });

    const { data: metricsData, isLoading } = useQuery<SiteMonitoringResponse>({
        queryKey: ['siteMetrics', siteId, type],
        queryFn: () => getSiteMetrics(siteId, type),
        refetchInterval: 5000,
        enabled: !!siteId
    });

    const [primaryHistory, setPrimaryHistory] = useState<any[]>([]);
    const [secondaryHistory, setSecondaryHistory] = useState<any[]>([]);

    useEffect(() => {
        if (metricsData) {
            const primary = metricsData.servers.find((s: any) => s.info.server_type?.toLowerCase().includes('active') || s.info.server_type?.toLowerCase().includes('primary') || Number(s.info.is_primary) === 1) || metricsData.servers[0];
            const secondary = metricsData.servers.find((s: any) => s !== primary);

            if (primary?.system?.load_average) {
                setPrimaryHistory(prev => {
                    const newVal = { created_at: new Date().toLocaleTimeString(), cpu_usage: Number(primary.system.load_average) };
                    const newHist = [...prev, newVal];
                    return newHist.slice(-20); // Keep last 20
                });
            }
            if (secondary?.system?.load_average) {
                setSecondaryHistory(prev => {
                    const newVal = { created_at: new Date().toLocaleTimeString(), cpu_usage: Number(secondary.system.load_average) };
                    const newHist = [...prev, newVal];
                    return newHist.slice(-20);
                });
            }
        }
    }, [metricsData]);

    if (isLoading || !site) {
        return <div className="flex justify-center items-center h-screen"><Loader2 className="animate-spin w-10 h-10 text-primary" /></div>;
    }

    const primaryServer = metricsData?.servers.find((s: any) => s.info.server_type?.toLowerCase().includes('active') || s.info.server_type?.toLowerCase().includes('primary') || Number(s.info.is_primary) === 1) || metricsData?.servers[0];
    const secondaryServer = metricsData?.servers.find((s: any) => s !== primaryServer); // Simple fallback

    // Helper for Gauge
    const renderGauge = (value: number, total: number, label: string, sublabel: string) => {
        const percent = total > 0 ? (value / total) * 100 : 0;
        const color = percent > 90 ? COLORS_DANGER : (percent > 75 ? COLORS_WARN : COLORS);
        const data = [{ value: value }, { value: total - value }];

        return (
            <div className="flex flex-col items-center">
                <div className="relative w-24 h-24">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                cx="50%"
                                cy="50%"
                                innerRadius={35}
                                outerRadius={45}
                                startAngle={90}
                                endAngle={-270}
                                dataKey="value"
                                stroke="none"
                            >
                                {data.map((_, index) => (
                                    <Cell key={`cell-${index}`} fill={index === 0 ? color[0] : color[1]} />
                                ))}
                            </Pie>
                        </PieChart>
                    </ResponsiveContainer>
                    <div className="absolute inset-0 flex items-center justify-center flex-col">
                        <span className={cn("text-lg font-bold", percent > 90 ? "text-red-500" : (percent > 75 ? "text-amber-500" : "text-emerald-500"))}>
                            {Math.round(percent)}%
                        </span>
                    </div>
                </div>
                <span className="text-sm font-semibold mt-1">{label}</span>
                <span className="text-xs text-muted-foreground">{sublabel}</span>
            </div>
        );
    };

    // Helper functions for Legacy Parsing


    const parseEquipments = (val?: string) => {
        if (!val) return '0';
        // Legacy: words count
        return val.trim().split(/\s+/).filter(p => p !== "").length.toString();
    };

    const parseReconciliator = (val?: string) => {
        if (!val) return { status: 'OK', color: 'text-emerald-500' };
        const issues = val.match(/\b(error|danger|warning|updated|deleted)\b/gi);
        if (issues) return { status: 'Warning', color: 'text-amber-500' };
        return { status: 'Done', color: 'text-emerald-500' };
    };

    const parseSummarizer = (activeServer: any) => {
        // checks summarizerLog, summarizerService, summarizerCrontab
        const log = activeServer?.app?.['summarizerLog']?.metric_value || '';
        const issues = (log as string).match(/\b(error|danger|warning)\b/gi);
        if (issues) return { status: 'Warning', color: 'text-amber-500' };
        return { status: 'Active', color: 'text-emerald-600' };
    };

    const countScripts = (val?: string) => {
        if (!val) return 0;
        return val.split(/\r?\n/).filter(l => l.trim() !== "").length;
    };

    // Advanced Parsers
    const parsePgSize = (val?: string) => {
        if (!val) return '0 MB';
        const match = val.match(/(\d+\s*[GM]B)/);
        return match ? match[1] : '0 MB';
    };



    const parseBigFiles = (val?: string) => {
        if (!val) return {};
        const lines = val.split('\n').filter(l => l.trim().length > 0);
        const groups: { [key: string]: { size: string, name: string }[] } = {};
        lines.forEach(line => {
            const parts = line.trim().split(/\s+/);
            if (parts.length >= 2) {
                const size = parts[0];
                const path = parts.slice(1).join(' ');
                const lastSlash = path.lastIndexOf('/');
                const dir = lastSlash > -1 ? path.substring(0, lastSlash) : '/';
                const name = lastSlash > -1 ? path.substring(lastSlash + 1) : path;
                if (!groups[dir]) groups[dir] = [];
                groups[dir].push({ size, name });
            }
        });
        return groups;
    };

    const parseShiftTables = (val?: string) => {
        if (!val) return [];
        const lines = val.split('\n');
        const tables: { name: string, count: string }[] = [];
        lines.forEach(l => {
            const match = l.match(/^\s*(\w+)\s*\|\s*(\d+)/);
            if (match) tables.push({ name: match[1], count: match[2] });
        });
        return tables;
    };

    const parseTopTable = (val?: string, tableName?: string) => {
        if (!val || !tableName) return null;
        const regex = new RegExp(`${tableName}\\s*\\|\\s*([\\d\\.]+\\s*[GM]B)`);
        const match = val.match(regex);
        return match ? match[1] : null;
    };

    const parseTopTableInt = (val?: string, tableName?: string) => {
        if (!val || !tableName) return null;
        const regex = new RegExp(`${tableName}\\s*\\|\\s*(\\d+)`);
        const match = val.match(regex);
        return match ? match[1] : null;
    };

    const parseIdleCount = (val?: string) => {
        if (!val) return 0;
        // Count lines that start with digits (process ID) ?
        // Snippet: "pid | backend..." then "123 | ..."
        // Just count lines excluding header?
        const lines = val.split('\n').filter(l => l.trim().match(/^\d+\s*\|/));
        return lines.length;
    };

    const reconciliatorStatus = parseReconciliator(primaryServer?.app?.['logReconciliador']?.metric_value);
    const summarizerStatus = parseSummarizer(primaryServer);

    return (
        <div className="p-6 max-w-[1600px] mx-auto space-y-6">
            {/* Header */}
            <div className="bg-yellow-400 p-2 text-center font-bold text-black rounded-t-md shadow-sm">
                Entorno de desarrollo del sistema de monitoreo Hexagon Mining.
            </div>

            <div className="flex items-center justify-between mb-4">
                <h1 className="text-2xl font-bold flex items-center gap-2">
                    <span className="p-2 bg-white rounded-full shadow-sm"><Server className="w-6 h-6" /></span>
                    Monitoreo {type.toUpperCase()} {site.alias}
                    <span className="text-sm font-normal text-muted-foreground ml-2">({site.name})</span>
                </h1>
                <div className="text-sm text-muted-foreground flex items-center gap-2">
                    <Clock className="w-4 h-4" />
                    Last update: {new Date().toLocaleTimeString()}
                </div>
            </div>

            {/* Top Cards Row */}
            <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4">
                {/* JAMS */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-cyan-500 flex items-center gap-4">
                    <div className="p-3 bg-cyan-100 rounded text-cyan-700"><Server className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">JAMS</div>
                        <div className="font-mono font-bold text-lg">{primaryServer?.app?.['versionJAMS']?.metric_value || 'N/A'}</div>
                    </div>
                </div>

                {/* Summarizer */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-teal-500 flex items-center gap-4">
                    <div className="p-3 bg-teal-100 rounded text-teal-700"><Activity className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">Summarizer</div>
                        <div className={`font-bold ${summarizerStatus.color}`}>{summarizerStatus.status}</div>
                    </div>
                </div>

                {/* Equipos */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-blue-500 flex items-center gap-4">
                    <div className="p-3 bg-blue-100 rounded text-blue-700"><Database className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">Equipos Conectados</div>
                        <div className="font-bold text-xl">{parseEquipments(primaryServer?.app?.['repc']?.metric_value)}</div>
                    </div>
                </div>

                {/* Estacion Base */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-indigo-500 flex items-center gap-4">
                    <div className="p-3 bg-indigo-100 rounded text-indigo-700"><ShieldCheck className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">Estación Base</div>
                        <div className="font-bold text-sm truncate">{primaryServer?.app?.['estacionBase']?.metric_value || '-'}</div>
                    </div>
                </div>

                {/* Daily Backup */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-sky-600 flex items-center gap-4">
                    <div className="p-3 bg-sky-100 rounded text-sky-700"><Clock className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">Daily</div>
                        <div className="font-bold text-sm tracking-tight text-gray-900 flex items-center gap-1 truncate">
                            Hace <TimeAgo filename={primaryServer?.app?.['daily']?.metric_value} />
                        </div>
                    </div>
                </div>

                {/* Hourly Backup */}
                <div className="bg-white p-4 rounded-lg shadow-sm border border-l-4 border-l-sky-600 flex items-center gap-4">
                    <div className="p-3 bg-sky-100 rounded text-sky-700"><Clock className="w-6 h-6" /></div>
                    <div>
                        <div className="text-sm font-bold text-gray-500">Hourly</div>
                        <div className="font-bold text-sm tracking-tight text-gray-900 flex items-center gap-1 truncate">
                            Hace <TimeAgo filename={primaryServer?.app?.['hourly']?.metric_value} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Top Row: Primary, Secondary, Services */}
            <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">

                {/* Primary Server */}
                <div className="bg-white rounded-lg shadow-sm overflow-hidden border flex flex-col h-full">
                    <div className="bg-sky-600/90 text-white px-3 py-1.5 font-bold flex justify-between text-sm items-center">
                        <span>Servidor Primario</span>
                        <span className="text-[10px] bg-sky-500 px-2 py-0.5 rounded shadow-sm">Active</span>
                    </div>
                    <div className="p-3 flex-1 flex flex-col justify-between">
                        {/* Row 1: Gauges + Graph */}
                        <div className="flex flex-row items-center gap-2 mb-2 h-32"> {/* Fixed Height h-32 */}
                            {/* Gauges Column */}
                            <div className="flex flex-col gap-1 shrink-0 justify-between h-full py-1">
                                <div className="flex gap-2">
                                    {metricsData && primaryServer && (() => {
                                        const val = primaryServer.system?.disk_used || 0;
                                        const tot = primaryServer.system?.disk_total || 1;
                                        return renderGauge(val, tot, 'Disco Duro', `HD ${Math.round(val)}G`);
                                    })()}
                                    {metricsData && primaryServer && (() => {
                                        const val = primaryServer.system?.ram_used || 0;
                                        const tot = primaryServer.system?.ram_total || 1;
                                        return renderGauge(val, tot, 'RAM', `RAM ${Math.round(val)}G`);
                                    })()}
                                </div>
                                <div className="flex gap-1 justify-center w-full mt-auto">
                                    <button className="text-[9px] border px-1.5 py-0.5 rounded bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium transition-colors w-full" onClick={() => alert(primaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No data')}>Particiones</button>
                                    <button className="text-[9px] border px-1.5 py-0.5 rounded bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium transition-colors w-full" onClick={() => alert(primaryServer?.app?.['crontab']?.metric_value || 'No data')}>Crontab</button>
                                </div>
                            </div>

                            {/* Graph (Flex 1) */}
                            <div className="flex-1 h-full min-w-0 border rounded p-1 bg-white relative flex flex-col">
                                <div className="flex justify-end items-center mb-0.5 gap-1 absolute top-1 right-1 z-10">
                                    <span className="text-[9px] font-semibold text-gray-400 bg-white/80 px-1 rounded">Load: {primaryServer?.system?.load_average || '0.0'}</span>
                                    <Eye className="w-3 h-3 text-gray-300 hover:text-sky-600 cursor-pointer" />
                                </div>
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={primaryHistory}>
                                        <defs>
                                            <linearGradient id="colorCpuPrimary" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#22c55e" stopOpacity={0.8} />
                                                <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={true} horizontal={true} stroke="#f0f0f0" />
                                        <XAxis dataKey="created_at" hide={true} />
                                        <YAxis domain={[0, 'auto']} hide={false} width={15} tick={{ fontSize: 8, fill: '#9ca3af' }} axisLine={false} tickLine={false} />
                                        <Tooltip contentStyle={{ fontSize: '10px' }} labelStyle={{ display: 'none' }} formatter={(value: any) => [value, 'Load Avg']} />
                                        <Area type="monotone" dataKey="cpu_usage" stroke="#16a34a" strokeWidth={1.5} fillOpacity={1} fill="url(#colorCpuPrimary)" dot={false} isAnimationActive={false} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Row 2: Info */}
                        <div className="grid grid-cols-2 gap-4 text-[10px] border-t pt-2 mt-auto">
                            <div className="overflow-hidden"><h4 className="font-semibold text-gray-500 leading-3 mb-0.5">Servidor</h4><p className="font-bold text-gray-700 truncate" title={primaryServer?.app?.['nombreServidor']?.metric_value}>{primaryServer?.app?.['nombreServidor']?.metric_value || primaryServer?.info?.name}</p></div>
                            <div className="overflow-hidden"><h4 className="font-semibold text-gray-500 leading-3 mb-0.5">IP Address</h4><p className="font-mono text-gray-600 truncate">{primaryServer?.app?.['ipServidor']?.metric_value || primaryServer?.info?.ip_address}</p></div>
                        </div>
                    </div>
                </div>

                {/* Secondary Server */}
                <div className="bg-white rounded-lg shadow-sm overflow-hidden border flex flex-col h-full opacity-95">
                    <div className="bg-sky-600/90 text-white px-3 py-1.5 font-bold flex justify-between text-sm items-center">
                        <span>Servidor Secundario</span>
                        <span className="text-[10px] bg-gray-500/50 px-2 py-0.5 rounded shadow-sm">Backup</span>
                    </div>
                    <div className="p-3 flex-1 flex flex-col justify-between">
                        {/* Row 1: Gauges + Graph */}
                        <div className="flex flex-row items-center gap-2 mb-2 h-32"> {/* Fixed Height h-32 */}
                            {/* Gauges Column */}
                            <div className="flex flex-col gap-1 shrink-0 justify-between h-full py-1">
                                <div className="flex gap-2">
                                    {metricsData && secondaryServer ? (() => {
                                        const val = secondaryServer.system?.disk_used || 0;
                                        const tot = secondaryServer.system?.disk_total || 1;
                                        return renderGauge(val, tot, 'Disco Duro', `HD ${Math.round(val)}G`);
                                    })() : <div className="w-[60px] h-[60px] flex items-center justify-center text-xs text-gray-300 bg-gray-50 rounded-full border">N/A</div>}

                                    {metricsData && secondaryServer ? (() => {
                                        const val = secondaryServer.system?.ram_used || 0;
                                        const tot = secondaryServer.system?.ram_total || 1;
                                        return renderGauge(val, tot, 'RAM', `RAM ${Math.round(val)}G`);
                                    })() : <div className="w-[60px] h-[60px] flex items-center justify-center text-xs text-gray-300 bg-gray-50 rounded-full border">N/A</div>}
                                </div>
                                <div className="flex gap-1 justify-center w-full mt-auto">
                                    <button className="text-[9px] border px-1.5 py-0.5 rounded bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium transition-colors w-full" onClick={() => alert(secondaryServer?.app?.['particionesDiscoDuro']?.metric_value || 'No data')}>Particiones</button>
                                    <button className="text-[9px] border px-1.5 py-0.5 rounded bg-gray-50 hover:bg-gray-100 text-gray-600 font-medium transition-colors w-full" onClick={() => alert(secondaryServer?.app?.['crontab']?.metric_value || 'No data')}>Crontab</button>
                                </div>
                            </div>

                            {/* Graph (Flex 1) */}
                            <div className="flex-1 h-full min-w-0 border rounded p-1 bg-white relative flex flex-col opacity-80">
                                <div className="flex justify-end items-center mb-0.5 gap-1 absolute top-1 right-1 z-10">
                                    <span className="text-[9px] font-semibold text-gray-400 bg-white/80 px-1 rounded">Load: {secondaryServer?.system?.load_average || '0.0'}</span>
                                    <Eye className="w-3 h-3 text-gray-300 hover:text-sky-600 cursor-pointer" />
                                </div>
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={secondaryHistory}>
                                        <defs>
                                            <linearGradient id="colorCpuSecondary" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#22c55e" stopOpacity={0.5} />
                                                <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={true} horizontal={true} stroke="#f0f0f0" />
                                        <XAxis dataKey="created_at" hide={true} />
                                        <YAxis domain={[0, 'auto']} hide={false} width={15} tick={{ fontSize: 8, fill: '#9ca3af' }} axisLine={false} tickLine={false} />
                                        <Tooltip contentStyle={{ fontSize: '10px' }} labelStyle={{ display: 'none' }} formatter={(value: any) => [value, 'Load Avg']} />
                                        <Area type="monotone" dataKey="cpu_usage" stroke="#16a34a" strokeWidth={1.5} fillOpacity={1} fill="url(#colorCpuSecondary)" dot={false} isAnimationActive={false} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Row 2: Info */}
                        <div className="grid grid-cols-2 gap-4 text-[10px] border-t pt-2 mt-auto">
                            <div className="overflow-hidden"><h4 className="font-semibold text-gray-500 leading-3 mb-0.5">Servidor</h4><p className="font-bold text-gray-700 truncate" title={secondaryServer?.info?.name}>{secondaryServer?.info?.name || '-'}</p></div>
                            <div className="overflow-hidden"><h4 className="font-semibold text-gray-500 leading-3 mb-0.5">IP Address</h4><p className="font-mono text-gray-600 truncate">{secondaryServer?.info?.ip_address || '-'}</p></div>
                        </div>
                    </div>
                </div>

                {/* Services & Scripts (Updated Layout) */}
                <div className="bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col h-full">
                    <div className="bg-sky-600/90 text-white px-3 py-1.5 font-bold text-sm">Servicios y Scripts</div>
                    <div className="p-3 flex flex-col gap-2 flex-1">
                        {/* Row 1: JAMS P & S */}
                        <div className="grid grid-cols-2 gap-2">
                            <div className="bg-emerald-500 text-white p-2 rounded shadow-sm flex flex-col justify-center">
                                <div className="font-bold text-xs truncate">JAMS {primaryServer?.info?.name.substring(0, 10)}</div>
                                <div className="text-[10px] opacity-90">Active</div>
                                <div className="text-[10px] mt-0.5">Reinicios: {countScripts(primaryServer?.app?.['reiniciosJAMS']?.metric_value)}</div>
                            </div>
                            <div className="bg-emerald-500 text-white p-2 rounded shadow-sm flex flex-col justify-center">
                                <div className="font-bold text-xs truncate">JAMS {secondaryServer?.info?.name?.substring(0, 10) || 'Sec'}...</div>
                                <div className="text-[10px] opacity-90">Backup</div>
                                <div className="text-[10px] mt-0.5">Reinicios: {countScripts(secondaryServer?.app?.['reiniciosJAMS']?.metric_value)}</div>
                            </div>
                        </div>

                        {/* Row 2: 4 Small Cards: Scripts, Replicas, Reconciliator, NTP */}
                        <div className="grid grid-cols-4 gap-2 flex-1 items-stretch">
                            {/* Scripts */}
                            <div className="bg-emerald-500 text-white p-1.5 rounded shadow-sm flex flex-col items-center justify-center text-center">
                                <div className="font-bold text-[10px] uppercase">Scripts</div>
                                <div className="text-xl font-bold leading-none">{countScripts(primaryServer?.app?.['scriptsEnEjecucion']?.metric_value)}</div>
                            </div>

                            {/* Replicas (New) */}
                            <div className="bg-emerald-500 text-white p-1.5 rounded shadow-sm flex flex-col items-center justify-center text-center">
                                <div className="font-bold text-[10px] uppercase">Replicas</div>
                                <div className="text-xs font-medium">{getReplicasCount(primaryServer?.app?.['procesoReplicas']?.metric_value)}</div>
                            </div>

                            {/* Reconciliator */}
                            <div className={`p-1.5 rounded shadow-sm flex flex-col items-center justify-center text-center ${reconciliatorStatus.color === 'text-emerald-500' ? 'bg-emerald-100 text-emerald-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                <ShieldCheck className="w-4 h-4 mb-0.5 opacity-70" />
                                <div className="font-bold text-[9px] uppercase leading-tight truncate w-full">Reconciliator</div>
                                <div className="text-[9px] font-bold">{reconciliatorStatus.status}</div>
                            </div>

                            {/* NTP (New) */}
                            <div className={`p-1.5 rounded shadow-sm flex flex-col items-center justify-center text-center ${getNTPStatus(primaryServer?.app?.['pingNtp']?.metric_value) === 'Active' ? 'bg-emerald-500 text-white' : 'bg-amber-100 text-amber-800'}`}>
                                <Clock className="w-4 h-4 mb-0.5 opacity-70" />
                                <div className="font-bold text-[10px] uppercase">NTP</div>
                                <div className="text-[9px] font-bold">{getNTPStatus(primaryServer?.app?.['pingNtp']?.metric_value)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom Row: Archivos & Database */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {/* Archivos y Log Pesados */}
                <div className="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col h-full">
                    <div className="bg-sky-700 px-4 py-2 border-b border-sky-800 flex justify-between items-center rounded-t-lg">
                        <h3 className="font-semibold text-white text-sm">Archivos y Log Pesados (Sobre 1 GB)</h3>
                    </div>
                    <div className="p-3 overflow-y-auto max-h-64 text-xs flex-1">
                        {metricsData && primaryServer && (() => {
                            const groups = parseBigFiles(primaryServer.app?.['bigFiles']?.metric_value);
                            if (Object.keys(groups).length === 0) return <p className="text-gray-400 italic">No large files detected.</p>;
                            return Object.entries(groups).map(([dir, files]) => (
                                <div key={dir} className="mb-3 last:mb-0">
                                    <div className="flex items-center gap-2 mb-1 text-amber-600 font-semibold break-all">
                                        <span className="text-amber-500">📁</span> {dir}
                                    </div>
                                    <ul className="pl-5 space-y-0.5 text-gray-600">
                                        {files.map((f: any, i: number) => (
                                            <li key={i}>- {f.name} <span className="text-gray-400">({f.size})</span></li>
                                        ))}
                                    </ul>
                                </div>
                            ));
                        })()}
                    </div>
                </div>

                {/* DataBase Section */}
                <div className="bg-white rounded-lg shadow-sm border border-slate-200 flex flex-col h-full">
                    <div className="bg-sky-700 px-4 py-2 border-b border-sky-800 flex justify-between items-center rounded-t-lg">
                        <h3 className="font-semibold text-white text-sm">DataBase</h3>
                    </div>
                    <div className="p-3 space-y-3 flex-1 flex flex-col">


                        {/* Active/Backup Cards */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="border rounded p-2 flex items-center gap-2">
                                <Database className="w-6 h-6 text-slate-600 shrink-0" />
                                <div>
                                    <h4 className="font-bold text-xs text-gray-900">Activo</h4>
                                    <p className="text-gray-600 text-xs">{parsePgSize(primaryServer?.app?.['sizeDataBase']?.metric_value)}</p>
                                </div>
                            </div>
                            <div className="border rounded p-2 flex items-center gap-2">
                                <Database className="w-6 h-6 text-slate-600 shrink-0" />
                                <div className="min-w-0">
                                    <h4 className="font-bold text-xs text-gray-900">Backup</h4>
                                    <p className="text-gray-600 text-xs">{parsePgSize(secondaryServer?.app?.['sizeDataBase']?.metric_value)}</p>
                                    <p className="text-gray-400 text-[9px] mt-0.5 truncate" title={primaryServer?.app?.['schemaInfo']?.metric_value || secondaryServer?.app?.['schemaInfo']?.metric_value}>{primaryServer?.app?.['schemaInfo']?.metric_value || secondaryServer?.app?.['schemaInfo']?.metric_value || '-'}</p>
                                </div>
                            </div>
                        </div>

                        {/* Table Event Logs */}
                        <div className="border rounded overflow-hidden">
                            <table className="w-full text-xs text-left">
                                <thead className="bg-gray-50 text-gray-600 font-semibold border-b">
                                    <tr>
                                        <th className="p-1.5">Tabla</th>
                                        <th className="p-1.5 text-center">PK</th>
                                        <th className="p-1.5 text-right">Max. Val</th>
                                        <th className="p-1.5 text-right">Gap</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(() => {
                                        const maxVal = 2147483647;
                                        const tVal = primaryServer?.app?.['primaryKeyTables']?.metric_value;
                                        const curValStr = parseTopTableInt(tVal, 'event_logs');
                                        const curVal = curValStr ? parseInt(curValStr) : 0;
                                        const gap = maxVal - curVal;
                                        return (
                                            <tr className="border-b last:border-0 hover:bg-gray-50">
                                                <td className="p-1.5 text-gray-700 font-medium">event_logs</td>
                                                <td className="p-1.5 text-gray-500 text-center font-mono">{curVal.toLocaleString()}</td>
                                                <td className="p-1.5 text-gray-500 text-right font-mono">{maxVal.toLocaleString()}</td>
                                                <td className="p-1.5 font-bold text-green-600 text-right font-mono">{gap.toLocaleString()}</td>
                                            </tr>
                                        );
                                    })()}
                                </tbody>
                            </table>
                            {/* Badges and Diff Logic */}
                            <div className="flex flex-wrap gap-2 items-center mt-auto">
                                <div className="bg-green-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                    SQL IDLE {parseIdleCount(primaryServer?.app?.['idleQuery']?.metric_value)}
                                </div>
                                <div className="bg-green-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm">
                                    shift_gps {parseTopTable(primaryServer?.app?.['topTenSizeTables']?.metric_value, 'shift_gps') || '0 MB'}
                                </div>

                                {/* Diff Tablas Badge (Max Diff) */}
                                {(() => {
                                    const pTables = parseShiftTables(primaryServer?.app?.['shiftTables']?.metric_value);
                                    const sTables = parseShiftTables(secondaryServer?.app?.['shiftTables']?.metric_value);
                                    let maxDiff = 0;
                                    let maxTable = 'Ninguna';

                                    pTables.forEach((pt: any) => {
                                        const st = sTables.find((s: any) => s.name === pt.name);
                                        const pCount = parseInt(pt.count) || 0;
                                        const sCount = st ? (parseInt(st.count) || 0) : 0;
                                        const diff = Math.abs(pCount - sCount);
                                        if (diff > maxDiff) {
                                            maxDiff = diff;
                                            maxTable = pt.name;
                                        }
                                    });

                                    return (
                                        <div className="bg-green-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm flex gap-1 items-center" title={`Mayor diferencia: ${maxTable} (${maxDiff})`}>
                                            <span>Diff {maxTable}:</span>
                                            <span className="font-mono bg-green-700/50 px-1 rounded">{maxDiff.toLocaleString()}</span>
                                        </div>
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    );
};

export default MonitoreoSite;

