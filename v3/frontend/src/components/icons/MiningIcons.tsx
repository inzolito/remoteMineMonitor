import React from 'react';

interface IconProps extends React.SVGProps<SVGSVGElement> {
    size?: number | string;
}

/**
 * Rajo Abierto (Open Pit Mine) Icon
 * A minimalist representation of concentric terraces in an open pit.
 */
export const RajoAbierto = ({ size = 24, className, ...props }: IconProps) => (
    <svg
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        className={className}
        {...props}
    >
        {/* Superior layer */}
        <ellipse cx="12" cy="7" rx="10" ry="4" />

        {/* Second terrace level (partial to show depth) */}
        <path d="M19 11c0 1.66-3.13 3-7 3s-7-1.34-7-3" />

        {/* Third terrace level */}
        <path d="M16 15c0 1.1-1.79 2-4 2s-4-.9-4-2" />

        {/* Bottom center */}
        <circle cx="12" cy="18" r="1" fill="currentColor" />

        {/* Depth indicators / Ramps */}
        <path d="M22 7l-3 4" opacity="0.4" />
        <path d="M2 7l3 4" opacity="0.4" />
    </svg>
);

/**
 * Camion CAEX (Mining Haul Truck)
 * Minimalist side view with massive wheels and industrial bed.
 */
export const CamionCaex = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Truck Bed */}
        <path d="M4 14l2-8h10l2 8H4z" />
        {/* Cabin */}
        <rect x="18" y="10" width="3" height="4" />
        {/* Massive Wheels */}
        <circle cx="7" cy="18" r="3" />
        <circle cx="15" cy="18" r="3" />
        {/* Chassis line */}
        <path d="M10 18h2" />
    </svg>
);

/**
 * Pala Electrica (Electric Shovel)
 * Minimalist representation of the bucket and main arm.
 */
export const PalaElectrica = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Main Body */}
        <rect x="3" y="14" width="8" height="6" rx="1" />
        {/* Arm */}
        <path d="M11 16l6-4 4 2" />
        {/* Bucket (Cucharon) */}
        <path d="M18 10l5 3v5l-5 2z" />
        {/* Tracks (Orugas) */}
        <path d="M4 20h6" strokeWidth="3" opacity="0.5" />
    </svg>
);

/**
 * Molino SAG / Bolas (Grinding Mill)
 * Cylindrical drum with gears and rotation indication.
 */
export const MolinoSag = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        <circle cx="12" cy="12" r="8" />
        <circle cx="12" cy="12" r="3" />
        <path d="M12 4v2M12 18v2M4 12h2M18 12h2" />
        <path d="M18.36 5.64l-1.42 1.42M7.06 16.94l-1.42 1.42" />
        <path d="M12 12l4-4" />
    </svg>
);

/**
 * Planta Procesadora (Processing Plant)
 * Silhouette of industrial buildings and silos.
 */
export const PlantaProceso = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        <path d="M2 20h20" />
        <path d="M4 20V10l4-2v12" />
        <path d="M8 20V6l4-2v16" />
        <path d="M12 20V12l4 2v6" />
        <rect x="17" y="10" width="4" height="10" />
        <path d="M19 10c0-2-2-2-2-4s2-2 2-4" strokeWidth="1" opacity="0.6" />
    </svg>
);

/**
 * Torre de Perforación (Drilling Rig)
 * Minimalist vertical structure.
 */
export const TorrePerforacion = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        <path d="M12 2v20" strokeWidth="3" />
        <path d="M8 22l4-20 4 20" />
        <path d="M5 22h14" />
        <path d="M10 8h4M9 13h6M8 18h8" />
    </svg>
);

/**
 * Pila de Acopio (Stockpile)
 * Triangular pile with texture.
 */
export const PilaAcopio = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        <path d="M2 20l10-16 10 16H2z" />
        <path d="M8 12h8M6 16h12" opacity="0.3" />
        <path d="M12 4v2" opacity="0.5" />
    </svg>
);
