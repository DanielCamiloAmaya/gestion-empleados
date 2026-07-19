# Plan E2E · PeopleOS

## Alcance

Validar en Chromium real el ciclo operativo principal de PeopleOS con datos aislados y reproducibles.

## Escenarios

1. Recursos Humanos inicia sesión, visualiza People Pulse, crea un departamento y crea un empleado con acceso seguro.
2. El evento de incorporación aparece en auditoría.
3. Un empleado inicia sesión, ve su portal, crea una solicitud de ausencia y solo accede a capacidades permitidas.
4. Recursos Humanos aprueba esa solicitud, asigna una tarea de onboarding y crea un objetivo de desempeño.
5. El empleado adjunta un PDF privado y envía una versión formal a revisión.
6. Recursos Humanos descarga/revisa la evidencia, deja retroalimentación y aprueba la tarea.
7. El empleado lleva el objetivo al 100 % y ve la solicitud aprobada.
8. La interfaz móvil conserva la navegación, no tiene desbordamiento horizontal y respeta preferencias de movimiento reducido.
9. La cookie administrativa no es persistente y un contexto de navegador nuevo exige autenticación.

## Criterios de aceptación

- No se usan credenciales ni datos de la base de desarrollo.
- Cada mutación se confirma en la interfaz posterior a la operación.
- Los selectores se basan en roles, etiquetas y texto visible.
- La suite se ejecuta contra SQLite aislado en el puerto 8001.
