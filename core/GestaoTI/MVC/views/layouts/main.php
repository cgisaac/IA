<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - Gestão TI' : 'Gestão TI' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Gestão TI</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>dashboard/gerencial" class="nav-item <?= ($active ?? '') == 'gerencial' ? 'active' : '' ?>">
                    📊 Dashboard Gerencial
                </a>
                <a href="<?= BASE_URL ?>dashboard/acompanhamento" class="nav-item <?= ($active ?? '') == 'acompanhamento' ? 'active' : '' ?>">
                    🔄 Acompanhamento
                </a>
                <a href="<?= BASE_URL ?>tecnico/app" class="nav-item <?= ($active ?? '') == 'tecnico' ? 'active' : '' ?>">
                    👨‍💻 App Técnico
                </a>
                <?php if ($_SESSION['user_role'] ?? '' === 'admin'): ?>
                <a href="<?= BASE_URL ?>config" class="nav-item <?= ($active ?? '') == 'config' ? 'active' : '' ?>">
                    ⚙️ Configurações
                </a>
                <a href="<?= BASE_URL ?>logs" class="nav-item <?= ($active ?? '') == 'logs' ? 'active' : '' ?>">
                    📋 Logs
                </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
                </div>
                <a href="<?= BASE_URL ?>auth/logout" class="btn-logout">Sair</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="topbar">
                <h1><?= isset($title) ? $title : '' ?></h1>
                <div class="topbar-actions">
                    <span class="last-sync">Última sincronização: <?= date('H:i:s') ?></span>
                </div>
            </header>
            
            <div class="content">
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= BASE_URL ?>assets/js/<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
