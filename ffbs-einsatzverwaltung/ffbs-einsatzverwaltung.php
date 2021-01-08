<?php
/*
Plugin Name: FFBS Einsatzverwaltung
Plugin URI: https://github.com/mybecks/einsatzverwaltung
Description: Einsatzverwaltung der FF Bad Schönborn
Version: 0.1.1
Author: Andre Becker
Author URI: ffbs.de
License: MIT
*/

define( 'MISSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( MISSIONS_PLUGIN_DIR . 'class.ffbs-einsatzverwaltung.php'               );
require_once( MISSIONS_PLUGIN_DIR . 'class.ffbs-einsatzverwaltung-db.php'        );
require_once( MISSIONS_PLUGIN_DIR . 'class.ffbs-einsatzverwaltung-admin.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'class.ffbs-einsatzverwaltung-custom-post.php' );
require_once( MISSIONS_PLUGIN_DIR . 'class.ffbs-einsatzverwaltung-widget.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'ffbs-einsatzverwaltung-constants.php'          );

register_activation_hook( __FILE__, 'plugin_activation' );
register_deactivation_hook( __FILE__, 'plugin_deactivation' );

$wpEinsatzverwaltung = new Einsatzverwaltung();

// add_shortcode( 'einsatzverwaltung', array( 'Einsatzverwaltung', 'my_einsatzverwaltung_handler' ));
// add_action( 'init', array( 'Einsatzverwaltung', 'init' ) );

?>
