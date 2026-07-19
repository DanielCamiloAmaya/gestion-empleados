# Privacidad y cumplimiento

PeopleOS incorpora controles técnicos; no declara certificaciones no obtenidas. El registro de controles dentro del producto permite asignar evidencia para ISO 27001, SOC 2, GDPR, Habeas Data y WCAG, pero la conformidad requiere alcance, políticas, operación sostenida y evaluación independiente.

## Ciclo de vida de datos

1. **Finalidad:** cada dato debe tener una finalidad laboral documentada y una base legítima.
2. **Minimización:** no recolectar información sensible que no sea necesaria.
3. **Acceso:** RBAC, MFA, SSO y auditoría para accesos administrativos.
4. **Retención:** plazos configurados por categoría, país y obligación contractual.
5. **Derechos:** proceso autenticado de consulta, corrección, portabilidad y eliminación cuando sea legalmente procedente.
6. **Salida:** exportación controlada, revocación de tokens, cierre de integraciones y eliminación verificable.

Antes de producción se deben completar el inventario de datos, DPA, lista de subprocesadores, evaluación de transferencias internacionales, política de retención, análisis de impacto y procedimiento de incidentes de privacidad.

## Evidencia de seguridad

- Matriz de riesgos y responsables.
- Registro de cambios y auditoría inmutable en almacenamiento externo.
- Resultados de SAST, DAST, análisis de dependencias y SBOM por release.
- Prueba de restauración y ejercicio de incidentes.
- Pentest independiente anual y después de cambios materiales.
- Revisión de accesibilidad WCAG 2.2 AA con pruebas manuales, no solo automatizadas.
