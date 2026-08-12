<?php

namespace SleekDBVCMS\Forms\Types;

class FormFieldsType extends AbstractType
{
    // Field types the front lead_form partial can render.
    private const FIELD_TYPES = [
        'text' => 'Text',
        'email' => 'Email',
        'tel' => 'Phone',
        'textarea' => 'Textarea',
        'select' => 'Select',
        'checkbox' => 'Checkbox',
    ];

    public function render(string $name, $value = null, array $attributes = []): string
    {
        $disabled = !empty($attributes['disabled']);
        unset($attributes['disabled']);

        // Normalize the stored value (JSON string or array) into field defs.
        $fields = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($fields)) {
            $fields = [];
        }

        $typesHtml = '';
        foreach (self::FIELD_TYPES as $type => $label) {
            $typesHtml .= '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($label) . '</option>';
        }

        $html = '<div class="form-fields-builder space-y-3"'
            . ' data-name="' . htmlspecialchars($name) . '"'
            . ' data-types="' . htmlspecialchars(json_encode(self::FIELD_TYPES), ENT_QUOTES) . '"'
            . ' data-disabled="' . ($disabled ? '1' : '0') . '">';

        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" class="form-fields-hidden" value="' . htmlspecialchars(json_encode($fields)) . '">';

        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">';
        $html .= '<div class="form-fields-empty px-4 py-6 text-center text-xs text-gray-500 dark:text-gray-400">No fields yet. Click "Add field".</div>';
        $html .= '<div class="form-fields-list divide-y divide-gray-200 dark:divide-gray-700"></div>';
        $html .= '</div>';

        if (!$disabled) {
            $html .= '<div><button type="button" class="form-fields-add inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">'
                . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'
                . 'Add field</button></div>';
        }

        $html .= '<script>' . $this->builderScript() . '</script>';
        $html .= '</div>';

        return $html;
    }

    private function builderScript(): string
    {
        return <<<'JS'
(function () {
    if (window.__formFieldsBuilderBound) { return; }
    window.__formFieldsBuilderBound = true;
    document.querySelectorAll('.form-fields-builder').forEach(function (builder) {
        var hidden = builder.querySelector('.form-fields-hidden');
        var list = builder.querySelector('.form-fields-list');
        var empty = builder.querySelector('.form-fields-empty');
        var addBtn = builder.querySelector('.form-fields-add');
        var name = builder.getAttribute('data-name') || 'fields';
        var disabled = builder.getAttribute('data-disabled') === '1';

        var TYPES = {};
        try { TYPES = JSON.parse(builder.getAttribute('data-types') || '{}'); } catch (e) {}

        var state = [];
        try { state = JSON.parse(hidden.value || '[]'); } catch (e) {}
        if (!Array.isArray(state)) { state = []; }

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function optionsToText(f) {
            var opts = f.options;
            if (typeof opts === 'string') { return opts; }
            if (Array.isArray(opts)) { return opts.join('\n'); }
            return '';
        }

        function rowHtml(f, i) {
            var type = f.type && TYPES[f.type] ? f.type : 'text';
            var required = !f.required ? '' : ' checked';
            var cls = 'w-full px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed';
            var clsSm = cls + ' text-xs';
            var typeOptions = '';
            Object.keys(TYPES).forEach(function (t) {
                typeOptions += '<option value="' + esc(t) + '"' + (t === type ? ' selected' : '') + '>' + esc(TYPES[t]) + '</option>';
            });

            var h = '<div class="ff-row p-4" data-idx="' + i + '">';
            h += '<div class="flex items-start gap-2">';
            h += '<div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">';

            h += '<div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Label</label>'
                + '<input type="text" class="ff-label ' + clsSm + '" value="' + esc(f.label || '') + '" placeholder="First name" ' + (disabled ? 'disabled' : '') + '></div>';

            h += '<div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Name</label>'
                + '<input type="text" class="ff-name ' + clsSm + '" value="' + esc(f.name || '') + '" placeholder="name" ' + (disabled ? 'disabled' : '') + '></div>';

            h += '<div><label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Type</label>'
                + '<select class="ff-type ' + clsSm + '" ' + (disabled ? 'disabled' : '') + '>' + typeOptions + '</select></div>';

            h += '<div class="flex items-end gap-2">'
                + '<label class="flex items-center gap-1.5 pb-1.5 text-xs text-gray-500 dark:text-gray-400"><input type="checkbox" class="ff-required h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 dark:bg-gray-800" ' + required + ' ' + (disabled ? 'disabled' : '') + '> Required</label>';

            if (!disabled) {
                h += '<button type="button" class="ff-remove p-1 rounded text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50" title="Remove field"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
            }
            h += '</div>';

            h += '</div>';
            h += '</div>';

            h += '<div class="ff-options mt-2" style="display:' + (type === 'select' ? '' : 'none') + '">'
                + '<label class="block text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Options (one per line)</label>'
                + '<textarea rows="2" class="ff-options-input w-full px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed" ' + (disabled ? 'disabled' : '') + '>' + esc(optionsToText(f)) + '</textarea>'
                + '</div>';

            h += '</div>';
            return h;
        }

        function render() {
            var html = '';
            state.forEach(function (f, i) { html += rowHtml(f, i); });
            list.innerHTML = html;
            if (empty) { empty.style.display = state.length ? 'none' : ''; }
            syncHidden();
        }

        function syncHidden() {
            hidden.value = JSON.stringify(state);
        }

        function readRow(row) {
            var f = state[parseInt(row.getAttribute('data-idx'), 10)];
            if (!f) { return; }
            f.label = row.querySelector('.ff-label').value;
            f.name = row.querySelector('.ff-name').value;
            f.type = row.querySelector('.ff-type').value;
            f.required = row.querySelector('.ff-required').checked;
            if (f.type === 'select') {
                f.options = row.querySelector('.ff-options-input').value.split('\n').map(function (s) { return s.trim(); }).filter(function (s) { return s !== ''; });
            } else {
                f.options = [];
            }
        }

        function refreshRow(row) {
            readRow(row);
            var idx = parseInt(row.getAttribute('data-idx'), 10);
            var f = state[idx];
            var optionsWrap = row.querySelector('.ff-options');
            optionsWrap.style.display = f.type === 'select' ? '' : 'none';
            syncHidden();
        }

        list.addEventListener('input', function (e) {
            var row = e.target.closest('.ff-row');
            if (row) { refreshRow(row); }
        });

        list.addEventListener('change', function (e) {
            var row = e.target.closest('.ff-row');
            if (row && e.target.classList.contains('ff-required')) { refreshRow(row); }
            if (row && e.target.classList.contains('ff-type')) { refreshRow(row); }
        });

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.ff-remove');
            if (!btn) { return; }
            var row = btn.closest('.ff-row');
            var idx = parseInt(row.getAttribute('data-idx'), 10);
            state.splice(idx, 1);
            render();
        });

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                state.push({ name: '', label: '', type: 'text', required: false, options: [] });
                render();
            });
        }

        render();
    });
})();
JS;
    }
}
