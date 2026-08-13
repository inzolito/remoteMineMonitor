import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Ticket, Search, AlertCircle, Eye, Loader2, MessageSquare, CheckCircle2, X, ExternalLink, Calendar, Clock, BarChart3, AlertTriangle } from 'lucide-react';
import { cn } from '../lib/utils';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';
import { createPortal } from 'react-dom';

interface SFCase {
    Id: string;
    CaseNumber: string;
    Subject: string;
    Status: string;
    Priority: string;
    CreatedDate: string;
    ClosedDate: string | null;
    Description: string;
    Resolution: string;
    Faena: string;
    Conglomerate: string;
    OwnerName: string;
    Duration: string;
    CommentCount: number;
    Comments: any[];
}

const TicketsPage = () => {
    const [page, setPage] = useState(1);
    const limit = 30;
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTab, setActiveTab] = useState<'all' | 'today' | 'week' | 'month' | 'escalado'>('all');
    
    // Modal state
    const [isSfModalOpen, setIsSfModalOpen] = useState(false);
    const [selectedSfTicket, setSelectedSfTicket] = useState<SFCase | null>(null);

    const { data: ticketsData, isLoading, error } = useQuery({
        queryKey: ['tickets_module', page],
        queryFn: async () => {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const res = await fetch(`/monitoreoLaboratorio/v3/api/tickets.php?page=${page}&limit=${limit}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            if (!res.ok) throw new Error('Error al cargar tickets');
            return res.json();
        },
        refetchInterval: 15000,
        placeholderData: (prev) => prev
    });

    const active_tickets: SFCase[] = ticketsData?.tickets || [];
    const stats = ticketsData?.stats || { total: 0, open: 0, seeking: 0, escalado_pd: 0, escalado_gt: 0, closed: 0, queue: 0, assigned: 0, sa_queue: 0 };
    const pagination = ticketsData?.pagination || { page: 1, limit: 30, total: 0, total_pages: 0 };

    const getOwnerAlias = (name: string) => {
        if (!name) return '--';
        if (name.toLowerCase().includes('queue') || name.toLowerCase().includes('support q')) {
            return 'S.A. Queue';
        }
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return `${parts[0][0]}. ${parts[parts.length - 1]}`;
        }
        return name;
    };

    const formatTimeElapsed = (dateString: string) => {
        if (!dateString) return '--';
        const now = new Date();
        const created = new Date(dateString.replace(' ', 'T'));
        const diffMs = now.getTime() - created.getTime();
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHrs = Math.floor(diffMin / 60);
        const diffDays = Math.floor(diffHrs / 24);

        if (diffHrs >= 24) return `${diffHrs}H (${diffDays}d)`;
        if (diffHrs > 0) return `${diffHrs}H`;
        if (diffMin > 0) return `${diffMin}M`;
        return 'NEW';
    };

    const handleOpenTicketDetails = (t: SFCase) => {
        setSelectedSfTicket(t);
        setIsSfModalOpen(true);
    };

    // Filter logic
    const isTicketInTimeframe = (t: SFCase, timeframe: string) => {
        if (!t.CreatedDate) return false;
        
        const created = new Date(t.CreatedDate.replace(' ', 'T')).getTime();
        const now = new Date().getTime();
        const diffMs = now - created;
        const diffDays = diffMs / (1000 * 60 * 60 * 24);
        
        if (timeframe === 'today') return diffDays <= 1;
        if (timeframe === 'week') return diffDays <= 7;
        if (timeframe === 'month') return diffDays <= 30;
        if (timeframe === 'escalado') {
            const st = t.Status?.toLowerCase() || '';
            return st.includes('escalado a pd') || st.includes('escalado a gt');
        }
        return true; // 'all'
    };

    const filteredTickets = active_tickets.filter((t: SFCase) => {
        const matchesSearch = t.CaseNumber?.toLowerCase().includes(searchQuery.toLowerCase()) ||
            t.Subject?.toLowerCase().includes(searchQuery.toLowerCase()) ||
            t.Faena?.toLowerCase().includes(searchQuery.toLowerCase()) ||
            t.OwnerName?.toLowerCase().includes(searchQuery.toLowerCase());
            
        return matchesSearch && isTicketInTimeframe(t, activeTab);
    });

    if (isLoading && !ticketsData) {
        return (
            <div className="flex-1 flex flex-col items-center justify-center min-h-[50vh] text-muted-foreground space-y-4">
                <Loader2 className="w-10 h-10 animate-spin text-primary/50" />
                <p className="text-sm font-medium animate-pulse">Cargando tickets de Salesforce...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex-1 p-6 flex items-center justify-center min-h-[50vh]">
                <div className="flex flex-col items-center gap-4 text-rose-500 max-w-md text-center bg-rose-500/10 p-8 rounded-3xl border border-rose-500/20">
                    <AlertCircle className="w-12 h-12" />
                    <h3 className="font-bold text-lg">Error de Conexión</h3>
                    <p className="text-sm opacity-80">No se pudieron cargar los tickets desde el servidor. Verifique su conexión y vuelva a intentarlo.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col gap-6 p-4 md:p-6 lg:p-8 max-w-[1600px] mx-auto w-full">
            
            {/* Header Section */}
            <div className="flex flex-col gap-1.5">
                <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-primary/10 text-primary rounded-xl">
                        <Ticket className="w-6 h-6" />
                    </div>
                    <h1 className="text-2xl font-black text-foreground tracking-tight">Módulo de Tickets Generales</h1>
                </div>
                <p className="text-sm font-medium text-muted-foreground ml-14">
                    Resumen global de gestión de tickets importados desde Salesforce.
                </p>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
                <div className="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">WORKING</span>
                    <span className="text-3xl font-black text-emerald-600 dark:text-emerald-400">{stats.open}</span>
                </div>
                <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">SEEKING</span>
                    <span className="text-3xl font-black text-amber-600 dark:text-amber-400">{stats.seeking}</span>
                </div>
                <div className="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">ESCALADO PD</span>
                    <span className="text-3xl font-black text-indigo-600 dark:text-indigo-400">{stats.escalado_pd}</span>
                </div>
                <div className="bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-purple-600 dark:text-purple-400">ESCALADO GT</span>
                    <span className="text-3xl font-black text-purple-600 dark:text-purple-400">{stats.escalado_gt}</span>
                </div>
                <div className="bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/50 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group opacity-80">
                    <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">CLOSED</span>
                    <span className="text-3xl font-black text-slate-600 dark:text-slate-300">{stats.closed}</span>
                </div>
                <div className="bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">ASIGNADO A S.A.</span>
                    <span className="text-3xl font-black text-rose-600 dark:text-rose-400">{stats.sa_queue}</span>
                </div>
            </div>

            {/* Tickets Grid */}
            <div className="w-full gap-6 items-start">
                <div className="w-full space-y-6">
                    <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-sm">
                        
                        {/* Tab & Filters Row */}
                        <div className="p-4 border-b border-border/60 bg-muted/10 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                            <div className="flex border border-border/60 p-0.5 bg-background dark:bg-muted/20 gap-0.5 rounded-xl shrink-0 w-fit overflow-x-auto max-w-full">
                                <button
                                    onClick={() => setActiveTab('all')}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5 whitespace-nowrap",
                                        activeTab === 'all' ? "bg-primary text-white shadow-sm" : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <BarChart3 className="w-3.5 h-3.5" /> Total ({stats.total})
                                </button>
                                <button
                                    onClick={() => setActiveTab('today')}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5 whitespace-nowrap",
                                        activeTab === 'today' ? "bg-primary text-white shadow-sm" : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Clock className="w-3.5 h-3.5" /> Hoy
                                </button>
                                <button
                                    onClick={() => setActiveTab('week')}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5 whitespace-nowrap",
                                        activeTab === 'week' ? "bg-primary text-white shadow-sm" : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Calendar className="w-3.5 h-3.5" /> Esta Semana
                                </button>
                                <button
                                    onClick={() => setActiveTab('month')}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5 whitespace-nowrap",
                                        activeTab === 'month' ? "bg-primary text-white shadow-sm" : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Calendar className="w-3.5 h-3.5" /> Este Mes
                                </button>
                                <button
                                    onClick={() => setActiveTab('escalado')}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5 whitespace-nowrap",
                                        activeTab === 'escalado' ? "bg-indigo-600 text-white shadow-sm" : "text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                    )}
                                >
                                    <AlertTriangle className="w-3.5 h-3.5" /> Escalado a PD / GT
                                </button>
                            </div>

                            <div className="relative group w-full xl:w-64 shrink-0">
                                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                <input
                                    type="text"
                                    placeholder="Buscar..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="w-full pl-8 pr-3 py-1.5 bg-background border border-border rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:bg-card"
                                />
                            </div>
                        </div>

                        {/* Main Content Body */}
                        <div className="grid grid-cols-1 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-border/60">
                            {/* Left Side (Table wrapper) */}
                            <div className="lg:col-span-3 flex flex-col justify-between max-h-[550px]">
                                <div className="overflow-x-auto overflow-y-auto">
                                    <table className="w-full text-left text-sm whitespace-nowrap">
                                        <thead>
                                            <tr className="border-b border-border/60 bg-muted/10 sticky top-0 z-20 backdrop-blur-md">
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Ticket / Faena</th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Asunto</th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Estado</th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Tiempo</th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-center"><MessageSquare className="w-3.5 h-3.5 inline" /></th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-center">Owner</th>
                                                <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-right"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/30">
                                            {[...filteredTickets].sort((a, b) => {
                                                const isClosedA = a.Status?.toLowerCase() === 'closed';
                                                const isClosedB = b.Status?.toLowerCase() === 'closed';
                                                if (isClosedA !== isClosedB) return isClosedA ? 1 : -1;
                                                const timeA = new Date(a.CreatedDate?.replace(' ', 'T') || 0).getTime();
                                                const timeB = new Date(b.CreatedDate?.replace(' ', 'T') || 0).getTime();
                                                return timeB - timeA;
                                            }).map((t) => {
                                                const isClosed = t.Status?.toLowerCase() === 'closed';
                                                const isWorking = t.Status?.toLowerCase() === 'working';
                                                const isAssigned = t.Status?.toLowerCase() === 'assigned';
                                                const isSeeking = t.Status?.toLowerCase().includes('seeking');
                                                const isEscalado = t.Status?.toLowerCase().includes('escalado a');
                                                const highlightRed = t.OwnerName?.toLowerCase().includes('queue') && !isClosed;

                                                return (
                                                    <tr key={t.Id} className={cn(
                                                        "group transition-colors hover:bg-muted/30",
                                                        isClosed ? "bg-slate-50/30 dark:bg-slate-900/10 opacity-75" : 
                                                        highlightRed ? "bg-rose-50/80 dark:bg-rose-900/10 border-l-2 border-l-rose-500" :
                                                        isWorking ? "bg-emerald-50/80 dark:bg-emerald-900/10 border-l-2 border-l-emerald-500" :
                                                        isAssigned ? "bg-blue-50/80 dark:bg-blue-900/10 border-l-2 border-l-blue-500" :
                                                        isEscalado ? "bg-indigo-50/80 dark:bg-indigo-900/10 border-l-2 border-l-indigo-500" :
                                                        isSeeking ? "bg-amber-50/80 dark:bg-amber-900/10 border-l-2 border-l-amber-500" : ""
                                                    )}>
                                                        <td className="px-4 py-3">
                                                            <div className="flex flex-col">
                                                                <span className="font-bold text-primary hover:underline cursor-pointer">
                                                                    {t.CaseNumber}
                                                                </span>
                                                                <span className="text-[10px] font-medium text-muted-foreground truncate max-w-[120px]">
                                                                    {t.Faena}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex flex-col max-w-[150px] md:max-w-[200px]">
                                                                <span className={cn(
                                                                    "text-sm font-semibold truncate",
                                                                    isClosed ? "text-slate-500" : "text-foreground"
                                                                )}>
                                                                    {t.Subject}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className={cn(
                                                                "text-[9px] px-2 py-1 rounded-md font-black uppercase tracking-wider border whitespace-nowrap",
                                                                isClosed ? "bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700" :
                                                                isWorking ? "bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800" :
                                                                isAssigned ? "bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800" :
                                                                isEscalado ? "bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800" :
                                                                isSeeking ? "bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800" :
                                                                "bg-muted text-muted-foreground border-border"
                                                            )}>
                                                                {t.Status}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className={cn(
                                                                "text-[10px] uppercase",
                                                                isClosed ? "font-normal text-slate-400" :
                                                                highlightRed ? "font-black text-rose-600" : "font-black text-foreground/90"
                                                            )}>
                                                                {formatTimeElapsed(t.CreatedDate)}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-center">
                                                            <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-muted/50 rounded-lg">
                                                                <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                                                                <span className="text-[10px] font-black text-foreground">{t.CommentCount || 0}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-3 text-center">
                                                            <span className={cn(
                                                                "text-[9px] px-2 py-1 rounded-md whitespace-nowrap border uppercase",
                                                                isClosed ? "font-normal text-slate-400 bg-slate-50 dark:bg-slate-800/50" :
                                                                highlightRed ? "font-bold text-rose-600 bg-rose-50 dark:bg-rose-900/20" :
                                                                "font-bold text-foreground bg-slate-100 dark:bg-slate-800"
                                                            )}>
                                                                {getOwnerAlias(t.OwnerName)}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-right">
                                                            <button onClick={() => handleOpenTicketDetails(t)} className="p-2 hover:bg-primary/10 text-primary rounded-xl transition-all">
                                                                <Eye className="w-4 h-4" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                            {filteredTickets.length === 0 && (
                                                <tr>
                                                    <td colSpan={7} className="px-4 py-8 text-center text-sm text-muted-foreground">
                                                        No se encontraron tickets.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                                
                                {/* Pagination Controls */}
                                {activeTab === 'all' && pagination.total_pages > 1 && (
                                    <div className="flex items-center justify-between px-4 py-2.5 border-t border-border/60 bg-muted/5 shrink-0">
                                        <div className="flex items-center text-[10px] text-muted-foreground font-semibold">
                                            Mostrando página {pagination.page} de {pagination.total_pages} ({pagination.total} tickets totales)
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <button 
                                                onClick={() => setPage(p => Math.max(1, p - 1))}
                                                disabled={pagination.page === 1}
                                                className="px-2.5 py-1 rounded border border-border bg-background text-foreground text-[10px] font-bold disabled:opacity-50 hover:bg-muted transition-colors"
                                            >
                                                Anterior
                                            </button>
                                            <button 
                                                onClick={() => setPage(p => Math.min(pagination.total_pages, p + 1))}
                                                disabled={pagination.page === pagination.total_pages}
                                                className="px-2.5 py-1 rounded border border-border bg-background text-foreground text-[10px] font-bold disabled:opacity-50 hover:bg-muted transition-colors"
                                            >
                                                Siguiente
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Right Side (Analytics Sidebar) */}
                            <div className="lg:col-span-2 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between border-b border-border/40 pb-1.5 shrink-0">
                                        <span className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Distribución Global</span>
                                        <span className="text-[9px] font-extrabold text-muted-foreground bg-muted dark:bg-muted/30 px-2 py-0.5 rounded-full">Total Anual: {stats.total}</span>
                                    </div>

                                    {stats.total > 0 ? (
                                        <div className="flex flex-col items-center">
                                            <div className="w-64 h-64 shrink-0 relative flex items-center justify-center">
                                                <ResponsiveContainer width="100%" height="100%">
                                                    <PieChart>
                                                        <Pie
                                                            data={[
                                                                { name: 'WORKING', value: stats.open, color: '#10b981' },
                                                                { name: 'SEEKING', value: stats.seeking, color: '#f59e0b' },
                                                                { name: 'ESCALADO PD', value: stats.escalado_pd, color: '#4f46e5' },
                                                                { name: 'ESCALADO GT', value: stats.escalado_gt, color: '#9333ea' },
                                                                { name: 'CLOSED', value: stats.closed, color: '#64748b' },
                                                                { name: 'ASSIGNED', value: stats.assigned, color: '#3b82f6' },
                                                                { name: 'ASIGNADO A S.A.', value: stats.sa_queue, color: '#e11d48' }
                                                            ].filter(d => d.value > 0)}
                                                            cx="50%"
                                                            cy="50%"
                                                            innerRadius={76}
                                                            outerRadius={108}
                                                            paddingAngle={2}
                                                            dataKey="value"
                                                        >
                                                            {[
                                                                { name: 'WORKING', value: stats.open, color: '#10b981' },
                                                                { name: 'SEEKING', value: stats.seeking, color: '#f59e0b' },
                                                                { name: 'ESCALADO PD', value: stats.escalado_pd, color: '#4f46e5' },
                                                                { name: 'ESCALADO GT', value: stats.escalado_gt, color: '#9333ea' },
                                                                { name: 'CLOSED', value: stats.closed, color: '#64748b' },
                                                                { name: 'ASSIGNED', value: stats.assigned, color: '#3b82f6' },
                                                                { name: 'ASIGNADO A S.A.', value: stats.sa_queue, color: '#e11d48' }
                                                            ].filter(d => d.value > 0).map((entry, index) => (
                                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                                            ))}
                                                        </Pie>
                                                        <RechartsTooltip 
                                                            contentStyle={{ fontSize: '9px', borderRadius: '8px' }}
                                                        />
                                                    </PieChart>
                                                </ResponsiveContainer>
                                                <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                    <span className="text-xs font-black uppercase text-slate-400 tracking-wider">Total</span>
                                                    <span className="text-4xl font-black text-foreground leading-none">{stats.total}</span>
                                                </div>
                                            </div>
                                            {/* Legend (Grid format) */}
                                            <div className="grid grid-cols-2 gap-2 mt-4 w-full">
                                                {[
                                                    { label: 'WORKING', value: stats.open, color: 'bg-emerald-500', bg: 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/20' },
                                                    { label: 'SEEKING', value: stats.seeking, color: 'bg-amber-500', bg: 'border-amber-200 bg-amber-50 dark:bg-amber-950/20' },
                                                    { label: 'ESCALADO PD', value: stats.escalado_pd, color: 'bg-indigo-500', bg: 'border-indigo-200 bg-indigo-50 dark:bg-indigo-950/20' },
                                                    { label: 'ESCALADO GT', value: stats.escalado_gt, color: 'bg-purple-500', bg: 'border-purple-200 bg-purple-50 dark:bg-purple-950/20' },
                                                    { label: 'CLOSED', value: stats.closed, color: 'bg-slate-500', bg: 'border-slate-200 bg-slate-50 dark:bg-slate-900/40' },
                                                    { label: 'ASSIGNED', value: stats.assigned, color: 'bg-blue-500', bg: 'border-blue-200 bg-blue-50 dark:bg-blue-950/20' },
                                                    { label: 'ASIGNADO A S.A.', value: stats.sa_queue, color: 'bg-rose-500', bg: 'border-rose-200 bg-rose-50 dark:bg-rose-950/20' }
                                                ].map((item, idx) => (
                                                    <div key={idx} className={`flex items-center justify-between text-[10px] border ${item.bg} rounded-lg p-2 dark:border-slate-800`}>
                                                        <div className="flex items-center gap-1.5">
                                                            <div className={`w-2 h-2 rounded-full ${item.color}`} />
                                                            <span className="font-bold text-slate-700 dark:text-slate-300">{item.label}</span>
                                                        </div>
                                                        <span className="font-black text-foreground text-xs">{item.value}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="py-6 text-center text-[10px] text-muted-foreground italic font-semibold">Sin datos para graficar</div>
                                    )}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {/* Salesforce Ticket Modal */}
            {isSfModalOpen && selectedSfTicket && createPortal(
                <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="bg-card border border-border rounded-[2rem] w-full max-w-4xl max-h-[85vh] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200 bg-white dark:bg-card">
                        <div className="p-6 border-b flex items-center justify-between bg-slate-50/50 dark:bg-muted/20">
                            <div className="flex items-center gap-3">
                                <div className="p-2 bg-primary/10 rounded-xl">
                                    <Ticket className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-lg font-black uppercase tracking-tight text-foreground">Ticket #{selectedSfTicket.CaseNumber}</h3>
                                        <span className={cn(
                                            "text-[9px] font-black uppercase px-2 py-0.5 rounded-full border",
                                            selectedSfTicket.Status?.toLowerCase() === 'closed'
                                                ? "bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700"
                                                : (selectedSfTicket.OwnerName?.toLowerCase().includes('support q') || selectedSfTicket.OwnerName?.toLowerCase().includes('queue'))
                                                    ? "bg-rose-500/10 text-rose-600 border-rose-500/20 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50 animate-pulse"
                                                    : "bg-emerald-500/10 text-emerald-600 border-emerald-500/20 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50"
                                        )}>
                                            {selectedSfTicket.Status}
                                        </span>
                                    </div>
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{selectedSfTicket.Faena || selectedSfTicket.Conglomerate || 'Global'}</p>
                                </div>
                            </div>
                            <button
                                onClick={() => setIsSfModalOpen(false)}
                                className="p-2 hover:bg-slate-100 dark:hover:bg-muted rounded-xl transition-colors"
                            >
                                <X className="w-5 h-5 text-muted-foreground" />
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
                            {/* Description */}
                            <div className="space-y-3">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                                    <Ticket className="w-3.5 h-3.5" /> Descripción del Caso
                                </h4>
                                <div className="bg-slate-50 dark:bg-muted/10 p-5 rounded-2xl border border-slate-200 dark:border-border/60">
                                    <p className="text-xs leading-relaxed text-foreground/90 whitespace-pre-wrap">
                                        {selectedSfTicket.Description || 'Sin descripción detallada.'}
                                    </p>
                                </div>
                            </div>

                            {/* Resolution (Shown if ticket is closed) */}
                            {selectedSfTicket.Status?.toLowerCase() === 'closed' && (
                                <div className="space-y-3">
                                    <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Resolución del Caso
                                    </h4>
                                    <div className="bg-emerald-500/5 dark:bg-emerald-500/10 p-5 rounded-2xl border border-emerald-500/20 dark:border-emerald-500/30">
                                        <p className="text-xs leading-relaxed text-foreground/90 whitespace-pre-wrap">
                                            {selectedSfTicket.Resolution || 'Sin resolución registrada.'}
                                        </p>
                                    </div>
                                </div>
                            )}

                            {/* Comments */}
                            <div className="space-y-4">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                                    <MessageSquare className="w-3.5 h-3.5" /> Comentarios ({(selectedSfTicket.Comments || []).length})
                                </h4>

                                <div className="space-y-3">
                                    {(selectedSfTicket.Comments || []).length > 0 ? (
                                        selectedSfTicket.Comments.map((comment: any, idx: number) => (
                                            <div key={idx} className="bg-card border rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="text-[10px] font-black text-primary uppercase tracking-tight">{comment.Author || 'Sistema'}</span>
                                                    <span className="text-[9px] font-mono text-muted-foreground italic">{new Date(comment.CreatedDate).toLocaleString()}</span>
                                                </div>
                                                <p className="text-[11px] leading-relaxed text-foreground/80 whitespace-pre-wrap">
                                                    {comment.Body}
                                                </p>
                                                <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary/20 group-hover:bg-primary transition-colors"></div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="py-10 text-center bg-slate-50/50 dark:bg-muted/5 rounded-2xl border border-dashed border-slate-200 dark:border-border/40">
                                            <MessageSquare className="w-10 h-10 text-slate-400/20 mx-auto mb-3" />
                                            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">No hay comentarios registrados</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="p-4 border-t bg-slate-50/30 dark:bg-muted/5 flex justify-end gap-3">
                            <a
                                href={`https://usa1.lightning.force.com/lightning/r/Case/${selectedSfTicket.Id}/view`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center gap-2 px-6 py-2.5 bg-primary text-primary-foreground rounded-xl text-[10px] font-black uppercase tracking-widest hover:shadow-lg hover:shadow-primary/20 transition-all text-white"
                            >
                                <ExternalLink className="w-3.5 h-3.5" /> Abrir en Salesforce
                            </a>
                        </div>
                    </div>
                </div>,
                document.body
            )}
        </div>
    );
};

export default TicketsPage;
