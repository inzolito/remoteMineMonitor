const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx';
let code = fs.readFileSync(file, 'utf8');

// 1. Center the Owner alias in the table header
code = code.replace(
    '<th className="px-4 py-3">Owner</th>',
    '<th className="px-4 py-3 text-center">Owner</th>'
);

// 2. Center the Owner alias in the ticket row
code = code.replace(
    '<td className="px-4 py-3">\n                        <span className={cn(\n                            "text-[10px] px-2 py-1 rounded-md whitespace-nowrap border"',
    '<td className="px-4 py-3 text-center">\n                        <span className={cn(\n                            "text-[10px] px-2 py-1 rounded-md whitespace-nowrap border"'
);

// 3. Make the chart double the size
// Increase container size from w-36 h-36 to w-64 h-64
code = code.replace(
    '<div className="w-36 h-36 shrink-0 relative flex items-center justify-center">',
    '<div className="w-64 h-64 shrink-0 relative flex items-center justify-center">'
);

// Increase innerRadius and outerRadius
code = code.replace(
    'innerRadius={38}',
    'innerRadius={76}'
);
code = code.replace(
    'outerRadius={54}',
    'outerRadius={108}'
);

// Increase the center text size
code = code.replace(
    '<span className="text-[8px] font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total</span>',
    '<span className="text-xs font-black uppercase text-slate-400 dark:text-slate-500 tracking-wider">Total</span>'
);
code = code.replace(
    '<span className="text-xl font-black text-foreground leading-none">{stats.total}</span>',
    '<span className="text-4xl font-black text-foreground leading-none">{stats.total}</span>'
);

fs.writeFileSync(file, code);
console.log("Successfully centered alias and increased chart size.");
