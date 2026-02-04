import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Activity, Bell, Ticket, ExternalLink, Shield, Cloud, LayoutDashboard, Clock, X, Terminal, Search, ChevronLeft, ChevronRight, User } from 'lucide-react';
import { cn } from '../lib/utils'; // Assuming cn helper is available or used directly

const fetchHomeMetrics = async () => {
    const token = localStorage.getItem('token');
    const response = await fetch('/monitoreoLaboratorio/v3/api/home_metrics.php', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    if (!response.ok) throw new Error('Network response was not ok');
    return response.json();
};

const Home = () => {
    const [selectedLog, setSelectedLog] = useState<{ title: string, content: string } | null>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 8;

    const { data, isLoading } = useQuery({
        queryKey: ['homeMetrics'],
        queryFn: fetchHomeMetrics,
        refetchInterval: 10000,
        placeholderData: (prev) => prev
    });

    const quickLinks = [
        { name: 'Monitoreo Remoto', path: '/monitoreo', icon: Activity, iconClass: 'bg-emerald-50 text-emerald-600 ring-emerald-500/20' },
        { name: 'New Confluence', url: 'https://hexagon-mining.atlassian.net/wiki/home', icon: Cloud, iconClass: 'bg-blue-50 text-blue-700 ring-blue-700/20' },
        { name: 'Replicon', url: 'https://login.replicon.com/DefaultV2.aspx?companykey=LeicaGeosystems&msg=&code=PleaseLoginToContinue&init=', icon: Clock, iconClass: 'bg-blue-50 text-blue-500 ring-blue-500/20' },
        { name: 'Salesforce', url: 'https://usa1.lightning.force.com/lightning/o/Case/list?filterName=00B8W000008ueBRUAY', icon: Cloud, iconClass: 'bg-sky-50 text-sky-500 ring-sky-500/20' },
        { name: 'MetaCompliance', url: 'https://cloud.metacompliance.com/Account/Login?ReturnUrl=%2FAvailable%2FViewContent%3Ftype%3Dcourse', icon: Shield, iconClass: 'bg-violet-50 text-violet-600 ring-violet-600/20' },
        { name: 'Cocha', url: 'https://cocha.kontroltravel.com/login.aspx', icon: ExternalLink, iconClass: 'bg-rose-50 text-rose-700 ring-rose-700/20' },
    ];

    if (isLoading && !data) {
        return (
            <div className="flex flex-col gap-4 justify-center items-center h-[70vh] ">
                <Activity className="animate-spin w-12 h-12 text-blue-600" />
                <p className="text-slate-500 font-bold animate-pulse text-lg tracking-tight">Iniciando Dashboard...</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between pb-2 border-b">
                <div>
                    <h1 className="text-2xl font-black tracking-tight text-slate-800 flex items-center gap-2">
                        <LayoutDashboard className="w-6 h-6 text-primary" />
                        Inicio
                    </h1>
                </div>
            </div>

            {/* Quick Links (Moved to Top) */}
            <div className="space-y-2">
                <h3 className="text-xs font-black text-slate-400 uppercase tracking-widest pl-1">Accesos Útiles</h3>
                <div className="bg-slate-50/50 p-3 rounded-xl border border-slate-100 mb-2">
                    <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
                        {quickLinks.map((link) => (
                            link.url ? (
                                <a
                                    key={link.name}
                                    href={link.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="bg-white border border-slate-200 rounded-lg p-2.5 flex items-center gap-3 hover:shadow-sm hover:border-primary/30 transition-all group overflow-hidden"
                                >
                                    <div className={cn(
                                        "p-2 rounded-lg shrink-0 transition-transform group-hover:scale-105 ring-1 ring-inset",
                                        link.iconClass
                                    )}>
                                        <link.icon size={16} />
                                    </div>
                                    <span className="text-[10px] font-black text-slate-600 uppercase tracking-tight truncate">{link.name}</span>
                                </a>
                            ) : (
                                <Link
                                    key={link.name}
                                    to={link.path || '#'}
                                    className="bg-white border border-slate-200 rounded-lg p-2.5 flex items-center gap-3 hover:shadow-sm hover:border-primary/30 transition-all group overflow-hidden"
                                >
                                    <div className={cn(
                                        "p-2 rounded-lg shrink-0 transition-transform group-hover:scale-105 ring-1 ring-inset",
                                        link.iconClass
                                    )}>
                                        <link.icon size={16} />
                                    </div>
                                    <span className="text-[10px] font-black text-slate-600 uppercase tracking-tight truncate">{link.name}</span>
                                </Link>
                            )
                        ))}
                    </div>
                </div>
            </div>

            {/* Row 1: Services */}
            <div className="space-y-2">
                <h3 className="text-xs font-black text-slate-400 uppercase tracking-widest pl-1">Scripts Corriendo</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {['oas', 'salesforce'].map((key) => {
                        const svc = data?.services?.[key];
                        const isOk = svc?.status === 'ok';
                        const title = key === 'oas' ? 'TÚNEL OAS GRAFANA' : 'COMUNICACIÓN CON SALESFORCE';

                        return (
                            <div
                                key={key}
                                onClick={() => setSelectedLog({ title, content: svc?.log || 'Cargando logs...' })}
                                className={cn(
                                    "p-4 rounded-xl border transition-all flex items-center justify-between shadow-sm cursor-pointer hover:scale-[1.005] active:scale-[0.995]",
                                    isOk ? "bg-white border-slate-200 hover:border-emerald-300" : "bg-red-600 border-red-700 text-white animate-pulse"
                                )}
                            >
                                <div className="flex items-center gap-3">
                                    <div className={cn(
                                        "p-2.5 rounded-lg",
                                        isOk ? "bg-emerald-500 text-white" : "bg-white text-red-600"
                                    )}>
                                        <Activity size={20} />
                                    </div>
                                    <div className="min-w-0">
                                        <h3 className={cn("font-black text-base uppercase truncate leading-none mb-1", isOk ? "text-slate-700" : "text-white")}>
                                            {title}
                                        </h3>
                                        <p className={cn("text-[10px] font-medium", isOk ? "text-slate-500" : "text-white/80")}>
                                            {svc?.fecha || 'Sin actividad'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex flex-col items-end shrink-0">
                                    <span className={cn(
                                        "px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider shadow-sm mb-1",
                                        isOk ? "bg-emerald-100 text-emerald-700" : "bg-white text-red-700"
                                    )}>
                                        {isOk ? 'Activo' : 'Inactivo'}
                                    </span>
                                    {svc?.minutos !== null && (
                                        <span className={cn("text-[9px] font-bold", isOk ? "text-slate-400" : "text-white/70")}>
                                            {svc?.minutos}m
                                        </span>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Row 2: Alerts and Tickets */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Active Alerts */}
                <div className="lg:col-span-1 border rounded-xl overflow-hidden bg-white shadow-sm flex flex-col min-h-[450px]">
                    <div className="bg-slate-50 px-4 py-3 border-b flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Bell className="w-5 h-5 text-amber-500" />
                            <h2 className="font-bold text-slate-700">Alertas Activas</h2>
                        </div>
                        <span className="bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">{data?.alerts?.length || 0}</span>
                    </div>
                    <div className="flex-1 overflow-auto p-3 space-y-2 max-h-[500px]">
                        {data?.alerts?.length > 0 ? (
                            data.alerts.map((alerta: any) => {
                                const isAck = alerta.status === 'acknowledged';
                                const desc = alerta.description?.split('] ').pop() || alerta.description;
                                const ipMatch = alerta.description?.match(/\[IP: (.*?)\]/);
                                const ip = ipMatch ? ipMatch[1] : null;

                                return (
                                    <div key={alerta.alert_id} className={cn(
                                        "p-3 rounded-lg border-l-4 transition-all shadow-sm flex flex-col gap-1.5 relative group bg-white border-slate-200 border-y border-r",
                                        alerta.status === 'active'
                                            ? "border-l-red-600"
                                            : "border-l-slate-400 opacity-80"
                                    )}>
                                        <div className="flex items-start justify-between gap-1 flex-wrap">
                                            <div className="flex items-center gap-1.5 flex-wrap">
                                                <span className={cn(
                                                    "text-[8px] font-black px-1 py-0.5 rounded uppercase tracking-tighter shrink-0",
                                                    alerta.status === 'active' ? "bg-red-600 text-white animate-pulse" : "bg-slate-500 text-white"
                                                )}>
                                                    {alerta.status === 'active' ? 'ACTIVA' : 'EN REVISIÓN'}
                                                </span>
                                                <span className="text-[9px] font-bold text-slate-500 uppercase truncate max-w-[100px]">{alerta.server_name}</span>
                                                {ip && <span className="text-[8px] font-mono text-blue-600 font-black bg-slate-50 px-1 rounded border border-blue-100/30 shrink-0">{ip}</span>}
                                            </div>
                                            <span className="text-[9px] text-slate-400 font-bold ml-auto shrink-0">
                                                {new Date(alerta.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </span>
                                        </div>

                                        <h4 className={cn("text-xs font-black leading-tight", alerta.status === 'active' ? "text-slate-800" : "text-slate-600")}>
                                            {alerta.title}
                                        </h4>

                                        <p className="text-[10px] text-slate-500 leading-snug italic line-clamp-1 group-hover:line-clamp-none transition-all">
                                            "{desc}"
                                        </p>

                                        {isAck && (
                                            <div className="flex items-center gap-1 mt-0.5 pt-1 border-t border-black/5">
                                                <div className="w-1 h-1 rounded-full bg-emerald-500" />
                                                <span className="text-[8px] text-slate-400 font-bold italic truncate">
                                                    Visto por {alerta.user_name?.split(' ')[0]}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <div className="h-40 flex items-center justify-center text-slate-400 text-sm italic">
                                Sin notificaciones activas
                            </div>
                        )}
                    </div>
                </div>

                {/* Latest Tickets */}
                <div className="lg:col-span-2 border rounded-xl overflow-hidden bg-white shadow-sm flex flex-col">
                    <div className="bg-slate-50 px-4 py-2 border-b flex flex-wrap items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <Ticket className="w-4 h-4 text-blue-500" />
                            <h2 className="font-bold text-slate-700 text-sm">Tickets (Sudamerican Support)</h2>
                        </div>

                        {/* Search Bar */}
                        <div className="relative group max-w-xs w-full sm:w-64">
                            <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 group-focus-within:text-primary transition-colors" />
                            <input
                                type="text"
                                placeholder="Buscar ticket, cliente o responsable..."
                                value={searchTerm}
                                onChange={(e) => {
                                    setSearchTerm(e.target.value);
                                    setCurrentPage(1); // Reset to first page on search
                                }}
                                className="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            />
                        </div>
                    </div>

                    <div className="flex-1 overflow-auto max-h-[500px]">
                        {(() => {
                            const tickets = data?.tickets || [];
                            const filteredTickets = tickets.filter((t: any) =>
                                t.CaseNumber?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                t.AccountName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                t.Subject?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                t.OwnerName?.toLowerCase().includes(searchTerm.toLowerCase())
                            );

                            const totalPages = Math.ceil(filteredTickets.length / itemsPerPage);
                            const paginatedTickets = filteredTickets.slice(
                                (currentPage - 1) * itemsPerPage,
                                currentPage * itemsPerPage
                            );

                            return (
                                <>
                                    <table className="w-full text-left border-collapse">
                                        <thead className="sticky top-0 bg-white/80 backdrop-blur-sm shadow-sm z-10">
                                            <tr className="text-[9px] text-slate-500 uppercase font-black tracking-widest border-b">
                                                <th className="px-3 py-2">Ticket</th>
                                                <th className="px-3 py-2">Cliente</th>
                                                <th className="px-3 py-2">Responsable</th>
                                                <th className="px-3 py-2">Asunto</th>
                                                <th className="px-3 py-2">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y text-[11px]">
                                            {paginatedTickets.length > 0 ? (
                                                paginatedTickets.map((ticket: any, idx: number) => (
                                                    <tr key={idx} className="hover:bg-slate-50 transition-colors">
                                                        <td className="px-3 py-2 font-mono text-blue-600 font-bold">
                                                            <a
                                                                href={`https://usa1.lightning.force.com/lightning/r/Case/${ticket.CaseId}/view`}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="hover:underline flex items-center gap-1 group/link"
                                                            >
                                                                {ticket.CaseNumber}
                                                                <ExternalLink size={10} className="opacity-0 group-hover/link:opacity-100 transition-opacity" />
                                                            </a>
                                                        </td>
                                                        <td className="px-3 py-2 font-bold text-slate-700 max-w-[120px] truncate">{ticket.AccountName}</td>
                                                        <td className="px-3 py-2 text-slate-500 font-medium whitespace-nowrap">
                                                            <div className="flex items-center gap-1.5">
                                                                <div className="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                                                    <User size={10} />
                                                                </div>
                                                                <span className="truncate max-w-[100px]">{ticket.OwnerName}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-3 py-2 text-slate-600 max-w-[200px] truncate">{ticket.Subject}</td>
                                                        <td className="px-3 py-2">
                                                            <span className={cn(
                                                                "px-1.5 py-0.5 rounded text-[8px] font-black uppercase shadow-sm",
                                                                ticket.Status === 'Closed' ? "bg-slate-100 text-slate-500" : "bg-amber-100 text-amber-700"
                                                            )}>
                                                                {ticket.Status}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan={5} className="px-3 py-10 text-center text-slate-400 italic">
                                                        No se encontraron tickets que coincidan con la búsqueda
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>

                                    {/* Pagination Controls */}
                                    {totalPages > 1 && (
                                        <div className="px-4 py-2 bg-slate-50/50 border-t flex items-center justify-between">
                                            <p className="text-[10px] text-slate-500 font-bold">
                                                Mostrando <span className="text-slate-800">{Math.min(filteredTickets.length, (currentPage - 1) * itemsPerPage + 1)}-{Math.min(filteredTickets.length, currentPage * itemsPerPage)}</span> de <span className="text-slate-800">{filteredTickets.length}</span> tickets
                                            </p>
                                            <div className="flex items-center gap-1">
                                                <button
                                                    onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                                                    disabled={currentPage === 1}
                                                    className="p-1 rounded border bg-white enabled:hover:bg-slate-50 disabled:opacity-50 transition-colors"
                                                >
                                                    <ChevronLeft size={14} className="text-slate-600" />
                                                </button>
                                                <div className="flex items-center px-2">
                                                    <span className="text-[10px] font-black text-slate-700">{currentPage}</span>
                                                    <span className="text-[10px] font-bold text-slate-400 mx-1">/</span>
                                                    <span className="text-[10px] font-black text-slate-400">{totalPages}</span>
                                                </div>
                                                <button
                                                    onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                                                    disabled={currentPage === totalPages}
                                                    className="p-1 rounded border bg-white enabled:hover:bg-slate-50 disabled:opacity-50 transition-colors"
                                                >
                                                    <ChevronRight size={14} className="text-slate-600" />
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </>
                            );
                        })()}
                    </div>
                </div>
            </div>


            {/* Log Modal */}
            {selectedLog && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
                    <div className="bg-slate-900 w-full max-w-5xl rounded-xl shadow-2xl border border-slate-700 flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
                        {/* Modal Header */}
                        <div className="flex items-center justify-between p-4 border-b border-slate-800 bg-slate-900/50">
                            <div className="flex items-center gap-3 text-slate-200">
                                <Terminal className="w-5 h-5 text-emerald-400" />
                                <h2 className="font-bold tracking-tight text-lg">Visor de Logs: {selectedLog.title}</h2>
                            </div>
                            <button
                                onClick={() => setSelectedLog(null)}
                                className="p-2 hover:bg-slate-800 rounded-full transition-all text-slate-400 hover:text-white"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Terminal Body */}
                        <div className="flex-1 overflow-auto p-4 bg-black font-mono text-sm leading-relaxed scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                            <pre className="text-emerald-500/90 whitespace-pre-wrap">
                                {selectedLog.content}
                            </pre>
                            {/* Cursor animation */}
                            <span className="inline-block w-2 h-4 bg-emerald-500 animate-pulse ml-1 align-middle" />
                        </div>

                        {/* Footer */}
                        <div className="p-3 border-t border-slate-800 text-right bg-slate-900">
                            <button
                                onClick={() => setSelectedLog(null)}
                                className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-bold rounded-lg transition-colors border border-slate-600 uppercase tracking-wide"
                            >
                                Cerrar Terminal
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Home;
