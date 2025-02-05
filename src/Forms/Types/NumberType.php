<?php

namespace SleekDBVCMS\Forms\Types;

class NumberType extends TextType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        return parent::render($name, $value, array_merge([
            'type' => 'number'
        ], $attributes));
    }
}
