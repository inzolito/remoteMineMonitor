import { api } from './authService';

export interface Site {
    id: number;
    name: string;
    alias: string;
    status: number; // 1 = online, 0 = offline (example)
    is_visible?: number; // 1 = visible, 0 = hidden
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
    const response = await api.get('/sites.php');
    return response.data;
};

export const getSiteById = async (id: number): Promise<Site> => {
    const response = await api.get(`/sites.php?id=${id}`);
    return response.data;
};

export const updateSite = async (id: number, siteData: Partial<Site>): Promise<void> => {
    await api.put(`/sites.php?id=${id}`, siteData);
};
