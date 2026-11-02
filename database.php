<?php
// Configurações de Conexão à Base de Dados MySQL (XAMPP)
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "noticias";

// Conexão inicial ao servidor MySQL
$conn = @new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;'>
        <strong>Erro de Ligação ao MySQL:</strong> " . htmlspecialchars($conn->connect_error) . "
        <br><small>Certifique-se de que o módulo MySQL está ativo no XAMPP Control Panel.</small>
    </div>");
}

// Cria a base de dados automaticamente se ainda não existir
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Seleciona a base de dados
if (!$conn->select_db($dbname)) {
    die("Erro ao selecionar a base de dados '$dbname': " . $conn->error);
}

// Configura o charset para UTF-8
$conn->set_charset("utf8mb4");

// Auto-criação das tabelas se ainda não existirem
$tabelaUtilizadoresSql = "CREATE TABLE IF NOT EXISTS `utilizadores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(50) NOT NULL DEFAULT 'Administrador',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($tabelaUtilizadoresSql);

$tabelaNoticiasSql = "CREATE TABLE IF NOT EXISTS `noticias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(255) NOT NULL,
  `subtitulo` VARCHAR(255) DEFAULT NULL,
  `categoria` VARCHAR(60) NOT NULL DEFAULT 'Geral',
  `conteudo` TEXT NOT NULL,
  `imagem` VARCHAR(255) DEFAULT NULL,
  `autor_id` INT NULL,
  `data_publicacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_noticias_autor` FOREIGN KEY (`autor_id`) 
    REFERENCES `utilizadores` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($tabelaNoticiasSql);

// Garante compatibilidade e integridade da estrutura da tabela `utilizadores`
$resCheckUsers = $conn->query("SELECT COUNT(*) AS total FROM `utilizadores`");
$totalUtilizadores = 0;
if ($resCheckUsers) {
    $rowU = $resCheckUsers->fetch_assoc();
    $totalUtilizadores = (int)($rowU['total'] ?? 0);
}

// Verifica se a coluna 'email' existe na tabela utilizadores
$resColEmail = $conn->query("SHOW COLUMNS FROM `utilizadores` LIKE 'email'");
$temEmail = ($resColEmail && $resColEmail->num_rows > 0);

