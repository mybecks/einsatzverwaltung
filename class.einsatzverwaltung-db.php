<?php
    /**
     * 
     */
    class DatabaseHandler {
        
        /** Refers to a single instance of this class. */
        private static $instance = null;

        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo A single instance of this class.
         */
        public static function get_instance() {
     
            if ( null == self::$instance ) {
                self::$instance = new self;
            }
     
            return self::$instance;
     
        }
     
        /**
         * Constructor
         */
        private function __construct() {
     
        }

        public function admin_insert_vehicle( $description ) {
            global $wpdb;
            
            $table_name_vehicles = $wpdb->prefix . "fahrzeuge";

            $wpdb->insert(
                $table_name_vehicles,
                array(
                    'description' => $_POST['add_new_vehicle']
                ),
                array(
                    '%s'
                )
            );
        }

        public function get_mission_details_by_post_id( $id ) {
            global $wpdb;

            $table_missions = $wpdb->prefix . "einsaetze";

            $query = "SELECT id, art_alarmierung, alarmstichwort, freitext, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media FROM ". $table_missions ." WHERE wp_posts_ID = ".$id;

            $mission = $wpdb->get_results( $query );

            return $mission;
        }
    }

    // DatabaseHandler::get_instance();
?>
