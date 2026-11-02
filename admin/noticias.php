<?php
// admin/noticias.php - Adicionar, listar, editar e remover notícias
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Exige autenticação
exigirAutenticacao('../login.php');
$usuarioLogado = obterUtilizadorLogado();

$erro = "";
$modoEdicao = false;
$noticiaEditar = null;

// Categorias sugeridas
$categoriasDisponiveis = ['Geral', 'Tecnologia', 'Economia', 'Cultura', 'Desporto', 'Política', 'Mundo'];

// --- PROCESSAR REMOÇÃO DE NOTÍCIA ---
if (isset($_GET['remover']) && (int)$_GET['remover'] > 0) {
    $idRemover = (int)$_GET['remover'];
    $stmtDel = $conn->prepare("DELETE FROM `noticias` WHERE `id` = ?");
    $stmtDel->bind_param("i", $idRemover);
    
    if ($stmtDel->execute()) {
        definirAlerta('sucesso', 'A notícia foi removida com sucesso.');
    } else {
        definirAlerta('erro', 'Erro ao remover a notícia: ' . $conn->error);
    }
    $stmtDel->close();
    header("Location: noticias.php");
    exit;
}

// --- VERIFICAR SE ESTÁ EM MODO DE EDIÇÃO ---
if (isset($_GET['editar']) && (int)$_GET['editar'] > 0) {
    $idEditar = (int)$_GET['editar'];
    $stmtFind = $conn->prepare("SELECT * FROM `noticias` WHERE `id` = ? LIMIT 1");
    $stmtFind->bind_param("i", $idEditar);
    $stmtFind->execute();
    $resFind = $stmtFind->get_result();
    if ($resFind && $resFind->num_rows === 1) {
        $modoEdicao = true;
        $noticiaEditar = $resFind->fetch_assoc();
    }
    $stmtFind->close();
}

