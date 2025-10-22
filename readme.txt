=== LearnDash FluentCart Integration ===
Contributors: wbcomdesigns
Tags: learndash, fluentcart, lms, ecommerce, course enrollment
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate LearnDash LMS with FluentCart to automatically enroll customers in courses and groups upon purchase.

== Description ==

LearnDash FluentCart Integration automatically enrolls customers in LearnDash courses and groups when they purchase products through FluentCart. Perfect for selling online courses, memberships, and training programs.

= Core Features =

* **Automatic Course Enrollment** - Enroll customers in LearnDash courses upon purchase
* **Automatic Group Enrollment** - Enroll customers in LearnDash groups for membership access
* **Multiple Product Support** - Configure different courses/groups for each product
* **Multi-Select Options** - Assign multiple courses or groups to a single product

= Payment & Order Management =

* Works with all payment methods (online, cash on delivery, offline)
* Automatic enrollment on payment confirmation
* Supports order status tracking (completed, processing, paid)
* Compatible with manually created orders

= Subscription Support =

Full subscription lifecycle management:

* Subscription Activated - Enroll on subscription start
* Subscription Renewed - Re-enroll on renewal
* Subscription Canceled - Remove access on cancellation
* Subscription Expired - Remove access on expiration
* Subscription End of Term - Remove access at term end

= Refund & Access Management =

* **Smart Refund Handling** - Automatically remove access on refunds
* **Access Counter System** - Tracks multiple purchases to prevent premature removal
* **Configurable Removal** - Choose whether to remove access on refund per product
* **Multiple Purchase Protection** - Access persists until all purchases are refunded

= Developer Features =

* Debug logging for troubleshooting (when WP_DEBUG enabled)
* Priority hook execution for reliability
* Comprehensive error handling
* Translation ready with `learndash-fluentcart` text domain
* WordPress Coding Standards compliant

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/learndash-fluentcart/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure both LearnDash LMS and FluentCart are installed and activated
4. Configure integration settings in FluentCart → Products → Edit Product → Integrations tab

== Frequently Asked Questions ==

= Does this work with all FluentCart payment methods? =

Yes, the plugin works with online payments, cash on delivery, and offline payment methods.

= Can I assign multiple courses to one product? =

Yes, you can select multiple courses and/or groups for each product using the multi-select options.

= What happens when a customer gets a refund? =

If "Remove Access on Refund" is enabled, the plugin uses an access counter system. Access is only removed when all purchases of that product are refunded. If the customer bought the same product twice, access remains until both are refunded.

= Does it support subscriptions? =

Yes, full subscription lifecycle is supported including activation, renewal, cancellation, expiration, and end of term events.

= How do I troubleshoot enrollment issues? =

Enable WordPress debug mode by adding to `wp-config.php`:
`define( 'WP_DEBUG', true );`
`define( 'WP_DEBUG_LOG', true );`

Check `/wp-content/debug.log` for detailed enrollment logs.

= Can I choose when enrollment happens? =

Yes, you can configure which FluentCart events trigger enrollment (on payment, on completion, on processing, etc.).

== Screenshots ==

1. LearnDash integration settings in FluentCart product editor
2. Course and group selection interface
3. Event trigger configuration options
4. Access counter tracking for refund protection

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic course and group enrollment
* Full subscription lifecycle support
* Refund handling with access counter system
* Support for all payment methods
* Translation ready
* WordPress Coding Standards compliant
* Debug logging for production troubleshooting

== Upgrade Notice ==

= 1.0.0 =
Initial release of LearnDash FluentCart Integration.

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* LearnDash LMS (any recent version)
* FluentCart (any recent version)

== Support ==

For issues, questions, or feature requests, please contact Wbcom Designs support.
