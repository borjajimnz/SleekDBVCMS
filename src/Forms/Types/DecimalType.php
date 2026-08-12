<?php

namespace SleekDBVCMS\Forms\Types;

class DecimalType extends NumberType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        return parent::render($name, $value, array_merge([
            'step' => '0.01',
            'min' => '0',
        ], $attributes));
    }
}
