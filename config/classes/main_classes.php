<?php
/**
 * Tag-Press – Hauptklassen
 *
 * Dieses Modul lädt und orchestriert alle Kernklassen des Tag-Press Systems.
 * Die Klassen sind modular aufgebaut und nutzen die zentrale Config für Pfade.
 *
 * Modulare Komponenten:
 * - Config.php: Zentrale Pfadauflösung via m()
 * - TagPressException.php: Spezifische Fehlerbehandlung
 * - Validator.php: Validiert Geometrie und Daten
 *
 * Kernklassen in dieser Datei:
 * - GeometryParser: Lädt und interpretiert die semantische Geometrie
 * - DataLoader: Lädt Datenobjekte aus dem daten-Verzeichnis
 * - Renderer: Übersetzt das validierte Modell in HTML
 * - TagPress: Orchestriert den gesamten Prozess
 *
 * @author Rob de Roy
 * @version 0.2
 * @license MIT
 */

declare(strict_types=1);

// Lade zentrale Konfiguration (stellt m() bereit)
require_once __DIR__ . '/../Config.php';

// Lade modulare Komponenten via m()
require_once m('classes', 'TagPressException.php');
require_once m('classes', 'Validator.php');
require_once m('classes', 'HtmlDocument.php');

/**
 * GeometryParser
 *
 * Lädt und interpretiert die semantische Geometrie aus main_geometrie.php.
 * Die Geometrie definiert vollständig und abschließend, welche Zonen existieren,
 * welche Bedeutung sie haben und welche Objekte in ihnen zulässig sind.
 *
 * Nutzt m() für Pfadauflösung.
 */
class GeometryParser
{
    private ?array $geometry = null;

    /**
     * Lädt die Geometrie-Definition
     *
     * @throws TagPressException wenn die Datei nicht existiert oder ungültig ist
     */
    public function load(): array
    {
        // Nutze m() mit Validierung
        $geometry = load('struktur', 'main_geometrie.php');

        if (!is_array($geometry)) {
            throw new TagPressException(
                "Geometrie-Datei muss ein Array zurückgeben",
                m('struktur', 'main_geometrie.php')
            );
        }

        $this->validateGeometryStructure($geometry);
        $this->geometry = $geometry;

        return $geometry;
    }

    /**
     * Validiert die Grundstruktur der Geometrie
     */
    private function validateGeometryStructure(array $geometry): void
    {
        $required = ['pages', 'object_types', 'page_assignments'];

        foreach ($required as $key) {
            if (!isset($geometry[$key])) {
                throw new TagPressException(
                    "Pflichtfeld '{$key}' fehlt in der Geometrie-Definition",
                    m('struktur', 'main_geometrie.php')
                );
            }

            if (!is_array($geometry[$key])) {
                throw new TagPressException(
                    "Feld '{$key}' muss ein Array sein",
                    m('struktur', 'main_geometrie.php')
                );
            }
        }
    }

    /**
     * Gibt die Seitendefinition zurück
     */
    public function getPage(string $pageId): ?array
    {
        return $this->geometry['pages'][$pageId] ?? null;
    }

    /**
     * Gibt die Objekttyp-Definition zurück
     */
    public function getObjectType(string $type): ?array
    {
        return $this->geometry['object_types'][$type] ?? null;
    }

    /**
     * Gibt die Seitenzuweisung zurück (Tag-Notation)
     */
    public function getPageAssignment(string $pageId): ?array
    {
        return $this->geometry['page_assignments'][$pageId] ?? null;
    }

    /**
     * Gibt die Site-Konfiguration mit Standardwerten zurück
     */
    public function getSite(): array
    {
        $site = $this->geometry['site'] ?? [];

        return [
            'name' => $site['name'] ?? 'Tag-Press',
            'language' => $site['language'] ?? 'de',
            'description' => $site['description'] ?? '',
            'footer_text' => $site['footer_text'] ?? '',
        ];
    }

