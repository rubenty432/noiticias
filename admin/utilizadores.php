<?php
// admin/utilizadores.php - Listar, adicionar e editar utilizadores
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Exige autenticação
exigirAutenticacao('../login.php');
$usuarioLogado = obterUtilizadorLogado();

$erro = "";
$modoEdicao = false;
$userEditar = null;

// Cargos disponíveis
$cargosDisponiveis = ['Administrador', 'Editor', 'Jornalista'];

// --- PROCESSAR REMOÇÃO DE UTILIZADOR ---
if (isset($_GET['remover']) && (int)$_GET['remover'] > 0) {
    $idRemover = (int)$_GET['remover'];

    // Impede que o próprio utilizador logado elimine a sua própria conta
    if ($idRemover === (int)$usuarioLogado['id']) {
        definirAlerta('erro', 'Operação não permitida: Não pode remover a sua própria conta em sessão.');
    } else {
        $stmtDel = $conn->prepare("DELETE FROM `utilizadores` WHERE `id` = ?");
        $stmtDel->bind_param("i", $idRemover);
        if ($stmtDel->execute()) {
            definirAlerta('sucesso', 'O utilizador foi removido com sucesso.');
        } else {
            definirAlerta('erro', 'Erro ao remover o utilizador: ' . $conn->error);
        }
        $stmtDel->close();
    }
    header("Location: utilizadores.php");
    exit;
}

// --- VERIFICAR MODO DE EDIÇÃO ---
if (isset($_GET['editar']) && (int)$_GET['editar'] > 0) {
    $idEditar = (int)$_GET['editar'];
    $stmtFind = $conn->prepare("SELECT id, nome, email, cargo FROM `utilizadores` WHERE `id` = ? LIMIT 1");
    $stmtFind->bind_param("i", $idEditar);
    $stmtFind->execute();
    $resFind = $stmtFind->get_result();
    if ($resFind && $resFind->num_rows === 1) {
        $modoEdicao = true;
        $userEditar = $resFind->fetch_assoc();
    }
    $stmtFind->close();
}

// --- PROCESSAR ENVIO DO FORMULÁRIO (ADICIONAR OU EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'adicionar';
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = trim($_POST['cargo'] ?? 'Administrador');
    $senha = trim($_POST['senha'] ?? '');

    // Validação
    if (empty($nome) || empty($email)) {
        $erro = "Por favor preencha o Nome e o E-mail.";
    } elseif (!validarEmail($email)) {
        $erro = "O formato do e-mail introduzido é inválido.";
    } else {
        if ($acao === 'editar' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];

            // Verificar se o email já pertence a outro utilizador
            $stmtCheck = $conn->prepare("SELECT id FROM `utilizadores` WHERE `email` = ? AND `id` != ? LIMIT 1");
            $stmtCheck->bind_param("si", $email, $id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $erro = "Já existe outro utilizador registado com este endereço de e-mail.";
            } else {
                if (!empty($senha)) {
                    // Atualiza com nova palavra-passe
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmtUp = $conn->prepare("UPDATE `utilizadores` SET `nome` = ?, `email` = ?, `cargo` = ?, `senha` = ? WHERE `id` = ?");
                    $stmtUp->bind_param("ssssi", $nome, $email, $cargo, $hash, $id);
                } else {
                    // Mantém a palavra-passe atual
                    $stmtUp = $conn->prepare("UPDATE `utilizadores` SET `nome` = ?, `email` = ?, `cargo` = ? WHERE `id` = ?");
                    $stmtUp->bind_param("sssi", $nome, $email, $cargo, $id);
                }

                if ($stmtUp->execute()) {
                    // Se editou os próprios dados, atualiza a sessão
                    if ($id === (int)$usuarioLogado['id']) {
                        $_SESSION['utilizador']['nome'] = $nome;
                        $_SESSION['utilizador']['email'] = $email;
                        $_SESSION['utilizador']['cargo'] = $cargo;
                    }
                    definirAlerta('sucesso', 'Utilizador atualizado com sucesso!');
                    header("Location: utilizadores.php");
                    exit;
                } else {
                    $erro = "Erro ao atualizar utilizador: " . $conn->error;
                }
                $stmtUp->close();
            }
            $stmtCheck->close();
        } else {
            // Adicionar novo utilizador
            if (empty($senha)) {
                $erro = "A palavra-passe é obrigatória para criar um novo utilizador.";
            } elseif (strlen($senha) < 6) {
                $erro = "A palavra-passe deve conter pelo menos 6 caracteres.";
            } else {
                // Verificar se o email já existe
                $stmtCheck = $conn->prepare("SELECT id FROM `utilizadores` WHERE `email` = ? LIMIT 1");
                $stmtCheck->bind_param("s", $email);
                $stmtCheck->execute();
                if ($stmtCheck->get_result()->num_rows > 0) {
                    $erro = "Já existe um utilizador registado com este endereço de e-mail.";
                } else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmtAdd = $conn->prepare("INSERT INTO `utilizadores` (`nome`, `email`, `senha`, `cargo`) VALUES (?, ?, ?, ?)");
                    $stmtAdd->bind_param("ssss", $nome, $email, $hash, $cargo);
                    if ($stmtAdd->execute()) {
                        definirAlerta('sucesso', 'Utilizador criado com sucesso!');
                        header("Location: utilizadores.php");
                        exit;
                    } else {
                        $erro = "Erro ao registar utilizador: " . $conn->error;
                    }
                    $stmtAdd->close();
                }
                $stmtCheck->close();
            }
        }
    }
}

