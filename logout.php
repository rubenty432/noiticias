<?php
// logout.php - Terminar a sessão ativa do utilizador
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

terminarSessao();

session_start();
definirAlerta('sucesso', 'A sua sessão foi encerrada com sucesso.');
header("Location: login.php");
exit;
?>
