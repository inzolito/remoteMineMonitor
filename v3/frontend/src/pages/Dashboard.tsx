import { LayoutGrid } from 'lucide-react';
import SitesList from '../components/SitesList';

const Dashboard = () => {
    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between pb-6 border-b border-border">
                <div>
                    <h1 className="text-3xl font-bold text-foreground">Dashboard General</h1>
                    <p className="text-muted-foreground mt-1">Vista general del estado de las faenas.</p>
                </div>

                <div className="flex items-center gap-2 bg-card px-4 py-2 rounded-full border border-border shadow-sm">
                    <div className="relative flex h-3 w-3">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </div>
                    <span className="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider">Tiempo Real</span>
                </div>
            </div>

            <div className="flex items-center gap-2 mb-4 text-xl font-semibold text-foreground">
                <LayoutGrid className="w-6 h-6 text-primary" />
                <h2>Faenas Monitoreadas</h2>
            </div>

            <SitesList />
        </div>
    );
};

export default Dashboard;
