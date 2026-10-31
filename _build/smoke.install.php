<?php
declare(strict_types=1);

// Development-only integration check. Use on a disposable MODX 3 installation.
if (PHP_SAPI !== 'cli' || count($argv) !== 3 || $argv[1] !== '--install') {
    exit("Usage: php smoke.install.php --install /path/to/config.core.php\n");
}
require $argv[2];
require_once MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new \MODX\Revolution\modX();
$modx->initialize('mgr');
$package = $modx->getObject(\MODX\Revolution\Transport\modTransportPackage::class, ['signature' => 'llmsforge-0.6.6-alpha']);
if (!$package) {
    $package = $modx->newObject(\MODX\Revolution\Transport\modTransportPackage::class);
    $package->fromArray(['signature' => 'llmsforge-0.6.6-alpha', 'package_name' => 'llmsforge', 'version_major' => 0, 'version_minor' => 6, 'version_patch' => 6, 'release' => 'alpha', 'state' => 1, 'workspace' => 1, 'created' => date('Y-m-d H:i:s')], '', true);
    if (!$package->save()) { throw new RuntimeException('Could not register test package'); }
}
if (!$package->install()) { throw new RuntimeException('Transport install failed; inspect MODX error log'); }
foreach ([
    [\MODX\Revolution\modMenu::class, ['text' => 'LLMS Forge']],
    [\MODX\Revolution\modSnippet::class, ['name' => 'LLMSForge']],
    [\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeRouter']],
    [\MODX\Revolution\modPlugin::class, ['name' => 'LLMSForgeVisits']],
    [\MODX\Revolution\modEvent::class, ['name' => 'OnLLMSForgeResourceCheck']],
] as [$class, $criteria]) {
    if (!$modx->getObject($class, $criteria)) { throw new RuntimeException('Missing object: ' . json_encode($criteria)); }
}
echo "Transport install and object checks passed\n";
