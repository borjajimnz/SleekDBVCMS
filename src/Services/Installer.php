<?php

namespace SleekDBVCMS\Services;

/**
 * Idempotent installer operations: seed the default admin user, the
 * contact form, and the default module templates. Called from Bootstrap
 * (on every boot, safe) and from `bin/cms install`/`seed`.
 */
class Installer
{
    private \SleekDBVCMS\Core $core;

    public function __construct(\SleekDBVCMS\Core $core)
    {
        $this->core = $core;
    }

    public function seed(): void
    {
        $db = $this->core->getDatabase();

        // Default admin user (only when the users store is empty).
        $db->store('users');
        $users = $db->findAll('users');
        if (empty($users)) {
            $db->insert('users', [
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'created' => date('Y-m-d H:i:s'),
            ]);
        }

        // Default contact form (referenced by lead_form modules).
        $db->store('forms');
        $forms = $db->findAll('forms');
        $contactFormId = 0;
        if (empty($forms)) {
            $contactForm = $db->insert('forms', [
                'title' => 'Formulario de contacto',
                'subtitle' => 'Rellena el formulario y te responderemos lo antes posible.',
                'fields' => json_encode([
                    ['name' => 'name', 'label' => 'Nombre', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ['name' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'required' => false],
                    ['name' => 'company', 'label' => 'Empresa', 'type' => 'text', 'required' => false],
                    ['name' => 'message', 'label' => 'mensaje', 'type' => 'textarea', 'required' => true],
                ]),
                'notify_to' => '',
                'notify_cc' => '',
                'button_text' => 'Enviar',
                'success_message' => '¡Gracias! Tu mensaje ha sido enviado.',
            ]);
            $contactFormId = (int)($contactForm['_id'] ?? 0);
        } else {
            $contactFormId = (int)($forms[0]['_id'] ?? 0);
        }

        // Default module templates (one per supported type, idempotent by title).
        $db->store('modules');
        $defaultModules = [
            ['title' => 'Hero Bienvenida', 'type' => 'hero', 'fields' => json_encode(['title', 'image', 'subtitle', 'cta_text', 'cta_url'])],
            ['title' => 'Texto de presentación', 'type' => 'text', 'fields' => json_encode(['html'])],
            ['title' => 'Últimos posts', 'type' => 'store_list', 'fields' => json_encode(['title', 'store', 'limit'])],
            ['title' => 'Post destacado', 'type' => 'store_item', 'fields' => json_encode(['title', 'store', 'item_id'])],
            ['title' => 'HTML libre', 'type' => 'html', 'fields' => json_encode(['html'])],
            ['title' => 'Formulario de contacto', 'type' => 'lead_form', 'fields' => json_encode(['title', 'form_id'])],
            ['title' => 'Llamada a la acción', 'type' => 'cta', 'fields' => json_encode(['title', 'subtitle', 'image', 'cta_text', 'cta_url'])],
            ['title' => 'Texto + Imagen', 'type' => 'split', 'fields' => json_encode(['title', 'text', 'image', 'image_position', 'cta_text', 'cta_url'])],
            ['title' => 'Características', 'type' => 'features', 'fields' => json_encode(['title', 'subtitle', 'features'])],
            ['title' => 'Cifras clave', 'type' => 'stats', 'fields' => json_encode(['title', 'stats'])],
            ['title' => 'Testimonios', 'type' => 'testimonials', 'fields' => json_encode(['title', 'subtitle', 'testimonials'])],
            ['title' => 'Preguntas frecuentes', 'type' => 'faq', 'fields' => json_encode(['title', 'subtitle', 'faq'])],
            ['title' => 'Planes de precios', 'type' => 'pricing', 'fields' => json_encode(['title', 'subtitle', 'pricing'])],
            ['title' => 'Confían en nosotros', 'type' => 'logos', 'fields' => json_encode(['title', 'logos'])],
            ['title' => 'Video promocional', 'type' => 'video', 'fields' => json_encode(['title', 'subtitle', 'video_url', 'poster'])],
        ];

        $existingTitles = [];
        foreach ($db->findAll('modules') as $existing) {
            $existingTitles[trim((string)($existing['title'] ?? ''))] = true;
        }
        foreach ($defaultModules as $module) {
            $title = trim((string)($module['title'] ?? ''));
            if ($title !== '' && isset($existingTitles[$title])) {
                continue;
            }
            $inserted = $db->insert('modules', $module);
            if (is_array($inserted)) {
                $existingTitles[trim((string)($inserted['title'] ?? $title))] = true;
            }
        }
    }
}