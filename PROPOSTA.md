# Proposta: nova web de l'AFA Escola Jacint Verdaguer (La Granada)

**Domini:** afalagranada.cat · **Repositori:** soportic-orb/afalagranada · **Data:** novembre 2026

## 1. Objectius

1. Explicar a les famílies **què és l'AFA**, què fa i per què val la pena fer-se'n soci/a.
2. Que la Junta pugui **mantenir el contingut** sense coneixements tècnics.
3. **Recaptar fons** per a projectes amb campanyes de micromecenatge i pagament en línia.
4. **Gestionar inscripcions** a activitats de principi a fi: publicació, pagament, entrades amb QR i control d'accés.
5. Dirigir les famílies cap a l'**app Abaco**, l'eina oficial per a la quota i els serveis.
6. Poder allotjar-la en un **hosting compartit econòmic** i actualitzar-la **amb un clic** des de GitHub.

## 2. Mapa de la web

| Secció | URL | Contingut |
|---|---|---|
| Inici | `/` | Missatge principal, què és l'AFA, serveis, avantatges de ser soci/a, properes activitats, campanyes actives, app, notícies |
| L'AFA | `/que-es-afa` | Qui som, Junta, comissions, com participar (editable) |
| Fes-te soci/a | `/fes-te-soci` | Com donar-se d'alta (editable, remet a l'app) |
| Activitats | `/activitats`, `/activitats/{activitat}` | Agenda, fitxa i formulari d'inscripció |
| Campanyes | `/campanyes`, `/campanyes/{campanya}` | Llistat, fitxa amb barra de progrés, galeria, aportacions i donació |
| L'app | `/aplicacio` | Presentació d'Abaco, funcionalitats, App Store, Google Play i guia per començar |
| Notícies | `/noticies` | Actualitat de l'AFA |
| Contacte | `/contacte` | Formulari de contacte (amb protecció anti-spam) i dades de l'AFA; pàgina de confirmació amb 🎉 i confetti |
| Pàgines lliures | `/{pàgina}` | Qualsevol pàgina nova creada des del panell (p. ex. menjador, acollida, extraescolars) |
| Entrades | `/inscripcio/{codi}`, `/entrada/{codi}` | Confirmació i entrades amb QR (no indexades) |
| Privacitat | `/privacitat` | Text legal (RGPD) |

Els serveis de l'AFA (extraescolars, acollida, menjador, llibres i material, activitats culturals, projectes) i els avantatges de ser soci/a són **blocs editables** amb icona, text i enllaç. Si un servei necessita més detall, s'enllaça a una pàgina pròpia.

## 3. Panell d'administració (`/admin`)

- **Tauler:** total recaptat, inscrits, campanyes i activitats en curs, darrers moviments i avisos de configuració.
- **Contingut (CMS):**
  - textos de portada;
  - pàgines;
  - blocs;
  - notícies;
  - pàgina de l'app.
  - Editor visual amb títols, negretes, llistes, enllaços, imatges (es pugen al servidor i es redimensionen) i vídeos de YouTube/Vimeo.
- **Campanyes:**
  - títol, descripció, imatge, galeria, fita, donació mínima i imports suggerits;
  - dates i estat (esborrany, activa o tancada), i destacar-la a la portada;
  - aportacions fora de la web (efectiu o transferència), que també sumen a la barra;
  - llistat de donacions, alta manual, reenviament de l'agraïment i exportació a CSV/Excel.
- **Activitats:**
  - data, lloc, places i màxim de participants per inscripció;
  - preu general i **preu per a socis/es**;
  - obertura i tancament d'inscripcions, i opció de demanar el curs de l'infant.
- **Inscripcions:**
  - llistat per estat, inscripció manual, marcar com a pagada i reenviar entrades;
  - cancel·lar, exportar a CSV i imprimir la llista.
- **Control d'accés:**
  - escàner QR amb la càmera del mòbil, amb so i vibració;
  - avisa si l'entrada ja s'ha fet servir, és d'una altra activitat o no és vàlida;
  - validació manual introduint el codi.
- **Configuració:** dades de l'AFA, logotip, xarxes socials, Stripe, correu SMTP, avisos a la Junta, analítica i repositori d'actualitzacions.
- **Usuaris i rols:**
  - Administrador/a, Editor/a i Control d'accés (per als voluntaris de la porta);
  - registre d'activitat del panell.