// Se a tabela utilizadores estiver vazia e não tiver a coluna email, recria as tabelas com a estrutura correta
if ($totalUtilizadores === 0 && !$temEmail) {
    $conn->query("DROP TABLE IF EXISTS `noticias`");
    $conn->query("DROP TABLE IF EXISTS `utilizadores`");
    $conn->query($tabelaUtilizadoresSql);
    $conn->query($tabelaNoticiasSql);
} else {
    // Migração/Adição automática de colunas caso a tabela já contenha dados
    $colunasUsers = [];
    $resCols = $conn->query("SHOW COLUMNS FROM `utilizadores`");
    if ($resCols) {
        while ($col = $resCols->fetch_assoc()) {
            $colunasUsers[] = strtolower($col['Field']);
        }
    }

    if (!in_array('nome', $colunasUsers)) {
        $conn->query("ALTER TABLE `utilizadores` ADD `nome` VARCHAR(100) NOT NULL DEFAULT 'Administrador'");
    }

    if (!in_array('email', $colunasUsers)) {
        if (in_array('username', $colunasUsers)) {
            $conn->query("ALTER TABLE `utilizadores` CHANGE `username` `email` VARCHAR(120) NOT NULL");
        } elseif (in_array('usuario', $colunasUsers)) {
            $conn->query("ALTER TABLE `utilizadores` CHANGE `usuario` `email` VARCHAR(120) NOT NULL");
        } elseif (in_array('login', $colunasUsers)) {
            $conn->query("ALTER TABLE `utilizadores` CHANGE `login` `email` VARCHAR(120) NOT NULL");
        } else {
            $conn->query("ALTER TABLE `utilizadores` ADD `email` VARCHAR(120) NULL");
            $conn->query("UPDATE `utilizadores` SET `email` = CONCAT('user', id, '@noticias.pt') WHERE `email` IS NULL OR `email` = ''");
            @$conn->query("ALTER TABLE `utilizadores` MODIFY `email` VARCHAR(120) NOT NULL");
            @$conn->query("ALTER TABLE `utilizadores` ADD UNIQUE (`email`)");
        }
    }

    if (!in_array('senha', $colunasUsers)) {
        if (in_array('password', $colunasUsers)) {
            $conn->query("ALTER TABLE `utilizadores` CHANGE `password` `senha` VARCHAR(255) NOT NULL");
        } else {
            $conn->query("ALTER TABLE `utilizadores` ADD `senha` VARCHAR(255) NOT NULL DEFAULT ''");
        }
    }

    if (!in_array('cargo', $colunasUsers)) {
        $conn->query("ALTER TABLE `utilizadores` ADD `cargo` VARCHAR(50) NOT NULL DEFAULT 'Administrador'");
    }

    if (!in_array('criado_em', $colunasUsers)) {
        $conn->query("ALTER TABLE `utilizadores` ADD `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }
}

// Garante compatibilidade e colunas necessárias na tabela `noticias`
$colunasNoticias = [];
$resColsNoticias = $conn->query("SHOW COLUMNS FROM `noticias`");
if ($resColsNoticias) {
    while ($col = $resColsNoticias->fetch_assoc()) {
        $colunasNoticias[] = strtolower($col['Field']);
    }
}

if (!in_array('titulo', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `titulo` VARCHAR(255) NOT NULL DEFAULT ''");
}
if (!in_array('subtitulo', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `subtitulo` VARCHAR(255) DEFAULT NULL");
}
if (!in_array('categoria', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `categoria` VARCHAR(60) NOT NULL DEFAULT 'Geral'");
}
if (!in_array('conteudo', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `conteudo` TEXT NOT NULL");
}
if (!in_array('imagem', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `imagem` VARCHAR(255) DEFAULT NULL");
}
if (!in_array('autor_id', $colunasNoticias)) {
    $conn->query("ALTER TABLE `noticias` ADD `autor_id` INT NULL");
}
if (!in_array('data_publicacao', $colunasNoticias)) {
    if (in_array('data', $colunasNoticias)) {
        $conn->query("ALTER TABLE `noticias` CHANGE `data` `data_publicacao` DATETIME DEFAULT CURRENT_TIMESTAMP");
    } elseif (in_array('criado_em', $colunasNoticias)) {
        $conn->query("ALTER TABLE `noticias` CHANGE `criado_em` `data_publicacao` DATETIME DEFAULT CURRENT_TIMESTAMP");
    } else {
        $conn->query("ALTER TABLE `noticias` ADD `data_publicacao` DATETIME DEFAULT CURRENT_TIMESTAMP");
    }
}

// Garante que existe pelo menos um administrador inicial
$checkAdmin = $conn->query("SELECT id FROM `utilizadores` LIMIT 1");
$adminId = null;

if ($checkAdmin && $checkAdmin->num_rows === 0) {
    // Senha padrão: admin123
    $hashPadrao = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO `utilizadores` (`nome`, `email`, `senha`, `cargo`) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $nome = "Administrador Principal";
        $email = "admin@noticias.pt";
        $cargo = "Administrador";
        $stmt->bind_param("ssss", $nome, $email, $hashPadrao, $cargo);
        $stmt->execute();
        $adminId = $stmt->insert_id;
        $stmt->close();
    }
} else {
    $userRow = $conn->query("SELECT id FROM `utilizadores` ORDER BY id ASC LIMIT 1");
    if ($userRow && $row = $userRow->fetch_assoc()) {
        $adminId = (int)$row['id'];
    }
}

// Insere notícias de exemplo se a tabela notícias estiver vazia
$checkNoticias = $conn->query("SELECT id FROM `noticias` LIMIT 1");
if ($checkNoticias && $checkNoticias->num_rows === 0 && !empty($adminId)) {
    $noticiasExemplo = [
        [
            'Avanços na Inteligência Artificial transformam o mercado tecnológico',
            'Novos modelos de linguagem e automação prometem maior eficiência.',
            'Tecnologia',
            'O setor de tecnologia continua a evoluir em ritmo acelerado com o avanço de ferramentas de Inteligência Artificial generativa. Especialistas destacam que a capacitação de profissionais e a integração responsável dessas inovações serão cruciais para a competitividade das empresas nos próximos anos.',
            'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=900&q=80',
            $adminId
        ],
        [
            'Economia sustentável ganha destaque na nova cimeira europeia',
            'Líderes discutem metas de redução de emissões e incentivos à transição energética.',
            'Economia',
            'Representantes de diversos países reuniram-se para definir novas diretrizes sobre sustentabilidade e transição verde. Foram anunciados investimentos de larga escala em energia eólica e solar, assim como incentivos fiscais para empresas com frotas descarbonizadas.',
            'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?auto=format&fit=crop&w=900&q=80',
            $adminId
        ],
        [
            'Festival cultural atrai milhares ao centro histórico',
            'Música, gastronomia e exposições celebram as tradições locais este fim de semana.',
            'Cultura',
            'Com mais de cinquenta artistas nacionais e internacionais, o festival anual de cultura regressou com uma programação diversificada. As ruas encheram-se de visitantes para assistir a concertos ao ar livre e feiras de artesanato local.',
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=900&q=80',
            $adminId
        ]
    ];

    $stmtNoticia = $conn->prepare("INSERT INTO `noticias` (`titulo`, `subtitulo`, `categoria`, `conteudo`, `imagem`, `autor_id`) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmtNoticia) {
        foreach ($noticiasExemplo as $n) {
            $stmtNoticia->bind_param("sssssi", $n[0], $n[1], $n[2], $n[3], $n[4], $n[5]);
            $stmtNoticia->execute();
        }
        $stmtNoticia->close();
    }
}
?>
