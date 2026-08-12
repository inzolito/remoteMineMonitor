import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Activity, Bell, Ticket, ExternalLink, Shield, Cloud, LayoutDashboard, Clock, X, Terminal, Search, ChevronLeft, ChevronRight, MessageSquare, Eye, CheckCircle2, Calendar, Sun, Moon, Home as HomeIcon } from 'lucide-react';
import { cn } from '../lib/utils'; // Assuming cn helper is available or used directly
import { ShiftStatusEye } from '../components/ShiftStatusEye';
import { SubShiftHandoff } from '../components/SubShiftHandoff';

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

const fetchShiftsData = async () => {
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
    const response = await fetch('/monitoreoLaboratorio/v3/api/shifts.php', {
        headers: { 'Authorization': `Bearer ${token}` }
    });
    if (!response.ok) throw new Error('Network response was not ok');
    return response.json();
};

const fetchNotifications = async () => {
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
    const response = await fetch('/monitoreoLaboratorio/v3/api/notifications.php', {
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

    const { data: shiftsData } = useQuery({
        queryKey: ['shiftsData'],
        queryFn: fetchShiftsData,
        refetchInterval: 15000,
        placeholderData: (prev) => prev
    });

    const { data: notificationsData } = useQuery({
        queryKey: ['homeNotifications'],
        queryFn: fetchNotifications,
        refetchInterval: 10000,
        placeholderData: (prev) => prev
    });

    const activeAlias = shiftsData?.config?.active_shift === 1 ? shiftsData?.config?.shift1_alias : shiftsData?.config?.shift2_alias;
    const inactiveAlias = shiftsData?.config?.active_shift === 1 ? shiftsData?.config?.shift2_alias : shiftsData?.config?.shift1_alias;
    const activeMembers = shiftsData?.config?.active_shift === 1 ? shiftsData?.shift1_members : shiftsData?.shift2_members;
    const inactiveMembers = shiftsData?.config?.active_shift === 1 ? shiftsData?.shift2_members : shiftsData?.shift1_members;

    const shiftStart = shiftsData?.config?.start_date ? new Date(shiftsData.config.start_date + 'T00:00:00') : null;
    const shiftEnd = shiftStart ? new Date(shiftStart) : null;
    if (shiftEnd && shiftStart) {
        shiftEnd.setDate(shiftStart.getDate() + 6);
    }

    const formatDate = (date: Date | null) => {
        if (!date) return '';
        return date.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'short' });
    };

    const isMemberWorking = (isActiveTurn: boolean, shiftType: string | null | undefined) => {
        if (!isActiveTurn) return false;
        
        const now = new Date();
        const currentHour = now.getHours();
        
        const startHourStr = shiftsData?.config?.start_hour || '08:00:00';
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

    const percentElapsed = shiftsData?.config?.days_elapsed 
        ? Math.min(100, Math.max(0, (shiftsData.config.days_elapsed / 7) * 100))
        : 0;

    // Use shiftsData.active_tickets as the single source of truth for ticket counts.
    // This guarantees consistency between the Inicio card and the Turno 7x7 tab.
    const shiftTickets: any[] = shiftsData?.active_tickets || [];

    const activeStats = {
        total:     shiftTickets.length,
        open:      shiftTickets.filter((t: any) => t.Status?.toLowerCase() !== 'closed').length,
        closed:    shiftTickets.filter((t: any) => t.Status?.toLowerCase() === 'closed').length,
        inherited: shiftTickets.filter((t: any) => t.is_inherited === true).length,
    };

    const hasScriptAlert = ['oas', 'salesforce'].some(key => {
        const svc = data?.services?.[key];
        return svc && svc.status !== 'ok';
    });
    
    const hasActiveAlert = data?.alerts?.some((a: any) => a.status === 'active');

    // Parse dismissed notifications from local storage safely inside render
    let dismissedIds: string[] = [];
    try {
        const stored = localStorage.getItem('dismissed_notifications');
        if (stored) dismissedIds = JSON.parse(stored);
    } catch (e) {}

    const activeNotifications = (notificationsData || []).filter((n: any) => !dismissedIds.includes(n.id));

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

            {/* 3-Column Layout Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                {/* Left Side: Shift and Tickets (2/3 width) */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Shift 7x7 Card (Conditional) */}
                    {shiftsData && shiftsData.config && activeMembers && inactiveMembers && (
                        <div className="bg-card border border-border rounded-xl p-4 shadow-sm relative overflow-hidden">
                            {/* Inner grid for active shift and progress */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                                {/* Col 1: Ciclo y Progreso */}
                                <div className="flex flex-col justify-between space-y-4 md:border-r md:border-border/40 md:pr-6">
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2.5">
                                            <div className="bg-primary/10 p-2 rounded-xl">
                                                <Clock className="w-5 h-5 text-primary animate-pulse" />
                                            </div>
                                            <div>
                                                <h2 className="text-base font-black text-foreground tracking-tight">Soporte Turno 7x7</h2>
                                                <p className="text-[10px] text-muted-foreground">Ciclo semanal miércoles a martes</p>
                                            </div>
                                        </div>
                                        {shiftStart && shiftEnd && (
                                            <div className="flex items-center gap-1.5 text-[10px] font-bold text-foreground bg-muted/40 px-2.5 py-1 rounded-lg border border-border/40 w-fit uppercase">
                                                <Calendar className="w-3 h-3 text-muted-foreground" />
                                                {formatDate(shiftStart)} al {formatDate(shiftEnd)}
                                            </div>
                                        )}
                                    </div>

                                    {/* Progress bar */}
                                    <div className="space-y-1">
                                        <div className="flex justify-between items-center text-[9px] font-bold">
                                            <span className="bg-primary text-white px-1.5 py-0.5 rounded font-black uppercase tracking-wider">
                                                Día {shiftsData.config.days_elapsed} de 7
                                            </span>
                                            <span className="text-muted-foreground">
                                                {7 - shiftsData.config.days_elapsed > 0 
                                                    ? `Faltan ${7 - shiftsData.config.days_elapsed} días` 
                                                    : 'Rotación hoy!'}
                                            </span>
                                        </div>
                                        <div className="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div 
                                                className={`h-full transition-all duration-500 rounded-full ${
                                                    shiftsData.config.days_elapsed > 7 
                                                        ? 'bg-rose-600 animate-pulse' 
                                                        : 'bg-gradient-to-r from-blue-500 to-indigo-600'
                                                }`}
                                                style={{ width: `${percentElapsed}%` }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                {/* Col 2: Turno Activo */}
                                <div className="space-y-3 flex flex-col justify-between md:pl-2">
                                    <div className="flex items-center justify-between border-b border-border/40 pb-2">
                                        <div className="flex items-center gap-1.5">
                                            <span className="relative flex h-2 w-2">
                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                            </span>
                                            <span className="text-xs font-black text-foreground uppercase tracking-wider">{activeAlias}</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <SubShiftHandoff
                                                openTickets={shiftTickets
                                                    .filter((t: any) => t.Status?.toLowerCase() !== 'closed')
                                                    .map((t: any) => ({
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
                                                cycleDay={shiftsData.config.days_elapsed}
                                                activeMembers={activeMembers}
                                            />
                                            <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/20 px-2 py-0.5 rounded uppercase tracking-wider">Activo</span>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 text-[11px] flex-1 pt-1">
                                        {/* Day Shift */}
                                        <div className="space-y-2 border-r border-border/20 pr-2">
                                            <div className="text-[8px] font-black uppercase text-amber-600 flex items-center gap-1 mb-1">
                                                <Sun className="w-3 h-3" /> Día
                                            </div>
                                            <div className="space-y-2">
                                                {activeMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m: any) => (
                                                    <div key={m.id} className="flex items-center gap-2 min-w-0 w-full">
                                                        <div className="w-6 h-6 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-black text-[9px] uppercase shrink-0 shadow-sm">
                                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                                        </div>
                                                        <div className="flex flex-col min-w-0 items-start">
                                                            <span className="font-bold text-foreground truncate max-w-full text-[10px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                                {m.first_name} {m.last_name.split(' ')[0]}
                                                            </span>
                                                            <div className="mt-0.5 shrink-0 scale-90 origin-left">
                                                                <ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                                {activeMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                                                    <span className="text-[9px] text-muted-foreground italic">Sin ingenieros</span>
                                                )}
                                            </div>
                                        </div>

                                        {/* Night Shift */}
                                        <div className="space-y-2 pl-1">
                                            <div className="text-[8px] font-black uppercase text-purple-600 flex items-center gap-1 mb-1">
                                                <Moon className="w-3 h-3" /> Noche
                                            </div>
                                            <div className="space-y-2">
                                                {activeMembers.filter((m: any) => m.turno_tipo === 'Noche').map((m: any) => (
                                                    <div key={m.id} className="flex items-center gap-2 min-w-0 w-full">
                                                        <div className="w-6 h-6 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-[9px] uppercase shrink-0 shadow-sm">
                                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                                        </div>
                                                        <div className="flex flex-col min-w-0 items-start">
                                                            <span className="font-bold text-foreground truncate max-w-full text-[10px] leading-tight" title={`${m.first_name} ${m.last_name}`}>
                                                                {m.first_name} {m.last_name.split(' ')[0]}
                                                            </span>
                                                            <div className="mt-0.5 shrink-0 scale-90 origin-left">
                                                                <ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} />
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                                {activeMembers.filter((m: any) => m.turno_tipo === 'Noche').length === 0 && (
                                                    <span className="text-[9px] text-muted-foreground italic">Sin ingenieros</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {/* Bottom row: ticket stats (left) and resting shift (right) */}
                            <div className="border-t border-border/40 pt-2.5 mt-3 flex flex-wrap items-center justify-between gap-3 text-[10px] font-bold text-muted-foreground">
                                {/* Left: Ticket Stats */}
                                <div className="flex items-center gap-2 flex-wrap">
                                    <div className="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-full border border-border/40 text-[9px]">
                                        <span className="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        <span className="text-foreground font-black">{activeStats.total}</span>
                                        <span className="text-muted-foreground/80 font-bold">Totales</span>
                                    </div>
                                    <div className="flex items-center gap-1.5 bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-500/20 text-[9px]">
                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span className="text-amber-600 dark:text-amber-400 font-black">{activeStats.open}</span>
                                        <span className="text-amber-600 dark:text-amber-400 font-bold">Abiertos</span>
                                    </div>
                                    <div className="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-full border border-border/40 text-[9px]">
                                        <span className="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        <span className="text-foreground font-black">{activeStats.closed}</span>
                                        <span className="text-muted-foreground/80 font-bold">Cerrados</span>
                                    </div>
                                    <div className="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-full border border-border/40 text-[9px]">
                                        <span className="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        <span className="text-foreground font-black">{activeStats.inherited}</span>
                                        <span className="text-muted-foreground/80 font-bold">Traspasados del turno anterior</span>
                                    </div>
                                </div>
                                
                                {/* Right: Resting Shift (Descanso) */}
                                <div className="flex flex-wrap items-center gap-2.5">
                                    <div className="flex items-center gap-1.5 bg-muted dark:bg-slate-900/60 px-2 py-1 rounded-lg border border-border/50 shrink-0">
                                        <HomeIcon className="w-3 h-3 text-slate-400 dark:text-slate-500" />
                                        <span className="text-[9px] font-black text-muted-foreground uppercase tracking-wider">
                                            Descanso {inactiveAlias}
                                        </span>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {inactiveMembers.map((m: any) => (
                                            <div key={m.id} className="flex items-center gap-1.5 px-2 py-1 bg-muted/30 dark:bg-slate-900/20 border border-border/40 rounded-full text-[10px] font-semibold text-muted-foreground/90 select-none">
                                                <div className="w-4 h-4 rounded-full bg-slate-350 dark:bg-slate-700 flex items-center justify-center text-[7.5px] font-black text-slate-600 dark:text-slate-300 uppercase shadow-sm">
                                                    {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                                </div>
                                                <span className="truncate max-w-[90px]">{m.first_name} {m.last_name.charAt(0)}.</span>
                                            </div>
                                        ))}
                                        {inactiveMembers.length === 0 && (
                                            <span className="text-[10px] text-muted-foreground italic pl-1">Sin ingenieros asignados</span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Latest Tickets Card */}
                    <div className="border border-border rounded-xl overflow-hidden bg-card shadow-sm flex flex-col">
                        <div className="bg-muted/50 dark:bg-slate-900/60 px-4 py-2 border-b border-border flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                {data?.salesforce_linked ? (
                                    <>
                                        <Cloud className="w-4 h-4 text-sky-500 animate-pulse" />
                                        <div className="flex flex-col">
                                            <h2 className="font-extrabold text-foreground text-sm leading-none flex items-center gap-1.5">
                                                Mis Tickets
                                                <span className="text-[9px] px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-600 font-bold border border-sky-500/20 uppercase tracking-wider animate-pulse">Salesforce</span>
                                            </h2>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <Ticket className="w-4 h-4 text-blue-500" />
                                        <div className="flex items-center gap-3">
                                            <h2 className="font-bold text-foreground text-sm">Tickets (Sudamerican Support)</h2>
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
                                    className="px-2 py-1.5 bg-card dark:bg-slate-900 border border-border dark:border-border/50 rounded-lg text-[11px] font-bold text-muted-foreground dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all cursor-pointer hover:border-slate-300"
                                >
                                    <option value="ALL">Todos los Estados</option>
                                    <option value="ACTIVOS">ACTIVOS (No Cerrados)</option>
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
                                        className="w-full pl-8 pr-3 py-1.5 bg-card dark:bg-slate-900 border border-border dark:border-border/50 rounded-lg text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
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
                                        
                                    const matchesStatus = statusFilter === 'ALL' 
                                        ? true 
                                        : statusFilter === 'ACTIVOS' 
                                            ? t.Status?.toLowerCase() !== 'closed' 
                                            : t.Status === statusFilter;
                                    
                                    return matchesSearch && matchesStatus;
                                });

                                const is7x7 = !!data?.is_7x7;

                                const sortedTickets = [...filteredTickets].sort((a, b) => {
                                    const isClosedA = a.Status?.toLowerCase() === 'closed';
                                    const isClosedB = b.Status?.toLowerCase() === 'closed';
                                    
                                    if (isClosedA !== isClosedB) return isClosedA ? 1 : -1;
                                    
                                    const timeA = new Date(a.CreatedDate?.replace(' ', 'T') || 0).getTime();
                                    const timeB = new Date(b.CreatedDate?.replace(' ', 'T') || 0).getTime();
                                    return timeB - timeA;
                                });

                                const totalPages = Math.ceil(sortedTickets.length / itemsPerPage);
                                const paginatedTickets = sortedTickets.slice(
                                    (currentPage - 1) * itemsPerPage,
                                    currentPage * itemsPerPage
                                );

                                return (
                                    <>
                                        <table className="w-full text-left border-collapse">
                                            <thead className="sticky top-0 bg-card/95 backdrop-blur-sm shadow-sm z-10">
                                                <tr className="text-[9px] text-muted-foreground uppercase font-black tracking-widest border-b border-border bg-muted/50 dark:bg-slate-900/60">
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
                                            <tbody className="divide-y divide-border text-[11px]">
                                                {paginatedTickets.length > 0 ? (
                                                    paginatedTickets.map((ticket: any, idx: number) => {
                                                        const isClosed = ticket.Status?.toLowerCase() === 'closed';
                                                        const isWorking = ticket.Status?.toLowerCase() === 'working';
                                                        const isQueueTicket = ticket.OwnerName?.toLowerCase().includes('support q') || ticket.OwnerName?.toLowerCase().includes('queue');
                                                        const highlightRed = isQueueTicket && !isClosed;
                                                        
                                                        const absoluteIdx = (currentPage - 1) * itemsPerPage + idx;
                                                        const isFirstClosed = isClosed && (absoluteIdx === 0 || sortedTickets[absoluteIdx - 1].Status?.toLowerCase() !== 'closed');
                                                        
                                                        return [
                                                            isFirstClosed && (
                                                                <tr key={`div-${ticket.CaseId || idx}`} className="bg-slate-100/50 dark:bg-slate-800/20">
                                                                    <td colSpan={is7x7 ? 8 : 7} className="py-2">
                                                                        <div className="w-full flex items-center justify-center gap-3">
                                                                            <div className="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                                                            <span className="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Tickets Cerrados</span>
                                                                            <div className="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            ),
                                                            <tr 
                                                                key={ticket.CaseId || idx} 
                                                                className={cn(
                                                                    "transition-colors group",
                                                                    isClosed 
                                                                        ? "bg-muted/10 dark:bg-slate-900/20 opacity-40 dark:opacity-25 grayscale hover:bg-muted/20 hover:opacity-40" 
                                                                        : highlightRed
                                                                            ? "bg-rose-500/10 border-l-2 border-l-rose-500 hover:bg-rose-500/15"
                                                                            : isWorking
                                                                                ? "bg-emerald-500/5 hover:bg-emerald-500/10"
                                                                                : "hover:bg-muted"
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
                                                                                ? "bg-slate-500/10 text-slate-500 border-slate-500/20 grayscale dark:bg-slate-800/40 dark:text-slate-500 dark:border-slate-800/50"
                                                                                : highlightRed
                                                                                    ? "bg-rose-500/15 text-rose-600 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                                                                    : "bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                                                        )}
                                                                    >
                                                                        {ticket.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                                                    </a>
                                                                </td>
                                                                <td className="px-4 py-3">
                                                                    <span className={cn(
                                                                        "text-[10px] font-black px-2 py-1 rounded-md border uppercase tracking-tighter",
                                                                        isClosed
                                                                            ? "text-slate-500 dark:text-slate-500 bg-slate-100/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800/50"
                                                                            : highlightRed
                                                                                ? "text-rose-600 bg-rose-500/10 border-rose-500/20"
                                                                                : "text-amber-600 bg-amber-500/5 border-amber-500/10"
                                                                    )}>
                                                                        {ticket.Faena || ticket.AccountName || 'Global'}
                                                                    </span>
                                                                </td>
                                                                <td className="px-4 py-3 max-w-xs md:max-w-md">
                                                                    <p className={cn(
                                                                        "text-[11px] line-clamp-1 group-hover:line-clamp-none transition-all",
                                                                        isClosed
                                                                            ? "font-normal text-slate-500 dark:text-slate-500/70"
                                                                            : highlightRed
                                                                                ? "font-bold text-rose-600 dark:text-rose-400"
                                                                                : "font-bold text-slate-700 dark:text-slate-200"
                                                                    )}>
                                                                        {ticket.Subject || '(Sin asunto)'}
                                                                    </p>
                                                                </td>
                                                                <td className="px-4 py-3">
                                                                    <div className="flex items-center gap-1.5">
                                                                        <div className={cn(
                                                                            "w-1.5 h-1.5 rounded-full",
                                                                            isClosed 
                                                                                ? "bg-slate-400" 
                                                                                : highlightRed
                                                                                    ? "bg-rose-500 animate-pulse"
                                                                                    : "bg-emerald-500 animate-pulse"
                                                                        )}></div>
                                                                        <span className={cn(
                                                                            "text-[10px] font-black uppercase tracking-tight",
                                                                            highlightRed ? "text-rose-600 dark:text-rose-400" : "text-muted-foreground"
                                                                        )}>{ticket.Status}</span>
                                                                    </div>
                                                                </td>
                                                                <td className="px-4 py-3">
                                                                    <div className="flex flex-col">
                                                                        <span className="text-[11px] font-black text-foreground uppercase">
                                                                            {formatTimeElapsed(ticket.CreatedDate)}
                                                                        </span>
                                                                        <span className="text-[9px] font-medium text-muted-foreground/60 whitespace-nowrap opacity-70">
                                                                            {new Date(ticket.CreatedDate).toLocaleDateString()}
                                                                        </span>
                                                                    </div>
                                                                </td>
                                                                <td className="px-4 py-3 text-center">
                                                                    <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-muted dark:bg-slate-900 rounded-lg">
                                                                        <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                                                                        <span className="text-[10px] font-black">{ticket.CommentCount || 0}</span>
                                                                    </div>
                                                                </td>
                                                                {is7x7 && (
                                                                    <td className="px-4 py-3">
                                                                        <span className={cn(
                                                                            "text-[10px] font-bold border px-2 py-1 rounded-md whitespace-nowrap",
                                                                            highlightRed
                                                                                ? "text-rose-600 bg-rose-500/10 border-rose-500/20"
                                                                                : "text-slate-600 dark:text-slate-300 bg-slate-100/70 dark:bg-slate-800/70 border-slate-200/50 dark:border-slate-800/50"
                                                                        )}>
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
                                                        ];
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
                                            <div className="px-4 py-2 bg-muted/30 dark:bg-slate-900/40 border-t border-border flex items-center justify-between">
                                                <p className="text-[10px] text-muted-foreground font-bold">
                                                    Mostrando <span className="text-foreground">{Math.min(filteredTickets.length, (currentPage - 1) * itemsPerPage + 1)}-{Math.min(filteredTickets.length, currentPage * itemsPerPage)}</span> de <span className="text-foreground">{filteredTickets.length}</span> tickets
                                                </p>
                                                <div className="flex items-center gap-1">
                                                    <button
                                                        onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                                                        disabled={currentPage === 1}
                                                        className="p-1 rounded border border-border bg-card hover:bg-muted disabled:opacity-50 transition-colors"
                                                    >
                                                        <ChevronLeft size={14} className="text-muted-foreground" />
                                                    </button>
                                                    <div className="flex items-center px-2">
                                                        <span className="text-[10px] font-black text-foreground">{currentPage}</span>
                                                        <span className="text-[10px] font-bold text-muted-foreground/60 mx-1">/</span>
                                                        <span className="text-[10px] font-black text-muted-foreground/60">{totalPages}</span>
                                                    </div>
                                                    <button
                                                        onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                                                        disabled={currentPage === totalPages}
                                                        className="p-1 rounded border border-border bg-card hover:bg-muted disabled:opacity-50 transition-colors"
                                                    >
                                                        <ChevronRight size={14} className="text-muted-foreground" />
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

                {/* Right Side: Sidebar (1/3 width) */}
                <div className="lg:col-span-1 space-y-6">
                    {
                        [
                            {
                                id: 'notificaciones',
                                priority: 100, // Highest priority! always fixed on top
                                defaultOrder: 0,
                                content: activeNotifications.length > 0 ? (
                                    <div className="border border-border rounded-xl overflow-hidden bg-card shadow-sm flex flex-col">
                                        <div className="bg-rose-500/10 px-4 py-3 border-b border-border flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Bell className="w-4 h-4 text-rose-500 animate-bounce" />
                                                <h2 className="font-bold text-xs text-rose-700 dark:text-rose-400 uppercase tracking-widest">Notificaciones</h2>
                                            </div>
                                            <span className="bg-rose-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow-sm">{activeNotifications.length}</span>
                                        </div>
                                        <div className="p-3 space-y-2 max-h-[260px] overflow-y-auto custom-scrollbar">
                                            {activeNotifications.map((notif: any) => (
                                                <div key={notif.id} className="p-2.5 rounded-lg border-l-4 border-l-rose-500 transition-all shadow-sm flex flex-col gap-1.5 bg-rose-50/50 dark:bg-rose-950/20 border-y border-r border-border relative group">
                                                    <div className="flex items-center justify-between gap-1 flex-wrap">
                                                        <h4 className="text-[10px] font-black text-rose-700 dark:text-rose-400 uppercase tracking-widest">
                                                            {notif.title}
                                                        </h4>
                                                        <span className="text-[8px] text-muted-foreground font-bold shrink-0">
                                                            {new Date(notif.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                        </span>
                                                    </div>
                                                    <p className="text-[9px] text-slate-700 dark:text-slate-300 font-bold leading-snug">
                                                        {notif.message}
                                                    </p>
                                                    <p className="text-[9px] text-slate-500 italic line-clamp-1 group-hover:line-clamp-none transition-all">
                                                        "{notif.subject}"
                                                    </p>
                                                    <a
                                                        href={`https://usa1.lightning.force.com/lightning/r/Case/${notif.case_id}/view`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="mt-1 inline-flex items-center gap-1 px-2 py-1 bg-rose-500/15 text-rose-600 dark:text-rose-400 font-black rounded border border-rose-500/25 hover:bg-rose-500 hover:text-white transition-all uppercase text-[8px] self-start"
                                                    >
                                                        Salesforce <ExternalLink className="w-2.5 h-2.5" />
                                                    </a>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ) : null
                            },
                            {
                                id: 'accesos',
                                priority: 0,
                                defaultOrder: 1,
                                content: (
                                    <div className="border border-border rounded-xl overflow-hidden bg-card shadow-sm flex flex-col">
                                        <div className="bg-muted/50 dark:bg-slate-900/60 px-4 py-3 border-b border-border flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <ExternalLink className="w-4 h-4 text-emerald-500" />
                                                <h2 className="font-bold text-xs text-foreground uppercase tracking-widest">Accesos Útiles</h2>
                                            </div>
                                        </div>
                                        <div className="p-3 grid grid-cols-2 gap-2">
                                                {quickLinks.map((link) => (
                                                    link.url ? (
                                                        <a
                                                            key={link.name}
                                                            href={link.url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="bg-muted/10 border border-border/80 rounded-lg p-2.5 flex items-center gap-2 hover:shadow-sm hover:border-primary/30 transition-all group overflow-hidden"
                                                        >
                                                            <div className={cn(
                                                                "p-1.5 rounded-md shrink-0 transition-transform group-hover:scale-105 ring-1 ring-inset",
                                                                link.iconClass
                                                            )}>
                                                                <link.icon size={14} />
                                                            </div>
                                                            <span className="text-[10px] font-black text-foreground/80 dark:text-slate-350 uppercase tracking-tight truncate">{link.name}</span>
                                                        </a>
                                                    ) : (
                                                        <Link
                                                            key={link.name}
                                                            to={link.path || '#'}
                                                            className="bg-muted/10 border border-border/80 rounded-lg p-2.5 flex items-center gap-2 hover:shadow-sm hover:border-primary/30 transition-all group overflow-hidden"
                                                        >
                                                            <div className={cn(
                                                                "p-1.5 rounded-md shrink-0 transition-transform group-hover:scale-105 ring-1 ring-inset",
                                                                link.iconClass
                                                            )}>
                                                                <link.icon size={14} />
                                                            </div>
                                                            <span className="text-[10px] font-black text-foreground/80 dark:text-slate-350 uppercase tracking-tight truncate">{link.name}</span>
                                                        </Link>
                                                    )
                                                ))}
                                        </div>
                                    </div>
                                )
                            },
                            {
                                id: 'scripts',
                                priority: hasScriptAlert ? 10 : 0,
                                defaultOrder: 2,
                                content: (
                                    <div className="border border-border rounded-xl overflow-hidden bg-card shadow-sm flex flex-col">
                                        <div className="bg-muted/50 dark:bg-slate-900/60 px-4 py-3 border-b border-border flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Terminal className="w-4 h-4 text-primary" />
                                                <h2 className="font-bold text-xs text-foreground uppercase tracking-widest">Scripts Corriendo</h2>
                                            </div>
                                            {hasScriptAlert && <span className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 text-[9px] font-black px-2 py-0.5 rounded-full animate-pulse">¡ERROR DETECTADO!</span>}
                                        </div>
                                        <div className="p-3 grid grid-cols-2 gap-2">
                                            {['oas', 'salesforce'].map((key) => {
                                                const svc = data?.services?.[key];
                                                const isOk = svc?.status === 'ok';
                                                const title = key === 'oas' ? 'TÚNEL OAS GRAFANA' : 'COMUNICACIÓN SALESFORCE';
                
                                                return (
                                                    <div
                                                        key={key}
                                                        onClick={() => setSelectedLog({ title, content: svc?.log || 'Cargando logs...' })}
                                                        className={cn(
                                                            "p-3.5 rounded-xl border transition-all flex items-center justify-between shadow-sm cursor-pointer hover:scale-[1.005] active:scale-[0.995]",
                                                            isOk ? "bg-card border-border hover:border-emerald-300 dark:hover:border-emerald-500" : "bg-red-600 dark:bg-rose-950/40 border-red-700 dark:border-red-900 text-white dark:text-red-300 animate-pulse"
                                                        )}
                                                    >
                                                        <div className="flex items-center gap-2.5 min-w-0">
                                                            <div className={cn(
                                                                "p-2 rounded-lg shrink-0",
                                                                isOk ? "bg-emerald-500 text-white" : "bg-white dark:bg-red-900 text-red-600 dark:text-red-200"
                                                            )}>
                                                                <Activity size={16} />
                                                            </div>
                                                            <div className="min-w-0">
                                                                <h4 className={cn("font-black text-xs uppercase truncate leading-none mb-1", isOk ? "text-foreground" : "text-white")}>
                                                                    {title}
                                                                </h4>
                                                                <p className={cn("text-[9px] font-semibold", isOk ? "text-muted-foreground/80" : "text-white/80")}>
                                                                    {svc?.fecha?.split(' ')[1] || 'Sin actividad'}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div className="flex flex-col items-end shrink-0">
                                                            <span className={cn(
                                                                "px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider shadow-sm mb-0.5",
                                                                isOk ? "bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400" : "bg-white dark:bg-red-900 text-red-700 dark:text-red-200"
                                                            )}>
                                                                {isOk ? 'Activo' : 'Inactivo'}
                                                            </span>
                                                            {svc?.minutos !== null && (
                                                                <span className={cn("text-[8px] font-bold", isOk ? "text-muted-foreground/60" : "text-white/70")}>
                                                                    {svc?.minutos}m
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )
                            },
                            {
                                id: 'alerts',
                                priority: hasActiveAlert ? 5 : 0,
                                defaultOrder: 3,
                                content: (
                                    <div className="border border-border rounded-xl overflow-hidden bg-card shadow-sm flex flex-col">
                                        <div className="bg-muted/50 dark:bg-slate-900/60 px-4 py-3 border-b border-border flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <Activity className="w-4 h-4 text-emerald-500" />
                                                <h2 className="font-bold text-xs text-foreground uppercase tracking-widest">Alertas Activas</h2>
                                            </div>
                                            <span className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 text-[9px] font-black px-2 py-0.5 rounded-full">{data?.alerts?.length || 0}</span>
                                        </div>
                                        <div className="p-3 space-y-2 max-h-[260px] overflow-y-auto custom-scrollbar">
                                                {data?.alerts?.length > 0 ? (
                                                    data.alerts.map((alerta: any) => {
                                                        const isAck = alerta.status === 'acknowledged';
                                                        const desc = alerta.description?.split('] ').pop() || alerta.description;
                                                        const ipMatch = alerta.description?.match(/\[IP: (.*?)\]/);
                                                        const ip = ipMatch ? ipMatch[1] : null;
                
                                                        return (
                                                            <div key={alerta.alert_id} className={cn(
                                                                "p-2.5 rounded-lg border-l-4 transition-all shadow-sm flex flex-col gap-1.5 relative group bg-card border-border border-y border-r",
                                                                alerta.status === 'active'
                                                                    ? "border-l-red-600"
                                                                    : "border-l-slate-400 opacity-80"
                                                            )}>
                                                                <div className="flex items-start justify-between gap-1 flex-wrap">
                                                                    <div className="flex items-center gap-1 flex-wrap">
                                                                        <span className={cn(
                                                                            "text-[7px] font-black px-1 py-0.5 rounded uppercase tracking-tighter shrink-0",
                                                                            alerta.status === 'active' ? "bg-red-600 text-white animate-pulse" : "bg-slate-500 text-white"
                                                                        )}>
                                                                            {alerta.status === 'active' ? 'ACTIVA' : 'EN REVISIÓN'}
                                                                        </span>
                                                                        <span className="text-[8px] font-bold text-muted-foreground uppercase truncate max-w-[80px]">{alerta.server_name}</span>
                                                                        {ip && <span className="text-[7.5px] font-mono text-blue-600 dark:text-blue-400 font-black bg-muted/50 dark:bg-slate-900 px-1 rounded border border-border shrink-0">{ip}</span>}
                                                                    </div>
                                                                    <span className="text-[8px] text-muted-foreground font-bold ml-auto shrink-0">
                                                                        {new Date(alerta.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                                    </span>
                                                                </div>
                
                                                                <h4 className={cn("text-[10px] font-black leading-tight", alerta.status === 'active' ? "text-red-700 dark:text-red-400" : "text-muted-foreground")}>
                                                                    {alerta.title}
                                                                </h4>
                
                                                                <p className="text-[9px] text-muted-foreground leading-snug italic line-clamp-1 group-hover:line-clamp-none transition-all">
                                                                    "{desc}"
                                                                </p>
                
                                                                {isAck && (
                                                                    <div className="flex items-center gap-1 mt-0.5 pt-1 border-t border-border">
                                                                        <div className="w-1 h-1 rounded-full bg-emerald-500" />
                                                                        <span className="text-[8px] text-muted-foreground/60 font-bold italic truncate">
                                                                            Visto por {alerta.user_name?.split(' ')[0]}
                                                                        </span>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        );
                                                    })
                                                ) : (
                                                    <div className="py-8 text-center text-muted-foreground text-xs italic">
                                                        Sin notificaciones activas
                                                    </div>
                                                )}
                                        </div>
                                    </div>
                                )
                            }
                        ]
                        .filter(section => section.content !== null)
                        .sort((a, b) => b.priority !== a.priority ? b.priority - a.priority : a.defaultOrder - b.defaultOrder)
                        .map(section => <div key={section.id} className="w-full">{section.content}</div>)
                    }
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
                    <div className="bg-card border border-border rounded-[2rem] w-full max-w-4xl max-h-[85vh] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200">
                        <div className="p-6 border-b border-border flex items-center justify-between bg-muted/30 dark:bg-slate-900/40">
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
                                className="p-2 hover:bg-muted rounded-xl transition-colors"
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
                                <div className="bg-muted/30 dark:bg-slate-900/40 p-5 rounded-2xl border border-border">
                                    <p className="text-xs leading-relaxed text-foreground whitespace-pre-wrap">
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
                                        <p className="text-xs leading-relaxed text-foreground whitespace-pre-wrap">
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
                                            <div key={idx} className="bg-card border border-border rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                                                <div className="flex justify-between items-start mb-2">
                                                    <span className="text-[10px] font-black text-primary uppercase tracking-tight">{comment.Author || 'Sistema'}</span>
                                                    <span className="text-[9px] font-mono text-muted-foreground italic">{new Date(comment.CreatedDate).toLocaleString()}</span>
                                                </div>
                                                <p className="text-[11px] leading-relaxed text-muted-foreground whitespace-pre-wrap">
                                                    {comment.CommentBody}
                                                </p>
                                                <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary/20 group-hover:bg-primary transition-colors"></div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="py-10 text-center bg-muted/20 dark:bg-slate-900/30 rounded-2xl border border-dashed border-border">
                                            <MessageSquare className="w-10 h-10 text-muted-foreground/30 mx-auto mb-3" />
                                            <p className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">No hay comentarios registrados</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="p-4 border-t border-border bg-muted/20 dark:bg-slate-900/30 flex justify-end gap-3">
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
