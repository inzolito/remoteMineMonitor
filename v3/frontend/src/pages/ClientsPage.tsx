import { useStaticSites } from '../hooks/useStaticSites';
import { Loader2, Search, Mountain, Truck, Shovel, Factory, HardHat, Warehouse, ChevronRight } from 'lucide-react';
import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';

// Helper for dynamic UI icons (no helmet)
const getSiteIcon = (name: string) => {
    const hash = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const icons = [Mountain, Truck, Shovel, Factory, HardHat, Warehouse];
    return icons[hash % icons.length];
};

const ClientsPage = () => {
    const navigate = useNavigate();
    const { data: sites, isLoading, isError } = useStaticSites();
    const [searchTerm, setSearchTerm] = useState('');

    // Grouping & Filtering Logic with Custom Sorting
    const { groupedSites, sortedGroups } = useMemo(() => {
        if (!sites) return { groupedSites: {}, sortedGroups: [] };

        // Filter out inactive sites AND check search term
        const filtered = sites.filter(site => {
            // Check visibility (fallback to status if strict type check fails, but API provides is_visible now)
            const isVisible = (site.is_visible !== undefined && site.is_visible !== null ? Number(site.is_visible) : Number(site.status)) === 1;
            if (!isVisible) return false;

            const searchMatch =
                (site.name || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (site.conglomerate || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
                (site.alias || '').toLowerCase().includes(searchTerm.toLowerCase());

            return searchMatch;
        });

        const groups = filtered.reduce((acc, site) => {
            const key = site.conglomerate || 'Operaciones Independientes';
            if (!acc[key]) acc[key] = [];
            acc[key].push(site);
            return acc;
        }, {} as Record<string, typeof sites>);

        // Custom Sort Order: AMSA and CODELCO first, then alphabetical
        const keys = Object.keys(groups).sort((a, b) => {
            const priority = ['AMSA', 'CODELCO'];
            const indexA = priority.indexOf(a);
            const indexB = priority.indexOf(b);

            if (indexA !== -1 && indexB !== -1) return indexA - indexB;
            if (indexA !== -1) return -1;
            if (indexB !== -1) return 1;

            return a.localeCompare(b);
        });

        return { groupedSites: groups, sortedGroups: keys };
    }, [sites, searchTerm]);

    if (isLoading) {
        return (
            <div className="flex flex-col items-center justify-center min-h-[400px] gap-3">
                <Loader2 className="w-8 h-8 text-primary animate-spin" />
                <p className="text-sm text-muted-foreground font-medium">Sincronizando con base de datos...</p>
            </div>
        );
    }

    if (isError) {
        return (
            <div className="p-10 text-center bg-card rounded-2xl border border-border">
                <p className="text-destructive font-bold">No se pudieron cargar las faenas.</p>
            </div>
        );
    }

    return (
        <div className="space-y-12 animate-in fade-in duration-500">
            {/* Minimalist Integrated Header */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 className="text-3xl font-black text-foreground tracking-tight">Clientes</h1>
                    <p className="text-muted-foreground text-sm font-medium">Lista de clientes</p>
                </div>

                <div className="relative group min-w-[300px]">
                    <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors" />
                    <input
                        type="text"
                        placeholder="Buscar faena o grupo..."
                        className="w-full pl-10 pr-4 py-2 rounded-xl bg-card border border-border focus:ring-4 focus:ring-primary/5 focus:border-primary outline-none text-sm text-foreground transition-all shadow-sm"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
            </div>

            {/* Grouped Sections */}
            <div className="space-y-10">
                {sortedGroups.map((group) => (
                    <div key={group} className="space-y-6">
                        <div className="flex items-center gap-3">
                            <div className={`h-6 w-1 ${group === 'AMSA' || group === 'CODELCO' ? 'bg-primary' : 'bg-muted-foreground/30'} rounded-full`}></div>
                            <h2 className="text-lg font-black text-foreground uppercase tracking-widest flex items-center gap-2">
                                {group}
                                <span className="text-[10px] bg-muted px-2 py-0.5 rounded-full text-muted-foreground font-bold">
                                    {groupedSites[group].length}
                                </span>
                            </h2>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                            {groupedSites[group].map((site) => {
                                const Icon = getSiteIcon(site.name);

                                return (
                                    <div
                                        key={site.id}
                                        className="bg-card rounded-2xl border border-border hover:border-primary/40 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group flex flex-col p-4 relative overflow-hidden"
                                    >
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="p-2 bg-primary/5 text-primary rounded-lg group-hover:bg-primary group-hover:text-white transition-colors">
                                                <Icon className="w-4 h-4" />
                                            </div>
                                            <span className="text-[9px] font-black text-muted-foreground/60 uppercase tracking-tighter">
                                                {site.alias}
                                            </span>
                                        </div>

                                        <div className="flex-1">
                                            <h3 className="font-extrabold text-foreground text-base mb-1 truncate" title={site.name}>
                                                {site.name}
                                            </h3>

                                            <div className="mb-4 space-y-3">
                                                <div className="pt-3 border-t border-border/50">
                                                    <span className="text-[9px] font-bold text-muted-foreground uppercase block mb-0.5">Admin Contrato</span>
                                                    <p className="text-xs font-bold text-foreground/80 truncate">
                                                        {site.contract_admin_users || site.contract_manager || 'No asignado'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <button
                                            onClick={() => navigate(`/site/${site.id}`)}
                                            className="mt-auto w-full py-2 bg-primary/5 text-primary rounded-xl font-black text-xs hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2"
                                        >
                                            VER DETALLES
                                            <ChevronRight className="w-3 h-3" />
                                        </button>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}

                {sortedGroups.length === 0 && (
                    <div className="py-20 text-center text-muted-foreground">
                        No se encontraron faenas que coincidan con la búsqueda.
                    </div>
                )}
            </div>
        </div>
    );
};

export default ClientsPage;
