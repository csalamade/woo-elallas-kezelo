# AI Context for "WooCommerce Elállás Kezelő"

This document provides context for AI assistants working on this WordPress/WooCommerce plugin.

## Purpose
"WooCommerce Elállás Kezelő" is a custom WordPress plugin that allows WooCommerce customers (both logged-in and guests) to submit cancellation (pre-dispatch) or withdrawal (post-dispatch) requests for their orders. It is designed to comply with EU/Hungarian consumer protection laws regarding the 14-day right of withdrawal.

## Core Features & Logic
- **Native Integration**: Seamlessly hooks into WooCommerce's built-in order tracking (`[woocommerce_order_tracking]`) and the 'My Account' -> 'Orders' area. No custom shortcode is needed.
- **Pre-dispatch vs. Post-dispatch**:
  - Based on order status (configured in settings).
  - Transit time (days) is factored in for post-dispatch orders to calculate the 14-day window accurately from the day of receipt.
- **Partial Returns**:
  - The form dynamically lists order items with checkboxes. Checkboxes are *unchecked* by default to prevent accidental full-returns.
  - If a user returns only specific items, it is considered a "partial return" (`$is_partial_return = true`).
  - Order status for partial returns is hardcoded to `on-hold` to allow admins to manually adjust the order.
  - Order status for full returns depends on whether it's pre-dispatch (uses a setting like `cancelled`) or post-dispatch (defaults to `on-hold`).
- **Data Storage**:
  - Returned item IDs are stored as an array in order meta: `_wejk_returned_items`.
  - The plugin does not create custom database tables; it uses standard WordPress/WooCommerce metadata, avoiding performance issues.
- **Admin Order UI**:
  - Automatically adds an Order Note detailing the withdrawal type and returned items.
  - Hooks into `woocommerce_before_order_itemmeta` to display a red `[LEMONDVA / ELÁLLÁS]` badge under cancelled items in the admin order edit screen.
- **WooCommerce Emails**:
  - Integrates directly into WooCommerce's email system (Settings -> Emails).
  - `WEJK_Email_Customer_Withdrawal`: Sent to the customer.
  - `WEJK_Email_Admin_Withdrawal`: Sent to the admin.
  - Both classes check `$this->returned_items` and fallback to `_wejk_returned_items` meta to ensure they only list the items actually returned.
- **Admin Settings Page**:
  - Located under WooCommerce -> Elállás Kezelő.
  - Configurable statuses (pre-dispatch, shipped, target action status).
  - Transit time.
  - Editable success message (uses standard `wp_options` textarea without slowing down the site).
- **Validation**:
  - Server-side validation handles empty submissions by redirecting with `?wejk_error=no_products`.
  - The frontend reads this URL parameter and renders a native WooCommerce-styled error notice above the form (bypassing WC sessions, which are unreliable for guests or cached pages).

## File Structure
- `woo-elallas-kezelo.php`: Main plugin file. Handles initialization, `init_hooks()`, class loading.
- `includes/class-wejk-admin.php`: Admin settings page (`WEJK_Admin`).
- `includes/class-wejk-admin-order.php`: Admin UI hooks for the Order edit screen (`WEJK_Admin_Order`).
- `includes/class-wejk-display.php`: Frontend shortcode and form rendering (`WEJK_Display`).
- `includes/class-wejk-process.php`: Form submission handling, validation, order meta saving, and status updates (`WEJK_Process`).
- `includes/emails/class-wejk-email-admin-withdrawal.php`: Admin WC_Email class.
- `includes/emails/class-wejk-email-customer-withdrawal.php`: Customer WC_Email class.

## Future Development Notes
- Always prioritize WooCommerce core functions (e.g., `wc_get_order()`, `$order->update_meta_data()`).
- Keep UI strings translatable using the `woo-elallas-kezelo` text domain.
- Do not add features that cause "autoload bloat" in `wp_options`.
- Always consider High-Performance Order Storage (HPOS) compatibility (use CRUD methods like `$order->save()`, not direct SQL).
