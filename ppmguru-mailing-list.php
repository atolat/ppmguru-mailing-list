<?php

/*
Plugin Name: PPM Guru Mailing List
Description: Custom email list building plugin for ppmguru. Capture new subscribers, premium/basic. Build lists for topics. Import and export subscribers with .csv
Version: 1.0
Author: Arpan Tolat
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ppmguru-mailing-list
*/

/* !0. TABLE OF CONTENTS */

/*

	1. HOOKS
        1.1 register shortcodes on init
        1.2 add filter to register custom admin column headers
        1.3 add filter to register custom column data

	2. SHORTCODES
        2.1 pgm_register_shortcodes()
        2.2 pgm_form_shortcode()

	3. FILTERS
        3.1 pgm_subscriber_column_headers($columns)
        3.2 pgm_subscriber_column_data($columns,$post_id)
        3.3 pgm_register_custom_admin_titles()
        3.4 pgm_custom_admin_titles( $title, $post_id )
        3.5 pgm_list_column_headers($columns)

	4. EXTERNAL SCRIPTS

	5. ACTIONS
        5.1 pgm_save_subscription()
        5.2 pgm_save_subscriber($subscriber_data)
        5.3 pgm_add_subscription( $subscriber_id, $list_id )

	6. HELPERS
        6.1 pgm_has_subscriptions()
		6.2 pgm_get_subscriber_id()
		6.3 pgm_get_subscritions()
		6.4 pgm_return_json()
		6.5 pgm_get_acf_key()
		6.6 pgm_get_subscriber_data()


	7. CUSTOM POST TYPES

	8. ADMIN PAGES

	9. SETTINGS

	10. MISCELLANEOUS 

*/




/* !1. HOOKS */
//1.1 register shortcodes on init
add_action('init','pgm_register_shortcodes');

//1.2 add filter to register custom admin column headers
add_filter('manage_edit-pgm_subscriber_columns','pgm_subscriber_column_headers');
add_filter('manage_edit-pgm_list_columns','pgm_list_column_headers');

//1.3 add filter to register custom column data
add_filter('manage_pgm_subscriber_posts_custom_column','pgm_subscriber_column_data',1,2);
add_action('admin_head-edit.php','pgm_register_custom_admin_titles');
add_filter('manage_pgm_list_posts_custom_column','pgm_list_column_data',1,2);

// 1.4
// register ajax actions
add_action('wp_ajax_nopriv_pgm_save_subscription', 'pgm_save_subscription'); // regular website visitor
add_action('wp_ajax_pgm_save_subscription', 'pgm_save_subscription'); // admin user

//1.5
//load external file
add_action('wp_enqueue_scripts','pgm_public_scripts');

// 1.6
// Advanced Custom Fields Settings
add_filter('acf/settings/path', 'pgm_acf_settings_path');
add_filter('acf/settings/dir', 'pgm_acf_settings_dir');
add_filter('acf/settings/show_admin', 'pgm_acf_show_admin');
if( !defined('ACF_LITE') ) define('ACF_LITE',true); // turn off ACF plugin menu



/* !2. SHORTCODES */
//2.1
function pgm_register_shortcodes(){
    add_shortcode('pgm_form','pgm_form_shortcode');
}

//2.2
function pgm_form_shortcode($args, $content = ""){
    //check for id from args and assign it to $id if it is available, else set to 0.
    $list_id = 0;
    if(isset($args['id'])){
        $list_id = (int)$args['id'];
    }
    //form html-edit action firld to point to admin-ajax on the server.
    $output = '
    <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
</head>
<body>
<div class="starter-template">
        <h1>Sign Up Form</h1>
      </div>
<div class="container">
    <div class="col-sm-6">
       <form id="pgm_form" name="pgm_form" class="pgm-form" method="post"
       action="/wordpress-plugin-course/wp-admin/admin-ajax.php?action=pgm_save_subscription" method="post">

       <input type="hidden" name="pgm_list" value="'. $list_id .'"> 
          <div class="form-group">
             <label for="pgm_fname">First Name</label>
              <input type="text" class="form-control" name="pgm_fname">
          </div>
          <div class="form-group">
             <label for="pgm_lname">Last Name</label>
              <input type="text" class="form-control" name="pgm_lname">
          </div>
          <div class="pgm_email">
             <label for="pgm_email">Email</label>
              <input type="email" class="form-control" name="pgm_email">
          </div><br>';

    if(strlen($content)){
        $output.= '<div>'.$content.'</div>';
    } 

    $output.='<input class="btn btn-primary" type="submit" name="pgm_submit" value="Sign Me Up!"> <br><br>

       </form>

    </div>


</div>

</body>

</html>';

    return $output;

}




