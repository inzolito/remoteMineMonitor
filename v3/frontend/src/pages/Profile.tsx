import { useState, useEffect, useContext } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { 
  User, Shield, Cloud, Settings, Key, AlertCircle, 
  CheckCircle2, Loader2, Unlink, Save 
} from 'lucide-react';
import { cn } from '../lib/utils';
import { AuthContext } from '../context/AuthContext';

// Types
interface ProfileData {
  id: number;
  username: string;
  first_name: string;
  last_name: string;
  email: string;
  cargo: string | null;
  salesforce_user_id: string | null;
  teams_webhook_url: string | null;
}

export default function Profile() {
  const queryClient = useQueryClient();
  const userStr = localStorage.getItem('user');
  const userObj = userStr ? JSON.parse(userStr) : null;
  const token = userObj?.token;
  const auth = useContext(AuthContext);

  // React Query for profile data
  const { data: profileResponse, isLoading, isError, refetch } = useQuery({
    queryKey: ['profile'],
    queryFn: async () => {
      const res = await fetch('/monitoreoLaboratorio/v3/api/profile.php', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      if (res.status === 401) {
        auth?.logout();
        throw new Error('Sesión vencida');
      }
      if (!res.ok) throw new Error('Error al cargar perfil');
      return res.json();
    }
  });

  const profile: ProfileData | undefined = profileResponse?.data;

  // Personal Info Form State
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [cargo, setCargo] = useState('');
  const [isEditingInfo, setIsEditingInfo] = useState(false);
  const [infoMessage, setInfoMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  const [isSavingInfo, setIsSavingInfo] = useState(false);

  // Sync Form States with Loaded Profile
  useEffect(() => {
    if (profile) {
      setFirstName(profile.first_name || '');
      setLastName(profile.last_name || '');
      setEmail(profile.email || '');
      setCargo(profile.cargo || '');
    }
  }, [profile]);

  // Password Form State
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [pwMessage, setPwMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  const [isSavingPw, setIsSavingPw] = useState(false);

  // Teams Webhook Form State (Mockup Active)

  // Salesforce Linkage State
  const [sfMessage, setSfMessage] = useState<{ type: 'success' | 'error', text: string } | null>(null);
  const [isLinking, setIsLinking] = useState(false);

  // Handlers
  const handleSaveInfo = async (e: React.FormEvent) => {
    e.preventDefault();
    setInfoMessage(null);
    setIsSavingInfo(true);

    try {
      const res = await fetch('/monitoreoLaboratorio/v3/api/profile.php?action=update_personal', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          first_name: firstName,
          last_name: lastName,
          email: email,
          cargo: cargo
        })
      });
      const result = await res.json();

      if (result.status === 'success') {
        setInfoMessage({ type: 'success', text: 'Datos personales actualizados correctamente.' });
        setIsEditingInfo(false);
        refetch();
        queryClient.invalidateQueries({ queryKey: ['profile'] });
      } else {
        setInfoMessage({ type: 'error', text: result.message || 'Error al actualizar.' });
      }
    } catch (err) {
      setInfoMessage({ type: 'error', text: 'Ocurrió un problema de red al intentar actualizar.' });
    } finally {
      setIsSavingInfo(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setPwMessage(null);

    if (newPassword !== confirmPassword) {
      setPwMessage({ type: 'error', text: 'La nueva contraseña y su confirmación no coinciden.' });
      return;
    }

    if (newPassword.length < 4) {
      setPwMessage({ type: 'error', text: 'La contraseña debe tener al menos 4 caracteres.' });
      return;
    }

    setIsSavingPw(true);

    try {
      const res = await fetch('/monitoreoLaboratorio/v3/api/profile.php?action=change_password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          new_password: newPassword
        })
      });
      const result = await res.json();

      if (result.status === 'success') {
        setPwMessage({ type: 'success', text: 'Contraseña cambiada con éxito.' });
        setNewPassword('');
        setConfirmPassword('');
      } else {
        setPwMessage({ type: 'error', text: result.message || 'Error al cambiar contraseña.' });
      }
    } catch (err) {
      setPwMessage({ type: 'error', text: 'Ocurrió un problema de red.' });
    } finally {
      setIsSavingPw(false);
    }
  };

  // handleSaveTeamsWebhook removed (Mockup Active)

  const handleLinkSfUser = async () => {
    setSfMessage(null);
    setIsLinking(true);

    try {
      const res = await fetch('/monitoreoLaboratorio/v3/api/user_link_sf.php?action=link', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      const result = await res.json();

      if (res.ok && result.status === 'success') {
        setSfMessage({ type: 'success', text: result.message || 'Cuenta vinculada exitosamente a Salesforce.' });
        refetch();
        queryClient.invalidateQueries({ queryKey: ['profile'] });
        queryClient.invalidateQueries({ queryKey: ['homeMetrics'] });
      } else {
        setSfMessage({ type: 'error', text: result.message || 'Error al vincular.' });
      }
    } catch (err) {
      setSfMessage({ type: 'error', text: 'Error al conectar con la API.' });
    } finally {
      setIsLinking(false);
    }
  };

  const handleUnlinkSf = async () => {
    if (!window.confirm('¿Seguro que deseas desvincular tu cuenta de Salesforce? Dejarás de recibir tus tickets en tiempo real.')) return;
    setSfMessage(null);

    try {
      const res = await fetch('/monitoreoLaboratorio/v3/api/user_link_sf.php?action=unlink', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      const result = await res.json();

      if (result.status === 'success') {
        setSfMessage({ type: 'success', text: 'Cuenta desvinculada exitosamente.' });
        refetch();
        queryClient.invalidateQueries({ queryKey: ['profile'] });
        queryClient.invalidateQueries({ queryKey: ['homeMetrics'] });
      } else {
        setSfMessage({ type: 'error', text: result.message || 'Error al desvincular.' });
      }
    } catch (err) {
      setSfMessage({ type: 'error', text: 'Error de red al desvincular.' });
    }
  };

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[70vh] gap-4">
        <Loader2 className="w-12 h-12 text-primary animate-spin" />
        <p className="text-sm font-bold text-muted-foreground animate-pulse">Cargando panel de perfil...</p>
      </div>
    );
  }

  if (isError || !profile) {
    return (
      <div className="p-8 max-w-xl mx-auto text-center flex flex-col items-center gap-4 bg-destructive/10 border border-destructive/20 rounded-2xl">
        <AlertCircle className="w-12 h-12 text-destructive animate-bounce" />
        <h2 className="font-extrabold text-xl">Error de Conexión</h2>
        <p className="text-sm text-muted-foreground leading-relaxed">
          No se pudo obtener la información de tu perfil. Asegúrate de tener una sesión activa o recarga la página.
        </p>
        <button onClick={() => refetch()} className="px-5 py-2 bg-destructive hover:bg-destructive/90 text-white font-bold text-sm rounded-lg transition-colors shadow">
          Reintentar Carga
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-300">
      
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-border pb-6">
        <div className="flex items-center gap-4">
          <div className="w-14 h-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-inner">
            <User className="w-7 h-7" />
          </div>
          <div>
            <h1 className="text-2xl font-black tracking-tight text-foreground flex items-center gap-2">
              Mi Perfil
              <span className="text-[10px] bg-primary/10 border border-primary/20 text-primary font-black uppercase px-2.5 py-0.5 rounded-full shadow-sm">{profile.username}</span>
            </h1>
            <p className="text-xs text-muted-foreground mt-0.5">Administra tus configuraciones de usuario, accesos de Salesforce y webhooks de emergencias.</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Left Column: Personal Info & Password */}
        <div className="lg:col-span-2 space-y-8">
          
          {/* Card: Personal Info */}
          <div className="bg-card border border-border rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <div className="p-6 border-b border-border flex items-center justify-between bg-muted/10">
              <div className="flex items-center gap-3">
                <Settings className="w-5 h-5 text-primary" />
                <h3 className="font-extrabold text-base text-foreground">Información Personal</h3>
              </div>
              <button 
                onClick={() => setIsEditingInfo(!isEditingInfo)}
                className={cn(
                  "text-xs px-3.5 py-1.5 rounded-lg border font-black transition-all",
                  isEditingInfo 
                    ? "bg-muted text-muted-foreground hover:bg-muted/80 border-border" 
                    : "bg-primary hover:bg-primary/95 text-primary-foreground border-transparent shadow-sm hover:shadow"
                )}
              >
                {isEditingInfo ? 'Cancelar' : 'Editar Campos'}
              </button>
            </div>
            
            <form onSubmit={handleSaveInfo} className="p-6 space-y-5">
              {infoMessage && (
                <div className={cn(
                  "p-4 rounded-xl text-xs font-bold border flex items-center gap-3 animate-in fade-in duration-200",
                  infoMessage.type === 'success' 
                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' 
                    : 'bg-destructive/10 text-destructive border-destructive/20'
                )}>
                  {infoMessage.type === 'success' ? <CheckCircle2 className="w-4 h-4 flex-shrink-0" /> : <AlertCircle className="w-4 h-4 flex-shrink-0" />}
                  <span>{infoMessage.text}</span>
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Nombre</label>
                  <input 
                    type="text"
                    disabled={!isEditingInfo}
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-60 disabled:bg-muted/30 transition-all text-sm font-semibold"
                    placeholder="Tu nombre"
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Apellido</label>
                  <input 
                    type="text"
                    disabled={!isEditingInfo}
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-60 disabled:bg-muted/30 transition-all text-sm font-semibold"
                    placeholder="Tu apellido"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Correo Electrónico</label>
                  <input 
                    type="email"
                    disabled={!isEditingInfo}
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary disabled:opacity-60 disabled:bg-muted/30 transition-all text-sm font-semibold"
                    placeholder="ejemplo@faena.cl"
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Cargo</label>
                  {!isEditingInfo ? (
                    <input 
                      type="text"
                      disabled
                      value={cargo || 'Sin cargo asignado'}
                      className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-muted/30 opacity-60 transition-all text-sm font-semibold"
                    />
                  ) : (
                    <select
                      value={cargo}
                      onChange={(e) => setCargo(e.target.value)}
                      className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm font-semibold cursor-pointer hover:border-slate-300"
                    >
                      <option value="">Selecciona tu cargo</option>
                      <option value="Ingeniero de soporte 7x7">Ingeniero de soporte 7x7</option>
                      <option value="Ingeniero de soporte">Ingeniero de soporte</option>
                      <option value="Lider de soporte">Lider de soporte</option>
                    </select>
                  )}
                </div>
              </div>

              {isEditingInfo && (
                <div className="pt-2 flex justify-end">
                  <button 
                    type="submit"
                    disabled={isSavingInfo}
                    className="bg-primary hover:bg-primary/95 text-primary-foreground font-black text-sm px-5 py-2.5 rounded-xl transition-all shadow hover:shadow-md flex items-center gap-2 enabled:active:scale-95 disabled:opacity-50"
                  >
                    {isSavingInfo ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                    <span>Guardar Cambios</span>
                  </button>
                </div>
              )}
            </form>
          </div>

          {/* Card: Change Password */}
          <div className="bg-card border border-border rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <div className="p-6 border-b border-border flex items-center gap-3 bg-muted/10">
              <Key className="w-5 h-5 text-primary" />
              <h3 className="font-extrabold text-base text-foreground">Cambio de Contraseña</h3>
            </div>
            
            <form onSubmit={handleChangePassword} className="p-6 space-y-5">
              {pwMessage && (
                <div className={cn(
                  "p-4 rounded-xl text-xs font-bold border flex items-center gap-3 animate-in fade-in duration-200",
                  pwMessage.type === 'success' 
                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' 
                    : 'bg-destructive/10 text-destructive border-destructive/20'
                )}>
                  {pwMessage.type === 'success' ? <CheckCircle2 className="w-4 h-4 flex-shrink-0" /> : <AlertCircle className="w-4 h-4 flex-shrink-0" />}
                  <span>{pwMessage.text}</span>
                </div>
              )}

              {/* Contraseña Actual field removed by user request */}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Nueva Contraseña</label>
                  <input 
                    type="password"
                    value={newPassword}
                    onChange={(e) => setNewPassword(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm font-semibold"
                    placeholder="••••••••"
                    required
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-[11px] font-bold text-muted-foreground uppercase tracking-wider">Confirmar Nueva Contraseña</label>
                  <input 
                    type="password"
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    className="w-full px-3.5 py-2.5 rounded-xl border border-border bg-background focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm font-semibold"
                    placeholder="••••••••"
                    required
                  />
                </div>
              </div>

              <div className="pt-2 flex justify-end">
                <button 
                  type="submit"
                  disabled={isSavingPw}
                  className="bg-primary hover:bg-primary/95 text-primary-foreground font-black text-sm px-5 py-2.5 rounded-xl transition-all shadow hover:shadow-md flex items-center gap-2 enabled:active:scale-95 disabled:opacity-50"
                >
                  {isSavingPw ? <Loader2 className="w-4 h-4 animate-spin" /> : <Shield className="w-4 h-4" />}
                  <span>Actualizar Contraseña</span>
                </button>
              </div>
            </form>
          </div>

        </div>

        {/* Right Column: Salesforce Integration & Microsoft Teams */}
        <div className="space-y-8">
          
          {/* Card: Salesforce Linkage */}
          <div className="bg-card border border-border rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <div className="p-6 border-b border-border flex items-center gap-3 bg-muted/10">
              <Cloud className="w-5 h-5 text-sky-500" />
              <h3 className="font-extrabold text-base text-foreground">Vinculación con Salesforce</h3>
            </div>
            
            <div className="p-6 space-y-6">
              {sfMessage && (
                <div className={cn(
                  "p-4 rounded-xl text-xs font-bold border flex items-center gap-3 animate-in fade-in duration-200",
                  sfMessage.type === 'success' 
                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' 
                    : 'bg-destructive/10 text-destructive border-destructive/20'
                )}>
                  {sfMessage.type === 'success' ? <CheckCircle2 className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
                  <span>{sfMessage.text}</span>
                </div>
              )}

              {profile.salesforce_user_id ? (
                // Linked State
                <div className="space-y-5 animate-in fade-in zoom-in-95 duration-200">
                  <div className="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20 flex flex-col items-center text-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-sky-500/20 flex items-center justify-center text-sky-600 shadow">
                      <Cloud className="w-6 h-6 animate-pulse" />
                    </div>
                    <div>
                      <p className="text-[11px] font-bold text-sky-600 uppercase tracking-widest leading-none">Vinculado Exitosamente</p>
                      <p className="text-sm font-black text-slate-800 dark:text-slate-100 mt-2">ID Salesforce: <span className="font-mono text-xs bg-white dark:bg-slate-900 border border-sky-500/20 px-2 py-0.5 rounded shadow-inner">{profile.salesforce_user_id}</span></p>
                      <p className="text-[10px] text-muted-foreground mt-2 leading-relaxed">Ahora puedes visualizar tus tickets en tiempo real directamente del sistema de monitoreo </p>
                    </div>
                  </div>

                  <button 
                    onClick={handleUnlinkSf}
                    className="w-full bg-destructive/10 hover:bg-destructive/20 text-destructive border border-destructive/20 font-black text-sm px-4 py-2.5 rounded-xl transition-all flex items-center justify-center gap-2"
                  >
                    <Unlink className="w-4 h-4" />
                    <span>Desvincular Cuenta</span>
                  </button>
                </div>
              ) : (
                // Unlinked State
                <div className="space-y-5 animate-in fade-in zoom-in-95 duration-200">
                  <div className="p-4 rounded-xl bg-muted/40 border border-border flex flex-col items-center text-center gap-3">
                    <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
                      <Cloud className="w-6 h-6" />
                    </div>
                    <div>
                      <p className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest leading-none">Sin Integración</p>
                      <p className="text-sm font-black text-foreground mt-2">¿Quieres ver tus tickets?</p>
                      <p className="text-[10px] text-muted-foreground mt-2 leading-relaxed">Vincula tu cuenta para cargar dinámicamente tu cola de soporte de Salesforce directamente en la pantalla de inicio.</p>
                    </div>
                  </div>

                  <button 
                    onClick={handleLinkSfUser}
                    disabled={isLinking}
                    className="w-full bg-[#00A1E0] hover:bg-[#00A1E0]/90 text-white font-black text-sm px-4 py-2.5 rounded-xl transition-all shadow hover:shadow-md flex items-center justify-center gap-2 disabled:opacity-55 disabled:cursor-not-allowed"
                  >
                    {isLinking ? (
                      <Loader2 className="w-5 h-5 animate-spin" />
                    ) : (
                      <svg viewBox="0 0 24 24" className="w-5 h-5 fill-current text-white animate-pulse" xmlns="http://www.w3.org/2000/svg">
                          <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                      </svg>
                    )}
                    <span>{isLinking ? 'Vinculando...' : 'Vincular Perfil Salesforce'}</span>
                  </button>
                </div>
              )}
            </div>
          </div>

          {/* Card: Microsoft Teams Integration (Base / Slack) */}
          <div className="bg-card border border-border rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <div className="p-6 border-b border-border flex items-center gap-3 bg-muted/10">
              <Shield className="w-5 h-5 text-[#464EB8]" />
              <h3 className="font-extrabold text-base text-foreground">Vincular con teams</h3>
            </div>
            
            <div className="p-6 space-y-6">
              <div className="space-y-5 animate-in fade-in zoom-in-95 duration-200">
                <div className="p-4 rounded-xl bg-[#464EB8]/5 border border-[#464EB8]/10 flex flex-col items-center text-center gap-3">
                  <div className="w-12 h-12 rounded-full bg-[#464EB8]/10 flex items-center justify-center text-[#464EB8] shadow-sm">
                    <svg viewBox="0 0 24 24" className="w-6 h-6 fill-current animate-pulse" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 14H4v-4h11v4zm0-5H4V9h11v4zm5 5h-4V9h4v9z"/>
                    </svg>
                  </div>
                  <div>
                    <span className="text-[9px] px-2.5 py-0.5 rounded-full bg-[#464EB8]/10 text-[#464EB8] font-black border border-[#464EB8]/20 uppercase tracking-widest leading-none">Próximamente</span>
                    <p className="text-sm font-black text-foreground mt-2">Alertas en Canales de Emergencia</p>
                    <p className="text-[10px] text-muted-foreground mt-2 leading-relaxed">
                      Conecta tu cuenta de Teams y hagamos que el sistema sea cada vez mejor :')
                    </p>
                  </div>
                </div>

                <a 
                  href="https://teams.microsoft.com/" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="w-full bg-[#464EB8] hover:bg-[#464EB8]/90 text-white font-black text-sm px-4 py-2.5 rounded-xl transition-all shadow hover:shadow-md flex items-center justify-center gap-2 enabled:active:scale-95"
                >
                  <svg viewBox="0 0 24 24" className="w-5 h-5 fill-current text-white animate-pulse" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 14H4v-4h11v4zm0-5H4V9h11v4zm5 5h-4V9h4v9z"/>
                  </svg>
                  <span>Vincular con teams</span>
                </a>
              </div>
            </div>
          </div>

        </div>

      </div>

      {/* SALESFORCE MODAL SEARCH REMOVED (NOW AUTOMATICALLY LINKED BY REGISTERED EMAIL) */}

    </div>
  );
}
