import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { type Site } from '../services/sitesService';
import { getCurrentUser } from '../services/authService';

const API_URL = '/monitoreoLaboratorio/v3/api';

const getAuthHeaders = () => {
    const user = getCurrentUser();
    if (user && user.token) {
        return { Authorization: `Bearer ${user.token}` };
    }
    return {};
};

const getActiveSites = async (): Promise<Site[]> => {
    const response = await axios.get(`${API_URL}/sites.php?status=1`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const useStaticSites = () => {
    return useQuery({
        queryKey: ['sites', 'static'],
        queryFn: getActiveSites,
        staleTime: 1000 * 60 * 5, // Data is fresh for 5 minutes
        gcTime: 1000 * 60 * 10,   // Keep in cache for 10 minutes
        refetchOnWindowFocus: false, // Don't refetch on window focus
    });
};
