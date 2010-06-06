=== xili-dictionary ===
Contributors: MS xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file, l10n, i18n, language, international,wpmu
Requires at least: 2.8.0
Tested up to: 3.0-RC
Stable tag: 1.0.7

xili-dictionary is a dictionary storable in taxonomy and terms to create and translate .po files or .mo files and more... 

== Description ==

**xili-dictionary is a dictionary storable in taxonomy and terms to create, update and translate .po files or .mo files and more...**

* xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. 
* With this dictionary, collecting terms from categories (title, description), from current theme - international terms with ` _e(), __() or _n() ` functions - , it is possible to create and update .mo file in the current theme folder.
* xili-dictionary is full compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/) plugin and [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/) plugin.

TRILOGY FOR MULTILINGUAL CMS SITE : [xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/), 

= 1.0.6, 1.0.7 = 
* fixes issues on wpmu mode and .mo saving
= 1.0.5 =
* introduces some improvements specifically for WPMU and management of .mo of theme shared by each site and particular translated file of one site of wpmu.
* now possible to import .pot file if the name of this file is the name of the theme text domain. *(twentyten.pot in twentyten default theme)*

= 1.0.4 =
* minor modifications for WP 3.0 and WPMU (for tests before future and specific improvements for wpmu 3.0)
= 1.0.3 =
* fixes some directories issues in (rare) xamp servers and in theme's terms import. 
* Create .po with empty translations. Helpful if you send the .po file to a translator that uses app like poedit. 

**1.0.2 beta**
* Create languages list, if xili-language plugin absent, for international themes - see [post](http://dev.xiligroup.com/?p=312 "xili-dictionary for international themes") - to manage or improve current translations.
* JS and vars, lot of fixes.
* Add a term UI now use dynamic input (with javascript included). 
* Now use POMO translations libraries included in WP since 2.8. 
* Add features to set and manage plural terms used by `_n()`

**For previous WP versions (<2.8), please use 0.9.9 release.** 

**0.9.9**
some fixes - better log display when importing from theme's files - tested on WP 2.9-rare

**0.9.8**
verified on official WP 2.8 - see Notes
**0.9.7.1**
grouping of terms by language now possible, - better import .po - enrich terms more possible (same terms with/without html tags (0.9.7.2 : some refreshing fixes)

THESE VERSIONS 1.0.x ARE BETA VERSION (running on our sites and elsewhere) - WE NEED MORE FEEDBACK even if the first from world are good - coded as OOP and new admin UI WP 2.7 features (meta_box, js, screen options,...)

Some features (importing themes words to fill msgid list) are not totally stable (if coding is crazy - too spacing !)...

== Installation ==

1. Upload the folder containing `xili-dictionary.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. active and visit the dictionary page in tools menu ... more details soon... [here](http://dev.xiligroup.com/?cat=394&lang=en_us) - 

== Frequently Asked Questions ==

= What about WPMU and the trilogy ? =
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)

Since WP 3.0-alpha, if multisite is activated, the trilogy is now compatible and will include progressively some improvements dedicaded especially for WPMU context. Future specific docs will be available for registered webmasters.

= Is the term msgid may contain words enriched by html tags ? =
like `<em> or <strong>`

Yes, since version 0.9.7. 

`
a <strong>good</strong> word
`

can be translated by

`
un mot <strong>exact</strong>
`


= Where can I see websites using this plugin ? =

dev.xiligroup.com [here](http://dev.xiligroup.com/ "a multi-language site")
and
www.xiliphone.mobi [here](http://www.xiliphone.mobi "a theme for mobile") also usable with mobile as iPhone.

= What is the difference with msgid and msgtr in .po file ? =
The msgid line is equal to the term or sentence hardcoded in the theme functions like  ` _e() or __() `. msgstr is the translation in the target language : by instance `fr_FR.po` for a french dictionary. (the compiled file is `fr_FR.mo` saved in the theme folder.
The root language is in Wordpress currently `en_US`, but with xili-dictionary, it is possible to create a `en_US.mo` containing the only few terms that you want to adapt.

= Is xili-dictionary usable without xili-language to edit .po or .mo file ? =

With certain conditions, the language must in the default list and if the language files are not in the root of the current theme, you must add this line in functions.php file of this theme (normally set before xili-language is installed) :

`define('THEME_LANGS_FOLDER','/nameoflangfolder'); // in Fusion: /lang`

= What about plural translations ? =
Today works with .mo or .po with simple twin msgid msgstr couple of lines and themes with functions like  ` _e() or __() ` for localization AND `_n()` which manage singular and plural terms like `msgid, msgid_plural, msgstr[0],...`

== Screenshots ==

1. the admin settings UI and boxes for editing, sub-selection and create or import files (.mo or .po).
2. Since 1.0.0, plural terms are allowed.
3. MsgID with his singular and his plural line.
4. MsgSTR with separators between occurrences of n plural terms `msgstr[n]` (soon more practical UI).

== Upgrade Notice ==

Upgrading can be easily procedeed through WP admin UI or through ftp.
Don't forget to backup before.
Verify you install latest version of trilogy.

== More infos ==

This first beta releases are for theme's creator or designer with some knowledges in i18n.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/?p=312 "Why xili-dictionary ?")

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-language/).

== Changelog ==
= 1.0.6 = fixes issues on wpmu and .mo saving
= 1.0.4 = 100408 - minor modifications for WP 3.0 and WPMU (tests purpose)
= 1.0.3 =
fixes some directories issues in (rare) xamp servers and in theme's terms import. Create .po with empty translations.
= 1.0.2 beta =
Use POMO libraries and classes only in WP > 2.8. Add plural translations. Add edit term dynamic UI
= 0.9.9 = 
* fixes existing msgid terms - 
* better log display in importing theme's terms
* more html tags in msg str or id
= 0.9.8 = 
* verified on official WP 2.8.
* fixes query error, 
* .1 fixe IIS error.

= 0.9.7.2 = some fixes
= 0.9.7.1 = list of msgid ID at end 
= 0.9.7 = grouping of terms by language now possible, and more...
= 0.9.6 = W3C - recover compatibility with future wp 2.8
= 0.9.5 = sub-selection of terms in UI, better UI (links as button)
= 0.9.4.1 = subfolder for langs file - ` THEME_LANGS_FOLDER ` to define in functions.php with xili-language
= 0.9.4 = second public release (beta) with OOP coding and new admin UI for WP 2.7
= 0.9.3 = first public release (beta) 

© 100606 - MS - dev.xiligroup.com
