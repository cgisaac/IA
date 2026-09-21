<?php
/**
 * View do Dashboard do Técnico
 */
use Core\MVC\View\ViewHelper;

$myTickets = $this->data['myTickets'] ?? [];
$myOpenTickets = $this->data['myOpenTickets'] ?? [];
$myTasks = $this->data['myTasks'] ?? [];
$myMetrics = $this->data['myMetrics'] ?? [];
?>

<div class="tecnico-container">
    <!-- Alertas de Novos Chamados -->
    <div id="new-ticket-alert" class="alert alert-info hidden" style="position: fixed; top: 80px; right: 20px; z-index: 1000; min-width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <strong>🔔 Novo Chamado Atribuído!</strong>
                <p id="new-ticket-message" style="margin-top: 0.5rem; font-size: 0.875rem;"></p>
            </div>
            <button onclick="this.parentElement.parentElement.classList.add('hidden')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>
    </div>
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.875rem; font-weight: 700; color: #1e293b;">👨‍💻 Área do Técnico</h1>
            <p style="color: #64748b; margin-top: 0.25rem;">Gerencie seus chamados e acompanhe sua performance</p>
        </div>
        <button onclick="openQuickTicketModal()" class="btn btn-success">
            ➕ Abertura Rápida
        </button>
    </div>
    
    <!-- Meus Indicadores -->
    <div class="grid grid-4" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-value"><?= count($myOpenTickets) ?></div>
            <div class="stat-label">Meus Chamados Abertos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #10b981;"><?= $myMetrics['closed_today'] ?? 0 ?></div>
            <div class="stat-label">Fechados Hoje</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #3b82f6;"><?= number_format($myMetrics['efficiency'] ?? 0, 0) ?>%</div>
            <div class="stat-label">Minha Eficiência</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: #8b5cf6;"><?= number_format($myMetrics['score'] ?? 0, 0) ?></div>
            <div class="stat-label">Meu Score</div>
        </div>
    </div>
    
    <!-- Atendimento em Curso -->
    <div id="current-attendance" class="card" style="margin-bottom: 2rem; border: 2px solid #3b82f6; display: none;">
        <div class="card-header">
            <h2 class="card-title">⏱️ Atendimento em Curso</h2>
            <span id="attendance-timer" style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;">00:00:00</span>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <strong>Chamado:</strong> <span id="current-ticket-title">-</span>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <strong>Solicitante:</strong> <span id="current-requester">-</span>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <strong>Início:</strong> <span id="current-start-time">-</span>
            </div>
        </div>
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
            <button onclick="pauseAttendance()" class="btn btn-warning">⏸️ Pausar</button>
            <button onclick="openCloseModal()" class="btn btn-success">✅ Finalizar</button>
            <button onclick="registerLunch()" class="btn btn-outline">🍽️ Almoço</button>
            <button onclick="registerExternal()" class="btn btn-outline">🚗 Atendimento Externo</button>
        </div>
    </div>
    
    <!-- Meus Chamados -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">📋 Meus Chamados</h2>
            <span class="badge badge-blue"><?= count($myOpenTickets) ?> abertos</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Criado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($myTickets)): ?>
                        <?php foreach ($myTickets as $ticket): ?>
                        <tr>
                            <td><strong>#<?= $ticket['id'] ?? 'N/A' ?></strong></td>
                            <td><?= ViewHelper::escape($ticket['title'] ?? 'Sem título') ?></td>
                            <td>
                                <span class="badge <?= ViewHelper::statusClass($ticket['status'] ?? '') ?>">
                                    <?= ViewHelper::statusIcon($ticket['status'] ?? '') ?> <?= ucfirst($ticket['status'] ?? 'desconhecido') ?>
                                </span>
                            </td>
                            <td><?= ViewHelper::priorityBadge($ticket['priority'] ?? 1) ?></td>
                            <td><?= ViewHelper::formatDate($ticket['created_at'] ?? '') ?></td>
                            <td>
                                <?php if ($ticket['status'] === 'open' || $ticket['status'] === 'assigned'): ?>
                                    <button onclick="startAttendance(<?= $ticket['id'] ?>)" class="btn btn-primary" style="padding: 0.375rem 0.75rem; font-size: 0.75rem;">
                                        ▶️ Iniciar
                                    </button>
                                <?php elseif ($ticket['status'] === 'in_progress'): ?>
                                    <button onclick="viewTicket(<?= $ticket['id'] ?>)" class="btn btn-outline" style="padding: 0.375rem 0.75rem; font-size: 0.75rem;">
                                        👁️ Ver
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">Nenhum chamado atribuído</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Minhas Tarefas Periódicas -->
    <?php if (!empty($myTasks)): ?>
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 class="card-title">📋 Minhas Tarefas Periódicas</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Tarefa</th>
                        <th>Frequência</th>
                        <th>Status</th>
                        <th>Vencimento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myTasks as $task): ?>
                    <tr>
                        <td><strong><?= ViewHelper::escape($task['title'] ?? 'Sem título') ?></strong></td>
                        <td>
                            <span class="badge badge-blue"><?= ucfirst($task['frequency'] ?? 'diária') ?></span>
                        </td>
                        <td>
                            <?php if ($task['status'] == 'pending'): ?>
                                <span class="badge badge-yellow">Pendente</span>
                            <?php elseif ($task['status'] == 'in_progress'): ?>
                                <span class="badge badge-purple">Em Andamento</span>
                            <?php else: ?>
                                <span class="badge badge-green">Concluída</span>
                            <?php endif; ?>
                        </td>
                        <td><?= ViewHelper::formatDate($task['due_date'] ?? '') ?></td>
                        <td>
                            <?php if ($task['status'] === 'pending'): ?>
                                <button onclick="startTask(<?= $task['id'] ?>)" class="btn btn-primary" style="padding: 0.375rem 0.75rem; font-size: 0.75rem;">
                                    Iniciar
                                </button>
                            <?php elseif ($task['status'] === 'in_progress'): ?>
                                <button onclick="completeTask(<?= $task['id'] ?>)" class="btn btn-success" style="padding: 0.375rem 0.75rem; font-size: 0.75rem;">
                                    Concluir
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Abertura Rápida -->
<div id="quick-ticket-modal" class="modal hidden" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 90%; max-width: 500px;">
        <h2 style="margin-bottom: 1.5rem;">➕ Abertura Rápida de Chamado</h2>
        <form id="quick-ticket-form">
            <div class="form-group">
                <label class="form-label">Solicitante *</label>
                <select name="requester_id" class="form-control" required>
                    <option value="">Selecione...</option>
                    <!-- Preenchido via AJAX -->
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Título *</label>
                <input type="text" name="title" class="form-control" required placeholder="Resumo do problema">
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Descrição detalhada..."></textarea>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeQuickTicketModal()" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Criar Chamado</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Fechamento -->
<div id="close-ticket-modal" class="modal hidden" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 90%; max-width: 500px;">
        <h2 style="margin-bottom: 1.5rem;">✅ Finalizar Atendimento</h2>
        <form id="close-ticket-form">
            <input type="hidden" name="ticket_id" id="close-ticket-id">
            <div class="form-group">
                <label class="form-label">Solução Executada *</label>
                <textarea name="solution" class="form-control" rows="5" required placeholder="Descreva as atividades executadas..."></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Avaliação da Eficiência</label>
                <select name="rating" class="form-control">
                    <option value="5">⭐⭐⭐⭐⭐ - Excelente</option>
                    <option value="4">⭐⭐⭐⭐ - Muito Bom</option>
                    <option value="3" selected>⭐⭐⭐ - Bom</option>
                    <option value="2">⭐⭐ - Regular</option>
                    <option value="1">⭐ - Ruim</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" onclick="closeCloseModal()" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-success">Finalizar e Fechar</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal { display: flex !important; }
