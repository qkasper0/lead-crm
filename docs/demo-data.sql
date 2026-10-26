-- Тестовые данные для Lead CRM.
-- 3 менеджера (пароль demo12345) и 24 лида за последние 30 дней:
-- разные статусы, источники, комментарии и история изменений.
-- Выполнять в phpMyAdmin → вкладка SQL. Можно запускать повторно: старые демо-данные удаляются.

SET NAMES utf8mb4;
START TRANSACTION;

-- Очистка прошлых демо-данных (лиды с телефонами +38099000xxxx и демо-менеджеры)
DELETE FROM `lead` WHERE `phone` LIKE '+38099000%';
DELETE FROM `user` WHERE `email` IN ('olga@example.com', 'igor@example.com', 'marina@example.com');

-- Менеджеры
INSERT INTO `user` (`name`, `email`, `password_hash`, `auth_key`, `role`, `is_active`, `created_at`) VALUES
  ('Ольга Коваль', 'olga@example.com', '$2y$13$UdfTuxcoOc.Lmf/j.LJLG.N0CQvk7OnOMLLFJBtlQ7strWZcZUQrW', MD5(RAND()), 'manager', 1, UNIX_TIMESTAMP() - 40*86400),
  ('Игорь Шевчук', 'igor@example.com', '$2y$13$UdfTuxcoOc.Lmf/j.LJLG.N0CQvk7OnOMLLFJBtlQ7strWZcZUQrW', MD5(RAND()), 'manager', 1, UNIX_TIMESTAMP() - 40*86400),
  ('Марина Бондар', 'marina@example.com', '$2y$13$UdfTuxcoOc.Lmf/j.LJLG.N0CQvk7OnOMLLFJBtlQ7strWZcZUQrW', MD5(RAND()), 'manager', 1, UNIX_TIMESTAMP() - 40*86400);

SET @olga  = (SELECT id FROM `user` WHERE email = 'olga@example.com');
SET @igor  = (SELECT id FROM `user` WHERE email = 'igor@example.com');
SET @marina = (SELECT id FROM `user` WHERE email = 'marina@example.com');
SET @now = UNIX_TIMESTAMP();

-- 1. Анна Мельник
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Анна Мельник', '+380990001000', 'facebook', 'new', @olga, @now - 40140, LEAST(@now, @now - 40140 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 40140);
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'facebook', @now - 40140),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 40140),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 40140);

-- 2. Дмитрий Бондаренко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Дмитрий Бондаренко', '+380990001037', 'website', 'new', @igor, @now - 13920, LEAST(@now, @now - 13920 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 13920);
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 13920),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 13920),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 13920);

-- 3. Светлана Ткач
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Светлана Ткач', '+380990001074', 'landing', 'in_work', @marina, @now - 68580, LEAST(@now, @now - 68580 + 18000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 68580),
  (@l, @marina, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 68580 + 18000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 68580),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 68580),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 68580),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 68580 + 18000)),
  (@l, @marina, 'comment', NULL, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 68580 + 18000));

-- 4. Максим Кравец
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Максим Кравец', '+380990001111', 'landing', 'success', @olga, @now - 1173840, LEAST(@now, @now - 1173840 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 1173840),
  (@l, @olga, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1173840 + 18000)),
  (@l, @olga, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 1173840 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 1173840),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 1173840),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 1173840),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 1173840 + 18000)),
  (@l, @olga, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1173840 + 18000)),
  (@l, @olga, 'status', 'in_work', 'success', LEAST(@now, @now - 1173840 + 36000)),
  (@l, @olga, 'comment', NULL, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 1173840 + 36000));

-- 5. Наталья Лысенко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Наталья Лысенко', '+380990001148', 'landing', 'rejected', @igor, @now - 155220, LEAST(@now, @now - 155220 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @igor, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 155220 + 18000)),
  (@l, @igor, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 155220 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 155220),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 155220),
  (@l, @igor, 'status', 'new', 'in_work', LEAST(@now, @now - 155220 + 18000)),
  (@l, @igor, 'comment', NULL, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 155220 + 18000)),
  (@l, @igor, 'status', 'in_work', 'rejected', LEAST(@now, @now - 155220 + 36000)),
  (@l, @igor, 'comment', NULL, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 155220 + 36000));

