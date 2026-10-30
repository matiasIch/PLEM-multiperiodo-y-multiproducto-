# Arquitectura técnica y UI — FARMAFÉ

## 1. Arquitectura objetivo

PHP nativo compatible con la versión seleccionada y documentada, MVC ligero, servicios de negocio y repositorios PDO. Evitar una arquitectura excesivamente sofisticada para el prototipo, pero mantener separación clara entre HTTP, dominio, persistencia y vistas.

### Estructura de carpetas propuesta

```text
Proyecto_Grado/
├── Bibliografia/                     # Fuente original, solo lectura por el agente
├── Datos/                            # Fuente original, solo lectura por el agente
├── Instrumentos/                     # Fuente original, solo lectura por el agente
├── Proyecto de Grado Versiones/      # Fuente original, solo lectura por el agente
└── Software/
    ├── app/
    │   ├── Controllers/
    │   ├── Middleware/
    │   ├── Models/
    │   ├── Repositories/
    │   ├── Services/
    │   │   ├── Import/
    │   │   ├── Inventory/
    │   │   ├── Planning/
    │   │   ├── Plem/
    │   │   ├── Evaluation/
    │   │   └── Reports/
    │   ├── Validation/
    │   ├── Core/
    │   └── Views/
    │       ├── layouts/
    │       ├── components/
    │       └── pages/
    ├── bootstrap/
    ├── config/
    ├── database/
    │   ├── sql/                      # SQL importable en phpMyAdmin
    │   ├── migrations/
    │   └── seeds/                    # Solo referencias no sensibles/pruebas etiquetadas
    ├── docs/
    │   ├── design/
    │   ├── decisions/
    │   ├── tests/
    │   └── CONTINUIDAD_IA.md
    ├── public/
    │   ├── index.php
    │   └── assets/
    │       ├── css/app.css           # CSS Tailwind compilado localmente
    │       ├── js/app.js
    │       ├── js/imports.js
    │       └── js/planning.js
    ├── resources/
    │   ├── css/app.css               # Entrada Tailwind
    │   └── js/
    ├── routes/web.php
    ├── storage/
    │   ├── imports/                  # Archivos privados, fuera de public
    │   ├── temp/
    │   ├── logs/
    │   └── backups/
    ├── tests/
    │   ├── Unit/
    │   ├── Integration/
    │   ├── Import/
    │   └── Plem/
    ├── .env.example
    ├── .gitignore
    └── README.md
```

Si ya existe estructura equivalente, Antigravity debe reutilizarla o documentar la migración; no crear carpetas duplicadas solo para hacer coincidir esta lista. Los originales del proyecto son de solo lectura.

## 2. Convención de archivos

- `.php`: controladores, servicios, repositorios, modelos, rutas y vistas de servidor.
- `resources/css/app.css`: entrada del compilador Tailwind; `public/assets/css/app.css` es la compilación local para ejecución.
- `resources/js/*.js`: interacción de importación, tablas, filtros, modal de confirmación y planificación; `public/assets/js/*.js` son los activos servidos.
- `.sql`: esquema inicial completo y migraciones versionadas.
- `.md`: documentación, decisiones y continuidad de IA.
- `.env`: secretos locales excluidos de backups públicos/versión; `.env.example` solo contiene valores vacíos/de ejemplo.

No usar Tailwind CDN ni depender de internet en tiempo de ejecución. Node/npm no estaban disponibles en el diagnóstico; el agente debe comprobar herramientas otra vez y, si siguen ausentes, usar el CLI standalone oficial compatible con el sistema o un CSS ya compilado y documentado. No declarar Tailwind “compilado” si solo se usa un CDN.

## 3. Identidad visual azul y amarillo

Paleta propuesta, sujeta a ajuste con el logotipo oficial si está disponible:

- Azul institucional oscuro: `#12315B` (navegación, encabezados y fondos de marca).
- Azul primario: `#1D4ED8` (acciones principales y enlaces).
- Amarillo de marca: `#F2C94C` (acentos, iconografía, indicadores no críticos y foco visual).
- Fondo: `#F7F9FC`.
- Superficie: `#FFFFFF`.
- Texto principal: `#172033`.
- Texto secundario: `#53657D`.
- Bordes: `#D8E0EA`.
- Estados funcionales: éxito/advertencia/error con contraste y texto explícito; no usar el color como único medio para transmitir estado.

