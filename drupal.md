# Drupal Market Watchlist Setup Guide

This document explains how to set up the Drupal environment, start Docker containers, install dependencies, configure the database, enable the `market_watchlist` module, configure permissions, and verify the watchlist page.

---

# 1. Pull Latest Code

Clone the repository:

```bash
git clone https://github.com/Restyle5/drupal.git
```

Navigate into the project directory:

```bash
cd drupal
```

---

# 2. Start Docker Containers

Start all required services using Docker Compose:

```bash
sudo docker compose up -d
```

This starts the Drupal environment, including:

- Drupal application container
- Database container
- Other services defined in `docker-compose.yml`

Verify that the containers are running:

```bash
sudo docker ps
```

Expected containers should include:

```
drupal
db
```

---

# 3. Install Composer Dependencies

Install Drupal PHP dependencies inside the Drupal container:

```bash
sudo docker exec -it drupal composer install
```

This installs all dependencies defined in:

```
composer.json
```

---

# 4. Start Drupal Installation

Open the Drupal installer:

```
http://localhost:8080/
```

---

# 5. Drupal Installation Configuration

## Language

Select:

```
English
```

---

## Installation Profile

Select:

```
Standard
```

---

## Fix File Permissions (If Verification Fails)

If Drupal reports that it cannot write files, update permissions:

```bash
sudo chown -R $(whoami):$(whoami) drupal/

chmod -R 777 drupal/web/sites/default
```

---

# Database Configuration

Choose:

```
MySQL / MariaDB / Percona Server
```

Use the following database settings:

| Setting | Value |
|---|---|
| Database name | `drupal` |
| Database username | `drupal` |
| Database password | `password` |

Open:

```
Advanced Options
```

Configure:

| Setting | Value |
|---|---|
| Database host | `db` |
| Database port | `3306` |

Example:

```
Host: db
Port: 3306
```

Continue with the installation.

---

# 6. Install Drupal

Drupal will create the required database tables and complete the installation.

No additional configuration is required during this step.

---

# 7. Configure Site Information

After Drupal installation completes, configure the site.

## Site Information

| Field | Value |
|---|---|
| Site name | `drupal.test` |
| Site email | `drupal.test@gmail.com` |

---

## Administrator Account

Create the administrator account:

| Field | Value |
|---|---|
| Username | `drupal` |
| Password | `AnythingYouWish123#` |
| Email | `drupal.test@gmail.com` |

Finish the installation.

---

# 8. Enable Market Watchlist Module

Enable the custom module using Drush:

```bash
sudo docker exec -it drupal drush pm:enable market_watchlist
```

Expected result:

```
Market Watchlist module enabled successfully.
```

Alternative:

```bash
sudo docker exec -it drupal drush en market_watchlist -y
```

---

# 9. Rebuild Drupal Cache

After enabling a new module, rebuild Drupal cache:

```bash
sudo docker exec -it drupal drush cr
```

or:

```bash
sudo docker exec -it drupal drush cache:rebuild
```

This refreshes:

- Routes
- Services
- Permissions
- Plugin definitions

---

# 10. Configure User Permissions

Open Drupal:

```
http://localhost:8080/
```

Login using the administrator account.

Navigate to:

```
People
    → Permissions
```

Search for:

```
market watchlist
```

Enable the following permissions for:

```
Authenticated user
```

Permissions:

```
View market watchlist

Import market watchlist prices
```

Click:

```
Save permissions
```

---

# 11. Verify Market Watchlist Page

Open:

```
http://localhost:8080/watchlist
```

Expected result:

- Market Watchlist page is displayed
- Stock data is visible
- Filter form is available
- Seeded price data appears

Example filter:

```
Symbol: AAPL
```

The page should display matching records.

---

# Troubleshooting

## Module Not Detected

Check whether Drupal detects the module:

```bash
sudo docker exec -it drupal drush pm:list --type=module | grep market
```

Expected output:

```
Market Watchlist    market_watchlist    Enabled
```

---

## Route Not Found

If `/watchlist` returns a 404:

Clear Drupal cache:

```bash
sudo docker exec -it drupal drush cr
```

Then retry:

```
http://localhost:8080/watchlist
```

---

## Permission Denied During Installation

Run:

```bash
sudo chown -R $(whoami):$(whoami) drupal/

chmod -R 777 drupal/web/sites/default
```

---

## Docker Container Name Does Not Match

If this command fails:

```bash
sudo docker exec -it drupal composer install
```

Check the actual container name:

```bash
sudo docker ps
```

Replace `drupal` with the correct container name.

Example:

```bash
sudo docker exec -it <container_name> composer install
```

---

# Useful Drush Commands

## List Enabled Modules

```bash
sudo docker exec -it drupal drush pm:list --status=enabled
```

---

## Enable Module

```bash
sudo docker exec -it drupal drush en market_watchlist -y
```

---

## Disable Module

```bash
sudo docker exec -it drupal drush pm:uninstall market_watchlist -y
```

---

## Rebuild Cache

```bash
sudo docker exec -it drupal drush cr
```

---

# Project Structure Reference

The custom module should exist at:

```
web/
└── modules/
    └── custom/
        └── market_watchlist/
            ├── market_watchlist.info.yml
            ├── market_watchlist.routing.yml
            ├── market_watchlist.permissions.yml
            └── src/
                ├── Controller/
                │   └── WatchlistController.php
                └── Form/
                    └── WatchlistFilterForm.php
```

---

# Completion Checklist

- [ ] Repository cloned successfully
- [ ] Docker containers started
- [ ] Composer dependencies installed
- [ ] Drupal installed successfully
- [ ] Database connected successfully
- [ ] `market_watchlist` module enabled
- [ ] Cache rebuilt
- [ ] Permissions configured
- [ ] `/watchlist` page accessible
- [ ] Stock data displayed
- [ ] Filtering works