<?php
/**
 * View de Login
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão TI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --danger-color: #ef4444;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            font-size: 0.75rem;
        }
        
        .spinner {
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🎯</div>
            <h1 class="login-title">Gestão TI</h1>
            <p class="login-subtitle">Sistema de Controle de Atendimentos</p>
        </div>
        
        <?php if (isset($_GET['logout'])): ?>
        <div class="alert alert-info">
            ✓ Você saiu do sistema com sucesso.
        </div>
        <?php endif; ?>
        
        <div id="error-alert" class="alert alert-error hidden"></div>
        
        <form id="login-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \Core\MVC\View\ViewHelper::csrfToken() ?>">
            
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="Digite seu usuário"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Senha</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Digite sua senha"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="btn-login" id="btn-login">
                <span>Entrar</span>
                <span class="spinner hidden" id="login-spinner"></span>
            </button>
        </form>
        
        <p class="footer-text">
            Usuário inicial: <strong>admin</strong> | Senha: <strong>gestaoti2024</strong>
        </p>
    </div>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btn-login');
            const spinner = document.getElementById('login-spinner');
            const errorAlert = document.getElementById('error-alert');
            const formData = new FormData(this);
            
            btn.disabled = true;
            btn.querySelector('span:first-child').textContent = 'Entrando...';
            spinner.classList.remove('hidden');
            errorAlert.classList.add('hidden');
            
            try {
                const response = await fetch('/index.php?route=authenticate', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (response.ok && data.success) {
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.error || 'Erro ao fazer login');
                }
            } catch (error) {
                errorAlert.textContent = '❌ ' + error.message;
                errorAlert.classList.remove('hidden');
                btn.disabled = false;
                btn.querySelector('span:first-child').textContent = 'Entrar';
                spinner.classList.add('hidden');
            }
        });
        
        // Limpa erro ao digitar
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', () => {
                document.getElementById('error-alert').classList.add('hidden');
            });
        });
    </script>
</body>
</html>
