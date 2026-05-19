import React, { useState, useEffect, useRef } from 'react';
import {
    Bell,
    Cloud,
    ExternalLink,
    X,
    Trash2
} from 'lucide-react';
import { api } from '../services/authService';
import { cn } from '../lib/utils';

export interface SystemNotification {
    id: string;
    case_id: string;
    case_number: string;
    title: string;
    message: string;
    subject: string;
    status: string;
    faena: string;
    created_at: string;
    type: string;
}

export const SystemNotificationsBell: React.FC = () => {
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<SystemNotification[]>([]);
    const [dismissedIds, setDismissedIds] = useState<string[]>([]);
    const wrapperRef = useRef<HTMLDivElement>(null);

    // Load dismissed notifications from localStorage on mount
    useEffect(() => {
        const stored = localStorage.getItem('dismissed_notifications');
        if (stored) {
            try {
                setDismissedIds(JSON.parse(stored));
            } catch (e) {
                console.error(e);
            }
        }
    }, []);

    const fetchNotifications = async () => {
        if (!localStorage.getItem('user')) return; // Defensive check
        try {
            const response = await api.get('/notifications.php');
            setNotifications(response.data || []);
        } catch (err) {
            console.error('Error fetching notifications:', err);
        }
    };

    useEffect(() => {
        fetchNotifications();
        const interval = setInterval(fetchNotifications, 10000); // Check every 10 seconds
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
        if (!open) fetchNotifications();
        setOpen(!open);
    };

    // Filter notifications that haven't been dismissed yet
    const activeNotifications = notifications.filter(n => !dismissedIds.includes(n.id));

    const dismissNotification = (id: string) => {
        const updated = [...dismissedIds, id];
        setDismissedIds(updated);
        localStorage.setItem('dismissed_notifications', JSON.stringify(updated));
    };

    const clearAllNotifications = () => {
        const allIds = notifications.map(n => n.id);
        const updated = Array.from(new Set([...dismissedIds, ...allIds]));
        setDismissedIds(updated);
        localStorage.setItem('dismissed_notifications', JSON.stringify(updated));
    };

    return (
        <div className="relative" ref={wrapperRef}>
            <button
                onClick={toggle}
                className="relative p-2 hover:bg-accent hover:text-accent-foreground rounded-full transition-colors text-muted-foreground"
                title="Notificaciones de Sistema"
            >
                <Bell className={cn("w-5 h-5", activeNotifications.length > 0 ? "text-rose-500 animate-bounce" : "text-muted-foreground")} />
                {activeNotifications.length > 0 && (
                    <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white ring-2 ring-white dark:ring-slate-900 animate-in fade-in zoom-in-50 duration-300">
                        {activeNotifications.length}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 mt-2 w-96 bg-popover text-popover-foreground rounded-xl shadow-lg border border-border z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div className="p-4 border-b border-border bg-muted/30 flex justify-between items-center">
                        <h3 className="font-semibold text-sm">Notificaciones de Sistema</h3>
                        {activeNotifications.length > 0 && (
                            <button
                                onClick={clearAllNotifications}
                                className="text-xs text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1 transition-colors"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                                Descartar todas
                            </button>
                        )}
                    </div>
                    <div className="max-h-[400px] overflow-y-auto">
                        {activeNotifications.length === 0 ? (
                            <div className="p-8 text-center text-muted-foreground text-sm">
                                <Bell className="w-8 h-8 mx-auto mb-2 opacity-20" />
                                Sin notificaciones pendientes.
                            </div>
                        ) : (
                            <div className="divide-y divide-border">
                                {activeNotifications.map(notification => (
                                    <div key={notification.id} className="p-4 bg-rose-500/5 dark:bg-rose-950/10 hover:bg-muted/50 transition-colors flex gap-3 relative group">
                                        <div className="mt-1 shrink-0">
                                            <div className="p-1.5 bg-rose-500/10 text-rose-500 rounded-lg">
                                                <Cloud className="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-2 mb-1">
                                                <h4 className="text-xs font-black text-rose-700 dark:text-rose-400 uppercase tracking-wider">{notification.title}</h4>
                                                <span className="text-[9px] text-muted-foreground">{new Date(notification.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                            </div>
                                            <p className="text-xs text-slate-700 dark:text-slate-300 font-bold mb-1 line-clamp-2">
                                                {notification.message}
                                            </p>
                                            <p className="text-[11px] text-slate-500 italic mb-2 line-clamp-1">
                                                "{notification.subject}"
                                            </p>
                                            <div className="flex justify-between items-center text-[10px] text-muted-foreground">
                                                <a
                                                    href={`https://usa1.lightning.force.com/lightning/r/Case/${notification.case_id}/view`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-500/15 text-rose-600 dark:text-rose-400 font-black rounded border border-rose-500/25 hover:bg-rose-500 hover:text-white transition-all uppercase text-[9px]"
                                                >
                                                    Salesforce <ExternalLink className="w-2.5 h-2.5" />
                                                </a>
                                                <button
                                                    onClick={() => dismissNotification(notification.id)}
                                                    className="text-muted-foreground hover:text-foreground font-semibold flex items-center gap-0.5"
                                                    title="Descartar notificación"
                                                >
                                                    <X className="w-3.5 h-3.5" /> Descartar
                                                </button>
                                            </div>
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
