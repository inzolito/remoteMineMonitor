import React, { useState, useRef } from 'react';
import { createPortal } from 'react-dom';
import html2canvas from 'html2canvas';
import { Mail, Loader2, X, Send, Copy, Image, FileText, AlertCircle } from 'lucide-react';
import { cn } from '../lib/utils';

interface Ticket {
    Id: string;
    CaseNumber: string;
    Subject: string;
    Status: string;
    Priority: string;
    CreatedDate: string;
    ClosedDate: string | null;
    Description: string;
    Resolution: string;
    Faena: string;
    Conglomerate: string;
    OwnerName: string;
    Duration: string;
    SolucionOComentario: string;
}

interface ApiResponse {
    conglomerate: string;
    start_date: string;
    end_date: string;
    tickets: Ticket[];
}

interface ConglomerateReportsProps {
    variant?: 'card' | 'inline';
}

export const ConglomerateReports: React.FC<ConglomerateReportsProps> = ({ variant = 'card' }) => {
    const [selectedConglom, setSelectedConglom] = useState<string | null>(null);
    const [displayName, setDisplayName] = useState<string>('');
    const [data, setData] = useState<ApiResponse | null>(null);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [isCopiedHtml, setIsCopiedHtml] = useState<boolean>(false);
    const [isCopiedImg, setIsCopiedImg] = useState<boolean>(false);
    const [fallbackImage, setFallbackImage] = useState<string | null>(null);
    const [showPasteGuide, setShowPasteGuide] = useState<boolean>(false);
    const tableRef = useRef<HTMLDivElement>(null);

    const conglomConfig = [
        { key: 'AMSA', name: 'AMSA', color: 'bg-blue-600 border-blue-700/30 hover:bg-blue-700 hover:border-blue-700 text-white', themeHex: '#2563eb' },
        { key: 'CODELCO', name: 'Codelco', color: 'bg-amber-600 border-amber-700/30 hover:bg-amber-700 hover:border-amber-700 text-white', themeHex: '#d97706' },
        { key: 'Capstone Copper', name: 'Manto Verde', color: 'bg-emerald-600 border-emerald-700/30 hover:bg-emerald-700 hover:border-emerald-700 text-white', themeHex: '#059669' },
        { key: 'Otros', name: 'Otras Minas', color: 'bg-slate-600 border-slate-700/30 hover:bg-slate-700 hover:border-slate-700 text-white', themeHex: '#475569' },
    ];

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

    const handleOpenReport = async (conglomKey: string, name: string) => {
        setSelectedConglom(conglomKey);
        setDisplayName(name);
        setIsLoading(true);
        setData(null);
        setIsCopiedHtml(false);
        setIsCopiedImg(false);
        setFallbackImage(null);
        setShowPasteGuide(false);

        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const response = await fetch(`/monitoreoLaboratorio/v3/api/conglomerate_tickets.php?conglomerate=${encodeURIComponent(conglomKey)}`, {
                headers: { Authorization: `Bearer ${token}` }
            });
            if (response.ok) {
                const resData = await response.json();
                setData(resData);
                // Automatically copy report HTML table to clipboard
                setTimeout(() => {
                    performCopyHtml(resData, name);
                }, 100);
            } else {
                console.error("Error fetching conglomerate tickets");
            }
        } catch (err) {
            console.error("Connection error", err);
        } finally {
            setIsLoading(false);
        }
    };

    const formatOwnerName = (name: string) => {
        if (!name) return 'Sin asignar';
        if (name.toLowerCase().includes('support q') || name.toLowerCase().includes('queue')) {
            return 'S.A. Queue';
        }
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return `${parts[0][0]}. ${parts[parts.length - 1]}`;
        }
        return name;
    };

    const getStatusLabel = (status: string) => {
        const s = status.toLowerCase();
        if (s === 'closed') return 'CERRADO';
        if (s === 'resolved') return 'RESUELTO';
        if (s === 'working') return 'EN PROCESO';
        if (s === 'seeking customer clarification') return 'ESP. CLIENTE';
        if (s === 'escalate pd') return 'ESCALADO PD';
        if (s === 'assigned') return 'ASIGNADO';
        if (s === 'new') return 'NUEVO';
        return status.toUpperCase();
    };

    const formatDateTime24h = (dateStr: string | null | undefined, includeYear: boolean = false) => {
        if (!dateStr) return '-';
        const cleanStr = dateStr.replace('T', ' ');
        const parts = cleanStr.split(' ');
        if (parts.length < 2) return dateStr;
        const dateParts = parts[0].split('-');
        const timeParts = parts[1].split(':');
        if (dateParts.length < 3 || timeParts.length < 2) return dateStr;
        
        const day = dateParts[2];
        const month = dateParts[1];
        const year = dateParts[0];
        const hours = timeParts[0];
        const minutes = timeParts[1];
        
        if (includeYear) {
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }
        return `${day}/${month} ${hours}:${minutes}`;
    };

    const generateHtmlTableString = (tickets: Ticket[], start: string, end: string, themeHex: string): string => {
        const formattedStart = formatDateTime24h(start, true);
        const formattedEnd = formatDateTime24h(end, true);
        
        let rowsHtml = '';
        tickets.forEach(t => {
            const isClosed = t.Status.toLowerCase() === 'closed';
            const rowBg = isClosed ? '#ffffff' : '#fef2f2';
            const rowText = isClosed ? '#334155' : '#991b1b';
            
            const badgeBg = isClosed ? '#dcfce7' : '#fee2e2';
            const badgeText = isClosed ? '#15803d' : '#b91c1c';
            const badgeLabel = getStatusLabel(t.Status);

            const createdDateFormatted = formatDateTime24h(t.CreatedDate, false);
            const closedDateFormatted = t.ClosedDate ? formatDateTime24h(t.ClosedDate, false) : '-';

            rowsHtml += `
                <tr style="background-color: ${rowBg}; color: ${rowText}; border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px; border: 1px solid #cbd5e1; white-space: nowrap;">
                        <div style="font-weight: bold; margin-bottom: 3px;">
                            <a href="https://usa1.lightning.force.com/lightning/r/Case/${t.Id}/view" style="color: #2563eb; text-decoration: none;">${t.CaseNumber}</a>
                        </div>
                        <div style="font-size: 10px; text-transform: uppercase; font-weight: 900; color: #475569;">
                            ${t.Faena} <span style="font-weight: normal; text-transform: none; color: #64748b;">(${formatOwnerName(t.OwnerName)})</span>
                        </div>
                    </td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; font-weight: bold;">${t.Subject}</td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; max-width: 250px; white-space: pre-line;">${t.Description}</td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; text-align: center;">
                        <span style="background-color: ${badgeBg}; color: ${badgeText}; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 10px; display: inline-block; white-space: nowrap;">
                            ${badgeLabel}
                        </span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; white-space: pre-line;">${t.SolucionOComentario}</td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; white-space: nowrap;">${createdDateFormatted}</td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; white-space: nowrap;">${closedDateFormatted}</td>
                    <td style="padding: 10px; border: 1px solid #cbd5e1; font-weight: bold; white-space: nowrap;">${t.Duration}</td>
                </tr>
            `;
        });

        if (tickets.length === 0) {
            rowsHtml = `
                <tr>
                    <td colspan="8" style="padding: 20px; border: 1px solid #cbd5e1; text-align: center; color: #64748b; font-style: italic;">
                        No se registraron tickets en este período.
                    </td>
                </tr>
            `;
        }

        const messageText = tickets.length > 0
            ? `Junto con saludar, comparto el reporte de tickets para <strong>${displayName.toUpperCase()}</strong>.`
            : `Junto con saludar, no se registraron tickets para <strong>${displayName.toUpperCase()}</strong>.`;

        return `
            <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #334155; max-width: 1000px;">
                <p>${getGreeting()},</p>
                <p>${messageText}</p>
                <p style="font-size: 11px; color: #64748b; margin-bottom: 15px;">Período evaluado: <strong>${formattedStart}</strong> al <strong>${formattedEnd}</strong></p>
                
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; font-family: 'Segoe UI', sans-serif; font-size: 11px; margin-top: 15px; background-color: #ffffff;">
                    <thead>
                        <tr style="background-color: ${themeHex}; color: #ffffff; text-align: left;">
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold;">Ticket / Faena (Creador)</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold;">Asunto</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold; width: 20%;">Descripción</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold; text-align: center;">Estado</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold; width: 25%;">Solución / Último Comentario</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold;">Apertura</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold;">Cierre</th>
                            <th style="padding: 12px 10px; border: 1px solid #cbd5e1; font-weight: bold;">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rowsHtml}
                    </tbody>
                </table>
                <br/>
                <p style="font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px;">Reporte generado automáticamente desde RMM System.</p>
            </div>
        `;
    };

    const performCopyHtml = async (resData: ApiResponse, conglomDisplayName: string) => {
        const configItem = conglomConfig.find(c => c.key === resData.conglomerate) || { themeHex: '#475569' };
        const htmlContent = generateHtmlTableString(resData.tickets, resData.start_date, resData.end_date, configItem.themeHex);
        const messageText = resData.tickets.length > 0
            ? `Junto con saludar, comparto el reporte de tickets para ${conglomDisplayName.toUpperCase()}.`
            : `Junto con saludar, no se registraron tickets para ${conglomDisplayName.toUpperCase()}.`;
        const plainText = `${getGreeting()},\n\n${messageText}\nPeriodo: ${resData.start_date} al ${resData.end_date}.`;

        try {
            const htmlBlob = new Blob([htmlContent], { type: 'text/html' });
            const textBlob = new Blob([plainText], { type: 'text/plain' });
            const clipboardItem = new ClipboardItem({
                'text/html': htmlBlob,
                'text/plain': textBlob
            });
            await navigator.clipboard.write([clipboardItem]);
            setIsCopiedHtml(true);
            setTimeout(() => setIsCopiedHtml(false), 3000);
            setShowPasteGuide(true);
        } catch (err) {
            console.warn("Modern clipboard API failed, trying execCommand fallback...", err);
            
            // execCommand fallback
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlContent;
            tempDiv.style.position = 'fixed';
            tempDiv.style.left = '-9999px';
            document.body.appendChild(tempDiv);
            
            const range = document.createRange();
            range.selectNode(tempDiv);
            const sel = window.getSelection();
            if (sel) {
                sel.removeAllRanges();
                sel.addRange(range);
            }
            
            const successful = document.execCommand('copy');
            if (sel) {
                sel.removeAllRanges();
            }
            document.body.removeChild(tempDiv);

            if (successful) {
                setIsCopiedHtml(true);
                setTimeout(() => setIsCopiedHtml(false), 3000);
                setShowPasteGuide(true);
            } else {
                alert("No se pudo copiar automáticamente. Por favor, selecciona y copia la tabla manualmente.");
            }
        }
    };

    const handleCopyHtml = async () => {
        if (!data) return;
        await performCopyHtml(data, displayName);
    };

    const handleCopyImage = async () => {
        if (!tableRef.current || !data) return;

        try {
            setIsCopiedImg(false);
            setFallbackImage(null);
            
            // Wait a brief moment
            await new Promise(resolve => setTimeout(resolve, 50));

            const canvas = await html2canvas(tableRef.current, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
                windowWidth: 1200
            });

            canvas.toBlob(async (blob) => {
                if (!blob) {
                    alert("Error al generar la imagen.");
                    return;
                }

                try {
                    const item = new ClipboardItem({ 'image/png': blob });
                    await navigator.clipboard.write([item]);
                    setIsCopiedImg(true);
                    setTimeout(() => setIsCopiedImg(false), 3000);
                    setShowPasteGuide(true);
                } catch (err) {
                    console.warn("Image clipboard copy failed, falling back to manual copy", err);
                    const dataUrl = canvas.toDataURL('image/png');
                    setFallbackImage(dataUrl);
                    setShowPasteGuide(true);
                }
            }, 'image/png');

        } catch (err) {
            console.error("Error capturing table with html2canvas", err);
            alert("No se pudo generar la imagen del reporte.");
        }
    };

    const handleOpenOutlook = () => {
        if (!data) return;
        const formattedDate = new Date().toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        const subject = encodeURIComponent(`Reporte de Turno 24h: ${displayName.toUpperCase()} - ${formattedDate}`);
        const greeting = getGreeting();
        const bodyText = encodeURIComponent(
            `${greeting},\n\n` +
            `Junto con saludar, comparto el reporte de tickets de las últimas 24 horas del conglomerado ${displayName.toUpperCase()}.`
        );
        
        window.open(`mailto:?subject=${subject}&body=${bodyText}`, '_blank');
        setShowPasteGuide(false);
    };

    const renderModal = () => {
        if (!selectedConglom) return null;
        return createPortal(
            <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in duration-200">
                <div className="bg-card w-full max-w-6xl rounded-2xl shadow-2xl border border-border flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200">
                    
                    {/* Header */}
                    <div className="flex items-center justify-between p-5 border-b border-border">
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-xl bg-primary/10 text-primary">
                                <FileText className="w-5 h-5" />
                            </div>
                            <div>
                                <h2 className="font-black text-base text-foreground">Reporte de Cierre: {displayName.toUpperCase()}</h2>
                                <p className="text-[10px] text-muted-foreground uppercase tracking-wider font-bold">
                                    Período de 24 horas (08:00 AM del día anterior a 08:00 AM de hoy)
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={() => setSelectedConglom(null)}
                            className="p-2 hover:bg-muted rounded-full transition-colors text-muted-foreground"
                        >
                            <X className="w-4 h-4" />
                        </button>
                    </div>

                    {/* Modal Body */}
                    <div className="flex-1 overflow-y-auto p-6 space-y-4">
                        {isLoading ? (
                            <div className="flex flex-col items-center justify-center py-20 gap-3">
                                <Loader2 className="w-8 h-8 text-primary animate-spin" />
                                <p className="text-xs text-muted-foreground font-bold animate-pulse">Cargando datos del conglomerado...</p>
                            </div>
                        ) : data ? (
                            <div className="space-y-4">
                                {/* Info Banner */}
                                <div className="flex justify-between items-center text-[10px] bg-muted/40 px-3 py-2 rounded-xl border border-border/40 font-bold">
                                    <span>Desde: {formatDateTime24h(data.start_date, true)}</span>
                                    <span>Hasta: {formatDateTime24h(data.end_date, true)}</span>
                                    <span className="bg-primary/10 text-primary px-2.5 py-0.5 rounded-md">Total: {data.tickets.length} tickets</span>
                                </div>

                                {/* Capture Area */}
                                <div className="border border-border rounded-xl overflow-hidden bg-white shadow-sm p-4 text-black dark:text-black">
                                    <div ref={tableRef} className="bg-white p-4">
                                        {/* Preview content inside capture wrapper */}
                                        <div className="font-sans text-xs text-slate-800 space-y-2">
                                            <p className="text-slate-900 font-medium">{getGreeting()},</p>
                                            <p>
                                                {data.tickets.length > 0
                                                    ? <>Junto con saludar, comparto el reporte de tickets para <strong>{displayName.toUpperCase()}</strong>.</>
                                                    : <>Junto con saludar, no se registraron tickets para <strong>{displayName.toUpperCase()}</strong>.</>}
                                            </p>
                                            <p className="text-[10px] text-slate-500">
                                                Período evaluado: <strong>{formatDateTime24h(data.start_date, true)}</strong> al <strong>{formatDateTime24h(data.end_date, true)}</strong>
                                            </p>
                                            
                                            <div className="overflow-x-auto mt-4">
                                                <table className="w-full text-left border-collapse border border-slate-300 text-[10.5px]">
                                                    <thead>
                                                        <tr className="bg-slate-800 text-white font-bold">
                                                            <th className="border border-slate-300 p-2.5">Ticket / Faena (Creador)</th>
                                                            <th className="border border-slate-300 p-2.5">Asunto</th>
                                                            <th className="border border-slate-300 p-2.5 max-w-[200px]">Descripción</th>
                                                            <th className="border border-slate-300 p-2.5 text-center">Estado</th>
                                                            <th className="border border-slate-300 p-2.5 max-w-[240px]">Solución / Último Comentario</th>
                                                            <th className="border border-slate-300 p-2.5">Apertura</th>
                                                            <th className="border border-slate-300 p-2.5">Cierre</th>
                                                            <th className="border border-slate-300 p-2.5">Duración</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {data.tickets.map((t) => {
                                                            const isClosed = t.Status.toLowerCase() === 'closed';
                                                            return (
                                                                <tr
                                                                    key={t.Id}
                                                                    className={cn(
                                                                        "border border-slate-300",
                                                                        isClosed ? "bg-white" : "bg-red-50/50 text-red-950 font-medium"
                                                                    )}
                                                                >
                                                                    <td className="border border-slate-300 p-2.5 whitespace-nowrap">
                                                                         <div className="font-bold text-blue-600 mb-0.5">{t.CaseNumber}</div>
                                                                         <div className="text-[10px] text-slate-500 uppercase font-black">
                                                                             {t.Faena} <span className="font-normal text-slate-400">({formatOwnerName(t.OwnerName)})</span>
                                                                         </div>
                                                                     </td>
                                                                    <td className="border border-slate-300 p-2.5 font-semibold">{t.Subject}</td>
                                                                    <td className="border border-slate-300 p-2.5 whitespace-pre-wrap max-w-[200px]">{t.Description}</td>
                                                                    <td className="border border-slate-300 p-2.5 text-center whitespace-nowrap">
                                                                        <span
                                                                            className={cn(
                                                                                "px-2 py-0.5 rounded text-[9px] font-black tracking-wide inline-block",
                                                                                isClosed 
                                                                                    ? "bg-green-100 text-green-800" 
                                                                                    : "bg-red-100 text-red-800"
                                                                            )}
                                                                        >
                                                                            {getStatusLabel(t.Status)}
                                                                        </span>
                                                                    </td>
                                                                    <td className="border border-slate-300 p-2.5 whitespace-pre-wrap max-w-[240px]">
                                                                        {t.SolucionOComentario}
                                                                    </td>
                                                                    <td className="border border-slate-300 p-2.5 whitespace-nowrap">
                                                                        {formatDateTime24h(t.CreatedDate, false)}
                                                                    </td>
                                                                    <td className="border border-slate-300 p-2.5 whitespace-nowrap">
                                                                        {t.ClosedDate ? formatDateTime24h(t.ClosedDate, false) : '-'}
                                                                    </td>
                                                                    <td className="border border-slate-300 p-2.5 font-bold whitespace-nowrap">{t.Duration}</td>
                                                                </tr>
                                                            );
                                                        })}
                                                        {data.tickets.length === 0 && (
                                                            <tr>
                                                                <td colSpan={8} className="border border-slate-300 p-8 text-center text-slate-400 italic">
                                                                    No se registraron tickets en este período.
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-20 gap-2">
                                <AlertCircle className="w-8 h-8 text-destructive" />
                                <p className="text-xs text-destructive font-bold">Error al cargar la información.</p>
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="border-t border-border bg-muted/20">
                        
                        {/* Paste Guide Banner */}
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
                                            {fallbackImage ? "Copiado automático de imagen bloqueado (Sin HTTPS)" : "¡Reporte copiado al portapapeles!"}
                                        </p>
                                        <div className="text-[10px] text-muted-foreground leading-relaxed">
                                            {fallbackImage ? (
                                                <span>
                                                    Tu navegador bloqueó el copiado automático por seguridad (sitio HTTP sin SSL). 
                                                    Por favor, <strong className="text-foreground">haz clic derecho sobre la imagen de abajo</strong> y selecciona <strong className="text-foreground">"Copiar imagen"</strong>.
                                                </span>
                                            ) : (
                                                <span>
                                                    Ahora haz clic en el botón de abajo para preparar el correo en Outlook.
                                                    Una vez abierto, <strong className="text-foreground">haz clic dentro del cuerpo del correo</strong> y presiona <kbd className="bg-muted border border-border rounded px-1 py-0.5 text-[9px] font-mono">Ctrl</kbd> + <kbd className="bg-muted border border-border rounded px-1 py-0.5 text-[9px] font-mono">V</kbd> para pegar.
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
                                            alt="Report Preview" 
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
                                        Abrir Outlook y pegar
                                    </button>
                                </div>
                            </div>
                        )}

                        <div className="p-5 flex flex-col sm:flex-row gap-3">
                            <button
                                onClick={() => setSelectedConglom(null)}
                                className="flex-1 py-2.5 rounded-xl border border-border text-xs font-bold text-muted-foreground hover:bg-muted transition-colors order-last sm:order-first"
                            >
                                Cerrar
                            </button>
                            
                            <button
                                onClick={handleCopyHtml}
                                disabled={isLoading || !data}
                                className={cn(
                                    "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl border text-xs font-bold shadow-sm transition-all",
                                    isCopiedHtml
                                        ? "bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400"
                                        : "bg-indigo-600 text-white hover:bg-indigo-700 border-indigo-600"
                                )}
                            >
                                <Copy className="w-4 h-4" />
                                {isCopiedHtml ? "¡Tabla Copiada!" : "Copiar Tabla HTML"}
                            </button>

                            <button
                                onClick={handleCopyImage}
                                disabled={isLoading || !data}
                                className={cn(
                                    "flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl border text-xs font-bold shadow-sm transition-all",
                                    isCopiedImg
                                        ? "bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400"
                                        : "bg-teal-600 text-white hover:bg-teal-700 border-teal-600"
                                )}
                            >
                                <Image className="w-4 h-4" />
                                {isCopiedImg ? "¡Imagen Copiada!" : "Copiar como Imagen"}
                            </button>
                        </div>
                    </div>
                </div>
            </div>,
            document.body
        );
    };

    if (variant === 'inline') {
        return (
            <>
                {conglomConfig.map((config) => (
                    <button
                        key={config.key}
                        onClick={() => handleOpenReport(config.key, config.name)}
                        className={cn(
                            "flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all shrink-0",
                            config.color
                        )}
                    >
                        <Mail className="w-3.5 h-3.5" />
                        {config.name}
                    </button>
                ))}
                {renderModal()}
            </>
        );
    }

    return (
        <div className="bg-card rounded-2xl border border-border p-4 shadow-sm relative overflow-hidden">
            <div className="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
            
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 relative z-10">
                <div>
                    <h3 className="text-xs font-black text-foreground uppercase tracking-widest flex items-center gap-2">
                        <FileText className="w-4 h-4 text-primary" /> Reportes de Cierre 24h
                    </h3>
                    <p className="text-[10px] text-muted-foreground mt-0.5">
                        Genera y envía reportes consolidados por conglomerado (Turno Día + Noche anterior: 08:00 a 08:00).
                    </p>
                </div>
                
                <div className="flex flex-wrap gap-2.5 w-full sm:w-auto">
                    {conglomConfig.map((config) => (
                        <button
                            key={config.key}
                            onClick={() => handleOpenReport(config.key, config.name)}
                            className={cn(
                                "flex-1 sm:flex-initial flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all",
                                config.color
                            )}
                        >
                            <Mail className="w-3.5 h-3.5" />
                            {config.name}
                        </button>
                    ))}
                </div>
            </div>
            {renderModal()}
        </div>
    );
};
