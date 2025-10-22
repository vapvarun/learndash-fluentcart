<?php
/**
 * Main LearnDash FluentCart Integration Class
 *
 * @package LearnDash_FluentCart_Integration
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main integration class
 */
class LearnDash_FluentCart_Integration {

	/**
	 * Enable debug logging
	 *
	 * @var bool
	 */
	private $debug = false;

	/**
	 * Singleton instance
	 *
	 * @var LearnDash_FluentCart_Integration
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return LearnDash_FluentCart_Integration
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// Load integration module
		require_once LEARNDASH_FLUENTCART_PATH . 'includes/class-learndash-integration.php';
		Learndash_FluentCart_Integration_Module::get_instance();

		$this->setup_hooks();
	}

	/**
	 * Log debug message
	 *
	 * @param string $message Log message.
	 * @param string $type Log type (info, error, warning).
	 */
	private function log( $message, $type = 'info' ) {
		if ( ! $this->debug ) {
			return;
		}

		$log_message = sprintf(
			'[LearnDash FluentCart] [%s] %s',
			strtoupper( $type ),
			$message
		);

		error_log( $log_message );
	}

	/**
	 * Setup hooks
	 */
	private function setup_hooks() {
		// Order enrollment hooks
		add_action( 'fluent_cart/order_paid_done', array( $this, 'process_enrollment' ), 10, 2 );
		add_action( 'fluent_cart/order_status_changed_to_paid', array( $this, 'process_enrollment' ), 10, 2 );
		add_action( 'fluent_cart/order_status_changed_to_completed', array( $this, 'process_enrollment' ), 10, 2 );
		add_action( 'fluent_cart/order_status_changed_to_processing', array( $this, 'process_enrollment' ), 10, 2 );

		// Subscription enrollment hooks
		add_action( 'fluent_cart/subscription_activated', array( $this, 'process_subscription_enrollment' ), 10, 2 );
		add_action( 'fluent_cart/subscription_renewed', array( $this, 'process_subscription_renewal' ), 10, 2 );

		// Order unenrollment hooks
		add_action( 'fluent_cart/order_refunded', array( $this, 'process_unenrollment' ), 10, 1 );
		add_action( 'fluent_cart/order_fully_refunded', array( $this, 'process_unenrollment' ), 10, 1 );
		add_action( 'fluent_cart/order_status_changed_to_cancelled', array( $this, 'process_unenrollment' ), 10, 1 );

		// Subscription unenrollment hooks
		add_action( 'fluent_cart/subscription_canceled', array( $this, 'process_subscription_unenrollment' ), 10, 2 );
		add_action( 'fluent_cart/subscription_eot', array( $this, 'process_subscription_unenrollment' ), 10, 2 );
		add_action( 'fluent_cart/subscription_expired_validity', array( $this, 'process_subscription_unenrollment' ), 10, 2 );
	}

