<?php

namespace SleekDBVCMS\Forms\Types;

class SelectType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $options = $attributes['options'] ?? [];
        unset($attributes['options']);
        
        $attrs = $this->buildAttributes($this->getDefaultAttributes($name, null, $attributes));
        $optionsHtml = $this->renderOptions($options, $value);
        
        return sprintf('<select %s>%s</select>', $attrs, $optionsHtml);
    }
    
    protected function renderOptions(array $options, $selectedValue): string
    {
        $html = '';
        foreach ($options as $value => $label) {
            $selected = $selectedValue == $value ? ' selected' : '';
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($value),
                $selected,
                htmlspecialchars($label)
            );
        }
        return $html;
    }
}
