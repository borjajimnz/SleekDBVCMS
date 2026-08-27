@php
    // lead_form modules reference a form template from the "forms" store.
    // The form's values win; the module instance is a fallback.
    $formConfig = $module;
    $formId = (int)($module['form_id'] ?? 0);
    if ($formId > 0 && isset($ctx['cms'])) {
        try {
            $form = $ctx['cms']->getDatabase()->findById('forms', $formId);
            if (is_array($form)) {
                foreach (['title', 'subtitle', 'fields', 'notify_to', 'notify_cc', 'button_text', 'success_message'] as $fkey) {
                    if (isset($form[$fkey]) && trim((string)$form[$fkey]) !== '') {
                        $formConfig[$fkey] = $form[$fkey];
                    }
                }
            }
        } catch (\Throwable $e) {
            // form not found: fall back to the module values
        }
    }

    $leadTitle = $formConfig['title'] ?? '';
    $leadSubtitle = $formConfig['subtitle'] ?? '';
    $buttonText = $formConfig['button_text'] ?? 'Send';
    $successMessage = $formConfig['success_message'] ?? 'Thank you! Your message has been sent.';
    $leadPage = $ctx['page'] ?? '';
    $leadIndex = $ctx['module_index'] ?? 0;

    $fieldDefs = is_string($formConfig['fields'] ?? null) ? json_decode($formConfig['fields'], true) : ($formConfig['fields'] ?? []);
    if (!is_array($fieldDefs)) {
        $fieldDefs = [];
    }

    $sent = isset($_GET['sent']);
    $error = $_GET['error'] ?? null;
    $old = $_POST ?? [];
@endphp
<section class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="p-5 sm:p-8">
        @if ($sent)
            <div class="px-4 py-3 rounded-lg bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 text-sm font-medium">
                {{ $successMessage }}
            </div>
        @else
            @if ($leadTitle)
                <h2 class="text-xl font-semibold mb-1">{{ $leadTitle }}</h2>
            @endif
            @if ($leadSubtitle)
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $leadSubtitle }}</p>
            @endif

            @if ($error)
                <div class="mb-4 px-4 py-2 rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                    {{ $error }}
                </div>
            @endif

            <form method="post" action="/{{ $leadPage !== '' ? rawurlencode($leadPage) : '' }}" class="space-y-4">
                <input type="hidden" name="lead_submit" value="1">
                <input type="hidden" name="lead_page" value="{{ $leadPage }}">
                <input type="hidden" name="lead_index" value="{{ $leadIndex }}">

                @foreach ($fieldDefs as $field)
                    @php
                        if (!is_array($field) || empty($field['name'])) {
                            continue;
                        }
                        $name = $field['name'];
                        $type = $field['type'] ?? 'text';
                        $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $name));
                        $required = !empty($field['required']);
                        $value = $old[$name] ?? '';
                        $cls = 'w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-60';
                    @endphp
                    <div>
                        <label for="lead_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $label }}{{ $required ? ' *' : '' }}
                        </label>
                        @if ($type === 'textarea')
                            <textarea id="lead_{{ $name }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 5 }}" class="{{ $cls }}" {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
                        @elseif ($type === 'select')
                            <select id="lead_{{ $name }}" name="{{ $name }}" class="{{ $cls }}" {{ $required ? 'required' : '' }}>
                                <option value="">—</option>
                                @foreach (($field['options'] ?? []) as $opt)
                                    <option value="{{ $opt }}" {{ (string)$value === (string)$opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif ($type === 'checkbox')
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" id="lead_{{ $name }}" name="{{ $name }}" value="1" class="h-4 w-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 dark:bg-gray-800 focus:ring-blue-500" {{ !empty($value) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @else
                            <input type="{{ $type === 'tel' ? 'tel' : ($type === 'email' ? 'email' : 'text') }}" id="lead_{{ $name }}" name="{{ $name }}" value="{{ $value }}" class="{{ $cls }}" {{ $required ? 'required' : '' }}>
                        @endif
                    </div>
                @endforeach

                @if (empty($fieldDefs))
                    <p class="text-sm text-gray-500 dark:text-gray-400">This form has no fields configured yet.</p>
                @endif

                <div>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
                        {{ $buttonText }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</section>
