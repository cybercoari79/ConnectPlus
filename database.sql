-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geraÃ§Ã£o: 30/09/2026 Ã s 22:08
-- VersÃ£o do servidor: 10.11.19-MariaDB-cll-lve-log
-- VersÃ£o do PHP: 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cyberco1_cyber`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cdn_pool`
--

CREATE TABLE `cdn_pool` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `url` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configs`
--

CREATE TABLE `configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `version_name` varchar(100) NOT NULL DEFAULT '1',
  `app_version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `update_apk` text NOT NULL,
  `actualization` varchar(10) NOT NULL DEFAULT 'false',
  `udp_port` varchar(100) NOT NULL DEFAULT '7300',
  `contato` text NOT NULL,
  `site` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `configs`
--

INSERT INTO `configs` (`id`, `user_id`, `version`, `version_name`, `app_version`, `update_apk`, `actualization`, `udp_port`, `contato`, `site`) VALUES
(1, 1, 10, '1', 1, '', 'false', '7300', 'https://t.me/coari', 'https://speedteste.net');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `username` varchar(40) NOT NULL DEFAULT '',
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `username`, `action`, `module`, `description`, `created_at`) VALUES
(1, 1, 'admin', 'AtualizaÃ§Ã£o', 'Perfil', 'Perfil/senha atualizado', '2026-09-20 12:39:20'),
(2, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-21 17:05:42'),
(3, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-21 17:14:50'),
(4, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-21 18:04:13'),
(5, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-21 18:05:57'),
(6, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-21 20:43:28'),
(7, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 11:08:50'),
(8, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 11:33:21'),
(9, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 11:50:34'),
(10, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 11:50:35'),
(11, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 11:50:39'),
(12, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 12:29:56'),
(13, 1, 'admin', 'RegeneraÃ§Ã£o', 'UUID', 'Novo UUID de atualizaÃ§Ã£o', '2026-09-22 12:35:11'),
(14, 1, 'admin', 'Salvamento', 'SMS', 'Mensagem atualizada', '2026-09-22 13:08:54'),
(15, 1, 'admin', 'Salvamento', 'SMS', 'Mensagem atualizada', '2026-09-22 13:37:00'),
(16, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 13:41:59'),
(17, 1, 'admin', 'Salvamento', 'SMS', 'Mensagem atualizada', '2026-09-22 13:44:32'),
(18, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 13:46:11'),
(19, 1, 'admin', 'Salvamento', 'SMS', 'Mensagem atualizada', '2026-09-22 14:12:14'),
(20, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 14:15:38'),
(21, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 14:16:30'),
(22, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 14:17:34'),
(23, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 14:19:19'),
(24, 1, 'admin', 'Salvamento', 'SMS', 'Mensagem atualizada', '2026-09-22 14:20:20'),
(25, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 16:33:20'),
(26, 1, 'admin', 'Salvamento', 'ConfiguraÃ§Ã£o', 'ConfiguraÃ§Ã£o geral e servidores', '2026-09-22 16:37:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orders`
--

