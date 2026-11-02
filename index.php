<?php
// index.php - Listar notícias e executar a pesquisa
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$termoPesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$categoriaFiltro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Obter lista de categorias disponíveis para os filtros
$categoriasResult = $conn->query("SELECT DISTINCT categoria FROM `noticias` WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
$listaCategorias = [];
if ($categoriasResult) {
    while ($row = $categoriasResult->fetch_assoc()) {
        $listaCategorias[] = $row['categoria'];
    }
}

// Construção da consulta com filtros seguros (Prepared Statements)
$sql = "SELECT n.*, u.nome AS autor_nome 
        FROM `noticias` n 
        LEFT JOIN `utilizadores` u ON n.autor_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if ($termoPesquisa !== '') {
    $sql .= " AND (n.titulo LIKE ? OR n.subtitulo LIKE ? OR n.conteudo LIKE ?)";
    $like = '%' . $termoPesquisa . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($categoriaFiltro !== '' && $categoriaFiltro !== 'Todas') {
    $sql .= " AND n.categoria = ?";
    $params[] = $categoriaFiltro;
    $types .= "s";
}

$sql .= " ORDER BY n.data_publicacao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$noticias = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$tituloPagina = "Últimas Notícias";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <!-- Hero & Pesquisa -->
  <section class="hero">
    <span class="hero-tagline">✨ Informação em Tempo Real</span>
    <h1 class="hero-title">Fique a par das novidades</h1>
    <p class="hero-desc">Explore os acontecimentos mais recentes de Portugal e do mundo com jornalismo claro e atualizado.</p>

    <!-- Barra de Pesquisa -->
    <div class="search-wrapper">
      <form action="index.php" method="GET" class="search-form" id="form-pesquisa">
        <?php if (!empty($categoriaFiltro)): ?>
          <input type="hidden" name="categoria" value="<?= htmlspecialchars($categoriaFiltro) ?>">
        <?php endif; ?>
        <input 
          type="text" 
          name="pesquisa" 
          class="search-input" 
          placeholder="Pesquisar notícias por palavras-chave..." 
          value="<?= htmlspecialchars($termoPesquisa) ?>"
          autocomplete="off"
          id="input-pesquisa"
        >
        <button type="submit" class="search-btn" id="btn-pesquisar">
          🔍 Pesquisar
        </button>
      </form>
    </div>

    <!-- Filtros de Categoria -->
    <?php if (!empty($listaCategorias)): ?>
      <div class="category-pills">
        <a 
          href="index.php<?= !empty($termoPesquisa) ? '?pesquisa=' . urlencode($termoPesquisa) : '' ?>" 
          class="category-pill <?= empty($categoriaFiltro) || $categoriaFiltro === 'Todas' ? 'active' : '' ?>"
        >
          Todas
        </a>
        <?php foreach ($listaCategorias as $cat): ?>
          <a 
            href="index.php?categoria=<?= urlencode($cat) ?><?= !empty($termoPesquisa) ? '&pesquisa=' . urlencode($termoPesquisa) : '' ?>" 
            class="category-pill <?= $categoriaFiltro === $cat ? 'active' : '' ?>"
          >
            <?= htmlspecialchars($cat) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Cabeçalho de Resultados -->
  <div class="news-section-title">
    <div>
      <h2>
        <?php if (!empty($termoPesquisa) || !empty($categoriaFiltro)): ?>
          Resultados da Pesquisa
        <?php else: ?>
          Destaques Recentes
        <?php endif; ?>
      </h2>
      <p style="color: var(--text-muted); font-size: 0.92rem;">
        <?= count($noticias) ?> <?= count($noticias) === 1 ? 'notícia encontrada' : 'notícias encontradas' ?>
        <?php if (!empty($termoPesquisa)): ?>
          para "<strong><?= htmlspecialchars($termoPesquisa) ?></strong>"
        <?php endif; ?>
        <?php if (!empty($categoriaFiltro)): ?>
          na categoria <strong><?= htmlspecialchars($categoriaFiltro) ?></strong>
        <?php endif; ?>
      </p>
    </div>

    <?php if (!empty($termoPesquisa) || !empty($categoriaFiltro)): ?>
      <a href="index.php" class="btn-outline btn-sm" id="btn-limpar-pesquisa">
        ✕ Limpar Filtros
      </a>
    <?php endif; ?>
  </div>

  <?php exibirAlerta(); ?>

  <!-- Grelha de Notícias -->
  <div class="news-grid">
    <?php if (!empty($noticias)): ?>
      <?php foreach ($noticias as $item): ?>
        <article class="news-card" id="noticia-<?= $item['id'] ?>">
          <div class="news-card-img-wrapper">
            <?php if (!empty($item['imagem'])): ?>
              <img src="<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="news-card-img" loading="lazy">
            <?php else: ?>
              <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b, #334155); color: #fff; font-size: 2.5rem;">
                📰
              </div>
            <?php endif; ?>
            <span class="news-card-badge"><?= htmlspecialchars($item['categoria']) ?></span>
          </div>

          <div class="news-card-body">
            <div class="news-meta">
              <span>📅 <?= formatarData($item['data_publicacao']) ?></span>
              <?php if (!empty($item['autor_nome'])): ?>
                <span>• ✍️ <?= htmlspecialchars($item['autor_nome']) ?></span>
              <?php endif; ?>
            </div>

            <h3 class="news-title">
              <a href="noticia.php?id=<?= $item['id'] ?>">
                <?= htmlspecialchars($item['titulo']) ?>
              </a>
            </h3>

            <p class="news-excerpt">
              <?= htmlspecialchars(resumirTexto(!empty($item['subtitulo']) ? $item['subtitulo'] . ' ' . $item['conteudo'] : $item['conteudo'], 130)) ?>
            </p>

            <div class="news-card-footer">
              <a href="noticia.php?id=<?= $item['id'] ?>" class="read-more" id="ler-noticia-<?= $item['id'] ?>">
                Ler Artigo Completo &rarr;
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Nenhuma notícia encontrada</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
          Não foram encontrados artigos correspondentes aos critérios de pesquisa.
        </p>
        <a href="index.php" class="nav-btn btn-primary">Ver Todas as Notícias</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
