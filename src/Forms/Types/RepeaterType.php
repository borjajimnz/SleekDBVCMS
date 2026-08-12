<?php

namespace SleekDBVCMS\Forms\Types;

/**
 * Generic JSON list builder. Stores a JSON array of objects in a hidden input;
 * the row editing UI lives in window.cmsRepeater (src/Views/layout.php), which
 * both renders the static form rows and initializes repeaters injected
 * dynamically by the ModulesType inline editor.
 */
class RepeaterType extends AbstractType
{
    // Schema definitions for the module repeater fields. Each entry is:
    // ['name' => ..., 'label' => ..., 'type' => text|textarea|url|select|checkbox|image, 'options' => ...]
    public static function schemaForField(string $field): array
    {
        $schemas = [
            'features' => [
                ['name' => 'icon', 'label' => 'Icon (emoji)', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['name' => 'text', 'label' => 'Description', 'type' => 'textarea'],
            ],
            'stats' => [
                ['name' => 'value', 'label' => 'Value', 'type' => 'text'],
                ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
            ],
            'testimonials' => [
                ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea'],
                ['name' => 'author', 'label' => 'Author', 'type' => 'text'],
                ['name' => 'role', 'label' => 'Role / company', 'type' => 'text'],
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
            ],
            'faq' => [
                ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea'],
            ],
            'pricing' => [
                ['name' => 'name', 'label' => 'Plan name', 'type' => 'text'],
                ['name' => 'price', 'label' => 'Price', 'type' => 'text'],
                ['name' => 'period', 'label' => 'Period', 'type' => 'text'],
                ['name' => 'features', 'label' => 'Features (one per line)', 'type' => 'textarea'],
                ['name' => 'cta_text', 'label' => 'Button text', 'type' => 'text'],
                ['name' => 'cta_url', 'label' => 'Button url', 'type' => 'url'],
                ['name' => 'highlight', 'label' => 'Highlight plan', 'type' => 'checkbox'],
            ],
            'logos' => [
                ['name' => 'image', 'label' => 'Logo image', 'type' => 'image'],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'url', 'label' => 'Link', 'type' => 'url'],
            ],
        ];
        return $schemas[$field] ?? [];
    }

    public function render(string $name, $value = null, array $attributes = []): string
    {
        $disabled = !empty($attributes['disabled']);
        $schema = $attributes['schema'] ?? [];
        unset($attributes['disabled'], $attributes['schema'], $attributes['name'], $attributes['id']);

        // Normalize the stored value (JSON string or array) into items.
        $items = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($items)) {
            $items = [];
        }

        $html = '<div class="repeater-builder space-y-3"'
            . ' data-name="' . htmlspecialchars($name) . '"'
            . ' data-schema="' . htmlspecialchars(json_encode($schema), ENT_QUOTES) . '"'
            . ' data-disabled="' . ($disabled ? '1' : '0') . '">';

        $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" class="repeater-hidden" value="' . htmlspecialchars(json_encode($items)) . '">';

        $html .= '<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">';
        $html .= '<div class="repeater-empty px-4 py-6 text-center text-xs text-gray-500 dark:text-gray-400">No items yet. Click "Add item".</div>';
        $html .= '<div class="repeater-list divide-y divide-gray-200 dark:divide-gray-700"></div>';
        $html .= '</div>';

        if (!$disabled) {
            $html .= '<div><button type="button" class="repeater-add inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">'
                . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'
                . 'Add item</button></div>';
        }

        $html .= '</div>';
        return $html;
    }
}
