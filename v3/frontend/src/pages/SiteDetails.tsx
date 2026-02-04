import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getSiteById } from '../services/sitesService';
import { getServersBySite, createServer, updateServer, type Server as ServerType } from '../services/serversService';
import { getContacts, type SiteContact } from '../services/contactsService';
import { Loader2, Server, Plus, Edit2, Phone, Users, Mail } from 'lucide-react';
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
                                {groupedContacts['contract_admin']?.map(c => (
                                    <div key={c.id} className="text-sm">
                                        <div className="font-medium">{c.name}</div>
                                        {c.phone && <div className="text-muted-foreground text-xs flex items-center gap-1 justify-center md:justify-start"><Phone className="w-3 h-3" /> {c.phone}</div>}
                                    </div>
                                )) || (
                                        <span className="text-sm text-muted-foreground italic">No definido</span>
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

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                {/* Server Cards */}
                {servers?.map((server) => (
                    <div key={server.id} className="bg-card rounded-lg border border-border shadow-sm overflow-hidden flex flex-col group hover:shadow-md transition-shadow">
                        {/* Colored Header */}
                        <div className={`${getServerColor(server.name)} px-4 py-2 flex justify-between items-center`}>
                            <div className="flex items-center gap-2 text-white font-bold truncate">
                                <Server className="w-4 h-4 opacity-80" />
                                {getSmartHeader(server)}
                            </div>
                            <button onClick={() => handleEditClick(server)} className="text-white/70 hover:text-white transition-colors">
                                <Edit2 className="w-3 h-3" />
                            </button>
                        </div>

                        {/* Card Body - Data Grid */}
                        <div className="p-4 text-sm space-y-3">
                            <div className="grid grid-cols-3 gap-2">
                                <span className="text-muted-foreground font-medium">Servidor</span>
                                <span className="col-span-2 text-foreground truncate" title={server.name}>{server.name}</span>
                            </div>

                            <div className="grid grid-cols-3 gap-2 items-center">
                                <span className="text-muted-foreground font-medium">Estado</span>
                                <div className="col-span-2">
                                    {server.status === 1 ? (
                                        <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                            <span className="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Online
                                        </span>
                                    ) : (
                                        <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800">
                                            <span className="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Offline
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-3 gap-2">
                                <span className="text-muted-foreground font-medium">IP</span>
                                <span className="col-span-2 text-foreground font-mono text-xs pt-0.5">{server.ip_address}</span>
                            </div>

                            <div className="grid grid-cols-3 gap-2">
                                <span className="text-muted-foreground font-medium">Sistema</span>
                                <span className="col-span-2 text-foreground">{server.os}</span>
                            </div>

                            <div className="grid grid-cols-3 gap-2">
                                <span className="text-muted-foreground font-medium">Usuario</span>
                                <span className="col-span-2 text-foreground">{server.ssh_user}</span>
                            </div>
                        </div>
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