CREATE TABLE `orders` (
  `id` varchar(100) NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(190) NOT NULL,
  `plan_id` varchar(50) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `mp_status` varchar(50) DEFAULT NULL,
  `payment_error` text DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `expires_at_after` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `servers`
--

CREATE TABLE `servers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL DEFAULT '',
  `color_name` varchar(30) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `color_description` varchar(30) NOT NULL DEFAULT '',
  `flag` text NOT NULL,
  `server_ip` varchar(255) NOT NULL DEFAULT '',
  `server_port` varchar(30) NOT NULL DEFAULT '',
  `check_user` varchar(255) NOT NULL DEFAULT '',
  `username` text NOT NULL,
  `password` text NOT NULL,
  `payload` mediumtext NOT NULL,
  `proxy_ip` varchar(255) NOT NULL DEFAULT '',
  `proxy_port` varchar(30) NOT NULL DEFAULT '',
  `sni` varchar(255) NOT NULL DEFAULT '',
  `path` text NOT NULL,
  `tls_version` varchar(50) NOT NULL DEFAULT '',
  `color` varchar(30) NOT NULL DEFAULT '',
  `info` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `servers`
--

INSERT INTO `servers` (`id`, `user_id`, `name`, `color_name`, `description`, `color_description`, `flag`, `server_ip`, `server_port`, `check_user`, `username`, `password`, `payload`, `proxy_ip`, `proxy_port`, `sni`, `path`, `tls_version`, `color`, `info`) VALUES
(12, 1, 'Tim ', '#ffffff', 'Tim', '#ffffff', 'https://cybercoari.com.br/cyber/imagens/tim.png', '', '', '', '', '', 'GET / HTTP/1.1[lf]Host: ln.centraldacdn.com[lf]Upgrade: websocket[lf][lf]#GET /app8 HTTP/1.1[lf]Host: ln.centraldacdn.com[lf]Upgrade: websocket[lf][lf]', '', '', '', '', '', '#0000ff', 'Tlsws');

-- --------------------------------------------------------

--
-- Estrutura para tabela `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` mediumtext NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'pixKey', '', '2026-09-20 12:17:30'),
(2, 'planPrices', '{\"monthly\":0.01000000000000000020816681711721685132943093776702880859375,\"quarterly\":4.9900000000000002131628207280300557613372802734375,\"yearly\":9.9900000000000002131628207280300557613372802734375}', '2026-09-20 12:24:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sms`
--

CREATE TABLE `sms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(30) NOT NULL DEFAULT '1',
  `update_url` text NOT NULL,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `sms`
--

INSERT INTO `sms` (`id`, `user_id`, `version`, `update_url`, `notes`) VALUES
(1, 1, '5', '', 'Teste de sms tudo bem. kkkkkkk');

-- --------------------------------------------------------

--
-- Estrutura para tabela `themes`
--

CREATE TABLE `themes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(30) NOT NULL DEFAULT '1',
  `app_name` varchar(190) NOT NULL DEFAULT 'ConnectPlus',
  `img_fundo` text NOT NULL,
  `img_logo` text NOT NULL,
  `img_banner` text NOT NULL,
  `img_menu` text NOT NULL,
  `img_logs` text NOT NULL,
  `img_check` text NOT NULL,
  `img_user` text NOT NULL,
  `img_pass` text NOT NULL,
  `img_update` text NOT NULL,
  `color_one` varchar(30) NOT NULL DEFAULT '',
  `color_two` varchar(30) NOT NULL DEFAULT '',
  `color_starter` varchar(30) NOT NULL DEFAULT '',
  `color_dialogs` varchar(30) NOT NULL DEFAULT '',
  `color_buttons` varchar(30) NOT NULL DEFAULT '',
  `update_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `themes`
--

INSERT INTO `themes` (`id`, `user_id`, `version`, `app_name`, `img_fundo`, `img_logo`, `img_banner`, `img_menu`, `img_logs`, `img_check`, `img_user`, `img_pass`, `img_update`, `color_one`, `color_two`, `color_starter`, `color_dialogs`, `color_buttons`, `update_url`) VALUES
(1, 1, '7', 'ConnectPlus v1', 'https://cybercoari.com.br/cyber/imagens/fundo.jpg', 'https://cybercoari.com.br/cyber/imagens/logo.png', 'https://cybercoari.com.br/cyber/imagens/', 'https://cybercoari.com.br/cyber/imagens/menu.png', 'https://cybercoari.com.br/cyber/imagens/logs.png', 'https://cybercoari.com.br/cyber/imagens/check.png', 'https://cybercoari.com.br/cyber/imagens/user.png', 'https://cybercoari.com.br/cyber/imagens/pass.png', 'https://cybercoari.com.br/cyber/imagens/updade.png', '#1Affffff', '#000000', '#121212', '#121212', '#121212', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `plan` varchar(50) NOT NULL DEFAULT 'trial',
  `expires_at` datetime DEFAULT NULL,
  `update_uuid` char(36) DEFAULT NULL,
  `password_reset_token_hash` char(64) DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `is_admin`, `status`, `active`, `plan`, `expires_at`, `update_uuid`, `password_reset_token_hash`, `password_reset_expires_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@cybercoari.com.br', '$2y$10$hGoUv3g8XKi0DQLiYjknYOkodOZe2AOxcNssX/JIwaLxkOFH1UhU6', 'admin', 1, 'active', 1, 'trial', '2026-09-23 15:16:01', '877ca2b1-c0e4-4149-8c09-dd2f216bfd7c', NULL, NULL, '2026-09-20 12:16:01', '2026-09-22 12:35:11');

--
-- Ãndices para tabelas despejadas
--

--
-- Ãndices de tabela `cdn_pool`
--
ALTER TABLE `cdn_pool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cdn_user` (`user_id`);

--
-- Ãndices de tabela `configs`
--
ALTER TABLE `configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_configs_user` (`user_id`);

--
-- Ãndices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`user_id`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Ãndices de tabela `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_user` (`username`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Ãndices de tabela `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_servers_user` (`user_id`);

--
-- Ãndices de tabela `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_settings_key` (`setting_key`);

--
-- Ãndices de tabela `sms`
--
ALTER TABLE `sms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sms_user` (`user_id`);

--
-- Ãndices de tabela `themes`
--
ALTER TABLE `themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_themes_user` (`user_id`);

--
-- Ãndices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_username` (`username`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD UNIQUE KEY `uk_users_uuid` (`update_uuid`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cdn_pool`
--
ALTER TABLE `cdn_pool`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configs`
--
ALTER TABLE `configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `servers`
--
ALTER TABLE `servers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `sms`
--
ALTER TABLE `sms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `themes`
--
ALTER TABLE `themes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- RestriÃ§Ãµes para tabelas despejadas
--

--
-- RestriÃ§Ãµes para tabelas `cdn_pool`
--
ALTER TABLE `cdn_pool`
  ADD CONSTRAINT `fk_cdn_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `configs`
--
ALTER TABLE `configs`
  ADD CONSTRAINT `fk_configs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- RestriÃ§Ãµes para tabelas `servers`
--
ALTER TABLE `servers`
  ADD CONSTRAINT `fk_servers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `sms`
--
ALTER TABLE `sms`
  ADD CONSTRAINT `fk_sms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- RestriÃ§Ãµes para tabelas `themes`
--
ALTER TABLE `themes`
  ADD CONSTRAINT `fk_themes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;