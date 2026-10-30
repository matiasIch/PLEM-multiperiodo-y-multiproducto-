<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h2 style="font-size: 1.15rem; color: var(--navy-800);">Configuración del Modelo de Optimización PLEM</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Modelo lineal entero mixto multiproducto y multiperiodo (Horizonte de seis meses)
            </div>
        </div>
        <div>
            <?= \App\Core\View::provenanceBadge('ASSUMPTION') ?>
        </div>
    </div>
    
    <div class="card-body">
        <form action="<?= e(\App\Core\Router::url('/app/planificacion/ejecutar')) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(\App\Core\Session::getCsrfToken()) ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Escenario -->
                <div class="form-group">
                    <label for="scenario_id" class="form-label">Escenario de Planificación *</label>
                    <select name="scenario_id" id="scenario_id" class="form-control">
                        <?php if (!empty($escenarios)): ?>
                            <?php foreach ($escenarios as $esc): ?>
                                <option value="<?= (int)$esc['id_escenario'] ?>">
                                    <?= e($esc['codigo_escenario']) ?> - <?= e($esc['nombre']) ?> (<?= (int)$esc['horizonte_meses'] ?> meses)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="0">Escenario Base 6 Meses (Generar Automáticamente)</option>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Define horizonte, fecha de corte, política de faltantes y presupuesto.</div>
                </div>

                <!-- Alcance de Productos -->
                <div class="form-group">
                    <label for="prod_mode" class="form-label">Conjunto de Medicamentos a Optimizar *</label>
                    <select name="prod_mode" id="prod_mode" class="form-control">
                        <option value="ALL">Todos los Medicamentos Activos del Catálogo (195)</option>
                        <option value="CLASS_A">Solo Medicamentos Clasificación A (Alta Rotación / Críticos)</option>
                    </select>
                    <div class="form-text">Permite evaluar el catálogo completo o segmentar por rotación.</div>
                </div>
            </div>

            <!-- Parametros del Contrato Matematico -->
            <div style="background-color: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.9rem; color: var(--navy-800); margin-bottom: 0.75rem;">
                    Reglas del Contrato Matemático (Optimización Lexicográfica en 2 Etapas):
                </h4>
                <ul style="font-size: 0.85rem; color: var(--text-main); margin-left: 1.25rem; line-height: 1.6;">
                    <li><strong>Etapa 1:</strong> Maximizar Nivel de Servicio Ponderado <code>S* = &Sigma; w_it &middot; (A_it / D_it)</code> sujeto a balances de inventario por lote, lead times de proveedores y restricciones presupuestarias.</li>
                    <li><strong>Etapa 2:</strong> Minimizar Costo Logístico Total <code>min C = C_compra + C_orden + C_posesion + C_faltante + C_vencimiento</code> imponiendo estrictamente <code>S &ge; S* - &epsilon;</code> con tolerancia numérica &epsilon; = 0.0001.</li>
                    <li><strong>FEFO e Inmutabilidad:</strong> Los lotes vencidos no son elegibles para despacho. Las órdenes de compra previamente marcadas como comprometidas permanecen congeladas.</li>
                </ul>
            </div>

            <!-- Tabla Resumen de Costos de Escenario -->
            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem;">
                    Parámetros Económicos y Operativos Vigentes (Editables en Módulo de Costos):
                </h4>
                <div class="table-responsive">
                    <table class="table" style="font-size: 0.8rem;">
                        <thead>
                            <tr>
                                <th>Parámetro</th>
                                <th>Valor Configurado</th>
                                <th>Unidad</th>
                                <th>Descripción</th>
                                <th>Naturaleza</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($costos as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['nombre_parametro']) ?></strong></td>
                                    <td><strong><?= \App\Core\View::formatNumber($c['valor'], 2) ?></strong></td>
                                    <td><?= e($c['unidad_medida']) ?></td>
                                    <td><?= e($c['descripcion']) ?></td>
                                    <td><?= \App\Core\View::provenanceBadge($c['naturaleza_dato']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-between align-center">
                <a href="<?= e(\App\Core\Router::url('/app')) ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-accent" style="padding: 0.75rem 2rem; font-weight: 700; font-size: 0.95rem;">
                    Ejecutar Optimización Lexicográfica PLEM
                </button>
            </div>
        </form>
    </div>
</div>

