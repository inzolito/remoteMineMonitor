import axios from 'axios';
import { getCurrentUser } from './authService';

const API_URL = '/monitoreoLaboratorio/v3/api';

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

const getAuthHeaders = () => {
    const user = getCurrentUser();
    if (user && user.token) {
        return {
            Authorization: `Bearer ${user.token}`,
            'X-Authorization': `Bearer ${user.token}` // Redundant for some server setups
        };
    }
    return {};
};

export const getAlerts = async (status: 'active' | 'active_or_acknowledged' = 'active_or_acknowledged'): Promise<Alert[]> => {
    const response = await axios.get(`${API_URL}/alerts.php?status=${status}`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const acknowledgeAlert = async (alertId: number): Promise<void> => {
    const user = getCurrentUser();
    await axios.post(`${API_URL}/alerts.php`, {
        alert_id: alertId,
        action: 'acknowledge',
        user_id: user?.user?.id,
        token: user?.token // Pass token in body for high reliability host
    }, {
        headers: getAuthHeaders(),
    });
};

export const solveAlert = async (alertId: number): Promise<void> => {
    const user = getCurrentUser();
    await axios.post(`${API_URL}/alerts.php`, {
        alert_id: alertId,
        action: 'solve',
        user_id: user?.user?.id,
        token: user?.token // Pass token in body for high reliability host
    }, {
        headers: getAuthHeaders(),
    });
};
