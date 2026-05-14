import { useNavigate, useLocation } from 'react-router-dom';
import { LayoutDashboard, Users, Activity, Ticket, Clock, Menu, Briefcase, ShieldCheck } from 'lucide-react';
import { useState, useEffect } from 'react';
import { useAuth } from '../hooks/useAuth';
import { api } from '../services/authService';

const Sidebar = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const { user } = useAuth();
    const [collapsed, setCollapsed] = useState(false);
    const [modules, setModules] = useState<any[]>([]);

    // Auto-collapse on Monitoreo pages for more space
    useEffect(() => {
        if (location.pathname.includes('/monitoreo')) {
            setCollapsed(true);
        }
    }, [location.pathname]);

    useEffect(() => {
        const fetchModules = async () => {
            if (!user?.token) return;
            try {
                const resp = await api.get('/modules.php?action=allowed');
                setModules(resp.data);
            } catch (err) {
                console.error("Failed to load modules", err);
            }
        };
        fetchModules();
    }, [user?.token]);

    // Map icon string to component
    const getIcon = (iconName: string) => {
        switch (iconName) {
            case 'LayoutDashboard': return LayoutDashboard;
            case 'Briefcase': return Briefcase;
            case 'Activity': return Activity;
            case 'Ticket': return Ticket;
            case 'Clock': return Clock;
            case 'Users': return Users;
            case 'ShieldCheck': return ShieldCheck;
            default: return LayoutDashboard;
        }
    };

    return (
        <aside className={`bg-card border-r border-border transition-all duration-300 flex flex-col ${collapsed ? 'w-20' : 'w-64'} hidden md:flex`}>
            {/* Logo Area */}
            <div className="h-16 flex items-center justify-between px-6 border-b border-border">
                {!collapsed && <span className="font-bold text-xl text-primary tracking-tight">Hexagon</span>}
                <button onClick={() => setCollapsed(!collapsed)} className="p-1 rounded-lg hover:bg-muted text-muted-foreground">
                    <Menu className="w-5 h-5" />
                </button>
            </div>

            {/* Menu Items */}
            <div className="flex-1 py-6 space-y-1 px-3">
                {modules.map((item) => {
                    const isActive = location.pathname === item.path;
                    const Icon = getIcon(item.icon);

                    return (
                        <button
                            key={item.path}
                            onClick={() => navigate(item.path)}
                            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group relative
                            ${isActive
                                    ? 'bg-primary/10 text-primary font-medium'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                }
                        `}
                        >
                            <Icon className={`w-5 h-5 ${isActive ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'}`} />
                            {!collapsed && <span>{item.name}</span>}

                            {/* Tooltip for collapsed state */}
                            {collapsed && (
                                <div className="absolute left-full ml-2 px-2 py-1 bg-popover text-popover-foreground text-xs rounded shadow-md opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50">
                                    {item.name}
                                </div>
                            )}
                        </button>
                    );
                })}
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-border">
                {!collapsed && (
                    <div className="text-xs text-muted-foreground text-center">
                        &copy; 2026 Hexagon Mining
                    </div>
                )}
            </div>
        </aside>
    );
};

export default Sidebar;
