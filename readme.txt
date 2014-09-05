=== Improved Image Editor ===
Contributors: CodeKitchen, markoheijnen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CQFB8UMDTEGGG
Tags: tabs, image, editor, gd, imagick, gmagick
Requires at least: 4.0
Tested up to: 4.0
Stable tag: 0.1
License: GPLv2 or later

Adds more image edit functionality to your WordPress installatin

== Description ==

This plugin extend the power WordPress already have when it comes to image editing.

It currently focuses on the API side to give developers great tools to manipulate specific image sizes.
You can do this by calling Improved_Image_Editor::register_image_size_info(). Here you can specify the following arguments:
- quality
- zoom
- filters (work in progress)

It also includes the functionality of the following plugins I build:
- Gmagick
- Improved GD Image Editor 
- WP_Image (will be included soon)


Future plans are to build a settings page so users can select the settings per image size themself.
I also have ideas to extend the current image editor UI with options to select the focus point per image size and build auto detection of the focus point. With every feature we add the main focus will be to build a decent API for it so at certain points it could be integrated in WordPress itself.

== Installation ==

1. Upload the folder `improved-image-editor` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Start using the functionality it got in your own plugin or theme

== Frequently Asked Questions ==

Coming soon.

== Screenshots ==

Coming soon.

== Changelog ==

= 0.2.0 ( 2012-9-? ) =
* Add WP_Image class

= 0.1.0 ( 2012-9-4 ) =
* First version to show people the possibilities