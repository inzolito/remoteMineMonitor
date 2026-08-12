const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx';
let code = fs.readFileSync(file, 'utf8');

const startStr = `<div className="bg-gradient-to-br from-card to-muted/40 rounded-2xl border border-border p-4 shadow-sm relative overflow-hidden">`;
const endStr = `            {/* Resumen Tickets Turno 7x7 */}`;

const startIndex = code.indexOf(startStr);
const endIndex = code.indexOf(endStr);

if (startIndex === -1 || endIndex === -1) {
    console.log("Could not find bounds!");
    process.exit(1);
}

const replacement = `<div className="flex flex-col gap-6 relative z-10 w-full mb-6">
    {/* Cycle Info Bar */}
    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-card border border-border rounded-xl p-4 sm:px-6 shadow-sm gap-4 relative overflow-hidden">
        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div className="flex items-center gap-4 relative z-10">
            <div className="bg-primary/10 p-2.5 rounded-xl border border-primary/20">
                <Clock className="w-5 h-5 text-primary animate-pulse" />
            </div>
            <div>
                <h1 className="text-lg sm:text-xl font-black text-foreground tracking-tight leading-none mb-1.5">Soporte Turno 7x7</h1>
                <div className="flex items-center gap-1.5">
                    <Calendar className="w-4 h-4 text-muted-foreground" />
                    <span className="text-xs text-muted-foreground font-semibold">{formatDate(start)} al {formatDate(end)}</span>
                </div>
            </div>
        </div>
        
        <div className="flex flex-col gap-2 w-full sm:w-72 relative z-10">
            <div className="flex justify-between items-center text-xs font-bold">
                <span className="text-primary-foreground font-black uppercase tracking-widest bg-primary px-2 py-0.5 rounded leading-none">
                    Día {config.days_elapsed} de 7
                </span>
                <span className="text-muted-foreground">{7 - config.days_elapsed > 0 ? \`Faltan \${7 - config.days_elapsed} días\` : 'Rotación automática hoy'}</span>
            </div>
            <div className="w-full bg-slate-200 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                <div 
                    className={\`h-full transition-all duration-500 rounded-full \${config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'}\`}
                    style={{ width: \`\${percentElapsed}%\` }}
                ></div>
            </div>
        </div>
    </div>

    {/* Shifts Area */}
    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        {/* Active Shift Card */}
        <div className="bg-card dark:bg-card/60 border-2 border-emerald-500/30 rounded-xl p-5 shadow-sm relative overflow-hidden flex flex-col gap-5">
            <div className="absolute top-0 right-0 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div className="flex flex-col sm:flex-row sm:items-center justify-between border-b border-border/40 pb-4 gap-4 relative z-10">
                <div className="flex items-center gap-3">
                    <span className="relative flex h-3.5 w-3.5">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                    </span>
                    <div>
                        <h2 className="text-lg font-black text-foreground uppercase tracking-wider leading-none mb-1">{activeAlias}</h2>
                        <span className="text-xs text-emerald-600 dark:text-emerald-400 font-black uppercase tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded">En Turno Activo</span>
                    </div>
                </div>
                
                <div className="shrink-0">
                    <SubShiftHandoff 
                        openTickets={active_tickets
                            .filter(t => t.Status?.toLowerCase() !== 'closed')
                            .map(t => ({
                                case_id:    t.CaseId,
                                case_number: t.CaseNumber,
                                status:     t.Status,
                                subject:    t.Subject,
                                faena:      t.Faena,
                                description: t.Description,
                                comment_count: t.CommentCount,
                                comments:   t.Comments || [],
                                created_date: t.CreatedDate,
                            }))}
                        isActiveGroup={true} 
                        cycleDay={config.days_elapsed} 
                        activeMembers={activeMembers} 
                    />
                </div>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 relative z-10">
                {/* Día */}
                <div className="flex flex-col gap-3 bg-muted/30 rounded-xl p-4 border border-border/50">
                    <div className="text-xs font-black uppercase text-amber-600 flex items-center gap-2">
                        <Sun className="w-4 h-4" /> Turno Día
                    </div>
                    <div className="flex flex-col gap-3">
                        {activeMembers.filter((m) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m) => (
                            <div key={m.id} className="flex items-center gap-3 bg-background dark:bg-slate-800/80 p-2 rounded-lg border border-border/50 shadow-sm">
                                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-black text-xs uppercase shrink-0">
                                    {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                </div>
                                <span className="font-bold text-foreground text-sm flex-1 truncate" title={\`\${m.first_name} \${m.last_name}\`}>
                                    {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                </span>
                                <ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} />
                            </div>
                        ))}
                        {activeMembers.filter((m) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                            <span className="text-xs text-muted-foreground italic px-2">Sin ingenieros asignados</span>
                        )}
                    </div>
                </div>
                
                {/* Noche */}
                <div className="flex flex-col gap-3 bg-muted/30 rounded-xl p-4 border border-border/50">
                    <div className="text-xs font-black uppercase text-purple-600 flex items-center gap-2">
                        <Moon className="w-4 h-4" /> Turno Noche
                    </div>
                    <div className="flex flex-col gap-3">
                        {activeMembers.filter((m) => m.turno_tipo === 'Noche').map((m) => (
                            <div key={m.id} className="flex items-center gap-3 bg-background dark:bg-slate-800/80 p-2 rounded-lg border border-border/50 shadow-sm">
                                <div className="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-xs uppercase shrink-0">
                                    {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                </div>
                                <span className="font-bold text-foreground text-sm flex-1 truncate" title={\`\${m.first_name} \${m.last_name}\`}>
                                    {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                </span>
                                <ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} />
                            </div>
                        ))}
                        {activeMembers.filter((m) => m.turno_tipo === 'Noche').length === 0 && (
                            <span className="text-xs text-muted-foreground italic px-2">Sin ingenieros asignados</span>
                        )}
                    </div>
                </div>
            </div>
        </div>

        {/* Inactive Shift Card */}
        <div className="bg-card/60 dark:bg-card/40 border border-border/60 rounded-xl p-5 shadow-sm relative overflow-hidden flex flex-col gap-5 opacity-80">
            <div className="flex items-center justify-between border-b border-border/40 pb-4">
                <div className="flex items-center gap-3">
                    <span className="h-3.5 w-3.5 rounded-full bg-slate-400"></span>
                    <div>
                        <h2 className="text-lg font-black text-foreground uppercase tracking-wider leading-none mb-1">{inactiveAlias}</h2>
                        <span className="text-xs text-muted-foreground font-black uppercase tracking-widest bg-muted dark:bg-muted/30 px-2 py-0.5 rounded">En Descanso</span>
                    </div>
                </div>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Día */}
                <div className="flex flex-col gap-3">
                    <div className="text-xs font-black uppercase text-slate-500 flex items-center gap-2">
                        <Sun className="w-4 h-4 text-slate-400" /> Turno Día
                    </div>
                    <div className="flex flex-col gap-3">
                        {inactiveMembers.filter((m) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m) => (
                            <div key={m.id} className="flex items-center gap-3 p-2">
                                <div className="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 font-black text-xs uppercase shrink-0">
                                    {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                </div>
                                <span className="font-bold text-muted-foreground text-sm flex-1 truncate" title={\`\${m.first_name} \${m.last_name}\`}>
                                    {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                </span>
                                <Home className="w-4 h-4 text-slate-400 shrink-0" />
                            </div>
                        ))}
                        {inactiveMembers.filter((m) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                            <span className="text-xs text-muted-foreground italic px-2">Sin ingenieros</span>
                        )}
                    </div>
                </div>
                
                {/* Noche */}
                <div className="flex flex-col gap-3">
                    <div className="text-xs font-black uppercase text-slate-500 flex items-center gap-2">
                        <Moon className="w-4 h-4 text-slate-400" /> Turno Noche
                    </div>
                    <div className="flex flex-col gap-3">
                        {inactiveMembers.filter((m) => m.turno_tipo === 'Noche').map((m) => (
                            <div key={m.id} className="flex items-center gap-3 p-2">
                                <div className="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 font-black text-xs uppercase shrink-0">
                                    {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                </div>
                                <span className="font-bold text-muted-foreground text-sm flex-1 truncate" title={\`\${m.first_name} \${m.last_name}\`}>
                                    {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                </span>
                                <Home className="w-4 h-4 text-slate-400 shrink-0" />
                            </div>
                        ))}
                        {inactiveMembers.filter((m) => m.turno_tipo === 'Noche').length === 0 && (
                            <span className="text-xs text-muted-foreground italic px-2">Sin ingenieros</span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>\n\n`;

const newCode = code.substring(0, startIndex) + replacement + code.substring(endIndex);
fs.writeFileSync(file, newCode);
console.log("Successfully redesigned the UI to an organized card layout!");
