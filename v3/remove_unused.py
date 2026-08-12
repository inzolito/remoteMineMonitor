import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# Remove renderTicketRow
start = content.find('    const renderTicketRow = (t: SFCase, idx: number) => {')
if start != -1:
    end = content.find('    };', start)
    if end != -1:
        end = end + len('    };\n\n')
        content = content[:start] + content[end:]

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)

