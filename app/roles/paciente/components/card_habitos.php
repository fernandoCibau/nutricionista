<?php 
// Variables requeridas: $habitos, $completados_hoy
?>
<!-- Card de Hábitos -->
<div class="card shadow-sm mt-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0">Mis Hábitos</h3>
            <small class="text-muted">Sigue tu progreso diario</small>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($habitos)): ?>
            <p class="text-muted">No tienes hábitos asignados. Contactá a tu nutricionista.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($habitos as $hab): ?>
                    <?php 
                        $completado = !empty($completados_hoy[$hab['id']]);
                        $racha = $hab['racha_actual'] ?? 0;
                        $progreso_semana = (($hab['completados_semana'] ?? 0) / 7) * 100;
                        $total_completados = $hab['veces_completado'] ?? 0;
                        
                        // Determinar icono basado en el texto del hábito
                        $texto_habito = strtolower($hab['descripcion']);
                        $icono = 'bi-check-circle-fill';
                        if (strpos($texto_habito, 'agua') !== false) {
                            $icono = 'bi-droplet-fill';
                        } elseif (strpos($texto_habito, 'ejercicio') !== false || strpos($texto_habito, 'actividad') !== false) {
                            $icono = 'bi-bicycle';
                        } elseif (strpos($texto_habito, 'comida') !== false || strpos($texto_habito, 'alimentación') !== false) {
                            $icono = 'bi-egg-fried';
                        } elseif (strpos($texto_habito, 'dormir') !== false || strpos($texto_habito, 'descanso') !== false) {
                            $icono = 'bi-moon-stars-fill';
                        }
                        
                        $color_progreso = match(true) {
                            $progreso_semana >= 80 => 'success',
                            $progreso_semana >= 60 => 'info',
                            $progreso_semana >= 40 => 'warning',
                            default => 'danger'
                        };
                    ?>
                    <div class="col">
                        <div class="card h-100 <?php echo $completado ? 'border-success' : ''; ?>">
                            <div class="card-body" style="min-height: 320px;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="bi <?php echo $icono; ?> fs-4 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($hab['descripcion']); ?></h5>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span>Progreso semanal</span>
                                        <span class="badge bg-<?php echo $color_progreso; ?>">
                                            <?php echo number_format($progreso_semana, 0); ?>%
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-<?php echo $color_progreso; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $progreso_semana; ?>%" 
                                             aria-valuenow="<?php echo $progreso_semana; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mb-3">
                                    <div class="text-center">
                                        <div class="h4 mb-0"><?php echo $racha; ?></div>
                                        <small class="text-muted">Racha actual</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="h4 mb-0"><?php echo $total_completados; ?></div>
                                        <small class="text-muted">Total completados</small>
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-2 mt-3">
                                    <form action="marcar_habito.php" method="POST" class="d-flex flex-column flex-md-row gap-2 align-items-center" onsubmit="return actualizarBotonSegunFecha(this)">
                                        <input type="hidden" name="id_habito" value="<?php echo $hab['id']; ?>">
                                        <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>"
                                               onchange="verificarCompletado(this.form, <?php echo $hab['id']; ?>)"
                                               class="form-control" style="min-width:120px;max-width:140px;">
                                        <button type="submit"
                                                class="btn <?php echo $completado ? 'btn-outline-danger' : 'btn-success'; ?> mx-auto">
                                            <?php if ($completado): ?>
                                                <i class="bi bi-x-circle me-1"></i>Desmarcar
                                            <?php else: ?>
                                                <i class="bi bi-check-circle me-1"></i>Marcar
                                            <?php endif; ?>
                                        </button>
                                    </form>

                                    <!-- Botón para abrir calendario -->
                                    <button type="button" 
                                            class="btn btn-outline-primary w-100 btn-open-calendar" 
                                            data-habit-id="<?php echo $hab['id']; ?>" 
                                            data-habit-desc="<?php echo htmlspecialchars($hab['descripcion'], ENT_QUOTES); ?>">
                                        <i class="bi bi-calendar3 me-1"></i>Ver calendario
                                    </button>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3"></i>
                                    Asignado: <?php echo date('d/m/Y', strtotime($hab['creado_en'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Modal reutilizable para calendario de hábitos -->
<div class="modal fade" id="habitCalendarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="habitCalendarTitle">Calendario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button class="btn btn-sm btn-secondary" id="calPrev"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-sm btn-secondary ms-2" id="calNext"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div id="calMonthYear" class="fw-semibold"></div>
                </div>
                <div id="habitCalendarContainer" style="min-height:240px;">
                    <!-- Calendar grid será renderizado aquí por JS -->
                </div>
                <div class="mt-3 small text-muted">Toca un día para marcar / desmarcar. Los cambios se guardan automáticamente.</div>
            </div>
        </div>
    </div>
</div>