	/**
	 * Process enrollment when order is paid
	 *
	 * @param object|array $order Order object or array.
	 * @param object|null $customer Customer object.
	 */
	public function process_enrollment( $order, $customer = null ) {
		try {
			// Normalize order to object if it's an array or ID
			$order = $this->normalize_order( $order );
			if ( ! $order ) {
				$this->log( 'Invalid order provided', 'error' );
				return;
			}

			$this->log( sprintf( 'Processing enrollment for order #%d', $order->id ) );

			// Get user ID
			$user_id = $this->get_user_id_from_order( $order, $customer );
			if ( ! $user_id ) {
				$this->log( 'Could not determine user ID for order', 'error' );
				return;
			}

			// Ensure order items are loaded
			if ( ! isset( $order->order_items ) || empty( $order->order_items ) ) {
				// Load items using FluentCart Order model
				if ( class_exists( 'FluentCart\App\Models\Order' ) ) {
					$order = \FluentCart\App\Models\Order::with( 'items' )->find( $order->id );
				}
			}

			// Get order items
			if ( ! isset( $order->order_items ) || empty( $order->order_items ) ) {
				$this->log( 'No items found in order', 'warning' );
				return;
			}

			foreach ( $order->order_items as $item ) {
				$product_id = isset( $item->post_id ) ? $item->post_id : 0;

				if ( ! $product_id ) {
					continue;
				}

				$this->log( sprintf( 'Processing product #%d', $product_id ) );

				// Get LearnDash integration settings from FluentCart integration system
				$integration_settings = $this->get_product_learndash_settings( $product_id );

				if ( empty( $integration_settings ) ) {
					$this->log( sprintf( 'No LearnDash integration found for product #%d', $product_id ), 'warning' );
					continue;
				}

				// Enroll in courses
				$courses = isset( $integration_settings['enrolled_courses'] ) ? $integration_settings['enrolled_courses'] : array();
				foreach ( $courses as $course_id ) {
					$this->enroll_user_in_course( $user_id, $course_id, $order->id );
				}

				// Enroll in groups
				$groups = isset( $integration_settings['enrolled_groups'] ) ? $integration_settings['enrolled_groups'] : array();
				foreach ( $groups as $group_id ) {
					$this->enroll_user_in_group( $user_id, $group_id, $order->id );
				}
			}

			$this->log( sprintf( 'Enrollment completed for order #%d', $order->id ) );

		} catch ( Exception $e ) {
			$this->log( sprintf( 'Error processing enrollment: %s', $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Process unenrollment when order is refunded/cancelled
	 *
	 * @param object|array $order Order object or array.
	 */
	public function process_unenrollment( $order ) {
		try {
			// Normalize order to object if it's an array or ID
			$order = $this->normalize_order( $order );
			if ( ! $order ) {
				$this->log( 'Invalid order provided', 'error' );
				return;
			}

			$this->log( sprintf( 'Processing unenrollment for order #%d', $order->id ) );

			// Get user ID
			$user_id = $this->get_user_id_from_order( $order );
			if ( ! $user_id ) {
				$this->log( 'Could not determine user ID for order', 'error' );
				return;
			}

			// Ensure order items are loaded
			if ( ! isset( $order->order_items ) || empty( $order->order_items ) ) {
				// Load items using FluentCart Order model
				if ( class_exists( 'FluentCart\App\Models\Order' ) ) {
					$order = \FluentCart\App\Models\Order::with( 'items' )->find( $order->id );
				}
			}

			// Get order items
			if ( ! isset( $order->order_items ) || empty( $order->order_items ) ) {
				$this->log( 'No items found in order', 'warning' );
				return;
			}

			foreach ( $order->order_items as $item ) {
				$product_id = isset( $item->post_id ) ? $item->post_id : 0;

				if ( ! $product_id ) {
					continue;
				}

				// Get LearnDash integration settings
				$integration_settings = $this->get_product_learndash_settings( $product_id );

				if ( empty( $integration_settings ) ) {
					continue;
				}

				// Check if remove_on_refund is enabled
				$remove_on_refund = isset( $integration_settings['remove_on_refund'] ) ? $integration_settings['remove_on_refund'] : 'yes';
				if ( $remove_on_refund !== 'yes' ) {
					$this->log( sprintf( 'Remove on refund disabled for product #%d', $product_id ) );
					continue;
				}

				// Unenroll from courses
				$courses = isset( $integration_settings['enrolled_courses'] ) ? $integration_settings['enrolled_courses'] : array();
				foreach ( $courses as $course_id ) {
					$this->unenroll_user_from_course( $user_id, $course_id, $order->id );
				}

				// Unenroll from groups
				$groups = isset( $integration_settings['enrolled_groups'] ) ? $integration_settings['enrolled_groups'] : array();
				foreach ( $groups as $group_id ) {
					$this->unenroll_user_from_group( $user_id, $group_id, $order->id );
				}
			}

			$this->log( sprintf( 'Unenrollment completed for order #%d', $order->id ) );

		} catch ( Exception $e ) {
			$this->log( sprintf( 'Error processing unenrollment: %s', $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Normalize order to a proper Order object
	 *
	 * @param mixed $order Order object, array, or ID.
	 * @return object|null Order object or null if invalid.
	 */
	private function normalize_order( $order ) {
		// If it's already an object with order_items loaded, return it
		if ( is_object( $order ) && isset( $order->id ) && isset( $order->order_items ) ) {
			return $order;
		}

		// Get order ID
		$order_id = 0;
		if ( is_numeric( $order ) ) {
			$order_id = (int) $order;
		} elseif ( is_array( $order ) && isset( $order['id'] ) ) {
			$order_id = (int) $order['id'];
		} elseif ( is_object( $order ) && isset( $order->id ) ) {
			$order_id = (int) $order->id;
		}

		if ( ! $order_id ) {
			return null;
		}

		// Load full order with items
		if ( class_exists( 'FluentCart\App\Models\Order' ) ) {
			return \FluentCart\App\Models\Order::with( 'order_items', 'customer' )->find( $order_id );
		}

		return null;
	}

	/**
	 * Get LearnDash integration settings for a product
	 *
	 * @param int $product_id Product ID.
	 * @return array|null Integration settings or null if not found.
	 */
	private function get_product_learndash_settings( $product_id ) {
		global $wpdb;

		// Query the FluentCart product_meta table
		$meta = $wpdb->get_row( $wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->prefix}fct_product_meta
			WHERE object_id = %d
			AND object_type = 'product_integration'
			AND meta_key = 'learndash'
			LIMIT 1",
			$product_id
		) );

		if ( ! $meta || empty( $meta->meta_value ) ) {
			return null;
		}

		$settings = maybe_unserialize( $meta->meta_value );
		if ( ! is_array( $settings ) ) {
			$settings = json_decode( $meta->meta_value, true );
		}

		// Check if integration is enabled
		if ( isset( $settings['enabled'] ) && $settings['enabled'] !== 'yes' ) {
			return null;
		}

		return $settings;
	}

	/**
	 * Get user ID from order
	 *
	 * @param object      $order Order object.
	 * @param object|null $customer Customer object.
	 * @return int|false User ID or false if not found.
	 */
	private function get_user_id_from_order( $order, $customer = null ) {
		// Try from customer object
		if ( $customer && isset( $customer->user_id ) && $customer->user_id > 0 ) {
			return (int) $customer->user_id;
		}

		// Try from order customer
		if ( isset( $order->customer ) && isset( $order->customer->user_id ) && $order->customer->user_id > 0 ) {
			return (int) $order->customer->user_id;
		}

		// Try from order user_id
		if ( isset( $order->user_id ) && $order->user_id > 0 ) {
			return (int) $order->user_id;
		}

		// Try from customer_id in order
		if ( isset( $order->customer_id ) && $order->customer_id > 0 ) {
			global $wpdb;
			$user_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}fct_customers WHERE id = %d",
				$order->customer_id
			) );
			if ( $user_id ) {
				return (int) $user_id;
			}
		}

		return false;
	}

	/**
	 * Enroll user in course
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 * @param int $order_id Order ID.
	 */
	private function enroll_user_in_course( $user_id, $course_id, $order_id ) {
		if ( ! function_exists( 'ld_update_course_access' ) ) {
			$this->log( 'ld_update_course_access function not found', 'error' );
			return;
		}

		try {
			ld_update_course_access( $user_id, $course_id, false );
			$this->increment_access_counter( $user_id, $course_id, 'course', $order_id );

			$this->log( sprintf( 'Enrolled user #%d in course #%d (order #%d)', $user_id, $course_id, $order_id ) );
		} catch ( Exception $e ) {
			$this->log( sprintf( 'Failed to enroll user #%d in course #%d: %s', $user_id, $course_id, $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Enroll user in group
	 *
	 * @param int $user_id User ID.
	 * @param int $group_id Group ID.
	 * @param int $order_id Order ID.
	 */
	private function enroll_user_in_group( $user_id, $group_id, $order_id ) {
		if ( ! function_exists( 'ld_update_group_access' ) ) {
			$this->log( 'ld_update_group_access function not found', 'error' );
			return;
		}

		try {
			ld_update_group_access( $user_id, $group_id, false );
			$this->increment_access_counter( $user_id, $group_id, 'group', $order_id );

			$this->log( sprintf( 'Enrolled user #%d in group #%d (order #%d)', $user_id, $group_id, $order_id ) );
		} catch ( Exception $e ) {
			$this->log( sprintf( 'Failed to enroll user #%d in group #%d: %s', $user_id, $group_id, $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Unenroll user from course
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 * @param int $order_id Order ID.
	 */
	private function unenroll_user_from_course( $user_id, $course_id, $order_id ) {
		$access_counter = $this->decrement_access_counter( $user_id, $course_id, 'course', $order_id );

		if ( $access_counter > 0 ) {
			$this->log( sprintf( 'User #%d still has %d active access(es) to course #%d', $user_id, $access_counter, $course_id ) );
			return;
		}

		if ( ! function_exists( 'ld_update_course_access' ) ) {
			$this->log( 'ld_update_course_access function not found', 'error' );
			return;
		}

		try {
			ld_update_course_access( $user_id, $course_id, true );
			$this->log( sprintf( 'Unenrolled user #%d from course #%d (order #%d)', $user_id, $course_id, $order_id ) );
		} catch ( Exception $e ) {
			$this->log( sprintf( 'Failed to unenroll user #%d from course #%d: %s', $user_id, $course_id, $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Unenroll user from group
	 *
	 * @param int $user_id User ID.
	 * @param int $group_id Group ID.
	 * @param int $order_id Order ID.
	 */
	private function unenroll_user_from_group( $user_id, $group_id, $order_id ) {
		$access_counter = $this->decrement_access_counter( $user_id, $group_id, 'group', $order_id );

		if ( $access_counter > 0 ) {
			$this->log( sprintf( 'User #%d still has %d active access(es) to group #%d', $user_id, $access_counter, $group_id ) );
			return;
		}

		if ( ! function_exists( 'ld_update_group_access' ) ) {
			$this->log( 'ld_update_group_access function not found', 'error' );
			return;
		}

		try {
			ld_update_group_access( $user_id, $group_id, true );
			$this->log( sprintf( 'Unenrolled user #%d from group #%d (order #%d)', $user_id, $group_id, $order_id ) );
		} catch ( Exception $e ) {
			$this->log( sprintf( 'Failed to unenroll user #%d from group #%d: %s', $user_id, $group_id, $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Increment access counter
	 *
	 * @param int    $user_id User ID.
	 * @param int    $item_id Course or Group ID.
	 * @param string $type Type (course or group).
	 * @param int    $order_id Order ID.
	 */
	private function increment_access_counter( $user_id, $item_id, $type, $order_id ) {
		$counter_key = '_ld_fluentcart_access_counter';
		$counter     = get_user_meta( $user_id, $counter_key, true );

		if ( ! is_array( $counter ) ) {
			$counter = array();
		}

		$item_key = $type . '_' . $item_id;

		if ( ! isset( $counter[ $item_key ] ) ) {
			$counter[ $item_key ] = array();
		}

		$counter[ $item_key ][] = $order_id;

		update_user_meta( $user_id, $counter_key, $counter );
	}

	/**
	 * Decrement access counter
	 *
	 * @param int    $user_id User ID.
	 * @param int    $item_id Course or Group ID.
	 * @param string $type Type (course or group).
	 * @param int    $order_id Order ID.
	 * @return int Remaining access count.
	 */
	private function decrement_access_counter( $user_id, $item_id, $type, $order_id ) {
		$counter_key = '_ld_fluentcart_access_counter';
		$counter     = get_user_meta( $user_id, $counter_key, true );

		if ( ! is_array( $counter ) ) {
			return 0;
		}

		$item_key = $type . '_' . $item_id;

		if ( ! isset( $counter[ $item_key ] ) || ! is_array( $counter[ $item_key ] ) ) {
			return 0;
		}

		// Remove this order ID from the counter
		$counter[ $item_key ] = array_diff( $counter[ $item_key ], array( $order_id ) );

		// Clean up empty arrays
		if ( empty( $counter[ $item_key ] ) ) {
			unset( $counter[ $item_key ] );
		}

		update_user_meta( $user_id, $counter_key, $counter );

		return isset( $counter[ $item_key ] ) ? count( $counter[ $item_key ] ) : 0;
	}

	/**
	 * Process subscription enrollment
	 *
	 * @param object $subscription Subscription object.
	 * @param object|null $order Order object.
	 */
	public function process_subscription_enrollment( $subscription, $order = null ) {
		try {
			$this->log( sprintf( 'Processing subscription enrollment for subscription #%d', $subscription->id ) );

			// Get the order if not provided
			if ( ! $order && isset( $subscription->parent_order_id ) ) {
				$order = fluent_cart_get_order( $subscription->parent_order_id );
			}

			if ( ! $order ) {
				$this->log( 'Could not find order for subscription', 'error' );
				return;
			}

			// Use the existing enrollment logic
			$this->process_enrollment( $order, $order->customer );

			$this->log( sprintf( 'Subscription #%d enrollment completed', $subscription->id ) );

		} catch ( Exception $e ) {
			$this->log( sprintf( 'Subscription enrollment error: %s', $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Process subscription renewal
	 *
	 * @param object $subscription Subscription object.
	 * @param object|null $order Renewal order object.
	 */
	public function process_subscription_renewal( $subscription, $order = null ) {
		try {
			$this->log( sprintf( 'Processing subscription renewal for subscription #%d', $subscription->id ) );

			// Get the order if not provided
			if ( ! $order && isset( $subscription->parent_order_id ) ) {
				$order = fluent_cart_get_order( $subscription->parent_order_id );
			}

			if ( ! $order ) {
				$this->log( 'Could not find order for subscription renewal', 'error' );
				return;
			}

			// For renewals, we just ensure enrollment is active (doesn't double-enroll)
			// The access counter system will handle this properly
			$this->process_enrollment( $order, $order->customer );

			$this->log( sprintf( 'Subscription #%d renewal completed', $subscription->id ) );

		} catch ( Exception $e ) {
			$this->log( sprintf( 'Subscription renewal error: %s', $e->getMessage() ), 'error' );
		}
	}

	/**
	 * Process subscription unenrollment (canceled, expired, end of term)
	 *
	 * @param object $subscription Subscription object.
	 * @param object|null $order Order object.
	 */
	public function process_subscription_unenrollment( $subscription, $order = null ) {
		try {
			$this->log( sprintf( 'Processing subscription unenrollment for subscription #%d', $subscription->id ) );

			// Get the order if not provided
			if ( ! $order && isset( $subscription->parent_order_id ) ) {
				$order = fluent_cart_get_order( $subscription->parent_order_id );
			}

			if ( ! $order ) {
				$this->log( 'Could not find order for subscription unenrollment', 'error' );
				return;
			}

			// Use the existing unenrollment logic
			$this->process_unenrollment( $order );

			$this->log( sprintf( 'Subscription #%d unenrollment completed', $subscription->id ) );

		} catch ( Exception $e ) {
			$this->log( sprintf( 'Subscription unenrollment error: %s', $e->getMessage() ), 'error' );
		}
	}
}
