<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/components/llmsforge/src/Visits.php';
use LLMSForge\Visits;
$directory = sys_get_temp_dir() . '/llms-visits-' . bin2hex(random_bytes(8));
$visits = new Visits($directory);
try {
    if (Visits::bot('Mozilla compatible; GPTBot/1.0') !== 'GPTBot' || Visits::bot('FakeGPTBot') !== null || Visits::bot('Mozilla Chrome') !== null) { throw new RuntimeException('Bot detection failed'); }
    $visits->record('web', 'GPTBot', '/llms.txt?token=secret', 'GET', 200);
    $visits->record('web', 'ClaudeBot', '/missing', 'HEAD', 404);
    $visits->record('other', 'GPTBot', '/private-context', 'GET', 200);
    $visits->record('web', 'GPTBot', '/ignored', 'POST', 200);
    $report = $visits->report('web', 7);
    if ($report['total'] !== 2 || $report['success'] !== 1 || count($report['rows']) !== 2) { throw new RuntimeException('Counters failed'); }
    if ($visits->report('web', 1, 'GPTBot')['total'] !== 1 || str_contains(json_encode($report), 'secret')) { throw new RuntimeException('Filtering/privacy failed'); }
    if (!str_starts_with(file_get_contents($directory . '/' . gmdate('Y-m-d') . '.php'), '<?php')) { throw new RuntimeException('Missing guard'); }
    echo "Visit detection, storage, counters, context isolation and privacy checks passed\n";
} finally {
    foreach (glob($directory . '/*.php') ?: [] as $file) { unlink($file); }
    if (is_dir($directory)) rmdir($directory);
}
