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

/**
 * Servidor IIS (Internet Information Server)
 * Representation of a Windows Web Server.
 */
export const IisServer = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Server Block */}
        <rect x="4" y="4" width="16" height="16" rx="2" ry="2" />
        {/* Screen/Globe indication inside */}
        <circle cx="12" cy="12" r="4" opacity="0.8" />
        <path d="M12 8v8" opacity="0.8" />
        <path d="M8 12h8" opacity="0.8" />
        {/* Server lights */}
        <circle cx="8" cy="18" r="1" fill="currentColor" />
        <circle cx="12" cy="18" r="1" fill="currentColor" />
    </svg>
);

/**
/**
 * Túnel (Tunnel)
 * Two computers connected by a server
 */
export const TunelIcon = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Left Computer */}
        <rect x="2" y="6" width="6" height="4" rx="1" />
        <path d="M2 12h6" />
        <path d="M5 10v2" />

        {/* Right Computer */}
        <rect x="16" y="6" width="6" height="4" rx="1" />
        <path d="M16 12h6" />
        <path d="M19 10v2" />

        {/* Center Server */}
        <rect x="10" y="14" width="4" height="6" rx="1" />
        <path d="M11 16h2M11 18h2" />

        {/* Connections */}
        <path d="M5 12v3h5" strokeDasharray="2 2" />
        <path d="M19 12v3h-5" strokeDasharray="2 2" />
    </svg>
);

/**
 * NTP Server (Antenna)
 * Radio/phone style antenna
 */
export const NtpAntennaIcon = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Base */}
        <path d="M12 21v-8" />
        <path d="M8 21h8" />

        {/* Signal waves */}
        <path d="M8.5 7.5A5 5 0 0 1 15.5 7.5" opacity="0.4" />
        <path d="M6 5A8 8 0 0 1 18 5" opacity="0.7" />
        <path d="M3 2A12 12 0 0 1 21 2" />

        {/* Antenna tip */}
        <circle cx="12" cy="13" r="2" fill="currentColor" />
        <path d="M12 11V5" />
    </svg>
);

/**
 * Pivote (PC with PV text)
 */
export const PivoteIcon = ({ size = 24, className, ...props }: IconProps) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        {/* Monitor Frame */}
        <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
        {/* Stand */}
        <path d="M8 21h8" />
        <path d="M12 17v4" />
        {/* PV Text */}
        <text
            x="12" y="10.5"
            textAnchor="middle"
            dominantBaseline="central"
            fontSize="8"
            fontWeight="bold"
            fill="currentColor"
            stroke="none"
        >
            PV
        </text>
    </svg>
);

/**
 * Generic System Icon (Initials inside a rounded square)
 */
export const SystemIcon = ({
    initials,
    size = 24,
    className,
    ...props
}: IconProps & { initials: string }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className} {...props}>
        <rect x="3" y="3" width="18" height="18" rx="4" ry="4" strokeWidth="2" />
        <text
            x="12" y="12"
            textAnchor="middle"
            dominantBaseline="central"
            fontSize={initials.length > 2 ? "8" : "10"}
            fontWeight="bold"
            fill="currentColor"
            stroke="none"
        >
            {initials}
        </text>
    </svg>
);

export const JviewIcon = (props: IconProps) => <SystemIcon initials="JV" {...props} />;
export const FmsIcon = (props: IconProps) => <SystemIcon initials="FMS" {...props} />;
export const MeIcon = (props: IconProps) => <SystemIcon initials="ME" {...props} />;
export const CasIcon = (props: IconProps) => <SystemIcon initials="CAS" {...props} />;
export const OasIcon = (props: IconProps) => <SystemIcon initials="OAS" {...props} />;
export const SlIcon = (props: IconProps) => <SystemIcon initials="SL" {...props} />;
export const MpdataIcon = (props: IconProps) => <SystemIcon initials="MP" {...props} />;
export const SqlIcon = (props: IconProps) => <SystemIcon initials="SQL" {...props} />;
export const PgIcon = (props: IconProps) => <SystemIcon initials="PG" {...props} />;
export const NtpIcon = (props: IconProps) => <SystemIcon initials="NTP" {...props} />;
export const IisIcon = (props: IconProps) => <SystemIcon initials="IIS" {...props} />;
