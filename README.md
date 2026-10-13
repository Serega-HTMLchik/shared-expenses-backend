# API «Совместные расходы»

Laravel API для групповых расходов, расчётов, приглашений, уведомлений и напоминаний. В репозитории верхнего уровня подготовлен единый Docker Compose: он запускает это API вместе с PostgreSQL, обработчиком очереди, планировщиком, Laravel Reverb и React-клиентом.

## Быстрый запуск в Docker

Выполните команды из каталога `shared-expenses-backend`. Рядом с ним должен находиться каталог `shared-expenses-frontend`:

```bash
cp docker-compose.env.example .env
docker compose up --build
```

После успешной инициализации миграции будут применены автоматически. Чеки сохраняются в отдельном Docker volume, который подключён и к Laravel storage, и к публичному пути `/storage`.

API доступно по адресу `http://localhost:8000`, документация OpenAPI — `http://localhost:8000/api/documentation`, а пользовательский интерфейс — `http://localhost:5173`.

Контейнеры выполняют следующие роли:

| Сервис | Назначение |
| --- | --- |
| `api` | HTTP API Laravel |
| `db` | PostgreSQL 16 |
| `queue` | Фоновые задания и почтовые уведомления |
| `scheduler` | Ежедневная проверка напоминаний должникам |
| `reverb` | WebSocket-сервер для мгновенных обновлений |
| `frontend` | Собранный React-клиент и прокси для `/api` и WebSocket |

Полезные команды:

```bash
docker compose logs -f
docker compose exec api php artisan test
docker compose exec api php artisan l5-swagger:generate
docker compose down
```

`docker compose down -v` удаляет и базу данных, и загруженные чеки; используйте эту команду только когда данные больше не нужны.

## Настройка сервера

1. Разместите рядом каталоги `shared-expenses-backend` и `shared-expenses-frontend` на сервере.
2. В каталоге `shared-expenses-backend` скопируйте `docker-compose.env.example` в `.env`.
3. Задайте уникальные `BACKEND_APP_KEY`, `POSTGRES_PASSWORD` и `REVERB_APP_SECRET`. Ключ приложения можно создать так: `docker compose run --rm api php artisan key:generate --show`.
4. Укажите публичные HTTPS-адреса:

   ```dotenv
   FRONTEND_PUBLIC_URL=https://expenses.example.com
   FRONTEND_URL=https://expenses.example.com
   APP_URL=https://expenses.example.com
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```

5. Настройте SMTP-параметры `MAIL_*`. При значении `MAIL_MAILER=log` письма не отправляются, а записываются в журналы контейнера.
6. Разместите перед портом `FRONTEND_PORT` обратный прокси с TLS. Он должен передавать WebSocket-заголовки `Upgrade` и `Connection` для пути `/app/`.
7. Запустите обновлённые контейнеры:

   ```bash
   docker compose up -d --build
   ```

Для публичного сервера не открывайте порты PostgreSQL и Reverb в интернет без необходимости: клиент подключается к WebSocket через публичный фронтенд по пути `/app/`.

## Запуск без Docker

Нужны PHP 8.4, Composer, PostgreSQL и Node.js. Создайте локальный `.env` из `.env.example`, настройте PostgreSQL и затем выполните:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Для фоновых задач в отдельных терминалах выполните `php artisan queue:work`, `php artisan schedule:work` и `php artisan reverb:start`.
