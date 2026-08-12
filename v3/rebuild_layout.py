import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. Update the tabs names
content = content.replace('>General ({active_tickets.length})<', '>Tickets Turno 7x7 2026 ({active_tickets.length})<')
content = content.replace('>Creados Hoy ({todayTickets.length})<', '>Tickets Turno 7x7 Creados Hoy ({todayTickets.length})<')
content = content.replace('>Creados en el Turno ({currentShiftTickets.length})<', '>Tickets Turno 7x7 Creados en Turno ({currentShiftTickets.length})<')

# 2. Extract Inactive Tickets list
inactive_start = content.find('{/* Inactive Tickets Warning List (Shown only if there are any) */}')
if inactive_start != -1:
    inactive_end = content.find(')}', inactive_start) + 2
    inactive_block = content[inactive_start:inactive_end]
    
    # Remove it from its current position
    content = content[:inactive_start] + content[inactive_end:]

# 3. Create the new Top Grid
top_start = content.find('{/* Unified Control Panel: general info and rosters at the top (COMPACT) */}')
top_end = content.find('{/* Tickets Grid */}')

original_top = content[top_start:top_end]

# Modify Global Stats Bar to be compact
new_top = original_top.replace('Closed<br/>(Ciclo)', 'CLOSED').replace('Total<br/>Histórico', 'TOTAL')
new_top = new_top.replace('mb-6 mt-6', 'mt-4 mb-2')
new_top = new_top.replace('p-4 relative overflow-hidden', 'p-3 relative overflow-hidden')
new_top = new_top.replace('gap-4 relative', 'gap-2 relative')
new_top = new_top.replace('p-3 flex flex-col', 'p-2 flex flex-col')
new_top = new_top.replace('text-2xl font-black text-foreground mt-1', 'text-xl font-black text-foreground mt-0.5')
new_top = new_top.replace('text-2xl font-black text-rose-600 dark:text-rose-400 mt-1', 'text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5')

# Wrap in Grid
if inactive_start != -1:
    grid_wrapper = f"""{{/* TOP ROW: Control Panel, Stats, Inactive Tickets */}}
<div className="grid grid-cols-1 xl:grid-cols-4 gap-6 items-start mb-6">
    <div className={{cn("flex flex-col", inactive_tickets.length > 0 ? "xl:col-span-3" : "xl:col-span-4")}}>
        {new_top}
    </div>
    {inactive_block.replace('max-h-[220px]', 'max-h-[220px]')}
</div>
"""
    content = content[:top_start] + grid_wrapper + content[top_end:]

# 4. Fix Tickets Grid span
content = content.replace("className={`${inactive_tickets.length > 0 ? 'xl:col-span-2' : 'xl:col-span-3'} space-y-6`}", "className=\"xl:col-span-3 space-y-6\"")
content = content.replace('<div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">', '<div className="grid grid-cols-1 gap-6 items-start">')
content = content.replace('className="xl:col-span-3 space-y-6"', 'className="w-full space-y-6"')

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
