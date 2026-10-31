<?php
declare(strict_types=1);

use MODX\Revolution\{modX, modNamespace, modSnippet, modMenu, modSystemSetting};

require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new modX();
$modx->initialize('mgr');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
if (!$modx->user || !$modx->user->isAuthenticated('mgr') || !$modx->hasPermission('settings')) {
    http_response_code(403);
    exit('Войдите в админку MODX под пользователем с разрешением settings, затем откройте эту страницу снова на том же домене.');
}
$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$_SESSION['llmsforge_setup_csrf'] ??= bin2hex(random_bytes(32));
$message = '';
$installed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['llmsforge_setup_csrf'], $_POST['csrf'])) {
        http_response_code(403);
        exit('Сессия формы устарела. Обновите страницу.');
    }
    try {
        if ((int) ($modx->getVersionData()['version'] ?? 0) !== 3) { throw new RuntimeException('Требуется MODX 3.'); }
        if (PHP_VERSION_ID < 80100 || !extension_loaded('dom') || !extension_loaded('json')) { throw new RuntimeException('Требуется PHP 8.1+, расширения DOM и JSON.'); }
        $component = MODX_CORE_PATH . 'components/llmsforge/';
        foreach (['src/Generator.php', 'src/Renderer.php', 'src/Manager.php', 'src/Documents.php', 'controllers/index.class.php', 'elements/snippets/llmsforge.php', 'elements/plugins/router.php'] as $file) {
            if (!is_readable($component . $file)) { throw new RuntimeException('Не загружен файл core/components/llmsforge/' . $file); }
        }
        foreach (['mgr.php', 'mgr.js', 'mgr.css'] as $file) {
            if (!is_readable(__DIR__ . '/' . $file)) { throw new RuntimeException('Не загружен файл assets/components/llmsforge/' . $file); }
        }
        $source = file_get_contents($component . 'elements/snippets/llmsforge.php');
        if ($source === false) { throw new RuntimeException('Не удалось прочитать сниппет.'); }
        $existingMenu = $modx->getObject(modMenu::class, ['text' => 'LLMS Forge']);
        if ($existingMenu && $existingMenu->get('namespace') !== 'llmsforge') { throw new RuntimeException('Имя меню LLMS Forge занято другим компонентом.'); }
        if (!$modx->beginTransaction()) { throw new RuntimeException('Не удалось начать установку.'); }
        try {
            $save = static function ($object, array $data): void {
                $object->fromArray($data, '', true);
                if (!$object->save()) { throw new RuntimeException('Ошибка записи настроек модуля.'); }
            };
            $namespace = $modx->getObject(modNamespace::class, ['name' => 'llmsforge']) ?? $modx->newObject(modNamespace::class);
            $save($namespace, ['name' => 'llmsforge', 'path' => '{core_path}components/llmsforge/', 'assets_path' => '{assets_path}components/llmsforge/']);
            $snippet = $modx->getObject(modSnippet::class, ['name' => 'LLMSForge']) ?? $modx->newObject(modSnippet::class);
            $save($snippet, ['name' => 'LLMSForge', 'description' => 'LLMS Forge: генератор llms.txt для MODX 3', 'snippet' => preg_replace('/^<\?php\s*/', '', $source)]);
            $menu = $existingMenu ?? $modx->newObject(modMenu::class);
            $save($menu, ['text' => 'LLMS Forge', 'parent' => 'components', 'action' => 'index', 'namespace' => 'llmsforge', 'description' => 'Генератор llms.txt', 'permissions' => 'settings']);
            $plugin = $modx->getObject(\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeRouter']) ?? $modx->newObject(\MODX\Revolution\modPlugin::class);
            $save($plugin, ['name' => 'LLMSForgeRouter', 'plugincode' => preg_replace('/^<\?php\s*/', '', file_get_contents($component . 'elements/plugins/router.php')), 'disabled' => 0]);
            $eventKey = ['pluginid' => $plugin->get('id'), 'event' => 'OnPageNotFound'];
            $event = $modx->getObject(\MODX\Revolution\modPluginEvent::class, $eventKey) ?? $modx->newObject(\MODX\Revolution\modPluginEvent::class);
            $save($event, $eventKey + ['priority' => 0]);
            $checkEvent = $modx->getObject(\MODX\Revolution\modEvent::class, ['name' => 'OnLLMSForgeResourceCheck']) ?? $modx->newObject(\MODX\Revolution\modEvent::class);
            $save($checkEvent, ['name' => 'OnLLMSForgeResourceCheck', 'service' => 1, 'groupname' => 'LLMS Forge']);
            $trackingSource = file_get_contents($component . 'elements/plugins/visits.php');
            if ($trackingSource === false || !is_file($component . 'src/Visits.php')) { throw new RuntimeException('Загрузите файлы статистики Visits.php и visits.php.'); }
            $tracking = $modx->getObject(\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeVisits']) ?? $modx->newObject(\MODX\Revolution\modPlugin::class);
            $save($tracking, ['name' => 'LLMSForgeVisits', 'plugincode' => preg_replace('/^<\?php\s*/', '', $trackingSource), 'disabled' => 0]);
            $trackingKey = ['pluginid' => $tracking->get('id'), 'event' => 'OnHandleRequest'];
            $save($modx->getObject(\MODX\Revolution\modPluginEvent::class, $trackingKey) ?? $modx->newObject(\MODX\Revolution\modPluginEvent::class), $trackingKey + ['priority' => -1000]);
            require_once $component . 'src/Manager.php';
            foreach (\LLMSForge\Manager::DEFAULTS as $name => $value) {
                $key = 'llmsforge.' . $name;
                if ($modx->getObject(modSystemSetting::class, ['key' => $key])) { continue; }
                $save($modx->newObject(modSystemSetting::class), ['key' => $key, 'value' => $value, 'xtype' => 'textfield', 'namespace' => 'llmsforge', 'area' => 'general']);
            }
            if (!$modx->commit()) { throw new RuntimeException('Не удалось завершить установку.'); }
        } catch (Throwable $error) { $modx->rollBack(); throw $error; }
        $installed = true;
        $message = 'Модуль зарегистрирован. Обновите страницу админки: Компоненты → LLMS Forge. Существующие настройки сохранены.';
        if (!$modx->cacheManager->refresh()) { $message .= ' Очистите кеш MODX вручную.'; }
    } catch (Throwable $error) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[LLMSForge setup] ' . $error->getMessage());
        $message = 'Установка не завершена: ' . $error->getMessage();
    }
}
$managerUrl = (string) $modx->getOption('manager_url', null, MODX_MANAGER_URL);
?>
<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Установка LLMS Forge</title>
<style>body{font:16px/1.6 system-ui;background:#f2f5f7;color:#243347;margin:0;padding:40px 20px}main{max-width:700px;margin:auto;background:white;padding:32px;border:1px solid #dfe5eb;border-radius:12px}h1{margin-top:0}button,a{font:inherit}button{background:#236c5d;color:white;border:0;border-radius:6px;padding:12px 20px;cursor:pointer}.message{padding:16px;background:#edf3f1;border-radius:6px}code{overflow-wrap:anywhere}</style>
<main><h1>Установка LLMS Forge</h1><p>Зарегистрирует меню, сниппет и настройки в MODX 3. Повторный запуск обновит код сниппета и меню, сохранив настройки сайта.</p>
<?php if ($message !== ''): ?><p class="message" role="status"><?= $escape($message) ?></p><?php endif; ?>
<?php if (!$installed): ?><form method="post"><input type="hidden" name="csrf" value="<?= $escape($_SESSION['llmsforge_setup_csrf']) ?>"><button type="submit">Установить / обновить модуль</button></form><?php endif; ?>
<p><a href="<?= $escape($managerUrl . '?a=index&namespace=llmsforge') ?>">Открыть LLMS Forge в админке</a></p>
<p>После успешной установки удалите <code>assets/components/llmsforge/setup.php</code> с сервера.</p></main></html>
