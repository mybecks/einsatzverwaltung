<?php
/**
 * Handler for all database related requests
 * @author Andre Becker
 */
class DatabaseHandler {
    /** Refers to a single instance of this class. */
    private static $instance = null;

    /**
     * Creates or returns an instance of this class.
     *
     * @return A single instance of this class.
     * @author Andre Becker
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

    /**
     *
     * Mission Related Requests
     * 
     **/
    

    /**
     * Delete mission by post id
     * 
     * @param  int $post_id
     * @author Andre Becker
     */
    public function delete_mission_by_post_id( $post_id ) {
        global $wpdb;
        $table_missions = $wpdb->prefix . "einsaetze";

        $mission = $this->load_mission_by_post_id( $post_id );

        $query = "DELETE FROM " . $table_missions . " WHERE wp_posts_ID = %d";
        $result = $wpdb->query( $wpdb->prepare( $query, $post_id ) );

        $this->remove_vehicles_from_mission( $mission->id );
    }


    /**
     * Update mission
     * @return [type] [description]
     * @author Andre Becker
     */
    public function update_mission() {

    }

    /**
     * Returns array with all mission years
     *
     * @return array()
     * @author Andre Becker
     * */
    public function get_mission_years() {
        global $wpdb;
        $array = array();
        $table_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT YEAR(alarmierung_date) AS Year FROM " . $table_missions . " GROUP BY Year DESC";

        $years = $wpdb->get_results( $query );

        foreach ( $years as $year ) {
            if ( 1970 != $year->Year )
                $array[] = $year->Year;
        }

        if ( empty( $array ) ) {
            array_push( $array, CURRENT_YEAR );
        }

        return $array;
    }

    /**
     * Get mission details by post id
     * 
     * @param  int $post_id
     * @return mission
     * @author Andre Becker
     */
    public function get_mission_details_by_post_id( $post_id ) {
        global $wpdb;
        $table_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT id, art_alarmierung, alarmstichwort, freitext, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media FROM "
                 . $table_missions . " WHERE wp_posts_ID = " . $post_id;
        $mission = $wpdb->get_results( $query );

        return $mission;
    }

    /**
     * Load missions by mission_id
     *
     * @param int id
     * @return array()
     * @author Andre Becker
     * */
    public function load_mission_by_id( $id ) {
        global $wpdb;
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT * FROM " . $table_name_missions . " WHERE id = " . $id;
        $mission_details = $wpdb->get_row( $query );

        return $mission_details;
    }

    /**
     * Load vehicles bound to mission
     *
     * @param $mission_id
     * @return array()
     * @author Andre Becker
     * */
    public function load_vehicles_by_mission_id( $mission_id ) {
        global $wpdb;
        $table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
        $table_name_vehicles =     $wpdb->prefix . "fahrzeuge";

        $query = "SELECT f.description FROM " . $table_name_vehicles . 
                 " as f, " . $table_name_missions_has_vehicles . " as h WHERE f.id = h.fahrzeuge_id AND h.einsaetze_id = " . $mission_id;

        $vehicles = $wpdb->get_results( $query );

        return $vehicles;
    }

    /**
     * Load missions bound to post id
     *
     * @param post    id
     * @return mission
     * @author Andre Becker
     * */
    public function load_mission_by_post_id( $id ) {
        global $wpdb;
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT * FROM " . $table_name_missions . " WHERE wp_posts_ID = " . $id;
        $mission_details = $wpdb->get_row( $query );

        return $mission_details;
    }

