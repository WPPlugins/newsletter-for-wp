=== Newsletter for WordPress ===
Contributors: Morloi
Tags: voxmail, mailrouter, mymailer, email, marketing, newsletter, subscribe, widget,  contact form 7, woocommerce, buddypress, ibericode, 
Requires at least: 3.7
Tested up to: 4.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Newsletter for WordPress, an indipendent port of Newsletter for Wordpress, supporting some Italian ESPs: VOXmail, Mailrouter, Mymailer. Subscribe your WordPress site visitors to your ESP lists, with ease.

== Description ==

#### Newsletter for WordPress

*Adding sign-up methods for your VOXmail, Mailrouter, Mymailer lists to your WordPress site should be easy. With this plugin, it finally is.*

Newsletter for WordPress helps you add more subscribers to your VOXmail, Mailrouter, Mymailer lists using various methods. You can create good looking opt-in forms or integrate with any other form on your site, like your comment, contact or checkout form.


#### Some of the Newsletter for WordPress features

- Connect with your VOXmail, Mailrouter, Mymailer account in seconds.

- Sign-up forms which are good looking, user-friendly and mobile optimized. You have complete control over the form fields and can send anything you like to VOXmail, Mailrouter, Mymailer.

- Seamless integration with the following plugins:
	- Default WordPress Comment Form
	- Default WordPress Registration Form
	
- Developer friendly. Newsletter for WordPress is built upon Newsletter for Wordpress [code reference for developers](http://developer.nl4wp.com/).

#### What is VOXmail?

VOXmail is a newsletter service that allows you to send out email campaigns to a list of email subscribers. VOXmailis free for lists up to 2500 subscribers, which is why it is the newsletter-service of choice for thousands of businesses in Italy.

This plugin acts as a bridge between your WordPress site and your VOXmail account, connecting the two together.

If you do not yet have a VOXmail account, [creating one is 100% free and only takes you about 30 seconds](http://http://www.voxmail.it/land/newsletter-italiano-voxmail/g-gen).

== Installation ==

#### Installing the plugin
1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **Newsletter for WordPress** and click "*Install now*"
1. Alternatively, download the plugin and upload the contents of `newsletter-for-wp.zip` to your plugins directory, which usually is `/wp-content/plugins/`.
1. Activate the plugin
1. Set your VOXmail/Mailrouter/Mymailer API key in the plugin settings.

#### Configuring Sign-Up Form(s)
1. Go to *Newsletter for WP > Forms*
2. *(Optional)* Add more fields to your form using the **add Newsletter field** dropdown.
3. Embed a sign-up form in pages or posts by using the `[nl4wp_form]` shortcode.
4. Show a sign-up form in your widget areas using the "Newsletter Sign-Up Form" widget.
5. Show a sign-up form from your theme files by using the following PHP function.

`
<?php

if( function_exists( 'nl4wp_show_form' ) ) {
	nl4wp_show_form();
}
`

== Frequently Asked Questions ==

#### How to display a form in posts or pages?
Use the `[nl4wp_form]` shortcode.

#### How to display a form in widget areas like the sidebar or footer?
Go to **Appearance > Widgets** and use the **Newsletter for WP Form** widget that comes with the plugin.

#### How to add a sign-up checkbox to my Contact Form 7 form?
Use the following shortcode in your CF7 form to display a Newsletter sign-up checkbox.

`
[nl4wp_checkbox "Subscribe to our newsletter?"]
`

#### The form shows a success message but subscribers are not added to my list?
If the form shows a success message, there is no doubt that the sign-up request succeeded. Newsletter could have a slight delay sending the confirmation email though, please just be patient and make sure to check your SPAM folder.

When you have double opt-in disabled, new subscribers will be seen as *imports* by VOXmail/Mailrouter/Mymailer. 

#### How can I style the sign-up form?
You can use custom CSS to style the sign-up form if you do not like the themes that come with the plugin. The following selectors can be used to target the various form elements.

`
.nl4wp-form { ... } /* the form element */
.nl4wp-form p { ... } /* form paragraphs */
.nl4wp-form label { ... } /* labels */
.nl4wp-form input { ... } /* input fields */
.nl4wp-form input[type="checkbox"] { ... } /* checkboxes */
.nl4wp-form input[type="submit"] { ... } /* submit button */
.nl4wp-alert { ... } /* success & error messages */
.nl4wp-success { ... } /* success message */
.nl4wp-error { ... } /* error messages */
`


== Changelog == 


#### 1.0.0 - February 10, 2016

**Improvements**

- Initial release.
