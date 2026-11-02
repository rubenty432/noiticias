-- Script de criação da Base de Dados para o Sistema de Notícias
-- Compatível com MySQL / MariaDB (XAMPP / phpMyAdmin)

CREATE DATABASE IF NOT EXISTS `noticias` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `noticias`;

-- Tabela de Utilizadores
CREATE TABLE IF NOT EXISTS `utilizadores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `cargo` VARCHAR(50) NOT NULL DEFAULT 'Administrador',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Notícias
CREATE TABLE IF NOT EXISTS `noticias` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utilizador Administrador Padrão
-- Credenciais:
-- Email: admin@noticias.pt
-- Senha: admin123
INSERT INTO `utilizadores` (`id`, `nome`, `email`, `senha`, `cargo`, `criado_em`) 
VALUES (
  1, 
  'Administrador Principal', 
  'admin@noticias.pt', 
  '$2y$10$wO3nC0WnIviwN8Z8Y4FjYedKqK0O4e9VvWb8.bNq1dM4g8N3P.KWe', 
  'Administrador', 
  NOW()
) ON DUPLICATE KEY UPDATE `email` = `email`;

-- Notícias de Exemplo
INSERT INTO `noticias` (`titulo`, `subtitulo`, `categoria`, `conteudo`, `imagem`, `autor_id`, `data_publicacao`) VALUES
(
  'Avanços na Inteligência Artificial transformam o mercado tecnológico',
  'Novos modelos de linguagem e automação prometem maior eficiência para empresas.',
  'Tecnologia',
  'O setor de tecnologia continua a evoluir em ritmo acelerado com o avanço de ferramentas de Inteligência Artificial generativa. Especialistas destacam que a capacitação de profissionais e a integração responsável dessas inovações serão cruciais para a competitividade das empresas nos próximos anos. Diversos setores, desde a saúde até à educação, já começam a colher os primeiros frutos da automação inteligente.',
  'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=900&q=80',
  1,
  NOW() - INTERVAL 2 HOUR
),
(
  'Economia sustentável ganha destaque na nova cimeira europeia',
  'Líderes discutem metas de redução de emissões e incentivos à transição energética.',
  'Economia',
  'Representantes de diversos países reuniram-se para definir novas diretrizes sobre sustentabilidade e transição verde. Foram anunciados investimentos de larga escala em energia eólica e solar, assim como incentivos fiscais para empresas com frotas descarbonizadas. O objetivo é acelerar a meta de neutralidade carbónica até 2050.',
  'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?auto=format&fit=crop&w=900&q=80',
  1,
  NOW() - INTERVAL 1 DAY
),
(
  'Festival cultural atrai milhares ao centro histórico',
  'Música, gastronomia e exposições celebram as tradições locais este fim de semana.',
  'Cultura',
  'Com mais de cinquenta artistas nacionais e internacionais, o festival anual de cultura regressou com uma programação diversificada. As ruas encheram-se de visitantes de todas as idades para assistir a concertos ao ar livre, provas gastronómicas e feiras de artesanato local, promovendo a economia e o turismo da região.',
  'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=900&q=80',
  1,
  NOW() - INTERVAL 2 DAY
);