- **Actualitzacions OTA:** comprovar, actualitzar des de `main`, migracions de base de dades i restaurar còpies.

## 4. Pagaments amb Stripe

- **Stripe Checkout** (la pàgina de pagament de Stripe): targeta, Apple Pay, Google Pay i Bizum (si el compte Stripe el té activat). Compleix PSD2/SCA, i la web no toca mai les dades de la targeta.
- Confirmació per **webhook signat** i, com a reforç, en tornar a la web. El procés és idempotent: un pagament no es pot comptar dues vegades.
- Pagaments caducats o abandonats: les places reservades s'alliberen al cap d'una hora.
- Els retorns es fan des del panell de Stripe i la web els marca automàticament.
- **Cost orientatiu:** comissió de Stripe per transacció amb targeta europea (sense quota mensual). No hi ha cap altra comissió de plataforma, a diferència de Verkami, Goteo o Eventbrite.

## 5. Entrades amb QR

- Cada participant rep una **entrada individual** amb codi QR únic i no endevinable.
- Un correu amb les entrades incrustades s'envia en confirmar el pagament o la inscripció gratuïta. També es poden consultar en línia.
- El QR conté l'URL de l'entrada. Si l'escaneja una persona del panell, la pot validar directament. Si l'escaneja qualsevol altra persona, només veu l'entrada.

## 6. Tecnologia i allotjament

- **PHP 8.1+ i MySQL/MariaDB**, sense framework pesant ni processos de compilació. És compatible amb qualsevol hosting compartit amb Apache.
- Dependències incloses al repositori: generació de QR (`chillerlan/php-qrcode`) i enviament de correu (`PHPMailer`).
- **Instal·lador web** en 1 minut (`/install`).
- **Actualització OTA:**
  - la branca `main` de GitHub és la versió de producció;
  - des del panell, un clic descarrega la versió, fa una còpia de seguretat, actualitza i migra la base de dades;
  - no cal FTP ni accés per SSH.
- Disseny propi, adaptat al mòbil, accessible (contrastos, navegació per teclat) i amb bones pràctiques de SEO (metadades i Open Graph per compartir campanyes a WhatsApp i xarxes).
- RGPD: dades mínimes, consentiment a les inscripcions, política de privacitat editable i sense galetes de seguiment per defecte.

## 6 bis. Botiga en línia

Botiga senzilla per vendre productes de l'AFA (samarretes, bosses, llibretes...), amb pagament amb Stripe.

### Per a les famílies
- **Catàleg** a `/botiga` i fitxa de cada producte, amb imatge, preu, descripció i disponibilitat («Últimes unitats», «Esgotat»).
- **Opcions per producte** (talles, colors...), cadascuna amb el seu propi estoc.
- **Cistella**, visible només dins les pàgines de la botiga:
  - comptador d'unitats a la barra de la botiga;
  - es poden canviar les quantitats o treure productes;
  - les quantitats s'ajusten soles a l'estoc disponible i al màxim per comanda.
- **Finalitzar la comanda:**
  - dades de contacte i tria del lliurament: recollida a l'escola (gratuïta) o enviament a domicili, amb cost configurable i enviament gratuït a partir d'un import;
  - pagament segur a la pàgina de Stripe, amb targeta, Apple Pay o Google Pay.
- **Seguiment de la comanda** a través d'un enllaç privat que arriba per correu:
  - estat amb una barra de progrés (Rebuda → En preparació → Enviada/lliurada);
  - número i enllaç de seguiment de l'enviament, productes, imports retornats i historial.
- **«Seguir la meva comanda»** (`/botiga/seguiment`):
  - el client escriu el seu correu i rep un enllaç a cadascuna de les seves comandes;
  - la resposta és sempre la mateixa, per no revelar quins correus han comprat;
  - hi ha un límit de peticions per evitar abusos.
- **Cancel·lació pel client** mentre la comanda està en estat «Rebuda»: es retorna l'import íntegre automàticament. Es pot desactivar.

### Per a la Junta (panell d'administració)
- **Productes i estoc:**
  - nom, descripció, imatge, preu, estoc (o il·limitat), màxim d'unitats per comanda, estat (esborrany, a la venda, ocult) i ordre;
  - opcions amb el seu estoc i unitats venudes.
