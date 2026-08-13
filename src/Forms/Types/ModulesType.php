<?php

namespace SleekDBVCMS\Forms\Types;

class ModulesType extends AbstractType
{
    // All fields a module instance can carry. Templates in the "modules"
    // collection do NOT store values for these — they only reference them via
    // a schema (see typeFields()/fieldLabels()). Values live per page in
    // pages.modules. 'type' is not editable on instances (it comes from the
    // template), so it is excluded here.
    public static function fieldNames(): array
    {
        return ['title', 'subtitle', 'image', 'cta_text', 'cta_url', 'html', 'store', 'limit', 'item_id', 'form_id', 'text', 'image_position', 'video_url', 'poster', 'features', 'stats', 'testimonials', 'faq', 'pricing', 'logos'];
    }

    // Human-readable labels for the schema picker (ModuleSchemaType).
    public static function fieldLabels(): array
    {
        return [
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'image' => 'Image',
            'cta_text' => 'Button text',
            'cta_url' => 'Button URL',
            'html' => 'HTML',
            'store' => 'Store',
            'limit' => 'Limit',
            'item_id' => 'Item ID',
            'form_id' => 'Form',
            'text' => 'Text',
            'image_position' => 'Image position',
            'video_url' => 'Video URL',
            'poster' => 'Poster',
            'features' => 'Features',
            'stats' => 'Stats',
            'testimonials' => 'Testimonials',
            'faq' => 'FAQ',
            'pricing' => 'Pricing',
            'logos' => 'Logos',
        ];
    }

    // Default schema per module type (single source of truth, mirrored to JS).
    public static function typeFields(): array
    {
        return [
            'hero' => ['title', 'image', 'subtitle', 'cta_text', 'cta_url'],
            'text' => ['html'],
            'html' => ['html'],
            'store_list' => ['title', 'store', 'limit'],
            'store_item' => ['title', 'store', 'item_id'],
            'lead_form' => ['title', 'form_id'],
            'cta' => ['title', 'subtitle', 'image', 'cta_text', 'cta_url'],
            'split' => ['title', 'text', 'image', 'image_position', 'cta_text', 'cta_url'],
            'features' => ['title', 'subtitle', 'features'],
            'stats' => ['title', 'stats'],
            'testimonials' => ['title', 'subtitle', 'testimonials'],
            'faq' => ['title', 'subtitle', 'faq'],
            'pricing' => ['title', 'subtitle', 'pricing'],
            'logos' => ['title', 'logos'],
            'video' => ['title', 'subtitle', 'video_url', 'poster'],
        ];
    }

