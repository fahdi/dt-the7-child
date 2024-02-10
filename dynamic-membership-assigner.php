<?php
/**
 * Plugin Name: Dynamic Membership Assignment for WP All Import and PMP
 * Description: Dynamically assigns membership levels to posts based on imported CSV data, integrating with WP All Import and Paid Memberships Pro.
 * Version: 1.0
 * Author: Your Name
 */

add_action( 'wp_insert_post', 'assign_memberships_to_lead_post', 30, 3 );

function assign_memberships_to_lead_post( $post_id, $post, $update ) {
	// Ensure we are dealing with the 'lead' post type
	if ( 'lead' === $post->post_type && ! $update ) {
		// Check for an import flag or other indication this is a newly imported lead

		error_log( 'Starting membership assignment for lead post ID: ' . $post_id );

		// Fetch state directly from ACF fields
		$state = get_field( 'state', $post_id );

		// Assuming 'disaster-type' is the taxonomy you want to check
		$disaster_types_terms = wp_get_post_terms( $post_id, 'disaster-type', [ 'fields' => 'names' ] );

		error_log( "Error or no disaster types found for lead post ID " . print_r($disaster_types_terms, true) );
		if ( ! is_wp_error( $disaster_types_terms ) && ! empty( $disaster_types_terms ) ) {
			$membership_levels = determine_membership_levels( $state, $disaster_types_terms );
			foreach ( $membership_levels as $level_id ) {
				assign_membership_to_post( $post_id, $level_id );
			}
			error_log( 'Completed membership assignment for lead post ID: ' . $post_id );
		} else {
			error_log( "Error or no disaster types found for lead post ID $post_id" );
		}
	}
}

function determine_membership_levels( $state, $damage_types ) {
	error_log( 'Determining levels for state: ' . $state . ' with types: ' . implode( ', ', $damage_types ) );
	$all_levels = pmpro_getAllLevels( true, true ); // Fetch all membership levels
	$levels     = [];

	foreach ( $all_levels as $level ) {
		foreach ( $damage_types as $type ) {
			$name_to_check = $state . " " . ucfirst( $type ) . " Leads Membership";
			if ( stripos( $level->name, $name_to_check ) !== false ) {
				$levels[] = $level->id;
			}
		}
	}

	return array_unique( $levels );
}

function assign_membership_to_post( $post_id, $level_ids ) {
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
	error_log( 'Assigned levels successfully to post ID: ' . $post_id );
}

// add_action( 'wp_loaded', 'myStartSession', 1 );
function myStartSession() {
	echo "<pre>";
	print_r( wp_get_post_terms( 940, 'disaster-type', array( 'fields' => 'names' ) ) );
	echo "</pre>";
}

// TODO: Delete this debug code later. Change the post ID when testing on a different post.
// add_action( 'wp_loaded', 'assign_me', 1 );
function assign_me() {
	assign_memberships_to_lead_post( 941, get_post( 941 ), false );
}

// Add sharding delay if needed
// add_filter( 'wp_all_import_shard_delay', 'add_delay', 10, 1 );
function add_delay( $sleep ) {
	return 500000;
}

// Add the admin menu page
function dma_add_admin_menu() {
	add_menu_page(
		'Assign Memberships', // Page title
		'Assign Memberships', // Menu title
		'manage_options', // Capability
		'dma_assign_memberships', // Menu slug
		'dma_assign_memberships_page' // Function to display the page
	);
}
add_action('admin_menu', 'dma_add_admin_menu');

// Display the admin page content
function dma_assign_memberships_page() {
	?>
	<div class="wrap">
		<h2>Assign Memberships to Leads</h2>
		<form method="post" action="">
			<?php submit_button('Assign Memberships'); ?>
		</form>
	</div>
	<?php

	// Check if the form is submitted
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		dma_process_membership_assignment();
	}
}

// Function to process membership assignment
function dma_process_membership_assignment() {
	$args = array(
		'post_type' => 'lead', // Adjust the post type if necessary
		'posts_per_page' => -1, // Process all posts
	);

	$query = new WP_Query($args);

	if ($query->have_posts()) {
		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();
			assign_memberships_to_lead_post($post_id, get_post($post_id), false);
		}
		wp_reset_postdata();
	}

	// Optionally, add some notification about completion
	echo '<div class="updated"><p>Membership assignment process completed.</p></div>';
}
