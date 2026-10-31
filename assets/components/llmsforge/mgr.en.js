(function () {
    'use strict';
    const labels = {
        'КОНТЕНТ ДЛЯ AI': 'CONTENT FOR AI', 'Контекст сайта': 'Site context', 'Проверка установки': 'Installation check', 'Проверить': 'Check',
        'Обращения AI-ботов': 'AI bot requests', 'Период': 'Period', 'Бот': 'Bot', 'Обновить журнал': 'Refresh log',
        'Время UTC': 'Time UTC', 'Бот (User-Agent)': 'Bot (User-Agent)', 'Адрес': 'Path', 'Метод / статус': 'Method / status',
        'Статистика текущих настроек': 'Current settings', 'Публикация для AI': 'AI publication', 'Доступность': 'Availability',
        'Выключена': 'Disabled', 'Опубликовать при сохранении': 'Publish on save', 'Описание сайта': 'Site description', 'Название сайта': 'Site title', 'Краткое описание': 'Short summary',
        'TV для Markdown-карточек (имена через запятую)': 'TVs for Markdown cards (comma separated)', 'Поля miniShop3 для Markdown-карточек': 'miniShop3 fields for Markdown cards',
        'Основной llms.txt': 'Main llms.txt', 'Собрать из выбранных страниц': 'Build from selected pages', 'Редактировать Markdown вручную': 'Edit Markdown manually', 'Содержимое llms.txt': 'llms.txt content',
        'Поиск по названию или ID': 'Search by title or ID', 'Выбрать все найденные': 'Select all matches', 'Выбрать опубликованные': 'Select published',
        'Снять выбор найденных': 'Deselect matches', 'Инвертировать найденные': 'Invert matches', 'Очистить весь выбор': 'Clear selection', '← Назад': '← Previous', 'Вперёд →': 'Next →', 'Ручная настройка ID': 'Edit IDs manually',
        'Включить': 'Include', 'Страница': 'Page', 'Раздел': 'Section', 'Выбранные ID в нужном порядке': 'Selected IDs in order', 'Исключить ID': 'Exclude IDs',
        'LLMS для отдельных страниц': 'LLMS for individual pages', 'Создать карточки из выбранных': 'Create cards from selected', '+ Добавить LLMS для страницы': '+ Add page LLMS', 'Сохранить настройки': 'Save settings', 'Предпросмотр': 'Preview',
        'Предпросмотр llms.txt': 'llms.txt preview', 'Скачать llms.txt': 'Download llms.txt', 'Скачать документ': 'Download document', 'Название': 'Title',
        'Раздел в основном файле': 'Section in main file', 'Источник содержимого': 'Content source', 'Написать Markdown вручную': 'Write Markdown manually',
        'Получать из ресурса MODX': 'Read MODX resource', 'Полный URL исходной страницы': 'Full source page URL', 'Markdown для AI': 'Markdown for AI',
        'Предпросмотр страницы': 'Preview page', 'Удалить': 'Remove', 'В таблице': 'Listed', 'Выбрано ID': 'Selected IDs', 'Исключено ID': 'Excluded IDs',
        'Карточек Markdown': 'Markdown cards', 'Из ресурсов / вручную': 'From resources / manual', 'Объём llms.txt': 'llms.txt size'
    };
    function translate(root) {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) {
            const node = walker.currentNode, key = node.nodeValue.trim();
            if (labels[key]) node.nodeValue = node.nodeValue.replace(key, labels[key]);
        }
    }
    const observer = new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (node.nodeType === Node.TEXT_NODE && labels[node.nodeValue.trim()]) node.nodeValue = labels[node.nodeValue.trim()];
                else if (node.nodeType === Node.ELEMENT_NODE && (node.id === 'llmsforge-app' || node.closest?.('#llmsforge-app'))) translate(node);
            }
        }
    });
    observer.observe(document.documentElement, {childList: true, subtree: true});
    const existing = document.getElementById('llmsforge-app'); if (existing) translate(existing);
}());
