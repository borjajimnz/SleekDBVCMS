<?php

namespace SleekDBVCMS\Forms\Types;

class RichTextareaType extends TextareaType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' rich-editor');
        return parent::render($name, $value, $attributes);
    }
}
