import { useNavigate } from 'react-router-dom';
import { useSites } from '../hooks/useSites';
import { Loader2, Wifi, WifiOff, HardHat, Server } from 'lucide-react';
import type { Site } from '../services/sitesService';

const SiteCard = ({ site }: { site: Site }) => {
    const isOnline = site.status === 1;
    const navigate = useNavigate();

    return (
        <div className="bg-card rounded-xl border border-border overflow-hidden hover:border-primary/50 transition-all duration-300 shadow-lg hover:shadow-primary/10 group">
            <div className={`h-2 w-full ${isOnline ? 'bg-green-500' : 'bg-red-500'}`} />

            <div className="p-6">
                <div className="flex justify-between items-start mb-4">
                    <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                            <h3 className="text-lg font-bold text-card-foreground group-hover:text-primary transition-colors line-clamp-1">{site.name}</h3>
                            {isOnline ? (
                                <Wifi className="w-4 h-4 text-green-500" />
                            ) : (
                                <WifiOff className="w-4 h-4 text-red-500" />
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground font-medium">{site.conglomerate}</p>
                    </div>
                    <div className="bg-muted p-2 rounded-lg ml-3">
                        {/* Fallback image if logo_url is missing or complex path logic from legacy */}
                        <img
                            src={site.logo_url && !site.logo_url.includes('generic') ? `dist/img/${site.logo_url}` : '/logoCasco.webp'}
                            alt="Logo"
                            className="w-8 h-8 object-contain"
                            onError={(e) => { e.currentTarget.src = '/logoCasco.webp'; }}
                        />
                    </div>
                </div>

                <div className="space-y-3">
                    <div className="flex items-start gap-2 text-sm text-muted-foreground">
                        <HardHat className="w-4 h-4 mt-0.5 text-muted-foreground" />
                        <span className="line-clamp-2">{site.onsite_engineers || 'Sin ingenieros asignados'}</span>
                    </div>
                </div>

                <div className="mt-6 flex gap-2">
                    <button
                        onClick={() => navigate(`/site/${site.id}`)}
                        className="flex-1 bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Ver Detalles
                    </button>
                    <button className="flex-1 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 py-2 rounded-lg text-sm font-medium transition-colors">
                        Servidores
                    </button>
                </div>
            </div>
        </div>
    );
};

const SitesList = () => {
    const { data: sites, isLoading, isError } = useSites();

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-20">
                <Loader2 className="w-10 h-10 text-blue-500 animate-spin" />
            </div>
        );
    }

    if (isError) {
        return (
            <div className="bg-red-500/10 border border-red-500/20 rounded-xl p-8 text-center">
                <p className="text-red-400 font-medium">Error al cargar las faenas. Reintentando...</p>
            </div>
        );
    }

    if (!sites || sites.length === 0) {
        return (
            <div className="text-center py-20 text-gray-500">
                <Server className="w-12 h-12 mx-auto mb-4 opacity-20" />
                <p>No se encontraron faenas configuradas.</p>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {sites.map((site) => (
                <SiteCard key={site.id} site={site} />
            ))}
        </div>
    );
};

export default SitesList;
