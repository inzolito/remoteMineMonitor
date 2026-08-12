const fs = require('fs');

// Fix UsersPage.tsx
const usersFile = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/UsersPage.tsx';
let usersCode = fs.readFileSync(usersFile, 'utf8');

// Add show_in_tickets to UserData
if (!usersCode.includes('show_in_tickets?: number;')) {
    usersCode = usersCode.replace(
        "turno_tipo?: 'Día' | 'Noche';",
        "turno_tipo?: 'Día' | 'Noche';\n    show_in_tickets?: number;"
    );
}
// Remove duplicate show_in_tickets: 0, show_in_tickets: 0
usersCode = usersCode.replace(/show_in_tickets: 0, show_in_tickets: 0/g, 'show_in_tickets: 0');

fs.writeFileSync(usersFile, usersCode);

// Fix TicketsPage.tsx
const ticketsFile = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/TicketsPage.tsx';
let ticketsCode = fs.readFileSync(ticketsFile, 'utf8');

// Fix handleOpenTicketDetails unused var
ticketsCode = ticketsCode.replace(
    'const handleOpenTicketDetails = (t: SFCase) => {',
    'const handleOpenTicketDetails = (t: SFCase) => {\n        console.log(t);\n'
);

// Fix active_tickets type inference
ticketsCode = ticketsCode.replace(
    'const active_tickets = ticketsData?.tickets || [];',
    'const active_tickets: SFCase[] = ticketsData?.tickets || [];'
);

fs.writeFileSync(ticketsFile, ticketsCode);

console.log("Fixed TS Errors");
