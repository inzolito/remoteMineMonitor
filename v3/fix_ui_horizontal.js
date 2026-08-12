const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx';
let code = fs.readFileSync(file, 'utf8');

const startStr = `<div className="grid grid-cols-1 lg:grid-cols-3 gap-4 relative z-10 items-start">`;
const endStr = `</div>\n                </div>\n            </div>\n\n            {/* Resumen Tickets Turno 7x7 */}`;

const startIndex = code.indexOf(startStr);
const endIndex = code.indexOf(endStr);

if (startIndex === -1 || endIndex === -1) {
    console.log("Could not find bounds!");
    process.exit(1);
}

const replacement = `<div className="flex flex-col 2xl:flex-row gap-4 relative z-10 items-start 2xl:items-center justify-between w-full">
                    
                    {/* Part 1: Ciclo y Progreso */}
                    <div className="flex flex-wrap sm:flex-nowrap items-center gap-4 2xl:pr-4 2xl:border-r border-border/40 shrink-0 w-full 2xl:w-auto">
                        <div className="flex items-center gap-2">
                            <div className="bg-primary/10 p-1.5 rounded-lg border border-primary/20">
                                <Clock className="w-4 h-4 text-primary animate-pulse" />
                            </div>
                            <div className="flex flex-col">
                                <h1 className="text-sm font-black text-foreground tracking-tight leading-none">Turno 7x7</h1>
                                <span className="text-[9px] text-muted-foreground mt-0.5">Mié a Mar</span>
                            </div>
                        </div>
                        <div className="flex flex-col gap-1 min-w-[150px] flex-1">
                            <div className="flex justify-between items-center text-[8px] font-bold">
                                <span className="text-primary-foreground font-black uppercase tracking-widest bg-primary px-1 py-0.5 rounded leading-none">
                                    Día {config.days_elapsed}/7
                                </span>
                                <span className="text-muted-foreground">{formatDate(start)} al {formatDate(end)}</span>
                            </div>
                            <div className="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div 
                                    className={\`h-full transition-all duration-500 rounded-full \${
                                        config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'
                                    }\`}
                                    style={{ width: \`\${percentElapsed}%\` }}
                                ></div>
                            </div>
                        </div>
                    </div>

                    {/* Part 2: Turno Activo */}
                    <div className="flex flex-col lg:flex-row items-start lg:items-center gap-4 flex-1 2xl:px-4 2xl:border-r border-border/40 w-full 2xl:w-auto">
                        <div className="flex flex-col shrink-0 gap-1 min-w-[120px]">
                            <div className="flex items-center gap-1.5">
                                <span className="relative flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span className="text-[11px] font-black text-foreground uppercase tracking-wider leading-none">{activeAlias}</span>
                            </div>
                            <div className="flex items-center gap-1">
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
                                <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 dark:bg-emerald-500/20 px-1 py-0.5 rounded uppercase tracking-wider leading-none">Activo</span>
                            </div>
                        </div>
                        
                        <div className="flex flex-wrap gap-2 items-center flex-1">
                            {activeMembers.map((m: ShiftMember) => {
                                const isDay = m.turno_tipo === 'Día' || !m.turno_tipo;
                                return (
                                <div key={m.id} className="flex items-center gap-1 bg-background dark:bg-slate-800/50 border border-border/50 pl-1 pr-1.5 py-0.5 rounded-full shadow-sm">
                                    <div className={\`w-4 h-4 rounded-full flex items-center justify-center text-white font-black text-[7px] uppercase shrink-0 \${isDay ? 'bg-gradient-to-br from-amber-400 to-orange-500' : 'bg-gradient-to-br from-purple-500 to-indigo-600'}\`}>
                                        {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                    </div>
                                    <span className="font-bold text-foreground truncate max-w-full text-[9px] leading-none" title={\`\${m.first_name} \${m.last_name}\`}>
                                        {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                    </span>
                                    <div className="flex items-center ml-0.5">
                                        {isDay ? <Sun className="w-2.5 h-2.5 text-amber-500" /> : <Moon className="w-2.5 h-2.5 text-purple-500" />}
                                    </div>
                                    <div className="scale-[0.8] origin-left -my-1 shrink-0 ml-0.5"><ShiftStatusEye isWorking={isMemberWorking(true, isDay ? 'Día' : 'Noche')} /></div>
                                </div>
                                );
                            })}
                            {activeMembers.length === 0 && <span className="text-[9px] text-muted-foreground italic">Vacío</span>}
                        </div>
                    </div>

                    {/* Part 3: Turno en Descanso */}
                    <div className="flex flex-col lg:flex-row items-start lg:items-center gap-4 shrink-0 2xl:pl-4 opacity-80 w-full 2xl:w-auto">
                        <div className="flex flex-col shrink-0 gap-1 min-w-[80px]">
                            <div className="flex items-center gap-1.5">
                                <span className="h-2 w-2 rounded-full bg-slate-400"></span>
                                <span className="text-[11px] font-black text-muted-foreground uppercase tracking-wider leading-none">{inactiveAlias}</span>
                            </div>
                            <span className="text-[8px] font-black text-muted-foreground bg-muted dark:bg-muted/20 px-1 py-0.5 rounded uppercase tracking-wider leading-none w-fit">Descanso</span>
                        </div>
                        
                        <div className="flex flex-wrap gap-2 items-center">
                            {inactiveMembers.map((m: ShiftMember) => {
                                const isDay = m.turno_tipo === 'Día' || !m.turno_tipo;
                                return (
                                <div key={m.id} className="flex items-center gap-1 bg-background dark:bg-slate-800/50 border border-border/50 pl-1 pr-1.5 py-0.5 rounded-full shadow-sm">
                                    <div className={\`w-4 h-4 rounded-full flex items-center justify-center text-white font-black text-[7px] uppercase shrink-0 bg-gradient-to-br from-slate-400 to-slate-500\`}>
                                        {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                    </div>
                                    <span className="font-bold text-foreground truncate max-w-full text-[9px] leading-none" title={\`\${m.first_name} \${m.last_name}\`}>
                                        {m.first_name.split(' ')[0]} {m.last_name.charAt(0)}.
                                    </span>
                                    <div className="flex items-center ml-0.5">
                                        {isDay ? <Sun className="w-2.5 h-2.5 text-slate-400" /> : <Moon className="w-2.5 h-2.5 text-slate-400" />}
                                    </div>
                                    <Home className="w-2.5 h-2.5 text-slate-400 ml-0.5 shrink-0" />
                                </div>
                                );
                            })}
                            {inactiveMembers.length === 0 && <span className="text-[9px] text-muted-foreground italic">Vacío</span>}
                        </div>
                    </div>`;

const newCode = code.substring(0, startIndex) + replacement + code.substring(endIndex);
fs.writeFileSync(file, newCode);
console.log("Applied horizontal layout!");
