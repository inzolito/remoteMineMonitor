import { createContext, useState, useEffect, type ReactNode } from 'react';
import { getCurrentUser, loginUser, logout } from '../services/authService';

interface User {
    id: number;
    username: string;
    firstname: string;
    lastname: string;
    email: string;
    role: string;
    token?: string;
}

interface AuthContextType {
    user: User | null;
    login: (u: string, p: string) => Promise<void>;
    logout: () => void;
    isLoading: boolean;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    const mapApiUser = (apiUser: any): User => {
        // Precise mapping based on login.php query results
        return {
            id: apiUser.id || apiUser.user_id,
            username: apiUser.username || apiUser.usuario || apiUser.user || 'maik', // Defaulting to maik if unknown for debugging or empty
            firstname: apiUser.first_name || apiUser.firstname || apiUser.nombre || 'Usuario',
            lastname: apiUser.last_name || apiUser.lastname || apiUser.apellido || '',
            email: apiUser.email || apiUser.mail || '',
            role: apiUser.role || apiUser.permiso || 'Usuario'
        };
    };

    useEffect(() => {
        const storedUser = getCurrentUser();
        if (storedUser && storedUser.user) {
            // Structure returned by API is { user: {...}, token: "..." }
            // We'll merge them for simpler usage
            setUser({ ...mapApiUser(storedUser.user), token: storedUser.token });
        }
        setIsLoading(false);
    }, []);

    const login = async (u: string, p: string) => {
        const data = await loginUser(u, p);
        if (data.user && data.token) {
            setUser({ ...mapApiUser(data.user), token: data.token });
        }
    };

    const logoutUser = () => {
        logout();
        setUser(null);
    };

    return (
        <AuthContext.Provider value={{ user, login, logout: logoutUser, isLoading }}>
            {children}
        </AuthContext.Provider>
    );
};
