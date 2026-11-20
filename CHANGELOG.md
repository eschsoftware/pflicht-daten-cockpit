# Changelog

## 1.1.0

- Die GPSR-Prüfung bleibt beim Händler. Checkout, Warenkorb und Storefront zeigen keinen Hinweis und sperren die Bestellung nicht.
- Beim Speichern von Artikel oder Hersteller erscheint eine Warnung, wenn Hersteller, Postanschrift oder E-Mail fehlen. Speichern bleibt möglich.
- Die Übersicht erfasst Varianten-Kinder und Artikel mit leerem `child_count`. Varianten übernehmen die Angaben des Vaters.
- „Alle App-Daten endgültig entfernen“ löscht die gespeicherten Herstellerwerte. Ohne den Schalter bleiben sie erhalten. Öffentliche Plugin-Assets werden bei jeder Deinstallation entfernt.
- Postanschrift und E-Mail stehen auf der Herstellerseite, nicht nur in der Zusatzfeld-Auswahl.
- Der Konsolenbefehl `esch-pdc:seed-gaps` ist nur noch in der Entwicklungsumgebung registriert und nicht im Store-ZIP.
- Lizenz: MIT, Copyright 2026 Michael Esch / EschSoftware.

## 1.0.0

- Admin-Cockpit für die GPSR-Herstellerangaben: Zähler, Filter, Suche und Sprung zum Artikel.
- Zusatzfelder am Hersteller für Postanschrift und E-Mail-Kontakt.
- Checkout wird gesperrt, solange Herstellername, Postanschrift oder E-Mail-Kontakt fehlen.
- Storefront-Hinweis auf Deutsch und Englisch.
- Deinstallation entfernt die Zusatzfelder, wenn keine Benutzerdaten behalten werden.
- Im Warenkorb-Offcanvas ist „Zur Kasse“ deaktiviert, solange GPSR-Herstellerangaben fehlen.
