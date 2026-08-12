import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Wrap the top part in a grid
# From `<div className="space-y-6">` up to before `            {/* Content Grid */}`
top_start = content.find('<div className="space-y-6">\n            {/* Unified Control Panel: general info and rosters at the top (COMPACT) */}')
if top_start != -1:
    content = content[:top_start] + '<div className="space-y-6">\n            {/* Top Row: Unified Control Panel, Global Stats, and Inactive Tickets */}\n            <div className="grid grid-cols-1 xl:grid-cols-4 gap-6">\n                <div className={cn("flex flex-col gap-6", inactive_tickets.length > 0 ? "xl:col-span-3" : "xl:col-span-4")}>\n                    {/* Unified Control Panel: general info and rosters at the top (COMPACT) */}' + content[top_start + 27:]

# End of global stats bar:
stats_end = content.find('            {/* Content Grid */}')
if stats_end != -1:
    # Insert closing div for the left column, and the inactive tickets column
    insert_str = """                </div>

                {/* Inactive Tickets Warning List (Shown only if there are any) */}
                {inactive_tickets.length > 0 && (
                    <div className="xl:col-span-1 h-full max-h-[220px] overflow-y-auto custom-scrollbar">
                        <div className="bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm h-full flex flex-col">
                            <div className="p-3 border-b border-border/60 bg-destructive/5 sticky top-0 z-10 shrink-0">
                                <h3 className="text-[11px] font-black text-destructive flex items-center gap-1.5 leading-tight">
                                    <AlertCircle className="w-3.5 h-3.5" />
                                    Tickets del Turno Inactivo ({inactive_tickets.length})
                                </h3>
                                <p className="text-[8px] text-destructive/70 font-medium mt-0.5 leading-tight">Deben ser transferidos al turno activo en Salesforce.</p>
                            </div>
                            <div className="divide-y divide-border/40 overflow-y-auto flex-1 bg-card">
                                {inactive_tickets.map(t => (
                                    <div key={t.CaseId} className="p-2.5 hover:bg-muted/30 transition-colors group">
                                        <div className="flex items-start justify-between gap-2 mb-1.5">
                                            <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} 
                                               target="_blank" rel="noopener noreferrer"
                                               className="text-[10px] font-black text-primary hover:underline truncate max-w-[120px]">
                                                #{t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 inline opacity-50" />
                                            </a>
                                            <div className="flex items-center gap-1.5">
                                                <span className="text-[8px] px-1.5 py-0.5 rounded border border-amber-500/30 bg-amber-500/10 text-amber-600 font-bold uppercase truncate max-w-[60px]" title={t.Faena}>{t.Faena || 'Global'}</span>
                                                <span className="text-[8px] px-1.5 py-0.5 rounded border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 font-bold uppercase truncate max-w-[80px]" title={t.Status}>{t.Status}</span>
                                            </div>
                                        </div>
                                        <p className="text-[10px] font-bold text-foreground line-clamp-1 group-hover:line-clamp-2 transition-all mb-1">{t.Subject || '(Sin asunto)'}</p>
                                        <div className="flex items-center justify-between text-[9px] text-muted-foreground font-medium">
                                            <span className="truncate max-w-[120px]">Owner: {t.OwnerName || 'N/A'}</span>
                                            <button onClick={() => handleOpenTicketDetails(t)} className="text-primary hover:text-primary/80 transition-colors p-1 rounded-md hover:bg-primary/10">
                                                <Eye className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

"""
    content = content[:stats_end] + insert_str + content[stats_end:]

# 2. Remove the old Inactive Tickets Warning List from the bottom
old_inactive_start = content.find('{/* Inactive Tickets Warning List (Shown only if there are any) */}')
# Find the second occurrence since the first is the one we just inserted!
old_inactive_start = content.find('{/* Inactive Tickets Warning List (Shown only if there are any) */}', old_inactive_start + 1)

if old_inactive_start != -1:
    # Find matching closing bracket
    # It ends with `                )}`
    # and then `            </div>` (for Content Grid)
    # Actually, the easiest way is to find the Content Grid div and remove the conditional column class.
    pass

# We also need to fix Content Grid class
content_grid_str = """            {/* Content Grid */}
            <div className="grid grid-cols-1 xl:grid-cols-4 gap-6 relative">
                {/* Main Content Area */}
                <div className={cn("space-y-6", inactive_tickets.length > 0 ? "xl:col-span-3" : "xl:col-span-4")}>"""
new_content_grid_str = """            {/* Content Grid */}
            <div className="grid grid-cols-1 gap-6 relative">
                {/* Main Content Area */}
                <div className="space-y-6">"""
content = content.replace(content_grid_str, new_content_grid_str)

# Now remove the old inactive_tickets section
old_inactive_block = """                </div>

                {/* Inactive Tickets Warning List (Shown only if there are any) */}
                {inactive_tickets.length > 0 && (
                    <div className="xl:col-span-1 space-y-6">
                        <div className="bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm">
                            <div className="p-4 border-b border-border/60 bg-destructive/5">
                                <h3 className="text-xs font-black text-destructive flex items-center gap-1.5">
                                    <AlertCircle className="w-4 h-4" />
                                    Tickets del Turno Inactivo ({inactive_tickets.length})
                                </h3>
                                <p className="text-[10px] text-destructive/70 font-medium mt-1">Deben ser transferidos al turno activo en Salesforce.</p>
                            </div>
                            <div className="divide-y divide-border/40 max-h-[500px] overflow-y-auto">
                                {inactive_tickets.map(t => (
                                    <div key={t.CaseId} className="p-3 hover:bg-muted/30 transition-colors group">
                                        <div className="flex items-start justify-between gap-2 mb-2">
                                            <a href={`https://usa1.lightning.force.com/lightning/r/Case/${t.CaseId}/view`} 
                                               target="_blank" rel="noopener noreferrer"
                                               className="text-[11px] font-black text-primary hover:underline">
                                                #{t.CaseNumber}
                                            </a>
                                            <div className="flex items-center gap-1.5">
                                                <span className="text-[9px] px-1.5 py-0.5 rounded border border-amber-500/30 bg-amber-500/10 text-amber-600 font-bold uppercase truncate max-w-[80px]" title={t.Faena}>{t.Faena || 'Global'}</span>
                                                <span className="text-[9px] px-1.5 py-0.5 rounded border border-emerald-500/30 bg-emerald-500/10 text-emerald-600 font-bold uppercase truncate max-w-[100px]" title={t.Status}>{t.Status}</span>
                                            </div>
                                        </div>
                                        <p className="text-[11px] font-bold text-foreground line-clamp-2 group-hover:line-clamp-none transition-all mb-2">{t.Subject || '(Sin asunto)'}</p>
                                        <div className="flex items-center justify-between text-[10px] text-muted-foreground font-medium">
                                            <span className="truncate max-w-[160px]">Owner: {t.OwnerName || 'N/A'}</span>
                                            <button onClick={() => handleOpenTicketDetails(t)} className="text-primary hover:text-primary/80 transition-colors">
                                                <Eye className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>"""

new_old_inactive_block = """                </div>
            </div>"""

if old_inactive_block in content:
    content = content.replace(old_inactive_block, new_old_inactive_block)
else:
    print("Could not find old inactive block")

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
