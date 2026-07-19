# Catálogo de servicio y SLA

Este SLA es una plantilla comercial. Solo debe incorporarse a contratos después de medir el servicio durante al menos 30 días en la infraestructura definitiva.

## Objetivo de disponibilidad

- Edición Business: objetivo mensual de 99,5 %.
- Edición Enterprise: objetivo mensual de 99,9 %.
- Ventanas de mantenimiento anunciadas con 72 horas y emergencias de seguridad quedan documentadas por separado.
- La disponibilidad se calcula sobre solicitudes válidas al servicio web, excluyendo redes del cliente y proveedores externos fuera del control contratado.

## Soporte

| Severidad | Ejemplo | Acuse Enterprise | Actualizaciones |
|---|---|---:|---:|
| SEV-1 | Servicio caído o exposición activa | 30 min, 24/7 | cada 60 min |
| SEV-2 | Flujo crítico degradado | 2 h hábiles | cada 4 h |
| SEV-3 | Función no crítica afectada | 1 día hábil | diaria |
| SEV-4 | Consulta o mejora | 2 días hábiles | por acuerdo |

Los tiempos son de respuesta y coordinación, no promesas irreales de resolución. Los créditos de servicio, límites de responsabilidad, canales y zonas horarias deben definirse en el contrato firmado.

## Responsabilidades compartidas

PeopleOS mantiene aplicación, parches, backups contratados y monitoreo. El cliente administra usuarios autorizados, exactitud de datos, dispositivos, dominios SSO y el uso legítimo de la información. Ambas partes mantienen contactos de seguridad actualizados.
