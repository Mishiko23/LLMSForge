<?php
declare(strict_types=1);

// Runs against a MODX 3 database. All temporary records are rolled back.
if (PHP_SAPI !== 'cli' || count($argv) !== 2 || !is_file($argv[1])) {
    exit("Usage: php smoke.runtime.php /path/to/config.core.php\n");
}
require $argv[1];
require_once MODX_CORE_PATH . 'vendor/autoload.php';
require_once MODX_CORE_PATH . 'components/llmsforge/src/Generator.php';

$modx = new \MODX\Revolution\modX();
$modx->initialize('web');
$check = static function (bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
};

$documents = new \LLMSForge\Documents($modx);
$product = $modx->getObject(\MODX\Revolution\modResource::class, 13);
if ($product && class_exists(\MiniShop3\Model\msProductData::class)) {
    $price = $modx->getObject(\MiniShop3\Model\msProductData::class, 13);
    if ($price && trim((string) $price->get('price')) !== '') {
        $text = $documents->markdown(['mode' => 'resource', 'resource_id' => 13], (string) $product->get('context_key'));
        $check($text !== null && str_contains($text, '- price: ' . $price->get('price')), 'miniShop3 price missing from card');
    }
}

$check((bool) $modx->beginTransaction(), 'Could not start test transaction');
try {
    $resource = $modx->newObject(\MODX\Revolution\modResource::class);
    $resource->fromArray(['pagetitle' => 'LLMS Forge release test', 'context_key' => 'web', 'published' => 1, 'searchable' => 1, 'deleted' => 0, 'parent' => 0, 'template' => 0, 'description' => ''], '', true);
    $check($resource->save(), 'Could not create temporary resource');
    $id = (int) $resource->get('id');
    $check($documents->resource($id, 'web') !== null, 'Published resource excluded');
    $check($documents->resource($id, 'mgr') === null, 'Cross-context resource exposed');

    $resource->set('published', 0);
    $check($resource->save() && $documents->resource($id, 'web') === null, 'Unpublished resource exposed');
    $resource->set('published', 1);
    $resource->set('pub_date', time() + 86400);
    $check($resource->save() && $documents->resource($id, 'web') === null, 'Future resource exposed');
    $resource->set('pub_date', 0);
    $resource->set('unpub_date', time() - 1);
    $check($resource->save() && $documents->resource($id, 'web') === null, 'Expired resource exposed');
    $resource->set('unpub_date', 0);
    $check($resource->save(), 'Could not reset temporary resource');

    $group = $modx->newObject(\MODX\Revolution\modResourceGroup::class);
    $group->set('name', 'LLMS Forge release test ' . $id);
    $check($group->save(), 'Could not create temporary group');
    $membership = $modx->newObject(\MODX\Revolution\modResourceGroupResource::class);
    $membership->fromArray(['document' => $id, 'document_group' => (int) $group->get('id')], '', true);
    $check($membership->save() && $documents->resource($id, 'web') === null, 'Grouped resource exposed');

    $parent = $modx->newObject(\MODX\Revolution\modResource::class);
    $parent->fromArray(['pagetitle' => 'LLMS Forge hidden parent', 'context_key' => 'web', 'published' => 0, 'searchable' => 1, 'deleted' => 0, 'parent' => 0, 'template' => 0], '', true);
    $check($parent->save(), 'Could not create temporary parent');
    $child = $modx->newObject(\MODX\Revolution\modResource::class);
    $child->fromArray(['pagetitle' => 'LLMS Forge child', 'context_key' => 'web', 'published' => 1, 'searchable' => 1, 'deleted' => 0, 'parent' => (int) $parent->get('id'), 'template' => 0], '', true);
    $check($child->save() && $documents->resource((int) $child->get('id'), 'web') === null, 'Hidden ancestor exposed');

    $contextTable = $modx->getTableName(\MODX\Revolution\modContext::class);
    $check($modx->exec("INSERT INTO {$contextTable} (`key`, `name`) VALUES ('lf_release_test', 'LLMS Forge release test')") === 1, 'Could not create temporary context');
    $secondResource = $modx->newObject(\MODX\Revolution\modResource::class);
    $secondResource->fromArray(['pagetitle' => 'Second context page', 'context_key' => 'lf_release_test', 'published' => 1, 'searchable' => 1, 'deleted' => 0, 'parent' => 0, 'template' => 0], '', true);
    $check($secondResource->save(), 'Could not create second-context resource');
    $secondId = (int) $secondResource->get('id');
    $check($documents->resource($secondId, 'web') === null && $documents->resource($secondId, 'lf_release_test') !== null, 'Context boundary failed');

    $output = (new \LLMSForge\Generator($modx))->generate('web', [
        'title' => 'Release test', 'description' => '', 'include_ids' => (string) $id,
        'exclude_ids' => '', 'sections' => '{}', 'documents' => '[]', 'main_mode' => 'auto',
    ]);
    $check(str_starts_with($output, '# Release test') && !str_contains($output, 'LLMS Forge release test'), 'Grouped resource appeared in main file');
    $secondOutput = (new \LLMSForge\Generator($modx))->generate('lf_release_test', [
        'title' => 'Second context', 'description' => '', 'include_ids' => (string) $secondId,
        'exclude_ids' => '', 'sections' => '{}', 'documents' => '[]', 'main_mode' => 'auto',
    ]);
    $check(str_contains($secondOutput, 'Second context page'), 'Second context page missing');
} finally {
    $modx->rollBack();
}
echo "Runtime checks passed: price, two contexts, publication dates, group and ancestor access, empty description\n";