- **Comandes:**
  - llistat amb filtres per estat i cerca per número, nom o correu;
  - indicadors de vendes netes i de comandes per preparar, i exportació a CSV per producte (útil per preparar els lliuraments);
  - comptador al menú i avís al tauler quan hi ha comandes noves.
- **Estat de la comanda:** Rebuda, En preparació, Enviada/lliurada, Cancel·lada i Devolució.
  - En canviar-lo es pot afegir un missatge, un número i un enllaç de seguiment.
  - El client rep un avís per correu (opcional).
- **Devolucions directament des del panell:**
  - totals o parcials, amb motiu;
  - es fan a la targeta del client a través de l'API de Stripe, sense haver d'entrar a Stripe;
  - opcionalment, les unitats tornen a l'estoc;
  - les devolucions fetes des del tauler de Stripe també es registren automàticament.
- **Historial de cada comanda,** amb notes internes, enllaç al pagament a Stripe i reenviament de la confirmació.

### Funcionament de l'estoc
- **Reserva:** en confirmar la comanda, l'estoc es reserva mentre el client paga.
- **Pagament no completat:** si al cap de 70 minuts el pagament no s'ha fet, la comanda caduca i les unitats tornen a estar disponibles.
- **Garantia:** no es pot vendre més del que hi ha, encara que dues persones comprin alhora.

### Correus
- **Client:** confirmació de la comanda, canvis d'estat, devolucions i enllaços de seguiment.
- **Junta:** avís de comanda nova, amb un botó per gestionar-la.

## 7. Identitat visual

El disseny parteix del **logotip de l'AFA**: la magrana de La Granada i tres figures que representen la comunitat educativa.

| Color | Hex | Ús a la web |
|---|---|---|
| Verd | `#078838` | Color principal: botons, enllaços, menú actiu, franja de l'app, capçaleres de les entrades |
| Vermell | `#d9151c` | Micromecenatge: botons «Col·labora-hi» i «Fer la donació», dates de l'agenda |
| Groc | `#fbdc08` | Accent: botó principal «Fes-te soci/a», detalls decoratius |
| Gris magrana | `#d6d7d7` | Motiu decoratiu de llavors de magrana als fons |
| Negre | `#1d1d1b` | Textos i barra lateral del panell |

- **Franja tricolor** (vermell, verd i groc) a la capçalera, al peu, a l'accés al panell i als correus.
- **Barra de progrés** de les campanyes en degradat de verd a groc.
- **Tipografies**: *Outfit* per als títols (geomètrica, propera a la del logotip) i *Nunito* per al text (arrodonida i molt llegible).
- El logotip apareix a la capçalera, al peu, al panell d'administració, a la pantalla d'accés, als correus (incrustat perquè es vegi encara que el client bloquegi les imatges externes) i com a icona del navegador i del mòbil.
- Des de Configuració es pot substituir el logotip per una altra versió sense tocar codi.

## 8. Abast lliurat en aquesta primera versió

- [x] Web pública completa amb contingut inicial en català (editable)
- [x] CMS amb editor visual
- [x] Micromecenatge amb Stripe, barra de progrés, galeria i darreres aportacions
- [x] Activitats amb inscripcions, preu soci/a, places, pagaments, entrades QR per correu i control d'accés
- [x] Pàgina de l'app Abaco
- [x] Panell amb rols, exportacions CSV i registre d'activitat
- [x] Instal·lador i actualitzacions OTA amb còpies de seguretat
- [x] Formulari de contacte amb bústia al panell
- [x] Botiga en línia amb cistella, estoc, seguiment de comandes i devolucions des del panell

## 9. Propers passos proposats

1. **Contingut real:** fotografies (amb autorització d'imatge), textos de la Junta, quota, enllaços d'Abaco a les botigues.
2. **Hosting i domini:** apuntar afalagranada.cat al hosting, crear la base de dades i el correu `no-reply@`, i activar l'HTTPS.
3. **Stripe:** crear el compte a nom de l'AFA (NIF i compte bancari de l'associació), primer en mode prova i després en mode real.
4. **Prova pilot:** una activitat gratuïta (p. ex. la Castanyada) i una campanya petita.
5. **Millores opcionals per a futures versions:**
   - certificat de donació per a la desgravació fiscal (si l'AFA és declarada d'utilitat pública);
   - llista d'espera quan s'exhaureixen les places;
   - formularis personalitzats per activitat;
   - recordatori automàtic el dia abans;
   - versió de la web en castellà.
