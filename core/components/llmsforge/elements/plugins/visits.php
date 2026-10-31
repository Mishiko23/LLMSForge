<?php
if ($modx->event->name !== 'OnHandleRequest' || $modx->context->get('key') === 'mgr') { return; }
require_once MODX_CORE_PATH . 'components/llmsforge/src/Visits.php';
$bot = \LLMSForge\Visits::bot(substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000));
if (!$bot || !empty($GLOBALS['llmsforge_visit_registered'])) { return; }
$GLOBALS['llmsforge_visit_registered'] = true;
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
register_shutdown_function(static function () use ($modx, $bot, $uri, $method): void {
    try {
        $context = (string) $modx->context->get('key');
        if ($context === 'mgr' || ($modx->user && $modx->user->isAuthenticated('mgr'))) { return; }
        $status = http_response_code();
        (new \LLMSForge\Visits(MODX_CORE_PATH . 'components/llmsforge/var/visits'))->record($context, $bot, $uri, $method, $status ?: 200);
    } catch (\Throwable $ignored) { /* Analytics must never break public responses. */ }
});
