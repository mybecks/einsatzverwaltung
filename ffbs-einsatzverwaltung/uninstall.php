<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

ffbs_einsatzverwaltung_uninstall_plugin();

function ffbs_einsatzverwaltung_uninstall_plugin () {

    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    delete_custom_posts();
    drop_tables();
}


function delete_custom_posts () {

    // fetch posts of type mission
    $args = array(
        'post_type' => 'mission',
        'numberposts' => -1
    );

    $custom_posts = get_posts( $args );

    foreach( $custom_posts as $post ) {
        // Delete's each post.
        wp_delete_post( $post->ID, true );
    }

    wp_reset_postdata();
}

function drop_tables () {
    global $wpdb;
    $table_vehicles              = $wpdb->prefix . "fahrzeuge";
    $table_missions              = $wpdb->prefix . "einsaetze";
    $table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

    $sql_missions_has_vehicles = "DROP TABLE $table_missions_has_vehicles";
    dbDelta( $sql_missions_has_vehicles );

    $sql_vehicles = "DROP TABLE $table_vehicles";
    dbDelta( $sql_vehicles );

    $sql_missions = "DROP TABLE $table_missions";
    dbDelta( $sql_missions );
}
