<?php
/*
Plugin Name: FFBS Einsatzverwaltung
Plugin URI: http://ffbs.de
Description: Einsatzverwaltung der FF Bad Schönborn
Version: 0.0.95
Author: Andre Becker
Author URI: ffbs.de
License: GPL2
GitHub Plugin URI: https://github.com/mybecks/einsatzverwaltung
GitHub Branch:     master
*/

define( 'MISSIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung.php'               );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-db.php'        );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-admin.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-custom-post.php' );
require_once( MISSIONS_PLUGIN_DIR . 'class.einsatzverwaltung-widget.php'          );
require_once( MISSIONS_PLUGIN_DIR . 'einsatzverwaltung-constants.php'          );

register_activation_hook( __FILE__, array( 'FFBS Einsatzverwaltung', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'FFBS Einsatzverwaltung', 'plugin_deactivation' ) );

$wpEinsatzverwaltung = new Einsatzverwaltung();

// add_shortcode( 'einsatzverwaltung', array( 'Einsatzverwaltung', 'my_einsatzverwaltung_handler' ));
// add_action( 'init', array( 'Einsatzverwaltung', 'init' ) );

?>