// --- PROCESSAR FORMULÁRIO (ADICIONAR OU EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'adicionar';
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'Geral');
    $imagem = trim($_POST['imagem'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');

    // Validação básica
    if (empty($titulo) || empty($conteudo)) {
        $erro = "Os campos 'Título' e 'Conteúdo' são de preenchimento obrigatório.";
    } else {
        if ($acao === 'editar' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmtUpdate = $conn->prepare("UPDATE `noticias` SET `titulo` = ?, `subtitulo` = ?, `categoria` = ?, `imagem` = ?, `conteudo` = ? WHERE `id` = ?");
            $stmtUpdate->bind_param("sssssi", $titulo, $subtitulo, $categoria, $imagem, $conteudo, $id);

            if ($stmtUpdate->execute()) {
                definirAlerta('sucesso', 'Notícia atualizada com sucesso!');
                header("Location: noticias.php");
                exit;
            } else {
                $erro = "Erro ao atualizar a notícia: " . $conn->error;
            }
            $stmtUpdate->close();
        } else {
            // Adicionar nova notícia
            $autorId = $usuarioLogado['id'];
            $stmtInsert = $conn->prepare("INSERT INTO `noticias` (`titulo`, `subtitulo`, `categoria`, `imagem`, `conteudo`, `autor_id`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("sssssi", $titulo, $subtitulo, $categoria, $imagem, $conteudo, $autorId);

            if ($stmtInsert->execute()) {
                definirAlerta('sucesso', 'Nova notícia adicionada com sucesso!');
                header("Location: noticias.php");
                exit;
            } else {
                $erro = "Erro ao inserir a notícia: " . $conn->error;
            }
            $stmtInsert->close();
        }
    }
}

// Obter listagem de todas as notícias
$sqlLista = "SELECT n.*, u.nome AS autor_nome 
             FROM `noticias` n 
             LEFT JOIN `utilizadores` u ON n.autor_id = u.id 
             ORDER BY n.data_publicacao DESC";
$resLista = $conn->query($sqlLista);
$listaNoticias = $resLista ? $resLista->fetch_all(MYSQLI_ASSOC) : [];

// Estatísticas rápidas
$totalNoticias = count($listaNoticias);

$tituloPagina = "Gestão de Notícias";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 50px;">
  <div class="admin-header-content" style="margin-bottom: 24px;">
    <div>
      <h1 style="font-size: 2rem;">📰 Gestão de Notícias</h1>
      <p style="color: var(--text-muted);">Adicione, consulte, edite e remova os artigos do portal.</p>
    </div>
    <div>
      <a href="#formulario-noticia" class="nav-btn btn-primary" id="btn-scroll-formulario">
        ➕ Nova Notícia
      </a>
    </div>
  </div>

  <?php exibirAlerta(); ?>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-error">
      <span>⚠</span> <?= htmlspecialchars($erro) ?>
    </div>
  <?php endif; ?>

  <!-- Estatísticas Rápidas -->
  <div class="admin-stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📄</div>
      <div>
        <div class="stat-val"><?= $totalNoticias ?></div>
        <div class="stat-label">Total de Artigos Publicados</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏷️</div>
      <div>
        <div class="stat-val"><?= count($categoriasDisponiveis) ?></div>
        <div class="stat-label">Categorias Ativas</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👤</div>
      <div>
        <div class="stat-val"><?= htmlspecialchars($usuarioLogado['nome']) ?></div>
        <div class="stat-label">Sessão Atual</div>
      </div>
    </div>
  </div>

  <!-- Formulário de Criação / Edição -->
  <div class="panel-card" id="formulario-noticia">
    <div class="panel-card-header">
      <h2 style="font-size: 1.25rem;">
        <?= $modoEdicao ? '✏️ Editar Notícia #' . $noticiaEditar['id'] : '➕ Adicionar Nova Notícia' ?>
      </h2>
      <?php if ($modoEdicao): ?>
        <a href="noticias.php" class="btn-outline btn-sm">✕ Cancelar Edição</a>
      <?php endif; ?>
    </div>
    <div class="panel-card-body">
      <form action="noticias.php" method="POST" id="form-noticia">
        <input type="hidden" name="acao" value="<?= $modoEdicao ? 'editar' : 'adicionar' ?>">
        <?php if ($modoEdicao): ?>
          <input type="hidden" name="id" value="<?= $noticiaEditar['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
          <div class="form-group">
            <label for="titulo" class="form-label">Título da Notícia *</label>
            <input 
              type="text" 
              id="titulo" 
              name="titulo" 
              class="form-control" 
              placeholder="Ex: Nova tecnologia revoluciona a produção de energia" 
              value="<?= htmlspecialchars($modoEdicao ? $noticiaEditar['titulo'] : ($_POST['titulo'] ?? '')) ?>" 
              required
            >
          </div>

          <div class="form-group">
            <label for="categoria" class="form-label">Categoria *</label>
            <select id="categoria" name="categoria" class="form-control" required>
              <?php 
              $catSelecionada = $modoEdicao ? $noticiaEditar['categoria'] : ($_POST['categoria'] ?? 'Geral');
              foreach ($categoriasDisponiveis as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $catSelecionada === $cat ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="form-group">
            <label for="subtitulo" class="form-label">Subtítulo / Lead (Opcional)</label>
            <input 
              type="text" 
              id="subtitulo" 
              name="subtitulo" 
              class="form-control" 
              placeholder="Breve resumo introdutório" 
              value="<?= htmlspecialchars($modoEdicao ? $noticiaEditar['subtitulo'] : ($_POST['subtitulo'] ?? '')) ?>"
            >
          </div>

          <div class="form-group">
            <label for="imagem" class="form-label">URL da Imagem (Opcional)</label>
            <input 
              type="url" 
              id="imagem" 
              name="imagem" 
              class="form-control" 
              placeholder="https://exemplo.com/imagem.jpg" 
              value="<?= htmlspecialchars($modoEdicao ? $noticiaEditar['imagem'] : ($_POST['imagem'] ?? '')) ?>"
            >
          </div>
        </div>

        <div class="form-group">
          <label for="conteudo" class="form-label">Conteúdo Completo do Artigo *</label>
          <textarea 
            id="conteudo" 
            name="conteudo" 
            class="form-control" 
            rows="7" 
            placeholder="Escreva aqui o artigo completo..." 
            required
          ><?= htmlspecialchars($modoEdicao ? $noticiaEditar['conteudo'] : ($_POST['conteudo'] ?? '')) ?></textarea>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
          <?php if ($modoEdicao): ?>
            <a href="noticias.php" class="nav-btn btn-outline">Cancelar</a>
          <?php endif; ?>
          <button type="submit" class="nav-btn btn-primary" id="btn-salvar-noticia">
            <?= $modoEdicao ? '💾 Atualizar Notícia' : '🚀 Publicar Notícia' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabela com listagem de notícias -->
  <div class="panel-card">
    <div class="panel-card-header">
      <h2 style="font-size: 1.25rem;">📋 Todas as Notícias Cadastradas (<?= $totalNoticias ?>)</h2>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Artigo / Título</th>
            <th>Categoria</th>
            <th>Autor</th>
            <th>Publicação</th>
            <th style="text-align: right; width: 180px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($listaNoticias)): ?>
            <?php foreach ($listaNoticias as $item): ?>
              <tr>
                <td><strong>#<?= $item['id'] ?></strong></td>
                <td>
                  <div style="font-weight: 700; color: var(--text-main); margin-bottom: 2px;">
                    <a href="../noticia.php?id=<?= $item['id'] ?>" target="_blank" title="Visualizar notícia no site">
                      <?= htmlspecialchars($item['titulo']) ?> ↗
                    </a>
                  </div>
                  <?php if (!empty($item['subtitulo'])): ?>
                    <small style="color: var(--text-muted);"><?= htmlspecialchars(resumirTexto($item['subtitulo'], 80)) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="category-pill active" style="font-size: 0.75rem; padding: 3px 8px;">
                    <?= htmlspecialchars($item['categoria']) ?>
                  </span>
                </td>
                <td>
                  <?= htmlspecialchars($item['autor_nome'] ?? 'Desconhecido') ?>
                </td>
                <td>
                  <small><?= formatarData($item['data_publicacao']) ?></small>
                </td>
                <td style="text-align: right;">
                  <div class="action-btns" style="justify-content: flex-end;">
                    <a href="noticias.php?editar=<?= $item['id'] ?>#formulario-noticia" class="btn-outline btn-sm" title="Editar">
                      ✏️ Editar
                    </a>
                    <a 
                      href="noticias.php?remover=<?= $item['id'] ?>" 
                      class="btn-danger btn-sm" 
                      onclick="return confirm('Tem certeza absoluta que deseja remover esta notícia? Esta ação não pode ser desfeita.');"
                      title="Remover Notícia"
                    >
                      🗑️ Remover
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                Ainda não existem notícias cadastradas. Utilize o formulário acima para publicar a primeira!
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
