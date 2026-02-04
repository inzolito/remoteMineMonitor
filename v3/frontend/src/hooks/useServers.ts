import { useState, useEffect } from 'react';
import axios from 'axios';
import { getCurrentUser } from '../services/authService';
import type { Server } from '../services/serversService';

const API_URL = '/monitoreoLaboratorio/v3/api';

export const useServers = () => {
    const [servers, setServers] = useState<Server[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const fetchServers = async () => {
            setLoading(true);
            setError(null);
            try {
                const user = getCurrentUser();
                const response = await axios.get(`${API_URL}/servers.php`, {
                    headers: user?.token ? { Authorization: `Bearer ${user.token}` } : {},
                });
                setServers(response.data);
            } catch (err: any) {
                setError(err.message || 'Error fetching servers');
                setServers([]);
            } finally {
                setLoading(false);
            }
        };

        fetchServers();
    }, []);

    return { servers, loading, error };
};
