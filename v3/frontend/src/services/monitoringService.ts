import { api } from './authService';

export interface ServerMetric {
    id: number;
    cpu_usage: number;
    cpu_status: string;
    ram_total: any;
    ram_used: any;
    ram_percent: number;
    ram_status: string;
    disk_total: any;
    disk_used: any;
    disk_percent: number;
    disk_status: string;
    load_average: string;
    uptime_seconds: number;
    created_at: string;
}

export interface ServerService {
    id: number;
    service_name: string;
    status: string; // active, stopped, etc.
    last_updated: string;
}

export interface DBMetric {
    id: number;
    db_name: string;
    active_connections: number;
    db_size_mb: number;
    table_count: number;
    max_pk_table: string;
    max_pk_value: number;
    max_pk_limit: number;
    created_at: string;
}

export interface AppMetric {
    metric_key: string;
    metric_value: string;
    status: string;
    last_updated?: string;
}

export interface ServerFullData {
    info: {
        id: number;
        name: string;
        ip_address: string;
        server_type: string; // Primary, Secondary, etc.
        status: number;
    };
    system: ServerMetric;
    cpu_history: { cpu_usage: number, created_at: string }[];
    services: ServerService[];
    db: DBMetric;
    app: { [key: string]: AppMetric };
}

export interface SiteMonitoringResponse {
    site_id: number;
    servers: ServerFullData[];
    debug_time: number;
}

export const getSiteMetrics = async (siteId: number, type: string = 'fms'): Promise<SiteMonitoringResponse> => {
    const response = await api.get(`/metrics.php?site_id=${siteId}&type=${type}`);
    return response.data;
};

export const getServerMetrics = async (serverId: number): Promise<any> => {
    const response = await api.get(`/metrics.php?server_id=${serverId}`);
    return response.data;
};
