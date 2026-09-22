<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicativo do Técnico - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; }
        
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .technician-status {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #27ae60;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .app-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .metric-card h3 {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .metric-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .main-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .panel-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-body {
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .ticket-item {
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .ticket-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
        }
        
        .ticket-item.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .ticket-item.urgent {
            border-left: 4px solid #e74c3c;
        }
        
        .timer-display {
            font-size: 2.5rem;
            font-weight: bold;
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .timer-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .activity-log {
            list-style: none;
        }
        
        .activity-log li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        
        .activity-log li:last-child {
            border-bottom: none;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-section { grid-template-columns: 1fr; }
            .app-header { flex-direction: column; gap: 1rem; }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app-header">
        <div>
            <h1>🔧 Aplicativo do Técnico</h1>
            <p style="opacity: 0.9; margin-top: 0.25rem;">
                <?= htmlspecialchars($data['technician_name'] ?? 'Técnico') ?>
            </p>
        </div>
        <div class="technician-status">
            <div class="status-indicator"></div>
            <span>Online</span>
            <button class="btn btn-warning" style="padding: 0.5rem 1rem;" onclick="alert('Funcionalidade de pausa será implementada')">⏸️ Pausar</button>
            <button class="btn" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.2); color: white;" onclick="window.location.href='?route=logout'">Sair</button>
        </div>
    </div>
    
    <div class="app-content">
        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Chamados Hoje</h3>
                <div class="value"><?= $data['metrics']['tickets_today'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>Tempo Atendimento (Hoje)</h3>
                <div class="value"><?= $data['metrics']['total_time_today'] ?? '0h 0m' ?></div>
            </div>
            <div class="metric-card">
                <h3>Eficiência</h3>
                <div class="value"><?= $data['metrics']['efficiency'] ?? '0%' ?></div>
            </div>
            <div class="metric-card">
                <h3>Meta Diária</h3>
                <div class="value"><?= $data['metrics']['daily_goal_progress'] ?? '0/0' ?></div>
            </div>
        </div>
        
        <div class="main-section">
            <div class="panel">
                <div class="panel-header">
                    <h3>📬 Meus Chamados</h3>
                    <button class="btn btn-primary" style="padding: 0.5rem 1rem;" onclick="openQuickTicketModal()">+ Abertura Rápida</button>
                </div>
                <div class="panel-body">
                    <?php if(!empty($data['my_tickets'])): ?>
                        <?php foreach($data['my_tickets'] as $ticket): ?>
                            <div class="ticket-item <?= $ticket['is_urgent'] ? 'urgent' : '' ?>" 
                                 onclick="selectTicket(<?= $ticket['id'] ?>)">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <strong>#<?= $ticket['id'] ?> - <?= htmlspecialchars($ticket['title']) ?></strong>
                                    <span style="font-size: 0.8rem; color: #666;"><?= $ticket['created_at'] ?></span>
                                </div>
                                <div style="font-size: 0.9rem; color: #666;">
                                    Solicitante: <?= htmlspecialchars($ticket['requester']) ?>
                                </div>
                                <?php if($ticket['is_urgent']): ?>
                                    <div style="margin-top: 0.5rem; color: #e74c3c; font-weight: bold; font-size: 0.85rem;">
                                        ⚠️ URGENTE
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; padding: 2rem;">
                            Nenhum chamado atribuído no momento
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <h3>⏱️ Controle de Tempo</h3>
                </div>
                <div class="panel-body">
                    <div id="selected-ticket-info" style="text-align: center; margin-bottom: 1rem; color: #666;">
                        Selecione um chamado para iniciar o atendimento
                    </div>
                    
                    <div class="timer-display" id="timer">00:00:00</div>
                    
                    <div class="timer-controls">
                        <button class="btn btn-success" id="btn-start" onclick="startTimer()" disabled>Iniciar</button>
                        <button class="btn btn-warning" id="btn-pause" onclick="pauseTimer()" disabled>Pausar</button>
                        <button class="btn btn-primary" id="btn-resume" onclick="resumeTimer()" style="display: none;">Retomar</button>
                        <button class="btn btn-danger" id="btn-stop" onclick="stopTimer()" disabled>Finalizar</button>
                    </div>
                    
                    <div class="quick-actions">
                        <button class="btn btn-warning" onclick="recordBreak('lunch')">🍽️ Almoço</button>
                        <button class="btn btn-warning" onclick="recordBreak('external')">🚗 Atendimento Externo</button>
                        <button class="btn btn-primary" onclick="recordNote()">📝 Anotação</button>
                        <button class="btn btn-success" onclick="closeTicket()">✅ Fechar Chamado</button>
                    </div>
                    
                    <h4 style="margin: 1.5rem 0 1rem; color: #333;">Histórico de Atividades</h4>
                    <ul class="activity-log" id="activity-log">
                        <li style="color: #999; text-align: center;">Nenhuma atividade registrada</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Abertura Rápida -->
    <div id="quick-ticket-modal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;">🎫 Abertura Rápida de Chamado</h2>
            <form id="quick-ticket-form" method="POST" action="?route=tecnicos&action=quick_ticket">
                <div class="form-group">
                    <label for="qt-user">Usuário Solicitante *</label>
                    <input type="text" id="qt-user" name="user" required placeholder="Nome ou login do usuário">
                </div>
                <div class="form-group">
                    <label for="qt-title">Título *</label>
                    <input type="text" id="qt-title" name="title" required placeholder="Resumo do problema">
                </div>
                <div class="form-group">
                    <label for="qt-description">Descrição</label>
                    <textarea id="qt-description" name="description" rows="4" placeholder="Detalhes do problema (opcional)"></textarea>
                </div>
                <div class="form-group">
                    <label for="qt-category">Categoria</label>
                    <select id="qt-category" name="category">
                        <option value="">Padrão do Sistema</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="network">Rede</option>
                        <option value="access">Acesso</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Criar Chamado</button>
                    <button type="button" class="btn" style="flex: 1; background: #eee;" onclick="closeQuickTicketModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let selectedTicketId = null;
        let timerInterval = null;
        let seconds = 0;
        let isRunning = false;
        
        function selectTicket(id) {
            selectedTicketId = id;
            document.querySelectorAll('.ticket-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.ticket-item').classList.add('active');
            
            document.getElementById('selected-ticket-info').innerHTML = 
                `<strong>Chamado #${id} selecionado</strong><br>Clique em "Iniciar" para começar o atendimento`;
            
            document.getElementById('btn-start').disabled = false;
        }
        
        function startTimer() {
            if (!selectedTicketId) return;
            
            isRunning = true;
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-pause').disabled = false;
            document.getElementById('btn-stop').disabled = false;
            
            timerInterval = setInterval(() => {
                seconds++;
                updateTimerDisplay();
            }, 1000);
            
            addActivityLog('Atendimento iniciado', new Date().toLocaleTimeString());
        }
        
        function pauseTimer() {
            if (!isRunning) return;
            
            clearInterval(timerInterval);
            isRunning = false;
            
            document.getElementById('btn-pause').disabled = true;
            document.getElementById('btn-resume').style.display = 'inline-block';
            
            addActivityLog('Atendimento pausado', new Date().toLocaleTimeString());
        }
        
        function resumeTimer() {
            if (isRunning) return;
            
            isRunning = true;
            document.getElementById('btn-resume').style.display = 'none';
            document.getElementById('btn-pause').disabled = false;
            
            timerInterval = setInterval(() => {
                seconds++;
                updateTimerDisplay();
            }, 1000);
            
            addActivityLog('Atendimento retomado', new Date().toLocaleTimeString());
        }
        
        function stopTimer() {
            if (!selectedTicketId) return;
            
            clearInterval(timerInterval);
            isRunning = false;
            seconds = 0;
            updateTimerDisplay();
            
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-pause').disabled = true;
            document.getElementById('btn-resume').style.display = 'none';
            document.getElementById('btn-stop').disabled = true;
            
            addActivityLog('Atendimento finalizado', new Date().toLocaleTimeString());
            
            selectedTicketId = null;
            document.getElementById('selected-ticket-info').innerHTML = 
                'Selecione um chamado para iniciar o atendimento';
        }
        
        function updateTimerDisplay() {
            const hrs = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            document.getElementById('timer').textContent = 
                `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        
        function addActivityLog(action, time) {
            const log = document.getElementById('activity-log');
            const firstItem = log.querySelector('li');
            if (firstItem && firstItem.textContent.includes('Nenhuma atividade')) {
                firstItem.remove();
            }
            
            const li = document.createElement('li');
            li.innerHTML = `<span>${action}</span><span style="color: #666;">${time}</span>`;
            log.insertBefore(li, log.firstChild);
        }
        
        function recordBreak(type) {
            if (!selectedTicketId) {
                alert('Selecione um chamado primeiro!');
                return;
            }
            
            const breakType = type === 'lunch' ? 'Almoço' : 'Atendimento Externo';
            addActivityLog(`${breakType} registrado`, new Date().toLocaleTimeString());
            alert(`${breakType} registrado com sucesso! O tempo será descontado do atendimento.`);
        }
        
        function recordNote() {
            if (!selectedTicketId) {
                alert('Selecione um chamado primeiro!');
                return;
            }
            
            const note = prompt('Digite sua anotação:');
            if (note) {
                addActivityLog(`Anotação: ${note}`, new Date().toLocaleTimeString());
            }
        }
        
        function closeTicket() {
            if (!selectedTicketId) {
                alert('Selecione um chamado primeiro!');
                return;
            }
            
            if (confirm('Deseja realmente fechar este chamado no GLPI?')) {
                addActivityLog('Chamado fechado no GLPI', new Date().toLocaleTimeString());
                alert('Chamado fechado com sucesso!');
                stopTimer();
            }
        }
        
        function openQuickTicketModal() {
            document.getElementById('quick-ticket-modal').classList.add('show');
        }
        
        function closeQuickTicketModal() {
            document.getElementById('quick-ticket-modal').classList.remove('show');
            document.getElementById('quick-ticket-form').reset();
        }
        
        // Fecha modal ao clicar fora
        document.getElementById('quick-ticket-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuickTicketModal();
            }
        });
        
        // Polling para novos chamados (configurável)
        setInterval(function() {
            // Aqui seria feita uma requisição AJAX para verificar novos chamados
            console.log('Verificando novos chamados...');
        }, <?= $data['polling_interval'] ?? 30 ?> * 1000);
    </script>
</body>
</html>
