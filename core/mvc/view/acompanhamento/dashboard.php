<?php
/**
 * View do Dashboard de Acompanhamento em Tempo Real
 */
use Core\MVC\View\ViewHelper;

$openTickets = $this->data['openTickets'] ?? [];
$inProgressTickets = $this->data['inProgressTickets'] ?? [];
$overdueTickets = $this->data['overdueTickets'] ?? [];
$periodicTasks = $this->data['periodicTasks'] ?? [];
?>

<div class="acompanhamento-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; color: #1e293b;">📊 Acompanhamento em Tempo Real</h1>
            <p style="color: #64748b; margin-top: 0.25rem;">Monitoramento de chamados e atividades da equipe</p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <span id="last-update" style="font-size: 0.875rem; color: #64748b; display: flex; align-items: center; gap: 0.5rem;">
                <span class="spinner" style="width: 12px; height: 12px; border-width: 2px;"></span>
                Atualizando...
            </span>
            <button onclick="refreshData()" class="btn btn-primary">🔄 Atualizar Agora</button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-4" style="margin-bottom: 2rem;">
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-value" id="count-open" style="color: #3b82f6;"><?= count($openTickets) ?></div>
            <div class="stat-label">📂 Em Aberto</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-value" id="count-progress" style="color: #8b5cf6;"><?= count($inProgressTickets) ?></div>
            <div class="stat-label">⏳ Em Andamento</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-value" id="count-overdue" style="color: #ef4444;"><?= count($overdueTickets) ?></div>
            <div class="stat-label">🔴 Atrasados</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-value" id="count-tasks"><?= count($periodicTasks) ?></div>
            <div class="stat-label">📋 Tarefas Periódicas</div>
        </div>
    </div>
    
    <!-- Main Grid -->
    <div class="grid grid-2" style="margin-bottom: 2rem;">
        <!-- Chamados em Aberto -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📂 Chamados em Aberto</h2>
                <span class="badge badge-blue" id="badge-open"><?= count($openTickets) ?></span>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Técnico</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody id="table-open">
                        <?php if (!empty($openTickets)): ?>
                            <?php foreach ($openTickets as $ticket): ?>
                            <tr>
                                <td>#<?= $ticket['id'] ?? 'N/A' ?></td>
                                <td><?= ViewHelper::escape($ticket['title'] ?? 'Sem título') ?></td>
                                <td><?= ViewHelper::escape($ticket['technician'] ?? 'Não atribuído') ?></td>
                                <td><?= ViewHelper::formatDate($ticket['created_at'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 2rem;">Nenhum chamado em aberto</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Chamados em Andamento -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⏳ Em Andamento</h2>
                <span class="badge badge-purple" id="badge-progress"><?= count($inProgressTickets) ?></span>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Técnico</th>
                            <th>Tempo</th>
                        </tr>
                    </thead>
                    <tbody id="table-progress">
                        <?php if (!empty($inProgressTickets)): ?>
                            <?php foreach ($inProgressTickets as $ticket): ?>
                            <tr>
                                <td>#<?= $ticket['id'] ?? 'N/A' ?></td>
                                <td><?= ViewHelper::escape($ticket['title'] ?? 'Sem título') ?></td>
                                <td><?= ViewHelper::escape($ticket['technician'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="timer" data-start="<?= $ticket['start_time'] ?? '' ?>">
                                        <?= ViewHelper::formatDuration(0) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #64748b; padding: 2rem;">Nenhum chamado em andamento</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Chamados Atrasados -->
    <?php if (!empty($overdueTickets)): ?>
    <div class="card" style="margin-bottom: 2rem; border: 2px solid #ef4444;">
        <div class="card-header">
            <h2 class="card-title" style="color: #ef4444;">🔴 Chamados Atrasados (Prioridade Máxima)</h2>
            <span class="badge badge-red" id="badge-overdue"><?= count($overdueTickets) ?></span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Solicitante</th>
                        <th>Técnico</th>
                        <th>Atraso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="table-overdue">
                    <?php foreach ($overdueTickets as $ticket): ?>
                    <tr style="background: #fef2f2;">
                        <td><strong>#<?= $ticket['id'] ?? 'N/A' ?></strong></td>
                        <td><?= ViewHelper::escape($ticket['title'] ?? 'Sem título') ?></td>
                        <td><?= ViewHelper::escape($ticket['requester'] ?? 'N/A') ?></td>
                        <td><?= ViewHelper::escape($ticket['technician'] ?? 'Não atribuído') ?></td>
                        <td style="color: #ef4444; font-weight: 600;">
                            <?= ViewHelper::formatDuration($ticket['overdue_seconds'] ?? 0) ?>
                        </td>
                        <td>
                            <a href="/index.php?route=tecnico&ticket=<?= $ticket['id'] ?>" class="btn btn-danger" style="padding: 0.375rem 0.75rem; font-size: 0.75rem;">
                                Atender
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tarefas Periódicas -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Tarefas Periódicas Ativas</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tarefa</th>
                        <th>Frequência</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Próxima Execução</th>
                    </tr>
                </thead>
                <tbody id="table-tasks">
                    <?php if (!empty($periodicTasks)): ?>
                        <?php foreach ($periodicTasks as $task): ?>
                        <tr>
                            <td><strong><?= ViewHelper::escape($task['title'] ?? 'Sem título') ?></strong></td>
                            <td>
                                <span class="badge badge-blue">
                                    <?= ucfirst($task['frequency'] ?? 'diária') ?>
                                </span>
                            </td>
                            <td><?= ViewHelper::escape($task['responsible'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($task['status'] == 'pending'): ?>
                                    <span class="badge badge-yellow">Pendente</span>
                                <?php elseif ($task['status'] == 'in_progress'): ?>
                                    <span class="badge badge-purple">Em Andamento</span>
                                <?php else: ?>
                                    <span class="badge badge-green">Concluída</span>
                                <?php endif; ?>
                            </td>
                            <td><?= ViewHelper::formatDate($task['next_execution'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #64748b;">Nenhuma tarefa periódica ativa</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Configuração de polling (configurável)
const POLLING_INTERVAL = <?= ($_SESSION['polling_interval'] ?? 30) * 1000 ?>;
let lastUpdate = new Date();

// Timer para chamados em andamento
function updateTimers() {
    document.querySelectorAll('.timer').forEach(timer => {
        const startTime = timer.getAttribute('data-start');
        if (startTime) {
            const start = new Date(startTime).getTime();
            const now = Date.now();
            const diff = Math.floor((now - start) / 1000);
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            timer.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    });
}

// Atualiza dados via AJAX
async function refreshData() {
    try {
        const response = await fetch('/index.php?route=acompanhamento_api');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualiza contadores
            document.getElementById('count-open').textContent = data.stats.total_open;
            document.getElementById('count-progress').textContent = data.stats.total_in_progress;
            document.getElementById('count-overdue').textContent = data.stats.total_overdue;
            document.getElementById('badge-open').textContent = data.stats.total_open;
            document.getElementById('badge-progress').textContent = data.stats.total_in_progress;
            document.getElementById('badge-overdue').textContent = data.stats.total_overdue;
            
            // Atualiza tabela de abertos
            updateTable('table-open', data.open_tickets, 'open');
            
            // Atualiza tabela de em andamento
            updateTable('table-progress', data.in_progress_tickets, 'progress');
            
            lastUpdate = new Date();
            document.getElementById('last-update').innerHTML = '✓ Atualizado: ' + lastUpdate.toLocaleTimeString();
        }
    } catch (error) {
        console.error('Erro ao atualizar dados:', error);
        document.getElementById('last-update').innerHTML = '❌ Erro na atualização';
    }
}

function updateTable(tableId, tickets, type) {
    const tbody = document.getElementById(tableId);
    if (!tbody || !tickets) return;
    
    if (tickets.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b; padding: 2rem;">Nenhum chamado</td></tr>';
        return;
    }
    
    let html = '';
    tickets.forEach(ticket => {
        if (type === 'open') {
            html += `<tr>
                <td>#${ticket.id || 'N/A'}</td>
                <td>${ticket.title || 'Sem título'}</td>
                <td>${ticket.technician || 'Não atribuído'}</td>
                <td>${formatDate(ticket.created_at)}</td>
            </tr>`;
        } else if (type === 'progress') {
            html += `<tr>
                <td>#${ticket.id || 'N/A'}</td>
                <td>${ticket.title || 'Sem título'}</td>
                <td>${ticket.technician || 'N/A'}</td>
                <td><span class="timer" data-start="${ticket.start_time || ''}">00:00:00</span></td>
            </tr>`;
        }
    });
    
    tbody.innerHTML = html;
    updateTimers();
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}

// Auto-update timers a cada segundo
setInterval(updateTimers, 1000);

// Auto-refresh dados no intervalo configurado
setInterval(refreshData, POLLING_INTERVAL);

// Primeira atualização
updateTimers();
</script>
