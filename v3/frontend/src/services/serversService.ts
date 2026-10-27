import { api } from './authService';

export interface SystemProduct {
    id: number;
    name: string;
    code: string;
    icon: string;
    color: string;
    default_os: string;
    default_port: string;
    default_protocol: string;
    is_active: number;
}

export interface Server {
    id: number;
    name: string;
    ip_address: string;
    os: string;
    server_type: string; // 'DB', 'APP', 'WEB', etc.
    primary_product_id?: number | null;
    secondary_product_ids?: number[];
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

export const getSystemProducts = async (): Promise<SystemProduct[]> => {
    const response = await api.get('/system_products.php');
    return response.data;
};

export const createSystemProduct = async (product: Partial<SystemProduct>): Promise<{ message: string; product: SystemProduct }> => {
    const response = await api.post('/system_products.php', product);
    return response.data;
};

export const getServersBySite = async (siteId: number): Promise<Server[]> => {
    const response = await api.get(`/servers.php?site_id=${siteId}`);
    return response.data;
};

export const createServer = async (server: Partial<Server> & { site_id: number }) => {
    const response = await api.post('/servers.php', server);
    return response.data;
};

export const updateServer = async (server: Partial<Server>) => {
    const response = await api.put('/servers.php', server);
    return response.data;
};

export const deleteServer = async (id: number) => {
    const response = await api.delete(`/servers.php?id=${id}`);
    return response.data;
};

export const getRawMetrics = async (serverId: number) => {
    const response = await api.get(`/raw_metrics.php?server_id=${serverId}`);
    return response.data;
};
