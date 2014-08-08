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
    
    /**
     *
     * Mission Related Requests
     * 
     **/
    

    /**
     * Update mission
     * @return [type] [description]
     * @author Andre Becker
     */
    public function update_mission() {

    }

    /**
     *
     * Vehicle Related Requests
     * 
     **/

    /**
     * Remove vehicle from mission
     * @param  int $mission_id [description]
     * @author Andre Becker
     */
    public function remove_vehicles_from_mission( $mission_id ) {
        global $wpdb;
        $table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

        $query = "DELETE FROM ". $table_name_missions_has_vehicles ." WHERE einsaetze_id = ".$mission_id;
        //fire delete query!
        $delete = $wpdb->query( $query );
    }

    /**
     * Insert new vehicles to mission
     * @param $mission_id [description]
     * @param $vehicle_id [description]
     * @author Andre Becker
     */
    public function insert_new_vehicle_to_mission( $mission_id, $vehicle_id ) {
        global $wpdb;
        $table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

        $wpdb->insert(
            $table_name_missions_has_vehicles,
            array(
                'einsaetze_id' => $mission_id,
                'fahrzeuge_id' => $vehicle_id
            ), 
            array() );
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
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT YEAR(alarmierung_date) AS Year FROM ".$table_name_missions." GROUP BY Year DESC";

        $years = $wpdb->get_results( $query );

        foreach ( $years as $year ) {
            if ( $year->Year != 1970 )
                $array[] = $year->Year;
        }

        if(empty($array)){
            array_push($array, CURRENT_YEAR);
        }

        return $array;
    }

    public function get_mission_details_by_post_id( $id ) {
        global $wpdb;

        $table_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT id, art_alarmierung, alarmstichwort, freitext, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media FROM ". $table_missions ." WHERE wp_posts_ID = ".$id;

        $mission = $wpdb->get_results( $query );

        return $mission;
    }

    /**
     * Load all vehicles
     *
     * @return array()
     * @author Andre Becker
     * */
    public function load_vehicles() {
        global $wpdb;
        $table_name_vehicles = $wpdb->prefix . "fahrzeuge";

        $query = "SELECT id, description FROM ". $table_name_vehicles;
        $vehicles = $wpdb->get_results( $query );


        return $vehicles;
    }

    /**
     * Load missions by mission_id
     *
     * @param $id
     * @return array()
     * @author Andre Becker
     * */
    public function load_mission_by_id( $id ) {
        global $wpdb;
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT * FROM ". $table_name_missions ." WHERE id = ".$id;
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

        $query = "SELECT f.description FROM ". $table_name_vehicles ." as f, ". $table_name_missions_has_vehicles ." as h WHERE f.id = h.fahrzeuge_id AND h.einsaetze_id = ".$mission_id;

        $vehicles = $wpdb->get_results( $query );

        return $vehicles;
    }

    /**
     * Load missions bound to post id
     *
     * @param post    id
     * @return single mission
     * @author Andre Becker
     * */
    public function load_mission_by_post_id( $id ) {
        global $wpdb;
        $table_name_missions = $wpdb->prefix . "einsaetze";

        $query = "SELECT * FROM ". $table_name_missions ." WHERE wp_posts_ID = ".$id;
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

        $query = "SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) AS Month, freitext ".
            "FROM ".$table_name_missions.
            " WHERE YEAR(alarmierung_date) = ".$year.
            " ORDER BY alarmierung_date DESC, alarmierung_time DESC";

        $missions = $wpdb->get_results( $query );
        
        foreach ( $missions as $mission ) {

            //http://stackoverflow.com/questions/1195549/php-arrays-and-solution-to-undefined-index-errors

            if( !array_key_exists($mission->Month, $arr_months)){
                $arr_months[$mission->Month] = array();
            }

            foreach ( $arr_months as $key => $value ) {

                if ( $key == $mission->Month ) {

                    $tmp_arr = $arr_months[$key];

                    $arr_content = array();

                    $post = get_post( $mission->wp_posts_ID );


                    if ( strlen( $post->post_content )!=0 ) {
                        $description = "Bericht";
                    }
                    else {
                        $description = "Kurzinfo";
                    }


                    if ( 'Freitext' == $mission->alarmstichwort || 'Sonstiger Brand' == $mission->alarmstichwort ) {
                        $alarmstichwort = $mission->freitext;
                    }else {
                        $alarmstichwort = $mission->alarmstichwort;
                    }

                    // if(strlen($alarmstichwort) > 22) {
                    //  // Shortening the string to 22 characters
                    //  $alarmstichwort = substr($alarmstichwort,0,22)."…";
                    // }


                    if ( strpos( $mission->art_alarmierung, 'Brandeinsatz' ) !== false ) {
                        $alarm_short = 'BE';
                    }else if ( strpos( $mission->art_alarmierung, 'Technischer Einsatz' ) !== false ) {
                            $alarm_short = 'TE';
                        }else {
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

        // Umgekehrte Sortierung der Monate (12,11,10,...,1)
        krsort( $arr_months );

        return $arr_months;
    }
}
// DatabaseHandler::get_instance();
?>
