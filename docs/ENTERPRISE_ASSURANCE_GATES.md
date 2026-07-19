# Gates de aseguramiento enterprise

PeopleOS separa claramente controles implementados, evidencia operativa y validación independiente. Una función existente en el código no equivale a una certificación ni a un SLA cumplido.

## Gate automatizado por versión

Toda versión candidata debe pasar:

1. pruebas PHP, aislamiento multitenant y flujos Playwright;
2. análisis estático OWASP, auditoría de dependencias y SBOM;
3. DAST contra una instancia efímera;
4. build de imagen inmutable y escaneo de vulnerabilidades;
5. `php artisan peopleos:readiness --profile=production --json` en staging;
6. despliegue canario, smoke test y rollback verificado.

Si un control falla, la versión no puede etiquetarse como lista para producción.

## Gate de recuperación

Por lo menos cada trimestre se restaura una copia en una cuenta o proyecto aislado. La evidencia mínima incluye identificador del backup, hora de inicio y fin, RPO observado, RTO observado, hash o conteo de integridad, pruebas de login/multitenencia/documentos/API y aprobación de dos responsables.

La existencia de un backup sin restauración comprobada se registra como `no verificado`.

## Gate de seguridad independiente

Antes de una venta enterprise:

- threat model aprobado y matriz OWASP ASVS;
- pentest externo sobre la versión candidata y la infraestructura definitiva;
- cero hallazgos críticos o altos abiertos;
- plan y fecha para hallazgos medios;
- re-test firmado por el proveedor independiente;
- revisión de privacidad, retención y transferencias internacionales aplicable al cliente.

PeopleOS no debe mostrarse como “SOC 2”, “ISO 27001”, “WCAG AA” o “pentested” hasta que exista evidencia vigente emitida por la parte competente.

## Gate contractual

El SLA de `docs/SLA.md` es una plantilla. Solo se convierte en compromiso después de medir el entorno definitivo, acordar exclusiones, soporte, créditos, RPO/RTO y firmar el contrato.
