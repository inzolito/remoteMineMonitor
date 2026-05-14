import { api } from './authService';

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
    const response = await api.get('/users.php');
    return response.data;
};

export const createUser = async (user: Partial<UserData>) => {
    const response = await api.post('/users.php', user);
    return response.data;
};

export const updateUser = async (user: Partial<UserData>) => {
    const response = await api.put('/users.php', user);
    return response.data;
};
