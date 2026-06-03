<?php
/**
 * Adminisztrációs felület beállításai
 */

if (!defined('ABSPATH')) {
    exit;
}

class WEJK_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    public function add_plugin_page() {
        add_submenu_page(
            'woocommerce',
            'Elállás Kezelő Beállítások',
            'Elállás Kezelő',
            'manage_woocommerce',
            'wejk-settings',
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WooCommerce Elállás Kezelő Beállítások', 'woo-elallas-kezelo'); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('wejk_option_group');
                do_settings_sections('wejk-setting-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting('wejk_option_group', 'wejk_pre_dispatch_statuses');
        register_setting('wejk_option_group', 'wejk_pre_dispatch_action_status');
        register_setting('wejk_option_group', 'wejk_shipped_status');
        register_setting('wejk_option_group', 'wejk_transit_time_days');

        add_settings_section(
            'wejk_setting_section',
            __('Rendelés státuszok és határidők', 'woo-elallas-kezelo'),
            array($this, 'section_info'),
            'wejk-setting-admin'
        );

        add_settings_field(
            'wejk_pre_dispatch_statuses',
            __('Feladás előtti elállást engedélyező státuszok', 'woo-elallas-kezelo'),
            array($this, 'pre_dispatch_statuses_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_pre_dispatch_action_status',
            __('Új státusz feladás előtti lemondáskor', 'woo-elallas-kezelo'),
            array($this, 'pre_dispatch_action_status_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_shipped_status',
            __('Teljesített (feladott) státusz', 'woo-elallas-kezelo'),
            array($this, 'shipped_status_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_transit_time_days',
            __('Szállítási átfutási idő (nap)', 'woo-elallas-kezelo'),
            array($this, 'transit_time_days_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_section(
            'wejk_email_setting_section',
            __('Vásárlói Visszaigazoló E-mail', 'woo-elallas-kezelo'),
            array($this, 'email_section_info'),
            'wejk-setting-admin'
        );
    }

    public function section_info() {
        echo esc_html__('Állítsd be, hogy mely rendelési státuszokban lehetséges az elállás és a rendelés lemondása.', 'woo-elallas-kezelo');
    }

    public function email_section_info() {
        $customer_email_url = admin_url('admin.php?page=wc-settings&tab=email&section=wejk_email_customer_withdrawal');
        $admin_email_url = admin_url('admin.php?page=wc-settings&tab=email&section=wejk_email_admin_withdrawal');
        
        echo '<p>' . esc_html__('A rendszer automatikusan értesíti a vásárlót és az adminisztrátort az elállásról a WooCommerce e-mail rendszerén keresztül.', 'woo-elallas-kezelo') . '</p>';
        echo '<p>';
        echo '<a href="' . esc_url($customer_email_url) . '" class="button button-secondary" style="margin-right: 10px;">' . esc_html__('Vásárlói e-mail testreszabása', 'woo-elallas-kezelo') . '</a>';
        echo '<a href="' . esc_url($admin_email_url) . '" class="button button-secondary">' . esc_html__('Admin értesítő testreszabása', 'woo-elallas-kezelo') . '</a>';
        echo '</p>';
    }

    public function pre_dispatch_statuses_callback() {
        $options = get_option('wejk_pre_dispatch_statuses', array('processing', 'pending'));
        if (!is_array($options)) {
            $options = array();
        }
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_pre_dispatch_statuses[]" multiple="multiple" style="width: 300px; height: 150px;">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            $selected = in_array($status_key, $options) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($status_key) . '" ' . $selected . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Több státusz is választható (Ctrl/Cmd lenyomásával). Ezekben a státuszokban a vásárló feladás előtt lemondhatja a rendelést.', 'woo-elallas-kezelo') . '</p>';
    }

    public function pre_dispatch_action_status_callback() {
        $option = get_option('wejk_pre_dispatch_action_status', 'cancelled');
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_pre_dispatch_action_status">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            $selected = ($option === $status_key) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($status_key) . '" ' . $selected . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Milyen státuszba kerüljön a rendelés, ha feladás előtt mondják le?', 'woo-elallas-kezelo') . '</p>';
    }

    public function shipped_status_callback() {
        $option = get_option('wejk_shipped_status', 'completed');
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_shipped_status">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            $selected = ($option === $status_key) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($status_key) . '" ' . $selected . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Amikor a rendelés ebbe a státuszba lép (pl. Teljesítve), a rendszer elkezdi számolni a 14 napot.', 'woo-elallas-kezelo') . '</p>';
    }

    public function transit_time_days_callback() {
        $option = get_option('wejk_transit_time_days', '2');
        echo '<input type="number" name="wejk_transit_time_days" value="' . esc_attr($option) . '" min="0" step="1" />';
        echo '<p class="description">' . esc_html__('Hány nap a szállítás? Ennyi nap adódik hozzá a feladási (státuszváltás) dátumához, hogy a 14 nap ténylegesen az átvételtől induljon.', 'woo-elallas-kezelo') . '</p>';
    }
}
