=== MightyReach for WordPress ===
Contributors: ryanwaggoner, benrasmusen
Donate link: http://mightybrand.com/blog/
Tags: mightybrand, mightyreach, social media, twitter, stats, feedburner, rss, google analytics
Requires at least: 2.8
Tested up to: 2.8.5
Stable tag: 0.2

== Description ==

MightyReach for WordPress displays your Feedburner, Twitter and Google Analytics stats on your dashboard

== Short Description == 

MightyReach for WordPress displays your Feedburner, Twitter and Google Analytics stats on your dashboard

== Installation ==

1. Upload the `mightyreach-wp` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the MightyReach settings menu and enter the following information:
	* Twitter username
	* Feedburner URI
	* Google Analytics Account ID and Grant Google Access via the link provided
1. Ensure that you do not have your Twitter profile protected and that you have enabled the Feedburner Awareness API (details in FAQ)
1. Done! The MightyReach widget will appear on your dashboard

== Frequently Asked Questions ==

= Why would I use this plugin? =

To easily keep track of your social media presence across many different services.

= How often is my data updated? =

Once per hour. Please note that in the case of Feedburner, only data from yesterday and the day before yesterday is available, so you won't see live changes.

= Why aren't I getting any data on Feedburner? =

Ensure that your Feedburner URI is set correctly. Your URI is just the portion of your RSS url following the feeds.feedburner.com:

For example, 'mightybrand' is the URI in the feed below:

http://feeds.feedburner.com/mightybrand

IMPORTANT: You also have to enable the Feedburner Awareness API. To do so, follow these steps:

1. Login to your Feedburner account
1. Select the feed you want to monitor
1. Go to the 'Publicize' tab
1. Click on the Awareness API link
1. Click 'Activate'

Data should start flowing shortly.

= Why am I not getting any data from Google Analytics? =

1. Make sure you have granted the plugin access to your Google Analytics account by clicking the link in the admin options
	* If no link is showing up you have probably granted Google Analytics access, if not check the 'Clear data' checkbox to start over
1. Verify that you've selected the correct Google Analytics Account in the list.

= What other services will you support? =

We're not sure, but here's a few of the ideas we have:

* Youtube
* Bit.ly
* Flickr
* Facebook

It's really up to you though...contact us and let us know what you want to see:

http://mightybrand.com/blog/contact/

= What features are planned? =

We're not sure, but here's a few of the ideas we have:

* Trend graphing
* Tracking more services
* Tracking more metrics from each service
* Tracking on a per-post basis
* Ability to publish your stats in your theme

It's really up to you though...contact us and let us know what you want to see:

http://mightybrand.com/blog/contact/

= Do I have to enter my passwords for any services? =

No. Though for Google Analytics you'll have to grant access to the plugin, but you never have to give up your login and you can always revoke access. To revoke access to the plugin go to: https://www.google.com/accounts/IssuedAuthSubTokens.

= Is any of my data stored or transmitted anywhere other than my WordPress database? =

No.

= What if I have a problem, comment, or suggestion? =

We'd love to hear from you, so please contact us at the link below. Be sure to specify that it's regarding the MightyReach for WordPress plugin. Thanks!

http://mightybrand.com/blog/contact/

== Screenshots ==

1. Standard dashboard view.

== Changelog ==

= 0.2 =
* Added Google Analytics support
* Fixed date issue with Feedburner

= 0.1 =
* Initial alpha version
* Only Twitter and Feedburner supported