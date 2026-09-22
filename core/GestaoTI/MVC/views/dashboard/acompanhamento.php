<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento em Tempo Real - Gestão TI</title>
    <meta http-equiv="refresh" content="<?= $data['polling_interval'] ?? 30 ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .content {
            padding: 2rem;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .summary-card.open { border-left-color: #3498db; }
        .summary-card.closed { border-left-color: #27ae60; }
        .summary-card.late { border-left-color: #e74c3c; }
        .summary-card.assigned { border-left-color: #f39c12; }
        
        .summary-card h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .summary-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .tickets-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .tickets-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tickets-table th,
        .tickets-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .tickets-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .tickets-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-open { background: #d1ecf1; color: #0c5460; }
        .status-closed { background: #d4edda; color: #155724; }
        .status-late { background: #f8d7da; color: #721c24; }
        .status-progress { background: #fff3cd; color: #856404; }
        
        .priority-tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .priority-high { background: #e74c3c; color: white; }
        .priority-medium { background: #f39c12; color: white; }
        .priority-low { background: #95a5a6; color: white; }
        
        .technician-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .last-update {
            text-align: center;
            padding: 1rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .tickets-table { font-size: 0.85rem; }
            .tickets-table th, .tickets-table td { padding: 0.5rem; }
            .header { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📡 Acompanhamento em Tempo Real</h1>
            <p style="opacity: 0.9; margin-top: 0.25rem;">Atualização automática a cada <?= $data['polling_interval'] ?? 30 ?> segundos</p>
        </div>
        <div class="status-badge">
            🟢 Online
        </div>
    </div>
    
    <div class="content">
        <div class="summary-cards">
            <div class="summary-card open">
                <h3>Em Aberto</h3>
                <div class="number"><?= $data['summary']['open'] ?? 0 ?></div>
            </div>
            <div class="summary-card assigned">
                <h3>Atribuídos</h3>
                <div class="number"><?= $data['summary']['assigned'] ?? 0 ?></div>
            </div>
            <div class="summary-card closed">
                <h3>Fechados Hoje</h3>
                <div class="number"><?= $data['summary']['closed_today'] ?? 0 ?></div>
            </div>
            <div class="summary-card late">
                <h3>Atrasados</h3>
                <div class="number"><?= $data['summary']['late'] ?? 0 ?></div>
            </div>
        </div>
        
        <div class="tickets-section">
            <div class="section-header">
                <h2>📋 Todos os Chamados</h2>
                <span style="font-size: 0.9rem; color: #666;">
                    Última atualização: <?= date('H:i:s') ?>
                </span>
            </div>
            
            <table class="tickets-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Solicitante</th>
                        <th>Técnico</th>
                        <th>Status</th>
                        <th>Prioridade</th>
                        <th>Abertura</th>
                        <th>SLA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($data['tickets'])): ?>
                        <?php foreach($data['tickets'] as $ticket): ?>
                            <tr>
                                <td><strong>#<?= $ticket['id'] ?? '-' ?></strong></td>
                                <td><?= htmlspecialchars($ticket['title'] ?? 'Sem título') ?></td>
                                <td><?= htmlspecialchars($ticket['requester'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if(!empty($ticket['technician'])): ?>
                                        <div style="display: flex; align-items: center;">
                                            <div class="technician-avatar">
                                                <?= strtoupper(substr($ticket['technician'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($ticket['technician']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">Não atribuído</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-tag status-<?= $ticket['status_class'] ?? 'open' ?>">
                                        <?= $ticket['status_label'] ?? 'Aberto' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-tag priority-<?= $ticket['priority_class'] ?? 'medium' ?>">
                                        <?= $ticket['priority_label'] ?? 'Média' ?>
                                    </span>
                                </td>
                                <td><?= $ticket['created_date'] ?? '-' ?></td>
                                <td>
                                    <?php if($ticket['is_late'] ?? false): ?>
                                        <span style="color: #e74c3c; font-weight: bold;">⚠️ Atrasado</span>
                                    <?php else: ?>
                                        <?= $ticket['sla_remaining'] ?? '-' ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #999;">
                                Nenhum chamado encontrado
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="last-update">
            <p>🔄 Página será atualizada automaticamente em <?= $data['polling_interval'] ?? 30 ?> segundos</p>
        </div>
    </div>
</body>
</html>
