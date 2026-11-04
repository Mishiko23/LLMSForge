<?php
declare(strict_types=1);

namespace LLMSForge;

use MODX\Revolution\modX;

require_once __DIR__ . '/Renderer.php';
require_once __DIR__ . '/Documents.php';

final class Generator
{
    public function __construct(private modX $modx) {}

    public function generate(?string $contextKey = null, array $overrides = []): string
    {
        $context = $contextKey ?? (string) $this->modx->context->get('key');
        if ($context === 'mgr') {
            throw new \RuntimeException('Generate in a public site context.');
        }
        $siteContext = $this->modx->getContext($context);
        if (!$siteContext) { throw new \RuntimeException('Unknown context'); }
        if (!$siteContext->prepare()) { throw new \RuntimeException('Cannot prepare context'); }
        $get = fn(string $key, $default = '') => $overrides[$key] ?? $siteContext->getOption('llmsforge.' . $key, $default);
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $get('include_ids'))), fn($id) => $id > 0)));
        $excluded = array_map('intval', explode(',', (string) $get('exclude_ids')));
        $sections = json_decode((string) $get('sections', '{}'), true);
        if (!is_array($sections)) {
            throw new \RuntimeException('llmsforge.sections must contain a JSON object.');
        }
        $pages = [];
        $documents = new Documents($this->modx);
        // Explicit selection avoids accidentally exposing newly created resources.
        foreach (array_slice($ids, 0, 5000) as $id) {
            if (in_array($id, $excluded, true)) {
                continue;
            }
            $resource = $documents->resource($id, $context);
            if (!$resource) { continue; }
            $pages[] = [
                'title' => $resource->get('pagetitle'),
                'description' => $resource->get('description'),
                'url' => $this->modx->makeUrl($id, $context, '', 'full'),
                'section' => is_string($sections[(string) $id] ?? null) ? $sections[(string) $id] : 'Pages',
            ];
        }
        $items = Documents::validate((string) $get('documents', '[]'));
        $documentLinks = $documents->links($items, $context, (string) $siteContext->getOption('site_url'));
        $renderer = new Renderer();
        if ($get('main_mode', 'auto') === 'manual') {
            $output = rtrim((string) $get('main_markdown')) . "\n";
            if ($documentLinks) {
                $index = $renderer->render('Index', '', $documentLinks);
                $output .= "\n" . substr($index, strpos($index, '## '));
            }
            return $output;
        }
        return $renderer->render((string) $get('title', $this->modx->getOption('site_name')), (string) $get('description'), array_merge($pages, $documentLinks));
    }
}
