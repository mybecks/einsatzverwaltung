<?php
/*
Plugin Name: Einsatzverwaltung 2.0
Plugin URI: http://la.ffbs.de
Description: Einsatzverwaltung der FF Langenbruecken
Version: 0.0.66
Author: Andre Becker
Author URI: la.ffbs.de
License: GPL2
*/

define( 'MISSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung.php'               );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-db.php'        );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-admin.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-custom-post.php' );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-widget.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'einsatzverwaltung-constants.php'          );

register_activation_hook( __FILE__, array( 'Einsatzverwaltung', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Einsatzverwaltung', 'plugin_deactivation' ) );

$wpEinsatzverwaltung = new Einsatzverwaltung()

// add_shortcode( 'einsatzverwaltung', array( 'Einsatzverwaltung', 'my_einsatzverwaltung_handler' ));
// add_action( 'init', array( 'Einsatzverwaltung', 'init' ) );

?>
