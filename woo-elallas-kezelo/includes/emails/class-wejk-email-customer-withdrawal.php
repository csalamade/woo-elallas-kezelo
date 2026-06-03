<?php
/**
 * Vásárlói visszaigazoló e-mail (WC_Email)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_Email')) {
    return;
}

class WEJK_Email_Customer_Withdrawal extends WC_Email {

    public function __construct() {
        $this->id             = 'wejk_customer_withdrawal';
        $this->customer_email = true;
        
        $this->title          = __('Elállás / Lemondás visszaigazolása', 'woo-elallas-kezelo');
        $this->description    = __('Ezt az e-mailt a vásárló kapja, miután sikeresen beküldte az elállási vagy lemondási nyilatkozatot a weboldalon.', 'woo-elallas-kezelo');
        
        $this->template_html  = 'emails/customer-withdrawal.php';
        $this->template_plain = 'emails/plain/customer-withdrawal.php';
        $this->template_base  = plugin_dir_path(dirname(__FILE__)) . 'templates/';

        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Alapértelmezett beállítások betöltése
        parent::__construct();
    }

    public function trigger($order_id, $is_pre_dispatch = false, $returned_items = array()) {
        $this->setup_locale();

        if ($order_id) {
            $this->object = wc_get_order($order_id);
            if ($this->object) {
                $this->recipient = $this->object->get_billing_email();
                
                $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
            }
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            
            // Dinamikus tartalom
            $this->is_pre_dispatch = $is_pre_dispatch;
            $this->returned_items = $returned_items;

            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }

    public function get_default_subject() {
        return __('Visszaigazolás az elállási/lemondási nyilatkozatról - Rendelés #{order_number}', 'woo-elallas-kezelo');
    }

    public function get_default_heading() {
        return __('Elállási nyilatkozat rögzítve', 'woo-elallas-kezelo');
    }

    public function get_content_html() {
        ob_start();
        
        $email_heading = $this->get_heading();
        
        // WooCommerce alap e-mail header
        do_action('woocommerce_email_header', $email_heading, $this);

        echo '<p>' . esc_html__('Tisztelt Vásárlónk,', 'woo-elallas-kezelo') . '</p>';
        echo '<p>' . esc_html__('Ezúton igazoljuk vissza, hogy a mai napon rögzítettük az elállási / lemondási nyilatkozatát a(z) ', 'woo-elallas-kezelo') . '<strong>' . $this->object->get_order_number() . '</strong>' . esc_html__(' számú rendeléssel kapcsolatban.', 'woo-elallas-kezelo') . '</p>';
        
        echo '<h3>' . esc_html__('Az elállás / lemondás részletei:', 'woo-elallas-kezelo') . '</h3>';
        echo '<ul>';
        echo '<li><strong>' . esc_html__('Beküldés időpontja:', 'woo-elallas-kezelo') . '</strong> ' . wp_date('Y-m-d H:i:s') . '</li>';
        echo '<li><strong>' . esc_html__('Vásárló neve:', 'woo-elallas-kezelo') . '</strong> ' . $this->object->get_formatted_billing_full_name() . '</li>';
        echo '<li><strong>' . esc_html__('Vásárló e-mail címe:', 'woo-elallas-kezelo') . '</strong> ' . $this->object->get_billing_email() . '</li>';
        echo '</ul>';

        echo '<h3>' . esc_html__('Érintett termékek:', 'woo-elallas-kezelo') . '</h3>';
        echo '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">';
        echo '<thead><tr><th style="text-align:left; border: 1px solid #eee;">' . esc_html__('Termék', 'woo-elallas-kezelo') . '</th><th style="text-align:left; border: 1px solid #eee;">' . esc_html__('Mennyiség', 'woo-elallas-kezelo') . '</th></tr></thead><tbody>';
        
        $items = $this->object->get_items();
        foreach ($items as $item_id => $item) {
            $product_id = $item->get_product_id();
            if (empty($this->returned_items) || in_array($product_id, $this->returned_items)) {
                echo '<tr>';
                echo '<td style="border: 1px solid #eee;">' . esc_html($item->get_name()) . '</td>';
                echo '<td style="border: 1px solid #eee;">' . esc_attr($item->get_quantity()) . ' db</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';

        echo '<h3>' . esc_html__('További lépések:', 'woo-elallas-kezelo') . '</h3>';
        
        $additional_content = $this->get_option('additional_content');
        if ($additional_content) {
            echo wpautop(wp_kses_post(wptexturize($additional_content)));
        } else {
            if ($this->is_pre_dispatch) {
                echo '<p>' . esc_html__('Mivel a rendelés még feladás előtt állt, a megrendelést töröltük. Ha a rendelés összegét már kifizette, munkatársaink hamarosan intézkednek a vételár visszatérítéséről. További teendője nincs.', 'woo-elallas-kezelo') . '</p>';
            } else {
                echo '<p>' . esc_html__('Kérjük, juttassa vissza a megjelölt terméke(ke)t az ÁSZF-ben megadott visszaküldési címünkre 14 napon belül. A csomag beérkezése és ellenőrzése után visszautaljuk az összeget.', 'woo-elallas-kezelo') . '</p>';
            }
        }

        do_action('woocommerce_email_footer', $this);
        
        return ob_get_clean();
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Bekapcsolás/Kikapcsolás', 'woo-elallas-kezelo'),
                'type'    => 'checkbox',
                'label'   => __('E-mail értesítés engedélyezése', 'woo-elallas-kezelo'),
                'default' => 'yes',
            ),
            'subject' => array(
                'title'       => __('Tárgy', 'woo-elallas-kezelo'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => sprintf(__('Alapértelmezett: <code>%s</code>.', 'woo-elallas-kezelo'), $this->get_default_subject()),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading' => array(
                'title'       => __('Fejléc', 'woo-elallas-kezelo'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => sprintf(__('Alapértelmezett: <code>%s</code>.', 'woo-elallas-kezelo'), $this->get_default_heading()),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __('Egyedi "További lépések" szöveg', 'woo-elallas-kezelo'),
                'description' => __('Ez a szöveg jelenik meg az e-mail alján. Ha üresen hagyod, egy alapértelmezett szöveg jelenik meg a rendelés státuszától (feladás előtti vagy utáni) függően.', 'woo-elallas-kezelo'),
                'type'        => 'textarea',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'       => __('E-mail formátuma', 'woo-elallas-kezelo'),
                'type'        => 'select',
                'description' => __('Válaszd ki az e-mail formátumát.', 'woo-elallas-kezelo'),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => array(
                    'html'      => __('HTML', 'woo-elallas-kezelo'),
                    'plain'     => __('Egyszerű szöveg', 'woo-elallas-kezelo'),
                ),
            ),
        );
    }
}
