<?php
/**
 * Tag-Press – CLI-Validierung
 *
 * Prüft alle (oder ausgewählte) Seiten ohne Browser – geeignet für
 * CI-Pipelines und Pre-Commit-Hooks.
 *
 * Verwendung:
 *   php bin/validate.php              Alle Seiten prüfen
 *   php bin/validate.php A kontakt   Nur bestimmte Seiten (ID oder Slug)
 *
 * Exit-Code: 0 = alles gültig, 1 = mindestens ein Fehler
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

echo "=== Tag-Press Validierung ===\n\n";

try {
    $tagPress = new TagPress();
} catch (Throwable $e) {
    echo "FEHLER beim Laden des Systems:\n{$e->getMessage()}\n";
    exit(1);
}

$geometry = $tagPress->getGeometry();
$validator = $tagPress->getValidator();

// Argumente (IDs oder Slugs) auflösen; ohne Argumente: alle Seiten
$requested = array_slice($argv, 1);
$pageIds = [];

if (empty($requested)) {
    $pageIds = $geometry->getPageIds();
} else {
    foreach ($requested as $arg) {
        $resolved = $geometry->resolvePageId($arg);
        if ($resolved === null) {
            echo "FEHLER: Seite '{$arg}' ist nicht definiert.\n";
            echo "Definierte Seiten: " . implode(', ', $geometry->getPageIds()) . "\n";
            exit(1);
        }
        $pageIds[] = $resolved;
    }
}

$failed = 0;

foreach ($pageIds as $pageId) {
    $page = $geometry->getPage($pageId);
    $name = $page['name'] ?? $pageId;
    echo "Seite {$pageId} ({$name}): ";

    try {
        $validator->validatePage($pageId);
        $warnings = $validator->getWarnings();

        if (empty($warnings)) {
            echo "OK\n";
        } else {
            echo "OK mit " . count($warnings) . " Warnung(en)\n";
            foreach ($warnings as $warning) {
                echo "  ! {$warning}\n";
            }
        }
    } catch (TagPressException $e) {
        $failed++;
        echo "FEHLGESCHLAGEN\n";
        foreach ($validator->getErrors() as $error) {
            echo "  ✗ {$error}\n";
        }
        foreach ($validator->getWarnings() as $warning) {
            echo "  ! {$warning}\n";
        }
    }
}

echo "\n";

if ($failed > 0) {
    echo "Ergebnis: {$failed} von " . count($pageIds) . " Seite(n) ungültig.\n";
    exit(1);
}

echo "Ergebnis: Alle " . count($pageIds) . " Seite(n) gültig.\n";
exit(0);
