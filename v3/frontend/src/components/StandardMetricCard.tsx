import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

interface StandardMetricCardProps {
    title: string;
    value: string | number | React.ReactNode;
    subValue?: string | React.ReactNode;
    icon: React.ElementType;
    statusColor?: 'success' | 'warning' | 'danger' | 'info';
    onClick?: () => void;
    pulse?: boolean;
}

export default function StandardMetricCard({
    title,
    value,
    subValue,
    icon: Icon,
    statusColor = 'success',
    onClick,
    pulse = false,
}: StandardMetricCardProps) {
    
    const bgColors = {
        success: 'bg-[#f0fdf4] dark:bg-emerald-950/20 border-[#dcfce7] dark:border-emerald-900/30 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-emerald-900',
        warning: 'bg-amber-500 border-amber-600 hover:bg-amber-400 text-amber-950',
        danger: 'bg-red-600 border-red-700 hover:bg-red-500 text-white shadow-md',
        info: 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-900',
    };

    const textColors = {
        success: 'text-[#166534] dark:text-emerald-400',
        warning: 'text-amber-950',
        danger: 'text-white',
        info: 'text-blue-700 dark:text-blue-400',
    };

    const titleColors = {
        success: 'text-[#166534]/50 dark:text-emerald-400/50',
        warning: 'text-amber-900/80',
        danger: 'text-red-100',
        info: 'text-blue-700/60 dark:text-blue-400/60',
    };

    return (
        <div
            onClick={onClick}
            className={cn(
                "border rounded-xl p-2 flex flex-col items-center justify-center text-center relative overflow-hidden group transition-colors h-[100px]",
                bgColors[statusColor],
                onClick && "cursor-pointer"
            )}
        >
            <div className={cn(
                "absolute -bottom-2 -right-2 opacity-10 group-hover:scale-110 transition-transform",
                textColors[statusColor]
            )}>
                <Icon size={48} />
            </div>
            
            <div className={cn(
                "text-[10px] md:text-xs font-black uppercase tracking-widest leading-none z-10 mb-1",
                titleColors[statusColor]
            )}>
                {title}
            </div>
            
            <div className={cn(
                "text-3xl font-black z-10 flex items-center justify-center gap-2",
                textColors[statusColor],
                pulse && "animate-pulse"
            )}>
                {value}
            </div>
            
            {subValue && (
                <div className={cn(
                    "text-[10px] font-bold uppercase tracking-tighter z-10 mt-1",
                    textColors[statusColor]
                )}>
                    {subValue}
                </div>
            )}
        </div>
    );
}
