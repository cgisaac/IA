# 🎯 Gestão TI - Sistema de Controle de Atendimentos

Sistema de gestão de TI integrado ao GLPI, desenvolvido em **PHP Puro** com arquitetura **MVC** e **POO**, seguindo princípios de **SDDD**.

## 📋 Funcionalidades

### 3 Módulos Principais

#### 1. 📊 Módulo Gerencial (Dashboard)
- Estatísticas e indicadores de TI
- Avaliação de chamados e colaboradores
- Insights automáticos para gestão
- Performance individual por técnico

#### 2. 👁️ Dashboard de Acompanhamento em Tempo Real
- Monitoramento de chamados abertos, em andamento e atrasados
- Polling configurável (padrão 30s)
- Tarefas periódicas com escalonamento
- Timers de atendimento em tempo real

#### 3. 👨‍💻 Aplicativo do Técnico
- Alertas sonoros de novos chamados
- Cronômetro de atendimento (start/pause/resume)
- Registro de almoço e atendimento externo
- Abertura rápida de chamados no GLPI
- Fechamento integrado com solução
- Base de conhecimento espelhada

## 🏗️ Arquitetura

```
/workspace
├── config/              # Configurações
├── core/
│   ├── agent/          # 5 Agentes SDDD
│   ├── database/       # Conexão DB + Migrations
│   └── mvc/            # Controllers, Models, Views, Router
├── public/             # Web root
│   ├── index.php      # Front controller
│   └── assets/        # CSS, JS
└── logs/              # Logs
```

## 🚀 Instalação Rápida

### 1. Configure MySQL
```sql
CREATE DATABASE gestaoti CHARACTER SET utf8mb4;
```

### 2. Edite config/config.php
```php
'db' => [
    'host' => 'localhost',
    'database' => 'gestaoti',
    'username' => 'root',
    'password' => 'sua_senha'
],
'glpi' => [
    'url' => 'https://seu-glpi.local',
    'app_token' => 'token',
    'user_token' => 'token'
]
```

### 3. Aponte servidor web para /workspace/public

### 4. Acesse e login
- URL: `http://localhost/index.php`
- User: `admin`
- Senha: `gestaoti2024`

## ⚙️ Configurações

| Parâmetro | Padrão | Descrição |
|-----------|--------|-----------|
| sync_interval | 300s | Sync GLPI |
| notification_polling | 30s | Polling notificações |
| escalation_time | 120min | Escalonamento |

## 🔐 Segurança
- CSRF protection
- XSS prevention
- SQL injection prevention
- Session timeout

## 📱 Responsivo
Funciona em Desktop, Laptop, Tablet e Mobile

## 🆘 Debug
Habilite em config: `'debug' => true`
Logs em: `/workspace/logs/`

---
**Gestão TI v1.0.0** - PHP Pure MVC © 2024
