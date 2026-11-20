# Pflicht-Daten-Cockpit

Shopware-6-Plugin von [EschSoftware](https://eschsoftware.de). Es prüft verkaufbare Artikel auf GPSR-Herstellerangaben (Name, Postanschrift, E-Mail-Kontakt). Fehlt eine Angabe, lässt sich der Artikel nicht bestellen. Im Admin liegen Zähler, Filter und ein Sprung zum Artikel.

Das Plugin ist **kostenlos** (MIT-Lizenz) und für **self-hosted Shopware 6.7** gedacht. Anpassung, Erweiterung und Wartung übernimmt [EschSoftware](https://eschsoftware.de/de/contact/).

This plugin is also documented in English below.

---

## Deutsch

### Was es tut

Händler sehen unter **Kataloge → Pflichtdaten**, welche aktiven Artikel keinen Hersteller, keinen Herstellernamen, keine Postanschrift oder keinen E-Mail-Kontakt haben. Zähler trennen geprüfte, lückenhafte und vollständige Artikel. Ein Filter und eine Suche grenzen die Liste ein. Die Artikelnummer öffnet den Artikel.

Dieselbe Regel gilt im Checkout. Liegt ein betroffener Artikel im Warenkorb, erscheint ein Hinweis und der Bestellbutton bleibt deaktiviert, auch im Offcanvas-Warenkorb. Die Bestellung wird zusätzlich serverseitig abgelehnt.

Postanschrift und E-Mail-Kontakt sind Zusatzfelder am Hersteller. Der Name kommt aus dem vorhandenen Herstellerfeld. Ein Hersteller gilt für alle seine Artikel.

**Diese Version prüft nur diese drei Angaben.** Sicherheitshinweise, die verantwortliche Person in der EU, Alt-Texte und der Data Act sind nicht enthalten. Die Prüfung ist eine technische Hilfe und ersetzt keine rechtliche Bewertung.

Getestet mit Shopware **6.7.16.1** (PHP 8.2+, `shopware/core` `>=6.7.16.1 <6.8.0`). Sprachen: Deutsch und Englisch (de-DE, en-GB) in Administration und Storefront.

### Installation (self-hosted Shopware 6.7)

#### Variante A: ZIP in der Plugin-Verwaltung

1. Die aktuelle ZIP-Datei von der [Produktseite](https://eschsoftware.de/de/pflicht-daten-cockpit/) laden.
2. Im Admin unter **Erweiterungen → Meine Erweiterungen** hochladen und installieren.
3. Aktivieren. Dabei werden die Zusatzfelder am Hersteller angelegt.
4. Unter **Kataloge → Hersteller** Postanschrift und E-Mail-Kontakt pflegen.
5. Unter **Kataloge → Pflichtdaten** die Lücken prüfen.
6. Administration neu bauen, falls der Menüpunkt nach der Installation fehlt: `bin/build-administration.sh`.

Deinstallation: Ohne „Benutzerdaten behalten“ werden die Zusatzfelder gelöscht. Mit der Option bleiben die gepflegten Werte erhalten. Nach dem Deaktivieren gibt es keine Checkout-Sperre mehr.

#### Variante B: Composer (Packagist)

```bash
composer require eschsoftware/pflicht-daten-cockpit
bin/console plugin:refresh
bin/console plugin:install --activate EschSoftwarePflichtDatenCockpit
bin/build-administration.sh
```

### Anpassung und Wartung

Das Plugin ist bewusst schlank gehalten. Wenn du weitere Pflichtdaten, andere Shops oder einen dauerhaften Betrieb brauchst, [schreib an EschSoftware](https://eschsoftware.de/de/contact/).

- Hersteller: [eschsoftware.de](https://eschsoftware.de)
- Kontakt: [mail@eschsoftware.de](mailto:mail@eschsoftware.de)
- Quellcode: [github.com/eschsoftware/pflicht-daten-cockpit](https://github.com/eschsoftware/pflicht-daten-cockpit)

---

## English

Shopware 6 plugin by [EschSoftware](https://eschsoftware.de). It checks sellable products for GPSR manufacturer details (name, postal address, email contact). If one of them is missing, the product cannot be ordered. The admin overview shows counters, filters and a link to the product.

The plugin is **free** (MIT license) and meant for **self-hosted Shopware 6.7**. For customisation, extensions and maintenance, contact [EschSoftware](https://eschsoftware.de/de/contact/).

### What it does

Merchants open **Catalogues → Mandatory data** and see which active products have no manufacturer, no manufacturer name, no postal address or no email contact. Counters separate checked, incomplete and complete products. A filter and a search narrow the list. The product number opens the product.

The same rule applies at checkout. If an affected product is in the cart, a notice is shown and the order button stays disabled, including the offcanvas cart. The order is also rejected on the server.

Postal address and email contact are custom fields on the manufacturer. The name is the existing manufacturer name. One manufacturer covers all of its products.

**This version checks only these three details.** Safety instructions, the EU responsible person, alt texts and the Data Act are not included. The check is a technical aid and does not replace a legal assessment.

Tested with Shopware **6.7.16.1** (PHP 8.2+, `shopware/core` `>=6.7.16.1 <6.8.0`). Languages: German and English (de-DE, en-GB) in the Administration and the Storefront.

### Installation (self-hosted Shopware 6.7)

#### Option A: ZIP in the plugin manager

1. Download the current ZIP from the [product page](https://eschsoftware.de/de/pflicht-daten-cockpit/).
2. Upload and install it in the admin under **Extensions → My extensions**.
3. Activate it. This creates the custom fields on the manufacturer.
4. Fill in the postal address and email contact under **Catalogues → Manufacturers**.
5. Review the gaps under **Catalogues → Mandatory data**.
6. Rebuild the Administration if the menu entry is missing after install: `bin/build-administration.sh`.

Uninstallation: without “keep user data” the custom fields are deleted. With that option the values stay. Deactivating the plugin removes the checkout block.

#### Option B: Composer (Packagist)

```bash
composer require eschsoftware/pflicht-daten-cockpit
bin/console plugin:refresh
bin/console plugin:install --activate EschSoftwarePflichtDatenCockpit
bin/build-administration.sh
```

### Customisation and maintenance

The plugin is intentionally slim. If you need more mandatory fields, other shops or ongoing operations, [get in touch with EschSoftware](https://eschsoftware.de/de/contact/).

- Vendor: [eschsoftware.de](https://eschsoftware.de)
- Contact: [mail@eschsoftware.de](mailto:mail@eschsoftware.de)
- Source: [github.com/eschsoftware/pflicht-daten-cockpit](https://github.com/eschsoftware/pflicht-daten-cockpit)
