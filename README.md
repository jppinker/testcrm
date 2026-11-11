# Employee shift scheduling

A Vue 3 / TypeScript scheduling interface with a Laravel API and SQLite persistence.

## Local development

Requirements: Node.js 22.12+ (or 20.19+), npm, Docker and Docker Compose. Docker supplies PHP and Composer; no host PHP installation is required.

From the repository root, prepare the backend:

```sh
cp backend/.env.example backend/.env
touch backend/database/database.sqlite
export LOCAL_UID=$(id -u) LOCAL_GID=$(id -g)
docker run --rm -u "$LOCAL_UID:$LOCAL_GID" -e COMPOSER_HOME=/tmp/composer -v "$PWD/backend:/app" -w /app composer:2 install
docker compose run --rm --entrypoint php backend artisan key:generate
docker compose run --rm --entrypoint php backend artisan migrate --seed
```

Start the backend with `docker compose up`. In another terminal:

```sh
cd frontend
npm ci
npm run dev
```

Open http://localhost:5173. Laravel is at http://localhost:8000; Vite proxies `/api` requests to it. SQLite data is stored in `backend/database/database.sqlite` and remains on the host. Run `npm run build` in `frontend` for type checking and a production build. Stop the backend with Ctrl+C or `docker compose down`.

## Using the schedule

Select one or more empty cells, then choose a template or enter custom hours and apply them. Click an existing shift to edit or delete it. The numbered controls on templates edit them; + creates a template. Changes are saved immediately. Save reloads the persisted schedule, and Publish records a snapshot of the visible period. Publishing does not send notifications or expose a separate public view. The seeder creates a sample schedule for the current week only on a fresh database and leaves existing data untouched. Only one shift per employee and date is supported; shifts must end on the same day.

Run backend tests with `docker compose run --rm --entrypoint php backend artisan test`.
