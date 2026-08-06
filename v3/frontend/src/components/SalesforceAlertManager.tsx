import { useEffect, useState, useRef, useContext } from 'react';
import { AuthContext } from '../context/AuthContext';
import { Cloud, ExternalLink, X } from 'lucide-react';
import { api } from '../services/authService';
import { cn } from '../lib/utils';
import type { SystemNotification } from './SystemNotificationsBell';

const SalesforceAlertManager = () => {
    const auth = useContext(AuthContext);
    const [alerts, setAlerts] = useState<SystemNotification[]>([]);
    const [currentAlert, setCurrentAlert] = useState<SystemNotification | null>(null);
    const [hasInteracted, setHasInteracted] = useState(false);
    const [voices, setVoices] = useState<SpeechSynthesisVoice[]>([]);
    const [isExiting, setIsExiting] = useState(false);

    const synthRef = useRef<SpeechSynthesis | null>(window.speechSynthesis);

    const isAuthorizedUser = !!auth?.user; // Visible to all logged-in users

    useEffect(() => {
        const loadVoices = () => {
            if (synthRef.current) {
                setVoices(synthRef.current.getVoices());
            }
        };
        loadVoices();
        if (synthRef.current) {
            synthRef.current.onvoiceschanged = loadVoices;
        }
    }, []);

    useEffect(() => {
        const handleInteraction = () => setHasInteracted(true);
        window.addEventListener('click', handleInteraction);
        window.addEventListener('keydown', handleInteraction);
        return () => {
            window.removeEventListener('click', handleInteraction);
            window.removeEventListener('keydown', handleInteraction);
        };
    }, []);

    useEffect(() => {
        if (!isAuthorizedUser) return;

        const fetchAlerts = async () => {
            if (!auth?.user) return;
            try {
                const response = await api.get('/notifications.php');
                const allNotifications: SystemNotification[] = response.data || [];

                let dismissedIds: string[] = [];
                try {
                    const stored = localStorage.getItem('dismissed_sf_alerts');
                    if (stored) dismissedIds = JSON.parse(stored);
                } catch (_) {}

                const filtered = allNotifications.filter(
                    (n) => n.type === 'salesforce_ticket' && !dismissedIds.includes(n.id)
                );

                setAlerts(filtered);
                if (filtered.length > 0) {
                    setCurrentAlert((prev) =>
                        prev && filtered.find((f) => f.id === prev.id) ? prev : filtered[0]
                    );
                } else {
                    setCurrentAlert(null);
                    synthRef.current?.cancel();
                }
            } catch (err) {
                console.error('SalesforceAlertManager fetch error:', err);
            }
        };

        fetchAlerts();
        const interval = setInterval(fetchAlerts, 5000);
        return () => clearInterval(interval);
    }, [isAuthorizedUser, auth?.user]);

    useEffect(() => {
        if (!currentAlert || !hasInteracted || !synthRef.current || voices.length === 0) return;
        if (synthRef.current.speaking) return;

        const text = `Atención Maik. Nuevo caso en South American Support. Ticket ${currentAlert.case_number} de la faena ${currentAlert.faena}. ${currentAlert.subject}`;
        const utterance = new SpeechSynthesisUtterance(text);

        const esVoices = voices.filter((v) => v.lang.startsWith('es-'));
        const femaleKw = ['female','femenino','helena','lucia','elena','paulina','laura','monica','zira'];
        const best =
            esVoices.find((v) => femaleKw.some((k) => v.name.toLowerCase().includes(k))) ||
            esVoices[0];
        if (best) { utterance.voice = best; utterance.lang = best.lang; } else { utterance.lang = 'es-ES'; }
        utterance.rate = 0.85;
        utterance.pitch = 1.05;
        utterance.volume = 1.0;
        synthRef.current.speak(utterance);
    }, [currentAlert, hasInteracted, voices]);

    const handleAcknowledge = () => {
        if (!currentAlert) return;
        setIsExiting(true);
        setTimeout(() => {
            try {
                let ids: string[] = [];
                try { ids = JSON.parse(localStorage.getItem('dismissed_sf_alerts') || '[]'); } catch (_) {}
                localStorage.setItem('dismissed_sf_alerts', JSON.stringify([...ids, currentAlert.id]));
            } catch (_) {}
            setAlerts((prev) => prev.filter((a) => a.id !== currentAlert.id));
            setCurrentAlert(() => {
                const remaining = alerts.filter((a) => a.id !== currentAlert.id);
                return remaining.length > 0 ? remaining[0] : null;
            });
            synthRef.current?.cancel();
            setIsExiting(false);
        }, 300);
    };

    // All hooks above this line — only render for authorized users with active alerts
    if (!isAuthorizedUser || !currentAlert) return null;

    return (
        <div
            style={{
                zIndex: 99999, position: 'fixed', inset: 0,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                backgroundColor: 'rgba(0,15,30,0.9)', backdropFilter: 'blur(12px)'
            }}
            className={cn('transition-all duration-500', isExiting ? 'opacity-0' : 'opacity-100')}
        >
            <div className={cn(
                'bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,161,224,0.3)] p-10 max-w-2xl w-full mx-4 relative overflow-hidden border-b-8 border-[#00a1e0] transition-all duration-300 transform',
                isExiting ? 'scale-95 translate-y-8 opacity-0' : 'scale-100 translate-y-0 opacity-100'
            )}>
                <div className="absolute top-0 left-0 w-full h-3 bg-[#00a1e0] animate-pulse" />

                <div className="flex flex-col items-center text-center pt-4">
                    <div className="bg-[#00a1e0]/10 p-6 rounded-full mb-8 animate-pulse">
                        <Cloud className="w-20 h-20 text-[#00a1e0] animate-bounce" />
                    </div>

                    <span className="text-[10px] font-black uppercase tracking-[0.3em] text-[#00a1e0] mb-2">
                        Nuevo Ticket Asignado
                    </span>
                    <h2 className="text-4xl font-black text-slate-900 mb-6 uppercase tracking-tight leading-none">
                        {currentAlert.title}
                    </h2>
                    <div className="w-16 h-1 bg-slate-100 mb-8 rounded-full" />

                    <p className="text-xl text-slate-600 font-medium mb-4 leading-relaxed max-w-lg">
                        El caso <span className="font-bold text-slate-900">#{currentAlert.case_number}</span>{' '}
                        ({currentAlert.faena}) está esperando asignación en la cola principal.
                    </p>
                    <p className="text-md text-slate-500 italic mb-10 max-w-lg bg-slate-50 p-4 rounded-xl border border-slate-100">
                        "{currentAlert.subject}"
                    </p>

                    <div className="flex gap-4 w-full">
                        <a
                            href={`https://usa1.lightning.force.com/lightning/r/Case/${currentAlert.case_id}/view`}
                            target="_blank"
                            rel="noopener noreferrer"
                            onClick={handleAcknowledge}
                            className="flex-1 bg-[#00a1e0] hover:bg-[#008bc2] text-white font-bold py-4 px-8 rounded-xl shadow-lg shadow-[#00a1e0]/25 transition-all active:scale-95 flex items-center justify-center gap-2"
                        >
                            <Cloud className="w-5 h-5" />
                            Abrir en Salesforce <ExternalLink className="w-4 h-4 ml-1" />
                        </a>
                        <button
                            onClick={handleAcknowledge}
                            className="flex-1 bg-white hover:bg-slate-50 text-slate-600 font-bold py-4 px-8 rounded-xl border-2 border-slate-200 transition-all active:scale-95 flex items-center justify-center gap-2"
                        >
                            <X className="w-5 h-5" />
                            Descartar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SalesforceAlertManager;
