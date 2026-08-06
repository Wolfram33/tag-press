<?php
/**
 * Tag-Press – Semantische Geometrie
 *
 * Diese Datei ist die zentrale Wahrheit des Tag-Press Systems.
 * Sie definiert vollständig und abschließend:
 * - Site-weite Metadaten (Name, Sprache, Footer)
 * - Welche Seiten existieren (mit Namen und URL-Slug)
 * - Welche Zonen jede Seite hat und welche Bedeutung sie tragen
 * - Welche Objekttypen existieren und ihre Pflichtattribute
 * - Welche Objekte in welchen Zonen erscheinen (page_assignments)
 *
 * EINE QUELLE DER WAHRHEIT:
 * Die Platzierung von Objekten steht AUSSCHLIESSLICH in 'page_assignments'
 * (Tag-Notation). Zonen beschreiben nur noch ihre semantische Bedeutung.
 * Optional kann eine Zone 'allowed_objects' definieren – dann wird die
 * Zuweisung zusätzlich gegen diese Whitelist validiert.
 *
 * WICHTIG: Diese Datei enthält KEIN HTML und KEIN CSS.
 * Layout-Angaben gehören in config/layout/grid_master.php.
 *
 * @author Rob de Roy
 * @version 0.2
 * @license MIT
 */

