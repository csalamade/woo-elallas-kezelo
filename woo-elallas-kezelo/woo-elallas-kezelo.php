<?php
/**
 * Plugin Name:       WooCommerce Elállási Kezelő
 * Plugin URI:        https://github.com/csalamade/woo-elallas-kezelo
 * Description:       Automatizált, 2026-os jogszabályoknak megfelelő elállási gomb és folyamatkezelő magánszemélyek (B2C) számára.
 * Version:           1.0.0
 * Author:            1st-tech
 * Author URI:        https://1st-tech.hu
 * Text Domain:       woo-elallas-kezelo
 */

// Megakadályozzuk a közvetlen hozzáférést
if (!defined('ABSPATH')) {
    exit;
}

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/csalamade/woo-elallas-kezelo/',
    __FILE__,
    'woo-elallas-kezelo'
);

$myUpdateChecker->getVcsApi()->enableReleaseAssets();




class WEJK_Plugin_Core {

    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Alapvető útvonalak definiálása a betöltéshez
        $plugin_dir = plugin_dir_path(__FILE__);

        // Megjelenítésért felelős osztály
        require_once $plugin_dir . 'includes/class-wejk-display.php';
        
        // Feldolgozásért felelős osztály
        require_once $plugin_dir . 'includes/class-wejk-process.php';

        // Adminisztrációs felület (csak ha adminban vagyunk)
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-wejk-admin.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-wejk-admin-order.php';
        }
    }

    private function init_hooks() {
        // Osztályok példányosítása (ezzel be is regisztráljuk a hookokat a konstruktorukon keresztül)
        new WEJK_Display();
        new WEJK_Process();

        if (is_admin()) {
            new WEJK_Admin();
            new WEJK_Admin_Order();
        }

        // Státuszváltás figyelése a _wejk_shipped_date mentéséhez
        add_action('woocommerce_order_status_changed', array($this, 'save_shipped_date'), 10, 4);
        
        // E-mail osztály regisztrálása
        add_filter('woocommerce_email_classes', array($this, 'register_emails'));

        // Aszinkron (háttérben futó) e-mail küldés hook-ok regisztrálása
        add_action('wejk_process_withdrawal_emails', array($this, 'send_withdrawal_emails_async'), 10, 1);
        add_action('wejk_process_withdrawal_emails_fallback', array($this, 'send_withdrawal_emails_fallback'), 10, 5);
    }

    public function send_withdrawal_emails_async($args) {
        $this->execute_email_sending(
            $args['order_id'], 
            $args['is_pre_dispatch'], 
            $args['merged_returned_items'], 
            $args['target_status'], 
            $args['new_returned_items']
        );
    }

    public function send_withdrawal_emails_fallback($order_id, $is_pre_dispatch, $merged_returned_items, $target_status, $new_returned_items) {
        $this->execute_email_sending($order_id, $is_pre_dispatch, $merged_returned_items, $target_status, $new_returned_items);
    }

    private function execute_email_sending($order_id, $is_pre_dispatch, $merged_returned_items, $target_status, $new_returned_items) {
        // Inicializáljuk a mailert, ha még nem történt meg (háttérfolyamatokban előfordulhat)
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();
        
        if (isset($emails['WEJK_Email_Admin_Withdrawal'])) {
            $emails['WEJK_Email_Admin_Withdrawal']->trigger($order_id, $is_pre_dispatch, $merged_returned_items, $target_status);
        }

        if (isset($emails['WEJK_Email_Customer_Withdrawal'])) {
            $emails['WEJK_Email_Customer_Withdrawal']->trigger($order_id, $is_pre_dispatch, $new_returned_items);
        }
    }

    public function register_emails($emails) {
        require_once plugin_dir_path(__FILE__) . 'includes/emails/class-wejk-email-customer-withdrawal.php';
        $emails['WEJK_Email_Customer_Withdrawal'] = new WEJK_Email_Customer_Withdrawal();
        
        require_once plugin_dir_path(__FILE__) . 'includes/emails/class-wejk-email-admin-withdrawal.php';
        $emails['WEJK_Email_Admin_Withdrawal'] = new WEJK_Email_Admin_Withdrawal();
        
        return $emails;
    }

    public function save_shipped_date($order_id, $from_status, $to_status, $order) {
        $shipped_status = get_option('wejk_shipped_status', 'completed');
        
        if ($to_status === $shipped_status) {
            // Rögzítjük az aktuális időbélyeget
            $order->update_meta_data('_wejk_shipped_date', time());
            $order->save();
        }
    }
}

// Bővítmény inicializálása
function wejk_init_plugin() {
    // Ellenőrizzük, hogy a WooCommerce aktív-e
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    new WEJK_Plugin_Core();
}
// Csak akkor induljon el a bővítmény, ha a pluginok betöltöttek
add_action('plugins_loaded', 'wejk_init_plugin');