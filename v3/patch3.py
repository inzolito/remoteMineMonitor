import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Add historyTotalItems state
content = content.replace(
    "const [historyTotalPages, setHistoryTotalPages] = useState(1);",
    "const [historyTotalPages, setHistoryTotalPages] = useState(1);\n    const [historyTotalItems, setHistoryTotalItems] = useState(0);"
)

# 2. Update fetchHistoricalTickets to set historyTotalItems
content = content.replace(
    "setHistoryTotalPages(json.total_pages || 1);",
    "setHistoryTotalPages(json.total_pages || 1);\n                setHistoryTotalItems(json.total_items || 0);"
)

# 3. Modify renderTicketsTable to include the Totals row at the top
table_start_str = '            <table className="w-full text-left border-collapse">'
new_table_start = """            <div className="flex flex-col w-full">
                {activeTab === 'all' && (
                    <div className="grid grid-cols-4 gap-4 p-4 border-b border-border/40 bg-card rounded-t-xl">
                        <div className="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 flex flex-col items-center justify-center relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-16 h-16 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>
                            <span className="text-[10px] font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Working</span>
                            <span className="text-2xl font-black text-foreground mt-1">{stats.open}</span>
                        </div>
                        <div className="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 flex flex-col items-center justify-center relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-16 h-16 bg-amber-500/20 rounded-full blur-2xl pointer-events-none"></div>
                            <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Seeking</span>
                            <span className="text-2xl font-black text-foreground mt-1">{stats.seeking}</span>
                        </div>
                        <div className="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 flex flex-col items-center justify-center relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-16 h-16 bg-emerald-500/20 rounded-full blur-2xl pointer-events-none"></div>
                            <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Closed (Ciclo)</span>
                            <span className="text-2xl font-black text-foreground mt-1">{stats.closed}</span>
                        </div>
                        <div className="bg-slate-100 dark:bg-slate-800 border border-border/50 rounded-xl p-3 flex flex-col items-center justify-center relative overflow-hidden">
                            <div className="absolute top-0 right-0 w-16 h-16 bg-slate-400/20 rounded-full blur-2xl pointer-events-none"></div>
                            <span className="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Histórico</span>
                            <span className="text-2xl font-black text-foreground mt-1">{historyTotalItems}</span>
                        </div>
                    </div>
                )}
                <table className="w-full text-left border-collapse">"""

content = content.replace(table_start_str, new_table_start)

# 4. Modify the end of renderTicketsTable
table_end_str = '            </table>'
new_table_end = """            </table>\n            </div>"""

# Only replace the last occurrence inside renderTicketsTable
idx_render_start = content.find("const renderTicketsTable = () => {")
idx_render_end = content.find("    // Cap elapsed progress bar at 7 days")

render_body = content[idx_render_start:idx_render_end]
render_body = render_body.replace(table_end_str, new_table_end)

content = content[:idx_render_start] + render_body + content[idx_render_end:]


with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
