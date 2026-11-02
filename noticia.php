<?php
// noticia.php - Visualização detalhada de uma notícia individual
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT n.*, u.nome AS autor_nome 
                        FROM `noticias` n 
                        LEFT JOIN `utilizadores` u ON n.autor_id = u.id 
                        WHERE n.id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    definirAlerta('erro', 'A notícia solicitada não foi encontrada ou foi removida.');
    header("Location: index.php");
    exit;
}

$noticia = $resultado->fetch_assoc();
$stmt->close();

$tituloPagina = $noticia['titulo'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <article class="article-header">
    <a href="index.php" class="article-back-link" id="voltar-noticias">
      &larr; Voltar para as notícias
    </a>

    <span class="category-pill active" style="display: inline-block; margin-bottom: 12px;">
      <?= htmlspecialchars($noticia['categoria']) ?>
    </span>

    <h1 class="article-title"><?= htmlspecialchars($noticia['titulo']) ?></h1>

    <?php if (!empty($noticia['subtitulo'])): ?>
      <p class="article-subtitulo"><?= htmlspecialchars($noticia['subtitulo']) ?></p>
    <?php endif; ?>

    <div class="article-meta-row">
      <span>📅 Publicado a <?= formatarData($noticia['data_publicacao']) ?></span>
      <?php if (!empty($noticia['autor_nome'])): ?>
        <span>• ✍️ Autor: <strong><?= htmlspecialchars($noticia['autor_nome']) ?></strong></span>
      <?php endif; ?>

      <?php if (estaAutenticado()): ?>
        <span style="margin-left: auto;">
          <a href="admin/noticias.php?editar=<?= $noticia['id'] ?>" class="btn-outline btn-sm" id="btn-editar-noticia">
            ✏️ Editar Notícia no Admin
          </a>
        </span>
      <?php endif; ?>
    </div>

    <?php if (!empty($noticia['imagem'])): ?>
      <div class="article-image-box">
        <img src="<?= htmlspecialchars($noticia['imagem']) ?>" alt="<?= htmlspecialchars($noticia['titulo']) ?>">
      </div>
    <?php endif; ?>

    <div class="article-content">
      <?= nl2br(htmlspecialchars($noticia['conteudo'])) ?>
    </div>
  </article>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
