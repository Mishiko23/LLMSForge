<?php
declare(strict_types=1);
namespace LLMSForge;
use MODX\Revolution\{modX, modResource, modResourceGroupResource};
require_once __DIR__ . '/Renderer.php';

final class Documents
{
    public function __construct(private modX $modx) {}

    public static function validate(string $json): array
    {
        $items = json_decode($json, true);
        if (!str_starts_with(ltrim($json), '[') || !is_array($items) || !array_is_list($items) || count($items) > 200) { throw new \InvalidArgumentException('Допустимо до 200 карточек страниц.'); }
        $slugs = [];
        foreach ($items as $item) {
            if (!is_array($item)) { throw new \InvalidArgumentException('Некорректная карточка.'); }
            foreach (['slug', 'title', 'url', 'mode', 'markdown', 'section'] as $field) {
                if (!isset($item[$field]) || !is_string($item[$field])) { throw new \InvalidArgumentException('Заполните поля карточки страницы.'); }
            }
            if (!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/D', $item['slug']) || isset($slugs[$item['slug']])) { throw new \InvalidArgumentException('Адрес карточки: уникальные латинские буквы, цифры и дефисы (до 80 символов).'); }
            $slugs[$item['slug']] = true;
            if (trim($item['title']) === '' || strlen($item['title']) > 500 || strlen($item['section']) > 300) { throw new \InvalidArgumentException('Укажите короткое название карточки и раздела.'); }
            if (!in_array($item['mode'], ['manual', 'resource'], true)) { throw new \InvalidArgumentException('Неизвестный источник содержимого.'); }
            if (!is_int($item['resource_id'] ?? null) || $item['resource_id'] < 0) { throw new \InvalidArgumentException('Некорректный ID ресурса.'); }
            if ($item['mode'] === 'resource' && $item['resource_id'] < 1) { throw new \InvalidArgumentException('Выберите ресурс для автозаполнения.'); }
            if ($item['mode'] === 'manual' && (!filter_var($item['url'], FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($item['url'], PHP_URL_SCHEME)), ['http', 'https'], true))) { throw new \InvalidArgumentException('Укажите полный URL страницы: https://…'); }
            if (strlen($item['markdown']) > 50000) { throw new \InvalidArgumentException('Markdown карточки: не более 50 КБ.'); }
            if ($item['mode'] === 'manual' && trim($item['markdown']) === '') { throw new \InvalidArgumentException('Заполните Markdown карточки.'); }
        }
        if (strlen($json) > 60000) { throw new \InvalidArgumentException('Общий объём карточек не должен превышать 60 КБ.'); }
        return $items;
    }

    public function resource(int $id, string $context): ?modResource
    {
        $resource = $this->modx->getObject(modResource::class, ['id' => $id, 'context_key' => $context, 'published' => 1, 'deleted' => 0, 'searchable' => 1]);
        if (!$resource || !$resource->checkPolicy('view') || $this->modx->getCount(modResourceGroupResource::class, ['document' => $id])) { return null; }
        $now = time();
        if ((int) $resource->get('pub_date') > $now || ((int) $resource->get('unpub_date') > 0 && (int) $resource->get('unpub_date') <= $now)) { return null; }
        $parentId = (int) $resource->get('parent');
        $visited = [$id => true];
        while ($parentId > 0) {
            if (isset($visited[$parentId]) || count($visited) > 100) { return null; }
            $visited[$parentId] = true;
            $parent = $this->modx->getObject(modResource::class, ['id' => $parentId, 'context_key' => $context, 'published' => 1, 'deleted' => 0]);
            if (!$parent || !$parent->checkPolicy('view') || $this->modx->getCount(modResourceGroupResource::class, ['document' => $parentId])) { return null; }
            if ((int) $parent->get('pub_date') > $now || ((int) $parent->get('unpub_date') > 0 && (int) $parent->get('unpub_date') <= $now)) { return null; }
            $parentId = (int) $parent->get('parent');
        }
        return $resource;
    }

    public function markdown(array $item, string $context): ?string
    {
        if ($item['mode'] === 'manual') { return rtrim($item['markdown']) . "\n\nИсточник: " . $item['url'] . "\n"; }
        $resource = $this->resource($item['resource_id'], $context);
        if (!$resource) { return null; }
        $renderer = new Renderer();
        $content = (string) $resource->get('content');
        $content = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $content) ?? '';
        $content = preg_replace('~</(?:p|div|h[1-6]|li)>|<br\s*/?>~i', "\n\n", $content) ?? '';
        $paragraphs = array_filter(array_map([$renderer, 'text'], preg_split('/\n\s*\n/', $content) ?: []));
        $url = $this->modx->makeUrl($item['resource_id'], $context, '', 'full');
        return '# ' . $renderer->text((string) $resource->get('pagetitle')) . "\n\n" . $renderer->text((string) $resource->get('description')) . "\n\n" . implode("\n\n", $paragraphs) . "\n\nИсточник: " . $url . "\n";
    }

    public function links(array $items, string $context, string $baseUrl): array
    {
        $links = [];
        foreach ($items as $item) {
            if ($item['mode'] === 'resource' && !$this->resource($item['resource_id'], $context)) { continue; }
            $links[] = ['title' => $item['title'], 'url' => rtrim($baseUrl, '/') . '/llms-txt/' . $item['slug'], 'section' => $item['section'] ?: 'Материалы для AI'];
        }
        return $links;
    }
}
