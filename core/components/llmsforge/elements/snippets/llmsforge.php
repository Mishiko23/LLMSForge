<?php
/** Use uncached in a text/plain resource with an empty template. */
require_once MODX_CORE_PATH . 'components/llmsforge/src/Generator.php';
try {
    return (new \LLMSForge\Generator($modx))->generate();
} catch (\Throwable $error) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[LLMSForge] ' . $error->getMessage());
    http_response_code(503);
    return '';
}
