<?php
// includes/auth.php - Gerenciamento de Sessão e Autorização

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se existe um utilizador autenticado na sessão atual.
 */
function estaAutenticado() {
    return isset($_SESSION['utilizador']) && !empty($_SESSION['utilizador']['id']);
}

/**
 * Retorna os dados do utilizador com sessão ativa, ou nulo.
 */
function obterUtilizadorLogado() {
    return estaAutenticado() ? $_SESSION['utilizador'] : null;
}

/**
 * Protege páginas restritas (Painel de Administração).
 * Se o utilizador não estiver logado, redireciona para a página de login.
 */
function exigirAutenticacao($caminhoLogin = '../login.php') {
    if (!estaAutenticado()) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_alerta'] = [
            'tipo' => 'erro',
            'mensagem' => 'Acesso restrito. Por favor, inicie sessão para aceder a esta página.'
        ];
        header("Location: " . $caminhoLogin);
        exit;
    }
}

/**
 * Autentica o utilizador através de email e senha usando Prepared Statements.
 */
function autenticarUtilizador($conn, $email, $senha) {
    $stmt = $conn->prepare("SELECT id, nome, email, senha, cargo FROM `utilizadores` WHERE `email` = ? LIMIT 1");
    if (!$stmt) {
        return ['sucesso' => false, 'mensagem' => 'Erro interno na base de dados.'];
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();
        
        // Suporta tanto o hash seguro (password_verify) como compatibilidade de texto plano
        if (password_verify($senha, $user['senha']) || $senha === $user['senha']) {
            // Se estava em texto simples, atualiza para hash seguro automaticamente
            if ($senha === $user['senha']) {
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE `utilizadores` SET `senha` = ? WHERE `id` = ?");
                $updateStmt->bind_param("si", $novoHash, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }

            // Regista na sessão
            $_SESSION['utilizador'] = [
                'id' => (int)$user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'cargo' => $user['cargo']
            ];

            return ['sucesso' => true, 'utilizador' => $_SESSION['utilizador']];
        }
    }

    return ['sucesso' => false, 'mensagem' => 'Email ou palavra-passe incorretos.'];
}

/**
 * Encerra a sessão ativa de forma segura.
 */
function terminarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
?>
