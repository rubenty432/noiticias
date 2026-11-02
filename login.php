<?php
// login.php - Autenticação de Utilizadores
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver com sessão iniciada, redireciona para a administração
if (estaAutenticado()) {
    header("Location: admin/noticias.php");
    exit;
}

$erroLogin = "";
$emailPreenchido = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
    $emailPreenchido = $email;

    if (empty($email) || empty($senha)) {
        $erroLogin = "Por favor, preencha o seu e-mail e palavra-passe.";
    } elseif (!validarEmail($email)) {
        $erroLogin = "O formato do e-mail introduzido é inválido.";
    } else {
        $resultado = autenticarUtilizador($conn, $email, $senha);
        if ($resultado['sucesso']) {
            definirAlerta('sucesso', 'Sessão iniciada com sucesso. Bem-vindo(a), ' . $resultado['utilizador']['nome'] . '!');
            header("Location: admin/noticias.php");
            exit;
        } else {
            $erroLogin = $resultado['mensagem'];
        }
    }
}

$tituloPagina = "Iniciar Sessão";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon">🔐</div>
      <h1 class="auth-title">Acesso Restrito</h1>
      <p class="auth-subtitle">Introduza as suas credenciais para aceder ao painel</p>
    </div>

    <?php exibirAlerta(); ?>

    <?php if (!empty($erroLogin)): ?>
      <div class="alert alert-error">
        <span>⚠</span> <?= htmlspecialchars($erroLogin) ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST" id="form-login">
      <div class="form-group">
        <label for="email" class="form-label">Endereço de E-mail</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          class="form-control" 
          placeholder="exemplo@noticias.pt" 
          value="<?= htmlspecialchars($emailPreenchido) ?>" 
          required 
          autofocus
        >
      </div>

      <div class="form-group">
        <label for="senha" class="form-label">Palavra-passe</label>
        <input 
          type="password" 
          id="senha" 
          name="senha" 
          class="form-control" 
          placeholder="••••••••" 
          required
        >
      </div>

      <button type="submit" class="nav-btn btn-primary" style="width: 100%; justify-content: center; padding: 12px;" id="btn-entrar">
        Entrar no Painel &rarr;
      </button>
    </form>

    <div style="margin-top: 24px; padding: 14px; background: var(--surface-alt); border-radius: var(--radius-sm); font-size: 0.82rem; color: var(--text-muted); border: 1px dashed var(--border);">
      <div style="font-weight: 700; color: var(--text-main); margin-bottom: 4px;">💡 Credenciais de Demonstração:</div>
      <div><strong>Email:</strong> <code>admin@noticias.pt</code></div>
      <div><strong>Palavra-passe:</strong> <code>admin123</code></div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
      <a href="index.php" style="font-size: 0.88rem; color: var(--text-muted);">&larr; Voltar à página pública</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
