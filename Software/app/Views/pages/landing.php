<div class="card" style="border-top: 4px solid var(--yellow-500); padding: 2.5rem 2rem; text-align: center;">
    <div style="margin-bottom: 1.5rem;">
        <span class="brand-badge" style="font-size: 1.5rem; padding: 0.4rem 1rem;">FARMAFÉ</span>
    </div>
    
    <h2 style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--navy-800);">
        Distribuidora de Medicamentos
    </h2>

    <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.75rem; line-height: 1.6;">
        Sistema de Apoyo a Decisiones para la Planificación Logística de Compras, Gestión de Lotes y Atención de la Demanda mediante Optimización Lineal Entera Mixta (PLEM).
    </p>

    <div style="background-color: var(--blue-50); border: 1px solid #bfdbfe; border-radius: 6px; padding: 1.25rem; margin-bottom: 2rem; text-align: left;">
        <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--blue-700); margin-bottom: 0.5rem;">
            Marco Académico del Proyecto
        </h4>
        <p style="font-size: 0.85rem; color: var(--navy-800); margin-bottom: 0.5rem;">
            <strong>Propuesta:</strong> Modelo PLEM multiproducto y multiperiodo con horizonte móvil de seis meses, optimización lexicográfica en dos etapas (Nivel de Servicio y Costo Logístico).
        </p>
        <p style="font-size: 0.8rem; color: var(--text-muted);">
            Sede: La Paz, Bolivia &bull; Caso de Estudio: FARMAFÉ &bull; Enfoque: Investigación de Operaciones aplicada.
        </p>
    </div>

    <div style="display: flex; justify-content: center; gap: 1rem;">
        <?php if ($isSetupNeeded): ?>
            <a href="<?= e(\App\Core\Router::url('/setup')) ?>" class="btn btn-accent" style="padding: 0.75rem 1.75rem; font-size: 0.95rem;">
                Configurar Administrador Inicial
            </a>
        <?php else: ?>
            <a href="<?= e(\App\Core\Router::url('/login')) ?>" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 0.95rem;">
                Ingresar a la Plataforma
            </a>
        <?php endif; ?>
    </div>
</div>

