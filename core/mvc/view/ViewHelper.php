<?php
namespace Core\MVC\View;

/**
 * View Helper - Funções auxiliares para views
 * Responsabilidade única: Prover utilitários para templates
 */
class ViewHelper
{
    /**
     * Escapa HTML para prevenir XSS
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Gura URL com base na rota
     */
    public static function url(string $route, array $params = []): string
    {
        $url = "/index.php?route={$route}";
        
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Formata data para padrão brasileiro
     */
    public static function formatDate(string $date, string $format = 'd/m/Y H:i'): string
    {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Formata tempo em segundos para HH:MM:SS
     */
    public static function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    /**
     * Retorna classe CSS baseada no status
     */
    public static function statusClass(string $status): string
    {
        $classes = [
            'novo' => 'bg-blue-100 text-blue-800',
            'aberto' => 'bg-yellow-100 text-yellow-800',
            'em_andamento' => 'bg-purple-100 text-purple-800',
            'pendente' => 'bg-orange-100 text-orange-800',
            'fechado' => 'bg-green-100 text-green-800',
            'atrasado' => 'bg-red-100 text-red-800',
        ];
        
        return $classes[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
    }
    
    /**
     * Retorna ícone baseado no status
     */
    public static function statusIcon(string $status): string
    {
        $icons = [
            'novo' => '🆕',
            'aberto' => '📂',
            'em_andamento' => '⏳',
            'pendente' => '⚠️',
            'fechado' => '✅',
            'atrasado' => '🔴',
        ];
        
        return $icons[strtolower($status)] ?? '📋';
    }
    
    /**
     * Gera token CSRF
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica token CSRF
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Formata número como moeda brasileira
     */
    public static function formatCurrency(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    /**
     * Trunca texto com reticências
     */
    public static function truncate(string $text, int $length = 50): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
    
    /**
     * Retorna badge colorido para prioridade
     */
    public static function priorityBadge(int $priority): string
    {
        $colors = [
            1 => 'bg-gray-200 text-gray-700',
            2 => 'bg-blue-200 text-blue-700',
            3 => 'bg-yellow-200 text-yellow-700',
            4 => 'bg-orange-200 text-orange-700',
            5 => 'bg-red-200 text-red-700',
        ];
        
        $labels = [
            1 => 'Baixa',
            2 => 'Média',
            3 => 'Alta',
            4 => 'Muito Alta',
            5 => 'Crítica',
        ];
        
        $color = $colors[$priority] ?? $colors[1];
        $label = $labels[$priority] ?? $labels[1];
        
        return "<span class=\"px-2 py-1 rounded-full text-xs font-medium {$color}\">{$label}</span>";
    }
}
