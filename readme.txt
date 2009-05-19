=== xili-dictionary ===
Contributors: MS xiligroup
Donate link: http://dev.xiligroup.com/
Tags: theme,post,plugin,posts, page, category, admin,multilingual,taxonomy,dictionary, .mo file, .po file,language,international
Requires at least: 2.7.0
Tested up to: 2.7.1
Stable tag: 0.9.7.2

xili-dictionary is a dictionary storable in taxonomy and terms to create and translate .po files or .mo files and more... 

== Description ==

xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. With this dictionary, collecting terms from categories (title, description), from current theme - international terms with ` _e() or __() ` functions - , it is possible to create and update .mo file in the current theme folder.
xili-dictionary is full compatible with [xili-language](http://wordpress.org/extend/plugins/xili-language/) plugin and [xili-tidy-tags](http://wordpress.org/extend/plugins/xili-tidy-tags/) plugin.

= NEW 0.9.7.1 =

grouping of terms by language now possible, - better import .po - enrich terms more possible (same terms with/without html tags (0.9.7.2 : some refreshing fixes)

THIS VERSION 0.9.x IS A BETA VERSION (running on our sites and elsewhere) - WE NEED MORE FEEDBACK even if the first from world are good - coded as OOP and new admin UI WP 2.7 features (meta_box, js, screen options,...)

Some features (importing themes words to fill msgid list) are not totally stable (if coding is crazy - too spacing !)...

== Installation ==

1. Upload the folder containing `xili-dictionary.php` and language files to the `/wp-content/plugins/` directory,
2. Verify that your theme is international compatible - translatable terms like `_e('the term','mytheme')` and no text hardcoded - 
3. active and vist the dictionary page in tools menu ... more details soon... [here](http://dev.xiligroup.com/?cat=394&lang=en_us) - 

== Frequently Asked Questions ==

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

= What about WP 2.8 ? =
Today works only with .mo or .po with simple twin msgid msgstr couple of lines and themes with functions like  ` _e() or __() ` for localization.

== Screenshots ==

1. the admin settings UI and boxes for editing, sub-selection and create or import files (.mo or .po).

== More infos ==

This first beta releases are for theme's creator or designer.

The plugin post is frequently updated [dev.xiligroup.com](http://dev.xiligroup.com/?p=312 "Why xili-dictionary ?")

See also the [Wordpress plugins forum](http://wordpress.org/tags/xili-language/).
= 0.9.7.2 = some fixes
= 0.9.7.1 = list of msgid ID at end 
= 0.9.7 = grouping of terms by language now possible, and more...
= 0.9.6 = W3C - recover compatibility with future wp 2.8
= 0.9.5 = sub-selection of terms in UI, better UI (links as button)
= 0.9.4.1 = subfolder for langs file - ` THEME_LANGS_FOLDER ` to define in functions.php with xili-language
= 0.9.4 = second public release (beta) with OOP coding and new admin UI for WP 2.7
= 0.9.3 = first public release (beta) 

© 090518 - MS - dev.xiligroup.com
