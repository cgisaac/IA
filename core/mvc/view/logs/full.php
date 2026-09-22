<?php
/**
 * View de Logs Completo do Sistema
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Completo - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; margin-bottom: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .btn { display: inline-block; padding: 10px 20px; margin-right: 10px; text-decoration: none; border-radius: 5px; font-weight: bold; background: #3498db; color: white; }
        .log-content { background: #1e1e1e; color: #d4d4d4; border: 1px solid #333; padding: 20px; font-family: 'Courier New', monospace; font-size: 11px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; max-height: 80vh; overflow-y: auto; }
        .level-ERROR, .level-CRITICAL { color: #ff6b6b; }
        .level-WARNING { color: #ffd93d; }
        .level-INFO { color: #6bcb77; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Log Completo do Sistema</h1>
    </div>
    
    <div class="container">
        <div style="margin-bottom: 15px;">
            <a href="?route=logs" class="btn">⬅️ Voltar</a>
            <a href="?route=logs&action=download" class="btn">⬇️ Baixar</a>
        </div>
        
        <div class="log-content">
            <?php if (empty(trim($logs))): ?>
                Nenhum log registrado.
            <?php else: ?>
                <?= nl2br(htmlspecialchars($logs)) ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