.modal.hidden { display: none !important; }
</style>

<script>
const POLLING_INTERVAL = <?= ($_SESSION['polling_interval'] ?? 30) * 1000 ?>;
let currentAttendanceId = null;
let attendanceTimer = null;
let attendanceStartTime = null;

// Inicializa
document.addEventListener('DOMContentLoaded', () => {
    checkNewTickets();
    checkCurrentAttendance();
    setInterval(checkNewTickets, POLLING_INTERVAL);
    setInterval(checkCurrentAttendance, 5000);
});

// Polling de novos chamados com alerta sonoro
async function checkNewTickets() {
    try {
        const response = await fetch('/index.php?route=tecnico_new_tickets');
        const result = await response.json();
        
        if (result.success && result.count > 0) {
            // Toca som de alerta
            playAlertSound();
            
            // Mostra notificação
            const alert = document.getElementById('new-ticket-alert');
            const message = document.getElementById('new-ticket-message');
            message.textContent = `${result.count} novo(s) chamado(s) atribuído(s)!`;
            alert.classList.remove('hidden');
            
            // Auto-hide após 10 segundos
            setTimeout(() => alert.classList.add('hidden'), 10000);
            
            // Recarrega página para mostrar novos chamados
            setTimeout(() => location.reload(), 2000);
        }
    } catch (error) {
        console.error('Erro ao verificar novos chamados:', error);
    }
}

