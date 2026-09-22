/**
 * Gestão TI - JavaScript Principal
 * Funções utilitárias e configurações globais
 */

// Configurações globais
const App = {
    config: {
        apiUrl: '/public/api/',
        pollingInterval: 30000,
        debug: false
    },
    
    // Notificação toast
    notify: function(message, type = 'info') {
        const colors = {
            info: '#3b82f6',
            success: '#10b981',
            warning: '#f59e0b',
            error: '#ef4444'
        };
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || colors.info};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },
    
    // Loading overlay
    showLoading: function(message = 'Carregando...') {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        `;
        overlay.innerHTML = `
            <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center;">
                <div class="spinner" style="width: 48px; height: 48px; border-width: 4px; margin: 0 auto 1rem;"></div>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    },
    
    hideLoading: function() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) overlay.remove();
    },
    
    // Confirmação
    confirm: function(message) {
        return new Promise((resolve) => {
            if (confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    },
    
    // Formatadores
    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },
    
    formatDate: function(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString('pt-BR');
    },
    
    formatDateTime: function(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleString('pt-BR');
    },
    
    formatDuration: function(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
};

// Adiciona animações CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Console de boas-vindas
console.log('%c🎯 Gestão TI', 'font-size: 24px; font-weight: bold; color: #2563eb;');
console.log('%cSistema de Controle de Atendimentos de TI', 'font-size: 12px; color: #64748b;');
console.log('%cVersão 1.0.0 - PHP Pure MVC', 'font-size: 10px; color: #94a3b8;');
