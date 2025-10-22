<?php
/**
 * LearnDash FluentCart Integration - FluentCart Integration Handler
 *
 * This class registers LearnDash as a FluentCart integration module
 *
 * @package LearnDash_FluentCart_Integration
 * @since 1.0.0
 */

class LearnDash_FluentCart_Integration_Module {

	/**
	 * Get instance
	 *
	 * @return LearnDash_FluentCart_Integration_Module
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'fluent_cart/integration/order_integrations', array( $this, 'register_integration' ), 10, 1 );
		add_filter( 'fluent_cart/integration/get_integration_settings_fields_learndash', array( $this, 'get_integration_settings_fields' ), 10, 2 );
		add_filter( 'fluent_cart/integration/get_integration_defaults_learndash', array( $this, 'get_integration_defaults' ), 10, 1 );
		add_filter( 'fluent_cart/integration/save_integration_values_learndash', array( $this, 'save_integration_values' ), 10, 2 );
	}

	/**
	 * Register LearnDash integration with FluentCart
	 *
	 * @param array $integrations List of integrations
	 * @return array Modified integrations list
	 */
	public function register_integration( $integrations ) {
		$integrations['learndash'] = array(
			'title'       => __( 'LearnDash LMS', 'learndash-fluentcart' ),
			'description' => __( 'Automatically enroll customers in LearnDash courses and groups when they purchase products.', 'learndash-fluentcart' ),
			'logo'        => LEARNDASH_FLUENTCART_URL . 'assets/images/learndash-icon.svg',
			'enabled'     => true,
			'scopes'      => array( 'product', 'order' ), // Available for both product and order level
			'is_active'   => true,
		);

		return $integrations;
	}

	/**
	 * Get integration defaults
	 *
	 * @param array $defaults Default settings
	 * @return array Default settings
	 */
	public function get_integration_defaults( $defaults ) {
		return array(
			'name'              => '',
			'enrolled_courses'  => array(),
			'enrolled_groups'   => array(),
			'remove_on_refund'  => 'yes',
			'event_trigger'     => array(
				// Enrollment triggers
				'order_paid_done',
				'subscription_activated',
				'subscription_renewed',
				// Unenrollment triggers
				'order_fully_refunded',
				'order_status_changed_to_canceled',
				'subscription_canceled',
				'subscription_eot',
				'subscription_expired_validity',
			),
			'enabled'           => 'yes',
		);
	}

	/**
	 * Get integration settings fields
	 *
	 * @param array $fields   Fields array
	 * @param array $settings Current settings
	 * @return array Fields configuration
	 */
	public function get_integration_settings_fields( $fields, $settings ) {
		$courses = $this->get_all_courses();
		$groups  = $this->get_all_groups();

		$course_options = array();
		foreach ( $courses as $course_id => $course_title ) {
			$course_options[ (string) $course_id ] = $course_title;
		}

		$group_options = array();
		foreach ( $groups as $group_id => $group_title ) {
			$group_options[ (string) $group_id ] = $group_title;
		}

		$fields = array(
			'name'             => array(
				'key'         => 'name',
				'label'       => __( 'Integration Name', 'learndash-fluentcart' ),
				'component'   => 'text',
				'placeholder' => __( 'e.g., Course Bundle Enrollment', 'learndash-fluentcart' ),
				'required'    => true,
				'inline_tip'  => __( 'Give this integration a descriptive name for your reference.', 'learndash-fluentcart' ),
			),
			'enrolled_courses' => array(
				'key'        => 'enrolled_courses',
				'label'      => sprintf(
					// translators: %s is for "Courses"
					__( 'Enroll in %s', 'learndash-fluentcart' ),
					learndash_get_custom_label( 'courses' )
				),
				'component'  => 'select',
				'is_multiple' => true,
				'options'    => $course_options,
				'placeholder' => __( 'Select courses...', 'learndash-fluentcart' ),
				'inline_tip' => __( 'Select one or more courses to enroll customers in.', 'learndash-fluentcart' ),
			),
			'enrolled_groups'  => array(
				'key'        => 'enrolled_groups',
				'label'      => sprintf(
					// translators: %s is for "Groups"
					__( 'Enroll in %s', 'learndash-fluentcart' ),
					learndash_get_custom_label( 'groups' )
				),
				'component'  => 'select',
				'is_multiple' => true,
				'options'    => $group_options,
				'placeholder' => __( 'Select groups...', 'learndash-fluentcart' ),
				'inline_tip' => __( 'Select one or more groups to enroll customers in.', 'learndash-fluentcart' ),
			),
			'remove_on_refund' => array(
				'key'            => 'remove_on_refund',
				'label'          => __( 'Remove Access on Refund', 'learndash-fluentcart' ),
				'component'      => 'checkbox-single',
				'checkbox_label' => __( 'Automatically remove course/group access when the order is refunded or cancelled.', 'learndash-fluentcart' ),
			),
		);

		// Add event trigger field (when to run the integration)
		if ( class_exists( 'FluentCart\App\Helpers\Status' ) ) {
			$fields[] = \FluentCart\App\Helpers\Status::eventTriggers();
		}

		return array(
			'fields'              => array_values( $fields ),
			'integration_title'   => __( 'LearnDash LMS', 'learndash-fluentcart' ),
			'button_require_list' => false,
		);
	}

