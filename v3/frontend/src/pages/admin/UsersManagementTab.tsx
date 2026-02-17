import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getUsers, createUser, updateUser, type UserData } from '../../services/usersService';
import { Loader2, Users, Plus, Edit2, X, Mail, Shield } from 'lucide-react';

const UsersManagementTab = () => {
    const queryClient = useQueryClient();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserData | null>(null);

    const [formData, setFormData] = useState<Partial<UserData>>({
        first_name: '', last_name: '', username: '', email: '', permission_id: 2, password: ''
    });

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
                password: ''
            });
        } else {
            setEditingUser(null);
            setFormData({
                first_name: '', last_name: '', username: '', email: '', permission_id: permissions?.[0]?.id || 2, password: ''
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

    if (usersLoading || permsLoading) {
        return <div className="flex items-center justify-center h-64"><Loader2 className="w-8 h-8 text-primary animate-spin" /></div>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between bg-card p-4 rounded-xl border">
                <div>
                    <h3 className="text-lg font-bold flex items-center gap-2">
                        <Users className="w-5 h-5 text-primary" />
                        Usuarios Registrados
                    </h3>
                    <p className="text-xs text-muted-foreground">Gestiona las cuentas de acceso al sistema</p>
                </div>
                <button
                    onClick={() => handleOpenModal()}
                    className="bg-primary hover:bg-primary/90 text-primary-foreground px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-all shadow-sm"
                >
                    <Plus className="w-4 h-4" /> Nuevo Usuario
                </button>
            </div>

            <div className="bg-card rounded-xl border overflow-hidden">
                <table className="w-full text-left text-sm">
                    <thead className="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th className="p-4 font-medium uppercase text-[10px] tracking-wider">Usuario</th>
                            <th className="p-4 font-medium uppercase text-[10px] tracking-wider">Email</th>
                            <th className="p-4 font-medium uppercase text-[10px] tracking-wider">Rol</th>
                            <th className="p-4 font-medium uppercase text-[10px] tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {users?.map((user) => (
                            <tr key={user.id} className="hover:bg-muted/30 transition-colors">
                                <td className="p-4">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                            {user.first_name.charAt(0)}{user.last_name.charAt(0)}
                                        </div>
                                        <div>
                                            <div className="font-bold">{user.first_name} {user.last_name}</div>
                                            <div className="text-[10px] text-muted-foreground font-mono">@{user.username}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="p-4 text-muted-foreground truncate max-w-[200px]">
                                    <div className="flex items-center gap-2">
                                        <Mail className="w-3 h-3" />
                                        {user.email}
                                    </div>
                                </td>
                                <td className="p-4">
                                    <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-primary/5 text-primary text-[10px] font-bold border border-primary/10">
                                        <Shield className="w-3 h-3" />
                                        {user.role || 'Usuario'}
                                    </span>
                                </td>
                                <td className="p-4 text-right">
                                    <button
                                        onClick={() => handleOpenModal(user)}
                                        className="text-muted-foreground hover:text-primary p-1.5 hover:bg-primary/5 rounded-lg transition-colors"
                                    >
                                        <Edit2 className="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {isModalOpen && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm">
                    <div className="bg-card rounded-2xl border border-border w-full max-w-lg shadow-2xl relative">
                        <div className="flex items-center justify-between p-6 border-b">
                            <h2 className="text-xl font-bold">{editingUser ? 'Editar Usuario' : 'Nuevo Usuario'}</h2>
                            <button onClick={handleCloseModal} className="text-muted-foreground hover:text-foreground">
                                <X className="w-6 h-6" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">Nombre</label>
                                    <input type="text" value={formData.first_name} onChange={(e) => setFormData({ ...formData, first_name: e.target.value })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none" required />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">Apellido</label>
                                    <input type="text" value={formData.last_name} onChange={(e) => setFormData({ ...formData, last_name: e.target.value })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none" required />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">Usuario</label>
                                    <input type="text" value={formData.username} onChange={(e) => setFormData({ ...formData, username: e.target.value })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none" required disabled={!!editingUser} />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">Rol</label>
                                    <select value={formData.permission_id} onChange={(e) => setFormData({ ...formData, permission_id: parseInt(e.target.value) })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none appearance-none">
                                        {permissions?.map((p: any) => (
                                            <option key={p.id} value={p.id}>{p.name}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">Email</label>
                                <input type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none" required />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-muted-foreground uppercase mb-1">{editingUser ? 'Actualizar Contraseña (Opcional)' : 'Contraseña'}</label>
                                <input type="password" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} className="w-full px-4 py-2 border rounded-xl bg-muted/30 focus:ring-1 ring-primary outline-none" required={!editingUser} />
                            </div>
                            <div className="pt-4 flex justify-end gap-3">
                                <button type="button" onClick={handleCloseModal} className="px-4 py-2 text-muted-foreground font-medium">Cancelar</button>
                                <button type="submit" className="px-6 py-2 bg-primary text-primary-foreground font-bold rounded-xl shadow-lg shadow-primary/20">
                                    {editingUser ? 'Actualizar' : 'Crear'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default UsersManagementTab;
