import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Clock, Ticket, Calendar, AlertCircle, Eye, Loader2, X, Sun, Moon, ExternalLink, MessageSquare, Search, CheckCircle2, HelpCircle, Activity } from 'lucide-react';
import { cn } from '../lib/utils';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';
import { ShiftStatusEye } from '../components/ShiftStatusEye';
import { SubShiftHandoff } from '../components/SubShiftHandoff';

interface ShiftMember {
    id: number;
    username: string;
    first_name: string;
    last_name: string;
    email: string;
    cargo: string;
    salesforce_user_id: string | null;
    turno_7x7: number;
    turno_tipo?: string | null;
}

interface SFCase {
    CaseId: string;
    CaseNumber: string;
    Subject: string;
    Status: string;
    Priority: string;
    CreatedDate: string;
    ClosedDate?: string | null;
    Description: string;
    Resolution?: string;
    Faena: string;
    AccountName: string;
    OwnerName: string;
    CommentCount: number;
    Comments?: any[];
    is_inherited?: boolean;
}

interface TotalStats {
    total: number;
    closed: number;
    open: number;
    seeking: number;
    assigned: number;
    working: number;
    sa_queue: number;
    avg_resolution: string;
}

interface ShiftResponse {
    config: {
        active_shift: number;
        shift1_alias: string;
        shift2_alias: string;
        start_date: string;
        start_hour?: string;
        end_hour?: string;
        days_elapsed: number;
    };
    shift1_members: ShiftMember[];
    shift2_members: ShiftMember[];
    active_tickets: SFCase[];
    inactive_tickets: SFCase[];
    total_stats: TotalStats;
    pagination: { page: number; per_page: number; total: number; };
}

