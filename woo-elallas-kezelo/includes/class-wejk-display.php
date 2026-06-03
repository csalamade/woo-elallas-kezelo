<?php
/**
 * Megjelenítésért felelős osztály
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Display {

    public function __construct() {
        // GOMB MEGJELENÍTÉSE A RENDELÉS ADATAI ALATT
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_return_button'), 10, 1);
    }

    public function display_return_button($order) {
        $pre_dispatch_statuses = get_option('wejk_pre_dispatch_statuses', array('processing', 'pending'));
        if (!is_array($pre_dispatch_statuses)) {
            $pre_dispatch_statuses = array();
        }
        $shipped_status = get_option('wejk_shipped_status', 'completed');

        $current_status = $order->get_status();
        $is_pre_dispatch = in_array($current_status, $pre_dispatch_statuses);
        $is_shipped = ($current_status === $shipped_status);

        // Csak engedélyezett státuszoknál
        if (!$is_pre_dispatch && !$is_shipped) {
            return;
        }

        // Csak magánszemélyeknek (ha a cégnév ki van töltve, kilépünk)
        if (!empty($order->get_billing_company())) {
            return;
        }

        // 14 napos szabály ellenőrzése CSAK ha feladott státuszban van
        if ($is_shipped) {
            $shipped_date = $order->get_meta('_wejk_shipped_date');
            
            // Ha valamiért nincs rögzítve (pl. régi rendelés), használjuk az eredeti completed dátumot fallback-ként
            if (!$shipped_date) {
                $completed_date_obj = $order->get_date_completed();
                $shipped_date = $completed_date_obj ? $completed_date_obj->getOffsetTimestamp() : false;
            }
            
            if ($shipped_date) {
                $transit_days = (int) get_option('wejk_transit_time_days', 2);
                $start_time = $shipped_date + ($transit_days * DAY_IN_SECONDS);
                
                // Csak akkor számít lejártnak, ha el is kezdődött az időszak, ÉS eltelt 14 nap
                if (time() >= $start_time) {
                    $days_passed = (time() - $start_time) / DAY_IN_SECONDS;
                    if ($days_passed > 14) {
                        echo '<div class="woocommerce-info">' . esc_html__('Az indokolás nélküli 14 napos elállási határidő erre a rendelésre lejárt.', 'woo-elallas-kezelo') . '</div>';
                        return;
                    }
                }
            }
        }

        // Gomb és az elrejtett űrlap
        if (isset($_GET['elallas_success']) && $_GET['elallas_success'] == '1') {
            ?>
            <div class="wejk-return-container" style="margin-top: 30px; padding: 20px; background: #d4edda; border-left: 4px solid #28a745; color: #155724;">
                <h3><?php esc_html_e('Nyilatkozat sikeresen beküldve', 'woo-elallas-kezelo'); ?></h3>
                <p><?php esc_html_e('Az elállási/lemondási nyilatkozatot sikeresen rögzítettük. Az ezzel kapcsolatos visszaigazolást, valamint a további teendőket elküldtük az e-mail címére (ez a jogszabályoknak megfelelő tartós adathordozónak minősül).', 'woo-elallas-kezelo'); ?></p>
            </div>
            <?php
            return;
        }

        if ($is_pre_dispatch) {
            $title = __('Szeretné lemondani a rendelését?', 'woo-elallas-kezelo');
            $desc = __('Rendelése még feladás előtt áll, így most gyorsan és egyszerűen lemondhatja. A 45/2014. (II. 26.) Korm. rendelet 20. § (2) bek. alapján ez jogilag is elállásnak minősül.', 'woo-elallas-kezelo');
            $btn_text = __('Rendelés lemondása (Elállás)', 'woo-elallas-kezelo');
            $confirm = __('Biztosan szeretné lemondani a rendelést még a feladás előtt?', 'woo-elallas-kezelo');
        } else {
            $title = __('Szeretne élni az elállási jogával?', 'woo-elallas-kezelo');
            $desc = __('Erre a rendelésre még érvényes a 14 napos elállási jog. A gombra kattintva jelezheti felénk visszaküldési szándékát.', 'woo-elallas-kezelo');
            $btn_text = __('Elállás bejelentése', 'woo-elallas-kezelo');
            $confirm = __('Biztosan szeretné kezdeményezni a rendelés visszaküldését?', 'woo-elallas-kezelo');
        }
        ?>
        <div class="wejk-return-container" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #007cba;">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($desc); ?></p>
            <form method="post" action="">
                <!-- Biztonsági kulcs generálása specifikusan ehhez a rendeléshez -->
                <?php wp_nonce_field('elallas_action_' . $order->get_id(), 'elallas_nonce'); ?>
                <input type="hidden" name="return_order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                
                <div class="wejk-product-list" style="margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ddd;">
                    <h4 style="margin-top: 0;"><?php esc_html_e('Válassza ki az érintett termékeket:', 'woo-elallas-kezelo'); ?></h4>
                    <?php
                    foreach ($order->get_items() as $item_id => $item) {
                        $product = $item->get_product();
                        $product_id = $product ? $product->get_id() : 0;
                        ?>
                        <label style="display: block; margin-bottom: 5px; cursor: pointer;">
                            <input type="checkbox" name="wejk_returned_products[]" value="<?php echo esc_attr($product_id); ?>" checked>
                            <?php echo esc_html($item->get_name()) . ' (' . esc_attr($item->get_quantity()) . ' db)'; ?>
                        </label>
                        <?php
                    }
                    ?>
                </div>

                <button type="submit" class="button alt" name="process_return_request" onclick="return confirm('<?php echo esc_attr($confirm); ?>');"><?php echo esc_html($btn_text); ?></button>
            </form>
        </div>
        <?php
    }
}