Reglas: amarillo como acento, no texto pequeño sobre blanco; azul oscuro para contraste; formularios consistentes; tablas legibles con filtros/paginación; layouts responsive; focus visible; labels asociados; navegación por teclado; modales solo para confirmar acciones o presentar fallos que necesiten atención. Los mensajes deben ser respetuosos y específicos, nunca culpar al usuario automáticamente.

No emojis en UI, íconos decorativos basados en emojis ni comentarios de código. Preferir iconos SVG locales/librería local validada o elementos CSS sencillos.

## 4. Páginas y navegación

### Zona pública
- `/`: landing page comercial/académica de la aplicación. No mostrar etapas de programación, versiones de PHP, estados de migración, carpetas o prompts.
- `/login`: autenticación.
- Página de error 403, 404 y 500 sin información sensible.

### Zona privada
- `/app`: dashboard con KPI consultados realmente y su periodo/naturaleza.
- `/app/importaciones`: lista, carga, mapeo, preview, validación y confirmación.
- `/app/medicamentos`, `/app/proveedores`, `/app/clientes`, `/app/ubicaciones`.
- `/app/compras`, `/app/recepciones`, `/app/lotes`, `/app/inventario`.
- `/app/pedidos`, `/app/demanda`.
- `/app/planificacion/escenarios`, `/app/planificacion/ejecutar`, `/app/planificacion/ejecuciones/{id}`.
- `/app/evaluacion/comparaciones`, `/app/reportes`.
- `/app/administracion/usuarios`, `/app/administracion/auditoria`.

Las rutas exactas pueden adaptarse, pero cada ruta visible debe existir, autorizar por rol, tener vista real y pruebas. No dejar enlaces “próximamente” fingiendo que son funcionales.

## 5. Seguridad y calidad PHP

- PDO y consultas preparadas siempre; nunca interpolar entradas de usuario en SQL.
- Escape contextual de salida HTML; protección CSRF en operaciones que cambian estado; validación en servidor.
- `password_hash()` y `password_verify()`; no semillas con contraseñas comunes. Primer administrador vía proceso de configuración local.
- Cookies de sesión HttpOnly, SameSite; Secure cuando se use HTTPS; regenerar ID al autenticar; expiración e invalidación en logout.
- Autorización en cada controlador/servicio; validar roles en el servidor.
- Logs en `storage/logs`, fuera de `public`, sin passwords, tokens ni datos sensibles.
- Límites de carga; validar extensión, MIME, tamaño y formato real de archivo. Bloquear ejecución de archivos subidos.
- Errores públicos con texto claro y un código de incidencia; stack trace solo en log privado en entorno de desarrollo.
- Transacciones para confirmar importación y registrar movimiento; idempotencia para evitar duplicar al reintentar.
- No ejecutar fórmulas/macros de Excel; solo leer valores.
- Configurar errores detallados solo en entorno local de desarrollo.
- Versiones PHP/MariaDB deben registrarse y verificarse contra avisos oficiales. No sustituir DLL ni actualizar XAMPP sin autorización y backup.

## 6. Solución del motor PLEM

PHP conserva orquestación, validación, snapshots y salida. Un `PlemAdapterInterface` desacopla el servicio de negocio de un solver MIP real como HiGHS, CBC o GLPK, sujeto a comprobar compatibilidad del ejecutable local.

- El solver se invoca con argumentos controlados y archivos temporales privados LP/MPS; no concatenar comandos con entradas del usuario.
- Registrar solver y versión, estado de salida, tiempo, parámetros, modelo exportado privado (si corresponde), resultado y diagnósticos.
- Si solver no instalado/no compatible, mostrar que la optimización no está disponible; nunca devolver resultados calculados a mano como si fueran salida PLEM.
- El motor debe ejecutar las dos fases lexicográficas y probarlas con casos manualmente verificables.

## 7. Tailwind y JavaScript

- Design tokens configurados en Tailwind para la paleta azul/amarillo.
- Componentes reutilizables: botón primario/secundario, badges de naturaleza del dato, campos, tabla, paginación, toast/mensaje, modal de confirmación, panel KPI, barra lateral y breadcrumb.
- JS sin lógica de negocio de confianza: la validación crítica y permisos se aplican en PHP.
- Mensajes de importación y PLEM muestran progreso, estado, detalle del error y siguiente acción.
- Accesibilidad: labels, `aria-live` para resultados de cargas, foco del modal, estados no solo por color.
- Ningún enlace externo de CDN en la versión final; activos servidos desde `public/assets`.
