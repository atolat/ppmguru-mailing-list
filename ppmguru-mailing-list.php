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
       <form action="login_create.php" method="post">
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




/* !6. HELPERS */




/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */




/* !10. MISCELLANEOUS */



