# Auditoría integral de PeopleOS

> **Línea base histórica.** Este informe conserva los hallazgos tal como fueron reproducidos antes de la remediación. No describe el estado actual del código. El cierre, las pruebas posteriores y los gates externos pendientes están en `docs/REPORTE_REMEDIACION_2026-07-18.md`.

**Fecha:** 18 de julio de 2026

**Alcance:** funcionalidad, seguridad, madurez de producto, diseño visual, accesibilidad, calidad de código y preparación para venta enterprise.
**Método:** PHPUnit, Playwright Chromium en modo headless, compilación de vistas y frontend, auditoría de dependencias, revisión estática de rutas, controladores, modelos, middleware, documentación e infraestructura.

## Veredicto ejecutivo

PeopleOS ya es una **buena base de producto y un prototipo avanzado para pilotos controlados**, pero **todavía no debe venderse como HRIS enterprise terminado ni con un SLA exigente**.

La arquitectura tiene decisiones valiosas: aislamiento multiempresa, guards separados, MFA TOTP, RBAC, sesiones no persistentes, entregables privados, Control Center independiente, soporte temporal aprobado y auditoría de plataforma encadenada. El diseño principal también tiene una identidad visual propia y funciona bien en escritorio y móvil.

Sin embargo, la auditoría reprodujo tres fallos funcionales directos y un fallo de seguridad de ciclo de vida:

1. Evaluaciones devuelve HTTP 500 para administrador y empleado cuando no existen evaluaciones.
2. Crear un token API con fecha de vencimiento devuelve HTTP 500.
3. Un empleado marcado como inactivo conserva acceso con una sesión que ya estaba abierta.
4. Talento+ contiene nueve controles sin etiqueta accesible y el sistema usa tipografía demasiado pequeña en muchas superficies.

Además, varias capacidades presentadas como “enterprise” son todavía registros o prototipos superficiales: los conectores del marketplace no integran ningún sistema, el plan y el límite de puestos no se hacen cumplir, “360°” no implementa una evaluación 360 real, y el offboarding usa un checklist que no revoca técnicamente las sesiones.

### Recomendación comercial

- **Ahora:** demostraciones, discovery con clientes y pilotos no críticos, después de corregir los bloqueos P0.
- **No todavía:** producción para una gran corporación, datos sensibles de nómina a escala, promesa de 99,9 %, certificación, cumplimiento formal o venta como suite global completa.
- **Siguiente gate razonable:** corregir P0, cubrir todas las mutaciones críticas, desplegar un entorno de staging semejante a producción, ejecutar carga, DAST, pentest externo, restauración de backup y revisión WCAG 2.2 AA.

## Evidencia de pruebas

| Comprobación | Resultado | Evidencia |
|---|---:|---|
| Suite backend original | 57 aprobadas | 258 aserciones antes de añadir la regresión de auditoría |
| Suite backend con regresión nueva | 57 aprobadas, 1 fallida | La sesión del empleado inactivo continúa autorizada |
| Suite Playwright existente, headless | 12 aprobadas | Flujos de personas, ausencias, entregables, documentos, Control Center, sesión y responsive |
| Suite Playwright de auditoría ampliada | 3 aprobadas, 4 fallidas | 7 escenarios; 3 causas funcionales/visuales únicas |
| Barrido de navegación autenticada | 42 visitas | 24 admin, 13 empleado y 5 Control Center |
| Inventario de rutas | 136 rutas | 56 superficies GET y 76 mutaciones |
| Build frontend | Aprobado | Vite completó la compilación |
| Dependencias JavaScript | Aprobado | `npm audit`: 0 vulnerabilidades conocidas |
| Dependencias PHP | Aprobado | `composer audit --locked`: 0 avisos conocidos |
| Validación Composer | Aprobada | Lock y manifiesto válidos |
| Compilación/lint de vistas Blade | Fallida | Una vista compilada contiene error de sintaxis |
| Estilo PHP con Pint | Fallido | `LocalDemoSeeder.php` y `routes/web.php` no pasan el gate |
| Responsive representativo | Aprobado | Sin overflow horizontal en las cuatro superficies medidas |
| Controles con nombre accesible | Fallido | 9 controles sin etiqueta formal en Talento+ |
| Kubernetes/HA real | No demostrado | Existe manifiesto de referencia, no un entorno validado |
| SAST local | No ejecutado | Semgrep no está instalado localmente; existe workflow de CI |
| Pentest/DAST independiente | No demostrado | La propia documentación lo deja como requisito pendiente |

