# Política de seguridad

## Versiones soportadas

Mientras el producto se encuentra en fase de fundación, solo la rama principal más reciente recibe correcciones de seguridad. No se recomienda operar versiones antiguas del lock de dependencias.

## Reporte responsable

No publiques vulnerabilidades en un issue abierto. Utiliza el formulario privado de GitHub en **Security → Advisories → Report a vulnerability** del repositorio o contacta de forma privada al propietario del proyecto.

Incluye:

- componente y versión afectada;
- pasos mínimos para reproducir;
- impacto observado o potencial;
- prueba de concepto sin datos personales reales;
- recomendación, si la tienes.

No accedas, modifiques ni descargues información de terceros durante la investigación.

## Compromiso de respuesta

Para una operación empresarial se deben formalizar responsables y SLA. La meta recomendada es:

- acuse de recibo: 2 días hábiles;
- clasificación inicial: 5 días hábiles;
- criticidad alta: mitigación prioritaria y comunicación coordinada;
- publicación: después de que exista una corrección desplegable.

## Controles para contribuciones

Todo cambio debe mantener en verde:

```powershell
composer audit --locked
npm audit
php artisan test
php vendor/bin/pint --test
npm run build
```

Los secretos, archivos `.env`, bases locales y credenciales nunca deben agregarse al repositorio.

## Política de sesiones

- Las cookies de sesión expiran al cerrar el navegador y nunca se emiten cookies de autenticación persistente.
- RR. HH. tiene 15 minutos de inactividad y 8 horas de duración absoluta.
- Los empleados tienen 30 minutos de inactividad y 12 horas de duración absoluta.
- Cada actividad válida renueva solamente la ventana de inactividad, no la duración absoluta.
- Al expirar se cierran ambos guards, se invalida la sesión completa y se regenera el token CSRF.
- Las respuestas autenticadas se entregan con `Cache-Control: no-store` para evitar recuperar información mediante el botón Atrás.

## Archivos entregables

Los entregables se almacenan en un disco privado y solo se descargan mediante un controlador autenticado que valida la relación entre empleado, jefe directo y RR. HH. El sistema limita formatos, cantidad y tamaño; genera nombres internos aleatorios y conserva checksum SHA-256.

En producción debe instalarse ClamAV y configurarse:

```env
DELIVERABLES_ANTIVIRUS_ENABLED=true
DELIVERABLES_ANTIVIRUS_BINARY=clamscan
```

Cuando el antivirus está activo, una detección, indisponibilidad o error del escáner bloquea la carga.
