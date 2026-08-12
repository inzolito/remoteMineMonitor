import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# Change max-h-[500px] to max-h-[220px] inside Inactive Tickets
inactive_start = content.find('{/* Inactive Tickets Warning List')
if inactive_start != -1:
    old_max = 'max-h-[500px]'
    new_max = 'max-h-[220px] custom-scrollbar'
    # We find the next occurrence of max-h-[500px] after Inactive Tickets Warning List
    next_max = content.find(old_max, inactive_start)
    if next_max != -1 and next_max < inactive_start + 1000:
        content = content[:next_max] + new_max + content[next_max+len(old_max):]
        with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
            f.write(content)

