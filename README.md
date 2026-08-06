# Tag-Press

**Ein dateibasiertes, deklaratives Website-System ohne klassische Datenbank.**

Webseiten werden durch eine formale Beschreibungssprache definiert und zur Laufzeit interpretiert. Das System erzwingt strikte Trennung von semantischer Struktur, Inhalten und Darstellung.

---

**Entwickler:** Rob de Roy
**Website:** [https://robderoy.de](https://robderoy.de)
**Repository:** [github.com/Wolfram33/Tag-Press](https://github.com/Wolfram33/Tag-Press)
**Lizenz:** MIT License
**Version:** 0.2

---

## Konzept

Tag-Press begann als **geistiges Trainingsgerät** und Lern-Experimentierrahmen – und ist inzwischen ein System, mit dem sich **echte Websites erstellen** lassen. Eine Website wird nicht gebaut, sondern *beschrieben*. Rendering ist lediglich die Interpretation dieser Beschreibung.

**Ziel:** Eleganz durch Reduktion. Das System ist absichtlich einfach und streng, um klare Denkweisen zu fördern.

---

## Grundprinzipien

### 1. Trennung der Concerns

| Ebene | Beschreibung |
|-------|--------------|
| **Struktur** | Semantische Geometrie: WAS existiert, WO und WARUM |
| **Daten** | Nur rohe Inhalte und Metadaten, keine Position oder Layout-Info |
| **Darstellung** | Wie wird es visuell umgesetzt (CSS/Grid) |

### 2. Keine Datenbank
Alles basiert auf flachen Dateien (PHP-Arrays). Vollständige Transparenz, Versionierbarkeit und Determinismus.

### 3. Deterministisch und streng
Keine impliziten Fallbacks. Ungültige Definitionen führen zu hartem Abbruch mit klarer Fehlermeldung. Das System ist korrekt oder es existiert nicht.

### 4. Validierung zentral
Jede Seite muss vor Rendering vollständig validiert werden. Fehler sind keine Warnungen, sondern Abbruchbedingungen. Der Validator sammelt **alle** Fehler einer Seite und meldet sie gemeinsam – inklusive „Meinten Sie …?"-Vorschlägen bei Tippfehlern.

### 5. Minimalismus
Nur das Nötigste. Erweiterungen nur durch explizite, dokumentierte Regeln.

### 6. Barrierefreiheit von Anfang an
Tag-Press generiert semantisches, WCAG 2.1 AA konformes HTML mit vollständiger Keyboard-Navigation, ARIA-Support und Screen Reader Optimierung. [Mehr Details →](ACCESSIBILITY.md)

### 7. Eine Quelle der Wahrheit
Die Platzierung von Objekten steht **ausschließlich** in den `page_assignments` (Tag-Notation). Seitentitel, Navigation und Sprache kommen aus der Geometrie – nichts wird doppelt gepflegt.

---

## Verzeichnisstruktur

```
tag-press/
├── index.php                  # Interpreter (lädt, validiert, rendert)
├── assets/
│   ├── styles.css             # Globale Styles (lokal, keine CDNs)
│   └── images/                # Lokale Bilder (SVG/JPG/PNG)
├── bin/
│   ├── validate.php           # CLI: alle Seiten prüfen (CI-tauglich)
│   ├── new-object.php         # CLI: Datenobjekt-Gerüst generieren
│   └── export.php             # CLI: statischer HTML-Export
├── config/
│   ├── Config.php             # Zentrale Pfadauflösung m()
│   ├── layout/
│   │   └── grid_master.php    # Physisches Grid (CSS-Klassen, Breakpoints)
│   └── classes/
│       ├── main_classes.php   # Parser, DataLoader, Renderer, TagPress
│       ├── Validator.php      # Zentrale Prüfinstanz
│       ├── HtmlDocument.php   # Dokument-Gerüst (Head, Navigation, Footer)
│       └── TagPressException.php
├── struktur/
│   └── main_geometrie.php     # Semantische Geometrie (zentrale Wahrheit)
├── daten/                     # Inhaltsdateien (sprechende Namen empfohlen)
└── tests/
    └── ValidatorTest.php      # Standalone-Testsuite (php tests/ValidatorTest.php)
```

---

## Semantische Geometrie

Die Datei `struktur/main_geometrie.php` ist die **zentrale Wahrheit** des Systems. Sie definiert:

- Site-weite Metadaten (`site`: Name, Sprache, Beschreibung, Footer)
- Welche Seiten existieren – mit Name, **URL-Slug** und Beschreibung
- Welche Zonen jede Seite hat und welche Bedeutung sie tragen
- Welche Objekttypen existieren und ihre Pflichtattribute
- Welche Objekte in welchen Zonen erscheinen (`page_assignments`)

### Notation

```
'Z1=hero_bild,hero_titel'
```

Bedeutet: Zone **Z1** enthält die Objekte `hero_bild` und `hero_titel` – in dieser Reihenfolge. Die Objekt-ID ist zugleich der Dateiname: `daten/hero_bild.php`.

**Sprechende Namen sind empfohlen.** Klassische IDs (`O1`, `O2`, …) funktionieren weiterhin, aber `Z1=hero_bild,hero_titel` erklärt sich selbst.

### Seiten und URLs

Jede Seite hat einen Slug für lesbare URLs:

| Aufruf | Ergebnis |
|--------|----------|
| `/?page=startseite` | Startseite (per Slug) |
| `/?page=A` | Startseite (per ID) |
| `/` | Erste Seite der Geometrie |
| `/?page=tippfehler` | Fehlerseite 404 mit Liste aller Seiten |

Die **Navigation wird automatisch** aus der Geometrie generiert (mit `aria-current` für die aktive Seite). Seiten mit `'in_nav' => false` erscheinen nicht im Menü.

### Zonen-Konzept

Eine Zone ist ein **semantischer Raum** mit klarer Funktion. Ihre Bedeutung ist unabhängig von ihrer visuellen Position.

| Zone | Bedeutung (Beispiel Startseite) |
|------|-----------|
| Z1 | Primärfokusbereich (Hero) |
| Z2 | Hauptinhaltsbereich |
| Z3 | Sekundärbereich |
| Z4 | Abschlussbereich |

Optional kann eine Zone `'allowed_objects'` definieren – dann wird die Zuweisung zusätzlich gegen diese Whitelist validiert.

### Objekttypen

Das System beschränkt sich bewusst auf wenige, klar definierte Typen. Die Attribute und Werte sind formal festgelegt:

| Typ      | Pflichtattribute         | Optionale Attribute | Werte/Details |
|----------|-------------------------|---------------------|---------------|
| `image`  | src (url), alt (string, min 5 Zeichen) | title (string), caption (string) | alt darf nicht Dateiname sein; lokale Bilddateien müssen existieren |
| `text`   | content (string), role (enum) | - | role: heading, subheading, intro, paragraph, note |
| `list`   | items (array, min 1)    | list_type (enum)    | list_type: ordered, unordered |
| `action` | label (string), href (url) | action_type (enum) | action_type: link, button (steuert nur die Optik – gerendert wird immer ein Link) |

**Wichtig:** Ein Bildobjekt ohne Alt-Text existiert in Tag-Press nicht!

---

## Datenebene

Dateien im `daten/`-Verzeichnis enthalten **ausschließlich Inhalte und Metadaten**:

```php
<?php
// daten/hero_bild.php
return [
    'type' => 'image',
    'src' => '/assets/images/hero-banner.svg',
    'alt' => 'Beschreibung des Bildes',
    'title' => 'Optionaler Titel'
];
```

Datenobjekte wissen **nichts** über:
- Ihre Position auf der Seite
- Ihre Größe oder Breite
- Die Zone, in der sie erscheinen
- Ihr Layout oder Styling

---

## Grid-Master

Der Grid-Master (`config/layout/grid_master.php`) übersetzt semantische Zonen in CSS-Klassen:

```php
<?php
return [
    'zones' => [
        'Z1' => 'zone-hero full-width bg-gradient',   // global
        'B.Z2' => 'zone-main container flow-container', // seitenspezifisch
    ],
    'objects' => [
        'image' => 'img-responsive img-cover',
        'text' => 'text-block prose',
    ]
];
```

Der Grid-Master:
- Kennt **keine** Inhalte
- Trifft **keine** Bedeutungsentscheidungen
- Ist ein reiner **Übersetzer** zwischen Geometrie und Darstellung

Ein Eintrag `'B.Z2'` (Seite.Zone) hat Vorrang vor dem globalen `'Z2'` – so kann dieselbe Zonen-ID pro Seite unterschiedlich dargestellt werden, ohne die Geometrie anzufassen.

---

## Validierung

Validierung ist ein **zentrales Prinzip**. Geprüft wird:

1. Existiert die angeforderte Seite?
2. Sind alle Zonen korrekt definiert und zugewiesen?
3. Sind alle Objekt-IDs gültig (kein Path-Traversal)?
4. Haben alle Objekte ihre Pflichtattribute im richtigen Datentyp?
5. Existieren referenzierte lokale Bilddateien?

Bei Fehlern: **Harter Abbruch** mit vollständigem Bericht – alle Fehler und Warnungen einer Seite auf einmal, bei Tippfehlern mit „Meinten Sie …?"-Vorschlag.

```bash
php bin/validate.php              # alle Seiten prüfen (Exit-Code für CI)
php bin/validate.php A kontakt    # nur bestimmte Seiten (ID oder Slug)
```

---

## Installation & Verwendung

1. Repository klonen
2. Webserver auf `index.php` zeigen lassen (oder statisch exportieren, s.u.)
3. Seite aufrufen: `/?page=startseite` (oder ohne Parameter für die erste Seite)

### Lokaler Test

```bash
cd tag-press
php -S localhost:8080
```

Dann im Browser: `http://localhost:8080`

Debug-Panel (Pfade, Cache, geladene Dateien): `http://localhost:8080/?debug=1`

---

## Eigene Website bauen (Schnellstart)

**1. Inhalte anlegen** – Datenobjekt-Gerüste generieren lassen:

```bash
php bin/new-object.php hero_titel text
php bin/new-object.php hero_bild image
```

Der Generator füllt alle Pflichtattribute aus der formalen Grammatik vor und dokumentiert erlaubte Werte. Danach die TODO-Werte in `daten/*.php` ausfüllen.

**2. Seite beschreiben** – in `struktur/main_geometrie.php`:

```php
'pages' => [
    'D' => [
        'name' => 'Leistungen',
        'slug' => 'leistungen',
        'zones' => [
            'Z1' => ['meaning' => 'Überblick über das Angebot'],
        ],
    ],
],
'page_assignments' => [
    'D' => ['Z1=hero_titel,hero_bild'],
],
```

Titel, Navigation und URL entstehen automatisch – nichts weiter nötig.

**3. Prüfen:**

```bash
php bin/validate.php
```

**4. Veröffentlichen** – entweder mit PHP-Hosting (Schritt „Installation") oder als statisches HTML:

```bash
php bin/export.php
```

Der Export rendert alle Seiten nach `export/` (`index.html`, `<slug>.html`, Assets inklusive) – lauffähig auf **jedem** Hosting, ganz ohne PHP. Möglich, weil Tag-Press deterministisch ist: gleiche Eingabe, gleiche Ausgabe.

---

## Didaktischer Fahrplan (für Azubis)

### Phase 1: Lesen und Verstehen
Jeder Azubi schreibt eine Dokumentation: "Was bedeutet Z1 semantisch?"

### Phase 2: Erweitern
Neue Zone oder Objekttyp definieren (muss argumentiert werden!)

### Phase 3: Validator bauen
Validierungslogik nachvollziehen und erweitern (`config/classes/Validator.php`)

### Phase 4: Renderer erweitern
Renderer-Klasse für neue Objekttypen ergänzen

### Phase 5: Echte Seite
Erste vollständige Seite bauen und testen – die Seiten „Über uns" (`/?page=ueber-uns`) und „Kontakt" (`/?page=kontakt`) sind als Referenz bereits umgesetzt

---

## Architektur-Übersicht

```
┌─────────────────────────────────────────────────────────────┐
│                        index.php                             │
│          (Interpreter: Slug-Auflösung, Orchestrierung)       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    GeometryParser                            │
│    (Lädt main_geometrie.php: Seiten, Slugs, Navigation)      │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐     ┌─────────────────────────────┐
│       Validator         │     │        DataLoader           │
│  (Sammelt ALLE Fehler)  │     │  (Lädt daten/*.php sicher)  │
└─────────────────────────┘     └─────────────────────────────┘
              │                               │
              └───────────────┬───────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                        Renderer                              │
│            (+ Grid-Master für CSS-Klassen)                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      HtmlDocument                            │
│   (Head, Navigation, Footer – für Server UND Export)         │
└─────────────────────────────────────────────────────────────┘
```

---

## Sicherheit

- Jede Ausgabe wird kontextgerecht escaped (auch Fehlermeldungen)
- Objekt-IDs und URL-Parameter werden streng validiert (kein Path-Traversal, keine Injection)
- Kein Inline-JavaScript, kein Inline-CSS – eine strikte Content-Security-Policy ohne `unsafe-inline` ist möglich
- Keine externen Abhängigkeiten: keine CDNs, keine Webfonts, keine Laufzeit-Paketmanager

---

## Tests

```bash
php tests/ValidatorTest.php
```

Die Testsuite läuft standalone (ohne PHPUnit) und prüft Parser, Validator, DataLoader, Renderer, Slug-Auflösung, Navigation und Sicherheits-Prüfungen.

---

## Warum Tag-Press?

Tag-Press ist kein weiteres CMS, sondern ein **strukturelles Gegenmodell** zu datenbankzentrierten Systemen. Es ist:

- **Deterministisch**: Kein Raten, kein "Best Effort"
- **Semantisch**: Bedeutung vor Darstellung
- **Streng**: Fehler werden nicht kaschiert
- **Lehrreich**: Zwingt zum Nachdenken über Abstraktion
- **Praktisch**: Vom Beschreiben bis zum statischen Export ohne Datenbank und ohne Build-Toolchain

Wer Tag-Press verstanden hat, versteht automatisch auch andere Systeme besser, weil er gelernt hat, zwischen Bedeutung, Struktur, Darstellung und Daten zu unterscheiden.

---

## Lizenz

MIT License - Freie Nutzung, Modifikation und Verteilung erlaubt.

---

*Tag-Press: Eleganz durch Einfachheit.*
