const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/components/layouts/Sidebar.tsx';
let code = fs.readFileSync(file, 'utf8');

// The new Tickets navigation item
const ticketsNavItem = `    { name: 'Módulo de Tickets', icon: Ticket, path: '/tickets', requiredPermission: 2 },\n`;

// Let's find a good place to put it, probably after 'Turnos 7x7' or 'Monitoreo Global'
if (!code.includes("path: '/tickets'")) {
    const shiftItem = `{ name: 'Turnos 7x7', icon: Briefcase, path: '/shifts', requiredPermission: 1 },`;
    code = code.replace(shiftItem, shiftItem + '\n' + ticketsNavItem);
}

// Ensure Ticket icon is imported
if (!code.includes('Ticket,')) {
    code = code.replace('import { Home, Users, BarChart2, Briefcase, Activity, Target, Network, Settings, LogOut, Ticket }',
                        'import { Home, Users, BarChart2, Briefcase, Activity, Target, Network, Settings, LogOut, Ticket, History }');
    code = code.replace(/import {([^}]+)} from 'lucide-react'/g, function(match, p1) {
        if (!p1.includes('Ticket')) {
            return `import {${p1}, Ticket} from 'lucide-react'`;
        }
        return match;
    });
}

fs.writeFileSync(file, code);
console.log("Updated Sidebar.tsx");
