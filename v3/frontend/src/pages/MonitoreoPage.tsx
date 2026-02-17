import { useQuery } from '@tanstack/react-query';
import { getSites } from '../services/sitesService';
import { useNavigate } from 'react-router-dom';
import { Loader2, Activity, Database, FileInput } from 'lucide-react';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Status helper based on Alert Presence
const getStatusColor = (alert: string | null | undefined) => {
    if (alert) {
        return 'text-red-500 bg-red-500/10 border-red-200 animate-pulse';
    }
    return 'text-emerald-500 bg-emerald-500/10 border-emerald-200';
};

const getStatusTitle = (alert: string | null | undefined, category: string) => {
    if (alert) return `${category} ERROR: ${alert}`;
    return `${category}: OK`;
};

const MonitoreoPage = () => {
    const navigate = useNavigate();
    const { data: sites, isLoading } = useQuery({
        queryKey: ['sites'],
        queryFn: getSites,
        refetchInterval: 30000
    });

    // Filter by is_visible (fallback to status if undefined)
    const activeSites = sites?.filter(s => (s.is_visible !== undefined && s.is_visible !== null ? Number(s.is_visible) : Number(s.status)) === 1) || [];

    if (isLoading) {
        return (
            <div className="flex items-center justify-center h-full">
                <Loader2 className="w-8 h-8 animate-spin text-primary" />
            </div>
        );
    }

    return (
        <div className="p-6 md:p-8 max-w-7xl mx-auto space-y-8 animate-in fade-in duration-500">
            <div>
                <h1 className="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    Monitoreo Remoto
                </h1>
                <p className="text-muted-foreground mt-2">
                    Estado general de servicios por faena.
                </p>
            </div>

            <div className="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50 border-b border-border">
                            <tr>
                                <th className="px-6 py-4 font-semibold text-foreground">Faena</th>
                                <th className="px-6 py-4 font-semibold text-center text-foreground w-32">Salud Servidor</th>
                                <th className="px-6 py-4 font-semibold text-center text-foreground w-32">Procesos</th>
                                <th className="px-6 py-4 font-semibold text-center text-foreground w-32">Base de Datos</th>
                                <th className="px-6 py-4 font-semibold text-center text-foreground w-32">Importadores</th>
                                <th className="px-6 py-4 font-semibold text-right text-foreground">Acción</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {activeSites.map((site) => {
                                const hasOfflineServers = (site.online_servers || 0) < (site.total_servers || 0);

                                return (
                                    <tr
                                        key={site.id}
                                        className={cn(
                                            "transition-all duration-300 group hover:shadow-md border-l-4",
                                            hasOfflineServers
                                                ? "bg-red-50/50 hover:bg-red-50 border-l-red-500"
                                                : "hover:bg-muted/30 border-l-transparent"
                                        )}
                                    >
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-4">
                                                <div className={cn(
                                                    "w-12 h-12 rounded-lg flex items-center justify-center font-bold text-sm border shrink-0 transition-all shadow-sm",
                                                    hasOfflineServers
                                                        ? "bg-red-100 text-red-600 border-red-200"
                                                        : "bg-primary/10 text-primary border-primary/20"
                                                )}>
                                                    {site.alias}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <div className={cn("font-bold text-base transition-colors", hasOfflineServers ? "text-red-700" : "text-foreground")}>
                                                            {site.name}
                                                        </div>
                                                        {hasOfflineServers && (
                                                            <span className="flex h-2 w-2 relative">
                                                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                                <span className="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground flex items-center gap-2">
                                                        {site.conglomerate}
                                                        {hasOfflineServers && (
                                                            <span className="text-[10px] font-bold bg-red-100 text-red-600 px-1.5 py-0.5 rounded border border-red-200">
                                                                OFFLINE
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {/* Metric Families - Alert Driven */}
                                        <td className="px-6 py-4 text-center">
                                            <div className={cn("inline-flex p-2 rounded-lg border transition-colors", getStatusColor(site.health_alert))} title={getStatusTitle(site.health_alert, 'Salud')}>
                                                <Activity className="w-5 h-5" />
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <div className={cn("inline-flex p-2 rounded-lg border transition-colors", getStatusColor(site.processes_alert))} title={getStatusTitle(site.processes_alert, 'Procesos')}>
                                                <Activity className="w-5 h-5" />
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <div className={cn("inline-flex p-2 rounded-lg border transition-colors", getStatusColor(site.db_alert))} title={getStatusTitle(site.db_alert, 'Base de Datos')}>
                                                <Database className="w-5 h-5" />
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-center">
                                            <div className={cn("inline-flex p-2 rounded-lg border transition-colors", getStatusColor(site.importadores_alert))} title={getStatusTitle(site.importadores_alert, 'Importadores')}>
                                                <FileInput className="w-5 h-5" />
                                            </div>
                                        </td>

                                        <td className="px-6 py-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {/* FMS Button */}
                                                <button
                                                    disabled={!site.has_fms}
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        navigate(`/monitoreo/site/${site.id}?type=fms`);
                                                    }}
                                                    className={cn(
                                                        "px-3 py-1.5 text-xs font-bold rounded-lg border transition-all flex items-center gap-1.5 shadow-sm",
                                                        site.has_fms
                                                            ? "bg-primary text-primary-foreground border-primary hover:bg-primary/90"
                                                            : "bg-muted text-muted-foreground border-border cursor-not-allowed opacity-50"
                                                    )}
                                                    title={!site.has_fms ? "No instalado" : (site.fms_status ? "FMS Online" : "FMS Offline")}
                                                >
                                                    {site.has_fms && (
                                                        <span className={cn("w-2 h-2 rounded-full animate-pulse", Number(site.fms_status) === 1 ? "bg-emerald-400" : "bg-red-400")} />
                                                    )}
                                                    FMS
                                                </button>

                                                {/* CAS Button */}
                                                <button
                                                    disabled={!site.has_cas}
                                                    className={cn(
                                                        "px-3 py-1.5 text-xs font-bold rounded-lg border transition-all shadow-sm",
                                                        site.has_cas
                                                            ? "bg-card text-foreground border-input hover:bg-accent hover:text-accent-foreground"
                                                            : "bg-muted text-muted-foreground border-border cursor-not-allowed opacity-50"
                                                    )}
                                                >
                                                    CAS
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {activeSites.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="px-6 py-12 text-center text-muted-foreground">
                                        No hay faenas activas configuradas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default MonitoreoPage;
