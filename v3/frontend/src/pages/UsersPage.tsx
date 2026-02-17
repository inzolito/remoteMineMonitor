import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getUsers, createUser, updateUser, type UserData } from '../services/usersService';
import { Loader2, Users, Plus, Edit2, X, Mail } from 'lucide-react';

const UsersPage = () => {
    const queryClient = useQueryClient();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<UserData | null>(null);

    // Form State
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
                password: '' // Don't fill password on edit
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
        return <div className="flex items-center justify-center h-96"><Loader2 className="w-10 h-10 text-primary animate-spin" /></div>;
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-3xl font-bold text-foreground flex items-center gap-3">
                    <Users className="w-8 h-8 text-primary" />
                    Gestión de Usuarios
                </h1>
                <button
                    onClick={() => handleOpenModal()}
                    className="bg-primary hover:bg-primary/90 text-primary-foreground px-4 py-2 rounded-xl font-medium flex items-center gap-2 transition-colors shadow-lg shadow-primary/20"
                >
                    <Plus className="w-5 h-5" />
                    Nuevo Usuario
                </button>
            </div>

            <div className="bg-card rounded-2xl border border-border overflow-hidden shadow-sm">
                <table className="w-full text-left">
                    <thead className="bg-muted text-muted-foreground text-sm uppercase tracking-wider">
                        <tr>
                            <th className="p-6 font-medium">Usuario</th>
                            <th className="p-6 font-medium">Email</th>
                            <th className="p-6 font-medium">Rol</th>
                            <th className="p-6 font-medium text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {users?.map((user) => (
                            <tr key={user.id} className="hover:bg-muted/50 transition-colors group">
                                <td className="p-6">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                            {user.first_name.charAt(0)}{user.last_name.charAt(0)}
                                        </div>
                                        <div>
                                            <div className="font-bold text-foreground">{user.first_name} {user.last_name}</div>
                                            <div className="text-sm text-muted-foreground font-mono">@{user.username}</div>
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
                                    <span className={`px-3 py-1 rounded-lg text-xs font-medium border ${user.permission_id === 69 ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'}`}>
                                        {user.role || 'Usuario'}
                                    </span>
                                </td>
                                <td className="p-6 text-right">
                                    <button
                                        onClick={() => handleOpenModal(user)}
                                        className="text-muted-foreground hover:text-foreground p-2 hover:bg-muted rounded-lg transition-colors"
                                    >
                                        <Edit2 className="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm">
                    <div className="bg-card rounded-2xl border border-border w-full max-w-lg shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
                        <div className="flex items-center justify-between p-6 border-b border-border">
                            <h2 className="text-xl font-bold text-foreground">
                                {editingUser ? 'Editar Usuario' : 'Nuevo Usuario'}
                            </h2>
                            <button onClick={handleCloseModal} className="text-muted-foreground hover:text-foreground transition-colors">
                                <X className="w-6 h-6" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Nombre</label>
                                    <input
                                        type="text"
                                        value={formData.first_name}
                                        onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-foreground mb-1">Apellido</label>
                                    <input
                                        type="text"
                                        value={formData.last_name}
                                        onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                                        className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                        required
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">Usuario</label>
                                <input
                                    type="text"
                                    value={formData.username}
                                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    required
                                    disabled={!!editingUser}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">Email</label>
                                <input
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">Rol de Usuario</label>
                                <select
                                    value={formData.permission_id}
                                    onChange={(e) => setFormData({ ...formData, permission_id: parseInt(e.target.value) })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground appearance-none"
                                >
                                    {permissions?.map((p: any) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1">
                                    {editingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña'}
                                </label>
                                <input
                                    type="password"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    className="w-full px-4 py-2 bg-muted/50 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground"
                                    required={!editingUser}
                                    placeholder={editingUser ? 'Dejar en blanco para mantener actual' : ''}
                                />
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
