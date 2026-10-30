# FARMAFÉ — Proyecto de Grado / Software de Planificación Logística PLEM

Este repositorio contiene la propuesta técnica, investigación y prototipo de software para la empresa distribuidora de medicamentos **FARMAFÉ** (La Paz, Bolivia).

## 🚀 Inicio Rápido del Prototipo Web

Para instalar, ejecutar y probar la aplicación web funcional:
1. Consulta la guía completa de instalación en: **[`Software/README.md`](Software/README.md)**.
2. Inicia **Apache** (puerto `8012`) y **MariaDB/MySQL** (puerto `3306`) en XAMPP.
3. Ingresa en el navegador a: **[http://localhost:8012/farmafe](http://localhost:8012/farmafe)**

### 🔑 Credenciales de Acceso

| Campo | Valor |
|---|---|
| **URL** | `http://localhost:8012/farmafe` *(redirige a `/farmafe/login`)* |
| **Usuario** | `admin` *(o `admin_farmafe`)* |
| **Contraseña** | `Farmafe2026*` |
| **Rol** | Administrador del Sistema (`ADMIN`) |

---

## 📂 Estructura del Repositorio

* **`Software/`**: Aplicación web completa en PHP 8 / MariaDB y motor matemático PLEM en Python (PuLP/HiGHS). Contiene su propio `README.md` con los pasos detallados de configuración y base de datos.
* **`Datos/`**: Archivos de datos históricos, catálogos e inventarios reales de FARMAFÉ (fuentes primarias protegidas).
* **`Instrumentos/`**: Guías de entrevista, encuestas y herramientas de recolección de información.
* **`Bibliografia/`**: Artículos científicos y referencias sobre modelos PLEM/MILP en distribución farmacéutica y cadena de suministro.
* **`Proyecto de Grado Versiones/`**: Documentos del marco teórico, metodológico y versiones escritas del proyecto de grado.

