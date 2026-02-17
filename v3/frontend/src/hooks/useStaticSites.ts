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
    // Fetch ALL sites (API defaults to all if no status param)
    // Frontend then filters by is_visible in ClientsPage.tsx
    const response = await axios.get(`${API_URL}/sites.php`, {
        headers: getAuthHeaders(),
    });
    return response.data;
};

export const useStaticSites = () => {
    return useQuery({
        queryKey: ['sites', 'static'],
        queryFn: getActiveSites,
        staleTime: 0, // Always fetch fresh data
        gcTime: 1000 * 60 * 5,   // Keep in cache for 5 minutes
        refetchOnWindowFocus: true, // Refetch when window gains focus
    });
};
