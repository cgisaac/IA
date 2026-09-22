<?php
/**
 * View do Dashboard Gerencial
 */
use Core\MVC\View\ViewHelper;

$stats = $this->data['stats'] ?? [];
$insights = $this->data['insights'] ?? [];
$performance = $this->data['performance'] ?? [];
$ticketsByStatus = $this->data['ticketsByStatus'] ?? [];
$recentTickets = $this->data['recentTickets'] ?? [];
$timeMetrics = $this->data['timeMetrics'] ?? [];
?>

<div class="dashboard-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; color: #1e293b;">Dashboard Gerencial</h1>
            <p style="color: #64748b; margin-top: 0.25rem;">Visão geral dos indicadores de TI</p>
        </div>
        <button onclick="location.reload()" class="btn btn-outline">
            🔄 Atualizar
        </button>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-4" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_tickets'] ?? 0 ?></div>
            <div class="stat-label">Total Chamados</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #f59e0b;"><?= $stats['open_tickets'] ?? 0 ?></div>
            <div class="stat-label">Em Aberto</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #10b981;"><?= $stats['closed_today'] ?? 0 ?></div>
            <div class="stat-label">Fechados Hoje</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #ef4444;"><?= $stats['overdue_tickets'] ?? 0 ?></div>
            <div class="stat-label">Atrasados</div>
        </div>
    </div>
    
    <!-- Insights -->
    <?php if (!empty($insights)): ?>
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">💡 Insights Automáticos</h2>
        </div>
        <div class="grid grid-3">
            <?php foreach (array_slice($insights, 0, 3) as $insight): ?>
            <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 4px solid <?= $insight['type'] == 'warning' ? '#f59e0b' : ($insight['type'] == 'success' ? '#10b981' : '#3b82f6') ?>">
                <strong><?= ViewHelper::escape($insight['title']) ?></strong>
                <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #64748b;"><?= ViewHelper::escape($insight['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Charts Row -->
    <div class="grid grid-2" style="margin-bottom: 2rem;">
        <!-- Tickets by Status -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📊 Chamados por Status</h2>
            </div>
            <div id="chart-status" style="height: 300px;">
                <?php foreach ($ticketsByStatus as $status => $count): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; padding: 0.5rem; background: #f8fafc; border-radius: 6px;">
                    <span><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                    <strong><?= $count ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Time Metrics -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⏱️ Métricas de Tempo</h2>
            </div>
            <div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Tempo Médio Atendimento</span>
                        <strong><?= ViewHelper::formatDuration($timeMetrics['avg_attendance_time'] ?? 0) ?></strong>
                    </div>
                    <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: #3b82f6; height: 100%; width: <?= min(100, ($timeMetrics['avg_attendance_time'] ?? 0) / 3600 * 100) ?>%"></div>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Tempo Médio Espera</span>
                        <strong><?= ViewHelper::formatDuration($timeMetrics['avg_wait_time'] ?? 0) ?></strong>
                    </div>
                    <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: #f59e0b; height: 100%; width: <?= min(100, ($timeMetrics['avg_wait_time'] ?? 0) / 3600 * 100) ?>%"></div>
                    </div>
                </div>
                
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Ociosidade Total</span>
                        <strong><?= ViewHelper::formatDuration($timeMetrics['total_idle_time'] ?? 0) ?></strong>
                    </div>
                    <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: #10b981; height: 100%; width: <?= min(100, ($timeMetrics['efficiency_rate'] ?? 0)) ?>%"></div>
                    </div>
                    <div style="text-align: right; margin-top: 0.25rem; font-size: 0.875rem; color: #64748b;">
                        Eficiência: <?= number_format($timeMetrics['efficiency_rate'] ?? 0, 1) ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance Table -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">👥 Performance dos Técnicos</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th>Chamados</th>
                        <th>Tempo Médio</th>
                        <td>Eficiência</td>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($performance)): ?>
                        <?php foreach ($performance as $tech): ?>
                        <tr>
                            <td><strong><?= ViewHelper::escape($tech['name'] ?? 'N/A') ?></strong></td>
                            <td><?= $tech['tickets_count'] ?? 0 ?></td>
                            <td><?= ViewHelper::formatDuration($tech['avg_time'] ?? 0) ?></td>
                            <td><?= number_format($tech['efficiency'] ?? 0, 1) ?>%</td>
                            <td>
                                <span class="badge <?= $tech['score'] >= 80 ? 'badge-green' : ($tech['score'] >= 60 ? 'badge-yellow' : 'badge-red') ?>">
                                    <?= number_format($tech['score'] ?? 0, 0) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">Nenhum dado disponível</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Chamados Recentes</h2>
            <a href="/index.php?route=acompanhamento" class="btn btn-primary">Ver Todos</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Solicitante</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentTickets)): ?>
                        <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td>#<?= $ticket['id'] ?? 'N/A' ?></td>
                            <td><?= ViewHelper::escape($ticket['title'] ?? 'Sem título') ?></td>
                            <td><?= ViewHelper::escape($ticket['requester'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge <?= ViewHelper::statusClass($ticket['status'] ?? '') ?>">
                                    <?= ViewHelper::statusIcon($ticket['status'] ?? '') ?> <?= ucfirst($ticket['status'] ?? 'desconhecido') ?>
                                </span>
                            </td>
                            <td><?= ViewHelper::formatDate($ticket['created_at'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">Nenhum chamado recente</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-refresh a cada 5 minutos
setTimeout(() => location.reload(), 300000);
</script>
