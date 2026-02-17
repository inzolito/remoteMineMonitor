import { useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { AuthContext } from '../context/AuthContext';
import { Loader2, Activity, User, Lock, ChevronRight, AlertCircle } from 'lucide-react';

const Login = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const auth = useContext(AuthContext);
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            if (auth) {
                await auth.login(username, password);
                navigate('/');
            }
        } catch (err) {
            setError('Credenciales de acceso no válidas');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen relative overflow-hidden flex items-center justify-center bg-[#f8fafc] font-['Outfit',sans-serif]">
            {/* Background Decorative Elements - High Visual Density */}
            <div className="absolute inset-0 z-0 overflow-hidden pointer-events-none">
                {/* Main Blobs */}
                <div className="absolute top-[-10%] left-[-5%] w-[60%] h-[60%] bg-sky-400/10 blur-[130px] rounded-full animate-blob"></div>
                <div className="absolute bottom-[-10%] right-[-5%] w-[60%] h-[60%] bg-indigo-400/10 blur-[130px] rounded-full animate-blob animation-delay-2000"></div>

                {/* Extra Accent Blobs */}
                <div className="absolute top-1/4 right-[10%] w-[30%] h-[30%] bg-amber-400/5 blur-[100px] rounded-full animate-blob animation-delay-4000"></div>
                <div className="absolute bottom-1/4 left-[10%] w-[25%] h-[25%] bg-emerald-400/5 blur-[90px] rounded-full animate-blob"></div>

                {/* Floating Geometric Shapes */}
                <div className="absolute top-[20%] right-[15%] w-32 h-32 border-2 border-blue-500/5 rounded-3xl rotate-12 animate-spin-slow"></div>
                <div className="absolute bottom-[20%] left-[5%] w-48 h-48 border-2 border-indigo-500/5 rounded-[3rem] -rotate-12 animate-reverse-spin"></div>

                {/* Subtle Grid & Mesh */}
                <div className="absolute inset-0 opacity-[0.02]" style={{ backgroundImage: 'linear-gradient(#000 1px, transparent 1px), linear-gradient(90deg, #000 1px, transparent 1px)', backgroundSize: '50px 50px' }}></div>
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(0,0,0,0.05)_1px,transparent_0)] bg-[size:25px_25px]"></div>
            </div>

            <main className="relative z-10 w-full max-w-[1240px] px-6 py-12 flex flex-col lg:flex-row items-center justify-between gap-12 lg:gap-20">

                {/* Left Side: Brand & Live Visualization */}
                <div className="hidden lg:flex flex-col gap-10 flex-1 max-w-xl animate-in fade-in slide-in-from-left duration-1000">
                    <div className="space-y-6">
                        <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-[#0284c7]/10 border border-[#0284c7]/20 rounded-full text-[#0284c7] text-[10px] font-black tracking-[0.2em] uppercase shadow-sm">
                            <span className="relative flex h-2.5 w-2.5">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0284c7]/40 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#0284c7]"></span>
                            </span>
                            Remote Mine Monitor v3
                        </div>

                        <div className="space-y-4">
                            <h1 className="text-6xl xl:text-8xl font-black text-slate-900 tracking-tighter leading-[0.9]">
                                Remote <br />
                                Mine <br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-[#0284c7] via-indigo-600 to-violet-600">Monitor</span>
                            </h1>
                            <p className="text-slate-500 text-xl max-w-md leading-relaxed font-medium opacity-80">
                                Sistema de monitoreo remoto gestionado por el area de soporte de Hexagon Minning
                            </p>
                        </div>
                    </div>

                    {/* Pseudo-Dashboard Elements for Visual Density */}
                    <div className="grid grid-cols-2 gap-6 animate-pulse-slow">
                        <div className="p-8 bg-white/40 border border-white/60 rounded-[2.5rem] backdrop-blur-md shadow-2xl shadow-[#0284c7]/5 group hover:bg-white/60 transition-all border-b-[6px] border-b-[#0284c7]/20">
                            <div className="flex justify-between items-start mb-6">
                                <div className="p-4 bg-[#0284c7] rounded-2xl shadow-lg shadow-[#0284c7]/30 text-white">
                                    <Activity className="w-6 h-6" />
                                </div>
                                <div className="text-[10px] font-black text-green-500 flex items-center gap-1 bg-green-500/10 px-3 py-1 rounded-full border border-green-500/10">
                                    +12.4% <ChevronRight className="w-3 h-3 rotate-[-90deg]" />
                                </div>
                            </div>
                            <div className="text-[11px] text-slate-400 uppercase font-black tracking-[0.2em] mb-1">Global Uptime</div>
                            <div className="text-3xl font-black text-slate-900">99.98%</div>
                        </div>

                        <div className="p-8 bg-white/40 border border-white/60 rounded-[2.5rem] backdrop-blur-md shadow-2xl shadow-indigo-500/5 group hover:bg-white/60 transition-all border-b-[6px] border-b-indigo-500/20">
                            <div className="flex justify-between items-start mb-6">
                                <div className="p-4 bg-indigo-500 rounded-2xl shadow-lg shadow-indigo-500/30 text-white">
                                    <User className="w-6 h-6" />
                                </div>
                            </div>
                            <div className="text-[11px] text-slate-400 uppercase font-black tracking-[0.2em] mb-1">Active Nodes</div>
                            <div className="text-3xl font-black text-slate-900">24 Online</div>
                        </div>
                    </div>
                </div>

                {/* Right Side: Enhanced Login Card */}
                <div className="w-full max-w-[460px] relative">
                    {/* Decorative Ring around card */}
                    <div className="absolute -inset-6 bg-gradient-to-br from-[#0284c7]/10 via-indigo-500/5 to-transparent rounded-[4rem] -z-10 blur-3xl opacity-60"></div>

                    <div className="animate-in fade-in slide-in-from-bottom lg:slide-in-from-right duration-1000 bg-white/40 border border-white/80 p-1.5 rounded-[3.5rem] shadow-2xl shadow-[#0284c7]/10 backdrop-blur-3xl">
                        <div className="bg-white/95 rounded-[3.2rem] p-12 lg:p-14 border border-white/50 relative overflow-hidden">
                            {/* Subtle internal gradient effect */}
                            <div className="absolute top-0 right-0 w-32 h-32 bg-[#0284c7]/5 blur-3xl rounded-full"></div>

                            <div className="text-center mb-12 relative z-10 transition-transform hover:scale-[1.02] duration-500">
                                <div className="relative inline-block group">
                                    <div className="absolute -inset-4 bg-[#0284c7]/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                    <div className="w-24 h-24 bg-gradient-to-br from-[#0284c7] to-indigo-700 rounded-[2rem] mx-auto flex items-center justify-center mb-8 shadow-2xl shadow-[#0284c7]/30 -rotate-3 group-hover:rotate-0 transition-all duration-500 cursor-pointer relative z-10">
                                        <Activity className="w-12 h-12 text-white group-hover:scale-110 transition-transform" />
                                    </div>
                                </div>
                                <h2 className="text-4xl font-black text-slate-900 mb-2 tracking-tighter">Bienvenido</h2>
                                <p className="text-slate-500 text-sm font-bold opacity-60 tracking-wide uppercase">Control de Acceso Seguro</p>
                            </div>

                            {error && (
                                <div className="bg-red-50 border border-red-100 text-red-600 px-6 py-4 rounded-3xl mb-10 text-sm flex items-center gap-4 animate-shake shadow-sm shadow-red-500/5">
                                    <AlertCircle className="w-5 h-5 flex-shrink-0" />
                                    <span className="font-bold">{error}</span>
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-8 relative z-10">
                                <div className="space-y-3 group">
                                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 group-focus-within:text-[#0284c7] transition-colors">ID de Usuario</label>
                                    <div className="relative">
                                        <div className="absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-[#0284c7] transition-colors">
                                            <User className="w-6 h-6" />
                                        </div>
                                        <input
                                            type="text"
                                            value={username}
                                            onChange={(e) => setUsername(e.target.value)}
                                            className="w-full pl-16 pr-6 py-5 bg-slate-50/50 border border-slate-200 rounded-[2rem] focus:ring-[8px] focus:ring-[#0284c7]/5 focus:border-[#0284c7] focus:bg-white outline-none text-slate-900 placeholder-slate-300 transition-all font-bold text-xl"
                                            placeholder="Nombre de usuario"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="space-y-3 group">
                                    <label className="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] ml-4 group-focus-within:text-[#0284c7] transition-colors">Contraseña Segura</label>
                                    <div className="relative">
                                        <div className="absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-[#0284c7] transition-colors">
                                            <Lock className="w-6 h-6" />
                                        </div>
                                        <input
                                            type="password"
                                            value={password}
                                            onChange={(e) => setPassword(e.target.value)}
                                            className="w-full pl-16 pr-6 py-5 bg-slate-50/50 border border-slate-200 rounded-[2rem] focus:ring-[8px] focus:ring-[#0284c7]/5 focus:border-[#0284c7] focus:bg-white outline-none text-slate-900 placeholder-slate-300 transition-all font-bold text-xl"
                                            placeholder="••••••••"
                                            required
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="w-full py-6 bg-gradient-to-r from-[#0284c7] via-[#0270a9] to-indigo-800 hover:from-[#0369a1] hover:to-indigo-700 text-white font-black rounded-[2rem] shadow-xl shadow-[#0284c7]/20 transition-all transform hover:-translate-y-1.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed group flex items-center justify-center gap-4 relative overflow-hidden shadow-[0_25px_50px_-15px_rgba(2,132,199,0.4)]"
                                >
                                    <div className="absolute inset-0 bg-white/20 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 skew-x-[45deg]"></div>
                                    {loading ? (
                                        <Loader2 className="w-8 h-8 animate-spin" />
                                    ) : (
                                        <>
                                            <span className="text-2xl">Ingresar al Sistema</span>
                                            <ChevronRight className="w-7 h-7 group-hover:translate-x-1.5 transition-transform" />
                                        </>
                                    )}
                                </button>
                            </form>

                            <div className="mt-16 text-center">
                                <p className="text-[10px] text-slate-400 uppercase tracking-[0.6em] font-black opacity-80">
                                    <span className="opacity-40">System Architect</span> <br />
                                    <span className="text-slate-900 mt-2 inline-block border-b-2 border-[#0284c7]/10 hover:border-[#0284c7] transition-colors cursor-default">Maikol Salas</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
};

export default Login;