-- 6. Роман Савченко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Роман Савченко', '+380990001185', 'landing', 'success', @marina, @now - 1602180, LEAST(@now, @now - 1602180 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 1602180),
  (@l, @marina, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1602180 + 18000)),
  (@l, @marina, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 1602180 + 36000)),
  (@l, @marina, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 1602180 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 1602180),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 1602180),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 1602180),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 1602180 + 18000)),
  (@l, @marina, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1602180 + 18000)),
  (@l, @marina, 'status', 'in_work', 'callback', LEAST(@now, @now - 1602180 + 36000)),
  (@l, @marina, 'comment', NULL, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 1602180 + 36000)),
  (@l, @marina, 'status', 'callback', 'success', LEAST(@now, @now - 1602180 + 54000)),
  (@l, @marina, 'comment', NULL, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 1602180 + 54000));

-- 7. Ирина Мороз
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Ирина Мороз', '+380990001222', 'website', 'rejected', @olga, @now - 1143240, LEAST(@now, @now - 1143240 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @olga, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 1143240 + 18000)),
  (@l, @olga, 'Клиент в отпуске, перезвонить через неделю.', LEAST(@now, @now - 1143240 + 36000)),
  (@l, @olga, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 1143240 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 1143240),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 1143240),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 1143240 + 18000)),
  (@l, @olga, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 1143240 + 18000)),
  (@l, @olga, 'status', 'in_work', 'callback', LEAST(@now, @now - 1143240 + 36000)),
  (@l, @olga, 'comment', NULL, 'Клиент в отпуске, перезвонить через неделю.', LEAST(@now, @now - 1143240 + 36000)),
  (@l, @olga, 'status', 'callback', 'rejected', LEAST(@now, @now - 1143240 + 54000)),
  (@l, @olga, 'comment', NULL, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 1143240 + 54000));

-- 8. Павел Руденко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Павел Руденко', '+380990001259', 'landing', 'new', NULL, @now - 448620, LEAST(@now, @now - 448620 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 448620);
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 448620),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 448620);

-- 9. Оксана Гончар
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Оксана Гончар', '+380990001296', 'website', 'new', @marina, @now - 326700, LEAST(@now, @now - 326700 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 326700),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 326700);

-- 10. Сергей Поляков
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Сергей Поляков', '+380990001333', 'api', 'in_work', @olga, @now - 159180, LEAST(@now, @now - 159180 + 18000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @olga, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 159180 + 18000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'api', @now - 159180),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 159180),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 159180 + 18000)),
  (@l, @olga, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 159180 + 18000));

-- 11. Юлия Литвин
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Юлия Литвин', '+380990001370', 'api', 'callback', @igor, @now - 2114940, LEAST(@now, @now - 2114940 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2114940),
  (@l, @igor, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 2114940 + 18000)),
  (@l, @igor, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 2114940 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'api', @now - 2114940),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 2114940),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2114940),
  (@l, @igor, 'status', 'new', 'in_work', LEAST(@now, @now - 2114940 + 18000)),
  (@l, @igor, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 2114940 + 18000)),
  (@l, @igor, 'status', 'in_work', 'callback', LEAST(@now, @now - 2114940 + 36000)),
  (@l, @igor, 'comment', NULL, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 2114940 + 36000));

-- 12. Андрей Ковальчук
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Андрей Ковальчук', '+380990001407', 'landing', 'success', @marina, @now - 2184240, LEAST(@now, @now - 2184240 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 2184240),
  (@l, @marina, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 2184240 + 18000)),
  (@l, @marina, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 2184240 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 2184240),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 2184240),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Хочу консультацию по стоимости', @now - 2184240),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 2184240 + 18000)),
  (@l, @marina, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 2184240 + 18000)),
  (@l, @marina, 'status', 'in_work', 'success', LEAST(@now, @now - 2184240 + 36000)),
  (@l, @marina, 'comment', NULL, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 2184240 + 36000));

-- 13. Виктория Демченко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Виктория Демченко', '+380990001444', 'google-ads', 'rejected', @olga, @now - 1443360, LEAST(@now, @now - 1443360 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 1443360),
  (@l, @olga, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 1443360 + 18000)),
  (@l, @olga, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 1443360 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'google-ads', @now - 1443360),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 1443360),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 1443360),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 1443360 + 18000)),
  (@l, @olga, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 1443360 + 18000)),
  (@l, @olga, 'status', 'in_work', 'rejected', LEAST(@now, @now - 1443360 + 36000)),
  (@l, @olga, 'comment', NULL, 'Отказ: неактуально, нашли решение сами.', LEAST(@now, @now - 1443360 + 36000));

