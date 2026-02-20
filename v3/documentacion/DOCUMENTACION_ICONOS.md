# Documentación de Íconos (MiningIcons)

Esta documentación detalla la estructura y el uso del sistema de íconos personalizados para el frontend del proyecto **remoteMineMonitor v3**. Todos los íconos se encuentran en la ruta `frontend/src/components/icons`.

## 1. Galería de Íconos y Catálogo

El proyecto utiliza dos tipos principales de íconos para representar los servidores, componentes y sistemas en el dashboard y modales:

### A. Íconos Vectoriales Personalizados (SVGs Mineros)
Estos son dibujos e ilustraciones vectoriales minimalistas que representan maquinaria y conceptos complejos (diseñados para verse modernos e integrarse con la UI):
- **Maquinaria:** `RajoAbierto`, `CamionCaex`, `PalaElectrica`, `MolinoSag`, `TorrePerforacion`.
- **Infraestructura:** `PlantaProceso`, `PilaAcopio`.
- **Sistemas Especiales (SVG):**
    - `TunelIcon`: Dos computadoras conectadas por un servidor (conexión dashed).
    - `NtpAntennaIcon`: Antena de telecomunicaciones clásica con ondas de señal.
    - `PivoteIcon`: Un monitor de PC que incluye las iniciales **PV** en pantalla.

### B. Íconos de Iniciales (SystemIcon)
Para los sistemas de software específicos se utiliza el componente `SystemIcon`, que genera un recuadro redondeado con iniciales.

**Íconos de iniciales configurados:**
- `JviewIcon` (JV)
- `FmsIcon` (FMS)
- `MeIcon` (ME - Mine Enterprise)
- `CasIcon` (CAS)
- `OasIcon` (OAS)
- `SlIcon` (SL - Sharperlight)
- `MpdataIcon` (MP)
- `SqlIcon` (SQL)
- `PgIcon` (PG - Postgres)
- `IisIcon` (IIS)

## 2. Nomenclatura Automática (Presets)

El módulo `ServerModal.tsx` incluye una lógica de autocompletado basada en estos íconos:
- Al seleccionar un preset, el campo **Nombre del Servidor** se rellena automáticamente siguiendo el formato: `[Nombre del Producto] [Alias del Sitio]`.
- Ejemplo: Si el sitio es `amcen` y se elige el preset **CAS**, el nombre sugerido será **CAS amcen**.
- Se eliminó el prefijo "Servidor " de los nombres de los productos para mantener una nomenclatura limpia.

## 3. Agregar un Nuevo Ícono de Sistema (Iniciales)

Si se incorpora un nuevo "Producto" o "Sistema" al monitoreo (ej. Dispatch), no es necesario dibujar un SVG complejo. Simplemente se exporta una nueva instancia de `SystemIcon` en el archivo `MiningIcons.tsx`:

```tsx
// Al final de frontend/src/components/icons/MiningIcons.tsx
export const DispatchIcon = (props: IconProps) => <SystemIcon initials="DSP" {...props} />;
```

## 4. Visualización en la Galería

El panel de **Super Admin** cuenta con una pestaña "Galería Iconos" (`MiningIconGallery.tsx`). Esta pantalla lee dinámicamente el archivo `MiningIcons.tsx` y despliega todos los componentes exportados (excluyendo la plantilla `SystemIcon` directamente para evitar errores de renderizado). 

Desde allí, puedes ver el ícono renderizado y hacer un clic rápido para **Copiar el código JSX** al portapapeles.