    // Decodes a template's stored schema (JSON array of field names) into a
    // valid list of field names; falls back to the type's defaults.
    public static function decodeSchema($raw, string $type): array
    {
        $schema = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $schema = $decoded;
            } else {
                $schema = array_map('trim', explode(',', $raw));
            }
        } elseif (is_array($raw)) {
            $schema = $raw;
        }
        $valid = array_flip(self::fieldNames());
        $schema = array_values(array_filter($schema, function ($f) use ($valid) {
            return isset($valid[(string)$f]);
        }));
        if (empty($schema)) {
            $schema = self::typeFields()[$type] ?? [];
        }
        return $schema;
    }

    // Maps each module field to the CMS form component used to edit it.
    private function fieldTypes(): array
    {
        return [
            'title' => 'text',
            'subtitle' => 'text',
            'image' => 'image',
            'cta_text' => 'text',
            'cta_url' => 'link',
            'html' => 'rich_textarea',
            'store' => 'select',
            'limit' => 'number',
            'item_id' => 'number',
            'form_id' => 'select',
            'text' => 'rich_textarea',
            'image_position' => 'select',
            'video_url' => 'link',
            'poster' => 'image',
            'features' => 'repeater',
            'stats' => 'repeater',
            'testimonials' => 'repeater',
            'faq' => 'repeater',
            'pricing' => 'repeater',
            'logos' => 'repeater',
        ];
    }

    public function render(string $name, $value = null, array $attributes = []): string
    {
        $disabled = !empty($attributes['disabled']);
        $allModules = $attributes['options'] ?? [];
        $storeOptions = $attributes['stores'] ?? [];
        $formOptions = $attributes['forms'] ?? [];
        unset($attributes['options'], $attributes['stores'], $attributes['forms']);

        // Pre-render each editable field with the real CMS component.
        // name/id are stripped so inline module inputs never collide with the page form.
        $typeInstances = [
            'text' => new TextType(),
            'image' => new ImageType(),
            'url' => new UrlType(),
            'link' => new LinkType(),
            'rich_textarea' => new RichTextareaType(),
            'number' => new NumberType(),
            'select' => new SelectType(),
            'textarea' => new TextareaType(),
            'repeater' => new RepeaterType(),
        ];
        $links = $attributes['links'] ?? [];
        unset($attributes['links']);
        $templates = [];
        $fieldTypeMap = [];
        foreach ($this->fieldTypes() as $field => $ft) {
            $attrs = [];
            if ($ft === 'select') {
                if ($field === 'form_id') {
                    $formList = [];
                    foreach ($formOptions as $formId => $formTitle) {
                        $formList[$formId] = $formTitle;
                    }
                    $attrs['options'] = $formList;
                } elseif ($field === 'image_position') {
                    $attrs['options'] = [
                        'left' => 'Image left',
                        'right' => 'Image right',
                    ];
                } else {
                    $storeList = array_values($storeOptions);
                    $attrs['options'] = array_combine($storeList, $storeList) ?: [];
                }
            }
            if ($ft === 'repeater') {
                $schema = RepeaterType::schemaForField($field);
                $attrs['schema'] = LinkType::decorateSchema($schema, $links);
            }
            if ($ft === 'link') {
                $attrs['links'] = $links;
            }
            $html = $typeInstances[$ft]->render($field, null, $attrs);
            $templates[$field] = preg_replace('/\s+(name|id)="[^"]*"/', '', $html);
            $fieldTypeMap[$field] = $ft;
        }

        // Build the pool of templates (from the "modules" collection). Each
        // template only carries a schema (which fields it exposes), not values.
        $pool = [];
        foreach ($allModules as $module) {
            $id = (int)($module['_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string)($module['title'] ?? ''));
            $pool[$id] = [
                'label' => $label !== '' ? $label : 'Module #' . $id,
                'type' => (string)($module['type'] ?? ''),
                'schema' => self::decodeSchema($module['fields'] ?? null, (string)($module['type'] ?? '')),
            ];
        }

        // Normalize the stored value into per-page instances. New instances
        // start EMPTY (values are edited per page, never on the template).
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
                    $inst = ['_module_id' => $id, 'type' => $pool[$id]['type']];
                    foreach ($pool[$id]['schema'] as $field) {
                        $inst[$field] = '';
                    }
                    $instances[] = $inst;
                }
            }
        }

        $html = '<div class="modules-builder space-y-3"'
            . ' data-modules="' . htmlspecialchars(json_encode($pool)) . '"'
            . ' data-templates="' . htmlspecialchars(json_encode($templates), ENT_QUOTES) . '"'
            . ' data-field-types="' . htmlspecialchars(json_encode($fieldTypeMap), ENT_QUOTES) . '"'
            . ' data-type-fields="' . htmlspecialchars(json_encode(self::typeFields()), ENT_QUOTES) . '"'
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

        $html .= '<script>' . $this->builderScript(json_encode(self::typeFields(), JSON_UNESCAPED_UNICODE)) . '</script>';
        $html .= '<small class="block text-xs text-gray-500 dark:text-gray-400">Adding a module adds an empty instance. Use the pencil to fill its values for this page only (templates never store values; other pages are not affected).</small>';
        $html .= '</div>';

        return $html;
    }

    private function builderScript(string $typeFieldsJson): string
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

        var TYPE_FIELDS = {};
        try { TYPE_FIELDS = JSON.parse(builder.getAttribute('data-type-fields') || '{}'); } catch (e) {}

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
                } else if (type === 'link') {
                    el.value = String(v);
                    var linkSel = c.querySelector('select');
                    if (linkSel) {
                        linkSel.value = String(v);
                        linkSel.onchange = function () {
                            el.value = linkSel.value;
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
            if (window.cmsRepeater) { window.cmsRepeater.destroy(editor); }
            editing = idx;
            var inst = state[idx];
            var m = pool[String(inst._module_id)] || {};
            var type = inst.type || m.type || 'text';
            fields = (m.schema && m.schema.length) ? m.schema : (TYPE_FIELDS[type] || []);
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
            if (window.cmsRepeater) { window.cmsRepeater.init(editor); }
            editor.style.display = 'block';
            editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function closeEditor() {
            editing = null;
            if (window.cmsRichText) { window.cmsRichText.destroy(editor); }
            if (window.cmsRepeater) { window.cmsRepeater.destroy(editor); }
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
            var inst = { _module_id: parseInt(id, 10), type: t.type };
            var schema = (t.schema && t.schema.length) ? t.schema : (TYPE_FIELDS[t.type] || []);
            schema.forEach(function (k) { inst[k] = ''; });
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
