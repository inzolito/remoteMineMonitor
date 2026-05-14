import { api, getCurrentUser } from './authService';

export interface Alert {
    id: number;
    server_id: number;
    metric_key: string;
    title: string;
    description: string;
    status: 'active' | 'acknowledged' | 'solved';
    created_at: string;
    server_name: string;
    site_id?: number | string;
    user_name?: string;
}

export const getAlerts = async (status: 'active' | 'active_or_acknowledged' = 'active_or_acknowledged'): Promise<Alert[]> => {
    const response = await api.get(`/alerts.php?status=${status}`);
    return response.data;
};

export const acknowledgeAlert = async (alertId: number): Promise<void> => {
    const user = getCurrentUser();
    await api.post('/alerts.php', {
        alert_id: alertId,
        action: 'acknowledge',
        user_id: user?.user?.id,
        token: user?.token // Pass token in body for high reliability host
    });
};

export const solveAlert = async (alertId: number): Promise<void> => {
    const user = getCurrentUser();
    await api.post('/alerts.php', {
        alert_id: alertId,
        action: 'solve',
        user_id: user?.user?.id,
        token: user?.token // Pass token in body for high reliability host
    });
};
