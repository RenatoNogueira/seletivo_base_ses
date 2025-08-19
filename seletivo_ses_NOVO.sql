-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/08/2025 às 19:18
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `seletivo_ses`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('super_admin','admin','moderador') DEFAULT 'admin',
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `administradores`
--

INSERT INTO `administradores` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `ativo`, `ultimo_login`, `data_criacao`, `data_atualizacao`) VALUES
(1, 'Administrador', 'ADMIN@saude.ma.gov.br', '$2y$10$toWjYo2v6SfMLhG2EmutJ.DOAkBB1XwiW1rB0koxfYC7.b.10t7eO', 'super_admin', 1, '2025-08-19 14:17:29', '2025-08-19 13:00:32', '2025-08-19 17:17:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `arquivos_upload`
--

CREATE TABLE `arquivos_upload` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `nome_salvo` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `tamanho` bigint(20) DEFAULT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_documento` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_sistema`
--

CREATE TABLE `configuracoes_sistema` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('texto','numero','boolean','data','email') DEFAULT 'texto',
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cotas`
--

CREATE TABLE `cotas` (
  `id` int(11) NOT NULL,
  `reserva_id` int(11) NOT NULL,
  `categoria` enum('pcd','acoes_afirmativas','escola_saude','regionais_saude') NOT NULL,
  `percentual` decimal(5,2) DEFAULT NULL,
  `total_cotas` int(11) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cursos_formacoes`
--

CREATE TABLE `cursos_formacoes` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `nivel` varchar(100) DEFAULT NULL,
  `area_formacao` varchar(255) DEFAULT NULL,
  `registro_profissional` varchar(100) DEFAULT NULL,
  `instituicao` varchar(255) DEFAULT NULL,
  `ano_conclusao` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formularios`
--

CREATE TABLE `formularios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `link_video` varchar(255) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `pcd` tinyint(1) DEFAULT 0,
  `tipo_deficiencia` varchar(100) DEFAULT NULL,
  `outra_deficiencia` varchar(255) DEFAULT NULL,
  `descricao_deficiencia` text DEFAULT NULL,
  `limitacoes_funcionais` text DEFAULT NULL,
  `necessidades_apoio` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rascunho_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rascunho_data`)),
  `objetivo_pgs` text DEFAULT NULL,
  `atividades_pgs` text DEFAULT NULL,
  `contribuicao_pgs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_admin`
--

CREATE TABLE `logs_admin` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `logs_admin`
--

INSERT INTO `logs_admin` (`id`, `admin_id`, `acao`, `descricao`, `ip_address`, `user_agent`, `data_acao`) VALUES
(4, 1, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:16'),
(5, 1, 'acesso_dashboard', 'Acesso ao dashboard administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:16'),
(6, 1, 'acesso_usuarios', 'Acesso à página de usuários', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:18'),
(7, 1, 'excluir_usuario', 'Usuário  (ID: 1) excluído', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:22'),
(8, 1, 'acesso_dashboard', 'Acesso ao dashboard administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:29'),
(9, 1, 'logout', 'Logout realizado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 14:23:35'),
(10, 1, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:17:29'),
(11, 1, 'acesso_dashboard', 'Acesso ao dashboard administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:17:29'),
(12, 1, 'acesso_usuarios', 'Acesso à página de usuários', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:17:31'),
(13, 1, 'excluir_usuario', 'Usuário  (ID: 2) excluído', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:17:33'),
(14, 1, 'acesso_dashboard', 'Acesso ao dashboard administrativo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-19 17:17:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `reservas_vagas`
--

CREATE TABLE `reservas_vagas` (
  `id` int(11) NOT NULL,
  `vaga_id` int(11) NOT NULL,
  `tipo_destino` enum('estado','municipio') NOT NULL,
  `percentual` decimal(5,2) NOT NULL,
  `total_reservado` int(11) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `data_nascimento` date NOT NULL,
  `nome_completo` varchar(255) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `estado_civil` varchar(50) DEFAULT NULL,
  `nacionalidade` varchar(100) DEFAULT NULL,
  `telefone_fixo` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_alternativo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `vagas`
--

CREATE TABLE `vagas` (
  `id` int(11) NOT NULL,
  `total_vagas` int(11) NOT NULL DEFAULT 0,
  `edital` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_estatisticas_sistema`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_estatisticas_sistema` (
`total_usuarios` bigint(21)
,`total_formularios` bigint(21)
,`total_arquivos` bigint(21)
,`total_cursos` bigint(21)
,`usuarios_hoje` bigint(21)
,`usuarios_semana` bigint(21)
,`usuarios_mes` bigint(21)
,`formularios_hoje` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_relatorio_usuarios`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_relatorio_usuarios` (
`usuario_id` int(11)
,`cpf` varchar(14)
,`nome_completo` varchar(255)
,`email` varchar(255)
,`rg` varchar(20)
,`estado_civil` varchar(50)
,`nacionalidade` varchar(100)
,`telefone_fixo` varchar(20)
,`celular` varchar(20)
,`email_alternativo` varchar(255)
,`data_nascimento` date
,`data_cadastro` timestamp
,`formulario_id` int(11)
,`link_video` varchar(255)
,`cep` varchar(10)
,`logradouro` varchar(255)
,`numero` varchar(50)
,`complemento` varchar(255)
,`bairro` varchar(255)
,`cidade` varchar(255)
,`estado` varchar(255)
,`pcd` tinyint(1)
,`tipo_deficiencia` varchar(100)
,`outra_deficiencia` varchar(255)
,`descricao_deficiencia` text
,`limitacoes_funcionais` text
,`necessidades_apoio` text
,`objetivo_pgs` text
,`atividades_pgs` text
,`contribuicao_pgs` text
,`data_envio_formulario` timestamp
,`total_cursos` bigint(21)
,`total_arquivos` bigint(21)
,`areas_formacao` mediumtext
,`registros_profissionais` mediumtext
,`tipos_documentos` mediumtext
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_estatisticas_sistema`
--
DROP TABLE IF EXISTS `vw_estatisticas_sistema`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_estatisticas_sistema`  AS SELECT (select count(0) from `usuarios`) AS `total_usuarios`, (select count(0) from `formularios`) AS `total_formularios`, (select count(0) from `arquivos_upload`) AS `total_arquivos`, (select count(0) from `cursos_formacoes`) AS `total_cursos`, (select count(0) from `usuarios` where cast(`usuarios`.`created_at` as date) = curdate()) AS `usuarios_hoje`, (select count(0) from `usuarios` where week(`usuarios`.`created_at`) = week(curdate())) AS `usuarios_semana`, (select count(0) from `usuarios` where month(`usuarios`.`created_at`) = month(curdate())) AS `usuarios_mes`, (select count(0) from `formularios` where cast(`formularios`.`submitted_at` as date) = curdate()) AS `formularios_hoje` ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_relatorio_usuarios`
--
DROP TABLE IF EXISTS `vw_relatorio_usuarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_relatorio_usuarios`  AS SELECT `u`.`id` AS `usuario_id`, `u`.`cpf` AS `cpf`, `u`.`nome_completo` AS `nome_completo`, `u`.`email` AS `email`, `u`.`rg` AS `rg`, `u`.`estado_civil` AS `estado_civil`, `u`.`nacionalidade` AS `nacionalidade`, `u`.`telefone_fixo` AS `telefone_fixo`, `u`.`celular` AS `celular`, `u`.`email_alternativo` AS `email_alternativo`, `u`.`data_nascimento` AS `data_nascimento`, `u`.`created_at` AS `data_cadastro`, `f`.`id` AS `formulario_id`, `f`.`link_video` AS `link_video`, `f`.`cep` AS `cep`, `f`.`logradouro` AS `logradouro`, `f`.`numero` AS `numero`, `f`.`complemento` AS `complemento`, `f`.`bairro` AS `bairro`, `f`.`cidade` AS `cidade`, `f`.`estado` AS `estado`, `f`.`pcd` AS `pcd`, `f`.`tipo_deficiencia` AS `tipo_deficiencia`, `f`.`outra_deficiencia` AS `outra_deficiencia`, `f`.`descricao_deficiencia` AS `descricao_deficiencia`, `f`.`limitacoes_funcionais` AS `limitacoes_funcionais`, `f`.`necessidades_apoio` AS `necessidades_apoio`, `f`.`objetivo_pgs` AS `objetivo_pgs`, `f`.`atividades_pgs` AS `atividades_pgs`, `f`.`contribuicao_pgs` AS `contribuicao_pgs`, `f`.`submitted_at` AS `data_envio_formulario`, count(distinct `cf`.`id`) AS `total_cursos`, count(distinct `au`.`id`) AS `total_arquivos`, group_concat(distinct `cf`.`area_formacao` separator ', ') AS `areas_formacao`, group_concat(distinct `cf`.`registro_profissional` separator ', ') AS `registros_profissionais`, group_concat(distinct `au`.`tipo_documento` separator ', ') AS `tipos_documentos` FROM (((`usuarios` `u` left join `formularios` `f` on(`u`.`id` = `f`.`usuario_id`)) left join `cursos_formacoes` `cf` on(`f`.`id` = `cf`.`formulario_id`)) left join `arquivos_upload` `au` on(`f`.`id` = `au`.`formulario_id`)) GROUP BY `u`.`id`, `f`.`id` ORDER BY `u`.`created_at` DESC ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `arquivos_upload`
--
ALTER TABLE `arquivos_upload`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_salvo` (`nome_salvo`),
  ADD KEY `formulario_id` (`formulario_id`);

--
-- Índices de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `cotas`
--
ALTER TABLE `cotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reserva_id` (`reserva_id`);

--
-- Índices de tabela `cursos_formacoes`
--
ALTER TABLE `cursos_formacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formulario_id` (`formulario_id`);

--
-- Índices de tabela `formularios`
--
ALTER TABLE `formularios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `logs_admin`
--
ALTER TABLE `logs_admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Índices de tabela `reservas_vagas`
--
ALTER TABLE `reservas_vagas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vaga_id` (`vaga_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `vagas`
--
ALTER TABLE `vagas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `arquivos_upload`
--
ALTER TABLE `arquivos_upload`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cotas`
--
ALTER TABLE `cotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cursos_formacoes`
--
ALTER TABLE `cursos_formacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `formularios`
--
ALTER TABLE `formularios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `logs_admin`
--
ALTER TABLE `logs_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `reservas_vagas`
--
ALTER TABLE `reservas_vagas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `vagas`
--
ALTER TABLE `vagas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `arquivos_upload`
--
ALTER TABLE `arquivos_upload`
  ADD CONSTRAINT `arquivos_upload_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`);

--
-- Restrições para tabelas `cotas`
--
ALTER TABLE `cotas`
  ADD CONSTRAINT `cotas_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas_vagas` (`id`);

--
-- Restrições para tabelas `cursos_formacoes`
--
ALTER TABLE `cursos_formacoes`
  ADD CONSTRAINT `cursos_formacoes_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`);

--
-- Restrições para tabelas `formularios`
--
ALTER TABLE `formularios`
  ADD CONSTRAINT `formularios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `logs_admin`
--
ALTER TABLE `logs_admin`
  ADD CONSTRAINT `logs_admin_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `administradores` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `reservas_vagas`
--
ALTER TABLE `reservas_vagas`
  ADD CONSTRAINT `reservas_vagas_ibfk_1` FOREIGN KEY (`vaga_id`) REFERENCES `vagas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