/* !3. FILTERS */
//3.1 This function will take in the admin colums from the subscriber post page and override the column names.
function pgm_subscriber_column_headers($columns){
    $columns = array(
        'cb'=>'<input type="checkbox" />', //checkbox-HTML empty checkbox
        'title'=>__('Subscriber Name'), //update header name to 'Subscriber Name'
        'email'=>__('Email Address'), //create new header for email
    );

    return $columns;
}


//3.2 This function will take in the column data, check the header for title or email and get the corresponding data, append it to the output stream and echo it. 
function pgm_subscriber_column_data($column,$post_id){
    $output = '';

    switch($column){

        case 'title':
            $fname = get_field('pgm_fname', $post_id);
            $lname = get_field('pgm_lname', $post_id);
            $output .= $fname.' '.$lname;
            break;
        case 'email':
            $email = get_field('pgm_email', $post_id);
            $output .= $email;
            break;
    }

    echo $output;
}

//3.3 registers special custom admin title columns- fix for newer version of wordpress
function pgm_register_custom_admin_titles() {
    add_filter('the_title','pgm_custom_admin_titles',99,2);
}

//3.4 handles custom admin title "title" column data for post types without titles, we are not using wordpress default titles in our custom field.
function pgm_custom_admin_titles( $title, $post_id ) {

    global $post;

    $output = $title;

    if( isset($post->post_type) ):
    switch( $post->post_type ) {
        case 'pgm_subscriber':
            $fname = get_field('pgm_fname', $post_id );
            $lname = get_field('pgm_lname', $post_id );
            $output = $fname .' '. $lname;
            break;
    }
    endif;

    return $output;
}

//3.5 function to handle list post headers
function pgm_list_column_headers($columns){
    $columns = array(
        'cb'=>'<input type="checkbox" />', //checkbox-HTML empty checkbox
        'title'=>__('Lists'), //update header name to 'List Name'
        'shortcode'=>__('Shortcode'),
    );

    return $columns;
}

//3.5 function to add shotcodes to list column data
function pgm_list_column_data($column,$post_id){
    $output = '';

    switch($column){
        case 'shortcode':
            $output .= '[pgm_form id="'. $post_id .'"]';
            break;
    }

    echo $output;
}


/* !4. EXTERNAL SCRIPTS */

//4.1
function pgm_public_scripts(){
    wp_register_script('ppmguru-mailing-list-js-public',plugins_url('/js/public/ppmguru-mailing-list.js',__FILE__), array('jquery'),'',true);
    wp_enqueue_script('ppmguru-mailing-list-js-public');
}

//4.2 Include ACF
include_once(plugin_dir_path(__FILE__).'lib/advanced-custom-fields/acf.php');



/* !5. ACTIONS */
//5.1 Function to save subscription data to an existing or new subscriber, wp ajax handler will redirect form data to this function.
function pgm_save_subscription(){
    //setting up default result array
    $result = array(
        'status'=>0,
        'message'=>'Subscription was not saved.',
        'error'=>'',
        'errors'=>array()
    );

    try{

        //get list_id
        $list_id = (int)$_POST['pgm_list'];

        //create an array of subscriber data
        $subscriber_data = array(
            'fname' => esc_attr($_POST['pgm_fname']),
            'lname' => esc_attr($_POST['pgm_lname']),
            'email' => esc_attr($_POST['pgm_email']),
        );

        //Setting up empty array to hold error data
        $errors = array();

        // form validation
        if( !strlen( $subscriber_data['fname'] ) ) $errors['fname'] = 'First name is required.';
        if( !strlen( $subscriber_data['email'] ) ) $errors['email'] = 'Email address is required.';
        if( strlen( $subscriber_data['email'] ) && !is_email( $subscriber_data['email'] ) ) $errors['email'] = 'Email address must be valid.';

        if( count($errors)){
            $result['error'] = 'Some fields are still required. ';
            $result['errors'] = $errors;
        } else{

            //attempt to create/save/update subscriber
            $subscriber_id = pgm_save_subscriber($subscriber_data); 

            if($subscriber_id){
                //Check if subscriber already has a subscription to the list
                if(pgm_subscriber_has_subscription($subscriber_id,$list_id)){

                    $list = get_post($list_id);

                    //return error message
                    $result['error'].=esc_attr($subscriber_data['email'].' is already subscribed to list '.$list->post_title.'.');
                } else {
                    //save the new subscription
                    $subscription_saved = pgm_add_subscription($subscriber_id,$list_id);
                }

                if($subscription_saved){
                    $result['status'] = 1;
                    $result['message'] = 'Subscription saved';
                } else{
                    $result['error'] = 'Unable to save subscription.';
                }

            }
        }
    }catch(Exception $e){

    } 

    //Return result as a JSON string
    pgm_return_json($result);
}

