<?php

namespace SleekDBVCMS\Forms\Types;

class ModulesType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        $value = $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $value;

        $attributes = array_merge([
            'name' => $name,
            'id' => $name,
            'class' => 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 font-mono text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed',
            'rows' => '14',
            'spellcheck' => 'false',
        ], $attributes);

        return '<textarea ' . $this->buildAttributes($attributes) . '>' . htmlspecialchars((string)($value ?? '')) . '</textarea>'
            . '<small class="block mt-1 text-xs text-gray-500 dark:text-gray-400">JSON array of modules. Order = display order. Types: hero, text, store_list, html, store_item.</small>';
    }
}
