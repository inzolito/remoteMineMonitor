import axios from 'axios';

const API_URL = '/monitoreoLaboratorio/v3/api';

// Create protected axios instance
export const api = axios.create({
    baseURL: API_URL
});

// Add request interceptor to inject token
api.interceptors.request.use((config) => {
    const user = getCurrentUser();
    if (user && user.token) {
        config.headers.Authorization = `Bearer ${user.token}`;
    }
    return config;
});

// Add response interceptor to handle 401
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            console.warn("401 Unauthorized detected. Checking if redirect is needed...");
            logout();
            // Avoid infinite loop if already on login page
            const currentPath = window.location.pathname;
            const isLoginPage = currentPath.includes('/login');
            
            if (!isLoginPage) {
                console.log("Redirecting to login from:", currentPath);
                window.location.href = '/monitoreoLaboratorio/v3/frontend/dist/login';
            } else {
                console.log("Already on login page (or path contains /login). Skipping redirect.");
            }
        }
        return Promise.reject(error);
    }
);

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
