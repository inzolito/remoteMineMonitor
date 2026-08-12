import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# Find the start and end of Inactive Tickets
inactive_start = content.find('{/* Inactive Tickets Warning List (Shown only if there are any) */}')
if inactive_start == -1:
    print("Could not find Inactive Tickets block")
    exit(1)

inactive_end = content.find(')}', inactive_start) + 2
inactive_block = content[inactive_start:inactive_end]

# Modify the inactive block max height
inactive_block = inactive_block.replace('max-h-[500px]', 'max-h-[200px]')

# Remove it from the original location
content = content[:inactive_start] + content[inactive_end:]

# Now, find the Unified Control Panel and Global Stats Bar
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
grid_wrapper = f"""{{/* TOP ROW: Control Panel, Stats, Inactive Tickets */}}
<div className="grid grid-cols-1 xl:grid-cols-4 gap-6 items-start mb-6">
    <div className={{cn("flex flex-col gap-0", inactive_tickets.length > 0 ? "xl:col-span-3" : "xl:col-span-4")}}>
{new_top}
    </div>
{inactive_block}
</div>
"""
content = content[:top_start] + grid_wrapper + content[top_end:]

# Fix Tickets Grid wrapper
old_tickets_wrapper = "className={`${inactive_tickets.length > 0 ? 'xl:col-span-2' : 'xl:col-span-3'} space-y-6`}"
new_tickets_wrapper = "className=\"w-full space-y-6\""
content = content.replace(old_tickets_wrapper, new_tickets_wrapper)

# Also fix the outer grid which was grid-cols-1 xl:grid-cols-3
old_outer_grid = '<div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">'
new_outer_grid = '<div className="grid grid-cols-1 gap-6 items-start">'
content = content.replace(old_outer_grid, new_outer_grid)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
