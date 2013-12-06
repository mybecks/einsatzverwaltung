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
    }

    // DatabaseHandler::get_instance();
?>
