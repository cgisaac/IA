<?php
/**
 * View de Logs do Sistema
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .header h1 { font-size: 24px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .actions { margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; margin-right: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn:hover { opacity: 0.9; }
        .log-container { background: white; border: 1px solid #ddd; border-radius: 5px; padding: 15px; }
        .log-content { background: #f8f9fa; border: 1px solid #e9ecef; padding: 15px; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 600px; overflow-y: auto; }
        .log-line { padding: 3px 0; border-bottom: 1px solid #eee; }
        .log-line:last-child { border-bottom: none; }
        .level-INFO { color: #27ae60; }
        .level-WARNING { color: #f39c12; }
        .level-ERROR { color: #e74c3c; }
        .level-CRITICAL { color: #c0392b; font-weight: bold; }
        .timestamp { color: #7f8c8d; }
        .info-box { background: #d6eaf8; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Logs do Sistema</h1>
    </div>
    
    <div class="container">
        <div class="info-box">
            <strong>Arquivo de Log:</strong> <?= htmlspecialchars($logFile) ?><br>
            <small>Os logs são gravados automaticamente pelo sistema. Use as ações abaixo para gerenciar.</small>
        </div>
        
        <div class="actions">
            <a href="?route=logs&view=full" class="btn btn-primary">📄 Ver Log Completo</a>
            <a href="?route=logs&action=download" class="btn btn-success">⬇️ Baixar Log</a>
            <a href="?route=logs&action=clear" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar o log?')">🗑️ Limpar Log</a>
            <a href="?route=dashboard" class="btn btn-primary">⬅️ Voltar ao Dashboard</a>
        </div>
        
        <div class="log-container">
            <h2>Últimos 100 registros</h2>
            <div class="log-content">
                <?php if (empty(trim($logs))): ?>
                    <em>Nenhum log registrado ainda.</em>
                <?php else: ?>
                    <?php
                    $lines = explode("\n", $logs);
                    foreach ($lines as $line):
                        if (trim($line) === '') continue;
                        
                        // Extrai nível do log
                        preg_match('/\[(.*?)\] \[(.*?)\]/', $line, $matches);
                        $level = $matches[2] ?? 'INFO';
                        $timestamp = $matches[1] ?? '';
                        
                        $message = str_replace($matches[0], '', $line);
                    ?>
                        <div class="log-line">
                            <span class="timestamp">[<?= htmlspecialchars($timestamp) ?>]</span>
                            <span class="level-<?= htmlspecialchars($level) ?>">[<?= htmlspecialchars($level) ?>]</span>
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