La suite E2E anterior podía aparecer completamente verde porque el flujo serial creaba un ciclo antes de abrir evaluaciones. La condición vacía no estaba cubierta. Las pruebas backend validaban el controlador y los datos, pero no compilaban la vista. Este es un ejemplo claro de por qué una suite verde no equivale a ausencia de fallos.

## Fallos reproducibles

### POS-001 — P0 — Evaluaciones devuelve HTTP 500

**Superficies:** `/evaluaciones` como administrador y como empleado.

**Reproducción:** base limpia, iniciar sesión y abrir Evaluaciones antes de crear un ciclo.

**Resultado esperado:** estado vacío visible.

**Resultado real:** HTTP 500.

La vista [resources/views/reviews/index.blade.php](../resources/views/reviews/index.blade.php) compila a PHP con un `endforeach` inesperado. `php -l` sobre las vistas compiladas reproduce el mismo error. El bloque problemático mezcla `@forelse`, `@if`, `@elseif` y `@endif` en una sola línea.

**Impacto:** módulo completo inaccesible en el primer uso; afecta a ambos perfiles.

**Solución recomendada:** reescribir la vista en bloques multilínea, corregir el anidamiento y añadir pruebas de render para colección vacía, borrador, publicada y reconocida. Añadir lint de todas las vistas compiladas al CI.

### POS-002 — P0 — La desactivación no revoca una sesión existente

**Reproducción automatizada:** autenticar un empleado, cambiar `employment_status` a `inactive` y volver a solicitar `/home`.

**Resultado esperado:** invalidación de sesión y redirect al login.

**Resultado real:** HTTP 200; la sesión continúa accediendo.

La regresión está en [tests/Feature/AuditEnterpriseRegressionTest.php](../tests/Feature/AuditEnterpriseRegressionTest.php). El middleware [EnsureAnyAuthenticated.php](../app/Http/Middleware/EnsureAnyAuthenticated.php) solo comprueba que exista un usuario autenticado; no vuelve a validar el estado laboral. [OffboardingController.php](../app/Http/Controllers/OffboardingController.php) cambia el estado a inactivo, pero no invalida sesiones activas.

**Impacto:** una persona retirada o desactivada puede mantener acceso hasta que expire su sesión. Para empleados el límite absoluto configurado es de 12 horas.

**Solución recomendada:** validar el estado del actor en cada petición protegida, invalidar todas las sesiones del usuario al desactivar/offboardear, rotar una versión de autenticación y revocar tokens/integraciones asociados. Añadir la misma garantía para administradores.

### POS-003 — P0 — Token API con vencimiento devuelve HTTP 500

**Superficie:** `/admin/plataforma`, formulario “Crear token de API”.

**Reproducción:** nombre, scope válido y `expires_in_days=30`.

**Resultado esperado:** token creado y mostrado una sola vez.

**Resultado real:** HTTP 500.

En [PlatformController.php](../app/Http/Controllers/PlatformController.php), el valor validado llega como string y se pasa directamente a `now()->addDays()`. Carbon 3 exige `int|float`.

**Impacto:** no se pueden emitir credenciales con vencimiento, precisamente la variante segura recomendada. Los tokens sin vencimiento sí funcionaron en el recorrido alternativo.

**Solución recomendada:** casteo explícito a entero, prueba de límites 1/365, prueba de expiración efectiva y política que prohíba tokens sin vencimiento en planes enterprise.

### POS-004 — P1 — Talento+ no cumple accesibilidad de formularios

La inspección headless encontró **9 controles interactivos sin etiqueta formal** en Talento+. Los placeholders no sustituyen una etiqueta persistente. La fuente está en [resources/views/expansion/index.blade.php](../resources/views/expansion/index.blade.php).

**Impacto:** lectores de pantalla, dictado, navegación asistida y usuarios con dificultades cognitivas reciben una experiencia incompleta; también dificulta soporte y automatización estable.

