<?php
declare(strict_types=1);
namespace LLMSForge;

final class HtmlToMarkdown
{
    public function convert(string $html): string
    {
        $html = preg_replace('/\[\[.*?\]\]/s', '', $html) ?? '';
        if (!class_exists(\DOMDocument::class)) { return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')); }
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $body = $document->getElementsByTagName('body')->item(0);
        $text = $body ? $this->children($body) : '';
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }

    private function children(\DOMNode $node): string
    {
        $result = '';
        foreach ($node->childNodes as $child) { $result .= $this->node($child); }
        return $result;
    }

    private function node(\DOMNode $node): string
    {
        if ($node instanceof \DOMText) { return preg_replace('/\s+/u', ' ', $node->textContent) ?? ''; }
        if (!$node instanceof \DOMElement) { return ''; }
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'form', 'noscript', 'svg'], true)) { return ''; }
        $text = $this->children($node);
        if (preg_match('/^h([1-6])$/', $tag, $match)) { return "\n\n" . str_repeat('#', (int) $match[1]) . ' ' . trim($text) . "\n\n"; }
        if ($tag === 'li') { return '- ' . trim($text) . "\n"; }
        if (in_array($tag, ['p', 'div', 'section', 'article', 'ul', 'ol', 'blockquote'], true)) { return "\n\n" . trim($text) . "\n\n"; }
        if ($tag === 'br') { return "\n"; }
        if (in_array($tag, ['strong', 'b'], true)) { return '**' . trim($text) . '**'; }
        if (in_array($tag, ['em', 'i'], true)) { return '*' . trim($text) . '*'; }
        if ($tag === 'a') {
            $url = $node->getAttribute('href');
            if (preg_match('~^https?://~i', $url)) { return '[' . trim($text) . '](' . str_replace(['(', ')'], ['%28', '%29'], $url) . ')'; }
        }
        return $text;
    }
}
