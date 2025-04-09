=== Get Use APIs - JSON Content Importer ===
Contributors: berkux
Tags: json, api, external api, endpoint, rest, rest api, block, template generator
Requires at least: 5.3
Requires PHP: 7.0
Tested up to: 6.4.1
Stable tag: 1.5.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Effortlessly integrate WordPress with external APIs. Easily get data from APIs and showcase API data through a Shortcode or a JCI Block. Generate a template with the JCI Block. Bridging WordPress with APIs has never been this simple.

== Description ==

The simplest method to link WordPress with external APIs

JCI simplifies the task of retrieving data from a third-party REST API. The returned data can be neatly arranged and showcased on your WordPress site via a shortcode or a JCI Block. Generate a template with the JCI Block.

20 seconds: Load API and display data

https://www.youtube.com/watch?v=RBlrAUVywAk

7 minutes: Basic intro to JCI free

https://www.youtube.com/watch?v=SDUj1teNG2s

### Examples, Preview

Check out the live demonstration to explore how we're interfacing with various APIs
[LIVE PREVIEW - WordPress and a external API](https://api.json-content-importer.com/free-jci-plugin-example/wetter/)

[Video: How to use the Wikipedia API](https://www.youtube.com/watch?v=GJGBPvaKZsk)

### Main Plugin Features

* Low code usage: Save time and avoid coding using an API
* Insert an API-URL into the JCI Block: Data is loaded, and a template can be generated to display the data.
* Easy to start: Check Installation - Is your WordPress ready for JCI? Most probably!
* Basic Settings: Check SSL, Cacher, Gutenberg and Authentication
* Use the JCI cacher to avoid many API requests
* Step 1: Use the simple JCI Block interface to get data! Query the API and check the response.
* Step 2: Utilize the simple template generator inside the JCI Block to use the data!

### Documentation

There's a wealth of resources and support at your fingertips. Explore the articles listed below to begin your journey:

* [JCI Manual](https://doc.json-content-importer.com/)
* [Step 1: Access the Data](https://doc.json-content-importer.com/json-content-importer/step-1-data-access/)
* [Step 2: Using the Data](https://doc.json-content-importer.com/json-content-importer/step-2-data-usage/)
* [Shortcode – Basic structure](https://doc.json-content-importer.com/json-content-importer/shortcode-basic-structure/)
* [Free JCI Plugin: Overview Videos](https://doc.json-content-importer.com/json-content-importer/free-overview-videos/)
* [Free JCI Plugin: Getting Started](https://doc.json-content-importer.com/json-content-importer/basic-start/)

### You need more?

= JSON Content Importer PRO =
Both the free and PRO JCI Plugins serve the same purpose: retrieving data, transforming it, and publishing the results.
However, while the free Plugin can only handle basic challenges, the PRO JCI Plugin offers nearly full control over WordPress, the database, and applications.
[Compare: Free vs. JCI PRO Plugin](https://json-content-importer.com/compare/)

PRO features:
* application building by creating a searchform and connect it to a JSON-API in the background: pass GET-Variables to use a dynamic JSON-Feed-URL ("talk to API / webservice")
* much better and more flexible templateengine: twig
* create and use Custom Post Types
* store Templates independent of pages
* more Shortcode-Parameters
* executing Shortcodes inside a template
* more features...

== Frequently Asked Questions ==

= Help! I need more information! =
[Check the JCI manual, please](https://doc.json-content-importer.com)

= Where to start?
Give it a try: The JCI plugin's block comes with a simple, easy-to-understand example. This lets you learn how JCI works without any risk.

= What does this plugin do? =
This plugin enables you to insert a WordPress shortcode or a Gutenberg Block within any page, post, or Custom Post Type (CPT). This facilitates the retrieval of data from an API, which you can then manipulate, such as converting it to HTML for display purposes.
Connecting an API to WordPress offers enhanced content management, extended functionality, personalization and scalability. It enables real-time data display, third-party service integration, and process automation, making your website dynamic and powerful.
The plugin parses almost any JSON-feed and allows you to display all data on your website: Import data from an API or Webservice to display it on your website.

= How can I make sure the plugin works? =
Select the 'JSON Content Importer' option from the Admin Menu. Upon doing so, you'll be presented with various tabs, one of which is labeled 'Check Installation.' Ensure all the tests conducted here are successfully passed for the optimal functioning of the plugin.
Then use the Shortcodes from Tab "Step 1: Get data" and if successful "Step 2: Use data". If that does not work, check Tab "Support".

= Where is this plugin from? =
This plugin is made in munich, bavaria, germany!
Famous for Oktoberfest, FC Bayern Munich, AllianzArena, TUM, BMW, Siemens, seas, mountains and much more...


### Basic structure of a JCI Shortcode (use the JCI Block to generate such a shortcode and a template):
= Shortcode =
'[jsoncontentimporter
* url="http://...json"
* numberofdisplayeditems="number: how many items of level 1 should be displayed? display all: leave empty or set -1"
* urlgettimeout="number: who many seconds for loading url till timeout?"
* basenode="starting point of datasets, the base-node in the JSON-Feed where the data is"
* oneofthesewordsmustbein="default empty, if not empty keywords spearated by ','. At least one of these keywords must be in the created text (here: text=code without html-tags)"
* oneofthesewordsmustbeindepth="default: 1, number: where in the JSON-tree oneofthesewordsmustbein must be?"
]
This is the template:
Any HTML-Code plus "basenode"-datafields wrapped in "{}"
{subloop:"basenode_subloop":"number of subloop-datasets to be displayed"}
Any HTML-Code plus "basenode_subloop"-datafields wrapped in "{}". If JSON-data is HTML add "html" flag like "{fieldname:html}"
{/subloop:"basenode_subloop"}
[/jsoncontentimporter]'

* templates like "{subloop-array:AAAA:10}{text}{subloop:AAAA.image:10}{id}{/subloop:AAAA.image}{/subloop-array:AAAA}" are possible:
one is the recursive usage of "subloop-array" and "subloop".
the other is "{subloop:AAAA.image:10}" where "AAAA.image" is the path to an object. This is fine for some JSON-data.

= Some special add-ons for datafields =
* "{street:purejsondata}": Default-display of a datafield is NOT HTML, but HTML-Tags are converted : use this to use really the pure data from the JSON-Feed
* "{street:html}": Default-display of a datafield is NOT HTML: "&lt;" etc. are converted to "&amp,lt;". Add "html" to display the HTML-Code as Code.
* "{street:htmlAndLinefeed2htmlLinefeed}": Same as "{street:html}" plus "\n"-Linefeeds are converted to HTML-Linebreak
* "{street:ifNotEmptyAddRight:,}": If datafield "street" is not empty, add "," right of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{street:html,ifNotEmptyAddRight:extratext}": you can combine "html" and "ifNotEmptyAdd..." like this
* "{street:purejsondata,ifNotEmptyAddLeftRight:LEFT##RIGHT##}": If datafield "street" is not empty, add text on the left and right
* "{street:ifNotEmptyAdd:,}": same as "ifNotEmptyAddRight"
* "{street:ifNotEmptyAddLeft:,}": If datafield "street" is not empty, add "," left of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{locationname:urlencode}": Insert the php-urlencoded value of the datafield "locationname". Needed when building URLs

== Screenshots ==  
1. Welcome to JCI! Thank you!
2. Check your JCI installation and its requirements.
3. Configure your JCI settings: SSL? Cache? Gutenberg?
4. Step 1: Retrieve data. Highly recommended: Use the JCI Block.
5. Step 2: Use data. Generate a template with the JCI Block and try it out.
6. Locate the JCI Block.
7. JCI Block: Welcome to the JCI Block. Familiarize yourself with the JSON example.
8. JCI Block: Turn debug mode on to see what is happening.
9. JCI Block: Generate a template from JSON.
	
== Changelog ==
= 1.5.4=
* Plugin ok with WordPress 6.4.1
* Improved Security in the Backend

= 1.5.3=
* Enhanced, see Tab "Basic Settings": Send Header "HEADER_KEY:HEADER_VALUE": Insert 'header HEADER_KEY1:HEADER_VALUE1#HEADER_KEY2:HEADER_VALUE2' in the following text field, and no 'Bearer' will be added. E.g.: 'header User-Agent:JCIfree'
* Enhanced: The internal communication within a WordPress block relies on APIs, typically accessed via GET requests. For larger datasets, POST requests are more suitable. This new JCIfree version now utilizes POST instead of GET.
* Changed: Some server have problems, using jcifree-block.php as JavaScript-Applicaton (strict mime policy). Solution: Renamed to jcifree-block.js
* Some Backend-Bugfixes for Block-React, PHP8...

= 1.5.2 =
* Bugfix: JCI Block wasn't showing up when a browser had the "strict mime type" setting enabled. With this bugfix, the issue has been resolved.

= 1.5.1 =
* Fixed: JCI Block - In some situations, unwanted content ('Welcometext') is displayed on the published page.
* Improved: Translation

= 1.5.0 =
* Versionproblems: Wordpress.org does not update from 1.4 to 1.4.1

= 1.4.1 =
* Bugfix: The default settings of the shortcode parameter basenode were incorrect. This has now been corrected.

= 1.4 =
* Recommendation: Please check your JCI cache! The plugin displays the number of files in the JCI cache and its size. You can delete the cached files by using the 'Clear Cache' function.
* Significantly improved admin interface: Tabs, Tests, Settings, Step 1, Step 2...
* Greatly enhanced JCI block. Better error handling, and as a highlight, the creation of templates.
* New: Improved uninstall process.
* Minor PHP 8 fixes
* New Screenshots and improved Plugin-Description

= 1.3.17 =
* Wordpress 6.2 changed the way Blocks are rendered. This Update fixes the crash of the JCI-Block with wordpress 6.2

= 1.3.16 =
* Fixed security issue: Rio D. discovered and reported a Cross Site Scripting (XSS) vulnerability to Patchstack. Thank you Rio! For utilize you need Wordpress-Backend-Access and the affected Page is in the Wordpress-Adminarea only. Nevertheless: Update your JCI-Plugin, please!
* PHP 8.1 fixes

= 1.3.15 =
* New! Use JCILISTITEM to show a JSON-List: {subloop-array:mylist:no_first_item,no_last_item}AA{JCILISTITEM}BB<<br>{/subloop-array:mylist}, see https://doc.json-content-importer.com/json-content-importer/free-show-the-data/ for more
* Enhanced! "purejsondata,ifNotEmptyAdd", "purejsondata,ifNotEmptyAddRight", "purejsondata,ifNotEmptyAddLeftRight" and "purejsondata,ifNotEmptyAddLeft", e. g. {jsondata:purejsondata,ifNotEmptyAddLeft:TEXT}, see https://doc.json-content-importer.com/json-content-importer/free-show-the-data/ for more
* Plugin Ok with WP 6.1.1
* PHP 8.1 fixes

= 1.3.14 =
* New Shortcode-Parameter: "trytorepairjson=16" in the Shortcode removes non ASCII characters from the JSON data
* Bugfix after Wordpress 6.0 changes: Load JSONcontentimporter-Quicktag in a slightly different way
* Bugfix: Handling of JSON-Nodes containing ( or ) 
* Plugin Ok with WP 6.0.2

= 1.3.13 =
* Added sslverify-switch in the plugins general options: If a https-certificate is suddenly invalid, try to switch off it's verification (not ideal, but pragmatic)
* Some improvements for debug-infos (e. g. add debugmode=10 to the shortcode)
* Plugin Ok with WP 5.8.1

= 1.3.12 =
* Importaint speed-up Bugfix for JCI-Gutenberg-Block! If you run your Wordpress with Gutenberg-Blocks (no ClassicEditor-Plugin active) several unneeded libraries are loaded (independend of using a JCI-Block or not). This slows the site and gives bad load-ratings e. g. at Googles Pagespeed ( https://developers.google.com/speed/pagespeed/ ). With this fix the unneeded libraries are not loaded any more and the load-rating should be better. 
* Background-Check for allow_url_fopen in Plugin Options: If allow_url_fopen is not set to TRUE in the PHP-Settings, this might prevent PHP and the plugin to get JSON via http-requests from remote servers (if there no red text, all is ok) 
* Plugin Ok with WP 5.6.1

= 1.3.11 =
* Internationalization added: Available languages are English, German. Feel free to add other languages!
* Shotcodeparam nojsonvalue: If "nojsonvalue=y" the API-Answer is available as {nojsonvalue}, helpful if the API answer is not JSON
* Plugin Ok with WP 5.6

= 1.3.10 =
* Bugfix if subloop's have several unreplaced {...} items
* Plugin Ok with WP 5.5.1

= 1.3.9 =
* Bugfix if a JSON-field is not always there or null (loop stopped if so)
* Placeholders for template: If you want to have curly or square brackets {}[] in the template, this can trouble the shortcode-syntax or the template-parser. Use the placeholders #CBO# (curly brackets open), #CBC# (curly brackets close), #SBO# (square brackets open) and #SBC# (square brackets close) in the template, the placeholders will be replaced by it's bracket-values in the end after parsing.
* Plugin ok with WP 5.4.2

= 1.3.8 =
Compatibility fix: Plugin is now Ok with PHP 7.4

= 1.3.7 =
* New Option: If a http-API request fails, you can use the maybe cached JSON. Set the radio-button in the plugins settings: what API-problem should be handled how (either a non valid API-http-response or a non JSON-API-response - or both). By default for backwards-compatibility this is switched off. Recommendation: Switch on the plugins-cacher (e. g. some minutes) and select the radiobutton for "If the API-http-answercode is not 200 OR sends invalid JSON: try to use cached JSON" in the plugins options.   

= 1.3.6 =
* Bugfix: Add Gutenberg-JS only in the backend (before it was also in the frontend) 

= 1.3.5 =
* New: Added a Quicktag to the Wordpress-Text-Editor to insert the JSONContentImporter-Shortcode incl. an example 

= 1.3.4 =
* New Plugin-Option: Switch off Gutenberg features (maybe a site builder needs that)

= 1.3.3 =
* Bugfix of Bugfix, sorry...

= 1.3.2 =
* Bugfixes: detect Gutenberg and Wordpress 5.0

= 1.3.1 =
* Bugfixes

= 1.3.0 =
* Plugin is ok with the Gutenberg Plugin 3.9.0
* Gutenberg-Mode: With an active Gutenberg Plugin you get an easy example for testing and learing how to use the plugin. If you don't want to use Gutenberg on live-stage: Use it to easy create the shortcode (almost avoid the learning of the shortcode-parameter-syntax)     

= 1.2.20 =
* Plugin is ok with Wordpress 4.9.8
* Plugin is ok with the Gutenberg Plugin 3.6.2
* Plugin is ok with the General Data Protection Regulation (GDPR): The plugin does not collect personal data itself. You may list the plugin in your GDPR-documentation as used software. Maybe the used API uses personal data - then you have to take care of the GDPR.
* Improved debugmode (use debugmode=10 in the shortcode for that)
* Changed the Videolink to a better HowTo-Video
* Added upgradelink to pro plugin in PluginList


= 1.2.19 =
* Plugin is ok with Wordpress 4.9.1
* Adding "debugmode=10" to the Shortcode parameters will show you info about the API-JSON-retrieve and the used template for converting to HTML.
* Ever since the plugin uses the PHP function "mb_check_encoding" to detect a maybe needed character-conversion. Unfortunately some Wordpress/PHP-installations do not have the PHP-optional "Multibyte String"-library required for that. Then the plugin does not work. Now the availablility of "mb_check_encoding" is checked: If it'S not there the conversion-feature is disabled.
* At the Plugin-options you can set a "Bearer"-accesskey for oAuth-Bearer-Authentication. The there defined accesskey-string is sent to the API as "Authorization:Bearer accesskey". Some APIs just need "Authorization:accesskey". So "Bearer " should not sent. To do this add "nobearer " (with one space at the end) at the beginning of the option-field.

= 1.2.18 =
* Plugin is ok with Wordpress 4.7.5
* New option: Add Default Useragent for http-request (some APIs need that)

= 1.2.17 =
* Plugin is ok with Wordpress 4.7
* https instead of http for Plugin-Website
* Remove invalid quotation marks in shortcode-attributes (when copypasting example code this can happen)

= 1.2.16 =
* Plugin is ok with Wordpress 4.6.1
* Plugin is ok with PHP 7.0
* Cleaner Code

= 1.2.15 =
* Plugin is ok with Wordpress 4.5.2
* Added features for coming future addons

= 1.2.14 =
* Plugin is ok with Wordpress 4.5
* Improved description

= 1.2.13 =
* Caching-Bug Fixed: Wordpress 4.4.2 does not create a "/cache/" folder. If "/cache/" is missing, the plugin creates on. This is relevant for totally new Wordpress installations, as older ones should have such a directory.


= 1.2.12 =
* Plugin is ok with Wordpress 4.4
* minor change: prevent direct calls of plugin
* bug fixed: path of cached files is now set ok


= 1.2.11 =
* bugfix: problems with numeric JSON-values and field manipulators like {JSONkey:ifNotEmptyAdd:....}
* beta feature Vers. 0.2: Fixed bug in using oAuth-Bearer-Accesscode for authentification at JSON-Server: Bearer-Code is added to the Request-Header.


= 1.2.10 =
* bugfix: caching now works even with very long URLs
* beta feature: On the Optionpage you can set an oAuth-Bearer-Accesscode for authentification at JSON-Server: This Bearer-Code is added to the Request-Header.


= 1.2.9 =
* new: "{street:purejsondata}": Default-display of a datafield is NOT HTML, but HTML-Tags are converted. If you want to use really the pure data from the JSON-Feed, add ":purejsondata". Try first {street}, then {street:html} and then {street:purejsondata}. In some cases: Take care that "meta http-equiv=content-type..." is set to "utf-8"


= 1.2.8 =
* bugfix: handling of JSON-values with $


= 1.2.7 =
* Wordpress 4.3.1: check - all ok
* bugfix: correct encoding of spaces when using {FIELD:html}
* bugfix: deleting unfilled template-items containing dots


= 1.2.6 =
* Wordpress 4.3: check - all ok
* added feature: remove unfilled template-placeholders {...}
* introducing "JSON Content Importer PRO"

= 1.2.5 =
* Wordpress 4.2.3: check
* minor bugfix regarding attribute "ifNotEmptyAddRight"
* new Pluginwebsite: http://json-content-importer.com/

= 1.2.4 =
* "&amp;" in JSON-Feed-URLs are replaced by "&"

= 1.2.3 =
* added a donated feature: new shortcode-params "oneofthesewordsmustnotbein", "oneofthesewordsmustnotbeindepth". This is for excluding JSON-data from display: When one of the ","-separated keywords at "oneofthesewordsmustnotbein" is found in the textblock, ignore this textblock

= 1.2.2 =
* minor bugfix: JSON-Structures like { "$a": "$b"} can be handled (before: "$" made problems)

= 1.2.1 =
* new feature "{street:htmlAndLinefeed2htmlLinefeed}": Text-Linefeeds of JSON-data are converted to HTML-Linefeeds
* Boolean JSON-Values were ignored before this version. Now the text "true" or "False" is displayed
* Bugfixing Cacher: Timeout-Parameter of cache was not handled right
* Fixed a bug with JSON-Value containing "$"

= 1.2.0 =
* new shortcode-parameter: "oneofthesewordsmustbein" and "oneofthesewordsmustbeindepth"
* filter & hook for third party extensions added: hook "json_content_importer_extension" and filter "json_content_importer_result_root"
* Sourcecode: Classes rearranged
* minor bugfix: number of items in subloop/subloop-array was sometimes ignored and all was displayed
* "made in munich" added (see faq)

= 1.1.2 =
* Bugfix: tags like "{aa/aa}" are ok (previous: error)
* Display JSON-HTML-Data really as HTML. Default: JSON-HTML-Data is displayed not as HTML but as HTML-Text. New in this version: tag-sytax like "{tag:html}" or "{street:html,ifNotEmptyAddRight:extratext}" allows real HTML-display.
* New parameter in "[jsoncontentimporter]"-shortcode: set http-timeout "urlgettimeout". default is 5 seconds (ueful if source-website of JSON is slow)
* Logo of plugin: Wordpress-Logo inserted
* Update of screenshots

= 1.1.1 =
Bugfixes

= 1.1.0 =
Completely rewritten template engine for even better JSON-handling:

* "subloop-array": key should also be in the closing tag, e.g. "{subloop-array:KEY:10}{some_array_field}{/subloop-array:KEY}".
The "subloop-array" without KEY in the closing tag is ok if there is only one "subloop-array" in the template. But if there are more than one "subloop-array" in the template insert the KEY in the closing tag!
Then the template engine can identify the correct JSON-data.

* "subloop": what is above for "subloop-array" is also for "subloop", e.g.  "{subloop:KEY:10}{some_object_field}{/subloop:KEY}"

* templates like "{subloop-array:AAAA:10}{text}{subloop:AAAA.image:10}{id}{/subloop:AAAA.image}{/subloop-array:AAAA}" are possible:
one is the recursive usage of "subloop-array" and "subloop".
the other is "{subloop:AAAA.image:10}" where "AAAA.image" is the path to an object.

* JSON-data with multiple use of arrays can be handled by the template engine

= 1.0.5 =
* Added Screenshots
* Enhanced "subloop-array", new processing of pure string/numeric-array data (before: only string/numeric-data in an object)
* Enhanced FAQs: Added Link to Website for better creating shortcode-markups

= 1.0.4 =
Bugfixes

= 1.0.3 =
Enhanced the template engine for better JSON-handling.

= 1.0.2 =
Initial release on WordPress.org

== Upgrade Notice ==
= 1.5.3=
* Enhanced, see Tab "Basic Settings": Send Header "HEADER_KEY:HEADER_VALUE": Insert 'header HEADER_KEY1:HEADER_VALUE1#HEADER_KEY2:HEADER_VALUE2' in the following text field, and no 'Bearer' will be added. E.g.: 'header User-Agent:JCIfree'
* Enhanced: The internal communication within a WordPress block relies on APIs, typically accessed via GET requests. For larger datasets, POST requests are more suitable. This new JCIfree version now utilizes POST instead of GET.
* Changed: Some server have problems, using jcifree-block.php as JavaScript-Applicaton (strict mime policy). Solution: Renamed to jcifree-block.js