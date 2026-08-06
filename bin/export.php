<?php
/**
 * Tag-Press – Statischer Export
 *
 * Rendert alle Seiten als reines HTML in ein Zielverzeichnis und kopiert
 * die Assets. Das Ergebnis läuft auf jedem Hosting – ganz ohne PHP.
 *
 * Möglich, weil Tag-Press deterministisch und dateibasiert ist:
 * Gleiche Eingabe → gleiche Ausgabe.
 *
 * Verwendung:
 *   php bin/export.php            Export nach export/
 *   php bin/export.php mein-ziel  Export in ein anderes Verzeichnis
 *
 * Die erste Seite der Geometrie wird zu index.html, alle weiteren
 * zu <slug>.html. Interne Links und Asset-Pfade werden relativ umgeschrieben.
 *
 * @author Rob de Roy
 * @version 0.2
 * @license MIT
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Dieses Skript ist nur über die Kommandozeile ausführbar.\n";
    exit(1);
}

require_once __DIR__ . '/../config/classes/main_classes.php';

/**
 * Kopiert ein Verzeichnis rekursiv
 */
function copyDirectory(string $source, string $target): int
{
    if (!is_dir($target) && !mkdir($target, 0755, true)) {
        throw new TagPressException("Zielverzeichnis kann nicht erstellt werden", $target);
    }

    $count = 0;
    $items = scandir($source) ?: [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
        $targetPath = $target . DIRECTORY_SEPARATOR . $item;

        if (is_dir($sourcePath)) {
            $count += copyDirectory($sourcePath, $targetPath);
        } else {
            if (!copy($sourcePath, $targetPath)) {
                throw new TagPressException("Datei kann nicht kopiert werden", $sourcePath);
            }
            $count++;
        }
    }

    return $count;
}

echo "=== Tag-Press Statischer Export ===\n\n";

// Zielverzeichnis: Argument oder Standard 'export' (relativ zum Projekt-Root)
$targetArg = $argv[1] ?? 'export';

// Zielverzeichnis säubern: kein Path-Traversal, keine absoluten Pfade
if (!preg_match('/^[A-Za-z0-9_][A-Za-z0-9_\/-]{0,127}$/', $targetArg) || str_contains($targetArg, '..')) {
    echo "FEHLER: Ungültiges Zielverzeichnis '{$targetArg}'.\n";
    echo "Erlaubt: relativer Pfad aus Buchstaben, Ziffern, '_', '-' und '/'.\n";
    exit(1);
}

$targetDir = TAG_PRESS_ROOT . rtrim($targetArg, '/');

try {
    $tagPress = new TagPress();
    $geometry = $tagPress->getGeometry();
    $document = new HtmlDocument($geometry, HtmlDocument::MODE_STATIC);

    $pageIds = $geometry->getPageIds();
    if (empty($pageIds)) {
        echo "FEHLER: Keine Seiten in der Geometrie definiert.\n";
        exit(1);
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        echo "FEHLER: Zielverzeichnis kann nicht erstellt werden: {$targetDir}\n";
        exit(1);
    }

    // Ersetzungstabelle: absolute Server-Pfade → relative statische Pfade
    $replacements = [
        'src="/assets/' => 'src="assets/',
        'href="/assets/' => 'href="assets/',
    ];
    foreach ($geometry->getNavigation() as $entry) {
        $replacements['href="/?page=' . rawurlencode($entry['slug']) . '"']
            = 'href="' . $entry['slug'] . '.html"';
        $replacements['href="/?page=' . $entry['id'] . '"']
            = 'href="' . $entry['slug'] . '.html"';
    }

    // Alle Seiten validieren und rendern (Validierung ist Pflicht)
    foreach ($pageIds as $pageId) {
        $page = $geometry->getPage($pageId);
        $navEntry = [
            'id' => $pageId,
            'name' => $page['name'] ?? $pageId,
            'slug' => $page['slug'] ?? $pageId,
        ];
        $fileName = $document->staticFileName($navEntry);

        $content = $tagPress->render($pageId);
        $html = $document->render($pageId, $content);
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // Der Slug der ersten Seite bleibt zusätzlich als Datei erreichbar
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        if (file_put_contents($filePath, $html) === false) {
            echo "FEHLER: Konnte {$fileName} nicht schreiben.\n";
            exit(1);
        }

        echo "  ✓ Seite {$pageId} ({$navEntry['name']}) → {$fileName}\n";
    }

    // Assets kopieren
    $assetSource = m('assets');
    if (is_dir($assetSource)) {
        $copied = copyDirectory($assetSource, $targetDir . DIRECTORY_SEPARATOR . 'assets');
        echo "  ✓ {$copied} Asset-Datei(en) kopiert\n";
    } else {
        echo "  ! Kein assets-Verzeichnis gefunden – übersprungen\n";
    }

    echo "\nExport abgeschlossen: {$targetDir}\n";
    echo "Zum Testen einfach {$targetArg}/index.html im Browser öffnen.\n";
    exit(0);

} catch (TagPressException $e) {
    echo "\nEXPORT ABGEBROCHEN – Validierungsfehler:\n";
    echo $e->getFormattedMessage() . "\n";
    echo "Tipp: php bin/validate.php zeigt alle Fehler.\n";
    exit(1);
} catch (Throwable $e) {
    echo "\nEXPORT ABGEBROCHEN – Unerwarteter Fehler:\n{$e->getMessage()}\n";
    exit(1);
}
