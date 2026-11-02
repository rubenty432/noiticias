<?php
// includes/functions.php - Funções reutilizáveis de validação, sanitização e apresentação

/**
 * Sanitiza valores de entrada para prevenir ataques XSS.
 */
function sanitizar($dado) {
    if (is_array($dado)) {
        return array_map('sanitizar', $dado);
    }
    return htmlspecialchars(trim((string)$dado), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida formato de e-mail.
 */
function validarEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formata data e hora para exibição em português.
 */
function formatarData($dataHora) {
    if (empty($dataHora)) {
        return "Data desconhecida";
    }
    $timestamp = strtotime($dataHora);
    if (!$timestamp) {
        return htmlspecialchars($dataHora);
    }
    
    $meses = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
    ];
    
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('m', $timestamp)];
    $ano = date('Y', $timestamp);
    $hora = date('H:i', $timestamp);
    
    return "$dia $mes $ano às $hora";
}

/**
 * Encurta textos longos para resumos em cartões.
 */
function resumirTexto($texto, $limite = 140) {
    $textoLimpo = strip_tags($texto);
    if (mb_strlen($textoLimpo, 'UTF-8') <= $limite) {
        return $textoLimpo;
    }
    return mb_substr($textoLimpo, 0, $limite, 'UTF-8') . '...';
}

/**
 * Define uma mensagem temporária de sessão (Flash Message).
 */
function definirAlerta($tipo, $mensagem) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_alerta'] = [
        'tipo' => $tipo, // 'sucesso' ou 'erro' ou 'aviso'
        'mensagem' => $mensagem
    ];
}

/**
 * Renderiza o alerta pendente na página e remove da sessão.
 */
function exibirAlerta() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['flash_alerta'])) {
        $alerta = $_SESSION['flash_alerta'];
        $classe = $alerta['tipo'] === 'sucesso' ? 'alert-success' : 'alert-error';
        $icone = $alerta['tipo'] === 'sucesso' ? '✓' : '⚠';
        echo "<div class='alert {$classe}'><span>{$icone}</span> " . htmlspecialchars($alerta['mensagem']) . "</div>";
        unset($_SESSION['flash_alerta']);
    }
}
?>
