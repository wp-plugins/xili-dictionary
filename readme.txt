=== xili-dictionary ===
Contributors: michelwppi, MS dev.xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file, l10n, i18n, language, international,wpmu,plural,multisite
Requires at least: 3.2.1
Tested up to: 3.4
Stable tag: 2.0.0

xili-dictionary is a dictionary storable in CPT and terms to create and translate .po files or .mo files and more... 

== Description ==

**xili-dictionary is a dictionary storable in custom post type (CPT) and terms (custom taxonomy) to create, update and translate .po files or .mo files of current theme folder.**

* xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the post tables of WordPress as CPT. 
* With this dictionary, collecting terms from taxonomies (title, description), from bloginfos, from wp_locale, from current theme - international terms with ` _e(), __() or _n() or _x(),  _ex(), _nx(),... ` functions - , it is possible to create and update .mo file in the current theme folder.
* By importing .mo files, it is possible to regenerate readable .po files and enrich translation tables.
* xili-dictionary is full compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/) plugin and [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/) plugin.


= roadmap =
* code source cleaning
* readme rewritting
* tags and more for msg lines
* dictionary for other than theme's .po, .mo files

= NEW 2.0: MAJOR UPGRADE =
* new way of saving lines in CPT 
* use as soon as possible wp-admin UI library
* now msg lines full commented as in .po
* now translated lines (msgstr) attached to same taxonomy as xili-language (> 2.4.1)
* VERY IMPORTANT : before upgrading from 1.4.4 to 2.0, export all the dictionary content in .po files and empty the dictionary table.

For previous versions, see Changelog and readme in tab Other Versions. 

== Installation ==

1. Upload the folder containing `xili-dictionary.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. Activate and visit the dictionary page in tools menu and docs [here](http://dev.xiligroup.com/xili-dictionary/) - 
4. To edit a msg, you can start from dictionary list or XD msg list using current WP admin UI library. Don't forget to adapt UI with screen options and moving meta boxes.

More infos will be added progressively in a wiki [here](http://wiki.xiligroup.org/index.php/Main_Page).

== Frequently Asked Questions ==

= What about WP multisite (or network - former named WPMU) and the trilogy ? =
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)

Since WP 3.0-alpha, if multisite is activated, the trilogy is now compatible and will include progressively some improvements dedicaded especially for WP network context. Future specific docs will be available for registered webmasters.

= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/ "a multi-language site"),
multilingual.wpmu.xilione.com [here](http://multilingual.wpmu.xilione.com/ "a multi-language demo site")
and
www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

= What is the difference with msgid and msgtr in .po file ? =
The msgid line is equal to the term or sentence hardcoded in the theme functions like  ` _e() or __() `. msgstr is the translation in the target language : by instance `fr_FR.po` for a french dictionary. (the compiled file is `fr_FR.mo` saved in the theme folder.
The root language is in Wordpress currently `en_US`, but with xili-dictionary, it is possible to create a `en_US.mo` containing the only few terms that you want to adapt.

= Is xili-dictionary usable without xili-language to edit .po or .mo file ? =

Yes and now automatically detected ! For example, to modify the results of a translation for your site with your words.

= What about plural translations ? =
Today works with .mo or .po with simple twin msgid msgstr couple of lines and themes with functions like  ` _e() or __() ` for localization AND `_n()` which manage singular and plural terms like `msgid, msgid_plural, msgstr[0],...`

= What is a po file ? =

It is a text file like this (here excerpt) with different types of msgid :

`
msgctxt "comments number"
msgid "%"
msgstr "%"

msgid "Leave a reply"
msgstr "Laisser une réponse"

msgid "One thought on &ldquo;%2$s&rdquo;"
msgid_plural "%1$s thoughts on &ldquo;%2$s&rdquo;"
msgstr[0] "Une réflexion au sujet de &laquo&nbsp;%2$s&nbsp;&raquo;"
msgstr[1] "%1$s réflexions au sujet de &laquo&nbsp;%2$s&nbsp;&raquo;"

msgctxt "sentence"
msgid "comment"
msgid_plural "comments"
msgstr[0] "commentaire"
msgstr[1] "commentaires"

`

= What happens if only the .mo is available ? =

xili-dictionary is able to import a .mo of the target language and rebuild a .po editable in backend or a text editor. Example: if it_IT is in your language list, it_IT.mo can be imported, completed by webmaster and export as it_IT.po text file in languages sub-folder of the theme.



== Screenshots ==

1. The admin settings UI: table for sub-selection and create or import files (.mo or .po).
2. Msg edit screen with the msg series dashboard.
3. Msg list table screen as designed with WP admin UI library.
4. MsgID with his singular and his plural line.
5. MsgSTR with his plural.

== Upgrade Notice ==

Upgrading can be easily procedeed through WP admin UI or through ftp (delete previous release folder before upgrading via ftp).
IMPORTANT - Don't forget to backup before.
Verify you install latest version of trilogy (xili-language, xili-tidy-tags,…).
IMPORTANT - Before updating to xili-dictionary 2.0, export all the dictionary contents in .po files foreach target langs and empty the dictionary table made by 1.4.4.

== More infos ==

This first beta releases are for theme's creator or designer with some knowledges in i18n.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/xili-dictionary/ "Why xili-dictionary ?")

See [dev.xiligroup forum plugins forum](http://forum2.dev.xiligroup.com/forum.php?id=3).

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-dictionary/).

© 2009-2012 MS - dev.xiligroup.com

== Changelog ==

= 2.0.0 = 
* 120417 - repository as current
* 120405 - pre-tests with WP 3.4: fixes metaboxes columns
* 120219 - new way of saving lines in CPT - new UI using WP library
* now msg lines full commented as in .po
* now translated lines (msgstr) attached to same taxonomy as xili-language
* compatible with theme and language files in sub-sub-folder.
* IMPORTANT - before upgrading from 1.4.4 to 2.0, export all the dictionary in .po files and empty the dictionary.

= beta 1.4.4 = 
* 111221 - fixes
* between 0.9.3 and 1.4.4 see version 1.4.4 - 20120219
= 0.9.3 = first public release (beta) 

© 20120417 - MS - dev.xiligroup.com