-- 14. Олег Карпенко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Олег Карпенко', '+380990001481', 'facebook', 'success', @igor, @now - 189120, LEAST(@now, @now - 189120 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Нужен расчёт под ключ', @now - 189120),
  (@l, @igor, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 189120 + 18000)),
  (@l, @igor, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 189120 + 36000)),
  (@l, @igor, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 189120 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'facebook', @now - 189120),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 189120),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Нужен расчёт под ключ', @now - 189120),
  (@l, @igor, 'status', 'new', 'in_work', LEAST(@now, @now - 189120 + 18000)),
  (@l, @igor, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 189120 + 18000)),
  (@l, @igor, 'status', 'in_work', 'callback', LEAST(@now, @now - 189120 + 36000)),
  (@l, @igor, 'comment', NULL, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 189120 + 36000)),
  (@l, @igor, 'status', 'callback', 'success', LEAST(@now, @now - 189120 + 54000)),
  (@l, @igor, 'comment', NULL, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 189120 + 54000));

-- 15. Татьяна Олейник
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Татьяна Олейник', '+380990001518', 'website', 'rejected', @marina, @now - 1132920, LEAST(@now, @now - 1132920 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @marina, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1132920 + 18000)),
  (@l, @marina, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 1132920 + 36000)),
  (@l, @marina, 'Отказ: не устроила цена.', LEAST(@now, @now - 1132920 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 1132920),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 1132920),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 1132920 + 18000)),
  (@l, @marina, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1132920 + 18000)),
  (@l, @marina, 'status', 'in_work', 'callback', LEAST(@now, @now - 1132920 + 36000)),
  (@l, @marina, 'comment', NULL, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 1132920 + 36000)),
  (@l, @marina, 'status', 'callback', 'rejected', LEAST(@now, @now - 1132920 + 54000)),
  (@l, @marina, 'comment', NULL, 'Отказ: не устроила цена.', LEAST(@now, @now - 1132920 + 54000));

-- 16. Артём Захарченко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Артём Захарченко', '+380990001555', 'api', 'new', NULL, @now - 1946280, LEAST(@now, @now - 1946280 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'api', @now - 1946280);

-- 17. Елена Пономаренко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Елена Пономаренко', '+380990001592', 'website', 'new', @igor, @now - 2214240, LEAST(@now, @now - 2214240 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2214240);
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 2214240),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 2214240),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2214240);

-- 18. Владимир Гриценко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Владимир Гриценко', '+380990001629', 'instagram', 'in_work', @marina, @now - 1306980, LEAST(@now, @now - 1306980 + 18000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @marina, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1306980 + 18000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'instagram', @now - 1306980),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 1306980),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 1306980 + 18000)),
  (@l, @marina, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 1306980 + 18000));

-- 19. Алина Марченко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Алина Марченко', '+380990001666', 'facebook', 'callback', @olga, @now - 2301480, LEAST(@now, @now - 2301480 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2301480),
  (@l, @olga, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 2301480 + 18000)),
  (@l, @olga, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 2301480 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'facebook', @now - 2301480),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 2301480),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2301480),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 2301480 + 18000)),
  (@l, @olga, 'comment', NULL, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 2301480 + 18000)),
  (@l, @olga, 'status', 'in_work', 'callback', LEAST(@now, @now - 2301480 + 36000)),
  (@l, @olga, 'comment', NULL, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 2301480 + 36000));

-- 20. Богдан Шевченко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Богдан Шевченко', '+380990001703', 'website', 'success', @igor, @now - 974340, LEAST(@now, @now - 974340 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 974340),
  (@l, @igor, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 974340 + 18000)),
  (@l, @igor, 'Договор подписан, счёт оплачен.', LEAST(@now, @now - 974340 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 974340),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 974340),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 974340),
  (@l, @igor, 'status', 'new', 'in_work', LEAST(@now, @now - 974340 + 18000)),
  (@l, @igor, 'comment', NULL, 'Связался с клиентом, уточняю детали заказа.', LEAST(@now, @now - 974340 + 18000)),
  (@l, @igor, 'status', 'in_work', 'success', LEAST(@now, @now - 974340 + 36000)),
  (@l, @igor, 'comment', NULL, 'Договор подписан, счёт оплачен.', LEAST(@now, @now - 974340 + 36000));

