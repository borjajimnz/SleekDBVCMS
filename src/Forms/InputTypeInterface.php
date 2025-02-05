<?php

namespace SleekDBVCMS\Forms;

interface InputTypeInterface
{
    public function render(string $name, $value = null, array $attributes = []): string;
}
