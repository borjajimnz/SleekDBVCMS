<?php

namespace SleekDBVCMS\Forms;

class FormBuilder
{
    private array $types = [];
    private array $data = [];

    public function __construct(array $types = [])
    {
        foreach ($types as $name => $type) {
            $this->addType($name, $type);
        }
    }

    public function addType(string $name, InputTypeInterface $type): void
    {
        $this->types[$name] = $type;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function startForm(string $action = '', string $method = 'post', array $attributes = []): string
    {
        $attrs = array_merge([
            'action' => $action,
            'method' => $method,
            'enctype' => 'multipart/form-data'
        ], $attributes);

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        return sprintf('<form%s>', $attrString);
    }

    public function endForm(): string
    {
        return '</form>';
    }

    public function renderField(string $name, string $type, array $options = []): string
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(sprintf('Unsupported field type: %s', $type));
        }

        $value = $this->data[$name] ?? null;
        $label = $options['label'] ?? ucfirst($name);
        
        return sprintf(
            '<div class="form-group">
                <label for="%s">%s</label>
                %s
            </div>',
            $name,
            $label,
            $this->types[$type]->render($name, $value, $options)
        );
    }
}
