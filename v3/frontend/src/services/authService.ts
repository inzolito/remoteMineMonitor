import axios from 'axios';

const API_URL = '/monitoreoLaboratorio/v3/api';

export const loginUser = async (username: string, password: string) => {
    const response = await axios.post(`${API_URL}/login.php`, { username, password });
    if (response.data.token) {
        localStorage.setItem('user', JSON.stringify(response.data));
    }
    return response.data;
};

export const logout = () => {
    localStorage.removeItem('user');
};

export const getCurrentUser = () => {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
};
