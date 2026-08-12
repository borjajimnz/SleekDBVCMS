<?php

namespace SleekDBVCMS\Forms\Types;

class CheckboxType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $checked = !empty($value) ? ' checked' : '';
        $attrs = $this->buildAttributes(array_merge([
            'name' => $name,
            'id' => $name,
            'type' => 'checkbox',
            'value' => '1',
            'class' => 'h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 dark:bg-gray-800 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed',
        ], $attributes));
        return '<div><input ' . $attrs . $checked . '></div>';
    }
}
