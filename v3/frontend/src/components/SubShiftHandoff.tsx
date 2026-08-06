import { useState, useRef } from 'react';
import html2canvas from 'html2canvas';
import { useQuery } from '@tanstack/react-query';
import {
    ArrowRightLeft, CheckCircle2, Clock,
    X, Send, Ticket, FileText
} from 'lucide-react';
import { cn } from '../lib/utils';
import { ConglomerateReports } from './ConglomerateReports';

interface HandoffTicket {
    case_id: string;
    case_number: string;
    status: string;
    status_at_handoff?: string;
    subject?: string;
    faena?: string;
    description?: string;
    comment_count?: number;
    comments?: Array<{ Body: string, CreatedDate: string, Author: string }>;
    created_date?: string;
}

interface HandoffSession {
    id: number;
    from_sub_shift: string;
    to_sub_shift: string;
    notes: string;
    status: 'pending' | 'accepted';
    created_at: string;
    first_name: string;
    last_name: string;
    ticket_count: number;
}

interface HandoffStatus {
    my_group: number;
    my_sub_shift: string;
    current_sub: string;
    next_sub: string;
    cycle_id: number;
    group_id: number;
    active_group: number;
    is_active_group: boolean;
    pending_session: HandoffSession | null;
    pending_tickets: HandoffTicket[];
    is_receiver: boolean;
}

const getToken = () => {
    try { return JSON.parse(localStorage.getItem('user') || '{}').token || ''; } catch { return ''; }
};

const fetchHandoffStatus = async (): Promise<HandoffStatus> => {
    const r = await fetch('/monitoreoLaboratorio/v3/api/sub_shift_handoff.php?action=pending', {
        headers: { Authorization: `Bearer ${getToken()}` }
    });
    return r.json();
};

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour >= 5 && hour < 12) {
        return 'Buenos días';
    } else if (hour >= 12 && hour < 20) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
};

interface SubShiftHandoffProps {
    /** Open tickets currently assigned to this engineer — passed from ShiftsPage */
    openTickets: HandoffTicket[];
    /** Whether the current user belongs to the active shift group */
    isActiveGroup: boolean;
    /** The current day of the 7-day cycle */
    cycleDay?: number;
    /** The list of active group members */
    activeMembers?: any[];
}