// Obter listagem de utilizadores
$resUsers = $conn->query("SELECT id, nome, email, cargo, criado_em FROM `utilizadores` ORDER BY criado_em DESC");
$listaUtilizadores = $resUsers ? $resUsers->fetch_all(MYSQLI_ASSOC) : [];
$totalUsers = count($listaUtilizadores);

$tituloPagina = "Gestão de Utilizadores";
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 50px;">
  <div class="admin-header-content" style="margin-bottom: 24px;">
    <div>
      <h1 style="font-size: 2rem;">👥 Gestão de Utilizadores</h1>
      <p style="color: var(--text-muted);">Controle de acessos, adição e edição de membros da equipa.</p>
    </div>
    <div>
      <a href="#formulario-utilizador" class="nav-btn btn-primary" id="btn-scroll-user">
        ➕ Novo Utilizador
      </a>
    </div>
  </div>

  <?php exibirAlerta(); ?>

  <?php if (!empty($erro)): ?>
    <div class="alert alert-error">
      <span>⚠</span> <?= htmlspecialchars($erro) ?>
    </div>
  <?php endif; ?>

  <!-- Formulário de Criação / Edição -->
  <div class="panel-card" id="formulario-utilizador">
    <div class="panel-card-header">
      <h2 style="font-size: 1.25rem;">
        <?= $modoEdicao ? '✏️ Editar Utilizador #' . $userEditar['id'] : '➕ Registar Novo Utilizador' ?>
      </h2>
      <?php if ($modoEdicao): ?>
        <a href="utilizadores.php" class="btn-outline btn-sm">✕ Cancelar Edição</a>
      <?php endif; ?>
    </div>
    <div class="panel-card-body">
      <form action="utilizadores.php" method="POST" id="form-utilizador">
        <input type="hidden" name="acao" value="<?= $modoEdicao ? 'editar' : 'adicionar' ?>">
        <?php if ($modoEdicao): ?>
          <input type="hidden" name="id" value="<?= $userEditar['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
          <div class="form-group">
            <label for="nome" class="form-label">Nome Completo *</label>
            <input 
              type="text" 
              id="nome" 
              name="nome" 
              class="form-control" 
              placeholder="Ex: Maria Santos" 
              value="<?= htmlspecialchars($modoEdicao ? $userEditar['nome'] : ($_POST['nome'] ?? '')) ?>" 
              required
            >
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Endereço de E-mail *</label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-control" 
              placeholder="maria@noticias.pt" 
              value="<?= htmlspecialchars($modoEdicao ? $userEditar['email'] : ($_POST['email'] ?? '')) ?>" 
              required
            >
          </div>

          <div class="form-group">
            <label for="cargo" class="form-label">Cargo / Perfil *</label>
            <select id="cargo" name="cargo" class="form-control" required>
              <?php 
              $cargoSelecionado = $modoEdicao ? $userEditar['cargo'] : ($_POST['cargo'] ?? 'Administrador');
              foreach ($cargosDisponiveis as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $cargoSelecionado === $c ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="senha" class="form-label">
              Palavra-passe <?= $modoEdicao ? '<small style="color:var(--text-muted);">(deixe em branco para manter a atual)</small>' : '*' ?>
            </label>
            <input 
              type="password" 
              id="senha" 
              name="senha" 
              class="form-control" 
              placeholder="<?= $modoEdicao ? '••••••••' : 'Mínimo 6 caracteres' ?>" 
              <?= $modoEdicao ? '' : 'required' ?>
            >
          </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 10px;">
          <?php if ($modoEdicao): ?>
            <a href="utilizadores.php" class="nav-btn btn-outline">Cancelar</a>
          <?php endif; ?>
          <button type="submit" class="nav-btn btn-primary" id="btn-salvar-utilizador">
            <?= $modoEdicao ? '💾 Gravar Alterações' : '👤 Registar Utilizador' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabela com listagem de utilizadores -->
  <div class="panel-card">
    <div class="panel-card-header">
      <h2 style="font-size: 1.25rem;">📋 Utilizadores Registados (<?= $totalUsers ?>)</h2>
    </div>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Cargo</th>
            <th>Data de Criação</th>
            <th style="text-align: right; width: 180px;">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($listaUtilizadores as $u): ?>
            <tr>
              <td><strong>#<?= $u['id'] ?></strong></td>
              <td>
                <strong><?= htmlspecialchars($u['nome']) ?></strong>
                <?php if ((int)$u['id'] === (int)$usuarioLogado['id']): ?>
                  <span class="role-pill" style="margin-left: 6px; font-size: 0.7rem; background:#dbeafe; color:#1e40af;">(Você)</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <span class="user-badge" style="display:inline-flex; padding: 3px 8px; font-size: 0.78rem;">
                  <?= htmlspecialchars($u['cargo']) ?>
                </span>
              </td>
              <td><small><?= formatarData($u['criado_em']) ?></small></td>
              <td style="text-align: right;">
                <div class="action-btns" style="justify-content: flex-end;">
                  <a href="utilizadores.php?editar=<?= $u['id'] ?>#formulario-utilizador" class="btn-outline btn-sm">
                    ✏️ Editar
                  </a>
                  <?php if ((int)$u['id'] !== (int)$usuarioLogado['id']): ?>
                    <a 
                      href="utilizadores.php?remover=<?= $u['id'] ?>" 
                      class="btn-danger btn-sm" 
                      onclick="return confirm('Deseja realmente eliminar o utilizador <?= htmlspecialchars(addslashes($u['nome'])) ?>?');"
                    >
                      🗑️ Eliminar
                    </a>
                  <?php else: ?>
                    <button class="btn-sm btn-outline" style="opacity: 0.4; cursor: not-allowed;" title="Não pode eliminar a sua própria conta ativa" disabled>
                      🗑️ Eliminar
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
