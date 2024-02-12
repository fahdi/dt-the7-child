<?php
/**
 * Plugin Name: Dynamic Membership Assignment for WP All Import and PMP
 * Plugin URI: https://www.fahadmurtaza.com
 * Description: Dynamically assigns membership levels to posts based on imported CSV data, integrating with WP All Import and Paid Memberships Pro. Adds an admin page for bulk processing membership assignments.
 * Version: 1.2
 * Author: Fahad Murtaza
 * Author URI: https://www.fahadmurtaza.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dynamic-membership-assigner
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.0
 */

// Register activation hook to setup initial transient for tracking.
register_activation_hook( __FILE__, 'dma_setup_initial_transient' );
function dma_setup_initial_transient() {
	set_transient( 'dma_last_processed_id', 0, 0 ); // Never expires.
}

// Add admin menu page for manual processing.
add_action( 'admin_menu', 'dma_add_admin_menu' );
function dma_add_admin_menu() {
	add_menu_page( 'Membership Assignment', 'Membership Assignment', 'manage_options', 'dma-membership-assignment', 'dma_membership_assignment_page' );
}

function dma_membership_assignment_page() {
	echo '<h1>Membership Assignment</h1>';
	echo '<p><button id="start-assignment">Start Assignment</button></p>';
	// Include JS to handle button click and AJAX request. JS code will be provided in dma-admin.js.
	// give front end udpats to what is happening
	echo '<div id="dma-assignment-status"></div>';

}

// AJAX handler for starting the assignment process.
add_action( 'wp_ajax_dma_start_assignment', 'dma_start_assignment_ajax' );
function dma_start_assignment_ajax() {
	dma_process_assignments();
	wp_die(); // Terminate AJAX request.
}

// Batch process assignment.
function dma_process_assignments() {
	$args = [
		'post_type'      => 'lead',
		'posts_per_page' => - 1, // Process 500 posts at a time.
		'post_status'    => 'publish',
		'orderby'        => 'ID',
		'order'          => 'ASC',

	];

	$query = new WP_Query( $args );
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			assign_memberships_to_lead_post( $post_id, get_post( $post_id ), false );
		}
	}

	return $query->post_count . " posts processed.";

}

// Enqueue script for AJAX on admin page.
add_action( 'admin_enqueue_scripts', 'dma_enqueue_scripts' );
function dma_enqueue_scripts( $hook ) {
	if ( 'toplevel_page_dma-membership-assignment' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'dma-admin-js', plugin_dir_url( __FILE__ ) . 'js/dma-admin.js', [ 'jquery' ], null, true );
	wp_localize_script( 'dma-admin-js', 'dmaAjax', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
}

function assign_memberships_to_lead_post( $post_id, $post, $update ) {

	// Check if the lead has already been processed
	$already_processed = get_post_meta( $post_id, 'dma_processed', true );
	if ( 'yes' === $already_processed ) {
		error_log( 'Lead post ID: ' . $post_id . ' has already been processed.' );

		return; // Stop processing this lead
	}

	// Ensure we are dealing with the 'lead' post type
	if ( 'lead' === $post->post_type && ! $update ) {
		// Check for an import flag or other indication this is a newly imported lead

		error_log( 'Starting membership assignment for lead post ID: ' . $post_id );

		// Fetch state directly from ACF fields
		$state = get_field( 'state', $post_id );

		// Assuming 'disaster-type' is the taxonomy you want to check
		$disaster_types_terms = wp_get_post_terms( $post_id, 'disaster-type', [ 'fields' => 'names' ] );

		error_log( "Disaster types found for lead post ID: " . print_r( $disaster_types_terms, true ) );
		if ( ! is_wp_error( $disaster_types_terms ) && ! empty( $disaster_types_terms ) ) {
			$membership_levels = determine_membership_levels( $state, $disaster_types_terms );
			assign_membership_to_post( $post_id, $membership_levels );
			error_log( 'Completed membership assignment for lead post ID: ' . $post_id );
		} else {
			error_log( "Error or no disaster types found for lead post ID $post_id" );
		}
	}

	update_post_meta( $post_id, 'dma_processed', 'yes' );
}

function determine_membership_levels( $state, $disaster_types ) {
	error_log( 'Determining levels for state: ' . $state . ' with types: ' . implode( ', ', $disaster_types ) );
	$all_levels = pmpro_getAllLevels( true, true ); // Fetch all membership levels
	$levels     = [];

	// Convert disaster types to lowercase for case-insensitive comparison
	$disaster_types = array_map( 'strtolower', $disaster_types );

	foreach ( $all_levels as $level ) {
		// Check each level name against each disaster type for a partial match
		foreach ( $disaster_types as $type ) {
			$name_to_check = strtolower( ucfirst( $type ) );
			if ( stripos( strtolower( $level->name ), $name_to_check ) !== false && stripos( strtolower( $level->name ), $state ) !== false ) {
				error_log( 'Name matched: ' . $name_to_check . ', and state matched: ' . $state . ', with level name: ' . strtolower( $level->name ) );
				$levels[] = $level->id;
			}
		}
	}

	error_log( 'Levels determined: ' . implode( ', ', array_unique( $levels ) ) );

	return array_unique( $levels );
}

function assign_membership_to_post( $post_id, $level_ids ): void {
	global $wpdb;

	if ( ! is_array( $level_ids ) ) {
		$level_ids = [ $level_ids ];
	}

	$wpdb->delete( "{$wpdb->prefix}pmpro_memberships_pages", [ 'page_id' => $post_id ] );

	foreach ( $level_ids as $level_id ) {
		$wpdb->insert( "{$wpdb->prefix}pmpro_memberships_pages", [
			'page_id'       => $post_id,
			'membership_id' => $level_id
		] );
	}
	error_log( 'Assigned levels ' . implode( ',', $level_ids ) . ' successfully to post ID: ' . $post_id );
}