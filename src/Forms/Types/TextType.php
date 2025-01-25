<?php

namespace SleekDBVCMS\Forms\Types;

use SleekDBVCMS\Forms\InputTypeInterface;

class TextType implements InputTypeInterface
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attrs = $this->buildAttributes(array_merge([
            'type' => 'text',
            'name' => $name,
            'value' => $value,
            'class' => 'form-control'
        ], $attributes));

        return sprintf('<input %s>', $attrs);
    }

    protected function buildAttributes(array $attributes): string
    {
        $attrs = [];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            $attrs[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }
        return implode(' ', $attrs);
    }
}
