<?php
/**
 * Tag-Press – Interpreter / Einstiegspunkt
 *
 * Die index.php fungiert als Interpreter des Tag-Press Systems.
 * Sie orchestriert den gesamten Rendering-Prozess:
 *
 * 1. Lädt die zentrale Config (stellt m() bereit)
 * 2. Löst den page-Parameter auf (Seiten-ID oder Slug)
 * 3. Validiert die Struktur und Daten
 * 4. Rendert den Seiteninhalt
 * 5. Übergibt das Ergebnis an HtmlDocument (Dokument-Gerüst)
 *
 * WICHTIG: Diese Datei enthält selbst KEINE Layout- oder Inhaltslogik.
 * Sie ist ein Orchestrator, kein Template.
 *
 * @author Rob de Roy
 * @version 0.2
 * @license MIT
 * @link https://robderoy.de
 */

declare(strict_types=1);

// Fehlerbehandlung: Alle Fehler anzeigen (Entwicklungsmodus)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Lade Hauptklassen (lädt automatisch Config.php mit m())
require_once __DIR__ . '/config/classes/main_classes.php';

/**
 * Hauptausführung
 *
 * Tag-Press rendert eine Seite oder bricht bei Fehlern hart ab.
 * Es gibt keine halben Zustände.
 */
try {
    // page-Parameter ist Nutzereingabe: Format streng prüfen bevor er
    // irgendwo verwendet wird (Seiten-ID oder Slug, z.B. 'A' oder 'startseite')
    $pageParam = (string)($_GET['page'] ?? '');
    if ($pageParam !== '' && !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $pageParam)) {
        outputError("Ungültiger Seiten-Parameter.", 404);
    }

    // Debug-Modus via URL-Parameter ?debug=1
    $debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';

    // Tag-Press initialisieren (nutzt m() intern für alle Pfade)
    $tagPress = new TagPress();

    // Seiten-ID oder Slug auflösen; leer = erste Seite (Startseite)
    $pageId = $tagPress->getGeometry()->resolvePageId($pageParam);
    if ($pageId === null) {
        $known = [];
        foreach ($tagPress->getGeometry()->getNavigation() as $entry) {
            $known[] = "{$entry['slug']} ({$entry['id']})";
        }
        outputError(
            "Seite '{$pageParam}' wurde nicht gefunden.\n\nVerfügbare Seiten:\n- " . implode("\n- ", $known),
            404
        );
    }

    $content = $tagPress->render($pageId);

    // HTML-Dokument ausgeben (mit optionalem Debug-Panel)
    $document = new HtmlDocument($tagPress->getGeometry());
    $debugHtml = $debugMode ? debugPanel(true) : '';
    echo $document->render($pageId, $content, $debugHtml);

} catch (TagPressException $e) {
    // Tag-Press spezifischer Fehler
    outputError($e->getFormattedMessage());

} catch (Throwable $e) {
    // Allgemeiner Fehler
    outputError("Unerwarteter Fehler: " . $e->getMessage() . "\n\nStacktrace:\n" . $e->getTraceAsString());
}

/**
 * Gibt eine Fehlerseite aus und beendet die Ausführung
 *
 * Fehler in Tag-Press sind hart. Die Seite wird nicht gerendert,
 * wenn etwas ungültig ist. Stattdessen wird der Fehler klar angezeigt.
 * Die Meldung wird escaped ausgegeben – auch Fehlertexte sind Ausgabe.
 *
 * @param string $message    Die Fehlermeldung (Klartext)
 * @param int    $statusCode HTTP-Statuscode (500 für Validierung, 404 für unbekannte Seiten)
 */
function outputError(string $message, int $statusCode = 500): never
{
    http_response_code($statusCode);

    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $cssPath = htmlspecialchars(asset('styles.css'), ENT_QUOTES, 'UTF-8');
    $title = $statusCode === 404 ? 'Seite nicht gefunden' : 'Validierungsfehler';

    echo <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | Tag-Press</title>
    <link rel="stylesheet" href="{$cssPath}">
</head>
<body class="tag-press-error-page">
    <main class="error-container">
        <h1 class="error-title">Tag-Press {$title}</h1>
        <pre class="error-message">{$safeMessage}</pre>
        <div class="error-hint">
            <p>Das System ist korrekt oder es existiert nicht.</p>
            <p>Prüfen Sie:</p>
            <ul>
                <li>Die Geometrie-Definition in <code>struktur/main_geometrie.php</code></li>
                <li>Die Datenobjekte in <code>daten/*.php</code></li>
                <li>Die Seitenzuweisungen in <code>page_assignments</code></li>
            </ul>
            <p>Tipp: <code>php bin/validate.php</code> prüft alle Seiten auf einmal.</p>
        </div>
    </main>
</body>
</html>
HTML;

    exit(1);
}
