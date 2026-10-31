(function () {
    'use strict';
    function mount() {
        const root = document.getElementById('llmsforge-app');
        const cfg = window.LLMSForgeConfig;
        root.innerHTML = `<header class="lf-header"><div><span class="lf-kicker">КОНТЕНТ ДЛЯ AI</span><h1>LLMS Forge</h1><p>Выберите полезные страницы сайта и соберите llms.txt.</p></div><label>Контекст сайта<select id="lf-context" aria-label="Контекст сайта"></select></label></header>
        <div id="lf-status" role="status" aria-live="polite"></div>
        <section class="lf-card"><h2>Проверка установки</h2><div id="lf-diagnostics" class="lf-diagnostics" aria-live="polite">Нажмите «Проверить» после обновления модуля.</div><button id="lf-check" type="button">Проверить</button></section>
        <section class="lf-card"><h2>Обращения AI-ботов</h2><p class="lf-muted">Идентификация по User-Agent, без проверки IP. Это запросы, а не подтверждение прочтения или цитирования. Только запросы, дошедшие до MODX; время UTC. Хранение — 30 дней, до 5 МБ в сутки.</p><div class="lf-actions"><label>Период<select id="lf-visit-days"><option value="1">Сегодня (UTC)</option><option value="7" selected>7 дней</option><option value="30">30 дней</option></select></label><label>Бот<input id="lf-visit-bot" placeholder="Все или точное имя, например GPTBot"></label><button id="lf-visit-refresh" type="button">Обновить журнал</button></div><p id="lf-visit-summary" role="status">Нажмите «Обновить журнал».</p><div class="lf-table-wrap"><table><thead><tr><th>Время UTC</th><th>Бот (User-Agent)</th><th>Адрес</th><th>Метод / статус</th></tr></thead><tbody id="lf-visit-rows"></tbody></table></div><p class="lf-muted">Показаны последние 200 запросов выбранного периода. IP и параметры URL не сохраняются.</p></section>
        <section class="lf-card"><h2>Статистика текущих настроек</h2><div id="lf-stats" class="lf-stats" aria-live="polite"></div><p class="lf-muted">Состав документа, не статистика посещений. Размер рассчитывается после предпросмотра основного файла. Выбранные ID не гарантируют включение: генератор проверяет доступность.</p></section>
        <section class="lf-card lf-publish"><div><h2>Публикация для AI</h2><p class="lf-muted">Адрес документа: <a id="lf-public-url" target="_blank" rel="noopener"></a></p></div><label>Доступность<select id="lf-enabled"><option value="0">Выключена</option><option value="1">Опубликовать при сохранении</option></select></label></section>
        <div class="lf-layout"><section class="lf-card"><h2>Описание сайта</h2>
        <label>Название сайта<input id="lf-title" maxlength="500" required></label>
        <label>Краткое описание<textarea id="lf-description" rows="3"></textarea></label>
        <label>TV для Markdown-карточек (имена через запятую)<input id="lf-tv_names" placeholder="Например: brand,material"></label>
        <label>Поля miniShop3 для Markdown-карточек<input id="lf-ms3_fields" placeholder="price,article"></label><p class="lf-muted">Доступны price, old_price, article, weight, vendor, made_in. Поля используются только при автоматическом получении текста ресурса.</p>
        <label>Основной llms.txt<select id="lf-main_mode"><option value="auto">Собрать из выбранных страниц</option><option value="manual">Редактировать Markdown вручную</option></select></label>
        <div id="lf-manual-area" hidden><label>Содержимое llms.txt<textarea id="lf-main_markdown" rows="12" placeholder="# Название сайта"></textarea></label><p class="lf-muted">Ссылки на карточки LLMS добавятся в конец автоматически.</p></div>
        <div id="lf-auto-area">
        <h2>Страницы и разделы <span id="lf-count"></span></h2>
        <p class="lf-muted">Порядок ссылок задаётся порядком ID. Неопубликованные и закрытые страницы не попадут в результат.</p>
        <label>Поиск по названию или ID<input id="lf-search" type="search" placeholder="Найти страницу"></label>
        <div class="lf-actions"><button id="lf-select-all" type="button">Выбрать все найденные</button><button id="lf-select-published" type="button">Выбрать опубликованные</button><button id="lf-select-none" type="button">Снять выбор найденных</button><button id="lf-select-invert" type="button">Инвертировать найденные</button><button id="lf-clear-all" type="button">Очистить весь выбор</button></div><p class="lf-muted">Действия учитывают поиск по всему загруженному списку. Максимум 5000 выбранных ID. Исключения сохраняются.</p>
        <div class="lf-table-wrap"><table><thead><tr><th>Включить</th><th>Страница</th><th>Раздел</th></tr></thead><tbody id="lf-rows"></tbody></table></div>
        <div class="lf-pagination"><button id="lf-page-prev" type="button">← Назад</button><span id="lf-page-info" aria-live="polite"></span><button id="lf-page-next" type="button">Вперёд →</button></div>
        <p id="lf-limit" class="lf-muted"></p>
        <details class="lf-advanced"><summary>Ручная настройка ID</summary><label>Выбранные ID в нужном порядке<input id="lf-include_ids" placeholder="1,12,15"></label><label>Исключить ID<input id="lf-exclude_ids" placeholder="Например: 8,9"></label></details>
        </div>
        <div class="lf-doc-heading"><h2>LLMS для отдельных страниц</h2><div class="lf-actions"><button id="lf-generate-documents" type="button">Создать карточки из выбранных</button><button id="lf-add-document" type="button">+ Добавить LLMS для страницы</button></div></div>
        <p class="lf-muted">Автоматические карточки создаются для выбранных опубликованных страниц. Если задан поиск, используются только найденные страницы. Повторно карточки не создаются; максимум 200. Цена и другие поля miniShop3 появятся в карточках товаров, если указаны выше.</p>
        <div id="lf-documents"></div>
        <div class="lf-actions"><button id="lf-save" class="lf-primary" type="button">Сохранить настройки</button><button id="lf-preview" type="button">Предпросмотр</button></div>
        <p class="lf-muted">После включения публикации адреса обслуживает MODX. Нужны настроенные Friendly URLs. Существующий файл или ресурс с таким адресом имеет приоритет.</p>
        </section><aside class="lf-card lf-preview"><h2>Предпросмотр llms.txt</h2><p id="lf-preview-note" class="lf-muted">Нажмите «Предпросмотр», чтобы проверить документ.</p><pre id="lf-output" tabindex="0"></pre><button id="lf-download" type="button" disabled>Скачать llms.txt</button></aside></div>`;
        const el = id => root.querySelector('#lf-' + id);
        let resources = [], sections = {}, documents = [], dirty = false, busy = false, current = '', preview = '', downloadName = 'llms.txt', pageIndex = 0;
        const pageSize = 25, expandedDocuments = new Set();
        const fields = ['title', 'description', 'include_ids', 'exclude_ids', 'main_mode', 'main_markdown', 'enabled', 'tv_names', 'ms3_fields'];
        function status(text, error = false) { el('status').textContent = text; el('status').className = error ? 'lf-error' : 'lf-notice'; }
        function changed() { dirty = true; preview = ''; mainBytes = null; stats(); el('download').disabled = true; el('preview-note').textContent = 'Настройки изменены. Обновите предпросмотр.'; status('Есть несохранённые изменения.'); }
        let mainBytes = null;
        function stats() {
            const items = [['В таблице', resources.length], ['Выбрано ID', new Set(selected()).size], ['Исключено ID', new Set(el('exclude_ids').value.split(',').map(s => s.trim()).filter(Boolean)).size], ['Карточек Markdown', documents.length], ['Из ресурсов / вручную', documents.filter(d => d.mode === 'resource').length + ' / ' + documents.filter(d => d.mode === 'manual').length], ['Объём llms.txt', mainBytes === null ? '—' : mainBytes + ' байт']];
            el('stats').replaceChildren();
            items.forEach(([label, value]) => { const box = document.createElement('div'), number = document.createElement('strong'), caption = document.createElement('span'); number.textContent = String(value); caption.textContent = label; box.append(number, caption); el('stats').append(box); });
        }
        function filteredResources() { const query = el('search').value.toLocaleLowerCase(); return resources.filter(r => (r.id + ' ' + r.title).toLocaleLowerCase().includes(query)); }
        function bulk(mode) {
            const ids = new Set(selected());
            if (mode === 'clear') ids.clear();
            else filteredResources().forEach(resource => { const id = String(resource.id); if (mode === 'none' || (mode === 'invert' && ids.has(id))) ids.delete(id); else if (mode !== 'published' || resource.published) ids.add(id); });
            if (ids.size > 5000) { status('Выбор превышает 5000 страниц. Уточните поиск или сократите текущий список. Изменения не применены.', true); return; }
            el('include_ids').value = Array.from(ids).join(','); changed(); renderRows();
        }
        function setBusy(value) { busy = value; root.setAttribute('aria-busy', String(value)); root.querySelectorAll('input,textarea,select,button').forEach(node => { node.disabled = value; }); el('download').disabled = value || !preview; if (!value) renderRows(); }
        async function request(action, data = {}) {
            const body = new URLSearchParams({action, context: current, csrf: cfg.csrf, ...data});
            if (window.MODx && MODx.siteId) body.set('HTTP_MODAUTH', MODx.siteId);
            const response = await fetch(cfg.url, {method: 'POST', credentials: 'same-origin', body});
            let result;
            try { result = await response.json(); } catch (_) { throw new Error('Сервер вернул неожиданный ответ. Проверьте сессию MODX.'); }
            if (!response.ok || !result.success) throw new Error(result.message || 'Не удалось выполнить запрос.');
            return result.object;
        }
        function values() { const data = {}; fields.forEach(key => { data[key] = el(key).value; }); data.sections = JSON.stringify(sections); data.documents = JSON.stringify(documents); return data; }
        function showMode() { el('manual-area').hidden = el('main_mode').value !== 'manual'; el('auto-area').hidden = el('main_mode').value === 'manual'; }
        function renderDocuments() {
            el('documents').replaceChildren();
            documents.forEach((item, index) => {
                const card = document.createElement('details'); card.className = 'lf-document'; card.open = expandedDocuments.has(item);
                const heading = document.createElement('summary'); heading.textContent = '#' + (index + 1) + ' · ' + (item.title || 'Без названия') + (item.mode === 'resource' ? ' · ресурс #' + item.resource_id : ' · вручную'); card.append(heading);
                card.addEventListener('toggle', () => { if (card.open) expandedDocuments.add(item); else expandedDocuments.delete(item); });
                function field(label, name, type = 'input', choices = null) {
                    const wrapper = document.createElement('label'); wrapper.append(document.createTextNode(label));
                    const control = document.createElement(type);
                    if (choices) choices.forEach(([value, text]) => { const option = document.createElement('option'); option.value = value; option.textContent = text; control.append(option); });
                    control.value = String(item[name]); if (type === 'textarea') control.rows = 8;
                    control.addEventListener(type === 'select' ? 'change' : 'input', () => { item[name] = name === 'resource_id' ? Number(control.value) : control.value; changed(); if (name === 'mode') renderDocuments(); });
                    wrapper.append(control); card.append(wrapper); return control;
                }
                const titleInput = field('Название', 'title'); const slugInput = field('Адрес Markdown: /llms-txt/…', 'slug'); field('Раздел в основном файле', 'section');
                field('Источник содержимого', 'mode', 'select', [['manual', 'Написать Markdown вручную'], ['resource', 'Получать из ресурса MODX']]);
                if (item.mode === 'resource') {
                    const searchLabel = document.createElement('label'); searchLabel.textContent = 'Поиск ресурса по названию или ID';
                    const search = document.createElement('input'); search.type = 'search'; search.placeholder = 'Введите название или ID'; searchLabel.append(search); card.append(searchLabel);
                    const picker = field('Ресурс: страница, товар, категория или статья', 'resource_id', 'select');
                    const manualId = field('ID ресурса (можно ввести вручную)', 'resource_id');
                    manualId.type = 'number'; manualId.min = '1';
                    const hint = document.createElement('p'); hint.className = 'lf-muted'; card.append(hint);
                    function updateChoices() {
                        const query = search.value.trim().toLocaleLowerCase();
                        const matches = resources.filter(r => (r.id + ' ' + r.title).toLocaleLowerCase().includes(query));
                        const shown = matches.slice(0, 50);
                        if (item.resource_id && !shown.some(r => r.id === item.resource_id)) {
                            const currentResource = resources.find(r => r.id === item.resource_id);
                            shown.unshift(currentResource || {id: item.resource_id, title: 'Текущий ресурс'});
                        }
                        picker.replaceChildren();
                        const placeholder = document.createElement('option'); placeholder.value = '0'; placeholder.textContent = 'Выберите ресурс'; picker.append(placeholder);
                        shown.forEach(r => { const option = document.createElement('option'); option.value = String(r.id); option.textContent = '#' + r.id + ' · ' + r.title; picker.append(option); });
                        picker.value = String(item.resource_id || 0);
                        hint.textContent = matches.length > 50 ? 'Найдено ' + matches.length + '. Показаны первые 50; уточните поиск.' : 'Найдено ' + matches.length + '.';
                    }
                    search.addEventListener('input', updateChoices);
                    picker.addEventListener('change', () => {
                        manualId.value = String(item.resource_id);
                        const resource = resources.find(r => r.id === item.resource_id);
                        if (resource && !item.title.trim()) { item.title = resource.title; titleInput.value = resource.title; }
                        if (resource && /^page-[a-z0-9]+$/.test(item.slug)) {
                            const used = new Set(documents.filter(other => other !== item).map(other => other.slug));
                            const base = 'resource-' + resource.id; let slug = base, suffix = 2;
                            while (used.has(slug)) slug = base + '-' + suffix++;
                            item.slug = slug; slugInput.value = slug;
                        }
                        updateChoices();
                    });
                    manualId.addEventListener('input', updateChoices);
                    if (card.open) updateChoices();
                    else card.addEventListener('toggle', () => { if (card.open) updateChoices(); });
                } else { field('Полный URL исходной страницы', 'url'); field('Markdown для AI', 'markdown', 'textarea'); }
                const actions = document.createElement('div'); actions.className = 'lf-actions';
                const view = document.createElement('button'); view.type = 'button'; view.textContent = 'Предпросмотр страницы';
                view.addEventListener('click', async () => { if (busy) return; setBusy(true); try { const data = await request('preview_document', {...values(), slug: item.slug}); preview = data.text; downloadName = item.slug + '.md'; el('output').textContent = preview; el('preview-note').textContent = 'Карточка: ' + item.title; status('Предпросмотр карточки обновлён.'); } catch (error) { status(error.message, true); } finally { setBusy(false); } });
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Удалить';
                remove.addEventListener('click', () => { if (!window.confirm('Удалить эту карточку? Изменение применится после сохранения.')) return; documents.splice(index, 1); expandedDocuments.delete(item); changed(); renderDocuments(); });
                actions.append(view, remove); card.append(actions); el('documents').append(card);
            });
            if (!documents.length) { const empty = document.createElement('p'); empty.className = 'lf-empty'; empty.textContent = 'Карточек пока нет. Добавьте страницу, чтобы дать AI подробное описание.'; el('documents').append(empty); }
        }
        function selected() { return el('include_ids').value.split(',').map(s => s.trim()).filter(Boolean); }
        function renderRows() {
            const ids = new Set(selected());
            el('count').textContent = '(' + ids.size + ')';
            const matches = filteredResources();
            const pages = Math.max(1, Math.ceil(matches.length / pageSize));
            pageIndex = Math.min(pageIndex, pages - 1);
            el('page-info').textContent = matches.length ? `${pageIndex * pageSize + 1}–${Math.min((pageIndex + 1) * pageSize, matches.length)} из ${matches.length}` : 'Страницы не найдены';
            el('page-prev').disabled = pageIndex === 0;
            el('page-next').disabled = pageIndex >= pages - 1;
            const fragment = document.createDocumentFragment();
            matches.slice(pageIndex * pageSize, (pageIndex + 1) * pageSize).forEach(resource => {
                const row = document.createElement('tr'), checkCell = document.createElement('td'), titleCell = document.createElement('td'), sectionCell = document.createElement('td');
                const check = document.createElement('input'); check.type = 'checkbox'; check.checked = ids.has(String(resource.id)); check.setAttribute('aria-label', 'Включить ' + resource.title);
                check.addEventListener('change', () => { const next = selected().filter(id => id !== String(resource.id)); if (check.checked) next.push(String(resource.id)); if (new Set(next).size > 5000) { check.checked = false; status('Можно выбрать не более 5000 страниц.', true); return; } el('include_ids').value = next.join(','); changed(); el('count').textContent = '(' + new Set(next).size + ')'; });
                checkCell.append(check); titleCell.textContent = '#' + resource.id + ' · ' + resource.title + (resource.published ? '' : ' — не опубликована');
                const section = document.createElement('input'); section.value = sections[resource.id] || ''; section.placeholder = 'Pages'; section.setAttribute('aria-label', 'Раздел для ' + resource.title);
                section.addEventListener('input', () => { if (section.value.trim()) sections[resource.id] = section.value; else delete sections[resource.id]; changed(); });
                sectionCell.append(section); row.append(checkCell, titleCell, sectionCell); fragment.append(row);
            });
            el('rows').replaceChildren(fragment);
            if (!el('rows').children.length) { const row = document.createElement('tr'), cell = document.createElement('td'); cell.colSpan = 3; cell.textContent = 'Страницы не найдены.'; row.append(cell); el('rows').append(row); }
        }
        async function load(key) {
            const previous = current; current = key; setBusy(true);
            try {
                const data = await request('load');
                el('visit-rows').replaceChildren(); el('visit-summary').textContent = 'Нажмите «Обновить журнал» для контекста «' + current + '».';
                fields.forEach(name => { el(name).value = data.settings[name] || ''; });
                sections = JSON.parse(data.settings.sections || '{}'); if (!sections || Array.isArray(sections)) sections = {};
                documents = JSON.parse(data.settings.documents || '[]'); expandedDocuments.clear(); pageIndex = 0;
                el('main_mode').value = data.settings.main_mode || 'auto'; el('enabled').value = data.settings.enabled || '0';
                el('public-url').textContent = data.public_url;
                if (/^https?:\/\//i.test(data.public_url)) el('public-url').href = data.public_url;
                resources = data.resources; dirty = false; preview = ''; el('output').textContent = ''; el('preview-note').textContent = 'Нажмите «Предпросмотр», чтобы проверить документ.';
                el('limit').textContent = data.truncated ? 'Показаны первые 10000 страниц. Остальные можно добавить по ID в ручной настройке.' : '';
                mainBytes = null; renderRows(); renderDocuments(); showMode(); stats(); status('Настройки контекста «' + current + '» загружены.');
            } catch (error) { current = previous; el('context').value = previous; status(error.message, true); }
            finally { setBusy(false); }
        }
        fields.forEach(name => el(name).addEventListener('input', () => { changed(); if (name === 'include_ids') renderRows(); if (name === 'main_mode') showMode(); }));
        el('add-document').addEventListener('click', () => { const item = {slug: 'page-' + Date.now().toString(36), title: '', url: '', mode: 'manual', resource_id: 0, markdown: '', section: 'Материалы для AI'}; documents.push(item); expandedDocuments.add(item); changed(); renderDocuments(); el('documents').lastElementChild.scrollIntoView({block: 'center', behavior: 'smooth'}); });
        el('generate-documents').addEventListener('click', () => {
            const selectedIds = new Set(selected());
            const excludedIds = new Set(el('exclude_ids').value.split(',').map(id => id.trim()));
            const visibleIds = new Set(filteredResources().map(resource => resource.id));
            const existingIds = new Set(documents.filter(item => item.mode === 'resource').map(item => item.resource_id));
            const usedSlugs = new Set(documents.map(item => item.slug));
            const candidates = resources.filter(resource => selectedIds.has(String(resource.id)) && visibleIds.has(resource.id) && resource.published && !excludedIds.has(String(resource.id)) && !existingIds.has(resource.id));
            if (!candidates.length) { status('Нет новых выбранных опубликованных страниц. Проверьте поиск, исключения и уже созданные карточки.', true); return; }
            if (documents.length + candidates.length > 200) { status('Можно хранить не более 200 карточек. Уточните поиск или сократите выбор.', true); return; }
            const additions = candidates.map(resource => {
                const base = 'resource-' + resource.id;
                let slug = base, suffix = 2;
                while (usedSlugs.has(slug)) slug = base + '-' + suffix++;
                usedSlugs.add(slug);
                return {slug, title: resource.title, url: '', mode: 'resource', resource_id: resource.id, markdown: '', section: sections[resource.id] || 'Материалы для AI'};
            });
            if (new TextEncoder().encode(JSON.stringify([...documents, ...additions])).length > 60000) { status('Общий объём карточек превышает 60 КБ. Уточните поиск или сократите выбор.', true); return; }
            documents.push(...additions); changed(); renderDocuments(); status('Создано карточек: ' + additions.length + '. Проверьте предпросмотр и сохраните настройки.');
        });
        el('search').addEventListener('input', () => { pageIndex = 0; renderRows(); });
        el('page-prev').addEventListener('click', () => { pageIndex--; renderRows(); });
        el('page-next').addEventListener('click', () => { pageIndex++; renderRows(); });
        el('check').addEventListener('click', async () => {
            if (busy || !current) return;
            setBusy(true);
            try { const result = await request('diagnostics'); el('diagnostics').replaceChildren(); Object.entries(result.checks).forEach(([label, ok]) => { const item = document.createElement('span'); item.className = ok ? 'lf-ok' : 'lf-fail'; item.textContent = (ok ? '✓ ' : '! ') + label; el('diagnostics').append(item); }); }
            catch (error) { el('diagnostics').textContent = error.message; }
            finally { setBusy(false); }
        });
        el('visit-refresh').addEventListener('click', async () => {
            if (busy || !current) return;
            setBusy(true);
            try {
                const data = await request('visits', {days: el('visit-days').value, bot: el('visit-bot').value.trim()});
                el('visit-summary').textContent = 'Запросов: ' + data.total + ' · Успешных (2xx): ' + data.success + ' · По ботам: ' + (Object.entries(data.bots).map(([name, count]) => name + ': ' + count).join(', ') || 'пока нет');
                el('visit-rows').replaceChildren();
                data.rows.forEach(row => { const tr = document.createElement('tr'); [row.time.replace('T', ' ').replace('Z', ''), row.bot, row.path, row.method + ' / ' + row.status].forEach(value => { const td = document.createElement('td'); td.textContent = value; td.style.overflowWrap = 'anywhere'; tr.append(td); }); el('visit-rows').append(tr); });
            } catch (error) { el('visit-summary').textContent = error.message; }
            finally { setBusy(false); }
        });
        [['select-all', 'all'], ['select-published', 'published'], ['select-none', 'none'], ['select-invert', 'invert'], ['clear-all', 'clear']].forEach(([id, mode]) => el(id).addEventListener('click', () => bulk(mode)));
        el('context').addEventListener('change', () => { if (dirty && !window.confirm('Переключить контекст и отменить несохранённые изменения?')) { el('context').value = current; return; } load(el('context').value); });
        ['save', 'preview'].forEach(action => el(action).addEventListener('click', async () => {
            if (busy) return;
            setBusy(true);
            try {
                const data = await request(action, values());
                if (action === 'save') { dirty = false; status('Настройки сохранены для контекста «' + current + '».'); }
                else { preview = data.text; mainBytes = new TextEncoder().encode(preview).length; stats(); downloadName = 'llms.txt'; el('output').textContent = preview; el('preview-note').textContent = 'Сформировано из текущих полей. Предпросмотр не сохраняет настройки.'; status('Предпросмотр обновлён.'); }
            } catch (error) { status(error.message, true); }
            finally { setBusy(false); }
        }));
        el('download').textContent = 'Скачать документ';
        el('download').addEventListener('click', () => { const url = URL.createObjectURL(new Blob([preview], {type: 'text/plain;charset=utf-8'})); const a = document.createElement('a'); a.href = url; a.download = downloadName; a.click(); setTimeout(() => URL.revokeObjectURL(url), 1000); });
        window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
        (async () => { setBusy(true); try { const data = await request('contexts'); data.contexts.forEach(context => { const option = document.createElement('option'); option.value = context.key; option.textContent = context.name + ' (' + context.key + ')'; el('context').append(option); }); if (!data.contexts.length) throw new Error('Нет доступных контекстов сайта.'); const key = data.contexts.some(c => c.key === 'web') ? 'web' : data.contexts[0].key; el('context').value = key; await load(key); } catch (error) { status(error.message, true); } finally { setBusy(false); } })();
    }
    Ext.onReady(function () {
        MODx.add({xtype: 'panel', renderTo: 'modx-panel-holder', border: false, html: '<div id="llmsforge-app"></div>', listeners: {afterrender: mount}});
    });
}());
