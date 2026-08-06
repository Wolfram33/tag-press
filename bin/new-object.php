<?php
/**
 * Tag-Press – Objekt-Generator (Scaffolding)
 *
 * Erzeugt eine neue Datendatei aus der formalen Grammatik der Geometrie:
 * Alle Pflichtattribute sind vorbereitet, optionale Attribute als
 * Kommentar dokumentiert – inklusive erlaubter Enum-Werte.
 *
 * Verwendung:
 *   php bin/new-object.php <objekt-id> <typ>
 *
 * Beispiele:
 *   php bin/new-object.php hero_bild image
 *   php bin/new-object.php team_liste list
 *
 * Danach: Objekt in struktur/main_geometrie.php unter 'page_assignments'
 * einer Zone zuweisen, z.B. 'Z1=hero_bild'.
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
 * Gibt die Hilfe aus und beendet das Skript
 */
function usage(array $types): never
{
    echo "Verwendung: php bin/new-object.php <objekt-id> <typ>\n\n";
    echo "  <objekt-id>  Sprechender Name (Buchstaben, Ziffern, '_', '-'),\n";
    echo "               wird zum Dateinamen daten/<objekt-id>.php\n";
    echo "  <typ>        Einer der definierten Objekttypen: " . implode(', ', $types) . "\n\n";
    echo "Beispiel: php bin/new-object.php hero_bild image\n";
    exit(1);
}

/**
 * Erzeugt einen Beispielwert für ein Attribut (als PHP-Code-String)
 */
function exampleValue(string $attrName, array $attrDef): string
{
    if (isset($attrDef['default'])) {
        return var_export($attrDef['default'], true);
    }

    return match ($attrDef['data_type'] ?? 'string') {
        'enum' => var_export($attrDef['allowed_values'][0] ?? '', true),
        'array' => "[\n        'Erstes Element',\n        'Zweites Element'\n    ]",
        'boolean' => 'true',
        'url' => "'/assets/images/beispiel.svg'",
        default => "''",
    };
}

try {
    $parser = new GeometryParser();
    $parser->load();
} catch (Throwable $e) {
    echo "FEHLER beim Laden der Geometrie:\n{$e->getMessage()}\n";
    exit(1);
}

$types = array_keys($parser->getObjectTypes());

$objectId = $argv[1] ?? '';
$type = $argv[2] ?? '';

if ($objectId === '' || $type === '') {
    usage($types);
}

// Eingaben streng prüfen: Objekt-ID wird zum Dateinamen
if (!DataLoader::isValidObjectId($objectId)) {
    echo "FEHLER: Ungültige Objekt-ID '{$objectId}'.\n";
    echo "Erlaubt: Buchstaben, Ziffern, '_' und '-' (Beginn mit Buchstabe, max. 64 Zeichen).\n";
    exit(1);
}

$typeDef = $parser->getObjectType($type);
if ($typeDef === null) {
    echo "FEHLER: Objekttyp '{$type}' ist nicht definiert.\n";
    echo "Definierte Typen: " . implode(', ', $types) . "\n";
    exit(1);
}

$fileName = strtolower($objectId) . '.php';
$filePath = m('daten', $fileName);

if (file_exists($filePath)) {
    echo "FEHLER: daten/{$fileName} existiert bereits – wird nicht überschrieben.\n";
    exit(1);
}

// Datei aus der Grammatik generieren
$lines = [];
$lines[] = "<?php";
$lines[] = "/**";
$lines[] = " * Datenobjekt {$objectId} - TODO: Kurzbeschreibung";
$lines[] = " *";
$lines[] = " * Generiert mit: php bin/new-object.php {$objectId} {$type}";
$lines[] = " * Dieses Objekt enthält ausschließlich Inhalte und Metadaten.";
$lines[] = " *";
$lines[] = " * @package Tag-Press";
$lines[] = " */";
$lines[] = "";
$lines[] = "return [";
$lines[] = "    'type' => '{$type}',";

foreach ($typeDef['attributes'] ?? [] as $attrName => $attrDef) {
    $required = $attrDef['required'] ?? false;
    $description = $attrDef['description'] ?? '';
    $hints = [];

    if (($attrDef['data_type'] ?? '') === 'enum' && isset($attrDef['allowed_values'])) {
        $hints[] = 'erlaubt: ' . implode(', ', $attrDef['allowed_values']);
    }
    if (isset($attrDef['min_length'])) {
        $hints[] = "min. {$attrDef['min_length']} Zeichen";
    }
    if (isset($attrDef['min_items'])) {
        $hints[] = "min. {$attrDef['min_items']} Element(e)";
    }

    $hintText = $description . ($hints ? ' (' . implode('; ', $hints) . ')' : '');
    $value = exampleValue($attrName, $attrDef);

    if ($required) {
        $lines[] = "";
        $lines[] = "    // PFLICHT: {$hintText}";
        $lines[] = "    '{$attrName}' => {$value},";
    } else {
        $lines[] = "";
        $lines[] = "    // Optional: {$hintText}";
        $lines[] = "    // '{$attrName}' => {$value},";
    }
}

$lines[] = "];";
$lines[] = "";

if (file_put_contents($filePath, implode("\n", $lines)) === false) {
    echo "FEHLER: Konnte daten/{$fileName} nicht schreiben.\n";
    exit(1);
}

echo "Erstellt: daten/{$fileName} (Typ: {$type})\n\n";
echo "Nächste Schritte:\n";
echo "  1. Pflichtattribute in daten/{$fileName} ausfüllen (TODO-Werte ersetzen)\n";
echo "  2. Objekt in struktur/main_geometrie.php zuweisen, z.B.:\n";
echo "     'Z1={$objectId}'\n";
echo "  3. Prüfen: php bin/validate.php\n";
exit(0);
