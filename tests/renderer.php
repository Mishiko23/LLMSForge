<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/components/llmsforge/src/Renderer.php';
$renderer = new \LLMSForge\Renderer();
$actual = $renderer->render('Example', '<b>Summary</b>', [
    ['title' => 'Guide', 'url' => 'https://example.com/guide', 'description' => 'Start here', 'section' => 'Docs'],
    ['title' => 'Duplicate', 'url' => 'https://example.com/guide'],
    ['title' => 'Unsafe', 'url' => 'javascript:alert(1)'],
]);
$expected = "# Example\n\n> Summary\n\n## Docs\n\n- [Guide](https://example.com/guide): Start here\n";
if ($actual !== $expected) { throw new RuntimeException('Unexpected document: ' . $actual); }
if ($renderer->text('Hello [[!Secret]] <b>world</b>') !== 'Hello world') { throw new RuntimeException('Content cleanup failed'); }
$escaped = $renderer->render('Site', '', [['title' => '[Injected]', 'url' => 'https://example.com/a(b)']]);
if (!str_contains($escaped, '\\[Injected\\]') || !str_contains($escaped, 'a%28b%29')) { throw new RuntimeException('Escaping failed'); }
try { $renderer->render('', '', []); throw new RuntimeException('Empty title accepted'); }
catch (InvalidArgumentException $expectedError) {}
echo "Renderer checks passed\n";
