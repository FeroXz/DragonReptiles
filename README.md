# Dragon Reptiles CMS – PHP Plattform für Terraristik

Dragon Reptiles ist ein leichtgewichtiges, auf PHP 8.3 und SQLite basierendes CMS für Reptilienhalter. Es vereint Tierverwaltung, Tierabgabe, Wiki-Inhalte sowie ein Admin-Backend mit granularen Berechtigungen. Alle Inhalte werden persistiert in einer lokalen SQLite-Datenbank gespeichert, Medien landen im Verzeichnis `public/uploads/`.

**Aktuelle Version:** 5.6.1

## Kernfunktionen

- 🦎 **Tierverwaltung** mit Art, Genetik, Herkunft, Besonderheiten, Bildern, Showcase-Flag und optionalem Besitzer.
- 🔒 **„Meine Tiere“** – angemeldete Benutzer sehen ausschließlich ihre privaten Tiere in einem separaten Bereich.
- 📨 **Tierabgabe-Workflow** mit öffentlichen Inseraten, Kontaktformular und Nachrichteneingang für Administrator*innen.
- ⚙️ **Einstellungen** für Seitentitel, Untertitel, Hero-/Abgabe-Text, Kontaktadresse und Footer (inkl. Versionshinweis).
- 👥 **Benutzer- & Rechteverwaltung**: Admins können weitere Accounts mit eingeschränkten Rechten (Tiere, Adoption, Einstellungen) anlegen.
- 📈 **Dashboard** mit Kennzahlen zu Bestand, Abgabeinträgen und neuen Anfragen.
- 💾 **Persistente Speicherung** per SQLite – keine zusätzliche Server-Software notwendig.
- 🖼️ **Galerie-Verwaltung** inklusive Uploads, Tags und Startseiten-Highlights.
- 📚 **Wissenssammlung** mit Themenbaum, Inhaltsverzeichnissen und internen Verlinkungen im Wiki-Stil.
- 🗂️ **Medienverwaltung** zur Organisation wiederverwendbarer Bilder und Alt-Texte.
- 🧩 **Drag-&-Drop-Startseitenlayout** für News-, Adoption-, Pflege- und Galerie-Sektionen.
- 🔄 **ZIP-Update-Manager** im Adminbereich – Updates ohne Verlust eigener Inhalte einspielen.

## Systemvoraussetzungen

| Komponente | Anforderung |
| ---------- | ----------- |
| PHP        | ≥ 8.3 mit PDO-SQLite, session, fileinfo |
| Webserver  | Apache, Nginx oder kompatibel (z. B. shared hosting) |
| Dateirechte | Schreibrechte für `storage/` und `uploads/` |

## Installation

1. **Dateien hochladen** – den Inhalt dieses Repositories auf den Webspace kopieren (z. B. via FTP oder Git-Deploy).
2. **Verzeichnisse beschreibbar machen**:
   ```bash
   chmod -R 775 storage public/uploads
   ```
3. **Aufruf im Browser** – `index.php` unter `public/` dient als Front-Controller. Richte den Dokumentenstamm deines Webservers auf `public/` aus.
4. **Erstanmeldung** – Standard-Zugangsdaten: Benutzername `admin`, Passwort `12345678`. Nach dem Login können weitere Benutzer erstellt und Passwörter geändert werden.

> Hinweis: Beim ersten Start wird automatisch eine SQLite-Datenbank unter `storage/database.sqlite` angelegt sowie ein Admin-Benutzer erzeugt.

## Ordnerstruktur

```
feroxz/
├── app/                 # PHP-Logik, Datenbank, Helper
├── public/
│   ├── assets/          # Stylesheet
│   ├── index.php        # Front-Controller
│   └── views/           # Öffentliche und Admin-Templates
├── storage/             # SQLite-Datenbank (wird zur Laufzeit angelegt)
├── uploads/             # Hochgeladene Medien (per .gitignore ausgenommen)
└── README.md
```

## Adminbereich & Workflows

