<?php

/*
Plugin Name: PPM Guru Mailing List
Description: Custom email list building plugin for WordPress. Capture new subscribers.Build unlimited lists. Import and export subscribers easily with .csv
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
		
	6. HELPERS
		
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



/* !2. SHORTCODES */

function pgm_register_shortcodes(){
    add_shortcode('pgm_form','pgm_form_shortcode');
}

function pgm_form_shortcode($args, $content = ""){
    //check for id from args and assign it to $id if it is available, else set to 0.
    $list_id = 0;
    if(isset($args['id'])){
        $list_id = (int)$args['id'];
    }
    //form html
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
       <form id="pgm_form" name="pgm_form" method="post"
       action="/wp-admin/admin-ajax.php?action=pgm_save_subscription" method="post">
       
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
    );
    
    return $columns;
}


/* !4. EXTERNAL SCRIPTS */




/* !5. ACTIONS */
//Function to save subscription data to an existing or new subscriber, wp ajax handler will redirect form data to this function.
function pgm_save_subscription(){
    //setting up default result array
    $result = array(
        'status'=>0,
        'message'=>'Subscription was not saved.',
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
        
        //attempt to create/save/update subscriber
        $subscriber_id = pgm_save_subscriber($subscriber_data); 
        
        if($subscriber_id){
            //Check if subscriber already has a subscription to the list
            if(pgm_subscriber_has_subscription($subscriber_id,$list_id)){
                
                $list = get_post($list_id);
                
                //return error message
                $result['message'].=esc_attr($subscriber_data['email'].' is already subscribed to list '.$list->post_title.'.');
            } else {
                //save the new subscription
                $subscription_saved = pgm_add_subscription($subscriber_id,$list_id);
            }
            
            if($subscription_saved){
                $result['status'] = 1;
                $result['message'] = 'Subscription saved';
            }
            
        }
    } catch(Exception $e){
        
    } 
    
    //Return result as a JSON string
    pgm_return_json($result);
}

function pgm_save_subscriber($subscriber_data){
    //set default id=0
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
        
        //Do something...
    }
    
    wp_reset_query();
    
    return $subscriber_id;
}




/* !6. HELPERS */
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




/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */




/* !10. MISCELLANEOUS */



