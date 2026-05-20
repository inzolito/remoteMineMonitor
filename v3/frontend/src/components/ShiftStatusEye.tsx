import React from 'react';
import { cn } from '../lib/utils';

interface ShiftStatusEyeProps {
    isWorking: boolean;
    className?: string;
}

export const ShiftStatusEye: React.FC<ShiftStatusEyeProps> = ({ isWorking, className }) => {
    return (
        <div className={cn("inline-flex items-center gap-1.5 select-none shrink-0", className)}>
            <style dangerouslySetInnerHTML={{ __html: `
                @keyframes eye-blink {
                    0%, 90%, 94%, 98%, 100% {
                        transform: scaleY(1);
                    }
                    92%, 96% {
                        transform: scaleY(0.1);
                    }
                }
                @keyframes sleeping-breath {
                    0%, 100% {
                        transform: scale(0.96);
                        opacity: 0.55;
                    }
                    50% {
                        transform: scale(1.04);
                        opacity: 0.85;
                    }
                }
                @keyframes status-pulse {
                    0%, 100% {
                        transform: scale(1);
                        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
                    }
                    50% {
                        transform: scale(1.1);
                    }
                    70% {
                        box-shadow: 0 0 0 5px rgba(16, 185, 129, 0);
                    }
                }
                .animate-eye-blink {
                    animation: eye-blink 4.5s infinite ease-in-out;
                    transform-origin: center;
                }
                .animate-sleep-breath {
                    animation: sleeping-breath 3s infinite ease-in-out;
                    transform-origin: center;
                }
                .animate-status-pulse {
                    animation: status-pulse 2s infinite ease-in-out;
                }
            `}} />

            {isWorking ? (
                // Active/Working: Open Blinking Eye with Green Indicator
                <div className="flex items-center gap-1.5 bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 px-2 py-0.5 rounded-full">
                    {/* Pulsing indicator */}
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-status-pulse" />
                    
                    {/* SVG Eye */}
                    <svg 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        strokeWidth="2.5" 
                        strokeLinecap="round" 
                        strokeLinejoin="round" 
                        className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"
                    >
                        {/* Eye shape group with blink animation */}
                        <g className="animate-eye-blink">
                            {/* Outer eyelids */}
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                            {/* Pupil */}
                            <circle cx="12" cy="12" r="3" fill="currentColor" className="opacity-90" />
                            {/* Reflection shine */}
                            <circle cx="13.5" cy="10.5" r="0.8" fill="white" stroke="none" />
                        </g>
                    </svg>
                    
                    <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">En Turno</span>
                </div>
            ) : (
                // Resting/Sleeping: Closed Eye with breathing animation
                <div className="flex items-center gap-1 bg-slate-500/5 dark:bg-slate-500/10 border border-slate-500/10 px-2 py-0.5 rounded-full opacity-70">
                    <svg 
                        viewBox="0 0 24 24" 
                        fill="none" 
                        stroke="currentColor" 
                        strokeWidth="2" 
                        strokeLinecap="round" 
                        strokeLinejoin="round" 
                        className="w-3.5 h-3.5 text-slate-500 dark:text-slate-400 animate-sleep-breath"
                    >
                        {/* Closed eyelid (U-shape curve) */}
                        <path d="M4 10c3 5 13 5 16 0" />
                        {/* Eyelashes */}
                        <path d="M7 13.5l-1.5 2.5" />
                        <path d="M12 15v3" />
                        <path d="M17 13.5l1.5 2.5" />
                    </svg>
                    
                    <span className="text-[8px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Descanso</span>
                </div>
            )}
        </div>
    );
};
