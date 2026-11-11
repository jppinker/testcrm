# Deploying to alwaysdata Free

This deploys the demo as a PHP site with Laravel and the compiled Vue files on one origin. The SQLite file and `.env` are outside the web root. No Docker, Node server or Vite server runs on alwaysdata. Node is needed only on the machine creating the frontend build.

## alwaysdata dashboard

1. In **Environment → PHP**, select **PHP 8.5** as the account default, so the website and SSH CLI use the same version. The lockfile was installed with PHP 8.5. Check `pdo_sqlite`, `sqlite3`, `mbstring`, `xml`, `curl`, `zip` and `openssl` using `php -m` in SSH; Composer checks the remaining requirements.
2. In **Web → Sites**, add or edit a **PHP** site. Set its address to your assigned `ACCOUNT.alwaysdata.net` address, and its root directory to **`/home/ACCOUNT/testcrm/backend/public`**, substituting the actual account name. The root must be `public`, not `backend` or `testcrm`. The Free plan's allowed addresses depend on the current offer; use the provided alwaysdata.net address.
3. Enable the site's HTTP-to-HTTPS redirect in its SSL settings. The provided alwaysdata.net address uses alwaysdata's wildcard certificate. Ensure the address is registered on this site, and avoid a second site with the same address.
4. Enable SSH access under **Remote access → SSH/SFTP**. PHP sites use Apache; Laravel's `public/.htaccess` handles rewriting. Do not expose the repository, SQLite database, `.env`, `storage`, or `vendor` as a site root.

See alwaysdata's [PHP configuration](https://help.alwaysdata.com/en/docs/web-hosting/languages/php/configuration/), [site setup](https://help.alwaysdata.com/en/docs/web-hosting/sites/add-a-site/), [SSH](https://help.alwaysdata.com/en/docs/web-hosting/remote-access/ssh/), and [TLS](https://help.alwaysdata.com/en/docs/web-hosting/sites/ssl-tls/lets-encrypt/) documentation.

## Build and upload from your computer

Run these commands from the repository root. Replace `ACCOUNT` with the alwaysdata account name and, if different, set `SSH_USER` to the SSH login configured in the dashboard. This packages the current working tree, including uncommitted changes; it does not upload local secrets, databases, dependencies, or logs.

```sh
cd /home/alex/cursor_stuff/testcrm
cd frontend
npm ci
npm run build:laravel
cd ..

tar -czf /tmp/testcrm-release.tar.gz \
  --exclude='./.env*' --exclude='./vendor' --exclude='./node_modules' \
  --exclude='./database/*.sqlite*' --exclude='./storage' \
  --exclude='./bootstrap/cache/*.php' --exclude='./public/hot' \
  -C backend .
ACCOUNT='YOUR_ACCOUNT'
SSH_USER="$ACCOUNT"
scp /tmp/testcrm-release.tar.gz "$SSH_USER@ssh-$ACCOUNT.alwaysdata.net:~/testcrm-release.tar.gz"
ssh "$SSH_USER@ssh-$ACCOUNT.alwaysdata.net"
```

## First installation over SSH

The following assumes the SSH user owns the account home and that `~/testcrm/backend` is the site root's parent. If the SSH login has a different home, use `/home/ACCOUNT` explicitly. Replace the example URL before running the commands. `composer2` is provided by alwaysdata.

```sh
SITE_URL='https://YOUR_ACCOUNT.alwaysdata.net'
mkdir -p "$HOME/testcrm/backend" "$HOME/testcrm-data"
chmod 700 "$HOME/testcrm-data"
tar -xzf "$HOME/testcrm-release.tar.gz" -C "$HOME/testcrm/backend"
cd "$HOME/testcrm/backend"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php -v
php -m | grep -E 'pdo_sqlite|sqlite3|mbstring|xml|curl|zip|openssl'
composer2 install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer2 check-platform-reqs --no-dev
```

Create `.env` **only on the first installation**. Do not regenerate `APP_KEY` on updates. The following block stops if `.env` already exists; it does not replace an existing database.

```sh
if [ -e .env ]; then
  echo '.env already exists; preserve it and skip first-install environment setup.'
else
  umask 077
  cat > .env <<ENV
APP_NAME="Shift Schedule"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=$SITE_URL
LOG_CHANNEL=single
LOG_LEVEL=error
DB_CONNECTION=sqlite
DB_DATABASE=$HOME/testcrm-data/database.sqlite
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
ENV
  php artisan key:generate --force
fi
[ -e "$HOME/testcrm-data/database.sqlite" ] || touch "$HOME/testcrm-data/database.sqlite"
chmod 600 .env "$HOME/testcrm-data/database.sqlite"
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan config:cache
```

The seeder adds demo employees, templates and current-week shifts only when the scheduling tables are empty. It never replaces existing schedule data. Laravel needs write access to `storage`, `bootstrap/cache`, and the SQLite file **and its parent directory** for SQLite journal files. alwaysdata normally runs the PHP site as the account user; if SSH and website users differ, grant the site user access without making these directories world-writable. The Free service has resource and disk limits; monitor usage. SQLite on shared network storage should be verified under the actual account's locking and concurrency conditions before relying on it for more than this demo.

## Verify

Visit `https://YOUR_ACCOUNT.alwaysdata.net/`, create a shift, and refresh the page. A nested URL such as `/schedules/example` should also load the Vue shell after a refresh. Check the API and error routing:

```sh
curl -i "$SITE_URL/up"
curl -i "$SITE_URL/api/schedule?from=2026-01-01&to=2026-01-31"
curl -i "$SITE_URL/api/does-not-exist"
curl -i "$SITE_URL/app/assets/does-not-exist.js"
```

The first two should return 200 and the last two 404. Verify `https://YOUR_ACCOUNT.alwaysdata.net/.env` and `/database/database.sqlite` are not accessible. If there is a 500 error, inspect `storage/logs/laravel.log` and alwaysdata's PHP/Apache logs under `~/admin/logs`. Verify PHP extensions, the site root, file ownership, and `.htaccess` first.

## Updating

Back up `~/testcrm-data/database.sqlite` using SQLite's backup mechanism, then repeat the local build and upload. Over SSH, extract the archive into `~/testcrm/backend`, run `composer2 install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, `php artisan migrate --force`, `php artisan optimize:clear`, and `php artisan config:cache`. Keep `.env`, `APP_KEY`, and the database. Do not run `migrate:fresh` or delete the data directory. Build artifacts are not committed, so rebuild and upload them for each release. Restart the site from **Web → Sites** if PHP's opcode cache retains old code.

This is an unauthenticated fictional-data demo: anyone who can reach it can change its schedule. Publishing records a snapshot; it does not notify employees or provide a separate public view.
