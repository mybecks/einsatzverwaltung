# Todos

## Backend
### go live
* [ x ] Fix uninstall of plugin
* [ x ] Vehicles: extend creation with an additional field for a image from the media gallery
* [ x ] Custom Post: Extend with possibility to link a post (wp_get_content on frontend)
* [ x ] Refactor tables:
    * Remove "Einsatz  Art" field
    * Remove "Alarm" field
    * "Freitext" should be permanent visible
* [ x ] Fix "update" of custom post
* [ ] refactor ..-admin.php to extend from WP_REST_Controller `$ffbs_rest_controler = new RestContller(); $ffbs_rest_controller->register_routes();` in `add_action('rest_api_init', array(....));`
* [ x ] Dropdown with post from specific category in custom post
* [ x ] refactor selector_for_dropdown function to just include once
* [ x ] permalink: /mission/2021/02/2021_02_th-hoelle-friert-zu/ -> /einsatz/2021/02/th-hoelle-friert-zu/* [ ] disable auto-draft in custom post
* [ x ] customize article category for "link to article"
* [ x ] Replace table in custom box with bootstrap coding

## Database
* [ x ] rename table names to `en`
* [ x ] rename columns of tables to `en`
    * [ x ] ffbs_mission
    * [ x ] ffbs_vehicles
    * [ x ] ffbs_moved_out_vehicles
* [ x ] prefix tables with `ffbs_`
* [ ] move db table names to constants
* [ ] refactor db handler
* [ ] move creation of tables to db handler

## UI Parts
* [ ] restructure UI components e.g. create partials
    * [ x ] settings page

## Vehicles Page
* [ x ] extend db with retierement option
* [ ] modify
* [ x ] delete
* [ x ] validate input
* [ ] Implement Mediathek chooser: https://wordpress.stackexchange.com/questions/235406/how-do-i-select-an-image-from-media-library-in-my-plugin

## Misc
* [ ] add ffbs_ prefix to all custom functions ...
* [ ] restructure plugin
* [ ] translate
* [ ] check for auto-draft

## Frontend
