<?php

namespace SleekDBVCMS\Forms\Types;

class DatetimeType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attributes = array_merge([
            'name' => $name,
            'id' => $name,
            'type' => 'datetime-local',
            'class' => 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed',
            'value' => $value,
        ], $attributes);
        return '<input ' . $this->buildAttributes($attributes) . '>';
    }
}
