# Seite B: Über uns (Beispiel)

## Hinweis

Diese Seite ist inzwischen **implementiert** – mit sprechenden Objekt-Namen statt `O11`-`O17` (siehe `struktur/main_geometrie.php`, `page_assignments['B']` und `daten/about_*.php`). Aufrufbar unter `/?page=ueber-uns`.

Der ursprüngliche Vorschlag unten bleibt als Übungsmaterial erhalten: Vergleiche ihn mit der tatsächlichen Umsetzung – was wurde anders gelöst und warum? (Stichworte: eine Quelle der Wahrheit, keine Layout-Properties in der Geometrie, sprechende Namen.)

---

## Vorgeschlagene Geometrie

```php
'B' => [
    'name' => 'Über uns',
    'description' => 'Informationen über das Projekt und Team',
    'zones' => [
        'Z1' => [
            'meaning' => 'Einleitungsbereich - Wer wir sind',
            'allowed_objects' => ['O11', 'O12'],
            'order' => ['O11', 'O12'],
            'properties' => [
                'layout' => 'centered'
            ]
        ],
        'Z2' => [
            'meaning' => 'Geschichte und Hintergrund',
            'allowed_objects' => ['O13', 'O14', 'O15'],
            'order' => ['O13', 'O14', 'O15'],
            'properties' => [
                'layout' => 'timeline'
            ]
        ],
        'Z3' => [
            'meaning' => 'Team-Vorstellung',
            'allowed_objects' => ['O16', 'O17'],
            'order' => ['O16', 'O17'],
            'properties' => [
                'layout' => 'grid',
                'columns' => 2
            ]
        ]
    ]
]
```

---

## Benötigte Datenobjekte

### O11 – Seitenüberschrift
```php
return [
    'type' => 'text',
    'role' => 'heading',
    'content' => 'Über Tag-Press'
];
```

### O12 – Einleitungstext
```php
return [
    'type' => 'text',
    'role' => 'intro',
    'content' => 'Tag-Press entstand aus der Überzeugung, dass Eleganz in der Einfachheit liegt.'
];
```

### O13-O15 – Geschichte (Meilensteine)
```php
// O13
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => '2024: Erste Idee während eines Azubi-Workshops'
];

// O14
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => '2025: Formalisierung der Tag-Notation'
];

// O15
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => '2025: Erste stabile Version v0.1'
];
```

### O16-O17 – Team
```php
// O16
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => 'Rob de Roy - Konzept und Architektur'
];

// O17
return [
    'type' => 'text',
    'role' => 'paragraph',
    'content' => 'Azubi-Team - Implementierung und Tests'
];
```

---

## Aufgabe für Azubis

1. Erstelle die Datenobjekte O11-O17 in `daten/`
2. Füge die Seite B zur Geometrie hinzu
3. Erweitere `page_assignments` um B
4. Teste die Seite mit `?page=B`
5. Dokumentiere deine Entscheidungen

---

## Tag-Notation (Ziel)

```
page_assignments['B'] = [
    'Z1=O11,O12',
    'Z2=O13,O14,O15',
    'Z3=O16,O17'
]
```
