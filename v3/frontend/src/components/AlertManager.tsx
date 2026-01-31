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
                    if (!currentSiteId || currentSiteId === 'undefined') return true;
                    return String(alert.site_id) === String(currentSiteId);
                });

                setAlerts(filteredData);
                if (filteredData.length > 0) {
                    // Normalize creation date for display comparison if needed
                    setCurrentAlert(filteredData[0]);
                } else {
                    setCurrentAlert(null);
                    // Cancel speech if no alerts
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

    const handleAcknowledge = async () => {
        if (!currentAlert) return;
        try {
            await acknowledgeAlert(currentAlert.id);
            if (synthRef.current) synthRef.current.cancel();

            // Remove locally to instant update UI
            const remaining = alerts.filter(a => a.id !== currentAlert.id);
            setAlerts(remaining);
            setCurrentAlert(remaining.length > 0 ? remaining[0] : null);
        } catch (error) {
            console.error("Failed to acknowledge", error);
        }
    };

    if (!currentAlert || isSafetyDelayActive) return <DebugOverlay alerts={alerts} error={errorMsg} user={auth?.user} isDelayed={isSafetyDelayActive} />;

    // Simplified styles to guarantee visibility (removed animate-in dependencies)
    return (
        <>
            <DebugOverlay alerts={alerts} error={errorMsg} user={auth?.user} isDelayed={isSafetyDelayActive} />
            <div style={{ zIndex: 99999, position: 'fixed', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(50, 0, 0, 0.95)' }} className="animate-in fade-in duration-500">
                <div className="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4 relative overflow-hidden border-4 border-red-600 animate-in zoom-in slide-in-from-bottom-5 duration-300">
                    {/* Pulse Effect Background */}
                    <div className="absolute top-0 left-0 w-full h-2 bg-red-600 animate-pulse"></div>

                    <div className="flex flex-col items-center text-center">
                        <div className="bg-red-100 p-4 rounded-full mb-6 ring-8 ring-red-500/20 animate-pulse">
                            {getAlertIcon(currentAlert.metric_key)}
                        </div>

                        <h2 className="text-3xl font-extrabold text-red-700 mb-2 uppercase tracking-wide">{currentAlert.title}</h2>

                        {/* Descriptive Badge for Role specificity if needed in title, but usually already in title */}
                        <div className="flex gap-2 mb-4">
                            <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold uppercase ring-1 ring-red-200 shadow-sm">
                                {currentAlert.server_name}
                            </span>
                        </div>

                        <p className="text-xl text-gray-800 font-medium mb-8">
                            {currentAlert.description.split('] ').pop()}
                        </p>

                        <div className="bg-gray-50 rounded-lg p-4 w-full mb-8 text-left border border-gray-300 shadow-inner">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span className="block text-gray-400 uppercase text-[10px] font-bold">Servidor</span>
                                    <span className="font-mono font-bold text-gray-900 text-base">{currentAlert.server_name}</span>
                                </div>
                                <div>
                                    <span className="block text-gray-400 uppercase text-[10px] font-bold">IP / Métrica</span>
                                    <div className="flex flex-col">
                                        <span className="font-mono font-bold text-blue-600 text-sm">{currentAlert.description.match(/\[IP: (.*?)\]/)?.[1] || 'N/A'}</span>
                                        <span className="font-mono font-bold text-gray-500 text-xs">{currentAlert.metric_key}</span>
                                    </div>
                                </div>
                                <div className="col-span-2">
                                    <span className="block text-gray-400 uppercase text-[10px] font-bold">Instante de Detección</span>
                                    <span className="font-mono font-bold text-gray-900">{currentAlert.created_at}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            onClick={handleAcknowledge}
                            className="w-full bg-red-600 hover:bg-red-700 text-white font-extrabold py-5 px-8 rounded-lg shadow-xl transform transition hover:scale-105 flex items-center justify-center gap-3 text-xl"
                        >
                            <CheckCircle className="w-8 h-8" />
                            MARCAR EN REVISIÓN
                        </button>

                        <p className="mt-6 text-sm text-gray-300">
                            * El audio puede requerir interacción con la página para iniciarse.
                        </p>
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
