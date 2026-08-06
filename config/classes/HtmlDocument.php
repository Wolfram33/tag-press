<?php
/**
 * Tag-Press – HtmlDocument
 *
 * Erzeugt das vollständige HTML-Dokument um den gerenderten Seiteninhalt:
 * <head> mit Titel und Metadaten, Navigation, <main>, Footer.
 *
 * Die Klasse ist die EINZIGE Stelle, die das Dokument-Gerüst kennt.
 * Sie wird sowohl von index.php (Server-Modus) als auch von
 * bin/export.php (statischer Export) verwendet.
 *
 * Alle Werte aus der Geometrie werden kontextgerecht escaped –
 * es gibt keine Inline-Scripts und keine Inline-Styles (strikte CSP möglich).
 *
 * @author Rob de Roy
 * @version 0.2
 * @license MIT
 */

declare(strict_types=1);

class HtmlDocument
{
    public const MODE_SERVER = 'server';
    public const MODE_STATIC = 'static';

    private GeometryParser $geometry;
    private string $mode;

    public function __construct(GeometryParser $geometry, string $mode = self::MODE_SERVER)
    {
        $this->geometry = $geometry;
        $this->mode = $mode === self::MODE_STATIC ? self::MODE_STATIC : self::MODE_SERVER;
    }

    /**
     * Rendert das vollständige HTML-Dokument einer Seite
     *
     * @param string $pageId    Seiten-ID (muss in der Geometrie existieren)
     * @param string $content   Der bereits gerenderte Seiteninhalt (Zonen)
     * @param string $debugHtml Optionales Debug-Panel-HTML
     */
    public function render(string $pageId, string $content, string $debugHtml = ''): string
    {
        $site = $this->geometry->getSite();
        $page = $this->geometry->getPage($pageId) ?? [];

        $lang = htmlspecialchars($site['language'], ENT_QUOTES, 'UTF-8');
        $siteName = htmlspecialchars($site['name'], ENT_QUOTES, 'UTF-8');
        $pageName = htmlspecialchars($page['name'] ?? $pageId, ENT_QUOTES, 'UTF-8');
        $pageIdAttr = htmlspecialchars($pageId, ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(
            $page['description'] ?? $site['description'],
            ENT_QUOTES,
            'UTF-8'
        );
        $cssPath = htmlspecialchars($this->assetUrl('styles.css'), ENT_QUOTES, 'UTF-8');
        $version = TAG_PRESS_VERSION;

        $metaDescription = $description !== ''
            ? "    <meta name=\"description\" content=\"{$description}\">\n"
            : '';

        $nav = $this->renderNavigation($pageId);
        $footer = $this->renderFooter($site, (string)$version);

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Tag-Press v{$version}">
{$metaDescription}    <title>{$pageName} | {$siteName}</title>
    <link rel="stylesheet" href="{$cssPath}">
</head>
<body data-page="{$pageIdAttr}">
    <!-- Skip Links für Barrierefreiheit -->
    <a href="#main-content" class="skip-link">Zum Hauptinhalt springen</a>
    <a href="#footer-content" class="skip-link">Zum Footer springen</a>

{$nav}    <main id="main-content" class="tag-press-main">
{$content}
    </main>

{$footer}{$debugHtml}
</body>
</html>
HTML;
    }

    /**
     * Rendert die Hauptnavigation aus der Geometrie
     *
     * Bei nur einer Seite wird keine Navigation ausgegeben.
     * Die aktive Seite wird mit aria-current="page" markiert.
     */
    private function renderNavigation(string $currentPageId): string
    {
        $entries = $this->geometry->getNavigation();

        if (count($entries) < 2) {
            return '';
        }

        $items = '';
        foreach ($entries as $entry) {
            $url = htmlspecialchars($this->pageUrl($entry), ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8');
            $current = $entry['id'] === $currentPageId ? ' aria-current="page"' : '';
            $items .= "            <li><a href=\"{$url}\"{$current}>{$name}</a></li>\n";
        }

        return <<<HTML
    <nav class="tag-press-nav" aria-label="Hauptnavigation">
        <ul>
{$items}        </ul>
    </nav>

HTML;
    }

    /**
     * Rendert den Footer
     */
    private function renderFooter(array $site, string $version): string
    {
        $footerText = htmlspecialchars($site['footer_text'], ENT_QUOTES, 'UTF-8');
        $ownText = $footerText !== '' ? "{$footerText} | " : '';

        return <<<HTML
    <footer id="footer-content" class="tag-press-footer">
        <p>{$ownText}Powered by <strong>Tag-Press</strong> v{$version}</p>
    </footer>

HTML;
    }

    /**
     * Erzeugt die URL zu einer Seite (abhängig vom Modus)
     *
     * Server-Modus: /?page=slug
     * Statischer Export: index.html für die erste Seite, sonst slug.html
     */
    public function pageUrl(array $navEntry): string
    {
        if ($this->mode === self::MODE_STATIC) {
            return $this->staticFileName($navEntry);
        }

        return '/?page=' . rawurlencode($navEntry['slug']);
    }

    /**
     * Dateiname einer Seite im statischen Export
     */
    public function staticFileName(array $navEntry): string
    {
        $firstPageId = $this->geometry->getPageIds()[0] ?? null;

        if ($navEntry['id'] === $firstPageId) {
            return 'index.html';
        }

        return $navEntry['slug'] . '.html';
    }

    /**
     * Erzeugt die URL zu einem Asset (abhängig vom Modus)
     */
    private function assetUrl(string $name): string
    {
        if ($this->mode === self::MODE_STATIC) {
            return 'assets/' . ltrim($name, '/');
        }

        return asset($name);
    }
}
