# Docker deployment

The isolated Compose project is in `/home/ubuntu/testcrm` on the VM. Its Nginx service publishes port 8088; PHP-FPM is internal. The `testcrm_testcrm-data` and `testcrm_testcrm-storage` volumes persist SQLite and Laravel storage. The server-only `.env.production` contains the application key; keep it and back up the SQLite volume before updates.

From that directory, manage only this project:

```sh
sudo docker compose -p testcrm -f compose.production.yaml ps
sudo docker compose -p testcrm -f compose.production.yaml logs --tail=100 app web
sudo docker compose -p testcrm -f compose.production.yaml up -d --build
sudo docker compose -p testcrm -f compose.production.yaml exec -T --user www-data app php artisan migrate --force
```

For updates, sync source while excluding `.env*`, databases, `vendor`, `node_modules`, and build artifacts; do not use `rsync --delete` against the server directory. Build and migrate with the commands above. Do not regenerate the key or remove the named volumes. The initial seeder does not overwrite existing schedules. Public access uses HTTP and has no authentication, appropriate only for fictional demo data.