**Solución recomendada:** añadir `label for/id` o `aria-label` apropiado, mensajes de error asociados con `aria-describedby`, agrupaciones y nombres visibles persistentes.

### POS-005 — P1 — Tipografía excesivamente pequeña

La medición de cuatro superficies arrojó:

| Superficie | Textos visibles menores de 12 px | Menores de 14 px | Targets menores de 24 px |
|---|---:|---:|---:|
| Dashboard admin desktop | 30 | 43 | 2 |
| Talento+ desktop | 15 | 20 | 1 |
| Dashboard admin móvil | 29 | 40 | 0 |
| Control Center desktop | 43 | 43 | 3 |

[resources/css/app.css](../resources/css/app.css) usa repetidamente 8, 9, 10 y 11 px para contenido operativo. La jerarquía es atractiva, pero la lectura sostenida y la accesibilidad sufren.

**Solución recomendada:** base mínima de 14 px para contenido secundario y 16 px para lectura normal; reservar 11–12 px para metadatos excepcionales; elevar targets a 24×24 como mínimo y preferiblemente 44×44 para acciones principales.

### POS-006 — P1 — El gate de estilo del CI no está verde

`php vendor/bin/pint --test` falla en:

- `database/seeders/LocalDemoSeeder.php`
- `routes/web.php`

**Impacto:** el workflow de calidad configurado en GitHub fallaría antes de declarar una release.

**Solución recomendada:** ejecutar Pint, revisar el diff y hacer obligatorio el mismo gate local/pre-push.

## Capacidades que funcionan y tienen buena base

### Identidad, tenancy y sesión

- Separación de empleados, administradores de empresa y usuarios internos de plataforma.
- Multiempresa con contexto y global scopes, más pruebas de fuga cruzada.
- Sesiones sin “recordarme”, cookie `HttpOnly`, expiración al cerrar navegador, ventana de inactividad y límite absoluto.
- MFA TOTP, códigos de recuperación de un solo uso y secreto cifrado.
- OIDC Authorization Code + PKCE con validación de issuer, audience, nonce y firma.
- Control Center con MFA obligatorio.

**Madurez:** buena fundación, no completa. Falta revocación inmediata para empleados, recuperación de cuenta, políticas de acceso condicional, SAML y controles enterprise de identidad.

### Directorio y operación de personas

- Alta y edición de empleado, soft delete, departamentos, manager, estado laboral e importación/exportación CSV.
- Importación transaccional, validación por tenant e invitaciones de uso único.
- Panel operativo responsive.

**Madurez:** apta para piloto de HRIS básico. Falta historial de puesto/posición, movimientos con vigencia, organigrama real, campos configurables, entidades legales por empleado, aprobaciones de cambios y autoservicio de datos.

### Ausencias y aprobaciones

- Solicitud, detección de solapamiento, aprobación/rechazo motivado, notificaciones, saldos y festivos.
- Bandeja unificada para RR. HH. y manager.

**Madurez:** flujo básico funcional. Falta motor de devengo, medios días/horas, calendarios regionales, aprobaciones multinivel/delegadas, SLA, bloqueo por saldo/política y edición/cancelación.

La ruta para crear políticas y festivos existe, pero [leave/settings.blade.php](../resources/views/leave/settings.blade.php) no ofrece formularios para ambas acciones. Solo permite ajustar saldos. La funcionalidad está incompleta desde la interfaz.

### Onboarding y entregables

- Carga privada versionada, múltiples formatos, checksum, autorización empleado/manager/admin, aprobación/rechazo motivado y nueva versión.
- Bloqueo de entregas duplicadas pendientes.
- El escáner antivirus falla de forma cerrada cuando está habilitado.

**Madurez:** es uno de los módulos más sólidos del producto. Para enterprise faltan plantillas, dependencias, asignaciones automáticas, SLA/escalaciones, retención y almacenamiento de objetos con AV/DLP realmente desplegado.

### Documentos y firma

- Archivo privado, hash SHA-256, descarga autorizada, consentimiento, evidencia de IP/agente y firma ligada al hash.

**Madurez:** evidencia electrónica básica; no equivale a una plataforma de firma avanzada o cualificada. Faltan versiones, plantillas, sobres, múltiples firmantes, sellado de tiempo confiable, identidad reforzada, revocación, retención/legal hold e integración con un proveedor de firma.

