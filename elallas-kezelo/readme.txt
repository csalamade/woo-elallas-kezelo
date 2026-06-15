=== Elállás Kezelő for WooCommerce ===
Contributors: csalamade
Tags: woocommerce, refund, return, withdrawal
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 10.8
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A professional WooCommerce extension that automates customer withdrawals and order cancellations, designed to help webshops comply with consumer protection laws.

== Description ==

**Elállás Kezelő for WooCommerce** (Withdrawal Manager for WooCommerce) is a professional solution that automates customer withdrawals and order cancellations in your webshop. It is specifically designed to comply with the 2026 Hungarian consumer protection laws (14-day right of withdrawal) for individuals (B2C).

### Main features:

* **Native WooCommerce Integration:** No separate shortcode is needed! The extension fully integrates into the native WooCommerce **Order Tracking** (`[woocommerce_order_tracking]`) interface and the registered customers' **My Account -> Orders** page. The button and the form automatically appear below the order details.
* **Intelligent Deadline Calculation:** It differentiates between a cancellation before dispatch and a withdrawal after dispatch. After dispatch, it also takes into account the delivery lead time, so it knows exactly when the customer received the package.
* **Partial Returns:** The customer can decide which items from the order they want to return using checkboxes for each product.
* **Automated WooCommerce Emails:**
    * *Customer Confirmation:* Detailed list of the returned products and further instructions (functions as a durable medium).
    * *Admin Notification:* Separate email to the webshop administrator with the details.
    * Both emails can be customized under the official *WooCommerce -> Settings -> Emails* menu!
* **Admin Dashboard Enhancements:**
    * Separate menu item for settings (*WooCommerce -> Withdrawal Manager*).
    * Automatic order note created for every withdrawal.
    * Partially cancelled products get a prominent **[CANCELLED / WITHDRAWAL]** red label on the order edit screen.
    * Automatic order status updates (e.g., "Cancelled" for full cancellation, "On Hold" for partial).

### Technical Information
The extension follows the best WordPress and WooCommerce programming practices:
* **Speed Optimized:** Does not create unnecessary custom database tables, does not cause "autoload bloat".
* **HPOS Compatible:** Fully supports the new WooCommerce High-Performance Order Storage (HPOS) feature.
* **Reliable Validation:** In addition to client-side (JavaScript) checks, it also uses comprehensive server-side (PHP) validation and strict nonce security checks.

### Disclaimer / Jogi Nyilatkozat

**EN:** I developed the legal logic of this plugin to the best of my knowledge to help webshops comply with consumer protection laws. However, I assume no legal liability for any legal issues, fines, or damages arising from the use of this software. If anyone has any remarks, suggestions, or feedback regarding the legal compliance or functionality, please let me know, and I will do my best to implement or fix it.

**HU:** A bővítmény jogi és működési logikáját a legjobb tudásom szerint készítettem el, hogy maximálisan segítsek a webáruházaknak a jogszabályi megfelelésben. A szoftver használatából eredő esetleges jogi problémákért vagy büntetésekért azonban semmilyen felelősséget nem vállalok. Ha bárkinek bármilyen észrevétele, javaslata van – akár a jogi megfelelőséggel, akár a működéssel kapcsolatban –, kérlek jelezzétek, és igyekszem beépíteni, javítani!

== Installation ==

1. Upload the `elallas-kezelo` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Visit the *WooCommerce -> Withdrawal Manager* menu to configure the settings.
4. Ensure your email templates are configured under *WooCommerce -> Settings -> Emails*.

== Frequently Asked Questions ==

= Do I need to insert a separate shortcode? =
No, the extension automatically integrates into the WooCommerce Order Tracking and My Account -> Orders pages. (Note: Guest users will need access to a page with the `[woocommerce_order_tracking]` shortcode).

= Does it work with the new HPOS (High-Performance Order Storage) system? =
Yes, the extension is fully compatible with the new HPOS database structure.

== Screenshots ==

1. A megjelenő elállási/lemondási űrlap.

== Changelog ==

=1.0.5=
* bugfix


= 1.0.4 =
* bugfix

= 1.0.3 =
* upload wp repository

= 1.0.2 =
* change submit button name
* standardization


= 1.0.1 =
* Removed update checker for WordPress.org compliance.
* Improved security validations and nonce checks.
* Fixed text domains and strict HPOS declarations.

= 1.0.0 =
* Initial release.
