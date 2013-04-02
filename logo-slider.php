<?php
/*
Plugin Name: Logo Slider
Plugin URI: http://www.wordpress.org/extend/plugins/logo-slider
Description:  Add a logo slideshow carousel to your site quicky and easily. Embedd in any post/page using shortcode <code>[logo-slider]</code> or to your theme with <code><?php logo_slider(); ?></code>
Version: 1.1
Author: Enigma Digital
Author URI: http://www.enigmaweb.com.au/
*/


/*
///////////////////////////////////////////////
This section defines the variables that
will be used throughout the plugin
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	define our defaults (filterable)
$wp_logo_defaults = apply_filters('wp_logo_defaults', array(
	
	'custom_css' => 'You can write your custom CSS here.',
	'arrow' => 1,
	'bgcolour' => '#FFFFFF',
	'slider_width' => 450,
	'slider_height' => 198,
	'num_img' => 2,
	'auto_slide' => 1,
	'auto_slide_time' => '',
	
));

//	pull the settings from the db
$wp_logo_slider_settings = get_option('wp_logo_slider_settings');
$wp_logo_slider_images = get_option('wp_logo_slider_images');

//	fallback
$wp_logo_slider_settings = wp_parse_args($wp_logo_slider_settings, $wp_logo_defaults);


/*
///////////////////////////////////////////////
This section hooks the proper functions
to the proper actions in WordPress
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/

//	this function registers our settings in the db
add_action('admin_init', 'wp_logo_register_settings');
function wp_logo_register_settings() {
	register_setting('wp_logo_slider_images', 'wp_logo_slider_images', 'wp_logo_images_validate');
	register_setting('wp_logo_slider_settings', 'wp_logo_slider_settings', 'wp_logo_settings_validate');
}
//	this function adds the settings page to the Appearance tab
add_action('admin_menu', 'wp_logo_slider_menu');
function wp_logo_slider_menu() {
	
	
	$page_title		=	'Logo Slider';
	$menu_title		=	'Logo Slider';
	$capability		=	'manage_options';
	$menu_slug		=	'wp_logo_slider';
	$function		=	'wp_logo_slider';
	$icon			=	plugin_dir_url( __FILE__ ).'icon.png';
	add_menu_page($page_title,$menu_title,$capability,$menu_slug,$function,$icon);
	
}

//	add "Settings" link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'wp_logo_plugin_action_links');
function wp_logo_plugin_action_links($links) {
	$wp_logo_settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'upload.php?page=wp_logo_slider' ), __('Settings') );
	array_unshift($links, $wp_logo_settings_link);
	return $links;
}


/*
///////////////////////////////////////////////
this function is the code that gets loaded when the
settings page gets loaded by the browser.  It calls 
functions that handle image uploads and image settings
changes, as well as producing the visible page output.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function wp_logo_slider() {
	echo '<div class="wrap">';
	
		//	handle image upload, if necessary
		if($_REQUEST['action'] == 'wp_handle_upload')
			wp_logo_handle_upload();
		
		//	delete an image, if necessary
		if(isset($_REQUEST['delete']))
		wp_logo_delete_upload($_REQUEST['delete']);
		
		//	the image management form
		wp_logo_images_admin();
		
		//	the settings management form
		wp_logo_settings_admin();

	echo '</div>';
}


/*
///////////////////////////////////////////////
this section handles uploading images, adding
the image data to the database, deleting images,
and deleting image data from the database.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function handles the file upload,
//	resize/crop, and adds the image data to the db
function wp_logo_handle_upload() {
	global $wp_logo_slider_settings, $wp_logo_slider_images;
	
	//	upload the image
	$upload = wp_handle_upload($_FILES['logo_images'], 0);
	
	//	extract the $upload array
	extract($upload);
	
	//	the URL of the directory the file was loaded in
	$upload_dir_url = str_replace(basename($file), '', $url);
	
	//	get the image dimensions
	list($width, $height) = getimagesize($file);
	
	//	if the uploaded file is NOT an image
	if(strpos($type, 'image') === FALSE) {
		unlink($file); // delete the file
		echo '<div class="error" id="message"><p>Sorry, but the file you uploaded does not seem to be a valid image. Please try again.</p></div>';
		return;
	}
	
	/*//	if the image doesn't meet the minimum width/height requirements ...
	if($width < $wp_logo_slider_settings['slider_width'] || $height < $wp_logo_slider_settings['slider_height']) {
		unlink($file); // delete the image
		echo '<div class="error" id="message"><p>Sorry, but this image does not meet the minimum height/width requirements. Please upload another image</p></div>';
		return;
	}*/
	
	//	if the image is larger than the width/height requirements, then scale it down.
	if($width > $wp_logo_slider_settings['slider_width'] || $height > $wp_logo_slider_settings['slider_height']) {
		//	resize the image
		$resized = image_resize($file, $wp_logo_slider_settings['slider_width'], $wp_logo_slider_settings['slider_height'], true, 'resized');
		$resized_url = $upload_dir_url . basename($resized);
		//	delete the original
		unlink($file);
		$file = $resized;
		$url = $resized_url;
	}
	
	//	make the thumbnail
	$thumb_height = round((100 * $wp_logo_slider_settings['slider_height']) / $wp_logo_slider_settings['slider_width']);
	if(isset($upload['file'])) {
		$thumbnail = image_resize($file, 100, $thumb_height, true, 'thumb');
		$thumbnail_url = $upload_dir_url . basename($thumbnail);
	}
	
	//	use the timestamp as the array key and id
	$time = date('YmdHis');
	
	//	add the image data to the array
	$wp_logo_slider_images[$time] = array(
		'id' => $time,
		'file' => $file,
		'file_url' => $url,
		'thumbnail' => $thumbnail,
		'thumbnail_url' => $thumbnail_url,
		'slide_title' => '',
		'slide_desc' => '',
		'image_links_to' => ''
	);
	
	//	add the image information to the database
	$wp_logo_slider_images['update'] = 'Added';
	update_option('wp_logo_slider_images', $wp_logo_slider_images);
}

