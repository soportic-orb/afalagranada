<?php
declare(strict_types=1);

/** Neteja l'HTML de l'editor visual amb una llista blanca d'etiquetes i atributs */
final class Sanitizer
{
    private const TAGS = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'a' => ['href', 'target', 'rel', 'title'], 'ul' => [], 'ol' => [], 'li' => ['data-list'],
        'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'img' => ['src', 'alt', 'title', 'width', 'height'], 'figure' => [], 'figcaption' => [],
        'span' => [], 'sub' => [], 'sup' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'iframe' => ['src', 'width', 'height', 'allowfullscreen', 'frameborder'],
    ];

    public static function html(?string $html): string
    {
        $html = trim((string)$html);
        if ($html === '' || $html === '<p><br></p>') {
            return '';
        }
        if (!class_exists('DOMDocument')) {
            return strip_tags($html, '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4><blockquote><img>');
        }
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('__root') ?? $doc->documentElement;
        self::clean($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    private static function clean(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta'], true)) {
                $node->removeChild($child);
                continue;
            }
            self::clean($child);
            if (!isset(self::TAGS[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowed = self::TAGS[$tag];
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                $val = trim($attr->value);
                $keep = in_array($name, $allowed, true);
                if ($name === 'class') {
                    $classes = array_filter(preg_split('/\s+/', $val), fn($c) => preg_match('/^ql-[a-z0-9-]+$/', $c));
                    if ($classes) {
                        $child->setAttribute('class', implode(' ', $classes));
                        continue;
                    }
                    $keep = false;
                }
                if ($keep && in_array($name, ['href', 'src'], true)) {
                    $keep = self::safeUrl($val, $tag === 'iframe');
                }
                if (!$keep) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'iframe' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img') {
                $child->setAttribute('loading', 'lazy');
            }
        }
    }

    private static function safeUrl(string $url, bool $iframe): bool
    {
        if ($iframe) {
            return (bool)preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)/#i', $url);
        }
        if (preg_match('#^(https?:|mailto:|tel:|/|\#)#i', $url)) {
            return true;
        }
        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#i', $url)) {
            return true;
        }
        return !preg_match('#^[a-z][a-z0-9+.-]*:#i', $url);
    }
}
