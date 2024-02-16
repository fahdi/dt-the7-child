<?php

/**
 * NDDL Shortcodes
 *
 */


// TOTAL-LEADS

function total_number_of_leads() {
	
	$count_posts = wp_count_posts('lead');
	
	$total_posts = $count_posts->publish;
	
	echo '<span class="lead-count total">' . $total_posts . '</span>';
	
}

add_shortcode('leads-total', 'total_number_of_leads');


// LEADS-LAST-WEEK

function number_of_leads_last_week() {
	
	$week_query_args = array(
		'posts_per_page' => -1,    // No limit
		'post_type'      => 'lead',
		'fields'         => 'property_address', // Reduce memory footprint
		'date_query'     => array(
			array( 'after' => '1 week ago' )
		)
	);
    
    $week_preview_query = new WP_Query($week_query_args);
		
    $count_last_week_posts = $week_preview_query->found_posts;
	
	echo '<span class="lead-count last-week">' . $count_last_week_posts . '</span>';

}

add_shortcode('leads-last-week', 'number_of_leads_last_week');


// LEADS-LAST-MONTH

function number_of_leads_last_month() {
	
	$month_query_args = array(
		'posts_per_page' => -1,    // No limit
		'post_type'      => 'lead',
		'fields'         => 'property_address', // Reduce memory footprint
		'date_query'     => array(
			array( 'after' => '1 month ago' )
		)
	);
    
    $month_preview_query = new WP_Query($month_query_args);
		
    $count_last_month_posts = $month_preview_query->found_posts;
	
	echo '<span class="lead-count last-month">';
	
	echo $count_last_month_posts;
	
	echo '</span>';

}

add_shortcode('leads-last-month', 'number_of_leads_last_month');

function format_phone_number_value( $value, $post_id, $field ) {
	// Check if the value is not empty and is a phone number
	if ( !empty($value) && is_numeric($value) && strlen($value) == 10 ) {
		// Format the phone number
		$formatted_value = sprintf("(%s) %s-%s",
			substr($value, 0, 3),
			substr($value, 3, 3),
			substr($value, 6, 4)
		);

		// Return the formatted phone number
		return $formatted_value;
	}

	// Return the original value if conditions are not met
	return $value;
}

// Apply the filter for each phone field
add_filter('acf/format_value/name=phone', 'format_phone_number_value', 10, 3);
add_filter('acf/format_value/name=phone_2', 'format_phone_number_value', 10, 3);
add_filter('acf/format_value/name=phone_3', 'format_phone_number_value', 10, 3);
add_filter('acf/format_value/name=phone_4', 'format_phone_number_value', 10, 3);
add_filter('acf/format_value/name=phone_5', 'format_phone_number_value', 10, 3);
