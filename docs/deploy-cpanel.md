# Развёртывание на хостинге с cPanel (без Docker)

Требования: **PHP 8.4+** с расширениями `pdo_mysql`, `mbstring`, `intl`; MySQL 5.7+ / MariaDB 10.3+.

## 0. Версия PHP

cPanel → **MultiPHP Manager** → для домена/поддомена выбрать **PHP 8.4** (ea-php84).
В терминале cPanel команда `php` может указывать на другую версию — проверьте `php -v`
и при необходимости вызывайте PHP 8.4 явно: `/opt/cpanel/ea-php84/root/usr/bin/php yii migrate`.

## 1. Поддомен

cPanel → **Domains** (или **Subdomains**) → создать, например, `crm.ваш-домен.com`.
**Document Root** укажите на папку `web` проекта: `lead-crm/web`.
Важно: корнем сайта должна быть именно `web`, тогда `.env`, `config/` и исходники не доступны из браузера.

### Вариант для основного домена (корень — `public_html`)

Корень основного домена в cPanel изменить нельзя, поэтому:

1. Проект распаковать в `~/lead-crm` (вне `public_html`).
2. Сохранить и убрать из `~/public_html` старые файлы сайта (`index.html`, `index.php` и т. п.).
3. Скопировать **содержимое** `~/lead-crm/web/` в `~/public_html/`: `index.php`, `.htaccess`, `css/`, `assets/`.
   В File Manager включите **Settings → Show Hidden Files**, чтобы скопировался `.htaccess`.
4. `index.php` сам найдёт проект в `~/lead-crm`; `.env`, код и `vendor/` остаются вне публичной папки.
5. Папка `~/public_html/assets` должна быть доступна на запись (755).

## 2. База данных

cPanel → **MySQL Databases**: создать базу и пользователя, выдать пользователю **All Privileges** на базу.
Имена получатся с префиксом аккаунта, например `cpuser_crm`.

## 3. Файлы

Вариант А — **готовый архив `lead-crm-hosting.zip`** (уже содержит `vendor/` с Yii2, Composer не нужен):
**File Manager** → загрузить архив в домашнюю папку → **Extract**. Получится `~/lead-crm`.

Вариант Б — **Git Version Control** в cPanel: клонировать репозиторий в `~/lead-crm`,
затем установить зависимости в **Terminal** (нужен Composer):

```bash
cd ~/lead-crm
composer install --no-dev --optimize-autoloader
```

## 4. Настройки

Скопировать `.env.example` в `.env` (в корне проекта, не в `web/`) и заполнить:

```
DB_DSN=mysql:host=localhost;dbname=cpuser_crm
DB_USER=cpuser_crm
DB_PASSWORD=...
COOKIE_KEY=...            # длинная случайная строка
CRM_API_TOKEN=...         # токен для формы сайта
YII_ENV=prod
YII_DEBUG=0
```

Папки `runtime/` и `web/assets/` должны быть доступны на запись (обычно 755 достаточно).

## 5. Таблицы и администратор

С терминалом:

```bash
cd ~/lead-crm
php yii migrate --interactive=0
php yii seed/admin admin@ваш-домен.com 'надёжный-пароль' 'Ваше имя'
php yii seed/demo          # по желанию: демо-менеджеры и лиды
```

Без терминала: phpMyAdmin → выбрать базу → **Import** → файл `docs/schema.sql`
(предварительно подставьте в конце файла email и bcrypt-хеш пароля администратора).

## 6. SSL

cPanel → **SSL/TLS Status** → **Run AutoSSL** для поддомена.

## Проверка

- `https://crm.ваш-домен.com` открывает страницу входа;
- войти администратором → раздел **API** → «Выпустить токен» (или `php yii seed/token email`);
- проверить токен и приём лида:
  ```bash
  curl https://crm.ваш-домен.com/api/me -H 'X-Api-Token: crm_…'
  curl -X POST https://crm.ваш-домен.com/api/leads -H 'X-Api-Token: crm_…' \
    --data-urlencode 'name=Тест' --data-urlencode 'phone=+380501112233'
  ```
  ответ `201 {"ok":true,...}` — лид появился в CRM.
