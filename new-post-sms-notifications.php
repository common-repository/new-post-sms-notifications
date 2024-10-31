<?php
/**
 * @package New_Post_SMS_Notifications
 * @version 1.0
 */
/*
Plugin Name: New Post SMS Notifications
Plugin URI: https://www.freebulksmsonline.com/free-sms-api/
Description: Notify your users by sms text message when a new post is published. This has two functions. First, it adds a 'mobile' field to users' profiles, along with a 'notifications' check box. Second, it adds a hook to send an SMS to each user when a post is published.
Author: mbomnda @ Free SMS Bulk SMS API
Version: 1.0
Author URI: https://freebulksmsonline.com/
Text Domain: New-Post-SMS-Notifications
License: GPL2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'npsmsn_add_admin_menu' );
add_action( 'admin_init', 'npsmsn_settings_init' );


function npsmsn_add_admin_menu(  ) { 

	add_menu_page( 'New Post SMS Notifications', 'New Post SMS Notifications', 'manage_options', 'new_post_sms_notifications', 'npsmsn_options_page' );

}


function npsmsn_settings_init(  ) { 

	register_setting( 'pluginPage', 'npsmsn_settings' );

	add_settings_section(
		'npsmsn_pluginPage_section', 
		__( 'SMS/Bulk SMS API Settings', 'new-post-sms-notifications' ), 
		'npsmsn_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'npsmsn_text_field_0', 
		__( 'API Token', 'new-post-sms-notifications' ), 
		'npsmsn_text_field_0_render', 
		'pluginPage', 
		'npsmsn_pluginPage_section' 
	);

	add_settings_field( 
		'npsmsn_select_field_1', 
		__( 'User Role (Required)<br>select role and save changes', 'new-post-sms-notifications' ), 
		'npsmsn_select_field_1_render', 
		'pluginPage', 
		'npsmsn_pluginPage_section' 
	);


}


function npsmsn_text_field_0_render(  ) { 

	$options = get_option( 'npsmsn_settings' );
	?>
	<input type='text' name='npsmsn_settings[npsmsn_text_field_0]' value='<?php echo $options['npsmsn_text_field_0']; ?>'>
	<?php

}


function npsmsn_select_field_1_render(  ) { 

	$options = get_option( 'npsmsn_settings' );
	?>
	<select name='npsmsn_settings[npsmsn_select_field_1]'>
	    <?php wp_dropdown_roles($selected=$options['npsmsn_select_field_1']); ?>
	</select>

<?php

}


function npsmsn_settings_section_callback(  ) { 

	echo __( 'Get your API token from <a href="https://freebulksmsonline.com/my-acount/" target="_blank">here</a><br><br><b>Note: </b> API token is not required. The free option is limited to 5 messages per day. <a href="https://freebulksmsonline.com/sign-up/" target="_blank">Sign-up today for one of our scalable plans</a><br><br>The User role is the group of users to be notified of new posts. Examples subscriber, editor, administrator etc<br>You will have to make sure the users have mobile numbers.', 'new-post-sms-notifications' );

}


function npsmsn_options_page(  ) { 

		?>
		<form action='options.php' method='post'>

			<h2>New Post SMS Notifications</h2>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

}

//We want users to be able to sign up for SMS notifications, and we’ll do that by adding a phone field in their user profile.
function npsmsn_modify_contact_methods ($profile_fields) {
    $profile_fields['mobile'] = "Mobile Phone (12223334444) <br> Start with Country code";

    return $profile_fields;
}
add_filter('user_contactmethods','npsmsn_modify_contact_methods');



// Add the hook action
add_action('transition_post_status', 'npsmsn_send_new_post', 10, 3);

// Listen for publishing of a new post
function npsmsn_send_new_post($new_status, $old_status, $post) {
  if('publish' === $new_status && 'publish' !== $old_status && $post->post_type === 'post') {
      
        // Add the hook action
        add_action('publish_post', 'npsmsn_post_published_notification', 10, 2);
        
        //Next, set up a function that is invoked during a publish_post action and use get_users to iterate over all the subscribers of the blog.
        function npsmsn_post_published_notification ( $ID, $post ) {
        
        //Get paramenters for sending in message body
        $npsmsn_options = get_option( 'npsmsn_settings' );
        $npsmsn_api_token = $npsmsn__options['npsmsn_text_field_0'];
        $npsmsn_usr_role = $npsmsn__options['npsmsn_select_field_1'];
        $npsmsn_get_permalink = get_permalink( $ID );
        $npsmsn_message_title = $post->post_title;
        $npsmsn_message_body = sprintf('New Post: %s %s', $npsmsn_message_title, $npsmsn_get_permalink);
        $npsmsn_blogusers = get_users( "blog_id=$ID&role=$npsmsn_usr_role" );
        
        //go through the blog users in selected category and send message to each
        foreach ( $npsmsn_blogusers as $npsmsn_user ) {
            
            $npsmsn_to_number = get_user_meta($npsmsn_user->ID, 'mobile', true);
            
            if ( intval($npsmsn_to_number) == 0 ) { continue; }
        
            //API URL
        	$npsmsn_api_url = 'https://freebulksmsonline.com/api/v1/index.php';
        
            
        	$npsmsn_send_postfields = array(
        		'number' => "$npsmsn_to_number",
        		'message' => "$npsmsn_message_body",
        		'token' => "$npsmsn_api_token"
        	);
            
            $npsmsn_send_postfields_wp = array(
            'method' => 'POST',
            'body' => $npsmsn_send_postfields,
            'timeout' => '15',
            'headers' => array(),
            'cookies' => array()
            );
     
            
            $npsmsn_api_response = wp_remote_post( $npsmsn_api_url, $npsmsn_send_postfields_wp );
        
        }
    }
  }
}


?>