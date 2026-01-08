=== All in One Security Audit Log Mailer ===
Contributors: studionoir
Tags: security, audit log, email, export, all-in-one-security
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically exports and emails All in One Security & Firewall audit logs on a monthly basis in CSV format.

== Description ==

This plugin automatically exports audit logs recorded by the All in One Security & Firewall plugin and sends them via email in CSV format. It helps security administrators and site managers regularly review security events on their sites.

= Key Features =

* Monthly automatic export: Automatically exports audit logs at specified date and time
* CSV format: Easy to open in Excel and other spreadsheet applications
* Email delivery: Can send to multiple email addresses simultaneously
* Flexible settings: Freely configure execution date/time and target period
* Manual execution: Instant execution available for testing
* Execution history: View last execution date/time and results in admin panel

= Requirements =

* All in One Security & Firewall plugin must be installed and activated

= Supported Languages =

* Japanese
* English

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New in WordPress admin
2. Search for "All in One Security Audit Log Mailer"
3. Click "Install Now"
4. Click "Activate" after installation

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Select and upload the zip file
4. Click "Install Now"
5. Click "Activate Plugin" after installation

= Initial Setup =

1. Go to Settings → AIOS Audit Log Mailer
2. Enter recipient email address(es)
3. Configure execution date/time and target period
4. Click "Save Settings"
5. Test with "Execute Now" button to verify operation

== Frequently Asked Questions ==

= Is All in One Security plugin required? =

Yes, this plugin uses audit logs from All in One Security & Firewall plugin.

= Email not arriving =

Please check:
* Email address is correctly entered
* WordPress email function is working (recommend using WP Mail SMTP or similar)
* Email not in spam folder

= Can I send to multiple email addresses? =

Yes, separate multiple addresses with commas. Example: mail1@example.com, mail2@example.com

= What is the CSV file encoding? =

UTF-8 with BOM for compatibility with Excel and Numbers.

== Screenshots ==

1. Settings page - Configure email recipients, schedule, and export period
2. System information - Check plugin status and execution history
3. Manual execution - Test export and email functionality

== Changelog ==

= 1.0.0 =
* Initial release
* Monthly automatic CSV export functionality
* Email delivery with attachment
* Flexible scheduling (date/time configuration)
* Manual execution for testing
* Execution history tracking

== Upgrade Notice ==

= 1.0.0 =
Initial release.