const fetchShiftsData = async (): Promise<ShiftResponse> => {
    const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
    const resp = await fetch('/monitoreoLaboratorio/v3/api/shifts.php', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    return resp.json();
};

const fetchTicketsPage = async (page: number): Promise<{ tickets: SFCase[]; total: number; per_page: number; page: number; }> => {
    const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
    const resp = await fetch(`/monitoreoLaboratorio/v3/api/shifts.php?action=tickets&page=${page}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    return resp.json();
};

const ShiftsPage = () => {
    const [selectedSfTicket, setSelectedSfTicket] = useState<SFCase | null>(null);
    const [isSfModalOpen, setIsSfModalOpen] = useState(false);
    const [sfTicketComments, setSfTicketComments] = useState<any[]>([]);
    const [isLoadingComments, setIsLoadingComments] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('ALL');
    const [activeTab, setActiveTab] = useState<'all' | 'today' | 'shift'>('all');
    const [currentPage, setCurrentPage] = useState(1);
    const [pageTickets, setPageTickets] = useState<SFCase[]>([]);
    const [pageLoading, setPageLoading] = useState(false);

    const handleOpenTicketDetails = async (ticket: SFCase) => {
        setSelectedSfTicket(ticket);
        setIsSfModalOpen(true);
        setIsLoadingComments(true);
        setSfTicketComments([]);

        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const res = await fetch(`/monitoreoLaboratorio/v3/api/admin.php?action=bot_sf_ticket_comments&case_id=${ticket.CaseId}`, {
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

    const handlePageChange = async (newPage: number) => {
        if (newPage === currentPage) return;
        setCurrentPage(newPage);
        if (newPage === 1) { setPageTickets([]); return; }
        setPageLoading(true);
        try {
            const result = await fetchTicketsPage(newPage);
            setPageTickets(result.tickets ?? []);
        } catch (e) {
            console.error('Error fetching page:', e);
        } finally {
            setPageLoading(false);
        }
    };


    const { data, isLoading, error } = useQuery<ShiftResponse>({
        queryKey: ['shiftsData'],
        queryFn: fetchShiftsData,
        refetchInterval: 15000 // Refresh every 15s for tickets
    });

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center h-96 gap-4">
                <Loader2 className="w-10 h-10 text-primary animate-spin" />
                <p className="text-muted-foreground font-medium animate-pulse">Cargando panel de turnos...</p>
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="flex flex-col items-center justify-center h-96 gap-4 text-center">
                <AlertCircle className="w-12 h-12 text-destructive" />
                <p className="text-destructive font-bold text-lg">Error al conectar con la API de turnos</p>
                <p className="text-muted-foreground text-sm max-w-md">Verifique que su sesión siga activa y que tenga los permisos correctos.</p>
            </div>
        );
    }

    const { config, shift1_members, shift2_members, active_tickets, inactive_tickets, total_stats, pagination } = data;

    // Tickets to show in the current page of the table
    const displayedTickets = currentPage === 1 ? active_tickets : pageTickets;
    // totalPages computed inside renderPaginator directly from pagination

    const todayTickets = active_tickets.filter(t => {
        if (!t.CreatedDate) return false;
        const created = new Date(t.CreatedDate.replace(' ', 'T'));
        const today = new Date();
        return created.getDate() === today.getDate() &&
            created.getMonth() === today.getMonth() &&
            created.getFullYear() === today.getFullYear();
    });

    const currentShiftTickets = active_tickets.filter(t => {
        if (!t.CreatedDate) return false;
        const created = new Date(t.CreatedDate.replace(' ', 'T')).getTime();
        const startHour = config.start_hour || '08:00:00';
        const combinedStart = `${config.start_date}T${startHour}`;
        const shiftStart = new Date(combinedStart).getTime();
        return created >= shiftStart;
    });

    const tabTickets = (() => {
        if (activeTab === 'today') return todayTickets;
        if (activeTab === 'shift') return currentShiftTickets;
        // For 'all' tab, show displayedTickets (current page)
        return displayedTickets;
    })();

    const filteredActiveTickets = tabTickets.filter((t) => {
        const matchesSearch = !searchTerm ? true : (
            t.CaseNumber?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            t.Faena?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            t.AccountName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            t.Subject?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            t.OwnerName?.toLowerCase().includes(searchTerm.toLowerCase())
        );
        const matchesStatus = statusFilter === 'ALL'
            ? true
            : statusFilter === 'ACTIVOS'
                ? t.Status?.toLowerCase() !== 'closed'
                : t.Status === statusFilter;
        return matchesSearch && matchesStatus;
    });

    // Stats are computed from total_stats for 'all', otherwise from local tabTickets
    const stats = (() => {
        if (activeTab === 'all') {
            return {
                total: total_stats?.total ?? 0,
                closed: total_stats?.closed ?? 0,
                seeking: total_stats?.seeking ?? 0,
                open: total_stats?.open ?? 0,
                avgResolutionTime: total_stats?.avg_resolution ?? 'N/A',
            };
        }

        let closedCount = 0;
        let seekingCount = 0;
        let openCount = 0;
        let totalResolutionTime = 0;
        let resolvedCount = 0;

        tabTickets.forEach(t => {
            const status = t.Status?.toLowerCase() || '';
            if (status === 'closed') {
                closedCount++;
                if (t.CreatedDate && t.ClosedDate) {
                    const created = new Date(t.CreatedDate).getTime();
                    const closed = new Date(t.ClosedDate).getTime();
                    if (!isNaN(created) && !isNaN(closed) && closed >= created) {
                        totalResolutionTime += (closed - created);
                        resolvedCount++;
                    }
                }
            } else if (status === 'seeking client input' || status === 'seeking client' || status === 'esperando cliente') {
                seekingCount++;
            } else {
                openCount++;
            }
        });

        let avgRes = 'N/A';
        if (resolvedCount > 0) {
            const avgTimeMs = totalResolutionTime / resolvedCount;
            const avgHours = Math.floor(avgTimeMs / (1000 * 60 * 60));
            const avgMins = Math.floor((avgTimeMs % (1000 * 60 * 60)) / (1000 * 60));
            if (avgHours > 0) {
                avgRes = `${avgHours}h ${avgMins}m`;
            } else {
                avgRes = `${avgMins}m`;
            }
        }

        return {
            total: tabTickets.length,
            closed: closedCount,
            seeking: seekingCount,
            open: openCount,
            avgResolutionTime: avgRes
        };
    })();

    const activeAlias = config.active_shift === 1 ? config.shift1_alias : config.shift2_alias;
    const activeMembers = config.active_shift === 1 ? shift1_members : shift2_members;

    // Calculate dates
    const start = new Date(config.start_date + 'T00:00:00');
    const end = new Date(start);
    end.setDate(start.getDate() + 6); // Wednesday + 6 days = Tuesday

    const formatDate = (date: Date) => {
        return date.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' });
    };

    const isMemberWorking = (isActiveTurn: boolean, shiftType: string | null | undefined) => {
        if (!isActiveTurn) return false;

        const now = new Date();
        const currentHour = now.getHours();

        const startHourStr = config?.start_hour || '08:00:00';
        const startHourVal = parseInt(startHourStr.split(':')[0], 10) || 8;
        const nightStartHour = (startHourVal + 12) % 24;

        const isDayShift = shiftType === 'Día' || !shiftType;

        if (isDayShift) {
            if (startHourVal < nightStartHour) {
                return currentHour >= startHourVal && currentHour < nightStartHour;
            } else {
                return currentHour >= startHourVal || currentHour < nightStartHour;
            }
        } else {
            if (nightStartHour < startHourVal) {
                return currentHour >= nightStartHour && currentHour < startHourVal;
            } else {
                return currentHour >= nightStartHour || currentHour < startHourVal;
            }
        }
    };

    const formatTimeElapsed = (dateString: string) => {
        if (!dateString) return '--';
        const now = new Date();
        const created = new Date(dateString);
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

    const renderTicketsTable = () => {
        if (filteredActiveTickets.length === 0) {
            if (active_tickets.length > 0) {
                return (
                    <div className="p-8 text-center text-xs text-muted-foreground">
                        No se encontraron tickets con los filtros aplicados.
                    </div>
                );
            }
            return (
                <div className="p-8 text-center text-xs text-muted-foreground">
                    No hay tickets activos asignados a este turno en Salesforce.
                </div>
            );
        }

        const sortedActiveTickets = [...filteredActiveTickets].sort((a, b) => {
            const isClosedA = a.Status?.toLowerCase() === 'closed';
            const isClosedB = b.Status?.toLowerCase() === 'closed';

            if (isClosedA !== isClosedB) return isClosedA ? 1 : -1;

            const timeA = new Date(a.CreatedDate?.replace(' ', 'T') || 0).getTime();
            const timeB = new Date(b.CreatedDate?.replace(' ', 'T') || 0).getTime();
            return timeB - timeA;
        });

        const openTickets = sortedActiveTickets.filter(t => t.Status?.toLowerCase() !== 'closed');
        const closedTickets = sortedActiveTickets.filter(t => t.Status?.toLowerCase() === 'closed');

        const renderTicketRow = (t: SFCase, idx: number) => {
            const isClosed = t.Status?.toLowerCase() === 'closed';
            const isWorking = t.Status?.toLowerCase() === 'working';
            const isAssigned = t.Status?.toLowerCase() === 'assigned';
            const isSeeking = t.Status?.toLowerCase().includes('seeking');
            const isQueueTicket = t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue');
            const highlightRed = isQueueTicket && !isClosed;

            return (
                <tr
                    key={t.CaseId || idx}
                    className={cn(
                        "transition-colors group",
                        isClosed
                            ? "bg-slate-100/40 dark:bg-slate-800/20 opacity-70 grayscale hover:bg-slate-100/60 dark:hover:bg-slate-800/40"
                            : highlightRed
                                ? "bg-rose-50/80 dark:bg-rose-950/20 border-l-2 border-l-rose-500 hover:bg-rose-100/80 dark:hover:bg-rose-950/40"
                                : isWorking
                                    ? "bg-emerald-50/80 dark:bg-emerald-950/20 border-l-2 border-l-emerald-500 hover:bg-emerald-100/80 dark:hover:bg-emerald-950/40"
                                    : isSeeking
                                        ? "bg-amber-50/80 dark:bg-amber-950/20 border-l-2 border-l-amber-500 hover:bg-amber-100/80 dark:hover:bg-amber-950/40"
                                        : isAssigned
                                            ? "bg-blue-50/80 dark:bg-blue-950/20 border-l-2 border-l-blue-500 hover:bg-blue-100/80 dark:hover:bg-blue-950/40"
                                            : "hover:bg-slate-50 dark:hover:bg-muted/20"
                    )}
                >
                    <td className="px-4 py-3">
                        <div className="flex flex-col gap-1.5 items-center w-full text-center">
                            <a
                                href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className={cn(
                                    "text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit mx-auto",
                                    isClosed
                                        ? "font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale"
                                        : highlightRed
                                            ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                            : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                )}
                            >
                                {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                            </a>
                            <span className={cn(
                                "text-[9px] font-black uppercase tracking-tighter w-fit mx-auto",
                                isClosed ? "text-slate-400" : highlightRed ? "text-rose-600 dark:text-rose-400" : "text-amber-600 dark:text-amber-500"
                            )}>
                                {t.Faena || 'Global'}
                            </span>
                        </div>
                    </td>
                    <td className="px-4 py-3 max-w-xs md:max-w-md">
                        <p className={cn(
                            "text-[11px] line-clamp-1 group-hover:line-clamp-none transition-all",
                            isClosed
                                ? "font-normal text-slate-400 dark:text-slate-500"
                                : highlightRed
                                    ? "font-bold text-rose-600 dark:text-rose-400"
                                    : "font-bold text-foreground"
                        )}>
                            {t.Subject || '(Sin asunto)'}
                        </p>
                    </td>
                    <td className="px-4 py-3">
                        <div className="flex items-center gap-1.5">
                            <div className={cn(
                                "w-1.5 h-1.5 rounded-full",
                                isClosed
                                    ? "bg-slate-300"
                                    : highlightRed
                                        ? "bg-rose-500 animate-pulse"
                                        : "bg-emerald-500 animate-pulse"
                            )}></div>
                            <span className={cn(
                                "text-[10px] uppercase tracking-tight",
                                isClosed
                                    ? "font-normal text-slate-400"
                                    : highlightRed
                                        ? "font-black text-rose-600 dark:text-rose-400"
                                        : "font-black text-foreground/80"
                            )}>{t.Status}</span>
                        </div>
                    </td>
                    <td className="px-4 py-3">
                        <div className="flex flex-col">
                            <span className={cn(
                                "text-[11px] uppercase",
                                isClosed
                                    ? "font-normal text-slate-400"
                                    : highlightRed
                                        ? "font-black text-rose-600 dark:text-rose-400"
                                        : "font-black text-foreground/90"
                            )}>
                                {formatTimeElapsed(t.CreatedDate)}
                            </span>
                            <span className="text-[9px] font-medium text-muted-foreground whitespace-nowrap opacity-70">
                                {new Date(t.CreatedDate).toLocaleDateString()}
                            </span>
                        </div>
                    </td>
                    <td className="px-4 py-3 text-center">
                        <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-muted/50 rounded-lg">
                            <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                            <span className={cn(
                                "text-[10px]",
                                isClosed
                                    ? "font-normal text-slate-400"
                                    : "font-black text-foreground"
                            )}>{t.CommentCount || 0}</span>
                        </div>
                    </td>
                    <td className="px-4 py-3 text-center">
                        <span className={cn(
                            "text-[10px] px-2 py-1 rounded-md whitespace-nowrap border",
                            isClosed
                                ? "font-normal text-slate-400/80 bg-slate-50/50 dark:bg-muted/30 border-slate-200/30"
                                : highlightRed
                                    ? "font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 border-rose-500/20"
                                    : "font-bold text-foreground/80 bg-slate-100/70 dark:bg-muted/70 border-slate-200/50 dark:border-border/50"
                        )}>
                            {getOwnerAlias(t.OwnerName)}
                        </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                        <button
                            onClick={() => handleOpenTicketDetails(t)}
                            className="p-2 hover:bg-primary/10 text-primary rounded-xl transition-all"
                            title="Ver Detalles"
                        >
                            <Eye className="w-4 h-4" />
                        </button>
                    </td>
                </tr>
            );
        };

        return (
            <table className="w-full text-left border-collapse">
                <thead className="sticky top-0 bg-white/95 dark:bg-card/95 backdrop-blur-sm shadow-sm z-10">
                    <tr className="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-black tracking-widest border-b bg-slate-50/70 dark:bg-muted/30">
                        <th className="px-4 py-3 text-center">Ticket & Faena</th>
                        <th className="px-4 py-3">Asunto</th>
                        <th className="px-4 py-3">Estado</th>
                        <th className="px-4 py-3">Time</th>
                        <th className="px-4 py-3 text-center">Coments</th>
                        <th className="px-4 py-3 text-center">Owner</th>
                        <th className="px-4 py-3 text-right">Ver</th>
                    </tr>
                </thead>

                {openTickets.length > 0 && (
                    <tbody className="divide-y divide-slate-100 dark:divide-border/40 text-[11px]">
                        {openTickets.map((t, idx) => renderTicketRow(t, idx))}
                    </tbody>
                )}

                {openTickets.length > 0 && closedTickets.length > 0 && (
                    <tbody>
                        <tr className="bg-slate-100/50 dark:bg-slate-800/20">
                            <td colSpan={7} className="py-2">
                                <div className="w-full flex items-center justify-center gap-3">
                                    <div className="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                    <span className="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Tickets Cerrados</span>
                                    <div className="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                )}

                {closedTickets.length > 0 && (
                    <tbody className="divide-y divide-slate-100 dark:divide-border/40 text-[11px]">
                        {closedTickets.map((t, idx) => renderTicketRow(t, openTickets.length + idx))}
                    </tbody>
                )}
            </table>
        );
    };

    const renderPaginator = () => {
        console.log("Pagination Data:", pagination);
        if (!pagination) return <div className="p-4 text-red-500">No pagination data received from API</div>;
        if (pagination.total <= pagination.per_page) return <div className="p-4 text-orange-500">Total ({pagination.total}) &lt;= Per Page ({pagination.per_page})</div>;
        const totalPgs = Math.ceil(pagination.total / pagination.per_page);
        return (
            <div className="flex items-center justify-between px-4 py-3 border-t border-border/60 bg-muted/5">
                <span className="text-[10px] font-bold text-muted-foreground">
                    Mostrando {((currentPage - 1) * pagination.per_page) + 1}–{Math.min(currentPage * pagination.per_page, pagination.total)} de {pagination.total} tickets
                    {pageLoading && <span className="ml-2 text-primary animate-pulse">Cargando...</span>}
                </span>
                <div className="flex items-center gap-1">
                    <button
                        onClick={() => handlePageChange(1)}
                        disabled={currentPage === 1 || pageLoading}
                        className="px-2 py-1 text-[10px] font-black rounded border border-border/60 hover:bg-primary hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                    >«</button>
                    <button
                        onClick={() => handlePageChange(currentPage - 1)}
                        disabled={currentPage === 1 || pageLoading}
                        className="px-2 py-1 text-[10px] font-black rounded border border-border/60 hover:bg-primary hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                    >‹</button>
                    {Array.from({ length: Math.min(5, totalPgs) }, (_, i) => {
                        const start = Math.max(1, Math.min(currentPage - 2, totalPgs - 4));
                        const pg = start + i;
                        if (pg > totalPgs) return null;
                        return (
                            <button
                                key={pg}
                                onClick={() => handlePageChange(pg)}
                                disabled={pageLoading}
                                className={cn(
                                    "px-2.5 py-1 text-[10px] font-black rounded border transition-all",
                                    pg === currentPage
                                        ? "bg-primary text-white border-primary shadow-sm"
                                        : "border-border/60 hover:bg-primary/10 disabled:opacity-50"
                                )}
                            >{pg}</button>
                        );
                    })}
                    <button
                        onClick={() => handlePageChange(currentPage + 1)}
                        disabled={currentPage === totalPgs || pageLoading}
                        className="px-2 py-1 text-[10px] font-black rounded border border-border/60 hover:bg-primary hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                    >›</button>
                    <button
                        onClick={() => handlePageChange(totalPgs)}
                        disabled={currentPage === totalPgs || pageLoading}
                        className="px-2 py-1 text-[10px] font-black rounded border border-border/60 hover:bg-primary hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-all"
                    >»</button>
                </div>
            </div>
        );
    };

    // Cap elapsed progress bar at 7 days
    const percentElapsed = Math.min(100, Math.max(0, (config.days_elapsed / 7) * 100)); return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {/* Left Side: Control Panel & Resumen */}
                <div className={cn("flex flex-col gap-6", inactive_tickets.length > 0 ? "xl:col-span-2" : "xl:col-span-3")}>
                    {/* Unified Control Panel: general info and rosters at the top */}
                    <div>
                        <h2 className="text-lg font-black uppercase tracking-tight text-foreground mb-3 flex items-center gap-2">
                            <Clock className="w-5 h-5 text-primary" />
                            Turno 7x7
                        </h2>
                        <div className="flex items-center bg-card border border-border/50 rounded-xl px-4 py-3 shadow-sm w-full gap-4 overflow-x-auto whitespace-nowrap scrollbar-hide relative z-10">

                {/* Cycle Info */}
                <div className="flex items-center gap-3 border-r border-border/50 pr-4 shrink-0">
                    <div className="flex flex-col gap-1 shrink-0">
                        <div className="flex items-center gap-2 text-[10px] font-bold">
                            <span className="text-primary-foreground uppercase tracking-widest bg-primary px-1.5 py-[2px] rounded leading-none shrink-0">Día {config.days_elapsed}/7</span>
                            <span className="text-muted-foreground shrink-0">{formatDate(start)} al {formatDate(end)}</span>
                        </div>
                        <div className="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div
                                className={`h-full transition-all duration-500 rounded-full ${config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'}`}
                                style={{ width: `${percentElapsed}%` }}
                            ></div>
                        </div>
                    </div>
                </div>

                {/* Active Shift */}
                <div className="flex items-center gap-3 border-r border-border/50 pr-4 shrink-0">
                    <span className="font-black text-sm uppercase flex items-center gap-1.5 text-foreground">
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        {activeAlias} <span className="text-[9px] text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1 py-0.5 rounded ml-1">ACTIVO</span>
                    </span>

                    {/* Active Members */}
                    <div className="flex items-center gap-1">
                        <Sun className="w-3.5 h-3.5 text-amber-500 ml-1" />
                        {activeMembers.filter(m => m.turno_tipo === 'Día' || !m.turno_tipo).map(m => (
                            <div key={m.id} className="flex items-center gap-1 bg-background border border-border/50 rounded-full px-2 py-0.5 shadow-sm text-xs">
                                <span className="font-bold text-foreground">{m.first_name} {m.last_name.charAt(0)}.</span>
                                <div className="scale-[0.8] origin-left -my-1 ml-0.5 shrink-0"><ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} /></div>
                            </div>
                        ))}
                        {activeMembers.filter(m => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && <span className="text-[10px] text-muted-foreground italic">Vacío</span>}

                        <Moon className="w-3.5 h-3.5 text-purple-500 ml-3" />
                        {activeMembers.filter(m => m.turno_tipo === 'Noche').map(m => (
                            <div key={m.id} className="flex items-center gap-1 bg-background border border-border/50 rounded-full px-2 py-0.5 shadow-sm text-xs">
                                <span className="font-bold text-foreground">{m.first_name} {m.last_name.charAt(0)}.</span>
                                <div className="scale-[0.8] origin-left -my-1 ml-0.5 shrink-0"><ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} /></div>
                            </div>
                        ))}
                        {activeMembers.filter(m => m.turno_tipo === 'Noche').length === 0 && <span className="text-[10px] text-muted-foreground italic">Vacío</span>}
                    </div>
                </div>


                {/* Handoff Button (El Switch) */}
                <div className="shrink-0 flex-1 flex justify-end">
                    <SubShiftHandoff
                        openTickets={active_tickets
                            .filter(t => t.Status?.toLowerCase() !== 'closed')
                            .map(t => ({
                                case_id: t.CaseId,
                                case_number: t.CaseNumber,
                                status: t.Status,
                                subject: t.Subject,
                                faena: t.Faena,
                                description: t.Description,
                                comment_count: t.CommentCount,
                                comments: t.Comments || [],
                                created_date: t.CreatedDate,
                            }))}
                        isActiveGroup={true}
                        cycleDay={config.days_elapsed}
                        activeMembers={activeMembers}
                    />
                </div>

                        </div>
                    </div>

            {/* Resumen Tickets Turno 7x7 */}
            <div className="bg-card dark:bg-card/95 border border-border rounded-2xl p-4 shadow-sm relative overflow-hidden">
                <div className="absolute top-0 left-0 w-1.5 h-full bg-primary/80"></div>

                <h3 className="text-[11px] font-black uppercase tracking-widest text-muted-foreground mb-3 flex items-center gap-2">
                    <Activity className="w-3.5 h-3.5" />
                    Resumen Tickets Turno 7x7
                </h3>

                <div className="grid grid-cols-2 sm:grid-cols-5 gap-4">

                    <div className="flex flex-col p-3 bg-blue-50/50 dark:bg-blue-950/20 rounded-lg border border-blue-100 dark:border-blue-900/30">
                        <span className="text-[10px] text-blue-600/70 dark:text-blue-400/70 font-bold uppercase tracking-wider mb-1">Assigned</span>
                        <span className="text-3xl font-black text-blue-600 dark:text-blue-400">{total_stats?.assigned ?? 0}</span>
                    </div>

                    <div className="flex flex-col p-3 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-lg border border-emerald-100 dark:border-emerald-900/30">
                        <span className="text-[10px] text-emerald-600/70 dark:text-emerald-400/70 font-bold uppercase tracking-wider mb-1">Working</span>
                        <span className="text-3xl font-black text-emerald-600 dark:text-emerald-400">{total_stats?.working ?? 0}</span>
                    </div>

                    <div className="flex flex-col p-3 bg-amber-50/50 dark:bg-amber-950/20 rounded-lg border border-amber-100 dark:border-amber-900/30">
                        <span className="text-[10px] text-amber-600/70 dark:text-amber-400/70 font-bold uppercase tracking-wider mb-1">Seeking</span>
                        <span className="text-3xl font-black text-amber-600 dark:text-amber-400">{total_stats?.seeking ?? 0}</span>
                    </div>

                    <div className="flex flex-col p-3 bg-slate-100/50 dark:bg-slate-800/40 rounded-lg border border-slate-200 dark:border-slate-700/50 opacity-80">
                        <span className="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-1">Closed</span>
                        <span className="text-3xl font-black text-slate-600 dark:text-slate-300">{total_stats?.closed ?? 0}</span>
                    </div>

                    <div className="flex flex-col p-3 bg-rose-50 dark:bg-rose-950/30 rounded-lg border border-rose-200 dark:border-rose-900/50 relative overflow-hidden group">
                        <div className="absolute inset-0 bg-rose-500/5 group-hover:bg-rose-500/10 transition-colors"></div>
                        <span className="text-[10px] text-rose-600 dark:text-rose-400 font-black uppercase tracking-wider mb-1 z-10 truncate" title="S.A. Queue">S.A. Queue</span>
                        <span className="text-3xl font-black text-rose-700 dark:text-rose-300 z-10 flex items-center gap-2">
                            {total_stats?.sa_queue ?? 0}
                            {(total_stats?.sa_queue ?? 0) > 0 && <span className="relative flex h-3 w-3 ml-2"><span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span><span className="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span></span>}
                        </span>
                    </div>
                </div>
            </div>
            </div> {/* End Left Side */}

            {/* Inactive Tickets Warning List (Moved to top row) */}
            {inactive_tickets.length > 0 && (
                <div className="xl:col-span-1 space-y-6 h-full min-h-0">
                    <div className="bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm flex flex-col h-full max-h-[350px]">
                        <div className="p-4 border-b border-border/60 bg-destructive/5 shrink-0">
                            <h3 className="text-xs font-black text-destructive flex items-center gap-1.5">
                                <AlertCircle className="w-4 h-4 shrink-0 animate-pulse" />
                                Tickets del Turno Inactivo ({inactive_tickets.length})
                            </h3>
                            <p className="text-[8px] text-muted-foreground mt-0.5">Deben ser transferidos al turno activo en Salesforce.</p>
                        </div>
                        <div className="divide-y divide-border overflow-y-auto grow min-h-0">
                            {inactive_tickets.map((t) => (
                                <div key={t.CaseId} className="p-3 hover:bg-muted/30 transition-colors flex items-center justify-between gap-3 opacity-90">
                                    <div className="min-w-0 space-y-0.5">
                                        <div className="flex items-center gap-1.5 flex-wrap">
                                            <span className="text-[11px] font-black text-blue-600 hover:underline cursor-pointer" onClick={() => handleOpenTicketDetails(t)}>
                                                #{t.CaseNumber}
                                            </span>
                                            <span className="text-[8px] font-black uppercase tracking-tight text-amber-600 bg-amber-500/5 px-1 py-0.2 rounded border border-amber-500/10">
                                                {t.Faena || 'N/A'}
                                            </span>
                                            <span className={cn(
                                                "text-[8px] font-black uppercase tracking-tight px-1.5 py-0.2 rounded border",
                                                t.Status?.toLowerCase() === 'closed'
                                                    ? "bg-slate-500/10 text-slate-500 border-slate-500/20"
                                                    : (t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue'))
                                                        ? "bg-rose-500/10 text-rose-600 border-rose-500/20 animate-pulse"
                                                        : "bg-emerald-500/10 text-emerald-600 border-emerald-500/20"
                                            )}>
                                                {t.Status}
                                            </span>
                                        </div>
                                        <p className="text-xs font-bold text-foreground truncate">{t.Subject}</p>
                                        <p className="text-[8px] text-muted-foreground">Owner: {t.OwnerName}</p>
                                    </div>
                                    <button
                                        onClick={() => handleOpenTicketDetails(t)}
                                        className="text-muted-foreground hover:text-foreground p-1 hover:bg-muted rounded transition-colors shrink-0"
                                    >
                                        <Eye className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
            </div> {/* End Top Grid */}

            {/* Tickets Grid */}
            <div className="w-full gap-6 items-start">

                {/* Active Tickets List */}
                <div className="w-full space-y-6">
                    <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-sm">
                        <div className="p-4 border-b border-border/60 bg-muted/10 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                            {/* Tabs Navigation */}
                            <div className="flex border border-border/60 p-0.5 bg-background dark:bg-muted/20 gap-0.5 rounded-xl shrink-0 w-fit">
                                <button
                                    onClick={() => { setActiveTab('all'); setStatusFilter('ALL'); }}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5",
                                        activeTab === 'all'
                                            ? "bg-primary text-white shadow-sm"
                                            : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Ticket className="w-3.5 h-3.5" />
                                    General ({total_stats?.total ?? 0})
                                </button>
                                <button
                                    onClick={() => { setActiveTab('today'); setStatusFilter('ALL'); }}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5",
                                        activeTab === 'today'
                                            ? "bg-primary text-white shadow-sm"
                                            : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Clock className="w-3.5 h-3.5" />
                                    Creados Hoy ({todayTickets.length})
                                </button>
                                <button
                                    onClick={() => { setActiveTab('shift'); setStatusFilter('ALL'); }}
                                    className={cn(
                                        "px-3 py-1.5 rounded-lg text-[11px] font-black transition-all flex items-center gap-1.5",
                                        activeTab === 'shift'
                                            ? "bg-primary text-white shadow-sm"
                                            : "text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                    )}
                                >
                                    <Calendar className="w-3.5 h-3.5" />
                                    Creados en el Turno ({currentShiftTickets.length})
                                </button>
                            </div>

                            {/* Search & Status Filters */}
                            <div className="flex items-center gap-2 max-w-md w-full lg:w-auto shrink-0">
                                {/* Status Filter */}
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="px-2 py-1.5 bg-background border border-border rounded-lg text-[11px] font-bold text-foreground/80 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-border-hover dark:bg-card"
                                >
                                    <option value="ALL">Todos los Estados</option>
                                    <option value="ACTIVOS">ACTIVOS (No Cerrados)</option>
                                    {Array.from(new Set(active_tickets.map((t: any) => t.Status).filter(Boolean)))
                                        .map((status: any) => (
                                            <option key={status} value={status}>
                                                {status.toUpperCase()}
                                            </option>
                                        ))}
                                </select>

                                {/* Search Bar */}
                                <div className="relative group flex-1 lg:w-48">
                                    <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                    <input
                                        type="text"
                                        placeholder="Buscar..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="w-full pl-8 pr-3 py-1.5 bg-background border border-border rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all dark:bg-card"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Tab Content Body */}
                        <div className="grid grid-cols-1 lg:grid-cols-5 divide-y lg:divide-y-0 lg:divide-x divide-border/60">
                            {/* Left Side (Table wrapper) */}
                            <div className="lg:col-span-3 flex flex-col justify-between max-h-[550px]">
                                <div className="overflow-x-auto overflow-y-auto">
                                    {renderTicketsTable()}
                                </div>
                                {activeTab === 'all' && renderPaginator()}
                            </div>

                            {/* Right Side (Analytics Sidebar) */}
                            <div className="lg:col-span-2 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">
                                    {/* Donut Chart */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between border-b border-border/40 pb-1.5 shrink-0">
                                            <span className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Distribución</span>
                                            <span className="text-[9px] font-extrabold text-muted-foreground bg-muted dark:bg-muted/30 px-2 py-0.5 rounded-full">Total: {stats.total}</span>
                                        </div>

                                        {stats.total > 0 ? (
                                            <div className="flex flex-col items-center">
                                                <div className="w-64 h-64 shrink-0 relative flex items-center justify-center">
                                                    <ResponsiveContainer width="100%" height="100%">
                                                        <PieChart>
                                                            <Pie
                                                                data={[
                                                                    { name: 'WORKING', value: stats.open, color: '#6366f1' },
                                                                    { name: 'SEEKING', value: stats.seeking, color: '#f59e0b' },
                                                                    { name: 'CLOSED', value: stats.closed, color: '#10b981' }
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
                                                                    { name: 'CLOSED', value: stats.closed, color: '#10b981' }
                                                                ].filter(d => d.value > 0).map((entry, index) => (
                                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                                ))}
                                                            </Pie>
                                                            <RechartsTooltip
                                                                contentStyle={{
                                                                    fontSize: '9px',
                                                                    borderRadius: '8px',
                                                                    background: 'rgba(255,255,255,0.95)',
                                                                    border: '1px solid #e2e8f0',
                                                                    padding: '4px 8px',
                                                                    boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)'
                                                                }}
                                                            />
                                                        </PieChart>
                                                    </ResponsiveContainer>
                                                    <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                        <span className="text-xs font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total</span>
                                                        <span className="text-4xl font-black text-foreground leading-none">{stats.total}</span>
                                                    </div>
                                                </div>

                                                {/* Legend (Horizontal & Compact) */}
                                                <div className="w-full mt-2 flex flex-wrap justify-center gap-x-3 gap-y-1.5 text-[9px] border-t border-slate-100 dark:border-slate-800/40 pt-2">
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                                        WORKING: <span className="text-foreground font-black">{stats.open} ({stats.total > 0 ? Math.round((stats.open / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                        SEEKING: <span className="text-foreground font-black">{stats.seeking} ({stats.total > 0 ? Math.round((stats.seeking / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        CLOSED: <span className="text-foreground font-black">{stats.closed} ({stats.total > 0 ? Math.round((stats.closed / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="py-6 text-center text-[10px] text-muted-foreground italic font-semibold">Sin datos para graficar</div>
                                        )}
                                    </div>

                                    <div className="border-t border-border/60 my-0.5 shrink-0"></div>

                                    {/* 2x2 Grid of 4 KPIs */}
                                    <div className="grid grid-cols-2 gap-2">
                                        {/* Card 1: WORKING */}
                                        <div className="bg-background rounded-xl border border-border/50 p-2.5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                                            <div className="space-y-0.5 z-10 min-w-0">
                                                <p className="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">WORKING</p>
                                                <p className="text-sm font-black text-foreground tracking-tight leading-none">{stats.open}</p>
                                                <p className="text-[8px] text-indigo-500 dark:text-indigo-400/80 font-bold truncate">Atención activa</p>
                                            </div>
                                            <div className="w-6 h-6 rounded-md bg-indigo-500/10 dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 z-10 shrink-0">
                                                <Activity className="w-3.5 h-3.5 animate-pulse" />
                                            </div>
                                            <div className="absolute -bottom-6 -right-6 w-10 h-10 bg-indigo-500/5 rounded-full blur-lg group-hover:bg-indigo-500/10 transition-colors"></div>
                                        </div>

                                        {/* Card 2: SEEKING */}
                                        <div className="bg-background rounded-xl border border-border/50 p-2.5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                                            <div className="space-y-0.5 z-10 min-w-0">
                                                <p className="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">SEEKING</p>
                                                <p className="text-sm font-black text-foreground tracking-tight leading-none">{stats.seeking}</p>
                                                <p className="text-[8px] text-amber-500 dark:text-amber-400/80 font-bold truncate">Espera cliente</p>
                                            </div>
                                            <div className="w-6 h-6 rounded-md bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 z-10 shrink-0">
                                                <HelpCircle className="w-3.5 h-3.5" />
                                            </div>
                                            <div className="absolute -bottom-6 -right-6 w-10 h-10 bg-amber-500/5 rounded-full blur-lg group-hover:bg-amber-500/10 transition-colors"></div>
                                        </div>

                                        {/* Card 3: CLOSED */}
                                        <div className="bg-background rounded-xl border border-border/50 p-2.5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                                            <div className="space-y-0.5 z-10 min-w-0">
                                                <p className="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">CLOSED</p>
                                                <p className="text-sm font-black text-foreground tracking-tight leading-none">{stats.closed}</p>
                                                <p className="text-[8px] text-emerald-500 dark:text-emerald-400/80 font-bold truncate">Casos resueltos</p>
                                            </div>
                                            <div className="w-6 h-6 rounded-md bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 z-10 shrink-0">
                                                <CheckCircle2 className="w-3.5 h-3.5" />
                                            </div>
                                            <div className="absolute -bottom-6 -right-6 w-10 h-10 bg-emerald-500/5 rounded-full blur-lg group-hover:bg-emerald-500/10 transition-colors"></div>
                                        </div>

                                        {/* Card 4: PROM. CIERRE */}
                                        <div className="bg-background rounded-xl border border-border/50 p-2.5 flex items-center justify-between shadow-sm relative overflow-hidden group">
                                            <div className="space-y-0.5 z-10 min-w-0">
                                                <p className="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">PROM. CIERRE</p>
                                                <p className="text-sm font-black text-foreground tracking-tight leading-none">{stats.avgResolutionTime}</p>
                                                <p className="text-[8px] text-sky-500 dark:text-sky-400/80 font-bold truncate">Prom. resolución</p>
                                            </div>
                                            <div className="w-6 h-6 rounded-md bg-sky-500/10 dark:bg-sky-500/20 flex items-center justify-center text-sky-600 dark:text-sky-400 z-10 shrink-0">
                                                <Clock className="w-3.5 h-3.5" />
                                            </div>
                                            <div className="absolute -bottom-6 -right-6 w-10 h-10 bg-sky-500/5 rounded-full blur-lg group-hover:bg-sky-500/10 transition-colors"></div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>

            </div>

            {/* Salesforce Ticket Modal */}
            {isSfModalOpen && selectedSfTicket && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
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
                                    <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{selectedSfTicket.Faena || selectedSfTicket.AccountName || 'Global'}</p>
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
                                    <MessageSquare className="w-3.5 h-3.5" /> Comentarios ({sfTicketComments.length})
                                </h4>

                                <div className="space-y-3">
                                    {isLoadingComments ? (
                                        <div className="py-12 text-center">
                                            <div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-2"></div>
                                            <p className="text-xs text-muted-foreground font-bold">Cargando comentarios...</p>
                                        </div>
                                    ) : sfTicketComments.length > 0 ? (
                                        sfTicketComments.map((comment: any, idx: number) => (
                                            <div key={idx} className="bg-card border rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="text-[10px] font-black text-primary uppercase tracking-tight">{comment.Author || 'Sistema'}</span>
                                                    <span className="text-[9px] font-mono text-muted-foreground italic">{new Date(comment.CreatedDate).toLocaleString()}</span>
                                                </div>
                                                <p className="text-[11px] leading-relaxed text-foreground/80 whitespace-pre-wrap">
                                                    {comment.CommentBody}
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
                                href={`https://usa1.lightning.force.com/lightning/r/Case/${selectedSfTicket.CaseId}/view`}
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

export default ShiftsPage;
