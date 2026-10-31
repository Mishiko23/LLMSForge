<?php
declare(strict_types=1);
require dirname(__DIR__) . '/core/components/llmsforge/src/Manager.php';
use LLMSForge\Manager;
$valid = ['title' => ' Example ', 'description' => 'Summary', 'include_ids' => '3, 1,3', 'exclude_ids' => '', 'sections' => '{"3":"Docs"}'];
$result = Manager::validate($valid);
if ($result['title'] !== 'Example' || $result['include_ids'] !== '3,1') { throw new RuntimeException('Normalization failed'); }
if (count(explode(',', Manager::validate(array_replace($valid, ['include_ids' => implode(',', range(1, 5000))]))['include_ids'])) !== 5000) { throw new RuntimeException('5000 resource selection rejected'); }
foreach ([['title' => ''], ['include_ids' => '1,-2'], ['exclude_ids' => '1 OR 1=1'], ['sections' => '[]'], ['sections' => '{"3":[]}'], ['sections' => '{"invalid":"Docs"}'], ['description' => str_repeat('x', 10001)], ['include_ids' => implode(',', range(1, 5001))]] as $invalid) {
    try { Manager::validate(array_replace($valid, $invalid)); }
    catch (InvalidArgumentException $expected) { continue; }
    throw new RuntimeException('Invalid settings accepted: ' . json_encode(array_keys($invalid)));
}
echo "Manager validation checks passed\n";
