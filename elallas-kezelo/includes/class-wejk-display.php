<?php

/**
 * Megjelenítésért felelős osztály
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Display
{

    public function __construct()
    {
        // GOMB MEGJELENÍTÉSE A RENDELÉS ADATAI ALATT
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_return_button'), 10, 1);
    }

    public function display_return_button($order)
    {
        // Elrejtés a sikeres rendelés (köszönő) oldalról
        if (function_exists('is_order_received_page') && is_order_received_page()) {
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

                // Csak akkor számít lejártnak, ha eltelt 14 nap a kalkulált átvételi időpont óta
                $days_passed = (time() - $start_time) / DAY_IN_SECONDS;
                if ($days_passed > 14) {
                    echo '<div class="woocommerce-info">' . esc_html__('Az indokolás nélküli 14 napos elállási határidő erre a rendelésre lejárt.', 'elallas-kezelo') . '</div>';
                    return;
                }
            }
        }

        // Gomb és az elrejtett űrlap
        if ($is_pre_dispatch) {
            $title = get_option('wejk_pre_dispatch_title', __('Elállás a szerződéstől', 'elallas-kezelo'));
            $desc = get_option('wejk_pre_dispatch_desc', __('Rendelése még feladás előtt áll, így most gyorsan és egyszerűen lemondhatja. A 45/2014. (II. 26.) Korm. rendelet 20. § (2) bek. alapján ez jogilag is elállásnak minősül.', 'elallas-kezelo'));
            $btn_text = __('Elállás megerősítése', 'elallas-kezelo');
            $confirm = __('Biztosan szeretné lemondani a rendelést még a feladás előtt?', 'elallas-kezelo');
        } else {
            $title = get_option('wejk_post_dispatch_title', __('Elállás a szerződéstől', 'elallas-kezelo'));
            $desc = get_option('wejk_post_dispatch_desc', __('Erre a rendelésre még érvényes a 14 napos elállási jog. A gombra kattintva jelezheti felénk visszaküldési szándékát.', 'elallas-kezelo'));
            $btn_text = __('Elállás megerősítése', 'elallas-kezelo');
            $confirm = __('Biztosan szeretné kezdeményezni a rendelés visszaküldését?', 'elallas-kezelo');
        }
?>
        <div class="wejk-return-container" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #007cba;">
            <?php
            $wejk_error = '';
            if (isset($_GET['wejk_error'])) {
                $wejk_error = sanitize_key(wp_unslash($_GET['wejk_error']));
            }

            if ($wejk_error === 'no_products') {
                $error_msg = __('Kérjük, válasszon ki legalább egy terméket az elálláshoz/lemondáshoz!', 'elallas-kezelo');
            } elseif ($wejk_error === 'invalid_status') {
                $error_msg = __('Ezt a rendelést jelenleg nem lehet lemondani vagy az elállási folyamat már elkezdődött.', 'elallas-kezelo');
            } elseif ($wejk_error === 'security') {
                $error_msg = __('Biztonsági hiba történt. Kérjük, próbálja újra.', 'elallas-kezelo');
            } else {
                $error_msg = '';
            }

            if (!empty($error_msg)) {
                echo '<div class="woocommerce-error" style="background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 15px; margin-bottom: 20px;">' . esc_html($error_msg) . '</div>';
            }
            ?>
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($desc); ?></p>
            <form method="post" action="">
                <!-- Biztonsági kulcs generálása specifikusan ehhez a rendeléshez -->
                <?php wp_nonce_field('elallas_action_' . $order->get_id(), 'elallas_nonce'); ?>
                <input type="hidden" name="return_order_id" value="<?php echo esc_attr($order->get_id()); ?>">

                <div class="wejk-consumer-data" style="margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ddd;">
                    <h4 style="margin-top: 0;"><?php esc_html_e('Nyilatkozattevő adatai:', 'elallas-kezelo'); ?></h4>
                    <p style="margin: 0; line-height: 1.6;">
                        <strong><?php esc_html_e('Név:', 'elallas-kezelo'); ?></strong> <?php echo esc_html($order->get_billing_last_name() . ' ' . $order->get_billing_first_name()); ?><br>
                        <strong><?php esc_html_e('E-mail cím:', 'elallas-kezelo'); ?></strong> <?php echo esc_html($order->get_billing_email()); ?><br>
                        <strong><?php esc_html_e('Rendelésszám:', 'elallas-kezelo'); ?></strong> <?php echo esc_html($order->get_order_number()); ?>
                    </p>
                </div>

                <div class="wejk-product-list" style="margin: 15px 0; padding: 15px; background: #fff; border: 1px solid #ddd;">

                    <?php
                    $existing_returned = $order->get_meta('_wejk_returned_items');
                    if (!is_array($existing_returned)) {
                        $existing_returned = array();
                    }

                    // Ellenőrizzük, hogy minden termék le van-e már mondva
                    $all_returned = true;
                    foreach ($order->get_items() as $item_id => $item) {
                        $item_pid = $item->get_product_id();
                        if (!in_array($item_id, $existing_returned) && !in_array($item_pid, $existing_returned)) {
                            $all_returned = false;
                            break;
                        }
                    }

                    if ($all_returned) {
                        echo '<p style="color: #155724; font-weight: bold;">' . esc_html__('Ebből a rendelésből már minden tételt lemondott / visszaküldött.', 'elallas-kezelo') . '</p>';
                        echo '</div></form></div>';
                        return; // Kilépünk, nem mutatjuk a beküldés gombot
                    }
                    ?>

                    <h4 style="margin-top: 0;"><?php esc_html_e('Válassza ki az érintett termékeket:', 'elallas-kezelo'); ?></h4>
                    <?php
                    foreach ($order->get_items() as $item_id => $item) {
                        $product_id = $item->get_product_id();
                        $is_returned = in_array($item_id, $existing_returned) || in_array($product_id, $existing_returned);
                        $opacity = $is_returned ? '0.6' : '1';
 
                        echo '<div style="margin-bottom: 10px; opacity: ' . esc_attr($opacity) . ';">';
                        echo '<label for="wejk_item_' . esc_attr($item_id) . '" style="display: flex; align-items: center; cursor: pointer;">';
                        echo '<input type="checkbox" name="wejk_returned_products[]" value="' . esc_attr($item_id) . '" id="wejk_item_' . esc_attr($item_id) . '" ' . disabled($is_returned, true, false) . ' ' . checked($is_returned, true, false) . '>';
                        echo '<span style="margin-left: 8px;">' . esc_html($item->get_name()) . ' (x' . esc_html($item->get_quantity()) . ')</span>';
                        if ($is_returned) {
                            echo '<span style="margin-left: 10px; color: #dc3232; font-size: 0.9em; font-weight: bold;">' . esc_html__('(Már lemondva)', 'elallas-kezelo') . '</span>';
                        }
                        echo '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <button type="submit" class="button alt wejk-submit-btn" name="process_return_request" data-confirm="<?php echo esc_attr($confirm); ?>" data-alert="<?php esc_attr_e('Kérjük, válasszon ki legalább egy terméket az elálláshoz/lemondáshoz!', 'elallas-kezelo'); ?>"><?php echo esc_html($btn_text); ?></button>
            </form>
        </div>
<?php
    }
}