    /**
     * Löst eine Seiten-ID oder einen Slug zur Seiten-ID auf
     *
     * Erlaubt lesbare URLs: resolvePageId('startseite') → 'A'.
     * Leerer Wert liefert die erste definierte Seite (Startseite).
     *
     * @return string|null Die Seiten-ID oder null wenn unbekannt
     */
    public function resolvePageId(string $idOrSlug): ?string
    {
        $pages = $this->geometry['pages'] ?? [];

        if ($idOrSlug === '') {
            $ids = array_keys($pages);
            return $ids[0] ?? null;
        }

        if (isset($pages[$idOrSlug])) {
            return $idOrSlug;
        }

        foreach ($pages as $id => $page) {
            if (($page['slug'] ?? '') === $idOrSlug) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Gibt die Navigationseinträge zurück
     *
     * Seiten mit 'in_nav' => false werden ausgeblendet.
     *
     * @return array Liste von ['id', 'name', 'slug']
     */
    public function getNavigation(): array
    {
        $nav = [];

        foreach ($this->geometry['pages'] ?? [] as $id => $page) {
            if (($page['in_nav'] ?? true) === false) {
                continue;
            }
            $nav[] = [
                'id' => $id,
                'name' => $page['name'] ?? $id,
                'slug' => $page['slug'] ?? $id,
            ];
        }

        return $nav;
    }

    /**
     * Gibt die geparsten Zuweisungen einer Seite zurück
     *
     * Die Tag-Notation ist die einzige Quelle der Objekt-Platzierung.
     *
     * @return array ['Z1' => ['O1', 'O2'], 'Z2' => [...], ...]
     */
    public function getParsedAssignments(string $pageId): array
    {
        $parsed = [];

        foreach ($this->getPageAssignment($pageId) ?? [] as $notation) {
            $result = $this->parseTagNotation($notation);
            $parsed[$result['zone']] = $result['objects'];
        }

        return $parsed;
    }

    /**
     * Gibt die Objekte einer Zone zurück (abgeleitet aus der Tag-Notation)
     */
    public function getZoneObjects(string $pageId, string $zoneId): array
    {
        return $this->getParsedAssignments($pageId)[$zoneId] ?? [];
    }

    /**
     * Parst eine Tag-Notation wie "Z1=O1,O2"
     *
     * @return array ['zone' => 'Z1', 'objects' => ['O1', 'O2']]
     */
    public function parseTagNotation(string $notation): array
    {
        if (!preg_match('/^(Z\d+)=(.+)$/', $notation, $matches)) {
            throw new TagPressException(
                "Ungültige Tag-Notation",
                $notation
            );
        }

        $zone = $matches[1];
        $objects = array_map('trim', explode(',', $matches[2]));

        return [
            'zone' => $zone,
            'objects' => $objects
        ];
    }

    /**
     * Gibt die geladene Geometrie zurück
     */
    public function getGeometry(): ?array
    {
        return $this->geometry;
    }

    /**
     * Gibt alle definierten Seiten-IDs zurück
     */
    public function getPageIds(): array
    {
        return array_keys($this->geometry['pages'] ?? []);
    }

    /**
     * Gibt alle definierten Objekttypen zurück
     */
    public function getObjectTypes(): array
    {
        return $this->geometry['object_types'] ?? [];
    }
}

/**
 * DataLoader
 *
 * Lädt Datenobjekte aus dem daten-Verzeichnis.
 * Datendateien enthalten ausschließlich Inhalte und Metadaten,
 * ohne jede Information über Position, Größe, Reihenfolge oder Darstellung.
 *
 * Nutzt m() für Pfadauflösung.
 */
class DataLoader
{
    /**
     * Erlaubtes Format für Objekt-IDs.
     *
     * Objekt-IDs werden zu Dateinamen – dieses Muster verhindert
     * Path-Traversal und andere gefährliche Zeichen.
     */
    public const OBJECT_ID_PATTERN = '/^[A-Za-z][A-Za-z0-9_-]{0,63}$/';

    private array $loadedObjects = [];

    /**
     * Prüft ob eine Objekt-ID ein gültiges Format hat
     */
    public static function isValidObjectId(string $objectId): bool
    {
        return preg_match(self::OBJECT_ID_PATTERN, $objectId) === 1;
    }

    /**
     * Lädt ein einzelnes Datenobjekt
     *
     * @throws TagPressException wenn das Objekt nicht existiert oder ungültig ist
     */
    public function load(string $objectId): array
    {
        if (isset($this->loadedObjects[$objectId])) {
            return $this->loadedObjects[$objectId];
        }

        if (!self::isValidObjectId($objectId)) {
            throw new TagPressException(
                "Ungültige Objekt-ID: '{$objectId}'",
                "Erlaubt sind Buchstaben, Ziffern, '_' und '-' (Beginn mit Buchstabe, max. 64 Zeichen)"
            );
        }

        $fileName = strtolower($objectId) . '.php';

        // Nutze m() für Pfadauflösung mit Validierung
        try {
            $data = load('daten', $fileName);
        } catch (TagPressException $e) {
            throw new TagPressException(
                "Datenobjekt '{$objectId}' nicht gefunden",
                m('daten', $fileName)
            );
        }

        if (!is_array($data)) {
            throw new TagPressException(
                "Datenobjekt '{$objectId}' muss ein Array zurückgeben",
                m('daten', $fileName)
            );
        }

        if (!isset($data['type'])) {
            throw new TagPressException(
                "Datenobjekt '{$objectId}' hat keinen Typ definiert",
                m('daten', $fileName)
            );
        }

        $this->loadedObjects[$objectId] = $data;
        return $data;
    }

    /**
     * Lädt mehrere Objekte auf einmal
     */
    public function loadMultiple(array $objectIds): array
    {
        $objects = [];
        foreach ($objectIds as $id) {
            $objects[$id] = $this->load($id);
        }
        return $objects;
    }

    /**
     * Prüft ob ein Objekt existiert
     */
    public function exists(string $objectId): bool
    {
        if (!self::isValidObjectId($objectId)) {
            return false;
        }
        $fileName = strtolower($objectId) . '.php';
        return pathExists('daten', $fileName);
    }

    /**
     * Gibt den Pfad zum Datenverzeichnis zurück
     */
    public function getDataPath(): string
    {
        return m('daten');
    }

    /**
     * Listet alle verfügbaren Objekt-IDs auf
     *
     * Objekt-IDs entsprechen den Dateinamen (kleingeschrieben, ohne .php).
     */
    public function listObjects(): array
    {
        $files = listFiles('daten', '*.php');
        return array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $files);
    }

    /**
     * Leert den Cache geladener Objekte
     */
    public function clearCache(): void
    {
        $this->loadedObjects = [];
    }
}

/**
 * Renderer
 *
 * Übersetzt das validierte semantische Modell in HTML.
 * Der Renderer nutzt den Grid-Master für CSS-Klassen
 * und erzeugt barrierefreies, semantisches HTML.
 *
 * WICHTIG: Der Renderer wird NUR nach erfolgreicher Validierung aufgerufen.
 */
class Renderer
{
    private GeometryParser $geometry;
    private DataLoader $dataLoader;
    private array $gridMaster;

