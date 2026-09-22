<?php
/**
 * View de Configurações do Sistema
 */
$successMsg = $_SESSION['success_message'] ?? '';
$errorMsg = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #2c3e50; color: white; padding: 20px; }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; }
        .card { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h2 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group small { display: block; margin-top: 5px; color: #777; }
        .btn { display: inline-block; padding: 12px 24px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; }
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .test-btn { background: #f39c12; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-left: 10px; }
        #testResult { margin-top: 10px; padding: 10px; border-radius: 4px; display: none; }
        .actions { margin-top: 20px; text-align: right; }
        .actions .btn { margin-left: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ Configurações do Sistema</h1>
    </div>
    
    <div class="container">
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="?route=config_save">
            <!-- Configuração GLPI -->
            <div class="card">
                <h2>🔗 Integração GLPI</h2>
                
                <div class="form-group">
                    <label for="glpi_url">URL do GLPI:</label>
                    <input type="url" id="glpi_url" name="glpi_url" value="<?= htmlspecialchars($glpiConfig['glpi_url'] ?? '') ?>" placeholder="https://seu-glpi.com" required>
                    <small>URL completa do seu servidor GLPI (ex: https://glpi.empresa.com)</small>
                    <button type="button" class="test-btn" onclick="testGLPI()">Testar Conexão</button>
                    <div id="testResult"></div>
                </div>
                
                <div class="form-group">
                    <label for="glpi_token">App Token GLPI:</label>
                    <input type="text" id="glpi_token" name="glpi_token" value="<?= htmlspecialchars($glpiConfig['app_token'] ?? '') ?>" placeholder="Token de aplicativo">
                    <small>Token da API REST do GLPI (obtido nas configurações do GLPI)</small>
                </div>
                
                <div class="form-group">
                    <label for="glpi_user_token">User Token (opcional):</label>
                    <input type="text" id="glpi_user_token" name="glpi_user_token" value="<?= htmlspecialchars($glpiConfig['user_token'] ?? '') ?>" placeholder="Token do usuário">
                    <small>Token pessoal para autenticação adicional</small>
                </div>
            </div>
            
            <!-- Parâmetros do Sistema -->
            <div class="card">
                <h2>📊 Parâmetros do Sistema</h2>
                
                <div class="form-group">
                    <label for="sync_interval">Intervalo de Sincronismo GLPI (segundos):</label>
                    <input type="number" id="sync_interval" name="sync_interval" value="<?= htmlspecialchars($settings['sync_interval'] ?? 300) ?>" min="60" max="3600" required>
                    <small>Tempo entre sincronizações com o GLPI (padrão: 300s = 5min)</small>
                </div>
                
                <div class="form-group">
                    <label for="notification_polling">Polling de Notificações (segundos):</label>
                    <input type="number" id="notification_polling" name="notification_polling" value="<?= htmlspecialchars($settings['notification_polling'] ?? 30) ?>" min="10" max="120" required>
                    <small>Frequência de verificação de novos chamados (padrão: 30s)</small>
                </div>
                
                <div class="form-group">
                    <label for="escalation_time">Tempo para Escalonamento (minutos):</label>
                    <input type="number" id="escalation_time" name="escalation_time" value="<?= htmlspecialchars($settings['escalation_time'] ?? 120) ?>" min="30" max="480" required>
                    <small>Tempo de ausência do técnico para repasse de tarefas (padrão: 120min)</small>
                </div>
                
                <div class="form-group">
                    <label for="default_category_id">Categoria Padrão GLPI:</label>
                    <input type="text" id="default_category_id" name="default_category_id" value="<?= htmlspecialchars($settings['default_category_id'] ?? '') ?>" placeholder="ID da categoria">
                    <small>ID da categoria do GLPI para abertura rápida de chamados</small>
                </div>
                
                <div class="form-group">
                    <label for="lunch_break_duration">Duração do Almoço (minutos):</label>
                    <input type="number" id="lunch_break_duration" name="lunch_break_duration" value="<?= htmlspecialchars($settings['lunch_break_duration'] ?? 60) ?>" min="30" max="180" required>
                    <small>Tempo padrão para pausa de almoço (padrão: 60min)</small>
                </div>
            </div>
            
            <div class="actions">
                <a href="?route=dashboard" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
            </div>
        </form>
    </div>
    
    <script>
    function testGLPI() {
        const url = document.getElementById('glpi_url').value;
        const token = document.getElementById('glpi_token').value;
        const resultDiv = document.getElementById('testResult');
        
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = 'Testando conexão...';
        resultDiv.style.background = '#fff3cd';
        
        fetch('?route=config_test_glpi', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({glpi_url: url, glpi_token: token})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '✅ ' + data.message;
                resultDiv.style.background = '#d4edda';
            } else {
                resultDiv.innerHTML = '❌ ' + data.message;
                resultDiv.style.background = '#f8d7da';
            }
        })
        .catch(err => {
            resultDiv.innerHTML = '❌ Erro na requisição: ' + err.message;
            resultDiv.style.background = '#f8d7da';
        });
    }
    </script>
</body>
</html>
