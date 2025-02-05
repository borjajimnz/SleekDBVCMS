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
            'class' => 'form-control',
            'value' => $value
        ], $attributes);
    }
}
