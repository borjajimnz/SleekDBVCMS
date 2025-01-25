<?php

namespace SleekDBVCMS\Forms\Types;

class TextareaType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attrs = $this->buildAttributes($this->getDefaultAttributes($name, null, $attributes));
        return sprintf('<textarea %s>%s</textarea>', $attrs, htmlspecialchars($value ?? ''));
    }
}
