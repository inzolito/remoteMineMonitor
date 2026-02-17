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

export interface Server {
    id: number;
    name: string;
    ip_address: string;
    os: string;
    server_type: string; // 'DB', 'APP', 'WEB', etc.
    ssh_user: string;
    ssh_password?: string;
    db_user?: string;
    db_password?: string;
    port?: string;
    protocol?: string;
    db_engine?: string;
    db_name?: string;
    status: number; // 0 or 1
    description: string;
    notes?: string;
    last_seen?: string;
}

export const getServersBySite = async (siteId: number): Promise<Server[]> => {
    const response = await axios.get(`${API_URL}/servers.php?site_id=${siteId}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const createServer = async (server: Partial<Server> & { site_id: number }) => {
    const response = await axios.post(`${API_URL}/servers.php`, server, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const updateServer = async (server: Partial<Server>) => {
    const response = await axios.put(`${API_URL}/servers.php`, server, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const deleteServer = async (id: number) => {
    const response = await axios.delete(`${API_URL}/servers.php?id=${id}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const getRawMetrics = async (serverId: number) => {
    const response = await axios.get(`${API_URL}/raw_metrics.php?server_id=${serverId}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};
