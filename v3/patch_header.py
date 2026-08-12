import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

start_str = "            {/* Unified Control Panel: general info and rosters at the top */}"
end_str = "            {/* Tab Navigation */}"

idx_start = content.find(start_str)
idx_end = content.find(end_str)

new_header = """            {/* Unified Control Panel: general info and rosters at the top */}
            <div className="flex items-center bg-card border border-border/50 rounded-lg px-4 py-2 shadow-sm w-full gap-6 overflow-x-auto whitespace-nowrap scrollbar-hide relative z-10 mb-6">
                
                {/* Ciclo y Progreso (Compact) */}
                <div className="flex items-center gap-4 border-r border-border/40 pr-6 shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary/10 p-2 rounded-lg border border-primary/20">
                            <Clock className="w-5 h-5 text-primary animate-pulse" />
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Ciclo Activo</span>
                            <span className="text-sm font-black text-foreground">{config.shift1_alias} / {config.shift2_alias}</span>
                        </div>
                    </div>
                    
                    <div className="flex flex-col gap-1 ml-4 min-w-[120px]">
                        <div className="flex justify-between items-center text-[9px] font-bold uppercase tracking-wider">
                            <span className="text-primary">Día {config.days_elapsed} de 7</span>
                            <span className="text-muted-foreground">{7 - config.days_elapsed > 0 ? `Faltan ${7 - config.days_elapsed}` : 'Rotación hoy'}</span>
                        </div>
                        <div className="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div 
                                className={`h-full transition-all duration-500 rounded-full ${config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'}`}
                                style={{ width: `${percentElapsed}%` }}
                            ></div>
                        </div>
                    </div>
                </div>

                {/* Turno A (Compact) */}
                <div className="flex items-center gap-4 border-r border-border/40 pr-6 shrink-0">
                    <div className="flex flex-col">
                        <div className="flex items-center gap-2 mb-1.5">
                            <span className="text-[10px] font-black uppercase tracking-wider text-foreground">{config.shift1_alias}</span>
                            {config.active_shift === 1 && (
                                <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded-sm uppercase">Activo</span>
                            )}
                        </div>
                        <div className="flex items-center gap-4 text-[10px]">
                            <div className="flex items-center gap-1.5">
                                <Sun className="w-3 h-3 text-amber-500" />
                                <span className="text-muted-foreground font-medium">{shift1_members.find(m => m.base_sub_shift === 'Día')?.first_name || 'Sin Asignar'}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <Moon className="w-3 h-3 text-indigo-400" />
                                <span className="text-muted-foreground font-medium">{shift1_members.find(m => m.base_sub_shift === 'Noche')?.first_name || 'Sin Asignar'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Turno B (Compact) */}
                <div className="flex items-center gap-4 shrink-0">
                    <div className="flex flex-col">
                        <div className="flex items-center gap-2 mb-1.5">
                            <span className="text-[10px] font-black uppercase tracking-wider text-foreground">{config.shift2_alias}</span>
                            {config.active_shift === 2 && (
                                <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded-sm uppercase">Activo</span>
                            )}
                        </div>
                        <div className="flex items-center gap-4 text-[10px]">
                            <div className="flex items-center gap-1.5">
                                <Sun className="w-3 h-3 text-amber-500" />
                                <span className="text-muted-foreground font-medium">{shift2_members.find(m => m.base_sub_shift === 'Día')?.first_name || 'Sin Asignar'}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <Moon className="w-3 h-3 text-indigo-400" />
                                <span className="text-muted-foreground font-medium">{shift2_members.find(m => m.base_sub_shift === 'Noche')?.first_name || 'Sin Asignar'}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

"""
if idx_start != -1 and idx_end != -1:
    content = content[:idx_start] + new_header + content[idx_end:]
    with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
        f.write(content)
    print("Patched successfully")
else:
    print("Could not find boundaries")
