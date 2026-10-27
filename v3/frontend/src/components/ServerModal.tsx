import React, { useState, useEffect } from 'react';
import { 
    Loader2, X, Database, Server as ServerIcon, Globe, Box, ShieldCheck, 
    Cpu, Check, Star, Layers, AlertCircle, Plus, HardDrive, Cloud, 
    Network, Terminal, Activity, Radio, Wifi, Zap, Key
} from 'lucide-react';
import type { Server, SystemProduct } from '../services/serversService';
import { getSystemProducts, createSystemProduct, deleteServer } from '../services/serversService';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

interface ServerModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSave: (server: Partial<Server>) => Promise<void>;
    initialData?: Server | null;
}

const ICON_MAP: Record<string, React.ElementType> = {
    Server: ServerIcon,
    Globe: Globe,
    Box: Box,
    ShieldCheck: ShieldCheck,
    Database: Database,
    Cpu: Cpu,
    HardDrive: HardDrive,
    Cloud: Cloud,
    Network: Network,
    Terminal: Terminal,
    Activity: Activity,
    Radio: Radio,
    Wifi: Wifi,
    Zap: Zap,
    Layers: Layers,
    Key: Key,
};

const AVAILABLE_ICONS: { name: string; icon: React.ElementType; label: string }[] = [
    { name: 'Server', icon: ServerIcon, label: 'Servidor' },
    { name: 'Database', icon: Database, label: 'Base de Datos' },
    { name: 'Globe', icon: Globe, label: 'Web / Red' },
    { name: 'Box', icon: Box, label: 'Contenedor / App' },
    { name: 'ShieldCheck', icon: ShieldCheck, label: 'Seguridad' },
    { name: 'Cpu', icon: Cpu, label: 'Cómputo / FMS' },
    { name: 'HardDrive', icon: HardDrive, label: 'Almacenamiento' },
    { name: 'Cloud', icon: Cloud, label: 'Cloud / Nube' },
    { name: 'Network', icon: Network, label: 'Networking' },
    { name: 'Terminal', icon: Terminal, label: 'Terminal / SSH' },
    { name: 'Activity', icon: Activity, label: 'Telemetría' },
    { name: 'Radio', icon: Radio, label: 'Radio / IoT' },
    { name: 'Wifi', icon: Wifi, label: 'Wireless' },
    { name: 'Zap', icon: Zap, label: 'Alta Velocidad' },
    { name: 'Layers', icon: Layers, label: 'Pila / Capas' },
    { name: 'Key', icon: Key, label: 'Autenticación' },
];

const AVAILABLE_COLORS: { name: string; label: string; bg: string; border: string; text: string }[] = [
    { name: 'orange', label: 'Naranja', bg: 'bg-orange-500', border: 'border-orange-500', text: 'text-orange-500' },
    { name: 'blue', label: 'Azul', bg: 'bg-blue-500', border: 'border-blue-500', text: 'text-blue-500' },
    { name: 'purple', label: 'Púrpura', bg: 'bg-purple-500', border: 'border-purple-500', text: 'text-purple-500' },
    { name: 'indigo', label: 'Índigo', bg: 'bg-indigo-500', border: 'border-indigo-500', text: 'text-indigo-500' },
    { name: 'emerald', label: 'Verde', bg: 'bg-emerald-500', border: 'border-emerald-500', text: 'text-emerald-500' },
    { name: 'red', label: 'Rojo', bg: 'bg-red-500', border: 'border-red-500', text: 'text-red-500' },
    { name: 'cyan', label: 'Cian', bg: 'bg-cyan-500', border: 'border-cyan-500', text: 'text-cyan-500' },
    { name: 'amber', label: 'Ámbar', bg: 'bg-amber-500', border: 'border-amber-500', text: 'text-amber-500' },
    { name: 'rose', label: 'Rosa', bg: 'bg-rose-500', border: 'border-rose-500', text: 'text-rose-500' },
    { name: 'violet', label: 'Violeta', bg: 'bg-violet-500', border: 'border-violet-500', text: 'text-violet-500' },
];

