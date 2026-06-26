<?php
/**
 * Űrlap feldolgozásáért és logikáért felelős osztály
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Process {

    public function __construct() {
        // Sikeres képernyő megjelenítése a tartalom helyén, csak ha szükséges (így nincs felesleges overhead más oldalakon)
        if (isset($_GET['wejk_elallas_success'])) {
            add_filter('the_content', array($this, 'display_success_page'));
        }
        
        // KÉRÉS FELDOLGOZÁSA (STÁTUSZ VÁLTÁS, E-MAIL KÜLDÉS)
        add_action('template_redirect', array($this, 'process_return_request'));
    }

    public function display_success_page($content) {
        if (isset($_GET['wejk_elallas_success'])) {
            $order_id = absint($_GET['wejk_elallas_success']);
            if (!$order_id || !wc_get_order($order_id)) {
                return $content;
            }

            // Csak a fő lekérdezésben és a fő loopban cseréljük ki, elkerülve a menüket, oldalsávokat stb.
            if (!is_main_query() || !in_the_loop()) {
                return $content;
            }

            // Eltávolítjuk a szűrőt, hogy elkerüljük az esetleges többszörös lefutást
            remove_filter('the_content', array($this, 'display_success_page'));

            $default_msg = "Az elállási/lemondási nyilatkozatot sikeresen rögzítettük.\n\nA visszaigazolást, valamint a további teendőket elküldtük az e-mail címére.\n\n(Ez a jogszabályoknak megfelelő tartós adathordozónak minősül).";
            $success_message = get_option('wejk_success_message', $default_msg);

            ob_start();
            ?>
            <div class="woocommerce">
                <div class="woocommerce-MyAccount-content" style="width: 100%; max-width: 800px; margin: 50px auto; text-align: center;">
                    <div style="padding: 40px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; color: #155724;">
                        <h2 style="color: #155724; margin-bottom: 20px;"><?php esc_html_e('Nyilatkozat sikeresen beküldve', 'elallas-kezelo'); ?></h2>
                        <div style="font-size: 16px; margin-bottom: 25px; line-height: 1.6;">
                            <?php echo wp_kses_post(wpautop($success_message)); ?>
                        </div>
                        
                        <a href="<?php echo esc_url(home_url()); ?>" class="button button-primary"><?php esc_html_e('Vissza a főoldalra', 'elallas-kezelo'); ?></a>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        return $content;
    }

    public function process_return_request() {
        // Ellenőrizzük, hogy elküldték-e az űrlapot
        if (isset($_POST['process_return_request']) && isset($_POST['return_order_id'])) {
            
            $order_id = absint($_POST['return_order_id']);
            
            // 1. Biztonsági ellenőrzés: Nonce validáció
            $nonce = isset($_POST['elallas_nonce']) ? sanitize_text_field(wp_unslash($_POST['elallas_nonce'])) : '';
            if (empty($nonce) || !wp_verify_nonce($nonce, 'elallas_action_' . $order_id)) {
                $req_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $redirect_url = add_query_arg('wejk_error', 'security', remove_query_arg(array('wejk_elallas_success'), esc_url_raw($req_uri)));
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
                $req_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $redirect_url = add_query_arg('wejk_error', 'invalid_status', remove_query_arg(array('wejk_elallas_success'), esc_url_raw($req_uri)));
                wp_safe_redirect($redirect_url);
                exit;
            }

            // Részleges elállás: termékek ellenőrzése
            $returned_items = isset($_POST['wejk_returned_products']) ? array_map('absint', $_POST['wejk_returned_products']) : array();
            
            // Validáljuk a beküldött ID-kat a rendelés tételei (és termék ID-k a visszakompatibilitás végett) alapján
            $valid_ids = array();
            foreach ($order->get_items() as $item_id => $item) {
                $valid_ids[] = $item_id;
                $valid_ids[] = $item->get_product_id();
            }
            $returned_items = array_intersect($returned_items, $valid_ids);
            
            // 3. Már meglévő lemondások betöltése
            $existing_returned = $order->get_meta('_wejk_returned_items');
            if (!is_array($existing_returned)) {
                $existing_returned = array();
            }

            // 4. Szűrjük az újonnan beküldött listát (ne dolgozzuk fel újra azt, ami már le van mondva)
            $new_returned_items = array();
            foreach ($returned_items as $submitted_id) {
                $is_already_returned = false;
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    if ($submitted_id === $item_id || $submitted_id === $product_id) {
                        if (in_array($item_id, $existing_returned) || in_array($product_id, $existing_returned)) {
                            $is_already_returned = true;
                            break;
                        }
                    }
                }
                if (!$is_already_returned) {
                    $new_returned_items[] = $submitted_id;
                }
            }

            if (empty($new_returned_items)) {
                $req_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
                $redirect_url = add_query_arg('wejk_error', 'no_products', remove_query_arg(array('wejk_elallas_success'), esc_url_raw($req_uri)));
                wp_safe_redirect($redirect_url);
                exit;
            }

            // Összefűzzük az összeset a meta mentéshez
            $merged_returned_items = array_unique(array_merge($existing_returned, $new_returned_items));
            $order->update_meta_data('_wejk_returned_items', $merged_returned_items);
            
            // Részleges lemondás vizsgálata az összes lemondott termék alapján
            $returned_count = 0;
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                if (in_array($item_id, $merged_returned_items) || in_array($product_id, $merged_returned_items)) {
                    $returned_count++;
                }
            }
            $is_partial_return = $returned_count < count($order->get_items());

            if ($is_partial_return) {
                // Részleges esetén az új beállítás szerinti státusz
                $target_status = get_option('wejk_post_dispatch_action_status', 'on-hold');
                $order->update_status($target_status, __('Újabb részleges elállás/lemondás érkezett a rendeléskövető felületen.', 'elallas-kezelo'));
            } else {
                if ($is_pre_dispatch) {
                    $target_status = get_option('wejk_pre_dispatch_action_status', 'cancelled');
                    $order->update_status($target_status, __('A vásárló a maradék termékeket is lemondta feladás előtt a rendeléskövető felületen.', 'elallas-kezelo'));
                } else {
                    // Feladás utáni teljes elállás új beállítás szerinti státusza
                    $target_status = get_option('wejk_post_dispatch_action_status', 'on-hold');
                    $order->update_status($target_status, __('A vásárló a maradék termékekre is elállást kezdeményezett a rendeléskövető felületen.', 'elallas-kezelo'));
                }
            }

            // Rendelési Jegyzet (Order Note) hozzáadása a MOST lemondott terméklistával
            $note = __('Új lemondás/elállás beküldve. Érintett termékek:', 'elallas-kezelo') . "\n";
            foreach ($order->get_items() as $item_id => $item) {
                $item_pid = $item->get_product_id();
                if (in_array($item_id, $new_returned_items) || in_array($item_pid, $new_returned_items)) {
                    $note .= '- ' . $item->get_name() . ' (ID: ' . $item_pid . ')' . "\n";
                }
            }
            $order->add_order_note($note);
            
            $order->save();

            // E-mailek küldésének háttérbe (aszinkron) szervezése a gyorsabb válaszidő érdekében
            if ( function_exists( 'as_enqueue_async_action' ) ) {
                as_enqueue_async_action( 'wejk_process_withdrawal_emails', array(
                    $order_id,
                    $is_pre_dispatch,
                    $merged_returned_items,
                    $target_status,
                    $new_returned_items
                ));
            } else {
                // Tartalék megoldás (WP Cron), ha valamiért az Action Scheduler nem lenne elérhető
                wp_schedule_single_event( time(), 'wejk_process_withdrawal_emails_fallback', array(
                    $order_id, $is_pre_dispatch, $merged_returned_items, $target_status, $new_returned_items
                ) );
            }

            // 5. Átirányítás a különálló sikeres képernyőre (ugyanarra az URL-re, de success paraméterrel)
            $req_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $redirect_url = add_query_arg('wejk_elallas_success', $order_id, esc_url_raw($req_uri));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
