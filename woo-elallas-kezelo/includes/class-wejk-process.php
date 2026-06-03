<?php
/**
 * Űrlap feldolgozásáért és logikáért felelős osztály
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Process {

    public function __construct() {
        // KÉRÉS FELDOLGOZÁSA (STÁTUSZ VÁLTÁS, E-MAIL KÜLDÉS)
        add_action('template_redirect', array($this, 'process_return_request'));
    }

    public function process_return_request() {
        // Ellenőrizzük, hogy elküldték-e az űrlapot
        if (isset($_POST['process_return_request']) && isset($_POST['return_order_id'])) {
            
            $order_id = absint($_POST['return_order_id']);
            
            // 1. Biztonsági ellenőrzés: Nonce validáció
            if (!isset($_POST['elallas_nonce']) || !wp_verify_nonce($_POST['elallas_nonce'], 'elallas_action_' . $order_id)) {
                wc_add_notice(__('Biztonsági hiba történt. Kérjük, próbálja újra.', 'woo-elallas-kezelo'), 'error');
                return;
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
                wc_add_notice(__('Ezt a rendelést jelenleg nem lehet lemondani vagy az elállási folyamat már elkezdődött.', 'woo-elallas-kezelo'), 'error');
                return;
            }

            // Részleges elállás: termékek ellenőrzése
            $returned_items = isset($_POST['wejk_returned_products']) ? array_map('absint', $_POST['wejk_returned_products']) : array();
            if (empty($returned_items)) {
                wc_add_notice(__('Kérjük, válasszon ki legalább egy terméket az elálláshoz/lemondáshoz!', 'woo-elallas-kezelo'), 'error');
                return;
            }
            $order->update_meta_data('_wejk_returned_items', $returned_items);
            $order->save();

            // 3. E-mail és státusz előkészítése
            $admin_email = get_option('admin_email');
            $message = __('Kedves Admin!', 'woo-elallas-kezelo') . "\n\n";

            if ($is_pre_dispatch) {
                $target_status = get_option('wejk_pre_dispatch_action_status', 'cancelled');
                $order->update_status($target_status, __('A vásárló feladás előtt lemondta a rendelést a rendeléskövető felületen.', 'woo-elallas-kezelo'));
                
                $subject = sprintf(__('Feladás előtti lemondás (Elállás) - Rendelés #%s', 'woo-elallas-kezelo'), $order_id);
                $message .= __('Egy vásárló még a feladás előtt lemondta a rendelését (elállt a vásárlástól). Kérjük, NE add fel a csomagot!', 'woo-elallas-kezelo') . "\n\n";
            } else {
                // Alapértelmezett on-hold a teljesített utáni elállásra
                $order->update_status('on-hold', __('A vásárló elállást kezdeményezett a rendeléskövető felületen (feladás után).', 'woo-elallas-kezelo'));
                
                $subject = sprintf(__('Új elállási kérelem - Rendelés #%s', 'woo-elallas-kezelo'), $order_id);
                $message .= __('Egy vásárló élt az elállási jogával a weboldalon, miután a csomag feladásra került.', 'woo-elallas-kezelo') . "\n\n";
            }

            $message .= sprintf(__('Rendelésszám: %s', 'woo-elallas-kezelo'), $order_id) . "\n";
            $message .= sprintf(__('Vásárló neve: %s', 'woo-elallas-kezelo'), $order->get_formatted_billing_full_name()) . "\n";
            $message .= sprintf(__('Vásárló e-mail címe: %s', 'woo-elallas-kezelo'), $order->get_billing_email()) . "\n\n";
            
            $message .= __('Érintett termékek azonosítói (ID): ', 'woo-elallas-kezelo') . implode(', ', $returned_items) . "\n\n";
            
            if ($is_pre_dispatch) {
                $message .= sprintf(__('A rendelés státusza automatikusan a beállított \'%s\' állapotra módosult. Kérjük, intézkedj a visszatérítésről, ha a rendelés már fizetve lett.', 'woo-elallas-kezelo'), wc_get_order_status_name($target_status));
            } else {
                $message .= __('A rendelés státusza automatikusan \'Felfüggesztve\' állapotra módosult. Kérjük, vedd fel a kapcsolatot a vásárlóval a további teendőkkel kapcsolatban.', 'woo-elallas-kezelo');
            }
            
            wp_mail($admin_email, $subject, $message);

            // Vásárlói visszaigazoló e-mail küldése (WC_Email)
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            if (isset($emails['WEJK_Email_Customer_Withdrawal'])) {
                $emails['WEJK_Email_Customer_Withdrawal']->trigger($order_id, $is_pre_dispatch, $returned_items);
            }

            // 5. Átirányítás a sikeres képernyőre
            $redirect_url = add_query_arg('elallas_success', '1');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
