# Pflicht-Daten-Cockpit

Shopware-Plugin von EschSoftware. Es prüft verkaufbare Artikel auf GPSR-Herstellerangaben: Herstellername, Postanschrift und E-Mail-Kontakt.

Die Pflicht bleibt beim Händler. Im Admin gibt es eine Übersicht und eine Warnung beim Speichern. Der Endkunde sieht keinen Hinweis, und der Checkout bleibt offen.

Lizenz: MIT. Copyright 2026 Michael Esch / EschSoftware.

Produktseite: https://eschsoftware.de/de/pflicht-daten-cockpit/
Kontakt: https://eschsoftware.de/de/contact/
Quellcode: https://github.com/eschsoftware/pflicht-daten-cockpit

Getestet mit Shopware 6.7.16.1. Englische Store-Texte liegen in `listing.en-GB.md`.

## Installation

1. Im Admin: Erweiterungen → Meine Erweiterungen → **Erweiterung hochladen**.
2. **Installieren**, danach den Schalter auf aktiv stellen.
3. Unter Kataloge → Hersteller Postanschrift und E-Mail pflegen. Die Felder stehen auf der Herstellerseite.
4. Unter Produkte → Pflichtdaten die Lücken prüfen.

Beim Speichern eines Artikels oder Herstellers erscheint eine Warnung, wenn eine Angabe fehlt. Speichern bleibt möglich.

Die ZIP enthält die gebauten Admin-Dateien. Ein Neubau der Administration ist nicht nötig. Nach dem Aktivieren die Administration einmal neu laden, falls der Menüpunkt noch fehlt.

## Deinstallation

Im Dialog **„Alle App-Daten endgültig entfernen“** einschalten, dann werden die Zusatzfelder und die gespeicherten Werte gelöscht. Bleibt der Schalter aus, bleiben die Werte erhalten.
