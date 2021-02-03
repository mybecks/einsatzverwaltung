<?php

/**
 * Handler for all database related requests
 * @author Andre Becker
 */

//http://wordpress.stackexchange.com/questions/73868/queries-inside-of-a-class
class DatabaseHandler
{
    /** Refers to a single instance of this class. */
    private static $instance = null;
    private $table;

    /**
     * Creates or returns an instance of this class.
     *
     * @return A single instance of this class.
     * @author Andre Becker
     */
    public static function get_instance()
    {

        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = (object) array(
            "missions" => $this->db->prefix . "einsaetze",
            "mission_has_vehicles" => $this->db->prefix . "einsaetze_has_fahrzeuge",
            "vehicles" => $this->db->prefix . "fahrzeuge"
        );
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
    public function delete_mission_by_post_id($post_id)
    {
        $mission = $this->load_mission_by_post_id($post_id);

        // $query = "DELETE FROM " . $this->table->missions . " WHERE wp_posts_ID = %d";
        // $result = $this->db->query( $this->db->prepare( $query, $post_id ) );
        $this->db->delete(
            $this->table->missions,
            array('wp_posts_ID' => $post_id),
            array('%d')
        );

        $this->remove_vehicles_from_mission($mission->id);
    }


    /**
     * Update mission
     * @return [type] [description]
     * @author Andre Becker
     */
    public function update_mission()
    {
    }

    /**
     * Returns array with all mission years
     *
     * @return array()
     * @author Andre Becker
     */
    public function get_mission_years()
    {
        $array = array();

        $query = "SELECT YEAR(alarmierung_date) AS Year FROM " . $this->table->missions . " GROUP BY Year DESC";
        $years = $this->db->get_results($query);

        foreach ($years as $year) {
            if (1970 != $year->Year)
                $array[] = $year->Year;
        }

        if (empty($array)) {
            array_push($array, CURRENT_YEAR);
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
    public function get_mission_details_by_post_id($post_id)
    {
        $query = "SELECT id, alarmstichwort, freitext, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media FROM "
            . $this->table->missions . " WHERE wp_posts_ID = %d";
        $mission = $this->db->get_results($this->db->prepare($query, $post_id));

        return $mission;
    }

    /**
     * Load missions by mission_id
     *
     * @param int id
     * @return array()
     * @author Andre Becker
     */
    public function load_mission_by_id($id)
    {
        $query = "SELECT * FROM " . $this->table->missions . " WHERE id = %d";
        $mission_details = $this->db->get_row($this->db->prepare($query, $id));

        return $mission_details;
    }

    /**
     * Load vehicles bound to mission
     *
     * @param int mission id
     * @return array()
     * @author Andre Becker
     */
    public function load_vehicles_by_mission_id($mission_id)
    {
        $query = "SELECT f.description FROM " . $this->table->vehicles .
            " as f, " . $this->table->mission_has_vehicles . " as h WHERE f.id = h.fahrzeuge_id AND h.einsaetze_id = %d";

        $vehicles = $this->db->get_results($this->db->prepare($query, $mission_id));

        return $vehicles;
    }

    /**
     * Load missions bound to post id
     *
     * @param post    id
     * @return mission
     * @author Andre Becker
     */
    public function load_mission_by_post_id($id)
    {
        $query = "SELECT * FROM " . $this->table->missions . " WHERE wp_posts_ID = %d";
        $mission_details = $this->db->get_row($this->db->prepare($query, $id));

        return $mission_details;
    }

    /**
     * Collect missions by year DESC
     *
     * @return array()
     * @author Andre Becker
     */
    public function get_missions_by_year($year)
    {
        $arr_months = array();

        $query = "SELECT id, alarmstichwort, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) AS Month, freitext, article_post_id " .
            "FROM " . $this->table->missions .
            " WHERE YEAR(alarmierung_date) = %d" .
            " ORDER BY alarmierung_date DESC, alarmierung_time DESC";

        $missions = $this->db->get_results($this->db->prepare($query, $year));

        foreach ($missions as $mission) {

            //http://stackoverflow.com/questions/1195549/php-arrays-and-solution-to-undefined-index-errors

            if (!array_key_exists($mission->Month, $arr_months)) {
                $arr_months[$mission->Month] = array();
            }

            foreach ($arr_months as $key => $value) {
                if ($key == $mission->Month) {
                    $tmp_arr = $arr_months[$key];

                    $post = get_post($mission->wp_posts_ID);
                    // var_dump($mission->article_post_id);
                    if (0 != strlen($post->post_content) || $mission->article_post_id) {
                        $description = "Bericht";
                    } else {
                        $description = "Kurzinfo";
                    }

                    if ('Freitext' == $mission->alarmstichwort || 'Sonstiger Brand' == $mission->alarmstichwort) {
                        $alarmstichwort = $mission->freitext;
                    } else {
                        $alarmstichwort = $mission->alarmstichwort;
                    }

                    // if (false !== strpos($mission->art_alarmierung, 'Brandeinsatz')) {
                    //     $alarm_short = 'BE';
                    // } else if (false !== strpos($mission->art_alarmierung, 'Technischer Einsatz')) {
                    //     $alarm_short = 'TE';
                    // } else {
                    //     $alarm_short = 'SE';
                    // }

                    // todo refactor
                    $arr_content = array();
                    // $arr_content[0] = $alarm_short;
                    $arr_content[0] = $alarmstichwort;
                    // $arr_content[2] = $mission->alarm_art;
                    $arr_content[1] = $mission->einsatzort;
                    $arr_content[2] = strftime("%d.%m.%Y", strtotime($mission->alarmierung_date));
                    $arr_content[3] = strftime("%H:%M", strtotime($mission->alarmierung_time));
                    $arr_content[4] = $mission->rueckkehr_date;
                    $arr_content[5] = $mission->rueckkehr_time;
                    $arr_content[6] = $mission->link_to_media;
                    $arr_content[7] = $description;
                    $arr_content[8] = get_permalink($mission->wp_posts_ID);

                    array_push($tmp_arr, $arr_content);

                    $arr_months[$key] = $tmp_arr;
                }
            }
        }

        // reverse sort of months (12,11,10,...,1)
        krsort($arr_months);

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
     */
    public function load_vehicles()
    {
        $query = "SELECT id, description, radio_id, location FROM " . $this->table->vehicles;
        $vehicles = $this->db->get_results($query);

        return $vehicles;
    }

    /**
     * Remove vehicle from mission
     * @param  int $mission_id
     * @author Andre Becker
     */
    public function remove_vehicles_from_mission($mission_id)
    {
        // $query = "DELETE FROM " . $this->table->missions_has_vehicles . " WHERE einsaetze_id = %d";
        // $delete = $this->db->query( $this->db->prepare( $query, $mission_id ) );
        $this->db->delete(
            $this->table->missions_has_vehicles,
            array('einsaetze_id' => $mission_id),
            array('%d')
        );
    }

    /**
     * Insert new vehicles to mission
     *
     * @param int $mission_id
     * @param int $vehicle_id
     * @author Andre Becker
     */
    public function insert_new_vehicle_to_mission($mission_id, $vehicle_id)
    {
        $this->db->insert(
            $this->table->mission_has_vehicles,
            array(
                'einsaetze_id' => $mission_id,
                'fahrzeuge_id' => $vehicle_id
            ),
            array(
                '%d',
                '%d'
            )
        );
    }

    /**
     * Insert a new vehicle in the database
     *
     * @param  string $description
     * @author Andre Becker
     */
    public function admin_insert_vehicle($vehicle)
    {
        $this->table->vehicles = $this->db->prefix . "fahrzeuge";
        wp_die($vehicle);
        $this->db->insert(
            $this->table->vehicles,
            array(
                'description' => $vehicle['description'],
                'radio_id' => $vehicle['radio_id'],
                'location' => $vehicle['location']
            ),
            array(
                '%s',
                '%s',
                '%s'
            )
        );
    }

    public function get_last_insert_id()
    {
        return $this->db->insert_id;
    }
}
