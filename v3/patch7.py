import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Fix globalStats
globalStats_str = """    const globalStats = {
        working: tabTickets.filter(t => t.Status?.toLowerCase() === 'working').length,
        seeking: tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length,
        assigned: tabTickets.filter(t => t.Status?.toLowerCase() === 'assigned').length,
        closed: tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length,
        queue: tabTickets.filter(t => t.Status?.toLowerCase() !== 'closed' && (t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue'))).length,
    };"""

new_globalStats_str = """    const globalStats = (() => {
        const working = tabTickets.filter(t => t.Status?.toLowerCase() === 'working').length;
        const seeking = tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length;
        const assigned = tabTickets.filter(t => t.Status?.toLowerCase() === 'assigned').length;
        const queue = tabTickets.filter(t => t.Status?.toLowerCase() !== 'closed' && (t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue'))).length;
        
        // El numero de tickets closed es la resta del total historico menos los activos actuales si estamos en 'all'
        const activeOrOpenCount = tabTickets.filter(t => t.Status?.toLowerCase() !== 'closed').length;
        const closed = activeTab === 'all' 
            ? Math.max(0, historyTotalItems - activeOrOpenCount)
            : tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length;
            
        return { working, seeking, assigned, closed, queue };
    })();"""

content = content.replace(globalStats_str, new_globalStats_str)


# 2. Fix percentages in Donut Chart Legend
legend_str = """                                                <div className="w-full mt-2 flex flex-wrap justify-center gap-x-3 gap-y-1.5 text-[9px] border-t border-slate-100 dark:border-slate-800/40 pt-2">
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                                        WORKING: <span className="text-foreground font-black">{stats.open} ({stats.total > 0 ? Math.round((stats.open / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                        SEEKING: <span className="text-foreground font-black">{stats.seeking} ({stats.total > 0 ? Math.round((stats.seeking / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        CLOSED: <span className="text-foreground font-black">{stats.closed} ({stats.total > 0 ? Math.round((stats.closed / stats.total) * 100) : 0}%)</span>
                                                    </span>
                                                </div>"""

new_legend_str = """                                                <div className="w-full mt-2 flex flex-wrap justify-center gap-x-3 gap-y-1.5 text-[9px] border-t border-slate-100 dark:border-slate-800/40 pt-2">
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                                        WORKING: <span className="text-foreground font-black">{stats.open} ({stats.total > 0 ? ((stats.open / stats.total) * 100).toFixed(1) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                        SEEKING: <span className="text-foreground font-black">{stats.seeking} ({stats.total > 0 ? ((stats.seeking / stats.total) * 100).toFixed(1) : 0}%)</span>
                                                    </span>
                                                    <span className="flex items-center gap-1 text-muted-foreground font-bold">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                        CLOSED: <span className="text-foreground font-black">{stats.closed} ({stats.total > 0 ? ((stats.closed / stats.total) * 100).toFixed(1) : 0}%)</span>
                                                    </span>
                                                </div>"""

content = content.replace(legend_str, new_legend_str)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
