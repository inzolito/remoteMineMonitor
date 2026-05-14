import * as MiningIcons from './MiningIcons';
import { Copy, Check } from 'lucide-react';
import { useState } from 'react';

const MiningIconGallery = () => {
    const [copied, setCopied] = useState<string | null>(null);

    const copyToClipboard = (name: string) => {
        const text = `<${name} />`;
        navigator.clipboard.writeText(text);
        setCopied(name);
        setTimeout(() => setCopied(null), 2000);
    };

    const icons = Object.entries(MiningIcons).filter(([name]) => name !== 'default');

    return (
        <div className="space-y-6">
            <div className="bg-primary/5 border border-primary/10 p-6 rounded-2xl">
                <h3 className="text-lg font-bold text-primary mb-2">Colección de Íconos Mineros (Custom SVG)</h3>
                <p className="text-sm text-muted-foreground">
                    Esta es una suite de íconos vectoriales diseñados específicamente para el monitoreo remoto.
                    Son escalables, ligeros y siguen el lenguaje visual del sitio.
                </p>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {icons.map(([name, IconComponent]: [string, any]) => (
                    <div
                        key={name}
                        className="group bg-card border rounded-xl p-6 flex flex-col items-center justify-center gap-4 hover:border-primary/50 hover:shadow-md transition-all relative"
                    >
                        <div className="p-4 bg-muted/30 rounded-full group-hover:bg-primary/10 transition-colors">
                            <IconComponent size={40} className="text-foreground group-hover:text-primary transition-colors" />
                        </div>
                        <div className="text-center">
                            <div className="font-bold text-sm">{name}</div>
                            <button
                                onClick={() => copyToClipboard(name)}
                                className="mt-2 flex items-center gap-1.5 text-[10px] uppercase tracking-wider font-bold text-muted-foreground hover:text-primary transition-colors"
                            >
                                {copied === name ? (
                                    <>
                                        <Check className="w-3 h-3" />
                                        Copiado
                                    </>
                                ) : (
                                    <>
                                        <Copy className="w-3 h-3" />
                                        Copiar JSX
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            <div className="bg-muted/30 p-4 rounded-xl border border-dashed text-center">
                <p className="text-xs text-muted-foreground italic">
                    ¿Necesitas un ícono nuevo? Pídeme que lo diseñe y lo verás aparecer aquí.
                </p>
            </div>
        </div>
    );
};

export default MiningIconGallery;
