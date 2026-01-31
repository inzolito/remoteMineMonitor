import { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { Navigate } from 'react-router-dom';
import {
    ShieldCheck, Server, Activity, Terminal,
    FileText, Plus, Trash2, Save, RefreshCw, Search,
    AlertCircle, ChevronRight, Settings, Eye
} from 'lucide-react';
import RooteoTab from '../components/RooteoTab';

const API_BASE = '/monitoreoLaboratorio/v3/api/admin.php';

const SuperAdmin = () => {
    const { user } = useAuth();
    const [activeTab, setActiveTab] = useState('servers');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);

    // Data states
    const [servers, setServers] = useState<any[]>([]);
    const [metrics, setMetrics] = useState<any[]>([]);
    const [serverTypes, setServerTypes] = useState<any[]>([]);
    const [commands, setCommands] = useState<any[]>([]);
    const [templateMetrics, setTemplateMetrics] = useState<any[]>([]);

    // Selected items for editing
    const [selectedMetric, setSelectedMetric] = useState<any>(null);
    const [selectedType, setSelectedType] = useState<any>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [detailMetric, setDetailMetric] = useState<any>(null); // For modal

    // Security: Only maik
    if (user?.username !== 'maik') {
        return <Navigate to="/" replace />;
    }

    const fetchData = async (action: string, params: string = '') => {
        setLoading(true);
        try {
            const resp = await fetch(`${API_BASE}?action=${action}${params}`, {
                headers: { 'Authorization': `Bearer ${user.token}` }
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

    const sendData = async (action: string, method: string, body: any) => {
        setLoading(true);
        try {
            const resp = await fetch(`${API_BASE}?action=${action}`, {
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

    const deleteItem = async (action: string, id: number, extra: string = '') => {
        if (!confirm('¿Estás seguro de eliminar este elemento?')) return;
        setLoading(true);
        try {
            const resp = await fetch(`${API_BASE}?action=${action}&id=${id}${extra}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${user.token}` }
            });
            if (resp.ok) {
                setMessage({ type: 'success', text: 'Eliminado correctamente' });
                return true;
            }
            const data = await resp.json();
            throw new Error(data.message || 'Error al eliminar');
        } catch (err: any) {
            setMessage({ type: 'error', text: err.message });
            return false;
        } finally {
            setLoading(false);
        }
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
            }
        };
        loadInitial();
    }, [activeTab]);

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

    return (
        <div className="p-8 max-w-7xl mx-auto space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between border-b pb-4">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-primary/10 rounded-lg">
                        <ShieldCheck className="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold">Super Admin Panel</h1>
                        <p className="text-sm text-muted-foreground">Gestión de arquitectura de métricas y comandos (Exclusivo Maik)</p>
                    </div>
                </div>
                {loading && <RefreshCw className="w-5 h-5 animate-spin text-muted-foreground" />}
            </div>

            {message && (
                <div className={`p-4 rounded-lg flex items-center gap-3 ${message.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {message.type === 'success' ? <RefreshCw className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
                    <span>{message.text}</span>
                    <button onClick={() => setMessage(null)} className="ml-auto font-bold">&times;</button>
                </div>
            )}

            {/* Tabs Navigation */}
            <div className="flex gap-2 p-1 bg-muted rounded-xl w-fit">
                {[
                    { id: 'servers', label: 'Servidores', icon: Server },
                    { id: 'metrics', label: 'Catálogo Métricas', icon: Activity },
                    { id: 'commands', label: 'Comandos Fallback', icon: Terminal },
                    { id: 'templates', label: 'Perfiles (Templates)', icon: FileText },
                    { id: 'rooteo', label: 'Rooteo', icon: Eye }
                ].map((tab) => {
                    const Icon = tab.icon;
                    return (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-all ${activeTab === tab.id ? 'bg-background shadow-sm text-primary font-medium' : 'text-muted-foreground hover:text-foreground'}`}
                        >
                            <Icon className="w-4 h-4" />
                            {tab.label}
                        </button>
                    );
                })}
            </div>

            {/* Tab: Servers */}
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
                                                            className="text-primary hover:bg-primary/10 p-1.5 rounded transition-all opacity-0 group-hover:opacity-100"
                                                            title="Sincronizar forzosamente"
                                                        >
                                                            <RefreshCw className="w-3.5 h-3.5" />
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
                                                <button onClick={() => setSelectedMetric(m)} className="p-1.5 hover:bg-muted rounded text-primary"><Settings className="w-4 h-4" /></button>
                                                <button onClick={async () => {
                                                    if (await deleteItem('metrics', m.id)) setMetrics(metrics.filter(x => x.id !== m.id));
                                                }} className="p-1.5 hover:bg-muted rounded text-red-500"><Trash2 className="w-4 h-4" /></button>
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
                                                    // Refresh handled by caller or manual update
                                                    setMessage({ type: 'success', text: 'Comando guardado' });
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
                            </div>
                            <div className="p-4 border-t bg-muted/5 flex justify-end">
                                <button onClick={() => setDetailMetric(null)} className="px-6 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:opacity-90 transition-opacity shadow-sm">Cerrar Detalle</button>
                            </div>
                        </div>
                    </div>
                )
            }

            {/* Tab: Rooteo */}
            {activeTab === 'rooteo' && <RooteoTab />}
        </div >
    );
};

export default SuperAdmin;
