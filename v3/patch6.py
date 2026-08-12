import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Merge CaseNumber and Faena in openTickets
open_ticket_str = """                                <td className="px-4 py-3">
                                    <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} target="_blank" rel="noopener noreferrer"
                                       className={cn("text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit",
                                       highlightRed ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                       : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                    )}>
                                        {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                    </a>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={cn("text-[10px] px-2 py-1 rounded-md border uppercase tracking-tighter",
                                        highlightRed ? "font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 border-rose-500/20"
                                        : "font-black text-amber-600 bg-amber-500/5 border-amber-500/10"
                                    )}>{t.Faena || 'Global'}</span>
                                </td>"""
                                
new_open_ticket_str = """                                <td className="px-4 py-3">
                                    <div className="flex flex-col items-center justify-center gap-1.5">
                                        <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} target="_blank" rel="noopener noreferrer"
                                           className={cn("text-[10px] px-2 py-1 rounded-md border transition-all flex items-center justify-center gap-1.5 w-full max-w-[100px]",
                                           highlightRed ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                           : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                        )}>
                                            {t.CaseNumber} <ExternalLink className="w-2 h-2 opacity-50 shrink-0" />
                                        </a>
                                        <span className={cn("text-[9px] px-2 py-0.5 rounded-md border uppercase tracking-tighter text-center w-full max-w-[100px] truncate",
                                            highlightRed ? "font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 border-rose-500/20"
                                            : "font-black text-amber-600 bg-amber-500/5 border-amber-500/10"
                                        )} title={t.Faena}>{t.Faena || 'Global'}</span>
                                    </div>
                                </td>"""
content = content.replace(open_ticket_str, new_open_ticket_str)


# 2. Merge CaseNumber and Faena in closedTickets
closed_ticket_str = """                                <td className="px-4 py-3">
                                    <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} target="_blank" rel="noopener noreferrer"
                                       className="text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale">
                                        {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                                    </a>
                                </td>
                                <td className="px-4 py-3">
                                    <span className="text-[10px] px-2 py-1 rounded-md border uppercase tracking-tighter font-normal text-slate-400 bg-slate-100/50 border-slate-200">
                                        {t.Faena || 'Global'}
                                    </span>
                                </td>"""
                                
new_closed_ticket_str = """                                <td className="px-4 py-3">
                                    <div className="flex flex-col items-center justify-center gap-1.5">
                                        <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} target="_blank" rel="noopener noreferrer"
                                           className="text-[10px] px-2 py-1 rounded-md border transition-all flex items-center justify-center gap-1.5 w-full max-w-[100px] font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale">
                                            {t.CaseNumber} <ExternalLink className="w-2 h-2 opacity-50 shrink-0" />
                                        </a>
                                        <span className="text-[9px] px-2 py-0.5 rounded-md border uppercase tracking-tighter font-normal text-slate-400 bg-slate-100/50 border-slate-200 text-center w-full max-w-[100px] truncate" title={t.Faena}>
                                            {t.Faena || 'Global'}
                                        </span>
                                    </div>
                                </td>"""
content = content.replace(closed_ticket_str, new_closed_ticket_str)


# 3. Center align the first header
header_str = '<th className="px-4 py-3">Ticket & Faena</th>'
new_header_str = '<th className="px-4 py-3 text-center">Ticket & Faena</th>'
content = content.replace(header_str, new_header_str)


# 4. Make Donut Chart always visible!
# In the return statement, near "Tab Content Body"
layout_str = """                        {/* Tab Content Body */}
                        <div className={cn(
                            activeTab === 'all'
                                ? "overflow-x-auto max-h-[550px] overflow-y-auto"
                                : "grid grid-cols-1 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-border/60"
                        )}>
                            {/* Left Side (Table wrapper) */}
                            {activeTab !== 'all' ? (
                                <div className="lg:col-span-3 overflow-x-auto max-h-[550px] overflow-y-auto">
                                    {renderTicketsTable()}
                                </div>
                            ) : (
                                renderTicketsTable()
                            )}

                            {/* Right Side (Analytics Sidebar) */}
                            {activeTab !== 'all' && (
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""

new_layout_str = """                        {/* Tab Content Body */}
                        <div className="grid grid-cols-1 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-border/60">
                            {/* Left Side (Table wrapper) */}
                            <div className="lg:col-span-3 overflow-x-auto max-h-[550px] overflow-y-auto">
                                {renderTicketsTable()}
                            </div>

                            {/* Right Side (Analytics Sidebar) */}
                            <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""
content = content.replace(layout_str, new_layout_str)

# Remove the matching closing bracket for `activeTab !== 'all' && (` 
# which is located after the analytics sidebar.
sidebar_end_str = """                                </div>
                            )}
                        </div>"""
new_sidebar_end_str = """                                </div>
                        </div>"""
content = content.replace(sidebar_end_str, new_sidebar_end_str)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
