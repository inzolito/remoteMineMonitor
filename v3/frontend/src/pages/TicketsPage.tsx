import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Ticket, Search, AlertCircle, Eye, Loader2, MessageSquare } from 'lucide-react';
import { cn } from '../lib/utils';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';

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
}

const TicketsPage = () => {
    const [page, setPage] = useState(1);
    const limit = 30;
    const [searchQuery, setSearchQuery] = useState('');

    const { data: ticketsData, isLoading, error } = useQuery({
        queryKey: ['tickets_module', page],
        queryFn: async () => {
            const res = await fetch(`/monitoreoLaboratorio/v3/api/tickets.php?page=${page}&limit=${limit}`);
            if (!res.ok) throw new Error('Error al cargar tickets');
            return res.json();
        },
        refetchInterval: 15000,
        placeholderData: (prev) => prev // keepPreviousData replacement in v5
    });

    const active_tickets: SFCase[] = ticketsData?.tickets || [];
    const stats = ticketsData?.stats || { total: 0, open: 0, seeking: 0, closed: 0, queue: 0, assigned: 0 };
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
        const created = new Date(dateString);
        const now = new Date();
        const diffMs = now.getTime() - created.getTime();
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const diffHrs = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const diffMin = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        if (diffDays > 0) return `${diffDays} ${diffDays === 1 ? 'Día' : 'Días'}`;
        if (diffHrs > 0) return `${diffHrs}H`;
        if (diffMin > 0) return `${diffMin}M`;
        return 'Ahora';
    };

    const handleOpenTicketDetails = (t: SFCase) => {
        console.log(t);

        // Placeholder, implement modal if needed
    };

    const filteredTickets = active_tickets.filter((t: SFCase) => 
        t.CaseNumber?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        t.Subject?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        t.Faena?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        t.OwnerName?.toLowerCase().includes(searchQuery.toLowerCase())
    );

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
            <div className="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4">
                <div className="bg-white dark:bg-card border border-border shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                        <Ticket className="w-12 h-12 text-slate-500" />
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Anual</span>
                    <span className="text-3xl font-black text-slate-800 dark:text-slate-100">{stats.total}</span>
                </div>
                <div className="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Asignados</span>
                    <span className="text-3xl font-black text-blue-600 dark:text-blue-400">{stats.assigned}</span>
                </div>
                <div className="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Trabajando</span>
                    <span className="text-3xl font-black text-emerald-600 dark:text-emerald-400">{stats.open}</span>
                </div>
                <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/30 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group">
                    <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Esperando Info</span>
                    <span className="text-3xl font-black text-amber-600 dark:text-amber-400">{stats.seeking}</span>
                </div>
                <div className="bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/50 shadow-sm rounded-2xl p-4 flex flex-col gap-1 relative overflow-hidden group opacity-80">
                    <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Cerrados</span>
                    <span className="text-3xl font-black text-slate-600 dark:text-slate-300">{stats.closed}</span>
                </div>
            </div>

            {/* Filter Row */}
            <div className="flex flex-col md:flex-row gap-4 justify-between items-start md:items-center bg-card border border-border p-3 rounded-2xl shadow-sm">
                <div className="relative w-full md:w-80">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <input
                        type="text"
                        placeholder="Buscar por Ticket, Asunto o Faena..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="block w-full pl-9 pr-3 py-2 border border-border rounded-xl bg-background/50 text-sm placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    />
                </div>
            </div>

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                {/* Tickets Table (Left Side) */}
                <div className="xl:col-span-2 space-y-6">
                    <div className="bg-card border border-border rounded-2xl shadow-sm overflow-hidden flex flex-col relative">
                        <div className="p-4 border-b border-border/50 bg-muted/10 flex items-center justify-between">
                            <h2 className="text-sm font-bold text-foreground flex items-center gap-2">
                                <Ticket className="w-4 h-4 text-primary" />
                                Tickets Recientes (Mostrando {active_tickets.length})
                            </h2>
                        </div>
                        
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm whitespace-nowrap">
                                <thead>
                                    <tr className="border-b border-border bg-muted/30">
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Ticket / Faena</th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Asunto</th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Estado</th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground">Tiempo</th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-center"><MessageSquare className="w-3.5 h-3.5 inline" /></th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-center">Owner</th>
                                        <th className="px-4 py-3 text-[10px] font-black uppercase tracking-wider text-muted-foreground text-right"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/50">
                                    {filteredTickets.map((t) => {
                                        const isClosed = t.Status?.toLowerCase() === 'closed';
                                        const isWorking = t.Status?.toLowerCase() === 'working';
                                        const isAssigned = t.Status?.toLowerCase() === 'assigned';
                                        const isSeeking = t.Status?.toLowerCase().includes('seeking');
                                        const highlightRed = t.OwnerName?.toLowerCase().includes('queue') && !isClosed;

                                        return (
                                            <tr key={t.Id} className={cn(
                                                "group transition-colors hover:bg-muted/30",
                                                isClosed ? "bg-slate-50/30 dark:bg-slate-900/10 opacity-75" : 
                                                highlightRed ? "bg-rose-50/80 dark:bg-rose-900/10 border-l-2 border-l-rose-500" :
                                                isWorking ? "bg-emerald-50/80 dark:bg-emerald-900/10 border-l-2 border-l-emerald-500" :
                                                isAssigned ? "bg-blue-50/80 dark:bg-blue-900/10 border-l-2 border-l-blue-500" :
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
                                                    <div className="flex flex-col max-w-[200px] lg:max-w-[300px]">
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
                                                        "text-[10px] px-2 py-1 rounded-md font-black uppercase tracking-wider border",
                                                        isClosed ? "bg-slate-100 text-slate-500 border-slate-200" :
                                                        isWorking ? "bg-emerald-100 text-emerald-700 border-emerald-200" :
                                                        isAssigned ? "bg-blue-100 text-blue-700 border-blue-200" :
                                                        isSeeking ? "bg-amber-100 text-amber-700 border-amber-200" :
                                                        "bg-muted text-muted-foreground border-border"
                                                    )}>
                                                        {t.Status}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-col">
                                                        <span className={cn(
                                                            "text-[11px] uppercase",
                                                            isClosed ? "font-normal text-slate-400" :
                                                            highlightRed ? "font-black text-rose-600" : "font-black text-foreground/90"
                                                        )}>
                                                            {formatTimeElapsed(t.CreatedDate)}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-muted/50 rounded-lg">
                                                        <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                                                        <span className="text-[10px] font-black text-foreground">{t.CommentCount || 0}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-center">
                                                    <span className={cn(
                                                        "text-[10px] px-2 py-1 rounded-md whitespace-nowrap border",
                                                        isClosed ? "font-normal text-slate-400 bg-slate-50" :
                                                        highlightRed ? "font-bold text-rose-600 bg-rose-50" :
                                                        "font-bold text-foreground bg-slate-100"
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
                        {pagination.total_pages > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 border-t border-border bg-muted/5">
                                <div className="flex items-center text-[11px] text-muted-foreground font-semibold">
                                    Mostrando página {pagination.page} de {pagination.total_pages} ({pagination.total} tickets totales)
                                </div>
                                <div className="flex items-center gap-1">
                                    <button 
                                        onClick={() => setPage(p => Math.max(1, p - 1))}
                                        disabled={pagination.page === 1}
                                        className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-xs font-bold disabled:opacity-50 hover:bg-muted"
                                    >
                                        Anterior
                                    </button>
                                    <button 
                                        onClick={() => setPage(p => Math.min(pagination.total_pages, p + 1))}
                                        disabled={pagination.page === pagination.total_pages}
                                        className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-xs font-bold disabled:opacity-50 hover:bg-muted"
                                    >
                                        Siguiente
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right Side (Analytics Sidebar) */}
                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 h-[550px]">
                    <div className="space-y-2 h-full flex flex-col">
                        <div className="flex items-center justify-between border-b border-border/40 pb-1.5 shrink-0">
                            <span className="text-[10px] font-black text-slate-400 uppercase tracking-wider">Distribución</span>
                            <span className="text-[9px] font-extrabold text-muted-foreground bg-muted px-2 py-0.5 rounded-full">Total: {stats.total}</span>
                        </div>

                        {stats.total > 0 ? (
                            <div className="flex flex-col items-center flex-1 justify-center">
                                <div className="w-64 h-64 shrink-0 relative flex items-center justify-center">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={[
                                                    { name: 'WORKING', value: stats.open, color: '#6366f1' },
                                                    { name: 'SEEKING', value: stats.seeking, color: '#f59e0b' },
                                                    { name: 'CLOSED', value: stats.closed, color: '#10b981' },
                                                    { name: 'ASSIGNED', value: stats.assigned, color: '#3b82f6' }
                                                ].filter(d => d.value > 0)}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={76}
                                                outerRadius={108}
                                                paddingAngle={2}
                                                dataKey="value"
                                            >
                                                {[
                                                    { name: 'WORKING', value: stats.open, color: '#6366f1' },
                                                    { name: 'SEEKING', value: stats.seeking, color: '#f59e0b' },
                                                    { name: 'CLOSED', value: stats.closed, color: '#10b981' },
                                                    { name: 'ASSIGNED', value: stats.assigned, color: '#3b82f6' }
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
                            </div>
                        ) : (
                            <div className="py-6 text-center text-[10px] text-muted-foreground italic font-semibold">Sin datos para graficar</div>
                        )}
                    </div>
                </div>

            </div>
        </div>
    );
};

export default TicketsPage;
