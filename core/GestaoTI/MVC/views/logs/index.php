<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - Gestão TI</title>
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
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logs-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .logs-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-entry {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry.error {
            background: #fee;
            border-left: 4px solid #e74c3c;
        }
        
        .log-entry.info {
            background: #f0f9ff;
            border-left: 4px solid #3498db;
        }
        
        .log-entry.warning {
            background: #fffbea;
            border-left: 4px solid #f39c12;
        }
        
        .log-timestamp {
            color: #666;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .log-level {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .level-error { background: #e74c3c; color: white; }
        .level-info { background: #3498db; color: white; }
        .level-warning { background: #f39c12; color: white; }
        
        .log-message {
            color: #333;
        }
        
        .empty-logs {
            padding: 3rem;
            text-align: center;
            color: #999;
        }
        
        .refresh-info {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.9);
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📋 Logs do Sistema</h1>
            <p class="refresh-info">Última atualização: <?= date('d/m/Y H:i:s') ?></p>
        </div>
        <a href="?route=dashboard" class="btn-back">← Voltar</a>
    </div>
    
    <div class="content">
        <div class="logs-container">
            <div class="logs-header">
                <h2>Arquivo: app.log</h2>
                <span style="color: #666;"><?= count($data['logs'] ?? []) ?> entradas</span>
            </div>
            
            <?php if(!empty($data['logs'])): ?>
                <?php foreach(array_reverse($data['logs']) as $log): ?>
                    <div class="log-entry <?= $log['level_class'] ?? 'info' ?>">
                        <span class="log-timestamp"><?= htmlspecialchars($log['timestamp']) ?></span>
                        <span class="log-level level-<?= $log['level'] ?? 'info' ?>">
                            <?= strtoupper($log['level']) ?>
                        </span>
                        <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-logs">
                    <p>📭 Nenhum log encontrado</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                        Os logs aparecerão aqui conforme o sistema for utilizado.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
