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
            <?php
            if (isset($_GET['wejk_error'])) {
                $error_msg = '';
                if ($_GET['wejk_error'] === 'no_products') {
                    $error_msg = __('Kérjük, válasszon ki legalább egy terméket az elálláshoz/lemondáshoz!', 'woo-elallas-kezelo');
                } elseif ($_GET['wejk_error'] === 'invalid_status') {
                    $error_msg = __('Ezt a rendelést jelenleg nem lehet lemondani vagy az elállási folyamat már elkezdődött.', 'woo-elallas-kezelo');
                } elseif ($_GET['wejk_error'] === 'security') {
                    $error_msg = __('Biztonsági hiba történt. Kérjük, próbálja újra.', 'woo-elallas-kezelo');
                }
                
                if (!empty($error_msg)) {
                    echo '<div class="woocommerce-error" style="background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 15px; margin-bottom: 20px;">' . esc_html($error_msg) . '</div>';
                }
            }
            ?>
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
                        echo '<div style="margin-bottom: 10px;">';
                        echo '<label for="wejk_item_' . esc_attr($item_id) . '" style="display: flex; align-items: center; cursor: pointer;">';
                        echo '<input type="checkbox" name="wejk_returned_products[]" value="' . esc_attr($item->get_product_id()) . '" id="wejk_item_' . esc_attr($item_id) . '">';
                        echo '<span style="margin-left: 8px;">' . esc_html($item->get_name()) . ' (x' . esc_html($item->get_quantity()) . ')</span>';
                        echo '</label>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <button type="submit" class="button alt wejk-submit-btn-<?php echo esc_attr($order->get_id()); ?>" name="process_return_request"><?php echo esc_html($btn_text); ?></button>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var btn = document.querySelector('.wejk-submit-btn-<?php echo esc_attr($order->get_id()); ?>');
                    if (btn) {
                        btn.addEventListener('click', function(e) {
                            var form = this.closest('form');
                            var checkboxes = form.querySelectorAll('input[name="wejk_returned_products[]"]:checked');
                            if (checkboxes.length === 0) {
                                e.preventDefault();
                                alert('<?php esc_attr_e('Kérjük, válasszon ki legalább egy terméket az elálláshoz/lemondáshoz!', 'woo-elallas-kezelo'); ?>');
                                return false;
                            }
                            if (!confirm('<?php echo esc_attr($confirm); ?>')) {
                                e.preventDefault();
                                return false;
                            }
                        });
                    }
                });
                </script>
            </form>
        </div>
        <?php
    }
}
