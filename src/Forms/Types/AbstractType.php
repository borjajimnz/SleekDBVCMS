<?php

namespace SleekDBVCMS\Forms\Types;

use SleekDBVCMS\Forms\InputTypeInterface;

abstract class AbstractType implements InputTypeInterface
{
    protected function buildAttributes(array $attributes): string
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_bool($value)) {
                if ($value) {
                    $attrs[] = $key;
                }
                continue;
            }
            $attrs[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }
        return implode(' ', $attrs);
    }

    protected function getDefaultAttributes(string $name, $value, array $attributes): array
    {
        return array_merge([
            'name' => $name,
            'id' => $name,
            'class' => 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed',
            'value' => $value
        ], $attributes);
    }
}
