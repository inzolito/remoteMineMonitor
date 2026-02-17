import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getSiteById } from '../services/sitesService';
import { getServersBySite, createServer, updateServer, type Server as ServerType } from '../services/serversService';
import { getContacts, type SiteContact } from '../services/contactsService';
import { Loader2, Server, Plus, Edit2, Phone, Users, Mail, Eye, EyeOff, Copy, Terminal, Cpu, Key, Database, Clock } from 'lucide-react';
import ServerModal from '../components/ServerModal';
import EditSiteModal from '../components/EditSiteModal';

const SiteDetails = () => {
    const { id } = useParams<{ id: string }>();
    const queryClient = useQueryClient();
    const siteId = parseInt(id || '0');

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditSiteModalOpen, setIsEditSiteModalOpen] = useState(false);
    const [editModalTab, setEditModalTab] = useState<'general' | 'contacts'>('general');
    const [editingServer, setEditingServer] = useState<ServerType | null>(null);
    const [revealedServerId, setRevealedServerId] = useState<number | null>(null);

    // Query for Site Details
    const { data: site, isLoading: siteLoading } = useQuery({
        queryKey: ['site', siteId],
        queryFn: () => getSiteById(siteId),
        enabled: !!siteId
    });

    // Query for Contacts
    const { data: contacts } = useQuery({
        queryKey: ['contacts', siteId],
        queryFn: () => getContacts(siteId),
        enabled: !!siteId
    });

    // Query for Servers
    const { data: servers, isLoading: serversLoading } = useQuery({
        queryKey: ['servers', siteId],
        queryFn: () => getServersBySite(siteId),
        enabled: !!siteId,
        refetchInterval: 5000 // Real-time status updates
    });

    const createMutation = useMutation({
        mutationFn: createServer,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['servers', siteId] });
            setIsModalOpen(false);
        }
    });

    const updateMutation = useMutation({
        mutationFn: updateServer,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['servers', siteId] });
            setIsModalOpen(false);
        }
    });

    const handleSaveServer = async (serverData: Partial<ServerType>) => {
        if (editingServer) {
            await updateMutation.mutateAsync({ ...serverData, id: editingServer.id });
        } else {
            await createMutation.mutateAsync({ ...serverData, site_id: siteId });
        }
    };

    const handleEditClick = (server: ServerType) => {
        setEditingServer(server);
        setIsModalOpen(true);
    };

    const handleNewClick = () => {
        setEditingServer(null);
        setIsModalOpen(true);
    };

    // Helper to format server name for the card header
    const getSmartHeader = (server: ServerType) => {
        const name = server.name.toLowerCase();
        const type = server.server_type ? server.server_type.toLowerCase() : '';

        // 0. High Priority Specifics (User Requests)
        if (name.includes('tunel') || type.includes('tunel')) return 'Tunel';
        if (name.includes('pivote') || type.includes('pivote')) return 'Pivote';
        if (name.includes('cas') || type.includes('cas')) return 'Servidor CAS';
        if (name.includes('jview') || type.includes('jview')) return 'Servidor JView';

        // 1. FMS/Dispatcher cases
        if (name.includes('fms') || type.includes('fms')) {
            if (name.includes('activo') || name.includes('primary') || name.includes('principal')) return 'FMS Primario';
            if (name.includes('backup') || name.includes('secondary') || name.includes('secundario')) return 'FMS Secundario';
            return 'Servidor FMS';
        }

        // 2. Base Station
        if (name.includes('base') || type.includes('base')) return 'Estacion Base';

        // 3. SQL Server
        if (name.includes('sql') || type === 'sql') return 'SQL Server';

        // 4. MPData specific
        if (name.includes('mpdata') || type.includes('mpdata')) return 'Servidor MPData';

        // 5. Default: Prefer Type if available and descriptive, else Name
        if (server.server_type && server.server_type !== 'Generic' && server.server_type.length > 3) {
            return server.server_type;
        }

        return server.name;
    };

    // Helper for server colors based on name/type
    const getServerColor = (name: string) => {
        const n = name.toLowerCase();
        if (n.includes('fms')) return 'bg-orange-500';
        if (n.includes('tunel')) return 'bg-teal-600';
        if (n.includes('wenco')) return 'bg-blue-600';
        if (n.includes('infra')) return 'bg-slate-600';
        if (n.includes('pivote')) return 'bg-indigo-600';
        return 'bg-primary'; // Default
    };

    const getLogoPath = (logoUrl: string | null | undefined) => {
        const fallback = '/monitoreoLaboratorio/v3/frontend/dist/img/generic/logoCasco.webp';
        if (!logoUrl || logoUrl.includes('generic')) return fallback;
        return `/monitoreoLaboratorio/v3/frontend/dist/img/${logoUrl}`;
    };

    if (siteLoading || serversLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <Loader2 className="w-10 h-10 text-primary animate-spin" />
            </div>
        );
    }

    if (!site) {
        return <div className="p-10 text-destructive">Faena no encontrada.</div>;
    }

    const groupedContacts = contacts?.reduce((acc, contact) => {
        const role = contact.role;
        if (!acc[role]) acc[role] = [];
        acc[role].push(contact);
        return acc;
    }, {} as Record<string, SiteContact[]>) || {};

    return (
        <div className="space-y-8 animate-fade-in-up">
            {/* Header Section */}
            <div className="bg-card rounded-xl border border-border shadow-sm overflow-hidden relative group">
                <div className="absolute top-4 right-4 flex gap-2">
                    {/* Contacts Manager Button */}
                    <button
                        onClick={() => {
                            setEditModalTab('contacts');
                            setIsEditSiteModalOpen(true);
                        }}
                        className="p-2 bg-muted hover:bg-primary hover:text-white rounded-full transition-all"
                        title="Gestionar Personal"
                    >
                        <Users className="w-4 h-4" />
                    </button>
                    {/* Edit Site Button */}
                    <button
                        onClick={() => {
                            setEditModalTab('general');
                            setIsEditSiteModalOpen(true);
                        }}
                        className="p-2 bg-muted hover:bg-primary hover:text-white rounded-full transition-all"
                        title="Editar Detalles de Faena"
                    >
                        <Edit2 className="w-4 h-4" />
                    </button>
                </div>

                {/* Title Bar */}
                <div className="p-6 md:p-8 flex flex-col md:flex-row gap-8">
                    {/* Left: Logo & Title */}
                    <div className="flex-shrink-0 flex flex-col items-center md:items-start gap-4 min-w-[200px] border-b md:border-b-0 md:border-r border-border pb-6 md:pb-0 md:pr-8">
                        <div className="w-40 h-40 bg-white rounded-xl shadow-inner flex items-center justify-center p-4 border border-border/50">
                            <img
                                src={getLogoPath(site.logo_url)}
                                alt="Logo"
                                className="w-full h-full object-contain"
                                onError={(e) => { e.currentTarget.src = '/monitoreoLaboratorio/v3/frontend/dist/img/generic/logoCasco.webp'; }}
                            />
                        </div>
                        <div className="text-center md:text-left">
                            <h1 className="text-2xl font-bold text-foreground">{site.name}</h1>
                            <p className="text-primary font-medium">{site.conglomerate}</p>

                            {/* Contract Admin Display */}
                            <div className="mt-4">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-muted-foreground mb-1">Admin Contrato</h3>
                                {groupedContacts && groupedContacts['contract_admin'] && groupedContacts['contract_admin'].length > 0 ? (
                                    groupedContacts['contract_admin'].map(c => (
                                        <div key={c.id} className="text-sm">
                                            <div className="font-medium">{c.name}</div>
                                            {c.phone && <div className="text-muted-foreground text-xs flex items-center gap-1 justify-center md:justify-start"><Phone className="w-3 h-3" /> {c.phone}</div>}
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-sm text-muted-foreground italic">
                                        No definido
                                        {/* Debug: {contacts ? ` (loaded ${contacts.length})` : ' (loading)'} */}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right: Contacts Grid */}
                    <div className="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Dispatch Column */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-2 border-b border-border pb-2">
                                <Phone className="w-5 h-5 text-primary" />
                                <h3 className="font-semibold text-foreground">Despacho</h3>
                            </div>
                            <div className="space-y-3">
                                {groupedContacts['dispatch']?.map(c => (
                                    <div key={c.id} className="bg-muted/30 p-2 rounded border border-border/50">
                                        <div className="font-medium text-sm">{c.name}</div>
                                        {c.phone && <div className="text-xs text-muted-foreground flex items-center gap-1"><Phone className="w-3 h-3" /> {c.phone}</div>}
                                        {c.email && <div className="text-xs text-muted-foreground flex items-center gap-1"><Mail className="w-3 h-3" /> {c.email}</div>}
                                    </div>
                                )) || <span className="text-sm text-muted-foreground italic">Sin contactos registrados</span>}
                            </div>
                        </div>

                        {/* Onsite Engineers Column */}
                        <div className="space-y-4">
                            <div className="flex items-center gap-2 border-b border-border pb-2">
                                <Users className="w-5 h-5 text-primary" />
                                <h3 className="font-semibold text-foreground">Ingenieros Onsite</h3>
                            </div>
                            <div className="space-y-3">
                                {groupedContacts['onsite_engineer']?.map(c => (
                                    <div key={c.id} className="bg-muted/30 p-2 rounded border border-border/50">
                                        <div className="font-medium text-sm">{c.name}</div>
                                        {c.phone && <div className="text-xs text-muted-foreground flex items-center gap-1"><Phone className="w-3 h-3" /> {c.phone}</div>}
                                        {c.email && <div className="text-xs text-muted-foreground flex items-center gap-1"><Mail className="w-3 h-3" /> {c.email}</div>}
                                    </div>
                                )) || <span className="text-sm text-muted-foreground italic">Sin ingenieros registrados</span>}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Servers Section */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Server className="w-6 h-6 text-primary" />
                    <h2 className="text-xl font-bold text-foreground">Infraestructura de Servidores</h2>
                </div>
                <button
                    onClick={handleNewClick}
                    className="flex justify-center items-center gap-2 px-6 py-2 bg-primary text-primary-foreground rounded-xl font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95"
                >
                    <Plus className="w-5 h-5" />
                    <span>Agregar Server</span>
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                {/* Server Cards */}
                {servers?.map((server) => (
                    <div key={server.id} className="bg-card rounded-xl border border-border shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden flex flex-col group">
                        {/* Header - Minimalist with Color Accent */}
                        <div className="relative pt-1">
                            <div className={`absolute top-0 left-0 right-0 h-1 ${getServerColor(server.name)}`}></div>
                            <div className="px-4 py-3 flex justify-between items-start">
                                <div className="flex flex-col gap-0.5 overflow-hidden">
                                    <div className="flex items-center gap-2">
                                        <div className={`w-2 h-2 rounded-full ${server.status === 1 ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'}`}></div>
                                        <h3 className="font-bold text-foreground text-sm truncate" title={getSmartHeader(server)}>
                                            {getSmartHeader(server)}
                                        </h3>
                                    </div>
                                    <span className="text-xs text-muted-foreground font-mono pl-4">{server.ip_address}</span>
                                </div>
                                <button
                                    onClick={() => handleEditClick(server)}
                                    className="text-muted-foreground hover:text-primary transition-colors p-1 rounded-md hover:bg-muted"
                                >
                                    <Edit2 className="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        {/* Card Body - Clean List */}
                        <div className="px-4 pb-4 pt-0 space-y-4 flex-1 flex flex-col">

                            {/* --- FULL SERVER DETAILS (Requested by User) --- */}
                            <div className="space-y-3">
                                {/* General Specs */}
                                <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                    <div className="flex flex-col">
                                        <span className="text-[9px] uppercase font-bold text-muted-foreground tracking-wider">Sistema</span>
                                        <div className="flex items-center gap-1.5 text-foreground">
                                            <Cpu className="w-3 h-3 text-primary/70" />
                                            <span className="truncate" title={server.os}>{server.os || '-'}</span>
                                        </div>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-[9px] uppercase font-bold text-muted-foreground tracking-wider">Tipo</span>
                                        <div className="flex items-center gap-1.5 text-foreground">
                                            <Server className="w-3 h-3 text-primary/70" />
                                            <span className="truncate" title={server.server_type}>{server.server_type || '-'}</span>
                                        </div>
                                    </div>
                                    <div className="col-span-2 flex flex-col pt-1">
                                        <span className="text-[9px] uppercase font-bold text-muted-foreground tracking-wider">Conexión</span>
                                        <div className="flex items-center gap-1.5 text-foreground font-mono">
                                            <Terminal className="w-3 h-3 text-primary/70" />
                                            <span className="truncate">{server.protocol || 'SSH'}:{server.port || '22'}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Description & Notes (Moved to Footer) */}

                                {/* Database Details (If applicable) */}
                                {(server.db_engine || server.db_name || server.db_user) && (
                                    <div className="space-y-2 pt-1">
                                        <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block border-b border-border/40 pb-1">Base de Datos</span>
                                        <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                            {server.db_engine && (
                                                <div className="flex flex-col">
                                                    <span className="text-[9px] font-bold text-muted-foreground">Motor</span>
                                                    <div className="flex items-center gap-1.5 text-foreground">
                                                        <Database className="w-3 h-3 text-blue-500/70" />
                                                        <span>{server.db_engine}</span>
                                                    </div>
                                                </div>
                                            )}
                                            {server.db_name && (
                                                <div className="flex flex-col">
                                                    <span className="text-[9px] font-bold text-muted-foreground">Nombre DB</span>
                                                    <span className="text-foreground truncate" title={server.db_name}>{server.db_name}</span>
                                                </div>
                                            )}
                                            {server.db_user && (
                                                <div className="col-span-2 flex items-center gap-2 pt-1">
                                                    <span className="text-[9px] font-bold text-muted-foreground">User:</span>
                                                    <span className="font-mono bg-muted/30 px-1 rounded">{server.db_user}</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {server.last_seen && (
                                    <div className="flex items-center gap-1.5 text-[10px] text-muted-foreground justify-end pt-1">
                                        <Clock className="w-3 h-3 opacity-70" />
                                        <span>Visto: {server.last_seen}</span>
                                    </div>
                                )}
                            </div>


                            {/* --- ACCESS / CREDENTIALS --- */}
                            {(server.ssh_user || server.ssh_password) && (
                                <div className="mt-auto pt-3 border-t border-border/50">
                                    <span className="text-[10px] uppercase font-bold text-muted-foreground tracking-wider block mb-2">Acceso</span>

                                    <div className="grid grid-cols-[60px_1fr] gap-y-2 gap-x-2 items-center text-xs">
                                        {server.ssh_user && (
                                            <>
                                                {/* Username Row */}
                                                <div className="text-muted-foreground flex items-center gap-1.5">
                                                    <Users className="w-3.5 h-3.5" />
                                                    User
                                                </div>
                                                <div className="font-mono text-foreground bg-muted/30 px-2 py-1 rounded select-all truncate">
                                                    {server.ssh_user}
                                                </div>
                                            </>
                                        )}

                                        {/* Password Row */}
                                        {server.ssh_password && (
                                            <>
                                                <div className="text-muted-foreground flex items-center gap-1.5">
                                                    <Key className="w-3.5 h-3.5" />
                                                    Pass
                                                </div>
                                                <div className="flex items-center gap-1 min-w-0">
                                                    <div className="font-mono text-foreground bg-muted/30 px-2 py-1 rounded flex-1 text-left cursor-pointer hover:bg-muted/50 transition-colors truncate"
                                                        onClick={() => setRevealedServerId(revealedServerId === server.id ? null : server.id)}
                                                        title={revealedServerId === server.id ? "Ocultar" : "Mostrar"}
                                                    >
                                                        {revealedServerId === server.id ? server.ssh_password : '••••••••'}
                                                    </div>

                                                    <button
                                                        className="p-1 hover:text-primary transition-colors text-muted-foreground flex-shrink-0"
                                                        onClick={() => setRevealedServerId(revealedServerId === server.id ? null : server.id)}
                                                        title={revealedServerId === server.id ? "Ocultar" : "Mostrar"}
                                                    >
                                                        {revealedServerId === server.id ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                                                    </button>

                                                    <button
                                                        className="p-1 hover:text-primary transition-colors text-muted-foreground flex-shrink-0"
                                                        onClick={() => {
                                                            navigator.clipboard.writeText(server.ssh_password || '');
                                                            // toast or alert? simplistic for now
                                                        }}
                                                        title="Copiar"
                                                    >
                                                        <Copy className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* --- FOOTER: Description & Notes --- */}
                        {(server.description || server.notes) && (
                            <div className="px-4 py-3 bg-muted/30 border-t border-border text-xs space-y-2">
                                {server.description && (
                                    <div>
                                        <span className="text-[9px] uppercase font-bold text-muted-foreground tracking-wider block mb-0.5">Descripción</span>
                                        <div className="text-foreground leading-snug">{server.description}</div>
                                    </div>
                                )}
                                {server.notes && (
                                    <div>
                                        <span className="text-[9px] uppercase font-bold text-amber-500/80 tracking-wider block mb-0.5">Notas</span>
                                        <div className="text-muted-foreground italic leading-snug">{server.notes}</div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            <ServerModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                onSave={handleSaveServer}
                initialData={editingServer}
            />

            {site && (
                <EditSiteModal
                    isOpen={isEditSiteModalOpen}
                    onClose={() => setIsEditSiteModalOpen(false)}
                    site={site}
                    initialTab={editModalTab}
                />
            )}
        </div>
    );
};

export default SiteDetails;