    public function __construct(GeometryParser $geometry, DataLoader $dataLoader, array $gridMaster)
    {
        $this->geometry = $geometry;
        $this->dataLoader = $dataLoader;
        $this->gridMaster = $gridMaster;
    }

    /**
     * Rendert eine komplette Seite
     */
    public function renderPage(string $pageId): string
    {
        $page = $this->geometry->getPage($pageId);

        $html = '';

        foreach ($this->geometry->getParsedAssignments($pageId) as $zoneId => $objectIds) {
            $html .= $this->renderZone($pageId, $page, $zoneId, $objectIds);
        }

        return $html;
    }

    /**
     * Rendert eine Zone mit ihren Objekten
     *
     * Grid-Klassen: Ein seitenspezifischer Eintrag 'B.Z3' im Grid-Master
     * hat Vorrang vor dem globalen Eintrag 'Z3'. So kann dieselbe Zonen-ID
     * auf verschiedenen Seiten unterschiedlich dargestellt werden.
     */
    private function renderZone(string $pageId, array $page, string $zoneId, array $objectIds): string
    {
        $zoneDef = $page['zones'][$zoneId];
        $zoneClasses = $this->gridMaster['zones']["{$pageId}.{$zoneId}"]
            ?? $this->gridMaster['zones'][$zoneId]
            ?? '';
        
        // ARIA-Label für bessere Barrierefreiheit
        $meaning = htmlspecialchars($zoneDef['meaning'], ENT_QUOTES, 'UTF-8');
        $ariaLabel = "aria-label=\"{$meaning}\"";

        $html = "<section class=\"zone {$zoneId} {$zoneClasses}\" {$ariaLabel} data-zone=\"{$zoneId}\">\n";

        // Semantische Bedeutung als Kommentar für Entwickler
        $html .= "  <!-- {$zoneDef['meaning']} -->\n";

        foreach ($objectIds as $objectId) {
            $html .= $this->renderObject($objectId);
        }

        $html .= "</section>\n";

        return $html;
    }

