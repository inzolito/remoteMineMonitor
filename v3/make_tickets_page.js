const fs = require('fs');
let code = fs.readFileSync('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'utf8');

// Rename Component
code = code.replace(/const ShiftsPage/g, 'const TicketsPage');
code = code.replace(/export default ShiftsPage/g, 'export default TicketsPage');
code = code.replace(/Panel de Turnos 7x7/g, 'Módulo de Tickets Generales');

// Replace fetch logic
const startFetch = code.indexOf('const { data, isLoading, error } = useQuery({');
const endFetch = code.indexOf('const currentShiftTickets = active_tickets.filter');
const fetchLogicOld = code.substring(startFetch, endFetch);

const fetchLogicNew = `const [page, setPage] = useState(1);
    const limit = 30;

    const { data: ticketsData, isLoading, error } = useQuery({
        queryKey: ['tickets_module', page],
        queryFn: async () => {
            const res = await fetch(\`/monitoreoLaboratorio/v3/api/tickets.php?page=\${page}&limit=\${limit}\`);
            if (!res.ok) throw new Error('Error al cargar tickets');
            return res.json();
        },
        refetchInterval: 15000,
        keepPreviousData: true
    });

    const active_tickets = ticketsData?.tickets || [];
    const stats = ticketsData?.stats || { total: 0, open: 0, seeking: 0, closed: 0, queue: 0, assigned: 0 };
    const pagination = ticketsData?.pagination || { page: 1, limit: 30, total: 0, total_pages: 0 };

    `;
code = code.replace(fetchLogicOld, fetchLogicNew);

// Remove inactive_tickets logic
code = code.replace(/const inactive_tickets = data\?\.inactive_tickets \|\| \[\];/g, '');
code = code.replace(/const \[activeTab, setActiveTab\] = useState<'all' \| 'my_turn'>\('my_turn'\);/g, "const activeTab = 'all';");

// Remove the Tabs UI completely
const tabsRegex = /<div className="flex bg-muted\/30 p-1 rounded-xl mb-4 w-full md:w-auto">[\s\S]*?<\/div>/g;
code = code.replace(tabsRegex, '');

// Fix Top Stats text
code = code.replace(/Resumen Tickets Turno 7x7/g, 'Resumen General de Tickets');
code = code.replace(/{active_tickets\.length}/g, '{stats.total}');
code = code.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'assigned'\)\.length}/g, '{stats.assigned}');
code = code.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'working'\)\.length}/g, '{stats.open}');
code = code.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\)\.includes\('seeking'\)\)\.length}/g, '{stats.seeking}');
code = code.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'closed'\)\.length}/g, '{stats.closed}');

// Replace cycle header info
const headerStart = code.indexOf('{/* Top Header: Cycle Info & Handoff */}');
const headerEnd = code.indexOf('{/* Filter Row */}');
code = code.substring(0, headerStart) + code.substring(headerEnd);

// Fix title above table
code = code.replace(/Tickets del Turno Activo \(\{active_tickets\.length\}\)/g, 'Tickets Recientes (Mostrando {active_tickets.length})');
code = code.replace(/General \(\{active_tickets\.length\}\)/g, 'Tickets Recientes (Mostrando {active_tickets.length})');
code = code.replace(/{inactive_tickets\.length > 0 \? 'xl:col-span-2' : 'xl:col-span-3'}/g, "'xl:col-span-3'");
code = code.replace(/activeTab !== 'all' && \(/g, 'true && (');
code = code.replace(/activeTab === 'all' \? 'xl:col-span-3' : 'xl:col-span-2'/g, "'xl:col-span-2'");

// Pagination controls
const endTableIdx = code.indexOf('</table>');
const paginationUI = `
                                </table>
                            </div>
                            {pagination.total_pages > 1 && (
                                <div className="flex items-center justify-between px-4 py-3 border-t border-border bg-muted/5">
                                    <div className="flex items-center text-[11px] text-muted-foreground font-semibold">
                                        Mostrando página {pagination.page} de {pagination.total_pages} ({pagination.total} tickets totales)
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <button 
                                            onClick={() => setPage(p => Math.max(1, p - 1))}
                                            disabled={pagination.page === 1}
                                            className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-[10px] font-bold disabled:opacity-50 hover:bg-muted"
                                        >
                                            Anterior
                                        </button>
                                        <button 
                                            onClick={() => setPage(p => Math.min(pagination.total_pages, p + 1))}
                                            disabled={pagination.page === pagination.total_pages}
                                            className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-[10px] font-bold disabled:opacity-50 hover:bg-muted"
                                        >
                                            Siguiente
                                        </button>
                                    </div>
                                </div>
                            )}`;
code = code.substring(0, endTableIdx) + paginationUI + code.substring(endTableIdx + 8);

// Remove inactive tickets grid completely
const inactiveGridStart = code.indexOf('{inactive_tickets.length > 0 && (');
if (inactiveGridStart !== -1) {
    const endGrid = code.indexOf('</DashboardLayout>', inactiveGridStart);
    code = code.substring(0, inactiveGridStart) + code.substring(endGrid);
}

fs.writeFileSync('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/TicketsPage.tsx', code);
console.log("Made TicketsPage.tsx cleanly.");