//	this function deletes the image,
//	and removes the image data from the db
function wp_logo_delete_upload($id) {
	global $wp_logo_slider_images;
	
	//	if the ID passed to this function is invalid,
	//	halt the process, and don't try to delete.
	if(!isset($wp_logo_slider_images[$id])) return;
	
	//	delete the image and thumbnail
	unlink($wp_logo_slider_images[$id]['file']);
	unlink($wp_logo_slider_images[$id]['thumbnail']);
	
	//	indicate that the image was deleted
	$wp_logo_slider_images['update'] = 'Deleted';
	
	//	remove the image data from the db
	unset($wp_logo_slider_images[$id]);
	update_option('wp_logo_slider_images', $wp_logo_slider_images);
}


/*
///////////////////////////////////////////////
these two functions check to see if an update
to the data just occurred. if it did, then they
will display a notice, and reset the update option.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function checks to see if we just updated the settings
//	if so, it displays the "updated" message.
function wp_logo_slider_settings_update_check() {
	global $wp_logo_slider_settings;
	if(isset($wp_logo_slider_settings['update'])) {
		echo '<div class="updated fade" id="message"><p>Wordpress Logo Slider Settings <strong>'.$wp_logo_slider_settings['update'].'</strong></p></div>';
		unset($wp_logo_slider_settings['update']);
		update_option('wp_logo_slider_settings', $wp_logo_slider_settings);
	}
}
//	this function checks to see if we just added a new image
//	if so, it displays the "updated" message.
function wp_logo_slider_images_update_check() {
	global $wp_logo_slider_images;
	if($wp_logo_slider_images['update'] == 'Added' || $wp_logo_slider_images['update'] == 'Deleted' || $wp_logo_slider_images['update'] == 'Updated') {
		echo '<div class="updated fade" id="message"><p>Image(s) '.$wp_logo_slider_images['update'].' Successfully</p></div>';
		unset($wp_logo_slider_images['update']);
		update_option('wp_logo_slider_images', $wp_logo_slider_images);
	}
}


/*
///////////////////////////////////////////////
these two functions display the front-end code
on the admin page. it's mostly form markup.
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	display the images administration code
function wp_logo_images_admin() { ?>
	<?php global $wp_logo_slider_images; ?>
	<?php wp_logo_slider_images_update_check(); ?>
	<h2><?php _e('Wordpress LogoSlider Images', 'wp_LogoSlider'); ?></h2>
	
	<table class="form-table">
		<tr valign="top"><th scope="row">Upload New Image</th>
			<td>
			<form enctype="multipart/form-data" method="post" action="?page=wp_logo_slider">
				<input type="hidden" name="post_id" id="post_id" value="0" />
				<input type="hidden" name="action" id="action" value="wp_handle_upload" />
				
				<label for="logo_images">Select a File: </label>
				<input type="file" name="logo_images" id="logo_images" />
				<input type="submit" class="button-primary" name="html-upload" value="Upload" />
			</form>
			</td>
		</tr>
	</table><br />
	
	<?php if(!empty($wp_logo_slider_images)) : ?>
	<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th scope="col" class="column-slug">Image</th>
				<th scope="col">Image Links To</th>
				<th scope="col" class="column-slug">Actions</th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<th scope="col" class="column-slug">Image</th>
				<th scope="col">Image Links To</th>
				<th scope="col" class="column-slug">Actions</th>
			</tr>
		</tfoot>
		
		<tbody>
		
		<form method="post" action="options.php">
		<?php settings_fields('wp_logo_slider_images'); ?>
		<?php foreach((array)$wp_logo_slider_images as $image => $data) : ?>
			<tr>
				<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][id]" value="<?php echo $data['id']; ?>" />
				<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][file]" value="<?php echo $data['file']; ?>" />
				<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][file_url]" value="<?php echo $data['file_url']; ?>" />
				<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][thumbnail]" value="<?php echo $data['thumbnail']; ?>" />
				<input type="hidden" name="wp_logo_slider_images[<?php echo $image; ?>][thumbnail_url]" value="<?php echo $data['thumbnail_url']; ?>" />
				<th scope="row" class="column-slug"><img src="<?php echo $data['thumbnail_url']; ?>" /></th>
                <td><input type="text" name="wp_logo_slider_images[<?php echo $image; ?>][image_links_to]" value="<?php echo $data['image_links_to']; ?>" size="30" /></td>
				<td class="column-slug"><input type="submit" class="button-primary" value="Update" /> <a href="?page=wp_logo_slider&amp;delete=<?php echo $image; ?>" class="button">Delete</a></td>
			</tr>
		<?php endforeach; ?>
		<input type="hidden" name="wp_logo_slider_images[update]" value="Updated" />
		</form>
		
		</tbody>
	</table>
	<?php endif; ?>

<?php
}

//	display the settings administration code
function wp_logo_settings_admin() { ?>

	<?php wp_logo_slider_settings_update_check(); ?>
	<h2><?php _e('Wordpress Logo Slider Settings', 'wp-LogoSlider'); ?></h2>
	<form method="post" action="options.php">
	<?php settings_fields('wp_logo_slider_settings'); ?>
	<?php global $wp_logo_slider_settings; $options = $wp_logo_slider_settings; ?>
	<table class="form-table">
		<tr><th scope="row">Size</th>
		<td>Width: <input type="text" name="wp_logo_slider_settings[slider_width]" value="<?php echo $options['slider_width'] ?>" size="4" /> Height: <input type="text" name="wp_logo_slider_settings[slider_height]" value="<?php echo $options['slider_height'] ?>" size="4" /></td></tr>
			
		<tr><th scope="row">Images Per Slide</th>
		<td><input type="text" name="wp_logo_slider_settings[num_img]" value="<?php echo $options['num_img'] ?>" size="4" /> <small>Number of logos per slide</small></td>
        </tr>
        
		<tr><th scope="row">Background Colour</th>
		<td><input type="text" name="wp_logo_slider_settings[bgcolour]" value="<?php echo $options['bgcolour'] ?>" /> <small>Format: #FFFFFF</small></td>
        </tr>
        <tr><th scope="row">Auto Slide</th>
		<td id="arrow-style"> 
            	
                ON <input type="radio" name="wp_logo_slider_settings[auto_slide]" value="1" <?php if($options['auto_slide']==1){echo 'checked="checked"';}?> />&nbsp; &nbsp;
                OFF <input type="radio" name="wp_logo_slider_settings[auto_slide]" value="2" <?php if($options['auto_slide']==2){echo 'checked="checked"';}?>/>
                </td>
                </tr>
                <tr><th scope="row">Auto Slide Time</th>
		<td><input type="text" name="wp_logo_slider_settings[auto_slide_time]" value="<?php echo $options['auto_slide_time'] ?>" size="4" /> <small>Set auto slide duration in seconds</small></td>
        </tr>
       
        <tr><th scope="row">Arrow Style</th>
		<td id="arrow-style"> 
            	
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow1.png" width="28" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="1" <?php if($options['arrow']==1){echo 'checked="checked"';}?> /></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow2.png" width="31" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="2" <?php if($options['arrow']==2){echo 'checked="checked"';}?>/></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow3.png" width="34" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="3" <?php if($options['arrow']==3){echo 'checked="checked"';}?>/></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow4.png" width="34" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="4" <?php if($options['arrow']==4){echo 'checked="checked"';}?>/></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow5.png" width="24" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="5" <?php if($options['arrow']==5){echo 'checked="checked"';}?>/></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow6.png" width="36" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="6" <?php if($options['arrow']==6){echo 'checked="checked"';}?>/></p>
                <p><img src="<?php echo plugin_dir_url(__FILE__); ?>/arrows/arrow7.png" width="38" height="40" alt="" /><br /><input type="radio" name="wp_logo_slider_settings[arrow]" value="7" <?php if($options['arrow']==7){echo 'checked="checked"';}?>/></p>
                
           </td>
        </tr>
        <tr valign="top"><th scope="row">Custom CSS</th>
		<td><textarea name="wp_logo_slider_settings[custom_css]" rows="6" cols="70"><?php echo $options['custom_css']; ?></textarea></td>
		</tr>
       <input type="hidden" name="wp_logo_slider_settings[update]" value="UPDATED" />
	 
	</table>
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
	</form>
	
	<!-- The Reset Option -->
	<form method="post" action="options.php">
	<?php settings_fields('wp_logo_slider_settings'); ?>
	<?php global $wp_logo_defaults; // use the defaults ?>
	<?php foreach((array)$wp_logo_defaults as $key => $value) : ?>
	<input type="hidden" name="wp_logo_slider_settings[<?php echo $key; ?>]" value="<?php echo $value; ?>" />
	<?php endforeach; ?>
	<input type="hidden" name="wp_logo_slider_settings[update]" value="RESET" />
	<input type="submit" class="button" value="<?php _e('Reset Settings') ?>" />
	</form>
	<!-- End Reset Option -->
	</p>

<?php
}


/*
///////////////////////////////////////////////
these two functions sanitize the data before it
gets stored in the database via options.php
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
//	this function sanitizes our settings data for storage
function wp_logo_settings_validate($input) {
	$input['slider_width'] = intval($input['slider_width']);
	$input['slider_height'] = intval($input['slider_height']);
	$input['num_img'] = intval($input['num_img']);
	$input['arrow'] = intval($input['arrow']);
	$input['custom_css'] = wp_filter_nohtml_kses($input['custom_css']);
	$input['bgcolour'] = wp_filter_nohtml_kses($input['bgcolour']);
	$input['auto_slide'] = intval($input['auto_slide']);
	$input['auto_slide_time'] = intval($input['auto_slide_time']);
	
	return $input;
}
//	this function sanitizes our image data for storage
function wp_logo_images_validate($input) {
	foreach((array)$input as $key => $value) {
		if($key != 'update') {
			$input[$key]['file_url'] = clean_url($value['file_url']);
			$input[$key]['thumbnail_url'] = clean_url($value['thumbnail_url']);
						
			if($value['image_links_to'])
			$input[$key]['image_links_to'] = clean_url($value['image_links_to']);
			
		}
	}
	return $input;
}

/*
///////////////////////////////////////////////
this final section generates all the code that
is displayed on the front-end of the WP Theme
\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
*/
function logo_slider($args = array(), $content = null) {
	global $wp_logo_slider_settings, $wp_logo_slider_images;
	// possible future use
	$args = wp_parse_args($args, $wp_logo_slider_settings);
	$newline = "\n"; // line break
	echo '<div id="logo-slider-wraper">';
	
	
	$data_chunks = array_chunk($wp_logo_slider_images, $wp_logo_slider_settings['num_img']);
	echo '<ul id="logo-slider">';
	foreach ($data_chunks as $data_chunk) {
		echo '<li class="slide">';
		foreach($data_chunk as $data) {
			if($data['image_links_to'])
		echo '<a href="'.$data['image_links_to'].'">';
		echo '<img src="'.$data['file_url'].'" class="logo-img" alt="" />';
		
		if($data['image_links_to'])
		echo '</a>';
		}
		echo '</li>';
	}
	echo '</ul>';
	
	
	echo '</div>';
	
}