// Som de alerta
function playAlertSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 800;
    oscillator.type = 'sine';
    gainNode.gain.value = 0.3;
    
    oscillator.start();
    setTimeout(() => oscillator.stop(), 200);
    setTimeout(() => {
        const osc2 = audioContext.createOscillator();
        const gain2 = audioContext.createGain();
        osc2.connect(gain2);
        gain2.connect(audioContext.destination);
        osc2.frequency.value = 1000;
        osc2.type = 'sine';
        gain2.gain.value = 0.3;
        osc2.start();
        setTimeout(() => osc2.stop(), 200);
    }, 300);
}

// Verifica atendimento atual
async function checkCurrentAttendance() {
    try {
        const response = await fetch('/index.php?route=tecnico_current_attendance');
        const result = await response.json();
        
        if (result.success && result.data.active) {
            document.getElementById('current-attendance').style.display = 'block';
            currentAttendanceId = result.data.tracking_id;
            attendanceStartTime = new Date(result.data.start_time).getTime();
            
            document.getElementById('current-ticket-title').textContent = result.data.ticket_title || '-';
            document.getElementById('current-requester').textContent = result.data.requester || '-';
            document.getElementById('current-start-time').textContent = new Date(result.data.start_time).toLocaleTimeString('pt-BR');
            
            // Inicia timer
            if (attendanceTimer) clearInterval(attendanceTimer);
            attendanceTimer = setInterval(updateTimer, 1000);
            updateTimer();
        } else {
            document.getElementById('current-attendance').style.display = 'none';
            if (attendanceTimer) clearInterval(attendanceTimer);
        }
    } catch (error) {
        console.error('Erro ao verificar atendimento:', error);
    }
}

function updateTimer() {
    if (!attendanceStartTime) return;
    const diff = Math.floor((Date.now() - attendanceStartTime) / 1000);
    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    const seconds = diff % 60;
    document.getElementById('attendance-timer').textContent = 
        `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Inicia atendimento
async function startAttendance(ticketId) {
    if (!confirm('Iniciar atendimento deste chamado?')) return;
    
    const formData = new FormData();
    formData.append('ticket_id', ticketId);
    
    try {
        const response = await fetch('/index.php?route=tecnico_start', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            alert('Atendimento iniciado! O cronômetro começou.');
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao iniciar atendimento');
    }
}

// Pausa atendimento
async function pauseAttendance() {
    const reason = prompt('Motivo da pausa:', 'Pausa técnica');
    if (!reason) return;
    
    const formData = new FormData();
    formData.append('tracking_id', currentAttendanceId);
    formData.append('reason', reason);
    
    try {
        const response = await fetch('/index.php?route=tecnico_pause', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            alert('Atendimento pausado. Clique em "Retomar" quando voltar.');
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao pausar atendimento');
    }
}

// Abre modal de fechamento
function openCloseModal() {
    document.getElementById('close-ticket-modal').classList.remove('hidden');
}

function closeCloseModal() {
    document.getElementById('close-ticket-modal').classList.add('hidden');
}

// Modal abertura rápida
function openQuickTicketModal() {
    document.getElementById('quick-ticket-modal').classList.remove('hidden');
    loadUsers();
}

function closeQuickTicketModal() {
    document.getElementById('quick-ticket-modal').classList.add('hidden');
}

async function loadUsers() {
    // Carregar usuários para select (implementar conforme API GLPI)
    const select = document.querySelector('#quick-ticket-form select');
    select.innerHTML = '<option value="">Carregando...</option>';
    // TODO: Implementar carregamento de usuários do GLPI
    select.innerHTML = '<option value="1">Usuário Teste</option>';
}

// Form handlers
document.getElementById('quick-ticket-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/index.php?route=tecnico_quick_open', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            alert('Chamado criado com sucesso! ID: ' + (result.data.id || 'N/A'));
            closeQuickTicketModal();
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao criar chamado');
    }
});

document.getElementById('close-ticket-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/index.php?route=tecnico_close', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            alert('Chamado fechado com sucesso!');
            closeCloseModal();
            location.reload();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao fechar chamado');
    }
});

function viewTicket(id) {
    window.location.href = '/index.php?route=tecnico&ticket=' + id;
}

function registerLunch() {
    // Implementar registro de almoço
    alert('Função de almoço será implementada');
}

function registerExternal() {
    const desc = prompt('Descrição do atendimento externo:');
    if (!desc) return;
    // Implementar registro
    alert('Atendimento externo registrado: ' + desc);
}

function startTask(taskId) {
    alert('Iniciar tarefa ' + taskId);
}

function completeTask(taskId) {
    alert('Completar tarefa ' + taskId);
}
</script>
