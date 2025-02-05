<?php

namespace SleekDBVCMS\Forms\Types;

use SleekDBVCMS\Forms\AbstractType;

class TextType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attrs = $this->buildAttributes($this->getDefaultAttributes($name, $value, array_merge([
            'type' => 'text'
        ], $attributes)));

        return sprintf('<input %s>', $attrs);
    }
}
