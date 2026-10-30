-- =============================================================================
-- ESQUEMA COMPLETO: FARMAFÉ (farmafe_app)
-- Base de datos para el prototipo de planificacion logistica PLEM
-- Importable directamente en phpMyAdmin o MySQL CLI
-- IMPORTANTE: No modifica ni destruye farmafe_db u otras bases existentes.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `farmafe_app` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `farmafe_app`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Configuracion del sistema
CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
    `clave` VARCHAR(64) NOT NULL PRIMARY KEY,
    `valor` TEXT NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `modificado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles del sistema
CREATE TABLE IF NOT EXISTS `roles` (
    `id_rol` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_rol` VARCHAR(32) NOT NULL UNIQUE,
    `nombre` VARCHAR(64) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_usuario` VARCHAR(64) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `nombre_completo` VARCHAR(128) NOT NULL,
    `email` VARCHAR(128) NOT NULL,
    `id_rol` INT NOT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `ultimo_acceso` DATETIME NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Auditoria de eventos
CREATE TABLE IF NOT EXISTS `auditoria_eventos` (
    `id_auditoria` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT NULL,
    `accion` VARCHAR(64) NOT NULL,
    `entidad` VARCHAR(64) NOT NULL,
    `id_entidad` VARCHAR(64) NULL,
    `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `valores_previos` LONGTEXT NULL,
    `valores_nuevos` LONGTEXT NULL,
    `motivo` TEXT NULL,
    `ip_origen` VARCHAR(45) NULL,
    INDEX `idx_auditoria_usuario` (`id_usuario`),
    INDEX `idx_auditoria_entidad` (`entidad`, `id_entidad`),
    INDEX `idx_auditoria_fecha` (`fecha_hora`),
    CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Importaciones (staging y trazabilidad)
