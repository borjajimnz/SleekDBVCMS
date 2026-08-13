<?php
/** @var \SleekDBVCMS\Core $core */
/** @var string $table */
/** @var array $fields */
/** @var array $data */
/** @var string $action */
/** @var array $joinData */

$isView = $action === 'view_row';

/**
 * Build the internal-link picker list for module/repeater URL fields:
 * pages, store listings, and each store's items. Format is a list of
 * ['value' => '/target', 'label' => 'Human label'] so it can feed both the
 * server-rendered LinkType select and the client-side repeater picker.
 */
function cms_build_internal_links(\SleekDBVCMS\Core $core): array
{
    $links = [];
    $db = $core->getDatabase();

    try {
        foreach ($db->findAll('pages', ['menu_order' => 'asc']) as $p) {
            $slug = trim((string)($p['slug'] ?? ''));
            if ($slug === '') {
                $slug = 'page-' . $p['_id'];
            }
            $title = trim((string)($p['title'] ?? '')) !== '' ? $p['title'] : 'Page #' . $p['_id'];
            $links[] = ['value' => '/' . $slug, 'label' => 'Page: ' . $title];
        }
    } catch (\Throwable $e) {
    }

    foreach (array_keys($core->getConfig()->getStores()) as $storeName) {
        // The menus store is edited from the CMS sidebar and never routed on
        // the front, so it can't be a valid internal target.
        if ($storeName === 'menus') {
            continue;
        }
        $links[] = ['value' => '/' . $storeName, 'label' => 'Listing: ' . $storeName];
        try {
            foreach ($db->findAll($storeName, ['_id' => 'desc']) as $row) {
                $label = trim((string)($row['title'] ?? ''));
                if ($label === '') {
                    foreach ($row as $k => $v) {
                        if ($k === '_id' || is_array($v)) {
                            continue;
                        }
                        $label = trim((string)$v);
                        if ($label !== '') {
                            break;
                        }
                    }
                }
                $label = $label !== '' ? $label : '#' . $row['_id'];
                $links[] = ['value' => '/' . $storeName . '/' . (int)$row['_id'], 'label' => $storeName . ' / ' . $label];
            }
        } catch (\Throwable $e) {
        }
    }

    return $links;
}
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
                            // Self-join on the menus store: never allow an item to
                            // be its own parent (circular sub-menu).
                            if ($table === 'menus' && $name === 'parent'
                                && isset($data['_id']) && (int)($option['_id'] ?? 0) === (int)$data['_id']) {
                                continue;
                            }
                            $display = '';
                            foreach ($value['join']['foreing_display'] as $dfield) {
                                $display .= $option[$dfield] ?? '';
                            }
                            $options[$option['_id']] = trim($display);
                        }
                        // Menus parent: allow a top-level item (no parent).
                        if ($table === 'menus' && $name === 'parent') {
                            $options = ['' => '— Ninguno (nivel superior) —'] + $options;
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
                    $fieldLinks = null;

                    if ($fieldType === 'modules') {
                        $fieldLinks = cms_build_internal_links($core);
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
                        if ($fieldLinks === null) {
                            $fieldLinks = cms_build_internal_links($core);
                        }
                        $attrs['schema'] = \SleekDBVCMS\Forms\Types\LinkType::decorateSchema(
                            \SleekDBVCMS\Forms\Types\RepeaterType::schemaForField($name),
                            $fieldLinks
                        );
                    } elseif ($fieldType === 'link') {
                        if ($fieldLinks === null) {
                            $fieldLinks = cms_build_internal_links($core);
                        }
                    } elseif ($fieldType === 'select' && $table === 'menus') {
                        if ($name === 'location') {
                            $fieldOptions = [
                                'header' => 'Header (menú principal)',
                                'footer' => 'Footer (menú de pie)',
                            ];
                        }
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
                    if (isset($fieldLinks) && in_array($fieldType, ['modules', 'repeater', 'link'], true)) {
                        $attrs['links'] = $fieldLinks;
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
