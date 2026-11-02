<?php
// includes/header.php - Cabeçalho público e barra de navegação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$paginaAtual = basename($_SERVER['PHP_SELF']);
$usuarioLogado = obterUtilizadorLogado();
$tituloPagina = isset($tituloPagina) ? $tituloPagina . " - Portal Notícias" : "Portal Notícias - Informação Atualizada";
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloPagina) ?></title>
  <meta name="description" content="Portal de notícias com as últimas informações sobre tecnologia, sociedade, economia e cultura.">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="navbar">
  <div class="container nav-content">
    <a href="index.php" class="brand-logo" id="logo-link">
      <div class="brand-icon">📰</div>
      <span>Portal Notícias</span>
    </a>

    <nav>
      <ul class="nav-links">
        <li>
          <a href="index.php" class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>" id="nav-inicio">
            Início
          </a>
        </li>

        <?php if ($usuarioLogado): ?>
          <li>
            <a href="admin/noticias.php" class="nav-link" id="nav-admin-noticias">
              Gerir Notícias
            </a>
          </li>
          <li>
            <a href="admin/utilizadores.php" class="nav-link" id="nav-admin-utilizadores">
              Utilizadores
            </a>
          </li>
          <li>
            <span class="user-badge">
              <span>👤 <?= htmlspecialchars($usuarioLogado['nome']) ?></span>
              <span class="role-pill"><?= htmlspecialchars($usuarioLogado['cargo'] ?? 'Admin') ?></span>
            </span>
          </li>
          <li>
            <a href="logout.php" class="nav-btn btn-outline btn-sm" id="nav-logout">
              Terminar Sessão
            </a>
          </li>
        <?php else: ?>
          <li>
            <a href="login.php" class="nav-btn btn-primary btn-sm" id="nav-login">
              Acesso Restrito
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<main class="main-content">
