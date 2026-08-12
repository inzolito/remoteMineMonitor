import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# Let's see what is at line 750-780
print("Lines 750-780:")
lines = content.split('\n')
for i in range(750, 780):
    if i < len(lines):
        print(f"{i+1}: {lines[i]}")

print("\nLines 980-1025:")
for i in range(980, 1025):
    if i < len(lines):
        print(f"{i+1}: {lines[i]}")
