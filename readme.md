# Fresh Drupal 11 + PHP 8.3 Docker Test Environment

## 1. Create an empty folder for the code

```bash
mkdir drupal
```

This just needs to exist and be empty — Composer will fill it in the next step,
and because it's mounted into the container, everything ends up on your host
machine too (so you can edit with your normal editor/IDE).

## 2. Scaffold Drupal 11 via Composer (one-off, before first boot)

```bash
docker run --rm -v "$(pwd)/drupal:/app" composer:2 create-project drupal/recommended-project:^11 /app
```

This pulls Drupal 11's recommended project skeleton into `./drupal`.

## 3. Build and start the stack

```bash
docker compose up -d --build
```

This starts:
- **drupal** — PHP 8.3 + Apache, at http://localhost:8080
- **db** — MariaDB 10.11
- **adminer** — DB admin UI at http://localhost:8081 (System: MySQL, Server: `db`, user: `drupal`, pass: `drupal`)

## 4. Run the install wizard

Visit **http://localhost:8080** and step through the Drupal installer.
When it asks for database details:

- Database name: `drupal`
- Username: `drupal`
- Password: `drupal`
- Host: `db`
- (Advanced options → Port: leave default 3306)

## 5. Verify versions match the test requirements

```bash
docker compose exec drupal php -v
docker compose exec drupal composer show drupal/core | grep versions
```

## 6. Everyday commands

```bash
docker compose exec drupal bash      # shell into the container
docker compose logs -f drupal        # tail logs
docker compose down                  # stop, keep DB data
docker compose down -v               # stop, wipe DB data (start totally fresh again)
```

## Notes
- `./drupal` on your host = `/opt/drupal` in the container, live-synced both ways.
- If Drush isn't already pulled in by `recommended-project`, add it with:
  `docker compose exec drupal composer require drush/drush`