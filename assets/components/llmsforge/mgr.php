<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$reply = static function (bool $success, array $data = [], string $message = ''): never {
    echo json_encode(['success' => $success, 'object' => $data, 'message' => $message], JSON_INVALID_UTF8_SUBSTITUTE); exit;
};
if (!$modx->user || !$modx->user->isAuthenticated('mgr') || !$modx->hasPermission('settings')) {
    http_response_code(403); $reply(false, [], 'Недостаточно прав.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) || !isset($_SESSION['llmsforge_csrf']) || !hash_equals($_SESSION['llmsforge_csrf'], $_POST['csrf'])) {
    http_response_code(403); $reply(false, [], 'Обновите страницу и повторите запрос.');
}
require_once MODX_CORE_PATH . 'components/llmsforge/src/Manager.php';
require_once MODX_CORE_PATH . 'components/llmsforge/src/Generator.php';
try {
    $manager = new \LLMSForge\Manager($modx);
    $action = $_POST['action'] ?? '';
    if ($action === 'contexts') {
        $items = [];
        foreach ($modx->getIterator(\MODX\Revolution\modContext::class, ['key:!=' => 'mgr']) as $context) {
            if ($context->checkPolicy('view')) { $items[] = ['key' => $context->get('key'), 'name' => $context->get('name') ?: $context->get('key')]; }
        }
        $reply(true, ['contexts' => $items]);
    }
    $key = (string) ($_POST['context'] ?? '');
    $siteContext = $manager->context($key);
    if ($action === 'diagnostics') {
        $plugin = $modx->getObject(\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeRouter']);
        $tracker = $modx->getObject(\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeVisits']);
        $visitDir = MODX_CORE_PATH . 'components/llmsforge/var/visits';
        $writablePath = $visitDir;
        while (!file_exists($writablePath) && dirname($writablePath) !== $writablePath) { $writablePath = dirname($writablePath); }
        $basePath = rtrim((string) $siteContext->getOption('base_path', MODX_BASE_PATH), '/\\') . '/';
        $fileConflict = is_file($basePath . 'llms.txt');
        $resourceConflict = $modx->getObject(\MODX\Revolution\modResource::class, ['context_key' => $key, 'uri' => 'llms.txt', 'deleted' => 0]) !== null;
        $checks = [
            'Маршрут llms.txt' => $plugin && !(bool) $plugin->get('disabled'),
            'Журнал посещений' => $tracker && !(bool) $tracker->get('disabled'),
            'Публикация в контексте' => (string) $siteContext->getOption('llmsforge.enabled', '0') === '1',
            'Адрес сайта' => filter_var($siteContext->getOption('site_url'), FILTER_VALIDATE_URL) !== false,
            'Запись журнала' => is_dir($writablePath) && is_writable($writablePath),
            'Адрес llms.txt свободен' => !$fileConflict && !$resourceConflict,
            'Расширение DOM' => extension_loaded('dom'),
            'Расширение mbstring' => extension_loaded('mbstring'),
            'PHP 8.1+' => PHP_VERSION_ID >= 80100,
        ];
        $reply(true, ['checks' => $checks, 'url' => rtrim((string) $siteContext->getOption('site_url'), '/') . '/llms.txt']);
    }
    if ($action === 'visits') {
        require_once MODX_CORE_PATH . 'components/llmsforge/src/Visits.php';
        $reply(true, (new \LLMSForge\Visits(MODX_CORE_PATH . 'components/llmsforge/var/visits'))->report($key, (int) ($_POST['days'] ?? 7), substr((string) ($_POST['bot'] ?? ''), 0, 100)));
    }
    if ($action === 'load') { $reply(true, $manager->load($key)); }
    if (!in_array($action, ['save', 'preview', 'preview_document'], true)) { throw new \InvalidArgumentException('Неизвестное действие.'); }
    $values = \LLMSForge\Manager::validate($_POST);
    $documents = \LLMSForge\Documents::validate($values['documents']);
    foreach ($documents as $item) {
        if ($item['mode'] === 'manual' && strtolower((string) parse_url($item['url'], PHP_URL_HOST)) !== strtolower((string) parse_url($siteContext->getOption('site_url'), PHP_URL_HOST))) { throw new \InvalidArgumentException('URL карточки должен относиться к выбранному сайту.'); }
    }
    if ($action === 'preview_document') {
        foreach ($documents as $item) {
            if ($item['slug'] === ($_POST['slug'] ?? '')) {
                $text = (new \LLMSForge\Documents($modx))->markdown($item, $key);
                if ($text === null) { throw new \InvalidArgumentException('Ресурс недоступен или не опубликован.'); }
                $reply(true, ['text' => $text]);
            }
        }
        throw new \InvalidArgumentException('Карточка не найдена.');
    }
    if ($action === 'save') { $manager->save($key, $values); $reply(true); }
    $reply(true, ['text' => (new \LLMSForge\Generator($modx))->generate($key, $values)]);
} catch (\InvalidArgumentException $error) { http_response_code(400); $reply(false, [], $error->getMessage()); }
catch (\Throwable $error) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[LLMSForge manager] ' . $error->getMessage());
    http_response_code(500); $reply(false, [], 'Ошибка обработки запроса. Подробности в журнале MODX.');
}
