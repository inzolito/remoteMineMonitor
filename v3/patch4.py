import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Remove the old stats block from renderTicketsTable
old_stats_block_start = '            <div className="flex flex-col w-full">\n                {activeTab === \'all\' && (\n                    <div className="grid grid-cols-4 gap-4 p-4 border-b border-border/40 bg-card rounded-t-xl">'
idx_start = content.find(old_stats_block_start)
if idx_start != -1:
    idx_table_start = content.find('                <table className="w-full text-left border-collapse">', idx_start)
    if idx_table_start != -1:
        content = content[:idx_start] + '            <table className="w-full text-left border-collapse">' + content[idx_table_start + len('                <table className="w-full text-left border-collapse">'):]

table_end_str = '            </table>\n            </div>'
content = content.replace(table_end_str, '            </table>')

# 2. Add the global stats computation and the new awesome div
panel_end_str = '            </div>\n\n            {/* Tickets Grid */}'

stats_logic = """
    const globalStats = {
        working: tabTickets.filter(t => t.Status?.toLowerCase() === 'working').length,
        seeking: tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length,
        assigned: tabTickets.filter(t => t.Status?.toLowerCase() === 'assigned').length,
        closed: tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length,
        queue: tabTickets.filter(t => t.Status?.toLowerCase() !== 'closed' && (t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue'))).length,
    };
"""

new_div = """            </div>

            {/* Global Stats Bar */}
            <div className="w-full bg-gradient-to-r from-card via-card to-card/50 border border-border/50 rounded-2xl shadow-sm p-4 relative overflow-hidden mb-6 mt-6">
                <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                <div className="absolute bottom-0 left-10 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 relative z-10">
                    <div className="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 flex flex-col items-center justify-center transition-all hover:scale-105 hover:bg-indigo-500/15">
                        <span className="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Working</span>
                        <span className="text-2xl font-black text-foreground mt-1">{globalStats.working}</span>
                    </div>
                    <div className="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex flex-col items-center justify-center transition-all hover:scale-105 hover:bg-amber-500/15">
                        <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Seeking</span>
                        <span className="text-2xl font-black text-foreground mt-1">{globalStats.seeking}</span>
                    </div>
                    <div className="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 flex flex-col items-center justify-center transition-all hover:scale-105 hover:bg-blue-500/15">
                        <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Assigned</span>
                        <span className="text-2xl font-black text-foreground mt-1">{globalStats.assigned}</span>
                    </div>
                    <div className="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 flex flex-col items-center justify-center transition-all hover:scale-105 hover:bg-emerald-500/15">
                        <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 text-center leading-tight">Closed<br/>(Ciclo)</span>
                        <span className="text-2xl font-black text-foreground mt-1">{globalStats.closed}</span>
                    </div>
                    <div className="bg-slate-100 dark:bg-slate-800 border border-border/50 rounded-xl p-3 flex flex-col items-center justify-center transition-all hover:scale-105 hover:bg-slate-200 dark:hover:bg-slate-700">
                        <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 text-center leading-tight">Total<br/>Histórico</span>
                        <span className="text-2xl font-black text-foreground mt-1">{activeTab === 'all' ? historyTotalItems : globalStats.working + globalStats.seeking + globalStats.assigned + globalStats.closed}</span>
                    </div>
                    <div className="bg-rose-500/10 border-2 border-rose-500/40 rounded-xl p-3 flex flex-col items-center justify-center relative overflow-hidden transition-all hover:scale-105 hover:bg-rose-500/20 shadow-[0_0_20px_rgba(244,63,94,0.15)] group">
                        <div className="absolute top-0 right-0 w-16 h-16 bg-rose-500/20 rounded-full blur-xl pointer-events-none group-hover:scale-150 transition-all"></div>
                        <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 text-center leading-tight">South American<br/>Support Q</span>
                        <span className="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{globalStats.queue}</span>
                    </div>
                </div>
            </div>

            {/* Tickets Grid */}"""

content = content.replace(panel_end_str, new_div)

idx_percent = content.find("    const percentElapsed = Math.min(100, Math.max(0, (config.days_elapsed / 7) * 100));")
if idx_percent != -1:
    content = content[:idx_percent] + stats_logic + "\n" + content[idx_percent:]

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
