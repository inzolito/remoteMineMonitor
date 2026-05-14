import { api } from './authService';

export interface SiteContact {
    id: number;
    site_id: number;
    name: string;
    role: 'contract_admin' | 'dispatch' | 'onsite_engineer' | 'other';
    phone?: string;
    email?: string;
}

export const getContacts = async (siteId: number): Promise<SiteContact[]> => {
    const response = await api.get(`/site_contacts.php?site_id=${siteId}`);
    return response.data;
};

export const createContact = async (contact: Omit<SiteContact, 'id'>): Promise<SiteContact> => {
    const response = await api.post('/site_contacts.php', contact);
    return { ...contact, id: response.data.id };
};

export const deleteContact = async (id: number): Promise<void> => {
    await api.delete(`/site_contacts.php?id=${id}`);
};
