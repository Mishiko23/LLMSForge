<?php
declare(strict_types=1);
namespace MODX\Revolution {
    class modResource { public function __construct(public array $data) {} public function get($key) { return $this->data[$key] ?? 0; } public function checkPolicy($policy) { return $this->data['view'] ?? true; } public function getTVValue($name) { return $this->data['tv'][$name] ?? ''; } }
    class modResourceGroupResource {}
    class modX {
        public array $rows = [];
        public array $groups = [];
        public function getObject($class, $criteria) { $row = $this->rows[$criteria['id']] ?? null; if (!$row) return null; foreach ($criteria as $key => $value) { if ($row->get($key) != $value) return null; } return $row; }
        public function getCount($class, $criteria) { return in_array($criteria['document'], $this->groups, true) ? 1 : 0; }
        public function makeUrl($id, ...$args) { return 'https://example.com/page/' . $id; }
        public function getContext($key) { return new class { public function getOption($key, $default = '') { return $default; } }; }
        public function invokeEvent($event, $params) { return []; }
    }
}
namespace {
    require dirname(__DIR__) . '/core/components/llmsforge/src/Documents.php';
    use LLMSForge\Documents;
    $item = ['slug' => 'guide', 'title' => 'Guide', 'url' => 'https://example.com/guide', 'mode' => 'manual', 'resource_id' => 0, 'markdown' => '# Guide', 'section' => 'Docs'];
    Documents::validate(json_encode([$item]));
    foreach ([[$item, $item], [array_replace($item, ['slug' => '../secret'])], [array_replace($item, ['url' => 'javascript:alert(1)'])], [array_replace($item, ['mode' => 'resource'])], [array_replace($item, ['markdown' => ''])]] as $invalid) {
        try { Documents::validate(json_encode($invalid)); } catch (InvalidArgumentException $expected) { continue; }
        throw new RuntimeException('Invalid document accepted');
    }
    $modx = new \MODX\Revolution\modX();
    $documents = new Documents($modx);
    if (!str_contains($documents->markdown($item, 'web'), 'Источник: https://example.com/guide')) throw new RuntimeException('Missing source');
    $row = ['id' => 2, 'context_key' => 'web', 'published' => 1, 'deleted' => 0, 'searchable' => 1, 'pagetitle' => 'Product', 'description' => 'Summary', 'content' => '<p>Hello</p><script>secret()</script>[[!Private]]<p>World</p>'];
    $modx->rows[2] = new \MODX\Revolution\modResource($row);
    $auto = array_replace($item, ['mode' => 'resource', 'resource_id' => 2]);
    $text = $documents->markdown($auto, 'web');
    if (!str_contains($text, "Hello\n\nWorld") || str_contains($text, 'secret') || str_contains($text, 'Private')) throw new RuntimeException('Unsafe conversion');
    $modx->groups = [2];
    if ($documents->markdown($auto, 'web') !== null || $documents->links([$auto], 'web', 'https://example.com')) throw new RuntimeException('Grouped resource leaked');
    $modx->groups = [];
    $modx->rows[2]->data['unpub_date'] = time() - 1;
    if ($documents->markdown($auto, 'web') !== null) throw new RuntimeException('Expired resource leaked');
    $modx->rows[2]->data['unpub_date'] = 0;
    $modx->rows[2]->data['parent'] = 10;
    if ($documents->markdown($auto, 'web') !== null) throw new RuntimeException('Missing ancestor allowed');
    echo "Document validation, conversion and access checks passed\n";
}
