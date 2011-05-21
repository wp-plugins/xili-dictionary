=== xili-dictionary ===
Contributors: MS xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file, l10n, i18n, language, international,wpmu,plural,multisite
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.3.4

xili-dictionary is a dictionary storable in taxonomy and terms to create and translate .po files or .mo files and more... 

== Description ==

**xili-dictionary is a dictionary storable in taxonomy and terms to create, update and translate .po files or .mo files and more...**

* xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. 
* With this dictionary, collecting terms from categories (title, description), from current theme - international terms with ` _e(), __() or _n() ` functions - , it is possible to create and update .mo file in the current theme folder.
* xili-dictionary is full compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/) plugin and [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/) plugin.

TRILOGY FOR MULTILINGUAL CMS SITE : [xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/), 

= roadmap =
* version only for WP 3.0 and more - code source cleaning
* more features for xili-language premium


= 1.3.4 =
* Detect recent xili-language premium used by professional webmasters - (for previous version < 3.0 use previous release)
= 1.3.3 =
Before xili-language version 1.8.8, it was necessary to change wp-config.php like japanese and set `WP_LANG` to ISO : from *ja* to **ja_JA**. Now xili-language version with 1.8.8 and xili-dictionary 1.3.3, the trilogy is updated, it is not necessary. So very easy for a japanese to manage his ja.po and ja.mo files in japanese or transform his site in a multilingual site by adding other language files. For other mother languages, just add the japanese (ja.mo) inside languages sub-folder of the target theme [kept here](http://ja.wordpress.org/).
= 1.3.2 =
* fixes some issues for mode standalone (*w/o xili-language for multilingual live mode*). This standalone mode is usable to improve (or adapt) localization or create po file in another language when not delivered with theme package.
= 1.3.1 =
* add translation links between `msgid` and (existing - or not) `msgstr` for each target language to help work of translator.
= 1.3.0 =
* javascript (thanks to DataTables library) for better list of terms displaying - 

= 1.2.2 =
* add help menu and messages on top
* fixes a temporary bug created by beta xili-language 1.8.1 
* compatibility with child theme and xili-language >=1.8.1 
* better folder detection

For previous versions, see Changelog and readme in tab Other Versions. 

== Installation ==

1. Upload the folder containing `xili-dictionary.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. active and visit the dictionary page in tools menu and docs [here](http://dev.xiligroup.com/xili-dictionary/) - 

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

== Screenshots ==

1. The admin settings UI and boxes for editing, sub-selection and create or import files (.mo or .po).
2. Since 1.3.1, links between original (msgid) and translations (msgstr).
3. Since 1.0.0, plural terms are allowed.
4. MsgID with his singular and his plural line.
5. MsgSTR with separators between occurrences of n plural terms `msgstr[n]` (soon more practical UI).

== Upgrade Notice ==

Upgrading can be easily procedeed through WP admin UI or through ftp.
Don't forget to backup before.
Verify you install latest version of trilogy (xili-language, xili-tidy-tags,…).

== More infos ==

This first beta releases are for theme's creator or designer with some knowledges in i18n.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/xili-dictionary/ "Why xili-dictionary ?")

See [dev.xiligroup forum plugins forum](http://forum2.dev.xiligroup.com/forum.php?id=3).

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-dictionary/).

© 2009-2011 MS - dev.xiligroup.com

== Changelog ==
= 1.3.4 = compatible with xili-language premium
= 1.3.3 = now able to use ja.mo and ja.po for japanese. fixes db issues.
= 1.3.2 = fixes for mode standalone w/o xili-language,
add translation links for each target lang.
= 1.3.0 = js for better list of terms display.
= 1.2.0 = compatibility with child theme and xili-language >=1.8.1 - better folder detection
= 1.1.1 = fixes issues in multisite mode (empty .mo)
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

© 20110521 - MS - dev.xiligroup.com
