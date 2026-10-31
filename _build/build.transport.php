<?php
declare(strict_types=1);

use MODX\Revolution\modX;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\Transport\modPackageBuilder;
use xPDO\Transport\xPDOTransport;

if (PHP_SAPI !== 'cli') { exit('CLI only'); }
$config = $argv[1] ?? '';
if (!is_file($config)) { exit("Usage: php build.transport.php /path/to/modx/config.core.php\n"); }
require $config;
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new modX();
$modx->initialize('mgr');
if ((int) ($modx->getVersionData()['version'] ?? 0) !== 3) { throw new RuntimeException('MODX 3 required'); }
$builder = new modPackageBuilder($modx);
$builder->createPackage('llmsforge', '0.6.0', 'alpha');
$builder->registerNamespace('llmsforge', false, true, '{core_path}components/llmsforge/');
$checkEvent = $modx->newObject(\MODX\Revolution\modEvent::class);
$checkEvent->fromArray(['name' => 'OnLLMSForgeResourceCheck', 'service' => 1, 'groupname' => 'LLMS Forge'], '', true);
$builder->putVehicle($builder->createVehicle($checkEvent, [xPDOTransport::UNIQUE_KEY => 'name', xPDOTransport::PRESERVE_KEYS => false, xPDOTransport::UPDATE_OBJECT => true]));
$source = dirname(__DIR__) . '/core/components/llmsforge';
$snippet = $modx->newObject(modSnippet::class);
$snippet->fromArray([
    'name' => 'LLMSForge',
    'description' => 'Curated llms.txt for MODX 3. Use uncached in a text/plain resource.',
    'snippet' => preg_replace('/^<\?php\s*/', '', file_get_contents($source . '/elements/snippets/llmsforge.php')),
], '', true);
$vehicle = $builder->createVehicle($snippet, [xPDOTransport::UNIQUE_KEY => 'name', xPDOTransport::PRESERVE_KEYS => false, xPDOTransport::UPDATE_OBJECT => true]);
$vehicle->resolve('file', ['source' => $source, 'target' => "return MODX_CORE_PATH . 'components/';"]);
$vehicle->resolve('file', ['source' => dirname(__DIR__) . '/assets/components/llmsforge', 'target' => "return MODX_ASSETS_PATH . 'components/';"]);
$builder->putVehicle($vehicle);
$menu = $modx->newObject(\MODX\Revolution\modMenu::class);
$menu->fromArray(['text' => 'LLMS Forge', 'parent' => 'components', 'action' => 'index', 'namespace' => 'llmsforge', 'description' => 'Генератор llms.txt', 'permissions' => 'settings'], '', true);
$builder->putVehicle($builder->createVehicle($menu, [xPDOTransport::UNIQUE_KEY => 'text', xPDOTransport::PRESERVE_KEYS => true, xPDOTransport::UPDATE_OBJECT => true]));
$plugin = $modx->newObject(\MODX\Revolution\modPlugin::class);
$plugin->fromArray(['name' => 'LLMSForgeRouter', 'plugincode' => preg_replace('/^<\?php\s*/', '', file_get_contents($source . '/elements/plugins/router.php')), 'disabled' => 0], '', true);
$event = $modx->newObject(\MODX\Revolution\modPluginEvent::class);
$event->fromArray(['event' => 'OnPageNotFound', 'priority' => 0], '', true);
$pluginEvents = [$event];
$plugin->addMany($pluginEvents, 'PluginEvents');
$builder->putVehicle($builder->createVehicle($plugin, [xPDOTransport::UNIQUE_KEY => 'name', xPDOTransport::PRESERVE_KEYS => false, xPDOTransport::UPDATE_OBJECT => true, xPDOTransport::RELATED_OBJECTS => true, xPDOTransport::RELATED_OBJECT_ATTRIBUTES => ['PluginEvents' => [xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'], xPDOTransport::PRESERVE_KEYS => true, xPDOTransport::UPDATE_OBJECT => true]]]));
require_once $source . '/src/Manager.php';
$tracking = $modx->newObject(\MODX\Revolution\modPlugin::class);
$tracking->fromArray(['name' => 'LLMSForgeVisits', 'plugincode' => preg_replace('/^<\?php\s*/', '', file_get_contents($source . '/elements/plugins/visits.php')), 'disabled' => 0], '', true);
$trackingEvent = $modx->newObject(\MODX\Revolution\modPluginEvent::class);
$trackingEvent->fromArray(['event' => 'OnHandleRequest', 'priority' => -1000], '', true);
$trackingEvents = [$trackingEvent];
$tracking->addMany($trackingEvents, 'PluginEvents');
$builder->putVehicle($builder->createVehicle($tracking, [xPDOTransport::UNIQUE_KEY => 'name', xPDOTransport::PRESERVE_KEYS => false, xPDOTransport::UPDATE_OBJECT => true, xPDOTransport::RELATED_OBJECTS => true, xPDOTransport::RELATED_OBJECT_ATTRIBUTES => ['PluginEvents' => [xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'], xPDOTransport::PRESERVE_KEYS => true, xPDOTransport::UPDATE_OBJECT => true]]]));
foreach (\LLMSForge\Manager::DEFAULTS as $name => $value) {
    $setting = $modx->newObject(modSystemSetting::class);
    $setting->fromArray(['key' => 'llmsforge.' . $name, 'value' => $value, 'xtype' => 'textfield', 'namespace' => 'llmsforge', 'area' => 'general'], '', true);
    $builder->putVehicle($builder->createVehicle($setting, [xPDOTransport::UNIQUE_KEY => 'key', xPDOTransport::PRESERVE_KEYS => true, xPDOTransport::UPDATE_OBJECT => false]));
}
$builder->setPackageAttributes([
    'license' => file_get_contents(dirname(__DIR__) . '/COPYING'),
    'readme' => file_get_contents(dirname(__DIR__) . '/README.md'),
    'changelog' => "0.6.0-alpha\n- Public URL conflict and PHP requirements checks.\n- Optional TV and miniShop3 data fields in Markdown cards.\n- HTML to Markdown conversion and custom resource access event.",
]);
if (!$builder->pack()) { throw new RuntimeException('Package build failed'); }
echo "Built llmsforge-0.6.0-alpha in MODX core/packages\n";
