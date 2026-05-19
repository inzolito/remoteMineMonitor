import React, { useState, useEffect, useRef } from 'react';
import {
    Check,
    AlertCircle,
    HardDrive,
    Database,
    Wifi,
    Cpu,
    Layers,
    Clock,
    Activity,
    AlertTriangle
} from 'lucide-react';
import { getAlerts, solveAlert, type Alert } from '../services/alertsService';
import { cn } from '../lib/utils';

const getAlertIcon = (key: string, className: string) => {
    if (key.includes('disk')) return <HardDrive className={className} />;
    if (key.includes('backup.daily')) return <Database className={className} />;
    if (key.includes('backup.hourly')) return <Clock className={className} />;
    if (key.includes('connectivity')) return <Wifi className={className} />;
    if (key.includes('cpu') || key.includes('load')) return <Cpu className={className} />;
    if (key.includes('ram')) return <Layers className={className} />;
    if (key.includes('ntp')) return <Activity className={className} />;
    if (key.includes('idleQuery')) return <Activity className={className} />;
    return <AlertCircle className={className} />;
};

export const MonitoringAlertsBell: React.FC = () => {
    const [open, setOpen] = useState(false);
    const [alerts, setAlerts] = useState<Alert[]>([]);
    const wrapperRef = useRef<HTMLDivElement>(null);

    const fetchHistory = async () => {
        if (!localStorage.getItem('user')) return; // Defensive check
        try {
            const data = await getAlerts('active_or_acknowledged');
            setAlerts(data);
        } catch (err) {
            console.error(err);
        }
    };

    useEffect(() => {
        fetchHistory();
        const interval = setInterval(fetchHistory, 5000); // Poll hardware alerts every 5 seconds
        return () => clearInterval(interval);
    }, []);

    // Outside click handler
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const toggle = () => {
        if (!open) fetchHistory();
        setOpen(!open);
    };

    return (
        <div className="relative" ref={wrapperRef}>
            <button
                onClick={toggle}
                className="relative p-2 hover:bg-accent hover:text-accent-foreground rounded-full transition-colors text-muted-foreground"
                title="Alertas de Monitoreo"
            >
                <Activity className="w-5 h-5 text-amber-500 dark:text-amber-400" />
                {alerts.length > 0 && (
                    <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900 animate-in fade-in zoom-in-50 duration-300">
                        {alerts.length}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-96 bg-popover text-popover-foreground rounded-xl shadow-lg border border-border z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div className="p-4 border-b border-border bg-muted/30 flex justify-between items-center">
                        <h3 className="font-semibold text-sm">Alertas de Monitoreo</h3>
                        <span className="text-xs text-muted-foreground">{alerts.length} activas</span>
                    </div>
                    <div className="max-h-[400px] overflow-y-auto">
                        {alerts.length === 0 ? (
                            <div className="p-8 text-center text-muted-foreground text-sm">
                                <AlertTriangle className="w-8 h-8 mx-auto mb-2 opacity-20 text-amber-500" />
                                No hay alertas de servidores activas.
                            </div>
                        ) : (
                            <div className="divide-y divide-border">
                                {alerts.map(alert => (
                                    <div key={alert.id} className={cn("p-4 hover:bg-muted/50 transition-colors flex gap-3", alert.status === 'active' ? 'bg-amber-500/5 dark:bg-amber-500/10' : '')}>
                                        <div className={cn("mt-1 shrink-0")}>
                                            {getAlertIcon(alert.metric_key, cn("w-4 h-4",
                                                alert.status === 'active' ? 'text-amber-500 animate-pulse' :
                                                    (alert.status === 'acknowledged' ? 'text-yellow-500' : 'text-green-500')
                                            ))}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <h4 className={cn("text-sm font-medium leading-none mb-1", alert.status === 'active' ? 'text-amber-600 dark:text-amber-400' : '')}>{alert.title}</h4>
                                            <p className="text-xs text-muted-foreground line-clamp-2 mb-2">{alert.description.split('] ').pop()}</p>
                                            <div className="flex justify-between items-center text-[10px] text-muted-foreground mr-2">
                                                <div className="flex gap-1 items-center">
                                                    <span className="font-mono bg-muted px-1 rounded text-blue-600 font-bold dark:text-blue-400">{alert.description.match(/\[IP: (.*?)\]/)?.[1] || 'N/A'}</span>
                                                    <span className="font-mono bg-muted px-1 rounded">{alert.server_name}</span>
                                                </div>
                                                <div className="flex gap-2 items-center">
                                                    <span>{new Date(alert.created_at).toLocaleTimeString()}</span>
                                                    {alert.status !== 'solved' && (
                                                        <button
                                                            onClick={async (e) => {
                                                                 e.stopPropagation();
                                                                 await solveAlert(alert.id);
                                                                 fetchHistory();
                                                            }}
                                                            className="text-primary hover:underline font-bold"
                                                        >
                                                            [RESOLVER]
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            {alert.status === 'acknowledged' && (
                                                <div className="mt-2 text-[10px] flex items-center gap-1 text-yellow-600/80">
                                                    <Check className="w-3 h-3" /> Revisado por {alert.user_name ? alert.user_name.split(' ')[0] : 'Sistema'}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};
