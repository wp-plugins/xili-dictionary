=== xili-dictionary ===
Contributors: michelwppi, MS dev.xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file, l10n, i18n, language, international,wpmu,plural,multisite, WP network, translation
Requires at least: 3.6.1
Tested up to: 4.0
Stable tag: 2.9.1
License: GPLv2


xili-dictionary is a dictionary storable in CPT and terms to create and translate .po files or .mo files and more...

== Description ==

**xili-dictionary is a dictionary storable in custom post type (CPT) and terms (custom taxonomy) to create, update and translate .po files or .mo files of current theme folder and of current plugins.**

* xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the post tables of WordPress as CPT to build .mo files (used online by WP website), .po files (file assigned to a language and used by translator, text format of compiled .mo), and now .pot files.
* With this dictionary, collecting terms from taxonomies (title, description), from bloginfos, from wp_locale, from current theme - international terms with ` _e(), __() or _n() or _x(),  _ex(), _nx(),... ` and other functions as in I10n.php - , it is possible to create and update .mo file in the current theme folder and current plugins.
* By importing .mo files, it is possible to regenerate readable .po files and enrich translation tables.
* xili-dictionary is full compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/) plugin and [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/) plugin. Also compatible with [xili re/un-attach media](http://wordpress.org/extend/plugins/xili-re-un-attach-media/) !


= roadmap =
* code source renewed with latest WP tools
* readme rewritting
* writer management

= Version 2.9.1 (updated 2014-07-18) =
see [Changelog tab](http://wordpress.org/extend/plugins/xili-dictionary/changelog/).

== Installation ==

1. Upload the folder containing `xili-dictionary.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded -
3. Activate and visit the dictionary page in tools menu and docs [here](http://dev.xiligroup.com/xili-dictionary/) -
4. To edit a msg, you can start from dictionary list or XD msg list using current WP admin UI library. Don't forget to adapt UI with screen options and moving meta boxes.

More infos will be added progressively in a wiki [here](http://wiki.xiligroup.org/index.php/Main_Page).

== Frequently Asked Questions ==

= Why xili-dictionary is not included in xili-language ? =

* because xili-dictionary is available for non multilingual site to manage theme and plugin translations.
* because it is possible to desactivate xili-dictionary after creation of po mo files used by WP and xili-language.

= What about WP multisite (or network - former named WPMU) and the trilogy ? =
[xili-language](http://wordpress.org/extend/plugins/xili-language/), [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/), [xili-dictionary](http://wordpress.org/extend/plugins/xili-dictionary/)

Since WP 3.0, if multisite is activated, the trilogy is compatible and will include progressively some improvements dedicaded especially for WP network context.

= Where can I see websites using this plugin ? =
dev.xiligroup.com [here](http://dev.xiligroup.com/?p=187 "why xili-language ?") and example child themes below:

Twenty Ten [2010](http://multilingual.wpmu.xilione.com/)

Twenty Eleven [2011](http://2011.wpmu.xilione.com/)

Twenty Twelve [2012](http://2012.wpmu.xilione.com/)

Responsive [responsive](http://childxili.wpmu.xilione.com/)

Twenty Thirteen [2013](http://2013.extend.xiligroup.org/)

Twenty Fourteen [2014](http://2014.extend.xiligroup.org/)

And as you can see in [stats](http://wordpress.org/extend/plugins/xili-language/stats/), thousand of sites use xili-language.

= What is the difference with msgid and msgtr in .po file ? =
The msgid line is equal to the term or sentence hardcoded in the theme functions like  ` _e() or __() `. msgstr is the translation in the target language : by instance `fr_FR.po` for a french dictionary. (the compiled file is `fr_FR.mo` saved in the theme folder.
The root language is in Wordpress currently `en_US`, but with xili-dictionary, it is possible to create a `en_US.mo` containing the only few terms that you want to adapt or modify.

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

xili-dictionary is able to import a .mo of the target language and rebuild a .po editable in backend or a text editor. Example: if it_IT is in your language list, it_IT.mo can be imported, completed by webmaster and export as it_IT.po text file in languages sub-folder of the theme (as text backup).


= What is the differences with poEdit application ? =

[Poedit](http://poedit.net) is a standalone desktop application. Poedit offers the ways to translate apps and sites (that use gettext). This application is used by us to create localization of this and other plugins.
xili-dictionary is online and based on WP Core and essentially made for files used by WP (theme, plugin, custom terms of site...). For technicians, some advantages: possible to recover .mo files, to translate online items, to export .pot files, and more...


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

== More infos ==

This releases are for theme's creator or designer with some knowledges in i18n. Help are more and more included inside help tabs of dashboard and each screen.

The plugin post is frequently updated [wiki.xiligroup.org](http://wiki.xiligroup.org/index.php/Category:Xili-dictionary_plugin)

See [dev.xiligroup forum plugins forum](http://forum2.dev.xiligroup.com/forum.php?id=3).

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-dictionary/).

© 2009-2014 MS - dev.xiligroup.com

== Changelog ==
= 2.9.1 (2014-07-01) =
* fixes js for file state of local- files in multisite install.
* Better import from local- mo file
* During deletion, if a msgid has more than one origin, the msgid is not deleted, only origin is unassigned.
= 2.9.0 (2014-06-29) =
* better message when creating or download or file writing,
* import from source files (current theme, plugins), a good way if no pot files delivered by authors,
* fixe uppercase content (BINARY),
* more local js for ui.
= 2.8.0 (2014-06-05) =
* can now create pot file,
* improvement in export, UI and texts.
* fixes
= 2.7.2 (2014-06-02) =
* disable media button,
* subtitle of msg,
* formatted title (based on id).
= 2.7.1 (2014-05-26) =
* clean code
* fixes issue in download
* improve detection of existing msgid with context during manual creation. Need draft step to define context.
= 2.7.0 (2014-05-16) =
* able to save local-xx_XX (po or mo) in wp-content/languages/themes/ (WP_LANG_DIR)
= 2.6.1 (2014-03-08) =
* fixes issues when importing plural form.
* tested with WP 3.9-RC1
= 2.6.1 (2014-03-08) =
* fixes issues with pot file name and verify no msgstr when importing pot file.
* clean obsolete codes
= 2.6.0 (2014-03-02) =

* possible now to manage language files from plugins
* more embedded help

= 2.5.0 (2014-02-28) =

* possible now to download language files to computer for further works.
* limits scripts enqueuing to this plugin.

= 2.4.0 (2014-02-01) =
* WP3.8 improvements - fixes noun comment import,
* detect context when detecting duplicates

= 2.3.9 (2013-08-22) =
* fixes selector issue when importing pot file.
* tests WP 3.6 final
= 2.3.8 (2013-05-12) =
* add column type / origin in msgs list,
* add parent theme origin when import/export if child theme
* clean obsolete code lines
* fixes (capability)
* tests WP 3.6-beta3
= 2.3.7 (2013-05-09) =
* clean $wp_roles on deactivating
* able to import/export from parent languages subfolder if child theme active.
* tests 3.6 *
= 2.3.6 (2013-04-26) =
* improve import title for context of languages (no context for languages list) (XL 2.8.8+)
* Fixes origin in menu
= 2.3.5 (2013-04-16) =
* import titles of xili_language_list - the_category (xili_language 2.8.7)
* tested with WP 3.6 beta
= 2.3.4 (2013-03-03) =
* add infos and links in cat (removed from xl)
* import from sources : detects esc_html and esc_attr functions (I10n.php) and more.
= 2.3.3 (2013-02-10) =
* improved UI linking Categories (Taxonomies) and Translations in Edit Categories list,
* add feature to sort msgid by text,
= 2.3.2 (2013-02-03) =
* improved UI when editing msg,
* manage capabilities and option to add editing capability to role Editor.
* add support email form as in xili-language or xili-tidy-tags
* enable to import mo or po files of themes in languages folder (wp-content). So easy to create an adapted copy in theme languages sub-folder (since WP3.5)
= 2.3.1 (2013-01-27) =
* Tests WP 3.5.1 and XL 2.8.4, fixes
= 2.3.0 (2012-10-22) =
* add ajax functions for import and erase functions (big files, no freeze)
* fixes
= 2.2.0 (2012-09-29) =
* fixes issues of 2.1 series (messages, import, conditions, and more…).
* 2.1.2 and 2.1.3 removed from repository
= 2.1.3 (2012-08-20) =
* fixes
= 2.1.2 (2012-07-15) =
* list of untranslated msgs in edit msg screen.
* shortcut in side metabox to update .mo of current lang. See [xili wiki](http://wiki.xiligroup.org/index.php/Xili-dictionary:_what%27s_new_since_version_2.1.2 "Wiki")
* fixes local-xx_XX import - new icons
= 2.1.0 (2012-05-08) =
* in multilingual website context, requires version 2.6 of xili-language.
* local datas saved in local-xx_XX .mo/po files.
* origin taxonomy used to manage one dictionary with multiple themes.
* see [xili wiki](http://wiki.xiligroup.org/)

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

© 20140718 - MS - dev.xiligroup.com