const COLOR_MAP: Record<string, { active: string; border: string; bg: string; text: string }> = {
    orange: { active: 'text-orange-600 dark:text-orange-400 bg-orange-500/15 border-orange-500 ring-2 ring-orange-500/50', border: 'border-orange-500/30', bg: 'bg-orange-500/10', text: 'text-orange-500' },
    blue: { active: 'text-blue-600 dark:text-blue-400 bg-blue-500/15 border-blue-500 ring-2 ring-blue-500/50', border: 'border-blue-500/30', bg: 'bg-blue-500/10', text: 'text-blue-500' },
    purple: { active: 'text-purple-600 dark:text-purple-400 bg-purple-500/15 border-purple-500 ring-2 ring-purple-500/50', border: 'border-purple-500/30', bg: 'bg-purple-500/10', text: 'text-purple-500' },
    indigo: { active: 'text-indigo-600 dark:text-indigo-400 bg-indigo-500/15 border-indigo-500 ring-2 ring-indigo-500/50', border: 'border-indigo-500/30', bg: 'bg-indigo-500/10', text: 'text-indigo-500' },
    emerald: { active: 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15 border-emerald-500 ring-2 ring-emerald-500/50', border: 'border-emerald-500/30', bg: 'bg-emerald-500/10', text: 'text-emerald-500' },
    red: { active: 'text-red-600 dark:text-red-400 bg-red-500/15 border-red-500 ring-2 ring-red-500/50', border: 'border-red-500/30', bg: 'bg-red-500/10', text: 'text-red-500' },
    cyan: { active: 'text-cyan-600 dark:text-cyan-400 bg-cyan-500/15 border-cyan-500 ring-2 ring-cyan-500/50', border: 'border-cyan-500/30', bg: 'bg-cyan-500/10', text: 'text-cyan-500' },
    amber: { active: 'text-amber-600 dark:text-amber-400 bg-amber-500/15 border-amber-500 ring-2 ring-amber-500/50', border: 'border-amber-500/30', bg: 'bg-amber-500/10', text: 'text-amber-500' },
    rose: { active: 'text-rose-600 dark:text-rose-400 bg-rose-500/15 border-rose-500 ring-2 ring-rose-500/50', border: 'border-rose-500/30', bg: 'bg-rose-500/10', text: 'text-rose-500' },
    violet: { active: 'text-violet-600 dark:text-violet-400 bg-violet-500/15 border-violet-500 ring-2 ring-violet-500/50', border: 'border-violet-500/30', bg: 'bg-violet-500/10', text: 'text-violet-500' },
};

const DEFAULT_FALLBACK_PRODUCTS: SystemProduct[] = [
    { id: 1, name: 'Servidor FMS', code: 'fms', icon: 'Server', color: 'orange', default_os: 'Linux', default_port: '22', default_protocol: 'SSH', is_active: 1 },
    { id: 2, name: 'Estacion Base', code: 'base', icon: 'Globe', color: 'blue', default_os: 'Linux', default_port: '22', default_protocol: 'SSH', is_active: 1 },
    { id: 3, name: 'Tunel', code: 'tunel', icon: 'Box', color: 'purple', default_os: 'Linux', default_port: '8080', default_protocol: 'HTTP', is_active: 1 },
    { id: 4, name: 'Pivote', code: 'pivote', icon: 'ShieldCheck', color: 'indigo', default_os: 'Linux', default_port: '22', default_protocol: 'SSH', is_active: 1 },
    { id: 5, name: 'Servidor CAS', code: 'cas', icon: 'Database', color: 'emerald', default_os: 'Windows', default_port: '3389', default_protocol: 'RDP', is_active: 1 },
    { id: 6, name: 'SQL Server', code: 'sql', icon: 'Database', color: 'red', default_os: 'Windows', default_port: '1433', default_protocol: 'TDS', is_active: 1 },
    { id: 7, name: 'Servidor Jview', code: 'jview', icon: 'Globe', color: 'cyan', default_os: 'Linux', default_port: '80', default_protocol: 'HTTP', is_active: 1 },
];

const ServerModal = ({ isOpen, onClose, onSave, initialData }: ServerModalProps) => {
    // Products from Configuration Table
    const [products, setProducts] = useState<SystemProduct[]>(DEFAULT_FALLBACK_PRODUCTS);
    const [primaryProductId, setPrimaryProductId] = useState<number | null>(null);
    const [secondaryProductIds, setSecondaryProductIds] = useState<number[]>([]);
    const [productError, setProductError] = useState<string | null>(null);

    // New Product Sub-form State
    const [showNewProductForm, setShowNewProductForm] = useState(false);
    const [newProductName, setNewProductName] = useState('');
    const [newProductIcon, setNewProductIcon] = useState('Server');
    const [newProductColor, setNewProductColor] = useState('blue');
    const [newProductOs, setNewProductOs] = useState('Linux');
    const [newProductPort, setNewProductPort] = useState('22');
    const [newProductProtocol, setNewProductProtocol] = useState('SSH');
    const [creatingProduct, setCreatingProduct] = useState(false);
    const [newProductError, setNewProductError] = useState<string | null>(null);

    // Core Fields
    const [name, setName] = useState('');
    const [ip, setIp] = useState('');
    const [os, setOs] = useState('Linux');
    const [type, setType] = useState('Generic');
    const [description, setDescription] = useState('');
    const [notes, setNotes] = useState('');

    // Auth Fields
    const [sshUser, setSshUser] = useState('');
    const [sshPassword, setSshPassword] = useState('');
    const [dbUser, setDbUser] = useState('');
    const [dbPassword, setDbPassword] = useState('');

    // Extra Technical Fields (Phase 8.2)
    const [port, setPort] = useState('');
    const [protocol, setProtocol] = useState('');
    const [dbEngine, setDbEngine] = useState('');
    const [dbName, setDbName] = useState('');

    const [loading, setLoading] = useState(false);

    // Load products from DB on modal open
    useEffect(() => {
        if (isOpen) {
            getSystemProducts()
                .then((data) => {
                    if (data && data.length > 0) {
                        setProducts(data);
                    }
                })
                .catch((err) => {
                    console.error("Failed to fetch system products, using fallback:", err);
                });
        }
    }, [isOpen]);

    useEffect(() => {
        if (initialData) {
            setName(initialData.name || '');
            setIp(initialData.ip_address || '');
            setOs(initialData.os || 'Linux');
            setType(initialData.server_type || 'Generic');
            setDescription((initialData.description || '').replace(/\\n/g, '\n'));
            setNotes((initialData.notes || '').replace(/\\n/g, '\n'));
            setSshUser(initialData.ssh_user || '');
            setSshPassword(initialData.ssh_password || '');
            setDbUser(initialData.db_user || '');
            setDbPassword(initialData.db_password || '');
            setPort(initialData.port || '');
            setProtocol(initialData.protocol || '');
            setDbEngine(initialData.db_engine || '');
            setDbName(initialData.db_name || '');
            setPrimaryProductId(initialData.primary_product_id || null);
            setSecondaryProductIds(initialData.secondary_product_ids || []);
            setProductError(null);
            setShowNewProductForm(false);
        } else {
            resetForm();
        }
    }, [initialData, isOpen]);

    const resetForm = () => {
        setName('');
        setIp('');
        setOs('Linux');
        setType('Generic');
        setDescription('');
        setNotes('');
        setSshUser('');
        setSshPassword('');
        setDbUser('');
        setDbPassword('');
        setPort('');
        setProtocol('');
        setDbEngine('');
        setDbName('');
        setPrimaryProductId(null);
        setSecondaryProductIds([]);
        setProductError(null);
        setShowNewProductForm(false);
        resetNewProductForm();
    };

    const resetNewProductForm = () => {
        setNewProductName('');
        setNewProductIcon('Server');
        setNewProductColor('blue');
        setNewProductOs('Linux');
        setNewProductPort('22');
        setNewProductProtocol('SSH');
        setNewProductError(null);
    };

    const getIconComponent = (iconName: string) => {
        return ICON_MAP[iconName] || ServerIcon;
    };

    const getColorClasses = (colorName: string) => {
        return COLOR_MAP[colorName] || COLOR_MAP.blue;
    };

    const handleSelectPrimaryProduct = (product: SystemProduct) => {
        setPrimaryProductId(product.id);
        setProductError(null);
        setType(product.name);
        if (product.default_os) setOs(product.default_os);
        if (product.default_port) setPort(product.default_port);
        if (product.default_protocol) setProtocol(product.default_protocol);

        // Remove from secondary if it was previously toggled as secondary
        setSecondaryProductIds(prev => prev.filter(id => id !== product.id));

        // Auto name suggestion if empty or previously auto-generated
        const isAutoGenerated = name === '' || products.some(p => name === `Servidor ${p.name}`);
        if (isAutoGenerated) {
            setName(`Servidor ${product.name}`);
        }
    };

    const handleToggleSecondaryProduct = (productId: number) => {
        if (productId === primaryProductId) return;
        setSecondaryProductIds(prev =>
            prev.includes(productId)
                ? prev.filter(id => id !== productId)
                : [...prev, productId]
        );
    };

    const handleCreateProduct = async () => {
        if (!newProductName.trim()) {
            setNewProductError('El nombre del tipo/producto es obligatorio.');
            return;
        }

        setCreatingProduct(true);
        setNewProductError(null);
        try {
            const res = await createSystemProduct({
                name: newProductName.trim(),
                icon: newProductIcon,
                color: newProductColor,
                default_os: newProductOs,
                default_port: newProductPort.trim() || '22',
                default_protocol: newProductProtocol.trim() || 'SSH'
            });

            if (res && res.product) {
                const created = res.product;
                // Add to products list
                setProducts(prev => [...prev, created]);
                // Select as primary immediately
                handleSelectPrimaryProduct(created);
                // Reset form & close
                resetNewProductForm();
                setShowNewProductForm(false);
            }
        } catch (err: any) {
            console.error("Error creating product", err);
            const msg = err.response?.data?.message || err.message || 'Error al guardar el nuevo producto';
            setNewProductError(msg);
        } finally {
            setCreatingProduct(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        // Obligatorio: Validar que se haya seleccionado un producto principal
        if (!primaryProductId) {
            setProductError('Debes seleccionar un Producto / Tipo Principal para el servidor.');
            return;
        }

        setLoading(true);
        try {
            await onSave({
                id: initialData?.id,
                name,
                ip_address: ip,
                os,
                server_type: type,
                primary_product_id: primaryProductId,
                secondary_product_ids: secondaryProductIds,
                description,
                notes,
                ssh_user: sshUser,
                ssh_password: sshPassword,
                db_user: dbUser,
                db_password: dbPassword,
                port,
                protocol,
                db_engine: dbEngine,
                db_name: dbName
            });
            onClose();
        } catch (error: any) {
            console.error("Failed to save server", error);
            const msg = error.response?.data?.message || error.message || "Error desconocido al guardar";
            alert(`Error al guardar: ${msg}`);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async () => {
        if (!initialData?.id) return;
        if (!confirm('¿Estás seguro de que quieres eliminar este servidor? Se mantendrá en el historial pero no estará visible.')) return;

        setLoading(true);
        try {
            await deleteServer(initialData.id);
            onClose();
            window.location.reload();
        } catch (error) {
            console.error("Failed to delete", error);
            alert("Error al eliminar servidor");
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div className="bg-card rounded-2xl border border-border w-full max-w-4xl shadow-2xl relative max-h-[90vh] flex flex-col">
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-border shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-primary/10 rounded-lg">
                            <Cpu className="w-6 h-6 text-primary" />
                        </div>
                        <div>
                            <h2 className="text-xl font-bold text-foreground">
                                {initialData ? 'Editar Servidor' : 'Agregar Nuevo Sistema'}
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                {initialData ? 'Modifica los detalles del servidor.' : 'Selecciona el producto principal y añade los servicios secundarios.'}
                            </p>
                        </div>
                    </div>
                    <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors p-2 hover:bg-muted rounded-full">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="overflow-y-auto p-6 space-y-6 custom-scrollbar">

                    {/* Section 1: Primary Product (Mandatory) */}
                    <div className="space-y-3 bg-muted/20 p-4 rounded-xl border border-border/70">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-foreground uppercase tracking-wider flex items-center gap-1.5">
                                <Star className="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
                                <span>Producto / Tipo Principal</span>
                                <span className="text-red-500 font-bold">*</span>
                            </label>
                            {primaryProductId ? (
                                <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-500/20 flex items-center gap-1">
                                    <Check className="w-3 h-3 stroke-[3]" /> Principal Definido
                                </span>
                            ) : (
                                <span className="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
                                    Obligatorio
                                </span>
                            )}
                        </div>

                        {/* Product Grid with "+ Nuevo Tipo" button */}
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-2.5">
                            {products.map((preset) => {
                                const IconComp = getIconComponent(preset.icon);
                                const colors = getColorClasses(preset.color);
                                const isSelected = primaryProductId === preset.id;
                                return (
                                    <button
                                        key={preset.id}
                                        type="button"
                                        onClick={() => handleSelectPrimaryProduct(preset)}
                                        className={cn(
                                            "relative flex flex-col items-center justify-center gap-2 p-3 rounded-xl border transition-all duration-200 text-center select-none cursor-pointer group",
                                            isSelected
                                                ? cn(colors.active, "shadow-md")
                                                : "bg-card border-border hover:border-primary/50 text-muted-foreground hover:bg-muted/50 hover:text-foreground"
                                        )}
                                    >
                                        {isSelected && (
                                            <span className="absolute -top-1.5 -right-1.5 bg-primary text-primary-foreground rounded-full p-0.5 shadow-sm">
                                                <Check className="w-3 h-3 stroke-[3]" />
                                            </span>
                                        )}
                                        <div className={cn("p-2 rounded-lg transition-transform group-hover:scale-105", isSelected ? colors.bg : "bg-muted/60")}>
                                            <IconComp className={cn("w-5 h-5", isSelected ? colors.text : "text-muted-foreground group-hover:text-foreground")} />
                                        </div>
                                        <span className="text-xs font-semibold leading-tight line-clamp-2">
                                            {preset.name}
                                        </span>
                                    </button>
                                );
                            })}

                            {/* Button to Add New Type/Product */}
                            <button
                                type="button"
                                onClick={() => setShowNewProductForm(prev => !prev)}
                                className={cn(
                                    "flex flex-col items-center justify-center gap-2 p-3 rounded-xl border border-dashed transition-all duration-200 text-center select-none cursor-pointer group",
                                    showNewProductForm
                                        ? "border-primary bg-primary/10 text-primary shadow-sm ring-1 ring-primary"
                                        : "border-border hover:border-primary/60 text-muted-foreground hover:bg-muted/40 hover:text-foreground"
                                )}
                            >
                                <div className="p-2 rounded-lg bg-muted/60 group-hover:bg-primary/10 transition-colors">
                                    <Plus className="w-5 h-5 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <span className="text-xs font-semibold leading-tight text-primary">
                                    + Nuevo Tipo
                                </span>
                            </button>
                        </div>

                        {productError && (
                            <div className="flex items-center gap-1.5 text-xs font-medium text-red-500 bg-red-500/10 border border-red-500/20 p-2.5 rounded-lg mt-2">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                <span>{productError}</span>
                            </div>
                        )}

                        {/* Inline Form to Create New System Product */}
                        {showNewProductForm && (
                            <div className="bg-card border border-primary/30 rounded-xl p-4 space-y-4 shadow-md animate-in fade-in-50 duration-200 mt-3">
                                <div className="flex items-center justify-between border-b border-border pb-2.5">
                                    <div className="flex items-center gap-2">
                                        <span className="p-1.5 bg-primary/10 rounded-lg text-primary">
                                            <Plus className="w-4 h-4" />
                                        </span>
                                        <h4 className="text-sm font-bold text-foreground">Crear y Registrar Nuevo Tipo de Servidor</h4>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setShowNewProductForm(false)}
                                        className="text-muted-foreground hover:text-foreground p-1 hover:bg-muted rounded-full"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                    {/* Live Preview Card */}
                                    <div className="md:col-span-4 flex flex-col items-center justify-center p-3.5 rounded-xl border bg-muted/20 text-center">
                                        <span className="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-2">Vista Previa</span>
                                        <div className={cn("p-3 rounded-xl mb-1.5", getColorClasses(newProductColor).bg)}>
                                            {React.createElement(getIconComponent(newProductIcon), {
                                                className: cn("w-7 h-7", getColorClasses(newProductColor).text)
                                            })}
                                        </div>
                                        <span className="text-xs font-bold text-foreground truncate max-w-full">
                                            {newProductName.trim() || 'Nuevo Sistema'}
                                        </span>
                                        <span className="text-[10px] text-muted-foreground mt-0.5">
                                            {newProductOs} · Puerto {newProductPort || '22'} · {newProductProtocol || 'SSH'}
                                        </span>
                                    </div>

                                    {/* Inputs */}
                                    <div className="md:col-span-8 space-y-3">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label className="block text-xs font-medium text-foreground mb-1">
                                                    Nombre del Producto / Tipo <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    value={newProductName}
                                                    onChange={(e) => setNewProductName(e.target.value)}
                                                    placeholder="Ej: Servidor Redis, DNS, Gateway..."
                                                    className="w-full px-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground"
                                                    autoFocus
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-foreground mb-1">OS por Defecto</label>
                                                <select
                                                    value={newProductOs}
                                                    onChange={(e) => setNewProductOs(e.target.value)}
                                                    className="w-full px-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground"
                                                >
                                                    <option value="Linux">Linux</option>
                                                    <option value="Windows">Windows</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label className="block text-xs font-medium text-foreground mb-1">Puerto por Defecto</label>
                                                <input
                                                    type="text"
                                                    value={newProductPort}
                                                    onChange={(e) => setNewProductPort(e.target.value)}
                                                    placeholder="Ej: 6379, 8080, 22"
                                                    className="w-full px-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-medium text-foreground mb-1">Protocolo por Defecto</label>
                                                <input
                                                    type="text"
                                                    value={newProductProtocol}
                                                    onChange={(e) => setNewProductProtocol(e.target.value)}
                                                    placeholder="Ej: TCP, HTTP, SSH, TDS"
                                                    className="w-full px-3 py-1.5 text-xs bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Dynamic Icon Picker */}
                                <div className="space-y-1.5">
                                    <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                        Seleccionar Icono Dinámico
                                    </label>
                                    <div className="grid grid-cols-4 sm:grid-cols-8 gap-2">
                                        {AVAILABLE_ICONS.map((item) => {
                                            const IconComp = item.icon;
                                            const isSelected = newProductIcon === item.name;
                                            return (
                                                <button
                                                    key={item.name}
                                                    type="button"
                                                    onClick={() => setNewProductIcon(item.name)}
                                                    title={item.label}
                                                    className={cn(
                                                        "flex flex-col items-center justify-center gap-1 p-2 rounded-lg border text-xs transition-all select-none cursor-pointer",
                                                        isSelected
                                                            ? "border-primary bg-primary/15 text-primary ring-2 ring-primary/40 font-semibold shadow-xs"
                                                            : "border-border bg-background hover:bg-muted/60 text-muted-foreground hover:text-foreground"
                                                    )}
                                                >
                                                    <IconComp className="w-4 h-4" />
                                                    <span className="text-[10px] truncate max-w-full">{item.label.split(' ')[0]}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Color Palette Picker */}
                                <div className="space-y-1.5">
                                    <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                        Color del Tema
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {AVAILABLE_COLORS.map((col) => {
                                            const isSelected = newProductColor === col.name;
                                            return (
                                                <button
                                                    key={col.name}
                                                    type="button"
                                                    onClick={() => setNewProductColor(col.name)}
                                                    className={cn(
                                                        "flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs transition-all select-none cursor-pointer",
                                                        isSelected
                                                            ? "ring-2 ring-primary border-transparent font-semibold bg-muted"
                                                            : "border-border hover:bg-muted/50 text-muted-foreground"
                                                    )}
                                                >
                                                    <span className={cn("w-2.5 h-2.5 rounded-full", col.bg)} />
                                                    <span className="text-[11px]">{col.label}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Error message */}
                                {newProductError && (
                                    <p className="text-xs text-red-500 font-medium">⚠️ {newProductError}</p>
                                )}

                                {/* Action Buttons */}
                                <div className="flex justify-end items-center gap-2 pt-1 border-t border-border">
                                    <button
                                        type="button"
                                        onClick={() => setShowNewProductForm(false)}
                                        className="px-3 py-1.5 text-xs text-muted-foreground hover:text-foreground font-medium rounded-lg hover:bg-muted transition-colors"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleCreateProduct}
                                        disabled={creatingProduct || !newProductName.trim()}
                                        className="px-4 py-1.5 text-xs font-semibold bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-50"
                                    >
                                        {creatingProduct ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Check className="w-3.5 h-3.5" />}
                                        Guardar en Base de Datos y Usar
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Section 2: Secondary Products (Optional Multi-Select with 1-click toggle) */}
                    <div className="space-y-2.5 bg-muted/10 p-4 rounded-xl border border-border/50">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-semibold text-muted-foreground uppercase tracking-wider flex items-center gap-1.5">
                                <Layers className="w-3.5 h-3.5 text-primary" />
                                <span>Productos / Servicios Secundarios</span>
                                <span className="text-muted-foreground text-[11px] font-normal lowercase">(opcional - 1 clic para activar)</span>
                            </label>
                            {secondaryProductIds.length > 0 && (
                                <span className="text-xs font-semibold text-primary bg-primary/10 px-2.5 py-0.5 rounded-full border border-primary/20">
                                    {secondaryProductIds.length} añadido{secondaryProductIds.length > 1 ? 's' : ''}
                                </span>
                            )}
                        </div>

                        <p className="text-xs text-muted-foreground">
                            Haz clic para agregar o quitar otros servicios o softwares que también conviven en esta máquina:
                        </p>

                        <div className="flex flex-wrap gap-2 pt-1">
                            {products
                                .filter((p) => p.id !== primaryProductId)
                                .map((p) => {
                                    const IconComp = getIconComponent(p.icon);
                                    const colors = getColorClasses(p.color);
                                    const isSelected = secondaryProductIds.includes(p.id);
                                    return (
                                        <button
                                            key={`sec-${p.id}`}
                                            type="button"
                                            onClick={() => handleToggleSecondaryProduct(p.id)}
                                            className={cn(
                                                "flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all duration-150 cursor-pointer select-none",
                                                isSelected
                                                    ? cn(colors.bg, colors.text, "border-current ring-1 ring-current font-semibold shadow-xs")
                                                    : "bg-card border-border text-muted-foreground hover:bg-muted/70 hover:text-foreground hover:border-border/80"
                                            )}
                                        >
                                            <IconComp className="w-3.5 h-3.5" />
                                            <span>{p.name}</span>
                                            {isSelected ? (
                                                <span className="ml-1 bg-current text-card rounded-full w-3.5 h-3.5 flex items-center justify-center text-[10px] font-bold">
                                                    ✓
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground/60 text-[11px] font-bold">+</span>
                                            )}
                                        </button>
                                    );
                                })}
                        </div>
                    </div>

                    <form id="server-form" onSubmit={handleSubmit} className="space-y-6">
                        {/* Main Info */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div className="lg:col-span-1">
                                <label className="block text-sm font-medium text-foreground mb-1.5">Nombre del Servidor</label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Ej: FMS Principal"
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Dirección IP</label>
                                <input
                                    type="text"
                                    value={ip}
                                    onChange={(e) => setIp(e.target.value)}
                                    placeholder="10.10.x.x"
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Tipo (Manual)</label>
                                <input
                                    type="text"
                                    value={type}
                                    onChange={(e) => setType(e.target.value)}
                                    list="server-types"
                                    placeholder="Escribe o selecciona..."
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                />
                                <datalist id="server-types">
                                    {products.map((p) => (
                                        <option key={p.id} value={p.name} />
                                    ))}
                                </datalist>
                            </div>
                        </div>

                        {/* Details Row */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-5">
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">OS</label>
                                <select
                                    value={os}
                                    onChange={(e) => setOs(e.target.value)}
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground appearance-none transition-all"
                                >
                                    <option>Linux</option>
                                    <option>Windows</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Puerto</label>
                                <input
                                    type="text"
                                    value={port}
                                    onChange={(e) => setPort(e.target.value)}
                                    placeholder="22"
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Protocolo</label>
                                <input
                                    type="text"
                                    value={protocol}
                                    onChange={(e) => setProtocol(e.target.value)}
                                    placeholder="SSH"
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Motor DB</label>
                                <input
                                    type="text"
                                    value={dbEngine}
                                    onChange={(e) => setDbEngine(e.target.value)}
                                    placeholder="PostgreSQL"
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground transition-all"
                                />
                            </div>
                        </div>

                        {/* Auth Section Box */}
                        <div className="bg-muted/30 border border-border rounded-xl p-5 space-y-4">
                            <div className="flex items-center gap-2 mb-2">
                                <ShieldCheck className="w-4 h-4 text-primary" />
                                <h3 className="text-sm font-semibold text-foreground">Credenciales de Acceso</h3>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* SSH/System Auth */}
                                <div className="space-y-3">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">Sistema / SSH</p>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs text-muted-foreground mb-1">Usuario</label>
                                            <input
                                                type="text"
                                                value={sshUser}
                                                onChange={(e) => setSshUser(e.target.value)}
                                                className="w-full px-3 py-1.5 bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-sm"
                                                placeholder="jigsaw"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-muted-foreground mb-1">Password</label>
                                            <input
                                                type="text"
                                                value={sshPassword}
                                                onChange={(e) => setSshPassword(e.target.value)}
                                                className="w-full px-3 py-1.5 bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-sm font-mono"
                                                placeholder="••••••"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* DB Auth */}
                                <div className="space-y-3">
                                    <p className="text-xs font-medium text-muted-foreground uppercase">Base de Datos</p>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="col-span-2">
                                            <label className="block text-xs text-muted-foreground mb-1">Nombre Base de Datos</label>
                                            <input
                                                type="text"
                                                value={dbName}
                                                onChange={(e) => setDbName(e.target.value)}
                                                className="w-full px-3 py-1.5 bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-sm"
                                                placeholder="Nombre BD (opcional)"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-muted-foreground mb-1">Usuario DB</label>
                                            <input
                                                type="text"
                                                value={dbUser}
                                                onChange={(e) => setDbUser(e.target.value)}
                                                className="w-full px-3 py-1.5 bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-sm"
                                                placeholder="jigsaw"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs text-muted-foreground mb-1">Pass DB</label>
                                            <input
                                                type="text"
                                                value={dbPassword}
                                                onChange={(e) => setDbPassword(e.target.value)}
                                                className="w-full px-3 py-1.5 bg-background border border-input rounded-lg focus:ring-1 focus:ring-primary outline-none text-foreground text-sm font-mono"
                                                placeholder="••••••"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Descripción</label>
                                <textarea
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground h-28 transition-all"
                                    placeholder="Descripción general del servidor..."
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-foreground mb-1.5">Notas</label>
                                <textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    className="w-full px-4 py-2 bg-muted/30 border border-input rounded-xl focus:ring-2 focus:ring-primary outline-none text-foreground h-28 transition-all font-mono text-xs"
                                    placeholder="Notas de mantenimiento, advertencias, etc..."
                                />
                            </div>
                        </div>
                    </form>
                </div>

                <div className="p-6 border-t border-border bg-card/50 flex justify-between items-center rounded-b-2xl shrink-0">
                    {initialData ? (
                        <button
                            type="button"
                            className="text-red-500 hover:text-red-600 text-sm font-medium opacity-80 hover:opacity-100 transition-opacity flex items-center gap-2 hover:bg-red-50 dark:hover:bg-red-950/30 p-2 rounded-lg"
                            onClick={handleDelete}
                        >
                            Eliminar Servidor
                        </button>
                    ) : <div></div>}

                    <div className="flex gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-muted-foreground hover:text-foreground font-medium transition-colors hover:bg-muted rounded-xl"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            form="server-form"
                            disabled={loading}
                            className="px-6 py-2 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold rounded-xl shadow-lg shadow-primary/20 transition-all flex items-center gap-2"
                        >
                            {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                            {initialData ? 'Guardar Cambios' : 'Crear Servidor'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ServerModal;
