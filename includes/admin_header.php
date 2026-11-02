<?php
// includes/admin_header.php - Cabeçalho do Painel Administrativo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Exige autenticação em todas as páginas admin
exigirAutenticacao('../login.php');

$usuarioLogado = obterUtilizadorLogado();
$paginaAtual = basename($_SERVER['PHP_SELF']);
$tituloPagina = isset($tituloPagina) ? $tituloPagina . " - Painel de Administração" : "Painel de Administração";
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloPagina) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="navbar">
  <div class="container nav-content">
    <a href="../index.php" class="brand-logo" id="admin-logo-link">
      <div class="brand-icon">⚙️</div>
      <span>Portal Notícias <small style="font-size:0.75rem; color:var(--primary); font-weight:700;">ADMIN</small></span>
    </a>

    <nav>
      <ul class="nav-links">
        <li>
          <a href="../index.php" class="nav-link" target="_blank" id="admin-ver-site">
            🌐 Ver Site
          </a>
        </li>
        <li>
          <a href="noticias.php" class="nav-link <?= $paginaAtual === 'noticias.php' ? 'active' : '' ?>" id="admin-menu-noticias">
            📰 Notícias
          </a>
        </li>
        <li>
          <a href="utilizadores.php" class="nav-link <?= $paginaAtual === 'utilizadores.php' ? 'active' : '' ?>" id="admin-menu-utilizadores">
            👥 Utilizadores
          </a>
        </li>
        <li>
          <span class="user-badge">
            <span>👤 <?= htmlspecialchars($usuarioLogado['nome']) ?></span>
            <span class="role-pill"><?= htmlspecialchars($usuarioLogado['cargo'] ?? 'Admin') ?></span>
          </span>
        </li>
        <li>
          <a href="../logout.php" class="nav-btn btn-outline btn-sm" id="admin-logout-btn">
            Terminar Sessão
          </a>
        </li>
      </ul>
    </nav>
  </div>
</header>
<main class="main-content">
