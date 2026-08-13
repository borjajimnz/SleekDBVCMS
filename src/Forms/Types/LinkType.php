<?php

namespace SleekDBVCMS\Forms\Types;

/**
 * URL picker for module fields (cta_url, video_url, repeater links). Lets the
 * editor type an external URL OR select an internal target (page, store
 * listing, store item) from a dropdown. The stored value is always a plain URL
 * string: internal targets are stored as "/slug", "/store" or "/store/id" so
 * the front renders them directly in href without extra conversion.
 */
class LinkType extends AbstractType
{
    // Links format: list of ['value' => '/target', 'label' => 'Human label'].
    public static function decorateSchema(array $schema, array $links): array
    {
        foreach ($schema as &$f) {
            if (in_array($f['type'] ?? '', ['url', 'link'], true)) {
                $f['type'] = 'link';
                $f['links'] = $links;
            }
        }
        return $schema;
    }

    public function render(string $name, $value = null, array $attributes = []): string
    {
        $links = $attributes['links'] ?? [];
        $disabled = !empty($attributes['disabled']);
        unset($attributes['links'], $attributes['options']);

        $inputAttrs = $this->getDefaultAttributes($name, $value, array_merge([
            'type' => 'text',
            'placeholder' => 'https://... o /pagina (interno)',
        ], $attributes));
        $input = sprintf('<input %s>', $this->buildAttributes($inputAttrs));

        $selectAttrs = [
            'class' => 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed cms-link-picker',
        ];
        if ($disabled) {
            $selectAttrs['disabled'] = true;
        }
        $selectAttrs = $this->buildAttributes($selectAttrs);
        $options = '<option value="">-- Enlace interno --</option>';
        foreach ($links as $link) {
            $val = (string)($link['value'] ?? '');
            $label = (string)($link['label'] ?? $val);
            $options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($val, ENT_QUOTES, 'UTF-8'),
                $value !== null && (string)$value === $val ? ' selected' : '',
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
        }
        $select = sprintf('<select %s>%s</select>', $selectAttrs, $options);

        return $input . $select;
    }
}
