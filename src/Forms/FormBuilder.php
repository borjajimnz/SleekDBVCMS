<?php

namespace SleekDBVCMS\Forms;

use SleekDBVCMS\Forms\Types\TextType;
use SleekDBVCMS\Forms\Types\TextareaType;
use SleekDBVCMS\Forms\Types\RichTextareaType;
use SleekDBVCMS\Forms\Types\EmailType;
use SleekDBVCMS\Forms\Types\NumberType;
use SleekDBVCMS\Forms\Types\ColorType;
use SleekDBVCMS\Forms\Types\UrlType;
use SleekDBVCMS\Forms\Types\FileType;
use SleekDBVCMS\Forms\Types\SelectType;

class FormBuilder
{
    private array $types = [];
    private array $data = [];
    private array $errors = [];

    public function __construct(array $data = [], array $errors = [])
    {
        $this->data = $data;
        $this->errors = $errors;
        $this->registerDefaultTypes();
    }

    private function registerDefaultTypes(): void
    {
        $this->types = [
            'text' => new TextType(),
            'textarea' => new TextareaType(),
            'rich_textarea' => new RichTextareaType(),
            'email' => new EmailType(),
            'number' => new NumberType(),
            'color' => new ColorType(),
            'url' => new UrlType(),
            'file' => new FileType(),
            'select' => new SelectType(),
        ];
    }

    public function start(string $action = '', string $method = 'POST', array $attributes = []): string
    {
        $defaultAttrs = [
            'action' => $action,
            'method' => $method,
            'enctype' => 'multipart/form-data',
            'class' => 'form'
        ];
        
        $attrs = array_merge($defaultAttrs, $attributes);
        $attrString = $this->buildAttributes($attrs);
        
        return sprintf('<form %s>', $attrString);
    }

    public function end(): string
    {
        return '</form>';
    }

    public function field(string $name, string $type = 'text', array $attributes = []): string
    {
        $value = $this->data[$name] ?? null;
        $error = $this->errors[$name] ?? null;
        $label = $attributes['label'] ?? ucfirst(str_replace('_', ' ', $name));
        unset($attributes['label']);

        $inputType = $this->types[$type] ?? $this->types['text'];
        
        $html = sprintf('<div class="form-group%s">', $error ? ' has-error' : '');
        $html .= sprintf('<label for="%s">%s</label>', $name, $label);
        $html .= $inputType->render($name, $value, $attributes);
        
        if ($error) {
            $html .= sprintf('<span class="help-block">%s</span>', $error);
        }
        
        $html .= '</div>';
        
        return $html;
    }

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
}
