<?php
declare(strict_types=1);
namespace LLMSForge;

use MODX\Revolution\{modX, modContext, modContextSetting, modResource};
require_once __DIR__ . '/Documents.php';

final class Manager
{
    public const DEFAULTS = ['title' => '', 'description' => '', 'include_ids' => '', 'exclude_ids' => '', 'sections' => '{}', 'main_mode' => 'auto', 'main_markdown' => '', 'documents' => '[]', 'enabled' => '0'];
    public function __construct(private modX $modx) {}

    public static function validate(array $input): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $input) && in_array($key, ['main_mode', 'main_markdown', 'documents', 'enabled'], true)) { $input[$key] = $default; }
            if (!is_string($input[$key] ?? null)) { throw new \InvalidArgumentException('Некорректное поле: ' . $key); }
            $values[$key] = trim($input[$key]);
        }
        if ($values['title'] === '' || strlen($values['title']) > 500) { throw new \InvalidArgumentException('Укажите название сайта (до 500 байт).'); }
        if (strlen($values['description']) > 10000) { throw new \InvalidArgumentException('Описание слишком длинное.'); }
        foreach (['include_ids', 'exclude_ids'] as $key) {
            if (!preg_match('/^(?:[1-9]\d*(?:\s*,\s*[1-9]\d*)*)?$/D', $values[$key])) { throw new \InvalidArgumentException('ID должны быть положительными числами через запятую.'); }
            $ids = $values[$key] === '' ? [] : array_unique(array_map('intval', explode(',', $values[$key])));
            if (count($ids) > 1000) { throw new \InvalidArgumentException('Не более 1000 ресурсов.'); }
            $values[$key] = implode(',', $ids);
        }
        $sections = json_decode($values['sections']);
        if (!$sections instanceof \stdClass || strlen($values['sections']) > 60000) { throw new \InvalidArgumentException('Разделы должны быть объектом JSON объёмом до 60 КБ.'); }
        foreach ($sections as $id => $label) {
            if (!ctype_digit((string) $id) || (int) $id < 1 || !is_string($label) || strlen($label) > 300) { throw new \InvalidArgumentException('Некорректное название раздела.'); }
        }
        $values['sections'] = json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (!in_array($values['main_mode'], ['auto', 'manual'], true) || !in_array($values['enabled'], ['0', '1'], true)) { throw new \InvalidArgumentException('Некорректный режим генерации.'); }
        if (strlen($values['main_markdown']) > 60000 || ($values['main_mode'] === 'manual' && !preg_match('/^#\s+\S/m', $values['main_markdown']))) { throw new \InvalidArgumentException('Основной Markdown должен содержать заголовок # и быть не больше 60 КБ.'); }
        Documents::validate($values['documents']);
        return $values;
    }

    public function context(string $key): modContext
    {
        $context = $this->modx->getContext($key);
        if (!$context || $key === 'mgr' || !$context->checkPolicy('view')) { throw new \InvalidArgumentException('Контекст недоступен.'); }
        if (!$context->prepare()) { throw new \RuntimeException('Не удалось загрузить настройки контекста.'); }
        return $context;
    }

    public function load(string $key): array
    {
        $context = $this->context($key);
        $settings = [];
        foreach (self::DEFAULTS as $name => $default) { $settings[$name] = (string) $context->getOption('llmsforge.' . $name, $default); }
        $rows = [];
        $query = $this->modx->newQuery(modResource::class, ['context_key' => $key, 'deleted' => 0]);
        $query->sortby('id', 'ASC');
        $query->limit(2001);
        foreach ($this->modx->getIterator(modResource::class, $query) as $resource) {
            if (!$resource->checkPolicy('view')) { continue; }
            $rows[] = ['id' => (int) $resource->get('id'), 'title' => (string) $resource->get('pagetitle'), 'published' => (bool) $resource->get('published')];
        }
        return ['settings' => $settings, 'resources' => array_slice($rows, 0, 2000), 'truncated' => count($rows) > 2000, 'public_url' => rtrim((string) $context->getOption('site_url'), '/') . '/llms.txt'];
    }

    public function save(string $key, array $values): void
    {
        $this->context($key);
        if (!$this->modx->beginTransaction()) { throw new \RuntimeException('Не удалось начать сохранение.'); }
        try {
            foreach ($values as $name => $value) {
                $criteria = ['context_key' => $key, 'key' => 'llmsforge.' . $name];
                $setting = $this->modx->getObject(modContextSetting::class, $criteria) ?? $this->modx->newObject(modContextSetting::class);
                $setting->fromArray($criteria + ['value' => $value, 'namespace' => 'llmsforge', 'area' => 'general', 'xtype' => 'textfield'], '', true);
                if (!$setting->save()) { throw new \RuntimeException('Не удалось сохранить настройки.'); }
            }
            if (!$this->modx->commit()) { throw new \RuntimeException('Не удалось завершить сохранение.'); }
        } catch (\Throwable $error) { $this->modx->rollBack(); throw $error; }
        $this->modx->cacheManager->refresh(['context_settings' => ['contexts' => [$key]]]);
    }
}
