<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class PolicyContent
{
    public static function render(?string $content): string
    {
        if (! $content) {
            return '';
        }

        if ($content === strip_tags($content)) {
            return nl2br(e($content));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$content.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $body = $document->getElementsByTagName('body')->item(0);

        return $body ? self::children($body) : '';
    }

    private static function children(DOMNode $parent): string
    {
        $html = '';
        foreach ($parent->childNodes as $node) {
            $html .= self::node($node);
        }

        return $html;
    }

    private static function node(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return e($node->textContent);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math'], true)) {
            return '';
        }
        if (! in_array($tag, ['p', 'div', 'span', 'br', 'ol', 'ul', 'li', 'strong', 'b', 'em', 'i', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'], true)) {
            return self::children($node);
        }
        if ($tag === 'br') {
            return '<br>';
        }

        // Retain editor formatting without exposing scripts or arbitrary HTML attributes.
        $attributes = '';
        $classes = array_filter(preg_split('/\s+/', $node->getAttribute('class')), fn ($class) =>
            preg_match('/^ql-(?:indent-[1-8]|align-(?:center|right|justify)|direction-rtl)$/', $class));
        if ($classes) {
            $attributes .= ' class="'.e(implode(' ', $classes)).'"';
        }
        $numberAttribute = $tag === 'ol' ? 'start' : ($tag === 'li' ? 'value' : null);
        if ($numberAttribute && preg_match('/^-?\d{1,6}$/', $node->getAttribute($numberAttribute))) {
            $attributes .= ' '.$numberAttribute.'="'.(int) $node->getAttribute($numberAttribute).'"';
        }

        return '<'.$tag.$attributes.'>'.self::children($node).'</'.$tag.'>';
    }
}
