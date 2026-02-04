import { useState, useEffect } from 'react';
import { type Site, updateSite } from '../services/sitesService';
import { X, Loader2, Save, Building, Link, Users, Settings } from 'lucide-react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import ContactsManager from './ContactsManager';

interface EditSiteModalProps {
    isOpen: boolean;
    onClose: () => void;
    site: Site;
    initialTab?: 'general' | 'contacts';
}

const EditSiteModal = ({ isOpen, onClose, site, initialTab = 'general' }: EditSiteModalProps) => {
    const queryClient = useQueryClient();
    const [activeTab, setActiveTab] = useState<'general' | 'contacts'>('general');

    // Identity Fields
    const [name, setName] = useState('');
    const [alias, setAlias] = useState('');
    const [conglomerate, setConglomerate] = useState('');
    const [logoUrl, setLogoUrl] = useState('');

    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (isOpen) {
            setActiveTab(initialTab);
        }
    }, [isOpen, initialTab]);

    useEffect(() => {
        if (isOpen && site) {
            setName(site.name || '');
            setAlias(site.alias || '');
            setConglomerate(site.conglomerate || '');
            setLogoUrl(site.logo_url || '');
            setError(null);
        }
    }, [isOpen, site]);

    const mutation = useMutation({
        mutationFn: async () => {
            await updateSite(site.id, {
                name,
                alias,
                conglomerate,
                logo_url: logoUrl
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['site', site.id] });
            onClose();
        },
        onError: (err: any) => {
            setError(err.response?.data?.message || 'Error al actualizar faena');
        }
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="bg-card w-full max-w-4xl rounded-xl shadow-2xl border border-border flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-border">
                    <h2 className="text-xl font-bold">Editar Faena</h2>
                    <button onClick={onClose} className="p-2 hover:bg-muted rounded-full transition-colors">
                        <X className="w-5 h-5 text-muted-foreground" />
                    </button>
                </div>

                {/* Tabs */}
                <div className="flex border-b border-border px-6">
                    <button
                        onClick={() => setActiveTab('general')}
                        className={`py-3 px-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'general'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                    >
                        <Settings className="w-4 h-4" />
                        General
                    </button>
                    <button
                        onClick={() => setActiveTab('contacts')}
                        className={`py-3 px-4 text-sm font-medium border-b-2 transition-colors flex items-center gap-2 ${activeTab === 'contacts'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                    >
                        <Users className="w-4 h-4" />
                        Personal
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {activeTab === 'general' ? (
                        <form onSubmit={handleSubmit} className="space-y-6 max-w-lg mx-auto">
                            {error && (
                                <div className="bg-destructive/10 text-destructive px-4 py-3 rounded-lg text-sm font-medium">
                                    {error}
                                </div>
                            )}

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 flex items-center gap-2">
                                        <Building className="w-4 h-4 text-primary" />
                                        Nombre de Faena
                                    </label>
                                    <input
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="Ej: Centinela"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Alias (Opcional)
                                    </label>
                                    <input
                                        type="text"
                                        value={alias}
                                        onChange={(e) => setAlias(e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="Nombre corto o código"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        Conglomerado
                                    </label>
                                    <input
                                        type="text"
                                        value={conglomerate}
                                        onChange={(e) => setConglomerate(e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="Ej: AMSA"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 flex items-center gap-2">
                                        <Link className="w-4 h-4 text-primary" />
                                        URL del Logo (Relativa)
                                    </label>
                                    <input
                                        type="text"
                                        value={logoUrl}
                                        onChange={(e) => setLogoUrl(e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="Ej: logos/centinela.png"
                                    />
                                    <p className="text-xs text-muted-foreground">Ruta relativa a <code>/img/</code>.</p>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <button
                                    onClick={(e) => handleSubmit(e as any)}
                                    disabled={mutation.isPending}
                                    className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full justify-center"
                                >
                                    {mutation.isPending ? (
                                        <>
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                            Guardando...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="w-4 h-4" />
                                            Guardar Cambios
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    ) : (
                        <ContactsManager siteId={site.id} />
                    )}
                </div>
            </div>
        </div>
    );
};

export default EditSiteModal;
