import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

# The duplicates are lines 78-82 exactly matching 72-76
# We'll just replace the double occurrence
dup_block = """    const [historyTickets, setHistoryTickets] = useState<SFCase[]>([]);
    const [historyPage, setHistoryPage] = useState(1);
    const [historyTotalPages, setHistoryTotalPages] = useState(1);
    const [historyTotalItems, setHistoryTotalItems] = useState(0);
    const [isLoadingHistory, setIsLoadingHistory] = useState(false);"""

# Replace two consecutive occurrences with one
content = content.replace(dup_block + "\n\n" + dup_block, dup_block)
content = content.replace(dup_block + "\n    \n" + dup_block, dup_block)

# Also remove duplicate fetchHistoricalTickets if present
fetch_block = """    const fetchHistoricalTickets = async (page: number) => {
        setIsLoadingHistory(true);
        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const resp = await fetch(`/monitoreoLaboratorio/v3/api/shifts.php?action=history&page=${page}&limit=50`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            if (resp.ok) {
                const json = await resp.json();
                setHistoryTickets(json.tickets || []);
                setHistoryTotalPages(json.total_pages || 1);
                setHistoryTotalItems(json.total_items || 0);
            }
        } catch (err) {
            console.error('Error fetching historical tickets:', err);
        } finally {
            setIsLoadingHistory(false);
        }
    };"""

content = content.replace(fetch_block + "\n\n" + fetch_block, fetch_block)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)