    /**
     * Collect missions by year DESC
     *
     * @return array()
     * @author Andre Becker
     * */
    public function get_missions_by_year( $year ) {
        global $wpdb;
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $arr_months = array();

        $query = "SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) AS Month, freitext " .
            "FROM " . $table_name_missions .
            " WHERE YEAR(alarmierung_date) = " . $year .
            " ORDER BY alarmierung_date DESC, alarmierung_time DESC";

        $missions = $wpdb->get_results( $query );
        
        foreach ( $missions as $mission ) {

            //http://stackoverflow.com/questions/1195549/php-arrays-and-solution-to-undefined-index-errors

            if ( ! array_key_exists( $mission->Month, $arr_months ) ) {
                $arr_months[$mission->Month] = array();
            }

            foreach ( $arr_months as $key => $value ) {

                if ( $key == $mission->Month ) {

                    $tmp_arr = $arr_months[$key];

                    $arr_content = array();

                    $post = get_post( $mission->wp_posts_ID );


                    if ( 0 != strlen( $post->post_content ) ) {
                        $description = "Bericht";
                    } else {
                        $description = "Kurzinfo";
                    }


                    if ( 'Freitext' == $mission->alarmstichwort || 'Sonstiger Brand' == $mission->alarmstichwort ) {
                        $alarmstichwort = $mission->freitext;
                    } else {
                        $alarmstichwort = $mission->alarmstichwort;
                    }

                    // if(strlen($alarmstichwort) > 22) {
                    //  // Shortening the string to 22 characters
                    //  $alarmstichwort = substr($alarmstichwort,0,22)."…";
                    // }


                    if ( false !== strpos( $mission->art_alarmierung, 'Brandeinsatz' ) ) {
                        $alarm_short = 'BE';
                    } else if ( false !== strpos( $mission->art_alarmierung, 'Technischer Einsatz' ) ) {
                        $alarm_short = 'TE';
                    } else {
                        $alarm_short = 'SE';
                    }

                    $arr_content[0] = $alarm_short;
                    $arr_content[1] = $alarmstichwort;
                    $arr_content[2] = $mission->alarm_art;
                    $arr_content[3] = $mission->einsatzort;
                    $arr_content[4] = strftime( "%d.%m.%Y", strtotime( $mission->alarmierung_date ) );
                    $arr_content[5] = strftime( "%H:%M", strtotime( $mission->alarmierung_time ) );
                    $arr_content[6] = $mission->rueckkehr_date;
                    $arr_content[7] = $mission->rueckkehr_time;
                    $arr_content[8] = $mission->link_to_media;
                    $arr_content[9] = $description;
                    $arr_content[10] = get_permalink( $mission->wp_posts_ID );

                    array_push( $tmp_arr, $arr_content );

                    $arr_months[$key] = $tmp_arr;
                }
            }
        }

        // reverse sort of months (12,11,10,...,1)
        krsort( $arr_months );

        return $arr_months;
    }

    /**
     *
     * Vehicle Related Requests
     * 
     **/

    /**
     * Load all vehicles
     *
     * @return array()
     * @author Andre Becker
     * */
    public function load_vehicles() {
        global $wpdb;
        $table_vehicles = $wpdb->prefix . "fahrzeuge";

        $query = "SELECT id, description FROM " . $table_vehicles;
        $vehicles = $wpdb->get_results( $query );

        return $vehicles;
    }

    /**
     * Remove vehicle from mission
     * @param  int $mission_id [description]
     * @author Andre Becker
     */
    public function remove_vehicles_from_mission( $mission_id ) {
        global $wpdb;
        $table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
        // $wpdb->show_errors();
        $query = "DELETE FROM " . $table_missions_has_vehicles . " WHERE einsaetze_id = %d";
        $delete = $wpdb->query( $wpdb->prepare( $query, $mission_id ) );

        // $wpdb->print_error();
        // wp_die($delete);
        // $wpdb->hide_errors();
    }

    /**
     * Insert new vehicles to mission
     * 
     * @param int $mission_id [description]
     * @param int $vehicle_id [description]
     * @author Andre Becker
     */
    public function insert_new_vehicle_to_mission( $mission_id, $vehicle_id ) {
        global $wpdb;
        $table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

        $wpdb->insert(
            $table_missions_has_vehicles,
            array(
                'einsaetze_id' => $mission_id,
                'fahrzeuge_id' => $vehicle_id
            ), 
            array() );
    }

    /**
     * Insert a new vehicle in the database
     * 
     * @param  string $description
     * @author Andre Becker
     */
    public function admin_insert_vehicle( $description ) {
        global $wpdb;
        
        $table_vehicles = $wpdb->prefix . "fahrzeuge";

        $wpdb->insert(
            $table_vehicles,
            array(
                'description' => $_POST['add_new_vehicle']
            ),
            array(
                '%s'
            )
        );
    }
}

?>
