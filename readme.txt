=== JSON Importer ===
Author: Tariq Hafeez
Tags: json, import, batch, post, comments
Requires at least: 3.0
Tested up to: 4.2
Stable tag: 4.2

Import posts from JSON data (facebook feed) file into WordPress.


== Description ==

This plugin imports posts from JSON data (facebook feed) file into your
WordPress blog. It can prove extremely useful when you want to import a bunch
of posts from facebook page, export FB feed as JSON and upload the JSON file and 
the plugin will take care of the rest.

= Features =

*   Imports post title, body, date, categories etc.
*   Import data in custom fields 
*	Import comments


== Installation ==

Installing the plugin:

1.  Unzip the plugin's directory into `wp-content/plugins`.
1.  Activate the plugin through the 'Plugins' menu in WordPress.
1.  The plugin will be available under Tools -> JSON Data Importer on
    WordPress administration page.


== Usage ==
Click on the JSON Importer link on your WordPress admin page under TOOLS menu, choose the
file you would like to import,  select default category, select post status and click Import. 


= Basic post information =

*   'csv_post_title' - title of the post
*   'csv_post_post' - body of the post
*   'csv_post_type' - 'post'

= Custom fields =
*	'caption' - caption in feed 
*   'link' - URL of story or video ,
*	'type' - FB post type link, video
*	'status_type' - FB status type could be shared_story, added_video
*	'shares' - How many times feed has been shared

== Comments ==
Plugin import comments and map
*	'comment_approved' => comments are approved by default,
*	'comment_author_url' => 'https://www.facebook.com/' . $comment->id, // this will take you to the FB comment page
*	'comment_author' => name of the person who commented
*	'comment_content' => actual message in comments
*	'comment_date' => timestamp when comment made

== Examples ==
Provided JSON data file is placed in example folder for test purposes.

== Tests ==
I have created few unit test using PHPUnit, just to show how can we perform unit test ,
though I would like to unit test the wordpress functionality with this plugin
               