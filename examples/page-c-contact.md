# Seite C: Kontakt (Beispiel)

## Hinweis

Diese Seite ist inzwischen **implementiert** – mit sprechenden Objekt-Namen statt `O20`-`O24` (siehe `struktur/main_geometrie.php`, `page_assignments['C']` und `daten/kontakt_*.php`). Aufrufbar unter `/?page=kontakt`.

Der ursprüngliche Vorschlag unten bleibt als Übungsmaterial erhalten und demonstriert, wie eine einfache Seite mit wenigen Zonen aussehen kann.

---

## Vorgeschlagene Geometrie

```php
'C' => [
    'name' => 'Kontakt',
    'description' => 'Kontaktinformationen und Handlungsaufforderung',
    'zones' => [
        'Z1' => [
            'meaning' => 'Kontakt-Header mit Überschrift',
            'allowed_objects' => ['O20', 'O21'],
            'order' => ['O20', 'O21'],
            'properties' => [
                'layout' => 'centered'
            ]
        ],
        'Z2' => [
            'meaning' => 'Kontaktdetails',
            'allowed_objects' => ['O22'],
            'order' => ['O22'],
            'properties' => [
                'layout' => 'flow'
            ]
        ],
        'Z3' => [
            'meaning' => 'Handlungsaufforderung',
            'allowed_objects' => ['O23', 'O24'],
            'order' => ['O23', 'O24'],
            'properties' => [
                'highlight' => true
            ]
        ]
    ]
]
```

---

## Benötigte Datenobjekte

### O20 – Kontakt-Überschrift
```php
return [
    'type' => 'text',
    'role' => 'heading',
    'content' => 'Kontakt'
];
```

### O21 – Einleitung
```php
return [
    'type' => 'text',
    'role' => 'intro',
    'content' => 'Fragen, Feedback oder Interesse an einer Zusammenarbeit? Wir freuen uns auf deine Nachricht.'
];
```

### O22 – Kontaktliste
```php
return [
    'type' => 'list',
    'list_type' => 'unordered',
    'items' => [
        'E-Mail: kontakt@robderoy.de',
        'Website: https://robderoy.de',
        'GitHub: github.com/robderoy/tag-press'
    ]
];
```

### O23 – Aufforderungstext
```php
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => 'Tag-Press ist ein Open-Source-Projekt. Beiträge sind willkommen!'
];
```

### O24 – GitHub-Link
```php
return [
    'type' => 'action',
    'label' => 'Zum GitHub Repository',
    'href' => 'https://github.com/robderoy/tag-press',
    'action_type' => 'button'
];
```

---

## Lernpunkte

### Weniger ist mehr
Diese Seite zeigt, dass nicht jede Seite 4 Zonen braucht. Die Struktur folgt dem Inhalt, nicht umgekehrt.

### Semantische Klarheit
- Z1: Header (Orientierung geben)
- Z2: Information (Fakten präsentieren)
- Z3: Aktion (zum Handeln auffordern)

### Wiederverwendung
Die Zone-Typen (meaning) können auf verschiedenen Seiten unterschiedliche IDs haben, aber ähnliche semantische Funktionen erfüllen.

---

## Tag-Notation (Ziel)

```
page_assignments['C'] = [
    'Z1=O20,O21',
    'Z2=O22',
    'Z3=O23,O24'
]
```
