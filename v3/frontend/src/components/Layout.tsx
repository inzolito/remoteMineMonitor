import { useContext, useState, useRef, useEffect } from 'react';
import { AuthContext } from '../context/AuthContext';
import { Outlet } from 'react-router-dom';
import { LogOut, Activity, User, ChevronDown, Settings } from 'lucide-react';
import { ThemeToggle } from './ThemeToggle';
import Sidebar from './Sidebar';
import { NotificationBell } from './NotificationBell';

const Layout = ({ children }: { children?: React.ReactNode }) => {
    const auth = useContext(AuthContext);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    // Handle clicking outside the user menu to close it
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setUserMenuOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const getFirstName = (name: string) => {
        if (!name) return 'Usuario';
        return name.split(' ')[0];
    };

    return (
        <div className="min-h-screen bg-background text-foreground transition-colors duration-300 flex">
            {/* Sidebar (Desktop) */}
            <Sidebar />

            <div className="flex-1 flex flex-col min-w-0 overflow-y-auto">
                {/* Topbar */}
                <header className="bg-card border-b border-border h-16 flex items-center justify-between px-6 z-50">
                    {/* Mobile Menu Trigger could go here */}
                    <div className="md:hidden flex items-center gap-3">
                        <div className="bg-primary p-2 rounded-lg">
                            <Activity className="w-5 h-5 text-primary-foreground" />
                        </div>
                        <span className="font-bold text-xl tracking-tight">V3</span>
                    </div>

                    {/* Spacer for Desktop to push right items */}
                    <div className="hidden md:block"></div>

                    <div className="flex items-center gap-3">
                        <NotificationBell />
                        <ThemeToggle />

                        <div className="w-px h-6 bg-border mx-1"></div>

                        {/* User Profile Dropdown */}
                        {auth?.user && (
                            <div className="relative" ref={dropdownRef}>
                                <button
                                    onClick={() => setUserMenuOpen(!userMenuOpen)}
                                    className="flex items-center gap-2 pl-2 pr-1 py-1 rounded-full hover:bg-muted/50 transition-all border border-transparent hover:border-border group"
                                >
                                    <div className="flex flex-col items-end mr-1 hidden sm:flex">
                                        <span className="text-sm font-bold leading-none">{getFirstName(auth.user.firstname)}</span>
                                    </div>
                                    <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center border border-primary/20 group-hover:border-primary/40 transition-colors">
                                        <User className="w-5 h-5 text-primary" />
                                    </div>
                                    <ChevronDown className={`w-4 h-4 text-muted-foreground transition-transform duration-200 ${userMenuOpen ? 'rotate-180' : ''}`} />
                                </button>

                                {userMenuOpen && (
                                    <div className="absolute right-0 mt-2 w-56 bg-card border border-border rounded-xl shadow-xl z-[60] py-2 animate-in fade-in zoom-in-95 duration-200 overflow-hidden">
                                        <div className="px-4 py-3 border-b border-border mb-1 bg-muted/30">
                                            <p className="text-sm font-bold truncate">{auth.user.firstname} {auth.user.lastname}</p>
                                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">{auth.user.role || 'Usuario'}</p>
                                        </div>

                                        <button className="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors text-left group">
                                            <Settings className="w-4 h-4 text-muted-foreground group-hover:text-primary" />
                                            <span>Mi Perfil</span>
                                        </button>

                                        <div className="h-px bg-border my-1"></div>

                                        <button
                                            onClick={() => {
                                                setUserMenuOpen(false);
                                                auth.logout();
                                            }}
                                            className="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium hover:bg-destructive/10 text-destructive transition-colors text-left"
                                        >
                                            <LogOut className="w-4 h-4" />
                                            <span>Cerrar Sesión</span>
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </header>

                {/* Main Content */}
                <main className="p-2 md:p-4 flex-1">
                    {children || <Outlet />}
                </main>

                {/* Footer Signature */}
                <footer className="px-6 py-3 border-t border-border/50 text-right">
                    <p className="text-[10px] font-medium text-muted-foreground/60 uppercase tracking-widest">
                        Developed by <span className="text-primary/70 font-black">Maikol Salas</span>
                    </p>
                </footer>
            </div>
        </div>
    );
};

export default Layout;
