const fs = require('fs');

let shiftsCode = fs.readFileSync('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'utf8');

// The new page is TicketsPage
let ticketsCode = shiftsCode.replace(/ShiftsPage/g, 'TicketsPage');
ticketsCode = ticketsCode.replace(/Panel de Turnos 7x7/g, 'Módulo de Tickets Generales');

// We need to fetch from our new API endpoint
// The old one used useQuery with ['shifts'] and '/api/shifts/'
// Let's create a simpler fetch logic using useEffect or standard useQuery.

const newFetchLogic = `
    const [page, setPage] = useState(1);
    const limit = 30;

    const { data: ticketsData, isLoading, error } = useQuery({
        queryKey: ['tickets_module', page],
        queryFn: async () => {
            const res = await fetch(\`/monitoreoLaboratorio/v3/api/tickets.php?page=\${page}&limit=\${limit}\`);
            if (!res.ok) throw new Error('Error al cargar tickets');
            return res.json();
        },
        refetchInterval: 15000,
        keepPreviousData: true // Keep showing old data while loading new page
    });

    const active_tickets = ticketsData?.tickets || [];
    const stats = ticketsData?.stats || { total: 0, open: 0, seeking: 0, closed: 0, queue: 0, assigned: 0 };
    const pagination = ticketsData?.pagination || { page: 1, limit: 30, total: 0, total_pages: 0 };
`;

// Replace the old fetch block
const startFetch = ticketsCode.indexOf('const { data, isLoading, error } = useQuery({');
const endFetch = ticketsCode.indexOf('    const config = data?.config || {};');
if (startFetch !== -1 && endFetch !== -1) {
    const endFetchFull = ticketsCode.indexOf('const currentShiftTickets = active_tickets', endFetch);
    const partToReplace = ticketsCode.substring(startFetch, endFetchFull);
    ticketsCode = ticketsCode.replace(partToReplace, newFetchLogic);
}

// Remove inactive tickets logic
ticketsCode = ticketsCode.replace(/const inactive_tickets = data\?\.inactive_tickets \|\| \[\];/g, '');
ticketsCode = ticketsCode.replace(/const \[activeTab, setActiveTab\] = useState<'all' \| 'my_turn'>\('my_turn'\);/g, `const [activeTab, setActiveTab] = useState<'all'>('all');`);
// Remove tabs UI
ticketsCode = ticketsCode.replace(/<div className="flex bg-muted\/30 p-1 rounded-xl mb-4 w-full md:w-auto">[\s\S]*?<\/div>/, '');

// Since we already get stats from the backend for the whole year, we should use those stats instead of calculating from active_tickets.
// Replace the top stats boxes to use `stats` object
ticketsCode = ticketsCode.replace(/{active_tickets\.length}/g, '{stats.total}');
ticketsCode = ticketsCode.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'assigned'\)\.length}/g, '{stats.assigned}');
ticketsCode = ticketsCode.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'working'\)\.length}/g, '{stats.open}');
ticketsCode = ticketsCode.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\)\.includes\('seeking'\)\)\.length}/g, '{stats.seeking}');
ticketsCode = ticketsCode.replace(/{active_tickets\.filter\(t => t\.Status\?\.toLowerCase\(\) === 'closed'\)\.length}/g, '{stats.closed}');

// Replace title "Resumen Tickets Turno 7x7"
ticketsCode = ticketsCode.replace(/Resumen Tickets Turno 7x7/g, 'Resumen General de Tickets');
ticketsCode = ticketsCode.replace(/Tickets del Turno Activo/g, 'Tickets Recientes');

// Remove Top Header Cycle Info (Turno A, Turno B, Switch)
const headerStart = ticketsCode.indexOf('{/* Top Header: Cycle Info & Handoff */}');
const headerEnd = ticketsCode.indexOf('{/* Filter Row */}');
if (headerStart !== -1 && headerEnd !== -1) {
    ticketsCode = ticketsCode.substring(0, headerStart) + ticketsCode.substring(headerEnd);
}

// Add Pagination Controls at the bottom of the table
const endTable = ticketsCode.indexOf('</table>');
if (endTable !== -1) {
    const paginationUI = `
                                </table>
                            </div>
                            {/* Pagination Controls */}
                            {pagination.total_pages > 1 && (
                                <div className="flex items-center justify-between px-4 py-3 border-t border-border bg-muted/5">
                                    <div className="flex items-center text-[11px] text-muted-foreground font-semibold">
                                        Mostrando página {pagination.page} de {pagination.total_pages} ({pagination.total} tickets totales)
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <button 
                                            onClick={() => setPage(p => Math.max(1, p - 1))}
                                            disabled={pagination.page === 1}
                                            className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-xs font-bold disabled:opacity-50 hover:bg-muted"
                                        >
                                            Anterior
                                        </button>
                                        <button 
                                            onClick={() => setPage(p => Math.min(pagination.total_pages, p + 1))}
                                            disabled={pagination.page === pagination.total_pages}
                                            className="px-3 py-1.5 rounded-lg border border-border bg-background text-foreground text-xs font-bold disabled:opacity-50 hover:bg-muted"
                                        >
                                            Siguiente
                                        </button>
                                    </div>
                                </div>
                            )}`;
    ticketsCode = ticketsCode.substring(0, endTable) + paginationUI + ticketsCode.substring(endTable + 8);
}

// Remove the inactive tickets grid at the bottom
const inactiveGrid = ticketsCode.indexOf('{inactive_tickets.length > 0 &&');
if (inactiveGrid !== -1) {
    const endInactiveGrid = ticketsCode.indexOf('</DashboardLayout>', inactiveGrid);
    ticketsCode = ticketsCode.substring(0, inactiveGrid) + ticketsCode.substring(endInactiveGrid);
}

// Remove right side Analytics if activeTab isn't "all" (we removed activeTab earlier, or fixed it to always show). Let's just fix it to always show.
ticketsCode = ticketsCode.replace(/{activeTab !== 'all' && \(/g, '{true && (');

fs.writeFileSync('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/TicketsPage.tsx', ticketsCode);
console.log("Successfully created TicketsPage.tsx");
