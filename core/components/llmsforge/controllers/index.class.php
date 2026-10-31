<?php
declare(strict_types=1);

class LLMSForgeIndexManagerController extends \MODX\Revolution\modExtraManagerController
{
    public function getPageTitle(): string { return 'LLMS Forge'; }
    public function getLanguageTopics(): array { return ['core:default', 'llmsforge:default']; }
    public function checkPermissions(): bool { return $this->modx->hasPermission('settings'); }
    public function loadCustomCssJs(): void
    {
        $_SESSION['llmsforge_csrf'] ??= bin2hex(random_bytes(32));
        $base = $this->modx->getOption('assets_url') . 'components/llmsforge/';
        $config = ['url' => $base . 'mgr.php', 'csrf' => $_SESSION['llmsforge_csrf']];
        $this->addCss($base . 'mgr.css?v=0.6.0');
        $this->addHtml('<script>window.LLMSForgeConfig=' . json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>');
        $this->addJavascript($base . 'mgr.js?v=0.6.0');
        if (str_starts_with((string) $this->modx->getOption('cultureKey', null, 'ru'), 'en')) {
            $this->addJavascript($base . 'mgr.en.js?v=0.6.0');
        }
    }
}
