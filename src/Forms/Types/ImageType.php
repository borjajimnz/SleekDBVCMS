<?php

namespace SleekDBVCMS\Forms\Types;

class ImageType extends AbstractType
{
    public function render(string $name, $value = null, array $attributes = []): string
    {
        $attributes = array_merge([
            'name' => $name,
            'type' => 'file',
            'class' => 'block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-950 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900 disabled:opacity-60 disabled:cursor-not-allowed',
            'accept' => implode(',', array_map(fn($e) => 'image/' . $e, ['jpeg', 'png', 'gif', 'webp'])),
        ], $attributes);
        unset($attributes['value']);

        $img = '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value ?? '') . '">';
        if (!empty($value)) {
            $img .= '<div class="mt-2"><img src="' . htmlspecialchars($value) . '" loading="lazy" class="rounded-lg border border-gray-200 dark:border-gray-800 max-w-[200px]"></div>';
        }
        $img .= '<input ' . $this->buildAttributes($attributes) . '>';
        return $img;
    }
}
