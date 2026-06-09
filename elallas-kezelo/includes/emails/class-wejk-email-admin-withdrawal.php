<?php
/**
 * Adminisztrátori értesítő e-mail (WC_Email)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Email')) {
    return;
}

class WEJK_Email_Admin_Withdrawal extends WC_Email {

    public bool $is_pre_dispatch = false;
    public array $returned_items = array();
    public string $target_status = '';

    public function __construct() {
        $this->id             = 'wejk_email_admin_withdrawal';
        $this->customer_email = false;
        
        $this->title          = __('Elállás / Lemondás értesítő (Admin)', 'elallas-kezelo');
        $this->description    = __('Ezt az e-mailt az adminisztrátor kapja, miután egy vásárló elállást vagy lemondást kezdeményezett.', 'elallas-kezelo');
        
        $this->template_html  = 'emails/admin-withdrawal.php';
        $this->template_plain = 'emails/plain/admin-withdrawal.php';
        $this->template_base  = plugin_dir_path(dirname(__FILE__)) . 'templates/';

        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );


        // Alapértelmezett beállítások betöltése
        parent::__construct();
        
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }

    public function trigger($order_id, $is_pre_dispatch = false, $returned_items = array(), $target_status = '') {
        $this->setup_locale();

        if ($order_id) {
            $this->object = wc_get_order($order_id);
            if ($this->object) {
                $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
            }
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            
            // Dinamikus tartalom
            $this->is_pre_dispatch = $is_pre_dispatch;
            $this->returned_items = $returned_items;
            $this->target_status = $target_status;

            // Szubjektum felülbírálása, ha szükséges
            $subject = $this->get_subject();
            if ($is_pre_dispatch && $subject === $this->get_default_subject()) {
                // translators: %s: Order number.
                $subject = sprintf(__('Feladás előtti lemondás (Elállás) - Rendelés #%s', 'elallas-kezelo'), $this->object->get_order_number());
            }

            $this->send($this->get_recipient(), $subject, $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    public function get_default_subject() {
        return __('Új elállási kérelem - Rendelés #{order_number}', 'elallas-kezelo');
    }

    public function get_default_heading() {
        return __('Elállási nyilatkozat érkezett', 'elallas-kezelo');
    }

    public function get_content_html() {
        ob_start();
        
        $email_heading = $this->get_heading();
        
        // WooCommerce alap e-mail header
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        do_action('woocommerce_email_header', $email_heading, $this);

        echo '<p>' . esc_html__('Kedves Admin!', 'elallas-kezelo') . '</p>';
        
        if ($this->is_pre_dispatch) {
            echo '<p><strong>' . esc_html__('Egy vásárló még a feladás előtt lemondta a rendelését (elállt a vásárlástól). Kérjük, NE add fel a csomagot!', 'elallas-kezelo') . '</strong></p>';
        } else {
            echo '<p>' . esc_html__('Egy vásárló élt az elállási jogával a weboldalon, miután a csomag feladásra került.', 'elallas-kezelo') . '</p>';
        }
        
        echo '<ul>';
        echo '<li><strong>' . esc_html__('Rendelésszám:', 'elallas-kezelo') . '</strong> ' . esc_html($this->object->get_order_number()) . '</li>';
        echo '<li><strong>' . esc_html__('Vásárló neve:', 'elallas-kezelo') . '</strong> ' . esc_html($this->object->get_formatted_billing_full_name()) . '</li>';
        echo '<li><strong>' . esc_html__('Vásárló e-mail címe:', 'elallas-kezelo') . '</strong> ' . esc_html($this->object->get_billing_email()) . '</li>';
        echo '</ul>';

        echo '<h3>' . esc_html__('Érintett termékek:', 'elallas-kezelo') . '</h3>';
        echo '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">';
        echo '<thead><tr><th style="text-align:left; border: 1px solid #eee;">' . esc_html__('Termék azonosító', 'elallas-kezelo') . '</th><th style="text-align:left; border: 1px solid #eee;">' . esc_html__('Név', 'elallas-kezelo') . '</th><th style="text-align:left; border: 1px solid #eee;">' . esc_html__('Mennyiség', 'elallas-kezelo') . '</th></tr></thead><tbody>';
        
        $actual_returned_items = $this->returned_items;
        if (empty($actual_returned_items)) {
            $meta = $this->object->get_meta('_wejk_returned_items');
            if (is_array($meta) && !empty($meta)) {
                $actual_returned_items = $meta;
            }
        }

        $items = $this->object->get_items();
        foreach ($items as $item_id => $item) {
            $product_id = $item->get_product_id();
            if (empty($actual_returned_items) || in_array($product_id, $actual_returned_items)) {
                echo '<tr>';
                echo '<td style="border: 1px solid #eee;">' . esc_attr($product_id) . '</td>';
                echo '<td style="border: 1px solid #eee;">' . esc_html($item->get_name()) . '</td>';
                echo '<td style="border: 1px solid #eee;">' . esc_attr($item->get_quantity()) . ' db</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        echo '<h3>' . esc_html__('Rendszer információ:', 'elallas-kezelo') . '</h3>';
        if ($this->is_pre_dispatch) {
            // translators: %s: Order status name.
            echo '<p>' . sprintf(esc_html__('A rendelés státusza automatikusan a beállított \'%s\' állapotra módosult. Kérjük, intézkedj a visszatérítésről, ha a rendelés már fizetve lett.', 'elallas-kezelo'), esc_html(wc_get_order_status_name($this->target_status))) . '</p>';

        } else {
            echo '<p>' . esc_html__('A rendelés státusza automatikusan \'Felfüggesztve\' (on-hold) állapotra módosult. Kérjük, vedd fel a kapcsolatot a vásárlóval a további teendőkkel kapcsolatban.', 'elallas-kezelo') . '</p>';
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        do_action('woocommerce_email_footer', $this);
        
        return ob_get_clean();
    }

    public function get_content_plain() {
        return wp_strip_all_tags($this->get_content_html());
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Bekapcsolás/Kikapcsolás', 'elallas-kezelo'),
                'type'    => 'checkbox',
                'label'   => __('E-mail értesítés engedélyezése', 'elallas-kezelo'),
                'default' => 'yes',
            ),
            'recipient' => array(
                'title'       => __('Címzett(ek)', 'elallas-kezelo'),
                'type'        => 'text',
                // translators: %s: Default admin email address.
                'description' => sprintf(__('Add meg, kinek menjen az értesítő (vesszővel elválasztva több cím is megadható). Alapértelmezett: <code>%s</code>.', 'elallas-kezelo'), get_option('admin_email')),
                'placeholder' => get_option('admin_email'),
                'default'     => get_option('admin_email'),
                'desc_tip'    => true,
            ),
            'subject' => array(
                'title'       => __('Tárgy', 'elallas-kezelo'),
                'type'        => 'text',
                'desc_tip'    => true,
                // translators: %s: Default subject.
                'description' => sprintf(__('Alapértelmezett: <code>%s</code>.', 'elallas-kezelo'), $this->get_default_subject()),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading' => array(
                'title'       => __('Fejléc', 'elallas-kezelo'),
                'type'        => 'text',
                'desc_tip'    => true,
                // translators: %s: Default heading.
                'description' => sprintf(__('Alapértelmezett: <code>%s</code>.', 'elallas-kezelo'), $this->get_default_heading()),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'email_type' => array(
                'title'       => __('E-mail formátuma', 'elallas-kezelo'),
                'type'        => 'select',
                'description' => __('Válaszd ki az e-mail formátumát.', 'elallas-kezelo'),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => array(
                    'html'      => __('HTML', 'elallas-kezelo'),
                    'plain'     => __('Egyszerű szöveg', 'elallas-kezelo'),
                ),
            ),
        );
    }
}