//	create the shortcode [wp_LogoSlider]
add_shortcode('logo-slider', 'wp_slider_shortcode');
function wp_slider_shortcode($atts) {
	
	// Temp solution, output buffer the echo function.
	ob_start();
	logo_slider();
	$output = ob_get_clean();
	
	return $output;
	
}

add_action('wp_print_scripts', 'wp_LogoSlider_scripts');
function wp_LogoSlider_scripts() {
	if(!is_admin())
	wp_enqueue_script('cycle', WP_CONTENT_URL.'/plugins/logo-slider/jquery.cycle.all.min.js', array('jquery'), '', true);
}

add_action('wp_footer', 'wp_slider_args', 15);
function wp_slider_args() {
	global $wp_logo_slider_settings; ?>


<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#logo-slider').before('<div class="slider-controls"><a href="#" id="prev">&lt;</a> <a href="#" id="next">&gt;</a></div>').cycle({ 
    timeout: <?php if($wp_logo_slider_settings['auto_slide'] == 1) {echo $wp_logo_slider_settings['auto_slide_time'] * 1000;} else { echo 0;} ?>,
	fx:      'scrollHorz',
	next:   '#prev',
	prev:   '#next',
});
});
</script>


<?php }

add_action( 'wp_head', 'wp_logo_slider_style' );
function wp_logo_slider_style() { 
	global $wp_logo_slider_settings;
	global $options;
?>
	
<style type="text/css" media="screen">
	<?php 
		echo $wp_logo_slider_settings['custom_css'];
	?>
	#logo-slider-wraper{
		position:relative;
		
	}
	.slider-controls{
		position:relative;
		width:<?php echo $wp_logo_slider_settings['slider_width']; ?>px;	
		top: <?php echo $wp_logo_slider_settings['slider_height'] / 2 - 20 ?>px;

	}
	#logo-slider {
		position: relative;
		width: <?php echo $wp_logo_slider_settings['slider_width']; ?>px;
		height: <?php echo $wp_logo_slider_settings['slider_height']?>px;
		margin: 0; padding: 0;
		overflow: hidden;
		list-style:none;
	}
	.slide{
		list-style:none;
		margin:0 !important;
		width:<?php echo $wp_logo_slider_settings['slider_width']; ?>px !important;
	}
	.slider-controls a{
		height:40px;
		width:40px;
		display:inline-block;
		text-indent:-9000px; 
	}
	#prev{
		background:url(<?php echo WP_CONTENT_URL.'/plugins/logo-slider/arrows/arrow'. $wp_logo_slider_settings['arrow'].'.png'; ?>) no-repeat center;
		float:right;
		margin-right:-50px;
	}	
	#next{
		background:url(<?php echo WP_CONTENT_URL.'/plugins/logo-slider/arrows/arrow'. $wp_logo_slider_settings['arrow'].'-prev.png'; ?>) no-repeat center;
		float:left;
		margin-left:-50px
	}
</style>
	
<?php }
add_action( 'admin_enqueue_scripts', 'admin_styles' );
function admin_styles(){ ?>
		<style type="text/css" media="screen">
			#arrow-style p{ float:left; height:60px; width:40px; text-align:center; margin-right:16px;}
		</style>
<?php } 

