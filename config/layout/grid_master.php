<?php
/**
 * Tag-Press – Grid Master
 *
 * REINES MAPPING – KEINE LOGIK
 *
 * Der Grid-Master ist ein Übersetzungswörterbuch zwischen
 * semantischer Geometrie und visueller Darstellung.
 *
 * Diese Datei enthält AUSSCHLIESSLICH:
 * - Mapping-Arrays (Zone → CSS-Klassen)
 * - Mapping-Arrays (Objekttyp → CSS-Klassen)
 * - Konfigurationswerte (Breakpoints, Abstände)
 *
 * Diese Datei enthält NIEMALS:
 * - Funktionen oder Methoden
 * - Berechnungen oder Logik
 * - Bedingungen oder Schleifen
 * - Datenbankzugriffe oder Includes
 *
 * WICHTIG:
 * - Der Grid-Master kennt KEINE Inhalte
 * - Er trifft KEINE Bedeutungsentscheidungen
 * - Er ist ein reiner Übersetzer zwischen Geometrie und Darstellung
 *
 * Unterschiedliche Grid-Master können dieselbe Geometrie
 * unterschiedlich darstellen, ohne dass Struktur oder Daten
 * angepasst werden müssen. Das ermöglicht Themes.
 *
 * @author Rob de Roy
 * @version 0.1
 * @license MIT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Zonen → CSS-Klassen Mapping
    |--------------------------------------------------------------------------
    |
    | Jede Zone der Geometrie wird hier einer Liste von CSS-Klassen zugeordnet.
    | Die Klassen werden dem <section>-Element der Zone hinzugefügt.
    |
    | Format: 'ZoneID' => 'klasse1 klasse2 klasse3'
    |
    */

    'zones' => [
        // Globale Zuordnung (gilt für alle Seiten, sofern kein
        // seitenspezifischer Eintrag existiert)
        'Z1' => 'zone-hero full-width bg-gradient',
        'Z2' => 'zone-main container grid-container grid-cols-3',
        'Z3' => 'zone-secondary container flow-container',
        'Z4' => 'zone-footer container highlight-section',

        // Seitenspezifische Zuordnung: 'Seite.Zone' hat Vorrang.
        // So kann dieselbe Zonen-ID pro Seite anders dargestellt werden.
        'B.Z2' => 'zone-main container flow-container',
        'C.Z2' => 'zone-main container flow-container',
        'C.Z3' => 'zone-footer container highlight-section',
    ],

    /*
    |--------------------------------------------------------------------------
    | Objekttypen → CSS-Klassen Mapping
    |--------------------------------------------------------------------------
    |
    | Jeder Objekttyp der Geometrie wird hier einer Liste von CSS-Klassen zugeordnet.
    | Die Klassen werden dem Element des Objekts hinzugefügt.
    |
    | Format: 'objekttyp' => 'klasse1 klasse2'
    |
    */

    'objects' => [
        'image'      => 'img-responsive img-cover',
        'text'       => 'text-block prose',
        'list'       => 'list-styled',
        'action'     => 'action-element',
        // Erweiterte Webdesign-Objekte
        'button'     => 'btn btn-primary',
        'card'       => 'card card-shadow',
        'navigation' => 'nav-main',
        'form'       => 'form-styled',
        'video'      => 'video-responsive',
        'icon'       => 'icon',
        'alert'      => 'alert alert-warning',
        'badge'      => 'badge badge-info',
        'table'      => 'table table-striped',
        'blockquote' => 'blockquote highlight',
    ],

    /*
    |--------------------------------------------------------------------------
    | Breakpoint-Definitionen
    |--------------------------------------------------------------------------
    |
    | Definiert die responsiven Breakpoints für das Layout.
    | Diese Werte werden vom CSS verwendet (styles.css).
    |
    | Format: 'name' => 'pixel-wert'
    |
    */

    'breakpoints' => [
        'mobile'  => '320px',
        'tablet'  => '768px',
        'desktop' => '1024px',
        'wide'    => '1440px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abstands-Definitionen
    |--------------------------------------------------------------------------
    |
    | Standardabstände für Zonen und Objekte.
    | Sollten mit CSS Custom Properties übereinstimmen.
    |
    */

    'spacing' => [
        'zone-gap'          => '4rem',
        'object-gap'        => '2rem',
        'container-padding' => '2rem',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grid-Konfiguration
    |--------------------------------------------------------------------------
    |
    | Konfiguration für das CSS Grid Layout.
    |
    */

    'grid' => [
        'columns'   => 12,
        'gap'       => '1.5rem',
        'max-width' => '1200px',
    ],

];