La documentación afirma acceso del jefe directo a documentos, pero `EmployeeDocumentController::download()` solo autoriza administrador o propietario del documento. Debe corregirse el código o la promesa.

### Control Center

- Identidad interna independiente, RBAC por función, MFA obligatorio.
- Alta de empresa, entidad legal, dominio DNS, suscripción, invitación segura del primer propietario, estados y soporte temporal aprobado.
- Auditoría de plataforma encadenada e inmutable a nivel de modelo.

**Madurez:** diseño y arquitectura prometedores. Para operar un SaaS real faltan facturación, medición, aplicación de límites, flujos documentales de KYB, dual control para acciones críticas, reconciliación y automatización operativa.

## Funcionalidades que son prototipo o pueden inducir a error

### Marketplace de integraciones

“Agregar” un conector solo crea un registro `configured` con quién y cuándo lo pulsó. No se capturan credenciales, no hay OAuth, sincronización, mapeo, logs ni reconciliación.

**Decisión recomendada:** ocultarlo tras feature flag o presentarlo como “catálogo próximo” hasta implementar conectores reales. En su estado actual no debe contarse en una propuesta comercial.

### Scopes API sin API correspondiente

Se pueden crear scopes `employees:write` y `reports:read`, pero las rutas publicadas solo incluyen lectura de empleados y SCIM. Son scopes sin capacidad real.

**Decisión recomendada:** eliminar temporalmente esos scopes o implementar endpoints versionados, documentación OpenAPI, idempotencia, auditoría, paginación consistente y rate limits.

### Evaluación “360°”

El tipo `360` solo etiqueta un ciclo; crea la misma evaluación de un administrador para cada empleado. No existen participantes, nominaciones, feedback de pares, anonimato, plantillas ni consolidación.

**Decisión recomendada:** llamarlo “ciclo de evaluación” hasta construir una evaluación 360 real.

### Registro de cumplimiento

Un administrador puede marcar un control como `verified` y escribir evidencia libre. Esto es útil como checklist interno, pero no demuestra ISO 27001, SOC 2, GDPR, Habeas Data o WCAG.

**Decisión recomendada:** renombrarlo “registro interno de controles”, exigir propietario/revisor/evidencia/fecha/versionado y evitar cualquier lenguaje de certificación.

### Talento+ como una única pantalla

ATS, tiempo, LMS y compensación comparten una vista larga. Las operaciones simples persisten correctamente, pero la agrupación comunica amplitud sin profundidad y expone salarios junto a módulos menos sensibles.

**Decisión recomendada:** separar productos, permisos, navegación y modelos de datos. Compensación debe tener controles de acceso más finos, auditoría de lectura y cifrado de campos sensibles.

## Evaluación de seguridad

### Fortalezas observadas

- CSRF y regeneración de sesión.
- Throttling en logins y MFA.
- Cookies no persistentes y páginas autenticadas sin caché.
- CSP estricta, anti-framing, `nosniff`, Referrer Policy y Permissions Policy.
- Hash de tokens, secretos cifrados y entrega de credencial una sola vez.
- Archivos privados con nombres internos aleatorios y lista cerrada de formatos.
- Pruebas de aislamiento tenant, scopes, MFA, sesión, documentos y entregables.
- Auditoría de plataforma con cadena HMAC y bloqueo de update/delete en el modelo.
- Cero avisos conocidos en los dos gestores de dependencias al momento del análisis.

### Bloqueos y riesgos pendientes

