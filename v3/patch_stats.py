import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

stats_block = """    const globalStats = (() => {
        const total = activeTab === 'all' ? historyTotalItems : tabTickets.length;
        const closed = tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length;
        const seeking = tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length;
        const open = tabTickets.filter(t => {
            const statusLower = t.Status?.toLowerCase() || '';
            return statusLower !== 'closed' && !statusLower.includes('seeking');
        }).length;

        const closedWithDates = tabTickets.filter(t => t.Status?.toLowerCase() === 'closed' && t.ClosedDate && t.CreatedDate);"""

new_stats_block = """    const globalStats = (() => {
        // Para calcular métricas completas en la vista General, usamos también los tickets de history (paginados)
        const statsTickets = activeTab === 'all' ? [...tabTickets, ...historyTickets] : tabTickets;
        
        const total = activeTab === 'all' ? historyTotalItems : tabTickets.length;
        
        // El número de closed global se extrae del total menos los activos/abiertos que están en la vista actual
        // o podemos simplemente contar los de statsTickets. Sin embargo, para la pestaña ALL, 
        // historyTotalItems ya incluye los cerrados.
        const openAndSeeking = tabTickets.filter(t => t.Status?.toLowerCase() !== 'closed').length;
        const closed = activeTab === 'all' ? Math.max(0, historyTotalItems - openAndSeeking) : tabTickets.filter(t => t.Status?.toLowerCase() === 'closed').length;
        
        const seeking = tabTickets.filter(t => t.Status?.toLowerCase().includes('seeking')).length;
        const open = tabTickets.filter(t => {
            const statusLower = t.Status?.toLowerCase() || '';
            return statusLower !== 'closed' && !statusLower.includes('seeking');
        }).length;

        const closedWithDates = statsTickets.filter(t => t.Status?.toLowerCase() === 'closed' && t.ClosedDate && t.CreatedDate);"""

if stats_block in content:
    content = content.replace(stats_block, new_stats_block)
    with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
        f.write(content)
    print("Patched stats calculation successfully!")
else:
    print("Could not find stats block!")
