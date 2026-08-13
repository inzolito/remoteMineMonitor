# Integración de Asistente IA (Google Gemini)

Este documento detalla la planificación y arquitectura para la implementación de un Asistente Inteligente basado en Google Gemini. El objetivo principal es permitir a los usuarios consultar problemas históricos, resoluciones y tickets específicos mediante lenguaje natural, buscando de forma inteligente en la base de datos local de Salesforce (`sf_cases` y `sf_case_comments`).

## 1. Requisitos Previos
- **API Key de Gemini:** Se requiere generar una clave en [Google AI Studio](https://aistudio.google.com/). 
- **Capa Gratuita:** La capa "Free Tier" es suficiente para esta implementación, ya que permite hasta 15 consultas por minuto y ofrece tokens suficientes para el volumen operativo esperado en tareas de soporte técnico.
- **Seguridad:** La API Key se almacenará en un archivo de entorno (`.env`) o en un archivo de configuración bloqueado en el backend, nunca expuesta en el frontend.

## 2. Enfoque Escalable (Multi-Módulo)
Para cumplir con el requisito de escalabilidad y permitir que el asistente se utilice en **múltiples módulos** (Turno 7x7, Tickets Globales, Home, etc.), la arquitectura se dividirá de la siguiente manera:

### Frontend: Componente Reutilizable
Se creará un componente de React independiente y encapsulado (ej. `AIChatWidget.tsx`). 
Este componente aceptará **"contextos"** como parámetros (props), lo que le permitirá a la IA saber en qué módulo se encuentra el usuario y adaptar sus respuestas o filtros de búsqueda.

**Ejemplo de uso en el código:**
```tsx
// En el módulo Turno 7x7:
<AIChatWidget moduleContext="turno_7x7" filterFaena="ALL" />

// En la página global de Tickets:
<AIChatWidget moduleContext="general_tickets" />
```
El diseño será un panel (Div) estético o un botón flotante que pueda ser incrustado en cualquier vista sin romper el layout (utilizando React `createPortal` si es necesario para evitar problemas de superposición).

### Backend: Endpoint Unificado
Se desarrollará un único punto de entrada en el servidor: `api/ai_chat.php`.
Este endpoint manejará las consultas de todos los módulos, pero procesará la información dependiendo del contexto que envíe el frontend.

## 3. Flujo Lógico Interno (Text-to-SQL)

Dado que las consultas se hacen a una base de datos MySQL relacional, el flujo de trabajo de la IA constará de 3 pasos que ocurrirán en fracciones de segundo en el servidor:

1. **Interpretación y Generación de Consulta (Text-to-SQL):**
   El usuario envía su duda (ej: *"¿Qué pasó con las antenas en Codelco ayer?"*). El backend envía esta pregunta a Gemini junto con el esquema de las tablas de Salesforce. Gemini devuelve una consulta SQL de tipo `SELECT`.
2. **Ejecución Segura en BD:**
   El script PHP evalúa que la consulta sea estrictamente un `SELECT` (evitando cualquier riesgo de inyección o modificación) y la ejecuta contra la base de datos `rmmsalesforce`.
3. **Síntesis y Respuesta Humana:**
   Los resultados de la base de datos (filas, comentarios, números de ticket) se empaquetan y se envían nuevamente a Gemini. Gemini lee estos datos duros y redacta una respuesta conversacional, amable y clara para el usuario, entregando los números de tickets como referencias.

## 4. Limitaciones y Consideraciones
- **Privacidad de Datos:** Al usar la API gratuita, se asume que los comentarios de los tickets consultados no contienen secretos corporativos críticos (como contraseñas, IPs internas sensibles o datos financieros), ya que Google podría usar estos prompts de forma anónima para entrenamiento en su Free Tier. Si las políticas de la empresa exigen privacidad absoluta, se requeriría el plan de pago (Pay-as-you-go).
- **Rendimiento de Base de Datos:** Las consultas generadas por la IA deben estar optimizadas. Se establecerán límites de resultados (`LIMIT 10`) en las instrucciones enviadas a Gemini para que la base de datos no se sobrecargue buscando demasiados registros históricos simultáneamente.
