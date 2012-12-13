=== Gritter Social Influence ===
Contributors: markjaquith, mdawaffe (this should be a list of wordpress.org userid's)
Donate link: http://www.jordan-code.de/gritter
Tags: gritter layer admin social influence jquery
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: 0.1
Version: 0.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With this plugin you can add social influence layers to your website.

== Description ==
With this plugin you can add social influence layers to your website.
The gritter plugin lets you define new layer and groups to be displayed with the jquery gritter plugin of
http://boedesign.com/blog/2009/07/11/growl-for-jquery-gritter/
or
https://github.com/jboesch/Gritter.
After installation you find a link under 'Settings' called 'Gritter'.
On default there is one group. At least one group have to be exists, because each layer mandatory one group.
It isn't possible to have a layer in multiple groups. One layer is only in one group. But one group can have different layers.

Settings you can set for groups:

'Title':    Title for the group, only displayed in the admin area.
'Logic':    Here you can set a logic, on which post types the layers of these group are displayed.
            Options (e.g.):
                    - [single] : Only displayed on post type single (if is_single() function returns true).
                    - [single#921#] : Only displayed on post type single with id 921 (Only one id is possible yet.).
                    - [page] : Onlye displayed on post type page (if is_page() function returns true);
                    - [page#921#] : Only displayed on post type page with id 921 (Only one id is possible yet.).
                    - [category#3,5,9,48#] : Displaye only on posts which are in these provided category ids.
'Random':   Define if a random couple of layer in these group displayed.
            Options (e.g.):
                    - -1 : No random, all layers are displayed.
                    - 0 : Random order of all layers.
                    - 1 : Only one layer is displayed, choosed by random.
                    - 2 : Only two layer are displayed, choosed by random.
                    - ...
             If you choose a number that is > as the count of layers in the group, the script automatically set the random
             to the count of layers.

Setting you can sset for layers:

'Title':    Title for the layer, displayed in the layers title section.
'Text':     Text for the layer, displayed in the layers text section.
'Group':    Option field to select a group.
'Timeout':  Set the timeout (e.g. 2500) for that layer. If you have multiple layer in a group, it adds the timeouts each up.
'Active':   If these layer is active.

You can use shortcodes for the title and text field:

[RND#0,5#] : Replaced the shortcode with a random number between 0 and 5.
[RNDDAY#0,10#] : Replace the shortcode with a day between 0 and 10. (e.g. 'vor 2 Tagen', or 'heute', or 'vor einem Tag')
[CITY] : Replace the shortcode with a random city. All cities a defined in the plugin file gritter-plugin-cities.php in the
         cities vairable.
[RNDYmdhi#0,10#] :  Same like normal RND shortcode, but with a seed. The seed, the signs after RND have to be a valid date()
                    function input. (E.g. Y, Ym, Ymd, Ymdh, Ymdhi, Ymdhis)
[RNDNOSEED#0,10#] : Same like normal RND shortcode, but use no seed.

'Settings' Page:
    You can choose a seed value for the date seed.
    E.g. if you want that a user gets the same layers for one day, select 'Ydm'.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload direcotry 'gritter-plugin' to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. All done, now you have a link under 'Settings' called 'Gritter'.

== Frequently Asked Questions ==

= Whats about to configure gritter? =

The configuration of gritter is done static in the '/css/gritter.min.js' file.

== Changelog ==

= 0.1 =
* First version

== Upgrade Notice ==

= 0.1 =
First version.