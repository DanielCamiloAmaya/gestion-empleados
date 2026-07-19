# Reporte de remediación y aseguramiento de PeopleOS

**Fecha de cierre técnico:** 18 de julio de 2026

**Alcance:** bloqueos funcionales, identidad, planes, accesibilidad, veracidad de producto y preparación operativa.

## Resultado

Los bloqueos reproducibles del informe inicial quedaron corregidos y cubiertos con pruebas automatizadas. La aplicación está lista para demostración y staging controlado. Una venta enterprise en producción todavía exige ejecutar los gates externos descritos al final de este documento.

| Hallazgo inicial | Remediación verificable |
|---|---|
| HTTP 500 en evaluaciones vacías | Estado vacío seguro para administrador y empleado; navegación headless aprobada |
| HTTP 500 en tokens con vencimiento | Conversión y validación estricta de vigencia entre 1 y 365 días |
| Sesión abierta de empleado inactivo | Estado laboral y versión de credencial verificados en cada solicitud |
| Controles sin etiqueta en Talento+ | Todos los controles tienen nombre accesible; matriz Playwright reporta 0 anónimos |
| Texto de 8–11 px | Piso operativo de 12 px; matriz reporta 0 textos menores de 12 px |
| Límites de plan decorativos | Puestos, entidades legales, dominios y módulos se validan en el backend |
| Marketplace sobredimensionado | Catálogo marcado como ficha de capacidad; no acredita conectores instalados |
| Evaluación “360°” superficial | Se retiró la afirmación 360; se conserva evaluación trazable sin prometer fuentes múltiples |
| Cumplimiento autodeclarado | Evidencia obligatoria, separación de funciones y advertencia de no certificación |
| Offboarding sin revocación | Inactivación, versión de credencial, remember token, sesiones y accesos pendientes revocados |
| Sin recuperación de contraseña | Flujo genérico anti-enumeración, token hash de 30 minutos y uso único |
| Sin ciclo de vida de administradores | Invitación, rol granular, activación propia, baja, reactivación y protección del último owner |
| HA/DR/SLA solo narrativos | Imagen reproducible, web/worker/scheduler Kubernetes, readiness ejecutable, DAST y gates documentados |

## Evidencia ejecutada

- PHPUnit: **65 pruebas aprobadas, 288 aserciones**.
- Playwright Chromium headless: **22 de 22 flujos aprobados**.
- Calidad visual: 0 controles sin nombre, 0 textos menores de 12 px y 0 overflow en las superficies medidas.
- Consola/red: 0 errores de página, 0 respuestas 5xx y 0 solicitudes fallidas en la matriz.
- Build Vite: aprobado.
- Pint: aprobado.
- `npm audit`: 0 vulnerabilidades conocidas.
- `composer audit --locked`: 0 avisos conocidos.
- `peopleos:readiness --profile=application`: aprobado.
- Caché de rutas y vistas: aprobada.

## Gates que no pueden autodeclararse

El perfil estricto de producción permanece bloqueado en el entorno local porque no usa `APP_ENV=production`, Redis distribuido, S3 privado, HTTPS ni cookies `Secure`. Es el comportamiento correcto: evita confundir una máquina de desarrollo con infraestructura enterprise.

Antes de comprometer un SLA o afirmar cumplimiento se requiere:

1. desplegar staging equivalente a producción con PostgreSQL/Redis/object storage administrados;
2. ejecutar restauración y medir RPO/RTO;
3. instrumentar y observar SLO por al menos 30 días;
4. realizar carga, soak y pruebas de conmutación;
5. obtener pentest independiente y cerrar/retestear hallazgos;
6. completar auditoría WCAG 2.2 AA y el marco regulatorio/certificación aplicable;
7. acordar y firmar el SLA comercial.

Ninguno de esos resultados debe presentarse como logrado hasta contar con evidencia emitida desde la infraestructura definitiva o por un tercero competente.