1. **Revocación de acceso:** el fallo POS-002 debe resolverse para empleados, admins y SCIM.
2. **Auditoría tenant:** `AuditLog` no es append-only ni tiene hash encadenado; la garantía fuerte solo existe en plataforma.
3. **Antimalware:** `.env.example` deja `DELIVERABLES_ANTIVIRUS_ENABLED=false`; debe ser obligatorio en producción y cubrir también documentos.
4. **Compensación:** salario y variable se almacenan sin cifrado de campo; falta auditoría de lectura.
5. **Recuperación de cuenta:** no hay “olvidé mi contraseña”, recovery administrado, desbloqueo seguro ni flujo de cambio forzado.
6. **MFA:** se exige a administradores de empresas creadas desde Control Center, pero no existe una política empresarial configurable para empleados, grupos o riesgos.
7. **Administradores tenant:** no hay flujo normal para que el propietario invite, desactive y recertifique administradores/HR dentro de su empresa.
8. **API:** no hay rate limit explícito por token/tenant, OpenAPI, idempotency keys, rotación automatizada ni OAuth2.
9. **Webhooks/SSRF:** se comprueba DNS al configurar, pero hace falta resolver y validar de nuevo en la entrega, controlar redirects y aplicar egress filtering contra DNS rebinding.
10. **Pentest y aseguramiento:** no existe evidencia de pentest independiente, OWASP ASVS, DAST o threat model aprobado.
11. **Privacidad:** faltan retención ejecutable, legal hold, exportación del titular, eliminación gobernada, clasificación y registro de accesos a PII.

No es técnicamente responsable afirmar “sin ningún fallo de seguridad”. La conclusión válida es: **no se encontraron advisories conocidos en dependencias y existen buenos controles base, pero hay un fallo confirmado de revocación y varias garantías enterprise todavía no demostradas**.

## Madurez por módulo

| Módulo | Estado actual | Evaluación |
|---|---|---|
| Multiempresa | Scopes y pruebas de aislamiento | Buena base |
| Sesiones | Idle/absoluta/no persistente | Buena, con fallo de revocación |
| RBAC | Roles configurables para admins existentes | Parcial |
| MFA | TOTP y recovery codes | Parcial enterprise |
| SSO | OIDC + PKCE | Real, falta SAML/políticas |
| SCIM | Users GET/POST/PATCH | Parcial |
| Control Center | Lifecycle básico y soporte JIT | Prototipo avanzado |
| Directorio | CRUD/import/export | Vendible para piloto |
| Ausencias | Solicitud/aprobación/saldos | Básico funcional |
| Onboarding/entregables | Versionado y revisión | Módulo más fuerte |
| Documentos/firma | Firma electrónica básica | Parcial |
| Evaluaciones | Ciclo manager simple | Roto en estado vacío y superficial |
| Offboarding | Checklist fijo de cinco tareas | Incompleto y con riesgo de sesión |
| Reclutamiento | Vacante/candidato/etapa | Prototipo |
| Asistencia | Clock in/out web | Prototipo |
| Formación | Curso/asignación/completado | Prototipo |
| Compensación | Historial salarial simple | Prototipo sensible |
| Reportes | Métricas operativas fijas | Básico |
| API/webhooks | Lectura, SCIM y eventos firmados | Parcial |
| Marketplace | Marca conectores como configurados | No funcional |
| Cumplimiento | Checklist de evidencia | No demuestra cumplimiento |
| HA/DR/observabilidad | Documentación y manifiesto | Diseño, no evidencia operativa |

## Funcionalidades faltantes para competir con grandes suites

### P0 — antes de una producción empresarial

1. Corregir POS-001, POS-002 y POS-003 con regresiones.
2. Cubrir las 76 rutas de mutación con casos felices, validación, autorización, concurrencia y aislamiento.
3. Provisionamiento y ciclo de vida de administradores tenant.
4. Recuperación segura de cuenta, rotación y revocación de sesiones/tokens.
5. Aplicación real de planes, límites de puestos y estados de suscripción.
6. Antivirus/DLP obligatorio en todos los archivos y object storage privado.
7. Auditoría tenant inmutable y exportación a SIEM/WORM.
8. Accesibilidad WCAG 2.2 AA en flujos críticos.
9. Staging productivo, load test, métricas, tracing, alertas, restore drill y rollback probado.
10. Threat model, ASVS, SAST/DAST por release y pentest externo.
11. Controles ejecutables de privacidad: retención, legal hold, DSAR, eliminación y exportación.

### P1 — producto HRIS competitivo

