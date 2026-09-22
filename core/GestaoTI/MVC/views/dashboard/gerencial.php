<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gerencial - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-title h1 { font-size: 1.5rem; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .dashboard-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card .trend {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .trend.up { color: #27ae60; }
        .trend.down { color: #e74c3c; }
        
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .insights-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .insight-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .insight-item:last-child { border-bottom: none; }
        
        .insight-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        @media (max-width: 768px) {
            .charts-section { grid-template-columns: 1fr; }
            .dashboard-header { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>📊 Dashboard Gerencial</h1>
        </div>
        <div class="user-info">
            <span>Olá, <?= htmlspecialchars($data['username'] ?? 'Administrador') ?></span>
            <button class="btn-logout" onclick="window.location.href='?route=logout'">Sair</button>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Chamados Abertos</h3>
                <div class="value"><?= $data['stats']['open_tickets'] ?? 0 ?></div>
                <div class="trend up">↑ 12% vs semana anterior</div>
            </div>
            <div class="stat-card">
                <h3>Tempo Médio Atendimento</h3>
                <div class="value"><?= $data['stats']['avg_resolution_time'] ?? '0h' ?></div>
                <div class="trend down">↓ 8% melhoria</div>
            </div>
            <div class="stat-card">
                <h3>Satisfação Clientes</h3>
                <div class="value"><?= $data['stats']['satisfaction_rate'] ?? '0%' ?></div>
                <div class="trend up">↑ 5% vs mês anterior</div>
            </div>
            <div class="stat-card">
                <h3>Técnicos Ativos</h3>
                <div class="value"><?= $data['stats']['active_technicians'] ?? 0 ?></div>
                <div class="trend">Hoje</div>
            </div>
        </div>
        
        <div class="charts-section">
            <div class="chart-card">
                <h3>📈 Evolução de Chamados (Últimos 7 dias)</h3>
                <div id="tickets-chart" style="height: 300px; display: flex; align-items: flex-end; gap: 10px; padding: 20px 0;">
                    <?php for($i = 0; $i < 7; $i++): ?>
                        <div style="flex: 1; background: linear-gradient(to top, #667eea, #764ba2); border-radius: 5px 5px 0 0; height: <?= rand(30, 100) ?>%; position: relative;">
                            <span style="position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; color: #666;">
                                <?= date('D', strtotime("-$i days")) ?>
                            </span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>🎯 Distribuição por Categoria</h3>
                <div style="padding: 1rem 0;">
                    <?php 
                    $categories = ['Hardware', 'Software', 'Rede', 'Acesso', 'Outros'];
                    foreach($categories as $cat): 
                    ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; padding: 0.5rem; background: #f8f9fa; border-radius: 5px;">
                            <span><?= $cat ?></span>
                            <strong><?= rand(5, 30) ?>%</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="insights-section">
            <h3>💡 Insights Automáticos</h3>
            <?php if(!empty($data['insights'])): ?>
                <?php foreach($data['insights'] as $insight): ?>
                    <div class="insight-item">
                        <span class="insight-badge badge-<?= $insight['type'] ?? 'info' ?>">
                            <?= strtoupper($insight['type'] ?? 'INFO') ?>
                        </span>
                        <strong><?= htmlspecialchars($insight['title']) ?></strong>
                        <p style="margin-top: 0.5rem; color: #666;"><?= htmlspecialchars($insight['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="insight-item">
                    <span class="insight-badge badge-info">INFO</span>
                    <strong>Nenhum insight gerado no momento</strong>
                    <p style="margin-top: 0.5rem; color: #666;">Os insights aparecerão aqui conforme a análise dos dados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
