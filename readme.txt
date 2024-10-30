=== Invite Codes for Contact Form 7 ===
Contributors: haste18
Tags: invite codes, form, coupon, restriction
Requires at least: 6.2
Tested up to: 6.6.2
Requires PHP: 7.2
Stable tag: 1.2.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin that adds invite codes functionality to Contact Form 7.

== Description ==
Invite Codes for Contact Form 7 is an innovative add-on for the popular [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) plugin that adds a new layer of control by allowing form submissions only when a valid invite code is entered.
This plugin is perfect for events, giveaways, private registrations, exclusive content, and more. 
By leveraging invite codes, you can ensure that only authorized users can submit your forms, enhancing security and exclusivity.

== Key Features ==
📋 Invite Code Management: Admins can easily create, view, and manage invite codes within the WordPress dashboard.
🔑 Unique Code Generator: A customizable generator allows users to create unique codes with parameters like prefix, postfix, length, and allowed characters/symbols.
🔄 Import & Export: Admins can import invite codes in bulk and export existing codes into CSV files for backup or marketing purposes.
⏳ Expiration & Usage Limits: Codes can have expiration dates and usage limits for added control.
📝 Invite Code Validation: Limit form submissions to users with a valid invite code.
🌐 Localization Support: Fully translatable, supporting multiple languages for global usability.

== Use cases ==
📅 Event-Driven Forms: Ideal for event registrations, RSVPs, or exclusive access to webinars.
🎁 Coupons for Giveaways: Distribute unique codes for contest entries or giveaways, ensuring only authorized participants can join.
🔒 Exclusive Content Access: Provide codes for members-only content or restricted access events.
🔗 Seamless Integration: Works seamlessly with Contact Form 7, keeping the user experience familiar and easy.

== Installation and Configuration ==
This plugin is an add-on for Contact Form 7. Make sure you have Contact Form 7 installed before installing this plugin. You can find Contact Form 7 here: https://wordpress.org/plugins/contact-form-7/

To install Invite Codes for Contact Form 7 follow these steps:
1. Upload the entire `invite-codes-for-contact-form-7` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** screen (**Plugins > Installed Plugins**).
3. Configure the invite codes via **Invite Codes** submenu in the **Contact** menu.
4. The manual in the **Instructions** tab guides you through all the options and settings.

== QuickStart ==

1. Open the **Invite Codes** plugin via WordPress Menu > **Contact** > **Invite Codes**
2. Go to the **Codes Generator** tab, select your options and click on **Generate Codes**
3. Copy the codes by clicking on the button **Copy Codes to Clipboard** and go to the **Import** tab
4. Paste the codes in the Invite codes form, select maximum usage and expiry date (optional) and click on the button **Import Invite Codes**
5. Create or edit the form you want to use the invite codes on via WordPress Menu > **Contact** > **Contact Forms**
6. Inside the form add the Invite Codes placeholder. Eg: Unique code[text* invite_code placeholder "Enter your unique code"]
7. Save the form. It's recommended to test the form with a test code.

== Screenshots ==
1. Codes tab: Shows imported invite codes and their status
2. Import tab: Here you import codes and set maximum usage and expiration date
3. Export tab: Here you can export codes and download a CSV file
4. Codes Generator tab: Generate codes up to 1000 codes at once with preferred settings
5. Settings tab: Customize validation messages and (de)activate case sensitive mode
6. Example of a unique code printed on a card
7. Example of a Contact Form 7 form where this unique code is used

== Changelog ==

= 1.2.6 =

- Changed: Bugfix for display submenu
- Added: Plugin checks if Contact Form 7 is installed before activation

= 1.2.5 =

- Updated: readme.txt with extra information on installation and configuration
- Added: readme.txt QuickStart guide.

= 1.2.4 =

- Changed: Security improvements for export module
- Changed: Code optimizations

= 1.2.3 =

- Changed: Code optimizations 

= 1.2.2 =

- Changed: Plugin name "CF7 Invite Codes" changed to "Invite Codes for Contact Form 7"
- Changed: Permalink "cf7-invite-codes" changed to "invite-codes-for-contact-form-7"


= 1.2.1 =

Minor bug fixes

- Changed: The expiry date did not update when manually changed on the codes page.
- Changed: After submitting the form, the times_used value was not incremented.

= 1.2.0 =

First official release

- Added: Option to reuse a code up to 999 times or 0 for unlimited  
- Changed: Column `used` changed to `times used` showing the number of times the code has been used.  
- Added: Column `Max usage` added which shows the number of times a code can be used.  
- Added: Import module adjusted with `Maximum usage` option.  
- Changed: Export module adjusted. Files are stored on server now with random string in filename and can be downloaded / deleted.  
- Changed: Codes Generator now has many more options available.