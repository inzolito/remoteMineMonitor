import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

start_str = "            {/* Unified Control Panel: general info and rosters at the top */}"
end_str = "            {/* Global Stats Bar */}"

idx_start = content.find(start_str)
idx_end = content.find(end_str)

new_panel = """            {/* Unified Control Panel: general info and rosters at the top (COMPACT) */}
            <div className="flex flex-col xl:flex-row items-center bg-card border border-border/50 rounded-2xl shadow-sm w-full divide-y xl:divide-y-0 xl:divide-x divide-border/40 relative z-10">
                
                {/* Ciclo y Progreso (Compact) */}
                <div className="flex items-center gap-4 p-3 shrink-0 w-full xl:w-auto">
                    <div className="flex items-center gap-3">
                        <div className="bg-primary/10 p-2 rounded-xl border border-primary/20">
                            <Clock className="w-5 h-5 text-primary animate-pulse" />
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[9px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-1">
                                <Calendar className="w-3 h-3" />
                                {formatDate(start)} al {formatDate(end)}
                            </span>
                            <span className="text-sm font-black text-foreground">Soporte Turno 7x7</span>
                        </div>
                    </div>
                    
                    <div className="flex flex-col gap-1.5 ml-auto xl:ml-4 min-w-[120px]">
                        <div className="flex justify-between items-center text-[9px] font-bold uppercase tracking-wider">
                            <span className="text-primary-foreground bg-primary px-1 rounded shadow-sm">Día {config.days_elapsed} de 7</span>
                            <span className="text-muted-foreground">{7 - config.days_elapsed > 0 ? `Faltan ${7 - config.days_elapsed}` : 'Rotación hoy'}</span>
                        </div>
                        <div className="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden shadow-inner">
                            <div 
                                className={`h-full transition-all duration-500 rounded-full ${config.days_elapsed > 7 ? 'bg-rose-600 animate-pulse' : 'bg-gradient-to-r from-blue-500 to-indigo-600'}`}
                                style={{ width: `${percentElapsed}%` }}
                            ></div>
                        </div>
                    </div>
                </div>

                {/* Turno Activo (Compact) */}
                <div className="flex flex-col flex-1 p-3 w-full xl:w-auto">
                    <div className="flex items-center gap-2 mb-2">
                        <span className="relative flex h-1.5 w-1.5">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                        <span className="text-[10px] font-black uppercase tracking-widest text-foreground">{activeAlias} <span className="text-emerald-500 dark:text-emerald-400 ml-1">(Activo)</span></span>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        {/* Día */}
                        <div className="flex flex-col gap-1.5 border-r border-border/40 pr-3">
                            <div className="text-[8px] font-black uppercase text-amber-600 flex items-center gap-1">
                                <Sun className="w-3 h-3" /> Día
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {activeMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m: any) => (
                                    <div key={m.id} className="flex items-center gap-1.5 bg-muted/40 rounded-md px-1.5 py-1 border border-border/50">
                                        <div className="w-4 h-4 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-black text-[7px] uppercase">
                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                        </div>
                                        <span className="text-[10px] font-bold truncate max-w-[70px]">{m.first_name} {m.last_name.charAt(0)}.</span>
                                        <div className="scale-75 origin-left"><ShiftStatusEye isWorking={isMemberWorking(true, 'Día')} /></div>
                                    </div>
                                ))}
                                {activeMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && <span className="text-[9px] italic text-muted-foreground">Sin asignar</span>}
                            </div>
                        </div>
                        {/* Noche */}
                        <div className="flex flex-col gap-1.5">
                            <div className="text-[8px] font-black uppercase text-purple-600 flex items-center gap-1">
                                <Moon className="w-3 h-3" /> Noche
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {activeMembers.filter((m: any) => m.turno_tipo === 'Noche').map((m: any) => (
                                    <div key={m.id} className="flex items-center gap-1.5 bg-muted/40 rounded-md px-1.5 py-1 border border-border/50">
                                        <div className="w-4 h-4 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-[7px] uppercase">
                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                        </div>
                                        <span className="text-[10px] font-bold truncate max-w-[70px]">{m.first_name} {m.last_name.charAt(0)}.</span>
                                        <div className="scale-75 origin-left"><ShiftStatusEye isWorking={isMemberWorking(true, 'Noche')} /></div>
                                    </div>
                                ))}
                                {activeMembers.filter((m: any) => m.turno_tipo === 'Noche').length === 0 && <span className="text-[9px] italic text-muted-foreground">Sin asignar</span>}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Turno Descanso (Compact) */}
                <div className="flex flex-col flex-1 p-3 opacity-60 hover:opacity-100 transition-opacity w-full xl:w-auto">
                    <div className="flex items-center gap-2 mb-2">
                        <span className="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                        <span className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">{inactiveAlias} <span className="ml-1">(Descanso)</span></span>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        {/* Día */}
                        <div className="flex flex-col gap-1.5 border-r border-border/40 pr-3">
                            <div className="text-[8px] font-black uppercase text-slate-400 flex items-center gap-1">
                                <Sun className="w-3 h-3" /> Día
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {inactiveMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).map((m: any) => (
                                    <div key={m.id} className="flex items-center gap-1.5 bg-muted/20 rounded-md px-1.5 py-1 border border-border/30">
                                        <div className="w-4 h-4 rounded-full bg-slate-400 flex items-center justify-center text-white font-black text-[7px] uppercase">
                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                        </div>
                                        <span className="text-[10px] font-bold truncate max-w-[70px]">{m.first_name} {m.last_name.charAt(0)}.</span>
                                    </div>
                                ))}
                                {inactiveMembers.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && <span className="text-[9px] italic text-muted-foreground">Sin asignar</span>}
                            </div>
                        </div>
                        {/* Noche */}
                        <div className="flex flex-col gap-1.5">
                            <div className="text-[8px] font-black uppercase text-slate-400 flex items-center gap-1">
                                <Moon className="w-3 h-3" /> Noche
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {inactiveMembers.filter((m: any) => m.turno_tipo === 'Noche').map((m: any) => (
                                    <div key={m.id} className="flex items-center gap-1.5 bg-muted/20 rounded-md px-1.5 py-1 border border-border/30">
                                        <div className="w-4 h-4 rounded-full bg-slate-400 flex items-center justify-center text-white font-black text-[7px] uppercase">
                                            {m.first_name.charAt(0)}{m.last_name.charAt(0)}
                                        </div>
                                        <span className="text-[10px] font-bold truncate max-w-[70px]">{m.first_name} {m.last_name.charAt(0)}.</span>
                                    </div>
                                ))}
                                {inactiveMembers.filter((m: any) => m.turno_tipo === 'Noche').length === 0 && <span className="text-[9px] italic text-muted-foreground">Sin asignar</span>}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

"""
if idx_start != -1 and idx_end != -1:
    content = content[:idx_start] + new_panel + content[idx_end:]
    with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
        f.write(content)
    print("Patched successfully")
else:
    print("Could not find boundaries")
