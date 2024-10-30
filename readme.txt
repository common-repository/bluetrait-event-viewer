===BTEV===
Contributors: mwdmeyer
Tags: admin, btev, events, event viewer, stats, statistics, security, monitor, plugin, audit, logging
Requires at least: 3.2
Tested up to: 3.9
Stable tag: 2.0.2

Bluetrait Event Viewer (BTEV) monitors events that occur in your WordPress install.

== Description ==

Bluetrait Event Viewer 2 is now out!  

Bluetrait Event Viewer (BTEV) monitors events (such as failed login attempts) that occur in your WordPress install.  

You can publish these events via a password protected RSS feed if you need to monitor multiple WordPress installs.

If you have any comments/tips/requests etc please contact me or I am on twitter @mwdale

BTEV tracks the following events:

* password_reset
* delete_user
* wp_login
* lostpassword_post
* profile_update
* add_attachment
* wp_logout
* user_register
* switch_theme
* publish_post
* monitors activation/deactivation of other plugins

Please be aware that this plugin will phone home once a week to check for updates. 
The only information sent is the current version of Bluetrait Event Viewer installed.

== Installation ==

1. Download
1. Unzip (zip contains btev.php)
1. Upload to wp-content/plugins
1. Activate within Wordpress Admin Panel

== Frequently Asked Questions ==

= What does LOCKDOWN mode do? =

The LOCKDOWN mode is designed to make it more difficult to disable BTEV.  
It might be required for extra security or if you don't want users with Administrator permissions to be able to disable the plugin.
In LOCKDOWN mode the follow options are disabled:

1. Clear Logs
1. Update Settings
1. Uninstall
1. Deactivate

This can make it more affective in logging changes that occur in your site.

= How do I enable LOCKDOWN mode? =

open btev.php and find the line (near the top) that says:

		define('BTEV_LOCKDOWN', FALSE);
and change it to:

		define('BTEV_LOCKDOWN', TRUE);
		
Then change the permissions of the file so that it cannot be edited.
		
= What limitations does LOCKDOWN mode have? =

1. Please be aware that LOCKDOWN mode is NOT a guarantee that BTEV will say active if you site is hacked.
1. An extra layer of security is added but there are many other ways to disabled BTEV.  
1. It is recommended that btev.php is NOT writable so that the file cannot be editted from within WordPress.
1. Please be aware that LOCKDOWN mode is NOT a guarantee that BTEV will say active if you site is hacked.

= BTEV gives me the following message "Your BTEV settings have been set back to the default settings. This won't happen in future upgrades." Why? =

You've just installed BTEV, you can ignore this message.

This message should only be displayed once. Let me know if you keep getting it.

= How often does "auto prune" run? =

If enabled auto prune runs once a day via the WordPress cron function.


= BTEV has a bug and/or I want to suggest some new features. How? =

Please contact me here: http://michaeldale.com.au/contact/

== Event API ==

It is possible to add your own events to the event viewer. Simply call the following function when you want add an entry:
        
        btev_trigger_error($error_string, $error_number, __FILE__, __LINE__);
or

        btev_trigger_error($error_string, $error_number);

Argument Descriptions:

1. $error\_string: This value can be any string, it is used in the description field in the event viewer.
1. $error\_number: This value can be one of the following: E\_USER\_ERROR, E\_USER\_WARNING, E\_USER\_NOTICE. These values determine the type of message in the event viewer (Error, Warning, Notice).
1. \_\_FILE\_\_: This is the file where the event occurred, please note \_\_FILE\_\_ is a PHP predefined variable. This value determines the source.
1. \_\_LINE\_\_: This is the line where the event occurred, please note \_\_LINE\_\_ is a PHP predefined variable.

So an example would be:
        
		btev_trigger_error('Login Successful: "' . $user_login . '"', E_USER_NOTICE);

or
        
		btev_trigger_error('Login Successful: "' . $user_login . '"', E_USER_NOTICE, __FILE__, __LINE__);

NOTE: You should check to make sure that the plugin is active. The easiest way to do this is as follows:

		if (function_exists('btev_trigger_error')) {
			btev_trigger_error('Login Successful: "' . $user_login . '"', E_USER_NOTICE);
		}


== Change Log ==

KEY  
* = Bug fix  
+ = Added feature/function  
- = Something changed (only if not a bug fix)  

Change Log Start

Note: Release Date is DD/MM/YYYY :)

