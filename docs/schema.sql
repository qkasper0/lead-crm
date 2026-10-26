-- Схема БД для импорта через phpMyAdmin (если на хостинге нет терминала для `php yii migrate`).
-- Повторяет миграции m261026_000001_init и m261026_000002_user_api_token и отмечает их применёнными, чтобы `php yii migrate`
-- в будущем не пытался создать таблицы повторно.

SET NAMES utf8mb4;

CREATE TABLE `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migration` (`version`, `apply_time`) VALUES
  ('m000000_000000_base', UNIX_TIMESTAMP()),
  ('m261026_000001_init', UNIX_TIMESTAMP()),
  ('m261026_000002_user_api_token', UNIX_TIMESTAMP());

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `auth_key` varchar(32) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'manager',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` int(11) NOT NULL,
  `api_token_hash` varchar(64) DEFAULT NULL,
  `api_token_hint` varchar(12) DEFAULT NULL,
  `api_token_created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_user_api_token` (`api_token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `source` varchar(100) NOT NULL DEFAULT 'website',
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `manager_id` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lead_status` (`status`),
  KEY `idx_lead_manager_status` (`manager_id`, `status`),
  KEY `idx_lead_phone` (`phone`),
  KEY `idx_lead_created` (`created_at`),
  CONSTRAINT `fk_lead_manager` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `text` text NOT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comment_lead` (`lead_id`),
  CONSTRAINT `fk_comment_lead` FOREIGN KEY (`lead_id`) REFERENCES `lead` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `field` varchar(30) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_history_lead` (`lead_id`, `created_at`),
  CONSTRAINT `fk_history_lead` FOREIGN KEY (`lead_id`) REFERENCES `lead` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Первый администратор. Хеш пароля сгенерируйте командой:
--   php -r 'echo password_hash("ваш-пароль", PASSWORD_BCRYPT), PHP_EOL;'
-- и подставьте вместо PASTE_BCRYPT_HASH_HERE.
INSERT INTO `user` (`name`, `email`, `password_hash`, `auth_key`, `role`, `is_active`, `created_at`)
VALUES ('Администратор', 'admin@example.com', 'PASTE_BCRYPT_HASH_HERE', MD5(RAND()), 'admin', 1, UNIX_TIMESTAMP());
