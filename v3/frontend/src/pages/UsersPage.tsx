import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getUsers, createUser, updateUser, type UserData } from '../services/usersService';
import { Loader2, Users, Plus, Edit2, X, Mail, Clock, ArrowLeftRight, Trash2, Sun, Moon, Calendar, Info } from 'lucide-react';

const UsersPage = () => {
    const queryClient = useQueryClient();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserData | null>(null);

    // Form State
    const [formData, setFormData] = useState<Partial<UserData>>({
        first_name: '', last_name: '', username: '', email: '', permission_id: 2, password: '', is_active: 1, cargo: '', turno_7x7: null, turno_tipo: 'Día'
    });
    const [togglingUserId, setTogglingUserId] = useState<number | null>(null);

    // Shift Configuration State
    const [shiftConfigForm, setShiftConfigForm] = useState({
        shift1_alias: 'Turno A',
        shift2_alias: 'Turno B',
        active_shift: 1,
        start_date: '',
        start_hour: '08:00',
        end_hour: '20:00'
    });
    const [isSavingShift, setIsSavingShift] = useState(false);

    const userStr = localStorage.getItem('user');
    const currentUser = userStr ? JSON.parse(userStr) : null;
    const isAdmin = 
        currentUser?.permission_id === 69 || 
        currentUser?.user?.permission_id === 69 || 
        currentUser?.user?.role === 'Administrador' ||
        currentUser?.role === 'Administrador';

    const { data: users, isLoading: usersLoading } = useQuery({
        queryKey: ['users'],
        queryFn: getUsers
    });

    const { data: permissions, isLoading: permsLoading } = useQuery({
        queryKey: ['permissions'],
        queryFn: async () => {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const resp = await fetch('/monitoreoLaboratorio/v3/api/permissions.php', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            return resp.json();
        }
    });

    const { data: shiftData, refetch: refetchShifts } = useQuery({
        queryKey: ['shiftConfig'],
        queryFn: async () => {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const resp = await fetch('/monitoreoLaboratorio/v3/api/shifts.php', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            if (!resp.ok) throw new Error('Failed to fetch shifts');
            return resp.json();
        },
        enabled: isAdmin
    });

    React.useEffect(() => {
        if (shiftData?.config) {
            setShiftConfigForm({
                shift1_alias: shiftData.config.shift1_alias || 'Turno A',
                shift2_alias: shiftData.config.shift2_alias || 'Turno B',
                active_shift: shiftData.config.active_shift || 1,
                start_date: shiftData.config.start_date || '',
                start_hour: shiftData.config.start_hour ? shiftData.config.start_hour.substring(0, 5) : '08:00',
                end_hour: shiftData.config.end_hour ? shiftData.config.end_hour.substring(0, 5) : '20:00'
            });
        }
    }, [shiftData]);

    const handleSaveShiftConfig = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSavingShift(true);
        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const resp = await fetch('/monitoreoLaboratorio/v3/api/shifts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(shiftConfigForm)
            });
            if (!resp.ok) throw new Error('Error al guardar la configuración');
            refetchShifts();
            alert('Configuración guardada exitosamente.');
        } catch (err: any) {
            alert(err.message);
        } finally {
            setIsSavingShift(false);
        }
    };



    const createMutation = useMutation({
        mutationFn: createUser,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['users'] });
            handleCloseModal();
        }
    });

    const updateMutation = useMutation({
        mutationFn: updateUser,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['users'] });
            handleCloseModal();
        }
    });

    const handleOpenModal = (user?: UserData) => {
        if (user) {
            setEditingUser(user);
            setFormData({
                first_name: user.first_name,
                last_name: user.last_name,
                username: user.username,
                email: user.email,
                permission_id: user.permission_id,
                is_active: user.is_active ?? 1,
                password: '', // Don't fill password on edit
                cargo: user.cargo || '',
                turno_7x7: user.turno_7x7 !== undefined ? user.turno_7x7 : null,
                turno_tipo: user.turno_tipo || 'Día'
            });
        } else {
            setEditingUser(null);
            setFormData({
                first_name: '', last_name: '', username: '', email: '', permission_id: permissions?.[0]?.id || 2, is_active: 1, password: '', cargo: '', turno_7x7: null, turno_tipo: 'Día'
            });
        }
        setIsModalOpen(true);
    };

    const handleCloseModal = () => {
        setIsModalOpen(false);
        setEditingUser(null);
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (editingUser) {
            await updateMutation.mutateAsync({ ...formData, id: editingUser.id });
        } else {
            await createMutation.mutateAsync(formData);
        }
    };

    const [activeTab, setActiveTab] = useState<'users' | 'shifts'>('users');
    const [selectedNewMemberS1, setSelectedNewMemberS1] = useState<string>('');
    const [selectedNewMemberS2, setSelectedNewMemberS2] = useState<string>('');
    const [isUpdatingMembers, setIsUpdatingMembers] = useState(false);

    const handleToggleActive = async (user: UserData) => {
        setTogglingUserId(user.id);
        try {
            const nextActiveState = user.is_active === 1 ? 0 : 1;
            await updateMutation.mutateAsync({ id: user.id, is_active: nextActiveState });
        } catch (err) {
            console.error(err);
        } finally {
            setTogglingUserId(null);
        }
    };

    const saveMembersMutation = async (s1Members: { id: number, tipo: string }[], s2Members: { id: number, tipo: string }[]) => {
        setIsUpdatingMembers(true);
        try {
            const token = localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')!).token : '';
            const resp = await fetch('/monitoreoLaboratorio/v3/api/shifts.php?action=update_members', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ shift1_members: s1Members, shift2_members: s2Members })
            });
            if (!resp.ok) throw new Error('Error al actualizar los integrantes');
            refetchShifts();
            queryClient.invalidateQueries({ queryKey: ['users'] });
        } catch (err: any) {
            alert(err.message);
        } finally {
            setIsUpdatingMembers(false);
        }
    };

    const handleMoveMember = async (userId: number, currentShift: number, currentTipo: string) => {
        if (!shiftData) return;
        const s1 = shiftData.shift1_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo })).filter((m: any) => m.id !== userId);
        const s2 = shiftData.shift2_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo })).filter((m: any) => m.id !== userId);
        
        if (currentShift === 1) {
            s2.push({ id: userId, tipo: currentTipo });
        } else {
            s1.push({ id: userId, tipo: currentTipo });
        }
        await saveMembersMutation(s1, s2);
    };

    const handleToggleTipo = async (userId: number, _currentShift: number, currentTipo: string) => {
        if (!shiftData) return;
        const nextTipo = currentTipo === 'Día' ? 'Noche' : 'Día';
        const s1 = shiftData.shift1_members.map((u: any) => ({ id: u.id, tipo: u.id === userId ? nextTipo : u.turno_tipo }));
        const s2 = shiftData.shift2_members.map((u: any) => ({ id: u.id, tipo: u.id === userId ? nextTipo : u.turno_tipo }));
        await saveMembersMutation(s1, s2);
    };

    const handleRemoveMember = async (userId: number) => {
        if (!shiftData) return;
        if (!window.confirm('\u00bfEstas seguro de remover este integrante del turno?')) return;
        const s1 = shiftData.shift1_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo })).filter((m: any) => m.id !== userId);
        const s2 = shiftData.shift2_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo })).filter((m: any) => m.id !== userId);
        await saveMembersMutation(s1, s2);
    };

    const handleAddMember = async (userId: number, targetShift: number, tipo: string) => {
        if (!shiftData) return;
        const s1 = shiftData.shift1_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo }));
        const s2 = shiftData.shift2_members.map((u: any) => ({ id: u.id, tipo: u.turno_tipo }));
        if (targetShift === 1) {
            s1.push({ id: userId, tipo });
        } else {
            s2.push({ id: userId, tipo });
        }
        await saveMembersMutation(s1, s2);
    };

    const getActiveCycleRange = () => {
        if (!shiftData?.config?.start_date) return 'Sin fecha registrada';
        const start = new Date(shiftData.config.start_date + 'T00:00:00');
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        const options: Intl.DateTimeFormatOptions = { weekday: 'short', day: 'numeric', month: 'short' };
        return `${start.toLocaleDateString('es-ES', options)} - ${end.toLocaleDateString('es-ES', options)}`;
    };

    // Build available users list from shift roster data (source of truth),
    // not from the legacy users.turno_7x7 field which can get out of sync.
    const assignedUserIds = new Set([
        ...(shiftData?.shift1_members?.map((m: any) => m.id) ?? []),
        ...(shiftData?.shift2_members?.map((m: any) => m.id) ?? []),
    ]);
    const availableUsers = users?.filter((u) => !assignedUserIds.has(u.id)) ?? [];

    if (usersLoading || permsLoading) {
        return <div className="flex items-center justify-center h-96"><Loader2 className="w-10 h-10 text-primary animate-spin" /></div>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold text-foreground flex items-center gap-3">
                    <Users className="w-8 h-8 text-primary" />
                    Gestión de Usuarios
                </h1>
                {activeTab === 'users' && (
                    <button
                        onClick={() => handleOpenModal()}
                        className="bg-primary hover:bg-primary/90 text-primary-foreground px-4 py-2 rounded-xl font-medium flex items-center gap-2 transition-colors shadow-lg shadow-primary/20"
                    >
                        <Plus className="w-5 h-5" />
                        Nuevo Usuario
                    </button>
                )}
            </div>

            {/* Tabs Navigation */}
            <div className="flex border-b border-border mb-6">
                <button
                    onClick={() => setActiveTab('users')}
                    className={`px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2 ${
                        activeTab === 'users'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    }`}
                >
                    <Users className="w-4 h-4" />
                    Usuarios
                </button>
                {isAdmin && (
                    <button
                        onClick={() => setActiveTab('shifts')}
                        className={`px-6 py-3 font-semibold text-sm transition-all border-b-2 flex items-center gap-2 ${
                            activeTab === 'shifts'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <Clock className="w-4 h-4" />
                        Configuración Turno 7x7
                    </button>
                )}
            </div>

            {activeTab === 'users' ? (
                /* Users Tab Content */
                <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-sm">
                    <table className="w-full text-left">
                        <thead className="bg-muted text-muted-foreground text-sm uppercase tracking-wider">
                            <tr>
                                <th className="p-6 font-medium">Usuario</th>
                                <th className="p-6 font-medium">Email</th>
                                <th className="p-6 font-medium">Rol</th>
                                <th className="p-6 font-medium">Estado</th>
                                <th className="p-6 font-medium text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {users?.map((user) => (
                                <tr key={user.id} className={`hover:bg-muted/50 transition-colors group ${user.is_active !== 1 ? 'opacity-60 bg-muted/10' : ''}`}>
                                    <td className="p-6">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                                {user.first_name.charAt(0)}{user.last_name.charAt(0)}
                                            </div>
                                            <div>
                                                <div className="font-bold text-foreground">{user.first_name} {user.last_name}</div>
                                                <div className="flex items-center gap-2 mt-0.5">
                                                    <span className="text-xs text-muted-foreground font-mono">@{user.username}</span>
                                                    {user.cargo && (
                                                        <span className="text-[9px] font-black uppercase tracking-tight text-amber-600 bg-amber-500/5 px-1.5 py-0.5 rounded border border-amber-500/10">
                                                            {user.cargo}
                                                        </span>
                                                    )}
                                                    {user.turno_7x7 && (
                                                         <span className="text-[9px] font-black uppercase tracking-tight text-blue-600 bg-blue-500/5 px-1.5 py-0.5 rounded border border-blue-500/10 animate-pulse">
                                                             {user.turno_7x7 === 1 
                                                                 ? (shiftData?.config?.shift1_alias || 'Turno 1') 
                                                                 : (shiftData?.config?.shift2_alias || 'Turno 2')}
                                                             {` (${user.turno_tipo || 'Día'})`}
                                                         </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-6 text-foreground">
                                        <div className="flex items-center gap-2">
                                            <Mail className="w-4 h-4 text-muted-foreground" />
                                            {user.email}
                                        </div>
                                    </td>
                                    <td className="p-6">
                                        <span className={`px-2.5 py-1 rounded-full text-xs font-semibold ${
                                            user.role === 'Administrador' 
                                                ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' 
                                                : 'bg-muted text-muted-foreground'
                                        }`}>
                                            {user.role}
                                        </span>
                                    </td>
                                    <td className="p-6">
                                        <button
                                            disabled={togglingUserId === user.id}
                                            onClick={() => handleToggleActive(user)}
                                            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none ${
                                                user.is_active === 1 ? 'bg-emerald-500' : 'bg-muted'
                                            }`}
                                        >
                                            <span
                                                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                    user.is_active === 1 ? 'translate-x-6' : 'translate-x-1'
                                                }`}
                                            />
                                        </button>
                                    </td>
                                    <td className="p-6 text-right">
                                        <button
                                            onClick={() => handleOpenModal(user)}
                                            className="p-2 hover:bg-muted text-muted-foreground hover:text-foreground rounded-lg transition-colors"
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                /* Shift Config Tab Content */
                <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                    
                    {/* Left Column: Config Panel */}
                    <div className="xl:col-span-1 bg-card rounded-2xl border border-border p-5 shadow-sm space-y-5">
                        <div>
                            <h2 className="text-lg font-extrabold text-foreground flex items-center gap-2">
                                <Clock className="w-5 h-5 text-primary" />
                                Ciclo y Horarios
                            </h2>
                            <p className="text-[11px] text-muted-foreground mt-0.5">
                                Configuración de horarios y rotación semanal.
                            </p>
                        </div>

                        {/* Automatic Active Cycle Box */}
                        <div className="p-3 bg-primary/5 border border-primary/10 rounded-xl space-y-1.5">
                            <div className="flex items-center gap-1.5 text-xs font-bold text-primary">
                                <Calendar className="w-4 h-4" />
                                Ciclo Activo (Miércoles a Martes)
                            </div>
                            <div className="text-sm font-extrabold text-foreground tracking-tight capitalize">
                                {getActiveCycleRange()}
                            </div>
                            <div className="text-[10px] text-muted-foreground flex items-start gap-1">
                                <Info className="w-3.5 h-3.5 shrink-0 text-primary/70 mt-0.5" />
                                <span>Calculado automáticamente. El traspaso de turno y la rotación de roles se realizan de forma automática cada semana.</span>
                            </div>
                        </div>

                        <form onSubmit={handleSaveShiftConfig} className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Alias Turno 1</label>
                                    <input
                                        type="text"
                                        value={shiftConfigForm.shift1_alias}
                                        onChange={(e) => setShiftConfigForm({ ...shiftConfigForm, shift1_alias: e.target.value })}
                                        className="w-full px-3 py-1.5 bg-muted/40 border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-xs font-semibold"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Alias Turno 2</label>
                                    <input
                                        type="text"
                                        value={shiftConfigForm.shift2_alias}
                                        onChange={(e) => setShiftConfigForm({ ...shiftConfigForm, shift2_alias: e.target.value })}
                                        className="w-full px-3 py-1.5 bg-muted/40 border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-xs font-semibold"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Hora Entrada</label>
                                    <input
                                        type="time"
                                        value={shiftConfigForm.start_hour}
                                        onChange={(e) => setShiftConfigForm({ ...shiftConfigForm, start_hour: e.target.value })}
                                        className="w-full px-3 py-1.5 bg-muted/40 border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-xs font-semibold"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Hora Salida</label>
                                    <input
                                        type="time"
                                        value={shiftConfigForm.end_hour}
                                        onChange={(e) => setShiftConfigForm({ ...shiftConfigForm, end_hour: e.target.value })}
                                        className="w-full px-3 py-1.5 bg-muted/40 border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-xs font-semibold"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-1">Turno Activo</label>
                                <select
                                    value={shiftConfigForm.active_shift}
                                    onChange={(e) => setShiftConfigForm({ ...shiftConfigForm, active_shift: parseInt(e.target.value) })}
                                    className="w-full px-3 py-1.5 bg-muted/40 border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-xs font-semibold"
                                >
                                    <option value="1">{shiftConfigForm.shift1_alias || 'Turno A'}</option>
                                    <option value="2">{shiftConfigForm.shift2_alias || 'Turno B'}</option>
                                </select>
                            </div>

                            {/* Hidden standard start_date state value, updated implicitly */}
                            <input type="hidden" value={shiftConfigForm.start_date} />

                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={isSavingShift}
                                    className="w-full py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-xl text-xs shadow-md transition-all flex items-center justify-center gap-1.5"
                                >
                                    {isSavingShift ? (
                                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                                    ) : (
                                        'Guardar Configuración'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Right Column: Rosters Panel */}
                    <div className="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        
                        {/* Turno 1 Card */}
                        <div className="bg-card rounded-2xl border border-border p-5 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-border pb-2.5">
                                <h3 className="text-sm font-extrabold text-foreground flex items-center gap-2">
                                    <span className={`w-2.5 h-2.5 rounded-full ${shiftConfigForm.active_shift === 1 ? 'bg-emerald-500 animate-pulse' : 'bg-muted'}`}></span>
                                    {shiftConfigForm.shift1_alias}
                                    {shiftConfigForm.active_shift === 1 && (
                                        <span className="text-[9px] font-black uppercase text-emerald-600 bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10">Activo</span>
                                    )}
                                </h3>
                                <span className="text-[10px] font-bold text-muted-foreground bg-muted px-2 py-0.5 rounded-full">
                                    {shiftData?.shift1_members?.length || 0} Integrantes
                                </span>
                            </div>

                            {/* Group by Day/Night */}
                            <div className="space-y-4 min-h-[220px]">
                                {/* ☀️ DIA */}
                                <div className="space-y-1.5">
                                    <div className="text-[10px] font-bold uppercase tracking-wider text-amber-600 flex items-center gap-1">
                                        <Sun className="w-3.5 h-3.5" />
                                        Jornada Día
                                    </div>
                                    <div className="space-y-1">
                                        {shiftData?.shift1_members?.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).map((member: any) => (
                                            <div key={member.id} className="flex items-center justify-between p-2 bg-muted/20 border border-border/40 rounded-lg hover:bg-muted/40 transition-colors">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <div className="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                                        {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-xs font-bold text-foreground truncate">{member.first_name} {member.last_name}</div>
                                                        <div className="text-[9px] text-muted-foreground truncate">@{member.username}</div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-0.5">
                                                    <button
                                                        onClick={() => handleToggleTipo(member.id, 1, 'Día')}
                                                        title="Cambiar a Jornada Noche"
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-purple-600 rounded-md transition-colors"
                                                    >
                                                        <Moon className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleMoveMember(member.id, 1, 'Día')}
                                                        title={`Mover a ${shiftConfigForm.shift2_alias}`}
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-primary rounded-md transition-colors"
                                                    >
                                                        <ArrowLeftRight className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleRemoveMember(member.id)}
                                                        title="Remover de turnos"
                                                        className="p-1 hover:bg-rose-500/10 text-muted-foreground hover:text-rose-600 rounded-md transition-colors"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {shiftData?.shift1_members?.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                                            <div className="text-[10px] italic text-muted-foreground py-1 pl-1">Sin ingenieros de día</div>
                                        )}
                                    </div>
                                </div>

                                {/* 🌙 NOCHE */}
                                <div className="space-y-1.5">
                                    <div className="text-[10px] font-bold uppercase tracking-wider text-purple-600 flex items-center gap-1">
                                        <Moon className="w-3.5 h-3.5" />
                                        Jornada Noche
                                    </div>
                                    <div className="space-y-1">
                                        {shiftData?.shift1_members?.filter((m: any) => m.turno_tipo === 'Noche').map((member: any) => (
                                            <div key={member.id} className="flex items-center justify-between p-2 bg-muted/20 border border-border/40 rounded-lg hover:bg-muted/40 transition-colors">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <div className="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs">
                                                        {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-xs font-bold text-foreground truncate">{member.first_name} {member.last_name}</div>
                                                        <div className="text-[9px] text-muted-foreground truncate">@{member.username}</div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-0.5">
                                                    <button
                                                        onClick={() => handleToggleTipo(member.id, 1, 'Noche')}
                                                        title="Cambiar a Jornada Día"
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-amber-500 rounded-md transition-colors"
                                                    >
                                                        <Sun className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleMoveMember(member.id, 1, 'Noche')}
                                                        title={`Mover a ${shiftConfigForm.shift2_alias}`}
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-primary rounded-md transition-colors"
                                                    >
                                                        <ArrowLeftRight className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleRemoveMember(member.id)}
                                                        title="Remover de turnos"
                                                        className="p-1 hover:bg-rose-500/10 text-muted-foreground hover:text-rose-600 rounded-md transition-colors"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {shiftData?.shift1_members?.filter((m: any) => m.turno_tipo === 'Noche').length === 0 && (
                                            <div className="text-[10px] italic text-muted-foreground py-1 pl-1">Sin ingenieros de noche</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Add Member Dropdown Inline S1 */}
                            <div className="pt-3 border-t border-border space-y-1.5">
                                <label className="block text-[9px] font-bold text-muted-foreground uppercase">Añadir Integrante a {shiftConfigForm.shift1_alias}</label>
                                <div className="flex gap-1.5">
                                    <select
                                        value={selectedNewMemberS1}
                                        onChange={(e) => setSelectedNewMemberS1(e.target.value)}
                                        className="flex-1 px-2 py-1 bg-muted/40 border border-input rounded-lg text-xs outline-none text-foreground"
                                    >
                                        <option value="">-- Seleccionar --</option>
                                        {availableUsers.map((u) => (
                                            <option key={u.id} value={u.id}>{u.first_name} {u.last_name}</option>
                                        ))}
                                    </select>
                                    <button
                                        type="button"
                                        disabled={!selectedNewMemberS1 || isUpdatingMembers}
                                        onClick={() => {
                                            handleAddMember(parseInt(selectedNewMemberS1), 1, 'Día');
                                            setSelectedNewMemberS1('');
                                        }}
                                        title="Añadir como Día"
                                        className="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[10px] font-bold transition-all flex items-center gap-0.5 shrink-0"
                                    >
                                        <Sun className="w-3 h-3" /> Día
                                    </button>
                                    <button
                                        type="button"
                                        disabled={!selectedNewMemberS1 || isUpdatingMembers}
                                        onClick={() => {
                                            handleAddMember(parseInt(selectedNewMemberS1), 1, 'Noche');
                                            setSelectedNewMemberS1('');
                                        }}
                                        title="Añadir como Noche"
                                        className="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[10px] font-bold transition-all flex items-center gap-0.5 shrink-0"
                                    >
                                        <Moon className="w-3 h-3" /> Noche
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Turno 2 Card */}
                        <div className="bg-card rounded-2xl border border-border p-5 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-border pb-2.5">
                                <h3 className="text-sm font-extrabold text-foreground flex items-center gap-2">
                                    <span className={`w-2.5 h-2.5 rounded-full ${shiftConfigForm.active_shift === 2 ? 'bg-emerald-500 animate-pulse' : 'bg-muted'}`}></span>
                                    {shiftConfigForm.shift2_alias}
                                    {shiftConfigForm.active_shift === 2 && (
                                        <span className="text-[9px] font-black uppercase text-emerald-600 bg-emerald-500/5 px-1.5 py-0.5 rounded border border-emerald-500/10">Activo</span>
                                    )}
                                </h3>
                                <span className="text-[10px] font-bold text-muted-foreground bg-muted px-2 py-0.5 rounded-full">
                                    {shiftData?.shift2_members?.length || 0} Integrantes
                                </span>
                            </div>

                            {/* Group by Day/Night */}
                            <div className="space-y-4 min-h-[220px]">
                                {/* ☀️ DIA */}
                                <div className="space-y-1.5">
                                    <div className="text-[10px] font-bold uppercase tracking-wider text-amber-600 flex items-center gap-1">
                                        <Sun className="w-3.5 h-3.5" />
                                        Jornada Día
                                    </div>
                                    <div className="space-y-1">
                                        {shiftData?.shift2_members?.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).map((member: any) => (
                                            <div key={member.id} className="flex items-center justify-between p-2 bg-muted/20 border border-border/40 rounded-lg hover:bg-muted/40 transition-colors">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <div className="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                                        {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-xs font-bold text-foreground truncate">{member.first_name} {member.last_name}</div>
                                                        <div className="text-[9px] text-muted-foreground truncate">@{member.username}</div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-0.5">
                                                    <button
                                                        onClick={() => handleToggleTipo(member.id, 2, 'Día')}
                                                        title="Cambiar a Jornada Noche"
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-purple-600 rounded-md transition-colors"
                                                    >
                                                        <Moon className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleMoveMember(member.id, 2, 'Día')}
                                                        title={`Mover a ${shiftConfigForm.shift1_alias}`}
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-primary rounded-md transition-colors"
                                                    >
                                                        <ArrowLeftRight className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleRemoveMember(member.id)}
                                                        title="Remover de turnos"
                                                        className="p-1 hover:bg-rose-500/10 text-muted-foreground hover:text-rose-600 rounded-md transition-colors"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {shiftData?.shift2_members?.filter((m: any) => m.turno_tipo === 'Día' || !m.turno_tipo).length === 0 && (
                                            <div className="text-[10px] italic text-muted-foreground py-1 pl-1">Sin ingenieros de día</div>
                                        )}
                                    </div>
                                </div>

                                {/* 🌙 NOCHE */}
                                <div className="space-y-1.5">
                                    <div className="text-[10px] font-bold uppercase tracking-wider text-purple-600 flex items-center gap-1">
                                        <Moon className="w-3.5 h-3.5" />
                                        Jornada Noche
                                    </div>
                                    <div className="space-y-1">
                                        {shiftData?.shift2_members?.filter((m: any) => m.turno_tipo === 'Noche').map((member: any) => (
                                            <div key={member.id} className="flex items-center justify-between p-2 bg-muted/20 border border-border/40 rounded-lg hover:bg-muted/40 transition-colors">
                                                <div className="flex items-center gap-2.5 min-w-0">
                                                    <div className="w-7 h-7 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs">
                                                        {member.first_name.charAt(0)}{member.last_name.charAt(0)}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="text-xs font-bold text-foreground truncate">{member.first_name} {member.last_name}</div>
                                                        <div className="text-[9px] text-muted-foreground truncate">@{member.username}</div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-0.5">
                                                    <button
                                                        onClick={() => handleToggleTipo(member.id, 2, 'Noche')}
                                                        title="Cambiar a Jornada Día"
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-amber-500 rounded-md transition-colors"
                                                    >
                                                        <Sun className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleMoveMember(member.id, 2, 'Noche')}
                                                        title={`Mover a ${shiftConfigForm.shift1_alias}`}
                                                        className="p-1 hover:bg-muted text-muted-foreground hover:text-primary rounded-md transition-colors"
                                                    >
                                                        <ArrowLeftRight className="w-3.5 h-3.5" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleRemoveMember(member.id)}
                                                        title="Remover de turnos"
                                                        className="p-1 hover:bg-rose-500/10 text-muted-foreground hover:text-rose-600 rounded-md transition-colors"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                        {shiftData?.shift2_members?.filter((m: any) => m.turno_tipo === 'Noche').length === 0 && (
                                            <div className="text-[10px] italic text-muted-foreground py-1 pl-1">Sin ingenieros de noche</div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Add Member Dropdown Inline S2 */}
                            <div className="pt-3 border-t border-border space-y-1.5">
                                <label className="block text-[9px] font-bold text-muted-foreground uppercase">Añadir Integrante a {shiftConfigForm.shift2_alias}</label>
                                <div className="flex gap-1.5">
                                    <select
                                        value={selectedNewMemberS2}
                                        onChange={(e) => setSelectedNewMemberS2(e.target.value)}
                                        className="flex-1 px-2 py-1 bg-muted/40 border border-input rounded-lg text-xs outline-none text-foreground"
                                    >
                                        <option value="">-- Seleccionar --</option>
                                        {availableUsers.map((u) => (
                                            <option key={u.id} value={u.id}>{u.first_name} {u.last_name}</option>
                                        ))}
                                    </select>
                                    <button
                                        type="button"
                                        disabled={!selectedNewMemberS2 || isUpdatingMembers}
                                        onClick={() => {
                                            handleAddMember(parseInt(selectedNewMemberS2), 2, 'Día');
                                            setSelectedNewMemberS2('');
                                        }}
                                        title="Añadir como Día"
                                        className="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[10px] font-bold transition-all flex items-center gap-0.5 shrink-0"
                                    >
                                        <Sun className="w-3 h-3" /> Día
                                    </button>
                                    <button
                                        type="button"
                                        disabled={!selectedNewMemberS2 || isUpdatingMembers}
                                        onClick={() => {
                                            handleAddMember(parseInt(selectedNewMemberS2), 2, 'Noche');
                                            setSelectedNewMemberS2('');
                                        }}
                                        title="Añadir como Noche"
                                        className="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-[10px] font-bold transition-all flex items-center gap-0.5 shrink-0"
                                    >
                                        <Moon className="w-3 h-3" /> Noche
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            )}

            {/* Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div className="bg-card w-full max-w-lg rounded-2xl border border-border p-6 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
                        <button
                            onClick={handleCloseModal}
                            className="absolute top-4 right-4 p-1.5 text-muted-foreground hover:text-foreground rounded-lg hover:bg-muted transition-colors"
                        >
                            <X className="w-5 h-5" />
                        </button>
                        <h2 className="text-xl font-bold text-foreground mb-6">
                            {editingUser ? 'Editar Usuario' : 'Nuevo Usuario'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Nombre</label>
                                    <input
                                        type="text"
                                        value={formData.first_name || ''}
                                        onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Apellido</label>
                                    <input
                                        type="text"
                                        value={formData.last_name || ''}
                                        onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">Nombre de Usuario</label>
                                <input
                                    type="text"
                                    value={formData.username || ''}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">Email</label>
                                <input
                                    type="email"
                                    value={formData.email || ''}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Contraseña</label>
                                    <input
                                        type="password"
                                        value={formData.password || ''}
                                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                        placeholder={editingUser ? 'Dejar en blanco para no cambiar' : ''}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                        required={!editingUser}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Rol / Permiso</label>
                                    <select
                                        value={formData.permission_id || 2}
                                        onChange={(e) => setFormData({ ...formData, permission_id: parseInt(e.target.value) })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    >
                                        {permissions?.map((p: any) => (
                                            <option key={p.id} value={p.id}>{p.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Cargo</label>
                                    <input
                                        type="text"
                                        value={formData.cargo || ''}
                                        onChange={(e) => setFormData({ ...formData, cargo: e.target.value })}
                                        placeholder="Ej: Ingeniero de soporte 7x7"
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Grupo de Turno 7x7</label>
                                    <select
                                        value={formData.turno_7x7 || ''}
                                        onChange={(e) => setFormData({ ...formData, turno_7x7: e.target.value ? parseInt(e.target.value) : null })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    >
                                        <option value="">Ninguno / No aplica</option>
                                        <option value="1">Turno 1</option>
                                        <option value="2">Turno 2</option>
                                    </select>
                                </div>
                            </div>

                            {formData.turno_7x7 !== null && formData.turno_7x7 !== undefined && (
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Jornada del Turno</label>
                                    <select
                                        value={formData.turno_tipo || 'Día'}
                                        onChange={(e) => setFormData({ ...formData, turno_tipo: e.target.value })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    >
                                        <option value="Día">☀️ Turno Día</option>
                                        <option value="Noche">🌙 Turno Noche</option>
                                    </select>
                                </div>
                            )}

                            <div className="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-xl">
                                <div>
                                    <div className="font-semibold text-sm text-foreground">Usuario Activo</div>
                                    <div className="text-xs text-muted-foreground">Permitir el ingreso al sistema</div>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setFormData({ ...formData, is_active: formData.is_active === 1 ? 0 : 1 })}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none ${
                                        formData.is_active === 1 ? 'bg-emerald-500' : 'bg-muted'
                                    }`}
                                >
                                    <span
                                        className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                            formData.is_active === 1 ? 'translate-x-6' : 'translate-x-1'
                                        }`}
                                    />
                                </button>
                            </div>

                            <div className="pt-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={handleCloseModal}
                                    className="px-4 py-2 text-muted-foreground hover:text-foreground font-medium transition-colors"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    className="px-6 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-xl shadow-lg shadow-primary/20 transition-all"
                                >
                                    {editingUser ? 'Actualizar' : 'Crear Usuario'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default UsersPage;
