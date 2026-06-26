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
            <h1><?php esc_html_e('WooCommerce Elállás Kezelő Beállítások', 'elallas-kezelo'); ?></h1>
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
        register_setting('wejk_option_group', 'wejk_pre_dispatch_statuses', array('sanitize_callback' => array($this, 'sanitize_array_field')));
        register_setting('wejk_option_group', 'wejk_pre_dispatch_action_status', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wejk_option_group', 'wejk_post_dispatch_action_status', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wejk_option_group', 'wejk_shipped_status', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wejk_option_group', 'wejk_transit_time_days', array('sanitize_callback' => 'absint'));
        register_setting('wejk_option_group', 'wejk_success_message', array('sanitize_callback' => 'wp_kses_post'));
        register_setting('wejk_option_group', 'wejk_pre_dispatch_title', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wejk_option_group', 'wejk_pre_dispatch_desc', array('sanitize_callback' => 'sanitize_textarea_field'));
        register_setting('wejk_option_group', 'wejk_post_dispatch_title', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wejk_option_group', 'wejk_post_dispatch_desc', array('sanitize_callback' => 'sanitize_textarea_field'));

        add_settings_section(
            'wejk_setting_section',
            __('Rendelés státuszok és határidők', 'elallas-kezelo'),
            array($this, 'section_info'),
            'wejk-setting-admin'
        );

        add_settings_field(
            'wejk_pre_dispatch_statuses',
            __('Feladás előtti elállást engedélyező státuszok', 'elallas-kezelo'),
            array($this, 'pre_dispatch_statuses_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_pre_dispatch_action_status',
            __('Új státusz feladás előtti (teljes) lemondáskor', 'elallas-kezelo'),
            array($this, 'pre_dispatch_action_status_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_post_dispatch_action_status',
            __('Új státusz feladás utáni elálláskor (vagy részleges lemondáskor)', 'elallas-kezelo'),
            array($this, 'post_dispatch_action_status_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_shipped_status',
            __('Teljesített (feladott) státusz', 'elallas-kezelo'),
            array($this, 'shipped_status_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_field(
            'wejk_transit_time_days',
            __('Szállítási átfutási idő (nap)', 'elallas-kezelo'),
            array($this, 'transit_time_days_callback'),
            'wejk-setting-admin',
            'wejk_setting_section'
        );

        add_settings_section(
            'wejk_email_setting_section',
            __('Vásárlói Visszaigazoló E-mail', 'elallas-kezelo'),
            array($this, 'email_section_info'),
            'wejk-setting-admin'
        );

        add_settings_section(
            'wejk_text_setting_section',
            __('Felületi szövegek', 'elallas-kezelo'),
            array($this, 'text_section_info'),
            'wejk-setting-admin'
        );

        add_settings_field(
            'wejk_success_message',
            __('Sikeres beküldés üzenete', 'elallas-kezelo'),
            array($this, 'success_message_callback'),
            'wejk-setting-admin',
            'wejk_text_setting_section'
        );

        add_settings_field(
            'wejk_pre_dispatch_title',
            __('Feladás előtti űrlap címe', 'elallas-kezelo'),
            array($this, 'pre_dispatch_title_callback'),
            'wejk-setting-admin',
            'wejk_text_setting_section'
        );

        add_settings_field(
            'wejk_pre_dispatch_desc',
            __('Feladás előtti űrlap leírása', 'elallas-kezelo'),
            array($this, 'pre_dispatch_desc_callback'),
            'wejk-setting-admin',
            'wejk_text_setting_section'
        );

        add_settings_field(
            'wejk_post_dispatch_title',
            __('Feladás utáni űrlap címe', 'elallas-kezelo'),
            array($this, 'post_dispatch_title_callback'),
            'wejk-setting-admin',
            'wejk_text_setting_section'
        );

        add_settings_field(
            'wejk_post_dispatch_desc',
            __('Feladás utáni űrlap leírása', 'elallas-kezelo'),
            array($this, 'post_dispatch_desc_callback'),
            'wejk-setting-admin',
            'wejk_text_setting_section'
        );
    }

    public function sanitize_array_field($input) {
        if (!is_array($input)) {
            return array();
        }
        return array_map('sanitize_text_field', $input);
    }


    public function section_info() {
        echo esc_html__('Állítsd be, hogy mely rendelési státuszokban lehetséges az elállás és a rendelés lemondása.', 'elallas-kezelo');
        
        echo '<div class="notice notice-warning" style="margin-top: 15px; padding: 10px 15px; border-left: 4px solid #f0b849; background-color: #fbf8f2;">';
        echo '<strong>' . esc_html__('JOGSZABÁLYI ELŐÍRÁS (45/2014. Korm. rendelet 22. §):', 'elallas-kezelo') . '</strong><br>';
        echo esc_html__('Az elállási funkciót elérhetővé tévő gomb / menüpont nevének kötelezően tartalmaznia kell az ', 'elallas-kezelo') . '<strong>' . esc_html__('„Elállás a szerződéstől”', 'elallas-kezelo') . '</strong>' . esc_html__(' kifejezést, a beküldő gomb feliratának pedig pontosan az ', 'elallas-kezelo') . '<strong>' . esc_html__('„Elállás megerősítése”', 'elallas-kezelo') . '</strong>' . esc_html__(' szöveget kell viselnie. Kérjük, ennek megfelelően nevezze el a menüpontot vagy oldalt a webáruházában.', 'elallas-kezelo') . '<br>';
        echo '<span style="font-size: 0.9em; color: #666; display: block; margin-top: 5px;"><em>' . esc_html__('Figyelem: Ez a tájékoztatás kizárólag információs célokat szolgál, és nem minősül hivatalos jogi tanácsadásnak.', 'elallas-kezelo') . '</em></span>';
        echo '</div>';

        echo '<div class="notice notice-info" style="margin-top: 15px; padding: 10px 15px;">';
        echo '<strong>' . esc_html__('Vendég vásárlók tájékoztatása:', 'elallas-kezelo') . '</strong> ';
        echo esc_html__('A regisztráció nélkül vásárló vendégek az alapértelmezett WooCommerce rendeléskövető felületen keresztül indíthatják el az elállási folyamatot. Ennek biztosításához kérjük, hozzon létre egy új oldalt (pl. "Elállás a szerződéstől / Rendelés követése"), majd illessze be a ', 'elallas-kezelo');
        echo '<code>[woocommerce_order_tracking]</code>';
        echo esc_html__(' shortcode-ot a tartalomba.', 'elallas-kezelo');
        echo '</div>';
    }

    public function text_section_info() {
        echo esc_html__('A weboldalon megjelenő egyedi szövegek beállításai.', 'elallas-kezelo');
    }

    public function email_section_info() {
        $customer_email_url = admin_url('admin.php?page=wc-settings&tab=email&section=wejk_email_customer_withdrawal');
        $admin_email_url = admin_url('admin.php?page=wc-settings&tab=email&section=wejk_email_admin_withdrawal');
        
        echo '<p>' . esc_html__('A rendszer automatikusan értesíti a vásárlót és az adminisztrátort az elállásról a WooCommerce e-mail rendszerén keresztül.', 'elallas-kezelo') . '</p>';
        echo '<p>';
        echo '<a href="' . esc_url($customer_email_url) . '" class="button button-secondary" style="margin-right: 10px;">' . esc_html__('Vásárlói e-mail testreszabása', 'elallas-kezelo') . '</a>';
        echo '<a href="' . esc_url($admin_email_url) . '" class="button button-secondary">' . esc_html__('Admin értesítő testreszabása', 'elallas-kezelo') . '</a>';
        echo '</p>';
    }

    public function pre_dispatch_statuses_callback() {
        $options = get_option('wejk_pre_dispatch_statuses', array('processing', 'pending'));
        if (!is_array($options)) {
            $options = array();
        }
        $statuses = wc_get_order_statuses();
        echo '<div class="wejk-checkbox-group" style="max-height: 180px; overflow-y: auto; border: 1px solid #8c8f94; padding: 10px; background: #fff; max-width: 400px; border-radius: 4px;">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            echo '<label style="display: block; margin-bottom: 8px; cursor: pointer; align-items: center;">';
            echo '<input type="checkbox" name="wejk_pre_dispatch_statuses[]" value="' . esc_attr($status_key) . '" ' . checked(in_array($status_key, $options), true, false) . ' style="margin-right: 8px; margin-top: 0;"> ';
            echo esc_html($status);
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description" style="margin-top: 10px;">' . esc_html__('Több státusz is választható. Ezekben a státuszokban a vásárló feladás előtt lemondhatja a rendelést.', 'elallas-kezelo') . '</p>';
    }

    public function pre_dispatch_action_status_callback() {
        $option = get_option('wejk_pre_dispatch_action_status', 'cancelled');
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_pre_dispatch_action_status">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            echo '<option value="' . esc_attr($status_key) . '" ' . selected($option, $status_key, false) . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Milyen státuszba kerüljön a rendelés, ha feladás előtt a teljes rendelést lemondják?', 'elallas-kezelo') . '</p>';
    }

    public function post_dispatch_action_status_callback() {
        $option = get_option('wejk_post_dispatch_action_status', 'on-hold');
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_post_dispatch_action_status">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            echo '<option value="' . esc_attr($status_key) . '" ' . selected($option, $status_key, false) . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Ide kerül a rendelés, ha a csomag már fel van adva és várni kell a visszaküldésre, VAGY ha csak részleges volt a lemondás. Ha zavaró az alapértelmezett "Felfüggesztve", nyugodtan válassz másikat.', 'elallas-kezelo') . '</p>';
    }

    public function shipped_status_callback() {
        $option = get_option('wejk_shipped_status', 'completed');
        $statuses = wc_get_order_statuses();
        echo '<select name="wejk_shipped_status">';
        foreach ($statuses as $key => $status) {
            $status_key = str_replace('wc-', '', $key);
            echo '<option value="' . esc_attr($status_key) . '" ' . selected($option, $status_key, false) . '>' . esc_html($status) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Amikor a rendelés ebbe a státuszba lép (pl. Teljesítve), a rendszer elkezdi számolni a 14 napot.', 'elallas-kezelo') . '</p>';
    }

    public function transit_time_days_callback() {
        $option = get_option('wejk_transit_time_days', '2');
        echo '<input type="number" name="wejk_transit_time_days" value="' . esc_attr($option) . '" min="0" step="1" />';
        echo '<p class="description">' . esc_html__('Hány nap a szállítás? Ennyi nap adódik hozzá a feladási (státuszváltás) dátumához, hogy a 14 nap ténylegesen az átvételtől induljon.', 'elallas-kezelo') . '</p>';
    }

    public function success_message_callback() {
        $default_msg = "Az elállási/lemondási nyilatkozatot sikeresen rögzítettük.\n\nA visszaigazolást, valamint a további teendőket elküldtük az e-mail címére.\n\n(Ez a jogszabályoknak megfelelő tartós adathordozónak minősül).";
        $option = get_option('wejk_success_message', $default_msg);
        echo '<textarea name="wejk_success_message" rows="6" cols="60" style="width: 100%; max-width: 600px;">' . esc_textarea($option) . '</textarea>';
        echo '<p class="description">' . esc_html__('Ez a szöveg jelenik meg a vásárlónak, miután sikeresen elküldte az űrlapot. Hagyományos sortöréseket és egyszerű HTML kódokat (pl. &lt;strong&gt;) is használhatsz.', 'elallas-kezelo') . '</p>';
    }

    public function pre_dispatch_title_callback() {
        $option = get_option('wejk_pre_dispatch_title', __('Elállás a szerződéstől', 'elallas-kezelo'));
        echo '<input type="text" name="wejk_pre_dispatch_title" value="' . esc_attr($option) . '" style="width: 100%; max-width: 600px;" />';
    }
 
    public function pre_dispatch_desc_callback() {
        $default_desc = __('Rendelése még feladás előtt áll, így most gyorsan és egyszerűen lemondhatja. A 45/2014. (II. 26.) Korm. rendelet 20. § (2) bek. alapján ez jogilag is elállásnak minősül.', 'elallas-kezelo');
        $option = get_option('wejk_pre_dispatch_desc', $default_desc);
        echo '<textarea name="wejk_pre_dispatch_desc" rows="3" cols="60" style="width: 100%; max-width: 600px;">' . esc_textarea($option) . '</textarea>';
    }
 
    public function post_dispatch_title_callback() {
        $option = get_option('wejk_post_dispatch_title', __('Elállás a szerződéstől', 'elallas-kezelo'));
        echo '<input type="text" name="wejk_post_dispatch_title" value="' . esc_attr($option) . '" style="width: 100%; max-width: 600px;" />';
    }

    public function post_dispatch_desc_callback() {
        $default_desc = __('Erre a rendelésre még érvényes a 14 napos elállási jog. A gombra kattintva jelezheti felénk visszaküldési szándékát.', 'elallas-kezelo');
        $option = get_option('wejk_post_dispatch_desc', $default_desc);
        echo '<textarea name="wejk_post_dispatch_desc" rows="3" cols="60" style="width: 100%; max-width: 600px;">' . esc_textarea($option) . '</textarea>';
    }
}
