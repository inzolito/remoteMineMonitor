import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# 1. ALWAYS show graph: Remove {activeTab !== 'all' && ( around Analytics Sidebar
sidebar_start = content.find('{activeTab !== \\'all\\' && (')
if sidebar_start != -1:
    sidebar_end_brace_idx = content.find(')}', sidebar_start + 200) # somewhere after
    # We will just replace it with a Fragment or just remove it
    # Actually, the sidebar starts exactly like this:
    sidebar_block = """                            {/* Right Side (Analytics Sidebar) */}
                            {activeTab !== 'all' && (
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""
    new_sidebar_block = """                            {/* Right Side (Analytics Sidebar) */}
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""
    content = content.replace(sidebar_block, new_sidebar_block)
    
    # Remove the closing )}
    closing_block = """                                    </div>
                                </div>
                            )}
                        </div>
                    </div>"""
    new_closing_block = """                                    </div>
                                </div>
                        </div>
                    </div>"""
    content = content.replace(closing_block, new_closing_block)

# 2. Fix the Stats block to use historyTickets when activeTab === 'all'
stats_logic = """    const stats = (() => {
        const active = activeTab === 'all' 
            ? [...active_tickets, ...inactive_tickets] 
            : activeTab === 'today' 
                ? todayTickets 
                : currentShiftTickets;
        
        let openCount = 0;
        let seekingCount = 0;
        let closedCount = 0;
        let queueCount = 0;
        let totalTime = 0;
        let resolveCount = 0;

        active.forEach((t: SFCase) => {
            const isClosed = t.Status?.toLowerCase() === 'closed';
            const isWorking = t.Status?.toLowerCase() === 'working';
            const isQueue = t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue');

            if (isClosed) closedCount++;
            else if (isWorking) openCount++;
            else if (isQueue) queueCount++;
            else seekingCount++;

            if (isClosed && t.CreatedDate && t.ClosedDate) {
                const created = new Date(t.CreatedDate.replace(' ', 'T')).getTime();
                const closed = new Date(t.ClosedDate.replace(' ', 'T')).getTime();
                totalTime += (closed - created);
                resolveCount++;
            }
        });

        let avgHours = 0;
        if (resolveCount > 0) {
            avgHours = totalTime / resolveCount / (1000 * 60 * 60);
        }

        return {
            total: active.length,
            open: openCount,
            seeking: seekingCount,
            closed: closedCount,
            queue: queueCount,
            avgResolutionTime: resolveCount > 0 ? `${avgHours.toFixed(1)}h` : 'N/A'
        };
    })();"""

new_stats_logic = """    const stats = (() => {
        let active = [];
        if (activeTab === 'all') {
            active = [...active_tickets, ...inactive_tickets, ...historyTickets];
        } else if (activeTab === 'today') {
            active = todayTickets;
        } else {
            active = currentShiftTickets;
        }
        
        let openCount = 0;
        let seekingCount = 0;
        let closedCount = 0;
        let queueCount = 0;
        let totalTime = 0;
        let resolveCount = 0;

        active.forEach((t: SFCase) => {
            const isClosed = t.Status?.toLowerCase() === 'closed';
            const isWorking = t.Status?.toLowerCase() === 'working';
            const isQueue = t.OwnerName?.toLowerCase().includes('support q') || t.OwnerName?.toLowerCase().includes('queue');

            if (isClosed) closedCount++;
            else if (isWorking) openCount++;
            else if (isQueue) queueCount++;
            else seekingCount++;

            if (isClosed && t.CreatedDate && t.ClosedDate) {
                const created = new Date(t.CreatedDate.replace(' ', 'T')).getTime();
                const closed = new Date(t.ClosedDate.replace(' ', 'T')).getTime();
                totalTime += (closed - created);
                resolveCount++;
            }
        });

        let avgHours = 0;
        if (resolveCount > 0) {
            avgHours = totalTime / resolveCount / (1000 * 60 * 60);
        }

        return {
            total: active.length,
            open: openCount,
            seeking: seekingCount,
            closed: closedCount,
            queue: queueCount,
            avgResolutionTime: resolveCount > 0 ? `${avgHours.toFixed(1)}h` : 'N/A'
        };
    })();"""
content = content.replace(stats_logic, new_stats_logic)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)

