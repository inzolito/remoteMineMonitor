import { useState, useEffect } from 'react';
import { getRawMetrics } from '../services/serversService';
import { useServers } from '../hooks/useServers';

interface RawMetric {
    metric_key: string;
    metric_value: string;
    status: string;
    updated_at: string;
    type: 'app' | 'system';
}

export default function RooteoTab() {
    const { servers, loading: serversLoading } = useServers();
    const [selectedServerId, setSelectedServerId] = useState<number | null>(null);
    const [metrics, setMetrics] = useState<RawMetric[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Fetch metrics when server is selected
    useEffect(() => {
        if (!selectedServerId) {
            setMetrics([]);
            return;
        }

        const fetchMetrics = async () => {
            setLoading(true);
            setError(null);
            try {
                const data = await getRawMetrics(selectedServerId);
                setMetrics(data);
            } catch (err: any) {
                setError(err.message || 'Error fetching metrics');
                setMetrics([]);
            } finally {
                setLoading(false);
            }
        };

        fetchMetrics();

        // Auto-refresh every 5 seconds
        const interval = setInterval(fetchMetrics, 5000);
        return () => clearInterval(interval);
    }, [selectedServerId]);

    const selectedServer = servers.find((s: any) => s.id === selectedServerId);

    return (
        <div className="grid grid-cols-12 gap-4 h-[calc(100vh-200px)]">
            {/* Server List Sidebar */}
            <div className="col-span-3 bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col">
                <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-3 font-bold">
                    Servidores
                </div>
                <div className="flex-1 overflow-y-auto">
                    {serversLoading ? (
                        <div className="p-4 text-center text-gray-500">Cargando...</div>
                    ) : servers.length === 0 ? (
                        <div className="p-4 text-center text-gray-500">No hay servidores</div>
                    ) : (
                        <div className="divide-y">
                            {servers.map((server: any) => (
                                <button
                                    key={server.id}
                                    onClick={() => setSelectedServerId(server.id)}
                                    className={`w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors ${selectedServerId === server.id ? 'bg-blue-100 border-l-4 border-blue-600' : ''
                                        }`}
                                >
                                    <div className="font-semibold text-gray-800">{server.name}</div>
                                    <div className="text-xs text-gray-500 font-mono">{server.ip_address}</div>
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Metrics Table */}
            <div className="col-span-9 bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col">
                <div className="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-3 font-bold flex justify-between items-center">
                    <span>
                        {selectedServer ? `Métricas: ${selectedServer.name}` : 'Selecciona un servidor'}
                    </span>
                    {loading && (
                        <span className="text-xs bg-white/20 px-2 py-1 rounded animate-pulse">
                            Actualizando...
                        </span>
                    )}
                </div>

                <div className="flex-1 overflow-auto">
                    {!selectedServerId ? (
                        <div className="flex items-center justify-center h-full text-gray-400">
                            <div className="text-center">
                                <svg className="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p className="text-lg">Selecciona un servidor para ver sus métricas</p>
                            </div>
                        </div>
                    ) : error ? (
                        <div className="p-4 text-center text-red-600">
                            Error: {error}
                        </div>
                    ) : metrics.length === 0 && !loading ? (
                        <div className="p-4 text-center text-gray-500">
                            No hay métricas disponibles
                        </div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 sticky top-0 border-b">
                                <tr>
                                    <th className="px-4 py-3 text-left font-semibold text-gray-700">Metric Key</th>
                                    <th className="px-4 py-3 text-left font-semibold text-gray-700">Value</th>
                                    <th className="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                    <th className="px-4 py-3 text-left font-semibold text-gray-700">Last Update</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {metrics.map((metric, idx) => (
                                    <tr key={idx} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-2 font-mono text-xs text-gray-800">
                                            {metric.metric_key}
                                        </td>
                                        <td className="px-4 py-2 font-mono text-xs text-gray-600 max-w-md truncate" title={metric.metric_value}>
                                            {metric.metric_value}
                                        </td>
                                        <td className="px-4 py-2">
                                            <span className={`inline-block px-2 py-0.5 rounded text-xs font-semibold ${metric.status === 'ok' ? 'bg-green-100 text-green-700' :
                                                metric.status === 'warning' ? 'bg-yellow-100 text-yellow-700' :
                                                    metric.status === 'danger' ? 'bg-red-100 text-red-700' :
                                                        'bg-gray-100 text-gray-700'
                                                }`}>
                                                {metric.status}
                                            </span>
                                        </td>
                                        <td className="px-4 py-2 text-xs text-gray-500">
                                            {new Date(metric.updated_at).toLocaleString('es-CL')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
