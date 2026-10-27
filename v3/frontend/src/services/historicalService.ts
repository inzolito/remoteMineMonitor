import { api } from './authService';

export interface HistoricalMetric {
    metric_key: string;
    metric_value: string;
    status: string;
    recorded_at: string;
}

export interface HistoricalServerSnapshot {
    info: {
        id: number;
        name: string;
        host: string;
        ip_address: string;
        is_primary: number;
        type: string;
    };
    system: any;
    app: any;
    cpu_history: { cpu_usage: string; created_at: string }[];
}

export interface HistoricalSnapshotResponse {
    snapshot_ts: string;
    site_id: number;
    servers: HistoricalServerSnapshot[];
}

export const getHistoricalSnapshot = async (
    site_id: number,
    target_ts: string
): Promise<HistoricalSnapshotResponse> => {
    const params = new URLSearchParams({ site_id: String(site_id), target_ts });
    const res = await api.get(`/historical_metrics.php?${params.toString()}`);
    return res.data;
};