-- 21. Катерина Ищенко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Катерина Ищенко', '+380990001740', 'landing', 'rejected', @marina, @now - 2110080, LEAST(@now, @now - 2110080 + 36000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 2110080),
  (@l, @marina, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 2110080 + 18000)),
  (@l, @marina, 'Отказ: не устроила цена.', LEAST(@now, @now - 2110080 + 36000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'landing', @now - 2110080),
  (@l, NULL, 'manager_id', NULL, @marina, @now - 2110080),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Интересует сотрудничество', @now - 2110080),
  (@l, @marina, 'status', 'new', 'in_work', LEAST(@now, @now - 2110080 + 18000)),
  (@l, @marina, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 2110080 + 18000)),
  (@l, @marina, 'status', 'in_work', 'rejected', LEAST(@now, @now - 2110080 + 36000)),
  (@l, @marina, 'comment', NULL, 'Отказ: не устроила цена.', LEAST(@now, @now - 2110080 + 36000));

-- 22. Николай Кузьменко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Николай Кузьменко', '+380990001777', 'facebook', 'success', @olga, @now - 196080, LEAST(@now, @now - 196080 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, @olga, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 196080 + 18000)),
  (@l, @olga, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 196080 + 36000)),
  (@l, @olga, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 196080 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'facebook', @now - 196080),
  (@l, NULL, 'manager_id', NULL, @olga, @now - 196080),
  (@l, @olga, 'status', 'new', 'in_work', LEAST(@now, @now - 196080 + 18000)),
  (@l, @olga, 'comment', NULL, 'Отправил коммерческое предложение на почту.', LEAST(@now, @now - 196080 + 18000)),
  (@l, @olga, 'status', 'in_work', 'callback', LEAST(@now, @now - 196080 + 36000)),
  (@l, @olga, 'comment', NULL, 'Просит перезвонить завтра после 15:00.', LEAST(@now, @now - 196080 + 36000)),
  (@l, @olga, 'status', 'callback', 'success', LEAST(@now, @now - 196080 + 54000)),
  (@l, @olga, 'comment', NULL, 'Оформлен заказ, передал в работу.', LEAST(@now, @now - 196080 + 54000));

-- 23. Дарья Остапенко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Дарья Остапенко', '+380990001814', 'facebook', 'rejected', @igor, @now - 2398620, LEAST(@now, @now - 2398620 + 54000));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2398620),
  (@l, @igor, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 2398620 + 18000)),
  (@l, @igor, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 2398620 + 36000)),
  (@l, @igor, 'Отказ: выбрали другого подрядчика.', LEAST(@now, @now - 2398620 + 54000));
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'facebook', @now - 2398620),
  (@l, NULL, 'manager_id', NULL, @igor, @now - 2398620),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Перезвоните, пожалуйста, в рабочее время', @now - 2398620),
  (@l, @igor, 'status', 'new', 'in_work', LEAST(@now, @now - 2398620 + 18000)),
  (@l, @igor, 'comment', NULL, 'Клиент интересуется сроками и стоимостью.', LEAST(@now, @now - 2398620 + 18000)),
  (@l, @igor, 'status', 'in_work', 'callback', LEAST(@now, @now - 2398620 + 36000)),
  (@l, @igor, 'comment', NULL, 'Не взял трубку, наберу повторно вечером.', LEAST(@now, @now - 2398620 + 36000)),
  (@l, @igor, 'status', 'callback', 'rejected', LEAST(@now, @now - 2398620 + 54000)),
  (@l, @igor, 'comment', NULL, 'Отказ: выбрали другого подрядчика.', LEAST(@now, @now - 2398620 + 54000));

-- 24. Евгений Романенко
INSERT INTO `lead` (`name`, `phone`, `source`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES ('Евгений Романенко', '+380990001851', 'website', 'new', NULL, @now - 357060, LEAST(@now, @now - 357060 + 0));
SET @l = LAST_INSERT_ID();
INSERT INTO `lead_comment` (`lead_id`, `user_id`, `text`, `created_at`) VALUES
  (@l, NULL, 'Комментарий из заявки: Нужен расчёт под ключ', @now - 357060);
INSERT INTO `lead_history` (`lead_id`, `user_id`, `field`, `old_value`, `new_value`, `created_at`) VALUES
  (@l, NULL, 'created', NULL, 'website', @now - 357060),
  (@l, NULL, 'comment', NULL, 'Комментарий из заявки: Нужен расчёт под ключ', @now - 357060);

COMMIT;

-- Готово. Вход менеджеров: olga@example.com / igor@example.com / marina@example.com, пароль demo12345.