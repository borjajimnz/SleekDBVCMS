<?php

namespace SleekDBVCMS\Forms\Types;

class ModulesType extends AbstractType
{
    // All fields a module instance can carry (matches the "modules" collection).
    private function moduleFields(): array
    {
        return ['title', 'type', 'subtitle', 'image', 'cta_text', 'cta_url', 'html', 'store', 'limit', 'item_id', 'fields', 'notify_to', 'notify_cc', 'button_text', 'success_message'];
    }

    // Maps each module field to the CMS form component used to edit it.
    private function fieldTypes(): array
    {
        return [
            'title' => 'text',
            'subtitle' => 'text',
            'image' => 'image',
            'cta_text' => 'text',
            'cta_url' => 'url',
            'html' => 'rich_textarea',
            'store' => 'select',
            'limit' => 'number',
            'item_id' => 'number',
            'fields' => 'textarea',
            'notify_to' => 'text',
            'notify_cc' => 'text',
            'button_text' => 'text',
            'success_message' => 'text',
        ];
    }

    public function render(string $name, $value = null, array $attributes = []): string
    {
        $disabled = !empty($attributes['disabled']);
        $allModules = $attributes['options'] ?? [];
        $storeOptions = $attributes['stores'] ?? [];
        unset($attributes['options'], $attributes['stores']);

        // Pre-render each editable field with the real CMS component.
        // name/id are stripped so inline module inputs never collide with the page form.
        $typeInstances = [
            'text' => new TextType(),
            'image' => new ImageType(),
            'url' => new UrlType(),
            'rich_textarea' => new RichTextareaType(),
            'number' => new NumberType(),
            'select' => new SelectType(),
            'textarea' => new TextareaType(),
        ];
        $templates = [];
        $fieldTypeMap = [];
        foreach ($this->fieldTypes() as $field => $ft) {
            $attrs = [];
            if ($ft === 'select') {
                $storeList = array_values($storeOptions);
                $attrs['options'] = array_combine($storeList, $storeList) ?: [];
            }
            $html = $typeInstances[$ft]->render($field, null, $attrs);
            $templates[$field] = preg_replace('/\s+(name|id)="[^"]*"/', '', $html);
            $fieldTypeMap[$field] = $ft;
        }

        // Build the pool of templates (from the "modules" collection).
        $pool = [];
        foreach ($allModules as $module) {
            $id = (int)($module['_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string)($module['title'] ?? ''));
            $fields = [];
            foreach ($this->moduleFields() as $field) {
                $fields[$field] = $module[$field] ?? '';
            }
            $pool[$id] = [
                'label' => $label !== '' ? $label : 'Module #' . $id,
                'type' => (string)($module['type'] ?? ''),
                'fields' => $fields,
            ];
        }

        // Normalize the stored value into per-page instances.
        $raw = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($raw)) {
            $raw = [];
        }
        $instances = [];
        foreach ($raw as $entry) {
            if (is_array($entry)) {
                $instances[] = $entry;
            } elseif (is_numeric($entry)) {
                $id = (int)$entry;
                if (isset($pool[$id])) {
                    $inst = $pool[$id]['fields'];
                    $inst['_module_id'] = $id;
                    $instances[] = $inst;
                }
            }
        }

        $html = '<div class="modules-builder space-y-3"'
            . ' data-modules="' . htmlspecialchars(json_encode($pool)) . '"'
            . ' data-templates="' . htmlspecialchars(json_encode($templates), ENT_QUOTES) . '"'
            . ' data-field-types="' . htmlspecialchars(json_encode($fieldTypeMap), ENT_QUOTES) . '"'
            . ' data-disabled="' . ($disabled ? '1' : '0') . '">';

        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" class="modules-hidden" value="' . htmlspecialchars(json_encode($instances)) . '">';

        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">';
        $html .= '<div class="modules-empty px-4 py-6 text-center text-xs text-gray-500 dark:text-gray-400">No modules selected.</div>';
        $html .= '<ul class="modules-list divide-y divide-gray-200 dark:divide-gray-700"></ul>';
        $html .= '</div>';

        $html .= '<div class="modules-editor rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800" style="display:none"></div>';

        if (!$disabled) {
            $html .= '<div class="modules-add flex gap-2">';
            $html .= '<select class="modules-select flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">';
            $html .= '<option value="">-- Add module --</option>';
            foreach ($pool as $id => $module) {
                $type = $module['type'] !== '' ? ' (' . htmlspecialchars($module['type']) . ')' : '';
                $html .= '<option value="' . $id . '">' . htmlspecialchars($module['label']) . $type . '</option>';
            }
            $html .= '</select>';
            $html .= '<button type="button" class="modules-add-btn px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">Add</button>';
            $html .= '</div>';
        }

        $html .= '<script>' . $this->builderScript() . '</script>';
        $html .= '<small class="block text-xs text-gray-500 dark:text-gray-400">Adding a module copies its values into this page. Use the pencil to edit its values for this page only (other pages are not affected).</small>';
        $html .= '</div>';

        return $html;
    }

    private function builderScript(): string
    {
        return <<<'JS'
(function () {
    if (window.__modulesBuilderBound) { return; }
    window.__modulesBuilderBound = true;
    document.querySelectorAll('.modules-builder').forEach(function (builder) {
        var hidden = builder.querySelector('.modules-hidden');
        var list = builder.querySelector('.modules-list');
        var empty = builder.querySelector('.modules-empty');
        var editor = builder.querySelector('.modules-editor');
        var addBtn = builder.querySelector('.modules-add-btn');
        var addSelect = builder.querySelector('.modules-select');
        var disabled = builder.getAttribute('data-disabled') === '1';

        var pool = {};
        try { pool = JSON.parse(builder.getAttribute('data-modules') || '{}'); } catch (e) {}
        var templates = {};
        try { templates = JSON.parse(builder.getAttribute('data-templates') || '{}'); } catch (e) {}
        var fieldTypes = {};
        try { fieldTypes = JSON.parse(builder.getAttribute('data-field-types') || '{}'); } catch (e) {}

        var TYPE_FIELDS = {
            hero: ['title', 'image', 'subtitle'],
            text: ['html'],
            html: ['html'],
            store_list: ['title', 'store', 'limit'],
            store_item: ['title', 'store', 'item_id'],
            lead_form: ['title', 'subtitle', 'fields', 'notify_to', 'notify_cc', 'button_text', 'success_message']
        };

        var state = [];
        try { state = JSON.parse(hidden.value || '[]'); } catch (e) {}
        if (!Array.isArray(state)) { state = []; }

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function syncHidden() { hidden.value = JSON.stringify(state); }

        function labelOf(inst) {
            if (inst.title) { return inst.title; }
            var m = pool[String(inst._module_id)];
            if (m && m.label) { return m.label; }
            return 'Module #' + (inst._module_id || '');
        }

        function rowHtml(inst, i) {
            var m = pool[String(inst._module_id)] || {};
            var type = inst.type || m.type || '';
            var badge = type ? '<span class="px-1.5 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-[10px] uppercase tracking-wide">' + esc(type) + '</span>' : '';
            var ctrl = '';
            if (!disabled) {
                ctrl = '<div class="flex items-center gap-1 ml-3">'
                    + '<button type="button" data-act="edit" data-idx="' + i + '" title="Edit values" class="module-btn p-1 rounded text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/50"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>'
                    + '<button type="button" data-act="up" data-idx="' + i + '" title="Move up" class="module-btn p-1 rounded text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg></button>'
                    + '<button type="button" data-act="down" data-idx="' + i + '" title="Move down" class="module-btn p-1 rounded text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>'
                    + '<button type="button" data-act="remove" data-idx="' + i + '" title="Remove" class="module-btn p-1 rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>'
                    + '</div>';
            }
            return '<li class="module-item flex items-center px-4 py-2.5" data-idx="' + i + '">'
                + '<span class="grip text-gray-400 mr-3 cursor-grab">⋮⋮</span>'
                + '<div class="flex-1 min-w-0"><div class="text-sm font-medium truncate">' + esc(labelOf(inst)) + '</div>'
                + '<div class="text-[10px] text-gray-400">' + (inst._module_id ? ('#' + inst._module_id) : '') + '</div></div>'
                + badge
                + ctrl
                + '</li>';
        }

        function render() {
            var html = '';
            state.forEach(function (inst, i) { html += rowHtml(inst, i); });
            list.innerHTML = html;
            if (empty) { empty.style.display = state.length ? 'none' : ''; }
            syncHidden();
        }

        function fieldHtml(f) {
            var tpl = templates[f] || '';
            if (!tpl) { return ''; }
            var type = fieldTypes[f] || 'text';
            var lbl = f === 'image' ? 'image url' : f;
            var h = '<label class="block"><span class="block text-xs mb-1 text-gray-500 dark:text-gray-400">' + esc(lbl) + '</span>';
            h += '<div data-field="' + f + '" data-type="' + type + '">' + tpl + '</div>';
            h += '</label>';
            return h;
        }

        function updateImagePreview(container, url) {
            var img = container.querySelector('img[src]');
            if (!url) {
                if (img) { img.remove(); }
                return;
            }
            if (img) { img.src = url; return; }
            var wrap = document.createElement('div');
            wrap.className = 'mt-2';
            img = document.createElement('img');
            img.src = url;
            img.loading = 'lazy';
            img.className = 'rounded-lg border border-gray-200 dark:border-gray-800 max-w-[200px]';
            wrap.appendChild(img);
            container.appendChild(wrap);
        }

        function bindValues() {
            fields.forEach(function (f) {
                var c = editor.querySelector('[data-field="' + f + '"]');
                if (!c) { return; }
                var type = c.getAttribute('data-type') || 'text';
                var el = c.querySelector('input, select, textarea');
                if (!el) { return; }
                var v = state[editing][f] == null ? '' : state[editing][f];
                if (type === 'image') {
                    el.value = String(v);
                    updateImagePreview(c, v);
                    var fileInput = c.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.onchange = function () {
                            var file = fileInput.files && fileInput.files[0];
                            if (!file) { return; }
                            var reader = new FileReader();
                            reader.onload = function () {
                                el.value = reader.result;
                                updateImagePreview(c, reader.result);
                            };
                            reader.readAsDataURL(file);
                        };
                    }
                } else {
                    el.value = v;
                }
            });
        }

        var editing = null;
        var fields = [];

        function openEditor(idx) {
            if (disabled) { return; }
            if (window.cmsRichText) { window.cmsRichText.destroy(editor); }
            editing = idx;
            var inst = state[idx];
            var m = pool[String(inst._module_id)] || {};
            var type = inst.type || m.type || 'text';
            fields = TYPE_FIELDS[type] || [];
            var h = '<div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"><span class="text-xs font-medium uppercase tracking-wide">Edit module values</span><span class="text-xs text-gray-400">' + esc(labelOf(inst)) + '</span></div>';
            h += '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4">';
            fields.forEach(function (f) { h += fieldHtml(f); });
            h += '</div>';
            h += '<div class="px-4 pb-4 flex gap-2 justify-end">'
                + '<button type="button" class="m-save px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">Save</button>'
                + '<button type="button" class="m-cancel px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">Cancel</button>'
                + '</div>';
            editor.innerHTML = h;
            bindValues();
            if (window.cmsRichText) { window.cmsRichText.init(editor); }
            editor.style.display = 'block';
            editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function closeEditor() {
            editing = null;
            if (window.cmsRichText) { window.cmsRichText.destroy(editor); }
            editor.style.display = 'none';
            editor.innerHTML = '';
        }

        function saveEditor() {
            if (editing == null) { return; }
            fields.forEach(function (f) {
                var c = editor.querySelector('[data-field="' + f + '"]');
                if (!c) { return; }
                var type = c.getAttribute('data-type') || 'text';
                var el = c.querySelector('input, select, textarea');
                if (!el) { return; }
                var v = el.value;
                if (type === 'number') {
                    v = v === '' ? '' : parseInt(v, 10);
                    if (isNaN(v)) { v = ''; }
                }
                state[editing][f] = v;
            });
            closeEditor();
            render();
        }

        function addModule() {
            var id = addSelect.value;
            if (!id) { return; }
            var t = pool[String(id)];
            if (!t) { return; }
            var inst = {};
            Object.keys(t.fields || {}).forEach(function (k) { inst[k] = t.fields[k]; });
            inst._module_id = parseInt(id, 10);
            state.push(inst);
            addSelect.value = '';
            closeEditor();
            render();
        }

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-act]');
            if (!btn) { return; }
            var act = btn.getAttribute('data-act');
            var idx = parseInt(btn.getAttribute('data-idx'), 10);
            if (act === 'edit') { openEditor(idx); return; }
            if (act === 'remove') { closeEditor(); state.splice(idx, 1); render(); return; }
            if (act === 'up' && idx > 0) { closeEditor(); var t = state[idx - 1]; state[idx - 1] = state[idx]; state[idx] = t; render(); return; }
            if (act === 'down' && idx < state.length - 1) { closeEditor(); var t2 = state[idx + 1]; state[idx + 1] = state[idx]; state[idx] = t2; render(); return; }
        });

        editor.addEventListener('click', function (e) {
            if (e.target.closest('.m-save')) { saveEditor(); }
            else if (e.target.closest('.m-cancel')) { closeEditor(); render(); }
        });

        if (addBtn && addSelect) {
            addBtn.addEventListener('click', addModule);
            addSelect.addEventListener('change', addModule);
        }

        render();
    });
})();
JS;
    }
}