    /**
     * Rendert ein einzelnes Objekt
     */
    private function renderObject(string $objectId): string
    {
        $data = $this->dataLoader->load($objectId);
        $type = $data['type'];
        $objectClasses = $this->gridMaster['objects'][$type] ?? '';

        return match ($type) {
            'image' => $this->renderImage($objectId, $data, $objectClasses),
            'text' => $this->renderText($objectId, $data, $objectClasses),
            'list' => $this->renderList($objectId, $data, $objectClasses),
            'action' => $this->renderAction($objectId, $data, $objectClasses),
            default => "  <!-- Unbekannter Objekttyp: {$type} -->\n"
        };
    }

    /**
     * Rendert ein Bildobjekt
     */
    private function renderImage(string $objectId, array $data, string $classes): string
    {
        $src = htmlspecialchars($data['src'], ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($data['alt'] ?? '', ENT_QUOTES, 'UTF-8');
        $title = isset($data['title']) ? htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8') : '';
        
        // Warnung wenn Alt-Text fehlt (für Barrierefreiheit kritisch)
        if (empty($alt)) {
            $alt = ''; // Dekoratives Bild, leerer Alt-Text ist korrekt
        }

        $html = "  <figure class=\"object object-image {$classes}\" data-object=\"{$objectId}\">\n";
        $html .= "    <img src=\"{$src}\" alt=\"{$alt}\"";
        if ($title) {
            $html .= " title=\"{$title}\"";
        }
        $html .= " loading=\"lazy\">\n";

        // Caption wenn vorhanden
        if (isset($data['caption'])) {
            $caption = htmlspecialchars($data['caption'], ENT_QUOTES, 'UTF-8');
            $html .= "    <figcaption>{$caption}</figcaption>\n";
        }

        $html .= "  </figure>\n";

        return $html;
    }

    /**
     * Rendert ein Textobjekt
     */
    private function renderText(string $objectId, array $data, string $classes): string
    {
        $content = htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8');
        $role = $data['role'];

        $tag = match($role) {
            'heading' => 'h1',
            'subheading' => 'h2',
            'intro' => 'p',
            'paragraph' => 'p',
            'note' => 'aside',
            default => 'div'
        };

        $roleClass = "role-{$role}";

        $html = "  <{$tag} class=\"object object-text {$roleClass} {$classes}\" data-object=\"{$objectId}\">\n";
        $html .= "    {$content}\n";
        $html .= "  </{$tag}>\n";

        return $html;
    }

    /**
     * Rendert ein Listenobjekt
     */
    private function renderList(string $objectId, array $data, string $classes): string
    {
        $items = $data['items'];
        $listType = $data['list_type'] ?? 'unordered';
        $tag = $listType === 'ordered' ? 'ol' : 'ul';

        $html = "  <{$tag} class=\"object object-list {$classes}\" data-object=\"{$objectId}\">\n";
        foreach ($items as $item) {
            $itemText = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
            $html .= "    <li>{$itemText}</li>\n";
        }
        $html .= "  </{$tag}>\n";

        return $html;
    }

    /**
     * Rendert ein Aktionsobjekt (Button/Link)
     *
     * Aktionen navigieren immer zu einer URL und werden deshalb als
     * <a>-Element gerendert – ein <button> ohne JavaScript wäre funktionslos.
     * 'action_type' => 'button' steuert nur die visuelle Darstellung.
     */
    private function renderAction(string $objectId, array $data, string $classes): string
    {
        $label = htmlspecialchars($data['label'], ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars($data['href'], ENT_QUOTES, 'UTF-8');
        $actionType = $data['action_type'] ?? 'link';
        $typeClass = $actionType === 'button' ? 'action-button' : 'action-link';

        // ARIA-Label für bessere Beschreibung wenn vorhanden
        $ariaLabel = isset($data['aria_label']) ? htmlspecialchars($data['aria_label'], ENT_QUOTES, 'UTF-8') : '';
        $ariaAttr = $ariaLabel ? " aria-label=\"{$ariaLabel}\"" : '';

        // Externe Links mit rel="noopener noreferrer" für Sicherheit.
        // Ohne Host-Kontext (CLI/Export) gilt jede absolute URL als extern.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isExternal = str_starts_with($data['href'], 'http')
            && ($host === '' || !str_contains($data['href'], $host));
        $relAttr = $isExternal ? ' rel="noopener noreferrer"' : '';

        $html = "  <a href=\"{$href}\" class=\"object object-action {$typeClass} {$classes}\" data-object=\"{$objectId}\"{$ariaAttr}{$relAttr}>\n";
        $html .= "    {$label}\n";
        $html .= "  </a>\n";

        return $html;
    }
}

/**
 * TagPress
 *
 * Hauptklasse, die den gesamten Rendering-Prozess orchestriert.
 * Nutzt m() für alle Pfadauflösungen.
 *
 * Ablauf:
 * 1. Geometrie laden via m('struktur', ...)
 * 2. Grid-Master laden via m('layout', ...)
 * 3. Validierung durchführen (PFLICHT)
 * 4. Rendering ausführen
 */
class TagPress
{
    private GeometryParser $geometry;
    private DataLoader $dataLoader;
    private Validator $validator;
    private Renderer $renderer;
    private array $gridMaster;

    public function __construct()
    {
        $this->loadGridMaster();
        $this->initializeComponents();
    }

    /**
     * Lädt den Grid-Master via m()
     */
    private function loadGridMaster(): void
    {
        $this->gridMaster = load('layout', 'grid_master.php');
    }

    /**
     * Initialisiert alle Komponenten
     */
    private function initializeComponents(): void
    {
        $this->geometry = new GeometryParser();
        $this->geometry->load();

        $this->dataLoader = new DataLoader();
        $this->validator = new Validator($this->geometry, $this->dataLoader);
        $this->renderer = new Renderer($this->geometry, $this->dataLoader, $this->gridMaster);
    }

    /**
     * Rendert eine Seite vollständig
     *
     * @throws TagPressException bei Validierungsfehlern
     */
    public function render(string $pageId = 'A'): string
    {
        // Validierung ist Pflicht - kommt IMMER vor Rendering
        $this->validator->validatePage($pageId);

        // Rendering nur bei erfolgreicher Validierung
        return $this->renderer->renderPage($pageId);
    }

    /**
     * Gibt die Geometrie-Instanz zurück
     */
    public function getGeometry(): GeometryParser
    {
        return $this->geometry;
    }

    /**
     * Gibt den Validator zurück
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Gibt den DataLoader zurück
     */
    public function getDataLoader(): DataLoader
    {
        return $this->dataLoader;
    }

    /**
     * Gibt den Grid-Master zurück
     */
    public function getGridMaster(): array
    {
        return $this->gridMaster;
    }

    /**
     * Gibt Projektinformationen zurück
     */
    public static function info(): array
    {
        return projectInfo();
    }

    /**
     * Gibt Debug-Informationen über Pfade zurück
     */
    public static function debugPaths(): array
    {
        return debugPaths();
    }
}
