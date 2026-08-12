const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx';
let code = fs.readFileSync(file, 'utf8');

const startStr = `<div className="flex flex-col gap-6 relative z-10 w-full mb-6">`;
const endStr = `            {/* Resumen Tickets Turno 7x7 */}`;

const startIndex = code.indexOf(startStr);
const endIndex = code.indexOf(endStr);

if (startIndex === -1 || endIndex === -1) {
    console.log("Could not find bounds!");
    process.exit(1);
}

const replacement = `<div className="flex items-center bg-card border border-border/50 rounded-lg px-4 py-2 shadow-sm w-full gap-4 overflow-x-auto whitespace-nowrap scrollbar-hide relative z-10 mb-6">
    
    {/* Cycle Info */}
    <div className="flex items-center gap-3 border-r border-border/50 pr-4 shrink-0">
        <div className="flex items-center gap-1.5">
            <Clock className="w-4 h-4 text-primary animate-pulse" />
            <span className="font-black text-sm text-foreground tracking-tight">Turno 7x7</span>
        </div>
        <div className="flex flex-col w-28 gap-1">
            <div className="flex justify-between items-center text-[9px] font-bold">
                <span className="text-primary-foreground uppercase tracking-widest bg-primary px-1.5 py-[1px] rounded leading-none">Día {config.days_elapsed}/7</span>
                <span className="text-muted-foreground">{formatDate(start)} al {formatDate(end)}</span>
            </div>
            <div className="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div 
                    className={\`h-full transition-all duration-500 rounded-full \${config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'}\`}
                    style={{ width: \`\${percentElapsed}%\` }}
                ></div>
            </div>
        </div>
    </div>

    {/* Active Shift */}
    <div className="flex items-center gap-3 border-r border-border/50 pr-4 shrink-0">
        <span className="font-black text-sm uppercase flex items-center gap-1.5 text-foreground">
            <span className="relative flex h-2 w-2">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            {activeAlias} <span className="text-[9px] text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1 py-0.5 rounded ml-1">ACTIVO</span>
        </span>
        
        {/* Active Members */}
        <div className="flex items-center gap-1">
            <Sun className="w-3.5 h-3.5 text-amber-500 ml-1" />
            {activeMembers.filter(m => m.turno_tipo === 'Día' || !m.turno_tipo).map(m => (
                <div key={m.id} className="flex items-center gap-1 bg-background border border-border/50 rounded-full px-2 py-0.5 shadow-sm text-xs">
                    <span className="font-bold text-foreground">{m.first_name} {m.last_name.charAt(0)}.</span>
                    <div className="scale-[0.8] origin-left -my-1 ml-0.5 shrink-0"><ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} /></div>
                </div>
            ))}
            {activeMembers.filter(m => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && <span className="text-[10px] text-muted-foreground italic">Vacío</span>}
            
            <Moon className="w-3.5 h-3.5 text-purple-500 ml-3" />
            {activeMembers.filter(m => m.turno_tipo === 'Noche').map(m => (
                <div key={m.id} className="flex items-center gap-1 bg-background border border-border/50 rounded-full px-2 py-0.5 shadow-sm text-xs">
                    <span className="font-bold text-foreground">{m.first_name} {m.last_name.charAt(0)}.</span>
                    <div className="scale-[0.8] origin-left -my-1 ml-0.5 shrink-0"><ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} /></div>
                </div>
            ))}
            {activeMembers.filter(m => m.turno_tipo === 'Noche').length === 0 && <span className="text-[10px] text-muted-foreground italic">Vacío</span>}
        </div>
    </div>

    {/* Inactive Shift */}
    <div className="flex items-center gap-3 border-r border-border/50 pr-4 shrink-0 opacity-80">
        <span className="font-black text-sm uppercase text-muted-foreground flex items-center gap-1.5">
            <span className="h-2 w-2 rounded-full bg-slate-400"></span>
            {inactiveAlias} <span className="text-[9px] bg-muted dark:bg-muted/30 px-1 py-0.5 rounded ml-1">DESCANSO</span>
        </span>
        
        {/* Inactive Members */}
        <div className="flex items-center gap-1">
            <Sun className="w-3.5 h-3.5 text-slate-400 ml-1" />
            {inactiveMembers.filter(m => m.turno_tipo === 'Día' || !m.turno_tipo).map(m => (
                <div key={m.id} className="flex items-center gap-1 bg-muted/50 rounded-full px-2 py-0.5 text-xs text-muted-foreground border border-border/50">
                    <span className="font-bold">{m.first_name} {m.last_name.charAt(0)}.</span>
                    <Home className="w-3 h-3 ml-0.5" />
                </div>
            ))}
            
            <Moon className="w-3.5 h-3.5 text-slate-400 ml-3" />
            {inactiveMembers.filter(m => m.turno_tipo === 'Noche').map(m => (
                <div key={m.id} className="flex items-center gap-1 bg-muted/50 rounded-full px-2 py-0.5 text-xs text-muted-foreground border border-border/50">
                    <span className="font-bold">{m.first_name} {m.last_name.charAt(0)}.</span>
                    <Home className="w-3 h-3 ml-0.5" />
                </div>
            ))}
        </div>
    </div>

    {/* Handoff Button (El Switch) */}
    <div className="shrink-0 flex-1 flex justify-end">
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

</div>\n\n`;

const newCode = code.substring(0, startIndex) + replacement + code.substring(endIndex);
fs.writeFileSync(file, newCode);
console.log("Successfully transformed into a SINGLE THIN DIV!");