export const SubShiftHandoff: React.FC<SubShiftHandoffProps> = ({ openTickets, isActiveGroup, cycleDay, activeMembers = [] }) => {
    const [showModal, setShowModal] = useState(false);
    const [ticketNotes, setTicketNotes] = useState<Record<string, string>>({});
    const [isCapturing, setIsCapturing] = useState(false);
    const printRef = useRef<HTMLDivElement>(null);

    const [isCopied, setIsCopied] = useState(false);
    const [showPasteGuide, setShowPasteGuide] = useState(false);
    const [fallbackImage, setFallbackImage] = useState<string | null>(null);

    // Filter active (non-closed) tickets assigned to the user, no time limit
    const recentTickets = openTickets.filter(t => t.status?.toLowerCase() !== 'closed');

    const { data, isLoading } = useQuery<HandoffStatus>({
        queryKey: ['handoffStatus'],
        queryFn: fetchHandoffStatus,
        refetchInterval: 15000,
        retry: 1,
    });

    // Safe fallbacks for modal header display
    const currSub = data?.current_sub ?? 'Día';
    const nextSub = currSub === 'Día' ? 'Noche' : 'Día';

    // Find current and next engineer names
    const currentEngineer = activeMembers.find(m => m.turno_tipo === currSub || (!m.turno_tipo && currSub === 'Día')) || { first_name: 'Compañero', last_name: '' };
    const nextEngineer = activeMembers.find(m => m.turno_tipo === nextSub || (!m.turno_tipo && nextSub === 'Día')) || { first_name: 'Compañero', last_name: '' };

    const handleSendEmail = async () => {
        if (!printRef.current) {
            console.error("No se encontró el contenedor a copiar.");
            return;
        }

        try {
            // Activar modo captura para que el DOM oculte los textareas del formulario y el primer div
            setIsCapturing(true);
            
            // Dar tiempo a React para re-renderizar
            await new Promise(resolve => setTimeout(resolve, 150));

            // Generar la imagen desde el DOM
            const canvas = await html2canvas(printRef.current, {
                scale: 2, // Mejor resolución
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
                windowWidth: 1024
            });
            
            // Apagar modo captura inmediatamente
            setIsCapturing(false);

            // Reset fallback image state on new attempt
            setFallbackImage(null);

            canvas.toBlob(async (blob) => {
                if (!blob) {
                    console.error("Error al generar el blob de la imagen.");
                    return;
                }
                
                try {
                    // 1. Intentar API moderna de Portapapeles (Requiere HTTPS)
                    // Copiamos únicamente la imagen al portapapeles
                    const item = new ClipboardItem({
                        'image/png': blob
                    });
                    await navigator.clipboard.write([item]);
                    setIsCopied(true);
                    setTimeout(() => setIsCopied(false), 4000);
                    setShowPasteGuide(true);
                } catch (err) {
                    console.warn('Clipboard API moderno bloqueado (probablemente HTTP sin SSL). Intentando fallback con execCommand...', err);
                    
                    // 2. Fallback clásico de execCommand seleccionando un tag de imagen
                    try {
                        const img = document.createElement('img');
                        img.src = canvas.toDataURL('image/png');
                        img.style.position = 'fixed';
                        img.style.left = '-9999px';
                        img.style.userSelect = 'all';
                        document.body.appendChild(img);
                        
                        const range = document.createRange();
                        range.selectNode(img);
                        const sel = window.getSelection();
                        if (sel) {
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                        
                        const successful = document.execCommand('copy');
                        if (sel) {
                            sel.removeAllRanges();
                        }
                        document.body.removeChild(img);
                        
                        if (successful) {
                            setIsCopied(true);
                            setTimeout(() => setIsCopied(false), 4000);
                            setShowPasteGuide(true);
                        } else {
                            throw new Error("execCommand('copy') retornó falso");
                        }
                    } catch (fallbackErr) {
                        console.error('El fallback automático también falló:', fallbackErr);
                        
                        // 3. Si todo lo automático falla, habilitamos el copiado manual
                        const dataUrl = canvas.toDataURL('image/png');
                        setFallbackImage(dataUrl);
                        setShowPasteGuide(true);
                    }
                }
            }, 'image/png');

        } catch (err) {
            setIsCapturing(false);
            console.error('Error con html2canvas:', err);
            alert('Hubo un error al generar la imagen del traspaso.');
        }
    };

    const handleOpenOutlook = () => {
        const dateStr = new Date().toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        const currSubLocal = data?.current_sub ?? 'Día';
        const nextSubLocal = currSubLocal === 'Día' ? 'Noche' : 'Día';
        const subject = encodeURIComponent(`Traspaso de Sub-Turno: ${currSubLocal} -> ${nextSubLocal} - ${dateStr}`);
        
        const greeting = getGreeting();
        const timeStr = new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', hour12: false });
        const currentEngineerName = `${currentEngineer.first_name} ${currentEngineer.last_name}`.toUpperCase();
        const nextEngineerName = `${nextEngineer.first_name} ${nextEngineer.last_name}`.toUpperCase();
        
        // Escribimos el saludo y la introducción directamente en el cuerpo del correo
        const bodyText = encodeURIComponent(
            `${greeting},\n\n` +
            `Hoy siendo las ${timeStr} hrs del día ${dateStr}, día ${cycleDay ?? '?'} del turno actual, yo ${currentEngineerName} traspaso el turno a ${nextEngineerName} con los siguientes tickets pendientes:\n\n`
        );
        
        window.open(`mailto:maikol.salas@hexagon.com?subject=${subject}&body=${bodyText}`, '_blank');
        setShowPasteGuide(false);
    };

    // While loading, show a subtle skeleton button so layout doesn't jump
    if (isLoading) {
        return (
            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border/40 text-[10px] text-muted-foreground animate-pulse w-36 h-7 bg-muted/30" />
        );
    }

    // ── RECEIVER banner — this engineer should accept the handoff ──
    if (data?.is_receiver && data.pending_session) {
        const session = data.pending_session;
        return (
            <div className="w-full animate-in fade-in slide-in-from-top-2 duration-300">
                <div className="rounded-xl border-2 border-emerald-500/50 bg-emerald-500/5 dark:bg-emerald-950/20 p-4 flex flex-col gap-3">
                    <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-emerald-500/15 text-emerald-600">
                            <ArrowRightLeft className="w-4 h-4" />
                        </div>
                        <div>
                            <p className="text-xs font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">
                                Traspaso Pendiente
                            </p>
                            <p className="text-[10px] text-muted-foreground">
                                {session.first_name} {session.last_name} · {session.from_sub_shift} → {session.to_sub_shift}
                                <span className="ml-1.5 opacity-60">· {session.ticket_count} tickets</span>
                            </p>
                        </div>
                    </div>

                    {session.notes && (
                        <div className="rounded-lg bg-background/60 border border-border/50 p-3 text-[11px] text-foreground/80 italic">
                            <span className="font-black not-italic text-muted-foreground uppercase text-[9px] block mb-1">Nota del traspaso:</span>
                            "{session.notes}"
                        </div>
                    )}

                    {(data.pending_tickets ?? []).length > 0 && (
                        <div className="rounded-lg border border-border/50 overflow-hidden">
                            <div className="bg-muted/40 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-1">
                                <Ticket className="w-3 h-3" /> Tickets traspasados
                            </div>
                            <div className="divide-y divide-border/30">
                                {data.pending_tickets.map(t => (
                                    <div key={t.case_id} className="px-3 py-2 flex items-center gap-2 text-[10px]">
                                        <span className="font-black text-primary">{t.case_number}</span>
                                        <span className="text-muted-foreground/70 uppercase text-[9px] bg-muted px-1.5 py-0.5 rounded">
                                            {t.status_at_handoff ?? t.status}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <button
                        disabled
                        className="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-emerald-600 text-white font-black text-xs uppercase tracking-wider opacity-50 cursor-not-allowed"
                    >
                        <CheckCircle2 className="w-4 h-4" />
                        Aceptar Traspaso
                    </button>
                </div>
            </div>
        );
    }

    // ── WAITING banner — sender already handed off, waiting for acceptance ──
    if (data?.pending_session && !data.is_receiver) {
        return (
            <div className="w-full rounded-xl border border-amber-500/30 bg-amber-500/5 px-4 py-3 flex items-center gap-3 animate-in fade-in duration-300">
                <Clock className="w-4 h-4 text-amber-500 animate-pulse shrink-0" />
                <div>
                    <p className="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">
                        Esperando aceptación del traspaso
                    </p>
                    <p className="text-[9px] text-muted-foreground">
                        {data.pending_session.ticket_count} ticket(s) traspasados al turno {data.pending_session.to_sub_shift}
                    </p>
                </div>
            </div>
        );
    }

    // ── HANDOFF BUTTON ──
    const isMyTurnToHandoff = isActiveGroup && data?.my_sub_shift === data?.current_sub;

    if (!isMyTurnToHandoff) return null;

    return (
        <>
            {/* Trigger button */}
            <button
                onClick={() => setShowModal(true)}
                title={`Traspasar turno ${currSub} → ${nextSub}`}
                className={cn(
                    "flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-[10px] font-black uppercase tracking-wider",
                    "bg-indigo-500/10 border-indigo-500/30 text-indigo-600 dark:text-indigo-400",
                    "hover:bg-indigo-500 hover:text-white hover:border-indigo-600 transition-all shadow-sm"
                )}
            >
                <ArrowRightLeft className="w-3.5 h-3.5" />
                Traspasar Turno
            </button>

            {/* Modal - Wide (max-w-5xl) for comfort */}
            {showModal && (
                <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
                    <div className="bg-card w-full max-w-5xl rounded-2xl shadow-2xl border border-border flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">

                        {/* Header */}
                        <div className="flex items-center justify-between p-5 border-b border-border">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-xl bg-indigo-500/10 text-indigo-600">
                                    <ArrowRightLeft className="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 className="font-black text-base text-foreground">Traspaso de Sub-Turno</h2>
                                    <p className="text-[10px] text-muted-foreground uppercase tracking-wider">
                                        {currSub} → {nextSub}
                                    </p>
                                </div>
                            </div>
                            <button onClick={() => setShowModal(false)} className="p-2 hover:bg-muted rounded-full transition-colors text-muted-foreground">
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        {/* Ticket summary */}
                        <div className="flex-1 overflow-y-auto p-5">
                            
                            {/* Inner wrapper for html2canvas capturing */}
                            <div ref={printRef} className="space-y-4 bg-card pb-4">
                                
                                {/* Declarative Intro Text (Only visible in modal, hidden during image capture) */}
                                {/* Greeting is EXCLUDED from modal page intro text */}
                                {!isCapturing && (
                                    <div className="bg-muted/30 p-4 rounded-xl border border-border/50 text-xs text-foreground/90 leading-relaxed">
                                        Hoy siendo las <span className="font-black">{new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit', hour12: false })}</span> hrs
                                        del día <span className="font-black">{new Date().toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-')}</span>,
                                        día <span className="font-black">{cycleDay ?? '?'}</span> del turno actual,
                                        yo <span className="font-black uppercase">{currentEngineer.first_name} {currentEngineer.last_name}</span> traspaso el turno a
                                        &nbsp;<span className="font-black uppercase">{nextEngineer.first_name} {nextEngineer.last_name}</span> con los siguientes tickets pendientes:
                                    </div>
                                )}

                                <div>
                                    <h3 className="text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-2 flex items-center gap-1.5">
                                        <Ticket className="w-3 h-3" />
                                        Tickets del último turno ({recentTickets.length})
                                    </h3>
                                    {recentTickets.length === 0 ? (
                                        <div className="rounded-lg border border-dashed border-border p-4 text-center text-[11px] text-muted-foreground">
                                            No se crearon tickets en este sub-turno.
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            {recentTickets.map(t => (
                                                <div key={t.case_id} className="rounded-xl border border-border bg-card overflow-hidden shadow-sm">
                                                    {/* Card Header */}
                                                    <div className="flex items-start justify-between gap-3 px-4 py-2.5 bg-muted/20 border-b border-border/40">
                                                        <div className="flex flex-col gap-1 min-w-0">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-[10px] font-black text-primary shrink-0">{t.case_number}</span>
                                                                <span className="text-[9px] font-bold uppercase text-amber-600 bg-amber-500/10 px-1.5 py-0.5 rounded shrink-0">{t.status}</span>
                                                                {t.faena && <span className="text-[9px] font-bold uppercase text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded shrink-0">{t.faena}</span>}
                                                            </div>
                                                            <span className="text-[11px] font-bold text-foreground/90">{t.subject || '(Sin asunto)'}</span>
                                                        </div>
                                                    </div>
                                                    {/* Card Body */}
                                                    <div className="px-4 py-2.5 space-y-3 text-[10px]">
                                                        <div className="text-muted-foreground leading-relaxed whitespace-pre-wrap">
                                                            {t.description || <span className="italic opacity-50">Sin descripción</span>}
                                                        </div>

                                                        {/* Comments Section - Fully shown */}
                                                        {t.comments && t.comments.length > 0 && (
                                                            <div className="space-y-1.5 pt-1.5 border-t border-border/30">
                                                                <div className="flex items-center gap-1 font-semibold text-slate-500 mb-2">
                                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                                    Últimos {t.comments.length} comentarios
                                                                </div>
                                                                <div className="space-y-2">
                                                                    {t.comments.map((c, i) => (
                                                                        <div key={i} className="bg-muted/10 rounded-lg p-2 border border-border/30">
                                                                            <div className="flex items-center justify-between gap-2 mb-1">
                                                                                <span className="font-bold text-foreground/80">{c.Author}</span>
                                                                                <span className="text-[8px] text-muted-foreground whitespace-nowrap">
                                                                                    {new Date(c.CreatedDate).toLocaleString('es-CL', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false })}
                                                                                </span>
                                                                            </div>
                                                                            <div className="text-muted-foreground/90 leading-tight whitespace-pre-wrap">
                                                                                {c.Body.replace(/<[^>]*>?/gm, '')}
                                                                            </div>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                             </div>
                                                        )}
                                                        {(!t.comments || t.comments.length === 0) && (
                                                            <div className="flex items-center gap-4 text-slate-500 pt-1">
                                                                <div className="flex items-center gap-1 font-semibold">
                                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                                                    0 comentarios
                                                                </div>
                                                            </div>
                                                        )}
                                                        {/* Ticket-specific justification (Only visible during capture/print inside each ticket's card) */}
                                                        {isCapturing && ticketNotes[t.case_id]?.trim() && (
                                                            <div className="mt-3 p-2.5 rounded-lg bg-indigo-500/5 border border-indigo-500/10 text-[10px]">
                                                                <span className="font-bold text-indigo-600 block mb-1 uppercase text-[8px] tracking-wider">Justificación de traspaso de ticket:</span>
                                                                <p className="text-foreground leading-relaxed whitespace-pre-wrap">{ticketNotes[t.case_id]}</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Consolidated Justification Inputs (Hidden during capture) */}
                                    {!isCapturing && recentTickets.length > 0 && (
                                        <div className="mt-6 pt-5 border-t border-border space-y-4">
                                            <div>
                                                <h3 className="text-[10px] font-black uppercase tracking-widest text-foreground mb-1 flex items-center gap-1.5">
                                                    <FileText className="w-3.5 h-3.5" />
                                                    Justificaciones de Traspaso
                                                </h3>
                                                <p className="text-[9px] text-muted-foreground">
                                                    Escribe la justificación o seguimiento de cada ticket en su respectivo recuadro:
                                                </p>
                                            </div>
                                            
                                            <div className="space-y-3">
                                                {recentTickets.map(t => (
                                                    <div key={t.case_id} className="p-3.5 rounded-xl border border-border bg-muted/20 space-y-2">
                                                        <div className="flex items-center justify-between text-[10px]">
                                                            <span className="font-black text-primary">{t.case_number}</span>
                                                            {t.faena && <span className="font-bold uppercase text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded shrink-0">{t.faena}</span>}
                                                        </div>
                                                        <div className="text-[10px] font-bold text-foreground/80 truncate">
                                                            {t.subject || '(Sin asunto)'}
                                                        </div>
                                                        <textarea
                                                            value={ticketNotes[t.case_id] || ''}
                                                            onChange={e => setTicketNotes(prev => ({ ...prev, [t.case_id]: e.target.value }))}
                                                            placeholder="Escribe el estado actual, pendientes o seguimiento de este ticket..."
                                                            rows={2}
                                                            className="w-full rounded-lg border border-border bg-card px-3 py-2 text-[10px] text-foreground placeholder:text-muted-foreground/40 focus:outline-none focus:ring-1 focus:ring-indigo-500/30 resize-none transition-all"
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="border-t border-border">

                            {/* Paste Guide Banner — shows after copying */}
                            {showPasteGuide && (
                                <div className={cn(
                                    "p-4 border-b animate-in fade-in slide-in-from-bottom-2 duration-300",
                                    fallbackImage 
                                        ? "bg-amber-500/5 border-amber-500/20" 
                                        : "bg-emerald-500/5 border-emerald-500/20"
                                )}>
                                    <div className="flex items-start gap-3">
                                        <div className={cn(
                                            "mt-0.5 shrink-0 w-8 h-8 rounded-full flex items-center justify-center",
                                            fallbackImage ? "bg-amber-500/15" : "bg-emerald-500/15"
                                        )}>
                                            {fallbackImage ? (
                                                <span className="text-amber-600 font-bold text-sm">⚠️</span>
                                            ) : (
                                                <svg className="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                            )}
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className={cn(
                                                "text-xs font-black mb-1",
                                                fallbackImage ? "text-amber-700 dark:text-amber-400" : "text-emerald-700 dark:text-emerald-400"
                                            )}>
                                                {fallbackImage ? "Copiado automático bloqueado (Sin HTTPS)" : "¡Diseño copiado al portapapeles!"}
                                            </p>
                                            <div className="text-[10px] text-muted-foreground leading-relaxed">
                                                {fallbackImage ? (
                                                    <span>
                                                        Tu navegador bloqueó el copiado automático por seguridad (sitio HTTP sin SSL). 
                                                        Por favor, <strong className="text-foreground">haz clic derecho sobre la imagen de abajo</strong> y selecciona <strong className="text-foreground">"Copiar imagen"</strong>.
                                                    </span>
                                                ) : (
                                                    <span>
                                                        Ahora haz clic en el botón de abajo para abrir Outlook.
                                                        Cuando se abra, <strong className="text-foreground">haz clic dentro del cuerpo del correo</strong> y presiona <kbd className="bg-muted border border-border rounded px-1 py-0.5 text-[9px] font-mono">Ctrl</kbd> + <kbd className="bg-muted border border-border rounded px-1 py-0.5 text-[9px] font-mono">V</kbd> para pegar el diseño.
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Manual copy preview if fallback is active */}
                                    {fallbackImage && (
                                        <div className="mt-3 flex flex-col items-center justify-center p-2 bg-muted/40 rounded-lg border border-border/50">
                                            <p className="text-[9px] font-bold text-muted-foreground mb-1.5 uppercase tracking-wider">Haz clic derecho aquí &rarr; Copiar imagen</p>
                                            <img 
                                                src={fallbackImage} 
                                                alt="Traspaso Preview" 
                                                className="max-h-32 rounded border border-border shadow-sm hover:scale-[1.02] transition-transform cursor-pointer"
                                                title="Haz clic derecho y selecciona 'Copiar imagen'"
                                            />
                                        </div>
                                    )}

                                    <div className="flex gap-2 mt-3">
                                        <button
                                            onClick={() => { setShowPasteGuide(false); setFallbackImage(null); }}
                                            className="flex-1 py-2 rounded-lg border border-border text-[10px] font-bold text-muted-foreground hover:bg-muted transition-colors"
                                        >
                                            Cancelar
                                        </button>
                                        <button
                                            onClick={handleOpenOutlook}
                                            className={cn(
                                                "flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-white text-[10px] font-black uppercase tracking-wider transition-colors shadow-sm",
                                                fallbackImage ? "bg-amber-600 hover:bg-amber-700" : "bg-emerald-600 hover:bg-emerald-700"
                                            )}
                                        >
                                            <Send className="w-3 h-3" />
                                            {fallbackImage ? "Abrir Outlook" : "Abrir Outlook y pegar"}
                                        </button>
                                    </div>
                                </div>
                            )}

                            <div className="p-5 flex flex-wrap items-center gap-3 bg-slate-50/50 dark:bg-muted/5">
                                <button
                                    onClick={() => { setShowModal(false); setShowPasteGuide(false); }}
                                    className="px-4 py-2.5 rounded-xl border border-border text-[10px] font-black uppercase tracking-wider text-muted-foreground hover:bg-muted transition-colors shrink-0"
                                >
                                    Cancelar
                                </button>
                                
                                <ConglomerateReports variant="inline" />

                                <button
                                    onClick={handleSendEmail}
                                    disabled={showPasteGuide}
                                    className={cn(
                                        "flex items-center gap-1.5 px-5 py-2.5 rounded-xl border transition-all text-[10px] font-black uppercase tracking-wider shadow-md ml-auto shrink-0",
                                        showPasteGuide
                                            ? "bg-emerald-500/20 border-emerald-500/30 text-emerald-700 dark:text-emerald-400 opacity-60 cursor-not-allowed"
                                            : isCopied
                                                ? "bg-emerald-600 border-emerald-600 text-white"
                                                : "bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0"
                                    )}
                                    title="Copiar diseño y preparar correo"
                                >
                                    <Send className="w-3.5 h-3.5" />
                                    {showPasteGuide ? "¡Diseño Copiado!" : "Correo Traspaso Turno"}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};
