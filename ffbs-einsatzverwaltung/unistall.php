if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_vehicles              = $wpdb->prefix . "fahrzeuge";
$table_missions              = $wpdb->prefix . "einsaetze";
$table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

// First clean up all custom posts
$custom_posts = get_posts( array( 'post_type' => 'mission', 'numberposts' => -1 ) );
foreach( $custom_posts as $post ) {
    // Delete's each post.
    wp_delete_post( $post->ID, true );
}

//Second remove plugin specific tables
$sql_missions_has_vehicles = "DELETE TABLE $table_missions_has_vehicles";
dbDelta( $sql_missions_has_vehicles );

$sql_vehicles = "DELETE TABLE $table_vehicles";
dbDelta( $sql_vehicles );

$sql_missions = "DELETE TABLE $table_missions";
dbDelta( $sql_missions );