	/**
	 * Save integration values (process before saving)
	 *
	 * @param array $integration Integration data
	 * @param array $args        Additional arguments
	 * @return array Processed integration data
	 */
	public function save_integration_values( $integration, $args ) {
		// Validate that at least one course or group is selected
		$enrolled_courses = isset( $integration['enrolled_courses'] ) ? $integration['enrolled_courses'] : array();
		$enrolled_groups  = isset( $integration['enrolled_groups'] ) ? $integration['enrolled_groups'] : array();

		// Normalize remove_on_refund to 'yes' or 'no'
		if ( isset( $integration['remove_on_refund'] ) ) {
			// Handle boolean true, string 'true', or truthy values
			if ( $integration['remove_on_refund'] === true || $integration['remove_on_refund'] === 'true' || $integration['remove_on_refund'] === 'yes' || $integration['remove_on_refund'] === 1 || $integration['remove_on_refund'] === '1' ) {
				$integration['remove_on_refund'] = 'yes';
			} else {
				$integration['remove_on_refund'] = 'no';
			}
		} else {
			// Default to 'yes' if not set
			$integration['remove_on_refund'] = 'yes';
		}

		// Normalize enabled to 'yes' or 'no'
		if ( isset( $integration['enabled'] ) ) {
			if ( $integration['enabled'] === true || $integration['enabled'] === 'true' || $integration['enabled'] === 'yes' || $integration['enabled'] === 1 || $integration['enabled'] === '1' ) {
				$integration['enabled'] = 'yes';
			} else {
				$integration['enabled'] = 'no';
			}
		} else {
			// Default to 'yes' if not set
			$integration['enabled'] = 'yes';
		}

		return $integration;
	}

	/**
	 * Get all published LearnDash courses
	 *
	 * @return array Course ID => Course Title
	 */
	private function get_all_courses() {
		$courses     = array();
		$course_args = array(
			'post_type'      => 'sfwd-courses',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$course_query = new WP_Query( $course_args );

		if ( $course_query->have_posts() ) {
			while ( $course_query->have_posts() ) {
				$course_query->the_post();
				$courses[ get_the_ID() ] = get_the_title();
			}
			wp_reset_postdata();
		}

		return $courses;
	}

	/**
	 * Get all published LearnDash groups
	 *
	 * @return array Group ID => Group Title
	 */
	private function get_all_groups() {
		$groups     = array();
		$group_args = array(
			'post_type'      => 'groups',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$group_query = new WP_Query( $group_args );

		if ( $group_query->have_posts() ) {
			while ( $group_query->have_posts() ) {
				$group_query->the_post();
				$groups[ get_the_ID() ] = get_the_title();
			}
			wp_reset_postdata();
		}

		return $groups;
	}
}
