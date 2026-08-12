const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx';
let code = fs.readFileSync(file, 'utf8');

// 1. Change grid logic to 50/50 (lg:grid-cols-2)
code = code.replace(
    'lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x',
    'lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x'
);
code = code.replace(
    '<div className="lg:col-span-3 overflow-x-auto max-h-[550px] overflow-y-auto">',
    '<div className="lg:col-span-1 overflow-x-auto max-h-[550px] overflow-y-auto">'
);

// 2. Merge Ticket and Faena in renderTicketRow
const targetRowOld = `<td className="px-4 py-3">
                        <a 
                            href={\`https://usa1.lightning.force.com/lightning/r/Case/\${t.CaseId}/view\`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className={cn(
                                "text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit",
                                isClosed
                                    ? "font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale"
                                    : highlightRed
                                        ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                        : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                            )}
                        >
                            {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                        </a>
                    </td>
                    <td className="px-4 py-3">
                        <span className={cn(
                            "text-[10px] px-2 py-1 rounded-md border uppercase tracking-tighter",
                            isClosed
                                ? "font-normal text-slate-400 bg-slate-100/50 border-slate-200"
                                : highlightRed
                                    ? "font-black text-rose-600 dark:text-rose-400 bg-rose-500/10 border-rose-500/20"
                                    : "font-black text-amber-600 bg-amber-500/5 border-amber-500/10"
                        )}>
                            {t.Faena || 'Global'}
                        </span>
                    </td>`;

const targetRowNew = `<td className="px-4 py-3">
                        <div className="flex flex-col gap-1.5 w-fit">
                            <a 
                                href={\`https://usa1.lightning.force.com/lightning/r/Case/\${t.CaseId}/view\`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className={cn(
                                    "text-[10px] px-2 py-1 rounded-md border transition-all flex items-center gap-1.5 w-fit",
                                    isClosed
                                        ? "font-normal bg-slate-100 text-slate-400 border-slate-200 grayscale"
                                        : highlightRed
                                            ? "font-black bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/20 hover:bg-rose-500 hover:text-white"
                                            : "font-black bg-primary/5 text-primary border-primary/10 hover:bg-primary hover:text-white"
                                )}
                            >
                                {t.CaseNumber} <ExternalLink className="w-2.5 h-2.5 opacity-50" />
                            </a>
                            <span className={cn(
                                "text-[9px] font-black uppercase tracking-tighter w-fit",
                                isClosed ? "text-slate-400" : highlightRed ? "text-rose-600 dark:text-rose-400" : "text-amber-600 dark:text-amber-500"
                            )}>
                                {t.Faena || 'Global'}
                            </span>
                        </div>
                    </td>`;

code = code.replace(targetRowOld, targetRowNew);

// 3. Update table headers
const tableHeaderOld = `<th className="px-4 py-3"># Ticket</th>
                        <th className="px-4 py-3">Faena</th>`;
const tableHeaderNew = `<th className="px-4 py-3">Ticket & Faena</th>`;
code = code.replace(tableHeaderOld, tableHeaderNew);

// 4. Update colSpan from 8 to 7
code = code.replace('colSpan={8}', 'colSpan={7}');

fs.writeFileSync(file, code);
console.log("Successfully updated layout and table columns.");
