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