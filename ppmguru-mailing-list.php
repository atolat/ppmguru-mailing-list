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
        1.1 add_action()
	
	2. SHORTCODES
        2.1 pgm_register_shortcodes()
        2.2 pgm_form_shortcode()
		
	3. FILTERS
		
	4. EXTERNAL SCRIPTS
		
	5. ACTIONS
		
	6. HELPERS
		
	7. CUSTOM POST TYPES
	
	8. ADMIN PAGES
	
	9. SETTINGS
	
	10. MISCELLANEOUS 

*/




/* !1. HOOKS */
add_action('init','pgm_register_shortcodes');



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




/* !4. EXTERNAL SCRIPTS */




/* !5. ACTIONS */




/* !6. HELPERS */




/* !7. CUSTOM POST TYPES */




/* !8. ADMIN PAGES */




/* !9. SETTINGS */




/* !10. MISCELLANEOUS */



