-- Painel de Entregas: estrutura e dados iniciais
-- Importe este arquivo pelo phpMyAdmin (aba Importar). Ele cria o banco painel_entregas.

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS painel_entregas;
CREATE DATABASE painel_entregas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE painel_entregas;


CREATE TABLE empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  razao_social VARCHAR(150) NOT NULL,
  cnpj CHAR(18) NOT NULL UNIQUE,
  regime ENUM('Simples Nacional', 'Lucro Presumido', 'Lucro Real') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE obrigacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  departamento ENUM('Fiscal', 'Pessoal', 'Contábil') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE entregas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  obrigacao_id INT NOT NULL,
  competencia CHAR(7) NOT NULL COMMENT 'Formato AAAA-MM',
  vencimento DATE NOT NULL,
  data_entrega DATE NULL,
  CONSTRAINT uk_entrega_empresa_obrigacao_competencia UNIQUE (empresa_id, obrigacao_id, competencia),
  CONSTRAINT fk_entrega_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id),
  CONSTRAINT fk_entrega_obrigacao FOREIGN KEY (obrigacao_id) REFERENCES obrigacoes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO empresas (razao_social, cnpj, regime) VALUES
  ('Mercado Bom Preço Ltda', '12.345.678/0001-90', 'Simples Nacional'),
  ('Padaria D''Ávila Ltda', '23.456.789/0001-01', 'Simples Nacional'),
  ('Construtora Horizonte S.A.', '34.567.890/0001-12', 'Lucro Real'),
  ('Clínica Vida Plena Ltda', '45.678.901/0001-23', 'Lucro Presumido'),
  ('Auto Peças Minas Ltda', '56.789.012/0001-34', 'Lucro Presumido'),
  ('Tech Serra Sistemas Ltda', '67.890.123/0001-45', 'Simples Nacional'),
  ('Transportadora Rota 40 Ltda', '78.901.234/0001-56', 'Lucro Real'),
  ('Café & Prosa ME', '89.012.345/0001-67', 'Simples Nacional');

INSERT INTO obrigacoes (nome, departamento) VALUES
  ('DAS', 'Fiscal'),
  ('EFD ICMS/IPI', 'Fiscal'),
  ('DCTFWeb', 'Fiscal'),
  ('FGTS Digital', 'Pessoal'),
  ('eSocial - Folha', 'Pessoal'),
  ('Balancete Mensal', 'Contábil'),
  ('ECD', 'Contábil');

-- As datas são relativas ao dia em que o banco é criado,
-- para que sempre existam entregas pendentes, atrasadas e entregues.
INSERT INTO entregas (empresa_id, obrigacao_id, competencia, vencimento, data_entrega) VALUES
  (1, 1, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 17 DAY)),
  (1, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
  (1, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL),
  (1, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 18 DAY), NULL),
  (2, 1, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL),
  (2, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
  (2, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), CURDATE(), NULL),
  (3, 2, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL),
  (3, 3, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
  (3, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL),
  (3, 6, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL),
  (3, 7, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 12 MONTH), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 25 DAY), NULL),
  (4, 3, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
  (4, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY)),
  (4, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL),
  (4, 6, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL),
  (5, 2, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
  (5, 3, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 9 DAY), NULL),
  (5, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL),
  (6, 1, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY)),
  (6, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL),
  (6, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 18 DAY), NULL),
  (7, 2, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 5 DAY), NULL),
  (7, 3, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 11 DAY)),
  (7, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
  (7, 6, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL),
  (8, 1, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL),
  (8, 4, DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m'), DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL),
  (8, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), CURDATE(), NULL),
  (8, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), DATE_ADD(CURDATE(), INTERVAL 18 DAY), NULL);
