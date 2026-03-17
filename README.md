### Icon Library

- Material Design Icons (https://icones.js.org/collection/mdi)
- Cookie Consent v3 (https://cookieconsent.orestbida.com/)

### Images

#### Galerie (uploads/galerie)
- **Public upload** (Seite „Foto hochladen“): Hochgeladenes Bild wird sofort in WebP umgewandelt und in vier Varianten gespeichert:
  - `{name}.webp` (1000×563, Hauptbild)
  - `{name}_thumb.webp` (110×56, Vorschaubild)
  - `{name}@2x.webp` (2000×1126)
  - `{name}@3x.webp` (3000×1689)
- **Admin-Upload** (EasyAdmin Fotos): Beim Speichern wird dasselbe gemacht – die ursprüngliche Datei (z. B. JPG) wird durch die WebP-Varianten ersetzt und gelöscht.
- Verarbeitung: `App\Service\ImageProcessingService` (GD, WebP).

- Header Images
  - Desktop
    - 3960 3x 2640 2x 1320 1x
  - Tablet
    - 2400 3x 1600 2x 800 1x
  - Mobil
    - 1440 3x 960 2x 480 1x

### Rebase

- git pull origin main --rebase

### Command to render Sitemap
-    php bin/console app:generate-sitemap

### Running Tests
php bin/phpunit                                    # Run all tests
php bin/phpunit tests/Service/                     # Run service tests
php bin/phpunit tests/Service/FrontendCacheServiceTest.php  # Run specific test

### Start lokal phpMyAdmin
php -S 127.0.0.1:8080 -t /opt/homebrew/share/phpmyadmin

---

## Docker (lokale Entwicklung)

Das Projekt kann komplett mit Docker laufen: PHP 8.2 + Apache + MySQL 8.

### Voraussetzung
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (oder Docker Engine + Docker Compose) installiert.

### Erste Schritte

1. **Container starten**
   ```bash
   docker compose up -d
   ```
   Beim ersten Mal wird das Image gebaut (z. B. 2–5 Minuten).

2. **App im Browser öffnen**
   - **App:** http://localhost:8080
   - **phpMyAdmin (Datenbank):** http://localhost:8081  
     Login: Benutzer `munich`, Passwort `munich_secret` (oder `root` / `root_secret` für alle Rechte).

3. **Datenbank einrichten** (einmalig nach dem ersten Start)
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```
   Optional: Fixtures laden, falls vorhanden.

4. **Cache leeren** (falls nötig)
   ```bash
   docker compose exec app php bin/console cache:clear
   ```

5. **Fahrtzeiten ab München vorberechnen** (optional, für „ca. X Min. ab München“ auf der Startseite)
   ```bash
   docker compose exec app php bin/console app:travel-time:warmup
   ```
   Nutzt die OSRM-API; Ergebnisse werden gecacht. Auf dem Live-Server zuerst `--test` ausführen, um die Verbindung zu prüfen: `php bin/console app:travel-time:warmup --test`. Bei cURL-Fehler 35 („no common encryption algorithm“) auf dem Host in `.env` setzen: `OSRM_SSL_VERIFY=0`.

### Nützliche Befehle

| Befehl | Beschreibung |
|--------|--------------|
| `docker compose up -d` | Container starten (im Hintergrund) |
| `docker compose down` | Container stoppen und entfernen |
| `docker compose exec app php bin/console …` | Symfony-Kommando im App-Container ausführen (DB: Host `mysql`) |
| `docker compose exec app bash` | Shell im App-Container öffnen |
| `docker compose logs -f app` | Logs der App anzeigen |
| **http://localhost:8081** | phpMyAdmin (Datenbank-Verwaltung) |

### Konfiguration

- **Datenbank:** In `docker-compose.yml` sind voreingestellt:
  - Host: `mysql` (Service-Name)
  - Datenbank: `munich`
  - User: `munich` / Passwort: `munich_secret`
  - Port auf dem Host: `3306` (z. B. für MySQL-Client oder phpMyAdmin)

- **Datenbank-Import (Kurzweg):** SQL-Dump per Kommandozeile einspielen (schneller als über phpMyAdmin, keine Größenbeschränkung):
  ```bash
  # Dump-Datei z. B. im Projektordner als dump.sql abgelegt, dann:
  docker compose exec -T mysql mysql -u munich -pmunich_secret munich < dump.sql
  ```
  Für gzipped Dumps: `gunzip -c dump.sql.gz | docker compose exec -T mysql mysql -u munich -pmunich_secret munich`

- **Eigene Werte:** Passwörter und Ports kannst du in `docker-compose.yml` unter `environment` bzw. `ports` anpassen.

- **Assets (npm/Encore):** Auf dem Host ausführen (`npm install`, `npm run build` oder `npm run watch`). Die gebauten Dateien liegen in `public/build` und werden per Volume in den Container übernommen.

### Hinweis
Die Datenbank-Daten liegen im Docker-Volume `mysql_data`. Bei `docker compose down -v` werden Volumes gelöscht – dann ist die DB leer und Migrations müssen erneut laufen.