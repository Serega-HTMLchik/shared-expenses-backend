# API «Совместные расходы»

Laravel API для групповых расходов, расчётов, приглашений, уведомлений и напоминаний. Docker Compose запускает это API вместе с PostgreSQL, обработчиком очереди, планировщиком, Laravel Reverb и React-клиентом.

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
4. Укажите разные публичные HTTPS-домены для интерфейса и API:

   ```dotenv
   FRONTEND_PUBLIC_URL=https://expenses.example.com
   FRONTEND_URL=https://expenses.example.com
   API_PUBLIC_URL=https://api.expenses.example.com
   CORS_ALLOWED_ORIGINS=https://expenses.example.com
   REVERB_ALLOWED_ORIGINS=expenses.example.com
   VITE_REVERB_HOST=api.expenses.example.com
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```

   После сборки клиент обращается к `https://api.expenses.example.com/api/v1`, а ссылки на чеки генерируются с API-доменом.

5. На сервере не публикуйте контейнерные порты в интернет. В `.env` укажите `FRONTEND_PORT=127.0.0.1:5173`, `API_PORT=127.0.0.1:8000` и `REVERB_PORT=127.0.0.1:8080`, затем настройте TLS reverse proxy:

   ```nginx
   server {
       listen 443 ssl http2;
       server_name expenses.example.com;

       location / {
           proxy_pass http://127.0.0.1:5173;
           proxy_set_header Host $host;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }
   }

   server {
       listen 443 ssl http2;
       server_name api.expenses.example.com;
       client_max_body_size 10m;

       location /app/ {
           proxy_pass http://127.0.0.1:8080;
           proxy_http_version 1.1;
           proxy_set_header Upgrade $http_upgrade;
           proxy_set_header Connection "upgrade";
           proxy_set_header Host $host;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }

       location / {
           proxy_pass http://127.0.0.1:8000;
           proxy_set_header Host $host;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }
   }
   ```

   `client_max_body_size` должен быть не меньше максимального размера чека. Laravel принимает чеки до 10 МБ; PHP-контейнер настроен с `upload_max_filesize=10M` и `post_max_size=12M`. После изменения конфигурации Nginx проверьте её (`nginx -t`) и перезагрузите (`systemctl reload nginx`).

6. Настройте SMTP-параметры `MAIL_*`. При значении `MAIL_MAILER=log` письма не отправляются, а записываются в журналы контейнера.
7. Запустите обновлённые контейнеры:

   ```bash
   docker compose up -d --build
   ```

Публичный API включает OpenAPI-документацию по адресу `https://api.expenses.example.com/api/documentation`. WebSocket подключается к тому же API-домену по `/app/`.

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
