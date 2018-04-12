=== Bounce Handler MailPoet 3 ===
Contributors: kasperta
Tags: newsletter, mail, email, emailing, mailpoet, bounce handler, bounce email, automatic, tikweb
Donate link: http://www.tikweb.dk/donate/
Requires at least: 4.6
Tested up to: 4.9
Requires PHP: 5.2
Stable tag: 1.3.5

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

= 1.3.5 - 2018-04-04 =
* Fixed the "Secure connection(SSL)" and "Self-signed certificates" issues.
  
  Settings for Secure connection(SSL):
  - Set Yes, if you want secure connection between this server to your mailserver.
  - Set NO, if you want a plain connection between this server to your mailserver.

  Settings for Self-signed certificates:
  - If your mailserver support Self Signed Certificate than set Yes, otherwise set No.


[Changelog](https://plugins.svn.wordpress.org/bounce-handler-mailpoet/trunk/changelog.txt)

== Upgrade Notice ==

= x.0.0 =
* There are nothing else needed, than upgrading from the WordPress pluings screen.
