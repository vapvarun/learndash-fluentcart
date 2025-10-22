# LearnDash FluentCart Integration

Seamlessly integrate LearnDash LMS with FluentCart to automatically enroll customers in courses and groups upon purchase.

## Features

### Core Functionality
- **Automatic Course Enrollment** - Automatically enroll customers in LearnDash courses when they purchase products
- **Automatic Group Enrollment** - Enroll customers in LearnDash groups for membership-based access
- **Multiple Product Support** - Configure different courses/groups for each product
- **Multi-Select Options** - Assign multiple courses or groups to a single product

### Payment & Order Management
- **All Payment Methods Supported** - Works with online payments, cash on delivery, and offline payments
- **Automatic on Payment Confirmation** - Enrollment triggers immediately when order is marked as paid
- **Order Status Tracking** - Responds to completed, processing, and paid order statuses
- **Manual Order Support** - Works with manually created orders and payment confirmations

### Subscription Support
- **Full Subscription Lifecycle** - Complete support for subscription-based products
  - Subscription Activated - Enroll on subscription start
  - Subscription Renewed - Re-enroll on renewal
  - Subscription Canceled - Remove access on cancellation
  - Subscription Expired - Remove access on expiration
  - Subscription End of Term - Remove access at term end

### Refund & Access Management
- **Smart Refund Handling** - Automatically remove course/group access on refunds
- **Access Counter System** - Tracks multiple purchases to prevent premature access removal
- **Configurable Removal** - Choose whether to remove access on refund per product
- **Multiple Purchase Protection** - If customer buys same product twice, access persists until all are refunded

### Integration Features
- **Event Trigger Selection** - Choose which FluentCart events trigger enrollment
- **Conditional Variations** - Apply integrations to specific product variations only
- **Integration Naming** - Give each integration a descriptive name for easy management
- **Enable/Disable Toggle** - Easily activate or deactivate integrations per product

### Developer & Technical Features
- **Debug Logging** - Comprehensive logging for troubleshooting (when WP_DEBUG enabled)
- **Priority Hook Execution** - Runs before email notifications to ensure reliability
- **Error Handling** - Graceful error handling with detailed logging
- **Access Counter Tracking** - User meta tracks order IDs per course/group for refund safety

### Translation & Standards
- **Translation Ready** - Full internationalization support with `learndash-fluentcart` text domain
- **WordPress Coding Standards** - 100% WPCS compliant code
- **LearnDash Custom Labels** - Respects custom labels for courses/groups
- **POT File Generation** - Grunt task for translation file generation

### Build & Deployment
- **Grunt Build System** - Automated build process included
  - `npm run build` - Build production-ready ZIP file
  - `npm run wpcs` - Check WordPress Coding Standards
  - `npm run makepot` - Generate translation POT file
- **Clean Deployment Package** - Excludes dev files from distribution

## Installation

1. Upload the plugin files to `/wp-content/plugins/learndash-fluentcart/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure both LearnDash LMS and FluentCart are installed and activated

## Configuration

1. Navigate to FluentCart → Products
2. Edit a product
3. Click the "Integrations" tab
4. Configure LearnDash integration:
   - **Integration Name** - Give it a descriptive name
   - **Enroll in Courses** - Select one or more courses
   - **Enroll in Groups** - Select one or more groups
   - **Remove Access on Refund** - Choose whether to remove access on refund
   - **Event Trigger** - Select when enrollment should happen (default: on payment)
5. Save the product

## How It Works

### Enrollment Process
1. Customer places an order with a product that has LearnDash integration
2. When order is marked as paid, enrollment is triggered automatically
3. Customer is enrolled in configured courses/groups
4. Access counter is incremented to track the purchase
5. Customer can immediately access their course/group content

### Unenrollment Process (Refunds)
1. When an order is refunded or cancelled
2. If "Remove Access on Refund" is enabled
3. Access counter is decremented for that order
4. If counter reaches zero (no other active purchases), access is removed
5. If counter > 0 (customer has other active purchases), access remains

## Supported Events

### Enrollment Triggers
- `order_paid_done` - Order payment completed
- `order_status_changed_to_paid` - Order manually marked as paid
- `order_status_changed_to_completed` - Order status changed to completed
- `order_status_changed_to_processing` - Order status changed to processing
- `subscription_activated` - Subscription becomes active
- `subscription_renewed` - Subscription renewed

### Unenrollment Triggers
- `order_refunded` - Order refunded
- `order_fully_refunded` - Order fully refunded
- `order_status_changed_to_cancelled` - Order cancelled
- `subscription_canceled` - Subscription cancelled
- `subscription_eot` - Subscription end of term
- `subscription_expired_validity` - Subscription expired

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- LearnDash LMS (any recent version)
- FluentCart (any recent version)

## Development

### Build Commands

```bash
# Install dependencies
npm install

# Build production ZIP
npm run build

# Check WordPress Coding Standards
npm run wpcs

# Generate translation POT file
npm run makepot
```

### Debug Mode

Enable WordPress debug mode to see enrollment logs:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Logs will appear in `/wp-content/debug.log` with format:
```
[LearnDash FluentCart] [INFO] Processing enrollment for order #123
[LearnDash FluentCart] [INFO] Enrolled user #45 in course #67 (order #123)
```

## Troubleshooting

### Enrollment Not Happening
1. Check if LearnDash integration is configured on the product
2. Verify integration is enabled (not disabled)
3. Ensure order status is "paid" or "completed"
4. Check debug logs in `/wp-content/debug.log`
5. Verify customer has a WordPress user account

### Access Not Removed on Refund
1. Check if "Remove Access on Refund" is enabled
2. Verify customer doesn't have multiple purchases of same product
3. Check access counter in user meta `_ld_fluentcart_access_counter`

### Debug Logs Not Appearing
1. Enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`
2. Check file permissions on `/wp-content/debug.log`
3. Verify plugin is activated

## Support

For issues, questions, or feature requests, please contact Wbcom Designs support.

## Changelog

### 1.0.0
- Initial release
- Automatic course and group enrollment
- Full subscription lifecycle support
- Refund handling with access counter
- Translation ready
- WordPress Coding Standards compliant
- Build system with Grunt

## License

This plugin is proprietary software developed by Wbcom Designs.

## Credits

Developed by Wbcom Designs