//5.2 function to save new subscribers
function pgm_save_subscriber($subscriber_data){
    //set default id=0, subscriber not saved.
    $subscriber_id = 0;

    try{
        $subscriber_id = pgm_get_subscriber_id($subscriber_data['email']);

        if(!$subscriber_id){
            $subscriber_id = wp_insert_post(
                array(
                    'post_type'=>'pgm_subscriber',
                    'post_title'=>$subscriber_data['fname'].' '.$subscriber_data['lname'],
                    'post_status'=>'publish',
                ),
                true
            );
        }

        //add/update custom metadata
        update_field(pgm_get_acf_key('pgm_fname'), $subscriber_data['fname'], $subscriber_id);
        update_field(pgm_get_acf_key('pgm_lname'), $subscriber_data['lname'], $subscriber_id);
        update_field(pgm_get_acf_key('pgm_email'), $subscriber_data['email'], $subscriber_id);
    } catch(Exception $e) {

        //Do something, if php error occurs...
    }

    wp_reset_query();

    return $subscriber_id;
}


//5.3 function adds subscriptions/lists to existing subscribers
function pgm_add_subscription( $subscriber_id, $list_id ) {

    // setup default return value
    $subscription_saved = false;

    // IF the subscriber does NOT have the current list subscription
    if( !pgm_subscriber_has_subscription( $subscriber_id, $list_id ) ){

        // get subscriptions and append new $list_id
        $subscriptions = pgm_get_subscriptions( $subscriber_id );
        $subscriptions[]=$list_id;

        // update pgm_subscriptions
        update_field( pgm_get_acf_key('pgm_subscriptions'), $subscriptions, $subscriber_id );

        // subscriptions updated!
        $subscription_saved = true;

    }

    // return result
    return $subscription_saved;

}






/* !6. HELPERS */
//6.1
function pgm_subscriber_has_subscription($subscriber_id, $list_id){
    //set default value
    $has_subscription = false;

    //get the subscriber from database
    $subscriber = get_post($subscriber_id);

    //get subscriptions from database
    $subscriptions = pgm_get_subscriptions($subscriber_id);

    //check subscriptions for $list_id
    if(in_array($list_id,$subscriptions)){

        $has_subscription = true;
    } else{

        //leave to default
    }

    return $has_subscription;
}

//6.2
function pgm_get_subscriber_id($email) {
    //set default value
    $subscriber_id = 0;

    try{
        $subscriber_query = new WP_Query(
            array(
                'post_type' => 'pgm_subscriber',
                'posts_per_page' => 1,
                'meta_key' => 'pgm_email',
                'meta_query' => array(
                    array(
                        'key' => 'pgm_email',
                        'value'=>$email,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if($subscriber_query->have_posts()){
            $subscriber_query->the_post();
            $subscriber_id = get_the_ID();
        }
    } catch(Exception $e){

    }

    wp_reset_query();

    return (int)$subscriber_id;


}

//6.3
function pgm_get_subscriptions($subscriber_id){
    //initialize empty array
    $subscriptions = array();

    //get subscriptions (returns array of list objects)
    $lists = get_field(pgm_get_acf_key('pgm_subscriptions'),$subscriber_id);

    if($lists){

        if(is_array($lists) && count($lists)){
            foreach($lists as &$list){
                $subscriptions[]=(int)$list->ID;
            }
        }
        elseif(is_numeric($lists)){
            $subscriptions[] = $lists;
        }

    }

    return (array)$subscriptions;
}

//6.4
function pgm_return_json($php_array){
    //encode results as json string
    $json_result = json_encode($php_array);
    //return result
    die($json_result);
    //stop all other processing
    exit;

}

//6.5 Convert between custom field ids generated by acf
function pgm_get_acf_key( $field_name ) {

    $field_key = $field_name;

    switch( $field_name ) {

        case 'pgm_fname':
            $field_key = 'field_57767bd66431b';
            break;
        case 'pgm_lname':
            $field_key = 'field_57767bf76431c';
            break;
        case 'pgm_email':
            $field_key = 'field_57767c396431d';
            break;
        case 'pgm_subscriptions':
            $field_key = 'field_57767c886431e';
            break;

    }

    return $field_key;

}

//6.6
function pgm_get_subscriber_data($subscriber_id){

    $subscriber_data = array();
    $subscriber = get_post($subscriber_id);
    if(isset($subscriber->post_type) && $subscriber->post_type == 'pgm_subscriber'){

        $fname = get_field(pgm_get_acf_key('pgm_fname'),$subscriber_id);
        $lname = get_field(pgm_get_acf_key('pgm_lname'),$subscriber_id);

        $subscriber_data = array(
            'name'=>$fname.' '.$lname,
            'fname'=>$fname,
            'lname'=>$lname,
            'email'=>get_field(pgm_get_acf_key('pgm_email'),$subscriber_id),
            'subscriptions'=>pgm_get_subscriptions($subscriber_id)
        );
    }
    return $subscriber_data;
}



/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */




/* !10. MISCELLANEOUS */