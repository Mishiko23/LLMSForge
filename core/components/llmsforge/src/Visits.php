<?php
declare(strict_types=1);
namespace LLMSForge;

final class Visits
{
    public function __construct(private string $directory) {}
    public static function bot(string $agent): ?string
    {
        foreach (['OAI-SearchBot', 'ChatGPT-User', 'GPTBot', 'Claude-SearchBot', 'Claude-User', 'ClaudeBot', 'PerplexityBot', 'Perplexity-User', 'Applebot', 'Amazonbot', 'Bytespider'] as $name) {
            if (preg_match('~(?<![a-z0-9_-])' . preg_quote($name, '~') . '(?![a-z0-9_-])~i', $agent)) { return $name; }
        }
        return null;
    }
    public function record(string $context, string $bot, string $uri, string $method, int $status): void
    {
        if (!in_array($method, ['GET', 'HEAD'], true)) { return; }
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) { return; }
        // PHP guard protects records even if the core directory is accidentally web accessible.
        $file = $this->directory . '/' . gmdate('Y-m-d') . '.php';
        $stream = @fopen($file, 'c+');
        if (!$stream) { return; }
        try {
            if (!flock($stream, LOCK_EX)) { return; }
            $size = fstat($stream)['size'];
            if ($size >= 5 * 1024 * 1024) { return; }
            fseek($stream, 0, SEEK_END);
            if ($size === 0) { fwrite($stream, "<?php http_response_code(404); exit; ?>\n"); }
            $path = (string) parse_url($uri, PHP_URL_PATH);
            $path = preg_replace('/[\x00-\x1f\x7f]/', '', $path) ?? '';
            if (preg_match('~(?:^|/)index\.php$~i', $path)) {
                parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
                $id = $query['id'] ?? null;
                if (is_string($id) && preg_match('/^[1-9][0-9]{0,9}$/D', $id)) { $path .= '?id=' . $id; }
            }
            fwrite($stream, json_encode(['time' => gmdate('Y-m-d\TH:i:s\Z'), 'context' => $context, 'bot' => $bot, 'path' => substr($path, 0, 2000), 'method' => $method, 'status' => $status], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) . "\n");
        } finally { flock($stream, LOCK_UN); fclose($stream); }
        foreach (glob($this->directory . '/*.php') ?: [] as $old) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}\.php$/D', basename($old)) && basename($old) < gmdate('Y-m-d', time() - 29 * 86400) . '.php') { @unlink($old); }
        }
    }
    public function report(string $context, int $days, string $bot = ''): array
    {
        $days = max(1, min(30, $days)); $rows = []; $bots = []; $paths = []; $total = 0; $success = 0;
        for ($day = 0; $day < $days; $day++) {
            $file = $this->directory . '/' . gmdate('Y-m-d', time() - $day * 86400) . '.php';
            $stream = @fopen($file, 'r'); if (!$stream) { continue; }
            try {
                if (!flock($stream, LOCK_SH)) { continue; }
                $dailyRows = [];
                fgets($stream);
                while (($line = fgets($stream, 12000)) !== false) {
                    $row = json_decode($line, true);
                    if (!is_array($row) || ($row['context'] ?? '') !== $context || ($bot !== '' && ($row['bot'] ?? '') !== $bot)) { continue; }
                    $total++; if ($row['status'] >= 200 && $row['status'] < 300) { $success++; }
                    $bots[$row['bot']] = ($bots[$row['bot']] ?? 0) + 1;
                    $paths[$row['path']] = ($paths[$row['path']] ?? 0) + 1;
                    $dailyRows[] = $row;
                    if (count($dailyRows) > 200) { array_shift($dailyRows); }
                }
                $rows = array_merge($rows, $dailyRows);
            } finally { flock($stream, LOCK_UN); fclose($stream); }
        }
        usort($rows, static fn($a, $b) => strcmp($b['time'], $a['time']));
        arsort($bots); arsort($paths);
        return ['total' => $total, 'success' => $success, 'bots' => $bots, 'paths' => array_slice($paths, 0, 20, true), 'rows' => array_slice($rows, 0, 200), 'days' => $days];
    }
}
