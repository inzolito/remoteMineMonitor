import React, { useEffect, useState, useRef, useContext } from 'react';
import { AuthContext } from '../context/AuthContext';
import {
    AlertCircle,
    CheckCircle,
    HardDrive,
    Database,
    Wifi,
    Cpu,
    Layers,
    Clock,
    Activity
} from 'lucide-react';
import { getAlerts, acknowledgeAlert, type Alert } from '../services/alertsService';
import { cn } from '../lib/utils';

const getAlertIcon = (key: string) => {
    if (key.includes('disk')) return <HardDrive className="w-20 h-20 text-red-600 animate-pulse" />;
    if (key.includes('backup.daily')) return <Database className="w-20 h-20 text-blue-600 animate-pulse" />;
    if (key.includes('backup.hourly')) return <Clock className="w-20 h-20 text-indigo-600 animate-pulse" />;
    if (key.includes('connectivity')) return <Wifi className="w-20 h-20 text-red-600 animate-pulse" />;
    if (key.includes('cpu') || key.includes('load')) return <Cpu className="w-20 h-20 text-orange-600 animate-pulse" />;
    if (key.includes('ram')) return <Layers className="w-20 h-20 text-purple-600 animate-pulse" />;
    if (key.includes('ntp')) return <Activity className="w-20 h-20 text-amber-600 animate-pulse" />;
    if (key.includes('idleQuery')) return <Activity className="w-20 h-20 text-red-600 animate-pulse" />;
    if (key.includes('db.diff')) return <Layers className="w-20 h-20 text-red-700 animate-bounce" />;
    if (key.includes('backup.schema')) return <Database className="w-20 h-20 text-purple-600 animate-pulse" />;
    if (key.includes('replica')) return <Database className="w-20 h-20 text-red-600 animate-pulse" />;
    return <AlertCircle className="w-20 h-20 text-red-600 animate-bounce" />;
};

