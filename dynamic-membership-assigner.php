<?php
/**
 * Plugin Name: Dynamic Membership Assignment for WP All Import and PMP
 * Description: Dynamically assigns membership levels to posts based on imported CSV data, integrating with WP All Import and Paid Memberships Pro.
 * Version: 1.0
 * Author: Your Name
 */

// Hook into WP All Import's 'pmxi_saved_post' action to assign memberships after import
add_action( 'pmxi_saved_post', 'assign_pmp_membership_based_on_csv', 10, 1 );

function assign_pmp_membership_based_on_csv( $post_id ) {
	error_log('Starting membership assignment for post ID: ' . $post_id);
	// Assuming 'state' and 'damage_type' are set as custom fields by WP All Import
	$state        = get_post_meta( $post_id, 'state', true );
	$damage_types = explode( ',', get_post_meta( $post_id, 'damage_type', true ) ); // 'hail,fire'

	$membership_levels = determine_membership_levels( $state, $damage_types );

	foreach ( $membership_levels as $level_id ) {
		assign_membership_to_post( $post_id, $level_id );
	}
	error_log('Completed membership assignment for post ID: ' . $post_id);
}

function determine_membership_levels( $state, $damage_types ) {
	error_log('Determining levels for state: ' . $state . ' with types: ' . implode(', ', $damage_types));
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
	error_log('Assigning levels to post ID: ' . $post_id . ' Levels: ' . implode(', ', $level_ids));

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
	error_log('Assigned levels successfully to post ID: ' . $post_id);
}
