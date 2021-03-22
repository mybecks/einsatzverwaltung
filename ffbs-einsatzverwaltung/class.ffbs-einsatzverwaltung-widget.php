<?php

/**
 * Widget class
 **/

// http://www.wpbeginner.com/wp-tutorials/how-to-create-a-custom-wordpress-widget/ -- custom
class Einsatzverwaltung_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'einsatzverwaltung_widget', // Base ID
            'Einsatzverwaltung', // Name
            array('description' => __('Last 3 missions in the current year', 'einsatzverwaltung_textdomain'),) // Args
        );
    }

    public function form($instance)
    {
        // outputs the options form on admin
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'einsatzverwaltung_textdomain');
        }
        // Widget admin form
?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
    <?php
    }

    public function update($new_instance, $old_instance)
    {
        // processes widget options to be saved
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        // outputs the content of the widget

        $title = apply_filters('widget_title', $instance['title']);
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];

        if (!empty($title)) {
            echo $args['before_title'] . $title . ' ' . date('Y') . $args['after_title'];
        }

        $this->db_handler = DatabaseHandler::get_instance();
        $missions = $this->db_handler->list_last_missions(3);

        $html = "<ul>";
        foreach ($missions as $mission) {
            $date = date("d.m", strtotime($mission->alarm_date));
            $html .= "<li>" . $date  . " " . $mission->category . " - " . $mission->keyword . "<li>";
        }
        $html .= "</ul>";
        echo $html;
        echo $args['after_widget'];
    }
}


class Einsatzverwaltung_Counter_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'einsatzverwaltung_counter_widget', // Base ID
            'Einsatzverwaltung Counter', // Name
            array('description' => __('Mission per Year', 'einsatzverwaltung_textdomain'),) // Args
        );
    }

    public function form($instance)
    {
        // outputs the options form on admin
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'einsatzverwaltung_textdomain');
        }
        // Widget admin form
    ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        // processes widget options to be saved
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        // outputs the content of the widget

        $title = apply_filters('widget_title', $instance['title']);
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];

        if (!empty($title)) {
            echo $args['before_title'] . $title . ' ' . date('Y') . $args['after_title'];
        }

        $this->db_handler = DatabaseHandler::get_instance();
        $result = $this->db_handler->get_missions_count_by_year(date('Y'));

        $html = "<span class='counter'>" . $result . "</span>";

        echo $html;
        echo $args['after_widget'];
    }
}

/**
 * Initialize Widget
 **/
function einsatzverwaltung_widgets_init()
{
    register_widget('Einsatzverwaltung_Widget');
    register_widget('Einsatzverwaltung_Counter_Widget');
}

add_action('widgets_init', 'einsatzverwaltung_widgets_init');

?>
