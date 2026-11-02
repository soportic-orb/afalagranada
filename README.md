# Web de l'AFA Escola Jacint Verdaguer — La Granada

Web pública i panell d'administració de l'AFA (domini **afalagranada.cat**).

- PHP 8.1+ i MySQL/MariaDB. No cal Node ni Composer al servidor: `vendor/` ja és al repositori.
- Funciona en **servidors compartits** (Apache amb `.htaccess`).
- **CMS** amb editor visual (Quill): pàgines, notícies, blocs de portada, textos.
- **Micromecenatge** amb Stripe Checkout: campanyes, fita, barra de progrés, donacions, galeria.
- **Activitats i inscripcions**: places, preu soci/no soci, pagament amb Stripe, **entrades amb codi QR** per correu, control d'accés amb la càmera del mòbil.
- **Botiga** a `/botiga`:
  - cistella, opcions per producte (talles) amb estoc i pagament amb Stripe;
  - seguiment de la comanda per enllaç enviat al correu;
  - gestió de comandes, estats i devolucions des del panell.
  - S'activa a Panell → Configuració de la botiga.
- **Formulari de contacte** a `/contacte`: els missatges s'envien al correu configurat a Configuració i es desen a Panell → Missatges.
- Pàgina de l'**app Abaco** amb enllaços a App Store i Google Play.
- **Actualitzacions OTA** des del panell: descarrega la branca `main` de GitHub, fa còpia de seguretat i executa les migracions.

La proposta funcional completa és a [PROPOSTA.md](PROPOSTA.md).

## Instal·lació en un servidor compartit

1. Crea una base de dades MySQL i un usuari des del panell del hosting (cPanel, Plesk…), amb el joc de caràcters `utf8mb4`.
2. Puja tot el contingut del repositori a l'arrel del domini (`public_html/`), per FTP o amb Git.
3. Comprova que es poden escriure l'arrel (per crear `config.php`), `uploads/` i `storage/` (permisos 755/775).
4. Obre `https://afalagranada.cat/install/` i omple el formulari: base de dades, URL i usuari administrador.
5. Entra a `https://afalagranada.cat/admin` i, a **Configuració**:
   - Claus de **Stripe** (`pk_…`, `sk_…`) i el **webhook** cap a `https://afalagranada.cat/stripe/webhook` amb els esdeveniments `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed` i `charge.refunded`. Copia el secret `whsec_…`.
   - Dades **SMTP** del compte de correu (p. ex. `no-reply@afalagranada.cat`) i envia un correu de prova.
   - Repositori i branca per a les actualitzacions. Si el repositori és privat, cal un token de GitHub de només lectura.
6. Activa l'HTTPS (Let's Encrypt al hosting) i descomenta la redirecció a HTTPS a `.htaccess`.

Si el hosting no té `mod_rewrite`, cal demanar que l'activin, perquè les URL depenen de les regles de reescriptura.

### Servidors amb nginx (Plesk, etc.)

Molts hostings fan servir **nginx**, que serveix els fitxers estàtics directament i **no llegeix `.htaccess`**. La web ja ho té en compte:

- Els fitxers PHP interns no fan res si s'obren directament.
- Les dades privades (registres i còpies de seguretat) es desen en una subcarpeta de `storage/` amb un nom impossible d'endevinar. Si el hosting permet escriure fora de l'arrel web, es pot indicar una altra ruta amb `'storage_path' => '/ruta/privada'` a `config.php`.

Tot i així, es recomana afegir aquestes directives d'nginx. A Plesk van a *Configuració d'Apache i nginx → Directives addicionals d'nginx*:

```nginx
location ~ ^/(app|storage|vendor)/ { deny all; return 404; }
location ~ ^/(config\.php|config\.example\.php|composer\.(json|lock)|VERSION|server\.php|README\.md|PROPOSTA\.md)$ { deny all; return 404; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
```

Si les URL amigables no funcionen amb nginx sol (sense Apache), cal afegir-hi també:

```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
```

### Requisits PHP

Extensions `pdo_mysql`, `mbstring`, `curl`, `zip`, `gd`, `dom` i `fileinfo` (venen actives a la majoria de hostings). L'instal·lador les comprova.

## Actualitzacions (OTA)

Panell → **Actualitzacions**:

1. **Comprovar actualitzacions**: consulta l'últim commit de la branca `main` a GitHub.
2. **Actualitzar ara**:
   - descarrega el ZIP del commit;
   - el valida;
   - fa una còpia del codi actual a `storage/backups/` (conserva les 5 últimes);
   - activa el mode manteniment;
   - substitueix els fitxers;
   - elimina els que ja no existeixen a `app/`, `assets/`, `vendor/` i `install/`;
   - executa les migracions de base de dades.
3. **Restaurar** una còpia anterior, si cal.

L'actualització no toca mai `config.php`, `uploads/` ni `storage/`.

### Flux de treball per als desenvolupadors

- Desenvolupa en branques i fusiona a `main` quan estigui llest per publicar.
- Els canvis d'esquema van a `app/migrations/` com a fitxers nous numerats (`003_xxx.sql` o `003_xxx.php`, que retorna `function (Database $db) {}`). Cada migració s'executa una sola vegada.
- Si canvies les dependències, executa `composer install --no-dev --prefer-dist -o` i puja `vendor/` al repositori.
- Incrementa `VERSION` en cada versió.

## Desenvolupament local

```bash
php -S localhost:8000 server.php
```

Obre `http://localhost:8000/install/` amb una base de dades MySQL local. Posa `'debug' => true` a `config.php` per veure els errors.

## Estructura

```
index.php               Controlador frontal
install/                Instal·lador web (es bloqueja quan existeix config.php)
app/bootstrap.php       Inicialització
app/routes/front.php    Web pública, Stripe, entrades
app/routes/admin.php    Panell d'administració
app/admin_resources.php Definició de pàgines/blocs/notícies/campanyes/activitats/usuaris al panell
app/lib/                Database, Auth, Router, Crud, Stripe, Mailer, Tickets (QR), Uploader, Sanitizer, Updater, Migrator
app/migrations/         Migracions de base de dades
app/views/              Plantilles (front, admin, emails, partials)
assets/                 CSS, JS i imatges
uploads/                Imatges pujades (no versionat)
storage/                Logs, còpies de seguretat, temporals (no versionat)
vendor/                 Dependències (chillerlan/php-qrcode, phpmailer)
```

## Rols d'usuari

| Rol | Permisos |
|---|---|
| Administrador/a | Tot: configuració, usuaris, actualitzacions |
| Editor/a | Contingut, campanyes, activitats i inscripcions |
| Control d'accés | Només l'escàner de QR per validar entrades |

## Seguretat

- Contrasenyes amb `password_hash`, protecció CSRF a tots els formularis, límit d'intents d'accés.
- Consultes preparades (PDO), HTML de l'editor filtrat amb una llista blanca d'etiquetes.
- Les pujades es validen pel tipus real del fitxer, i a `uploads/` no s'hi poden executar scripts.
- `app/`, `storage/`, `vendor/` i `config.php` no són accessibles des del web.
- Els pagaments es fan a la pàgina de Stripe: la web no veu ni desa mai dades de targetes. Els webhooks es verifiquen amb signatura HMAC.
