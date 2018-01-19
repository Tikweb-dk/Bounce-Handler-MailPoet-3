=== Bounce Handler MailPoet 3 ===
Contributors: kasperta
Tags: newsletter, mail, email, emailing, mailpoet, bounce handler, bounce email, automatic, tikweb
Donate link: http://www.tikweb.dk/donate/
Requires at least: 4.6
Tested up to: 4.9
Requires PHP: 5.2
Stable tag: 1.3.2

Automatic mail bounce handling for MailPoet 3 to handle bounce emails easily when using your own SMTP server.

== Description ==

Automatic mail bounce handling for MailPoet 3, for installations using your own SMTP server. Install the plugin and find `Bounce Handling` menu under the MailPoet menu to setup.

= What's new? =

* New Action & rules (Change status to Bounce) added in the bounce scenario
* Proper e-mail fields validation
* Bounced email logs.

= Features =

* Delete bounce emails
* Automatic un-subscribing of users from the MailPoet newsletter list, based on bounced emails
* Connect with IMAP, POP3 and NNTP
* Check bounced emails with selected scheduling settings

== Installation ==


There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your WordPress dashboard, navigate to Plugins > Add New
2. Search for `Bounce Handler MailPoet`
3. Click on "install now" under "Bounce Handler MailPoet"
4. Activate the plugin
5. A new `Bounce Handling` sub-menu will appear under the MailPoet menu in your WordPress dashboard

= 2. The easy way =
1. Download the plugin (.zip file) by using the blue "download" button underneath the plugin banner at the top
2. In your WordPress dashboard, navigate to Plugins > Add New
3. Click on "Upload Plugin"
4. Upload the .zip file
5. Activate the plugin
6. A new `Bounce Handling` sub-menu will appear under the MailPoet menu in your WordPress dashboard

= 3. The old-fashioned and reliable way (FTP) =
1. Download the plugin (.zip file) by using the blue "download" button underneath the plugin banner at the top
2. Extract the archive and then upload, via FTP, the `bounce-handler-mailpoet` folder to the `<WP install folder>/wp-content/plugins/` folder on your host
3. Activate the plugin
4. A new `Bounce Handling` sub-menu will appear under the MailPoet menu in your WordPress dashboard


== Screenshots ==

1. Bounce Handling Settings Page

== Changelog ==

= 1.3.2 – 2017-11-22 =
* Updated include/class-mailpoet-bounce-handler.php

= 1.3.1 – 2017-11-22 =
* To fixed Settings option key name that is affected cron system.

= 1.3.0 – 2017-11-07 =
* To fixed Actions & Notifications settings
* To added more effective functions in the Bounce Logs tab
	- To added log list dropdown
	- To added new filter for bounce reasons
	- Each coulmns are now sorting by clicking the column header.

= 1.2.2 – 2017-10-13 =
* Updated new dropdown named "Disregard current list" and fixed the functionalities.

= 1.2.1 – 2017-09-29 =
* On the Actions & Notifications tab, the fifth item [[Add/remove on another list] on the dropdowns for mail box full and mailbox not available.
* When it is selected, it will show the same three extra dropdown as for “Unsubscribe the user”

= 1.2.0 – 2017-09-29 =
* On the Actions & Notifications tab, the two dropdowns for mailbox full and mailbox is not available split into 4 dropdowns each. 

Older version 1.1.2: [Unsubscribe the user and add him to the list “xxx”]
Version 1.2.0: [Unsubscribe the user] and [add/remove] him for the list [xxx] as [Subscribed/Unconfirmed/Unsubscribed/Bounced] 

= 1.1.2 =
* The error functions have been improved more than before in the version 1.1.2. 

= 1.1.1 =
* Fixed the PHP warning issue in the version 1.1.0. 

= 1.1.0 =
* Bounced email logger add. Now you can check list of email those were bounced.
* Bounce checker output buffering improved.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= x.0.0 =
* There are nothing else needed, than upgrading from the WordPress pluings screen.