- **Dashboard** – Überblick über Tiere, Abgabeinträge und eingegangene Nachrichten.
- **Tiere** – CRUD für Tiere inkl. Upload und Zuordnung zu Benutzer*innen.
- **Tierabgabe** – Inserate verwalten, Tiere aus dem Bestand übernehmen, Preis/Status pflegen.
- **Anfragen** – Einsicht in alle Adoption-Anfragen, direkte Antwort via `mailto:`.
- **Einstellungen** – Seitentexte und Kontaktadresse aktualisieren.
- **Benutzer** – Nur für Admins sichtbar. Neue Benutzer mit selektiven Rechten anlegen.

## Styling

Das Theme nutzt Glas-/Neon-Akzente inspiriert von tropischen Terrarien. Anpassungen erfolgen im Stylesheet `public/assets/style.css`.

## Entwicklung (lokal)

Ein PHP-Entwicklungsserver reicht aus:

```bash
cd public
php -S localhost:8000
```

Danach im Browser `http://localhost:8000/index.php` öffnen.

## Tests

Syntax-Check der PHP-Dateien:

```bash
find public app -name "*.php" -print0 | xargs -0 -n1 php -l
```

## Seed-Dateien prüfen

Mit dem Skript `scripts/seed_check.mjs` lässt sich vor einem Deployment nachvollziehen, ob alle erwarteten Seed-Dateien im Verzeichnis `storage/seeds/` vorhanden sind.

```bash
node scripts/seed_check.mjs
```

Ohne Manifest-Datei (`storage/seeds/manifest.json`) listet das Skript alle gefundenen Seed-Dateien auf und weist darauf hin, wenn keine Seeds vorhanden sind. Liegt ein Manifest mit einem `required`-Array vor, werden fehlende Einträge explizit markiert und das Skript beendet sich mit einem Fehlercode.

### Seeds exportieren & importieren

Für lokale Sicherungen und Wiederherstellungen der SQLite-Datenbank stehen Makefile-Kürzel bereit:

```bash
make seed-dump   # Exportiert Inserts nach storage/seeds/
make seed-import # Spielt gespeicherte Seeds wieder ein
```

Beim Import werden alle SQL-Dateien gemäß `manifest.json` berücksichtigt und optional benutzerdefinierte Skripte aus `storage/seeds/custom/` ausgeführt.




## Admin-Update

### Voraussetzungen
- Installiere `lftp` auf dem Server, damit das Deploy-Skript Dateien per SFTP spiegeln kann.
- Hinterlege die Zugangsdaten in `config/deploy.php` oder setze die passenden Umgebungsvariablen (`GITHUB_OWNER`, `GITHUB_REPO`, `SFTP_HOST`, `SFTP_PORT`, `SFTP_USER`, `SFTP_PASS`, `SFTP_KEY_BASE64`, `DEPLOY_TARGET_DIR`, `DRY_RUN`).

### Bedienung im Adminbereich
- Im Adminbereich unter „Update-Manager“ steht die Karte „System-Update“ bereit.
- Dort lässt sich wahlweise ein Dry-Run auslösen, optional eine konkrete Pull-Request-Nummer hinterlegen und der Fortschritt über den Live-Log verfolgen.
- Während des Deployments wird automatisch `storage/maintenance.flag` gesetzt – öffentliche Seiten liefern HTTP 503, der Admin-Bereich bleibt dank Wartungs-Bypass erreichbar.
- Der „Abbrechen“-Button sendet ein SIGTERM an das Deploy-Skript, wartet kurz und erzwingt falls nötig einen SIGKILL.

### API-Beispiele
Alle Endpunkte erfordern eine aktive Admin-Session sowie das Header-Feld `X-CSRF` (der Wert wird auf der Adminseite angezeigt).

```bash
# Start mit optionaler PR-Nummer (Dry-Run deaktiviert)
curl -X POST 'https://example.com/admin/api/update.php?action=start' \
  -H 'Content-Type: application/json' \
  -H 'X-CSRF: CSRF_TOKEN' \
  -b 'PHPSESSID=...' \
  -d '{"dry_run": false, "pr": 123}'

# Status-Abfrage inklusive Log-Tail
curl -b 'PHPSESSID=...' 'https://example.com/admin/api/update.php?action=status'

# Deploy abbrechen
curl -X POST 'https://example.com/admin/api/update.php?action=cancel' \
  -H 'X-CSRF: CSRF_TOKEN' \
  -b 'PHPSESSID=...'

# Vollständige Log-Ausgabe abrufen
curl -b 'PHPSESSID=...' 'https://example.com/admin/api/update.php?action=log'
```

