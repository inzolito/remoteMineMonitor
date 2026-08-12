import re

with open('frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# Extract the Inactive Tickets block
inactive_pattern = r"( {16}\{\/\* Inactive Tickets Warning List.*?\n {16}\}\)\n)"
match = re.search(inactive_pattern, content, re.DOTALL)
if not match:
    print("Could not find inactive tickets block")
    exit(1)

inactive_block = match.group(1)

# Remove it from the bottom
content = content.replace(inactive_block, "")

# Remove the closing tag of the Tickets Grid that used to wrap it
grid_end = r" {12}<\/div>\n\n {12}\{\/\* Salesforce Ticket Modal \*\/\}"
content = content.replace("            </div>\n\n            {/* Salesforce Ticket Modal */}", "\n            {/* Salesforce Ticket Modal */}")

# Simplify the Tickets Grid wrapper
tickets_grid_pattern = r"( {12}\{\/\* Tickets Grid \*\/\}\n {12}<div className=\"grid grid-cols-1 xl:grid-cols-3 gap-6 items-start\">\n\n {16}\{\/\* Active Tickets List \*\/\}\n {16}<div className=\{`\$\{inactive_tickets\.length > 0 \? 'xl:col-span-2' : 'xl:col-span-3'\} space-y-6`\}>\n)"
tickets_grid_replacement = """            {/* Tickets Grid */}
            <div className="w-full space-y-6">
"""
content = re.sub(tickets_grid_pattern, tickets_grid_replacement, content)

# Now inject the top layout
# We want to wrap the Control Panel and Resumen
# Control Panel starts at: "            {/* Unified Control Panel: general info and rosters at the top */}"
# And Resumen ends before "            {/* Tickets Grid */}"

top_pattern = r"( {12}\{\/\* Unified Control Panel: general info and rosters at the top \*\/\}\n {12}<div className=\"flex items-center bg-card border border-border\/50 rounded-lg px-4 py-2 shadow-sm w-full gap-4 overflow-x-auto whitespace-nowrap scrollbar-hide relative z-10 mb-6\">\n)"
top_replacement = """            <div className="flex flex-col xl:flex-row gap-6">
                <div className={cn("space-y-6 flex-1 min-w-0", inactive_tickets.length > 0 ? "xl:w-2/3" : "w-full")}>
                    {/* Unified Control Panel: general info and rosters at the top */}
                    <div className="flex items-center bg-card border border-border/50 rounded-lg px-4 py-2 shadow-sm w-full gap-4 overflow-x-auto whitespace-nowrap scrollbar-hide relative z-10">
"""
content = content.replace(top_pattern, top_replacement)

# End of Resumen is just before Tickets Grid
# So we look for the end of the Resumen div and inject the closing of the left col, and then the Inactive Tickets block.
resumen_end_pattern = r"( {16}<\/div>\n {12}<\/div>\n\n {12}\{\/\* Tickets Grid \*\/\})"

inactive_modified = inactive_block.replace('xl:col-span-1 space-y-6', 'xl:w-1/3 shrink-0 flex flex-col')
inactive_modified = inactive_modified.replace('bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm', 'bg-card rounded-2xl border border-destructive/30 overflow-hidden shadow-sm flex flex-col h-full max-h-[350px]')

resumen_end_replacement = f"""                </div>
            </div>

{inactive_modified}
            </div>

            {{/* Tickets Grid */}}"""

content = re.sub(resumen_end_pattern, resumen_end_replacement, content)

with open('frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
print("Done")
