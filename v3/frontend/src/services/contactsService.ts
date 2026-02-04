import axios from 'axios';
import { getCurrentUser } from './authService';

const API_URL = '/monitoreoLaboratorio/v3/api';

const getAuthHeaders = () => {
    const user = getCurrentUser();
    if (user && user.token) {
        return { Authorization: `Bearer ${user.token}` };
    }
    return {};
};

export interface SiteContact {
    id: number;
    site_id: number;
    name: string;
    role: 'contract_admin' | 'dispatch' | 'onsite_engineer' | 'other';
    phone?: string;
    email?: string;
}

export const getContacts = async (siteId: number): Promise<SiteContact[]> => {
    const response = await axios.get(`${API_URL}/site_contacts.php?site_id=${siteId}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const createContact = async (contact: Omit<SiteContact, 'id'>): Promise<SiteContact> => {
    const response = await axios.post(`${API_URL}/site_contacts.php`, contact, {
        headers: getAuthHeaders(),
    });
    return { ...contact, id: response.data.id };
};

export const deleteContact = async (id: number): Promise<void> => {
    await axios.delete(`${API_URL}/site_contacts.php?id=${id}`, {
        headers: getAuthHeaders(),
    });
};
