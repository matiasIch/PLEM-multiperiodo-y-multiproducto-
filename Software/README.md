# FARMAFÉ — Sistema de Planificación Logística y Optimización PLEM

Plataforma web de soporte a decisiones para la planificación logística de compras, gestión de inventario por lotes (FEFO) y atención de demanda en la distribuidora farmacéutica **FARMAFÉ** (La Paz, Bolivia). Integra un modelo matemático de **Programación Lineal Entera Mixta (PLEM/MILP)** multiproducto y multiperiodo (6 meses) con optimización lexicográfica en dos etapas:
1. **Etapa 1:** Maximizar el nivel de servicio ($S^*$) sobre la demanda elegible.
2. **Etapa 2:** Minimizar el costo logístico total preservando el nivel de servicio óptimo alcanzado ($S \ge S^* - \epsilon$).

---

## 🔑 Credenciales de Acceso al Sistema

Una vez iniciado el servidor, ingresa a la siguiente URL en el navegador:

👉 **[http://localhost:8012/farmafe](http://localhost:8012/farmafe)** *(redirige a `/farmafe/login`)*

| Campo | Valor |
|---|---|
| **URL de Acceso** | `http://localhost:8012/farmafe` |
| **Usuario** | `admin` *(o también `admin_farmafe`)* |
| **Contraseña** | `Farmafe2026*` |
| **Rol asignado** | Administrador del Sistema (`ADMIN`) |

> **Nota:** La pantalla de login no requiere conexión a Internet ni CDNs externos; todos los estilos CSS y scripts se procesan localmente.

---

## 📋 Requisitos del Entorno

1. **XAMPP para Windows:**
   - **Apache 2.4+** con módulo `mod_rewrite` habilitado (por defecto configurado en el puerto `8012` o `80`).
   - **PHP 8.1 o superior** con extensiones activas: `pdo_mysql`, `mbstring`, `json`, `curl`.
   - **MariaDB 10.4 o MySQL 8.0+** en el puerto `3306`.
2. **Python 3.10 o superior** (para el solver PLEM):
   - Librerías requeridas: `pulp`, `scipy`, `highspy`, `openpyxl`.

---

## 🚀 Guía de Instalación y Puesta en Marcha

### Paso 1: Configurar el Enlace en Apache (XAMPP)

Para que el servidor web sirva la aplicación desde la carpeta `public`, crea una unión de directorios (*directory junction*) en `C:\xampp\htdocs`:

Abre una consola **PowerShell** o **Símbolo del sistema** como Administrador y ejecuta:

```powershell
cmd /c mklink /J "C:\xampp\htdocs\farmafe" "C:\Users\paola\Desktop\Proyecto_Grado\Software\public"
```

*(Si la ruta del proyecto cambia, ajusta la segunda ruta hacia la carpeta `Software\public` correspondiente).*

### Paso 2: Crear y Poblar la Base de Datos (`farmafe_app`)

La base de datos del sistema se denomina **`farmafe_app`**. No modifica ni destruye bases anteriores.

Desde la consola de MariaDB/MySQL en XAMPP, ejecuta los scripts SQL en el siguiente orden estricto:

```powershell
cd C:\xampp\mysql\bin

# 1. Crear base de datos y tablas del esquema
.\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS farmafe_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
.\mysql.exe -u root farmafe_app < "C:\Users\paola\Desktop\Proyecto_Grado\Software\database\sql\00_schema_fresh.sql"

# 2. Cargar datos iniciales (roles, configuración y costos base)
.\mysql.exe -u root farmafe_app < "C:\Users\paola\Desktop\Proyecto_Grado\Software\database\sql\01_initial_data.sql"

# 3. Cargar datos consolidados FARMAFÉ (195 medicamentos, 3 proveedores, 848 lotes, 448 registros de demanda)
.\mysql.exe -u root farmafe_app < "C:\Users\paola\Desktop\Proyecto_Grado\Software\database\seeds\02_farmafe_consolidado.sql"
```

### Paso 3: Configuración Local (`config.local.php`)

Verifica que el archivo `Software/config/config.local.php` exista con los parámetros de tu entorno local:

```php
<?php
// Software/config/config.local.php

return [
    'app' => [
        'url'   => 'http://localhost:8012/farmafe',
        'debug' => true,
        'env'   => 'development',
    ],
    'database' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'farmafe_app',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'solver' => [
        'python_bin'     => 'C:\\Program Files\\Blender Foundation\\Blender 5.0\\5.0\\python\\bin\\python.exe', // O la ruta a tu python.exe
        'script_path'    => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'solver' . DIRECTORY_SEPARATOR . 'plem_solver.py',
        'timeout_seconds'=> 120,
    ],
];
```

### Paso 4: Dependencias del Solver PLEM (Python)

Instala los paquetes matemáticos requeridos para el solver PuLP / HiGHS:

```powershell
# Usando tu ejecutable de Python:
python -m pip install pulp scipy highspy openpyxl
```

### Paso 5: Iniciar los Servicios en XAMPP

1. Abre el **XAMPP Control Panel**.
2. Inicia los módulos **Apache** y **MySQL**.
3. Confirma que Apache esté escuchando en el puerto configurado (`8012` o `80`).

---

## 🧪 Verificación y Pruebas del Sistema

Puedes verificar que tanto el motor matemático como las rutas del aplicativo web respondan correctamente ejecutando las suites de prueba:

### Pruebas Unitarias del Motor Matemático (Python / PuLP):
```powershell
cd C:\Users\paola\Desktop\Proyecto_Grado\Software
python tests\Plem\test_plem_suite.py
```
*Debe reportar 9 pruebas matemáticas aprobadas (balance de masa, vencimientos, horizonte multiperiodo, lexicográfico de dos etapas, restricciones presupuestarias y de órdenes mínimas).*

### Pruebas de Rutas y Controlador Web (PHP):
```powershell
C:\xampp\php\php.exe tests\Unit\WebRoutesTest.php
```
*Debe reportar 11/11 pruebas aprobadas.*

---

## 🖥️ Módulos del Sistema Disponibles

Una vez dentro de la plataforma (`/farmafe/app`), tendrás acceso a la barra lateral con los siguientes módulos operativos:

1. **Panel de Control:**
   - Resumen de KPIs en tiempo real (195 Medicamentos, 3 Proveedores, 848 Lotes físicos, Alertas de vencimiento).
   - Resumen del último plan de optimización ejecutado con el nivel de servicio alcanzado ($S^*$) y costo logístico total.
2. **Datos Maestros:**
   - `Medicamentos`: Catálogo completo con forma farmacéutica, concentración y categoría.
   - `Proveedores y Oferta`: Matriz producto-proveedor con costos unitarios, tiempos de entrega (*lead times*) y cantidades mínimas de pedido (MOQ).
   - `Clientes`: Cartera de clientes distribuidores/farmacias con identificación anonimizada.
   - `Zonas de Almacén`: Ubicaciones físicas clasificadas por condiciones de almacenamiento.
3. **Abastecimiento y Stock:**
   - `Órdenes de Compra`: Registro de órdenes emitidas y opción para fijar/comprometer pedidos en reoptimizaciones.
   - `Recepciones`: Verificación física de ingresos contra pedidos de compra.
   - `Gestión de Lotes (FEFO)`: Control de existencias por lote, fecha de vencimiento y semáforo de riesgo de caducidad.
   - `Movimientos de Stock`: Kardex auditable de entradas y salidas.
4. **Demanda y Pedidos:**
   - `Pedidos de Clientes`: Registro histórico de pedidos.
   - `Demanda Periódica`: Proyección y demanda histórica mensual agregada por medicamento.
5. **Optimización PLEM (Investigación de Operaciones):**
   - `Configurar y Ejecutar PLEM`: Parámetros de tolerancia lexicográfica ($\epsilon$), presupuesto mensual, selección de horizonte (6 meses) y ejecución del solver en tiempo real.
   - `Versiones del Plan (6M)`: Historial inmutable de planes generados con hash criptográfico SHA-256 de auditoría.
   - `Exportación de Resultados`: Descarga del plan de compras y proyecciones de inventario en formato CSV.
   - `Comparación AS-IS / TO-BE`: Análisis de mejora estimada frente a la situación inicial.
6. **Administración y Auditoría:**
   - `Parámetros y Costos`: Configuración de tasas de almacenamiento, costos de emisión de pedido y penalizaciones por merma.
   - `Usuarios y Roles`: Control de cuentas y permisos (`ADMIN`, `GERENCIA`, `VENTAS`, `ALMACEN`).
   - `Auditoría del Sistema`: Registro inmutable de eventos, inicios de sesión y ejecuciones del optimizador.

---

## 🏷️ Política de Integridad y Trazabilidad de Datos

El sistema cumple estrictamente con el principio de veracidad de datos:
* Todo registro visualizado en tablas y reportes incluye un distintivo con su procedencia:
  - `OBSERVED`: Registro directamente observado en fuentes oficiales de FARMAFÉ.
  - `DERIVED`: Métrica calculada formalmente a partir de datos observados.
  - `ASSUMPTION`: Supuesto logístico configurable en el sistema.
  - `SIMULATED`: Registro generado para simulaciones de escenario.
  - `PENDING_VALIDATION`: Dato pendiente de validación operativa.
* La tasa histórica OTIF (*On-Time In-Full*) se reporta explícitamente como **no calculable con datos históricos** debido a que los registros contables previos agregan ventas anuales sin trazabilidad de líneas de pedido con fechas solicitadas vs. entregadas.

---

## 🛠️ Solución a Incidencias Frecuentes

* **Error 404 al navegar entre módulos:**
  Verifica que el módulo `rewrite_module` esté descomentado en `C:\xampp\apache\conf\httpd.conf` y que la directiva `AllowOverride All` esté activa para el directorio `htdocs`.
* **Apache no inicia en puerto 80:**
  XAMPP está configurado para escuchar en el puerto **`8012`**. Accede siempre indicando el puerto: `http://localhost:8012/farmafe`.
* **Error al ejecutar el solver PLEM:**
  Asegúrate de que la ruta a `python_bin` en `Software/config/config.local.php` apunte a un ejecutable válido con el paquete `pulp` instalado.

