# PeopleOS Control Center

El Control Center es el plano de control interno de PeopleOS. No comparte autenticación ni autorización con los administradores o empleados de una empresa.

## Fronteras de identidad

- `platform_users`: personal interno de PeopleOS, sin `organization_id`.
- `admins`: propietarios y administradores pertenecientes a una empresa.
- `users`: empleados pertenecientes a una empresa.
- Guard independiente `platform`, MFA obligatorio y sesiones sin persistencia.
- Un usuario de plataforma no se convierte en administrador de un cliente.

## Alta de una empresa

1. Un operador autorizado crea el workspace en estado `onboarding`.
2. Se registra la entidad legal principal y su identificación fiscal.
3. Se crea la suscripción, plan y capacidad contratada.
4. PeopleOS envía al propietario una invitación de un solo uso con vigencia de 72 horas.
5. El propietario define su propia contraseña; PeopleOS nunca la genera ni la conoce.
6. Seguridad verifica una entidad legal y al menos un dominio mediante DNS TXT.
7. La empresa puede activarse únicamente cuando entidad, dominio, propietario y suscripción están listos.

Estados permitidos:

```text
onboarding -> active -> suspended -> active
                         |
                         +-> offboarded
```

La suspensión bloquea el acceso del workspace sin eliminar datos. `offboarded` es terminal desde la interfaz.

## Acceso temporal de soporte

El especialista registra ticket, motivo, alcances y duración. La solicitud queda pendiente hasta que un administrador con `security.manage` la aprueba desde el portal de la empresa.

La sesión aprobada:

- pertenece exclusivamente al especialista designado;
- expira entre 15 minutos y 4 horas después de la aprobación;
- solo muestra metadatos incluidos en los alcances;
- no autentica al especialista como administrador del cliente;
- excluye expedientes, documentos, compensación e información personal;
- queda registrada en la auditoría del cliente y de plataforma;
- puede revocarse inmediatamente desde cualquiera de los dos lados.

## Auditoría

`platform_audit_logs` es un ledger append-only. Cada entrada incorpora el hash HMAC-SHA256 de su contenido y el hash de la entrada anterior. El modelo bloquea modificaciones y eliminaciones, y el Control Center verifica la cadena en cada consulta.

En producción, esta evidencia debe exportarse además a almacenamiento WORM o a un SIEM externo. El hash encadenado detecta alteraciones, pero no reemplaza controles de infraestructura, retención inmutable ni separación de funciones en la base de datos.

## Bootstrap seguro

Configura una única cuenta inicial:

```dotenv
INITIAL_PLATFORM_OWNER_NAME="PeopleOS Platform Owner"
INITIAL_PLATFORM_OWNER_EMAIL=owner@peopleos.example
INITIAL_PLATFORM_OWNER_PASSWORD="use-a-secret-manager"
```

Después ejecuta:

```bash
php artisan db:seed --class=PlatformUserSeeder
```

Elimina la contraseña del entorno tras el bootstrap y activa MFA en el primer acceso. Las cuentas posteriores se crean exclusivamente mediante invitaciones de 24 horas.

## Pruebas

```bash
php artisan test
npm run build
npx playwright test tests/e2e/control-center.spec.ts
```

La suite cubre aislamiento de guards, MFA, provisionamiento, invitación de propietario, activation gates, RBAC interno, soporte JIT, cierre de navegador, responsive y detección de alteraciones de auditoría.
