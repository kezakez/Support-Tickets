=== Support Tickets ===
Contributors: takayukister
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9244372
Tags: support, support tickets, helpdesk, ajax, captcha, akismet, WPML, multilingual
Requires at least: 2.8
Tested up to: 2.9-rare
Stable tag: 1.0.1

With this plugin, you can manage a simple support ticket system on your WordPress.

== Description ==

Support Tickets is a WordPress plugin which allows you to create and manage a simple support ticket system or helpdesk system on your WordPress. If you are offering a support service and are looking for a simple tool to help you with that, Support Tickets is an excellent choice. I'm using this for my [customization service for Contact Form 7](http://contactform7.com/customization/), as well.

I've developed the Support Tickets plugin based on my [Contact Form 7](http://contactform7.com/) plugin, so there are similarities. If you are familiar with Contact Form 7, you'll be comfortable with Support Tickets very soon.

**[Home page](http://ideasilo.wordpress.com/2009/10/28/support-tickets/)**

= Multilingual Support =

You will have the ability to make a "multilingual support ticket system" with this plugin. This plugin allows you to write messages in your language and ask a professional translator to translate your message to another user's language. This feature utilize the [WPML](http://wpml.org/) plugin, so you need to install the plugin beforehand.

= Translators =

* Italian (it_IT) - [Gianni Diurno](http://gidibao.net/)
* Japanese (ja) - [Takayuki Miyoshi](http://ideasilo.wordpress.com)

== Installation ==

1. Upload the entire `support-tickets` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

You will find 'Support' menu in your WordPress admin panel.

== Frequently Asked Questions ==

If you have questions, please submit them [to the support forum](http://wordpress.org/tags/support-tickets?forum_id=10#postform).

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.0.1 =
* Bug fix: Additional fields don't show up.
* Bug fix: Backslashes disappear.
* Call $captcha->cleanup() if callable. Shorten cleanup period to 1h.
