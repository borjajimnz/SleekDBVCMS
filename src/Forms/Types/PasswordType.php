<?php

namespace SleekDBVCMS\Forms\Types;

class PasswordType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attributes = array_merge([
            'name' => $name,
            'type' => 'password',
            'class' => 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed',
            'autocomplete' => 'new-password',
        ], $attributes);
        unset($attributes['value']);
        return '<input ' . $this->buildAttributes($attributes) . '><small class="block mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank if you don\'t want to change the password.</small>';
    }
}