# cms

## Verbesserungen

- [x] Robustere Prüfungen beim Upload von Bildern inklusive MIME-Type-Validierung und sicherer Verzeichnisanlage.
- [x] Validierte Adoptionsanfragen mit sauberer Fehlerbehandlung, falls Einträge entfernt wurden.
- [x] Vereinheitlichte Formularwerte und IDs, um PHP-Warnungen durch fehlende Felder zu vermeiden.
- [x] Admin-Navigation für Mobilgeräte mit flexibler Spalten-/Zeilen-Anordnung und Vollbreiten-Buttons versehen.
- [x] Neuen Admin-Bereich „Texte“ eingeführt, um sämtliche statischen Inhalte der Seiten anzupassen.
- [x] Zweites, reptilieninspiriertes „Serpent Flux“-Theme implementiert und per Einstellung umschaltbar gemacht.
- [x] Admin-Schnellnavigation stapelt Einträge bis zum Desktop zuverlässig für eine echte Mobile-Ansicht.
- [x] Admin-Formulare für Tiere & Adoptionen nutzen eine gemeinsame Artenliste inklusive Genetik-Auswahl je Spezies.
- [x] Tieralter lassen sich per Jahr/Monat/Tag-Auswahl mit optionalen Angaben statt Freitext pflegen.
- [x] Zweispaltige Admin-Bereiche reagieren mobilfreundlich und stapeln Inhalte automatisch untereinander.
- [x] Admin-Editor mit permanenter Nuxt UI Komponenten-Galerie inklusive Suche, Vorschau und direkter Einfügefunktion (Version 4.9.0).
- [x] Umfangreiche Hakennasennatter-Genetik inklusive sämtlicher bestätigter Kombinationsmorphe als Referenzkarten.
- [x] Neues „Nebula Prism“-Theme mit gläsernem Neon-Look, das im Admin-Bereich auswählbar ist.
- [x] Dropdown-Navigation öffnet auf Touch-Geräten ohne ungewollte Seitenwechsel.
- [x] Seed-Check steht als ausführbares Node-Skript (`node scripts/seed_check.mjs`) bereit und der Standard-Footer verweist nun auf Version 3.1.0.
- [x] HorizonUI-3.0-inspiriertes Darkmode-Redesign „Horizon Nightfall“ mit Nuxt UI 4.1 Styling für Frontend und ArminDashboard (Version 3.2.0).
- [x] Nuxt UI 4.1 Runtime eingebunden und HorizonUI-Token für Navigation, Hero-CTA und Admin-Panels neu abgestimmt (Version 3.3.0).
- [x] Dragon-Reptiles-Edition mit Galerieverwaltung, Drag-&-Drop-Startseitenlayout und ZIP-Update-Manager für inhaltsneutrale Releases.
- [x] Wissenssammlung mit Wiki-Funktionen wie Themenbaum, internen Verlinkungen und Inhaltsverzeichnis ausgebaut (Version 3.4.0).
- [x] Medienverwaltung für Bild-Uploads samt Metadaten, Suche und Austausch im Admin-Bereich bereitgestellt (Version 3.4.0).
- [x] Öffentliche Seiten und Wiki auf Nuxt UI Komponentenlayout mit Horizon-Oberflächen umgestellt (Version 3.5.0).
- [x] Medienbibliothek in Tiere-, Adoption- und Galerie-Formularen integriert, Upload-Pfad nach `public/uploads/` verschoben und Beispielcontent mit funktionsfähigen Bildern ausgeliefert (Version 3.6.0).
- [x] Medienpfade normalisiert, bestehende Assets automatisch in die Bibliothek übernommen und Release-News um den 3.6.0-Changelog ergänzt (Version 3.7.0).
- [x] CSRF-Validierung toleriert Token-Übergabe per GET/X-CSRF-Header und Genpool um den Swiss-Chocolate-Morph ergänzt (Version 3.8.0).
- [x] Genetik-Rechner zeigt Kombinationstitel und Basisformen ohne Wildtyp-Duplikate, Referenzkarten unterstützen Bilder aus der Medienbibliothek und die Tierverwaltung nutzt eine Suchauswahl für Genetik wie der Rechner (Version 3.9.0).
- [x] Vollständig Nuxt UI-basierte Startseite mit konfigurierbaren Standardsektionen, frei anlegbaren Custom-Bereichen und erweitertem Swiss-Chocolate-Genpool (Version 4.0.0).
- [x] Rich-Text-Editor bietet einen Nuxt UI Komponenten-Picker für Hero-, Feature- und Callout-Bausteine (Version 4.1.0).
- [x] Update-Manager lädt auf Wunsch das Repository automatisch herunter und aktualisiert ohne Inhaltsverlust, neues Branding inklusive Logo & Icon (Version 4.1.0).
- [x] MorphMarket-orientierter Genetik-Rechner mit React-Oberfläche, Chip-Filtern, Superform-Labels und Wahrscheinlichkeitsbalken (Version 4.2.0).
- [x] MorphMarket-Suchleiste für Eltern-Genetik inklusive Vorschlagschips und Konfliktprüfung ersetzt Segment-Schalter (Version 4.3.0).
- [x] MorphMarket-kompatibler Genetik-Rechner mit Chip-Suche, Tabellen-Resultaten, Badge-Farben und teilbarer URL (Version 4.4.0).
- [x] Serverseitiger Admin-Deploy mit Dry-Run, Live-Log, Abbruch und Wartungs-Bypass (Version 4.5.0).
- [x] SQLite-Seed-Dump & Import-Skripte inklusive Admin-Hinweisblock mit Makefile-Befehlen (Version 4.6.0).
- [x] Umfangreicher Seed-Datensatz für Import/Export inklusive konfigurierbarem Admin-Branding (Version 4.7.0).
- [x] MorphMarket-orientierter Genetik-Rechner mit Segment-Controls, Superformen-Validierung und Ergebnisliste samt Wahrscheinlichkeiten (Version 4.8.0).
- [x] Deploy-Skript wertet GitHub-Antworten per Node aus und benötigt kein jq mehr für den Update-Lauf (Version 4.8.1).
- [x] Genetik-Rechner nach MorphMarket-Vorbild mit korrekten Gen-Typen, posHet-Logik, klaren Labels für Heterodon/Pogona und Dark-Theme-Chipsteuerung (Version 5.0.0).
- [x] MorphMarket-konformer Genetik-Rechner mit Autocomplete-Suche, Chip-Auswahl pro Elternteil, badgebasierter Ergebnistabelle und teilbarer URL-State (Version 5.1.0).
- [x] MorphMarket-Suchvorschläge inklusive Morph-Kombinations-Presets, Alias-abhängiger Ergebnisnamen und erweitertem Genpool für Hakennasennattern (Version 5.2.0).
- [x] Konfliktprüfung für allelische Morph-Kombinationen mit Warnhinweis sowie Wahrscheinlichkeitsbalken in der Ergebnisliste (Version 5.3.0).
- [x] Produktionsbuild des Genetik-Rechners inklusive Suchfeld, Konflikt-Blocker und Fortschrittsbalken ausgeliefert (Version 5.3.1).
- [x] Nuxt UI-inspirierte Mehrfachauswahl für Traits inklusive markierter Vorschlagsliste und Kombinations-Presets im Genetik-Rechner (Version 5.4.0).
- [x] Nuxt UI Hero-Bereich mit Glas-Karten für Artwahl, Trait-Suche und Ergebnislisten im Genetik-Rechner (Version 5.5.0).
- [x] ZIP-Upload im Admin-Update bewahrt automatisch <code>public/uploads/</code> sowie die SQLite-Datenbank (Version 5.5.2).
- [x] Echtzeit-Genetik-Rechner mit Nuxt UI Trait-Pills, Live-Suche für Eltern-Genetik und automatisch aktualisierten Wahrscheinlichkeiten (Version 5.6.0).
- [x] Schnellaktion zum Entfernen aller Traits im Genetik-Rechner inklusive mobilfreundlicher Layout-Anpassung (Version 5.6.1).
