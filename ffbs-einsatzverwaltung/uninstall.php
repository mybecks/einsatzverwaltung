<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

ffbs_einsatzverwaltung_uninstall_plugin();

function ffbs_einsatzverwaltung_uninstall_plugin()
{

    if (!current_user_can('activate_plugins')) {
        return;
    }

    //Prevent accidental deletion of content
    delete_custom_posts();
    drop_tables();
    drop_tables_old();
}


function delete_custom_posts()
{
    // fetch posts of type mission
    $args = array(
        'post_type' => 'mission',
        'numberposts' => -1
    );

    $custom_posts = get_posts($args);

    foreach ($custom_posts as $post) {
        // Delete's each post.
        wp_delete_post($post->ID, true);
    }

    unregister_post_type('mission');
    wp_reset_postdata();
}

function drop_tables_old()
{
    global $wpdb;

    $table_vehicles = $wpdb->prefix . "fahrzeuge";
    $table_missions = $wpdb->prefix . "einsaetze";
    $table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

    $sql_missions_has_vehicles = "DROP TABLE IF EXISTS $table_missions_has_vehicles";
    $wpdb->query($sql_missions_has_vehicles);

    $sql_vehicles = "DROP TABLE IF EXISTS $table_vehicles";
    $wpdb->query($sql_vehicles);

    $sql_missions = "DROP TABLE IF EXISTS $table_missions";
    $wpdb->query($sql_missions);
}

function drop_tables()
{
    global $wpdb;

    $table_vehicles = $wpdb->prefix . "ffbs_vehicles";
    $table_missions = $wpdb->prefix . "ffbs_missions";
    $table_moved_out_vehicles = $wpdb->prefix . "ffbs_moved_out_vehicles";
    $table_settings = $wpdb->prefix . "ffbs_settings";

    $sql_missions_has_vehicles = "DROP TABLE IF EXISTS $table_moved_out_vehicles";
    $wpdb->query($sql_missions_has_vehicles);

    $sql_vehicles = "DROP TABLE IF EXISTS $table_vehicles";
    $wpdb->query($sql_vehicles);

    $sql_missions = "DROP TABLE IF EXISTS $table_missions";
    $wpdb->query($sql_missions);

    $sql_settings = "DROP TABLE IF EXISTS $table_settings";
    $wpdb->query($sql_settings);
}
