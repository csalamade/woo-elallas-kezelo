<?php
/**
 * Űrlap feldolgozásáért és logikáért felelős osztály
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Process {

    public function __construct() {
        // Sikeres képernyő megjelenítése (kiváltja a normál oldalbetöltést)
        add_action('template_redirect', array($this, 'display_success_page'), 5);
        
        // KÉRÉS FELDOLGOZÁSA (STÁTUSZ VÁLTÁS, E-MAIL KÜLDÉS)
        add_action('template_redirect', array($this, 'process_return_request'));
    }

    public function display_success_page() {
        if (isset($_GET['wejk_elallas_success'])) {
            get_header();
            ?>
            <div class="woocommerce">
                <div class="woocommerce-MyAccount-content" style="width: 100%; max-width: 800px; margin: 50px auto; text-align: center;">
                    <div style="padding: 40px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">
                        <h2 style="color: #155724;"><?php esc_html_e('Nyilatkozat sikeresen beküldve', 'woo-elallas-kezelo'); ?></h2>
                        <p style="font-size: 16px; margin-bottom: 15px;"><?php esc_html_e('Az elállási/lemondási nyilatkozatot sikeresen rögzítettük.', 'woo-elallas-kezelo'); ?></p>
                        <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px;"><?php esc_html_e('A visszaigazolást, valamint a további teendőket elküldtük az e-mail címére.', 'woo-elallas-kezelo'); ?></p>
                        <p style="font-size: 14px; margin-bottom: 25px;"><?php esc_html_e('(Ez a jogszabályoknak megfelelő tartós adathordozónak minősül).', 'woo-elallas-kezelo'); ?></p>
                        
                        <a href="<?php echo esc_url(home_url()); ?>" class="button button-primary"><?php esc_html_e('Vissza a főoldalra', 'woo-elallas-kezelo'); ?></a>
                    </div>
                </div>
            </div>
            <?php
            get_footer();
            exit;
        }
    }

    public function process_return_request() {
        // Ellenőrizzük, hogy elküldték-e az űrlapot
        if (isset($_POST['process_return_request']) && isset($_POST['return_order_id'])) {
            
            $order_id = absint($_POST['return_order_id']);
            
            // 1. Biztonsági ellenőrzés: Nonce validáció
            if (!isset($_POST['elallas_nonce']) || !wp_verify_nonce($_POST['elallas_nonce'], 'elallas_action_' . $order_id)) {
                $redirect_url = add_query_arg('wejk_error', 'security', remove_query_arg(array('wejk_elallas_success'), wp_unslash($_SERVER['REQUEST_URI'])));
                wp_safe_redirect($redirect_url);
                exit;
            }

            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            $pre_dispatch_statuses = get_option('wejk_pre_dispatch_statuses', array('processing', 'pending'));
            if (!is_array($pre_dispatch_statuses)) {
                $pre_dispatch_statuses = array();
            }
            $shipped_status = get_option('wejk_shipped_status', 'completed');

            $current_status = $order->get_status();
            $is_pre_dispatch = in_array($current_status, $pre_dispatch_statuses);
            $is_shipped = ($current_status === $shipped_status);

            // 2. Csak engedélyezett státuszoknál
            if (!$is_pre_dispatch && !$is_shipped) {
                $redirect_url = add_query_arg('wejk_error', 'invalid_status', remove_query_arg(array('wejk_elallas_success'), wp_unslash($_SERVER['REQUEST_URI'])));
                wp_safe_redirect($redirect_url);
                exit;
            }

            // Részleges elállás: termékek ellenőrzése
            $returned_items = isset($_POST['wejk_returned_products']) ? array_map('absint', $_POST['wejk_returned_products']) : array();
            if (empty($returned_items)) {
                $redirect_url = add_query_arg('wejk_error', 'no_products', remove_query_arg(array('wejk_elallas_success'), wp_unslash($_SERVER['REQUEST_URI'])));
                wp_safe_redirect($redirect_url);
                exit;
            }
            $order->update_meta_data('_wejk_returned_items', $returned_items);
            $order->save();

            if ($is_pre_dispatch) {
                $target_status = get_option('wejk_pre_dispatch_action_status', 'cancelled');
                $order->update_status($target_status, __('A vásárló feladás előtt lemondta a rendelést a rendeléskövető felületen.', 'woo-elallas-kezelo'));
            } else {
                // Alapértelmezett on-hold a teljesített utáni elállásra
                $target_status = 'on-hold';
                $order->update_status($target_status, __('A vásárló elállást kezdeményezett a rendeléskövető felületen (feladás után).', 'woo-elallas-kezelo'));
            }

            // Vásárlói visszaigazoló és admin értesítő e-mail küldése (WC_Email)
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            
            if (isset($emails['WEJK_Email_Admin_Withdrawal'])) {
                $emails['WEJK_Email_Admin_Withdrawal']->trigger($order_id, $is_pre_dispatch, $returned_items, $target_status);
            }

            // Vásárlói visszaigazoló e-mail küldése (WC_Email)
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            if (isset($emails['WEJK_Email_Customer_Withdrawal'])) {
                $emails['WEJK_Email_Customer_Withdrawal']->trigger($order_id, $is_pre_dispatch, $returned_items);
            }

            // 5. Átirányítás a különálló sikeres képernyőre (ugyanarra az URL-re, de success paraméterrel)
            $redirect_url = add_query_arg('wejk_elallas_success', $order_id, wp_unslash($_SERVER['REQUEST_URI']));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
