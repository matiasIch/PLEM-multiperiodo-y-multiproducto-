-- =============================================================================
-- DATOS INICIALES DEL SISTEMA: FARMAFÉ (farmafe_app)
-- Inserta roles de usuario, costos base de escenario y parametros globales
-- NO contiene usuarios ni credenciales predeterminadas.
-- =============================================================================

USE `farmafe_app`;

-- Roles del sistema
INSERT INTO `roles` (`id_rol`, `codigo_rol`, `nombre`, `descripcion`, `activo`) VALUES
(1, 'ADMIN', 'Administrador', 'Control total, gestion de usuarios, maestros, importacion y auditoria', 1),
(2, 'GERENCIA', 'Gerencia', 'Consulta ejecutiva, aprobacion de planes PLEM y evaluacion economica', 1),
(3, 'VENTAS', 'Ventas y Demanda', 'Gestion de pedidos, clientes y seguimiento de atencion de demanda', 1),
(4, 'ALMACEN', 'Almacen e Inventarios', 'Recepcion de compras, control de lotes, vencimientos y movimientos', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Configuracion global del sistema
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`) VALUES
('empresa_nombre', 'FARMAFÉ', 'Razon comercial de la distribuidora'),
('empresa_pais', 'Bolivia', 'Pais de operacion'),
('empresa_ciudad', 'La Paz', 'Ciudad de la sede central'),
('sistema_instalado', '0', '0 = Requiere asistente inicial de administrador; 1 = Instalado'),
('moneda_simbolo', 'Bs', 'Simbolo monetario boliviano'),
('moneda_codigo', 'BOB', 'Codigo ISO monetario'),
('version_sistema', '1.0.0', 'Version del prototipo web FARMAFE'),
('python_executable_path', 'C:\\Program Files\\Blender Foundation\\Blender 5.0\\5.0\\python\\bin\\python.exe', 'Ruta al binario Python con SciPy / HiGHS')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- Parametros de costos y supuestos de escenario (editables)
-- Marcados formalmente como ASSUMPTION
INSERT INTO `costos_escenario` (`nombre_parametro`, `categoria`, `valor`, `unidad_medida`, `descripcion`, `naturaleza_dato`) VALUES
('costo_emision_orden_bs', 'COSTO', 50.0000, 'Bs/orden', 'Costo administrativo fijo por emitir una orden de compra', 'ASSUMPTION'),
('costo_posesion_anual_pct', 'COSTO', 20.0000, '% anual', 'Tasa anual de costo de mantener inventario sobre el valor de compra', 'ASSUMPTION'),
('costo_faltante_unitario_bs', 'COSTO', 15.0000, 'Bs/unidad', 'Costo estimado de penalizacion por unidad no satisfecha a tiempo', 'ASSUMPTION'),
('costo_baja_vencimiento_bs', 'COSTO', 5.0000, 'Bs/unidad', 'Costo adicional de destruccion/baja por medicamento vencido', 'ASSUMPTION'),
('costo_despacho_envio_bs', 'COSTO', 35.0000, 'Bs/envio', 'Costo promedio atribuible de envio local/departamental', 'ASSUMPTION'),
('tolerancia_numerica_epsilon', 'TOLERANCIA', 0.0001, 'adimensional', 'Tolerancia numerica para conservar nivel de servicio optimo en etapa 2', 'ASSUMPTION'),
('dias_alerta_vencimiento', 'OPERACION', 365.0000, 'dias', 'Umbral de corto vencimiento comercial segun politica de la empresa', 'ASSUMPTION')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

