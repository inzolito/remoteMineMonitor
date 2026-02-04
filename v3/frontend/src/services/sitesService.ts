import axios from 'axios';
import { getCurrentUser } from './authService';

const API_URL = '/monitoreoLaboratorio/v3/api';

// Helper to get headers with token
const getAuthHeaders = () => {
    const user = getCurrentUser();
    if (user && user.token) {
        return { Authorization: `Bearer ${user.token}` };
    }
    return {};
};

export interface Site {
    id: number;
    name: string;
    alias: string;
    status: number; // 1 = online, 0 = offline (example)
    conglomerate: string;
    logo_url: string;
    contract_number?: string;
    contract_validity?: string;
    contract_manager?: string;
    contract_admin_users?: string;
    dispatch_contact?: string;
    dispatch_phone?: string;
    onsite_engineers?: string;
    has_fms?: number;
    has_cas?: number;
    fms_status?: number; // 1 = online, 0 = offline
    cas_status?: number;
    online_servers?: number;
    total_servers?: number;
    health_status?: number; // 1 = ok, 2 = warning, 3 = danger
    db_status?: number;
    processes_status?: number;
    importadores_status?: number;
    health_alert?: string | null;
    db_alert?: string | null;
    processes_alert?: string | null;
    importadores_alert?: string | null;
}

export const getSites = async (): Promise<Site[]> => {
    const response = await axios.get(`${API_URL}/sites.php`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const getSiteById = async (id: number): Promise<Site> => {
    const response = await axios.get(`${API_URL}/sites.php?id=${id}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const updateSite = async (id: number, siteData: Partial<Site>): Promise<void> => {
    await axios.put(`${API_URL}/sites.php?id=${id}`, siteData, {
        headers: getAuthHeaders(),
    });
};
