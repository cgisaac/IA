<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Gestão TI</title>
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .config-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 1.2rem;
        }
        
        .section-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.85rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-connected {
            background: #d4edda;
            color: #155724;
        }
        
        .status-disconnected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>⚙️ Configurações do Sistema</h1>
            <p style="opacity: 0.9; margin-top: 0.25rem;">Gerencie as configurações do sistema e integração GLPI</p>
        </div>
        <a href="?route=dashboard" class="btn-back">← Voltar</a>
    </div>
    
    <div class="content">
        <?php if(isset($data['success'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($data['success']) ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($data['error'])): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($data['error']) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="?route=config&action=save">
            <!-- Configurações GLPI -->
            <div class="config-section">
                <div class="section-header">
                    <h2>🔗 Integração GLPI</h2>
                    <span class="status-badge <?= $data['glpi_connected'] ? 'status-connected' : 'status-disconnected' ?>">
                        <?= $data['glpi_connected'] ? '✓ Conectado' : '✗ Desconectado' ?>
                    </span>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label for="glpi_url">URL do GLPI *</label>
                        <input type="url" id="glpi_url" name="glpi_url" 
                               value="<?= htmlspecialchars($data['settings']['glpi_url'] ?? '') ?>" 
                               placeholder="https://glpi.seudominio.com.br" required>
                        <small>URL completa de acesso ao GLPI (ex: https://glpi.exemplo.com.br)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="glpi_token">Token da API *</label>
                        <input type="text" id="glpi_token" name="glpi_token" 
                               value="<?= htmlspecialchars($data['settings']['glpi_token'] ?? '') ?>" 
                               placeholder="Token de autenticação da API" required>
                        <small>Token gerado no GLPI em: Administração > APIs</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="glpi_app_token">App Token (opcional)</label>
                        <input type="text" id="glpi_app_token" name="glpi_app_token" 
                               value="<?= htmlspecialchars($data['settings']['glpi_app_token'] ?? '') ?>" 
                               placeholder="App Token da API">
                    </div>
                    
                    <div class="form-group">
                        <label for="default_category_id">Categoria Padrão para Chamados Rápidos</label>
                        <input type="number" id="default_category_id" name="default_category_id" 
                               value="<?= htmlspecialchars($data['settings']['default_category_id'] ?? '') ?>" 
                               placeholder="ID da categoria no GLPI">
                        <small>ID da categoria do GLPI para chamados abertos rapidamente (deixe vazio para usar padrão)</small>
                    </div>
                </div>
            </div>
            
            <!-- Configurações de Tempo -->
            <div class="config-section">
                <div class="section-header">
                    <h2>⏱️ Configurações de Tempo</h2>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label for="sync_interval">Intervalo de Sincronismo com GLPI (segundos) *</label>
                        <input type="number" id="sync_interval" name="sync_interval" 
                               value="<?= htmlspecialchars($data['settings']['sync_interval'] ?? 300) ?>" 
                               min="60" max="3600" required>
                        <small>Tempo entre sincronizações com o GLPI (padrão: 300 segundos = 5 minutos)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification_polling">Polling de Notificações (segundos) *</label>
                        <input type="number" id="notification_polling" name="notification_polling" 
                               value="<?= htmlspecialchars($data['settings']['notification_polling'] ?? 30) ?>" 
                               min="10" max="300" required>
                        <small>Frequência de verificação de novos chamados (padrão: 30 segundos)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="escalation_time">Tempo para Escalonamento (minutos) *</label>
                        <input type="number" id="escalation_time" name="escalation_time" 
                               value="<?= htmlspecialchars($data['settings']['escalation_time'] ?? 120) ?>" 
                               min="15" max="1440" required>
                        <small>Tempo sem atividade para escalonar tarefa (padrão: 120 minutos)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="lunch_break">Duração do Almoço (minutos)</label>
                        <input type="number" id="lunch_break" name="lunch_break" 
                               value="<?= htmlspecialchars($data['settings']['lunch_break'] ?? 60) ?>" 
                               min="15" max="180">
                        <small>Duração padrão do intervalo de almoço (padrão: 60 minutos)</small>
                    </div>
                </div>
            </div>
            
            <!-- Configurações do Sistema -->
            <div class="config-section">
                <div class="section-header">
                    <h2>🖥️ Configurações do Sistema</h2>
                </div>
                <div class="section-body">
                    <div class="form-group">
                        <label for="system_name">Nome do Sistema</label>
                        <input type="text" id="system_name" name="system_name" 
                               value="<?= htmlspecialchars($data['settings']['system_name'] ?? 'Gestão TI') ?>" 
                               placeholder="Gestão TI">
                    </div>
                    
                    <button type="submit" class="btn-save">💾 Salvar Configurações</button>
                </div>
            </div>
        </form>
        
        <!-- Testar Conexão GLPI -->
        <div class="config-section">
            <div class="section-header">
                <h2>🧪 Testar Conexão GLPI</h2>
            </div>
            <div class="section-body">
                <p style="margin-bottom: 1rem; color: #666;">
                    Clique no botão abaixo para testar a conexão com o GLPI usando as configurações atuais.
                </p>
                <a href="?route=config&action=test_glpi" class="btn-save" style="display: inline-block; text-decoration: none;">
                    🔄 Testar Conexão Agora
                </a>
            </div>
        </div>
    </div>
</body>
</html>
