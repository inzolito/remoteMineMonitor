import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    lines = f.readlines()

depth = 0
for i, line in enumerate(lines):
    if '<div' in line:
        depth += line.count('<div')
    if '</div' in line:
        depth -= line.count('</div')
    if i > 1205:
        print(f"{i+1}: Depth={depth} | {line.strip()}")

print("Final depth:", depth)
