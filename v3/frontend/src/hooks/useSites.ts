import { useQuery } from '@tanstack/react-query';
import { getSites, type Site } from '../services/sitesService';

export const useSites = () => {
    return useQuery<Site[]>({
        queryKey: ['sites'],
        queryFn: getSites,
        refetchInterval: 5000, // Poll every 5 seconds (Smart Polling)
        staleTime: 5000,
    });
};
