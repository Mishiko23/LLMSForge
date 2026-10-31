<?php
declare(strict_types=1);

namespace LLMSForge;

final class Renderer
{
    public function text(string $value): string
    {
        $value = preg_replace('/\[\[.*?\]\]/s', '', $value) ?? '';
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[\x00-\x20\x7f]+/u', ' ', $value) ?? '');
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '[', ']', '*', '_', '`', '<', '>'], ['\\\\', '\\[', '\\]', '\\*', '\\_', '\\`', '&lt;', '&gt;'], $this->text($value));
    }

    public function render(string $title, string $description, array $pages): string
    {
        $title = $this->escape($title);
        if ($title === '') {
            throw new \InvalidArgumentException('Site title must not be empty.');
        }
        $output = '# ' . $title . "\n";
        if ($this->text($description) !== '') {
            $output .= "\n> " . $this->escape($description) . "\n";
        }
        $sections = [];
        $seen = [];
        foreach ($pages as $page) {
            $url = (string) ($page['url'] ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                continue;
            }
            if (isset($seen[$url])) {
                continue;
            }
            $label = $this->escape((string) ($page['title'] ?? ''));
            if ($label === '') {
                continue;
            }
            $seen[$url] = true;
            $section = $this->escape((string) ($page['section'] ?? 'Pages')) ?: 'Pages';
            $url = str_replace(['(', ')', '<', '>'], ['%28', '%29', '%3C', '%3E'], $url);
            $line = '- [' . $label . '](' . $url . ')';
            $summary = $this->text((string) ($page['description'] ?? ''));
            if (preg_match('/^(.{1,155}?[.!?])(?:\s|$)/u', $summary, $firstSentence)) { $summary = $firstSentence[1]; }
            if (mb_strlen($summary, 'UTF-8') > 160) { $summary = rtrim(mb_substr($summary, 0, 157, 'UTF-8')) . '…'; }
            $summary = $this->escape($summary);
            $sections[$section][] = $line . ($summary !== '' ? ': ' . $summary : '');
        }
        foreach ($sections as $section => $lines) {
            $output .= "\n## " . $section . "\n\n" . implode("\n", $lines) . "\n";
        }
        return $output;
    }
}
