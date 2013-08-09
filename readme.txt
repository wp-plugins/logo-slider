=== Logo Slider ===
Contributors: EnigmaWeb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CEJ9HFWJ94BG4
Tags: logo slide, logo slideshow, logo slide show, logo carousel, image carousel, logo slider, sponsors, logo showcase
Requires at least: 3.1
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Showcase logos in stylish slideshow carousel.

== Description ==

Add a logo slideshow carousel to your site quickly and easily. Embed in any post/page using shortcode `[logo-slider]` or in your theme with `<?php logo_slider(); ?>`

Perfect for displaying a list of sponsor or client logos.

= Features =
*	Simple and light weight
*	Nice selection of arrow icons
*	Easy to customise (height, width, number of images before slide etc)
*	Easy image uploader
*	Ability to add links to each logo if you want
*	Auto-slide option

= Demo =

[Click here](http://demo.enigmaweb.com.au/logo-slider/) to see Logo Slider in action.

== Installation ==

1. Upload the `logo-slider` folder to the `/wp-content/plugins/` directory
1. Activate Logo Slider plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the `Logo Slider` tab that appears in your admin menu.
1. Add to any page using shortcode `[logo-slider]` or to your theme using `<?php logo_slider(); ?>`
 
== Frequently Asked Questions ==

= How can I use this in a widget? =

Just place the shortcode into a text widget. If that doesn't work (it just renders [logo-slider] in text) then that means your theme isn't 'widgetized' which you can fix easily by adding 1 tiny piece of code to your theme functions.php:

`add_filter('widget_text', 'do_shortcode');`

Add this code above to fuctions.php between the `<?php` and `?>` tags. A good place would be either at the very top or the very bottom of the file. Once you've done this you should be able to use shortcode in widgets now.

= How can I customise the design? =

You can do some basic presentation adjustments via Logo Slider tab on the admin menu. Beyond this, you can completely customise the design using CSS in the Custom CSS field on settings screen.

= The layout is broken =

It's most likely just a matter of tweaking the css. 

= Where can I get support for this plugin? =

If you've tried all the obvious stuff and it's still not working please request support via the forum.

== Screenshots ==

1. An example of Logo Slider in action
2. The settings screen in WP-Admin

== Changelog ==

= 1.2 =
* Bug fix for configuration menu display and image resize function. Thanks to Grant Kimball for this fix.

= 1.1 =
* Added auto-slide options

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.2 =
* Bug fix for configuration menu display and image resize function. Thanks to Grant Kimball for this fix.

= 1.1 =
* Added auto-slide options

= 1.0 =
* Initial release

 