= 2.0.2 (4/5/2014) =

+ Tested with WordPress 3.9 
- HTML improvements (updated some button styles to match WordPress 3.9) 

= 2.0.1 (2/9/2012) =

* Fixed PHP Error Reporting
* Fixed LOCKDOWN mode 
- Email subject is now included in event logs 

= 2.0 (18/8/2012) =

+ Tested with WordPress 3.4.1 
+ Logs "Publish Post" Event 
+ Improved Display of Events 
- New Update Checker 
- Now Requires PHP 5.2+ and WordPress 3.2+ (major upgrade to code base was done in 2.0) 

= 1.9.3 (8/8/2011) =

* Fixed email issue in WordPress 3.2 (thanks Robert) 

= 1.9.2.1 (27/07/2011) =

+ Tested with WordPress 3.2.1 
- Email Alerts now use wp_mail and therefore are logged in the event viewer 
- More HTML improvements

= 1.9.2 (27/04/2011) =

+ Email Alerts for successful/failed logins 
+ Tested with WordPress 3.1.2

= 1.9.1 (25/04/2011) =

+ Tested with WordPress 3.1.1  
- Updated dashboard widget to improve display of information  
- Updated a couple of URLs and HTML fixes  
- Javascript confirm when clearing logs  

1.9.0 (24/07/2009)

+ Support for Bluetrait Connector  

1.8.3 (16/11/2008)

* fixed error where database script was not run when upgrading to 1.8.2 through the auto plugin update system.  
* fixed activity_box_end issue with new WordPress 2.7 style  

1.8.2 (15/11/2008)

* compatible with WordPress 2.6 and 2.7  
+ Dashboard widget for WordPress 2.7+  
+ Option to select what data is logged (notices, warnings, errors)

1.8.1 (05/06/2008)

* fixed debug error reporting when using PHP4  
* fixed rss date output (RSS feed should be valid now)  
* fixed upgrading from versions older that 1.7  
* fixes to the cron stuff  
+ added javascript confirm when uninstalling

1.8 (03/05/2008)

+ Styling update to match WordPress 2.5  
+ RSS for Recent Events

1.7 (08/04/2008)

* fixed error when using PHP4 (reported by David T).

1.6 (27/01/2008)

* compatible with WordPress 2.5  
* able to deactivate plugin while LOCKDOWN enabled and plugin file is in a folder (now fixed)  
* fixed "Your BTEV settings have been set back to the default settings. This won't happen in future upgrades." message in WordPress 2.5  
* track failed logins within WordPress 2.5 now works  
- Updated plugin url

1.5 (29/11/2007)

+ Event Details  
+ Auto Prune (using cron)  
- Changed RDNS link to services.bluetrait.org  
- Small code cleanup

1.4 (11/09/2007)

+ Source Cropping  
* small WordPress 2.3 improvements  

1.3 (29/08/2007)

* now installs on WordPress 2.3  

1.2 (28/08/2007)

* Bug fix for MySQL 3 users  
* minor fixes

1.1 (17/05/2007)

+ Lockdown Mode  
+ Tracks Deactivate All plugins  
+ API improvements

1.0 (Released 14/05/2007)

- wp_mail function now supported under WordPress 2.2  

0.6 (Released 22/04/2007)

* bug in lostpassword_post causing it to error  

0.5 (Released 22/04/2007)

+ monitor activation/deactivation of other plugins  
+ track email sent from WordPress  
- small code cleanup/changes  

0.4 (Released 07/04/2007)

+++Requires WordPress 2.0.0+++

+ New Events Tracked:  
+ Switching Themes  
+ Started cron code (not finished)  
+ wp\_nonce protection stuff.  
+ uninstaller  
- removed btev\_site table. Two less queries per page now.  
- Updated plugin website link  
- Small code fixes  
- removed btev\_site database  

0.3 (Released 01/04/2007)

+ Logs file uploads, Logout, Added user, able to override wp\_login  
+ Able to use set\_error_handler  
* fixed Previous/Next Page links  
* stops the file from being run directly  
- cleaned up and commented some code  

0.2 (Released 30/03/2007)

+ Date is filled in for an event  
+ Update checker  
+ More events tracked  
- Moved Event Viewer to link under dashboard  

0.1 (Released 28/03/2007)

+ Public release

== Screenshots ==
1. Dashboard Recent Events
1. Settings Page