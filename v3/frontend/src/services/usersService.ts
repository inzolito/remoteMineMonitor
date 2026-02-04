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

export interface UserData {
    id: number;
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    role: string;
    permission_id: number;
    password?: string; // Only for create/update
}

export const getUsers = async (): Promise<UserData[]> => {
    const response = await axios.get(`${API_URL}/users.php`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const createUser = async (user: Partial<UserData>) => {
    const response = await axios.post(`${API_URL}/users.php`, user, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const updateUser = async (user: Partial<UserData>) => {
    const response = await axios.put(`${API_URL}/users.php`, user, {
        headers: getAuthHeaders(),
    });
    return response.data;
};
