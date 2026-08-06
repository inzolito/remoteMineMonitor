import { api } from './authService';

export interface HistorialAlert {
    id: number;
    server_id: number;
    metric_key: string;
    title: string;
    description: string;
    status: 'active' | 'acknowledged' | 'solved';
    created_at: string;
    acknowledged_at: string | null;
    solved_at: string | null;
    server_name: string;
    server_ip: string | null;
    server_type: string;
    site_name: string;
    site_id: number;
    acknowledged_by: string | null;
    response_time_seconds: number | null;
    response_time_fmt: string | null;
    resolution_time_seconds: number | null;
    resolution_time_fmt: string | null;
    category_id: string;
    category_label: string;
    metric_value: string | null;
}

export interface HistorialMeta {
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
    avg_response_seconds: number | null;
    avg_response_fmt: string | null;
    avg_resolution_seconds: number | null;
    avg_resolution_fmt: string | null;
    top_alert_category: string | null;
    top_alert_count: number;
}

export interface ShiftCycle {
    id: number;
    start_datetime: string;
    end_datetime: string | null;
    group_alias: string;
    color_hex: string;
}

export interface ShiftMember {
    user_id: number;
    group_id: number;
    sub_shift: 'Día' | 'Noche';
    full_name: string;
    group_alias: string;
    color_hex: string;
}

export interface HistorialFilters {
    cycle_id?: number;
    sub_shift?: 'Día' | 'Noche' | '';
    user_id?: number;
    date_from?: string;
    date_to?: string;
    site_id?: number;
    category?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export interface HistorialResponse {
    alerts: HistorialAlert[];
    meta: HistorialMeta;
    shift_cycles: ShiftCycle[];
    shift_members: ShiftMember[];
}

export const getHistorial = async (filters: HistorialFilters = {}): Promise<HistorialResponse> => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => {
        if (v !== undefined && v !== '' && v !== null) params.append(k, String(v));
    });
    const response = await api.get(`/alert_history.php?${params.toString()}`);
    return response.data;
};
