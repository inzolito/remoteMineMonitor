import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Activity, Bell, Ticket, ExternalLink, Shield, Cloud, LayoutDashboard, Clock, X, Terminal, Search, ChevronLeft, ChevronRight, MessageSquare, Eye } from 'lucide-react';
import { cn } from '../lib/utils'; // Assuming cn helper is available or used directly

const fetchHomeMetrics = async () => {
    let token = null;
    try {
        const userStr = localStorage.getItem('user');
        if (userStr) {
            const userData = JSON.parse(userStr);
            token = userData.token;
        }
    } catch (e) {
        console.error('Error reading token from localStorage:', e);
    }
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
    const itemsPerPage = 5;

    const [selectedSfTicket, setSelectedSfTicket] = useState<any | null>(null);
    const [isSfModalOpen, setIsSfModalOpen] = useState(false);
    const [sfTicketComments, setSfTicketComments] = useState<any[]>([]);
    const [isLoadingComments, setIsLoadingComments] = useState(false);
    const [statusFilter, setStatusFilter] = useState('ALL');

    const formatTimeElapsed = (dateString: string) => {
        if (!dateString) return '--';
        const now = new Date();
        const created = new Date(dateString);
        const diffMs = now.getTime() - created.getTime();
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHrs = Math.floor(diffMin / 60);
        const diffDays = Math.floor(diffHrs / 24);

        if (diffDays > 0) return `${diffDays} ${diffDays === 1 ? 'Día' : 'Días'}`;
        if (diffHrs > 0) return `${diffHrs}H`;
        if (diffMin > 0) return `${diffMin}M`;
        return 'NEW';
    };

    const getOwnerAlias = (name: string) => {
        if (!name) return '--';
        if (name.toLowerCase().includes('support q') || name.toLowerCase().includes('queue')) {
            return 'S.A. Queue';
        }
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return `${parts[0][0]}. ${parts[parts.length - 1]}`;
        }
        return name;
    };

    const handleOpenTicketDetails = async (ticket: any) => {
        setSelectedSfTicket(ticket);
        setIsSfModalOpen(true);
        setIsLoadingComments(true);
        setSfTicketComments([]);

        try {
            let token = null;
            try {
                const userStr = localStorage.getItem('user');
                if (userStr) {
                    const userData = JSON.parse(userStr);
                    token = userData.token;
                }
            } catch (e) {
                console.error('Error reading token from localStorage:', e);
            }
            const res = await fetch(`/monitoreoLaboratorio/v3/api/admin.php?action=bot_sf_ticket_comments&case_id=${ticket.CaseId || ticket.Id}`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            if (res.ok) {
                const comments = await res.json();
                setSfTicketComments(comments || []);
            }
        } catch (err) {
            console.error('Error fetching comments:', err);
        } finally {
            setIsLoadingComments(false);
        }
    };

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
                <div className="lg:col-span-1 border rounded-xl overflow-hidden bg-white shadow-sm flex flex-col">
                    <div className="bg-slate-50 px-4 py-3 border-b flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Bell className="w-5 h-5 text-amber-500" />
                            <h2 className="font-bold text-slate-700">Alertas Activas</h2>
                        </div>
                        <span className="bg-red-100 text-red-700 text-[10px] font-black px-2 py-0.5 rounded-full">{data?.alerts?.length || 0}</span>
                    </div>
                    <div className="flex-1 p-3 space-y-2">
                        {data?.alerts?.length > 0 ? (
                            data.alerts.slice(0, 5).map((alerta: any) => {
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
                            {data?.salesforce_linked ? (
                                <>
                                    <Cloud className="w-4 h-4 text-sky-500 animate-pulse" />
                                    <div className="flex flex-col">
                                        <h2 className="font-extrabold text-slate-800 text-sm leading-none flex items-center gap-1.5">
                                            Mis Tickets
                                            <span className="text-[9px] px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-600 font-bold border border-sky-500/20 uppercase tracking-wider animate-pulse">Salesforce</span>
                                        </h2>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <Ticket className="w-4 h-4 text-blue-500" />
                                    <div className="flex items-center gap-3">
                                        <h2 className="font-bold text-slate-700 text-sm">Tickets (Sudamerican Support)</h2>
                                        <Link 
                                            to="/perfil" 
                                            className="text-[9px] bg-[#00A1E0]/10 hover:bg-[#00A1E0]/20 text-[#00A1E0] px-2 py-0.5 rounded-full font-bold transition-all flex items-center gap-1.5 border border-[#00A1E0]/20 hover:shadow-sm"
                                        >
                                            <svg viewBox="0 0 24 24" className="w-3 h-3 fill-current" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                                            </svg>
                                            Vincular con Salesforce
                                        </Link>
                                    </div>
                                </>
                            )}
                        </div>

                        {/* Search & Status Filters */}
                        <div className="flex items-center gap-2 max-w-md w-full sm:w-auto">
                            {/* Status Filter */}
                            <select
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    setCurrentPage(1); // Reset to first page on status filter change
                                }}
                                className="px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-slate-300"
                            >
                                <option value="ALL">Todos los Estados</option>
                                {Array.from(new Set((data?.tickets || []).map((t: any) => t.Status).filter(Boolean)))
                                    .map((status: any) => (
                                        <option key={status} value={status}>
                                            {status.toUpperCase()}
                                        </option>
                                    ))}
                            </select>

                            {/* Search Bar */}
                            <div className="relative group flex-1 sm:w-48">
                                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 group-focus-within:text-primary transition-colors" />
                                <input
                                    type="text"
                                    placeholder="Buscar..."
                                    value={searchTerm}
                                    onChange={(e) => {
                                        setSearchTerm(e.target.value);
                                        setCurrentPage(1); // Reset to first page on search
                                    }}
                                    className="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex-1 overflow-x-auto">
                        {(() => {
                            const tickets = data?.tickets || [];
                            const filteredTickets = tickets.filter((t: any) => {
                                const matchesSearch = t.CaseNumber?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                    t.AccountName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                    t.Subject?.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                    t.OwnerName?.toLowerCase().includes(searchTerm.toLowerCase());
                                    
                                const matchesStatus = statusFilter === 'ALL' || t.Status === statusFilter;
                                
                                return matchesSearch && matchesStatus;
                            });

                            const is7x7 = !!data?.is_7x7;

                            const totalPages = Math.ceil(filteredTickets.length / itemsPerPage);
                            const paginatedTickets = filteredTickets.slice(
                                (currentPage - 1) * itemsPerPage,
                                currentPage * itemsPerPage
                            );

                            return (
                                <>
                                    <table className="w-full text-left border-collapse">
                                        <thead className="sticky top-0 bg-white/95 backdrop-blur-sm shadow-sm z-10">
                                            <tr className="text-[9px] text-slate-500 uppercase font-black tracking-widest border-b bg-slate-50/70">
                                                <th className="px-4 py-3"># Ticket</th>
                                                <th className="px-4 py-3">Faena</th>
                                                <th className="px-4 py-3">Asunto</th>
                                                <th className="px-4 py-3">Estado</th>
                                                <th className="px-4 py-3">Time</th>
                                                <th className="px-4 py-3 text-center">Coments</th>
                                                {is7x7 && <th className="px-4 py-3">Owner</th>}
                                                <th className="px-4 py-3 text-right">Ver</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 text-[11px]">
                                            {paginatedTickets.length > 0 ? (
                                                paginatedTickets.map((ticket: any, idx: number) => {
                                                    const isClosed = ticket.Status?.toLowerCase() === 'closed';
                                                    const isWorking = ticket.Status?.toLowerCase() === 'working';
                                                    
                                                    return (
                                                        <tr 
                                                            key={idx} 
                                                            className={cn(
                                                                "transition-colors group",
                                                                isClosed 
                                                                    ? "bg-slate-50/50 opacity-60 grayscale hover:bg-slate-50" 
                                                                    : isWorking
                                                                        ? "bg-emerald-500/5 hover:bg-emerald-500/10"
                                                                        : "hover:bg-slate-50"
                                                            )}
                                                        >
                                                            <td className="px-4 py-3">
                                                                <a 
                                                                    href={`https://usa1.lightning.force.com/lightning/r/Case/${ticket.CaseId || ticket.Id}/view`}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className={cn(
                                                                        "text-[10px] font-black px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit",
                                                                        isClosed
                                                                            ? "bg-slate-500/10 text-slate-500 border-slate-500/20 grayscale"
                                                                            : "bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                                                    )}
                                                                >
                                                                    {ticket.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                                                </a>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <span className="text-[10px] font-black text-amber-600 bg-amber-500/5 px-2 py-1 rounded-md border border-amber-500/10 uppercase tracking-tighter">
                                                                    {ticket.Faena || ticket.AccountName || 'Global'}
                                                                </span>
                                                            </td>
                                                            <td className="px-4 py-3 max-w-xs md:max-w-md">
                                                                <p className="text-[11px] font-bold text-slate-700 line-clamp-1 group-hover:line-clamp-none transition-all">
                                                                    {ticket.Subject || '(Sin asunto)'}
                                                                </p>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <div className="flex items-center gap-1.5">
                                                                    <div className={cn(
                                                                        "w-1.5 h-1.5 rounded-full",
                                                                        isClosed ? "bg-slate-400" : "bg-emerald-500 animate-pulse"
                                                                    )}></div>
                                                                    <span className="text-[10px] font-black uppercase tracking-tight text-slate-600">{ticket.Status}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <div className="flex flex-col">
                                                                    <span className="text-[11px] font-black text-slate-700 uppercase">
                                                                        {formatTimeElapsed(ticket.CreatedDate)}
                                                                    </span>
                                                                    <span className="text-[9px] font-medium text-slate-400 whitespace-nowrap opacity-70">
                                                                        {new Date(ticket.CreatedDate).toLocaleDateString()}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3 text-center">
                                                                <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 rounded-lg">
                                                                    <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                                                                    <span className="text-[10px] font-black">{ticket.CommentCount || 0}</span>
                                                                </div>
                                                            </td>
                                                            {is7x7 && (
                                                                <td className="px-4 py-3">
                                                                    <span className="text-[10px] font-bold text-slate-600 bg-slate-100/70 border border-slate-200/50 px-2 py-1 rounded-md whitespace-nowrap">
                                                                        {getOwnerAlias(ticket.OwnerName)}
                                                                    </span>
                                                                </td>
                                                            )}
                                                            <td className="px-4 py-3 text-right">
                                                                <button 
                                                                    onClick={() => handleOpenTicketDetails(ticket)}
                                                                    className="p-2 hover:bg-primary/10 text-primary rounded-xl transition-all"
                                                                    title="Ver Detalles"
                                                                >
                                                                    <Eye className="w-4 h-4" />
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    );
                                                })
                                            ) : (
                                                <tr>
                                                    <td colSpan={is7x7 ? 8 : 7} className="px-4 py-10 text-center text-slate-400 italic">
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

            {/* Salesforce Ticket Modal */}
            {isSfModalOpen && selectedSfTicket && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="bg-card border border-border rounded-[2rem] w-full max-w-4xl max-h-[85vh] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200 bg-white">
                        <div className="p-6 border-b flex items-center justify-between bg-slate-50/50">
                            <div className="flex items-center gap-3">
                                <div className="p-2 bg-primary/10 rounded-xl">
                                    <Ticket className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-black uppercase tracking-tight text-slate-800">Ticket #{selectedSfTicket.CaseNumber}</h3>
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{selectedSfTicket.Faena || selectedSfTicket.AccountName || 'Global'}</p>
                                </div>
                            </div>
                            <button 
                                onClick={() => setIsSfModalOpen(false)}
                                className="p-2 hover:bg-slate-100 rounded-xl transition-colors"
                            >
                                <X className="w-5 h-5 text-muted-foreground" />
                            </button>
                        </div>
                        
                        <div className="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
                            {/* Description */}
                            <div className="space-y-3">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                                    <Terminal className="w-3.5 h-3.5" /> Descripción del Caso
                                </h4>
                                <div className="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                                    <p className="text-xs leading-relaxed text-slate-700 whitespace-pre-wrap">
                                        {selectedSfTicket.Description || 'Sin descripción detallada.'}
                                    </p>
                                </div>
                            </div>

                            {/* Comments */}
                            <div className="space-y-4">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                                    <MessageSquare className="w-3.5 h-3.5" /> Comentarios ({sfTicketComments.length})
                                </h4>
                                
                                <div className="space-y-3">
                                    {isLoadingComments ? (
                                        <div className="py-12 text-center">
                                            <div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-2"></div>
                                            <p className="text-xs text-slate-500 font-bold">Cargando comentarios...</p>
                                        </div>
                                    ) : sfTicketComments.length > 0 ? (
                                        sfTicketComments.map((comment: any, idx: number) => (
                                            <div key={idx} className="bg-white border rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="text-[10px] font-black text-primary uppercase tracking-tight">{comment.Author || 'Sistema'}</span>
                                                    <span className="text-[9px] font-mono text-muted-foreground italic">{new Date(comment.CreatedDate).toLocaleString()}</span>
                                                </div>
                                                <p className="text-[11px] leading-relaxed text-slate-600 whitespace-pre-wrap">
                                                    {comment.CommentBody}
                                                </p>
                                                <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary/20 group-hover:bg-primary transition-colors"></div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="py-10 text-center bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                            <MessageSquare className="w-10 h-10 text-slate-400/20 mx-auto mb-3" />
                                            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">No hay comentarios registrados</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="p-4 border-t bg-slate-50/30 flex justify-end gap-3">
                            <a 
                                href={`https://usa1.lightning.force.com/lightning/r/Case/${selectedSfTicket.CaseId || selectedSfTicket.Id}/view`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center gap-2 px-6 py-2.5 bg-primary text-primary-foreground rounded-xl text-[10px] font-black uppercase tracking-widest hover:shadow-lg hover:shadow-primary/20 transition-all text-white"
                            >
                                <ExternalLink className="w-3.5 h-3.5" /> Abrir en Salesforce
                            </a>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Home;
