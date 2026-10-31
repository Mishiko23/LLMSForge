<?php
// Runs after the site's context routing, only for URLs without an existing resource.
if ($modx->event->name !== 'OnPageNotFound' || $modx->context->get('key') === 'mgr' || !$modx->getOption('llmsforge.enabled', null, false)) { return; }
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = (string) $modx->getOption('base_url', null, '/');
if (!str_starts_with($path, $base)) { return; }
$relative = trim(substr($path, strlen($base)), '/');
if ($relative !== 'llms.txt' && !preg_match('~^llms-txt/([a-z0-9][a-z0-9-]{0,79})$~D', $relative, $match)) { return; }
require_once MODX_CORE_PATH . 'components/llmsforge/src/Generator.php';
try {
    $context = (string) $modx->context->get('key');
    if ($relative === 'llms.txt') {
        $text = (new \LLMSForge\Generator($modx))->generate();
    } else {
        $items = \LLMSForge\Documents::validate((string) $modx->getOption('llmsforge.documents', null, '[]'));
        $text = null;
        foreach ($items as $item) {
            if ($item['slug'] === $match[1]) { $text = (new \LLMSForge\Documents($modx))->markdown($item, $context); break; }
        }
        if ($text === null) { return; }
    }
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') { echo $text; }
    exit;
} catch (\Throwable $error) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[LLMSForge router] ' . $error->getMessage());
    http_response_code(503); header('Content-Type: text/plain; charset=utf-8'); echo 'LLMS document temporarily unavailable.'; exit;
}
