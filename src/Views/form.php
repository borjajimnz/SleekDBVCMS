<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $table */
/** @var array $fields */
/** @var array $data */
/** @var string $action */
/** @var array $joinData */

$isView = $action === 'view_row';
?>
<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="font-semibold capitalize"><?php $core->_('Create'); ?> <?php print htmlspecialchars($table); ?></h2>
        </div>
        <div class="p-5">
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <?php if ($action === 'update_row') { ?>
                    <input type="hidden" name="id" value="<?php print htmlspecialchars((string)($data['_id'] ?? '')); ?>">
                <?php } ?>

                <?php
                $fb = $core->getFormBuilder()->withData($data);
                foreach ($fields as $name => $value) {
                    if (is_array($value) && isset($value['join'])) {
                        $options = [];
                        foreach ($joinData[$name] ?? [] as $option) {
                            $display = '';
                            foreach ($value['join']['foreing_display'] as $dfield) {
                                $display .= $option[$dfield] ?? '';
                            }
                            $options[$option['_id']] = trim($display);
                        }
                        print $fb->field($name, 'select', [
                            'options' => $options,
                            'disabled' => $isView,
                            'label' => $name,
                        ]);
                        continue;
                    }

                    $fieldType = (string)$value;
                    $fieldOptions = null;
                    $fieldStores = null;

                    if ($fieldType === 'modules') {
                        try {
                            $fieldOptions = $core->getDatabase()->findAll('modules', ['_id' => 'desc']);
                        } catch (\Throwable $e) {
                            $fieldOptions = [];
                        }
                        $fieldStores = array_keys($core->getConfig()->getStores());
                        try {
                            $fieldForms = [];
                            foreach ($core->getDatabase()->findAll('forms', ['_id' => 'desc']) as $form) {
                                $fieldForms[(string)$form['_id']] = trim((string)($form['title'] ?? '')) !== ''
                                    ? $form['title']
                                    : 'Form #' . $form['_id'];
                            }
                        } catch (\Throwable $e) {
                            $fieldForms = [];
                        }
                    } elseif ($fieldType === 'select' && $table === 'modules') {
                        if ($name === 'type') {
                            $fieldOptions = [
                                'hero' => 'Hero',
                                'text' => 'Text',
                                'html' => 'HTML',
                                'store_list' => 'Store list',
                                'store_item' => 'Store item',
                                'lead_form' => 'Lead form',
                                'cta' => 'Call to action',
                                'split' => 'Split (text + image)',
                                'features' => 'Features grid',
                                'stats' => 'Stats band',
                                'testimonials' => 'Testimonials',
                                'faq' => 'FAQ accordion',
                                'pricing' => 'Pricing plans',
                                'logos' => 'Logos strip',
                                'video' => 'Video',
                            ];
                        }
                    } elseif ($fieldType === 'repeater') {
                        $attrs['schema'] = \SleekDBVCMS\Forms\Types\RepeaterType::schemaForField($name);
                    } elseif ($fieldType === 'select' && $table === 'redirects') {
                        if ($name === 'code') {
                            $fieldOptions = [
                                '301' => '301 Moved Permanently',
                                '302' => '302 Found (temporary)',
                                '307' => '307 Temporary Redirect',
                                '308' => '308 Permanent Redirect',
                            ];
                        }
                    }

                    $attrs = ['label' => $name];
                    if ($fieldOptions !== null) {
                        $attrs['options'] = $fieldOptions;
                    }
                    if (isset($fieldStores) && $fieldType === 'modules') {
                        $attrs['stores'] = $fieldStores;
                    }
                    if (isset($fieldForms) && $fieldType === 'modules') {
                        $attrs['forms'] = $fieldForms;
                    }
                    if ($isView) {
                        $attrs['disabled'] = true;
                    }
                    print $fb->field($name, $fieldType, $attrs);
                }
                ?>

                <div class="pt-2 flex flex-col sm:flex-row gap-3">
                    <?php if (!$isView) { ?>
                        <button name="<?php print htmlspecialchars($action); ?>"
                                class="flex items-center justify-center gap-1.5 px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <?php $core->_($action); ?>
                        </button>
                    <?php } ?>
                    <a href="index.php?p=<?php print urlencode($table); ?>"
                       class="inline-flex items-center justify-center px-5 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800">
                        <?php $core->_('cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
