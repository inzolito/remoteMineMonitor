import { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { Navigate } from 'react-router-dom';
import {
    ShieldCheck, Server, Activity, Terminal,
    FileText, Plus, Trash2, Save, RefreshCw, Search,
    AlertCircle, ChevronRight, Settings, XOctagon, Network,
    Users, Palette, LayoutGrid, Clock, Cpu, Mountain, Database, Ticket, ChevronDown, ChevronUp,
    Eye, MessageSquare, ExternalLink
} from 'lucide-react';
import UsersManagementTab from './admin/UsersManagementTab';
import MiningIconGallery from '../components/icons/MiningIconGallery';
import RooteoTab from '../components/RooteoTab';

const API_BASE = '/monitoreoLaboratorio/v3/api/admin.php';

const formatUptime = (since: string) => {
    if (!since || since === "" || since === "undefined") return "--h --m --s";
    try {
        let dateStr = since;
        if (since.includes(' ')) {
            const parts = since.trim().split(/\s+/);
            // Expected: ["Fri", "2026-05-15", "12:21:04", "-04"] or similar
            if (parts.length >= 3) {
                // Try to find the YYYY-MM-DD part
                const datePart = parts.find(p => p.includes('-'));
                const timePart = parts.find(p => p.includes(':'));
                const tzPart = parts.find(p => (p.startsWith('-') || p.startsWith('+')) && p.length <= 5);
                
                if (datePart && timePart) {
                    dateStr = `${datePart}T${timePart}${tzPart || ''}`;
                }
            }
        }
        
        let start = new Date(dateStr).getTime();
        
        // If still NaN, try one more aggressive normalization
        if (isNaN(start)) {
            const clean = since.replace(/[a-zA-Z]/g, ' ').trim().split(/\s+/);
            if (clean.length >= 2) {
                start = new Date(`${clean[0]}T${clean[1]}`).getTime();
            }
        }

        if (isNaN(start)) return "--h --m --s";

        const now = new Date().getTime();
        const diff = Math.max(0, now - start);
        const hours = Math.floor(diff / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        return `${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
    } catch (e) { return "--h --m --s"; }
};

const SuperAdmin = () => {
    const { user } = useAuth();
    const [activeCategory, setActiveCategory] = useState('infrastructure');
    const [activeTab, setActiveTab] = useState('bot_fms');
    const [, setLoading] = useState(false);
    const [, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);

    const categories = [
        { id: 'infrastructure', label: 'Infraestructura', icon: LayoutGrid, description: 'Servidores, Métricas y Monitoreo' },
        { id: 'general', label: 'Administración', icon: Settings, description: 'Usuarios, Permisos y Recursos' },
    ];

    const tabsByCategory: Record<string, any[]> = {
        infrastructure: [
            { id: 'bot_fms', label: 'BOT FMS', icon: Activity },
            { id: 'bot_sf', label: 'BOT SF', icon: Mountain },
            { id: 'servers', label: 'Servidores', icon: Server },
            { id: 'metrics', label: 'Catálogo Métricas', icon: Activity },
            { id: 'commands', label: 'Comandos Fallback', icon: Terminal },
            { id: 'templates', label: 'Perfiles / Templates', icon: FileText },
            { id: 'alerts', label: 'Reglas Alerta', icon: AlertCircle },
            { id: 'rooteo', label: 'Inspección Red', icon: Network },
        ],
        general: [
            { id: 'users', label: 'Gestión Usuarios', icon: Users },
            { id: 'permissions', label: 'Matriz Permisos', icon: ShieldCheck },
            { id: 'iconography', label: 'Galería Iconos', icon: Palette },
        ]
    };

    // Data states
    const [servers, setServers] = useState<any[]>([]);
    const [metrics, setMetrics] = useState<any[]>([]);
    const [serverTypes, setServerTypes] = useState<any[]>([]);
    const [commands, setCommands] = useState<any[]>([]);
    const [templateMetrics, setTemplateMetrics] = useState<any[]>([]);
    const [alertRules, setAlertRules] = useState<any[]>([]);
    const [permissions, setPermissions] = useState<any[]>([]); // Roles
    const [modules, setModules] = useState<any[]>([]); // All Modules
    const [botStatus, setBotStatus] = useState<any>(null);
    const [botSfStatus, setBotSfStatus] = useState<any>(null);
    const [botSites, setBotSites] = useState<any[]>([]);
    const [selectedLogSite, setSelectedLogSite] = useState<any>(null);
    const [botLogs, setBotLogs] = useState<string>('');
    const [liveUptime, setLiveUptime] = useState('--h --m --s');
    const [liveSfUptime, setLiveSfUptime] = useState('--h --m --s');
    
    // Salesforce Bot Detailed States
    const [sfUsers, setSfUsers] = useState<any[]>([]);
    const [selectedSfUser, setSelectedSfUser] = useState<any>(null);
    const [sfTickets, setSfTickets] = useState<any[]>([]);
    const [sfSearchTerm, setSfSearchTerm] = useState('');
    const [showInactiveSfUsers, setShowInactiveSfUsers] = useState(false);

    // Salesforce Modal States
    const [selectedSfTicket, setSelectedSfTicket] = useState<any>(null);
    const [sfTicketComments, setSfTicketComments] = useState<any[]>([]);
    const [isSfModalOpen, setIsSfModalOpen] = useState(false);

    // Selected items for editing
    const [selectedMetric, setSelectedMetric] = useState<any>(null);
    const [selectedType, setSelectedType] = useState<any>(null);
    const [selectedRule, setSelectedRule] = useState<any>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [detailMetric, setDetailMetric] = useState<any>(null); // For generic modal
    const [purgeData, setPurgeData] = useState<any>(null); // For deletion review modal
    const [selectedConnection, setSelectedConnection] = useState<any>(null); // For Connection Path modal

    // Security: Only maik
    if (user?.username !== 'maik') {
        return <Navigate to="/" replace />;
    }

    const fetchData = async (action: string, params: string = '') => {
        setLoading(true);
        try {
            // Special handling for permissions/modules endpoints which are separate files
            let url = `${API_BASE}?action=${action}${params}`;
            if (action === 'permissions') {
                url = '/monitoreoLaboratorio/v3/api/permissions.php';
            } else if (action === 'modules') {
                url = '/monitoreoLaboratorio/v3/api/modules.php?action=all';
            }

            const resp = await fetch(url, {
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await resp.json();
            if (resp.ok) return data;
            throw new Error(data.message || 'Error fetching data');
        } catch (err: any) {
            setMessage({ type: 'error', text: err.message });
            return null;
        } finally {
            setLoading(false);
        }
    };

    // ... existing sendData ...
    const sendData = async (action: string, method: string, body: any, params: string = '') => {
        setLoading(true);
        try {
            let url = `${API_BASE}?action=${action}${params}`;
            if (action === 'permissions') {
                url = '/monitoreoLaboratorio/v3/api/permissions.php'; // POST to permissions.php
            }

            const resp = await fetch(url, {
                method,
                headers: {
                    'Authorization': `Bearer ${user.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });
            const data = await resp.json();
            if (resp.ok) {
                setMessage({ type: 'success', text: data.message });
                return true;
            }
            throw new Error(data.message || 'Error sending data');
        } catch (err: any) {
            setMessage({ type: 'error', text: err.message });
            return false;
        } finally {
            setLoading(false);
        }
    };

    const deleteItem = async (action: string, id: number, extraParams: string = '') => {
        if (!confirm('¿Estás seguro de eliminar este elemento?')) return false;

        setLoading(true);
        try {
            const resp = await fetch(`${API_BASE}?action=${action}&id=${id}${extraParams}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${user.token}`
                }
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'Error deleting item');

            setMessage({ type: 'success', text: 'Elemento eliminado correctamente' });
            // Refresh data based on the current action/tab
            if (activeTab === 'alerts' && action === 'alert_rules') {
                const rules = await fetchData('alert_rules');
                if (rules) setAlertRules(rules);
            } else {
                // Generic refresh attempts
                if (action === 'servers') {
                    const s = await fetchData('servers');
                    if (s) setServers(s);
                }
            }
            return true;
        } catch (err: any) {
            setMessage({ type: 'error', text: err.message });
            return false;
        } finally {
            setLoading(false);
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

        if (diffDays > 0) return `${diffDays}D`;
        if (diffHrs > 0) return `${diffHrs}H`;
        if (diffMin > 0) return `${diffMin}M`;
        return 'NEW';
    };

    useEffect(() => {
        const loadInitial = async () => {
            if (activeTab === 'servers') {
                const [s, st] = await Promise.all([fetchData('servers'), fetchData('server_types')]);
                if (s) setServers(s);
                if (st) setServerTypes(st);
            } else if (activeTab === 'metrics') {
                const m = await fetchData('metrics');
                if (m) setMetrics(m);
            } else if (activeTab === 'templates') {
                const [st, m] = await Promise.all([fetchData('server_types'), fetchData('metrics')]);
                if (st) setServerTypes(st);
                if (m) setMetrics(m);
            } else if (activeTab === 'alerts') {
                const [r, m] = await Promise.all([fetchData('alert_rules'), fetchData('metrics')]);
                if (r) setAlertRules(r);
                if (m) setMetrics(m);
            } else if (activeTab === 'permissions') {
                const [p, m] = await Promise.all([fetchData('permissions'), fetchData('modules')]);
                if (p) setPermissions(p);
                if (m) setModules(m);
            } else if (activeTab === 'bot_fms') {
                const [s, st] = await Promise.all([fetchData('bot_status'), fetchData('bot_sites')]);
                if (s) setBotStatus(s);
                if (st) setBotSites(st);
            } else if (activeTab === 'bot_sf') {
                const [s, users] = await Promise.all([fetchData('bot_sf_status'), fetchData('bot_sf_users')]);
                if (s) setBotSfStatus(s);
                if (users) setSfUsers(users);
            }
        };
        loadInitial();
    }, [activeTab]);

    useEffect(() => {
        if (activeTab === 'bot_sf' && selectedSfUser) {
            fetchData('bot_sf_user_tickets', `&user_id=${selectedSfUser.Id}`).then(t => {
                if (t) setSfTickets(t);
            });
        }
    }, [activeTab, selectedSfUser]);

    useEffect(() => {
        if (selectedMetric && activeTab === 'commands') {
            fetchData('commands', `&metric_id=${selectedMetric.id}`).then(c => setCommands(c || []));
        }
    }, [selectedMetric, activeTab]);

    useEffect(() => {
        if (selectedType && activeTab === 'templates') {
            fetchData('template_metrics', `&type_id=${selectedType.id}`).then(tm => setTemplateMetrics(tm || []));
        }
    }, [selectedType, activeTab]);

    // Polling for Bot Status and Logs (FMS)
    useEffect(() => {
        if (activeTab !== 'bot_fms') return;

        const pollStatus = async () => {
            const s = await fetchData('bot_status');
            if (s) setBotStatus(s);
            
            if (selectedLogSite) {
                const l = await fetchData('bot_logs', `&site_id=${selectedLogSite.id}`);
                if (l && l.logs) setBotLogs(l.logs);
            }
        };

        const interval = setInterval(pollStatus, 10000);
        return () => clearInterval(interval);
    }, [activeTab, selectedLogSite]);

    // Polling for Bot Status and Logs (SF)
    useEffect(() => {
        if (activeTab !== 'bot_sf') return;

        const pollSfStatus = async () => {
            const [s, users] = await Promise.all([fetchData('bot_sf_status'), fetchData('bot_sf_users')]);
            if (s) setBotSfStatus(s);
            if (users) setSfUsers(users);
        };

        const interval = setInterval(pollSfStatus, 10000);
        return () => clearInterval(interval);
    }, [activeTab]);

    // Real-time Uptime Timer
    useEffect(() => {
        if (activeTab !== 'bot_fms' || !botStatus?.since) return;

        const timer = setInterval(() => {
            setLiveUptime(formatUptime(botStatus.since));
        }, 1000);

        return () => clearInterval(timer);
    }, [activeTab, botStatus?.since]);

    // Real-time Uptime Timer (SF)
    useEffect(() => {
        if (activeTab !== 'bot_sf' || !botSfStatus?.last_sync) return;

        const timer = setInterval(() => {
            setLiveSfUptime(formatUptime(botSfStatus.last_sync));
        }, 1000);

        return () => clearInterval(timer);
    }, [activeTab, botSfStatus?.last_sync]);

    // Fetch logs immediately when site changes
    useEffect(() => {
        if (activeTab === 'bot_fms' && selectedLogSite) {
            fetchData('bot_logs', `&site_id=${selectedLogSite.id}`).then(l => {
                if (l && l.logs) setBotLogs(l.logs);
            });
        }
    }, [activeTab, selectedLogSite]);

    return (
        <div className="p-8 max-w-7xl mx-auto space-y-6">
            {/* Header with Categories */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b pb-6">
                <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-primary/10 rounded-2xl">
                        <Settings className="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-black tracking-tight">Super Admin Panel</h1>
                        <p className="text-sm text-muted-foreground font-medium">Arquitectura de monitoreo y administración general</p>
                    </div>
                </div>

                <div className="flex bg-muted/50 p-1.5 rounded-2xl border border-border/50">
                    {categories.map((cat) => (
                        <button
                            key={cat.id}
                            onClick={() => {
                                setActiveCategory(cat.id);
                                setActiveTab(tabsByCategory[cat.id][0].id);
                            }}
                            className={`flex items-center gap-2.5 px-6 py-2.5 rounded-xl transition-all duration-300 font-bold text-xs uppercase tracking-wider ${activeCategory === cat.id
                                ? 'bg-background text-primary shadow-xl shadow-primary/5 border border-border/50 scale-[1.02]'
                                : 'text-muted-foreground hover:text-foreground'
                                }`}
                        >
                            <cat.icon className={`w-4 h-4 ${activeCategory === cat.id ? 'text-primary' : ''}`} />
                            {cat.label}
                        </button>
                    ))}
                </div>
            </div>

            {/* Sub-navigation Tabs */}
            <div className="flex flex-wrap gap-2 py-2 border-b">
                {tabsByCategory[activeCategory].map((tab) => {
                    const Icon = tab.icon;
                    return (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`flex items-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 text-sm font-bold ${activeTab === tab.id
                                ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-[1.02]'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                                }`}
                        >
                            <Icon className="w-4 h-4" />
                            {tab.label}
                        </button>
                    );
                })}
            </div>

            {/* Tab: BOT FMS */}
            {activeTab === 'bot_fms' && (
                <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500 h-[calc(100vh-250px)] flex flex-col">
                    {/* TOP BAR: General Bot Status (Compact) */}
                    <div className="bg-card border rounded-2xl p-3 px-6 shadow-sm flex items-center justify-between">
                        <div className="flex items-center gap-6">
                            <div className="flex items-center gap-2.5">
                                <div className={`w-2 h-2 rounded-full ${botStatus?.state === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'}`}></div>
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Master:</span>
                                <span className={`text-[10px] font-black uppercase ${botStatus?.state === 'active' ? 'text-emerald-600' : 'text-red-600'}`}>{botStatus?.state || 'OFFLINE'}</span>
                            </div>
                            <div className="h-4 w-px bg-border"></div>
                            <div className="flex items-center gap-2">
                                <Cpu className="w-3.5 h-3.5 text-primary" />
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Obreros Totales:</span>
                                <span className="text-[10px] font-black text-primary">{botStatus?.process_count || 0}</span>
                            </div>
                            <div className="h-4 w-px bg-border"></div>
                            <div className="flex items-center gap-2">
                                <Clock className="w-3.5 h-3.5 text-muted-foreground" />
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Uptime:</span>
                                <span className="text-[10px] font-mono font-bold">{liveUptime}</span>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button onClick={async () => { if (confirm('⚠️ ¿Reiniciar Maestro?')) { await sendData('bot_restart', 'POST', {}); const s = await fetchData('bot_status'); if (s) setBotStatus(s); } }} className="text-[9px] font-black uppercase tracking-tighter px-3 py-1 bg-red-500/10 text-red-600 border border-red-500/20 rounded-lg hover:bg-red-500 hover:text-white transition-all">Reiniciar Master</button>
                            <button onClick={async () => { if (confirm('¿Reiniciar todos los obreros?')) { await sendData('bot_restart_subs', 'POST', {}); } }} className="text-[9px] font-black uppercase tracking-tighter px-3 py-1 bg-blue-500/10 text-blue-600 border border-blue-500/20 rounded-lg hover:bg-blue-500 hover:text-white transition-all">Reiniciar Todos</button>
                        </div>
                    </div>

                    <div className="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
                        {/* LEFT SIDEBAR: Faenas list by Conglomerate */}
                        <div className="w-full lg:w-80 flex flex-col gap-4 overflow-hidden">
                            <div className="bg-card border rounded-[1.5rem] p-4 shadow-xl shadow-primary/5 flex flex-col h-full">
                                <div className="flex items-center gap-2 mb-4 px-2">
                                    <LayoutGrid className="w-4 h-4 text-primary" />
                                    <h2 className="text-sm font-black uppercase tracking-tight">Obreros FMS</h2>
                                </div>
                                
                                <div className="flex-1 overflow-y-auto space-y-4 pr-1 custom-scrollbar">
                                    {Object.entries(
                                        botSites.reduce((acc: any, site: any) => {
                                            const cong = site.conglomerate || 'OTROS';
                                            if (!acc[cong]) acc[cong] = [];
                                            acc[cong].push(site);
                                            return acc;
                                        }, {})
                                    ).sort(([a], [b]) => {
                                        if (a === 'AMSA') return -1;
                                        if (b === 'AMSA') return 1;
                                        if (a === 'CODELCO') return -1;
                                        if (b === 'CODELCO') return 1;
                                        if (a === 'OTROS') return 1;
                                        if (b === 'OTROS') return -1;
                                        return a.localeCompare(b);
                                    }).map(([conglomerate, sites]: [string, any]) => (
                                        <div key={conglomerate} className="space-y-1.5">
                                            <div className="px-2 py-1 bg-muted/50 rounded-lg text-[9px] font-black uppercase tracking-widest text-muted-foreground flex justify-between items-center">
                                                <span>{conglomerate}</span>
                                                <span className="opacity-50">{sites.length}</span>
                                            </div>
                                            <div className="space-y-1">
                                                {sites.map((site: any) => (
                                                    <button
                                                        key={site.id}
                                                        onClick={() => setSelectedLogSite(site)}
                                                        className={`w-full flex items-center justify-between p-2.5 rounded-xl transition-all duration-200 group ${selectedLogSite?.id === site.id 
                                                            ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-[1.02]' 
                                                            : 'hover:bg-muted/80 text-foreground/80'}`}
                                                    >
                                                        <div className="flex items-center gap-2 overflow-hidden">
                                                            <div className={`w-1.5 h-1.5 rounded-full flex-shrink-0 ${site.is_monitored == 1 ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-400 opacity-30'}`}></div>
                                                            <span className="text-[10px] font-black truncate uppercase tracking-tight">{site.name}</span>
                                                        </div>
                                                        {site.is_monitored == 1 ? (
                                                            <span className={`text-[8px] font-bold px-1 rounded ${selectedLogSite?.id === site.id ? 'bg-white/20' : 'bg-emerald-500/10 text-emerald-600'}`}>ON</span>
                                                        ) : (
                                                            <span className="text-[8px] font-bold opacity-30">PAUSA</span>
                                                        )}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* RIGHT DASHBOARD: Summary + Logs */}
                        <div className="flex-1 flex flex-col gap-4 overflow-hidden">
                            {/* Summary Header */}
                            <div className="bg-card border rounded-[1.5rem] p-5 shadow-xl shadow-primary/5 flex items-center justify-between relative overflow-hidden">
                                <div className="flex items-center gap-5 relative z-10">
                                    <div className={`w-14 h-14 rounded-2xl flex items-center justify-center border-2 ${selectedLogSite?.is_monitored == 1 ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-slate-500/20 bg-slate-500/5'}`}>
                                        <Activity className={`w-7 h-7 ${selectedLogSite?.is_monitored == 1 ? 'text-emerald-500' : 'text-slate-400'}`} />
                                    </div>
                                    <div>
                                        <h2 className="text-2xl font-black tracking-tighter uppercase leading-tight">
                                            {selectedLogSite ? selectedLogSite.name : 'Selecciona una faena'}
                                        </h2>
                                        <div className="flex items-center gap-3 mt-0.5">
                                            <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{selectedLogSite?.conglomerate || 'Sin Conglomerado'}</span>
                                            <div className="w-1 h-1 rounded-full bg-border"></div>
                                            <span className={`text-[10px] font-black uppercase tracking-widest ${selectedLogSite?.is_monitored == 1 ? 'text-emerald-500' : 'text-slate-400'}`}>
                                                {selectedLogSite?.is_monitored == 1 ? 'Monitoreo Activo' : 'En Pausa'}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex gap-4 items-center relative z-10">
                                    {selectedLogSite && (
                                        <>
                                            <div className="flex items-center gap-3 px-4 py-2 bg-muted/30 rounded-2xl border">
                                                <span className={`text-[9px] font-black uppercase tracking-widest ${selectedLogSite.is_monitored == 1 ? 'text-emerald-500' : 'text-muted-foreground opacity-50'}`}>
                                                    {selectedLogSite.is_monitored == 1 ? 'Encendida' : 'Apagada'}
                                                </span>
                                                <button 
                                                    onClick={async () => {
                                                        const newStatus = selectedLogSite.is_monitored == 1 ? 0 : 1;
                                                        await fetchData('bot_site_toggle', `&site_id=${selectedLogSite.id}&status=${newStatus}`);
                                                        const updatedSites = await fetchData('bot_sites');
                                                        if (updatedSites) {
                                                            setBotSites(updatedSites);
                                                            const fresh = updatedSites.find((s: any) => s.id === selectedLogSite.id);
                                                            if (fresh) setSelectedLogSite(fresh);
                                                        }
                                                    }}
                                                    className={`relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none ${selectedLogSite.is_monitored == 1 ? 'bg-emerald-500' : 'bg-slate-300'}`}
                                                >
                                                    <span className={`inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform duration-200 ${selectedLogSite.is_monitored == 1 ? 'translate-x-[22px]' : 'translate-x-1'}`} />
                                                </button>
                                            </div>

                                            <button 
                                                onClick={async () => {
                                                    if (confirm(`¿Reiniciar proceso de ${selectedLogSite.name}?`)) {
                                                        await fetchData('bot_site_restart', `&site_id=${selectedLogSite.id}`);
                                                    }
                                                }}
                                                className="flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary border border-primary/20 rounded-xl text-[10px] font-black uppercase hover:bg-primary hover:text-white transition-all"
                                            >
                                                <RefreshCw className="w-3.5 h-3.5" /> Reiniciar Obrero
                                            </button>
                                        </>
                                    )}
                                </div>

                                {/* Decorative background element */}
                                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                    <Cpu className="w-24 h-24 text-primary" />
                                </div>
                            </div>

                            {/* Real-time Logs Terminal */}
                            <div className="flex-1 bg-[#0b0e14] rounded-[1.5rem] border border-slate-800 shadow-2xl overflow-hidden flex flex-col relative group">
                                <div className="flex items-center justify-between px-5 py-3 border-b border-slate-800/50 bg-slate-900/30">
                                    <div className="flex items-center gap-3">
                                        <div className="flex gap-1.5"><div className="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></div><div className="w-2.5 h-2.5 rounded-full bg-[#ffbd2e]"></div><div className="w-2.5 h-2.5 rounded-full bg-[#27c93f]"></div></div>
                                        <span className="ml-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Terminal — {selectedLogSite ? selectedLogSite.alias : 'Monitor'}</span>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <div className="flex items-center gap-1.5 px-2 py-1 bg-emerald-500/10 rounded-lg">
                                            <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></div>
                                            <span className="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Live Stream (Last 200)</span>
                                        </div>
                                    </div>
                                </div>
                                <div 
                                    className="flex-1 p-5 overflow-y-auto font-mono text-[11px] text-slate-300 custom-scrollbar leading-relaxed scroll-smooth"
                                    ref={(el) => { 
                                        if (el) {
                                            // Force scroll to bottom if we just changed site or if we are near bottom
                                            const isAtBottom = el.scrollHeight - el.scrollTop <= el.clientHeight + 100;
                                            if (isAtBottom || el.dataset.siteId !== selectedLogSite?.id?.toString()) {
                                                el.scrollTop = el.scrollHeight;
                                                el.dataset.siteId = selectedLogSite?.id?.toString() || '';
                                            }
                                        }
                                    }}
                                >
                                    {selectedLogSite ? (
                                        <pre className="whitespace-pre-wrap break-all">
                                            {botLogs || 'Conectando con el obrero...'}
                                        </pre>
                                    ) : (
                                        <div className="h-full flex flex-col items-center justify-center text-slate-700 opacity-40 gap-4">
                                            <Activity className="w-12 h-12" />
                                            <span className="text-xs font-black uppercase tracking-widest">Selecciona una faena para iniciar el monitoreo</span>
                                        </div>
                                    )}
                                </div>
                                
                                {/* Auto-scroll anchor */}
                                <div className="absolute bottom-4 right-6 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div className="bg-primary/20 backdrop-blur px-3 py-1.5 rounded-full border border-primary/30 text-[8px] font-black text-primary uppercase tracking-widest">
                                        Sincronizado
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Tab: BOT SF */}
            {activeTab === 'bot_sf' && (
                <div className="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500 h-[calc(100vh-250px)] flex flex-col">
                    {/* TOP BAR: Salesforce Bot Status */}
                    <div className="bg-card border rounded-2xl p-3 px-6 shadow-sm flex items-center justify-between">
                        <div className="flex items-center gap-6">
                            <div className="flex items-center gap-2.5">
                                <div className={`w-2 h-2 rounded-full ${botSfStatus?.state === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'}`}></div>
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">SF Daemon:</span>
                                <span className={`text-[10px] font-black uppercase ${botSfStatus?.state === 'active' ? 'text-emerald-600' : 'text-red-600'}`}>{botSfStatus?.state || 'OFFLINE'}</span>
                            </div>
                            <div className="h-4 w-px bg-border"></div>
                            <div className="flex items-center gap-2">
                                <Database className="w-3.5 h-3.5 text-primary" />
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Última Sincronización:</span>
                                <span className="text-[10px] font-black text-primary">{botSfStatus?.last_sync || 'Nunca'}</span>
                            </div>
                            <div className="h-4 w-px bg-border"></div>
                            <div className="flex items-center gap-2">
                                <Clock className="w-3.5 h-3.5 text-muted-foreground" />
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Time since last sync:</span>
                                <span className="text-[10px] font-mono font-bold text-amber-600">{liveSfUptime}</span>
                            </div>
                            <div className="h-4 w-px bg-border"></div>
                            <div className="flex items-center gap-2">
                                <FileText className="w-3.5 h-3.5 text-muted-foreground" />
                                <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Registros:</span>
                                <span className="text-[10px] font-mono font-bold">{botSfStatus?.records_processed || 0}</span>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button 
                                onClick={async () => { 
                                    if (confirm('¿Reiniciar Motor de Sincronización Salesforce?')) { 
                                        await fetchData('bot_sf_restart'); 
                                        const s = await fetchData('bot_sf_status'); 
                                        if (s) setBotSfStatus(s); 
                                    } 
                                }} 
                                className="text-[9px] font-black uppercase tracking-tighter px-4 py-1.5 bg-primary/10 text-primary border border-primary/20 rounded-lg hover:bg-primary hover:text-white transition-all flex items-center gap-2"
                            >
                                <RefreshCw className="w-3 h-3" /> Reiniciar Motor SF
                            </button>
                        </div>
                    </div>

                    {/* Main Content Area: Maestro-Detalle */}
                    <div className="flex flex-col lg:flex-row gap-6 flex-1 overflow-hidden">
                        {/* LEFT SIDEBAR: SF Users */}
                        <div className="w-full lg:w-64 flex flex-col gap-4 overflow-hidden">
                            <div className="bg-card border rounded-[1.5rem] p-4 shadow-xl shadow-primary/5 flex flex-col h-full">
                                <div className="flex items-center gap-2 mb-4 px-2">
                                    <Users className="w-4 h-4 text-primary" />
                                    <h2 className="text-sm font-black uppercase tracking-tight">Usuarios Salesforce</h2>
                                </div>
                                
                                {/* Search */}
                                <div className="px-2 mb-4">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground" />
                                        <input 
                                            type="text" 
                                            placeholder="Buscar usuario..." 
                                            className="w-full bg-muted/50 border-none rounded-xl pl-9 pr-4 py-2 text-xs font-medium focus:ring-1 focus:ring-primary/30 transition-all"
                                            value={sfSearchTerm}
                                            onChange={(e) => setSfSearchTerm(e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="flex-1 overflow-y-auto space-y-1 pr-1 custom-scrollbar">
                                    {/* Special Fixed Queue: South American Support Q */}
                                    {(() => {
                                        const sasQueue = sfUsers.find((u: any) => u.is_queue);
                                        if (!sasQueue) return null;
                                        return (
                                            <div className="sticky top-0 z-20 bg-card pb-2">
                                                <button 
                                                    onClick={() => setSelectedSfUser(sasQueue)}
                                                    className={`w-full flex items-center justify-between p-2.5 rounded-xl transition-all duration-200 ${selectedSfUser?.Id === sasQueue.Id 
                                                        ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20' 
                                                        : 'bg-primary/5 hover:bg-primary/10 text-primary'}`}
                                                >
                                                    <div className="flex items-center gap-2 overflow-hidden">
                                                        <span className="text-[10px] font-black truncate uppercase tracking-tight">{sasQueue.Name}</span>
                                                    </div>
                                                    {sasQueue.ticket_count > 0 && (
                                                        <div className="flex items-center gap-1">
                                                            <span className="text-[9px] font-black">{sasQueue.ticket_count}</span>
                                                            <span className="flex h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                                        </div>
                                                    )}
                                                </button>
                                                <div className="h-px bg-border/50 mt-2 mx-1"></div>
                                            </div>
                                        );
                                    })()}

                                    {/* Active Users */}
                                    {sfUsers
                                        .filter(u => !u.is_queue && u.is_active_stats == 1 && u.Name.toLowerCase().includes(sfSearchTerm.toLowerCase()))
                                        .map(user => (
                                            <div key={user.Id} className="group relative">
                                                <button
                                                    onClick={() => setSelectedSfUser(user)}
                                                    className={`w-full flex items-center justify-between p-2.5 rounded-xl transition-all duration-200 ${selectedSfUser?.Id === user.Id 
                                                        ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20' 
                                                        : 'hover:bg-muted/80 text-foreground/80'}`}
                                                >
                                                    <div className="flex items-center gap-2 overflow-hidden">
                                                        <div className="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-[10px] font-black group-hover:bg-primary/20 transition-colors">
                                                            {user.Name?.split(' ').map((n:any) => n[0]).join('').slice(0, 2).toUpperCase() || 'U'}
                                                        </div>
                                                        <span className="text-[10px] font-black truncate uppercase tracking-tight">{user.Name}</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <button 
                                                            onClick={async (e) => {
                                                                e.stopPropagation();
                                                                await fetchData('bot_sf_user_toggle', `&user_id=${user.Id}&status=0`);
                                                                const users = await fetchData('bot_sf_users');
                                                                if (users) setSfUsers(users);
                                                            }}
                                                            className={`p-1 rounded-md transition-all ${selectedSfUser?.Id === user.Id ? 'hover:bg-white/20' : 'hover:bg-red-500/10 text-red-500'}`}
                                                            title="Desactivar"
                                                        >
                                                            <XOctagon className="w-3.5 h-3.5" />
                                                        </button>
                                                    </div>
                                                </button>
                                            </div>
                                        ))
                                    }

                                    {/* Collapsed Inactive Users */}
                                    <div className="mt-4 pt-4 border-t border-border/50">
                                        <button 
                                            onClick={() => setShowInactiveSfUsers(!showInactiveSfUsers)}
                                            className="w-full flex items-center justify-between px-2 py-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            <span className="flex items-center gap-2">
                                                <Users className="w-3 h-3 opacity-50" /> Usuarios Deshabilitados
                                            </span>
                                            {showInactiveSfUsers ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                                        </button>
                                        
                                        {showInactiveSfUsers && (
                                            <div className="mt-2 space-y-1 animate-in fade-in slide-in-from-top-2">
                                                {sfUsers
                                                    .filter(u => u.is_active_stats == 0 && u.Name.toLowerCase().includes(sfSearchTerm.toLowerCase()))
                                                    .map(user => (
                                                        <div key={user.Id} className="opacity-50 hover:opacity-100 transition-opacity">
                                                            <button
                                                                onClick={() => setSelectedSfUser(user)}
                                                                className={`w-full flex items-center justify-between p-2 rounded-xl text-foreground/60 hover:bg-muted/80`}
                                                            >
                                                                <span className="text-[10px] font-bold truncate uppercase tracking-tight">{user.Name}</span>
                                                                <button 
                                                                    onClick={async (e) => {
                                                                        e.stopPropagation();
                                                                        await fetchData('bot_sf_user_toggle', `&user_id=${user.Id}&status=1`);
                                                                        const users = await fetchData('bot_sf_users');
                                                                        if (users) setSfUsers(users);
                                                                    }}
                                                                    className="p-1 hover:bg-emerald-500/10 text-emerald-500 rounded-md"
                                                                    title="Activar"
                                                                >
                                                                    <RefreshCw className="w-3.5 h-3.5" />
                                                                </button>
                                                            </button>
                                                        </div>
                                                    ))
                                                }
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* RIGHT DASHBOARD: Tickets Table */}
                        <div className="flex-1 flex flex-col gap-4 overflow-hidden">
                            {/* User Header */}
                            <div className="bg-card border rounded-[1.5rem] p-5 shadow-xl shadow-primary/5 flex items-center justify-between relative overflow-hidden">
                                <div className="flex items-center gap-5 relative z-10">
                                    <div className={`w-14 h-14 rounded-2xl flex items-center justify-center border-2 border-primary/20 bg-primary/5`}>
                                        <Users className="w-7 h-7 text-primary" />
                                    </div>
                                    <div>
                                        <h2 className="text-2xl font-black tracking-tighter uppercase leading-tight">
                                            {selectedSfUser ? selectedSfUser.Name : 'Selecciona un usuario'}
                                        </h2>
                                        <div className="flex items-center gap-3 mt-0.5">
                                            <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Tickets Salesforce</span>
                                            <div className="w-1 h-1 rounded-full bg-border"></div>
                                            <span className={`text-[10px] font-black uppercase tracking-widest text-primary`}>
                                                {sfTickets.length} registros encontrados
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="absolute top-0 right-0 p-4 opacity-5 pointer-events-none">
                                    <Ticket className="w-24 h-24 text-primary" />
                                </div>
                            </div>

                            {/* Tickets Table */}
                            <div className="flex-1 bg-card border rounded-[1.5rem] shadow-xl overflow-hidden flex flex-col">
                                <div className="overflow-x-auto h-full custom-scrollbar">
                                    <table className="w-full text-left border-collapse min-w-[600px]">
                                                <thead className="sticky top-0 bg-card z-10">
                                            <tr className="border-b bg-muted/30">
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground"># Ticket</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Faena</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Asunto</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Estado</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground">Time</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground text-center">Coments</th>
                                                <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/50">
                                            {selectedSfUser ? (
                                                sfTickets.length > 0 ? (
                                                    sfTickets.map((ticket: any) => (
                                                        <tr 
                                                            key={ticket.CaseNumber} 
                                                            className={`transition-colors group ${
                                                                ticket.Status?.toLowerCase() === 'closed' 
                                                                ? 'bg-muted/5 opacity-50 grayscale' 
                                                                : ticket.Status?.toLowerCase() === 'working' 
                                                                    ? 'bg-emerald-500/5 hover:bg-emerald-500/10' 
                                                                    : 'hover:bg-muted/30'
                                                            }`}
                                                        >
                                                            <td className="px-6 py-4">
                                                                <a 
                                                                    href={`https://usa1.lightning.force.com/lightning/r/Case/${ticket.Id}/view`}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className={`text-[10px] font-black px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit ${
                                                                        ticket.Status?.toLowerCase() === 'closed'
                                                                        ? 'bg-slate-500/10 text-slate-500 border-slate-500/20 grayscale'
                                                                        : 'bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white'
                                                                    }`}
                                                                >
                                                                    {ticket.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                                                </a>
                                                            </td>
                                                            <td className="px-6 py-4">
                                                                <span className="text-[10px] font-black text-amber-600 bg-amber-500/5 px-2 py-1 rounded-md border border-amber-500/10 uppercase tracking-tighter">
                                                                    {ticket.Faena || 'Global'}
                                                                </span>
                                                            </td>
                                                            <td className="px-6 py-4 max-w-md">
                                                                <p className="text-[11px] font-bold text-foreground/90 line-clamp-1 group-hover:line-clamp-none transition-all">
                                                                    {ticket.Subject || '(Sin asunto)'}
                                                                </p>
                                                            </td>
                                                            <td className="px-6 py-4">
                                                                <div className="flex items-center gap-1.5">
                                                                    <div className={`w-1.5 h-1.5 rounded-full ${
                                                                        ticket.Status?.toLowerCase() === 'closed' ? 'bg-slate-400' : 'bg-emerald-500 animate-pulse'
                                                                    }`}></div>
                                                                    <span className="text-[10px] font-black uppercase tracking-tight text-foreground/70">{ticket.Status}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4">
                                                                <div className="flex flex-col">
                                                                    <span className="text-[11px] font-black text-foreground uppercase">
                                                                        {formatTimeElapsed(ticket.CreatedDate)}
                                                                    </span>
                                                                    <span className="text-[9px] font-medium text-muted-foreground whitespace-nowrap opacity-70">
                                                                        {new Date(ticket.CreatedDate).toLocaleDateString()} {new Date(ticket.CreatedDate).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 text-center">
                                                                <div className="inline-flex items-center gap-1.5 px-2 py-1 bg-muted/50 rounded-lg">
                                                                    <MessageSquare className="w-3 h-3 text-primary opacity-50" />
                                                                    <span className="text-[10px] font-black">{ticket.CommentCount}</span>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 text-right">
                                                                <button 
                                                                    onClick={async () => {
                                                                        setSelectedSfTicket(ticket);
                                                                        setIsSfModalOpen(true);
                                                                        const comments = await fetchData('bot_sf_ticket_comments', `&case_id=${ticket.Id}`);
                                                                        if (comments) setSfTicketComments(comments);
                                                                    }}
                                                                    className="p-2 hover:bg-primary/10 text-primary rounded-xl transition-all"
                                                                    title="Ver Detalles"
                                                                >
                                                                    <Eye className="w-4 h-4" />
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))
                                                ) : (
                                                    <tr>
                                                        <td colSpan={7} className="py-20 text-center">
                                                            <div className="flex flex-col items-center gap-4 opacity-30">
                                                                <Ticket className="w-12 h-12 text-muted-foreground" />
                                                                <p className="text-xs font-black uppercase tracking-widest">No se encontraron tickets para este usuario</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )
                                            ) : (
                                                <tr>
                                                    <td colSpan={7} className="py-20 text-center">
                                                        <div className="flex flex-col items-center gap-4 opacity-30">
                                                            <Search className="w-12 h-12 text-muted-foreground" />
                                                            <p className="text-xs font-black uppercase tracking-widest">Selecciona un usuario para ver sus tickets</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Salesforce Ticket Modal */}
                    {isSfModalOpen && selectedSfTicket && (
                        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
                            <div className="bg-card border rounded-[2rem] w-full max-w-4xl max-h-[85vh] shadow-2xl overflow-hidden flex flex-col animate-in zoom-in-95 duration-200">
                                <div className="p-6 border-b flex items-center justify-between bg-muted/30">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2 bg-primary/10 rounded-xl">
                                            <Ticket className="w-5 h-5 text-primary" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-black uppercase tracking-tight">Ticket #{selectedSfTicket.CaseNumber}</h3>
                                            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{selectedSfTicket.Faena || 'Global'}</p>
                                        </div>
                                    </div>
                                    <button 
                                        onClick={() => setIsSfModalOpen(false)}
                                        className="p-2 hover:bg-muted rounded-xl transition-colors"
                                    >
                                        <XOctagon className="w-5 h-5 text-muted-foreground" />
                                    </button>
                                </div>
                                
                                <div className="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
                                    {/* Description */}
                                    <div className="space-y-3">
                                        <h4 className="text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                                            <FileText className="w-3.5 h-3.5" /> Descripción del Caso
                                        </h4>
                                        <div className="bg-muted/50 p-5 rounded-2xl border border-border/50">
                                            <p className="text-xs leading-relaxed text-foreground/80 whitespace-pre-wrap">
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
                                            {sfTicketComments.length > 0 ? (
                                                sfTicketComments.map((comment: any, idx: number) => (
                                                    <div key={idx} className="bg-card border rounded-2xl p-4 shadow-sm relative overflow-hidden group">
                                                        <div className="flex justify-between items-start mb-2">
                                                            <span className="text-[10px] font-black text-primary uppercase tracking-tight">{comment.Author || 'Sistema'}</span>
                                                            <span className="text-[9px] font-mono text-muted-foreground italic">{new Date(comment.CreatedDate).toLocaleString()}</span>
                                                        </div>
                                                        <p className="text-[11px] leading-relaxed text-foreground/70 whitespace-pre-wrap">
                                                            {comment.CommentBody}
                                                        </p>
                                                        <div className="absolute left-0 top-0 bottom-0 w-1 bg-primary/20 group-hover:bg-primary transition-colors"></div>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="py-10 text-center bg-muted/20 rounded-2xl border border-dashed border-border/50">
                                                    <MessageSquare className="w-10 h-10 text-muted-foreground/20 mx-auto mb-3" />
                                                    <p className="text-[10px] font-black uppercase tracking-widest text-muted-foreground/50">No hay comentarios registrados</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="p-4 border-t bg-muted/10 flex justify-end gap-3">
                                    <a 
                                        href={`https://usa1.lightning.force.com/lightning/r/Case/${selectedSfTicket.Id}/view`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center gap-2 px-6 py-2.5 bg-primary text-primary-foreground rounded-xl text-[10px] font-black uppercase tracking-widest hover:shadow-lg hover:shadow-primary/20 transition-all"
                                    >
                                        <ExternalLink className="w-3.5 h-3.5" /> Abrir en Salesforce
                                    </a>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}
            {activeTab === 'servers' && (
                <div className="grid gap-6">
                    <div className="bg-card rounded-xl border p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-lg font-semibold">Mapeo de Servidores a Perfiles</h2>
                            <div className="relative w-64">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <input
                                    type="text"
                                    placeholder="Buscar servidor, IP o faena..."
                                    className="w-full pl-9 pr-4 py-2 bg-muted/50 border rounded-lg text-sm focus:ring-1 ring-primary outline-none"
                                    value={searchTerm}
                                    onChange={e => setSearchTerm(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="text-muted-foreground border-b text-left bg-muted/20">
                                    <tr>
                                        <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider">Servidor</th>
                                        <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider">Faena / Empresa</th>
                                        <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider text-center">Estado</th>
                                        <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider">IP</th>
                                        <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider">Perfil Asignado</th>
                                        <th className="py-3 px-4 font-medium text-right uppercase text-xs tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {Object.entries(
                                        servers
                                            .filter(s =>
                                                s.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                                (s.ip_address || '').includes(searchTerm) ||
                                                (s.site_name || '').toLowerCase().includes(searchTerm.toLowerCase())
                                            )
                                            .reduce((groups: Record<string, any[]>, server) => {
                                                const key = server.conglomerate || 'Sin Empresa';
                                                if (!groups[key]) groups[key] = [];
                                                groups[key].push(server);
                                                return groups;
                                            }, {}) as Record<string, any[]>
                                    ).map(([groupName, groupServers]) => (
                                        <>
                                            <tr key={`group-${groupName}`} className="bg-muted/30">
                                                <td colSpan={5} className="py-2 px-4 font-semibold text-primary text-xs uppercase tracking-widest border-y">
                                                    {groupName} ({groupServers.length})
                                                </td>
                                            </tr>
                                            {groupServers.map((s: any) => (
                                                <tr key={s.id} className="hover:bg-muted/40 transition-colors group">
                                                    <td className="py-3 px-4 font-medium text-foreground">{s.name}</td>
                                                    <td className="py-3 px-4 text-muted-foreground text-xs">
                                                        {s.site_name || '-'}
                                                    </td>
                                                    <td className="py-3 px-4 text-center">
                                                        {Number(s.status) === 1 ? (
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                ONLINE
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold border border-red-200">
                                                                <span className="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                                OFFLINE
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-4 font-mono text-xs text-muted-foreground bg-muted/10 rounded w-fit my-1 inline-block px-1">
                                                        {s.ip_address || 'N/A'}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <select
                                                            value={s.server_type_id || ''}
                                                            onChange={async (e) => {
                                                                const val = e.target.value ? parseInt(e.target.value) : null;
                                                                if (await sendData('servers', 'PUT', { id: s.id, server_type_id: val })) {
                                                                    setServers(servers.map(serv => serv.id === s.id ? { ...serv, server_type_id: val } : serv));
                                                                }
                                                            }}
                                                            className={`bg-transparent border rounded p-1.5 text-xs w-full max-w-[180px] focus:ring-1 ring-primary outline-none transition-all ${!s.server_type_id ? 'border-red-300 text-red-600 bg-red-50' : 'border-gray-200'}`}
                                                        >
                                                            <option value="">-- Sin Asignar --</option>
                                                            {serverTypes.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                                                        </select>
                                                    </td>
                                                    <td className="py-3 px-4 text-right">
                                                        <button
                                                            className="text-primary hover:bg-primary/10 p-1.5 rounded transition-all"
                                                            title="Sincronizar forzosamente"
                                                        >
                                                            <RefreshCw className="w-3.5 h-3.5" />
                                                        </button>

                                                        <button
                                                            onClick={async () => {
                                                                const details = await fetchData('connection_details', `&server_id=${s.id}`);
                                                                if (details) {
                                                                    setSelectedConnection({
                                                                        server: s,
                                                                        connection: details.connection || { server_id: s.id, connection_name: s.name, connection_status: 1 },
                                                                        paths: details.paths || []
                                                                    });
                                                                }
                                                            }}
                                                            className="text-indigo-600 hover:bg-indigo-50 p-1.5 rounded transition-all"
                                                            title="Configurar Ruta de Conexión (Saltos)"
                                                        >
                                                            <Network className="w-3.5 h-3.5" />
                                                        </button>

                                                    </td>
                                                </tr>
                                            ))}
                                        </>
                                    ))}
                                </tbody>
                            </table>
                            {servers.length === 0 && (
                                <div className="p-8 text-center text-muted-foreground">
                                    No se encontraron servidores.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Tab: Metrics */}
            {activeTab === 'metrics' && (
                <div className="grid grid-cols-3 gap-6">
                    <div className="col-span-2 bg-card rounded-xl border overflow-hidden">
                        <div className="p-4 border-b bg-muted/50 flex justify-between items-center">
                            <h2 className="font-semibold">Catálogo Global</h2>
                            <button
                                onClick={() => setSelectedMetric({ name: '', display_name: '', description: '' })}
                                className="flex items-center gap-1 text-xs bg-primary text-primary-foreground px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity"
                            >
                                <Plus className="w-4 h-4" /> Nueva Métrica
                            </button>
                        </div>
                        <div className="max-h-[600px] overflow-y-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/20 text-muted-foreground sticky top-0 border-b">
                                    <tr>
                                        <th className="p-3 text-left font-medium">Nombre Técnino</th>
                                        <th className="p-3 text-left font-medium">Alias (Display)</th>
                                        <th className="p-3 text-right font-medium">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {metrics.map(m => (
                                        <tr key={m.id} className={`hover:bg-muted/30 transition-colors ${selectedMetric?.id === m.id ? 'bg-primary/5' : ''}`}>
                                            <td className="p-3 font-mono text-xs">{m.name}</td>
                                            <td className="p-3">{m.display_name}</td>
                                            <td className="p-3 text-right space-x-2">
                                                <button onClick={() => setSelectedMetric(m)} className="p-1.5 hover:bg-muted rounded text-primary" title="Editar Info Básica"><Settings className="w-4 h-4" /></button>
                                                <button onClick={async () => {
                                                    const details = await fetchData('metric_details', `&metric_id=${m.id}`);
                                                    if (details) setPurgeData(details);
                                                }} className="p-1.5 hover:bg-muted rounded text-red-500" title="Borrar Métrica (Deep Purge)"><Trash2 className="w-4 h-4" /></button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="bg-card rounded-xl border p-6 h-fit sticky top-6">
                        <h2 className="font-semibold mb-4">{selectedMetric?.id ? 'Editar Métrica' : 'Añadir Métrica'}</h2>
                        {selectedMetric ? (
                            <form className="space-y-4" onSubmit={async (e) => {
                                e.preventDefault();
                                if (await sendData('metrics', 'POST', selectedMetric)) {
                                    const m = await fetchData('metrics');
                                    setMetrics(m);
                                }
                            }}>
                                <div className="space-y-1.5">
                                    <label className="text-xs font-medium text-muted-foreground uppercase">Nombre Técnico</label>
                                    <input
                                        type="text" value={selectedMetric.name}
                                        onChange={e => setSelectedMetric({ ...selectedMetric, name: e.target.value })}
                                        className="w-full p-2 border rounded-lg bg-muted/50 focus:ring-1 ring-primary outline-none" placeholder="system.cpu.load"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-xs font-medium text-muted-foreground uppercase">Display Name</label>
                                    <input
                                        type="text" value={selectedMetric.display_name}
                                        onChange={e => setSelectedMetric({ ...selectedMetric, display_name: e.target.value })}
                                        className="w-full p-2 border rounded-lg bg-muted/50 focus:ring-1 ring-primary outline-none" placeholder="CPU Load"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-xs font-medium text-muted-foreground uppercase">Descripción</label>
                                    <textarea
                                        value={selectedMetric.description}
                                        onChange={e => setSelectedMetric({ ...selectedMetric, description: e.target.value })}
                                        className="w-full p-2 border rounded-lg bg-muted/50 h-24 focus:ring-1 ring-primary outline-none text-sm" placeholder="Detalles de la métrica..."
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <button type="submit" className="flex-1 bg-primary text-primary-foreground py-2 rounded-lg font-medium flex items-center justify-center gap-2">
                                        <Save className="w-4 h-4" /> Guardar
                                    </button>
                                    <button type="button" onClick={() => setSelectedMetric(null)} className="px-3 border rounded-lg hover:bg-muted font-medium text-sm">Cancelar</button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-center py-12 border-2 border-dashed rounded-lg">
                                <Activity className="w-12 h-12 text-muted-foreground/20 mx-auto mb-2" />
                                <p className="text-xs text-muted-foreground px-4">Selecciona una métrica del catálogo para editarla o crea una nueva.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Tab: Commands */}
            {activeTab === 'commands' && (
                <div className="grid grid-cols-4 gap-6">
                    <div className="bg-card rounded-xl border p-4 max-h-[700px] overflow-y-auto">
                        <h3 className="text-sm font-semibold mb-3 flex items-center gap-2"><Activity className="w-4 h-4" /> Seleccionar Métrica</h3>
                        <div className="space-y-1">
                            {metrics.map(m => (
                                <button
                                    key={m.id}
                                    onClick={() => setSelectedMetric(m)}
                                    className={`w-full text-left px-3 py-2 rounded-lg text-xs truncate transition-all ${selectedMetric?.id === m.id ? 'bg-primary text-primary-foreground font-medium shadow-md' : 'hover:bg-muted text-muted-foreground'}`}
                                >
                                    {m.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="col-span-3 space-y-4">
                        {selectedMetric ? (
                            <>
                                <div className="bg-primary/5 border border-primary/10 p-4 rounded-xl flex items-center justify-between">
                                    <div>
                                        <h2 className="font-bold flex items-center gap-2 text-primary"><Terminal className="w-5 h-5" /> Comandos para: {selectedMetric.name}</h2>
                                        <p className="text-xs text-muted-foreground">{selectedMetric.display_name}</p>
                                    </div>
                                    <button
                                        onClick={() => setCommands([...commands, { metric_id: selectedMetric.id, command: '', priority: commands.length + 1, timeout_seconds: 30, os_family: 'linux' }])}
                                        className="bg-primary text-primary-foreground p-2 rounded-lg hover:shadow-lg transition-all"
                                    ><Plus className="w-5 h-5" /></button>
                                </div>

                                <div className="space-y-4">
                                    {commands.map((cmd, idx) => (
                                        <div key={idx} className="bg-card border rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow grid grid-cols-12 gap-4 items-end">
                                            <div className="col-span-1 space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground uppercase">Prio</label>
                                                <input
                                                    type="number" value={cmd.priority}
                                                    onChange={e => {
                                                        const newCmds = [...commands];
                                                        newCmds[idx].priority = parseInt(e.target.value);
                                                        setCommands(newCmds);
                                                    }}
                                                    className="w-full p-2 border rounded-lg bg-muted/30 text-center font-bold"
                                                />
                                            </div>
                                            <div className="col-span-7 space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground uppercase">Comando Bash / SQL</label>
                                                <input
                                                    type="text" value={cmd.command}
                                                    onChange={e => {
                                                        const newCmds = [...commands];
                                                        newCmds[idx].command = e.target.value;
                                                        setCommands(newCmds);
                                                    }}
                                                    className="w-full p-2 border rounded-lg font-mono text-xs bg-muted/10 focus:ring-1 ring-primary" placeholder="uptime | awk..."
                                                />
                                            </div>
                                            <div className="col-span-1 space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground uppercase">OS</label>
                                                <select
                                                    value={cmd.os_family}
                                                    onChange={e => {
                                                        const newCmds = [...commands];
                                                        newCmds[idx].os_family = e.target.value;
                                                        setCommands(newCmds);
                                                    }}
                                                    className="w-full p-2 border rounded-lg bg-muted/30 text-xs"
                                                >
                                                    <option value="linux">Linux</option>
                                                    <option value="windows">Windows</option>
                                                    <option value="any">Any</option>
                                                </select>
                                            </div>
                                            <div className="col-span-1 space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground uppercase">Time (s)</label>
                                                <input
                                                    type="number" value={cmd.timeout_seconds}
                                                    onChange={e => {
                                                        const newCmds = [...commands];
                                                        newCmds[idx].timeout_seconds = parseInt(e.target.value);
                                                        setCommands(newCmds);
                                                    }}
                                                    className="w-full p-2 border rounded-lg bg-muted/30 text-center"
                                                />
                                            </div>
                                            <div className="col-span-2 flex gap-1">
                                                <button onClick={() => sendData('commands', 'POST', cmd)} className="p-2.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200"><Save className="w-5 h-5" /></button>
                                                <button onClick={async () => {
                                                    if (cmd.id && await deleteItem('commands', cmd.id)) {
                                                        setCommands(commands.filter((_, i) => i !== idx));
                                                    } else if (!cmd.id) {
                                                        setCommands(commands.filter((_, i) => i !== idx));
                                                    }
                                                }} className="p-2.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200"><Trash2 className="w-5 h-5" /></button>
                                            </div>
                                        </div>
                                    ))}
                                    {commands.length === 0 && (
                                        <div className="text-center py-20 border-2 border-dashed rounded-2xl bg-muted/20 text-muted-foreground">
                                            No hay comandos definidos para esta métrica.
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="h-[400px] flex flex-col items-center justify-center border-2 border-dashed rounded-2xl text-muted-foreground">
                                <Terminal className="w-16 h-16 opacity-20 mb-4" />
                                <p className="font-medium">Selecciona una métrica del catálogo para ver sus comandos.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Tab: Templates */}
            {activeTab === 'templates' && (
                <div className="grid grid-cols-3 gap-6">
                    <div className="space-y-4">
                        <h3 className="text-sm font-semibold flex items-center gap-2"><FileText className="w-4 h-4" /> Perfiles de Servidor</h3>
                        {serverTypes.map(st => (
                            <button
                                key={st.id}
                                onClick={() => setSelectedType(st)}
                                className={`w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all border ${selectedType?.id === st.id ? 'bg-primary text-primary-foreground border-primary shadow-lg ring-2 ring-primary/20' : 'bg-card hover:bg-muted text-foreground'}`}
                            >
                                <div className="text-left">
                                    <div className="font-bold">{st.name}</div>
                                    <div className={`text-xs ${selectedType?.id === st.id ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}>{st.description}</div>
                                </div>
                                <ChevronRight className="w-5 h-5 opacity-40" />
                            </button>
                        ))}

                        <button
                            onClick={async () => {
                                setSelectedType({ id: 'orphans', name: 'Métricas Sin Asignar', description: 'No vinculadas a ningún perfil' });
                                const data = await fetchData('template_metrics', `&type_id=orphans`);
                                if (data) setTemplateMetrics(data);
                            }}
                            className={`w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all border ${selectedType?.id === 'orphans' ? 'bg-amber-500 text-white border-amber-600 shadow-lg ring-2 ring-amber-500/20' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 border-amber-200'}`}
                        >
                            <div className="text-left">
                                <div className="font-bold flex items-center gap-2"><AlertCircle className="w-4 h-4" /> Sin Asignar</div>
                                <div className={`text-xs ${selectedType?.id === 'orphans' ? 'text-white/80' : 'text-amber-700/70'}`}>Métricas Huérfanas</div>
                            </div>
                            <ChevronRight className="w-5 h-5 opacity-40" />
                        </button>
                    </div>

                    <div className="col-span-2 space-y-6">
                        {selectedType ? (
                            <>
                                <div className="bg-card border rounded-2xl p-6 shadow-sm">
                                    <div className="flex justify-between items-center mb-6">
                                        <h2 className="text-xl font-bold flex items-center gap-2">Asignación de Métricas: {selectedType.name}</h2>
                                        <div className="flex gap-2">
                                            <select
                                                id="metric-add"
                                                className="p-2 border rounded-lg text-sm bg-muted/50"
                                            >
                                                <option value="">Añadir Métrica...</option>
                                                {metrics.filter(m => !templateMetrics.some(tm => tm.metric_id === m.id)).map(m => (
                                                    <option key={m.id} value={m.id}>{m.name}</option>
                                                ))}
                                            </select>
                                            <button
                                                onClick={async () => {
                                                    const mid = (document.getElementById('metric-add') as HTMLSelectElement).value;
                                                    if (!mid) return;
                                                    if (await sendData('template_metrics', 'POST', { server_type_id: selectedType.id, metric_id: parseInt(mid) })) {
                                                        const tm = await fetchData('template_metrics', `&type_id=${selectedType.id}`);
                                                        setTemplateMetrics(tm || []);
                                                    }
                                                }}
                                                className="bg-primary text-primary-foreground px-4 py-2 rounded-lg font-medium shadow-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2"
                                            >
                                                <Plus className="w-4 h-4" /> Asignar
                                            </button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        {templateMetrics.map(tm => (
                                            <div key={tm.metric_id} className="group relative flex flex-col justify-between p-4 bg-muted/40 rounded-xl border border-transparent hover:border-primary/20 hover:bg-muted/60 transition-all">
                                                <div className="space-y-2">
                                                    <div className="flex justify-between items-start">
                                                        <button
                                                            onClick={async () => {
                                                                const details = await fetchData('metric_details', `&metric_id=${tm.metric_id}`);
                                                                if (details) setDetailMetric(details);
                                                            }}
                                                            className="font-medium text-sm text-left hover:text-primary transition-colors line-clamp-1"
                                                            title="Ver detalles completos"
                                                        >
                                                            {tm.metric_name}
                                                        </button>
                                                        {selectedType.id !== 'orphans' && (
                                                            <div className="text-[10px] font-mono bg-background px-1.5 py-0.5 rounded border text-muted-foreground">
                                                                {tm.default_frequency}s
                                                            </div>
                                                        )}
                                                    </div>

                                                    <div className="text-xs text-muted-foreground line-clamp-1" title={tm.display_name}>
                                                        {tm.display_name}
                                                    </div>

                                                    {/* Command Preview in Card */}
                                                    <div className="bg-background/50 p-2 rounded border border-border/50 text-[10px] font-mono text-muted-foreground line-clamp-2 break-all group-hover:border-primary/20 transition-colors cursor-help" title={tm.command_preview || 'Sin comando'}>
                                                        {tm.command_preview || <span className="italic opacity-50">Sin comando definido</span>}
                                                    </div>
                                                </div>

                                                {selectedType.id !== 'orphans' && (
                                                    <div className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <button
                                                            onClick={async (e) => {
                                                                e.stopPropagation();
                                                                if (await deleteItem('template_metrics', 0, `&type_id=${selectedType.id}&metric_id=${tm.metric_id}`)) {
                                                                    setTemplateMetrics(templateMetrics.filter(x => x.metric_id !== tm.metric_id));
                                                                }
                                                            }}
                                                            className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>

                                    {templateMetrics.length === 0 && (
                                        <div className="text-center py-20 text-muted-foreground">
                                            No hay métricas asignadas a este perfil.
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="h-[400px] flex flex-col items-center justify-center border-2 border-dashed rounded-3xl text-muted-foreground">
                                <FileText className="w-16 h-16 opacity-20 mb-4" />
                                <p className="font-medium">Selecciona un perfil para gestionar sus métricas habilitadas.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}
            {/* Detail Modal */}

            {/* Tab: Alerts */}
            {activeTab === 'alerts' && (
                <div className="grid grid-cols-12 gap-6">
                    <div className="col-span-8 bg-card rounded-xl border overflow-hidden flex flex-col">
                        <div className="p-4 border-b bg-muted/50 flex justify-between items-center">
                            <h2 className="font-semibold flex items-center gap-2">
                                <AlertCircle className="w-5 h-5 text-primary" />
                                Reglas de Alerta ({alertRules.length})
                            </h2>
                            <button
                                onClick={() => setSelectedRule({
                                    metric_id: null,
                                    metric_pattern: '',
                                    rule_type: 'threshold',
                                    operator: '>',
                                    threshold_value: '',
                                    alert_category: 'warning',
                                    description: '',
                                    priority: 1
                                })}
                                className="flex items-center gap-1 text-xs bg-primary text-primary-foreground px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity"
                            >
                                <Plus className="w-4 h-4" /> Nueva Regla
                            </button>
                        </div>
                        <div className="max-h-[700px] overflow-y-auto border-t">
                            {(() => {
                                const linked = alertRules.filter(r => r.metric_id);
                                const unlinked = alertRules.filter(r => !r.metric_id);

                                const grouped = linked.reduce((acc: any, rule) => {
                                    if (!acc[rule.metric_id]) acc[rule.metric_id] = {
                                        id: rule.metric_id,
                                        name: rule.metric_technical_name,
                                        display: rule.metric_display_name,
                                        rules: []
                                    };
                                    acc[rule.metric_id].rules.push(rule);
                                    return acc;
                                }, {});

                                return (
                                    <div className="divide-y divide-slate-100">
                                        {Object.values(grouped).map((group: any) => (
                                            <div key={group.id} className="p-4 hover:bg-muted/5 transition-colors">
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <h3 className="font-black text-slate-800 flex items-center gap-2 text-sm uppercase">
                                                            <Activity className="w-4 h-4 text-primary" />
                                                            {group.display}
                                                        </h3>
                                                        <p className="text-[10px] font-mono text-slate-400">{group.name}</p>
                                                    </div>
                                                    <button
                                                        onClick={async () => {
                                                            const details = await fetchData('metric_details', `&metric_id=${group.id}`);
                                                            if (details) setPurgeData(details);
                                                        }}
                                                        className="text-[9px] font-black text-red-500 hover:bg-red-500 hover:text-white px-2 py-1 rounded border border-red-100 transition-all uppercase flex items-center gap-1 shadow-sm"
                                                    >
                                                        <XOctagon className="w-3 h-3" /> Purgar Métrica
                                                    </button>
                                                </div>

                                                <div className="space-y-1.5 ml-6">
                                                    {group.rules.map((rule: any) => (
                                                        <div key={rule.id} className={`flex items-center justify-between p-2 rounded-lg border text-xs shadow-sm transition-all ${selectedRule?.id === rule.id ? 'bg-primary/5 border-primary/30 ring-1 ring-primary/10' : 'bg-white hover:border-slate-300'}`}>
                                                            <div className="flex items-center gap-4 flex-1 min-w-0">
                                                                <div className="flex items-center gap-2 min-w-[120px]">
                                                                    <span className="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-black border uppercase tracking-tighter">
                                                                        {rule.operator}
                                                                    </span>
                                                                    <span className="font-mono text-xs text-primary font-black">
                                                                        {rule.threshold_value}
                                                                    </span>
                                                                </div>

                                                                <span className={`px-2 py-0.5 rounded text-[9px] font-black uppercase border tracking-widest ${rule.alert_category === 'danger' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100'
                                                                    }`}>
                                                                    {rule.alert_category}
                                                                </span>

                                                                <span className="text-[10px] text-slate-400 italic truncate ml-2">
                                                                    "{rule.description}"
                                                                </span>
                                                            </div>

                                                            <div className="flex items-center gap-1 ml-4 border-l pl-2 border-slate-100">
                                                                <button onClick={() => setSelectedRule(rule)} className="p-1.5 hover:bg-slate-50 rounded text-primary transition-all" title="Editar"><Settings className="w-4 h-4" /></button>
                                                                <button
                                                                    onClick={async () => {
                                                                        if (confirm(`¿Eliminar solo esta regla?`)) {
                                                                            if (await deleteItem('alert_rules', rule.id)) {
                                                                                const r = await fetchData('alert_rules');
                                                                                setAlertRules(r || []);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="p-1.5 hover:bg-orange-50 rounded text-orange-400 hover:text-orange-600 transition-all"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}

                                        {unlinked.length > 0 && (
                                            <div className="bg-slate-50 border-t-8 border-slate-100 shadow-inner">
                                                <div className="p-4 bg-slate-100/50 flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Trash2 className="w-4 h-4 text-slate-400" />
                                                        <h3 className="font-black text-slate-500 uppercase text-[10px] tracking-widest">Depósito de Reglas de Patrón / Basura</h3>
                                                    </div>
                                                    <span className="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-bold">{unlinked.length}</span>
                                                </div>
                                                <div className="px-6 py-4 space-y-2">
                                                    {unlinked.map((rule: any) => (
                                                        <div key={rule.id} className="flex items-center justify-between p-3 bg-white border-2 border-dashed border-slate-200 rounded-xl hover:border-slate-400 transition-all shadow-sm">
                                                            <div className="flex-1 min-w-0">
                                                                <div className="font-mono text-[10px] font-bold text-slate-500 truncate mb-1 bg-slate-50 px-2 py-1 rounded inline-block">{rule.metric_pattern}</div>
                                                                <div className="flex items-center gap-3">
                                                                    <span className="text-[10px] bg-muted px-2 py-0.5 rounded font-black border uppercase">{rule.operator} {rule.threshold_value}</span>
                                                                    <span className={`text-[9px] px-2 py-0.5 rounded font-black uppercase border shadow-sm ${rule.alert_category === 'danger' ? 'bg-red-500 text-white border-red-600' : 'bg-amber-400 text-white border-amber-500'
                                                                        }`}>{rule.alert_category}</span>
                                                                    <span className="text-[10px] text-slate-400 italic font-medium ml-2">"{rule.description}"</span>
                                                                </div>
                                                            </div>
                                                            <div className="flex gap-1 ml-4 border-l pl-3 border-slate-100">
                                                                <button onClick={() => setSelectedRule(rule)} className="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-primary transition-all"><Settings className="w-4 h-4" /></button>
                                                                <button
                                                                    onClick={async () => {
                                                                        const params = rule.metric_id ? `&metric_id=${rule.metric_id}` : `&pattern=${encodeURIComponent(rule.metric_pattern)}`;
                                                                        const details = await fetchData('metric_details', params);
                                                                        if (details) setPurgeData({ ...details, rule_id: rule.id });
                                                                    }}
                                                                    className="p-2 hover:bg-red-50 rounded text-red-300 hover:text-red-500 transition-all"
                                                                >
                                                                    <XOctagon className="w-4 h-4" />
                                                                </button>
                                                                <button
                                                                    onClick={async () => {
                                                                        if (confirm('¿Eliminar regla?')) {
                                                                            if (await deleteItem('alert_rules', rule.id)) {
                                                                                const r = await fetchData('alert_rules');
                                                                                setAlertRules(r || []);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="p-2 hover:bg-orange-50 rounded text-orange-300 hover:text-orange-500 transition-all"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })()}
                        </div>
                    </div>

                    <div className="col-span-4 bg-card rounded-xl border p-6 h-fit sticky top-6 shadow-sm">
                        <div className="flex items-center gap-2 mb-6 border-b pb-4">
                            <Settings className="w-5 h-5 text-primary" />
                            <h2 className="font-bold">{selectedRule?.id ? 'Configurar Regla' : 'Nueva Regla de Alerta'}</h2>
                        </div>

                        {selectedRule ? (
                            <form className="space-y-5" onSubmit={async (e) => {
                                e.preventDefault();
                                if (await sendData('alert_rules', 'POST', selectedRule)) {
                                    const r = await fetchData('alert_rules');
                                    setAlertRules(r || []);
                                    setSelectedRule(null);
                                }
                            }}>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase flex items-center gap-1">
                                        Métrica Asociada
                                        <div className="h-px bg-border flex-1 ml-1 opacity-50"></div>
                                    </label>
                                    <select
                                        className="w-full p-2.5 border rounded-lg bg-muted/30 focus:ring-1 ring-primary outline-none transition-all text-sm font-medium"
                                        value={selectedRule.metric_id || ''}
                                        onChange={e => setSelectedRule({
                                            ...selectedRule,
                                            metric_id: e.target.value ? parseInt(e.target.value) : null,
                                            metric_pattern: e.target.value ? '' : selectedRule.metric_pattern
                                        })}
                                    >
                                        <option value="">-- Usar Patrón Manual (Regex/Like) --</option>
                                        {metrics.map(m => (
                                            <option key={m.id} value={m.id}>{m.display_name} ({m.name})</option>
                                        ))}
                                    </select>
                                    {!selectedRule.metric_id && (
                                        <input
                                            type="text"
                                            placeholder="Patrón (ej: system.cpu.%)"
                                            className="w-full p-2.5 border rounded-lg bg-orange-50/30 border-orange-200 text-sm font-mono focus:ring-1 ring-orange-500 outline-none"
                                            value={selectedRule.metric_pattern}
                                            onChange={e => setSelectedRule({ ...selectedRule, metric_pattern: e.target.value })}
                                        />
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-muted-foreground uppercase">Evaluación</label>
                                        <select
                                            className="w-full p-2.5 border rounded-lg bg-muted/30 text-sm font-bold"
                                            value={selectedRule.operator}
                                            onChange={e => setSelectedRule({ ...selectedRule, operator: e.target.value })}
                                        >
                                            <optgroup label="Numérico">
                                                <option value=">">Mayor que {'>'}</option>
                                                <option value=">=">Mayor o igual {'>='}</option>
                                                <option value="<">Menor que {'<'}</option>
                                                <option value="<=">Menor o igual {'<='}</option>
                                                <option value="BETWEEN">Entre (X,Y)</option>
                                            </optgroup>
                                            <optgroup label="Texto / Especial">
                                                <option value="CONTAINS">Contiene texto</option>
                                                <option value="NOT_CONTAINS">NO contiene</option>
                                                <option value="REGEX">Expresión Regular</option>
                                            </optgroup>
                                            <optgroup label="Tiempo / Fecha">
                                                <option value="AGE_GREATER_THAN">Antigüedad {'>'} a...</option>
                                                <option value="DURATION_GREATER_THAN">Duración {'>'} a...</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-muted-foreground uppercase">Valor Umbral</label>
                                        <input
                                            type="text"
                                            placeholder={selectedRule.operator === 'BETWEEN' ? "ej: 10,20" : "5.5, Error..."}
                                            className="w-full p-2.5 border rounded-lg bg-muted/30 text-sm font-bold focus:ring-1 ring-primary outline-none"
                                            value={selectedRule.threshold_value}
                                            onChange={e => setSelectedRule({ ...selectedRule, threshold_value: e.target.value })}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase">Gravedad / Categoría</label>
                                    <div className="grid grid-cols-3 gap-2">
                                        {['warning', 'danger', 'critical'].map(cat => (
                                            <button
                                                key={cat}
                                                type="button"
                                                onClick={() => setSelectedRule({ ...selectedRule, alert_category: cat })}
                                                className={`py-2 rounded-lg text-[10px] font-black uppercase border transition-all ${selectedRule.alert_category === cat
                                                    ? (cat === 'warning' ? 'bg-amber-100 border-amber-400 text-amber-700 ring-2 ring-amber-400/20' :
                                                        cat === 'danger' ? 'bg-orange-100 border-orange-400 text-orange-700 ring-2 ring-orange-400/20' :
                                                            'bg-red-100 border-red-400 text-red-700 ring-2 ring-red-400/20')
                                                    : 'bg-muted/30 border-transparent text-muted-foreground opacity-50 grayscale hover:grayscale-0'
                                                    }`}
                                            >
                                                {cat}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase">Mensaje de la Alerta (Descripción)</label>
                                    <textarea
                                        className="w-full p-3 border rounded-lg bg-muted/30 h-28 text-sm focus:ring-1 ring-primary outline-none"
                                        placeholder="Ej: Se ha detectado una sobrecarga crítica en la CPU..."
                                        value={selectedRule.description}
                                        onChange={e => setSelectedRule({ ...selectedRule, description: e.target.value })}
                                    />
                                    <p className="text-[10px] text-muted-foreground italic">Este texto aparecerá en el FS de Alerta cuando se cumpla la condición.</p>
                                </div>

                                <div className="pt-2 flex gap-2">
                                    <button type="submit" className="flex-1 bg-primary text-primary-foreground py-3 rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg hover:shadow-primary/20 active:scale-[0.98] transition-all">
                                        <Save className="w-5 h-5" /> Guardar Regla
                                    </button>
                                    <button type="button" onClick={() => setSelectedRule(null)} className="px-4 border rounded-xl hover:bg-muted font-bold text-xs uppercase tracking-wider">Cancelar</button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-center py-20 border-2 border-dashed rounded-2xl bg-muted/10 opacity-60">
                                <AlertCircle className="w-16 h-16 text-muted-foreground/30 mx-auto mb-4" />
                                <p className="text-sm text-muted-foreground px-10">Selecciona una regla para editar sus parámetros o define una condición nueva.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}


            {/* Tab: Permissions */}
            {activeTab === 'permissions' && (
                <div className="bg-card rounded-xl border p-6">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h2 className="text-lg font-semibold flex items-center gap-2"><ShieldCheck className="w-5 h-5 text-primary" /> Matriz de Permisos</h2>
                            <p className="text-sm text-muted-foreground">Define qué módulos puede ver cada rol de usuario.</p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground border-b text-left bg-muted/20">
                                <tr>
                                    <th className="py-3 px-4 font-medium uppercase text-xs tracking-wider">Módulo / Sección</th>
                                    {permissions.map(p => (
                                        <th key={p.id} className="py-3 px-4 font-medium uppercase text-xs tracking-wider text-center border-l">
                                            {p.name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {modules.map(m => (
                                    <tr key={m.id} className="hover:bg-muted/40 transition-colors">
                                        <td className="py-3 px-4 flex items-center gap-3">
                                            <div className="p-1.5 bg-primary/5 rounded text-primary">
                                                {/* We don't have Icon component here unless we map again or store icon name */}
                                                <span className="font-mono text-xs">{m.icon}</span>
                                            </div>
                                            <div>
                                                <div className="font-medium text-foreground">{m.name}</div>
                                                <div className="text-xs text-muted-foreground">{m.description}</div>
                                            </div>
                                        </td>
                                        {permissions.map(p => {
                                            const isChecked = p.enabled_modules?.includes(m.id);
                                            return (
                                                <td key={`${p.id}-${m.id}`} className="py-3 px-4 text-center border-l bg-muted/5">
                                                    <input
                                                        type="checkbox"
                                                        checked={isChecked}
                                                        onChange={async (e) => {
                                                            const checked = e.target.checked;
                                                            if (await sendData('permissions', 'POST', {
                                                                permission_id: p.id,
                                                                module_id: m.id,
                                                                can_view: checked
                                                            })) {
                                                                // Update local state optimizing for speed
                                                                const newPerms = [...permissions];
                                                                const permIdx = newPerms.findIndex(x => x.id === p.id);
                                                                if (permIdx >= 0) {
                                                                    if (checked) {
                                                                        newPerms[permIdx].enabled_modules.push(m.id);
                                                                    } else {
                                                                        newPerms[permIdx].enabled_modules = newPerms[permIdx].enabled_modules.filter((id: number) => id !== m.id);
                                                                    }
                                                                    setPermissions(newPerms);
                                                                }
                                                            }
                                                        }}
                                                        className="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary cursor-pointer accent-primary"
                                                    />
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
            {/* Detail Modal */}
            {
                detailMetric && (
                    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                        <div className="bg-background rounded-xl border shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-200">
                            <div className="p-6 border-b flex justify-between items-start sticky top-0 bg-background z-10 glass">
                                <div>
                                    <h3 className="text-xl font-bold flex items-center gap-2"><Activity className="w-5 h-5 text-primary" /> {detailMetric.metric.display_name}</h3>
                                    <p className="text-muted-foreground font-mono text-sm">{detailMetric.metric.name}</p>
                                </div>
                                <button onClick={() => setDetailMetric(null)} className="p-1 hover:bg-muted rounded"><Trash2 className="w-5 h-5 opacity-0" /> <span className="text-2xl leading-4">&times;</span></button>
                            </div>
                            <div className="p-6 space-y-8">
                                {/* Command Editor */}
                                <div>
                                    <h4 className="font-semibold mb-3 flex items-center gap-2 text-sm uppercase tracking-wide text-muted-foreground">
                                        <Terminal className="w-4 h-4" /> Comando Asociado
                                        <button
                                            onClick={async () => {
                                                const cmd = detailMetric.command || { metric_id: detailMetric.metric.id, command: '', priority: 1, timeout_seconds: 30, os_family: 'linux' };
                                                if (await sendData('commands', 'POST', cmd)) {
                                                    setMessage({ type: 'success', text: 'Comando guardado' });
                                                    // Re-fetch details to get the new ID and updated fields
                                                    const updated = await fetchData('metric_details', `&metric_id=${detailMetric.metric.id}`);
                                                    if (updated) setDetailMetric(updated);
                                                }
                                            }}
                                            className="ml-auto text-xs bg-primary/10 text-primary px-2 py-1 rounded hover:bg-primary/20 transition-colors"
                                        >
                                            Guardar Comando
                                        </button>
                                    </h4>
                                    <textarea
                                        value={detailMetric.command?.command || ''}
                                        onChange={e => setDetailMetric({ ...detailMetric, command: { ...detailMetric.command, command: e.target.value, metric_id: detailMetric.metric.id } })}
                                        className="bg-slate-950 text-slate-50 p-4 rounded-lg font-mono text-sm w-full border border-slate-800 shadow-inner focus:ring-1 ring-primary outline-none min-h-[100px]"
                                        placeholder="Escribe el comando bash/sql aquí..."
                                    />
                                    <div className="mt-3 grid grid-cols-3 gap-4">
                                        <div>
                                            <label className="text-[10px] uppercase font-bold text-muted-foreground">Prioridad</label>
                                            <input
                                                type="number"
                                                value={detailMetric.command?.priority || 1}
                                                onChange={e => setDetailMetric({ ...detailMetric, command: { ...detailMetric.command, priority: parseInt(e.target.value) } })}
                                                className="w-full p-2 border rounded bg-muted/50 text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-[10px] uppercase font-bold text-muted-foreground">Timeout (s)</label>
                                            <input
                                                type="number"
                                                value={detailMetric.command?.timeout_seconds || 30}
                                                onChange={e => setDetailMetric({ ...detailMetric, command: { ...detailMetric.command, timeout_seconds: parseInt(e.target.value) } })}
                                                className="w-full p-2 border rounded bg-muted/50 text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-[10px] uppercase font-bold text-muted-foreground">OS Family</label>
                                            <select
                                                value={detailMetric.command?.os_family || 'linux'}
                                                onChange={e => setDetailMetric({ ...detailMetric, command: { ...detailMetric.command, os_family: e.target.value } })}
                                                className="w-full p-2 border rounded bg-muted/50 text-sm"
                                            >
                                                <option value="linux">Linux</option>
                                                <option value="windows">Windows</option>
                                                <option value="any">Any</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-8">
                                    <div>
                                        <h4 className="font-semibold mb-3 flex items-center gap-2 text-sm uppercase tracking-wide text-muted-foreground"><ShieldCheck className="w-4 h-4" /> Perfiles Asignados</h4>
                                        {detailMetric.profiles.length > 0 ? (
                                            <ul className="space-y-2">
                                                {detailMetric.profiles.map((p: any, i: number) => (
                                                    <li key={i} className="bg-blue-50/50 p-3 rounded-lg border border-blue-100/50 flex flex-col gap-2 hover:bg-blue-50 transition-colors group">
                                                        <div className="flex justify-between items-center">
                                                            <span className="font-medium text-sm text-blue-900">{p.name}</span>
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <label className="text-[10px] text-blue-700 font-bold uppercase">Frecuencia:</label>
                                                            <input
                                                                type="number"
                                                                value={p.default_frequency}
                                                                onChange={e => {
                                                                    const newProfiles = [...detailMetric.profiles];
                                                                    newProfiles[i].default_frequency = parseInt(e.target.value);
                                                                    setDetailMetric({ ...detailMetric, profiles: newProfiles });
                                                                }}
                                                                className="w-16 p-1 text-xs border rounded text-center font-mono"
                                                            />
                                                            <span className="text-xs text-blue-600">seg</span>
                                                            <button
                                                                onClick={async () => {
                                                                    // Update map table
                                                                    // We use template_metrics endpoint with DELETE then POST or update?
                                                                    // Assuming we have an update endpoint or we re-insert.
                                                                    // Ideally admin.php supports updating map. 
                                                                    // For now, simpler: delete and add or just custom action?
                                                                    // Let's assume POST to template_metrics updates if exists?
                                                                    if (await sendData('template_metrics', 'POST', { server_type_id: p.id, metric_id: detailMetric.metric.id, default_frequency: p.default_frequency })) {
                                                                        setMessage({ type: 'success', text: 'Frecuencia actualizada' });
                                                                    }
                                                                }}
                                                                className="ml-auto text-[10px] bg-blue-200 text-blue-800 px-2 py-1 rounded opacity-0 group-hover:opacity-100 hover:bg-blue-300 transition-all"
                                                            >
                                                                Guardar
                                                            </button>
                                                        </div>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : <p className="text-sm text-muted-foreground italic bg-muted/20 p-4 rounded-lg text-center">No asignado a ningún perfil.</p>}
                                    </div>
                                    <div>
                                        <h4 className="font-semibold mb-3 flex items-center gap-2 text-sm uppercase tracking-wide text-muted-foreground"><Server className="w-4 h-4" /> Servidores Activos</h4>
                                        {detailMetric.servers.length > 0 ? (
                                            <ul className="space-y-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                                                {detailMetric.servers.map((s: any, i: number) => (
                                                    <li key={i} className="flex justify-between items-center text-sm p-2 hover:bg-muted/50 rounded-lg transition-colors border border-transparent hover:border-border">
                                                        <span className="font-medium">{s.name}</span>
                                                        <span className="text-[10px] font-mono text-muted-foreground">{s.ip}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : <p className="text-sm text-muted-foreground italic bg-muted/20 p-4 rounded-lg text-center">Ningún servidor usa esta métrica.</p>}
                                    </div>
                                </div>

                                <div className="border-t pt-4">
                                    <h4 className="font-semibold mb-1 text-sm">Descripción Técnica</h4>
                                    <textarea
                                        value={detailMetric.metric.description || ''}
                                        onChange={e => setDetailMetric({ ...detailMetric, metric: { ...detailMetric.metric, description: e.target.value } })}
                                        className="w-full p-2 border rounded bg-muted/10 text-sm min-h-[80px]"
                                    />
                                    <button
                                        onClick={async () => {
                                            if (await sendData('metrics', 'POST', detailMetric.metric)) {
                                                setMessage({ type: 'success', text: 'Descripción guardada' });
                                            }
                                        }}
                                        className="mt-2 text-xs bg-muted hover:bg-muted/80 px-3 py-1 rounded"
                                    >
                                        Actualizar Descripción
                                    </button>
                                </div>

                                <div className="pt-6 border-t">
                                    <div className="bg-red-50 p-4 rounded-xl border border-red-100 flex items-center justify-between">
                                        <div>
                                            <h4 className="text-red-900 font-bold">Zona de Peligro</h4>
                                            <p className="text-[10px] text-red-600">Eliminar esta métrica borrará comandos, históricos y perfiles.</p>
                                        </div>
                                        <button
                                            onClick={() => setPurgeData(detailMetric)}
                                            className="bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-black shadow-lg hover:bg-red-700 transition-all flex items-center gap-2"
                                        >
                                            <Trash2 className="w-3.5 h-3.5" /> ELIMINAR MÉTRICA
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div className="p-4 border-t bg-muted/5 flex justify-end">
                                <button onClick={() => setDetailMetric(null)} className="px-6 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition-opacity shadow-sm">Cerrar Detalle</button>
                            </div>
                        </div>
                    </div>
                )
            }

            {/* Tab: Alerts */}
            {activeTab === 'alerts' && (
                <div className="grid grid-cols-12 gap-6">
                    <div className="col-span-8 bg-card rounded-xl border overflow-hidden flex flex-col">
                        <div className="p-4 border-b bg-muted/50 flex justify-between items-center">
                            <h2 className="font-semibold flex items-center gap-2">
                                <AlertCircle className="w-5 h-5 text-primary" />
                                Reglas de Alerta ({alertRules.length})
                            </h2>
                            <button
                                onClick={() => setSelectedRule({
                                    metric_id: null,
                                    metric_pattern: '',
                                    rule_type: 'threshold',
                                    operator: '>',
                                    threshold_value: '',
                                    alert_category: 'warning',
                                    description: '',
                                    priority: 1
                                })}
                                className="flex items-center gap-1 text-xs bg-primary text-primary-foreground px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity"
                            >
                                <Plus className="w-4 h-4" /> Nueva Regla
                            </button>
                        </div>
                        <div className="max-h-[700px] overflow-y-auto border-t">
                            {(() => {
                                const linked = alertRules.filter(r => r.metric_id);
                                const unlinked = alertRules.filter(r => !r.metric_id);

                                const grouped = linked.reduce((acc: any, rule) => {
                                    if (!acc[rule.metric_id]) acc[rule.metric_id] = {
                                        id: rule.metric_id,
                                        name: rule.metric_technical_name,
                                        display: rule.metric_display_name,
                                        rules: []
                                    };
                                    acc[rule.metric_id].rules.push(rule);
                                    return acc;
                                }, {});

                                return (
                                    <div className="divide-y divide-slate-100">
                                        {Object.values(grouped).map((group: any) => (
                                            <div key={group.id} className="p-4 hover:bg-muted/5 transition-colors">
                                                <div className="flex justify-between items-start mb-3">
                                                    <div>
                                                        <h3 className="font-black text-slate-800 flex items-center gap-2 text-sm uppercase">
                                                            <Activity className="w-4 h-4 text-primary" />
                                                            {group.display}
                                                        </h3>
                                                        <p className="text-[10px] font-mono text-slate-400">{group.name}</p>
                                                    </div>
                                                    <button
                                                        onClick={async () => {
                                                            const details = await fetchData('metric_details', `&metric_id=${group.id}`);
                                                            if (details) setPurgeData(details);
                                                        }}
                                                        className="text-[9px] font-black text-red-500 hover:bg-red-500 hover:text-white px-2 py-1 rounded border border-red-100 transition-all uppercase flex items-center gap-1 shadow-sm"
                                                    >
                                                        <XOctagon className="w-3 h-3" /> Purgar Métrica
                                                    </button>
                                                </div>

                                                <div className="space-y-1.5 ml-6">
                                                    {group.rules.map((rule: any) => (
                                                        <div key={rule.id} className={`flex items-center justify-between p-2 rounded-lg border text-xs shadow-sm transition-all ${selectedRule?.id === rule.id ? 'bg-primary/5 border-primary/30 ring-1 ring-primary/10' : 'bg-white hover:border-slate-300'}`}>
                                                            <div className="flex items-center gap-4 flex-1 min-w-0">
                                                                <div className="flex items-center gap-2 min-w-[120px]">
                                                                    <span className="bg-slate-100 px-1.5 py-0.5 rounded text-[10px] font-black border uppercase tracking-tighter">
                                                                        {rule.operator}
                                                                    </span>
                                                                    <span className="font-mono text-xs text-primary font-black">
                                                                        {rule.threshold_value}
                                                                    </span>
                                                                </div>

                                                                <span className={`px-2 py-0.5 rounded text-[9px] font-black uppercase border tracking-widest ${rule.alert_category === 'danger' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100'
                                                                    }`}>
                                                                    {rule.alert_category}
                                                                </span>

                                                                <span className="text-[10px] text-slate-400 italic truncate ml-2">
                                                                    "{rule.description}"
                                                                </span>
                                                            </div>

                                                            <div className="flex items-center gap-1 ml-4 border-l pl-2 border-slate-100">
                                                                <button onClick={() => setSelectedRule(rule)} className="p-1.5 hover:bg-slate-50 rounded text-primary transition-all" title="Editar"><Settings className="w-4 h-4" /></button>
                                                                <button
                                                                    onClick={async () => {
                                                                        if (confirm(`¿Eliminar solo esta regla?`)) {
                                                                            if (await deleteItem('alert_rules', rule.id)) {
                                                                                const r = await fetchData('alert_rules');
                                                                                setAlertRules(r || []);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="p-1.5 hover:bg-orange-50 rounded text-orange-400 hover:text-orange-600 transition-all"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}

                                        {unlinked.length > 0 && (
                                            <div className="bg-slate-50 border-t-8 border-slate-100 shadow-inner">
                                                <div className="p-4 bg-slate-100/50 flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Trash2 className="w-4 h-4 text-slate-400" />
                                                        <h3 className="font-black text-slate-500 uppercase text-[10px] tracking-widest">Depósito de Reglas de Patrón / Basura</h3>
                                                    </div>
                                                    <span className="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-bold">{unlinked.length}</span>
                                                </div>
                                                <div className="px-6 py-4 space-y-2">
                                                    {unlinked.map((rule: any) => (
                                                        <div key={rule.id} className="flex items-center justify-between p-3 bg-white border-2 border-dashed border-slate-200 rounded-xl hover:border-slate-400 transition-all shadow-sm">
                                                            <div className="flex-1 min-w-0">
                                                                <div className="font-mono text-[10px] font-bold text-slate-500 truncate mb-1 bg-slate-50 px-2 py-1 rounded inline-block">{rule.metric_pattern}</div>
                                                                <div className="flex items-center gap-3">
                                                                    <span className="text-[10px] bg-muted px-2 py-0.5 rounded font-black border uppercase">{rule.operator} {rule.threshold_value}</span>
                                                                    <span className={`text-[9px] px-2 py-0.5 rounded font-black uppercase border shadow-sm ${rule.alert_category === 'danger' ? 'bg-red-500 text-white border-red-600' : 'bg-amber-400 text-white border-amber-500'
                                                                        }`}>{rule.alert_category}</span>
                                                                    <span className="text-[10px] text-slate-400 italic font-medium ml-2">"{rule.description}"</span>
                                                                </div>
                                                            </div>
                                                            <div className="flex gap-1 ml-4 border-l pl-3 border-slate-100">
                                                                <button onClick={() => setSelectedRule(rule)} className="p-2 hover:bg-slate-100 rounded-lg text-slate-400 hover:text-primary transition-all"><Settings className="w-4 h-4" /></button>
                                                                <button
                                                                    onClick={async () => {
                                                                        const params = rule.metric_id ? `&metric_id=${rule.metric_id}` : `&pattern=${encodeURIComponent(rule.metric_pattern)}`;
                                                                        const details = await fetchData('metric_details', params);
                                                                        if (details) setPurgeData({ ...details, rule_id: rule.id });
                                                                    }}
                                                                    className="p-2 hover:bg-red-50 rounded text-red-300 hover:text-red-500 transition-all"
                                                                >
                                                                    <XOctagon className="w-4 h-4" />
                                                                </button>
                                                                <button
                                                                    onClick={async () => {
                                                                        if (confirm('¿Eliminar regla?')) {
                                                                            if (await deleteItem('alert_rules', rule.id)) {
                                                                                const r = await fetchData('alert_rules');
                                                                                setAlertRules(r || []);
                                                                            }
                                                                        }
                                                                    }}
                                                                    className="p-2 hover:bg-orange-50 rounded text-orange-300 hover:text-orange-500 transition-all"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                );
                            })()}
                        </div>
                    </div>

                    <div className="col-span-4 bg-card rounded-xl border p-6 h-fit sticky top-6 shadow-sm">
                        <div className="flex items-center gap-2 mb-6 border-b pb-4">
                            <Settings className="w-5 h-5 text-primary" />
                            <h2 className="font-bold">{selectedRule?.id ? 'Configurar Regla' : 'Nueva Regla de Alerta'}</h2>
                        </div>

                        {selectedRule ? (
                            <form className="space-y-5" onSubmit={async (e) => {
                                e.preventDefault();
                                if (await sendData('alert_rules', 'POST', selectedRule)) {
                                    const r = await fetchData('alert_rules');
                                    setAlertRules(r || []);
                                    setSelectedRule(null);
                                }
                            }}>
                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase flex items-center gap-1">
                                        Métrica Asociada
                                        <div className="h-px bg-border flex-1 ml-1 opacity-50"></div>
                                    </label>
                                    <select
                                        className="w-full p-2.5 border rounded-lg bg-muted/30 focus:ring-1 ring-primary outline-none transition-all text-sm font-medium"
                                        value={selectedRule.metric_id || ''}
                                        onChange={e => setSelectedRule({
                                            ...selectedRule,
                                            metric_id: e.target.value ? parseInt(e.target.value) : null,
                                            metric_pattern: e.target.value ? '' : selectedRule.metric_pattern
                                        })}
                                    >
                                        <option value="">-- Usar Patrón Manual (Regex/Like) --</option>
                                        {metrics.map(m => (
                                            <option key={m.id} value={m.id}>{m.display_name} ({m.name})</option>
                                        ))}
                                    </select>
                                    {!selectedRule.metric_id && (
                                        <input
                                            type="text"
                                            placeholder="Patrón (ej: system.cpu.%)"
                                            className="w-full p-2.5 border rounded-lg bg-orange-50/30 border-orange-200 text-sm font-mono focus:ring-1 ring-orange-500 outline-none"
                                            value={selectedRule.metric_pattern}
                                            onChange={e => setSelectedRule({ ...selectedRule, metric_pattern: e.target.value })}
                                        />
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-muted-foreground uppercase">Evaluación</label>
                                        <select
                                            className="w-full p-2.5 border rounded-lg bg-muted/30 text-sm font-bold"
                                            value={selectedRule.operator}
                                            onChange={e => setSelectedRule({ ...selectedRule, operator: e.target.value })}
                                        >
                                            <optgroup label="Numérico">
                                                <option value=">">Mayor que {'>'}</option>
                                                <option value=">=">Mayor o igual {'>='}</option>
                                                <option value="<">Menor que {'<'}</option>
                                                <option value="<=">Menor o igual {'<='}</option>
                                                <option value="BETWEEN">Entre (X,Y)</option>
                                            </optgroup>
                                            <optgroup label="Texto / Especial">
                                                <option value="CONTAINS">Contiene texto</option>
                                                <option value="NOT_CONTAINS">NO contiene</option>
                                                <option value="REGEX">Expresión Regular</option>
                                            </optgroup>
                                            <optgroup label="Tiempo / Fecha">
                                                <option value="AGE_GREATER_THAN">Antigüedad {'>'} a...</option>
                                                <option value="DURATION_GREATER_THAN">Duración {'>'} a...</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="text-[10px] font-bold text-muted-foreground uppercase">Valor Umbral</label>
                                        <input
                                            type="text"
                                            placeholder={selectedRule.operator === 'BETWEEN' ? "ej: 10,20" : "5.5, Error..."}
                                            className="w-full p-2.5 border rounded-lg bg-muted/30 text-sm font-bold focus:ring-1 ring-primary outline-none"
                                            value={selectedRule.threshold_value}
                                            onChange={e => setSelectedRule({ ...selectedRule, threshold_value: e.target.value })}
                                        />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase">Gravedad / Categoría</label>
                                    <div className="grid grid-cols-3 gap-2">
                                        {['warning', 'danger', 'critical'].map(cat => (
                                            <button
                                                key={cat}
                                                type="button"
                                                onClick={() => setSelectedRule({ ...selectedRule, alert_category: cat })}
                                                className={`py-2 rounded-lg text-[10px] font-black uppercase border transition-all ${selectedRule.alert_category === cat
                                                    ? (cat === 'warning' ? 'bg-amber-100 border-amber-400 text-amber-700 ring-2 ring-amber-400/20' :
                                                        cat === 'danger' ? 'bg-orange-100 border-orange-400 text-orange-700 ring-2 ring-orange-400/20' :
                                                            'bg-red-100 border-red-400 text-red-700 ring-2 ring-red-400/20')
                                                    : 'bg-muted/30 border-transparent text-muted-foreground opacity-50 grayscale hover:grayscale-0'
                                                    }`}
                                            >
                                                {cat}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase">Mensaje de la Alerta (Descripción)</label>
                                    <textarea
                                        className="w-full p-3 border rounded-lg bg-muted/30 h-28 text-sm focus:ring-1 ring-primary outline-none"
                                        placeholder="Ej: Se ha detectado una sobrecarga crítica en la CPU..."
                                        value={selectedRule.description}
                                        onChange={e => setSelectedRule({ ...selectedRule, description: e.target.value })}
                                    />
                                    <p className="text-[10px] text-muted-foreground italic">Este texto aparecerá en el FS de Alerta cuando se cumpla la condición.</p>
                                </div>

                                <div className="pt-2 flex gap-2">
                                    <button type="submit" className="flex-1 bg-primary text-primary-foreground py-3 rounded-xl font-bold flex items-center justify-center gap-2 shadow-lg hover:shadow-primary/20 active:scale-[0.98] transition-all">
                                        <Save className="w-5 h-5" /> Guardar Regla
                                    </button>
                                    <button type="button" onClick={() => setSelectedRule(null)} className="px-4 border rounded-xl hover:bg-muted font-bold text-xs uppercase tracking-wider">Cancelar</button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-center py-20 border-2 border-dashed rounded-2xl bg-muted/10 opacity-60">
                                <AlertCircle className="w-16 h-16 text-muted-foreground/30 mx-auto mb-4" />
                                <p className="text-sm text-muted-foreground px-10">Selecciona una regla para editar sus parámetros o define una condición nueva.</p>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Purge Metric & Rules Modal */}
            {purgeData && (
                <div className="fixed inset-0 bg-red-950/40 z-[60] flex items-center justify-center p-4 backdrop-blur-md">
                    <div className="bg-white rounded-2xl border-4 border-red-200 shadow-[0_20px_50px_rgba(0,0,0,0.3)] w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
                        <div className="p-6 bg-red-600 text-white flex justify-between items-start">
                            <div className="flex items-center gap-4">
                                <div className="p-3 bg-white/20 rounded-xl">
                                    <Trash2 className="w-8 h-8" />
                                </div>
                                <div>
                                    <h3 className="text-2xl font-black uppercase tracking-tight">Confirmar Eliminación Total</h3>
                                    <p className="text-red-100 font-medium">Estás a punto de borrar una métrica y todos sus vínculos.</p>
                                </div>
                            </div>
                            <button onClick={() => setPurgeData(null)} className="text-4xl leading-6 hover:scale-110 transition-transform">&times;</button>
                        </div>

                        <div className="flex-1 overflow-y-auto p-8 space-y-6">
                            <div className="bg-red-50 border border-red-100 p-4 rounded-xl">
                                <h4 className="text-xs font-black text-red-800 uppercase mb-2">Métrica del Catálogo</h4>
                                <div className="text-xl font-bold text-red-900">{purgeData.metric.display_name}</div>
                                <div className="text-sm font-mono text-red-700 opacity-60">{purgeData.metric.name}</div>
                            </div>

                            <div className="grid grid-cols-2 gap-6">
                                <div className="space-y-3">
                                    <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <Terminal className="w-4 h-4" /> Comandos que se borrarán
                                    </h4>
                                    {purgeData.command ? (
                                        <div className="bg-slate-100 p-3 rounded-lg border font-mono text-[10px] text-slate-600 break-all leading-relaxed">
                                            {purgeData.command.command}
                                        </div>
                                    ) : <div className="text-xs italic text-slate-400">Sin comandos asociados</div>}
                                </div>

                                <div className="space-y-3">
                                    <h4 className="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <Server className="w-4 h-4" /> Servidores Afectados
                                    </h4>
                                    <div className="max-h-32 overflow-y-auto space-y-1.5 pr-2">
                                        {purgeData.servers.length > 0 ? (
                                            purgeData.servers.map((s: any, i: number) => (
                                                <div key={i} className="text-xs bg-slate-50 border p-2 rounded flex justify-between">
                                                    <span className="font-bold">{s.name}</span>
                                                    <span className="opacity-50 font-mono">{s.ip}</span>
                                                </div>
                                            ))
                                        ) : <div className="text-xs italic text-slate-400">No asiganda a servidores actualmente</div>}
                                    </div>
                                </div>
                            </div>

                            <div className="bg-amber-50 border border-amber-200 p-4 rounded-xl flex items-start gap-4">
                                <AlertCircle className="w-6 h-6 text-amber-600 flex-none mt-1" />
                                <div className="text-sm text-amber-900">
                                    <p className="font-bold">Esta acción es irreversible:</p>
                                    <ul className="list-disc ml-5 mt-2 space-y-1 opacity-80">
                                        <li>Se borrará la regla de alerta asociada.</li>
                                        <li>Se borrará la métrica del catálogo global.</li>
                                        <li>Se eliminarán todos los comandos vinculados.</li>
                                        <li>Se borrará el histórico de valores de esta métrica.</li>
                                        <li>Se quitará esta métrica de todos los perfiles (Templates).</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div className="p-6 bg-slate-50 border-t flex gap-4">
                            <button
                                onClick={async () => {
                                    setLoading(true);
                                    try {
                                        let ok = true;
                                        if (purgeData.metric.id > 0) {
                                            ok = await sendData('metrics', 'DELETE', {}, `&id=${purgeData.metric.id}`);
                                        }

                                        if (ok) {
                                            if (purgeData.rule_id) {
                                                await deleteItem('alert_rules', purgeData.rule_id);
                                            }

                                            // Refresh everything
                                            const r = await fetchData('alert_rules');
                                            setAlertRules(r || []);
                                            const m = await fetchData('metrics');
                                            setMetrics(m || []);
                                            setPurgeData(null);
                                            setMessage({ type: 'success', text: purgeData.metric.id > 0 ? 'Métrica y vínculos purgados completamente.' : 'Regla eliminada correctamente.' });
                                        }
                                    } catch (err: any) {
                                        setMessage({ type: 'error', text: 'Error al purgar datos: ' + err.message });
                                    } finally {
                                        setLoading(false);
                                    }
                                }}
                                className="flex-1 bg-red-600 hover:bg-red-700 text-white font-black py-4 rounded-xl shadow-lg shadow-red-200 transition-all active:scale-95 uppercase tracking-wider"
                            >
                                Sí, borrar todo permanentemente
                            </button>
                            <button
                                onClick={() => setPurgeData(null)}
                                className="px-8 bg-white border-2 border-slate-200 hover:bg-slate-50 text-slate-600 font-bold rounded-xl transition-all"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            )}
            {/* Connection Path Modal */}
            {selectedConnection && (
                <div className="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center p-4 backdrop-blur-sm animate-in fade-in duration-300">
                    <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200">
                        <div className="bg-indigo-600 p-6 text-white flex justify-between items-center">
                            <div className="flex items-center gap-3">
                                <Network className="w-6 h-6" />
                                <div>
                                    <h3 className="text-xl font-bold">Ruta de Conexión</h3>
                                    <p className="text-indigo-100 text-sm opacity-80">Configuración de saltos para: <span className="font-bold">{selectedConnection.server.name}</span></p>
                                </div>
                            </div>
                            <button onClick={() => setSelectedConnection(null)} className="p-2 hover:bg-white/20 rounded-lg transition-colors text-2xl leading-none">&times;</button>
                        </div>

                        <div className="p-6 space-y-6">
                            {/* Connection Info */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Nombre Conexión</label>
                                    <input
                                        type="text"
                                        value={selectedConnection.connection.connection_name}
                                        onChange={e => setSelectedConnection({
                                            ...selectedConnection,
                                            connection: { ...selectedConnection.connection, connection_name: e.target.value }
                                        })}
                                        className="w-full p-2.5 bg-slate-50 border rounded-xl text-sm focus:ring-2 ring-indigo-500/20 outline-none"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Estado de Seguimiento</label>
                                    <select
                                        value={selectedConnection.connection.connection_status}
                                        onChange={e => setSelectedConnection({
                                            ...selectedConnection,
                                            connection: { ...selectedConnection.connection, connection_status: parseInt(e.target.value) }
                                        })}
                                        className="w-full p-2.5 bg-slate-50 border rounded-xl text-sm focus:ring-2 ring-indigo-500/20 outline-none"
                                    >
                                        <option value={1}>Activo (Monitoreado)</option>
                                        <option value={0}>Inactivo / Pausado</option>
                                    </select>
                                </div>
                            </div>

                            {/* Hops List */}
                            <div className="space-y-3">
                                <div className="flex justify-between items-center">
                                    <h4 className="text-sm font-bold text-slate-700 flex items-center gap-2">
                                        <Terminal className="w-4 h-4 text-indigo-500" /> Secuencia de Saltos (Hops)
                                    </h4>
                                    <button
                                        onClick={() => {
                                            const newHops = [...selectedConnection.paths, { jump_server_id: '', jump_order: selectedConnection.paths.length + 1 }];
                                            setSelectedConnection({ ...selectedConnection, paths: newHops });
                                        }}
                                        className="text-[10px] bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-full font-black hover:bg-indigo-100 transition-colors uppercase tracking-widest"
                                    >
                                        + Agregar Salto
                                    </button>
                                </div>

                                <div className="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                    {selectedConnection.paths.map((path: any, idx: number) => (
                                        <div key={idx} className="flex items-center gap-3 p-3 bg-slate-50 border rounded-xl group transition-all hover:border-indigo-200">
                                            <div className="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-[10px] font-black">
                                                {idx + 1}
                                            </div>
                                            <div className="flex-1">
                                                <select
                                                    value={path.jump_server_id ? String(path.jump_server_id) : ''}
                                                    onChange={e => {
                                                        const newPaths = [...selectedConnection.paths];
                                                        const val = e.target.value;
                                                        const selectedServ = servers.find(s => String(s.id) === val);
                                                        newPaths[idx].jump_server_id = val ? parseInt(val) : null;
                                                        newPaths[idx].jump_server_name = selectedServ ? selectedServ.name : '';
                                                        setSelectedConnection({ ...selectedConnection, paths: newPaths });
                                                    }}
                                                    className="w-full bg-transparent text-sm font-medium outline-none focus:text-indigo-600"
                                                >
                                                    <option value="">Seleccionar servidor de salto...</option>
                                                    {(() => {
                                                        const jid = path.jump_server_id;
                                                        if (!jid) return null;

                                                        const sjid = String(jid);
                                                        const isSelf = sjid === String(selectedConnection.server.id);
                                                        const isActive = servers.some(s => String(s.id) === sjid);

                                                        if (isSelf || !isActive) {
                                                            return (
                                                                <option value={sjid}>
                                                                    {path.jump_server_name || 'Desconocido'} {isSelf ? '(Mismo Servidor)' : '(Inactivo/Eliminado)'}
                                                                </option>
                                                            );
                                                        }
                                                        return null;
                                                    })()}

                                                    {servers
                                                        .filter(serv => String(serv.id) !== String(selectedConnection.server.id))
                                                        .map(serv => (
                                                            <option key={serv.id} value={String(serv.id)}>
                                                                {serv.name} ({serv.ip_address})
                                                            </option>
                                                        ))
                                                    }

                                                </select>

                                            </div>
                                            <button
                                                onClick={() => {
                                                    const newPaths = selectedConnection.paths.filter((_: any, i: number) => i !== idx);
                                                    setSelectedConnection({ ...selectedConnection, paths: newPaths });
                                                }}
                                                className="opacity-0 group-hover:opacity-100 p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ))}
                                    {selectedConnection.paths.length === 0 && (
                                        <div className="text-center py-8 bg-slate-50 border border-dashed rounded-xl text-slate-400 text-xs italic">
                                            Sin saltos configurados. La conexión es directa.
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="p-4 bg-indigo-50 rounded-xl border border-indigo-100 flex gap-3 italic">
                                <AlertCircle className="w-4 h-4 text-indigo-500 mt-0.5" />
                                <p className="text-[10px] text-indigo-800 leading-relaxed">
                                    El orden de los saltos define la ruta lógica. El sistema usará esta jerarquía para determinar si una falla es de conectividad local o de un salto intermedio.
                                </p>
                            </div>
                        </div>

                        <div className="p-6 bg-slate-50 border-t flex gap-3">
                            <button
                                onClick={() => setSelectedConnection(null)}
                                className="px-6 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-600 text-sm font-bold hover:bg-slate-50 transition-all"
                            >
                                Cancelar
                            </button>
                            <button
                                onClick={async () => {
                                    setLoading(true);
                                    try {
                                        // 1. Save Connection
                                        const connectionResp = await fetch(`${API_BASE}?action=connections`, {
                                            method: 'POST',
                                            headers: {
                                                'Authorization': `Bearer ${user.token}`,
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify(selectedConnection.connection)
                                        });
                                        const connData = await connectionResp.json();

                                        if (!connectionResp.ok) throw new Error(connData.message);

                                        const cid = connData.id;

                                        // 2. Save Paths
                                        const pathResp = await fetch(`${API_BASE}?action=connection_paths`, {
                                            method: 'POST',
                                            headers: {
                                                'Authorization': `Bearer ${user.token}`,
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                connection_id: cid,
                                                hops: selectedConnection.paths.map((p: any) => p.jump_server_id).filter((id: any) => id)
                                            })
                                        });
                                        const pathData = await pathResp.json();

                                        if (!pathResp.ok) throw new Error(pathData.message);

                                        setMessage({ type: 'success', text: 'Ruta de conexión actualizada perfectamente.' });
                                        setSelectedConnection(null);
                                    } catch (err: any) {
                                        setMessage({ type: 'error', text: 'Error al guardar la ruta: ' + err.message });
                                    } finally {
                                        setLoading(false);
                                    }
                                }}
                                className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2"
                            >
                                <Save className="w-4 h-4" /> Guardar Configuración
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Tab: Users */}
            {activeTab === 'users' && <UsersManagementTab />}

            {/* Tab: Iconography */}
            {activeTab === 'iconography' && <MiningIconGallery />}

            {/* Tab: Rooteo */}
            {activeTab === 'rooteo' && <RooteoTab />}
        </div >
    );
};


export default SuperAdmin;
