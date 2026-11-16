# Материалы для публикации LLMS Forge на modstore.pro

## Файлы и ссылки

- Пакет MODX 3: [`dist/llmsforge-1.0.0-pl.transport.zip`](../dist/llmsforge-1.0.0-pl.transport.zip) — 73 158 байт.
- Название, краткое и полное описание, история версии: [`modstore-listing.ru.md`](modstore-listing.ru.md).
- Логотип: [`assets/llmsforge-logo.png`](assets/llmsforge-logo.png).
- Иллюстрация: [`assets/llmsforge-overview.png`](assets/llmsforge-overview.png).
- Демонстрационный снимок панели: [`admin-preview.png`](admin-preview.png). Он содержит демонстрационные данные; для галереи желательно снять актуальную установленную панель MODX.
- Документация: https://docs.modx.pro/components/llmsforge/
- Репозиторий и поддержка через Issues: https://github.com/Mishiko23/LLMSForge
- Лицензия: GPL-3.0, полный текст в [`../COPYING`](../COPYING).

## Поля карточки

| Поле | Значение |
| --- | --- |
| Название | LLMS Forge — генератор llms.txt для MODX 3 |
| Цена | Бесплатно |
| Версия | 1.0.0-pl |
| Совместимость | MODX Revolution 3, PHP 8.1+ |
| Категория | AI; если такой категории нет в форме, «Каталог, Поиск, Фильтрация» |
| Лицензия | GPL-3.0 |
| Автор | Mishiko23 |

## Перед отправкой на модерацию

1. Загрузить пакет и изображения, вставить текст из `modstore-listing.ru.md`, указать ссылку на документацию.
2. Сверить имя правообладателя с профилем modstore.pro и проверить отображение карточки.
3. Проверить работу на установленном MODX 3: открытие панели и публичного `/llms.txt`.
4. Учесть [требования Modstore к разработчикам](https://modstore.pro/info/developers): площадка отдельно указывает использование ExtJS для части админки. Текущая панель LLMS Forge подключается через контроллер MODX и `Ext.onReady`, но основная форма написана на обычном JavaScript. Этот пункт может потребовать согласования с модерацией.

Форма загрузки и отправка на модерацию выполняются из аккаунта издателя на modstore.pro.