CREATE TABLE IF NOT EXISTS `importaciones` (
    `id_importacion` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_archivo_original` VARCHAR(255) NOT NULL,
    `nombre_archivo_almacenado` VARCHAR(255) NOT NULL,
    `hash_sha256` VARCHAR(64) NOT NULL,
    `tamano_bytes` BIGINT NOT NULL,
    `formato` VARCHAR(10) NOT NULL,
    `tipo_dataset` VARCHAR(64) NOT NULL,
    `hoja` VARCHAR(100) NULL,
    `filas_totales` INT NOT NULL DEFAULT 0,
    `filas_validas` INT NOT NULL DEFAULT 0,
    `filas_con_error` INT NOT NULL DEFAULT 0,
    `estado` ENUM('CARGADO', 'VALIDADO', 'CONFIRMADO', 'RECHAZADO') NOT NULL DEFAULT 'CARGADO',
    `naturaleza_general` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `id_usuario` INT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `confirmado_en` DATETIME NULL,
    INDEX `idx_importacion_hash` (`hash_sha256`),
    CONSTRAINT `fk_importaciones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Filas de importacion (staging)
CREATE TABLE IF NOT EXISTS `importacion_filas` (
    `id_fila` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_importacion` INT NOT NULL,
    `numero_fila` INT NOT NULL,
    `identificador_negocio` VARCHAR(128) NULL,
    `datos_crudos_json` LONGTEXT NOT NULL,
    `estado` ENUM('PENDIENTE', 'VALIDO', 'ERROR', 'CONFIRMADO') NOT NULL DEFAULT 'PENDIENTE',
    `mensaje_error` TEXT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_filas_importacion` (`id_importacion`),
    CONSTRAINT `fk_filas_importacion` FOREIGN KEY (`id_importacion`) REFERENCES `importaciones` (`id_importacion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Catalogo de medicamentos / productos
CREATE TABLE IF NOT EXISTS `productos` (
    `id_producto` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(32) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NOT NULL,
    `presentacion` VARCHAR(128) NULL,
    `unidad_medida` VARCHAR(32) NOT NULL DEFAULT 'UNIDAD',
    `factor_empaque` INT NOT NULL DEFAULT 1,
    `costo_unitario_promedio_bs` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
    `clasificacion_abc` ENUM('A', 'B', 'C') NOT NULL DEFAULT 'B',
    `condicion_almacenamiento` VARCHAR(64) NOT NULL DEFAULT 'AMBIENTE',
    `cadena_frio` ENUM('SI', 'NO', 'NO_IDENTIFICADO') NOT NULL DEFAULT 'NO_IDENTIFICADO',
    `registro_sanitario` VARCHAR(64) NULL,
    `estado_registro_sanitario` ENUM('VIGENTE', 'VENCIDO', 'PENDIENTE_VALIDACION') NOT NULL DEFAULT 'PENDIENTE_VALIDACION',
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_producto_abc` (`clasificacion_abc`),
    INDEX `idx_producto_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Catalogo de proveedores
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(32) NOT NULL UNIQUE,
    `razon_social` VARCHAR(255) NOT NULL,
    `tipo` VARCHAR(64) NULL,
    `departamento` VARCHAR(64) NULL,
    `plazo_base_dias` INT NOT NULL DEFAULT 7,
    `variacion_plazo_dias` INT NOT NULL DEFAULT 2,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Pares producto - proveedor (oferta y condiciones)
CREATE TABLE IF NOT EXISTS `producto_proveedor` (
    `id_producto_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
    `id_producto` INT NOT NULL,
    `id_proveedor` INT NOT NULL,
    `costo_unitario_bs` DECIMAL(12, 4) NOT NULL,
    `plazo_dias` INT NOT NULL DEFAULT 7,
    `compra_minima` INT NOT NULL DEFAULT 1,
    `multiplo_empaque` INT NOT NULL DEFAULT 1,
    `prioridad` INT NOT NULL DEFAULT 1,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_producto_proveedor` (`id_producto`, `id_proveedor`),
    CONSTRAINT `fk_pp_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_pp_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Clientes (codigos anonimizados)
CREATE TABLE IF NOT EXISTS `clientes` (
    `id_cliente` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_anonimizado` VARCHAR(32) NOT NULL UNIQUE,
    `tipo_cliente` VARCHAR(64) NULL,
    `segmento_cliente` VARCHAR(64) NULL,
    `departamento` VARCHAR(64) NULL,
    `ciudad` VARCHAR(64) NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Zonas de almacenamiento
CREATE TABLE IF NOT EXISTS `zonas_almacenamiento` (
    `id_zona` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(32) NOT NULL UNIQUE,
    `nombre` VARCHAR(128) NOT NULL,
    `condicion_almacenamiento` VARCHAR(64) NOT NULL DEFAULT 'AMBIENTE',
    `capacidad_empaques` INT NOT NULL DEFAULT 5000,
    `unidad_capacidad` VARCHAR(32) NOT NULL DEFAULT 'EMPAQUES',
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'SIMULATED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Lotes
CREATE TABLE IF NOT EXISTS `lotes` (
    `id_lote` INT AUTO_INCREMENT PRIMARY KEY,
    `id_producto` INT NOT NULL,
    `codigo_lote` VARCHAR(64) NOT NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `fecha_recepcion` DATE NULL,
    `estado_lote` ENUM('DISPONIBLE', 'BLOQUEADO', 'CUARENTENA', 'VENCIDO', 'AGOTADO') NOT NULL DEFAULT 'DISPONIBLE',
    `condicion_almacenamiento` VARCHAR(64) NOT NULL DEFAULT 'AMBIENTE',
    `id_zona` INT NULL,
    `saldo_actual` INT NOT NULL DEFAULT 0,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_producto_lote` (`id_producto`, `codigo_lote`),
    INDEX `idx_lote_vencimiento` (`fecha_vencimiento`),
    INDEX `idx_lote_estado` (`estado_lote`),
    CONSTRAINT `fk_lotes_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_lotes_zona` FOREIGN KEY (`id_zona`) REFERENCES `zonas_almacenamiento` (`id_zona`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Ordenes de compra
CREATE TABLE IF NOT EXISTS `ordenes_compra` (
    `id_orden` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_orden` VARCHAR(32) NOT NULL UNIQUE,
    `id_proveedor` INT NOT NULL,
    `fecha_emision` DATE NOT NULL,
    `fecha_prometida` DATE NULL,
    `fecha_recepcion_real` DATE NULL,
    `estado` ENUM('PROPUESTA', 'APROBADA', 'EMITIDA', 'PARCIALMENTE_RECIBIDA', 'RECIBIDA', 'CERRADA', 'CANCELADA') NOT NULL DEFAULT 'PROPUESTA',
    `es_comprometida` TINYINT(1) NOT NULL DEFAULT 0,
    `id_usuario` INT NULL,
    `notas` TEXT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_oc_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
    CONSTRAINT `fk_oc_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Detalles de orden de compra
CREATE TABLE IF NOT EXISTS `orden_compra_detalles` (
    `id_detalle_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_orden` INT NOT NULL,
    `id_producto` INT NOT NULL,
    `cantidad_solicitada` INT NOT NULL,
    `cantidad_recibida` INT NOT NULL DEFAULT 0,
    `costo_unitario_bs` DECIMAL(12, 4) NOT NULL,
    `subtotal_bs` DECIMAL(14, 4) NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ocd_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes_compra` (`id_orden`) ON DELETE CASCADE,
    CONSTRAINT `fk_ocd_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Recepciones de mercaderia
CREATE TABLE IF NOT EXISTS `recepciones` (
    `id_recepcion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_orden` INT NULL,
    `numero_nota_ingreso` VARCHAR(64) NOT NULL,
    `fecha_recepcion` DATE NOT NULL,
    `estado` ENUM('REGISTRADA', 'CONCILIADA', 'ANULADA') NOT NULL DEFAULT 'REGISTRADA',
    `id_usuario` INT NULL,
    `observacion` TEXT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_recep_orden` FOREIGN KEY (`id_orden`) REFERENCES `ordenes_compra` (`id_orden`) ON DELETE SET NULL,
    CONSTRAINT `fk_recep_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Detalles de recepcion
CREATE TABLE IF NOT EXISTS `recepcion_detalles` (
    `id_detalle_recepcion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_recepcion` INT NOT NULL,
    `id_detalle_compra` INT NULL,
    `id_producto` INT NOT NULL,
    `id_lote` INT NULL,
    `cantidad_recibida` INT NOT NULL,
    `costo_unitario_bs` DECIMAL(12, 4) NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rd_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `recepciones` (`id_recepcion`) ON DELETE CASCADE,
    CONSTRAINT `fk_rd_detalle_compra` FOREIGN KEY (`id_detalle_compra`) REFERENCES `orden_compra_detalles` (`id_detalle_compra`) ON DELETE SET NULL,
    CONSTRAINT `fk_rd_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_rd_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Movimientos de inventario (Libro Diario de Stock)
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
    `id_movimiento` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `id_producto` INT NOT NULL,
    `id_lote` INT NULL,
    `tipo_movimiento` ENUM('ENTRADA_COMPRA', 'SALIDA_PEDIDO', 'BAJA_VENCIMIENTO', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO', 'DEVOLUCION') NOT NULL,
    `cantidad` INT NOT NULL,
    `saldo_resultante` INT NOT NULL,
    `fecha_movimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `motivo` VARCHAR(255) NOT NULL,
    `documento_referencia` VARCHAR(128) NULL,
    `id_usuario` INT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mov_producto` (`id_producto`),
    INDEX `idx_mov_lote` (`id_lote`),
    INDEX `idx_mov_fecha` (`fecha_movimiento`),
    CONSTRAINT `fk_mov_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_mov_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`) ON DELETE SET NULL,
    CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Pedidos de clientes
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id_pedido` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_pedido` VARCHAR(32) NOT NULL UNIQUE,
    `id_cliente` INT NOT NULL,
    `fecha_solicitud` DATE NOT NULL,
    `fecha_compromiso` DATE NULL,
    `estado` ENUM('SOLICITADO', 'APROBADO', 'EN_PREPARACION', 'PARCIALMENTE_ATENDIDO', 'ATENDIDO', 'CANCELADO') NOT NULL DEFAULT 'SOLICITADO',
    `gestion` INT NOT NULL,
    `mes` INT NOT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'SIMULATED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ped_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Detalles de pedidos
CREATE TABLE IF NOT EXISTS `pedido_detalles` (
    `id_detalle_pedido` INT AUTO_INCREMENT PRIMARY KEY,
    `id_pedido` INT NOT NULL,
    `id_producto` INT NOT NULL,
    `cantidad_solicitada` INT NOT NULL,
    `cantidad_atendida` INT NOT NULL DEFAULT 0,
    `cantidad_pendiente` INT NOT NULL DEFAULT 0,
    `cantidad_perdida` INT NOT NULL DEFAULT 0,
    `id_lote_asignado` INT NULL,
    `estado_linea` ENUM('PENDIENTE', 'PARCIAL', 'COMPLETA', 'PERDIDA') NOT NULL DEFAULT 'PENDIENTE',
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'SIMULATED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pdet_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
    CONSTRAINT `fk_pdet_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_pdet_lote` FOREIGN KEY (`id_lote_asignado`) REFERENCES `lotes` (`id_lote`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Despachos
CREATE TABLE IF NOT EXISTS `despachos` (
    `id_despacho` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_despacho` VARCHAR(32) NOT NULL UNIQUE,
    `id_pedido` INT NOT NULL,
    `fecha_preparacion` DATE NOT NULL,
    `fecha_salida` DATE NULL,
    `fecha_entrega` DATE NULL,
    `estado` ENUM('PREPARADO', 'EN_TRANSITO', 'ENTREGADO', 'RECHAZADO') NOT NULL DEFAULT 'PREPARADO',
    `costo_despacho_bs` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'SIMULATED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_desp_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Demanda periodica (agregada o modelada)
CREATE TABLE IF NOT EXISTS `demanda_periodica` (
    `id_demanda` INT AUTO_INCREMENT PRIMARY KEY,
    `id_producto` INT NOT NULL,
    `gestion` INT NOT NULL,
    `mes` INT NOT NULL,
    `periodo_secuencial` INT NOT NULL DEFAULT 1,
    `cantidad` INT NOT NULL,
    `unidad_medida` VARCHAR(32) NOT NULL DEFAULT 'UNIDAD',
    `tipo_demanda` ENUM('VENTA_OBSERVADA', 'DEMANDA_SOLICITADA', 'PRONOSTICO') NOT NULL DEFAULT 'VENTA_OBSERVADA',
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'OBSERVED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_demanda_prod_mes` (`id_producto`, `gestion`, `mes`, `tipo_demanda`),
    CONSTRAINT `fk_dp_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Presupuestos periodicos
CREATE TABLE IF NOT EXISTS `presupuestos_periodicos` (
    `id_presupuesto` INT AUTO_INCREMENT PRIMARY KEY,
    `gestion` INT NOT NULL,
    `mes` INT NOT NULL,
    `periodo_secuencial` INT NOT NULL DEFAULT 1,
    `presupuesto_total_bs` DECIMAL(14, 2) NOT NULL,
    `reserva_emergencia_pct` DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    `presupuesto_ordinario_bs` DECIMAL(14, 2) NOT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'SIMULATED',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_presupuesto_mes` (`gestion`, `mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Costos y parametros de escenario
CREATE TABLE IF NOT EXISTS `costos_escenario` (
    `id_costo` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_parametro` VARCHAR(64) NOT NULL UNIQUE,
    `categoria` ENUM('COSTO', 'OPERACION', 'TOLERANCIA') NOT NULL DEFAULT 'COSTO',
    `valor` DECIMAL(14, 4) NOT NULL,
    `unidad_medida` VARCHAR(32) NOT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'ASSUMPTION',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Escenarios de planificacion
CREATE TABLE IF NOT EXISTS `escenarios_planificacion` (
    `id_escenario` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_escenario` VARCHAR(32) NOT NULL UNIQUE,
    `nombre` VARCHAR(128) NOT NULL,
    `descripcion` TEXT NULL,
    `fecha_corte` DATE NOT NULL,
    `periodo_inicio_gestion` INT NOT NULL,
    `periodo_inicio_mes` INT NOT NULL,
    `horizonte_meses` INT NOT NULL DEFAULT 6,
    `modo_demanda` ENUM('AGREGADO', 'DETALLADO') NOT NULL DEFAULT 'AGREGADO',
    `politica_faltante` ENUM('BACKORDER', 'VENTA_PERDIDA') NOT NULL DEFAULT 'BACKORDER',
    `presupuesto_mensual_defecto_bs` DECIMAL(14, 2) NOT NULL DEFAULT 250000.00,
    `tolerancia_epsilon` DECIMAL(8, 6) NOT NULL DEFAULT 0.000100,
    `estado` ENUM('BORRADOR', 'ACTIVO', 'CERRADO') NOT NULL DEFAULT 'ACTIVO',
    `id_usuario` INT NULL,
    `naturaleza_dato` ENUM('OBSERVED', 'DERIVED', 'SIMULATED', 'ASSUMPTION', 'PENDING_VALIDATION') NOT NULL DEFAULT 'ASSUMPTION',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_esc_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Ejecuciones PLEM (inmutables)
CREATE TABLE IF NOT EXISTS `ejecuciones_plem` (
    `id_ejecucion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_escenario` INT NOT NULL,
    `version_plan` VARCHAR(32) NOT NULL,
    `fecha_ejecucion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `solver_usado` VARCHAR(64) NOT NULL DEFAULT 'HiGHS / SciPy MILP',
    `version_solver` VARCHAR(32) NULL,
    `tiempo_ejecucion_segundos` DECIMAL(8, 3) NOT NULL DEFAULT 0.000,
    `estado_solver_etapa1` VARCHAR(32) NOT NULL,
    `estado_solver_etapa2` VARCHAR(32) NOT NULL,
    `nivel_servicio_optimo_s1` DECIMAL(8, 6) NOT NULL DEFAULT 0.000000,
    `costo_logistico_total_s2` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_compra_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_orden_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_posesion_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_faltante_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_vencimiento_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `costo_despacho_bs` DECIMAL(16, 4) NOT NULL DEFAULT 0.0000,
    `total_demanda_solicitada` INT NOT NULL DEFAULT 0,
    `total_demanda_atendida` INT NOT NULL DEFAULT 0,
    `total_demanda_perdida` INT NOT NULL DEFAULT 0,
    `total_compras_unidades` INT NOT NULL DEFAULT 0,
    `hash_entradas` VARCHAR(64) NOT NULL,
    `snapshot_entradas_json` LONGTEXT NULL,
    `mensaje_diagnostico` TEXT NULL,
    `id_usuario` INT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ejec_escenario` (`id_escenario`),
    CONSTRAINT `fk_ejec_escenario` FOREIGN KEY (`id_escenario`) REFERENCES `escenarios_planificacion` (`id_escenario`),
    CONSTRAINT `fk_ejec_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Planes de compras recomendadas
CREATE TABLE IF NOT EXISTS `planes_compras` (
    `id_plan_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_ejecucion` INT NOT NULL,
    `periodo_t` INT NOT NULL,
    `periodo_nombre` VARCHAR(32) NOT NULL,
    `id_producto` INT NOT NULL,
    `id_proveedor` INT NOT NULL,
    `cantidad_propuesta` INT NOT NULL,
    `costo_unitario_bs` DECIMAL(12, 4) NOT NULL,
    `subtotal_bs` DECIMAL(14, 4) NOT NULL,
    `lead_time_dias` INT NOT NULL DEFAULT 7,
    `periodo_llegada_t` INT NOT NULL,
    `estado_decision` ENUM('PROPUESTA', 'APROBADA', 'COMPROMETIDA', 'EMITIDA', 'CANCELADA') NOT NULL DEFAULT 'PROPUESTA',
    `id_orden_compra_emitida` INT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pc_ejecucion` (`id_ejecucion`),
    CONSTRAINT `fk_pc_ejecucion` FOREIGN KEY (`id_ejecucion`) REFERENCES `ejecuciones_plem` (`id_ejecucion`) ON DELETE CASCADE,
    CONSTRAINT `fk_pc_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_pc_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
    CONSTRAINT `fk_pc_oc` FOREIGN KEY (`id_orden_compra_emitida`) REFERENCES `ordenes_compra` (`id_orden`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Proyeccion de inventario por producto/lote/periodo
CREATE TABLE IF NOT EXISTS `inventario_proyectado` (
    `id_inventario_proy` INT AUTO_INCREMENT PRIMARY KEY,
    `id_ejecucion` INT NOT NULL,
    `periodo_t` INT NOT NULL,
    `periodo_nombre` VARCHAR(32) NOT NULL,
    `id_producto` INT NOT NULL,
    `id_lote` INT NULL,
    `saldo_inicial` INT NOT NULL DEFAULT 0,
    `recepciones_programadas` INT NOT NULL DEFAULT 0,
    `demanda_asignada` INT NOT NULL DEFAULT 0,
    `cantidad_vencida` INT NOT NULL DEFAULT 0,
    `saldo_final` INT NOT NULL DEFAULT 0,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_ejecucion` (`id_ejecucion`),
    CONSTRAINT `fk_ip_ejecucion` FOREIGN KEY (`id_ejecucion`) REFERENCES `ejecuciones_plem` (`id_ejecucion`) ON DELETE CASCADE,
    CONSTRAINT `fk_ip_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_ip_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Resultados de atencion de demanda por periodo
CREATE TABLE IF NOT EXISTS `demanda_resultado_plan` (
    `id_resultado_demanda` INT AUTO_INCREMENT PRIMARY KEY,
    `id_ejecucion` INT NOT NULL,
    `periodo_t` INT NOT NULL,
    `periodo_nombre` VARCHAR(32) NOT NULL,
    `id_producto` INT NOT NULL,
    `id_detalle_pedido` INT NULL,
    `cantidad_solicitada` INT NOT NULL,
    `cantidad_atendida` INT NOT NULL DEFAULT 0,
    `cantidad_pendiente` INT NOT NULL DEFAULT 0,
    `cantidad_perdida` INT NOT NULL DEFAULT 0,
    `porcentaje_atencion` DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dr_ejecucion` (`id_ejecucion`),
    CONSTRAINT `fk_dr_ejecucion` FOREIGN KEY (`id_ejecucion`) REFERENCES `ejecuciones_plem` (`id_ejecucion`) ON DELETE CASCADE,
    CONSTRAINT `fk_dr_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_dr_dp` FOREIGN KEY (`id_detalle_pedido`) REFERENCES `pedido_detalles` (`id_detalle_pedido`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. Asignaciones de lotes a demanda en el plan
CREATE TABLE IF NOT EXISTS `asignaciones_plan` (
    `id_asignacion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_ejecucion` INT NOT NULL,
    `periodo_t` INT NOT NULL,
    `id_producto` INT NOT NULL,
    `id_lote` INT NOT NULL,
    `cantidad_asignada` INT NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_asig_ejecucion` (`id_ejecucion`),
    CONSTRAINT `fk_asig_ejecucion` FOREIGN KEY (`id_ejecucion`) REFERENCES `ejecuciones_plem` (`id_ejecucion`) ON DELETE CASCADE,
    CONSTRAINT `fk_asig_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
    CONSTRAINT `fk_asig_lote` FOREIGN KEY (`id_lote`) REFERENCES `lotes` (`id_lote`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Comparaciones de planes (AS-IS vs TO-BE, o Re-optimizaciones)
CREATE TABLE IF NOT EXISTS `comparaciones_planes` (
    `id_comparacion` INT AUTO_INCREMENT PRIMARY KEY,
    `id_ejecucion_base` INT NOT NULL,
    `id_ejecucion_evaluada` INT NOT NULL,
    `tipo_comparacion` ENUM('AS_IS_VS_TO_BE', 'PLAN_VS_REAL', 'SENSIBILIDAD') NOT NULL DEFAULT 'AS_IS_VS_TO_BE',
    `fecha_comparacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `kpi_servicio_base` DECIMAL(8, 6) NOT NULL,
    `kpi_servicio_evaluado` DECIMAL(8, 6) NOT NULL,
    `dif_servicio_pct` DECIMAL(8, 4) NOT NULL,
    `kpi_costo_base` DECIMAL(16, 4) NOT NULL,
    `kpi_costo_evaluado` DECIMAL(16, 4) NOT NULL,
    `dif_costo_bs` DECIMAL(16, 4) NOT NULL,
    `observacion` TEXT NULL,
    `id_usuario` INT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comp_base` FOREIGN KEY (`id_ejecucion_base`) REFERENCES `ejecuciones_plem` (`id_ejecucion`),
    CONSTRAINT `fk_comp_eval` FOREIGN KEY (`id_ejecucion_evaluada`) REFERENCES `ejecuciones_plem` (`id_ejecucion`),
    CONSTRAINT `fk_comp_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

