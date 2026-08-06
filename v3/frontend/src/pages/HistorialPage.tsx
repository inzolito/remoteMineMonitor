import { useState, useEffect, useCallback, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Clock, Filter, Download, FileSpreadsheet, RefreshCw, AlertTriangle, CheckCircle, Activity, Users, Calendar, ChevronLeft, ChevronRight, Search, X, SlidersHorizontal } from 'lucide-react';
import { getHistorial } from '../services/historialService';
import type { HistorialFilters, HistorialAlert } from '../services/historialService';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

// ── Helpers ──────────────────────────────────────────────────────────────────

const CATEGORIES = [
    { id: 'jams',       label: 'JAMS',          color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' },
    { id: 'summarizer', label: 'Summarizer',     color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' },
    { id: 'backup',     label: 'Backup',         color: 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300' },
    { id: 'cpu',        label: 'CPU / Carga',    color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300' },
    { id: 'disk',       label: 'Disco',          color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' },
    { id: 'freshness',  label: 'Conectividad',   color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' },
    { id: 'repc',       label: 'Equipos',        color: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300' },
    { id: 'db',         label: 'Base de Datos',  color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300' },
];

function getCategoryColor(catId: string) {
    return CATEGORIES.find(c => c.id === catId)?.color ?? 'bg-gray-100 text-gray-700';
}

function timeBadge(seconds: number | null, type: 'response' | 'resolution') {
    if (seconds === null) return <span className="text-slate-400 text-xs">—</span>;
    const thresholds = type === 'response'
        ? { green: 300, yellow: 1800 }
        : { green: 900, yellow: 3600 };
    const cls = seconds <= thresholds.green
        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
        : seconds <= thresholds.yellow
        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
    const fmt = seconds < 60 ? `${seconds}s`
        : seconds < 3600 ? `${Math.floor(seconds/60)}m ${seconds%60}s`
        : `${Math.floor(seconds/3600)}h ${Math.floor((seconds%3600)/60)}m`;
    return <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${cls}`}>{fmt}</span>;
}

function statusPill(status: string) {
    const map: Record<string, string> = {
        solved:       'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        acknowledged: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        active:       'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    const labels: Record<string,string> = { solved: 'Resuelta', acknowledged: 'En revisión', active: 'Activa' };
    return (
        <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${map[status] ?? ''}`}>
            {labels[status] ?? status}
        </span>
    );
}

function formatDt(dt: string | null) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('es-CL', { day:'2-digit', month:'2-digit', year:'2-digit', hour:'2-digit', minute:'2-digit' });
}

function StatCard({ icon: Icon, label, value, sub, color }: { icon: any; label: string; value: string; sub?: string; color: string }) {
    return (
        <div className="bg-white dark:bg-slate-900 rounded-xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div className={`p-3 rounded-xl ${color}`}><Icon className="w-5 h-5" /></div>
            <div>
                <p className="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wide">{label}</p>
                <p className="text-xl font-black text-slate-800 dark:text-slate-100 leading-tight">{value}</p>
                {sub && <p className="text-[11px] text-slate-400 mt-0.5">{sub}</p>}
            </div>
        </div>
    );
}

// ── Main Component ────────────────────────────────────────────────────────────

export default function HistorialPage() {
    const [filters, setFilters] = useState<HistorialFilters>({ page: 1, per_page: 50 });
    const [applied, setApplied] = useState<HistorialFilters>({ page: 1, per_page: 50 });
    const [showAdvanced, setShowAdvanced] = useState(false);
    const initializedRef = useRef(false);

    const { data, isLoading, isFetching, refetch } = useQuery({
        queryKey: ['historial', applied],
        queryFn: () => getHistorial(applied),
        staleTime: 30000,
    });

    const alerts        = data?.alerts        ?? [];
    const meta          = data?.meta;
    const shiftCycles   = data?.shift_cycles  ?? [];
    const shiftMembers  = data?.shift_members ?? [];

    const applyFilters = useCallback(() => {
        setApplied({ ...filters, page: 1 });
        setShowAdvanced(false);
    }, [filters]);

    useEffect(() => {
        if (!initializedRef.current && shiftCycles.length > 0) {
            initializedRef.current = true;
            const f = { page: 1, per_page: 50, cycle_id: shiftCycles[0].id };
            setFilters(f);
            setApplied(f);
        }
    }, [shiftCycles]);

    const setQuickFilter = (type: 'all' | 'current_shift') => {
        if (type === 'all') {
            const f = { page: 1, per_page: 50 };
            setFilters(f); setApplied(f);
        } else if (type === 'current_shift' && shiftCycles.length > 0) {
            const f = { page: 1, per_page: 50, cycle_id: shiftCycles[0].id };
            setFilters(f); setApplied(f);
        }
    };

    const setPage = (p: number) => setApplied(prev => ({ ...prev, page: p }));

    // ── Export helpers ──────────────────────────────────────────────────────

    const exportExcel = () => {
        const rows = alerts.map(a => ({
            'Faena':            a.site_name,
            'Servidor':         a.server_name,
            'Tipo de Alerta':   a.category_label,
            'Métrica':          a.metric_key,
            'Descripción':      a.description,
            'Estado':           a.status,
            'Inicio':           formatDt(a.created_at),
            'Revisado por':     a.acknowledged_by ?? '—',
            'T. Respuesta':     a.response_time_fmt ?? '—',
            'T. Resolución':    a.resolution_time_fmt ?? '—',
        }));
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Historial');
        XLSX.writeFile(wb, `historial_alertas_${new Date().toISOString().slice(0,10)}.xlsx`);
    };

    const exportPDF = () => {
        const doc = new jsPDF({ orientation: 'landscape' });
        doc.setFontSize(14);
        doc.text('Historial de Alertas — Monitoreo Remoto V3', 14, 14);
        doc.setFontSize(9);
        doc.text(`Exportado: ${new Date().toLocaleString('es-CL')}  |  Total registros: ${meta?.total ?? 0}`, 14, 21);
        autoTable(doc, {
            startY: 26,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [2, 132, 199] },
            head: [['Faena','Servidor','Tipo','Estado','Inicio','Revisado por','T.Respuesta','T.Resolución']],
            body: alerts.map(a => [
                a.site_name, a.server_name, a.category_label, a.status,
                formatDt(a.created_at), a.acknowledged_by ?? '—',
                a.response_time_fmt ?? '—', a.resolution_time_fmt ?? '—',
            ]),
        });
        doc.save(`historial_alertas_${new Date().toISOString().slice(0,10)}.pdf`);
    };

    // ── Render ──────────────────────────────────────────────────────────────

    return (
        <div className="flex flex-col h-full bg-slate-50 dark:bg-slate-950 min-h-screen">

            {/* Header */}
            <div className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="p-2 bg-sky-100 dark:bg-sky-900/30 rounded-xl">
                        <Clock className="w-5 h-5 text-sky-600" />
                    </div>
                    <div>
                        <h1 className="text-lg font-black text-slate-800 dark:text-slate-100">Historial de Alertas</h1>
                        <p className="text-xs text-slate-500">Registro histórico de incidencias del sistema de monitoreo</p>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <button onClick={() => refetch()} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <RefreshCw className={`w-4 h-4 ${isFetching ? 'animate-spin' : ''}`} /> Actualizar
                    </button>
                    <button onClick={exportExcel} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700 transition-colors font-medium">
                        <FileSpreadsheet className="w-4 h-4" /> Excel
                    </button>
                    <button onClick={exportPDF} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700 transition-colors font-medium">
                        <Download className="w-4 h-4" /> PDF
                    </button>
                </div>
            </div>

            <div className="flex-1 p-6 space-y-5 overflow-auto">

                {/* Stats */}
                {meta && (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <StatCard icon={AlertTriangle} label="Total Alertas" value={meta.total.toLocaleString('es-CL')} color="bg-red-100 dark:bg-red-900/30 text-red-600" />
                        <StatCard icon={Clock} label="Tiempo Prom. Respuesta" value={meta.avg_response_fmt ?? '—'} sub="desde alerta a revisión" color="bg-amber-100 dark:bg-amber-900/30 text-amber-600" />
                        <StatCard icon={CheckCircle} label="Tiempo Prom. Resolución" value={meta.avg_resolution_fmt ?? '—'} sub="desde alerta a resuelta" color="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600" />
                        <StatCard icon={Activity} label="Tipo Más Frecuente" value={meta.top_alert_category ?? '—'} sub={`${meta.top_alert_count} ocurrencias`} color="bg-sky-100 dark:bg-sky-900/30 text-sky-600" />
                    </div>
                )}

                {/* Minimal Filters Bar */}
                <div className="flex flex-col gap-3 mb-2">
                    <div className="flex items-center justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            <button onClick={() => setQuickFilter('current_shift')} 
                                    className={`px-4 py-1.5 rounded-full text-xs font-bold transition-all shadow-sm ${filters.cycle_id === shiftCycles[0]?.id && !filters.date_from ? 'bg-[#0284c7] text-white border border-transparent' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50'}`}>
                                Turno Actual
                            </button>
                            <button onClick={() => setQuickFilter('all')} 
                                    className={`px-4 py-1.5 rounded-full text-xs font-bold transition-all shadow-sm ${!filters.cycle_id && !filters.user_id && !filters.category && !filters.date_from ? 'bg-slate-800 text-white dark:bg-slate-200 dark:text-slate-900 border border-transparent' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50'}`}>
                                Todo el Historial
                            </button>

                            {/* Personas Quick Select */}
                            {filters.cycle_id === shiftCycles[0]?.id && shiftMembers.length > 0 && (
                                <>
                                    <div className="w-px h-5 bg-slate-300 dark:bg-slate-700 mx-1 hidden sm:block"></div>
                                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1 hidden sm:block">Personas:</span>
                                    {shiftMembers.map(m => (
                                        <button key={m.user_id} onClick={() => {
                                                const newFilters = { ...filters, user_id: filters.user_id === m.user_id ? undefined : m.user_id };
                                                setFilters(newFilters); setApplied(newFilters);
                                            }}
                                            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all shadow-sm ${filters.user_id === m.user_id ? 'bg-indigo-600 text-white border-transparent' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'}`}>
                                            <Users className="w-3 h-3 opacity-70" /> {m.full_name.split(' ')[0]}
                                        </button>
                                    ))}
                                </>
                            )}

                            {/* Active Filter Chips */}
                            {Object.entries(filters).map(([k, v]) => {
                                if (!v || k === 'page' || k === 'per_page' || k === 'cycle_id' || k === 'user_id') return null;
                                const removeFilter = () => { const f = {...filters}; delete (f as any)[k]; setFilters(f); setApplied(f); };
                                let label = String(v);
                                if (k === 'category') label = CATEGORIES.find(c => c.id === v)?.label || String(v);
                                
                                return (
                                    <span key={k} className="flex items-center gap-1.5 bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 border border-sky-100 dark:border-sky-900/30 px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm animate-in zoom-in-95 duration-200">
                                        <span className="opacity-70 capitalize">{k.replace('_', ' ')}:</span> {label}
                                        <X className="w-3.5 h-3.5 cursor-pointer hover:text-red-500 transition-colors ml-1" onClick={removeFilter} />
                                    </span>
                                );
                            })}
                        </div>
                        
                        <button onClick={() => setShowAdvanced(!showAdvanced)} 
                                className={`flex items-center gap-2 px-4 py-1.5 rounded-full border shadow-sm text-xs font-bold transition-all whitespace-nowrap ${showAdvanced ? 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-200' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50'}`}>
                            <SlidersHorizontal className="w-3.5 h-3.5" /> Filtros Avanzados
                        </button>
                    </div>
                </div>

                {/* Advanced Filters Panel */}
                {showAdvanced && (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-lg animate-in slide-in-from-top-2 duration-200 overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
                        <div className="bg-slate-50/50 dark:bg-slate-800/50 px-5 py-3 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                            <span className="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest flex items-center gap-2">
                                <Filter className="w-3.5 h-3.5 text-sky-500" /> Búsqueda Específica
                            </span>
                            <button onClick={() => setShowAdvanced(false)} className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><X className="w-4 h-4" /></button>
                        </div>
                        <div className="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                            {/* Ciclo de turno */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Ciclo de Turno</label>
                                <select className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"
                                    value={filters.cycle_id ?? ''} onChange={e => setFilters(f => ({ ...f, cycle_id: e.target.value ? parseInt(e.target.value) : undefined }))}>
                                    <option value="">Cualquier ciclo</option>
                                    {shiftCycles.map(c => (
                                        <option key={c.id} value={c.id}>{c.group_alias} — {new Date(c.start_datetime).toLocaleDateString('es-CL')} → {c.end_datetime ? new Date(c.end_datetime).toLocaleDateString('es-CL') : 'Hoy'}</option>
                                    ))}
                                </select>
                            </div>

                            {/* Sub-turno */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Sub-Turno</label>
                                <div className="flex gap-2 h-[38px]">
                                    {(['', 'Día', 'Noche'] as const).map(st => (
                                        <button key={st} onClick={() => setFilters(f => ({ ...f, sub_shift: st as any }))}
                                            className={`flex-1 text-xs font-bold rounded-lg border shadow-sm transition-all ${(filters.sub_shift ?? '') === st ? 'bg-sky-600 text-white border-sky-600' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-300'}`}>
                                            {st || 'Ambos'}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Persona de turno */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1"><Users className="w-3 h-3"/>Persona de Turno</label>
                                <select className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"
                                    value={filters.user_id ?? ''} onChange={e => setFilters(f => ({ ...f, user_id: e.target.value ? parseInt(e.target.value) : undefined }))}>
                                    <option value="">Cualquier persona</option>
                                    {shiftMembers.map(m => (
                                        <option key={m.user_id} value={m.user_id}>{m.full_name} ({m.group_alias} – {m.sub_shift})</option>
                                    ))}
                                </select>
                            </div>

                            {/* Faena */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Faena</label>
                                <select className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm"
                                    value={filters.site_id ?? ''} onChange={e => setFilters(f => ({ ...f, site_id: e.target.value ? parseInt(e.target.value) : undefined }))}>
                                    <option value="">Todas las faenas</option>
                                    {[...new Map(alerts.map(a => [a.site_id, { id: a.site_id, name: a.site_name }])).values()].map(s => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                            </div>

                            {/* Fecha desde */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5 flex items-center gap-1"><Calendar className="w-3 h-3"/>Desde</label>
                                <input type="date" className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 shadow-sm"
                                    value={filters.date_from ?? ''} onChange={e => setFilters(f => ({ ...f, date_from: e.target.value || undefined }))} />
                            </div>

                            {/* Fecha hasta */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Hasta</label>
                                <input type="date" className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 shadow-sm"
                                    value={filters.date_to ?? ''} onChange={e => setFilters(f => ({ ...f, date_to: e.target.value || undefined }))} />
                            </div>

                            {/* Estado */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Estado</label>
                                <select className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 shadow-sm"
                                    value={filters.status ?? ''} onChange={e => setFilters(f => ({ ...f, status: e.target.value || undefined }))}>
                                    <option value="">Todos</option>
                                    <option value="solved">Resuelta</option>
                                    <option value="acknowledged">En revisión</option>
                                    <option value="active">Activa</option>
                                </select>
                            </div>

                            {/* Categoría */}
                            <div>
                                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Categoría</label>
                                <select className="w-full text-sm rounded-lg border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 shadow-sm"
                                    value={filters.category ?? ''} onChange={e => setFilters(f => ({ ...f, category: e.target.value || undefined }))}>
                                    <option value="">Cualquier Categoría</option>
                                    {CATEGORIES.map(cat => <option key={cat.id} value={cat.id}>{cat.label}</option>)}
                                </select>
                            </div>

                        </div>
                        
                        <div className="bg-slate-50 dark:bg-slate-800/80 px-5 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button onClick={() => { setFilters({ page: 1, per_page: 50 }); setApplied({ page: 1, per_page: 50 }); setShowAdvanced(false); }} className="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 px-3 py-1.5">
                                Limpiar
                            </button>
                            <button onClick={applyFilters} className="flex items-center justify-center gap-2 px-6 py-2 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-black tracking-wide uppercase rounded-lg shadow-md transition-all hover:scale-[1.02]">
                                <Search className="w-3.5 h-3.5" /> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                )}

                {/* Table */}
                <div className="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    {isLoading ? (
                        <div className="flex items-center justify-center py-20 text-slate-400">
                            <RefreshCw className="w-6 h-6 animate-spin mr-3" /> Cargando historial...
                        </div>
                    ) : alerts.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-20 text-slate-400 gap-3">
                            <CheckCircle className="w-12 h-12 opacity-20" />
                            <p className="font-semibold">No hay alertas para los filtros seleccionados</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                                        {['Faena','Servidor','Tipo','Descripción','Inicio','Revisado por','T. Respuesta','T. Resolución','Estado'].map(h => (
                                            <th key={h} className="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 whitespace-nowrap">{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {alerts.map((a: HistorialAlert) => (
                                        <tr key={a.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td className="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">{a.site_name ?? '—'}</td>
                                            <td className="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs whitespace-nowrap">
                                                {a.server_name} {a.server_ip && <span className="opacity-60 ml-1">({a.server_ip})</span>}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${getCategoryColor(a.category_id)}`}>
                                                    {a.category_label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 max-w-[240px]">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-slate-500 dark:text-slate-400 truncate" title={a.description}>{a.description}</span>
                                                    {a.metric_value && (
                                                        <span className="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/50" title={`Valor: ${a.metric_value}`}>
                                                            {a.metric_value}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-xs text-slate-500 whitespace-nowrap font-mono">{formatDt(a.created_at)}</td>
                                            <td className="px-4 py-3 text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">{a.acknowledged_by ?? <span className="text-slate-300">—</span>}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">{timeBadge(a.response_time_seconds, 'response')}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">{timeBadge(a.resolution_time_seconds, 'resolution')}</td>
                                            <td className="px-4 py-3 whitespace-nowrap">{statusPill(a.status)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    {meta && meta.total_pages > 1 && (
                        <div className="flex items-center justify-between px-5 py-3 border-t border-slate-100 dark:border-slate-800">
                            <p className="text-xs text-slate-500">
                                Mostrando {((meta.page - 1) * meta.per_page) + 1}–{Math.min(meta.page * meta.per_page, meta.total)} de {meta.total.toLocaleString('es-CL')} alertas
                            </p>
                            <div className="flex items-center gap-1">
                                <button disabled={meta.page <= 1} onClick={() => setPage(meta.page - 1)}
                                    className="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <ChevronLeft className="w-4 h-4" />
                                </button>
                                {Array.from({ length: Math.min(meta.total_pages, 7) }, (_, i) => {
                                    const pg = i + 1;
                                    return (
                                        <button key={pg} onClick={() => setPage(pg)}
                                            className={`w-8 h-8 rounded-lg text-xs font-bold transition-colors ${pg === meta.page ? 'bg-sky-600 text-white' : 'border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400'}`}>
                                            {pg}
                                        </button>
                                    );
                                })}
                                <button disabled={meta.page >= meta.total_pages} onClick={() => setPage(meta.page + 1)}
                                    className="p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <ChevronRight className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
