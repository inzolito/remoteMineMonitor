import React, { useState, useRef, useEffect } from 'react';
import { Send, Sparkles, X, Loader2, ExternalLink } from 'lucide-react';
import type { SFCase } from '../pages/TicketsPage';
interface Message {
    sender: 'user' | 'gemini';
    text: string;
    sql?: string;
    isError?: boolean;
}

interface AIChatWidgetProps {
    onClose: () => void;
    activeTickets: SFCase[];
    handleOpenTicketDetails: (t: SFCase) => void;
}

export const AIChatWidget: React.FC<AIChatWidgetProps> = ({
    onClose,
    activeTickets,
    handleOpenTicketDetails
}) => {
    const [messages, setMessages] = useState<Message[]>([
        {
            sender: 'gemini',
            text: '¡Hola! Soy **Gemini IA** integrado con RMM, tu asistente inteligente para buscar soluciones en tickets.\n\nPuedes preguntarme sobre tickets específicos, soluciones sobre problemas ya vistos, etc. Por ejemplo:\n* *Dame un resumen del ticket 00647658*\n* *Tengo un problema donde se congela el jspanel, ¿hay tickets que hayan resuelto ese problema?*'
        }
    ]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Auto-scroll to the bottom when new messages arrive
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isLoading]);

    const handleOpenTicketByNumber = async (caseNumber: string) => {
        // 1. Search in local activeTickets first
        const local = activeTickets.find(t => t.CaseNumber === caseNumber);
        if (local) {
            handleOpenTicketDetails(local);
            return;
        }

        // 2. Fetch from backend using the search parameter
        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const res = await fetch(`/monitoreoLaboratorio/v3/api/tickets.php?page=1&limit=5&search=${caseNumber}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            if (res.ok) {
                const data = await res.json();
                const found = data.tickets?.find((t: SFCase) => t.CaseNumber === caseNumber);
                if (found) {
                    handleOpenTicketDetails(found);
                } else {
                    alert(`No se encontró el ticket ${caseNumber} en la base de datos.`);
                }
            }
        } catch (e) {
            console.error('Error fetching ticket by number:', e);
        }
    };

    const handleSend = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!input.trim() || isLoading) return;

        const userMsg = input.trim();
        setInput('');
        setMessages(prev => [...prev, { sender: 'user', text: userMsg }]);
        setIsLoading(true);

        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const res = await fetch('/monitoreoLaboratorio/v3/api/ai_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    message: userMsg,
                    context: 'general_tickets'
                })
            });

            const data = await res.json();

            if (res.ok) {
                setMessages(prev => [...prev, {
                    sender: 'gemini',
                    text: data.response || 'No recibí respuesta del asistente.',
                    sql: data.sql
                }]);
            } else {
                setMessages(prev => [...prev, {
                    sender: 'gemini',
                    text: data.details || data.error || 'Ocurrió un error al procesar tu solicitud.',
                    isError: true
                }]);
            }
        } catch (err) {
            console.error(err);
            setMessages(prev => [...prev, {
                sender: 'gemini',
                text: 'Error de red. No se pudo conectar con el servidor de IA.',
                isError: true
            }]);
        } finally {
            setIsLoading(false);
        }
    };

    const renderMessageText = (text: string) => {
        const ticketRegex = /\b(00\d{6})\b/g;
        const parts = text.split(ticketRegex);
        
        return (
            <span className="whitespace-pre-line text-[11px] leading-relaxed">
                {parts.map((part, i) => {
                    // Check if part is an 8-digit ticket number starting with 00
                    if (part.match(/^00\d{6}$/)) {
                        return (
                            <button
                                key={i}
                                onClick={() => handleOpenTicketByNumber(part)}
                                className="inline-flex items-center gap-1.5 mx-1 px-2 py-0.5 rounded-lg bg-primary/10 hover:bg-primary/20 border border-primary/20 text-primary font-black text-[10px] transition-all hover:scale-105 whitespace-nowrap align-middle"
                            >
                                <ExternalLink className="w-2.5 h-2.5" />
                                {part}
                            </button>
                        );
                    }
                    
                    // Format markdown bold **text**
                    const subParts = part.split(/(\*\*.*?\*\*)/g);
                    return subParts.map((sub, j) => {
                        if (sub.startsWith('**') && sub.endsWith('**')) {
                            return (
                                <strong key={j} className="font-black text-foreground dark:text-white">
                                    {sub.slice(2, -2)}
                                </strong>
                            );
                        }
                        return sub;
                    });
                })}
            </span>
        );
    };

    return (
        <div className="flex flex-col h-full bg-card text-foreground">
            {/* Header */}
            <div className="flex items-center justify-between p-3 border-b border-border/80 bg-muted/20 shrink-0">
                <div className="flex items-center gap-2">
                    <div className="p-1.5 rounded-lg bg-gradient-to-tr from-blue-600 via-indigo-600 to-purple-600 text-white shadow-md animate-pulse">
                        <Sparkles className="w-4 h-4" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xs font-black tracking-wide bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-500 bg-clip-text text-transparent">Gemini AI</span>
                        <div className="flex items-center gap-1">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span className="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Asistente</span>
                        </div>
                    </div>
                </div>
                <button
                    onClick={onClose}
                    className="p-1.5 hover:bg-muted text-muted-foreground hover:text-foreground rounded-lg transition-colors"
                >
                    <X className="w-4 h-4" />
                </button>
            </div>

            {/* Chat Messages */}
            <div className="flex-1 overflow-y-auto p-4 flex flex-col gap-4 bg-muted/5">
                {messages.map((msg, i) => (
                    <div
                        key={i}
                        className={`flex flex-col gap-1 max-w-[85%] ${
                            msg.sender === 'user' ? 'self-end items-end' : 'self-start items-start'
                        }`}
                    >
                        {/* Bubble */}
                        <div
                            className={`p-3 rounded-2xl border shadow-sm ${
                                msg.sender === 'user'
                                    ? 'bg-primary border-primary text-primary-foreground rounded-tr-none'
                                    : msg.isError
                                    ? 'bg-rose-50 dark:bg-rose-950/10 border-rose-200/50 text-rose-800 dark:text-rose-400 rounded-tl-none'
                                    : 'bg-card border-border/50 text-card-foreground rounded-tl-none'
                            }`}
                        >
                            {msg.sender === 'user' ? (
                                <p className="text-[11px] font-medium leading-relaxed whitespace-pre-wrap">{msg.text}</p>
                            ) : (
                                renderMessageText(msg.text)
                            )}
                        </div>

                        {/* SQL Debug Info */}
                        {msg.sql && (
                            <span className="text-[7.5px] font-mono text-muted-foreground/60 px-1 truncate max-w-full">
                                {msg.sql}
                            </span>
                        )}
                    </div>
                ))}

                {isLoading && (
                    <div className="self-start flex items-center gap-2 bg-card border border-border/50 p-3 rounded-2xl rounded-tl-none shadow-sm max-w-[85%]">
                        <Loader2 className="w-3.5 h-3.5 animate-spin text-indigo-600" />
                        <span className="text-[10px] text-muted-foreground font-semibold animate-pulse">Gemini está pensando...</span>
                    </div>
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Bar */}
            <form onSubmit={handleSend} className="p-3 border-t border-border/80 bg-muted/20 shrink-0 flex gap-2">
                <input
                    type="text"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    placeholder="Escribe tu consulta sobre los tickets..."
                    disabled={isLoading}
                    className="flex-1 px-3 py-2 bg-background border border-border rounded-xl text-[11px] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-50 transition-all placeholder:text-muted-foreground/60 dark:bg-card"
                />
                <button
                    type="submit"
                    disabled={!input.trim() || isLoading}
                    className="p-2 bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-xl disabled:opacity-50 disabled:from-slate-400 disabled:to-slate-400 shadow-md hover:shadow-lg transition-all hover:scale-105 active:scale-95"
                >
                    <Send className="w-3.5 h-3.5" />
                </button>
            </form>
        </div>
    );
};
