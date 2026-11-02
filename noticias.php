<?php
// noticias.php - Redirecionador inteligente para index.php ou noticia.php
if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    header("Location: noticia.php?id=" . (int)$_GET['id']);
    exit;
}

// Redireciona com quaisquer parâmetros de pesquisa existentes
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: index.php" . $queryString);
exit;
?>
