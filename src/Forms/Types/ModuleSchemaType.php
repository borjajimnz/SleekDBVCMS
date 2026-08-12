<?php

namespace SleekDBVCMS\Forms\Types;

/**
 * Schema picker for module templates (the "modules" collection). Lets the
 * admin choose which fields a template exposes, stored as a JSON array of
 * field names in a hidden input. Templates never carry values — only schemas.
 */
class ModuleSchemaType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $disabled = !empty($attributes['disabled']);
        unset($attributes['disabled']);

        $labels = ModulesType::fieldLabels();
        $selected = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($selected)) {
            $selected = [];
        }
        $selected = array_fill_keys(array_map('strval', $selected), true);

        $html = '<div class="module-schema-builder"'
            . ' data-name="' . htmlspecialchars($name) . '"'
            . ' data-disabled="' . ($disabled ? '1' : '0') . '">';

        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" class="module-schema-hidden" value="' . htmlspecialchars(json_encode(array_keys($selected))) . '">';

        $html .= '<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">';
        foreach ($labels as $field => $label) {
            $checked = isset($selected[$field]) ? ' checked' : '';
            $html .= '<label class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">'
                . '<input type="checkbox" class="module-schema-cb" value="' . htmlspecialchars($field) . '"' . $checked . ($disabled ? ' disabled' : '') . '>'
                . htmlspecialchars($label)
                . '</label>';
        }
        $html .= '</div>';

        $html .= '<script>' . $this->builderScript() . '</script>';
        $html .= '</div>';
        return $html;
    }

    private function builderScript(): string
    {
        return <<<'JS'
(function () {
    if (window.__moduleSchemaBound) { return; }
    window.__moduleSchemaBound = true;
    document.querySelectorAll('.module-schema-builder').forEach(function (builder) {
        var hidden = builder.querySelector('.module-schema-hidden');
        var cbs = builder.querySelectorAll('.module-schema-cb');
        var disabled = builder.getAttribute('data-disabled') === '1';
        if (disabled) { return; }

        function sync() {
            var sel = [];
            cbs.forEach(function (cb) { if (cb.checked) { sel.push(cb.value); } });
            hidden.value = JSON.stringify(sel);
        }
        cbs.forEach(function (cb) { cb.addEventListener('change', sync); });
    });
})();
JS;
    }
}
