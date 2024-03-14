<?php
/**
 * Plugin Name: Dynamic Membership Assignment for WP All Import and PMP
 * Description: Dynamically assigns membership levels to posts based on imported CSV data, integrating with WP All Import and Paid Memberships Pro. Adds an admin page for bulk processing membership assignments.
 * Version: 2.3.0
 * Author: Fahad Murtaza
 * Author URI: https://www.fahadmurtaza.com
 * License: GPL2
 * Text Domain: dynamic-membership-assigner
 * Domain Path: /languages
 *
 * This plugin is designed to automate the assignment of membership levels in WordPress, leveraging data from CSV imports.
 */

/**
 * Prevents direct access to the plugin file.
 *
 * Checks if ABSPATH is defined to ensure the file is being called through WordPress. If not, the script execution is halted.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the plugin's admin page to the WordPress dashboard menu.
 *
 * Registers a new menu page under the admin menu, providing site administrators with a manual membership assignment interface.
 */
// Add admin menu page for manual processing.
add_action( 'admin_menu', 'dma_add_admin_menu' );
function dma_add_admin_menu(): void {
	add_menu_page( 'Membership Assignment', 'Membership Assignment', 'manage_options', 'dma-membership-assignment', 'dma_membership_assignment_page' );
}

/**
 * Renders the plugin's admin page.
 *
 * Displays the admin page for manual membership assignment, including a button to initiate the assignment process.
 */
function dma_membership_assignment_page(): void {
	echo '<h1>Membership Assignment</h1>';
	echo '<p><button id="start-assignment">Start Assignment</button></p>';
	// Include JS to handle button click and AJAX request. JS code will be provided in dma-admin.js.
	// give front end udpats to what is happening
	echo '<div id="dma-assignment-status"></div>';

}

// AJAX handler for starting the assignment process.
add_action( 'wp_ajax_dma_start_assignment', 'dma_start_assignment_ajax' );

/**
 * Handles AJAX requests for starting the membership assignment process.
 *
 * Initiates the process of assigning membership levels to posts when the 'Start Assignment' button is clicked on the admin page.
 *
 * @return void
 */
function dma_start_assignment_ajax(): void {
	dma_process_assignments();
	wp_die(); // Terminate AJAX request.
}

// Batch process assignment.
/**
 * @return void
 */
function dma_process_assignments(): void {
	$args = [
		'post_type'      => 'lead',
		'posts_per_page' => - 1, // Process all posts at a time.
		'post_status'    => 'publish',
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_query'     => [
			[
				'key'     => 'dma_processed',
				'compare' => 'NOT EXISTS' // Only select posts that haven't been marked as processed
			]
		],
	];

	$query           = new WP_Query( $args );
	$processed_count = 0; // Track the number of posts processed

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			// Process the post for membership assignment
			assign_memberships_to_lead_post( $post_id, get_post( $post_id ), false );
			// Mark the post as processed
			update_post_meta( $post_id, 'dma_processed', 'yes' );
			$processed_count ++;
		}
	}

	// Send a success message with the count of posts processed
	wp_send_json_success( "$processed_count posts processed." );
}

// Enqueue script for AJAX on admin page.
add_action( 'admin_enqueue_scripts', 'dma_enqueue_scripts' );
function dma_enqueue_scripts( $hook ): void {
	if ( 'toplevel_page_dma-membership-assignment' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'dma-admin-js', plugin_dir_url( __FILE__ ) . 'js/dma-admin.js', [ 'jquery' ], null, true );
	wp_localize_script( 'dma-admin-js', 'dmaAjax', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
}

/**
 * Assigns membership levels to individual posts.
 *
 * Processes each post to assign appropriate membership levels based on the specified criteria from imported CSV data.
 *
 * @param int $post_id The ID of the post being processed.
 * @param WP_Post $post The post object.
 * @param bool $update Specifies if the operation is an update.
 */
function assign_memberships_to_lead_post( int $post_id, WP_Post $post, bool $update ): void {

	// Ensure we are dealing with the 'lead' post type
	if ( 'lead' === $post->post_type && ! $update ) {

		error_log( 'Starting membership assignment for lead post ID: ' . $post_id );

		// Fetch state directly from ACF fields
		$state = get_field( 'property_state', $post_id );

		// Assuming 'disaster-type' is the taxonomy you want to check
		$disaster_types_terms = wp_get_post_terms( $post_id, 'disaster-type', [ 'fields' => 'names' ] );

		// Always include 'Nationwide All-Access' membership level ID
		$nationwide_all_access_id = get_nationwide_all_access_level_id();
		if ( $nationwide_all_access_id !== null ) {
			$membership_levels[] = $nationwide_all_access_id;
			assign_membership_to_post( $post_id, $membership_levels );
		}

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

function get_nationwide_all_access_level_id() {
	$all_levels = pmpro_getAllLevels( true, true );
	foreach ( $all_levels as $level ) {
		if ( strtolower( $level->name ) === 'nationwide all-access' ) {
			return $level->id; // Found the ID of 'Nationwide All-Access'
		}
	}

	return null; // Return null if not found
}

/**
 * Determines the membership levels applicable to a post based on disaster types.
 *
 * Matches the post's disaster type(s) against available membership levels, ensuring case-insensitive and partial matches are considered.
 *
 * @param string $state The state associated with the post.
 * @param array $disaster_types An array of disaster types associated with the post.
 *
 * @return array An array of applicable membership level IDs.
 */
function determine_membership_levels( string $state, array $disaster_types ): array {
	$all_levels = pmpro_getAllLevels( true, true );
	$levels     = [];

//	// Always include 'Nationwide All-Access' membership level ID
//	$nationwide_all_access_id = get_nationwide_all_access_level_id();
//	if ( $nationwide_all_access_id !== null ) {
//		$levels[] = $nationwide_all_access_id;
//	}

	// Normalize criteria for comparison
	$normalizedState         = strtolower( $state );
	$normalizedDisasterTypes = array_map( 'strtolower', $disaster_types );

	foreach ( $all_levels as $level ) {
		$levelNameLower = strtolower( $level->name );

		// Check if the level name starts with the full state name followed by a space or another identifier
		if ( substr( $levelNameLower, 0, strlen( $normalizedState ) + 1 ) === $normalizedState . " " ) {
			foreach ( $normalizedDisasterTypes as $type ) {
				if ( str_contains( $levelNameLower, $type ) ) {
					$levels[] = $level->id;
				}
			}
		}
	}

	return array_unique( $levels );
}


/**
 * Updates the membership level assignments for a post in the database.
 *
 * Inserts or updates membership level associations for a given post, ensuring it has access to the specified levels.
 *
 * @param int $post_id The ID of the post to assign memberships to.
 * @param array $level_ids An array of membership level IDs to assign to the post.
 */
function assign_membership_to_post( int $post_id, array $level_ids ): void {
	global $wpdb;

	$wpdb->delete( "{$wpdb->prefix}pmpro_memberships_pages", [ 'page_id' => $post_id ] );

	foreach ( $level_ids as $level_id ) {
		$wpdb->insert( "{$wpdb->prefix}pmpro_memberships_pages", [
			'page_id'       => $post_id,
			'membership_id' => $level_id
		] );
	}
	error_log( 'Assigned levels ' . implode( ',', $level_ids ) . ' successfully to post ID: ' . $post_id );
}