- Organigrama y posiciones con historial/effective dating.
- Campos personalizados y workflows configurables.
- Ausencias por horas/medios días, accrual engine y aprobación multinivel.
- Onboarding/offboarding con plantillas, dependencias, automatización y SLA.
- Firma empresarial integrada.
- Performance real: competencias, check-ins, calibración, feedback 360 y planes de desarrollo.
- ATS con portal de empleo, CV, entrevistas, scorecards, ofertas, consentimiento y conversión a empleado.
- Tiempo/asistencia con turnos, correcciones, overtime, reglas y exportación a nómina.
- LMS con contenido, certificaciones, skills, learning paths y SCORM/xAPI si el mercado lo exige.
- Compensación con bandas, ciclos merit, presupuestos, equidad, aprobaciones y payroll.
- People analytics gobernado, report builder, exports programados y definiciones de métricas.
- Integraciones reales con nómina, ERP, Entra/Google, Slack/Teams y almacenamiento documental.

### P2 — diferenciación enterprise/global

- SAML 2.0, SCIM Groups completo, IdP-initiated SSO, JIT configurable y acceso condicional.
- Multiidioma real, monedas, zonas horarias y reglas por entidad/país.
- Residencia regional, BYOK/CMK, data masking y entornos de sandbox.
- API pública completa, OAuth2, portal developer, versionado y marketplace certificado.
- Multi-región, failover probado, status page, soporte 24/7 y SLA medido.
- Workforce planning, headcount budget, sucesión y escenarios.
- IA con revisión humana, evaluaciones, explicabilidad, privacidad y controles de sesgo.

## Evaluación del diseño visual

### Lo que está bien

- Identidad propia: verde oscuro, menta, tipografía y composición coherentes.
- Dashboard admin claro, jerarquía visual fuerte y CTAs comprensibles.
- Control Center visualmente diferenciado del tenant; comunica bien la frontera de seguridad.
- Responsive sin desbordamiento en 390×844.
- Navegación móvil usable.
- Animaciones sobrias y soporte de `prefers-reduced-motion`.
- Estados vacíos, tarjetas y métricas consistentes.

### Lo que impide llamarlo “top enterprise”

- Exceso de texto entre 8 y 11 px.
- Talento+ depende de placeholders y carece de etiquetas.
- Pantallas con datos escasos generan mucho espacio vacío y scroll innecesario.
- Mezcla de términos en inglés y español (`Learning`, `Pipeline`, estados técnicos).
- Módulos complejos se presentan como formularios simples en una sola vista.
- Faltan patrones enterprise de datos: columnas configurables, saved views, bulk actions, export contextual, drill-down, filtros persistentes y estados de carga/error.
- Compensación necesita una experiencia visual y de permisos separada.
- Debe ejecutarse una auditoría manual de teclado, lector de pantalla, zoom 200/400 %, contraste y mensajes de error.

**Conclusión visual:** el dashboard y el Control Center se ven modernos y distintivos; la base estética es vendible. El sistema completo aún no alcanza excelencia corporativa por accesibilidad, densidad tipográfica y profundidad desigual entre módulos.

## Plan recomendado de corrección

### Sprint 0 — bloqueo de venta

- POS-001, POS-002, POS-003.
- Lint de Blade y Pint verdes.
- Etiquetas de formularios críticos.
- Revocación integral de sesión/token al desactivar.
- Test de regresión para cada defecto.

### Fase 1 — piloto serio

- Admin lifecycle tenant, recovery, MFA policy.
- Seat limits y subscription enforcement.
- Quitar/feature-flag de conectores no reales y claims 360/compliance.
- Completar UI de políticas/festivos.
- Subir tipografía y corregir targets.
- Cobertura E2E de mutaciones críticas y matriz de permisos.

### Fase 2 — enterprise

- Infraestructura desplegada y observada.
- Load/soak/chaos/restore tests.
- Auditoría inmutable tenant, SIEM y retención.
- Pentest externo, WCAG manual, privacy readiness.
- Profundización de módulos seleccionados según mercado objetivo.

## Archivos de auditoría añadidos

- [specs/full-product-audit.plan.md](../specs/full-product-audit.plan.md)
- [tests/e2e/audit](../tests/e2e/audit)
- [tests/Feature/AuditEnterpriseRegressionTest.php](../tests/Feature/AuditEnterpriseRegressionTest.php)

No se corrigieron silenciosamente los fallos del producto durante esta auditoría. Las pruebas que fallan quedan intencionalmente como evidencia reproducible y como gates de regresión para la siguiente fase.