return [
    /**
     * Site-weite Metadaten
     *
     * 'name'        → erscheint im <title> und im Footer
     * 'language'    → lang-Attribut des HTML-Dokuments
     * 'description' → Standard-Meta-Description (Seiten können sie überschreiben)
     * 'footer_text' → Text im Footer (wird escaped ausgegeben)
     */
    'site' => [
        'name' => 'Tag-Press',
        'language' => 'de',
        'description' => 'Ein dateibasiertes, deklaratives Website-System ohne klassische Datenbank.',
        'footer_text' => 'Ein Projekt von Rob de Roy',
    ],

    /**
     * Seiten-Definitionen
     *
     * Jede Seite wird durch einen eindeutigen Bezeichner identifiziert.
     * 'slug' erlaubt lesbare URLs: /?page=startseite statt /?page=A
     * 'in_nav' => false blendet eine Seite aus der Navigation aus.
     */
    'pages' => [
        'A' => [
            'name' => 'Startseite',
            'slug' => 'startseite',
            'description' => 'Die Haupteinstiegsseite der Website',
            'zones' => [
                /**
                 * Z1 - Primärfokusbereich (Hero)
                 * Der erste, prominenteste Bereich, der die Aufmerksamkeit
                 * des Besuchers sofort fängt.
                 */
                'Z1' => [
                    'meaning' => 'Primärfokusbereich - Hero-Sektion mit Hauptbotschaft',
                ],

                /**
                 * Z2 - Hauptinhaltsbereich
                 * Der zentrale Inhaltsbereich, in dem die
                 * Hauptinformationen präsentiert werden.
                 */
                'Z2' => [
                    'meaning' => 'Hauptinhaltsbereich - Zentrale Informationen und Features',
                ],

                /**
                 * Z3 - Sekundärbereich
                 * Ergänzende Informationen, die den Hauptinhalt
                 * unterstützen aber nicht dominieren.
                 */
                'Z3' => [
                    'meaning' => 'Sekundärbereich - Ergänzende Inhalte und Details',
                ],

                /**
                 * Z4 - Abschlussbereich
                 * Der abschließende Bereich der Seite, typischerweise
                 * für Call-to-Actions oder Zusammenfassungen.
                 */
                'Z4' => [
                    'meaning' => 'Abschlussbereich - Finale Handlungsaufforderung',
                ],
            ],
        ],

        'B' => [
            'name' => 'Über uns',
            'slug' => 'ueber-uns',
            'description' => 'Informationen über das Projekt und Team',
            'zones' => [
                'Z1' => [
                    'meaning' => 'Einleitungsbereich - Wer wir sind',
                ],
                'Z2' => [
                    'meaning' => 'Geschichte und Hintergrund',
                ],
                'Z3' => [
                    'meaning' => 'Team-Vorstellung',
                ],
            ],
        ],

        'C' => [
            'name' => 'Kontakt',
            'slug' => 'kontakt',
            'description' => 'Kontaktinformationen und Handlungsaufforderung',
            'zones' => [
                'Z1' => [
                    'meaning' => 'Kontakt-Header mit Überschrift',
                ],
                'Z2' => [
                    'meaning' => 'Kontaktdetails',
                ],
                'Z3' => [
                    'meaning' => 'Handlungsaufforderung',
                ],
            ],
        ],
    ],

    /**
     * Objekttyp-Definitionen (Formale Grammatik)
     *
     * Jeder Objekttyp definiert exakt:
     * - 'type': Kategorie des Datentyps (scalar, compound, interactive)
     * - 'attributes': Formale Definition jedes Attributs mit Datentyp
     * - 'constraints': Zusätzliche Validierungsregeln
     *
     * Das System ist bewusst auf wenige, klar definierte Typen beschränkt.
     * Neue Typen müssen semantisch begründet werden.
     *
     * Attribut-Datentypen:
     * - string: Zeichenkette (nicht leer wenn required)
     * - url: Gültige URL/Pfad-Angabe
     * - enum: Wert aus definierter Liste
     * - array: Liste von Elementen
     * - boolean: true/false
     */
    'object_types' => [
        /**
         * image - Bildobjekt
         *
         * Typ: scalar (einfaches Medienobjekt)
         * Ein Bild ohne Alt-Text existiert in Tag-Press nicht!
         */
        'image' => [
            'type' => 'scalar',
            'description' => 'Ein Bildobjekt mit Quelle und Alternativtext',
            'attributes' => [
                'src' => [
                    'data_type' => 'url',
                    'required' => true,
                    'description' => 'Pfad oder URL zur Bilddatei'
                ],
                'alt' => [
                    'data_type' => 'string',
                    'required' => true,
                    'min_length' => 5,
                    'description' => 'Alternativtext für Barrierefreiheit (Pflicht!)'
                ],
                'title' => [
                    'data_type' => 'string',
                    'required' => false,
                    'description' => 'Optionaler Titel für Tooltip'
                ],
                'caption' => [
                    'data_type' => 'string',
                    'required' => false,
                    'description' => 'Optionale Bildunterschrift'
                ]
            ],
            'constraints' => [
                'alt_not_filename' => true,   // Alt-Text darf nicht der Dateiname sein
                'src_must_exist' => true,     // Lokale Bilddateien müssen existieren
            ]
        ],

        /**
         * text - Textobjekt
         *
         * Typ: scalar (einfaches Inhaltsobjekt)
         * Die Rolle bestimmt die semantische Bedeutung.
         */
        'text' => [
            'type' => 'scalar',
            'description' => 'Ein Textobjekt mit Inhalt und semantischer Rolle',
            'attributes' => [
                'content' => [
                    'data_type' => 'string',
                    'required' => true,
                    'min_length' => 1,
                    'description' => 'Der Textinhalt'
                ],
                'role' => [
                    'data_type' => 'enum',
                    'required' => true,
                    'allowed_values' => ['heading', 'subheading', 'intro', 'paragraph', 'note'],
                    'description' => 'Semantische Rolle des Textes'
                ]
            ],
            'constraints' => []
        ],

        /**
         * list - Listenobjekt
         *
         * Typ: compound (zusammengesetztes Objekt mit Kindelementen)
         */
        'list' => [
            'type' => 'compound',
            'description' => 'Eine geordnete oder ungeordnete Liste',
            'attributes' => [
                'items' => [
                    'data_type' => 'array',
                    'required' => true,
                    'min_items' => 1,
                    'item_type' => 'string',
                    'description' => 'Array von Listenelementen'
                ],
                'list_type' => [
                    'data_type' => 'enum',
                    'required' => false,
                    'allowed_values' => ['ordered', 'unordered'],
                    'default' => 'unordered',
                    'description' => 'Art der Liste'
                ]
            ],
            'constraints' => []
        ],

        /**
         * action - Aktionsobjekt (Button/Link)
         *
         * Typ: interactive (interaktives Element)
         * Wird immer als Link gerendert; 'button' steuert nur die Optik.
         */
        'action' => [
            'type' => 'interactive',
            'description' => 'Ein interaktives Element wie Button oder Link',
            'attributes' => [
                'label' => [
                    'data_type' => 'string',
                    'required' => true,
                    'min_length' => 1,
                    'max_length' => 100,
                    'description' => 'Beschriftung des Elements'
                ],
                'href' => [
                    'data_type' => 'url',
                    'required' => true,
                    'description' => 'Ziel-URL oder Anker'
                ],
                'action_type' => [
                    'data_type' => 'enum',
                    'required' => false,
                    'allowed_values' => ['link', 'button'],
                    'default' => 'link',
                    'description' => 'Darstellungsart'
                ]
            ],
            'constraints' => [
                'href_not_empty' => true
            ]
        ]
    ],

    /**
     * Seitenzuweisungen (Tag-Notation) – DIE einzige Quelle der Platzierung
     *
     * Hier wird festgelegt, welche Objekte in welchen Zonen einer Seite
     * erscheinen – und in welcher Reihenfolge. Die Notation ist deterministisch:
     *
     * 'Z1=O1,O2' bedeutet: Zone Z1 enthält die Objekte O1 und O2 (in dieser
     * Reihenfolge). Objekt-IDs entsprechen Dateinamen in daten/
     * (kleingeschrieben, ohne .php).
     *
     * Sprechende Namen sind erlaubt und empfohlen:
     * 'Z1=hero_bild,hero_titel' lädt daten/hero_bild.php und daten/hero_titel.php
     */
    'page_assignments' => [
        'A' => [
            'Z1=O1,O2,O3',
            'Z2=O4,O5,O6',
            'Z3=O7,O8',
            'Z4=O9,O10'
        ],
        'B' => [
            'Z1=about_titel,about_intro',
            'Z2=about_geschichte_titel,about_bild,about_meilensteine',
            'Z3=about_team_titel,about_team'
        ],
        'C' => [
            'Z1=kontakt_titel,kontakt_intro',
            'Z2=kontakt_wege',
            'Z3=kontakt_mitmachen,kontakt_aktion'
        ]
    ]
];
