# OrderUploadBundle (Bestellung-Upload)

Kimai-Plugin für Projekte:

1. **Feld "Bestellung"** im Dialog "Projekt erstellen" und "Projekt bearbeiten": ein Dokument hochladen.
2. **Box "Bestellung"** auf der Projekt-Detailseite: Download und Entfernen.
3. **Box "Anhänge"** auf der Projekt-Detailseite: beliebig viele weitere Dateien.

## Installation

```
cd /pfad/zu/kimai/var/plugins/
unzip OrderUploadBundle.zip
cd /pfad/zu/kimai
bin/console kimai:reload --env=prod
```

Das Verzeichnis muss exakt `OrderUploadBundle` heißen, `var/` muss für den Webserver-Benutzer beschreibbar sein. Keine Datenbank-Migration nötig.

Docker: `var/data` muss als Volume persistiert sein.

## Verhalten

- Die Bestellung ist ein Kimai-Meta-Feld ("bestellung") am Projekt. Gespeichert wird nur die Dateikennung in der Meta-Tabelle, die Datei selbst liegt in `var/data/project_attachments/bestellung_dokumente/`.
- Die weiteren Anhänge liegen unter `var/data/project_attachments/<Projekt-ID>/`.
- Beides liegt außerhalb des Webroots, Downloads werden immer als Anhang ausgeliefert.
- Ansehen und Herunterladen: wer das Projekt sehen darf (Recht `view`). Hochladen, Ersetzen und Löschen: wer das Projekt bearbeiten darf (Recht `edit`).
- Maximal 25 MB pro Datei (`AttachmentStorage::MAX_BYTES`), zusätzlich gelten `upload_max_filesize` und `post_max_size` aus der PHP-Konfiguration.
- Beim Bearbeiten ohne neue Datei bleibt die Bestellung unverändert.

## Grenzen

- Pro Projekt gibt es genau eine Bestellung. Ersetzt man sie, bleibt die alte Datei auf der Platte liegen. Beim Entfernen über die Box wird sie gelöscht.
- Beim Löschen eines Projekts bleiben die Dateien liegen.
- Schlägt das Speichern des Projekts nach dem Upload an anderer Stelle fehl, muss die Datei im Dialog erneut gewählt werden.
- Der Name des Uploaders wird nicht gespeichert.
- Die Verzeichnisse gehören in die Datensicherung (`var/data`).

Entwickelt gegen die Quellen von Kimai 2.72.0 und Symfony 6.4.49, Test in Kimai 2.71.0 (Docker).
# kimai_orderuploadbundle
