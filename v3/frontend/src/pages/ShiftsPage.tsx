import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Clock, Ticket, Calendar, AlertCircle, Eye, Loader2, X, Sun, Moon, ExternalLink, MessageSquare, Search, CheckCircle2, HelpCircle, Activity, Home } from 'lucide-react';
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
}

const fetchShiftsData = async (): Promise<ShiftResponse> => {
    const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
    const resp = await fetch('/monitoreoLaboratorio/v3/api/shifts.php', {
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

    const { config, shift1_members, shift2_members, active_tickets, inactive_tickets } = data;

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
        return active_tickets;
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

    const stats = (() => {
        const total = tabTickets.length;
        const closed = tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length;
        const seeking = tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length;
        const open = tabTickets.filter(t => {
            const statusLower = t.Status?.toLowerCase() || '';
            return statusLower !== 'closed' && !statusLower.includes('seeking');
        }).length;

        const closedWithDates = tabTickets.filter(t => t.Status?.toLowerCase() === 'closed' && t.ClosedDate && t.CreatedDate);
        let avgResolutionTime = 'N/A';
        if (closedWithDates.length > 0) {
            let totalMs = 0;
            closedWithDates.forEach(t => {
                const created = new Date(t.CreatedDate.replace(' ', 'T')).getTime();
                const closed = new Date(t.ClosedDate!.replace(' ', 'T')).getTime();
                if (closed > created) {
                    totalMs += (closed - created);
                }
            });
            const avgMs = totalMs / closedWithDates.length;
            const totalMinutes = Math.floor(avgMs / (1000 * 60));
            const hours = Math.floor(totalMinutes / 60);
            const mins = totalMinutes % 60;
            if (hours > 24) {
                const days = (hours / 24).toFixed(1);
                avgResolutionTime = `${days}d`;
            } else if (hours > 0) {
                avgResolutionTime = `${hours}h ${mins}m`;
            } else {
                avgResolutionTime = `${mins}m`;
            }
        }

        return { total, open, closed, seeking, avgResolutionTime };
    })();

    const activeAlias = config.active_shift === 1 ? config.shift1_alias : config.shift2_alias;
    const inactiveAlias = config.active_shift === 1 ? config.shift2_alias : config.shift1_alias;
    const activeMembers = config.active_shift === 1 ? shift1_members : shift2_members;
    const inactiveMembers = config.active_shift === 1 ? shift2_members : shift1_members;

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

        return (
            <table className="w-full text-left border-collapse">
                <thead className="sticky top-0 bg-white/95 dark:bg-card/95 backdrop-blur-sm shadow-sm z-10">
                    <tr className="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-black tracking-widest border-b bg-slate-50/70 dark:bg-muted/30">
                        <th className="px-4 py-3"># Ticket</th>
                        <th className="px-4 py-3">Faena</th>
                        <th className="px-4 py-3">Asunto</th>
                        <th className="px-4 py-3">Estado</th>
                        <th className="px-4 py-3">Time</th>
                        <th className="px-4 py-3 text-center">Coments</th>
                        <th className="px-4 py-3">Owner</th>
                        <th className="px-4 py-3 text-right">Ver</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-border/40 text-[11px]">
                    {filteredActiveTickets.map((t: SFCase, idx: number) => {
                        const isClosed = t.Status?.toLowerCase() === 'closed';
                        const isWorking = t.Status?.toLowerCase() === 'working';
                        const isQueueTicket = t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue');
                        const highlightRed = isQueueTicket && !isClosed;

                        return (
                            <tr 
                                key={idx} 
                                className={cn(
                                    "transition-colors group",
                                    isClosed 
                                        ? "bg-slate-50/50 dark:bg-slate-900/20 opacity-60 grayscale hover:bg-slate-50 dark:hover:bg-slate-900/30" 
                                        : highlightRed
                                            ? "bg-rose-500/10 dark:bg-rose-950/25 border-l-2 border-l-rose-500 hover:bg-rose-500/15 dark:hover:bg-rose-950/40"
                                            : isWorking
                                                ? "bg-emerald-500/5 hover:bg-emerald-500/10"
                                                : "hover:bg-slate-50 dark:hover:bg-muted/20"
                                )}
                            >
                                <td className="px-4 py-3">
                                    <a 
                                        href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className={cn(
                                            "text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit",
                                            isClosed
                                                ? "font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale"
                                                : highlightRed
                                                    ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                                    : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                        )}
                                    >
                                        {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                    </a>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={cn(
                                        "text-[10px] px-2 py-1 rounded-md border uppercase tracking-tighter",
                                        isClosed
                                            ? "font-normal text-slate-400 bg-slate-100/50 border-slate-200"
                                            : highlightRed
                                                ? "font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 border-rose-500/20"
                                                : "font-black text-amber-600 bg-amber-500/5 border-amber-500/10"
                                    )}>
                                        {t.Faena || 'Global'}
                                    </span>
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
                                <td className="px-4 py-3">
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
                    })}
                </tbody>
            </table>
        );
    };

    // Cap elapsed progress bar at 7 days
    const percentElapsed = Math.min(100, Math.max(0, (config.days_elapsed / 7) * 100));    return (
        <div className="space-y-6">
            {/* Unified Control Panel: general info and rosters at the top */}
            <div className="bg-gradient-to-br from-card to-muted/40 rounded-2xl border border-border p-4 shadow-sm relative overflow-hidden">
                <div className="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10 items-stretch">
                    {/* Col 1: Ciclo y Progreso */}
                    <div className="flex flex-col justify-between space-y-4 lg:border-r lg:border-border/40 lg:pr-6">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2.5">
                                <div className="bg-primary/10 p-2 rounded-xl border border-primary/20">
                                    <Clock className="w-5 h-5 text-primary animate-pulse" />
                                </div>
                                <div>
                                    <h1 className="text-lg font-black text-foreground tracking-tight">Soporte Turno 7x7</h1>
                                    <p className="text-[10px] text-muted-foreground">Ciclo semanal miércoles a martes</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-1.5 text-[11px] font-bold text-foreground bg-muted/40 px-2.5 py-1.5 rounded-xl border border-border/40 w-fit">
                                <Calendar className="w-3.5 h-3.5 text-muted-foreground" />
                                {formatDate(start)} al {formatDate(end)}
                            </div>
                        </div>

                        {/* Progress bar */}
                        <div className="space-y-1.5">
                            <div className="flex justify-between items-center text-[10px] font-bold">
                                <span className="text-primary-foreground font-black uppercase tracking-widest bg-primary px-1.5 py-0.5 rounded">
                                    Día {config.days_elapsed} de 7
                                </span>
                                <span className="text-muted-foreground">
                                    {7 - config.days_elapsed > 0 
                                        ? `Faltan ${7 - config.days_elapsed} días` 
                                        : 'Rotación automática hoy!'}
                                </span>
                            </div>
                            <div className="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div 
                                    className={`h-full transition-all duration-500 rounded-full ${
                                        config.days_elapsed > 7 
                                            ? 'bg-rose-600 animate-pulse' 
                                            : 'bg-gradient-to-r from-blue-500 to-indigo-600'
                                    }`}
                                    style={{ width: `${percentElapsed}%` }}
                                ></div>
                            </div>
                        </div>
                    </div>

                    {/* Col 2: Turno Activo */}
                    <div className="space-y-3.5 flex flex-col justify-between lg:border-r lg:border-border/40 lg:px-6">
                        <div className="flex items-center justify-between border-b border-border/40 pb-2.5">
                            <div className="flex items-center gap-1.5">
                                <span className="relative flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span className="text-xs font-black text-foreground uppercase tracking-wider">{activeAlias}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <SubShiftHandoff
                                    openTickets={active_tickets
                                        .filter(t => t.Status?.toLowerCase() !== 'closed')
                                        .map(t => ({
                                            case_id:    t.CaseId,
                                            case_number: t.CaseNumber,
                                            status:     t.Status,
                                            subject:    t.Subject,
                                            faena:      t.Faena,
                                            description: t.Description,
                                            comment_count: t.CommentCount,
                                            comments:   t.Comments || [],
                                            created_date: t.CreatedDate,
                                        }))}
                                    isActiveGroup={true}
                                    cycleDay={config.days_elapsed}
                                    activeMembers={activeMembers}
                                />
                                <span className="text-[9px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider">Activo</span>
                            </div>
                        </div>
                        
                        <div className="grid grid-cols-2 gap-4 text-[11px] flex-1 pt-1.5">
                            {/* ☀️ Día */}
                            <div className="space-y-3 border-r border-border/20 pr-4">
                                <div className="text-[8px] font-black uppercase text-amber-600 flex items-center gap-1 mb-1">
                                    <Sun className="w-3.5 h-3.5" /> Día
                                </div>
                                <div className="space-y-3">
                                    {activeMembers.filter((m: ShiftMember) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m: ShiftMember) => (
                                        <div key={m.id} className="flex items-center gap-2.5 min-w-0 w-full">
                                            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-black text-[10px] uppercase shrink-0 shadow-sm">
                                                {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                            </div>
                                            <div className="flex flex-col min-w-0 items-start">
                                                <span className="font-bold text-foreground truncate max-w-full text-[11px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                    {m.first_name} {m.last_name}
                                                </span>
                                                <div className="mt-1 shrink-0">
                                                    <ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {activeMembers.filter((m: ShiftMember) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                                        <span className="text-[10px] text-muted-foreground italic">Sin ingenieros</span>
                                    )}
                                </div>
                            </div>
                            
                            {/* 🌙 Noche */}
                            <div className="space-y-3 pl-2">
                                <div className="text-[8px] font-black uppercase text-purple-600 flex items-center gap-1 mb-1">
                                    <Moon className="w-3.5 h-3.5" /> Noche
                                </div>
                                <div className="space-y-3">
                                    {activeMembers.filter((m: ShiftMember) => m.turno_tipo === 'Noche').map((m: ShiftMember) => (
                                        <div key={m.id} className="flex items-center gap-2.5 min-w-0 w-full">
                                            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-[10px] uppercase shrink-0 shadow-sm">
                                                {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                            </div>
                                            <div className="flex flex-col min-w-0 items-start">
                                                <span className="font-bold text-foreground truncate max-w-full text-[11px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                    {m.first_name} {m.last_name}
                                                </span>
                                                <div className="mt-1 shrink-0">
                                                    <ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {activeMembers.filter((m: ShiftMember) => m.turno_tipo === 'Noche').length === 0 && (
                                        <span className="text-[10px] text-muted-foreground italic">Sin ingenieros</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Col 3: Turno en Descanso */}
                    <div className="space-y-3.5 flex flex-col justify-between lg:pl-6 opacity-75">
                        <div className="flex items-center justify-between border-b border-border/40 pb-2.5">
                            <div className="flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full bg-slate-400"></span>
                                <span className="text-xs font-black text-muted-foreground uppercase tracking-wider">{inactiveAlias}</span>
                            </div>
                            <span className="text-[9px] font-black text-muted-foreground bg-muted dark:bg-muted/20 px-2 py-0.5 rounded-md uppercase tracking-wider">Descanso</span>
                        </div>
                        
                        <div className="grid grid-cols-2 gap-4 text-[11px] flex-1 pt-1.5">
                            {/* ☀️ Día */}
                            <div className="space-y-3 border-r border-border/20 pr-4">
                                <div className="text-[8px] font-black uppercase text-slate-400 flex items-center gap-1 mb-1">
                                    <Sun className="w-3.5 h-3.5 text-slate-400" /> Día
                                </div>
                                <div className="space-y-3">
                                    {inactiveMembers.filter((m: ShiftMember) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m: ShiftMember) => (
                                        <div key={m.id} className="flex items-center gap-2.5 min-w-0 w-full">
                                            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-500 flex items-center justify-center text-white font-black text-[10px] uppercase shrink-0 shadow-sm">
                                                {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                            </div>
                                            <div className="flex flex-col min-w-0 items-start">
                                                <span className="font-bold text-foreground/80 truncate max-w-full text-[11px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                    {m.first_name} {m.last_name}
                                                </span>
                                                <div className="flex items-center gap-1 text-slate-500 dark:text-slate-400 mt-1 shrink-0">
                                                    <Home className="w-2.5 h-2.5" />
                                                    <span className="text-[7.5px] font-bold uppercase tracking-wider whitespace-nowrap">Descanso Turno</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {inactiveMembers.filter((m: ShiftMember) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                                        <span className="text-[10px] text-muted-foreground italic">Sin ingenieros</span>
                                    )}
                                </div>
                            </div>
                            
                            {/* 🌙 Noche */}
                            <div className="space-y-3 pl-2">
                                <div className="text-[8px] font-black uppercase text-slate-400 flex items-center gap-1 mb-1">
                                    <Moon className="w-3.5 h-3.5 text-slate-400" /> Noche
                                </div>
                                <div className="space-y-3">
                                    {inactiveMembers.filter((m: ShiftMember) => m.turno_tipo === 'Noche').map((m: ShiftMember) => (
                                        <div key={m.id} className="flex items-center gap-2.5 min-w-0 w-full">
                                            <div className="w-7 h-7 rounded-full bg-gradient-to-br from-slate-400 to-slate-500 flex items-center justify-center text-white font-black text-[10px] uppercase shrink-0 shadow-sm">
                                                {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                            </div>
                                            <div className="flex flex-col min-w-0 items-start">
                                                <span className="font-bold text-foreground/80 truncate max-w-full text-[11px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                    {m.first_name} {m.last_name}
                                                </span>
                                                <div className="flex items-center gap-1 text-slate-500 dark:text-slate-400 mt-1 shrink-0">
                                                    <Home className="w-2.5 h-2.5" />
                                                    <span className="text-[7.5px] font-bold uppercase tracking-wider whitespace-nowrap">Descanso Turno</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                    {inactiveMembers.filter((m: ShiftMember) => m.turno_tipo === 'Noche').length === 0 && (
                                        <span className="text-[10px] text-muted-foreground italic">Sin ingenieros</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Tickets Grid */}
            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                
                {/* Active Tickets List */}
                <div className={`${inactive_tickets.length > 0 ? 'xl:col-span-2' : 'xl:col-span-3'} space-y-6`}>
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
                                    General ({active_tickets.length})
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
                        <div className={cn(
                            activeTab === 'all' 
                                ? "overflow-x-auto max-h-[550px] overflow-y-auto"
                                : "grid grid-cols-1 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-border/60"
                        )}>
                            {/* Left Side (Table wrapper) */}
                            {activeTab !== 'all' ? (
                                <div className="lg:col-span-3 overflow-x-auto max-h-[550px] overflow-y-auto">
                                    {renderTicketsTable()}
                                </div>
                            ) : (
                                renderTicketsTable()
                            )}

                            {/* Right Side (Analytics Sidebar) */}
                            {activeTab !== 'all' && (
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">
                                    {/* Donut Chart */}
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between border-b border-border/40 pb-1.5 shrink-0">
                                            <span className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider">Distribución</span>
                                            <span className="text-[9px] font-extrabold text-muted-foreground bg-muted dark:bg-muted/30 px-2 py-0.5 rounded-full">Total: {stats.total}</span>
                                        </div>

                                        {stats.total > 0 ? (
                                            <div className="flex flex-col items-center">
                                                <div className="w-36 h-36 shrink-0 relative flex items-center justify-center">
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
                                                                innerRadius={38}
                                                                outerRadius={54}
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
                                                        <span className="text-[8px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total</span>
                                                        <span className="text-xl font-black text-foreground leading-none">{stats.total}</span>
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
                            )}
                        </div>
                    </div>
                </div>

                {/* Inactive Tickets Warning List (Shown only if there are any) */}
                {inactive_tickets.length > 0 && (
                    <div className="xl:col-span-1 space-y-6">
                        <div className="bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm">
                            <div className="p-4 border-b border-border/60 bg-destructive/5">
                                <h3 className="text-xs font-black text-destructive flex items-center gap-1.5">
                                    <AlertCircle className="w-4 h-4 shrink-0 animate-pulse" />
                                    Tickets del Turno Inactivo ({inactive_tickets.length})
                                </h3>
                                <p className="text-[8px] text-muted-foreground mt-0.5">Deben ser transferidos al turno activo en Salesforce.</p>
                            </div>
                            <div className="divide-y divide-border max-h-[500px] overflow-y-auto">
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