const AlertManager: React.FC<{ initialDataLoaded?: boolean }> = ({ initialDataLoaded }) => {
    const auth = useContext(AuthContext);
    const [alerts, setAlerts] = useState<Alert[]>([]);
    const [currentAlert, setCurrentAlert] = useState<Alert | null>(null);
    const [hasInteracted, setHasInteracted] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [voices, setVoices] = useState<SpeechSynthesisVoice[]>([]);

    // Safety delay rules
    const [isSafetyDelayActive, setIsSafetyDelayActive] = useState(true);
    const dataLoadedTimeRef = useRef<number | null>(null);

    // Initial 30s safety timer
    useEffect(() => {
        const timer = setTimeout(() => {
            setIsSafetyDelayActive(false);
        }, 30000);
        return () => clearTimeout(timer);
    }, []);

    // 5s delay after first data load
    useEffect(() => {
        if (initialDataLoaded && dataLoadedTimeRef.current === null) {
            dataLoadedTimeRef.current = Date.now();
            const timer = setTimeout(() => {
                setIsSafetyDelayActive(false);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [initialDataLoaded]);

    const synthRef = useRef<SpeechSynthesis | null>(window.speechSynthesis);

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

    // Poll for alerts
    useEffect(() => {
        const fetchAlerts = async () => {
            try {
                const data: Alert[] = await getAlerts('active');

                // [NEW] Context Filter: Only show alerts for the current site in the URL
                const pathParts = window.location.pathname.split('/');
                const siteIdx = pathParts.indexOf('site');
                const currentSiteId = siteIdx > -1 ? pathParts[siteIdx + 1] : null;

                const filteredData = data.filter(alert => {
                    if (ignoredAlertsRef.current.has(alert.id)) return false;

                    if (!currentSiteId || currentSiteId === 'undefined') return true;
                    return String(alert.site_id) === String(currentSiteId);
                });

                // Cleanup ignored set: Remove IDs that are no longer in the backend response
                // [MODIFIED] Logic disabled to prevent "Ghosting" alerts (re-opening if API flickers)
                /* 
                const backendIds = new Set(data.map(a => a.id));
                for (const id of ignoredAlertsRef.current) {
                    if (!backendIds.has(id)) {
                        ignoredAlertsRef.current.delete(id);
                    }
                }
                */

                setAlerts(filteredData);
                if (filteredData.length > 0) {
                    // Update only if different to avoid re-triggering effects excessively
                    if (!currentAlert || currentAlert.id !== filteredData[0].id) {
                        setCurrentAlert(filteredData[0]);
                    }
                } else {
                    setCurrentAlert(null);
                    if (synthRef.current) synthRef.current.cancel();
                }
            } catch (error: any) {
                console.error("Failed to fetch alerts", error);
                setErrorMsg(error.message as string);
            }
        };

        fetchAlerts();
        const interval = setInterval(fetchAlerts, 3000); // Poll every 3s instead of 5s
        return () => clearInterval(interval);
    }, []);

    // Debugging render
    useEffect(() => {
        console.log("AlertManager Rendered. Active Alerts:", alerts.length, "Current:", currentAlert);
    }, [alerts, currentAlert]);

    // Handle TTS
    useEffect(() => {
        if (currentAlert && hasInteracted && synthRef.current && voices.length > 0) {
            if (synthRef.current.speaking) return;

            // Strip IP context from text for cleaner TTS
            const cleanTitle = currentAlert.title;
            const cleanDesc = currentAlert.description.replace(/\[IP: (.*?)\]/g, '').trim();
            const utterance = new SpeechSynthesisUtterance(`${cleanTitle}. ${cleanDesc}`);

            // Look for Spanish voices, prioritizing "Female", "Neural" and high quality ones
            const esVoices = voices.filter(v => v.lang.startsWith('es-'));

            // Search for specific female voices common in modern browsers (Google, Microsoft, etc)
            const femaleKeywords = ['female', 'femenino', 'helena', 'lucia', 'elena', 'paulina', 'sabina', 'laura', 'monica', 'hilda', 'zira', 'mónica'];
            const bestVoice = esVoices.find(v => {
                const name = v.name.toLowerCase();
                return name.includes('neural') && femaleKeywords.some(kw => name.includes(kw));
            }) || esVoices.find(v => femaleKeywords.some(kw => v.name.toLowerCase().includes(kw)))
                || esVoices.find(v => v.name.toLowerCase().includes('neural'))
                || esVoices.find(v => (v.name.toLowerCase().includes('google') || v.name.toLowerCase().includes('microsoft')) && femaleKeywords.some(kw => v.name.toLowerCase().includes(kw)))
                || esVoices[0];

            if (bestVoice) {
                utterance.voice = bestVoice;
                utterance.lang = bestVoice.lang;
                console.log("Using Voice:", bestVoice.name);
            } else {
                utterance.lang = 'es-ES';
            }

            utterance.rate = 0.85;
            utterance.pitch = 1.05; // Slightly higher pitch for female feel if neutral
            utterance.volume = 1.0;

            synthRef.current.speak(utterance);
        }
    }, [currentAlert, hasInteracted, voices]);

    const [isExiting, setIsExiting] = useState(false);
    const ignoredAlertsRef = useRef<Set<number>>(new Set());

    const handleAcknowledge = async () => {
        if (!currentAlert) return;
        setIsExiting(true);

        // Optimistically ignore this alert immediately
        ignoredAlertsRef.current.add(currentAlert.id);

        // Wait for exit animation
        await new Promise(resolve => setTimeout(resolve, 300));

        try {
            // Store local state update immediately to visually remove it
            const remaining = alerts.filter(a => a.id !== currentAlert.id);
            setAlerts(remaining);
            setCurrentAlert(remaining.length > 0 ? remaining[0] : null);

            await acknowledgeAlert(currentAlert.id);
            if (synthRef.current) synthRef.current.cancel();
        } catch (error) {
            console.error("Failed to acknowledge", error);
        } finally {
            setIsExiting(false);
        }
    };

    if (!currentAlert || isSafetyDelayActive) return <DebugOverlay alerts={alerts} error={errorMsg} user={auth?.user} isDelayed={isSafetyDelayActive} />;

    return (
        <>
            <DebugOverlay alerts={alerts} error={errorMsg} user={auth?.user} isDelayed={isSafetyDelayActive} />
            <div
                style={{ zIndex: 99999, position: 'fixed', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(20, 0, 0, 0.9)', backdropFilter: 'blur(12px)' }}
                className={cn("transition-all duration-500", isExiting ? "opacity-0" : "opacity-100")}
            >
                <div className={cn(
                    "bg-white rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.5)] p-10 max-w-2xl w-full mx-4 relative overflow-hidden border-b-8 border-red-600 transition-all duration-300 transform",
                    isExiting ? "scale-95 translate-y-8 opacity-0" : "scale-100 translate-y-0 opacity-100 animate-in zoom-in-95 duration-300"
                )}>
                    {/* Animated Danger Stripe */}
                    <div className="absolute top-0 left-0 w-full h-3 bg-red-600">
                        <div className="w-full h-full bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" />
                    </div>

                    <div className="flex flex-col items-center text-center">
                        <div className="bg-red-50 p-6 rounded-full mb-8 ring-12 ring-red-500/10 animate-pulse">
                            {getAlertIcon(currentAlert.metric_key)}
                        </div>

                        <span className="text-[10px] font-black uppercase tracking-[0.3em] text-red-500 mb-2">Estado Crítico Detectado</span>
                        <h2 className="text-4xl font-black text-slate-900 mb-6 uppercase tracking-tight leading-none">{currentAlert.title}</h2>

                        <div className="w-16 h-1 bg-slate-100 mb-8 rounded-full" />

                        <p className="text-xl text-slate-600 font-medium mb-10 leading-relaxed max-w-lg">
                            {currentAlert.description.split('] ').pop()}
                        </p>

                        <div className="bg-slate-50 rounded-xl p-6 w-full mb-10 text-left border border-slate-100 shadow-inner grid grid-cols-2 gap-6 relative overflow-hidden">
                            <div className="absolute top-0 right-0 p-4 opacity-5">
                                <Activity className="w-20 h-20 text-slate-900" />
                            </div>

                            <div className="relative z-10">
                                <span className="block text-slate-400 uppercase text-[9px] font-black tracking-widest mb-1">Servidor Originador</span>
                                <span className="font-bold text-slate-800 text-lg block truncate">{currentAlert.server_name}</span>
                                <span className="font-mono text-blue-600 text-xs font-bold">{currentAlert.description.match(/\[IP: (.*?)\]/)?.[1] || 'N/A'}</span>
                            </div>

                            <div className="relative z-10">
                                <span className="block text-slate-400 uppercase text-[9px] font-black tracking-widest mb-1">Detección y Métrica</span>
                                <span className="font-bold text-slate-800 text-sm block">{new Date(currentAlert.created_at).toLocaleTimeString()}</span>
                                <span className="font-mono text-slate-400 text-[10px] lowercase">{currentAlert.metric_key}</span>
                            </div>
                        </div>

                        <button
                            onClick={handleAcknowledge}
                            className="group relative w-full bg-red-600 hover:bg-red-700 text-white font-black py-6 px-8 rounded-xl shadow-[0_10px_30px_rgba(220,38,38,0.3)] transform transition-all active:scale-[0.98] flex items-center justify-center gap-4 text-xl overflow-hidden"
                        >
                            <div className="absolute inset-0 bg-gradient-to-r from-red-500 to-red-700 opacity-0 group-hover:opacity-100 transition-opacity" />
                            <CheckCircle className="w-8 h-8 relative z-10" />
                            <span className="relative z-10 tracking-wide uppercase">MARCAR EN REVISIÓN</span>
                        </button>

                        <div className="mt-8 flex items-center gap-2 text-slate-400">
                            <Clock size={14} />
                            <p className="text-[10px] font-bold uppercase tracking-widest italic">
                                * Se requiere interacción para activar el sistema de voz
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
};

export default AlertManager;

// Debug Overlay Component (Internal)
const DebugOverlay = ({ alerts, error, user, isDelayed }: { alerts: Alert[], error: string | null, user: any, isDelayed: boolean }) => {
    const voices = window.speechSynthesis.getVoices();
    const esVoices = voices.filter(v => v.lang.startsWith('es-'));
    const femaleKeywords = ['female', 'femenino', 'helena', 'lucia', 'elena', 'paulina', 'sabina', 'laura', 'monica', 'hilda', 'zira', 'mónica'];
    const activeVoice = esVoices.find(v => {
        const name = v.name.toLowerCase();
        return name.includes('neural') && femaleKeywords.some(kw => name.includes(kw));
    }) || esVoices.find(v => femaleKeywords.some(kw => v.name.toLowerCase().includes(kw))) || esVoices[0];

    return (
        <div style={{
            position: 'fixed', bottom: 10, left: 10, backgroundColor: 'rgba(0,0,0,0.85)', color: '#0f0',
            padding: '10px', fontSize: 11, zIndex: 999999, pointerEvents: 'none', border: '1px solid #0f0',
            fontFamily: 'monospace', borderRadius: '4px'
        }}>
            <p style={{ fontWeight: 'bold', borderBottom: '1px solid #0f0', marginBottom: '5px', paddingBottom: '2px' }}>SYSTEM DEBUG (V3.2)</p>
            <p>SESIÓN: <span style={{ color: '#fff' }}>{user ? `${user.firstname} ${user.lastname} (@${user.username})` : 'Invitado'}</span></p>
            <p>ROL: <span style={{ color: '#fff' }}>{user?.role || 'N/A'}</span></p>
            <p>ALERTAS ACTIVAS: <span style={{ color: alerts.length > 0 ? '#f00' : '#0f0' }}>{alerts.length}</span></p>
            <p>VOZ ACTIVA: <span style={{ color: '#0af' }}>{activeVoice?.name || 'Buscando...'} (Mujer)</span></p>
            <p>SAFETY DELAY: <span style={{ color: isDelayed ? '#f00' : '#0f0' }}>{isDelayed ? 'ACTIVO (Mudo)' : 'INACTIVO'}</span></p>
            <p>ÚLTIMO CHECK: {new Date().toLocaleTimeString()}</p>
            {error && <p style={{ color: '#f00' }}>ERROR: {error}</p>}
        </div>
    );
};
