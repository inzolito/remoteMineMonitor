import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { getContacts, createContact, deleteContact, type SiteContact } from '../services/contactsService';
import { Trash2, UserPlus, Phone, Mail, User } from 'lucide-react';

interface ContactsManagerProps {
    siteId: number;
}

const ContactsManager = ({ siteId }: ContactsManagerProps) => {
    const queryClient = useQueryClient();
    const [newName, setNewName] = useState('');
    const [newPhone, setNewPhone] = useState('');
    const [newEmail, setNewEmail] = useState('');
    const [newRole, setNewRole] = useState<SiteContact['role']>('onsite_engineer');

    const { data: contacts, isLoading } = useQuery({
        queryKey: ['contacts', siteId],
        queryFn: () => getContacts(siteId),
        enabled: !!siteId
    });

    const createMutation = useMutation({
        mutationFn: createContact,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['contacts', siteId] });
            setNewName('');
            setNewPhone('');
            setNewEmail('');
        }
    });

    const deleteMutation = useMutation({
        mutationFn: deleteContact,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['contacts', siteId] });
        }
    });

    const handleAdd = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newName) return;
        createMutation.mutate({
            site_id: siteId,
            name: newName,
            role: newRole,
            phone: newPhone,
            email: newEmail
        });
    };

    const roleLabels = {
        contract_admin: 'Admin Contrato',
        dispatch: 'Despacho',
        onsite_engineer: 'Ingeniero Onsite',
        other: 'Otro'
    };

    const groupedContacts = contacts?.reduce((acc, contact) => {
        const role = contact.role;
        if (!acc[role]) acc[role] = [];
        acc[role].push(contact);
        return acc;
    }, {} as Record<string, SiteContact[]>) || {};

    const orderedRoles: SiteContact['role'][] = ['contract_admin', 'dispatch', 'onsite_engineer', 'other'];

    return (
        <div className="space-y-6">
            {/* Add Contact Form */}
            <form onSubmit={handleAdd} className="bg-muted/30 p-4 rounded-lg space-y-3 border border-border">
                <h3 className="text-sm font-semibold mb-2 flex items-center gap-2">
                    <UserPlus className="w-4 h-4" />
                    Agregar Personal
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <select
                        value={newRole}
                        onChange={(e) => setNewRole(e.target.value as any)}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="contract_admin">Admin Contrato</option>
                        <option value="dispatch">Despacho</option>
                        <option value="onsite_engineer">Ingeniero Onsite</option>
                        <option value="other">Otro</option>
                    </select>
                    <input
                        type="text"
                        placeholder="Nombre"
                        value={newName}
                        onChange={(e) => setNewName(e.target.value)}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    />
                    <input
                        type="text"
                        placeholder="Teléfono"
                        value={newPhone}
                        onChange={(e) => setNewPhone(e.target.value)}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <input
                        type="email"
                        placeholder="Email (Opcional)"
                        value={newEmail}
                        onChange={(e) => setNewEmail(e.target.value)}
                        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={createMutation.isPending}
                        className="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
                    >
                        {createMutation.isPending ? 'Agregando...' : 'Agregar'}
                    </button>
                </div>
            </form>

            {/* Contacts List */}
            <div className="space-y-6">
                {isLoading ? (
                    <div className="text-sm text-muted-foreground">Cargando contactos...</div>
                ) : (
                    orderedRoles.map(role => {
                        const roleContacts = groupedContacts[role];
                        if (!roleContacts?.length) return null;

                        return (
                            <div key={role} className="space-y-3">
                                <h4 className="text-sm font-bold uppercase tracking-wider text-muted-foreground border-b border-border pb-1">
                                    {roleLabels[role]}
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    {roleContacts.map(contact => (
                                        <div key={contact.id} className="bg-card border border-border rounded-lg p-3 shadow-sm flex justify-between items-start group">
                                            <div className="space-y-1">
                                                <div className="font-semibold text-sm flex items-center gap-2">
                                                    <User className="w-3 h-3 text-primary" />
                                                    {contact.name}
                                                </div>
                                                {contact.phone && (
                                                    <div className="text-xs text-muted-foreground flex items-center gap-2">
                                                        <Phone className="w-3 h-3" />
                                                        {contact.phone}
                                                    </div>
                                                )}
                                                {contact.email && (
                                                    <div className="text-xs text-muted-foreground flex items-center gap-2">
                                                        <Mail className="w-3 h-3" />
                                                        {contact.email}
                                                    </div>
                                                )}
                                            </div>
                                            <button
                                                onClick={() => deleteMutation.mutate(contact.id)}
                                                className="text-muted-foreground hover:text-destructive transition-colors opacity-0 group-hover:opacity-100"
                                                title="Eliminar"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })
                )}
                {!contacts?.length && !isLoading && (
                    <div className="text-center text-muted-foreground text-sm py-8">
                        No hay personal registrado.
                    </div>
                )}
            </div>
        </div>
    );
};

export default ContactsManager;
