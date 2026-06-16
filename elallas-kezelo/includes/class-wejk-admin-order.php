<?php
/**
 * Admin rendelés szerkesztő felület kiegészítései
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Admin_Order {

    public function __construct() {
        // Piros jelvény (badge) hozzáadása a visszaküldött/lemondott termékek alá az admin rendelés részletezőben
        add_action('woocommerce_before_order_itemmeta', array($this, 'display_returned_badge'), 10, 3);
    }

    /**
     * @param int $item_id
     * @param WC_Order_Item_Product $item
     * @param WC_Product $product
     */
    public function display_returned_badge($item_id, $item, $product) {
        if (!$item || !is_a($item, 'WC_Order_Item_Product')) {
            return;
        }

        $order_id = $item->get_order_id();
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $returned_items = $order->get_meta('_wejk_returned_items');

        // Ha a rendelésben vannak visszaküldött/lemondott termékek
        if (is_array($returned_items) && !empty($returned_items)) {
            $product_id = $item->get_product_id();
            
            // Ha ez a konkrét termék benne van a listában
            if (in_array($item_id, $returned_items) || in_array($product_id, $returned_items)) {
                echo '<div style="margin-top: 5px;"><span style="background: #dc3232; color: #fff; padding: 3px 6px; border-radius: 3px; font-size: 11px; font-weight: bold; text-transform: uppercase;">' . esc_html__('Lemondva / Elállás', 'elallas-kezelo') . '</span></div>';
            }
        }
    }
}
