<?php
/*
Plugin Name: xili-dictionary
Plugin URI: http://dev.xiligroup.com/xili-dictionary/
Description: A tool using wordpress's CPT and taxonomy for localized themes or multilingual themes managed by xili-language - a powerful tool to create .mo file(s) on the fly in the theme's folder and more... - ONLY for >= WP 3.2.1 -
Author: dev.xiligroup - MS
Version: 2.12.1
Author URI: http://dev.xiligroup.com
License: GPLv2
Text Domain: xili-dictionary
Domain Path: /languages/
*/

# 2.12.1 - 150704 - datatables js updated to 1.10.7 (for jQuery 1.11.3 WP 4.3)
# 2.12.0 - 150628 - fixes, better labels in Writers and Origins, able to import parent sources if child theme active, writers displayed in list, compatible with Polylang taxonomy
# 2.11.2 - 150527 - link title added, more terms from post-template, core import process improved
# 2.11.1 - 150514 - fixes import terms from comment-template.php
# 2.11.0 - 150422 - add way to create only POT w/o import msgid in dictionary

# 2.10.3 - 150322 - improves adding context after draft state only, import get_the_archive_title msgid (since WP 4.1) - new datatables JS & css
# 2.10.2 - 150312 - fixes no context msgid creation after creation with context
# 2.10.1 - 150228 - pre-test with WP4.2 alpha - manages plugin language files in WP_LANG_DIR

# 2.10.0 - 141218 - ready for WP4.1 - add comment terms (from wp-includes/comment-template.php) - better query for WP 4.1
# 2.9.2 - 141216 - fixes WPLANG (obsolete in WP4.0) and export local-xx_yy.po
# 2.9.1 - 140701 - fixes js for file state of local- files in multisite install. Better import from local- mo file
# 2.9.0 - 140628 - better message when creating - scan and import from source files (current theme, plugins) - fixe uppercase content (BINARY)
# 2.8.0 - 140605 - can now create pot file - improvement in export, UI and texts
# 2.7.2 - 140602 - disable media button, subtitle of msg, formatted msg title (id)
# 2.7.1 - 140526 - clean code - fixes issue in download - improve detection of existing msgid with context
# 2.7.0 - 140516 - able to save local-xx_XX (po or mo) in WP_LANG_DIR/themes
# 2.6.2 - 140414 - fixes issues (warning) when importing plural form on origin assiging
# 2.6.1 - 140308 - fixes issues with pot file name and verify no msgstr when importing pot file.
# 2.6.0 - 140303 - possible to manage plugin language files, more help, clean source
# 2.5.0 - 140228 - possible to download po / mo / pot files to computer
# 2.4.0 - 140201 - WP3.7 improvements, WP3.8 - fixes noun comment import, detect context when detecting duplicate - new icons
#
# 2.3.9 - 130822 - WP3.6 final - fixes Strict Standards message (__construct) - fixe js when importing pot
# 2.3.8 - 130512 - tests 3.6 - add parent theme origin when import/export if child theme - clean obsolete code lines
# 2.3.7 - 130508 - tests 3.6 - clean $wp_roles on deactivating, able to import/export from parent languages subfolder
# 2.3.6 - 130426 - improve import title for context of languages (no context for languages list), fixes origin in menu
# 2.3.5 - 130415 - import titles of xili_language_list - the_category
# 2.3.4 - 130223 - add infos and links in cat (removed from xl) - import from sources : detects esc_html and esc_attr functions (I10n.php) and more
# 2.3.3 - 130211 - fixes nonce, add editor size option - add import taxonomies in dictionary in bottom of edit list - sortby content (add postmeta)
# 2.3.2 - 130203 - add support, add capabilities for editor, add import from subfolder wp-content/languages/themes/ // Else, load textdomain from the Language directory (I10n.php #470
# 2.3.1 - 130127 - tests wp351 and XL 2.8.4, fixes and few improvements in UI
#
# 2.3.0 - 121118 - add ajax functions for import and erase functions
# 2.2.0 - 120922 - fixes issues with .mo and .po inserts - better messages and warning
# 2.1.3 - 120728 - fixes
# 2.1.2 - 120715 - list in msg edit - new query - new metabox - new pointers - ...
# 2.1.1 - 120710 - fixes - new icons
# 2.1.0 - 120507 - options to save on new local-xx_XX.mo and more... needs XL 2.6
# 2.0.0 - 120417 - repository as current
# beta 2.0.0-rc4 - 120415 - fixes
# beta 2.0.0-rc3 - 120406 - pre-tests WP3.4: fixes metaboxes columns, conditional edit link in list
# beta 2.0.0-rc2 - 120402 - latest fixes (writers)
# beta 2.0.0-rc1 - 120318 - before svn
# beta 2.0.0 - 120219 - new way of saving lines in CPT
#
# now msg lines full commented as in .po
# now translated lines (msgstr) attached to same taxonomy as xili-language
# before upgrading from 1.4.4 to 2.0, export all the dictionary in .po files and empty the dictionary.
#
# beta 1.4.4 - 111221 - fixes
# between 0.9.3 and 1.4.4 see version 1.4.4 - 20120219
# beta 0.9.3 - first published - 090131 MS


// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'XILIDICTIONARY_VER', '2.12.1' );
define( 'XILIDICTIONARY_DEBUG', false ); // WP_DEBUG must be also true !

include_once ( ABSPATH . WPINC . '/pomo/po.php'); /* not included in wp-settings */

// the class
class xili_dictionary {

	var $plugin_url = ''; // Url to this plugin - see construct
	var $plugin_path = ''; // The path to this plugin - see construct

	var $subselect = ''; /* used to subselect by msgid or languages*/
	var $searchtranslated = ''; /* used to search untranslated - 2.1.2 */
	var $languages_key_slug = array(); // used for slug to other items
	var $languages_names = array(); // 2.6.1
	var $msg_action_message = '';
	var $xililanguage = ''; /* neveractive isactive wasactive */
	var $xililanguage_ms = false; // xlms
	var $tempoutput = "";

	var $langfolder =''; /* where po or mo files */
	var $parentlangfolder =''; /* where po or mo files of parent */

	var $xili_settings; /* saved in options */
	var $ossep = "/"; /* for recursive file search in xamp */

	// 2.0 new vars
	var $xdmsg = "xdmsg";
	var $xd_settings_page = "edit.php?post_type=xdmsg&amp;page=dictionary_page"; // now in CPT menu

	// post meta
	var $ctxt_meta = '_xdmsg_ctxt'; // to change to xdctxt
	var $msgtype_meta = '_xdmsg_msgtype'; // to hidden
	var $msgchild_meta = '_xdmsg_msgchild';
	var $msglang_meta = '_xdmsg_msglangs';
	var $msgidlang_meta = '_xdmsg_msgid_id'; // origin of the msgstr
	var $msg_extracted_comments = '_xdmsg_extracted_comments';
	var $msg_translator_comments = '_xdmsg_translator_comments';
	var $msg_flags = '_xdmsg_flags';
	var $msg_sort_slug = '_xdmsg_sort_slug'; // 2.3.3 for content sort

	var $origin_theme = ""; // used when importing
	var $local_tag = '[local]';
	var $exists_style_ext = false; // wp_enqueue_style( 'xili_dictionary_stylesheet' );
	var $style_message = '';
	var $xilidev_folder = '/xilidev-libraries'; //must be in plugins

	var $theme_mos = array(); // $this->get_pomo_from_theme();
	var $plugin_mos = array(); // 2.6.1 - mo from plugin
	var $local_mos = array() ; // $this->get_pomo_from_theme( true ); // 2.1
	//	is_multisite
	var $file_site_mos = array() ; // $this->get_pomo_from_site(); // since 1.2.0 - mo of site
	var $file_site_local_mos = array() ; // $this->get_pomo_from_site( true );

	var $default_langs_array = array(); // default languages
	var $internal_list = false; // created by xl or xd

	var $importing_mode = false ; // for new action by hand ( action save when new )
	var $msg_str_labels = array (
		'msgid' => 'msgid', 'msgid_plural' => 'msgid_plural',
		'msgstr' => 'msgstr', 'msgstr_0' => 'msgstr[0]',
		'msgstr_1' => 'msgstr[1]', 'msgstr_2' => 'msgstr[2]',
		'msgstr_3' => 'msgstr[3]', 'msgstr_4' => 'msgstr[4]'
		) ;
	var $importing_po_comments = '' ;	// mode replace or append 2.0-rc2
	var $create_line_lang = ''; // line between box

	var $langs_group_id; /* group ID and Term Taxo ID */
	var $langs_group_tt_id;

	// temp mo/po object
	var $temp_po;

	var $taxlist = array(); // list of current tax in edit-tags table
	var $tax_msgid_list = array();	// list of current tax visible in dictionary list

	var $active_theme_directory = ''; // current template (or stylesheet) directory

	var $wikilink = 'http://wiki.xiligroup.org';

	var $msg_settings = ''; // used by download process 2.5
	var $download_uri = ''; // URI used by download process 2.5
	var $plugin_domain_path = "";
	var $plugin_text_domain = "";

	var $news_id = 0; //for multi pointers
	var $news_case = array();

	var $import_message = ''; // core import process

	var $examples_list = array(); // try to use GP class (jetpack)
	var $multilanguage_plugin_active = ''; //2.12
	var $ISO_to_term_id = array();

	public function __construct( $langsfolder = '/' ) {

		global $wp_version;
		/* activated when first activation of plug */
		// 2.0
		define ( 'XDMSG', $this->xdmsg ); // CPT to change from msg to xdmsg (less generic) 20120217

		$this->plugin_path = plugin_dir_path(__FILE__) ;
		$this->plugin_url = plugins_url('', __FILE__) ;

		register_activation_hook( __FILE__, array( &$this,'xili_dictionary_activation') );
		register_deactivation_hook( __FILE__, array(&$this, 'remove_capabilities') ); //2.3.7

		$this->ossep = strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/'; /* for rare xamp servers*/

		/* get current settings - name of taxonomy - name of query-tag */
		$this->xililanguage_state();
		$this->xili_settings = get_option('xili_dictionary_settings');
		if(empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$this->xili_dictionary_activation();
			$this->xili_settings = get_option('xili_dictionary_settings');
		}

		/* test if version changed */
		$version = $this->xili_settings['version'];
		if ( $version <= '0.2' ) {
				/* update relationships for grouping existing dictionary terms */
			$this->update_terms_langs_grouping();
			$this->xili_settings['version'] = '1.0';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		if ( $version == '1.0' ) {
			$this->xili_settings['external_xd_style'] = "off";
			$this->xili_settings['version'] = '2.0';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		if ( $version == '2.0' ) {
			$this->update_postmeta_msgid();
			$this->xili_settings['version'] = '2.1';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		if ( $version == '2.1' ) {
			$this->xili_settings['editor_caps'] = "no_caps"; // saved value of capabilities of editor role 2.3.2
			$this->xili_settings['version'] = '2.2';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		if ( $version == '2.2' ) {
			if ( !isset( $this->xili_settings['meta_keys'] ) || ( isset( $this->xili_settings['meta_keys'] ) && $this->xili_settings['meta_keys'] != "updated" ) ) {
				$this->xili_settings['meta_keys'] = $this->updated_sort_meta_keys () ; // 2.3.3
			}
			$this->xili_settings['version'] = '2.3';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		if ( $version == '2.3' ) {
			$this->xili_settings['parent_langs_folder'] = "";
			$this->xili_settings['version'] = '2.4';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}

		$this->fill_default_languages_list();
		/* Actions */

		/* admin */
		add_action( 'admin_init', array(&$this, 'set_roles_capabilities') ); // 2.3.2
		add_action( 'admin_init', array(&$this, 'admin_init') ); // 1.3.0
		add_action( 'admin_init', array(&$this, 'ext_style_init') ); // 2.1
		add_action( 'admin_init', array(&$this, 'xd_erasing_init_settings') ); // 2.3
		add_action( 'admin_init', array(&$this, 'xd_importing_init_settings') ); // 2.3
		add_action( 'admin_init', array(&$this, 'xd_download_init_settings') ); // 2.5
		add_action( 'admin_init', array(&$this, 'download_file_if') ); // 2.5

		// Attach to the admin head with our ajax requests cycle and css
		add_action( 'admin_head', array(&$this, 'admin_head' ) );
		add_action( 'admin_head', array(&$this, 'check_post_type_and_remove_media_buttons') );

		// Attach to the admin ajax request to process cycles
		add_action( 'wp_ajax_xd_erasing_process', array(&$this, 'erasing_process_callback' ) ); // 2.3
		add_action( 'wp_ajax_xd_importing_process', array(&$this, 'importing_process_callback' ) ); // 2.3
		add_action( 'wp_ajax_xd_live_state_file', array(&$this, 'xd_live_state_file' ) ); // 2.9
		add_action( 'wp_ajax_xd_from_file_exists', array(&$this, 'xd_from_file_exists' ) );

		add_action( 'add_meta_boxes', array(&$this, 'add_custom_box_in_post_msg') ); // 2.1.2

		add_action( 'init', array(&$this, 'post_type_msg'), 9 );
		add_action( 'init', array(&$this, 'xili_dictionary_register_taxonomies') ); // init + menu

		add_filter( 'plugin_locale', array(&$this,'get_plugin_domain_array'), 10, 2 );

		if ( is_admin() ) {
			add_filter( 'manage_posts_columns', array(&$this,'xili_manage_column_name') ,9 , 1);
			add_filter( "manage_pages_custom_column", array(&$this,'xili_manage_column_row'), 9, 2); // hierarchic
			add_filter( 'manage_edit-'.XDMSG.'_sortable_columns', array(&$this,'msgcontent_column_register_sortable') );
			add_filter( 'request', array(&$this,'msgcontent_column_orderby' ) );

			if ( !class_exists ('xili_language' ) )
				add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_languages_posts') );


			if ( !class_exists ('xili_language_ms' ) ) {
				add_action('category_add_form', array(&$this,'add_content_in_taxonomy_edit_form'));
				add_filter( 'manage_category_custom_column', array(&$this,'xili_manage_tax_column'), 10, 3); // 2.3.3
				add_action( 'after-category-table', array(&$this,'add_import_in_XD_button') );
				add_action('parse_query', array(&$this,'show_imported_msgs_in_xdmg_list'));
				add_filter('query_vars', array(&$this,'keywords_addQueryVar'));
			}

			add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_writer_posts'), 11 );
			add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_origin_posts'), 10 );
			add_action( 'pre_get_posts', array(&$this,'wpse6066_pre_get_posts' ) );

			add_action( 'category_edit_form_fields', array(&$this, 'show_translation_msgstr'), 10, 2 );

			add_action( 'wp_print_scripts', array(&$this,'auto_save_unsetting' ), 2 ); // before other

			add_filter( 'user_can_richedit', array(&$this, 'disable_richedit_for_cpt') );

			if ( defined ('WP_DEBUG') && WP_DEBUG != true ) {
				add_filter( 'page_row_actions', array(&$this, 'remove_quick_edit'), 10, 1); // before to solve metas column
			}
			add_action( 'save_post', array(&$this, 'custom_post_type_title'), 11 ,2); //
			add_action( 'save_post', array(&$this, 'msgid_post_new_create'), 12 ,2 );
			add_action( 'save_post', array(&$this, 'update_msg_comments'), 13, 2 ); // comments and contexts
			add_filter( 'post_updated_messages', array(&$this, 'msg_post_messages'));

			add_action( 'before_delete_post', array(&$this, 'msgid_post_links_delete') );

			add_action( 'admin_print_styles-post.php', array(&$this, 'print_styles_xdmsg_edit') );
			add_action( 'admin_print_styles-post-new.php', array(&$this, 'print_styles_xdmsg_edit') );

			add_action( 'admin_print_styles-post.php', array(&$this,'admin_enqueue_styles') );
			add_action( 'admin_print_scripts-post.php', array(&$this,'admin_enqueue_scripts') );

			add_action( 'admin_print_styles-edit.php', array(&$this, 'print_styles_xdmsg_list') ); // list of msgs
			add_action( 'admin_print_styles-edit-tags.php', array(&$this, 'print_styles_edit_tags') ); //

			add_action( 'admin_print_styles-xdmsg_page_dictionary_page', array(&$this, 'print_styles_xdmsg_tool') );
			add_action( 'admin_print_styles-xdmsg_page_erase_dictionary_page', array(&$this, 'print_styles_new_ui') );
			add_action( 'admin_print_styles-xdmsg_page_import_dictionary_page', array(&$this, 'print_styles_new_ui') );


			add_action( 'add_meta_boxes_' . XDMSG, array(&$this, 'msg_update_action')); // to locally update files from editing...
		}

		add_filter( 'plugin_action_links', array(&$this,'xilidict_filter_plugin_actions'), 10, 2);

		/* special to detect theme changing since 1.1.9 */
		add_action( 'switch_theme', array(&$this,'xd_theme_switched') );

		// Test about import frontend terms of plugin
		if ( !is_admin() && get_option ( 'xd_test_importation' , false ) )
			add_filter( 'gettext', array(&$this,'detect_plugin_frontent_msg'), 5, 3); // front-end limited

		if ( !is_admin() && get_option ( 'xd_test_importation' , false ) )
			add_action( 'wp', array(&$this,'start_detect_plugin_msg'), 100 );
		if ( !is_admin() && get_option ( 'xd_test_importation' , false ) )
			add_action( 'shutdown', array(&$this,'end_detect_plugin_msg'));

		add_action('export_filters', array(&$this,'message_export_limited' ) ); // 2.7

		add_action( 'contextual_help', array(&$this,'add_help_text'), 10, 3 ); /* 1.2.2 */

		if ( class_exists('xili_language_ms') ) $this->xililanguage_ms = true; // 1.3.4

		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( $langfolderset !='' ) ? $langfolderset.'/' : '/';
		// doublon
		$this->langfolder = str_replace ("//","/", $this->langfolder ); // upgrading... 2.0 and sub folder sub

		$langfolderset = $this->xili_settings['parent_langs_folder']; // 2.3.7
		$this->parentlangfolder = ( $langfolderset !='' ) ? $langfolderset.'/' : '/';
		// doublon
		$this->parentlangfolder = str_replace ("//","/", $this->parentlangfolder );

	} // end construct



	public function xili_dictionary( $langsfolder = '/' ) { // ?? php4
		$this->__construct( $langsfolder );
	}

	/**
	 * used when updating 2.3.3
	 */
	function updated_sort_meta_keys () {
		// select all msgs
		global $wpdb ;
		$all_posts = $wpdb->get_results($wpdb->prepare("SELECT ID, post_content FROM $wpdb->posts WHERE post_type = '%s' ", XDMSG));

		if ( $all_posts ) {
			if ( is_wp_error($all_posts) ) { return 'error'; }

			foreach ( $all_posts as $all_post ) {
				update_post_meta ( $all_post->ID, $this->msg_sort_slug, sanitize_title ( $all_post->post_content ) );
			}
		}
		return 'updated';	// empty or ...
	}


	function set_roles_capabilities () {
		global $wp_roles;

		$wp_roles->remove_cap ('editor', 'xili_dictionary_admin'); // reset
		$wp_roles->remove_cap ('editor', 'xili_dictionary_edit');
		$wp_roles->remove_cap ('editor', 'xili_dictionary_edit_save');

		if ( current_user_can ('activate_plugins') ) {

			$wp_roles->add_cap ('administrator', 'xili_dictionary_admin');
			$wp_roles->add_cap ('administrator', 'xili_dictionary_edit');
			$wp_roles->add_cap ('administrator', 'xili_dictionary_edit_save');

		} elseif ( current_user_can ( 'edit_others_pages' ) ) {
			if ( $this->xili_settings['editor_caps'] == 'cap_edit' ) {
				$wp_roles->add_cap ('editor', 'xili_dictionary_edit');
			} elseif ( $this->xili_settings['editor_caps'] == 'cap_edit_save' ) {
				$wp_roles->add_cap ('editor', 'xili_dictionary_edit');
				$wp_roles->add_cap ('editor', 'xili_dictionary_edit_save');
			}
		}
	}

	// when desactivating - 2.3.7
	function remove_capabilities () {
		global $wp_roles;

		$wp_roles->remove_cap ( 'administrator', 'xili_dictionary_admin' );
		$wp_roles->remove_cap ( 'administrator', 'xili_dictionary_edit' );
		$wp_roles->remove_cap ( 'administrator', 'xili_dictionary_edit_save' );

		$wp_roles->remove_cap ('editor', 'xili_dictionary_admin'); // reset
		$wp_roles->remove_cap ('editor', 'xili_dictionary_edit');
		$wp_roles->remove_cap ('editor', 'xili_dictionary_edit_save');

	}
	/* wp 4.0 -2.9.1 */
	function get_WPLANG () {
		global $wp_version;
		if ( version_compare($wp_version, '4.0', '<') ) {
			if ( defined('WPLANG') )
				return WPLANG;
			else
				return '';
		} else {
			return get_option( 'WPLANG', '' );
		}
	}

	/* wp 3.0 WP-net */
	function xili_dictionary_register_taxonomies () {

		if ( is_child_theme() ) { // move here from init 1.4.1
			if ( isset( $this->xili_settings['langs_in_root_theme'] ) && $this->xili_settings['langs_in_root_theme'] == 'root' ) { // for future uses
				$this->active_theme_directory = get_template_directory();
			} else {
				$this->active_theme_directory = get_stylesheet_directory();
			}
		} else {
			$this->active_theme_directory = get_template_directory();
		}

		$this->init_textdomain(); // plugin

		// new method for languages 2.0
		$this->internal_list = $this->default_language_taxonomy ();

		if ( $this->internal_list ) { // test if empty
			$listlanguages = get_terms( TAXONAME, array('hide_empty' => false));
			if ( $listlanguages == array() ) {
				$this->create_default_languages();
			}
			$this->other_multilingual_compat( $listlanguages );
		}

		//,'slug' => 'the-langs-group'
		$thegroup = get_terms( TAXOLANGSGROUP, array('hide_empty' => false));
		if ( !is_wp_error($thegroup) && $thegroup != array() ) { // notice on first start
			$this->langs_group_id = $thegroup[0]->term_id;
			$this->langs_group_tt_id = $thegroup[0]->term_taxonomy_id;
		}

xili_xd_error_log ( '# '. __LINE__ . ' admin_menu_add ------------');
		add_action( 'admin_menu', array(&$this,'dictionary_menus_pages') );
		add_action( 'admin_menu', array(&$this,'admin_sub_menus_hide') );
	}

	/**
	 * to be compatible with multilingual other plugins (first = Polylang)
	 *
	 */
	function other_multilingual_compat ( $languages ) {
		if ( class_exists('Polylang') ) { // 2.12
			$this->multilanguage_plugin_active = 'Polylang';
			// attach languages to group
			foreach ( $languages as $language ) {
				wp_set_object_terms( $language->term_id, 'the-langs-group', TAXOLANGSGROUP);
				$desc_array = unserialize( $language->description );
				$this->ISO_to_term_id[ $desc_array['locale'] ] = $language->term_id;
			}
			add_filter ( 'other_multilingual_plugin_filter_terms', array( &$this, 'Polylang_language_terms_compat') );
			add_filter ( 'other_multilingual_plugin_filter_term', array( &$this, 'Polylang_language_one_term_compat') );
		}
	}
	// series
	function Polylang_language_terms_compat ( $languages ) {
		$adapted_languages = array();
		if ( $languages ) {
			foreach ( $languages as $language ) {
				// array
				$adapted_languages[] = $this->Polylang_language_one_term_compat ( $language ) ;
			}
			return $adapted_languages;
		}
		return $languages;
	}
	// one term
	function Polylang_language_one_term_compat ( $language ) {
		$adapted_language = array();
		$adapted_language = (array) $language ; // all values but with array to avoid error
		$desc_array = unserialize( $language->description );
		$adapted_language['name'] = $desc_array['locale']; // ISO
		$adapted_language['description'] = $language->name; // full name
		return (object) $adapted_language;
	}


	// to be cleanly attached w/o creating terms (iso is passed)
	function target_lang( $target_lang ) {
		if (is_int ( $target_lang )) return $target_lang ;
		if ( $this->multilanguage_plugin_active == 'Polylang' ) {
			return (int) $this->ISO_to_term_id [ $target_lang ] ;
		} else {
			return $target_lang ;
		}
	}


	function xili_dictionary_activation() {
		$this->xili_settings = get_option('xili_dictionary_settings');
		if ( empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$submitted_settings = array(
				'taxonomy'		=> 'dictionary',
				'langs_folder' => '',
				'external_xd_style' => 'off',
				'editor_caps' => "no_caps", // 2.3.2
				'parent_langs_folder' => '', // 2.3.7
				'version'	=> '2.4'
			);
			update_option('xili_dictionary_settings', $submitted_settings);
		}
	xili_xd_error_log ( '# '. __LINE__ . ' ----------- xili_dictionary_activation ------------');
	}

	function post_type_msg() {
		load_plugin_textdomain('xili-dictionary', false, 'xili-dictionary/languages' ); // 2.9
		$labels = array(
			'name' => _x('xili-dictionary©', 'post type general name', 'xili-dictionary'),
			'singular_name' => _x('Msg', 'post type singular name', 'xili-dictionary'),
			'add_new' => __('New Msgid', 'xili-dictionary'),
			'add_new_item' => __('Add New Msgid', 'xili-dictionary'),
			'edit_item' => __('Edit Msg', 'xili-dictionary'),
			'new_item' => __('New Msg', 'xili-dictionary'),
			'view_item' => __('View Msg', 'xili-dictionary'),
			'search_items' => __('Search Msg', 'xili-dictionary'),
			'not_found' => __('No Msg found', 'xili-dictionary'),
			'not_found_in_trash' => __('No Msg found in Trash', 'xili-dictionary'),
			'parent_item_colon' => ''
		);

		// impossible to see in front-end (no display in edit list)
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'_edit_link' => 'post.php?post=%d',
			'_builtin' => false,
			'show_ui' => true,
			'query_var' => XDMSG, // add 2.3.3
			'rewrite' => false,
			'capability_type' => 'post',
			'show_in_menu' => current_user_can ('xili_dictionary_edit'), // ?? if not admin
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array( 'author', 'editor', 'excerpt' ), // ,'page-attributes', 'custom-fields' => (parent for plural), not ready
			'taxonomies' => array ('origin', 'writer' ), // 'appearance',
			'rewrite' => array( 'slug' => XDMSG, 'with_front' => FALSE, ),
			'menu_icon' => plugins_url( 'images/XD-logo-16.png', __FILE__ ) // 16px16
		);
		register_post_type( XDMSG, $args );

		register_taxonomy( 'origin', array(XDMSG),
			array('hierarchical' => false,
				'label' => __('Origins','xili-dictionary'),
				'query_var' => 'origin',
				'public' => false, // fixed for menus 2.3.6
				'show_ui' => true,
				//'show_in_nav_menus' => false, // not necessary CPT XDMSG not in Nav Menu WP 4.3
				'rewrite' => array('slug' => 'origin' ),
				'labels' => array (
					'name' => __('Origins','xili-dictionary'),
					'singular_name' => __('Origin','xili-dictionary'),
					'search_items' => __('Search Origins', 'xili-dictionary'),
					'popular_items' => __('Popular Origins', 'xili-dictionary'),
					'all_items' => __('All Origins', 'xili-dictionary'),
					'parent_item' => __('Parent Origin', 'xili-dictionary'),
					'parent_item_colon' => __('Parent Origin:', 'xili-dictionary'),
					'edit_item' => __('Edit Origin', 'xili-dictionary'),
					'update_item' => __('Update Origin', 'xili-dictionary'),
					'add_new_item' => __('Add New Origin', 'xili-dictionary'),
					'new_item_name' => __('New Origin Name ', 'xili-dictionary'),
					'separate_items_with_commas' => __('Separate origins with commas', 'xili-dictionary'),
					'add_or_remove_items' => __('Add or remove origins', 'xili-dictionary'),
					'choose_from_most_used' => __('Choose from the most used origins', 'xili-dictionary'),
					'not_found' => __('No origin found', 'xili-dictionary'),
				)
			)
		);

		register_taxonomy( 'writer', array(XDMSG),
			array( 'hierarchical' => true,
					'label' => __('Writers', 'xili-dictionary'),
					'rewrite' => true,
					'query_var' => 'writer_name',
					'public' => false,
					'show_ui' => true,
					'labels' => array (
						'name' => __('Writers','xili-dictionary'),
						'singular_name' => __('Writer','xili-dictionary'),
						'search_items' => __('Search Writers', 'xili-dictionary'),
						'popular_items' => __('Popular Writers', 'xili-dictionary'),
						'all_items' => __('All Writers', 'xili-dictionary'),
						'parent_item' => __('Parent Writer', 'xili-dictionary'),
						'parent_item_colon' => __('Parent Writer:', 'xili-dictionary'),
						'edit_item' => __('Edit Writer', 'xili-dictionary'),
						'update_item' => __('Update Writer', 'xili-dictionary'),
						'add_new_item' => __('Add New Writer', 'xili-dictionary'),
						'new_item_name' => __('New Writer Name ', 'xili-dictionary'),
						'separate_items_with_commas' => __('Separate writers with commas', 'xili-dictionary'),
						'add_or_remove_items' => __('Add or remove writers', 'xili-dictionary'),
						'choose_from_most_used' => __('Choose from the most used writers', 'xili-dictionary'),
						'not_found' => __('No writer found', 'xili-dictionary'),
					)
			)
		);
	}

	// 2.7.2
	function check_post_type_and_remove_media_buttons() {
		$screen = get_current_screen();
		if ( $screen->id == XDMSG ) {
			remove_action( 'media_buttons', 'media_buttons' ) ;
			add_action( 'edit_form_top', array(&$this, 'msg_subtitle') );
		}
	}

	/**
	 * subtitle inserted to describe msg type
	 *
	 * @since 2.7.2
	 */
	function msg_subtitle ( $post ) {
		$singular_name = __('series','xili-dictionary');
		$type = get_post_meta ( $post->ID, $this->msgtype_meta, true);
		if ( false === strpos( $type, 'str') ) { // msgid
			echo '<h3>' . sprintf ( _x('%s', 'form', 'xili-dictionary'), $type ) . '</h3>' ;
			echo '<p>' . __('Reference', 'xili-dictionary') . '<br />' ;
			echo '<em>' . __('This text (msgid) is the Reference and must modified <strong>ONLY IF</strong> you are sure that it will be the same as in source code or text.', 'xili-dictionary') . '</em><br />' ;
			echo '<em>' . sprintf(__('To create or edit translations (msgstr), see %s in the table of box %s.', 'xili-dictionary'),
				'<a style="background:yellow;" href="#msg_state" >' . __('links', 'xili-dictionary') . '</a>',
			 '</em>' . sprintf(__("msg %s",'xili-dictionary'), $singular_name)  )  . '</p>' ;
		} else { // msgstr
			if ( false === strpos( $type, '_') ) {
				echo '<h3>' . sprintf ( _x('%s', 'form', 'xili-dictionary'), $type ) . '</h3>' ;
				$plural = '';
			} else { // plural translation
				$indices = explode ('_', $type);
				$indice = $indices[1];
				echo '<h3>' . sprintf ( _x('msgstr[%d]', 'form', 'xili-dictionary'), $indice ) . '</h3>' ;
				if ( false !== strpos( $type, '_0') ) {
					$plural = '';
				} else {
					$plural = __('&nbsp;(plural)', 'xili-dictionary') ;
				}
			}
			echo '<p><em>' . sprintf(__('Translation%s', 'xili-dictionary'), $plural ). '</em></p>' ;
		}
	}

	/**
	 * register language taxonomy if no xili_language - 'update_count_callback' => array(&$this, '_update_post_lang_count'),
	 *
	 *
	 */
	function default_language_taxonomy () {
		if ( ! defined ( 'TAXONAME' ) ) {
			if ( ! defined ( 'QUETAG' ) ) define ('QUETAG', 'lang');

			define ('TAXONAME', 'language');
			register_taxonomy( TAXONAME, 'post', array(
				'hierarchical' => false,
				'label' => false,
				'rewrite' => false,
				'show_ui' => false,
				'_builtin' => false,
				'show_in_nav_menus' => false,
				'query_var' => QUETAG
				) );

			define('TAXOLANGSGROUP', 'languages_group');
			register_taxonomy( TAXOLANGSGROUP, 'term', array(
				'hierarchical' => false,
				'update_count_callback' => '',
				'show_ui' => false,
				'label'=>false,
				'rewrite' => false,
				'show_in_nav_menus' => false, //
				'_builtin' => false
				) );
			$thegroup = get_terms( TAXOLANGSGROUP, array('hide_empty' => false,'slug' => 'the-langs-group') );
			if ( array() == $thegroup ) {
				$args = array( 'alias_of' => '', 'description' => 'the group of languages', 'parent' => 0, 'slug' =>'the-langs-group');
				wp_insert_term( 'the-langs-group', TAXOLANGSGROUP, $args); /* create and link to existing langs */
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * add styles in edit msg screen
	 *
	 */
	 function print_styles_xdmsg_edit ( ) {
	 	global $post;
	 	if ( get_post_type( $post->ID ) == XDMSG ) {
			echo '<!---- xd css ----->'."\n";
			echo '<style type="text/css" media="screen">'."\n";

			echo '#msg-states { width:69%; float:left; border:0px solid red; padding-bottom: 10px;}'."\n";
			echo '#msg-states-comments { width:27%; margin-left: 70%; border-left:0px #666 solid; padding:10px 10px 0; }'."\n";
			echo '#msg-states-actions { background:#ffffff; clear:left; padding: 8px 5px; margin-top:5px; }'."\n";
			echo '.xdversion { font-size:80%; text-align:right; }'."\n";
			echo '.msg-states-actions-left { float:left; padding: 8px 0px; overflow:hidden; width:50% }'."\n";
			echo '.msg-states-actions-right { float:left; padding: 8px 0px; width:50% }'."\n";
			echo '.alert { color:red;}'."\n";
			echo '.editing { color:blue; background:yellow;}'."\n";
			echo '.msgidstyle { line-height:200% !important; font-size:105%; padding:4px 10px 6px;}'."\n";
			echo '.msgstrstyle { line-height:180% !important; font-size:105%; }'."\n";
			echo '.msg-saved { background:#ffffff !important; border:1px dotted #999; padding:5px; margin-bottom:5px;}'."\n";
			echo '.column-msgtrans {width: 20%;}'."\n";
			echo 'mark { background-color:#e0e0e0;}'."\n";
			// buttons
			echo '.action-button {text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;}'."\n";
			echo '.blue-button {border:1px #33f solid;}'."\n";
			echo '.grey-button {border:1px #ccc solid;}'."\n";

			echo '</style>'."\n";

			$this->insert_flags_style_css();
		}
	}

	/**
	 * add style for flags (coming from xili-language if active)
	 * @since 2.10.3
	 *
	 */
	function insert_flags_style_css() {
		global $xili_language, $xili_language_admin;
		if ( $this->xililanguage == 'isactive' && $this->xili_settings['external_xd_style'] == "on" ) {
			echo '<!---- xd css flags ----->'."\n";
			echo '<style type="text/css" media="screen">'."\n";
			echo 'div#msg-states tr[class|="lang"] th>span {display:inline-block; }'."\n";
			echo 'div#msg-states-comments a[class|="lang"] {display:inline-block; text-indent:-9999px; width:20px; }'."\n";
			$listlanguages = $xili_language->get_listlanguages();
			$folder_url = $xili_language->plugin_url . '/xili-css/flags/' ;
			foreach ($listlanguages as $language) {
				$ok = false;
				$flag_id = $xili_language->get_flag_series ( $language->slug, 'admin' );
				if ( $flag_id != 0 ) {
				    $flag_uri = wp_get_attachment_url( $flag_id ) ;
					$ok = true;
				} else {
					// test the default in xili-language here
					$ok = file_exists( $xili_language_admin->style_flag_folder_path . $language->slug .'.png' );
					$flag_uri = $folder_url . $language->slug .'.png';
					if (!$ok) { // test the default in plugin here
						$ok = file_exists( $this->plugin_path . '/xili-css/flags/' . $language->slug .'.png' );
						$flag_uri = $this->plugin_url . '/xili-css/flags/' . $language->slug .'.png';
					}
				}
				if ( $ok ) {
					echo 'tr.lang-'.$language->slug.' th, div#msg-states-comments a.lang-'.$language->slug.' { background: transparent url('.$flag_uri.') no-repeat 60% center; }'."\n";
				}
			}
			echo '</style>'."\n";
		} else {
			if ( $this->exists_style_ext && $this->xili_settings['external_xd_style'] == "on" ) wp_enqueue_style( 'xili_dictionary_stylesheet' );
		}

	}

	/**
	 * add styles in edit tags screen
	 *
	 */
	function print_styles_edit_tags ( ) {

		echo '<!---- xd css tags ----->'."\n";
		echo '<style type="text/css" media="screen">'."\n";
		echo '.displaybbt { margin: 10px 0 0 200px !important }'."\n";
		echo '.displaybbt a:link { text-decoration:none; }'."\n";
		echo '.taxinmos { border:1px solid #999; width:500px; padding: 10px 20px }'."\n";
		echo '.taxinmoslist { border-bottom:1px solid #eee; padding: 5px 0; margin-bottom: 5px; }'."\n"; // in xl
		echo '</style>'."\n";
		$this->insert_flags_style_css();
	}

	/**
	 * add styles in list of msgs screen icon32-posts-xdmsg
	 *
	 */
	function print_styles_xdmsg_list ( ) {

		if ( isset( $_GET['post_type']) && $_GET['post_type'] == XDMSG ) {

			echo '<!---- xd css ----->'."\n";
			echo '<style type="text/css" media="screen">'."\n";

			echo 'mark { background-color:#e0e0e0;}'."\n";
			echo '.alert { color:red;}'."\n";
			echo '.column-language, .column-msgposttype { width: 80px; }'."\n";
			echo '.column-date { width: 80px !important; }'."\n";
			echo '.column-msgcontent { width: auto; }'."\n";
			echo '.column-msgpostmeta { width: 150px; }'."\n";
			echo '.column-msgposttype { width: 150px; }'."\n";
			echo '.column-author { width: 80px !important; }'."\n";
			echo '.column-title { width: 160px !important; }'."\n";
			echo '.column-date { width: 100px !important; }'."\n";

			echo '#icon-edit.icon32-posts-xdmsg { background:transparent url('.plugins_url( 'images/XD-full-logo-32.png', __FILE__ ) . ') no-repeat !important ; }'."\n";
			echo '</style>'."\n";
			$this->insert_flags_style_css();
		}
	}

	/**
	 * add styles in tool screen
	 *
	 */
	function print_styles_xdmsg_tool ( ) {
		echo '<!---- xd css ----->'."\n";
		echo '<style type="text/css" media="screen">'."\n";

		echo '.metabox-content { background:transparent;}'."\n";
		echo '.dialoglang { float:left; width:18%; border:0px solid grey; margin: 5px; }'."\n";
		echo '.dialogfile { float:left; width:42%; min-height:80px; border-left:1px solid #ddd; padding: 10px 5px 10px 5px; } '."\n";
		echo '.dialogorigin { float:left; width:32%; border-left:1px solid #ddd; padding: 10px 5px 10px 10px; min-height:180px} '."\n";
		echo '#xd-file-state p {font-size:10px; margin-left:20%;}'."\n";
		echo '#xd-file-exists {font-size:11px; margin-left: 20%; padding-top:5px;}'."\n";
		echo '.dialogbbt {clear:left; text-align:right; padding:12px 2px 5px; }'."\n";
		echo 'table.checktheme { width:95%; margin-left:10px;}'."\n";
		echo 'table.checktheme>tr>td { width:45% }'."\n";

		echo 'mark { background-color:#e0e0e0;}'."\n";
		echo '.dialogcontainer { margin-top:14px !important; padding:12px 10px; overflow:hidden; background:#f0f0f0;}'."\n";

		// buttons
		echo '.action-button {text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;}'."\n";
		echo '.small-action-button {text-decoration:none; text-align:center; display:inline-block; width:16%; margin:0px 1px 1px 10px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; border:1px #ccc solid;}'."\n";
		echo '.blue-button {border:1px #33f solid;}'."\n";
		echo '.grey-button {border:1px #ccc solid;}'."\n";

		echo '</style>'."\n";
		// $this->insert_flags_style_css();
	}

	/**
	 * add styles in new screens (import / erase)
	 *
	 */
	function print_styles_new_ui ( ) {

		echo '<!---- xd css ----->'."\n";
		echo '<style type="text/css" media="screen">'."\n";
		echo 'form#xd-looping-settings > p { width:600px; padding:10px 20px 10px 0; font-size:110%; }'."\n";
		echo '.sub-field {border:1px #ccc solid; width:600px; padding:10px 20px ; margin:5px 0;}'."\n";
		echo '.link-back > a { border:1px #ccc solid; width:80px; padding:4px 10px ; border-radius: 3px; margin-right:4px;}'."\n";
		echo '.xd-looping-updated{ width:870px !important; }'."\n";
		echo '#xd-file-state p {font-size:10px;}'."\n";
		echo '</style>'."\n";

	}

	/**
	 * style for new dashboard
	 * @since 2.1
	 *
	 */
	function ext_style_init () {
				// test successively style file in theme, plugins, current plugin subfolder
		if ( file_exists ( get_stylesheet_directory().'/xili-css/xd-style.css' ) ) { // in child theme
				$this->exists_style_ext = true;
				$this->style_folder = get_stylesheet_directory_uri();
				$this->style_flag_folder_path = get_stylesheet_directory () . '/images/flags/';
				$this->style_message = __( 'xd-style.css is in sub-folder xili-css of current theme folder', 'xili-dictionary' );
		} elseif ( file_exists( WP_PLUGIN_DIR . $this->xilidev_folder . '/xili-css/xd-style.css' ) ) { // in plugin xilidev-libraries
				$this->exists_style_ext = true;
				$this->style_folder = plugins_url() . $this->xilidev_folder;
				$this->style_flag_folder_path = WP_PLUGIN_DIR . $this->xilidev_folder . '/xili-css/flags/' ;
				$this->style_message = sprintf( __( 'xd-style.css is in sub-folder xili-css of %s folder', 'xili-dictionary' ), $this->style_folder );
		} elseif ( file_exists ( $this->plugin_path.'/xili-css/xd-style.css' ) ) { // in current plugin
				$this->exists_style_ext = true;
				$this->style_folder = $this->plugin_url ;
				$this->style_flag_folder_path = $this->plugin_path . '/xili-css/flags/' ;
				$this->style_message = __( 'xd-style.css is in sub-folder xili-css of xili-dictionary plugin folder (example)', 'xili-dictionary' );
		} else {
				$this->style_message = __( 'no xd-style.css', 'xili-dictionary' );
		}
		if ( $this->exists_style_ext ) wp_register_style( 'xili_dictionary_stylesheet', $this->style_folder . '/xili-css/xd-style.css' );
	}

	/**
	 * create default languages if no xili_language
	 *
	 * @since
	 */
	function create_default_languages () {

		$this->default_langs_array = array(
			'en_us' => array('en_US', 'english'),
			'fr_fr' => array('fr_FR', 'french'),
			'de_de' => array('de_DE', 'german'),
			'es_es' => array('es_ES', 'spanish'),
			'it_it' => array('it_IT', 'italian'),
			'pt_pt' => array('pt_PT', 'portuguese'),
			'ru_ru' => array('ru_RU', 'russian'),
			'zh_cn' => array('zh_CN', 'chinese'),
			'ja' => array('ja', 'japanese'),
			'ar_ar' => array('ar_AR', 'arabic')
		);

		$term = 'en_US';
		$args = array( 'alias_of' => '', 'description' => 'english', 'parent' => 0, 'slug' =>'en_us');
		$theids = $this->safe_lang_term_creation ( $term, $args );
		if ( !is_wp_error($theids) )
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);

		/* default value detected in config */
		if ( ''!= $this->get_WPLANG() && ( strlen( $this->get_WPLANG() )==5 || strlen( $this->get_WPLANG() ) == 2 ) ) : // for japanese
			$this->default_lang = $this->get_WPLANG();
		else:
			$this->default_lang = 'en_US';
		endif;

		$term = $this->default_lang;
		$desc = $this->default_lang;
		$slug = strtolower( $this->default_lang ) ; // 2.3.1
		if ( $this->default_lang == 'en_US' || $this->default_lang == '' ) {
			$term = 'fr_FR'; $desc = 'French'; $slug = 'fr_fr' ;
		}
		$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => 0, 'slug' => $slug);

		$theids = $this->safe_lang_term_creation ( $term, $args ) ;
		if ( !is_wp_error($theids) )
			wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);

	}

	/**
	 * Safe language term creation (if XL inactive)
	 *
	 * @since 2.0 (from XL 2.4.1)
	 */
	function safe_lang_term_creation ( $term, $args ) {
		global $wpdb ;
		// test if exists with other slug or name
		if ( $term_id = term_exists( $term ) ) {
			$existing_term = $wpdb->get_row( $wpdb->prepare( "SELECT name, slug FROM $wpdb->terms WHERE term_id = %d", $term_id), ARRAY_A );
			if ( $existing_term['slug'] != $args['slug'] ) {
				$res = wp_insert_term( $term.'xl', TAXONAME, $args); // temp insert with temp other name
				$args['name'] = $term ;
				$res = wp_update_term( $res['term_id'], TAXONAME, $args);
			} else {
				return new WP_Error('term_exists', __('A term with the name provided already exists.'), $term_id );
			}
		} else {
			$res = wp_insert_term( $term, TAXONAME, $args);
		}
		/*
		if ( is_wp_error( $res ) ) {
			return $res ;
		} else {
			$theids = $res;
		}*/
		return $res ;
	}

	/**
	 * call from filter disable_richedit
	 *
	 * disable rich editor in msg cpt
	 *
	 * @since 2.0
	 *
	 */
	function disable_richedit_for_cpt ( $default ) {
		global $post;
		if ( XDMSG == get_post_type( $post ) )
			return false;
		return $default;
	}
	function remove_quick_edit( $actions ) {
		if ( isset ( $_GET['post_type'] ) && $_GET['post_type'] == XDMSG )
			unset($actions['inline hide-if-no-js']);

		return $actions;
	}

	/**
	 * call from filter save_post
	 *
	 * save content in title - fixes empty msgid
	 *
	 * @since 2.0
	 *
	 */
	function custom_post_type_title ( $post_id, $post ) {
		global $wpdb;
		if ( get_post_type( $post_id ) == XDMSG ) {
			$where = array( 'ID' => $post_id );
			$what = array ();

			if ( false === strpos( $post->post_title, 'MSG:' ) ) {
				$format_id = substr(10000000 + (int)$post_id, - 7) ; // since 2.7.2
				$title = 'MSG:'.$format_id;
				$what['post_title'] = $title ;
			}

			if ( $post->post_content == '' ) {
				$what['post_content'] = "XD say: do not save empty ".$post_id;
			}
			if ( $what != array() )
				$wpdb->update( $wpdb->posts, $what, $where );
		}
	}

	/**
	 * clean msgid postmeta before deleting
	 */
	function msgid_post_links_delete ( $post_id ) {
		// type of msg
		if ( get_post_type( $post_id ) == XDMSG ) {
			$type = get_post_meta ( $post_id, $this->msgtype_meta, true);

			if ( $type == 'msgid_plural' ) {

				$parent = get_post($post_id)->post_parent;
				$res = get_post_meta ( $parent, $this->msgchild_meta, false );
				$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				if ( $res != '' ) {
					unset ( $thechilds['msgid']['plural'] ) ;
					update_post_meta ( $parent, $this->msgchild_meta, $thechilds );
				}

			} elseif ( $type != 'msgid' ) {
				$langs = get_the_terms( $post_id, TAXONAME );
				$target_lang = $langs[0]->name ;
				// id of msg id or parent
				if ( $type == 'msgstr' && $target_lang != '' ) {
					$msgid_ID = get_post_meta ( $post_id, $this->msgidlang_meta , true);
					$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false );
					$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
					if ( $res != '' && is_array ( $thelangs ) ) {

						unset ( $thelangs['msgstrlangs'][$target_lang]['msgstr'] ) ;
						if ( isset ( $thelangs['msgstrlangs'][$target_lang] ) && $thelangs['msgstrlangs'][$target_lang] == array( ) ) unset ( $thelangs['msgstrlangs'][$target_lang] ); // 2.3
						if ( isset ( $thelangs['msgstrlangs'] ) && $thelangs['msgstrlangs'] == array( ) ) unset ( $thelangs['msgstrlangs'] ); // 2.3
						if ( $thelangs != array() )
							update_post_meta ( $msgid_ID, $this->msglang_meta, $thelangs ); // update id post_meta
						else
							delete_post_meta ( $msgid_ID, $this->msglang_meta );
					}
				} elseif ( false !== strpos( $type, 'msgstr_' ) && $target_lang != '' ) {
					$indices = explode ('_', $type);
					$msgid_ID = get_post_meta ( $post_id, $this->msgidlang_meta , true);
					if ( $indices[1] == 0 ) {
						$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false );
						$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
						if ( $res != '' && is_array ( $thelangs ) ) {
							unset ( $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ) ;
							if ( isset ( $thelangs['msgstrlangs'][$target_lang] ) && $thelangs['msgstrlangs'][$target_lang] == array( ) ) unset ( $thelangs['msgstrlangs'][$target_lang] ); // 2.3
							if ( isset ( $thelangs['msgstrlangs'] ) && $thelangs['msgstrlangs'] == array( ) ) unset ( $thelangs['msgstrlangs'] ); // 2.3

							if ( $thelangs != array() )
								update_post_meta ( $msgid_ID, $this->msglang_meta, $thelangs ); // update id post_meta
							else
								delete_post_meta ( $msgid_ID, $this->msglang_meta ); // 2.9
							}
					} else {
						$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false );
						$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
						if ( $res != '' && is_array ( $thelangs ) ) {
							if ( isset ( $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ) ) {
								$parent = $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ;
								$res = get_post_meta ( $parent, $this->msgchild_meta, false );
								$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
								if ( $res != '' ) {
									unset ( $thechilds['msgstr']['plural'][$indices[1]] ) ;
									update_post_meta ( $parent, $this->msgchild_meta, $thechilds );
								}
							}
						}
					} // indice > 0
				} // str plural
			} // msgstr
		} // XDMSG
	}

	/**
	 * a new msgid is created manually
	 */
	function msgid_post_new_create ( $post_id, $post ) {
		global $wpdb;
		if ( isset( $_POST['_inline_edit'] ) ) return;
		if ( isset( $_GET['bulk_edit'] ) ) return;
		if ( get_post_type( $post_id ) == XDMSG ) {
			if ( ! wp_is_post_revision( $post_id ) && $this->importing_mode != true ) {

				//$temp_post = $this->temp_get_post ( $post_id );
				$type = get_post_meta ( $post_id, $this->msgtype_meta, true ) ;
				if ( $type == "" ) {
					update_post_meta ( $post_id, $this->msgtype_meta, 'msgid' );
					update_post_meta ( $post_id, $this->msglang_meta, array() );
					update_post_meta ( $post_id, $this->msg_extracted_comments, $this->local_tag . ' '); // 2.2.0 local by default if hand created
					update_post_meta ( $post_id, $this->msg_sort_slug, sanitize_title ( $post->post_content ) );
				}
				if ( isset( $_POST['add_ctxt'] ) ) {
					$the_context = $_POST['add_ctxt'] ; // 2.7.2 - fixed if very new msgid
				} else {
					$the_context = get_post_meta ( $post_id, $this->ctxt_meta, true );
				}

				$result = $this->msgid_exists ( $post->post_content, $the_context );
				if ( $result === false || $result[0] == $post_id ) {
					return ;
				} else {
					if ( $type == get_post_meta ( $result[0], $this->msgtype_meta, true ) && $type == 'msgid') {
						$found_context = get_post_meta ( $result[0], $this->ctxt_meta, true ); //2.10.2
						if ( $found_context == $the_context ) {
							$context = (''!= $the_context) ? ' ' . sprintf(__('and context %s','xili-dictionary'), $the_context ) : '' ; // 2.7.1
							$newcontent = sprintf( __('msgid exists as %1$d with content: %2$s%3$s','xili-dictionary'), $result[0], $post->post_content, $context) ;
							$where = array( 'ID' => $post_id );
							$wpdb->update( $wpdb->posts, array( 'post_content' => $newcontent ), $where );
						}
					} else {
						return ;
					}
				}
			}
		}
	}

	/**
	 * Main "dashboard" box in msg edit to display and link the series of msg
	 *
	 * @since 2.0
	 * @updated 2.1.2 - called by action add_meta_boxes
	 *
	 */
	function add_custom_box_in_post_msg () {
		$singular_name = __('series','xili-dictionary');

		add_meta_box('msg_state', sprintf(__("msg %s",'xili-dictionary'), $singular_name), array(&$this,'msg_state_box'), XDMSG , 'normal','high');
		if ( get_current_screen()->action != 'add' ) { // only for edit not new
			add_meta_box('msg_untranslated_list', sprintf(__("List of MSG %s to translate",'xili-dictionary'), $singular_name), array(&$this,'msg_untranslated_list_box'), XDMSG , 'normal','high');
			if ( current_user_can ('xili_dictionary_edit_save') ) {	// 2.3.2
				add_meta_box('msg_tools_shortcuts', __("Shortcuts to update mo files",'xili-dictionary'), array(&$this,'msg_tools_shortcuts_box'), XDMSG , 'side' );
			}
		}

	}

	// need langfolder
	function mo_files_array () {
		$this->theme_mos = $this->get_pomo_from_theme();
		$this->local_mos = $this->get_pomo_from_theme( true ); // 2.1
		if ( is_multisite() ) {
			$this->file_site_mos = $this->get_pomo_from_site(); // since 1.2.0 - mo of site
			$this->file_site_local_mos = $this->get_pomo_from_site( true );
		}
		// test if plugin has msgid
		$list_plugins = $this->get_origin_plugin_paths();
		if ( $list_plugins ) {
			foreach ($list_plugins as $plugin_path ){
				$this->plugin_mos[$plugin_path] = $this->get_pomo_from_plugin( $plugin_path ); // update for lang
			}
		}
	}

	function get_list_languages () {
		$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );
		$this->languages_key_slug = array();
		$this->languages_names = array();
		foreach ( $listlanguages as $language ) {
			$this->languages_key_slug[$language->slug] = array ('name'=>$language->name, 'description'=>$language->description );
			$this->languages_names[$language->slug] = $language->name; // 2.6.1 for importing pot
		}
		return $listlanguages;
	}

	/**
	 * display shortcut links to update mo
	 *
	 * called add_meta_box('msg_tools_shortcuts'
	 *
	 * @since 2.3.2
	 */
	function msg_tools_shortcuts_box ( $post ) {
		$post_ID = $post->ID;
		$lang = $this->cur_lang ( $post_ID );

		if ( $lang ) {
			$link_theme_mo = wp_nonce_url (admin_url().'post.php?post='.$post_ID.'&action=edit&msgupdate=updatetheme&langstr='.$lang->name.'&message=33', 'xd-updatemo');
			$link_local_mo = wp_nonce_url (admin_url().'post.php?post='.$post_ID.'&action=edit&msgupdate=updatelocal&langstr='.$lang->name.'&message=34', 'xd-updatemo');

			$cur_theme_name = $this->get_option_theme_name();

			echo '<p>' . sprintf(__('This msg translation is in %1$s (%2$s)','xili-dictionary'),$lang->description, $lang->name).'</p>';
			echo '<h4>'. __('Updating shortcuts', 'xili-dictionary').'</h4>';

			if ( $this->count_msgids ( $lang->name, true ) > 0 ) {
				echo '<p>' . sprintf('<a class="action-button blue-button" onClick="verifybefore(1)" href="%2$s" >'.__('Update','xili-dictionary').' local-%3$s.mo</a>','#', '#', $lang->name).'</p>';
			} else {
				echo '<p class="action-button grey-button">' . sprintf( __('No local translated msgid to be saved in %s','xili-dictionary'), ' local-'.$lang->name.'.mo' ) . '</p>';
			}

			echo '<p>' . sprintf(__('It is possible to update the .mo files of current theme %s','xili-dictionary'), '<strong>'.$cur_theme_name.'</strong>' ).'</p>';

			if ( current_user_can ('xili_dictionary_admin') ) {

				echo '<p><em>' . __('Before to use this button, it is very important that you verify that your term list is quite achieved inside the dictionary. It is because the original .mo delivered with theme is updated (erased) !!!', 'xili-dictionary') .'</em></p>';

				if ( $this->count_msgids ( $lang->name, false, $cur_theme_name ) > 0 ) {
					echo '<p>' . sprintf('<a class="action-button grey-button" onClick="verifybefore(0)" href="%1$s" >'.__('Update','xili-dictionary').' %3$s.mo</a>','#', '#', $lang->name).'</p>';
				} else {
					echo '<p class="action-button grey-button">' . sprintf( __('No translated msgid to be saved in %s','xili-dictionary'), $lang->name.'.mo' ) . '</p>';
				}
			}


			//echo '<p>- ' . sprintf('<a href="%1$s" >%3$s.mo</a><br />- <a href="%2$s" >'.__('local','xili-dictionary').'-%3$s.mo</a>',$link_theme_mo, $link_local_mo, $lang->name).'</p>';
			echo '<small>'.$this->msg_action_message.'</small>';


		} else {

		echo '<p>' . __('Links are available if a translation (msgstr) is edited.','xili-dictionary').'</p>';
		}

		if ( $lang ) {
		?>

		<p class="xdversion">XD v. <?php echo XILIDICTIONARY_VER; ?></p>
		<script type="text/javascript">
function verifybefore(id) {
 var link = new Array();

 link[0] = "<?php echo str_replace('amp;','', $link_theme_mo); ?>";
 link[1] = "<?php echo str_replace('amp;','', $link_local_mo); ?>";
 var confirmmessage = "<?php _e('Are you sure you want to update mo ? ', 'xili-dictionary'); ?>";
 var message = "Action Was Cancelled By User " ;
 if (confirm(confirmmessage)) {

	window.location = link[id];

 } else {

 // alert(message);

}

}
</script>
		<?php
		}
	}

	// add messages called by add_filter( 'post_updated_messages' @since 2.1.2
	function msg_post_messages ( $messages ) {
		$messages['post'][33] = __('MO file updating started: see result in meta-box named - Shortcuts... - below buttons', 'xili-dictionary');
		$messages['post'][34] = __('Local MO updating started: see result in meta-box named - Shortcuts... - below buttons', 'xili-dictionary');
		return $messages;
	}

	/**
	 * update current .mo
	 *
	 * called add_action( 'add_meta_boxes_' . XDMSG
	 *
	 * to have values before metaboxes built
	 */
	function msg_update_action ( $post ) {
		$extract_array = array();
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( $langfolderset !='' ) ? $langfolderset.'/' : '/';
		// doublon
		$this->langfolder = str_replace ("//","/", $this->langfolder ); // upgrading... 2.0 and sub folder sub
		if ( isset ($_GET['msgupdate'] ) && isset ($_GET['langstr']) ) { // shortcut to update .mo - 2.1.2
			check_admin_referer( 'xd-updatemo' );
			$filetype = $_GET['msgupdate'];
			$selectlang = $_GET['langstr'];

			$cur_theme_name = $this->get_option_theme_name();

			if ( is_multisite() ) {
				if (($uploads = xili_upload_dir()) && false === $uploads['error'] ) {

				if ( $filetype == 'updatelocal' ) { // only current site - need tools for other superadmin place
					$local = 'local-';
					$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
					$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
					$file = $uploads['path']."/local-".$selectlang.".mo" ;

				} else {

				$extract_array [ 'origin' ] = array( $cur_theme_name ); // only if assigned to current theme domain

					$local = '';
					$file = $uploads['path']."/".$selectlang.".mo" ;

				}
				$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name ;
				$mo = $this->from_cpt_to_POMO_wpmu ( $selectlang, 'mo', true, $extract_array ); // do diff if not superadmin
			}

			} else { // standalone

				if ( $filetype == 'updatelocal' ) {
					$local = 'local-';
					$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
					$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
					$file = $this->active_theme_directory . $this->langfolder.'local-'.$selectlang.'.mo' ;

				} else {

					$extract_array [ 'origin' ] = array( $cur_theme_name );
					$local = '';
					$file = '';
				}
				$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name ;
				$mo = $this->from_cpt_to_POMO ( $selectlang, 'mo', $extract_array );
			}

			if ( isset ( $mo ) && count ($mo->entries) > 0 ){

				if ( false === $this->Save_MO_to_file ( $selectlang , $mo, $file ) ) {
					$this->msg_action_message = sprintf('<span class="alert">'.__('Error with File %s !', 'xili-dictionary').'</span> ('.$file.')', $local.$selectlang.'.mo');
				} else {
					$this->msg_action_message = sprintf(__('File %1s updated with %2s msgids', 'xili-dictionary'), $local.$selectlang.'.mo', count ($mo->entries) );
				}

			} else {
				$this->msg_action_message = sprintf('<span class="alert">'.__('Nothing modified in %s, file not updated', 'xili-dictionary').'</span>', $local.$selectlang.'.mo');
			}
		}
	}

	// the first lang of msgstr or false for msgid
	function cur_lang ( $post_ID ) {
		$langs = wp_get_object_terms( $post_ID, TAXONAME);
		if ( ! is_wp_error( $langs ) && ! empty( $langs ) ) {
			return apply_filters ( 'other_multilingual_plugin_filter_term' , $langs[0] ) ;
		}
		return false;
	}

	/**
	 * Normal metabox : List to display untranslated msgid in target lang like msgstr currently displayed
	 *
	 * @since 2.1.2
	 */
	function msg_untranslated_list_box ( $post ) {
		$post_ID = $post->ID;
		$type = get_post_meta ( $post_ID, $this->msgtype_meta, true);
		$msglang = '';
		$message = '';
		$arraylink = array();
		$sortparent = (($this->subselect == '') ? '' : '&amp;tagsgroup_parent_select='.$this->subselect );
		$listlanguages = $this->get_list_languages();
		foreach ( $listlanguages as $language ) {
			$arraylink[] = sprintf( '<a href="%s" >'.$language->name.'</a>', 'post.php?post='.$post_ID.'&action=edit&workinglang='.$language->slug );
		}
		$listlink = implode (' ', $arraylink );
		$working_lang = ( isset ($_GET['workinglang']) ) ? $_GET['workinglang'] : '' ;

		if ( $type == 'msgstr' ) {

			$lang = $this->cur_lang ( $post_ID );

			if ( $lang ) $msglang = $lang->slug ;

			$this->subselect = ( $working_lang == '' ) ? $msglang : $working_lang ;
		$this->searchtranslated = 'not';
		$message = sprintf(__('MSGs not translated in %1$s. <em>Sub-select in %2$s</em>', 'xili-dictionary' ), $this->languages_key_slug[$this->subselect]['name'], $listlink ) ;

		} else { // msgid

			$this->subselect = $working_lang;

			$message = ( $working_lang == '' ) ? sprintf( __('No selection: Sub-select in %s', 'xili-dictionary' ), $listlink ) : sprintf(__('MSGs not translated in %1$s. <em>Sub-select in %2$s</em>', 'xili-dictionary' ), $_GET['workinglang'], $listlink );
		$this->searchtranslated = ( $working_lang == '' ) ? '' : 'not' ;
		}

	?>
		<p><?php echo $message ; ?></p>
		<div id="topbanner">
		</div>
		<div id="tableupdating">
		</div>

		<table class="display" id="linestable">
			<thead>
				<tr>
					<th scope="col" class="center colid"><a href="<?php echo $this->xd_settings_page; ?>" ><?php _e('ID') ?></a></th>
					<th scope="col" class="coltexte"><a href="<?php echo $this->xd_settings_page.'&amp;orderby=name'.$sortparent; ?>"><?php _e('Text') ?></a>
					</th>
					<th scope="col" class="colslug"><?php _e('Metas','xili-dictionary') ?></th>
					<th scope="col" class="colgroup center"><?php _e('Save status', 'xili-dictionary') ?></th>
					<th colspan="2"><?php _e('Action') ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
					<?php

					$this->xili_dict_cpt_row(); /* the lines */
					?>
			</tbody>
		</table>
		<div id="bottombanner">
		</div>
		<?php
		$this->insert_js_for_datatable( array('swidth2'=>'50%', 'screen' => 'post-edit') );
	}

	/**
	 * insert js for datatable - used in post and in tools
	 *
	 * @since 2.1.2
	 *
	 */
	function insert_js_for_datatable( $args ) {
		?>
		<script type="text/javascript">

			//<![CDATA[
			jQuery(document).ready( function($) {

				var termsTable = $('#linestable').dataTable( {
					"iDisplayLength": 20,
					"bStateSave": true,
					"bAutoWidth": false,
					"sDom": '<"topbanner"ipf>rt<"bottombanner"lp><"clear">',
					"sPaginationType": "full_numbers",
					"aLengthMenu": [[20, 30, 60, -1], [20, 30, 60, "<?php _e('All lines','xili-dictionary') ?>"]],
					"oLanguage": {
						"oPaginate": {
							"sFirst": "<?php _e('First','xili-dictionary') ?>",
							"sLast": "<?php _e('Last page','xili-dictionary') ?>",
							"sNext": "<?php _e('Next','xili-dictionary') ?>",
							"sPrevious": "<?php _e('Previous','xili-dictionary') ?>"
						},
						"sInfo": "<?php _e('Showing (_START_ to _END_) of _TOTAL_ entries','xili-dictionary') ?>",
						"sInfoFiltered": "<?php _e('(filtered from _MAX_ total entries)','xili-dictionary') ?>",
						"sEmptyTable": "<?php _e('Empty table','xili-dictionary') ?>",
						"sInfoEmpty": "<?php _e('No entry','xili-dictionary') ?>",
						"sLengthMenu": "<?php _e('Show _MENU_ entries','xili-dictionary') ?>",
						"sSearch": "<?php _e('Filter terms:','xili-dictionary') ?>"

					},
					"aaSorting": [[1,'asc']],
					"aoColumns": [
						{ "bSearchable": false, "sWidth" : "30px" },
						{ "sWidth" : "<?php echo $args['swidth2']; ?>" },
						{ "bSortable": false, "bSearchable": false },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "105px" },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "70px" } ]
				} );

				$('#tableupdating').hide();
				$('#linestable').css({ visibility:'visible' });

				<?php if ( $args['screen'] == 'toolbox' ) { ?>
					// close postboxes that should be closed
					jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
					// postboxes setup
					postboxes.add_postbox_toggles('<?php echo $this->thehook; ?>');
				<?php } ?>
			});
			//]]>
		</script>
	<?php
	}

	function msg_state_box () {
		global $post_ID, $post ;

		$type = get_post_meta ( $post_ID, $this->msgtype_meta, true);

		$this->mo_files_array ();

		?>
		<div id="msg-states">
			<?php $this->msg_status_display ( $post_ID ); ?>
		</div>
		<div id="msg-states-comments">
			<?php $for_bottom_box = $this->msg_status_comments ( $post_ID ); ?>
		</div>
		<div id="msg-states-actions" >
			<strong><?php _e( 'Informations and actions about files .po / mo', 'xili-dictionary' ); echo ':</strong><br />'; ?>
			<div class="msg-states-actions-left" >
			<?php echo $for_bottom_box['link'] .'<br />'; ?>
			<?php $origins = get_the_terms( $post_ID, 'origin' );
			$names = array();
			if ( $origins ) {
				foreach ( $origins as $origin ) {
					$names[] = $origin->name;
				}
				echo __( 'Come from:', 'xili-dictionary') .' '. implode (' ', $names).'<br />';;
			} else {
				if ( !$for_bottom_box['state'] ) {
					if ( $type == 'msgid' ) _e ( 'Not yet assigned', 'xili-dictionary') ;
				}
			} ?>
			</div>
			<div class="msg-states-actions-right" >
			<?php
			$context = get_post_meta ( $post_ID, $this->ctxt_meta, true);
			$res = $this->is_saved_cpt_in_theme( htmlspecialchars_decode ($post->post_content), $type, $context );
			$save_state = '<br />'. ( ( false === strpos ( $res[0], '**</span>' ) ) ? sprintf( __('theme folder %s','xili-dictionary') ,$res[0]) : '' ) . ( ( false == strpos ( $res[2], '?</span>' ) ) ? ' (theme local-'.$res[2].')' : '' );
			if ( is_multisite() ) $save_state .= '<br />'. __('this site','xili-dictionary') . ( ( false === strpos ( $res[1], '**</span>' ) ) ? sprintf( __('folder %s','xili-dictionary') ,$res[1]) : ' ' ) . ( ( false == strpos ( $res[3], '?</span>' ) ) ? ' (theme local-'.$res[3].')' : '' );

			echo $type.' <em>' . $post->post_content . '</em> ' . __('saved in ','xili-dictionary') . $save_state;

			//$plugin_path = 'xili-postinpost/xili-postinpost.php';
			foreach ( $names as $plugin_path) {
				$plugin_res = $this->is_saved_cpt_in_plugin( $plugin_path, htmlspecialchars_decode ($post->post_content), $type, $context );
				if ( $plugin_res ) echo sprintf( '<br /><small>' . __('Plugin ( %s ):', 'xili-dictionary'), $this->get_plugin_name ($plugin_path) ) . '</small> ' . implode (' - ', $plugin_res ).'<br />';
			}
			?>
			</div>
			<p class="xdversion">XD v. <?php echo XILIDICTIONARY_VER; ?></p>
		</div>
	<?php
	}

	/**
	 * test unique content for msgid + context
	 *
	 * @since 2.0
	 * @return ID is true
	 */
	function msgid_exists ( $content = "", $ctxt = null ) {
		global $wpdb;
		if ( $content != "" ) {
			if ( null == $ctxt) {
				$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE BINARY post_content = %s AND post_type = %s", $content, XDMSG );

			} else {
				$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) INNER JOIN $wpdb->postmeta as mt1 ON ($wpdb->posts.ID = mt1.post_id) WHERE BINARY post_content = %s AND post_type = %s AND $wpdb->postmeta.meta_key= '{$this->ctxt_meta}' AND mt1.meta_key= '{$this->ctxt_meta}' AND mt1.meta_value = %s ", $content, XDMSG, $ctxt);
			}
			// 2.2.0
			$found_posts = $wpdb->get_col($posts_query);
			if ( empty($found_posts) ) {
				return false;
			} else {
				return $found_posts;
			}
		}

	}

	/**
	 * test unique content for msgstr + msgid + language
	 *
	 * @since 2.0
	 * @return ID is true
	 */
	function msgstr_exists ( $content = "", $msgid, $curlang ) {
		global $wpdb;
		if ( "" != $content) {
			$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) INNER JOIN $wpdb->postmeta as mt1 ON ($wpdb->posts.ID = mt1.post_id) WHERE BINARY post_content = %s AND post_type = %s AND $wpdb->postmeta.meta_key='{$this->msgidlang_meta}' AND mt1.meta_key='{$this->msgidlang_meta}' AND mt1.meta_value = %s ", $content, XDMSG, $msgid);


			$found_posts = $wpdb->get_col($posts_query);
			if ( empty($found_posts) ) {
				return false;
			} else {

				if ( in_array ( $curlang , wp_get_object_terms( $found_posts, TAXONAME, array ( 'fields' => 'names' ) ) ) ) {
					// select only this with $curlang
					return $found_posts ;

				} else {
					return false;
				}

			}
		}
		return false;
	}

	// used by new ajax
	function pomo_entry_to_xdmsg ( $pomsgid, $pomsgstr, $curlang = 'en_US', $args = array('importing_po_comments'=> '', 'origin_theme' =>'' ) ) {
		$nblines = array( 0, 0); // id, str count
		// test if msgid exists
			$result = $this->msgid_exists ( $pomsgstr->singular, $pomsgstr->context ) ;

			if ( $result === false ) {
				// create the msgid
				$type = 'msgid';
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $pomsgstr->singular, $pomsgstr->context, $type, 0, $pomsgstr ) ;
				$nblines[0]++ ;
			} else {
				$msgid_post_ID = $result[0];
				if ( $args['importing_po_comments'] != '' ) {
					$this->insert_comments( $msgid_post_ID, $pomsgstr, $args['importing_po_comments'] );
				}

			}

			// add origin taxonomy
			if ( ''!= $args['origin_theme'] )
				wp_set_object_terms( $msgid_post_ID, $args['origin_theme'], 'origin', true ); // true to append to existing

			if ( $pomsgstr->is_plural != null ) {
				// create msgid plural (child of msgid)
				// $pomsgstr->plural, $msgid_post_ID
				$result = $this->msgid_exists ( $pomsgstr->plural ) ;
				if ( $result === false ) {
					$msgid_post_ID_plural = $this->insert_one_cpt_and_meta( $pomsgstr->plural, null, 'msgid_plural' , $msgid_post_ID, $pomsgstr );
					// add origin taxonomy
					if ( ''!= $args['origin_theme'] )
						wp_set_object_terms( $msgid_post_ID_plural, $args['origin_theme'], 'origin', true ); // true to append to existing

				}
			}

			// create msgstr - taxonomy - if lang exists
			if ( $curlang != '') {
				if ( $pomsgstr->is_plural == null || $pomsgstr->is_plural == 0 ) {

					$msgstr_content = ( isset( $pomsgstr->translations[0]) ) ? $pomsgstr->translations[0] : "" ;

					if ( $this->is_importing_pot( $curlang ) ) $msgstr_content = "" ;

					if ( $msgstr_content != "" ) {
						// test exists with taxo before
						$result = $this->msgstr_exists ( $msgstr_content, $msgid_post_ID, $curlang ) ;
						if ( $result === false ) {
							$msgstr_post_ID = $this->insert_one_cpt_and_meta( $msgstr_content, null, 'msgstr', 0, $pomsgstr );
							wp_set_object_terms( $msgstr_post_ID, $this->target_lang($curlang), TAXONAME );
							$nblines[1]++ ;
						} else {
							$msgstr_post_ID = $result[0];
						}

					// create link according lang

						$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
						$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
						$thelangs['msgstrlangs'][$curlang]['msgstr'] = $msgstr_post_ID;
						update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_post_ID );

						// add origin taxonomy
						if ( ''!= $args['origin_theme'] )
							wp_set_object_terms( $msgstr_post_ID, $args['origin_theme'], 'origin', true ); // true to append to existing

					}


				} else {
					// $pomsgstr->translations
					$i=0; $parentplural = 0;
					foreach ( $pomsgstr->translations as $onetranslation ) {
						$msgstr_plural = 'msgstr_' . $i ;
						$parent = ( $i == 0 ) ? 0 : $parentplural ;
						if ($this->is_importing_pot( $curlang )) $onetranslation = "" ; // 2.6.1

						if ( $onetranslation != "" ) {
							// test exists with taxo before
							$result = $this->msgstr_exists ( $onetranslation, $msgid_post_ID, $curlang ) ;
							if ( $result === false ) {
								$msgstr_post_ID_plural = $this->insert_one_cpt_and_meta( $onetranslation, null, $msgstr_plural , $parent, $pomsgstr );
								wp_set_object_terms( $msgstr_post_ID_plural, $this->target_lang($curlang), TAXONAME );
								$nblines[1]++ ;
							} else {
								$msgstr_post_ID_plural = $result[0];
							}
							update_post_meta ( $msgstr_post_ID_plural, $this->msgidlang_meta, $msgid_post_ID );
							// add origin taxonomy
							if ( ''!= $args['origin_theme'] )
								wp_set_object_terms( $msgstr_post_ID_plural, $args['origin_theme'], 'origin', true ); // true to append to existing - fixes 2.6.2


						}

						if ( $i == 0 ) {
							$parentplural = $msgstr_post_ID_plural;

							// create link according lang in msgid
							$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
							$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
							$thelangs['msgstrlangs'][$curlang][$msgstr_plural] = $msgstr_post_ID_plural;
							update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );

						} // only first str

						$i++;
					}
				}
			}
			return $nblines;
	}

	/**
	 * Test if importing POT file
	 *
	 * @since 2.6.1
	 *
	 * @return true or false (true = pot import)
	 */
	function is_importing_pot( $curlang ){

		if (!in_array( $curlang , $this->languages_names ) ){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * import a msg line
	 *
	 * @since 2.0
	 *
	 * @updated 2.1.2
	 *
	 * @return ID
	 */
	function insert_one_cpt_and_meta ( $content, $context = null, $type , $parent = 0, $entry = null ) {
		global $user_ID;
		/* if (!empty($entry->translator_comments)) $po[] = PO::comment_block($entry->translator_comments);
				if (!empty($entry->extracted_comments)) $po[] = PO::comment_block($entry->extracted_comments, '.');
				if (!empty($entry->references)) $po[] = PO::comment_block(implode(' ', $entry->references), ':');
				if (!empty($entry->flags)) $po[] = PO::comment_block(implode(", ", $entry->flags), ',');
			*/
		if ( null != $entry ) {
			$references = (!empty($entry->references)) ? implode ( ' #: ' , $entry->references ) : '' ;
			$flags = (!empty($entry->flags)) ? implode ( ', ' , $entry->flags ) : '' ;
			$extracted_comments = (!empty($entry->extracted_comments)) ? $entry->extracted_comments : '' ;
			$translator_comments = (!empty($entry->translator_comments)) ? $entry->translator_comments : '' ;
		} else {
			$references = "";
			$flags = "";
			$extracted_comments = "";
			$translator_comments = "";

		}

		$params = array('post_status' => 'publish', 'post_type' => XDMSG, 'post_author' => $user_ID,
		'ping_status' => get_option('default_ping_status'), 'post_parent' => $parent,
		'menu_order' => 0, 'to_ping' => '', 'pinged' => '', 'post_password' => '',
		'guid' => '', 'post_content_filtered' => '', 'post_excerpt' => $references, 'import_id' => 0,
		'post_content' => $content, 'post_title' => '');

		$post_id = wp_insert_post( $params ) ;

		if ( $post_id != 0 ) {
			if ( $context != null ) // postmeta
				update_post_meta ( $post_id, $this->ctxt_meta, $context );

		// type postmeta

			update_post_meta ( $post_id, $this->msgtype_meta, $type );

			if ( $type == 'msgid' ) {
				if ( $extracted_comments != "" ) update_post_meta ( $post_id, $this->msg_extracted_comments, $extracted_comments );
				if ( $translator_comments != "") update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
				if ( $flags != "")
					update_post_meta ( $post_id, $this->msg_flags, $flags );
				$this->update_msgid_flags ( $post_id, $params['post_content'] ); // 2.9
				update_post_meta ( $post_id, $this->msglang_meta, array() ); // 2.1.2
			}

			if ( $type == 'msgstr' || $type == 'msgstr_0' ) {

				if ( $translator_comments != "") update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
			}
			update_post_meta ( $post_id, $this->msg_sort_slug, sanitize_title ( $content ) );

		// update postmeta children
		// create array
			if ( $parent != 0 ) {

				$res = get_post_meta ( $parent, $this->msgchild_meta, false );
				$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				if ( $type == 'msgid_plural' ) {
					$thechilds['msgid']['plural'] = $post_id;

				} elseif ( $type != 'msgstr' ){
					$indices = explode ('_', $type);
					$thechilds['msgstr']['plural'][$indices[1]] = $post_id;
				}

				update_post_meta ( $parent, $this->msgchild_meta, $thechilds );

			}
		}
		return $post_id;
	}

	/**
	 * insert comments of msgid / msgstr
	 *
	 * called by pomo_entry_to_xdmsg
	 *
	 */
	function insert_comments( $post_id, $entry, $import_comment_mode = 'replace' ) {

		$references = (!empty($entry->references)) ? implode ( ' #: ' , $entry->references ) : '' ;
		$flags = (!empty($entry->flags)) ? implode ( ', ' , $entry->flags ) : '' ;
		$extracted_comments = (!empty($entry->extracted_comments)) ? $entry->extracted_comments : '' ;
		$translator_comments = (!empty($entry->translator_comments)) ? $entry->translator_comments : '' ;

		if ( $import_comment_mode == 'replace' ) {
			// update references in excerpt
			$postarr = get_post( $post_id, ARRAY_A ) ;

			$postarr['post_excerpt'] = $references;

			wp_insert_post( $postarr );

			// update comments in meta
			if ( $extracted_comments != "" ) update_post_meta ( $post_id, $this->msg_extracted_comments, $extracted_comments );
			if ( $translator_comments != "") update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
			if ( $flags != "") update_post_meta ( $post_id, $this->msg_flags, $flags );
			$this->update_msgid_flags ( $post_id, $postarr['post_content'] ); // 2.9

		} elseif ( $import_comment_mode == 'append' ) { // don't erase existing comments - can be risked


		}

	}

	/**
	 * new columns in cpt list
	 *
	 */
	function xili_manage_column_name( $columns ) { // must be verified
		global $post_type; // from admin.php+edit.php
		if ( $post_type == XDMSG ) {
			$ends = array('author', 'date', 'rel', 'visible');
			$end = array();
			foreach( $columns AS $k=>$v ) {
				if ( in_array($k, $ends) ) {
					$end[$k] = $v;
					unset($columns[$k]);
				}
			}
			$columns['msgcontent'] = __('Content','xili-dictionary'); // ? sortable ?
			$columns['msgpostmeta'] = __('Metas','xili-dictionary');
			$columns['msgposttype'] = __('Type / Origin','xili-dictionary');
			if ( !class_exists ( 'xili_language' ) ) {
				$columns[TAXONAME] = __('Language','xili-dictionary');
			}
			$columns = array_merge($columns, $end);
		}
		return $columns;

	}

	function xili_manage_column_row ( $column , $id ) {
		global $post;
		$type = get_post_meta ( $id, $this->msgtype_meta, true);

		if ($column == 'msgcontent' && $post->post_type == XDMSG ) {
			echo htmlspecialchars( $post->post_content );
			$writers = wp_get_post_terms($id, 'writer', array("fields" => "names"));
			if ( $type != 'msgid' ) {
				if ( is_wp_error( $writers ) || !$writers) {
					echo '<br /><br /><small>( ' . __( "No writer found", 'xili-dictionary') . ' )</small>';
				} else {
					$title_w = ( 1 == count ( $writers )  ) ? __( "Writer", 'xili-dictionary') : __( "Writers", 'xili-dictionary') ;
					echo '<br /><br />( <small>' . $title_w . sprintf(__(': %s', 'xili-dictionary'), implode( $writers, ', ') ) . '</small> )';
				}
			}
		}
		if ($column == 'msgpostmeta' && $post->post_type == XDMSG ) {

			$this->msg_link_display ( $id );
		}
		if ($column == 'msgposttype' && $post->post_type == XDMSG ) {


			if ( $type == 'msgid' ) {
				$msgid_ID = $id;
			} else {
				$msgid_ID = get_post_meta ( $id, $this->msgidlang_meta , true);
			}
			$extracted_comments = get_post_meta ( $msgid_ID, $this->msg_extracted_comments, true );
			if ( ( $extracted_comments != "" && false !== strpos( $extracted_comments, $this->local_tag .' ' ) ) ) {
				_e('Local', 'xili-dictionary');
			}

			//
			$origins = get_the_terms( $id, 'origin' );
			$names = array();
			if ( $origins ) {
				echo '<br />';
				foreach ( $origins as $origin ) {
					$names[] = $this->get_plugin_name( $origin->name , __('Plugin:', 'xili-dictionary' ) ); // if not: no prefix 2.6.0
				}
				printf( __('<small>From:</small> %s', 'xili-dictionary' ) , implode (', ', $names ) ) ;
			}
		}

		if ($column == 'language' && $post->post_type == XDMSG ) {
			if ( !class_exists ( 'xili_language' ) ) {

				$lang = $this->cur_lang( $id );
				if ( isset ( $lang->name) ) echo $lang->name;

			}
		}

		return;

	}

	// Register the column as sortable
	function msgcontent_column_register_sortable( $columns ) {
		$columns['msgcontent'] = 'msgcontent';
		$columns['msgpostmeta'] = 'msgpostmeta';
		return $columns;
	}

	function msgcontent_column_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) && 'msgpostmeta' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' => $this->msgtype_meta,
				'orderby' => 'meta_value'
			) );
		}

		return $vars;
	}

	/**
	 * Add Languages selector in edit.php edit after Category Selector (hook: restrict_manage_posts) only if no XL
	 *
	 * @since 2.0
	 * @updated 2.3.4 - only xdmsg if xl is not
	 */
	function restrict_manage_languages_posts () {
		global $post_type; // from admin.php+edit.php
		if ( $post_type == XDMSG ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			?>
	<select name="lang" id="lang" class='postform'>
		<option value=""> <?php _e('View all languages','xili-dictionary') ?> </option>

				<?php foreach ($listlanguages as $language) {
					$selected = ( isset ( $_GET[QUETAG] ) && $language->slug == $_GET[QUETAG] ) ? "selected=selected" : "" ;
					echo '<option value="'.$language->slug.'" '.$selected.' >'.__($language->description, 'xili-dictionary').'</option>';
				}
				?>
	</select>
			<?php
		}
	}

	/**
	 * Add a tool to import all terms of a taxonomy inside XD list - not called when Add New or Apply bulk action
	 *
	 * @since 2.3.3
	 *
	 */
	function add_import_in_XD_button ( $taxonomy ) {
		global $xili_language;
		$taxonomy_obj = get_taxonomy( $taxonomy );
		$result = '';
		$paged = ( isset( $_GET['paged'] ) ) ? '&paged='.$_GET['paged'] : '';
		$quantities = array( 0, 0, array(), array() );

		if ( isset( $_GET['import-in-xd'] ) ) {
			if ( isset ( $_GET['wpnonce'] ) && wp_verify_nonce( $_GET['wpnonce'], 'upload-xili-dictionary-'. $taxonomy ) ) {

				$quantities = $this->xili_read_catsterms_cpt( $taxonomy );

				$result = sprintf(__('xili-dictionary msgid list updated with %1$s terms - %2$s name(s) and %3$s description(s) - ', 'xili-dictionary'), $taxonomy_obj->labels->singular_name, $quantities[0], $quantities[1]) ;
			} else {
				wp_die( __( 'Security check', 'xili-dictionary' ) );
			}
		}

		?>
		<br />
		<div class="updated" style="background: #f5f5f5; border:#dfdfdf 1px solid;">
		<fieldset style="margin:10px 0 2px; padding:10px 6px;" ><legend><strong><?php _e('Xili-dictionary tool to prepare translation','xili-dictionary') ?></strong></legend>
		<form action="" method="get">
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
			<input type="hidden" name="import-in-xd" value="true" />
			<input type="hidden" name="wpnonce" value="<?php echo wp_create_nonce('upload-xili-dictionary-'. $taxonomy ); ?>" />
		<?php
		if ( $result ) {
			echo '<div class="updated">';
			echo '<p><em>'.sprintf (__('Message : %s','xili-dictionary'), $result ).'</em></p>';

			if ( $quantities[3] != array() ) {
				echo '<p><strong>'.sprintf (__('%3$s terms of %1$s were just imported, <a href="%2$s">display those terms</a> in msg list of xili-dictionary.','xili-dictionary'), $taxonomy_obj->labels->name, 'edit.php?post_type='.XDMSG.'&amp;only_'.XDMSG."=".implode(',', $quantities[3] ), $quantities[0] + $quantities[1]) .'</strong></p>' ;

			}
			if ( $quantities[2] != array() ) {
				echo '<p><strong>'.sprintf (__('All %1$s terms are checked , <a href="%2$s">display those terms</a> in xili-dictionary.','xili-dictionary'), $taxonomy_obj->labels->name, 'edit.php?post_type='.XDMSG.'&amp;only_'.XDMSG."=".implode(',', $quantities[2] ) ) .'</strong></p>' ;
			}

			echo '<p style="text-align:right">'.sprintf (__('<a href="%2$s">Refresh</a> %3$s column of %1$s table.','xili-dictionary'), $taxonomy_obj->labels->name, admin_url(). 'edit-tags.php?taxonomy='.$taxonomy, __('Language','xili-dictionary') ) . '</p></div>';
		}
		?>
		<p><?php printf (__('%1$s terms can be imported inside xili-dictionary msgid list','xili-dictionary'), $taxonomy_obj->labels->name ) ?>
		<?php
		echo '&nbsp;&nbsp;';
		submit_button( sprintf(__('Import %1$s terms','xili-dictionary'), $taxonomy_obj->labels->name), 'xbutton', false, false, array( 'id' => 'xd-import' ) );
		?></p>
		</form>
		<?php
		if ( $this->taxlist != array() ) {
		?>
		<hr />
		<form action="" method="get" >
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
			<input type="hidden" name="see-in-xd" value="true" />
		<?php

		if ( $this->tax_msgid_list != array() ) { // build by rows

			//echo '<p>'.sprintf (__('To display the current above %1$s terms list, click this <a href="%2$s">link</a>','xili-dictionary'), $taxonomy_obj->labels->name, 'edit.php?post_type='.XDMSG.'&amp;only_'.XDMSG."=".implode(',', $msgid_list ) ) .'</p>' ;

			if ( isset( $_REQUEST[ 'see-in-xd']) ) {
				$url_redir = admin_url(). 'edit.php?post_type='.XDMSG.'&only_'.XDMSG."=".implode(',', $this->tax_msgid_list ) ;
				?>
<script type="text/javascript">
<!--
	window.location= <?php echo "'" . $url_redir . "'"; ?>;
//-->
</script>
<?php
			}
		}
		echo '<p>'.sprintf(__('In the term listed below, %d items are displayed (each has a name and a description) :', 'xili-dictionary'), count($this->taxlist)).'&nbsp;' ;


		if ( $this->tax_msgid_list != array() ) {

			submit_button( sprintf(__( 'Display these %s terms in xili-dictionary', 'xili-dictionary' ), $taxonomy_obj->labels->name ), 'xbutton', false, false, array( 'id' => 'xd-display' ) );
			echo '</p>';

		} else {
			echo '</p><p>'.sprintf(__('None of these %d terms are available in the msgid list of xili-dictionary. Click button above to populate dictionary before you will translate.', 'xili-dictionary'), count($this->taxlist)).'</p>';
		}

		?>
		</form>
		<?php } // if list not empty ?>

		</fieldset></div>
		<?php
	}

	/**
	 * Modify query to display only recent imported msgs array or adapt order_by
	 *
	 * @since 2.3.3
	 *
	 */
	function show_imported_msgs_in_xdmg_list ( $args = array() ) {
		global $wp_query;

		$query = $args->query;
		if ( is_array($query) ) {
			$r = $query;
		} else {
			parse_str( $query, $r );
		}

		if ( $r['post_type'] == XDMSG ) {

			if ( isset ( $r['only_'.XDMSG] ) ) {

				$wp_query->query_vars['post__in'] = explode(',', $r['only_'.XDMSG]);
				$wp_query->query_vars['meta_key'] = $this->msg_sort_slug;
				$wp_query->query_vars['orderby'] = "meta_value";
				if ( !isset ( $wp_query->query_vars['order'] ) ) $wp_query->query_vars['order'] = "asc";

			} elseif ( isset ( $r['orderby'] ) && $r['orderby'] == 'msgcontent' ) { // sort by content via meta msg_sort_slug
				$wp_query->query_vars['meta_key'] = $this->msg_sort_slug;
				$wp_query->query_vars['orderby'] = "meta_value";

			}
		}

	}

	function keywords_addQueryVar( $vars ) {
		$vars[] = 'only_'.XDMSG;

	return $vars ;
	}

	function add_content_in_taxonomy_edit_form ( $taxonomy ) {
		?>
		<?php // future features under Add New button
	}

	function xili_manage_tax_column ( $content, $name, $id ) {
		global $taxonomy;
		if( $name != TAXONAME )
			return $content; // to have more than one added column 2.8.1
		$this->taxlist[] = $id;
		$a = '';
		$ids = array();
		// check if in msgid
		$tax = get_term((int) $id , $taxonomy ) ;
		$result = $this->msgid_exists ( $tax->name );
		if ( $result != false ) {
			// $msgid_name_id
			$this->tax_msgid_list[] = $result[0];
			if ( get_post_status( $result[0] ) != 'trash' ) {
				$ids[] = $result[0];
			}
		}
		$result = $this->msgid_exists ( $tax->description );
		if ( $result != false ) {
			// $msgid_desc_id
			$this->tax_msgid_list[] = $result[0];
			if ( get_post_status( $result[0] ) != 'trash' ) {
				$ids[] = $result[0];
			}
		}
		if ( $ids != array() ) { // 2.11.2
			$a = sprintf ( '<a title="%1$s" href="%3$s">%2$s</a>' ,
				__('To display name and description of this term in msgid list','xili-dictionary' ), __('Display in XD','xili-dictionary' ), admin_url(). 'edit.php?post_type='.XDMSG.'&only_'.XDMSG."=".implode(',', $ids ) );
		}
		return $content . $a;
	}

	/**
	 * Add Origin selector in edit.php edit
	 *
	 * @since 2.0
	 *
	 */
	function restrict_manage_origin_posts () {
		if ( isset ( $_GET['post_type'] ) && $_GET['post_type'] == XDMSG ) {
			$listorigins = get_terms('origin', array('hide_empty' => false));
			if ( $listorigins != array() ) {
				$selected = "";
				if ( isset ( $_GET['origin'] ) ) {
					$selected = $_GET['origin'];
				}
				$dropdown_options = array(
						'taxonomy' => 'origin',
						'show_option_all' => __( 'View all origins', 'xili-dictionary' ),
						'hide_empty' => 0,
						'hierarchical' => 0,
						'show_count' => 0,
						'orderby' => 'name',
						'name' => 'origin',
						'selected' => $selected
					);
				wp_dropdown_categories( $dropdown_options );
			}
		}
	}


	/**
	 * Complete taxonomy edit screen (just under description)
	 *
	 * @since 2.0
	 *
	 */
	function show_translation_msgstr ( $tag, $taxonomy ) {

		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="description"><?php _e('Translated in', 'xili-dictionary'); ?></label></th>
			<td>
			<?php
			echo '<fieldset class="taxinmos" ><legend><em>'.__('Name').'</em> = '.$tag->name.'</legend>';
			$a = $this->is_msg_saved_in_localmos ( $tag->name, 'msgid', '', 'single' );
			echo $a[0];
			$ids = array();
			if ( current_user_can ('xili_dictionary_edit')) {

				$result = $this->msgid_exists ( $tag->name );
				if ( $result != false ) {
					// $msgid_desc_id
					$this->tax_msgid_list[] = $result[0];
					if ( get_post_status( $result[0] ) != 'trash' ) {
						$ids[] = $result[0];
					}
				}
			}

			echo '</fieldset><br /><fieldset class="taxinmos" ><legend><em>'.__('Description').'</em> = '.$tag->description.'</legend>';
			$a = $this->is_msg_saved_in_localmos ( $tag->description, 'msgid', '', 'single' );
			echo $a[0];
			if ( current_user_can ('xili_dictionary_edit')) {

				$result = $this->msgid_exists ( $tag->description );
				if ( $result != false ) {
					// $msgid_desc_id
					$this->tax_msgid_list[] = $result[0];
					if ( get_post_status( $result[0] ) != 'trash' ) {
						$ids[] = $result[0];
					}

				}
			}
			echo '</fieldset>';
			if ( $ids != array() ) {
				echo '<p><span class="button displaybbt">';
				printf ( __( '<a href="%s">Display term(s) in XD</a>', 'xili-dictionary' ) , admin_url(). 'edit.php?post_type='.XDMSG.'&only_'.XDMSG."=".implode(',', $ids ) );
				echo '</span></p>';
			}


			?>
			<p><em><?php _e( 'This list above gathers the translations of name and description saved in current local-xx_XX.mo files of the current theme.', 'xili-dictionary'); ?></em></p>
			</td>
		</tr>

		<?php
	}

	/**
	 * Add writer selector in edit.php edit
	 *
	 * @since 2.0
	 *
	 */
	function restrict_manage_writer_posts () {
		if ( isset ( $_GET['post_type'] ) && $_GET['post_type'] == XDMSG ) {
			$listwriters = get_terms('writer', array('hide_empty' => false));
			if ( $listwriters != array() ) {
				$selected = "";
				if ( isset ( $_GET['writer_name'] ) ) {
					$selected = $_GET['writer_name'];
				}
				$dropdown_options = array(
						'taxonomy' => 'writer',
						'show_option_all' => __( 'View all writers', 'xili-dictionary' ),
						'hide_empty' => 0,
						'hierarchical' => 1,
						'show_count' => 0,
						'orderby' => 'name',
						'name' => 'writer_name',
						'selected' => $selected
					);
				wp_dropdown_categories( $dropdown_options );
			}
		}
	}

	/**
	 * to fixes wp_dropdown_categories id value in option
	 * thanks to http://wordpress.stackexchange.com/questions/6066/query-custom-taxonomy-by-term-id
	 */
	function wpse6066_pre_get_posts( &$wp_query ) {

		if ( $wp_query->is_tax ) { ;
			if ( is_numeric( $wp_query->get( 'writer_name' ) ) ) {
			// Convert numberic terms to term slugs for dropdown

				$term = get_term_by( 'term_id', $wp_query->get( 'writer_name' ), 'writer' );

				if ( $term ) {
					$wp_query->set( 'writer_name', $term->slug );
				}
			}

			if ( is_numeric( $wp_query->get( 'origin' ) ) ) {

			// Convert numberic terms to term slugs for dropdown

				$term = get_term_by( 'term_id', $wp_query->get( 'origin' ), 'origin' );

				if ( $term ) {
					$wp_query->set( 'origin', $term->slug );
				}
			}
		}
	}

	/**
	 * display msg comments
	 *
	 * @param post ID
	 *
	 */
	function msg_status_comments ( $id ) {
		global $post;
		$type = get_post_meta ( $id, $this->msgtype_meta, true);
		// search msgid
		if ( $type == 'msgid' ) {
			$target_id = $id;
		} elseif ( $type == 'msgid_plural' ) {
			$temp_post = $this->temp_get_post ( $id );
			$target_id = $temp_post->post_parent;
		} else {
			$target_id = get_post_meta ( $id, $this->msgidlang_meta, true);
		}
		$for_bottom_box = array('link'=> '','state' => false );
		if ( $temp_post = $this->temp_get_post ( $target_id ) ) {

			$ctxt = get_post_meta ( $target_id, $this->ctxt_meta, true );
			if ( $ctxt != "" && $type != 'msgid' ) printf ( '<strong>ctxt:</strong> %s <br /><br />' , $ctxt );
			if ( $type == 'msgid' ) {
				if ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'addctxt' ) {
					?>
<label for="add_ctxt"><?php _e('Context','xili-dictionary') ; ?></label>
<input id="add_ctxt" name="add_ctxt" value="<?php echo $ctxt; ?>" style="width:80%" />
					<?php

				} else {
					if ( $ctxt != "" ) {
						printf ( '<strong>ctxt:</strong> %s <br /><br />', $ctxt );
						printf( __('&nbsp;<a href="%s" >Edit context</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=addctxt' );
					} else {
						if ($post->post_status != 'auto-draft') {
							// link to add ctxt
							printf( __('&nbsp;<a href="%s" >Create context</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=addctxt' );
						} else {
							echo '&nbsp;<em>'. sprintf(__('After saving this msgid as draft,%s it will be possible to add a context', 'xili-dictionary'), '<br />') . '</em>';
						}
					}
				}
			}
			// local or not
			$linktotax ='';

			$extracted_comments = get_post_meta ( $target_id, $this->msg_extracted_comments, true );
			if ( $extracted_comments != "" ) {

				$pattern = '/([^local\]].*?)from\s(.*?)\swith/';
				$matches = array();
				if ( 1 == preg_match($pattern, $extracted_comments, $matches) ) {

					$search = '';
					if ( $type == 'msgid' && false !== strpos( $extracted_comments, 'name from' ) )
						$search = '&s='.str_replace(' ', '+', $temp_post->post_content );

					$linktotax = sprintf('<a href="%1s" >%2s</a>', 'edit-tags.php?taxonomy='.$matches[2].'&post_type=post'.$search, sprintf(__('Return to %s list', 'xili-dictionary'), $matches[2] ));

				}
			}

			echo '<p>';

				if ( $extracted_comments != "" )
					printf ( __('Extracted comments: %s', 'xili-dictionary').'<br />', $extracted_comments );

				$translator_comments = get_post_meta ( $target_id, $this->msg_translator_comments, true );
				if ( $translator_comments != "") printf ( __('Translator comments: %s', 'xili-dictionary').'<br />', $translator_comments );
				$flags = get_post_meta ( $target_id, $this->msg_flags, true );
				if ( $flags != "") printf ( __('Flags: %s', 'xili-dictionary').'<br />', $flags );

			echo '</p>';
			if ( $type == 'msgstr' || $type == 'msgstr_0' ) {
				$translator_comments = get_post_meta ( $id, $this->msg_translator_comments, true );
				//if ( $translator_comments != "") printf ( __('Msgstr Translator comments: %s', 'xili-dictionary').'<br />', $translator_comments );

				?>
<label for="add_translator_comments"><?php _e('msgstr Translator comments','xili-dictionary') ; ?></label>
<input id="add_translator_comments" name="add_translator_comments" value="<?php echo $translator_comments; ?>" style="width:80%" />
				<?php
			}


			$lines = $temp_post->post_excerpt;
			if ( $lines != "") {
				echo '<p>';
				printf ( __('Lines: %s', 'xili-dictionary').'<br />', $lines );
				echo '</p>';
			}
			if (current_user_can ('xili_dictionary_admin')) {
				echo '<p><strong>'.sprintf(__('Return to <a href="%s" title="Go to msg list">msg list</a>','xili-dictionary'), $this->xd_settings_page).'</strong> '.$linktotax.'</p>';
			} // 2.3.2
			//echo ( $this->create_line_lang != "" ) ? '<p><strong>'.$this->create_line_lang.'</strong></p>' : "-";


			if ( $type == 'msgid' ) {
				if ( ( $extracted_comments == "" ) || ( $extracted_comments != "" && false === strpos( $extracted_comments, $this->local_tag .' ' ) ) ) {

					$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=setlocal', 'xd-setlocal' ) ;

					$for_bottom_box['link'] = sprintf( __('Set in theme (<a href="%s" >set local</a>)', 'xili-dictionary'), $nonce_url );

				} else {
					$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=unsetlocal', 'xd-setlocal' ) ;

					$for_bottom_box['link'] = sprintf( __('Set in local (<a href="%s" >unset</a>)', 'xili-dictionary'), $nonce_url );
					$for_bottom_box['state'] = true; // false by default
				}
			}


		} else {
			printf ( __('The msgid (%d) was deleted. The msg series must be recreated and commented.','xili-dictionary' ), $target_id );
			if (current_user_can ('xili_dictionary_admin')) {
				echo '<p><strong>'.sprintf(__('Return to <a href="%s" title="Go to msg list">msg list</a>','xili-dictionary'), $this->xd_settings_page).'</strong></p>';
			}

		}
		return $for_bottom_box ;
	}

	/**
	 * @since
	 * call after saved xdmsg
	 *
	 * updated to insert flags according msgid content
	 *
	 */
	function update_msg_comments ( $post_id ) {
		if ( get_post_type( $post_id ) == XDMSG ) {
			// only visible if msgstr
			$translator_comments = ( isset ( $_POST['add_translator_comments'] )) ? $_POST['add_translator_comments'] : "" ;
			if ( '' != $translator_comments ) {
				update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
			}
			// add_ctxt

			if ( isset ( $_POST['add_ctxt'] ) ) {
				$ctxt = $_POST['add_ctxt'] ;
				if ( '' != $ctxt ) {
					update_post_meta ( $post_id, $this->ctxt_meta, $ctxt );
				} else {
					delete_post_meta ( $post_id, $this->ctxt_meta);
				}
			}
			$thepost = $this->temp_get_post ( $post_id ) ;
			update_post_meta ( $post_id, $this->msg_sort_slug, sanitize_title ( $thepost->post_content ) ); // 2.3.3

			$type = get_post_meta ( $post_id, $this->msgtype_meta, true);
			if ( $type == 'msgid' ) {
				$this->update_msgid_flags ( $post_id, $thepost->post_content );
			}
		}
	}

	function update_msgid_flags ( $post_id, $post_content ) {
		// analyse $thepost->post_content %d %s %2$s
		if ( preg_match('/(%d|%s|%\S\$s)*/', $post_content, $matches) ) {

			$flags = get_post_meta ( $post_id, $this->msg_flags, true );
			if ( $flags ) {
				$flags = explode (',' , $flags );
				if ( !in_array ('php-format', $flags ) ) $flags[] = 'php-format';
				$flags = implode (',' , $flags );
			} else {
				$flags = 'php-format' ;
			}
			update_post_meta ( $post_id, $this->msg_flags, $flags );
		} else {
			$flags = get_post_meta ( $post_id, $this->msg_flags, true );
			if ( $flags ) {
				$flags = explode (',' , $flags );
				if ( in_array ('php-format', $flags ) ) {
					$key = array_search('php-format', $flags);
					unset ( $flags [$key]) ;
					$flags = implode (',' , $flags );
					update_post_meta ( $post_id, $this->msg_flags, $flags );
				}
			}
		}
	}


	/**
	 * msg dashboard left
	 *
	 * @since 2.0
	 *
	 */
	function msg_status_display ( $id ) {
		global $post;
		$spanred = '<span class="alert">';
		$spanend = '</span>';

		$type = get_post_meta ( $id, $this->msgtype_meta, true);
		// search msgid
		if ( $type == 'msgid' ) {
			$msgid_id = $id;
		} elseif ( $type == 'msgid_plural' ) {
			$temp_post_msg_id_plural = $this->temp_get_post ( $id );
			$msgid_id = $temp_post_msg_id_plural->post_parent;
			$temp_post_msg_id = $this->temp_get_post ( $msgid_id );
		} else {
			$msgid_id = get_post_meta ( $id, $this->msgidlang_meta, true);
		}

		if ( $temp_post_msg_id = $this->temp_get_post ( $msgid_id ) ) {
			if ( defined ('WP_DEBUG') && WP_DEBUG == true ) {
				//echo '<div class="msg-saved" >';
				//printf( __('%s saved as: <em>%s</em>', 'xili-dictionary'), $this->msg_str_labels[$type], $post->post_content );
				//echo '</div>';
			}
			$res = get_post_meta ( $msgid_id, $this->msgchild_meta, false );
			$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();

			$res = get_post_meta ( $msgid_id, $this->msglang_meta, false );
			$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();

			if ( isset ($_GET['msgaction'] ) && isset ($_GET['langstr']) ) { // action to create child and default line - single or plural...
				check_admin_referer( 'xd-langstr' );
				$target_lang = $_GET['langstr'];
				if ( $_GET['msgaction'] == 'msgstr' && !isset( $thelangs['msgstrlangs'][$target_lang] ) ) {
				// create post
					if ( !isset ( $thechilds['msgid']['plural'] ) ) {

						$msgstr_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s:', 'xili-dictionary'), $target_lang ). ' ' . $temp_post_msg_id->post_content , null, 'msgstr' , 0 );
						wp_set_object_terms( $msgstr_post_ID, $this->target_lang($target_lang), TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['msgstr'] = $msgstr_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_id );

						$translated_post_ID = $msgstr_post_ID;
						//printf( 'msgstr created in %s <br/>', $target_lang ) ;

					} else {
						// create msgstr_0
						$msgstr_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s (msgstr[0]): ', 'xili-dictionary'), $target_lang ). ' ' .$temp_post_msg_id->post_content , null, 'msgstr_0' , 0 );
						wp_set_object_terms( $msgstr_post_ID, $this->target_lang($target_lang), TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['msgstr_0'] = $msgstr_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_id );

						$translated_post_ID = $msgstr_post_ID;
						//printf( 'msgstr[0] created in %s <br/>', $target_lang ) ;

						// create msgstr_1
						$temp_post_msg_id_plural = $this->temp_get_post ( $thechilds['msgid']['plural'] );
						$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
						$msgstr_1_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s (msgstr[1]): ', 'xili-dictionary'), $target_lang ). ' ' . $content_plural , null, 'msgstr_1' , $msgstr_post_ID );
						wp_set_object_terms( $msgstr_1_post_ID, $this->target_lang($target_lang), TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['plural'][1] = $msgstr_1_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_1_post_ID, $this->msgidlang_meta, $msgid_id );

						//printf( 'msgstr[1] created in %s <br/>', $target_lang ) ;
					}
					// redirect


					$url_redir = admin_url().'post.php?post='.$translated_post_ID.'&action=edit';

				?>
	<script type="text/javascript">
	<!--
		window.location= <?php echo "'" . $url_redir . "'"; ?>;
	//-->
	</script><br />
<?php
				//}
				}
			} elseif ( isset ($_GET['msgaction']) && $_GET['msgaction'] == 'msgid_plural' && !isset( $thelangs['msgstrlangs'] ) ) {
				check_admin_referer( 'xd-plural' );
					$msgid_plural_post_ID = $this->insert_one_cpt_and_meta ( __('XD say id to plural: ', 'xili-dictionary').$temp_post_msg_id->post_content , null, 'msgid_plural' , $msgid_id );
					$res = get_post_meta ( $msgid_id, $this->msgchild_meta, false );
					$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				$url_redir = admin_url().'post.php?post='.$msgid_plural_post_ID.'&action=edit';
	//2.3		?>
	<script type="text/javascript">
	<!--
		window.location= <?php echo "'" . $url_redir . "'"; ?>;
	//-->
	</script><br />
<?php

			} elseif ( $type == 'msgid' && isset ($_GET['msgaction']) && $_GET['msgaction'] == 'setlocal' ) {
				check_admin_referer( 'xd-setlocal' );
				$extracted_comments = get_post_meta ( $msgid_id, $this->msg_extracted_comments, true );
				$extracted_comments = $this->local_tag .' '. $extracted_comments;
				update_post_meta ( $msgid_id, $this->msg_extracted_comments, $extracted_comments );

			} elseif ( $type == 'msgid' && isset ($_GET['msgaction']) && $_GET['msgaction'] == 'unsetlocal' ) {
				check_admin_referer( 'xd-setlocal' );
				$extracted_comments = get_post_meta ( $msgid_id, $this->msg_extracted_comments, true );
				$extracted_comments = str_replace ( $this->local_tag .' ', '', $extracted_comments);
				update_post_meta ( $msgid_id, $this->msg_extracted_comments, $extracted_comments );
			}


			// display current saved content

			//if ( $type != "msgid" ) {
				$line = __('msgid:', 'xili-dictionary');
				$line .= '&nbsp;<strong>'. htmlspecialchars($temp_post_msg_id->post_content ) . '</strong>' ;
				if ( $post->ID != $msgid_id ) {
					$line .= sprintf( __('( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'),'post.php?post='.$msgid_id.'&action=edit', $msgid_id, __('Edit') ) ;
				} else {
					$line .= '<br />';
				}
				$this->hightlight_line ( $line, $type, 'msgid' );
			//}
			if ( isset ( $thechilds['msgid']['plural'] ) ) {
				$post_status = get_post_status ( $thechilds['msgid']['plural'] ) ;
				$line = "";
				if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
				$line .= '<span class="msgid_plural">'. __('msgid_plural:', 'xili-dictionary') . '</span>&nbsp;';
				if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;
				$temp_post_msg_id_plural = $this->temp_get_post ( $thechilds['msgid']['plural'] );
				$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
				$line .= '<strong>'. $content_plural . '</strong> ' ;
				if ( $post->ID != $thechilds['msgid']['plural'] )
					$line .= sprintf( __('( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'),'post.php?post='.$thechilds['msgid']['plural'].'&action=edit', $thechilds['msgid']['plural'], __('Edit') ) ;
				$this->hightlight_line ( $line, $type, 'msgid_plural' );


			} else {
				//2.3
				if ( $post->post_status !='auto-draft' && !isset ( $thelangs['msgstrlangs'] ) && !isset ( $thechilds['msgid']['plural'] ) ) { // not yet translated

					$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=msgid_plural', 'xd-plural' ) ;
					printf( __('&nbsp;<a href="%s" >Create msgid_plural</a>', 'xili-dictionary'), $nonce_url );
					echo '<br />';
				}
			}

			// display series
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			if (isset ( $thelangs['msgstrlangs'] ) ) {
				$translated_langs = array ();
				echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
				_e( 'translated in', 'xili-dictionary');
				echo '</th><th>‟msgstr”</th></tr></thead><tbody>';
				foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

					$strid = 0;
					if ( isset ( $msgtr['msgstr'] ) ) {
						$strid = $msgtr['msgstr'] ;
						$str_plural = false ;
						$translated_langs[] = $curlang;
						$typeref = 'msgstr';
					} elseif ( isset ( $msgtr['msgstr_0'] ) ) {
						$strid = $msgtr['msgstr_0'] ;
						$str_plural = true ;
						$translated_langs[] = $curlang; // detect content empty
						$typeref = 'msgstr_0';
					}

					if ( $strid != 0 ) {
						$target_lang = implode ( ' ', wp_get_object_terms( $id, TAXONAME, $args = array( 'fields' => 'names')) );
						echo '<tr class="lang-'.strtolower($curlang).'" ><th><span>';
						printf( '%s : ', $curlang );
						echo '</span></th><td>';
						$temp_post = $this->temp_get_post ( $strid );
						$content = htmlspecialchars( $temp_post->post_content );
						$line = "";
						if ( $str_plural ) $line .= "[0] ";

						$line .= '‟<strong>'. $content . '</strong>”' ;
						$post_status = get_post_status ( $strid );
						if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
						if ( $post->ID != $strid ) {
							$line .= sprintf( ' ( <a href="%s" title="link to:%d">%s</a> )<br />', 'post.php?post='.$strid.'&action=edit', $strid, __('Edit') ) ;
						} else {
							$line .= '<br />';
						}
						if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;

						$this->hightlight_line_str ( $line, $type, $typeref, $curlang, (int)$id ); // now id

						if ( $str_plural ) {
							$res = get_post_meta ( $strid, $this->msgchild_meta, false );
							$strthechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
							foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
								$temp_post = $this->temp_get_post ( $strchildid );
								$content = htmlspecialchars( $temp_post->post_content );
								$line = "";
								$post_status = get_post_status ( $strchildid ); // fixed 2.1
								if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
								$line .= sprintf ( '[%s] ', $key );
								if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;
								if ( $post->ID != $strchildid ) {
									$line .= sprintf ( '‟<strong>%s</strong>” ( %s )', $content, '<a href="post.php?post='.$strchildid.'&action=edit" title="link to:'.$strchildid.'">'.__('Edit').'</a>' ) ;
								} else {
									$line .= sprintf ( '‟<strong>%s</strong>”', $content );
								}
								$this->hightlight_line_str ( $line, $type, 'msgstr_'.$key, $curlang, (int)$id );
								echo '<br />';
							}
										// if possible against current lang add links - compare to count of $strthechilds['msgstr']['plural']

						}
						echo '</td></tr>';
					}

				} ///


				$this->create_line_lang = "";
				if ( count ($translated_langs) != count ($listlanguages) ) {
							//echo '<br />';
					$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
					foreach ( $listlanguages as $tolang ) {
						if ( !in_array ( $tolang->name , $translated_langs ) ) {
							$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name, 'xd-langstr' ) ;
							$this->create_line_lang .= sprintf( '&nbsp; <a class="lang-'. strtolower($tolang->name).'" href="%s" >'.$tolang->name.'</a>', $nonce_url );
							echo '<tr class="lang-'.strtolower($tolang->name).'" ><th><span>';
							printf( '%s : ', $tolang->name );
							echo '</span></th><td>';
							printf( '&nbsp; <a class="lang-'. strtolower($tolang->name).'" href="%s" >'.__('Create and edit', 'xili-dictionary').'</a>', $nonce_url );
							echo '</td></tr>';
						}

					}
				}

				echo '</tbody></table>';

			} else {
				$this->create_line_lang = "";
				if ( !isset ($_POST['msgaction'] ) || ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'msgid_plural' ) ) {
					_e( 'not yet translated.', 'xili-dictionary'); echo '&nbsp;'; printf (__( 'Status: %s', 'xili-dictionary'), $post -> post_status );
					if ( $post -> post_status != 'auto-draft' ) {
						echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
						_e( 'Translation in', 'xili-dictionary');
						echo '</th><th>‟msgstr”</th></tr></thead><tbody>';


						$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
						foreach ( $listlanguages as $tolang ) {
							$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name, 'xd-langstr' ) ;
							$this->create_line_lang .= sprintf( '&nbsp; <a class="lang-'. strtolower( $tolang->name).'" href="%s" >'.$tolang->name.'</a>', $nonce_url );
							echo '<tr class="lang-'.strtolower($tolang->name).'" ><th><span>';
								printf( '%s : ', $tolang->name );
								echo '</span></th><td>';
								printf( '&nbsp; <a class="lang-'. strtolower($tolang->name).'" href="%s" >'.__('Create and edit', 'xili-dictionary').'</a>', $nonce_url );
								echo '</td></tr>';

						}
						echo '</tbody></table>';
					}
				}
			}
		} else {

			printf ( __('The msgid (%d) was deleted. The msg series must be recreated.','xili-dictionary' ), $msgid_id );
		}
	}

	function hightlight_line ( $line, $cur_type, $type ) {
		if ( $cur_type == $type) {
			echo '<span class="editing msgidstyle">'. $line .'</span>';
		} else {
			echo '<span class="msgidstyle">'. $line .'</span>';
		}
	}

	function hightlight_line_str ( $line, $cur_type, $type, $cur_lang, $lang_or_id ) {
		if ( is_int( $lang_or_id ) ) {
			$lang = ( is_object ( $this->cur_lang( $lang_or_id ) ) ) ? $this->cur_lang( $lang_or_id )->name : $this->cur_lang( $lang_or_id ); // false if msgid
		} else {
 			$lang = $lang_or_id;
		}

		if ( $cur_type == $type && $cur_lang == $lang ) {
			echo '<span class="editing msgstrstyle">'.$line.'</span>';
		} else {
			echo '<span class="msgstrstyle">'. $line .'</span>';
		}
	}

	/**
	 * display msg series linked together
	 *
	 * @param post ID, display (true for single edit)
	 *
	 */
	function msg_link_display ( $id , $display = false, $thepost = null ) {

			if ( $thepost != null ) {
				$post = $thepost ;
			} else {
				global $post ;
			}

			$spanred = '<span class="alert">';
			$spanend = '</span>';
		// type
			$type = get_post_meta ( $id, $this->msgtype_meta, true);

			$res = get_post_meta ( $id, $this->msgchild_meta, false );
			$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();

			$res = get_post_meta ( $id, $this->msglang_meta, false );
			$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();

			if ( $type == 'msgid' ) {
				$ctxt = get_post_meta ( $id, $this->ctxt_meta, true );
				if ( $post->post_status == "trash" ) echo $spanred;
				if ( $display ) {
					echo '<div class="msg-saved" >';
					printf( __('msgid saved as: <em>%s</em>', 'xili-dictionary'), ( $post->post_content ) );
					echo '</div>';
				} else {
					echo '<mark><strong>msgid</strong></mark>';
				}
				if ( $post->post_status == "trash" ) echo $spanend;
				echo '<br />';
				if ( $ctxt != "" && !$display ) printf ( 'ctxt: %s <br />' , $ctxt );

				if ( isset ( $thechilds['msgid']['plural'] ) ) {
					$post_status = get_post_status ( $thechilds['msgid']['plural'] ) ;
					if ( !$display ) {
						if ( $post_status == "trash" || $post_status === false ) echo $spanred;
						printf( __('has plural: <a href="%s" >%d</a><br />', 'xili-dictionary'),'post.php?post='.$thechilds['msgid']['plural'].'&action=edit', $thechilds['msgid']['plural'] ) ;
						if ( $post_status == "trash" || $post_status === false ) echo $spanend;
					} else {
						if ( $post_status == "trash" || $post_status === false ) echo $spanred;
						_e('has plural:', 'xili-dictionary'); echo '&nbsp;';
						if ( $post_status == "trash" || $post_status === false ) echo $spanend;
						$temp_post = $this->temp_get_post ( $thechilds['msgid']['plural'] );
						$content_plural = htmlspecialchars( $temp_post->post_content );
						echo '<strong>'. $content_plural . '</strong> ' ;
						printf( __('( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'),'post.php?post='.$thechilds['msgid']['plural'].'&action=edit', $thechilds['msgid']['plural'], __('Edit') ) ;
					}
				} else {
					if ( $display && !isset ( $thelangs['msgstrlangs'] ) && !isset ( $thechilds['msgid']['plural'] ) ) { // not yet translated

						printf( __('&nbsp;<a href="%s" >Create msgid_plural</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=msgid_plural' );
						echo '<br />';
					}
				}
				$res = get_post_meta ( $id, $this->msglang_meta, false );
				$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				// action to create child and default line - single or plural...
				if ( isset ($_GET['msgaction'] ) && isset ($_GET['langstr']) && $display) {
					$target_lang = $_GET['langstr'];
					if ( $_GET['msgaction'] == 'msgstr' && !isset( $thelangs['msgstrlangs'][$target_lang] ) ) {
					// create post
						if ( !isset ( $thechilds['msgid']['plural'] ) ) {

							$msgstr_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s: ', 'xili-dictionary'), $target_lang ) .' '.$post->post_content , null, 'msgstr' , 0 );
							wp_set_object_terms( $msgstr_post_ID, $this->target_lang($target_lang), TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['msgstr'] = $msgstr_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $id );

							sprintf( 'msgstr created in %s <br/>', $target_lang ) ;

						} else {
							// create msgstr_0
							$msgstr_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s (msgstr[0]): ', 'xili-dictionary'), $target_lang ) .' '. $post->post_content , null, 'msgstr_0' , 0 );
							wp_set_object_terms( $msgstr_post_ID, $this->target_lang($target_lang), TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['msgstr_0'] = $msgstr_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $id );

							sprintf( 'msgstr[0] created in %s <br/>', $target_lang ) ;

							// create msgstr_1
							$msgstr_1_post_ID = $this->insert_one_cpt_and_meta ( sprintf(__('XD say to translate in %s (msgstr[1]): ', 'xili-dictionary'), $target_lang ) .' '. $content_plural , null, 'msgstr_1' , $msgstr_post_ID );
							wp_set_object_terms( $msgstr_1_post_ID, $this->target_lang($target_lang), TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['plural'][1] = $msgstr_1_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_1_post_ID, $this->msgidlang_meta, $msgid_id );

							sprintf( 'msgstr[1] created in %s <br/>', $target_lang ) ;
						}
					} elseif ( $_GET['msgaction'] == 'msgid_plural' && !isset( $thelangs['msgstrlangs'][$target_lang] ) ) {

						$msgid_plural_post_ID = $this->insert_one_cpt_and_meta ( __('XD say id to plural: ', 'xili-dictionary').$post->post_content , null, 'msgid_plural' , $id );

					}
				}
				$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
				if ( isset ( $thelangs['msgstrlangs'] ) ) {
					//$thelangs['msgstrlangs'][$curlang]['msgstr'] = $msgstr_post_ID;

					$translated_langs = array ();
					if ( $display ) {
						echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
						_e( 'translated in', 'xili-dictionary');
						echo '</th><th>msgstr</th></tr></thead><tbody>';
					} else {
						echo ( __( 'translated in', 'xili-dictionary').':<br />');
					}
					foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

						$strid = 0;
						if ( isset ( $msgtr['msgstr'] ) ) {
							$strid = $msgtr['msgstr'] ;
							$str_plural = false ;
							$translated_langs[] = $curlang;
						} elseif ( isset ( $msgtr['msgstr_0'] ) ) {
							$strid = $msgtr['msgstr_0'] ;
							$str_plural = true ;
							$translated_langs[] = $curlang; // detect content empty
						}

						if ( $strid != 0 ) {
							if ( !$display ) {
							// get strid status
								$post_status = get_post_status ( $strid );
								if ( $post_status == "trash" || $post_status === false ) echo $spanred;
								printf( '- %s : <a href="%s" >%d</a><br />', $curlang, 'post.php?post='.$strid.'&action=edit', $strid ) ;
								if ( $post_status == "trash" || $post_status === false ) echo $spanend;
							} else {
								echo '<tr><th>';
								printf( '%s : ', $curlang );
								echo '</th><td>';
								$temp_post = $this->temp_get_post ( $strid );
								$content = htmlspecialchars( $temp_post->post_content );

								if ( $str_plural ) echo "[0] ";

								echo '<strong>'. $content . '</strong>' ;
								$post_status = get_post_status ( $strid );
								if ( $post_status == "trash" || $post_status === false ) echo $spanred;
									printf( ' ( <a href="%s" title="link to:%d">%s</a> )<br />', 'post.php?post='.$strid.'&action=edit', $strid, __('Edit') ) ;
								if ( $post_status == "trash" || $post_status === false ) echo $spanend;

								if ( $str_plural ) {
									$res = get_post_meta ( $strid, $this->msgchild_meta, false );
									$strthechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
									foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
										$temp_post = $this->temp_get_post ( $strchildid );
										$content = htmlspecialchars( $temp_post->post_content );
										$post_status = get_post_status ( $strchildid );
										if ( $post_status == "trash" || $post_status === false ) echo $spanred;
										printf ( '[%s] ', $key );
										if ( $post_status == "trash" || $post_status === false ) echo $spanend;
										printf ( '<strong>%s</strong> ( %s )<br />', $content, '<a href="post.php?post='.$strchildid.'&action=edit" title="link to:'.$strchildid.'">'.__('Edit').'</a>' ) ;

									}
									// if possible against current lang add links - compare to count of $strthechilds['msgstr']['plural']

								}
								echo '</td></tr>';
							}
						}

					}
					if ( $display ) echo '</tbody></table>';
					$this->create_line_lang = "";
					if ( $display && ( count ($translated_langs) != count ($listlanguages) ) ) {
						//echo '<br />';
						$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
						foreach ( $listlanguages as $tolang ) {
							if ( !in_array ( $tolang->name , $translated_langs ) ) {
								$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name, 'xd-langstr' ) ;
								$this->create_line_lang .= sprintf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', $nonce_url );
							}
						}
					}

				} else { // no translation
					if ( !isset ($_POST['msgaction'] ) || ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'msgid_plural' ) ) {
						_e( 'not yet translated.', 'xili-dictionary');
						echo '&nbsp';
						if ( $display ) {
							_e('Create msgstr in: ', 'xili-dictionary');

							foreach ( $listlanguages as $tolang ) {
								$nonce_url = wp_nonce_url ('post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name, 'xd-langstr' ) ;
								printf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', $nonce_url );
							}
						}
					}
				}


			} elseif ( $type != '' ) {

				$msgid_ID = get_post_meta ( $id, $this->msgidlang_meta , true);




				if ( $display && ( $type == 'msgid_plural' || ( false !== strpos( $type, 'msgstr_' ) && substr( $type, -1 ) !='0') ) ) {
					$temp_post = $this->temp_get_post ( $post->post_parent );
					$content = htmlspecialchars( $temp_post->post_content ) ;
					$target_lang = implode ( ' ', wp_get_object_terms( $id, TAXONAME, $args = array( 'fields' => 'names')) );
					$is_plural = true;
				} elseif ( $display ) {
					$temp_post = $this->temp_get_post ( $msgid_ID );
					$content = htmlspecialchars( $temp_post->post_content ) ;
					$target_lang = implode ( ' ', wp_get_object_terms( $id, TAXONAME, $args = array( 'fields' => 'names')) );
					$is_plural = false;
				}

				$span_msgid = ( get_post_status ( $msgid_ID ) == "trash" || get_post_status ( $msgid_ID ) === false ) ;
				$span_parent = ( get_post_status ( $post->post_parent ) == "trash" || get_post_status ( $post->post_parent ) === false ) ;

				if ( $display ) {
					echo '<div class="msg-saved" >';
					printf( __('%s saved as: <em>%s</em>', 'xili-dictionary'), $this->msg_str_labels[$type], $post->post_content );
					echo '</div>';
				}

				switch ( $type ) {
					case 'msgid_plural':

						if ( $span_parent ) echo $spanred ;
						if ( $display ) {
							printf( __('msgid plural of: <strong>%s</strong> ( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'), $content,'post.php?post='.$post->post_parent.'&action=edit', $post->post_parent, __('Edit') );
						} else {
							printf( __('msgid plural of: <a href="%s" >%d</a><br />', 'xili-dictionary'),'post.php?post='.$post->post_parent.'&action=edit',$post->post_parent ) ;
						}
						if ( $span_parent ) echo $spanend ;


						break;
					case 'msgstr':
						if ( $display ) echo '<strong>'.$target_lang."</strong> translation of: <strong>" . $content . '</strong> ';
						if ( $span_msgid ) echo $spanred ;
						if ( $display ) {
							printf( __('( <a href="%s" title = "link of:%d">%s</a> )<br />', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID, __('Edit') );
						} else {

							printf( __('msgstr of: <a href="%s" >%d</a><br />', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID );
						}
						if ( $span_msgid ) echo $spanend ;

						break;

					default:
						if ( false !== strpos( $type, 'msgstr_' ) ) {
							$indices = explode ('_', $type);
							$indice = $indices[1];
							$edit_id = ( $indice == 0 ) ? $msgid_ID : $post->post_parent ;

							if ( $display ) {
								if ( $is_plural ) {
									printf(__( '<strong>%s</strong> plural of: <strong>%s</strong>( <a href="%s" title="link to:%d">%s</a> )<br />', ''),$target_lang, $content, 'post.php?post='.$edit_id.'&action=edit' , $edit_id, __('Edit') );
								} else {
									printf(__( '<strong>%s</strong> translation of: <strong>%s</strong>( <a href="%s" title="link to:%d">%s</a> )<br />', ''),$target_lang, $content, 'post.php?post='.$edit_id.'&action=edit' , $edit_id, __('Edit') );
								}
							} else {
								if ( $indice == 0 ) {
									if ( $span_msgid ) echo $spanred ;
									printf( __('msgstr of: <a href="%s" >%d</a><br />', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID );
									if ( $span_msgid ) echo $spanend ;
								} else {
									if ( $span_parent ) echo $spanred ;
									printf( __('msgstr[%d] plural of: <a href="%s" >%d</a><br />', 'xili-dictionary'), $indice, 'post.php?post='.$post->post_parent.'&action=edit', $post->post_parent ) ;
									if ( $span_parent ) echo $spanend ;
								}
							}
							if ( $display && $indice > 0) {
								printf(__('go to <a href="%s" title="link to:%d">msgid</a>', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID ) ;
							}
						}
				}

			}
		return $type;
	}

	function temp_get_post ( $post_id ) {
		global $wpdb ;
		$res = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d LIMIT 1", $post_id));
		if ( $res && !is_wp_error($res) )
			return $res;
		else
			return false;
	}

	/**
	 * unset autosave for msg
	 * @since 2.0
	 */
	function auto_save_unsetting() {
		global $hook_suffix, $post ;
		$type = '';
		if ( isset($_GET['post_type']) )
			$type = $_GET['post_type'];

		if ( ( $hook_suffix == 'post-new.php' && $type == XDMSG ) || ( $hook_suffix == 'post.php' && $post->post_type == XDMSG )) {

						wp_dequeue_script('autosave');

		}
	}

	/**
	 * Reset values when theme was changed... updated by previous function
	 * @since 1.0.5
	 */
	function xd_theme_switched ($theme) {
		$this->xili_settings['langs_folder'] ="unknown";
		/* to force future search in new theme */
		update_option('xili_dictionary_settings', $this->xili_settings);
	}

	/**
	 * @since 1.3.0 for js in tools list
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_script( 'datatables-v10'  );
	}

	function admin_enqueue_styles() {
		wp_enqueue_style( 'table_style-v10' ); // style of js table
	}

	function admin_init() {
		$suffix = ( defined('SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		/* Register our script. Same as in XTT loaded before if active */
		wp_register_script( 'datatables-v10', plugins_url('js/jquery.dataTables'.$suffix.'.js', __FILE__ ) , array( 'jquery' ), '1.10.7', true );

		wp_register_style( 'table_style-v10', plugins_url('/css/jquery.dataTables'.$suffix.'.css', __FILE__ ), array(), XILIDICTIONARY_VER, 'screen' );
	}

	/**
	 * add admin menu and associated pages
	 */
	function dictionary_menus_pages() {

		$this->admin_menus(); // moved here from add_action
		$this->thehook = add_submenu_page( 'edit.php?post_type='.XDMSG, __('Xili Dictionary','xili-dictionary'), __('Tools, Files po mo','xili-dictionary'), 'xili_dictionary_admin', 'dictionary_page', array(&$this,'xili_dictionary_settings') );
		add_action( "admin_head-".$this->thehook, array(&$this,'modify_menu_highlight' ));
		add_action('load-'.$this->thehook, array(&$this,'on_load_page'));

		add_action( 'admin_print_scripts-'.$this->thehook, array(&$this,'admin_enqueue_scripts') );
		add_action( 'admin_print_styles-'.$this->thehook, array(&$this,'admin_enqueue_styles') );



		// Add to end of admin_menu action function
		global $submenu;
		$submenu['edit.php?post_type='.XDMSG][5][0] = __('Msg list','xili-dictionary'); // sub menu
		$submenu['edit.php?post_type='.XDMSG][5][1] = 'edit_posts'; // sub menu
		$submenu['edit.php?post_type='.XDMSG][5][2] = 'edit.php?post_type=xdmsg'; // added for first activation
		$post_type_object = get_post_type_object(XDMSG);
		$post_type_object->labels->name = __('XD Msg list','xili-dictionary'); // title list screen

		$this->insert_news_pointer ( 'xd_new_version' ); // pointer in menu for updated version
		add_action( 'admin_print_footer_scripts', array(&$this, 'print_the_pointers_js') );

	}

	function on_load_page() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');

			add_meta_box('xili-dictionary-sidebox-style', __('XD settings','xili-dictionary'), array(&$this,'on_sidebox_settings_content'), $this->thehook , 'side', 'low'); // low to be at end 2.3.1
			add_meta_box('xili-dictionary-sidebox-info', __('Info','xili-dictionary'), array(&$this,'on_sidebox_info_content'), $this->thehook , 'side', 'low');
			add_meta_box('xili-dictionary-sidebox-message', __('Message','xili-dictionary'), array(&$this,'on_sidebox_message_content'), $this->thehook , 'side', 'low');
			add_meta_box('xili-dictionary-sidebox-mail', __('Mail & Support','xili-dictionary'), array(&$this,'on_sidebox_mail_content'), $this->thehook , 'normal', 'low');

	}

	/**
	 * Add action link(s) to plugins page
	 *
	 * @since 0.9.3
	 * @author MS
	 * @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link and scripts@schloebe.de
	 */
	function xilidict_filter_plugin_actions($links, $file){
		static $this_plugin;

		if (!$this_plugin ) $this_plugin = plugin_basename(__FILE__);

		if ($file == $this_plugin ) {
			$settings_link = '<a href="'.$this->xd_settings_page.'">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links); // before other links
		}
		return $links;
	}

	function init_textdomain() {
	/*multilingual for admin pages and menu*/

		if ( class_exists('xili_language') ) {
			global $xili_language ;
			$langs_folder = $xili_language->xili_settings['langs_folder']; // set by override_load_textdomain filter
			if ( $this->xili_settings['langs_folder'] != $langs_folder ) {
				$this->xili_settings['langs_folder'] = $langs_folder ;
				update_option('xili_dictionary_settings', $this->xili_settings);
			}
			if ( is_child_theme() ) {
				$parent_langs_folder = $xili_language->xili_settings['parent_langs_folder']; // set by override_load_textdomain filter
				if ( $this->xili_settings['parent_langs_folder'] != $parent_langs_folder ) {
					$this->xili_settings['parent_langs_folder'] = $parent_langs_folder ;
					$this->parent_langs_folder = $parent_langs_folder ;
					update_option('xili_dictionary_settings', $this->xili_settings);
				}
			}
		} else {
			if ( file_exists( $this->active_theme_directory ) ) // when theme was unavailable
				$this->find_files($this->active_theme_directory, '/^.*\.(mo|po|pot)$/', array(&$this,'searchpath'));
			if ( is_child_theme() ) {
				if ( file_exists( get_template_directory() ) ) // when theme was unavailable
					$this->find_files( get_template_directory(), '/^.*\.(mo|po|pot)$/', array(&$this,'searchpathparent'));
			}
		}
	}

	/* call by findfiles */
	function searchpath($path, $filename) {
		$langs_folder = str_replace($this->active_theme_directory, '', $path); // updated 1.2.0
		if ( $this->xili_settings['langs_folder'] != $langs_folder ) {
			$this->xili_settings['langs_folder'] = $langs_folder ;
			$this->langs_folder = $langs_folder ;
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
	}

	/* call by findfiles */
	function searchpathparent($path, $filename) {
		$parent_langs_folder = str_replace( get_template_directory(), '', $path); // updated 1.2.0
		if ( $this->xili_settings['parent_langs_folder'] != $parent_langs_folder ) {
			$this->xili_settings['parent_langs_folder'] = $parent_langs_folder ;
			$this->parent_langs_folder = $parent_langs_folder ;
			update_option( 'xili_dictionary_settings', $this->xili_settings );
		}
	}

	function xililanguage_state() {
	/* test if xili-language is present or was present */
		if (class_exists('xili_language')) {

			$this->xililanguage = 'isactive';

		} else {
			/* test if language taxonomy relationships are present */
			$xl_settings = get_option('xili_language_settings');
			if ( empty($xl_settings) ) {
				$this->xililanguage = 'neveractive';
			} else {
				$this->xililanguage = 'wasactive';
			}
		}
	}
	/** * @since 1.02 */
	function fill_default_languages_list() {
		if ( $this->xililanguage == 'neveractive' || $this->xililanguage == 'wasactive' ) {

			if ( !isset( $this->xili_settings['xl-dictionary-langs'] ) ) {

				$default_langs_array = array(
					'en_us' => array('en_US', 'english'),
					'fr_fr' => array('fr_FR', 'french'),
					'de_de' => array('de_DE', 'german'),
					'es_es' => array('es_ES', 'spanish'),
					'it_it' => array('it_IT', 'italian'),
					'pt_pt' => array('pt_PT', 'portuguese'),
					'ru_ru' => array('ru_RU', 'russian'),
					'zh_cn' => array('zh_CN', 'chinese'),
					'ja' => array('ja', 'japanese'),
					'ar_ar' => array('ar_AR', 'arabic')
				);
				/* add wp admin lang */
				if ( $this->get_WPLANG() ) {
					$lkey = $this->get_WPLANG() ;
					if (!array_key_exists($lkey, $default_langs_array)) $default_langs_array[$lkey] = array ($this->get_WPLANG(), $this->get_WPLANG());
				}
				$this->xili_settings['xl-dictionary-langs'] = $default_langs_array;
				update_option('xili_dictionary_settings', $this->xili_settings);
			}
		}
	}

	/**
	 * for slug with 5 (_fr_fr) or 2 letters (as japanese)
	 *
	 */
	function extract_extend ( $line_slug ) {
		$end = substr($line_slug, -6 ) ;
		if ( substr($end, 0, 1) == '_' && substr($end, 3, 1) == '_' ) {
			return substr($line_slug, -5 );
		} else {
			return substr($line_slug, -2 ); // as ja
		}
	}

	/**
	 * 2.3.2
	 *
	 */
	function theme_domain () {

		if ( function_exists('the_theme_domain')) { // xili-language
			return the_theme_domain();
		} else {
			return get_option('template'); // child same as parent domain
		}
	}

/**
 * private functions for dictionary_settings
 * @since 0.9.3
 *
 * fill the content of the boxes (right side and normal)
 *
 */
	function on_sidebox_message_content( $data ) {
		extract($data);
		?>
<h4><?php _e('Note:','xili-dictionary') ?></h4>
<p><?php echo $message; ?></p>
		<?php
	}

	/**
	 * return the current theme name as saved in option with parent
	 * param: true if parent appended
	 */
	function get_option_theme_name ( $child_of = false ) {
		if ( is_child_theme() ) { // 1.8.1 and WP 3.0
			$theme_name = get_option( "stylesheet" );
			if ( $child_of ) $theme_name .= ' '.__('child of','xili-dictionary').' '.get_option( "template" );
		} else {
			$theme_name = get_option( "template" );
		}
		return $theme_name;
	}

	function on_sidebox_info_content() {
		$template_directory = $this->active_theme_directory;

		$cur_theme_name = $this->get_option_theme_name ( true );
		if ( $this->xililanguage_ms ) {
			echo '<p><em>'.__('xili-language-ms is active !','xili-dictionary').'</em></p>';

		} else {
				switch ( $this->xililanguage ) {
					case 'neveractive';
						echo '<p>'.__('xili-language plugin is not present !','xili-dictionary').'</p>';
						break;
					case 'wasactive';
						echo '<p>'.__('xili-language plugin is not activated !','xili-dictionary').'</p><br />';
						break;
				}
		}
		?>
<p><?php printf ( __('xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the post tables of WordPress as custom post type (%s). With this dictionary, it is possible to create and update .mo file in the current theme folder. And more...','xili-dictionary'), '<em>' . $this->xdmsg . '</em>' ); ?>
</p>
<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;">
	<legend><?php echo __("Theme's informations:",'xili-dictionary').' ('. $cur_theme_name .')'; ?></legend>
	<p>
				<?php $langfolder = $this->xili_settings['langs_folder'];
				echo __("Languages sub-folder:",'xili-dictionary').' '. $langfolder; ?><br />
				<?php
				if ( $langfolder == 'unknown' ) { ?><span style='color:red'><?php
				_e("No languages files are present in theme's folder or theme's sub-folder: <strong>add at least a .po or a .mo inside.</strong><br /> Errors will occur if you try to import or export!",'xili-dictionary'); echo '<br />';	?></span> <?php
				} else {
				_e('Available MO files:','xili-dictionary'); echo '<br />';
				if ( file_exists( $this->active_theme_directory ) ) // when theme was unavailable
					$this->find_files($this->active_theme_directory, '/.mo$/', array(&$this,'available_mo_files')) ;
				}

				?>
	</p>
</fieldset>


		<?php
	}

	function on_sidebox_settings_content() {
		?>
	<p> <?php _e( 'External file xd-style.css for dashboard (flags, customization)','xili-dictionary' ); ?></p>
		<?php
		if ( ! $this->exists_style_ext ) {

			echo '<p>'. __( 'There is no style for dashboard','xili-dictionary' ) .' ('.$this->style_message . ' )</p>';

		} else {

			echo '<p>'. $this->style_message . '</p>';
		}

		if ( $this->xili_settings['external_xd_style'] == "on" ) {

			$style_action = __( 'No style for dashboard','xili-dictionary' );
			$what = 'off';

		} else {

			$style_action = __( 'Activate style for dashboard','xili-dictionary' );
			$what = 'on';
		}
		?>

		<fieldset style="margin:2px; padding:6px 6px; "><strong><?php _e('Dictionary Styles','xili-dictionary') ;?></strong><br /><br />
		<?php
			$url = "?post_type=xdmsg&action=setstyle&what=".$what."&amp;page=dictionary_page";
			$nonce_url = wp_nonce_url( $url, 'xdsetstyle' );
		?>
			<a class="action-button grey-button" href="<?php echo $nonce_url ?>" title="<?php _e( 'Change style mode', 'xili-dictionary') ?>"><?php _e( $style_action ) ?></a>

		</fieldset>
		<hr />
		<p><strong><?php _e( 'Capabilities for editor role','xili-dictionary' ); ?></strong></p>
		<p><?php _e('Here, as admin, set capabilities of the editor role:','xili-dictionary') ?></p>


			<select name="editor_caps" id="editor_caps" >
						<option value="no_caps" ><?php _e('No capability','xili-dictionary'); ?></option>
						<option value="cap_edit" <?php selected( 'cap_edit', $this->xili_settings['editor_caps']); ?>><?php _e('Editor can edit MSGs','xili-dictionary'); ?></option>
						<option value="cap_edit_save" <?php selected( 'cap_edit_save', $this->xili_settings['editor_caps']); ?>><?php _e('Can edit MSGs and save local-xx_XX.mo','xili-dictionary'); ?></option>

				</select>
				<p class="submit">
				<input type="submit" id="setcapedit" name="setcapedit" value="<?php _e('Update Role…','xili-dictionary'); ?>" />
			</p>

	<?php
	}

	/**
	 * @since 2.0 with datatables js (ex widefat)
	 *
	 */
	function metabox_with_cpt_content_list( $data ) {
		extract( $data );
		$sortparent = (($this->subselect == '') ? '' : '&amp;tagsgroup_parent_select='.$this->subselect );
		?>
<div id="topbanner">
</div>
<div id="tableupdating" ><br /><br />
	<h2><?php _e('Drawing table...','xili-dictionary') ?></h2>
</div>
<table class="display" id="linestable" style="visibility:hidden;">
	<thead>
		<tr>
			<th scope="col" class="center colid"><a href="<?php echo $this->xd_settings_page; ?>" ><?php _e('ID') ?></a></th>
			<th scope="col" class="coltexte"><a href="<?php echo $this->xd_settings_page.'&amp;orderby=name'.$sortparent; ?>"><?php _e('Text') ?></a>
			</th>
			<th scope="col" class="colslug"><?php _e('Metas','xili-dictionary') ?></th>
			<th scope="col" class="colgroup center"><?php _e('Save status', 'xili-dictionary') ?></th>
			<th colspan="2"><?php _e('Action') ?></th>
		</tr>
	</thead>
	<tbody id="the-list">
			<?php $this->xili_dict_cpt_row( $orderby, $tagsnamelike, $tagsnamesearch ); /* the lines */
			?>
	</tbody>
</table>
<div id="bottombanner">
</div>
			<?php
	}

	/**
	 * metabox shared by dialogs before actions with XD list
	 *
	 */
	 function metabox_shared_by_dialogs ( $data ) {
		extract( $data );
		$do = true;

		$cur_theme_name = $this->get_option_theme_name();

		?>
<div class="metabox-content" >
	<div class="dialogcontainer" >

		<p id="add_edit"><?php _e( $formhow, 'xili-dictionary') ?></p>
		<?php
		$cur_theme_name = $this->get_option_theme_name( true );
		if ( in_array ( $action , array ( 'importbloginfos', 'importtaxonomy', 'importpluginmsgs' ) ) ) {

			echo '<p><em>' . sprintf( __('Before importing terms, verify that the %1$strash%2$s is empty !', 'xili-dictionary') , '<a href="edit.php?post_type='.XDMSG .'">', '</a>' ) . '</em></p>';
		}

		if ( in_array( $action, array ( 'export', 'exportpo', 'download' ) ) ) {
			// left column ?>
			<div class="dialoglang">
				<label for="language_file">
					<select name="language_file" id="language_file" ><?php
						$default_lang = $this->get_WPLANG() ;
						$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' );//get_terms(TAXONAME, array('hide_empty' => false));
						if ( $listlanguages ) {
							foreach ($listlanguages as $reflanguage) {
								echo '<option value="'.$reflanguage->name.'" ';
								echo selected ( $default_lang, $reflanguage->name, false ) ." >".__( $reflanguage->description, 'xili-dictionary' ).'</option>';
							}
						}
						if ( $action == 'exportpo' && !is_multisite() ) {
							echo '<option value="pot-file" >' .__( 'Build a .pot file', 'xili-dictionary' ).'</option>';
						}
						?>
					</select>
				</label>

			</div>
			<div class="dialogfile">
		<?php // middle column

			if ( ($action=='export' || $action=='exportpo' ) && is_multisite() && is_super_admin() && $this->xililanguage_ms ) { ?>
				<p><?php printf (__('Verify before that you are authorized to write in languages folder in theme named: %s','xili-dictionary'), $cur_theme_name ) ?>
				</p>
			<?php }
			if ( ( $action=='export' || $action=='exportpo' ) && is_multisite() && is_super_admin() && !$this->xililanguage_ms ) { ?>
				<label for="only-theme">
				<?php
				if ( $action == 'export' ) { // if not checked = blog-dir
					printf ( __('SuperAdmin: %sonly as theme .mo','xili-dictionary'), '<br />') ;
				} else {
					printf ( __('SuperAdmin: %sonly as theme .po','xili-dictionary'), '<br />') ;
				}
			?>
					<input id="only-theme" name="only-theme" type="checkbox" value="theme-dir" />
				</label>

			<?php }
			if ( ! is_multisite() ) {
				$do_it = true;
			} else {
				$do_it = is_super_admin(); // only super admin can create plugin language file
			}
			if ( $action=='export' || $action=='exportpo' ) { ?>

				<label for="target">
					<input checked="checked" class="target" name="target" type="radio" value="targettheme" />&nbsp;<?php _e('Theme','xili-dictionary'); ?>
				</label>
				<div style="margin:10px 10px 0 23px;"><label for="only-local">
				<?php
				$filetype = ( $action == 'export' ) ? "mo" : "po";
				?>
				<select id="only-local" name="only-local">
					<?php
						echo '<option value="" >' . sprintf( __('Build and save %s file','xili-dictionary'), $filetype);
						echo '<option value="local" selected="selected" >' . sprintf( __('Save only the local-xx_XX.%s sub-selection','xili-dictionary'), $filetype);
						echo '<option value="wolocal" >' . sprintf( __('Save %s w/o local msgs','xili-dictionary'), $filetype);

					?>
					</select>
				</label>
				<br />&nbsp;&nbsp;<label for="languagesfolder">
					<?php _e('In which languages subfolder','xili-dictionary'); ?>
					<select id="languagesfolder" name="languagesfolder">
						<?php
						echo '<option value="currenttheme" >' . sprintf( __('Sub-folder (%1$s) in current theme (%2$s)', 'xili-dictionary'), $this->langfolder, $cur_theme_name ) . '</option>';
						if ( is_child_theme() ) {
							echo '<option value="parenttheme" >' . sprintf( __('Languages sub-folder (%1$s) in parent theme ( %2$s )', 'xili-dictionary'), $this->parentlangfolder, get_option("template") ) . '</option>';
						}
						echo '<option value="contentlanguages" >' . __('Themes sub-folder in wp-content/languages (WP_LANG_DIR)', 'xili-dictionary') . '</option>';
						?>
					</select>
				</label></div> <?php
		if ( $do_it ) {
		?>
			<hr /><label for="target"><input class="target" name="target" type="radio" value="targetplugin" />&nbsp;<?php _e('Plugin language folder','xili-dictionary'); ?></label>
			<br /><label for="target"><input class="target" name="target" type="radio" value="targetpluginwplang" />&nbsp;<?php printf(__('Plugin language file in %s','xili-dictionary'), str_replace ( WP_CONTENT_DIR, '', WP_LANG_DIR . "/plugins/") ); ?></label>
			<hr />
		<?php }
		}
		?>
		</div>
		<?php
		// check origin theme
		if ( $action=='export' || $action=='exportpo' ) {

			$list_plugins = get_plugins(); // 2.6.0

			$listterms = get_terms( 'origin', array('hide_empty' => false) );
			if ( $action=='export' ) {
				echo '<input type="hidden" value="mo" id="_xd_file_extend" name="_xd_file_extend" >' ;
			} else {
				echo '<input type="hidden" value="po" id="_xd_file_extend" name="_xd_file_extend" >' ;
			}

			echo '<div class="dialogorigin">';
			if ( $listterms ) {
				$checkline = __ ( 'Check Origin(s) to be exported', 'xili-dictionary' ).':<br />';
				$i = 0;
				echo '<table class="checktheme" ><tr>';
				foreach ( $listterms as $onetheme) {
					if ( in_array( $onetheme->name, array_keys($list_plugins) ) && ! $do_it ) continue ;
					// Origin for plugins
					if ( in_array( $onetheme->name, array_keys($list_plugins) ) ) {
						$title = sprintf (__('Plugin: %s','xili-dictionary'), $list_plugins[$onetheme->name]['Name']);
					} else {
						$title = $onetheme->name;
					}

					$checked = ( $onetheme->name == $cur_theme_name ) ? 'checked="checked"' : '' ;
					$checkline .= '<td><input type="checkbox" '. $checked .' id="theme-'.$onetheme->term_id.'" name="theme-'.$onetheme->term_id.'" value="'.$onetheme->name.'" />&nbsp;' . $title .'</td>';
					$i++;
					if ( ($i % 2) == 0 ) $checkline .= '</tr><tr>';
				}
				echo $checkline.'</tr></table>';
			}
			echo '</div>';
		}
		// end container ?>
		<div style="clear:both;">
			<div id="xd-file-exists"><p><?php _e('The previous file will be overwritten !', 'xili-dictionary'); ?></p></div>
			<div id="xd-file-state"></div>
		</div>
	</div>


	<div class="dialogbbt">
		<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
		<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" />
	</div>
</div>
<script type="text/javascript">
	//<![CDATA[
	<?php
	if ( function_exists('the_theme_domain') ) {// in new xili-language
		echo 'var potfile = "'.the_theme_domain().'";';
	} else {
		echo 'var potfile = "'.$this->get_option_theme_name().'";';
	}
	echo 'var curthemename = "'.$this->get_option_theme_name().'";';
	if ( is_child_theme() )
		echo 'var parentthemename = "'. get_option('template') . '";';

	$this->echo_js_plugins_datas();

	echo 'var pluginpotfile = "plugin pot file";';


	?>
	function update_ui_state () {
		var x = jQuery('#_xd_file_extend').val();
		var place = jQuery('#languagesfolder').val().replace ('current', '');
		place = place.replace ('content', '');
		var multiupload = 'blog-dir';
		if ( jQuery('#only-theme').attr('checked') ) multiupload = 'theme-dir'; // multisite
		var local = jQuery('#only-local').val(); // 2.16.0
		var la = jQuery('#language_file').val();
		var target = jQuery('.target:checked').val();
		if ( target == "targetplugin" || target == "targetpluginwplang" ) {
			place = target.replace('target', '');
			var domain = jQuery( "table.checktheme input:checked" )
			.map(function() {
			return jQuery(this).val();
			}) .get();
			var domains = jQuery.makeArray( domain ) ;
			var plugin = "";
			plugin = domains[0];

		} else {
			var plugin = "";
		}

		var a = from_file_exists( 'files', place, curthemename, plugin, la, x, local, multiupload ); //alert ( a + ' <> ' + place + curthemename + plugin + la + x );
		show_file_states( 'files', place, curthemename, plugin, la, x, local, multiupload );
		if ( a == "exists" ) {
			jQuery( '#xd-file-exists' ).show();
			jQuery( '#xd-file-state' ).show();
		} else {
			jQuery( '#xd-file-exists' ).hide() ;
			jQuery( '#xd-file-state' ).show() ;
		}

	}
	jQuery(document).ready( function() {
		update_ui_state () ;
	});

	jQuery('#language_file , #languagesfolder, #only-local, .target, table.checktheme input:checkbox , #only-theme' ).change(function() {
		update_ui_state ();
	});


	//]]>
</script>
		<?php
		// other actions
		} elseif ( in_array( $action, array ( 'collectingpluginmsgs', 'checkimportingpluginmsgs','importbloginfos', 'importtaxonomy', 'erasedictionary', 'importpluginmsgs') ) ) {

			if ( $action == 'importtaxonomy' ) { ?>
		<label for="taxonomy_name"><?php _e('Slug:','xili-dictionary') ?></label>
		<input name="taxonomy_name" id="taxonomy_name" type="text" value="<?php echo ( $selecttaxonomy != '') ? $selecttaxonomy : 'category'; ?>" /><br />
			<?php
			} elseif ( in_array( $action, array ( 'collectingpluginmsgs', 'importpluginmsgs', 'checkimportingpluginmsgs' ) ) ) {

				global $l10n;
				echo '<br/>';
				$list_domains = array_keys ( $l10n ) ;
				$unlistable_domains = array( 'default', 'xili-language', 'bbpress', 'xili_xl_bbp_addon', 'xili_postinpost', 'xili_tidy_tags', 'xili-language-widget', 'xili-dictionary', 'twentyten' ) ;
				$domains_checking = array_diff ( $list_domains, $unlistable_domains );

				if ( 'importpluginmsgs' == $action ) {
					_e('Some active domains are detected in memory','xili-dictionary');
					$checked_domains = array();
					foreach ( $domains_checking as $domain ) {
						$po = $l10n[$domain];
						if ( count ($po->entries) > 0 ){
							echo sprintf ( __('This domain named %s has %d active entries.','xili-dictionary'), '<strong>' . $domain .'</strong>', count ($po->entries)) . '</br>';
							print_r($po->headers);
							$checked_domains[] = $domain;
						} else {
							echo sprintf ( __('No entry in %s (or .mo file badly built).','xili-dictionary'), '<strong>' . $domain .'</strong>' ) . '</br>';
						}
						echo '<br /><hr />';
					}

				}

				if ( $action == 'checkimportingpluginmsgs' ) {
					$collected_terms = get_option ( 'xd_test_importation_list', array() ) ;

					if ( $collected_terms ) {

						printf( __('Some terms collected from %s !', 'xili-dictionary'), __('Domain : ' . get_option ( 'xd_test_importation', "" ) ) );
						print_r ( $collected_terms ) ;

					} else {

						printf( __('No terms collected from %s !', 'xili-dictionary'), __('Domain : ' . get_option ( 'xd_test_importation', "" ) ) );
						$do = false;
					}
				}
			}

			if ( $action == 'importpluginmsgs' && $checked_domains != array() ) {
				echo '<select name="plugin_domain" id="plugin_domain" >';
				foreach ( $checked_domains as $one_domain ) {
					echo '<option value="'. $one_domain .'" >'. $one_domain .'</option>';
				}
				echo '</select>';
				?> <br />
				<?php
			} else if ( in_array ( $action, array ( 'collectingpluginmsgs', 'importpluginmsgs' ) ) ) {
				echo __('Domain : ', 'xili-dictionary') . get_option ( 'xd_test_importation', "" );
			}
		?>
		<br class="clear" />&nbsp;<br />

		<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
		<?php if ( $do == true ) { ?>
			<input id="import_start" class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br />
		<?php } ?>
	</div>
</div>
<script type="text/javascript">
				//<![CDATA[
				var plugin = '';
				<?php
				if ( function_exists('the_theme_domain') ) {// in new xili-language
					echo 'var potfile = "'.the_theme_domain().'";';
				} else {
					echo 'var potfile = "'.$this->get_option_theme_name().'";';
				}
				echo 'var curthemename = "'.$this->get_option_theme_name().'";';
				if ( is_child_theme() )
					echo 'var parentthemename = "'. get_option('template') . '";';

				$this->echo_js_plugins_datas();
				echo 'var pluginpotfile = "plugin pot file";';
				?>
				jQuery(document).ready( function() {
					var plugin = jQuery('#_xd_plugin').val();
					var t = '<?php echo esc_js( $submit_text ); ?>';

					jQuery('.source_pot').change(function() {
							var rb = jQuery(this).val();
							if ( rb == 'theme' ) {
								jQuery('#_xd_plugin').val('');
								jQuery("#import_start").val( t + ' : ' + curthemename );
								jQuery('#backup_pot_label').html( '('+ potfile + '.pot)' );
							} else if ( rb == 'plugin' ) {
								plugin = jQuery('#_xd_plugin').val();
								if ( 'string' == typeof (plugin) && plugin != '' ) jQuery('#backup_pot_label').html( '('+ plugindatas[plugin]['domain'] + '.pot)' );
								if ( 'string' == typeof (plugin) && plugin != '' ) jQuery("#import_start").val( t + ' : ' + plugindatas[plugin]['name'] );
							}
					});

					jQuery('#_xd_plugin').change(function() {
						jQuery('#source_plugin').attr('checked','checked');
						plugin = jQuery(this).val();
						if ( 'string' == typeof (plugin) && plugin != '' ) jQuery("#import_start").val( t + ' : ' + plugindatas[plugin]['name'] );
						if ( 'string' == typeof (plugin) && plugin != '' ) jQuery('#backup_pot_label').html( '('+ plugindatas[plugin]['domain'] + '.pot)' );
					});

				});
				//]]>
				</script>
		<?php
	// nothing inside
		} else {
			if ( $action == 'importingbloginfos' ) { // because special (to avoid ajax)
				echo '<p style="border-left:3px #00e000 solid; padding:5px 10px; background-color:#ffffff;">'.__( 'Now, all the blog and core terms are imported:', 'xili-dictionary' ). $this->import_message . '.</p>';
			}
			echo '<p><em>'.__( 'This box is used for input dialog, leave it opened and visible…', 'xili-dictionary' ).'</em></p></div></div>';
		}
	}

	/**
	 * @since 2.1
	 * built checked themes array
	 *
	 */
	function checked_themes_array() {
		$checked_themes = array();
		$listterms = get_terms( 'origin', array('hide_empty' => false) );
		if ( $listterms ) {

			foreach ( $listterms as $onetheme) {
				$idcheck = 'theme-'.$onetheme->term_id;
					if ( isset ( $_POST[$idcheck] ) ) $checked_themes[] = $onetheme->name;
			}
		}
		return $checked_themes;
	}

	/**
	 * @updated 1.0.2
	 * manage files
	 */
	function metabox_import_export_files( $data ) {
		extract( $data );
		$default_lang_get = ( $this->get_WPLANG() ) ? '&amp;' . QUETAG . '=' . $this->get_WPLANG() : '' ;
		?>
<h4 id="manage_file"><?php _e('The files','xili-dictionary') ;?></h4>
<a class="action-button blue-button" href="<?php echo $this->xd_settings_page.'&amp;action=export'; ?>" title="<?php _e('Create or Update mo file in current theme folder','xili-dictionary') ?>"><?php _e('Build mo file','xili-dictionary') ?></a>
&nbsp;<br /><?php _e('Import po/mo file','xili-dictionary') ?>:<a class="small-action-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=import_dictionary_page&amp;extend=po<?php echo$default_lang_get ?>" title="<?php _e('Import an existing .po file from current theme folder','xili-dictionary') ?>">PO</a>
<a class="small-action-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=import_dictionary_page&amp;extend=mo<?php echo$default_lang_get ?>" title="<?php _e('Import an existing .mo file from current theme folder','xili-dictionary') ?>">MO</a><br />
&nbsp;<br /><a class="action-button grey-button" href="<?php echo $this->xd_settings_page.'&amp;action=exportpo'; ?>" title="<?php _e('Create or Update po file in current theme folder','xili-dictionary') ?>"><?php _e('Build po file','xili-dictionary') ?></a>
<br /><a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=download_dictionary_page" title="<?php _e('Download po or file to your computer','xili-dictionary') ?>"><?php _e('Download file','xili-dictionary') ?></a>

<h4 id="manage_categories"><?php _e('The taxonomies','xili-dictionary') ;?></h4>
<a class="action-button blue-button" href="<?php echo $this->xd_settings_page.'&amp;action=importtaxonomy'; ?>" title="<?php _e('Import name and description of taxonomy','xili-dictionary') ?>"><?php _e('Import terms of taxonomy','xili-dictionary') ?></a>

<h4 id="manage_website_infos"><?php _e('The website infos (title, sub-title and more…)','xili-dictionary') ;?></h4>
		<?php if ( class_exists ('xili_language') && version_compare ( XILILANGUAGE_VER, '2.3.9', '>' )	) {
			_e ( '…and comment, locale, date terms, archive,…', 'xili-dictionary' ); echo '<br /><br />';
		} ?>
<a class="action-button blue-button" href="<?php echo $this->xd_settings_page.'&amp;action=importbloginfos'; ?>" title="<?php _e('Import infos of web site and more','xili-dictionary') ?>"><?php _e("Import terms of website's infos",'xili-dictionary') ?></a>

<h4 id="manage_dictionary"><?php _e('Dictionary in database','xili-dictionary') ;?></h4>
	<a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=erase_dictionary_page" title="<?php _e('Erase selected terms of dictionary ! (but not .mo or .po files)','xili-dictionary') ?>"><?php _e('Erase (selection of) terms','xili-dictionary') ?></a>
	<a class="action-button grey-button" href="edit.php?post_type=<?php echo XDMSG ?>&amp;page=import_dictionary_page&amp;scan=sources" title="<?php _e('Import terms from files','xili-dictionary') ?>"><?php _e('Import terms from source files','xili-dictionary') ?></a>
		<?php if ( isset($_GET['test']) ) { /* during testing phase 2.3.5 */ ?>
<h4 id="manage_dictionary"><?php _e('Selection of plugin’s msgs for front-end','xili-dictionary') ;?></h4>
	<a class="action-button grey-button" href="<?php echo $this->xd_settings_page.'&amp;action=importpluginmsgs'; ?>" title="<?php _e('Import terms for current active plugin','xili-dictionary') ?>"><?php _e('Import terms from plugins','xili-dictionary') ?></a>


		<?php
		} // temp test
	}

	/**
	 * @since 090423 -
	 * Sub selection box
	 */
	function metabox_msg_selection( $data = array() ) {
		extract( $data );
		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php _e('Sub list of terms','xili-dictionary'); ?></legend>
<?php /*
			<label for="tagsnamelike"><?php _e('Starting with:','xili-dictionary') ?></label>
			<input name="tagsnamelike" id="tagsnamelike" type="text" value="<?php echo $tagsnamelike; ?>" /><br />
*/ ?>
			<label for="tagsnamesearch"><?php _e('Containing:','xili-dictionary') ?></label>
			<input name="tagsnamesearch" id="tagsnamesearch" type="text" value="<?php echo $tagsnamesearch; ?>" />
			<p class="submit">
				<input type="submit" id="tagssublist" name="tagssublist" value="<?php _e('Sub select…','xili-dictionary'); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="submit" id="notagssublist" name="notagssublist" value="<?php _e('No select…','xili-dictionary'); ?>" />
			</p>
		</fieldset>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php _e('Selection by language','xili-dictionary'); ?></legend>
			<select name="tagsgroup_parent_select" id="tagsgroup_parent_select" style="width:100%;">
				<option value="no_select" ><?php _e('No sub-selection','xili-dictionary'); ?></option>
				<?php $checked = ($this->subselect == "msgid") ? 'selected="selected"' :'' ;
				echo '<option value="msgid" '.$checked.' >'.__('Only MsgID (en_US)','xili-dictionary').'</option>';
				$checked = ($this->subselect == "msgstr") ? 'selected="selected"' :'' ;
				echo '<option value="msgstr" '.$checked.' >'.__('Only Msgstr','xili-dictionary').'</option>';
				$checked = ($this->subselect == "msgstr_0") ? 'selected="selected"' :'' ;
				echo '<option value="msgstr_0" '.$checked.' >'.__('Only Msgstr plural','xili-dictionary').'</option>';
				echo $this->build_grouplist();
				echo $this->build_grouplist('nottransin_');	// 2.1.2 - not translated in
				?>
			</select>
			<br />
			<p class="submit">
				<input type="submit" id="subselection" name="subselection" value="<?php _e('Sub select…','xili-dictionary'); ?>" />
			</p>
		</fieldset>
		<?php
	}

	/**
	 * @since 1.0.2
	 * only if xili-language plugin is absent
	 */
	function metabox_languages_list_management( $data = array() ) {
		extract( $data );
		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php _e('Language to delete','xili-dictionary'); ?></legend>
			<p><?php _e('Only the languages list is here modified (but not the dictionary\'s contents)','xili-dictionary'); ?>
			</p>
			<select name="langs_list" id="langs_list" style="width:100%;">
				<option value="no_select" ><?php _e('Select...','xili-dictionary'); ?></option>
				<?php echo $this->build_grouplist(''); ?>
			</select>
			<br />
			<p class="submit">
				<input type="submit" id="lang_delete" name="lang_delete" value="<?php _e('Delete a language','xili-dictionary'); ?>" />
			</p>
		</fieldset><br />

		<?php
		$this->examples_list = array();
		$GP_locale_path = str_replace( 'xili-dictionary/', '', plugin_dir_path ( __FILE__ ) ) . 'jetpack/locales.php';
		if (!class_exists('GP_Locales') && file_exists ( $GP_locale_path ) ) {
			require_once ( $GP_locale_path ); // from JetPack
		}
		if ( class_exists('GP_Locales' ) ) {
			$XL_locales = GP_Locales::instance();
			foreach ( $XL_locales->locales as $key => $one_locale ) {
				if ( isset( $one_locale->wp_locale ) && $one_locale->wp_locale != ''){
					$this->examples_list[$one_locale->wp_locale] = sprintf( _x( '%1$s/%2$s', 'locales', 'xili-dictionary' ), $one_locale->english_name, $one_locale->native_name );
				} else { // a * inserted if no existing WP_locale declared...
					$this->examples_list[$key] = sprintf( _x( '%1$s/%2$s *', 'locales', 'xili-dictionary' ), $one_locale->english_name, $one_locale->native_name );
				}
			}
		}

		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
			<legend><?php _e('Language to add','xili-dictionary'); ?></legend>
			<?php if ( $this->examples_list  ) { ?>
			<select name="language_name_list" id="language_name_list">
				<?php $this->example_langs_list(); ?>
			</select><br />
			<?php } ?>
			<label for="lang_ISO"><?php _e('ISO (xx_YY)','xili-dictionary') ?></label>:&nbsp;
			<input name="lang_ISO" id="lang_ISO" type="text" value="" size="5"/><br />
			<label for="lang_name"><?php _e('Name (eng.)','xili-dictionary') ?></label>:&nbsp;
			<input name="lang_name" id="lang_name" type="text" value="" size="20" />
			<br />
			<p class="submit">
				<input type="submit" id="lang_add" name="lang_add" value="<?php _e('Add a language','xili-dictionary'); ?>" />
			</p>
		</fieldset>
		<script type="text/javascript">
		//<![CDATA[
			jQuery(document).ready( function($) {
				$('#language_name_list').change(function() {
				var x = $(this).val();
				$('#lang_ISO').val(x);
				var v = $('#language_name_list option:selected').text();
				v1 = v.substring(0,v.indexOf("/",0));
				v2 = v1.substring(0,v1.indexOf(" (",0));
				if ('' != v2 ) {
					v = v2;
				} else {
					v = v1;
				}
				$('#lang_name').val(v);
			});
		});
		//]]>
		</script>
	<?php }

	/**
	 * private functions for admin page : the language example list
	 * @since 1.6.0
	 */
	function example_langs_list( ) {

		/* reduce list according present languages in today list */
		if ($state != 'delete' && $state != 'edit') {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC');
			foreach ( $listlanguages as $language ) {
				if ( array_key_exists($language->name, $this->examples_list)) unset ($this->examples_list[$language->name]);
			}
		}
		//
		echo '<option value="">'.__('Choose…','xili-dictionary').'</option>';
		foreach($this->examples_list AS $key=>$value) {
			// $selected = (''!=$language_name && $language_name == $key) ? 'selected=selected' : '';
			$selected = '';
			echo '<option value="' . $key . '" ' . $selected . '>' . $value . ' (' . $key . ')</option>';
		}
	}

	/**
	 * build the list of group of languages for dictionary
	 *
	 * @updated 1.0.2
	 *
	 */
	function build_grouplist ($left_line = '') {

		$listdictlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
		$optionlist = "";
		$lefttext = "";
		if ( $left_line == 'nottransin_' ) $lefttext = __('not translated in','xili-dictionary').' ';
		foreach($listdictlanguages as $dictlanguage) {
			$checked = ($this->subselect == $left_line.$dictlanguage->slug) ? 'selected="selected"' :'' ;
		$optionlist .= '<option value="'.$left_line.$dictlanguage->slug.'" '.$checked.' >'.$lefttext.$dictlanguage->name .' ('.$dictlanguage->description.')</option>';
		}
	return $optionlist;
	}

	 /**
	  * @since 1.3.3
	  * // obsolete ?
	  *
	  */
	function xd_wp_set_object_terms( $term_id, $lang_slug, $taxo_dictgroup , $bool = false) {

		// check if lang_slug exists in this dict taxonomy
		if ( ! $term_info = term_exists($lang_slug, $taxo_dictgroup) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int( $term_info ) )
				continue;
			$args = array( 'alias_of' => '', 'description' => 'Dictionary Group in '.$lang_slug );
			$term_info = wp_insert_term($lang_slug, $taxo_dictgroup, $args); //print_r ($term_info);
		}
		wp_set_object_terms( $term_id, $lang_slug, $taxo_dictgroup, $bool );

	}

	/**
	 * Dashboard - Manage - Dictionary
	 *
	 * @since 0.9
	 * @updated 2.0
	 *
	 */
	function xili_dictionary_settings() {
		global $wp_version ;

		$action = "";
		$emessage = ""; // email
		$term_id = 0;
		$formtitle = __('Dialog box','xili-dictionary');
		$formhow = " ";
		$submit_text = __('Do &raquo;','xili-dictionary');
		$cancel_text = __('Cancel');

		$tagsnamelike = ''; // not yet used
		$selecttaxonomy = "";

		//$tagsnamelike = ( isset ( $_POST['tagsnamelike'] ) ) ? $_POST['tagsnamelike'] : '';
		//if (isset($_GET['tagsnamelike']))
			//$tagsnamelike = $_GET['tagsnamelike']; /* if link from table */
		$tagsnamesearch = ( isset ( $_POST['tagsnamesearch'] ) ) ? $_POST['tagsnamesearch'] : '';
		if (isset($_GET['tagsnamesearch']))
			$tagsnamesearch = $_GET['tagsnamesearch'];



		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];

		} elseif ( isset($_POST['sendmail']) ) { //2.3.2
			$action = 'sendmail' ;

		} elseif ( isset( $_POST['setcapedit']) ) {
			$action = 'setcapedit';

		} elseif (isset($_POST['action'])) {
			$action = $_POST['action']; // hidden input by default

		} elseif ( isset ( $_GET['action'] ) ) {
			$action = $_GET['action'];

		}
		/* language delete or add */
		if (isset($_POST['lang_delete'])) {
			$action='lang_delete';
		}
		if (isset($_POST['lang_add'])) {
			$action='lang_add';
		}
		/* sub lists */
		if (isset($_POST['notagssublist'])) {
			$action='notagssublist';
		}

		if (isset($_POST['tagssublist'])) {
			$action='tagssublist';
		}
		if (isset($_GET['orderby'])) :
			$orderby = $_GET['orderby'] ;
		else :
			$orderby = 't.term_id'; /* 0.9.8 */
		endif;
		if ( isset($_POST['tagsgroup_parent_select']) && $_POST['tagsgroup_parent_select'] != 'no_select') {
				$this->subselect = $_POST['tagsgroup_parent_select'];
			} else {
				$this->subselect = '';
			}
		if ( isset($_GET['tagsgroup_parent_select']))
			$this->subselect = $_GET['tagsgroup_parent_select'];

		if (isset($_POST['subselection'])) {
			$action='subselection';
		}

		$cur_theme_name = $this->get_option_theme_name();

		$message = ''; //$action." = " ;
		$msg = 0;

		switch($action) {

		case 'setcapedit';

			$this->xili_settings['editor_caps'] = $_POST['editor_caps'];
			update_option('xili_dictionary_settings', $this->xili_settings);
			$actiontype = "add";
			$message .= ' Editor role updated';
			break;
		case 'setstyle'; // external xd-style.css
			check_admin_referer( 'xdsetstyle' );
			if ( isset ( $_GET['what'] ) ) {
				$what = 'off';
				if ( $_GET['what'] == 'on' ) {
					$what = 'on';
				} else if ( $_GET['what'] == 'off' ) {
					$what = 'off';
				}
				$this->xili_settings['external_xd_style'] = $what ;
				update_option('xili_dictionary_settings', $this->xili_settings);
			}

			$actiontype = "add";
			break;

		case 'lang_delete';
			$reflang = $_POST['langs_list'];
			$wp_lang = ($this->get_WPLANG()) ? strtolower($this->get_WPLANG()) : 'en_us';
			if ($reflang != 'no_select' && $reflang != 'en_us' && $reflang != $wp_lang) {
				$ids = term_exists($reflang, TAXONAME);
				if ( $ids ) {
					if ( is_wp_error( $ids ) ) {
						$message .= ' '.$reflang.' error';
					} else {
						$t_id = $ids['term_id'];
						wp_delete_term( $t_id, TAXONAME );
						$message .= ' '.$reflang.' deleted';
					}
				} else {
					$message .= ' '.$reflang.' not exist';
				}

			} else {
				$message .= ' nothing to delete';
			}

			$actiontype = "add";
			break;

		case 'lang_add';
				$reflang = ('' != $_POST['lang_ISO'] ) ? $_POST['lang_ISO'] : "???";
				$reflangname = ('' !=$_POST['lang_name']) ? $_POST['lang_name'] : $reflang;
				if ( $reflang != '???' && ( ( strlen($reflang) == 5 && substr($reflang,2,1) == '_') ) || ( strlen($reflang) == 2 ) ) {

					$args = array( 'alias_of' => '', 'description' => $reflangname, 'parent' => 0, 'slug' =>strtolower( $reflang ));
					$theids = $this->safe_lang_term_creation ( $reflang, $args );
					if ( !is_wp_error($theids) )
						wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
					$message .= ' '.$reflang.$msg;
				} else {
					$message .= ' error ('.$reflang.') ! no add';
				}

				$actiontype = "add";
				break;

		case 'subselection';
				//$tagsnamelike = $_POST['tagsnamelike'];
				$tagsnamesearch = $_POST['tagsnamesearch'];
				$message .= ' selection of '.$_POST['tagsgroup_parent_select'];
				$actiontype = "add";
				break;

		case 'notagssublist';
				$tagsnamelike = '';
				$tagsnamesearch = '';
				$message .= ' no sub list of terms';
				$actiontype = "add";
				break;

		case 'tagssublist';
				//$message .= ' sub list of terms starting with '.$_POST['tagsnamelike'];
				$actiontype = "add";
				break;

		case 'export';
			$actiontype = "exporting";
			$formtitle = __('Build mo file','xili-dictionary');
			$formhow = sprintf(__('To build a %s file, choose language, destination (current theme or plugin) and origin.','xili-dictionary'), '.mo' );
			$submit_text = sprintf(__('Build %s file &raquo;','xili-dictionary'), '.mo' );
			break;

		case 'exporting'; // MO
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
			if ("" != $selectlang){
				//$this->xili_create_mo_file(strtolower($selectlang));
				$file = "";
				$extract_array = array();
				$checked_themes = $this->checked_themes_array();

				if ( $_POST['target'] == "targettheme" ) {

					if ( is_multisite() ) { /* complete theme's language with db structure languages (cats, desc,…) in uploads */
						//global $wpdb;
						//$thesite_ID = $wpdb->blogid;
						$superadmin = ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'theme-dir') ? true : false ;
						$message .= ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'theme-dir') ? "- exported only in theme - " : "- exported in uploads - " ;

						if (($uploads = xili_upload_dir()) && false === $uploads['error'] ) {

							if ( $superadmin === true ) {
								if ( $_POST['only-local'] == 'local' ) {
									$local = 'local';
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
									$file = $this->active_theme_directory.$this->langfolder.'local-'.$selectlang.'.mo' ;
								} elseif ( $_POST['only-local'] == 'wolocal' ) {
									$extract_array [ 'origin' ] = $checked_themes;
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = false;
									$local = '';
									$file = '';
								} else {
									$extract_array [ 'origin' ] = $checked_themes;
									$local = '';
									$file = '';
								}
							} else {
								if ( $_POST['only-local'] == 'local' ) {
									$local = 'local';
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
									$file = $uploads['path']."/local-".$selectlang.".mo" ;

								} elseif ( $_POST['only-local'] == 'wolocal' ) {
									$extract_array [ 'origin' ] = $checked_themes;
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = false; // no local

									$file = $uploads['path']."/".$selectlang.".mo" ;
								} else {
									$extract_array [ 'origin' ] = $checked_themes;
									$local = '';
									$file = $uploads['path']."/".$selectlang.".mo" ;
								}
							}
								$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name ;
								$mo = $this->from_cpt_to_POMO_wpmu ( $selectlang, 'mo', $superadmin, $extract_array ); // do diff if not superadmin
						}

					} else { // not multisite

						if ( $_POST['only-local'] == 'local' ) {
							$local = 'local';
							$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
							$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$file = get_template_directory().$this->parentlangfolder.'local-'.$selectlang.'.mo' ; // not used if child by xili-language
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() .'-local-'.$selectlang.'.mo' ;
								} else {
									$file = $this->active_theme_directory.$this->langfolder.'local-'.$selectlang.'.mo' ;
								}
							}
						} elseif ( $_POST['only-local'] == 'wolocal' ) {
							$extract_array [ 'origin' ] = $checked_themes;
							$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
							$extract_array [ 'like-'.$this->msg_extracted_comments ] = false; // no local
							$local = '';
							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$file = get_template_directory().$this->parentlangfolder.$selectlang.'.mo' ;
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() .'-'. $selectlang.'.mo' ;
								} else {
									$file = $this->active_theme_directory.$this->langfolder.$selectlang.'.mo' ;
								}
							}

						} else {

							$extract_array [ 'origin' ] = $checked_themes;
							$local = '';
							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$file = get_template_directory().$this->parentlangfolder.$selectlang.'.mo' ;
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() .'-'. $selectlang.'.mo' ;
								} else {
									$file = $this->active_theme_directory.$this->langfolder.$selectlang.'.mo' ;
								}
							}
						}
						$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name ;
						$mo = $this->from_cpt_to_POMO ( $selectlang, 'mo', $extract_array );
					}

				} else { // target plugin

					if ( $checked_themes != array() ) {
						$extract_array [ 'origin' ] = $checked_themes;
						$extract_array['projet_id_version'] = 'plugin = ' . $checked_themes[0] ;
						$mo = $this->from_cpt_to_POMO ( $selectlang, 'mo', $extract_array );
						$file = $this->path_plugin_file( $checked_themes[0], $selectlang, 'mo', ( $_POST['target'] == "targetpluginwplang" ) ); // 2.10.1
					}
				}

				if ( isset ( $mo ) && count ($mo->entries) > 0 ){ // 2.2
					$local = ( $_POST['target'] == "targetplugin" || $_POST['target'] == "targetpluginwplang") ? '&nbsp;-&nbsp;'.basename($file, '.mo') : $local;
					if ( false === $this->Save_MO_to_file ($selectlang , $mo, $file ) ) {
						$message .= ' '.sprintf(__('error during exporting in %s file.','xili-dictionary'), '<em>' . str_replace ( WP_CONTENT_DIR, '', $file ) .'</em>' );
					} else {
						$message .= ' '.sprintf(__('exported in %1$s file with %2$s msgids.','xili-dictionary'), '<em>' . str_replace ( WP_CONTENT_DIR, '', $file .'</em>' ), count ($mo->entries) );
					}
				} else {
					$message .= sprintf('<span class="alert">'.__('Nothing saved or updated in %s file !', 'xili-dictionary').'</span>', str_replace ( WP_CONTENT_DIR, '', $file ) );
				}
			} else {
				$message .= ' : error "'.$selectlang.'"';
			}
				$msg = 6 ;
				break;

		case 'exportpo';
			$actiontype = "exportingpo";
			$formtitle = __('Build po file','xili-dictionary');
			$formhow = sprintf(__('To build a %s file, choose language, destination (current theme or plugin) and origin.','xili-dictionary'), '.po' );
			$submit_text = sprintf(__('Build %s file &raquo;','xili-dictionary'), '.po' );
			break;

		case 'exportingpo'; // PO
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
			if ("" != $selectlang) {
				$file_suffix = ( $selectlang == 'pot-file' && !is_multisite() ) ? '.pot' : '.po'; // 2.8.0

				$extract_array = array();
				$checked_themes = $this->checked_themes_array();

				if ( $_POST['target'] == "targettheme" ) {

					if ( is_multisite() ) { /* complete theme's language with db structure languages (cats, desc,…) in uploads */

						$superadmin = ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'theme-dir') ? true : false ;
						$message .= ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'theme-dir') ? "- exported only in theme - " : "- exported in uploads - " ;

						if (($uploads = xili_upload_dir()) && false === $uploads['error'] ) {

							if ( $superadmin === true ) {
								if ( $_POST['only-local'] == 'local' ) {
									$local = 'local';
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
									$file = $this->active_theme_directory.$this->langfolder.'local-'.$selectlang.'.po' ; // theme folder

								} elseif ( $_POST['only-local'] == 'wolocal' ) {
									$local = '';
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = false; // no local
									$file = '';
								} else {
									$local = '';
									$file = '';
								}

							} else {
								if ( $_POST['only-local'] == 'local' ) {
									$local = 'local';
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;
									$file = $uploads['path']."/local-".$selectlang.".po" ; // blogs.dir folder
								} elseif ( $_POST['only-local'] == 'wolocal' ) {
									$extract_array [ 'origin' ] = $checked_themes;
									$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
									$extract_array [ 'like-'.$this->msg_extracted_comments ] = false; // no
									$local = '';
									$file = $uploads['path']."/".$selectlang.".po" ;
								} else {
									$extract_array [ 'origin' ] = $checked_themes;


									$local = '';
									$file = $uploads['path']."/".$selectlang.".po" ;
								}
							}
						}

					} else { // standalone

						if ( $_POST['only-local'] == 'local' && $file_suffix != '.pot') { // no pot for local
							$local = 'local';
							$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
							$extract_array [ 'like-'.$this->msg_extracted_comments ] = true;

							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$file = get_template_directory().$this->parentlangfolder.'local-'.$selectlang.'.po' ;
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() .'-local-'.$selectlang.'.po' ;
								} else {
									$file = $this->active_theme_directory.$this->langfolder.'local-'.$selectlang.'.po' ;
								}
							}
						} elseif ( $_POST['only-local'] == 'wolocal' ) {
							$local = '';
							$extract_array [ 'origin' ] = $checked_themes;
							$extract_array [ $this->msg_extracted_comments ] = $this->local_tag;
							$extract_array [ 'like-'.$this->msg_extracted_comments ] = false; // no local

							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$selectlang1 = ( $file_suffix == '.pot' ) ? $this->theme_domain() : $selectlang;
								$file = get_template_directory() . $this->parentlangfolder . $selectlang1 . $file_suffix ;
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$selectlang1 = ( $file_suffix == '.pot' ) ? '' : '-' . $selectlang;
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . $selectlang1 . $file_suffix ;
								} else {
									$selectlang1 = ( $file_suffix == '.pot' ) ? $this->theme_domain() : $selectlang;
									$file = $this->active_theme_directory . $this->langfolder . $selectlang1 . $file_suffix ;
								}
							}

						} else {
							$extract_array [ 'origin' ] = $checked_themes;
							$local = '';

							if ( $_POST['languagesfolder'] == 'parenttheme' ) {
								$selectlang1 = ( $file_suffix == '.pot' ) ? $this->theme_domain() : $selectlang;
								$file = get_template_directory() . $this->parentlangfolder . $selectlang1 . $file_suffix ;
							} else {
								if ( $_POST['languagesfolder'] == 'contentlanguages' ) {
									$selectlang1 = ( $file_suffix == '.pot' ) ? '' : '-' . $selectlang;
									$file = WP_LANG_DIR . '/themes/' . $this->theme_domain() . $selectlang1 . $file_suffix ;
								} else {
									$selectlang1 = ( $file_suffix == '.pot' ) ? $this->theme_domain() : $selectlang;
									$file = $this->active_theme_directory . $this->langfolder . $selectlang1 . $file_suffix ;
								}
							}
						}
					}
					$extract_array['projet_id_version'] = 'theme = ' . $cur_theme_name ;
					$po = $this->from_cpt_to_POMO ( $selectlang, substr ( $file_suffix, 1 ) , $extract_array ); // po or pot

				} else { // target plugin

					if ( $checked_themes != array() ) {
						$extract_array [ 'origin' ] = $checked_themes;
						$extract_array['projet_id_version'] = 'plugin = ' . $checked_themes[0] ;
						$po = $this->from_cpt_to_POMO ( $selectlang, substr ( $file_suffix, 1 ), $extract_array );
						$selectlang = ( $file_suffix == '.pot' ) ? 'plugin pot file' : $selectlang ;
						$file = $this->path_plugin_file( $checked_themes[0], $selectlang, substr ( $file_suffix, 1 ), ( $_POST['target'] == "targetpluginwplang" ) ); //2.10.1
					}
				}

				if ( count ($po->entries) > 0 ){ // 2.2
					$local = ( $_POST['target'] == "targetplugin" || $_POST['target'] == "targetpluginwplang" ) ? '&nbsp;-&nbsp;'.basename( $file, $file_suffix ) : $local;
					if ( false === $this->Save_PO_to_file ( $selectlang , $po, $file ) ) {
						$message .= ' '.sprintf(__('error during exporting in %s file.','xili-dictionary'), '<em>' . str_replace ( WP_CONTENT_DIR, '', $file ) .'</em>' );
					} else {
						$message .= ' '.sprintf(__('exported in %1$s file with %2$s msgids.','xili-dictionary'), '<em>' . str_replace ( WP_CONTENT_DIR, '', $file .'</em>' ), count ($po->entries) );
					}
				} else {
					$message .= sprintf('<span class="alert">'.__('Nothing saved or updated in %s file !', 'xili-dictionary').'</span>', str_replace ( WP_CONTENT_DIR, '', $file ) );
				}

			} else {
				$message .= ' : error "'.$selectlang.'"';
			}
			break;

		case 'importbloginfos'; // bloginfos and others since 1.1.0
			$actiontype = "importingbloginfos";
			$formtitle = __('Import terms of blog info and others…','xili-dictionary');
			$formhow = __('To import terms of blog info and others defining this current website (title, date, comment, archive...), click below.','xili-dictionary')
			. '<br />' . __('The process will import around 140 <strong>msgid</strong> from sources, so be patient.','xili-dictionary');
			$UI_lang = get_locale();
			if ( 'en_US' != get_locale() ) // 2.11.1
				$formhow .= '<br />' . sprintf(__('The language of dashboard is not <em>en_US</em>, so the process will try to import translations in %s.','xili-dictionary'), '<strong>'.get_locale().'</strong>' );
			else
				$formhow .= '<br />' . __('if you switch language of dashboard is other than in <em>en_US</em>, then the process will try to import translations of chosen language.','xili-dictionary');

			// detect xml
			if ( $this->available_theme_mod_xml() ) {
				$formhow .= '<hr />' ;
				$formhow .= $this->display_form_theme_mod_xml();
				$formhow .= '<hr />' ;
			}

			$submit_text = __('Import blog info terms &raquo;','xili-dictionary');
			break;

		case 'importingbloginfos'; // bloginfos and others since 1.1.0
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";

			//$infosterms = $this->xili_import_infosterms_cpt ();

			$msg = 10;

			break;

		case 'importpluginmsgs';

			$actiontype = "collectingpluginmsgs";
			$formtitle = __('Import terms from active plugins','xili-dictionary');
			$formhow = __('To import terms …, click below.','xili-dictionary');
			$submit_text = __('Import msgs &raquo;','xili-dictionary');
			break;

		case 'collectingpluginmsgs';
			check_admin_referer( 'xilidicoptions' );

			$selectplugin_domain = $_POST['plugin_domain'];
			global $l10n;
			if ( isset ( $l10n[$selectplugin_domain] ) ) {

				$formtitle = __('Start collecting terms from active plugins','xili-dictionary');
				$formhow = __('To import terms, open a browser in front-end side.','xili-dictionary');
				$submit_text = __('Stop msgs collecting &raquo;','xili-dictionary');


				update_option ( 'xd_test_importation', $selectplugin_domain ) ;
				$actiontype = "checkimportingpluginmsgs";
			} else {
				$formtitle = __('Error: no domain specified','xili-dictionary');
				$formhow = __('Please specify a domain...','xili-dictionary');
				$submit_text = __('End collecting &raquo;','xili-dictionary');
				delete_option ( 'xd_test_importation' ) ;
				delete_option ( 'xd_test_importation_list' );
				$actiontype = "reset";
			}

			break;

		case 'checkimportingpluginmsgs';
			check_admin_referer( 'xilidicoptions' );

			$actiontype = "importingpluginmsgs";
			$formtitle = __('Import terms from active plugins','xili-dictionary');
			$formhow = __('To import terms, open a browser in front-end side.','xili-dictionary');
			$submit_text = __('Import collected &raquo;','xili-dictionary');


			break;

		case 'importingpluginmsgs';
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			// import into db
			$collected_msgs = get_option ( 'xd_test_importation_list', array() );

			if ( $collected_msgs != array() ) {
				// the curlang of admin
				$locale = $this->get_WPLANG();
				$nbterms = $this->import_plugin_collected_msgs ( $locale );
				// merge mo

				// the other if exists

			}
			if ( is_array( $collected_msgs ) && $nbterms != array() ) {
				$message .= __('imported terms = ','xili-dictionary') . $nbterms;
			} else {
				$message .= ' '.$readfile.__('plugin’s terms pbs!','xili-dictionary');
			}

			// reset values

			delete_option ( 'xd_test_importation' ) ;
			delete_option ( 'xd_test_importation_list' );

			break;

		case 'importtaxonomy';
			$actiontype = "importingtax";
			$formtitle = __('Import terms of taxonomy','xili-dictionary');
			$formhow = __('To import terms of the current taxonomy named, click below.', 'xili-dictionary');
			$submit_text = __('Import taxonomy’s terms &raquo;', 'xili-dictionary');
			break;

		case 'importingtax';
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			$selecttaxonomy = $_POST['taxonomy_name']; //
			if ( taxonomy_exists( $selecttaxonomy ) ) {
				$nbterms = $this->xili_read_catsterms_cpt( $selecttaxonomy, $this->local_tag ); //$this->xili_read_catsterms();
				$msg = 4;
				if ( is_array($nbterms) ) {
				//$nbterms = $this->xili_importcatsterms_in_tables($catterms);
					$message .= __('names = ','xili-dictionary').$nbterms[0].' & ' .__('descs = ','xili-dictionary').$nbterms[1];
				} else {
					$message .= ' '.sprintf(__('taxonomy -%s- terms pbs!', 'xili-dictionary'), $selecttaxonomy );
				}
			} else {
				$msg = 8;
				$message .= ' '.sprintf(__('taxonomy -%s- do not exists', 'xili-dictionary'), $selecttaxonomy );
			}

			break;

		case 'erasedictionary';
			$actiontype = "erasingdictionary";
			$formtitle = __('Erase all terms','xili-dictionary');
			$formhow = __('To erase terms of the dictionary, click below. (before, create a .po if necessary!)');
			$submit_text = __('Erase all terms &raquo;','xili-dictionary');
			break;

		case 'erasingdictionary';
			check_admin_referer( 'xilidicoptions' );

			$selection = ""; // $selecttaxonomy = $_POST['erasing_selection'];
			$this->erase_dictionary( $selection );


			$actiontype = "add";
			$message .= ' '.__('All terms erased !','xili-dictionary'); $msg = 7;
			// for next update
			break;

		case 'reset';
			$actiontype = "add";
			break;

		case 'sendmail'; // 2.3.2
				check_admin_referer( 'xilidicoptions' );
				$this->xili_settings['url'] = ( isset( $_POST['urlenable'] ) ) ? $_POST['urlenable'] : '' ;
				$this->xili_settings['theme'] = ( isset( $_POST['themeenable'] ) ) ? $_POST['themeenable'] : '' ;
				$this->xili_settings['wplang'] = ( isset( $_POST['wplangenable'] ) ) ? $_POST['wplangenable'] : '' ;
				$this->xili_settings['version-wp'] = ( isset( $_POST['versionenable'] ) ) ? $_POST['versionenable'] : '' ;
				$this->xili_settings['xiliplug'] = ( isset( $_POST['xiliplugenable'] ) ) ? $_POST['xiliplugenable'] : '' ;
				$this->xili_settings['webmestre-level'] = $_POST['webmestre']; // 1.8.2
				update_option('xili_dictionary_settings', $this->xili_settings);
				$contextual_arr = array();
				if ( $this->xili_settings['url'] == 'enable' ) $contextual_arr[] = "url=[ ".get_bloginfo ('url')." ]" ;
				if ( isset($_POST['onlocalhost']) ) $contextual_arr[] = "url=local" ;
				if ( $this->xili_settings['theme'] == 'enable' ) $contextual_arr[] = "theme=[ ".get_option ('stylesheet')." ]" ;
				if ( $this->xili_settings['wplang'] == 'enable' ) $contextual_arr[] = "WPLANG=[ ".$this->get_WPLANG()." ]" ;
				if ( $this->xili_settings['version-wp'] == 'enable' ) $contextual_arr[] = "WP version=[ ".$wp_version." ]" ;
				if ( $this->xili_settings['xiliplug'] == 'enable' ) $contextual_arr[] = "xiliplugins=[ ". $this->check_other_xili_plugins() ." ]" ;

				$contextual_arr[] = $this->xili_settings['webmestre-level']; // 1.9.1

				$headers = 'From: xili-dictionary plugin page <' . get_bloginfo ('admin_email').'>' . "\r\n" ;
				if ( '' != $_POST['ccmail'] ) $headers .= 'Cc: <'.$_POST['ccmail'].'>' . "\r\n";
				$headers .= "\\";
				$message = "Message sent by: ".get_bloginfo ('admin_email')."\n\n" ;
				$message .= "Subject: ".$_POST['subject']."\n\n" ;
				$message .= "Topic: ".$_POST['thema']."\n\n" ;
				$message .= "Content: ".$_POST['mailcontent']."\n\n" ;
				$message .= "Checked contextual infos: ". implode ( ', ', $contextual_arr ) ."\n\n" ;
				$message .= "This message was sent by webmaster in xili-dictionary plugin settings page.\n\n";
				$message .= "\n\n";
				$result = wp_mail('contact@xiligroup.com', $_POST['thema'].' from xili-dictionary v.'.XILIDICTIONARY_VER.' plugin settings page.' , $message, $headers );
				$message = __('Email sent.','xili_tidy_tags');
				$msg = 7;
				$emessage = sprintf( __( 'Thanks for your email. A copy was sent to %s (%s)','xili-dictionary' ), $_POST['ccmail'], $result ) ;
				$actiontype = "add";
				break;

		default:
			$actiontype = "add";
			$message .= ' '.__('Find below the list of terms.','xili-dictionary');

		}
		/* register the main boxes always available */

		/* files import export*/
		add_meta_box('xili-dictionary-sidebox-3', __('Import & export','xili-dictionary'), array(&$this,'metabox_import_export_files'), $this->thehook , 'side', 'core');
		/* msg selection */
		add_meta_box('xili-dictionary-sidebox-4', __('Terms list management','xili-dictionary'), array(&$this,'metabox_msg_selection'), $this->thehook , 'side', 'core');
		if ( $this->xililanguage != 'isactive' && $this->multilanguage_plugin_active != 'Polylang' )
			/* Languages list when xili-language is absent */
			add_meta_box('xili-dictionary-sidebox-5', __('Languages list management','xili-dictionary'), array(&$this,'metabox_languages_list_management'), $this->thehook , 'side', 'core');

		/* dialog input form shared */
		add_meta_box('xili-dictionary-normal-1', __( $formtitle, 'xili-dictionary'), array(&$this,'metabox_shared_by_dialogs'), $this->thehook , 'normal', 'core' );
		/* list of terms*/

		add_meta_box('xili-dictionary-normal-cpt', __('Multilingual Terms','xili-dictionary'), array(&$this,'metabox_with_cpt_content_list'), $this->thehook , 'normal', 'core');

		// since 1.2.2 - need to be upgraded...
		if ($msg == 0 && $message != '' ) $msg = 6 ; //by temporary default
		$themessages[1] = __('A new term was added.','xili-dictionary');
		$themessages[2] = __('A term was updated.','xili-dictionary');
		$themessages[3] = __('A term was deleted.','xili-dictionary');
		$themessages[4] = __('terms imported from WP: ','xili-dictionary') . $message;
		$themessages[5] = __('All terms imported !','xili-dictionary') . ' ('.$message.')';
		$themessages[6] = sprintf(__('Result log: %s', 'xili-dictionary' ), $message );
		$themessages[7] = __('All terms erased !','xili-dictionary');
		$themessages[8] = __('Error when adding !','xili-dictionary') . ' ('.$message.')';
		$themessages[9] = __('Error when updating !','xili-dictionary');
		$themessages[10] = __('Wait during terms importing process until the multilingual terms list appears below!','xili-dictionary');

		/* form datas in array for do_meta_boxes() */
		$data = array(
			'message'=>$message,
			'action'=>$action,
			'formtitle'=>$formtitle,
			'submit_text'=>$submit_text,
			'cancel_text'=>$cancel_text,
			'formhow'=>$formhow,
			'orderby'=>$orderby,
			'term_id'=>$term_id,
			'tagsnamesearch'=>$tagsnamesearch,
			'tagsnamelike'=>$tagsnamelike,
			'selecttaxonomy' =>$selecttaxonomy,
			'emessage'=>$emessage
			);
		if ( isset ( $dictioline ) ) $data['dictioline'] = $dictioline ;

		?>
<div id="xili-dictionary-settings" class="wrap columns-2" style="min-width:850px">
			<?php screen_icon('tools'); ?>
	<h2><?php _e('Dictionary','xili-dictionary') ?></h2>
			<?php if (0!= $msg ) { // 1.2.2 ?>
	<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php }

			$poststuff_class = "";
			$postbody_class = 'class="metabox-holder columns-2"';
			$postleft_id = 'id="postbox-container-2"';
			$postright_id = "postbox-container-1";
			$postleft_class = 'class="postbox-container"';
			$postright_class = "postbox-container";
			?>
	<form name="add" id="add" method="post" action="<?php echo $this->xd_settings_page; ?>">
		<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-dictionary-settings'); ?>
				<?php wp_nonce_field('xilidicoptions'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );
				/* 0.9.6 add has-right-sidebar for next wp 2.8*/ ?>
		<div id="poststuff" <?php echo $poststuff_class; ?> >

			<div id="post-body" <?php echo $postbody_class; ?> >
				<div id="<?php echo $postright_id; ?>" class="<?php echo $postright_class; ?>">
						<?php do_meta_boxes($this->thehook, 'side', $data); ?>
				</div>
				<div id="post-body-content" >
					<div <?php echo $postleft_id; ?> <?php echo $postleft_class; ?> style="min-width:360px">
						<?php
								if ( $action == 'importingbloginfos' ) {
									$infosterms = $this->xili_import_infosterms_cpt ();
									if ($infosterms[1] > 0) {
										$this->import_message = ' ('.$infosterms[1].'/'.$infosterms[0].') '. sprintf(__('msgid imported with success (%s msgstr)','xili-dictionary'), $infosterms[2] );
									} else {
										$this->import_message = ' '.__('already imported','xili-dictionary') . ' (' .$infosterms[0].') ';
									}
									do_meta_boxes($this->thehook, 'normal', $data);
								} else {
									do_meta_boxes($this->thehook, 'normal', $data);
								}
						?>
					</div>
					<h4><a href="http://dev.xiligroup.com/xili-dictionary" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/XD-full-logo-32.png', __FILE__ ) ; ?>" alt="xili-dictionary logo"/></a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-2015 - v. <?php echo XILIDICTIONARY_VER; ?></h4>
				</div>
			</div>
			<br class="clear" />
		</div>
	</form>
</div>

		<?php	//end settings div
		$this->insert_js_for_datatable( array('swidth2'=>'60%', 'screen' => 'toolbox' ) );
		}

	/**
	 * delete lines of dictionary
	 *
	 *
	 */
	 function erase_dictionary ( $selection = "" ) {

		if ( $selection == "" ) {
			// select all ids
			$listdictiolines = get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'post_type' => XDMSG,
				'suppress_filters' => true
			) );


		} else { // to improve soon

		}

		if ( $listdictiolines ) {
			// loop
			foreach ( $listdictiolines as $oneline ) {
				wp_delete_post( $oneline->ID, false ) ;
			}
		}
	}

	/**
	 * create an array of mo content of theme (maintained by super-admin)
	 *
	 * @since 1.1.0
	 */
	function get_pomo_from_theme( $local = false ) {
		$theme_mos = array();
		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));

			foreach ($listlanguages as $reflanguage) {
				$res = $this->pomo_import_MO ( $reflanguage->name, '', $local);
				if ( false !== $res )
					$theme_mos[$reflanguage->slug] = $res->entries;
			}
		}
		return $theme_mos;
	}

	function get_pomo_from_plugin( $plugin_path ) {
		if ( !isset ( $this->plugin_mos[$plugin_path] )) $this->plugin_mos[$plugin_path] = array();
		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));

			foreach ($listlanguages as $reflanguage) {
				$res = $this->pomo_import_MO ( $reflanguage->name, '', false , $plugin_path); // 2.10.1 only in plugin language folder
				if ( false !== $res )
					$this->plugin_mos[$plugin_path][$reflanguage->slug] = $res->entries;
				$res = $this->pomo_import_MO ( $reflanguage->name, '', false , $plugin_path, true); // 2.10.1 only in content language folder
				if ( false !== $res ) {
					$this->plugin_mos[$plugin_path][$reflanguage->slug] = $res->entries; // WP_LANG_DIR has priority
					$this->plugin_mos[$plugin_path]['WLD'][$reflanguage->slug] = 1;
				}
			}
		}
		return $this->plugin_mos[$plugin_path] ;
	}

	/**
	 * create an array of mo content of theme (maintained by admin of current site)
	 * currently contains the msgid which are not in theme mo
	 *
	 * @since 1.2.0
	 */
	function get_pomo_from_site( $local = false ) {
		$theme_mos = array();
		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $reflanguage) {
			$res = $this->import_mo_file_wpmu ( $reflanguage->name, false, $local ); // of current site
			if (false !== $res) $theme_mos[$reflanguage->slug] = $res->entries;
			}
		}
		return $theme_mos;
	}

	/**
	 * private function for admin page : one line of taxonomy
	 *
	 *
	 */
	function xili_dict_cpt_row( $listby='name', $tagsnamelike='', $tagsnamesearch='' ) { /* the lines */

		// select msg
		$special_query = false;
		switch ( $this->subselect ) {

			case 'msgid' :
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgid';
				break;
			case 'msgstr' :
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgstr';
				break;
			case 'msgstr_0' :
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgstr_0';
				break;
			case '' :
				$meta_key_val = '';
				$meta_value_val = '';
				break;
			default;
				if ( false !== strpos ( $this->subselect, 'only=' ) ) {
					$exps = explode ('=', $this->subselect);
					$special_query = 'strlang' ;
					$curlang = $exps[1];

				} else {
					if ( false !== strpos ( $this->subselect, 'nottransin_' ) ) {
						$exps = explode ('_', $this->subselect);
						$special_query = 'idlang' ;
						$curlang = $exps[1];
						$this->searchtranslated = 'not'; // 2.1.2
					} else {
						// msgid + language
						$curlang = $this->subselect;
						$special_query = 'idlang' ;
					}
				}
		}
		if ( $special_query == 'idlang' ) {
			if ( $this->searchtranslated != 'not' ) {
				$listdictiolines = $this->get_cpt_msgids( $curlang ) ;
			} else {
				$listdictiolines = $this->get_cpt_msgids( $curlang, 'mo', array(), true ) ; // search not translated in target language
			}
		} elseif ( $special_query == 'strlang' ) {
			$listdictiolines = get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true, 's' => $tagsnamesearch,
				'tax_query' => array(
						array(
							'taxonomy' => TAXONAME,
							'field' => 'name',
							'terms' => $curlang
					)
				),
				'meta_query' => array(
						array(
							'key' => $this->msgtype_meta,
							'value' => array( 'msgstr', 'msgstr_0', 'msgstr_1' ),
							'compare' => 'IN'
						)
				)
			) );

		} else {
			$listdictiolines = get_posts( array(
			'numberposts' => -1, 'offset' => 0,
			'category' => 0, 'orderby' => 'ID',
			'order' => 'ASC', 'include' => array(),
			'exclude' => array(), 'meta_key' => $meta_key_val,
			'meta_value' =>$meta_value_val, 'post_type' => XDMSG,
			'suppress_filters' => true, 's' => $tagsnamesearch
		) );
		}
		$class = "";
		$this->mo_files_array ();

		foreach ( $listdictiolines as $dictioline ) {

		$class = (( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";

			$type = get_post_meta ( $dictioline->ID, $this->msgtype_meta, true);
			$context = get_post_meta ( $dictioline->ID, $this->ctxt_meta, true);

			$res = $this->is_saved_cpt_in_theme( $dictioline->post_content, $type, $context );

			$save_state = $res[0] . ' (local-'.$res[2].')'; // improve for str and multisite

			if ( is_multisite() ) $save_state .= '<br />'.__('this site', 'xili-dictionary').': '.$res[1] . ' (local-'.$res[3].')';

			$origins = get_the_terms( $dictioline->ID, 'origin' );
				$names = array();
				if ( $origins ) {
					foreach ( $origins as $origin ) {
						$names[] = $origin->name;
					}
					$p = '';
					foreach ( $names as $plugin_path) {
						$plugin_res = $this->is_saved_cpt_in_plugin( $plugin_path, htmlspecialchars_decode ($dictioline->post_content), $type, $context );
						if ( $plugin_res ) $p .= sprintf( '<small>' . __('P{%s}:', 'xili-dictionary'), $this->get_plugin_name ($plugin_path) ) . ' ' . implode (' - ', $plugin_res ).'</small><br />';
					}
					$save_state .= '<br />' . $p;
				}


			$edit = "<a href='post.php?post=$dictioline->ID&action=edit' >".__( 'Edit' )."</a></td>";

			$line = "<tr id='cat-$dictioline->ID'$class>
				<td scope='row' style='text-align: center'>$dictioline->ID</td>

				<td>". htmlspecialchars( $dictioline->post_content ) ."</td>

				<td>";
			echo $line;
			$this->msg_link_display ( $dictioline->ID, false, $dictioline ) ;
			$line = "</td>
				<td class='center'>$save_state</td>

				<td class='center'>$edit</td>\n\t</tr>\n"; /*to complete*/
			echo $line;

		}
	}

	/**
	 * return count of msgid (local or theme domain)
	 * @since
	 */
	function count_msgids ( $curlang, $local = true, $theme_domain = '' ) {

		if ( $local ) {
			// msg id with lang
			$the_query = array(
			'post_type' => XDMSG,
			'meta_query' => array(
			'relation' => 'AND',
				array(
					'key' => $this->msgtype_meta,
					'value' => 'msgid',
					'compare' => '='
					),
				array(
					'key' => $this->msglang_meta,
					'value' => $curlang,
					'compare' => 'LIKE' // 2.1.2
					),
				array(
					'key' => $this->msg_extracted_comments,
					'value' => $this->local_tag,
					'compare' => 'LIKE'
					)
			)
		);

		} else if ( $theme_domain == '') {
			$the_query = array(
			'post_type' => XDMSG,
			'tax_query' => array(
				array(
					'taxonomy' => TAXONAME,
					'field' => 'name',
					'terms' => $curlang
					)
				)
			);

		} else {

			$the_query = array(
			'post_type' => XDMSG,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => $this->msgtype_meta,
					'value' => 'msgid',
					'compare' => '='
					),
				array(
					'key' => $this->msglang_meta,
					'value' => $curlang,
					'compare' => 'LIKE' // 2.1.2
					)
				),
			'tax_query' => array(

				array(
					'taxonomy' => 'origin',
					'field' => 'slug',
					'terms' => array($theme_domain),
					'operator' => 'IN'
					)
				)
			);

		}


		$query_4_test = new WP_Query( $the_query );
		return $query_4_test->found_posts;

	}

	/**
	 * test if line is in entries
	 * @since
	 */
	function is_intheme_mos ( $msg, $type, $entries, $context ) {
		foreach ($entries as $entry) {
			$diff = 1;
			switch ( $type ) {
				case 'msgid' :
					$diff = strcmp( $msg , $entry->singular );
					if ( $context != "" ) {
						if ( $entry->context != null ) {
							$diff += strcmp( $context , $entry->context );
						}
					}
					break;
				case 'msgid_plural' :
					$diff = strcmp( $msg , $entry->plural );
					break;
				case 'msgstr' :
					if ( isset ( $entry->translations[0] ) )
					$diff = strcmp( $msg , $entry->translations[0] );
					break;
				default:
					if ( false !== strpos ( $type, 'msgstr_' ) ) {
						$indice = (int) substr ( $type, -1) ;
						if ( isset ( $entry->translations[$indice] ) )
							$diff = strcmp( $msg , $entry->translations[$indice] );
					}
			}

			//if ( $diff != 0) { echo $msg.' i= '.strlen($msg); echo $entry->singular.') e= '.strlen($entry->singular); }
			if ( $diff == 0) return true;
		}
	return false;
	}

	function get_msg_in_entries ( $msg, $type, $entries, $context ) {
		foreach ($entries as $entry) {
			$diff = 1;
			switch ( $type ) {
				case 'msgid' :
					$diff = strcmp( $msg , $entry->singular );
					if ( $context != "" ) {
						if ( $entry->context != null ) {
							$diff += strcmp( $context , $entry->context );
						}
					}
					break;
				case 'msgid_plural' :
					$diff = strcmp( $msg , $entry->plural );
					break;
				case 'msgstr' :
					if ( isset ( $entry->translations[0] ) )
						$diff = strcmp( $msg , $entry->translations[0] );
					break;
				default:
					if ( false !== strpos ( $type, 'msgstr_' ) ) {
						$indice = (int) substr ( $type, -1) ;
						if ( isset ( $entry->translations[$indice] ) )
							$diff = strcmp( $msg , $entry->translations[$indice] );
					}
			}

			//if ( $diff != 0) { echo $msg.' i= '.strlen($msg); echo $entry->singular.') e= '.strlen($entry->singular); }
			if ( $diff == 0) {
				if ( isset ( $entry->translations[0] ) ) {
					return array( 'msgid' => $entry->singular , 'msgstr' => $entry->translations[0] );
				} else {
					return array() ;
				}
			}
		}
	return array() ;
	}

	/**
	 * Detect if cpt are saved in theme's languages folder
	 * @since 2.3.4
	 *
	 */
	function is_msg_saved_in_localmos ( $msg, $type, $context = "", $mode = "list" ) {

		$thelist = array();
		$thelistsite = array();
		$outputsite = "";
		$output = "";
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( $langfolderset !='' ) ? $langfolderset.'/' : '/';
	// doublon
		$this->langfolder = str_replace ("//","/", $this->langfolder ); // upgrading... 2.0 and sub folder sub
		$this -> mo_files_array ();

		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' );

			foreach ($listlanguages as $reflanguage) {

				if ( isset($this->local_mos[$reflanguage->slug]) ) {
					if ( $mode == "list" && $this->is_intheme_mos ( $msg, $type, $this->local_mos[$reflanguage->slug], $context ) ) {
						$thelist[] = '<span class="lang-'. $reflanguage->slug .'" >'. $reflanguage->name .'</span>';
					} else if ( $mode == "single" ) {
						$res = $this->get_msg_in_entries ( $msg, $type, $this->local_mos[$reflanguage->slug], $context ) ;
						if ( $res != array () )
							$thelist[$reflanguage->name] = $res ;
					}
				}

				if ( is_multisite() ) {
					if ( isset($this->file_site_local_mos[$reflanguage->slug]) ) {
						if ( $this->get_msg_in_entries ( $msg, $type, $this->file_site_local_mos[$reflanguage->slug], $context ) ) // fixed 2.11.1
							$thelistsite[] = '<span class="lang-'. $reflanguage->slug .'" >'. $reflanguage->name .'</span>';
					}
				}

			}

			if ( $mode == "list" ) {

				$output = ($thelist == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in theme's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in theme's files: ","xili-dictionary").'" >'. implode(' ',$thelist).'</small></small>';

					if ( is_multisite() ) {

						$outputsite = ($thelistsite == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in site's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in site's files: ","xili-dictionary").'" >'. implode(', ',$thelistsite).'</small></small>';

					}

			} else if ( $mode == "single" ) {

				if ($thelist == array()) {

					$output = __('Not yet translated in any language (not in any .mo files)','xili-dictionary') .'<br />';
				} else {
					$output = '';
					foreach ( $thelist as $key => $msg ) {

						$output .= '<span title="'.__('Translated in', 'xili-dictionary').' '.$key.'" class="lang-'. strtolower ( $key ) .'" >' . $key . '</span> : ' . $msg['msgstr'] . '<br />';
					}
				}
			}
		}
		return array ( $output, $outputsite ) ;

	}

	/**
	 * Detect if cpt are saved in theme's languages folder
	 * @since 2.0
	 *
	 */
	function is_saved_cpt_in_theme( $msg, $type, $context = "" ) {
		$thelist = array();
		$thelistsite = array();
		$thelist_local = array();
		$thelistsite_local = array();
		$outputsite = "";
		$localfile_site = "";
		$output = "";
		$localfile="";

		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));

			foreach ($listlanguages as $reflanguage) {
				if ( isset( $this->theme_mos[$reflanguage->slug]) ) {
					if ( $this->is_intheme_mos ( $msg, $type, $this->theme_mos[$reflanguage->slug], $context ) )
						$thelist[] = $reflanguage->name.".mo";
				}
				// local data
				if ( isset( $this->local_mos[$reflanguage->slug]) ) {
					if ( $this->is_intheme_mos ( $msg, $type, $this->local_mos[$reflanguage->slug], $context ) )
						$thelist_local[] = $reflanguage->name.".mo";
				}

				if ( is_multisite() ) {
					if ( isset($this->file_site_mos[$reflanguage->slug]) ) {
						if ( $this->is_intheme_mos ( $msg, $type, $this->file_site_mos[$reflanguage->slug], $context ) )
							$thelistsite[] = $reflanguage->name.".mo";
					}
					// local data
					if ( isset($this->file_site_local_mos[$reflanguage->slug]) ) {
						if ( $this->is_intheme_mos ( $msg, $type, $this->file_site_local_mos[$reflanguage->slug], $context ) )
							$thelistsite_local[] = $reflanguage->name.".mo";
					}
				}

			}

			$output = ($thelist == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in theme's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in theme's files: ","xili-dictionary").'" >'. implode(', ',$thelist).'</small></small>';

			$localfile = ($thelist_local == array()) ? '<small><span style="color:black" title="'.__("No translations saved in local-xx_XX .mo files","xili-dictionary").'">?</span></small>' : '<small><span style="color:green" title="'.__("Original with translations saved in local-xx_XX files: ","xili-dictionary").'" >'. implode(', ', $thelist_local ).'</small></small>';

			if ( is_multisite() ) {

				$outputsite = ($thelistsite == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in site's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in site's files: ","xili-dictionary").'" >'. implode(', ',$thelistsite).'</small></small>';

				$localfile_site = ($thelistsite_local == array()) ? '<small><span style="color:black" title="' . __("No translations saved in site's local .mo files","xili-dictionary").'">?</span></small>' : '<small><span style="color:green" title="'.__("Original with translations saved in site's local files: ","xili-dictionary").'" >'. implode(', ',$thelistsite_local).'</small></small>';

			}

			return array ( $output, $outputsite, $localfile, $localfile_site ) ;
		}
	}

	function is_saved_cpt_in_plugin( $plugin_path, $msg, $type, $context = "" ) {
		$thelist = array();
		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP, TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));

			foreach ($listlanguages as $reflanguage) {
				if ( isset( $this->plugin_mos[$plugin_path][$reflanguage->slug]) ) {
					if ( $this->is_intheme_mos ( $msg, $type, $this->plugin_mos[$plugin_path][$reflanguage->slug], $context ) ) {
						if ( isset( $this->plugin_mos[$plugin_path]['WLD'][$reflanguage->slug] )) {
							$thelist[] = '*&nbsp;' . $reflanguage->name.".mo"; //2.10.1
						} else {
							$thelist[] = $reflanguage->name.".mo";
						}
					}

				}
			}
			return $thelist ;
		}
	}


	function get_langfolder() {
		$xili_settings = get_option('xili_dictionary_settings');
		$langfolderset = $xili_settings['langs_folder'];
		$full_folder = ( $langfolderset !='' ) ? $langfolderset.'/' : '/';
		return $full_folder ;
	}

	/**
	 * Import POMO file in respective class PO or MO
	 *
	 *
	 * @since 2.3
	 */
	function import_POMO ( $extend = 'po', $lang = 'en_US', $local = '' , $pomofile = '', $multilocal = true ) {

		if ( in_array ( $extend, array ( 'po', 'mo') ) ) {

			if ( $extend == 'po' ) {
				$pomo = new PO();

				if ( $lang == $this->theme_domain() ) $extend = 'pot';

			} else {
				$pomo = new MO();
			}
			$path = "";

			if ( is_multisite() && $multilocal ) {
				if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
					$path = $uploads['basedir']."/languages/";
				}
			} else {
				if ( !is_child_theme() ) {
					$path = $this->active_theme_directory . $this->langfolder;
				} else {
					if ( 'parenttheme' == $local ) {
						$path = get_template_directory() . $this->parentlangfolder;
					} else {
						$path = get_stylesheet_directory() . $this->langfolder;
					}
				}
			}

			if ( $pomofile == "" && $local == 'local' ) {

				$pomofile = $path . $local . '-' . $lang. '.' . $extend ;

			} else if ( 'theme' == $local || 'parenttheme' == $local ) {

				$pomofile = $path . $lang . '.' . $extend ;

			} else if ( 'languages' == $local ) {

				$pomofile = WP_LANG_DIR . "/themes/". $this->theme_domain() . '-' . $lang . '.' . $extend ;

			}

			if ( file_exists( $pomofile ) ) {
				if ( !$pomo->import_from_file( $pomofile ) ) {
					return false;
				} else {
					return $pomo;
				}
			} else {
				return false;
			}


		} else {
			return false;
		}

	}

	/**
	 * Import MO file in class MO
	 *
	 *
	 * @since 1.0.2 - only WP >= 2.8.4
	 * @updated 1.0.5 - for wp-net
	 * @param lang
	 * @param $mofile since 1.0.5
	 * @updated 2.1 - local-xx_XX
	 */
	function pomo_import_MO ($lang = "", $mofile = "", $local = false, $plugin_path = false, $wp_lang_dir = false ) {
		$mo = new MO();

		if ( $plugin_path ) {
			$mofile = $this->path_plugin_file( $plugin_path, $lang, 'mo', $wp_lang_dir ); // 2.10.1
		} else {
			if ( $mofile == "" && $local == true ) {
				$mofile = $this->active_theme_directory.$this->langfolder.'local-'.$lang.'.mo';
			} else if ( '' == $mofile ) {
				$mofile = $this->active_theme_directory.$this->langfolder.$lang.'.mo';
			}
		}

		if ( file_exists( $mofile ) ) {
			if ( !$mo->import_from_file( $mofile ) ) {
				return false;
			} else {
				return $mo;
			}
		} else {
			return false;
		}
	}

	/**
	 * import mo for temporary diff mo files or check if saved
	 *
	 * @since 1.0.6
	 *
	 */
	function import_mo_file_wpmu ($lang = "", $istheme = true, $local = false ){
		if ($istheme == true) {
			return $this->pomo_import_MO ( $lang, "", $local );
		} else {
				global $wpdb;
				$thesite_ID = $wpdb->blogid;
				if ( ($uploads = wp_upload_dir()) && false === $uploads['error'] ) {
					//if ($thesite_ID > 1) {
						if ( $local == true ) {
							$mofile = $uploads['basedir']."/languages/local-".$lang.'.mo';
						} else {
							$mofile = $uploads['basedir']."/languages/".$lang.'.mo'; //normally inside theme's folder if root wp-net
						}

						return $this->pomo_import_MO ( $lang, $mofile, $local );
					//} else {
						//return false; // normally inside theme's folder if root wp-net
					//}
				} else {
					return false;
				}
		}
	}

	/**
	 * convert twinlines (msgid - msgstr) to MOs in wp-net
	 * @since 1.0.4
	 * @updated 2.0
	 * @params as from_twin_to_POMO and $superadmin
	 */
	function from_cpt_to_POMO_wpmu ($curlang, $obj='mo', $superadmin = false, $extract = array() )	{
		global $user_identity,$user_url,$user_email;
		// the table array
		$table_mo = $this->from_cpt_to_POMO( $curlang, $obj, $extract );
		$site_mo = new MO () ;

		$translation ='
	Project-Id-Version: theme: '.get_option("template").'\n
	Report-Msgid-Bugs-To: contact@xiligroup.com\n
	POT-Creation-Date: '.date("c").'\n
	PO-Revision-Date: '.date("c").'\n
	Last-Translator: '.$user_identity.' <'.$user_email.'>\n
	Language-Team: xili-dictionary WP plugin and '.$user_url.' <'.$user_email.'>\n
	MIME-Version: 1.0\n
	Content-Type: text/plain; charset=utf-8\n
	Content-Transfer-Encoding: 8bit\n
	Plural-Forms: '.$this->plural_forms_rule($curlang).'\n
	X-Poedit-Language: '.$curlang.'\n
	X-Poedit-Country: '.$curlang.'\n
	X-Poedit-SourceCharset: utf-8\n';

		$site_mo->set_headers($site_mo->make_headers($translation));
		// array diff
		if (false === $superadmin) {
		// special for superadmin who don't need diff.
		// the pomo array available in theme's folder
			$theme_mo = $this->import_mo_file_wpmu ( $curlang, true );
			if ( false !== $theme_mo ) {
				// without keys available in theme' mo
			$site_mo->entries = array_diff_key ( $table_mo->entries, $theme_mo->entries ); // those differents ex. categories
			// those with same keys but translations[0] diff
			$diff_mo_trans = array_uintersect_assoc ( $table_mo->entries, $theme_mo->entries, array(&$this,'test_translations') ) ;

			$site_mo->entries += $diff_mo_trans ;
			//print_r ( array_keys ( $diff_mo_trans ));

		}
		return $site_mo;
		} elseif ( $extract != '' ) {

		return $table_mo;
		}
	}

	function test_translations ( $table, $theme ) {
		if ( $table->translations[0] != $theme->translations[0] ) {
			if ( $table->singular == $theme->singular ) {
				//echo '--tQuote--not'.$table->translations[0];
				return 0;

			} else {
				return 1;
			}
		}
		if ( $table->singular > $theme->singular ) return 1;
		return -1;
	}

	/**
	 * return array of msgid objects
	 * @since 2.0
	 *
	 * @updated 2.1.2
	 */
	function get_cpt_msgids( $curlang, $pomo = "mo", $extract_array = array(), $not = false ) {
		global $wpdb, $wp_version;
		$like = ($not === true ) ? 'NOT LIKE' : 'LIKE' ;
		if ( $pomo == "mo" ) {

			if ( $extract_array == array() ) {

				return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						),
						array(
							'key' => $this->msglang_meta,
							'value' => $curlang,
							'compare' => $like // 2.1.2
						)
					)
				) );


			} else if ( isset ( $extract_array [ $this->msg_extracted_comments ] ) && isset ( $extract_array [  'like-'. $this->msg_extracted_comments ] ) && $extract_array [ 'like-'. $this->msg_extracted_comments ] === true ) {
				$extract =	$extract_array [ $this->msg_extracted_comments ];

					return get_posts( array(
					'numberposts' => -1, 'offset' => 0,
					'category' => 0, 'orderby' => 'ID',
					'order' => 'ASC', 'include' => array(),
					'exclude' => array(),
					'post_type' => XDMSG,
					'suppress_filters' => true,
					'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '='
							),
							array(
								'key' => $this->msglang_meta,
								'value' => $curlang,
								'compare' => 'LIKE'
							),
							array(
								'key' => $this->msg_extracted_comments,
								'value' => $extract,
								'compare' => 'LIKE'
							)
						)
					) );


			} else if ( isset ( $extract_array [ 'origin' ] ) ) {

				if ( !is_array( $extract_array [ 'origin' ] ) ) {

					$array_tax = array(
							'taxonomy' => 'origin',
							'field' => 'slug',
							'terms' => $extract_array [ 'origin' ]
						);

				} else {

					$array_tax = array(
							'taxonomy' => 'origin',
							'field' => 'slug',
							'terms' => $extract_array [ 'origin' ],
							'operator' => 'IN'
						);
				}

				if ( isset ( $extract_array [  'like-'. $this->msg_extracted_comments ] ) && $extract_array [ 'like-'. $this->msg_extracted_comments ] === false ) {

					if ( version_compare( $wp_version, '4.1-RC', '>=') ) {
						$meta_query = array(
							'relation' => 'OR', // better OR
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '='
								),
								array(
									'key' => $this->msglang_meta,
									'value' => $curlang,
									'compare' => 'LIKE'
								),
								array(
									'key' => $this->msg_extracted_comments,
									'compare' => 'NOT EXISTS'
								)
							),
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '='
								),
								array(
									'key' => $this->msglang_meta,
									'value' => $curlang,
									'compare' => 'LIKE'
								),
								array(
									'key' => $this->msg_extracted_comments,
									'value' => $extract_array [ $this->msg_extracted_comments ] ,
									'compare' => 'NOT LIKE'
								)
							)
						);

					} else {
						$meta_query = array(
							'relation' => 'AND',
							array(
								'key' => $this->msgtype_meta,
								'value' => 'msgid',
								'compare' => '='
							),
							array(
								'key' => $this->msglang_meta,
								'value' => $curlang,
								'compare' => 'LIKE'
							),
							array(
								'key' => $this->msg_extracted_comments,
								'value' => $extract_array [ $this->msg_extracted_comments ] ,
								'compare' => 'NOT LIKE'
							)
						);
					}

				} else {
					$meta_query = array(
						'relation' => 'AND',
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						),
						array(
							'key' => $this->msglang_meta,
							'value' => $curlang,
							'compare' => 'LIKE'
						)
					);

				}


				if ( $extract_array [ 'origin' ] == '' || $extract_array [ 'origin' ] == array() ) {

					return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => $meta_query
				) );

				} else {

				return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => $meta_query,
				'tax_query' => array(
						$array_tax
					)
				) );
				}

			}



		} else { // po

			if ( $extract_array == array() ) {
				// to have also empty translation
				$meta_key_val = $this->msgtype_meta;
				$meta_value_val = 'msgid';
				return get_posts( array(
					'numberposts' => -1, 'offset' => 0,
					'category' => 0, 'orderby' => 'ID',
					'order' => 'ASC', 'include' => array(),
					'exclude' => array(), 'post_type' => XDMSG,
					'suppress_filters' => true,
					'meta_query' => array(
						array (
							'meta_key' => $meta_key_val,
							'meta_value' =>$meta_value_val
							)
						),
					)
				);

			} else if ( isset ( $extract_array [ 'origin' ] ) ) {


				if ( !is_array( $extract_array [ 'origin' ] ) ) {

					$array_tax = array(
							'taxonomy' => 'origin',
							'field' => 'slug',
							'terms' => $extract_array [ 'origin' ]
						);

				} else {

					$array_tax = array(
							'taxonomy' => 'origin',
							'field' => 'slug',
							'terms' => $extract_array [ 'origin' ],
							'operator' => 'IN'
						);
				}

				if ( isset ( $extract_array [  'like-'. $this->msg_extracted_comments ] ) && $extract_array [ 'like-'. $this->msg_extracted_comments ] === false ) {

					if ( version_compare( $wp_version, '4.1-RC', '>=') ) {

						$meta_query = array(
							'relation' => 'OR', // better OR
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '='
								),
								array(
									'key' => $this->msg_extracted_comments,
									'value' => $extract_array [ $this->msg_extracted_comments ] ,
									'compare' => 'NOT LIKE'
								)
							),
							array(
								'relation' => 'AND',
								array(
									'key' => $this->msgtype_meta,
									'value' => 'msgid',
									'compare' => '='
								),
								array(
									'key' => $this->msg_extracted_comments,
									'compare' => 'NOT EXISTS'
								)
							)
						);

					} else {
						$meta_query = array(
							'relation' => 'AND',
							array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
							),
							array(
							'key' => $this->msg_extracted_comments,
							'value' => $extract_array [ $this->msg_extracted_comments ] ,
							'compare' => 'NOT LIKE'
							)
						);
					}

				} else {
					$meta_query = array(
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						)
					);
				}

				return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => $meta_query ,
				'tax_query' => array(
						$array_tax
					)
				) );

			} else {

				$extract =	$extract_array [ $this->msg_extracted_comments ];

				$like_or_not = ( $extract_array [ 'like-'. $this->msg_extracted_comments ] === true )? 'LIKE' : 'NOT LIKE' ;

				return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(),
				'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => $this->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						),
						array(
							'key' => $this->msg_extracted_comments,
							'value' => $extract ,
							'compare' => $like_or_not
						)
					)
				) );

			}
		}
	}

	/**
	 * return msgstr object (array translation)
	 * @since 2.0
	 */
	function get_cpt_msgstr( $cur_msgid_ID, $curlang, $plural = false ) {
		$res = get_post_meta ( $cur_msgid_ID, $this->msglang_meta, false );
		$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();

		if ( $plural ) {
			if ( isset ( $thelangs['msgstrlangs'][$curlang]['msgstr_0'] ) ) {
				$cur_msgstr_ID = $thelangs['msgstrlangs'][$curlang]['msgstr_0'];
				// get_parent (msgstr_0)
				$msgstr_array = array ( get_post( $cur_msgstr_ID ) ) ;
				// get_children
				$args = array(
							'numberposts' => -1, 'post_type' => XDMSG,
							'post_status' => 'publish', 'post_parent' => $cur_msgstr_ID
				);
				$children = get_posts( $args );
				return array_merge($msgstr_array, $children);
			} else {
				return false;
			}

		} else {
			if ( isset ( $thelangs['msgstrlangs'][$curlang]['msgstr'] ) ) {
				$cur_msgstr_ID = $thelangs['msgstrlangs'][$curlang]['msgstr'];
			// get_content
				return get_post( $cur_msgstr_ID );
			} else {
				return false;
			}
		}
	}

	/**
	 * convert cpt (msgid - msgstr) to MO or PO
	 *
	 * @since 2.0
	 *
	 * @updated 2.8.0
	 *
	 */

	function from_cpt_to_POMO ( $curlang, $po_obj='mo', $extract = array() ) {
		global $user_identity, $user_url, $user_email;
		if ($po_obj == 'mo') {
			$obj = 'mo';
			$mo = new MO(); /* par default */
		} else {
			$obj = 'po';
			$mo = new PO(); // po or pot - 2.8
		}

		/* header */

		if ( $po_obj == 'pot' ) {
			$translation ='
				Project-Id-Version: ' . $extract['projet_id_version'] . '\n
				Report-Msgid-Bugs-To: contact@xiligroup.com\n
				POT-Creation-Date: '.date("c").'\n
				MIME-Version: 1.0\n
				Content-Type: text/plain; charset=UTF-8\n
				Content-Transfer-Encoding: 8bit\n
				PO-Revision-Date: 2014-MO-DA HO:MI+ZONE\n
				Last-Translator: FULL NAME <EMAIL@ADDRESS>\n
				Language-Team: LANGUAGE <LL@domain-example.org>\n';
		} else {
			$translation ='
				Project-Id-Version: ' . $extract['projet_id_version'] . '\n
				Report-Msgid-Bugs-To: contact@xiligroup.com\n
				POT-Creation-Date: '.date("c").'\n
				PO-Revision-Date: '.date("c").'\n
				Last-Translator: '.$user_identity.' <'.$user_email.'>\n
				Language-Team: xili-dictionary WP plugin and '.$user_url.' <'.$user_email.'>\n
				MIME-Version: 1.0\n
				Content-Type: text/plain; charset=utf-8\n
				Content-Transfer-Encoding: 8bit\n
				Plural-Forms: '.$this->plural_forms_rule($curlang).'\n
				X-Poedit-Language: '.$curlang.'\n
				X-Poedit-Country: '.$curlang.'\n
				X-Poedit-SourceCharset: utf-8\n';
		}

		$mo->set_headers($mo->make_headers($translation));
		/* entries */

		$list_msgids = $this->get_cpt_msgids( $curlang, $obj, $extract ); // msgtype = msgid && $curlang in

		foreach ( $list_msgids as $cur_msgid ) {

			if ( $cur_msgid->post_content == '++' ) continue; // no empty msgid

			$getctxt = get_post_meta( $cur_msgid->ID , $this->ctxt_meta, true ) ;
			$cur_msgid->ctxt = ( $getctxt == "" ) ? false : $getctxt;

			$cur_msgid->plural = false ;
			$res = get_post_meta ( $cur_msgid->ID, $this->msgchild_meta, false );
			$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : false;

			if ( $thechilds ) {
				if ( isset ( $thechilds['msgid']['plural'] ) ) {
					$cur_msgid->plural = true ;
					$plural_ID = $thechilds['msgid']['plural'];

					$post_child_msgid = get_post( $plural_ID );
					$cur_msgid->plural_post_content = $post_child_msgid -> post_content ;
				}
			}
			// force empty translation if pot...
			$list_msgstr = ( $po_obj == 'pot' ) ? false : $this->get_cpt_msgstr( $cur_msgid->ID, $curlang, $cur_msgid->plural );

			$noentry = true; /* to create po with empty translation */
			if ( false !== $list_msgstr ) {
				if ($obj == 'mo') {
					if ( $cur_msgid->plural === false ) {
						if ( false === $cur_msgid->ctxt ) {
							$original = $cur_msgid->post_content;
						} else {
							$original = $cur_msgid->ctxt . chr(4) . $cur_msgid->post_content ;
						}
						$mo->add_entry( $mo->make_entry( $original, $list_msgstr->post_content ) );

					} else {
						$list_msgstr_plural_post_content = array();
						foreach ( $list_msgstr as $one_msgstr ) {
							$list_msgstr_plural_post_content[] = $one_msgstr->post_content ;
						}
						if ( false === $cur_msgid->ctxt ) { // PLURAL
							$original = $cur_msgid->post_content . chr(0) . $cur_msgid->plural_post_content ;
							$translation = implode( chr(0), $list_msgstr_plural_post_content );
							$mo->add_entry($mo->make_entry($original, $translation));
						} else { // CONTEXT + PLURAL
							$original = $cur_msgid->ctxt .chr(4). $cur_msgid->post_content . chr(0) . $cur_msgid->plural_post_content ;
							$translation = implode( chr(0), $list_msgstr_plural_post_content );
							$mo->add_entry( $mo->make_entry($original, $translation) );
						}
					}

				} else { /* po */

					// comments prepare
					// *	- translator_comments (string) -- comments left by translators
					// *	- extracted_comments (string) -- comments left by developers
					// *	- references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
					// *	- flags (array) -- flags like php-format

					$comment_array = array(); // $list_msgstr because in msgstr (20120318)

					if ( $cur_msgid->plural === false ) {
						$translator_comments = get_post_meta ( $list_msgstr->ID, $this->msg_translator_comments, true );
						if ( $translator_comments != '' ) $comment_array['translator_comments'] = $translator_comments;
					} else {
						$translator_comments = get_post_meta ( $list_msgstr[0]->ID, $this->msg_translator_comments, true );
						if ( $translator_comments != '' ) $comment_array['translator_comments'] = $translator_comments;
					}

					$extracted_comments = get_post_meta ( $cur_msgid->ID, $this->msg_extracted_comments, true );
					if ( $extracted_comments != '' ) $comment_array['extracted_comments'] = $extracted_comments;
					if ( $cur_msgid->post_excerpt != '' ) {
						$references = explode ('#: ', $cur_msgid->post_excerpt );
						$comment_array['references'] = $references;
					}
					$flags = get_post_meta ( $cur_msgid->ID, $this->msg_flags, true );
					if ( $flags != '' ) {
						$comment_array['flags'] = explode (', ', $flags );
					}

					if ( $cur_msgid->plural === false ) {
						if ( false === $cur_msgid->ctxt ) {
							$entry_array = array('singular'=>$cur_msgid->post_content,'translations'=> array( $list_msgstr ->post_content ) ) ;
						} else {
							$entry_array = array('context'=>$cur_msgid->ctxt, 'singular'=>$cur_msgid->post_content, 'translations'=> array($list_msgstr ->post_content) );
						}
					} else { // PLURAL
						$list_msgstr_plural_post_content = array();
						foreach ( $list_msgstr as $one_msgstr ) {
							$list_msgstr_plural_post_content[] = $one_msgstr->post_content ;
						}

						if ( false === $cur_msgid->ctxt ) {
							$entry_array = array('singular' => $cur_msgid->post_content,'plural' => $cur_msgid->plural_post_content, 'is_plural' =>1, 'translations' => $list_msgstr_plural_post_content );
						} else { // CONTEXT + PLURAL
							$entry_array = array('context'=>$cur_msgid->ctxt, 'singular' => $cur_msgid->post_content, 'plural' => $cur_msgid->plural_post_content, 'is_plural' =>1, 'translations' => $list_msgstr_plural_post_content );
						}
					}
					$entry = new Translation_Entry( array_merge ( $entry_array, $comment_array ) );

					$mo->add_entry($entry);
					$noentry = false;
				}
			}
			/* to create po with empty translations */
			if ($obj == 'po' && $noentry == true) { // noentry forced by pot
				$comment_array = array(); // $list_msgstr because in msgstr (20120318)


				$extracted_comments = get_post_meta ( $cur_msgid->ID, $this->msg_extracted_comments, true );
				if ( $extracted_comments != '' ) $comment_array['extracted_comments'] = $extracted_comments;
				if ( $cur_msgid->post_excerpt != '' ) {
					$references = explode ('#: ', $cur_msgid->post_excerpt );
					$comment_array['references'] = $references;
				}
				$flags = get_post_meta ( $cur_msgid->ID, $this->msg_flags, true );
				if ( $flags != '' ) {
					$comment_array['flags'] = explode (', ', $flags );
				}

				// 2.8. - improve for po empty and context of plural

				if ( $cur_msgid->plural === false ) {
					if ( false === $cur_msgid->ctxt ) {
						$entry_array = array('singular'=>$cur_msgid->post_content,'translations'=> "" ) ;
					} else {
						$entry_array = array('context'=>$cur_msgid->ctxt, 'singular'=>$cur_msgid->post_content, 'translations'=> "" );
					}
				} else {
					if ( false === $cur_msgid->ctxt ) {
							$entry_array = array('singular' => $cur_msgid->post_content,'plural' => $cur_msgid->plural_post_content, 'is_plural' => 1, 'translations' => "" );
					} else { // CONTEXT + PLURAL
						$entry_array = array('context'=>$cur_msgid->ctxt, 'singular' => $cur_msgid->post_content, 'plural' => $cur_msgid->plural_post_content, 'is_plural' => 1, 'translations' => "" );
					}
				}
				$entry = new Translation_Entry( array_merge ( $entry_array, $comment_array ) ) ;

				$mo->add_entry($entry);
			}
		}



		return $mo;
	}

	/**
	 * Save MO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 * @updated 1.0.5 - wp-net
	 *
	 * @updated 2.1
	 */
	function Save_MO_to_file ($curlang , $mo, $createfile = "" )	{
		$filename = ( strlen ($curlang) == 5 ) ? substr($curlang,0,3).strtoupper(substr($curlang,-2)) : $curlang;
		$filename .= '.mo';
		if ("" == $createfile)
			$createfile = $this->active_theme_directory.$this->langfolder.$filename;
		//echo $createfile;
		if ( false === $mo->export_to_file( $createfile ) ) return false;
	}

	/**
	 * Save PO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 *
	 * @updated 2.1
	 */
	function Save_PO_to_file ($curlang , $po, $createfile = "" )	{
		$filename = ( strlen ($curlang) == 5 ) ? substr($curlang,0,3).strtoupper(substr($curlang,-2)) : $curlang;
		$filename .= '.po';
		if ( "" == $createfile)
			$createfile = $this->active_theme_directory . $this->langfolder . $filename;
			xili_xd_error_log ( '# '. __LINE__ . ' ---- XD po file ------- '.$createfile );
		if ( false === $po->export_to_file( $createfile ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * thanks to http://urbangiraffe.com/articles/translating-wordpress-themes-and-plugins/2/#plural_forms
	 * @since 1.0 - only WP >= 2.8
	 *
	 * called when creating po
	 */
	function plural_forms_rule( $curlang ) {
		$curlang = ( strlen ($curlang) == 5 ) ? substr($curlang,0,3).strtoupper(substr($curlang,-2)) : $curlang;
		$rulesarrays = array(
		'nplurals=1; plural=0' => array('tr_TR','ja_JA','ja'),
		'nplurals=2; plural=1' => array('zh_ZH'),
		'nplurals=2; plural=n != 1' => array('en_US','en_UK','es_ES','da_DA'),
		'nplurals=2; plural=n>1' => array('fr_FR','fr_CA','fr_BE','pt_BR'),
		'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2' => array('lv_LV'),
		'nplurals=3; plural=n==1 ? 0 : n==2 ? 1 : 2' => array('gd_GD'),
		'nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2' => array('lt_LT'),
		'nplurals=3; plural=n%100/10==1 ? 2 : n%10==1 ? 0 : (n+9)%10>3 ? 2 : 1' => array('hr_HR','cs_CS','ru_RU','uk_UK'),
		'nplurals=3; plural=(n==1) ? 1 : (n>=2 && n<=4) ? 2 : 0' => array('sk_SK'),
		'nplurals=3; plural=n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2' => array('pl_PL'),
		'nplurals=4; plural=n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3' => array('sl_SL')
		);
		foreach ($rulesarrays as $rule => $langs) {
			if (in_array($curlang, $langs)) return $rule;
		}
		return 'nplurals=2; plural=n != 1'; /* english and most... */
	}

	/**
	 * bloginfo term and others in cpt
	 * @since 2.0
	 *
	 */
	function xili_import_infosterms_cpt () {
		global $wp_version;
		$curlang = get_locale() ; // admin language of config - import id and str

		$this->importing_mode = true ;
		$msg_counters = array ( 0, 0, 0); // to import, imported, msgstr
		$terms_to_import = array();
		$temp = array ();
		$temp['msgid'] = get_bloginfo( 'blogname', 'display' );
		$temp['extracted_comments'] = $this->local_tag.' bloginfo - blogname';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = get_bloginfo( 'description', 'display' );
		$temp['extracted_comments'] = $this->local_tag.' bloginfo - description';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = addslashes ( get_option('time_format') );
		$temp['extracted_comments'] = $this->local_tag.' bloginfo - time_format';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = addslashes ( get_option('date_format') );
		$temp['extracted_comments'] = $this->local_tag.' bloginfo - date_format';
		$terms_to_import[] = $temp ;

		if ( class_exists ('xili_language') ) {
			global $xili_language;
			foreach ( $xili_language->comment_form_labels as $key => $label) {
				$temp = array();
				$temp['msgid'] = $label ;
				if ( $key == 'comment' ) $temp['ctxt'] = 'noun'; // 2.4.0
				$temp['extracted_comments'] = $this->local_tag.' comment_form_labels '.$key;
				if ('en_US' != $curlang ) {
					$temp['msgstr'] = $this->default_translate_no_plural ( $temp );
				}
				$terms_to_import[] = $temp ;
			}

			// added 2.10.0 to extract comment default msgid excluding comment_form_labels
			$from_file_count = $this->import_msgid_from_one_wp_file ( ABSPATH . WPINC, "comment-template", $xili_language->comment_form_labels, true, true ); // local + msgstr
			$msg_counters[1] = $from_file_count;
			$msg_counters[0] += $from_file_count;
			// added 2.11.2 from post-template.php (password and private test)
			$temp = array ();
			$temp[0]['msgid'] = 'Protected: %s';
			$temp[1]['msgid'] = 'Private: %s';
			$temp[2]['msgid'] = '(more&hellip;)';
			$temp[3]['msgid'] = 'There is no excerpt because this is a protected post.';
			$temp[4]['msgid'] = 'Pages:';
			$temp[5]['msgid'] = 'Next page';
			$temp[6]['msgid'] = 'Previous page';
			$temp[7]['msgid'] = 'This content is password protected. To view it please enter your password below:';
			$temp[8]['msgid'] = 'Password:';
			$temp[9]['msgid'] = 'Submit';

			foreach ($temp as $oneline){
		  		$oneline['msgstr'] = "";
		  		if ('en_US' != $curlang ) {
		  			$oneline['msgstr'] = $this->default_translate_no_plural ( $oneline );
		  		}
		  		$oneline['extracted_comments'] = $this->local_tag.' post-template';
		  		$terms_to_import[] = $oneline;
		  	}

			// language description
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			foreach ($listlanguages as $reflanguage) { // 2.1
				$temp = array();
				$temp['msgid'] = $reflanguage->description ;
				$temp['extracted_comments'] = $this->local_tag.' language with ISO '.$reflanguage->name;
				$terms_to_import[] = $temp ;
				$temp = array();
				$temp['msgid'] = $reflanguage->description ;
				$temp['ctxt'] = 'linktitle';
				$temp['extracted_comments'] = $this->local_tag.' language with ISO '.$reflanguage->name . ' ctxt=linktitle';
				$terms_to_import[] = $temp ;
				$temp = array();
				$temp['msgid'] = $reflanguage->description ;
				$temp['ctxt'] = 'otherposts';
				$temp['extracted_comments'] = $this->local_tag.' language with ISO '.$reflanguage->name . ' ctxt=otherposts';
				$terms_to_import[] = $temp ;
				$temp = array();
				$temp['msgid'] = $reflanguage->description ;
				$temp['ctxt'] = 'searchform';
				$temp['extracted_comments'] = $this->local_tag.' language with ISO '.$reflanguage->name . ' ctxt=searchform';
				$terms_to_import[] = $temp ;
			}


			if ( version_compare( XILILANGUAGE_VER, '2.3.9', '>' ) ) { // msgid and msgstr
				global $wp_locale;
				$wp_locale_array_trans = array (
					'Sunday' => $wp_locale->weekday[0], 'Monday' => $wp_locale->weekday[1], 'Tuesday' => $wp_locale->weekday[2],
					'Wednesday' => $wp_locale->weekday[3], 'Thursday' => $wp_locale->weekday[4], 'Friday' => $wp_locale->weekday[5],
					'Saturday' => $wp_locale->weekday[6],
					'S_Sunday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[0]],
					'M_Monday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[1]],
					'T_Tuesday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[2]],
					'W_Wednesday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[3]],
					'T_Thursday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[4]],
					'F_Friday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[5]],
					'S_Saturday_initial' => $wp_locale->weekday_initial[$wp_locale->weekday[6]],
					'Sun' => $wp_locale->weekday_abbrev[$wp_locale->weekday[0]],
					'Mon' => $wp_locale->weekday_abbrev[$wp_locale->weekday[1]],
					'Tue' => $wp_locale->weekday_abbrev[$wp_locale->weekday[2]],
					'Wed' => $wp_locale->weekday_abbrev[$wp_locale->weekday[3]],
					'Thu' => $wp_locale->weekday_abbrev[$wp_locale->weekday[4]],
					'Fri' => $wp_locale->weekday_abbrev[$wp_locale->weekday[5]],
					'Sat' => $wp_locale->weekday_abbrev[$wp_locale->weekday[6]],
					'January' => $wp_locale->month['01'], 'February' => $wp_locale->month['02'],
					'March' => $wp_locale->month['03'], 'April' => $wp_locale->month['04'], 'May' => $wp_locale->month['05'],
					'June' => $wp_locale->month['06'], 'July' => $wp_locale->month['07'], 'August' => $wp_locale->month['08'],
					'September' => $wp_locale->month['09'], 'October' => $wp_locale->month['10'], 'November' => $wp_locale->month['11'],
					'December' => $wp_locale->month['12'],
					'Jan_January_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['01']],
					'Feb_February_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['02']],
					'Mar_March_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['03']],
					'Apr_April_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['04']],
					'May_May_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['05']],
					'Jun_June_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['06']],
					'Jul_July_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['07']],
					'Aug_August_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['08']],
					'Sep_September_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['09']],
					'Oct_October_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['10']],
					'Nov_November_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['11']],
					'Dec_December_abbreviation' => $wp_locale->month_abbrev[$wp_locale->month['12']],
					'am' => $wp_locale->meridiem['am'], 'pm' => $wp_locale->meridiem['pm'],
					'AM' => $wp_locale->meridiem['AM'], 'PM' => $wp_locale->meridiem['PM'],
					'number_format_thousands_sep' => $wp_locale->number_format['thousands_sep'], 'number_format_decimal_point' => $wp_locale->number_format['decimal_point']
					);
				if ( isset ( $wp_locale->text_direction) ) {
					$wp_locale_array_trans['text_direction'] = $wp_locale->text_direction; //_x( 'ltr', 'text direction', $theme_domain ) )) )
				}

				$temp = array();
				foreach ( $wp_locale_array_trans as $key => $value ) {
					$temp['msgid'] = $key ;
					if ('en_US' != $curlang ) {
		  				$temp['msgstr'] = $value;
		  			}
		  			if ( $key == 'text_direction' ) {
							$temp['ctxt'] = 'text direction';
					}
					$temp['extracted_comments'] = $this->local_tag.' wp_locale '.$key;
					$terms_to_import[] = $temp ;
				}
			}
			if ( version_compare( XILILANGUAGE_VER, '2.8.6', '>') ) { // since 2.8.7
				if ( isset ( $xili_language->xili_settings['list_link_title'] ) && $xili_language->xili_settings['list_link_title'] != array() ) {
					$temp = array();
					foreach ( $xili_language->xili_settings['list_link_title'] as $key => $title ) {

						$temp['msgid'] = $title ;
						$temp['extracted_comments'] = $this->local_tag.' language list title '.$key;
						$terms_to_import[] = $temp ;

					}

				}
			}
			if ( version_compare( $wp_version, '4.0', '>') ) { //2.10.3
				$temp = array ();
				// msgi used in fonction get_the_archive_title()
				$temp[0]['msgid'] = 'Category: %s';
				$temp[1]['msgid'] = 'Tag: %s';
				$temp[2]['msgid'] = 'Author: %s';
				$temp[3]['msgid'] = 'Year: %s';
				$temp[4]['msgid'] = 'Y';
				$temp[4]['ctxt'] = 'yearly archives date format';
				$temp[5]['msgid'] = 'Month: %s';
				$temp[6]['msgid'] = 'F Y';
				$temp[6]['ctxt'] = 'monthly archives date format';
				$temp[7]['msgid'] = 'Day: %s';
				$temp[8]['msgid'] = 'F j, Y';
				$temp[8]['ctxt'] = 'daily archives date format';
				$temp[9]['msgid'] = 'Asides';
				$temp[9]['ctxt'] = 'post format archive title';
				$temp[10]['msgid'] = 'Galleries';
				$temp[10]['ctxt'] = 'post format archive title';
				$temp[11]['msgid'] = 'Images';
				$temp[11]['ctxt'] = 'post format archive title';
				$temp[12]['msgid'] = 'Videos';
				$temp[12]['ctxt'] = 'post format archive title';
				$temp[13]['msgid'] = 'Quotes';
				$temp[13]['ctxt'] = 'post format archive title';
				$temp[14]['msgid'] = 'Links';
				$temp[14]['ctxt'] = 'post format archive title';
				$temp[15]['msgid'] = 'Statuses';
				$temp[15]['ctxt'] = 'post format archive title';
				$temp[16]['msgid'] = 'Audio';
				$temp[16]['ctxt'] = 'post format archive title';
				$temp[17]['msgid'] = 'Chats';
				$temp[17]['ctxt'] = 'post format archive title';
				$temp[18]['msgid'] = 'Archives: %s';
				$temp[19]['msgid'] = '%1$s: %2$s';

			  	foreach ($temp as $oneline){
			  		$oneline['msgstr'] = "";
			  		if ('en_US' != $curlang ) {
			  			$oneline['msgstr'] = $this->default_translate_no_plural ( $oneline );
			  		}
			  		$oneline['extracted_comments'] = $this->local_tag.' get_the_archive_title';
			  		$terms_to_import[] = $oneline;
			  	}
			}
		}

		if ( isset( $_POST['xmlimport' ]) ) {
			$to_be_filtered = $this->get_xml_contents();

			if ( $to_be_filtered ) {
				$theme_slug = get_option( 'stylesheet' );
				$oneline = array();
				foreach ( $to_be_filtered[$theme_slug] as $config_name ) {
					//$filtername = 'theme_mod_' . $config_name ;
					$value = get_theme_mod ( $config_name, false );
					if ( $value &&  is_string( $value ) ) {
						$oneline['msgid'] = $value;
						$oneline['extracted_comments'] = $this->local_tag.' theme_mod_' . $config_name ;
						$terms_to_import[] = $oneline;
					}
				}
			}
		}

		// shortcode [linked-post-in lang="fr_fr"]Voir cet article[/linked-post-in] - 2.18.2
		$oneline = array();
		$oneline['msgid'] = 'A similar post in %s';
		$oneline['ctxt'] = 'linktitle'; // default context
		$oneline['extracted_comments'] = $this->local_tag.' shortcode_linked-post-in';
		$terms_to_import[] = $oneline;

		// import the series...
		$msg_counters[0] += count( $terms_to_import );
		foreach ( $terms_to_import as $term ) {

			$the_context = null;

			if ( $term['msgid'] == 'text_direction' ) {
				$the_context = 'text direction';
			}
			if ( isset ( $term['ctxt'] ) ) { // 2.3.6
				$the_context = $term['ctxt'];
			}

			$result = $this->msgid_exists ( $term['msgid'], $the_context ) ;

			$t_entry = array();
			$t_entry['extracted_comments'] = $term['extracted_comments'] ;
			$entry = (object) $t_entry ;

			if ( $result === false ) {
				// create the msgid

				$msgid_post_ID = $this->insert_one_cpt_and_meta( $term['msgid'], $the_context, 'msgid', 0, $entry ) ;
				$msg_counters[1]++;
			} else {
				$msgid_post_ID = $result[0];
			}

			if ( isset ( $term['msgstr'] ) && '' !=  $term['msgstr'] ) { // now insert msgstr if exists

				$value = $term['msgstr'];
				$result = $this->msgstr_exists ( $value, $msgid_post_ID, $curlang ) ; // with lang of default (admin side)

				if ( $result === false ) {
					$msgstr_post_ID = $this->insert_one_cpt_and_meta( $value, $the_context, 'msgstr', 0, $entry );
					$msg_counters[2]++;
					wp_set_object_terms( $msgstr_post_ID, $this->target_lang($curlang), TAXONAME );
				} else {
					$msgstr_post_ID = $result[0];
				}

				// create link according lang

				$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
				$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				$thelangs['msgstrlangs'][$curlang]['msgstr'] = $msgstr_post_ID;
				update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
				update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_post_ID );

			}
		}
		$this->importing_mode = false ;
		return $msg_counters;

	}

	/**
	 * add default translate
	 *
	 */
	function default_translate_no_plural ( $oneline ) {
		$line = array();
		$is_plural = false;
		$line = $oneline ;

		if ( isset ($line['ctxt']) ) {
			return translate_with_gettext_context( $line['msgid'], $line['ctxt']);
		} else {
			return translate ( $line['msgid'] );
		}
	}

	/**
	 * taxonomy's terms in array (name - description)
	 * by default taxonomy
	 *
	 */
	function xili_read_catsterms_cpt( $taxonomy = 'category', $local_tag = '[local]' ){
		$this->importing_mode = true ;
		$msg_counters = array ( 0, 0, array(), array() ); // q of term, description, list ids checked, imported
		$listcategories = get_terms( $taxonomy, array('hide_empty' => false));
		foreach ($listcategories as $category) {

			$result = $this->msgid_exists ( $category->name ) ;

			$t_entry = array();
			$t_entry['extracted_comments'] = sprintf ( $local_tag.' name from %s with slug %s', $taxonomy, $category->slug ) ;
			$entry = (object) $t_entry ;

			if ( $result === false ) {
				// create the msgid
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $category->name, null, 'msgid', 0, $entry ) ;
				$msg_counters[0]++;
				if ( $msgid_post_ID ) $msg_counters[3][] = $msgid_post_ID ;
			} else {
				$msgid_post_ID = $result[0];
				// add comment in existing ?
			}

			if ( $msgid_post_ID ) $msg_counters[2][] = $msgid_post_ID ;

			$result = $this->msgid_exists ( $category->description ) ;

			$t_entry = array();
			$t_entry['extracted_comments'] = sprintf ( $this->local_tag.' desc from %s with slug %s', $taxonomy, $category->slug ) ;
			$entry = (object) $t_entry ;

			if ( $result === false ) {
				// create the msgid
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $category->description, null, 'msgid', 0, $entry ) ;
				$msg_counters[1]++;
				if ( $msgid_post_ID ) $msg_counters[3][] = $msgid_post_ID ;
			} else {
				$msgid_post_ID = $result[0];
				// add comment in existing ?
			}

			if ( $msgid_post_ID ) $msg_counters[2][] = $msgid_post_ID;

		}

		$this->importing_mode = false ;
		return $msg_counters;
	}

	/**
	 * Insert entries previously extracted from files
	 *
	 * @since 2.8.1
	 *
	 */
	function from_entries_to_xdmsg ( $originals, $origin_theme = 'twentyfourteen-xili', $backup_pot = array(), $curlang = '' ) {

		$temp_po = new PO;
		$temp_po->entries = $originals->entries;
		$lines = 0;
		$this->importing_mode = true; // not manual
		foreach ( $temp_po->entries as $pomsgid => $pomsgstr ) {

			$this->pomo_entry_to_xdmsg ( $pomsgid, $pomsgstr, $curlang, array( 'importing_po_comments'=>'replace', 'origin_theme'=>$origin_theme ) );
			$lines++;
		}
		$this->importing_mode = false;
		if ( $backup_pot ) {

			$temp_po->set_header( 'Project-Id-Version', $backup_pot['title'] );
			$temp_po->set_header( 'Report-Msgid-Bugs-To', 'http://dev.xiligroup.com/' );
			$temp_po->set_header( 'POT-Creation-Date', gmdate( 'Y-m-d H:i:s+00:00' ) );
			$temp_po->set_header( 'MIME-Version', '1.0' );
			$temp_po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
			$temp_po->set_header( 'Content-Transfer-Encoding', '8bit' );
			$temp_po->set_header( 'PO-Revision-Date', gmdate( 'Y' ) . '-MO-DA HO:MI+ZONE' );
			$temp_po->set_header( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
			$temp_po->set_header( 'Language-Team', 'LANGUAGE <EMAIL@ADDRESS>' );

			// Write POT file
			$result = $temp_po->export_to_file( $backup_pot['file'] );

		}
		unset( $temp_po );
		return $lines;
	}

	/**
	 * Recursive search of files in a path
	 * @since 1.0
	 *
	 */
	function find_files($path, $pattern, $callback, $param = null) {
		//$path = rtrim(str_replace("\\", "/", $path), '/') . '/';
		$matches = Array();
		$entries = Array();
		$dir = dir($path);
		while (false !== ($entry = $dir->read())) {
			$entries[] = $entry;
		}
		$dir->close();
		foreach ($entries as $entry) {
			$fullname = $path .$this->ossep. $entry;
			if ($entry != '.' && $entry != '..' && is_dir($fullname)) {
				$this->find_files($fullname, $pattern, $callback, $param );
			} else if (is_file($fullname) && preg_match($pattern, $entry)) {
				if ( $param ) {
					call_user_func($callback, $path , $entry, $param );
				} else {
					call_user_func($callback, $path , $entry );
				}
			}
		}
	}

	/**
	 * return array with states of a file - thanks to Geert
	 */
	function state_of_file ( $file ) {
		return array(
		'file'        => $file,
		'file_exists' => file_exists( $file ),
		'is_readable' => is_readable( $file ),
		'is_writable' => is_writable( $file ),
		'filemtime'   => ( is_readable( $file ) ) ? filemtime( $file ) : false,
		'filesize'    => ( is_readable( $file ) ) ? filesize( $file ) : false
		);
	}

	function display_file_states ( $file_state ) {
		$return = "";
		if ( $file_state['file_exists'] ) {
			$return .= sprintf(" <strong>%-40s</strong> [%sreadable] [%swritable]<br />", basename( $file_state['file'] ), $file_state['is_readable'] ? "" : "not ", $file_state['is_writable'] ? "" : "not ");

		} else {
			$return .= sprintf(" %-40s  [do not exists]<br />", basename( $file_state['file'] ) );
			// test dirname
			$return .= sprintf(" <strong>%-40s</strong> [%swritable]<br />", basename( $file_state['file'] ), is_writable( dirname($file_state['file']) ) ? "" : "not ");
		}

		$return .= sprintf("   * Path: %s<br />", dirname( $file_state['file'] ) . '/');
		$return .= sprintf("   * Size: %s<br />", $file_state['file_exists'] ? number_format( $file_state['filesize'], 0 ) : '--');
		$return .= sprintf("   * Last updated: %s<br />", $file_state['filemtime'] ? @date( 'F jS Y H:i:s', $file_state['filemtime'] ) : '--');
		return $return ;
	}

	/**
	 * display lines of files in special sidebox
	 * @since 1.0
	 */
	function available_mo_files( $path , $filename ) {
		$langfolder = str_replace($this->active_theme_directory, '', $path);
		if ( $langfolder == "" ) $langfolder = '/';
		$shortfilename = str_replace(".mo","",$filename );
		$alert = '<span style="color:red;">'.__('Uncommon filename','xili-dictionary').'</span>' ;
		if ( strlen($shortfilename)!=5 && strlen($shortfilename) != 2 ) {
			if ( false === strpos( $shortfilename, 'local-' ) ) {
				$message = $alert;
			} else {
				$message = '<em>'.__("Site's values",'xili-dictionary').'</em>';
			}

		} else if ( false === strpos( $shortfilename, '_' ) && strlen($shortfilename) == 5 ) {
			$message = $alert;
		} else {
			$message = '';
		}

		echo $shortfilename . " (". $langfolder . ") " . $message . "<br />";
	}

	function start_detect_plugin_msg () {
		$this->domain_to_detect_list = get_option ( 'xd_test_importation_list' , array() ) ;
	}

	function detect_plugin_frontent_msg ($translation, $text, $domain ) {
		global $locale;
		$domain_to_detect = get_option ( 'xd_test_importation' , false ) ;

		if ( $domain_to_detect && $domain == $domain_to_detect && isset ( $this->domain_to_detect_list ) ) {
			if ( !isset( $this->domain_to_detect_list[$locale] ) || !in_array ( array ('msgid' => $text , 'msgstr' => $translation ), $this->domain_to_detect_list[$locale] ) )
				$this->domain_to_detect_list[$locale][] = array ('msgid' => $text , 'msgstr' => $translation );


		}

		return $translation;
	}

	function end_detect_plugin_msg () {
		if ( isset ( $this->domain_to_detect_list ) )
			update_option ( 'xd_test_importation_list', $this->domain_to_detect_list ) ;
	}

	function import_plugin_collected_msgs ( $locale ) {

		$collected_msgs = get_option ( 'xd_test_importation_list' );

		if ( isset ( $collected_msgs[$locale] ) && is_array( $collected_msgs[$locale] ) && $collected_msgs[$locale] != array() ) {

			$the_context = null;

			foreach ( $collected_msgs[$locale] as $oneline ) {

				$t_entry = array();
				$t_entry['extracted_comments'] = $this->local_tag.' plugin ';

				$entry = (object) $t_entry ;

				$result = $this->msgid_exists ( $oneline['msgid'], $the_context ) ;

				if ( $result === false ) {
					// create the msgid

					$msgid_post_ID = $this->insert_one_cpt_and_meta( $oneline['msgid'], $the_context, 'msgid', 0, $entry ) ;

				} else {
					$msgid_post_ID = $result[0];

				}



				$result = $this->msgstr_exists ( $oneline['msgstr'], $msgid_post_ID, $locale ) ;
				if ( $result === false ) {
					$msgstr_post_ID = $this->insert_one_cpt_and_meta( $oneline['msgstr'], $the_context, 'msgstr', 0, $entry );

					wp_set_object_terms( $msgstr_post_ID, $this->target_lang($locale), TAXONAME );
				} else {
					$msgstr_post_ID = $result[0];
				}

				// create link according lang

				$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
				$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
				$thelangs['msgstrlangs'][$locale]['msgstr'] = $msgstr_post_ID;
				update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
				update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_post_ID );

			}

			$nbterms = count( $collected_msgs[$locale] ) ;

		} else {

			$nbterms = 0 ;
		}
		return $nbterms;
	}

	// with xili-language, it is now possible to export/import xml with language
	function message_export_limited() {
		echo '<div class="error"><p>'. __ ('WARNING: xili-dictionary datas are not ready to be imported from XML generated here. <br />So, it is fully recommended to <strong>desactivate temporarily xili-dictionary</strong> during export and so, avoid clutter of the file.', 'xili-dictionary') . '</p>'
		.'<p>'. __ ('To export/import/backup dictionary datas, use .po files built by the plugin.', 'xili-dictionary') . '</p></div>';
	}



	/**** Functions that improve taxinomy.php - avoid conflict if xl absent ****/
	function get_terms_of_groups_lite ($group_ids, $taxonomy, $taxonomy_child, $order = '') {
		global $wpdb;
		if ( !is_array($group_ids) )
			$group_ids = array($group_ids);
		$group_ids = array_map('intval', $group_ids);
		$group_ids = implode(', ', $group_ids);
		$theorderby = '';

		// lite release
		if ($order == 'ASC' || $order == 'DESC') $theorderby = ' ORDER BY tr.term_order '.$order ;

		$query = "SELECT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".$theorderby;

		$listterms = $wpdb->get_results($query);
		if ( ! $listterms )
			return array();

		return apply_filters ( 'other_multilingual_plugin_filter_terms', $listterms );
	}

	// used onetime from v2 to v2.1
	function update_postmeta_msgid() {
		global $wpdb;
		// scan msgid
		$query = sprintf("SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = '%s'
			AND $wpdb->posts.ID NOT IN
			( SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = '%s' )
			ORDER BY $wpdb->posts.ID ASC", XDMSG, $this->msglang_meta );
		$listposts = $wpdb->get_results($query, ARRAY_A);
		// test if no postmeta lang
		$i = 0;
		if ( $listposts ) {
			foreach ( $listposts as $onepost ) {
				if ( 'msgid' == get_post_meta ( $onepost['ID'], $this->msgtype_meta, true ) ) {
					// update
					update_post_meta ( $onepost['ID'], $this->msglang_meta, array() );
					++$i;
				}
			}
		}
		xili_xd_error_log ( '# '. __LINE__ . ' XD MSG - Updated 2.0->2.1 '.count($i) ) ;
	}

	/****************** NEW ADMIN UI *******************/
	/**
	* @since 2.3
	*/
	function admin_menus() {

		$hooks = array();

		$hooks[] = add_submenu_page('edit.php?post_type='.XDMSG,
				__( 'Erasing', 'xili-dictionary' ),
				__( 'Erase', 'xili-dictionary' ),
				'xili_dictionary_admin',
				'erase_dictionary_page', array(&$this,'xili_dictionary_erase')
			);
		$hooks[] = add_submenu_page('edit.php?post_type='.XDMSG,
				__( 'Importing files', 'xili-dictionary' ),
				__( 'Import', 'xili-dictionary' ),
				'xili_dictionary_admin',
				'import_dictionary_page', array(&$this,'xili_dictionary_import')
			);
		$hooks[] = add_submenu_page('edit.php?post_type='.XDMSG,
				__( 'Downloading files to your computer', 'xili-dictionary' ),
				__( 'Download', 'xili-dictionary' ),
				'xili_dictionary_admin',
				'download_dictionary_page', array(&$this,'xili_dictionary_download')
			);

		// Fudge the highlighted subnav item when on a XD admin page
		foreach( $hooks as $hook ) {
			add_action( "admin_head-$hook", array(&$this,'modify_menu_highlight' ));
		}

	}
	function admin_sub_menus_hide () {

		remove_submenu_page( 'edit.php?post_type='.XDMSG, 'erase_dictionary_page' );
		remove_submenu_page( 'edit.php?post_type='.XDMSG, 'import_dictionary_page' );
		remove_submenu_page( 'edit.php?post_type='.XDMSG, 'download_dictionary_page' );


	}

	function modify_menu_highlight() {
		global $plugin_page, $submenu_file;

		// This tweaks the Tools subnav menu to only show one XD menu item
		if ( in_array( $plugin_page, array( 'erase_dictionary_page', 'import_dictionary_page', 'download_dictionary_page' ) ) )
			$submenu_file = 'dictionary_page';
	}

	/**
 	* Main settings section description for the settings page
 	*
 	* @since
 	*/
	function xd_erasing_setting_callback_main_section() {
		?>

		<p><?php _e( "Here it now possible to erase your dictionary (here in WP database) after creating the .mo files (and saving the .po files which is readable with any text editor). <strong>This erasing process don't delete the .mo or .po files.</strong>", 'xili-dictionary' ); ?></p>

		<?php
	}

	function xd_sub_selection__setting_callback_row() {
		?>

		<?php

		$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			?>
		<div class="sub-field">
			<label for="_xd_lang"><?php _e( 'Terms (msgid / msgstr) to erase', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_lang" id="_xd_lang" class='postform'>
		<option value=""> <?php _e('Original (msgid) and translations or Select language...','xili-dictionary') ?> </option>
		<option value="all-lang"> <?php _e('Translations (msgstr) in all languages','xili-dictionary') ?> </option>
				<?php
				foreach ($listlanguages as $language) {
					$selected = ( isset ( $_GET[QUETAG] ) && $language->name == $_GET[QUETAG] ) ? "selected=selected" : "" ;
					echo '<option value="'.$language->name.'" '.$selected.' >'. sprintf(__('Translations (msgstr) in %s','xili-dictionary'), __($language->description, 'xili-dictionary')).'</option>';
				}?>
	</select>
	<p class="description"><?php _e( 'Language of the translated terms to be erased. Without selection, Original (msgid) AND translation (msgstr) will both be erased.', 'xili-dictionary' ); ?></p>
	</div>
	<div class="sub-field">
		<?php

		$cur_theme_name = $this->get_option_theme_name();

		$listterms = get_terms( 'origin', array('hide_empty' => false) );
		echo '<div class="dialogorigin">';
		if ( $listterms ) {
			$checkline = __ ( 'Check Origin(s) to be erased', 'xili-dictionary' ).':<br />';
			$i = 0;
			echo '<table class="checktheme" ><tr>';
			foreach ( $listterms as $onetheme) {
				$checked = ( $onetheme->name == $cur_theme_name ) ? 'checked="checked"' : '' ;
				$checkline .= '<td><input type="checkbox" '. $checked .' id="theme-'.$onetheme->term_id.'" name="theme-'.$onetheme->term_id.'" value="'.$onetheme->slug.'" />&nbsp;' . $onetheme->name .'</td>';
				$i++;
				if ( ($i % 2) == 0 ) $checkline .= '</tr><tr>';
			}
			echo $checkline.'</tr></table>';
		}
		echo '</div>'; ?>
	<p class="description"><?php _e( 'Origins of the msgs to be erased. Without selection, msg without origin will be erased', 'xili-dictionary' ); ?></p>
	</div>
	<div class="sub-field">
		<input id="_xd_local" name="_xd_local" type="checkbox" id="_xd_local" value="local" />
		<label for="_xd_local"><?php _e( 'Locale msgs only', 'xili-dictionary' ); ?></label>
		<p class="description"><?php _e( 'Erase only locale msgs (as saved in local-xx_YY files)', 'xili-dictionary' ); ?></p>
	</div>

	<?php }

	function xd_erasing_setting_callback_row() {
		?>

		<input name="_xd_looping_rows" type="text" id="_xd_looping_rows" value="50" class="small-text" />
		<label for="_xd_looping_rows"><?php _e( 'Number of entries in one step', 'xili-dictionary' ); ?></label>

		<?php
	}

	/**
	 * Edit Delay Time setting field
	 *
	 * @since
	 */
	function xd_erasing_setting_callback_delay_time() {
		?>

	<input name="_xd_looping_delay_time" type="text" id="_xd_looping_delay_time" value=".5" class="small-text" />
	<label for="_xd_looping_delay_time"><?php _e( 'second(s) delay between each group of rows', 'xili-dictionary' ); ?></label>
	<p class="description"><?php _e( 'Keep this high to prevent too-many-connection issues.', 'xili-dictionary' ); ?></p>

		<?php
	}
	/**
	 * hidden values now
	 *
	 * @since 2.9
	 */
	function xd_setting_callback_process_section () {
		echo '<input name="_xd_looping_rows" type="hidden" id="_xd_looping_rows" value="50">';
		echo '<input name="_xd_looping_delay_time" type="hidden" id="_xd_looping_delay_time" value=".5">';
	}

	function xd_erasing_init_settings () {
		add_settings_section( 'xd_erasing_main', __( 'Erasing the dictionary', 'xili-dictionary' ), array(&$this,'xd_erasing_setting_callback_main_section'), 'xd_erasing' );

		// erasing sub-selections
		add_settings_field( '_xd_sub_selection', __( 'What to erase ?', 'xili-dictionary' ), array(&$this,'xd_sub_selection__setting_callback_row'), 'xd_erasing', 'xd_erasing_main' );
		register_setting( 'xd_erasing_main', '_xd_sub_selection', '_xd_sub_selection_define' );

		// ajax section
		add_settings_section( 'xd_erasing_tech', __( 'Processing...', 'xili-dictionary' ), array(&$this,'xd_setting_callback_process_section'), 'xd_erasing' );

		// erasing rows step
		//add_settings_field( '_xd_looping_rows', __( 'Entries step', 'xili-dictionary' ), array(&$this,'xd_erasing_setting_callback_row'), 'xd_erasing', 'xd_erasing_tech' );
		register_setting( 'xd_erasing_tech', '_xd_looping_rows', 'sanitize_title' );

		// Delay Time
		//add_settings_field( '_xd_looping_delay_time', __( 'Delay Time', 'xili-dictionary' ), array(&$this,'xd_erasing_setting_callback_delay_time'), 'xd_erasing', 'xd_erasing_tech' );
		register_setting ( 'xd_erasing_tech', '_xd_looping_delay_time', 'intval' );
	}

	function xili_dictionary_erase() {
	?>
	<div class="wrap">

		<?php screen_icon( 'tools' ); ?>

		<h2 class="nav-tab-wrapper"><?php _e( 'Erasing Dictionary lines', 'xili-dictionary' ); ?></h2>

		<form action="#" method="post" id="xd-looping-settings">

			<?php settings_fields( 'xd_erasing' ); delete_option( '_xd_erasing_step'); // echo get_option( '_xd_erasing_step', 1 ); ?>

			<?php do_settings_sections( 'xd_erasing' ); ?>
			<h4 class="link-back"><?php printf(__('<a href="%s">Back</a> to the list of msgs and tools','xili-dictionary'), admin_url() . 'edit.php?post_type='.XDMSG.'&page=dictionary_page' ); ?></h4>
			<p class="submit">
				<input type="button" name="submit" class="button-primary" id="xd-looping-start" value="<?php _e( 'Start erasing', 'xili-dictionary' ); ?>" onclick="xd_erasing_start()" />
				<input type="button" name="submit" class="button-primary" id="xd-looping-stop" value="<?php _e( 'Stop', 'xili-dictionary' ); ?>" onclick="xd_looping_stop()" />
				<img id="xd-looping-progress" src="<?php echo admin_url(); ?>images/wpspin_light.gif">
			</p>

			<div class="xd-looping-updated" id="xd-looping-message"></div>
		</form>
	</div>

	<?php
	}

	function build_file_full_path ( $request ) {
		$file = ' ? ';
		if ( function_exists('the_theme_domain') ) { // in new xili-language
			$theme_text_domain = the_theme_domain();
		} else {
			$theme_text_domain = $this->get_option_theme_name( false ); // need analysis as in xl
		}

		if ( $request['fromwhat'] == 'sources' ) {
			if ( $request['place'] == 'theme' || $request['place'] == 'parenttheme') {
				//$origin_theme = $this->get_option_theme_name( false ) ;

				$folder = ( $request['place'] == 'theme' ) ? $this->langfolder : $this->parentlangfolder ;
				$active_theme_directory = ( $request['place'] == 'theme' ) ? get_stylesheet_directory() : get_template_directory() ;
				$file = $active_theme_directory . $folder . $theme_text_domain .'.pot' ;

			} else {
				$plugin_key = $request['plugin'];
				$project_key = dirname( $request['plugin'] ) ; // w/o php file
				$list_plugins = get_plugins();
				if ( isset ($list_plugins[$plugin_key]) && $project_key != '.' ) {
					$cur_plugin = $list_plugins[$plugin_key];

					$cur_plugin_TextDomain = $cur_plugin['TextDomain'] ;

					if ( $cur_plugin_TextDomain == '' )
						$cur_plugin_TextDomain = $this->plugin_textdomain_search ( $plugin_key );
					$file = str_replace ( '//', '/' , WP_PLUGIN_DIR . '/' . $project_key .'/'. $cur_plugin['DomainPath'] .'/'. $cur_plugin_TextDomain .'.pot');
				}
			}
		} else {  // files (import / export)

			switch ( $request['local'] ) {
				case ('local') :
					if ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) {
						$local = 'local';
					} else {
						$local = 'local-';
					}
					$base = '';
					if ( $request['place'] = 'local') $request['place'] = 'theme' ; //mixed in import only from theme
					break;
				default:
					$base = '';
					$local = '';
					break;
				}

			switch ( $request['place'] ) {

				case ('theme') :
					$path = get_stylesheet_directory() . $this->langfolder ;
					$base = $local;
					if ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) $base = $theme_text_domain;

					if ( is_multisite () && is_super_admin() && isset( $request['multiupload'] ) && $request['multiupload'] == 'blog-dir' ) {
						if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error']  ) {
							$path = $uploads['basedir']."/languages/";
						}
					}
					break;

				case ('parenttheme') :
					$path = get_template_directory() . $this->parentlangfolder;
					if ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) {
						$base = $theme_text_domain;
					} else {
						$base = $local;
					}
					break;

				case ('languages') :
					$path = WP_LANG_DIR . "/themes/";
					if ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) {
						$base = $theme_text_domain ;
					} else {
						$base = $theme_text_domain . '-' . $local;
					}

					break;

				case ('plugin') :
					if (isset( $request['plugin'])) {
						$plugin_key = $request['plugin'];
						$project_key = dirname( $request['plugin'] ) ; // w/o php file
						$list_plugins = get_plugins();
						if ( isset ($list_plugins[$plugin_key]) && $project_key != '.' ) {
							$cur_plugin = $list_plugins[$plugin_key];

							$cur_plugin_TextDomain = $cur_plugin['TextDomain'] ;

							if ( $cur_plugin_TextDomain == '' )
								$cur_plugin_TextDomain = $this->plugin_textdomain_search ( $plugin_key );
							$path = WP_PLUGIN_DIR . '/' . $project_key .'/';
							$base = $cur_plugin['DomainPath'] .'/'. $cur_plugin_TextDomain;
							$base = ( in_array ( $request['lang'], array( 'plugin pot file', 'pot-file' )) ) ? $base : $base . '-';
						} else {
							$path = WP_PLUGIN_DIR . '/';
							$base = 'undefined-';
						}
					} else {
						$path = WP_PLUGIN_DIR . '/';
						$base = 'undefined-';
					}
					break;

				case ('pluginwplang') :
					$path = WP_LANG_DIR . "/plugins/";

					if (isset( $request['plugin'])) {
						$plugin_key = $request['plugin'];
						$project_key = dirname( $request['plugin'] ) ; // w/o php file
						$list_plugins = get_plugins();
						if ( isset ($list_plugins[$plugin_key]) && $project_key != '.' ) {
							$cur_plugin = $list_plugins[$plugin_key];

							$cur_plugin_TextDomain = $cur_plugin['TextDomain'] ;

							if ( $cur_plugin_TextDomain == '' )
								$cur_plugin_TextDomain = $this->plugin_textdomain_search ( $plugin_key );

							$base = $cur_plugin['DomainPath'] .'/'. $cur_plugin_TextDomain;
							$base = ( in_array ( $request['lang'], array( 'plugin pot file', 'pot-file' )) ) ? $base : $base . '-';
						} else {

							$base = 'undefined-';
						}
					} else {

						$base = 'undefined-';
					}
					break;

				default:
					$path = get_stylesheet_directory() . $this->langfolder ;
					break;
			}
			$suffix = ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) ? 'pot' : $request['suffix'];
			$lang = ( in_array ( $request['lang'], array( 'plugin pot file', $theme_text_domain, 'pot-file' )) ) ? '' : str_replace ('-', '_', $request['lang']);
			$base = $base . $lang;
			$file = str_replace ( '//', '/' ,  $path . $base . '.' . $suffix );
		}
		return $file ;
	}

	/**
	 * called by ajax to display state of file when importing or scanning
	 *
	 * @since 2.9
	 *
	 */
	function xd_live_state_file ( $args ) {

		check_ajax_referer( 'xd-live-state-file' );

		$html = '<p>';

		$file = $this->build_file_full_path( $_REQUEST );

		$html .= $this->display_file_states( $this->state_of_file($file) ) ; ;
		wp_send_json_success( $html . '</p>' );
	}

	/**
	 * called by ajax to display state of file when importing or scanning
	 *
	 * @since 2.9
	 *
	 */
	function xd_from_file_exists ( $args ) {
		check_ajax_referer( 'xd-from-file-exists' );

		$file = $this->build_file_full_path( $_REQUEST );
		$state = $this->state_of_file($file) ;
		$return =  ( $state['file_exists']) ? 'exists' : 'not exists' ;
		wp_die( $return );
	}

	function admin_head() {
		$screen = get_current_screen();

		if ( in_array($screen->base, array( 'xdmsg_page_import_dictionary_page', 'xdmsg_page_dictionary_page', 'xdmsg_page_download_dictionary_page' ) ) ) : // 2.9.0
		?>
			<script type="text/javascript">
			//<![CDATA[
			function show_file_states( fromwhat, place, curthemename, plugin, la, x, local, multiupload ) {

				var post = {
						fromwhat: fromwhat,
						place: place,
						theme: curthemename,
						plugin: plugin,
						lang: la,
						suffix: x,
						local: local,
						multiupload: multiupload,
						action: 'xd_live_state_file',
						_ajax_nonce : "<?php echo wp_create_nonce( 'xd-live-state-file' ); ?>"
					};

				jQuery.ajax( ajaxurl, {
					type: 'POST',
					data: post,
					dataType: 'json'
				}).done( function( x ) {
					if ( ! x.success ) {
						jQuery( '#xd-file-state' ).text( 'error' );
					}
					jQuery( '#xd-file-state' ).html( x.data );
				}).fail( function() {
					jQuery( '#xd-file-state' ).text( 'error' );
				});
			}

			function from_file_exists ( fromwhat, place, curthemename, plugin, la, x, local, multiupload) {
				var output; // required for output !
				var post = {
					fromwhat: fromwhat,
					place: place,
					theme: curthemename,
					plugin: plugin,
					lang: la,
					suffix: x,
					local: local,
					multiupload: multiupload,
					action: 'xd_from_file_exists',
					_ajax_nonce : "<?php echo wp_create_nonce( 'xd-from-file-exists' ); ?>"
				};

				jQuery.ajax( ajaxurl, {
					type: 'POST',
					data: post,
					async :false, // required for output !
					dataType: 'text'
				}).done( function( x ) {
					output = x;
				}).fail( function() {
					output = 'error';
				});

				return output;
			}
			//]]>
			</script>
		<?php
		endif;
		if ( in_array($screen->base, array('xdmsg_page_import_dictionary_page','xdmsg_page_erase_dictionary_page') ) ) : // 2.5.0
		?>
			<script type="text/javascript">
			//<![CDATA[

				var xd_looping_is_running = false;
				var xd_looping_run_timer;
				var xd_looping_delay_time = 0;

				function xd_importing_grab_data() {
					var values = {};
					jQuery.each(jQuery('#xd-looping-settings').serializeArray(), function(i, field) {
						values[field.name] = field.value;
					});

					if( values['_xd_looping_delay_time'] ) {
						xd_looping_delay_time = values['_xd_looping_delay_time'] * 1000;
					}

					values['action'] = 'xd_importing_process';
					values['_ajax_nonce'] = '<?php echo wp_create_nonce( 'xd_importing_process' ); ?>';

					return values;
				}

				function xd_importing_start() {
					if( false == xd_looping_is_running ) {
						xd_looping_is_running = true;
						jQuery('#xd-looping-start').hide();
						jQuery('#xd-looping-stop').show();
						jQuery('#xd-looping-progress').show();
						xd_looping_log( '<p class="loading"><?php echo esc_js(__( 'Starting Importing', 'xili-dictionary' )); ?></p>' );
						xd_importing_run();
					}
				}

				function xd_importing_run() {
					jQuery.post(ajaxurl, xd_importing_grab_data(), function(response) {
						var response_length = response.length - 1;
						response = response.substring(0,response_length);
						xd_importing_success(response);
					});
				}

				function xd_importing_success(response) {
					xd_looping_log(response);
					var possuccess = response.indexOf ("success",0);
					var poserror = response.indexOf ("error",0);
					if ( possuccess != -1 || poserror != -1 || response.indexOf('error') > -1 ) {
						xd_looping_log('<p><?php echo esc_js(__("Go to the list of msgs:","xili-dictionary")); ?> <a href="<?php echo admin_url(); ?>edit.php?post_type=<?php echo XDMSG ?>&page=dictionary_page"><?php echo esc_js(__("Continue","xili-dictionary")); ?></a></p>');

						xd_looping_stop();
						jQuery('#xd-looping-start').hide();
					} else if( xd_looping_is_running ) { // keep going
						jQuery('#xd-looping-progress').show();
						clearTimeout( xd_looping_run_timer );
						xd_looping_run_timer = setTimeout( 'xd_importing_run()', xd_looping_delay_time );
					} else {

						xd_looping_stop();
					}
				}


				function xd_erasing_grab_data() {
					var values = {};
					jQuery.each(jQuery('#xd-looping-settings').serializeArray(), function(i, field) {
						values[field.name] = field.value;
					});

					if( values['_xd_looping_delay_time'] ) {
						xd_looping_delay_time = values['_xd_looping_delay_time'] * 1000;
					}

					values['action'] = 'xd_erasing_process';
					values['_ajax_nonce'] = '<?php echo wp_create_nonce( 'xd_erasing_process' ); ?>';

					return values;
				}



				function xd_erasing_start() {
					if( false == xd_looping_is_running ) {
						xd_looping_is_running = true;
						jQuery('#xd-looping-start').hide();
						jQuery('#xd-looping-stop').show();
						jQuery('#xd-looping-progress').show();
						xd_looping_log( '<p class="loading"><?php echo esc_js( __( 'Starting Erasing', 'xili-dictionary' ) ) ; ?></p>' );
						xd_erasing_run();
					}
				}

				function xd_erasing_run() {
					jQuery.post(ajaxurl, xd_erasing_grab_data(), function(response) {
						var response_length = response.length - 1;
						response = response.substring(0,response_length);
						xd_erasing_success(response);
					});
				}

				function xd_erasing_success(response) {
					xd_looping_log(response);
					var possuccess = response.indexOf ("success",0);
					var poserror = response.indexOf ("error",0);
					if ( possuccess != -1 || poserror != -1 || response.indexOf('error') > -1 ) {
						xd_looping_log('<p><?php echo esc_js(__("Go to the list of msgs:","xili-dictionary")); ?> <a href="<?php echo admin_url(); ?>edit.php?post_type=<?php echo XDMSG ?>&page=dictionary_page"><?php echo esc_js(__("Continue","xili-dictionary")); ?></a></p>');
						xd_looping_stop();
						jQuery('#xd-looping-start').hide();
					} else if( xd_looping_is_running ) { // keep going
						jQuery('#xd-looping-progress').show();
						clearTimeout( xd_looping_run_timer );
						xd_looping_run_timer = setTimeout( 'xd_erasing_run()', xd_looping_delay_time );
					} else {
						xd_looping_stop();
					}
				}

				function xd_looping_stop() {
					jQuery('#xd-looping-start').show();
					jQuery('#xd-looping-stop').hide();
					jQuery('#xd-looping-progress').hide();
					jQuery('#xd-looping-message p').removeClass( 'loading' );
					xd_looping_is_running = false;
					clearTimeout( xd_looping_run_timer );
				}

				function xd_looping_log(text) {
					if ( jQuery('#xd-looping-message').css('display') == 'none' ) {
						jQuery('#xd-looping-message').show();
					}
					if ( text ) {
						jQuery('#xd-looping-message p').removeClass( 'loading' );
						jQuery('#xd-looping-message').prepend( text );
					}
				}
				//]]>
			</script>
			<style type="text/css" media="screen">
				/*<![CDATA[*/

				div.xd-looping-updated,
				div.xd-looping-warning {
					border-radius: 3px 3px 3px 3px;
					border-style: solid;
					border-width: 1px;
					padding: 5px 5px 5px 5px;
				}

				div.xd-looping-updated {
					height: 300px;
					overflow: auto;
					display: none;
					background-color: #FFFFE0;
					border-color: #E6DB55;
					font-family: monospace;
					font-weight: bold;
				}

				div.xd-looping-updated p {
					margin: 0.5em 0;
					padding: 2px;
					float: left;
					clear: left;
				}

				div.xd-looping-updated p.loading {
					padding: 2px 20px 2px 2px;
					background-image: url('<?php echo admin_url(); ?>images/wpspin_light.gif');
					background-repeat: no-repeat;
					background-position: center right;
				}

				#xd-looping-stop {
					display:none;
				}

				#xd-looping-progress {
					display:none;
				}

				/*]]>*/
			</style>

		<?php
		endif;
	}

	/**
	*
	*/
	function erasing_process_callback() {

		check_ajax_referer( 'xd_erasing_process' );

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
			ini_set( 'memory_limit', '256M' );
			ini_set( 'implicit_flush', '1' );
			ignore_user_abort( true );
		}

		// Save step and count so that it can be restarted.
		if ( ! get_option( '_xd_erasing_step' ) ) {
			update_option( '_xd_erasing_step', 1 );
			update_option( '_xd_erasing_start', 0 );
		}

		$step = (int) get_option( '_xd_erasing_step', 1 );
		$min = (int) get_option( '_xd_erasing_start', 0 );
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$max = ( $min + $count ) - 1;
		$start = $min;


		switch ( $step ) {

			// STEP 1. Prepare and count.
			case 1 :
				$count_lines = $this->caching_msgs_to_erase ( );

				if ( $count_lines > 0 ) {
					update_option( '_xd_erasing_step', $step + 1 );
					update_option( '_xd_erasing_start', 0 );
					update_option( '_xd_erasing_count_lines', $count_lines );
					$this->looping_output( sprintf( __('Lines found ! ( %2$s lines)', 'xili-dictionary' ), '', $count_lines ) );

				} else {
					delete_option( '_xd_erasing_step' );
					delete_option( '_xd_erasing_start' );
					delete_option( '_xd_erasing_count_lines' );
					delete_option( '_xd_deletion_type' );
					delete_option ( '_xd_cache_temp_array_IDs' );
					$this->looping_output( __( 'No msgs to erase', 'xili-dictionary' ), 'error' );

				}
				break;
			// STEP 2. Loop
			case 2 :
				$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );

				$back = $this->erasing_msgs( $start );
				if ( in_array( $back , array( 'no-list', 'loop-over', 'loop-full' ) ) ) {
					update_option( '_xd_erasing_step', $step + 1 );
					update_option( '_xd_erasing_start', 0 );
					if ( empty( $start ) || $back == 'no-list' ) {

						if ( 'loop-full' == $back ) {
							$this->looping_output( sprintf(__( 'No more msgs to erase (%s)', 'xili-dictionary' ), $back ) , 'loading' );
						} else {
							$this->looping_output( sprintf(__( 'No msgs to erase (%s)', 'xili-dictionary' ), $back ) , 'error' );

						}
					}

				} else {

					update_option( '_xd_erasing_start', $max + 1 );

					$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );
					$end = ( $count_lines > $max ) ? $max + 1 : $count_lines ;

					$this->looping_output( sprintf( __( 'Erasing msgs (%1$s - %2$s)', 'xili-dictionary' ), $min, $end ), 'loading' );

				}

				break;


			default:
				$count_lines = get_option( '_xd_erasing_count_lines', $max + 1 );
				delete_option( '_xd_erasing_step' );
				delete_option( '_xd_erasing_start' );
				delete_option( '_xd_erasing_count_lines');
				delete_option( '_xd_deletion_type' );
				delete_option ( '_xd_cache_temp_array_IDs' );

				$this->looping_output( sprintf( __( 'Erasing Complete (%1$s)', 'xili-dictionary' ), $count_lines ), 'success' );
				break;

		}
	}

	private static function looping_output( $output = '', $type = '' ) {

		switch ( $type ) {

			case 'success':
				$class = ' class="success"';
				break;
			case 'error':
				$class = ' class="error"';
				break;
			default:
				$class = ' class="loading"';
				break;
		}

		// Get the last query
		$before = "<p$class>";
		$after = '</p>';
		echo $before . $output . $after;
	}

	private static function erasing_msgs ( $start ) {
		global $xili_dictionary;
		// $listdictiolines = $xili_dictionary->get_msgs_to_erase ( $start );
		$id_list = get_option ( '_xd_cache_temp_array_IDs' );
		if ( $id_list ) { //$listdictiolines


			$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;

			$only_local = ( isset ( $_POST['_xd_local'] ) ) ? true : false ;

			$selected_lang = ( isset ( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : "" ;

			$deletion_type = get_option( '_xd_deletion_type' );

			// loop
			$count_lines = count( $id_list );

			$i = 0;
			foreach ( $id_list as $one_id ) {
				// to exit loop when only ids remain in loop
				//if ( in_array( $deletion_type , array ( 'all_str', 'only_local' ) ) ) {
					$i++;
				if ( $i < $start ) continue;
				if ( $i > ($start + $count) - 1 ) return 'loop';
				if ( $i > $count_lines) return 'loop-over';
				//}

				$res = get_post_meta ( $one_id, $xili_dictionary->msglang_meta, false );
			$thelangs = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();


				switch ( $deletion_type ) {

					case 'all':
						wp_delete_post( $one_id, false ) ;
						break;

					case 'only_local': // id and all str or all str

						if ( isset ( $thelangs['msgstrlangs'] ) ) {
							foreach ( $thelangs['msgstrlangs'] as $curlang => $msgtr ) {

								$res = get_post_meta ( $one_id, $xili_dictionary->msgchild_meta, false );
							$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
								if ( isset ( $thechilds['msgid']['plural'] ) ) {
								$msgstrs_arr = $xili_dictionary->get_cpt_msgstr( $one_id, $curlang, true );
								if ( $msgstrs_arr ) {
									foreach ( $msgstrs_arr as $msgstrs ) {
										if ( $selected_lang == "all-lang" )
											wp_delete_post( $msgstrs->ID, false ) ;
										}
									}

								} else {

									$msgstrs = $xili_dictionary->get_cpt_msgstr( $one_id, $curlang, false ); // affiner plural

									// delete only msgstr of $oneline->ID

									if ( $msgstrs != false && $selected_lang == "all-lang" ) {
										wp_delete_post( $msgstrs->ID, false ) ;
									}
							}
							}
						}
						// clean msgid
						// msgid_post_links_delete
						// delete msgid - if $selected_lang == ""
						if ( $selected_lang == "" ) {
							wp_delete_post( $one_id, false ) ;
						}
						break;

					case 'str_one_lang':

						$res = get_post_meta ( $one_id, $xili_dictionary->msgchild_meta, false );
					$thechilds = ( is_array ( $res ) && array() != $res ) ? $res[0] : array();
						if ( isset ( $thechilds['msgid']['plural'] ) ) {
							$msgstrs_arr = $xili_dictionary->get_cpt_msgstr( $one_id, $selected_lang, true );
							if ( $msgstrs_arr ) {
								foreach ( $msgstrs_arr as $msgstrs ) {
									wp_delete_post( $msgstrs->ID, false ) ;
								}
							}

						} else {

						// delete msgstr of this lang of $oneline->ID
							$msgstrs = $xili_dictionary->get_cpt_msgstr( $one_id, $selected_lang, false );
						// clean msgid (inside delete_post)

						// delete str
							if ( $msgstrs )
								wp_delete_post( $msgstrs->ID, false ) ;
						}
						break;

					case 'all_str':

						wp_delete_post( $one_id, false ) ; // id is cleaned by delete filter

						break;
				}
			}
			return 'loop-full';
		} else {
			return 'nolist';
		}

	}

	function caching_msgs_to_erase ( ) {

		$list = $this->get_msgs_to_erase ( 0 ) ;

		if ( $list ) {
			$id_list = array();
			foreach ( $list as $one ) {
				$id_list[] = $one->ID;
			}
			update_option( '_xd_cache_temp_array_IDs', $id_list );
			return count ( $list );
		} else {
			return 0;
		}


	}

	private static function get_msgs_to_erase ( $start ) {
		global $xili_dictionary;
		// sub-selection according msg origin (theme)
		$origins = array();
		$listterms = get_terms( 'origin', array('hide_empty' => false) );
		foreach ( $listterms as $onetheme) {
			if ( isset ( $_POST[ 'theme-'.$onetheme->term_id ] ) ) {
				$origins[] = $onetheme->slug;
			}
		}

		$deletion_type = '';
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;

		$only_local = ( isset ( $_POST['_xd_local'] ) ) ? true : false ;

		$selected_lang = ( isset ( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : "" ;


		// check origin checked

		if ( $selected_lang == "" && $only_local == false ) {
			$query = array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'post_type' => XDMSG,
				'suppress_filters' => true
			);
			$deletion_type = 'all';

		} else if ( ( $selected_lang == "" || $selected_lang == "all-lang" ) && $only_local == true ) {

			$query = array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
					'relation' => 'AND',
						array(
							'key' => $xili_dictionary->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						),
						array(
							'key' => $xili_dictionary->msg_extracted_comments,
							'value' => $xili_dictionary->local_tag,
							'compare' => 'LIKE'
						)
					)
			);
			$deletion_type = 'only_local';

		} else if ( $selected_lang == "all-lang" && $only_local == false ) {

			$query = array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
						array(
							'key' => $xili_dictionary->msgtype_meta,
							'value' => array( 'msgstr', 'msgstr_0', 'msgstr_1' ),
							'compare' => 'IN'
							)
					)
			);

			$deletion_type = 'all_str';

		} else if ( $selected_lang != "" && $selected_lang != "all-lang" ) {


			// only msgstr
			$query = array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'post_type' => XDMSG,
				'suppress_filters' => true,
				'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => $xili_dictionary->msgtype_meta,
							'value' => 'msgid',
							'compare' => '='
						),
						array(
							'key' => $xili_dictionary->msglang_meta,
							'value' => $selected_lang,
							'compare' => 'LIKE'
						)
					)
			);

			if ( $only_local ) {

				$query['meta_query'][] = array(
							'key' => $xili_dictionary->msg_extracted_comments,
							'value' => $xili_dictionary->local_tag,
							'compare' => 'LIKE'
						);
			}



			$deletion_type = 'str_one_lang';
		}

		if ( $origins != array() ) {

				if ( count ( $origins ) == 1 ) {

					$array_tax = array(
						'taxonomy' => 'origin',
						'field' => 'slug',
						'terms' => $origins[0]
					);

				} else {

					$array_tax = array(
							'taxonomy' => 'origin',
							'field' => 'slug',
							'terms' => $origins,
							'operator' => 'IN'
						);
				}
				$query['tax_query'] = array( $array_tax ) ;

			}

		$listdictiolines = get_posts( $query );

		if ( $listdictiolines ) {
			update_option( '_xd_deletion_type', $deletion_type );
			return $listdictiolines;
		} else {
			return false;
		}
	}

	/**
	* download file to computer
	* need function in init
	* @since 2.5
	*
	* 2014-02-14
	*/
	function xili_dictionary_download () {
		$themessages = array ( 'ok' => __( 'File is ready for download:', 'xili-dictionary' ), 'error' => __( 'Impossible to download file', 'xili-dictionary'), 'error_lang' => __( 'Please specify language', 'xili-dictionary') );
		$msg = $this->msg_settings;

		?>
		<div class="wrap">

			<?php screen_icon( 'tools' ); ?>

			<h2 class="nav-tab-wrapper"><?php _e( 'Download files (.po, .pot, .mo) to your computer', 'xili-dictionary' ); ?></h2>
<?php if ($msg != '') : ?>

					<?php
						$class_error = ( $msg != 'ok' ) ? ' error-message error' : '' ;
						echo '<div id="message" class="updated fade'.$class_error.'"><p>';
						echo $themessages[$msg];
						if ( 'error' == $msg ) {
							$sub_type = ($_POST['download_sub_type_file'] == 'local') ? 'local-': '' ;
							if ($_POST['download_type_file'] == 'pot' ) $sub_type = $this->theme_domain() ;
							echo ' “' . $sub_type . $_POST['language_file'] .'.' .$_POST['download_type_file'];

							$place = ($_POST['download_place_file'] == 'parenttheme') ? __('Parent theme', 'xili-dictionary'): __('Child theme', 'xili-dictionary');
							echo '”&nbsp;' . __('from','xili-dictionary') . '&nbsp;' .$place ;
						}

						if ($this->download_uri ) {
							$basename = basename ( $this->download_uri );
							echo ' <a href="'. $this->download_uri . '" /><strong>' . sprintf(__('Click here to download file %s', 'xili-dictionary'), $basename ) . '</strong></a>' ;
						}
					?></p></div>
<?php endif; ?>
			<form enctype="multipart/form-data" action="edit.php?post_type=xdmsg&amp;page=download_dictionary_page" method="post" id="xd-download-settings">

				<?php
				wp_nonce_field( 'xili_dictionary_download','xd_download' );
				settings_fields( 'xd_download_main' ); ?>

				<?php do_settings_sections( 'xd_downloadd' ); ?>
				<h4 class="link-back"><?php printf(__('<a href="%s">Back</a> to the list of msgs and tools','xili-dictionary'), admin_url() . 'edit.php?post_type='.XDMSG.'&page=dictionary_page' ); ?></h4>
				<p class="submit">
					<input type="submit" name="submit" class="button-primary" id="submit" value="<?php _e( 'Prepare download', 'xili-dictionary' ); ?>" />
				</p>
			</form>

		</div>
	<?php
	}

	function xd_download_init_settings () {
		//
		register_setting( 'xd_download_main', 'xd_downloadd', array(&$this,'_xd_download_type_define') );
		add_settings_section( 'xd_download_mainn', __( 'Download the files', 'xili-dictionary' ), array(&$this,'xd_download_setting_callback_main_section'), 'xd_downloadd' );

		//
		add_settings_field( '_xd_download_type', __( 'Define the file to download', 'xili-dictionary' ), array(&$this,'xd_file_download_setting_callback_row'), 'xd_downloadd', 'xd_download_mainn' );

	}

	function xd_download_setting_callback_main_section() {
		echo '<p>'.__('Here it is possible to choose a language file of the current theme to download to your computer for further works or archives.', 'xili-dictionary' ) . '</p>';
	}

	function xd_file_download_setting_callback_row() {
		// pop up to build
		$extend = 'mo';
		?>


		<div class="sub-field">
			<label for="download_type_file"><?php _e( 'Type', 'xili-dictionary' ); ?>:&nbsp;&nbsp;<strong>.</strong></label>
			<select name="download_type_file" id="download_type_file" class='postform'>
				<option value="" <?php selected( "", $extend ); ?>> <?php _e('Select type...','xili-dictionary') ?> </option>
				<option value="po" <?php selected( "po", $extend ); ?>> <?php _e('PO file','xili-dictionary') ?> </option>
				<option value="mo" <?php selected( "mo", $extend ); ?>> <?php _e('MO file','xili-dictionary') ?> </option>
				<option value="pot" <?php selected( "pot", $extend ); ?>> <?php _e('POT file','xili-dictionary') ?> </option>
			</select>
			<p class="description"><?php _e( 'Type of the file (.mo, .po or .pot)', 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
			<label for="download_sub_type_file">&nbsp;<?php _e( 'Subtype of file', 'xili-dictionary' ); ?>:&nbsp;
			<select name="download_sub_type_file" id="download_sub_type_file">
				<option value="" ><?php _e('xx_YY', 'xili-dictionary'); ?></option>
				<option value="local" ><?php _e('local-xx_YY', 'xili-dictionary'); ?></option>
			</select>
			</label>
				<p class="description"><?php _e( 'Subtype of the file (theme or local ?)', 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
			<label for="language_file"><?php _e( 'Language', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="language_file" id="language_file" class='postform'>
				<option value=""> <?php _e('Select language','xili-dictionary') ?> </option>

			<?php
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' );
			foreach ($listlanguages as $language) {
				$selected = ( isset ( $_GET[QUETAG] ) && $language->name == $_GET[QUETAG] ) ? "selected=selected" : "" ;
				echo '<option value="'.$language->name.'" '.$selected.' >'.__($language->description, 'xili-dictionary').'</option>';
			}?>
				<option value="pot-file"> <?php _e('no language (pot)','xili-dictionary') ?> </option>
			</select>
			<p class="description"><?php _e( "Language of the file.", 'xili-dictionary' ); ?></p>
		</div><br />
		<div class="sub-field">
				<label for="download_place_file">&nbsp;<?php _e( 'Place of file', 'xili-dictionary' ); ?>:&nbsp;
				<select name="download_place_file" id="download_place_file">
					<?php if ( is_child_theme() ) { ?>
					<option value="theme" ><?php _e('Child theme', 'xili-dictionary'); ?></option>
					<?php } ?>
					<option value="parenttheme" ><?php _e('Parent theme', 'xili-dictionary'); ?></option>
				</select>
				</label>
				<p class="description"><?php _e( 'Place of the file (parent or child theme)', 'xili-dictionary' ); ?></p>
		</div><br />
		<?php $extend = 'zip' ?>
		<div class="sub-field">
				<label for="download_compress_file">&nbsp;<?php _e( 'Compression of file', 'xili-dictionary' ); ?>:&nbsp;
				<select name="download_compress_file" id="download_compress_file">
					<option value="" ><?php _e('No compression', 'xili-dictionary'); ?></option>
					<option value="zip" <?php selected( "zip", $extend ); ?>><?php _e('Zip', 'xili-dictionary'); ?></option>
					<option value="gz" ><?php _e('Gz', 'xili-dictionary'); ?></option>

				</select>
				</label>
				<p class="description"><?php _e( 'Type of compression...', 'xili-dictionary' ); ?></p>
		</div><br /><hr />
		<div id="xd-file-exists"><p><?php _e('The wanted file do not exist ! Try another selection (language ?) !', 'xili-dictionary'); ?></p></div>
		<div id="xd-file-state"></div>
		<script type="text/javascript">
		//<![CDATA[
		<?php
		if ( function_exists('the_theme_domain') ) {// in new xili-language
			echo 'var potfile = "'.the_theme_domain().'";';
		} else {
			echo 'var potfile = "'.$this->get_option_theme_name().'";';
		}
		echo 'var curthemename = "'.$this->get_option_theme_name().'";';
		if ( is_child_theme() )
			echo 'var parentthemename = "'. get_option('template') . '";';
		?>

		var plugin = '';

		function update_ui_download_state () {

			var place = jQuery('#download_place_file').val();

			var la = jQuery('#language_file').val();
			var place = jQuery('#download_place_file').val();

			var subtype = jQuery('#download_sub_type_file').val();
			var local = '';
			if ( subtype == 'local' ) local = 'local';

			var x = jQuery('#download_type_file').val();

			if ( x == 'pot' ) la = 'pot-file';
			var a = from_file_exists( 'files', place, curthemename, plugin, la, x, local); //alert ( a + ' <> ' + place + curthemename + plugin + la + x );
			show_file_states( 'files', place, curthemename, plugin, la, x, local );
			if ( a == "exists" ) {
				jQuery( '#xd-file-exists' ).hide();
				jQuery( '#xd-file-state' ).show();
				jQuery( '#submit' ).attr('disabled', false );
			} else {
				jQuery( '#xd-file-exists' ).show() ;
				jQuery( '#xd-file-state' ).hide() ;
				jQuery( '#submit' ).attr('disabled', true );
			}
		}
		jQuery(document).ready( function() {
			update_ui_download_state() ;
		});

		jQuery('#language_file, #download_place_file, #download_sub_type_file, #download_type_file').change(function() {
			update_ui_download_state();
		});

		//]]>
		</script>

	<?php }

	function _xd_download_type_define( $input ) {
		add_settings_error( 'my-settings', 'invalid-email', 'You have entered an invalid e-mail address.' );
		return $input;
	}

	// call by init filter
	function download_file_if() {

		if ( isset($_POST['language_file']) && isset($_POST['download_type_file']) ) {
			check_admin_referer( 'xili_dictionary_download','xd_download' );
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
			$type_file = $_POST['download_type_file'];

			if ("" != $selectlang || $type_file == 'pot' ){

				$sub_type_file = $_POST['download_sub_type_file'];
				$place_file = $_POST['download_place_file'];
				$compress_file = $_POST['download_compress_file'];

				$result = $this->deliver_file ($selectlang, $type_file, $sub_type_file, $place_file, $compress_file );


				if ( $result ) {
					$this->msg_settings = 'ok';
				} else {
					$this->msg_settings = 'error';
				}
			}	else {

				$this->msg_settings = 'error_lang';
			}
		}

	}

	function deliver_file ($selectlang, $type_file, $sub_type_file, $place_file, $compress_file = '' ){
		//
		$filename = $selectlang . '.' .$type_file;
		if ($sub_type_file) $filename = 'local-'.$filename;
		if ( $type_file == 'pot' ) $filename = $this->theme_domain() . '.pot';

		if ( is_multisite() && $multilocal ) {
			if ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) {
				$path = $uploads['basedir']."/languages/";
			}
		} else {
			if ( !is_child_theme() && 'languages' != $place_file) {
				$path = $this->active_theme_directory . $this->langfolder;
			} else {
				if ( 'languages' == $place_file ) {
					$path = WP_LANG_DIR . "/themes/". $this->theme_domain() . '-';
				} else if ( 'parenttheme' == $place_file ) {
					$path = get_template_directory() . $this->parentlangfolder;
				} else {
					$path = get_stylesheet_directory() . $this->langfolder;
				}
			}
		}

		$diskfile = $path . $filename;

		$path_file = $this->transfer_file($diskfile, $filename, $compress_file);

		if ( $path_file ) {
			$this->download_uri = str_replace ( WP_CONTENT_DIR, WP_CONTENT_URL, $path_file );
			return true;
		}

		return false;
	}

	/**
	* inspirated form wp-db-backup
	*/
	function transfer_file ( $diskfile = '', $filename = '', $compress_file = '' ) {
		if ( '' == $diskfile ) { return "nofile-error"; }
		$gz_diskfile = "{$diskfile}.gz";

		$success = "error";
		/**
		 * Try upping the memory limit before gzipping
		 */
		if ( function_exists('memory_get_usage') && ( (int) @ini_get('memory_limit') < 64 ) ) {
			@ini_set('memory_limit', '64M' );
		}

		if ( file_exists( $diskfile && $compress_file == 'gz') ) {
			/**
			 * Try gzipping with an external application
			 */
			if ( file_exists( $diskfile ) && ! file_exists( $gz_diskfile ) ) {
				@exec( "gzip $diskfile" );
			}

			if ( file_exists( $gz_diskfile ) ) {
				if ( file_exists( $diskfile ) ) {
					//unlink($diskfile);
				}
				$diskfile = $gz_diskfile;
				$filename = "{$filename}.gz";

			/**
			 * Try to compress to gzip, if available
			 */
			} else {
				if ( function_exists('gzencode') ) {
					if ( function_exists('file_get_contents') ) {
						$text = file_get_contents($diskfile);
					} else {
						$text = implode("", file($diskfile));
					}
					$gz_text = gzencode($text, 9);
					$fp = fopen($gz_diskfile, "w");
					fwrite($fp, $gz_text);
					if ( fclose($fp) ) {
						//unlink($diskfile);
						$diskfile = $gz_diskfile;
						$filename = "{$filename}.gz";
					}
				}
			}

		} else if ( file_exists( $diskfile ) && $compress_file == 'zip' ) {
			//
			$zip_diskfile = "{$diskfile}.zip";
			$filename = "{$filename}.zip";
			require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
			//$zip = new ZipArchive();
			$zip = new PclZip($zip_diskfile);
			//if ($zip->open($zip_diskfile, ZIPARCHIVE::CREATE)===TRUE) {
			//	$zip->addFile($diskfile);
			//	$zip->close();
			//}
			$zip -> create ($diskfile,PCLZIP_OPT_REMOVE_PATH, dirname($diskfile.'/'));
			$diskfile = $zip_diskfile;

		}

		if ( file_exists( $diskfile ) ) {
/*
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Length: ' . filesize($diskfile));
			header("Content-Disposition: attachment; filename=$filename");

			flush();

			$r = readfile($diskfile);

			flush();
			if ( $r == true ) {
				$success = 'ok';
				$this->msg_settings = 'ok';
			} else {
				$this->msg_settings = 'error';
			}
*/
			return $diskfile;
		}


		return false;
	}

	/** importing new UI
	*
	*/
	function xili_dictionary_import() {
		?>

		<div class="wrap">

			<?php screen_icon( 'tools' ); ?>

			<h2 class="nav-tab-wrapper"><?php
			if (isset($_GET['scan']) && $_GET['scan'] == "sources" ) {
				$scanning = true;
				_e( 'Scanning source files of current theme or plugins', 'xili-dictionary' );
				$submit_start = __( 'Start scanning', 'xili-dictionary' );
				$submit_start_js = 'xd_importing_start()';
			} else {
				_e( 'Importing files (.po, .pot, .mo)', 'xili-dictionary' );
				$scanning = false;
				$submit_start = __( 'Start importing', 'xili-dictionary' );
				$submit_start_js = 'xd_importing_start()';
			}

			?></h2>

			<form action="#" method="post" id="xd-looping-settings">

				<?php
					if ($scanning) {
						settings_fields( 'xd_scanning' ); delete_option( '_xd_importing_step');
						do_settings_sections( 'xd_scanning' );
						echo '<input type="hidden" id="from-what" name="from-what" value="sources" >';
					} else {
						settings_fields( 'xd_importing' ); delete_option( '_xd_importing_step');
						do_settings_sections( 'xd_importing' );
						echo '<input type="hidden" id="from-what" name="from-what" value="files" >';
					}
					?>

				<h4 class="link-back"><?php printf(__('<a href="%s">Back</a> to the list of msgs and tools','xili-dictionary'), admin_url() . 'edit.php?post_type='.XDMSG.'&page=dictionary_page' ); ?></h4>
				<p class="submit">
					<input type="button" name="submit" class="button-primary" id="xd-looping-start" value="<?php echo $submit_start ; ?>" onclick="<?php echo $submit_start_js; ?>" />
					<input type="button" name="submit" class="button-primary" id="xd-looping-stop" value="<?php _e( 'Stop', 'xili-dictionary' ); ?>" onclick="xd_importing_stop()" />
					<img id="xd-looping-progress" src="<?php echo admin_url(); ?>images/wpspin_light.gif">
				</p>
				<div class="xd-looping-updated" id="xd-looping-message"></div>

			</form>
		</div>
		<script type="text/javascript">
		//<![CDATA[
		<?php
		if ( function_exists('the_theme_domain') ) {// in new xili-language
			echo 'var potfile = "'.the_theme_domain().'";';
		} else {
			echo 'var potfile = "'.$this->get_option_theme_name().'";';
		}
		echo 'var curthemename = "'.$this->get_option_theme_name().'";';
		if ( is_child_theme() )
			echo 'var parentthemename = "'. get_option('template') . '";';

		$this->echo_js_plugins_datas();

		echo 'var pluginpotfile = "plugin pot file";';


		?>
	jQuery(document).ready( function() {

		function bbtvalue ( pot ) {
		var fromwhat = jQuery('#from-what').val();
		var x = jQuery('#_xd_file_extend').val();
		var lo = jQuery('#_xd_local').val();
		var place = jQuery('#_xd_place').val();
		var la = jQuery('#_xd_lang').val();
		var multiupload = 'blog-dir';
		if ( jQuery('#_xd_multi_local').length  ) multiupload = jQuery('#_xd_multi_local').val(); //multisite and not super_admin

		if ( fromwhat == 'sources' ) {
			var t = '<?php echo esc_js(__( 'Start scanning', 'xili-dictionary' )); ?>';
			var sources = { 'theme' : '<?php echo esc_js(__( 'this theme', 'xili-dictionary' )); ?>', 'parenttheme' : '<?php echo esc_js(__( 'parent of this theme', 'xili-dictionary' )); ?>', 'plugin' : '<?php echo esc_js(__( 'this plugin', 'xili-dictionary' )); ?>'}
		} else {
			var t = '<?php echo esc_js(__( 'Start importing', 'xili-dictionary' )); ?>';
			if ( place == 'parenttheme' ) {
				t = '<?php echo esc_js(__( 'Start importing from parent theme', 'xili-dictionary' )); ?>';
			}
		}
		var plugin = jQuery('#_xd_plugin').val();
		var themedomain = '<?php echo $this->theme_domain() ; ?>';
		var local = '';
		if ( place == 'local' ) {
			local = 'local';
			submitval = t+" : "+place+"-"+la+'.'+x ;
			jQuery("#_origin_theme").val( curthemename );
			jQuery(".title_origin_theme").html( curthemename );

		} else if ( place == 'theme' || place == 'parenttheme' ) {
			if ( fromwhat == 'sources' ) {
				submitval = t+' '+sources[place]+' : '+ curthemename ;
			} else {
				submitval = t+' : '+la+'.'+x+pot ;
				if ( place == 'parenttheme' ) {
					jQuery("#_origin_theme").val( parentthemename );
					jQuery(".title_origin_theme").html( parentthemename );
				} else {
					jQuery("#_origin_theme").val( curthemename );
					jQuery(".title_origin_theme").html( curthemename );
				}
			}

		} else if ( place == 'plugin' ) {

			if ( fromwhat == 'sources' ) {
				if ( 'string' == typeof (plugin) && plugin != '' ) {
					submitval = t+' '+sources[place]+' : '+ plugindatas[plugin]['domain'] ;
				} else {
					submitval = ' ? ';
				}
				if ( 'string' == typeof (plugin) && plugin != '' ) {
					jQuery('#backup_pot_label').html( '('+ plugindatas[plugin]['domain'] + '.pot)' );
				} else {
					jQuery('#backup_pot_label').html( ' ' );
				}
			} else {
				if ( la == pluginpotfile ) {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '.pot' ;
				} else {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '-'+la+'.'+x ;
				}
			}
			jQuery("#_origin_theme").val( plugin );
			if ( 'string' == typeof (plugin) && plugin != '' ) {
				jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
			} else {
				jQuery(".title_origin_theme").html( ' ' );
			}
		} else if ( place == 'pluginwplang' ) {
			if ( la == pluginpotfile ) {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '.pot' ;
				} else {
					if ( 'string' == typeof (plugin) && plugin != '' ) submitval = t+' : ' + plugindatas[plugin]['domain'] + '-'+la+'.'+x ;
				}

			jQuery("#_origin_theme").val( plugin );
			if ( 'string' == typeof (plugin) && plugin != '' ) {
				jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
			} else {
				jQuery(".title_origin_theme").html( ' ' );
			}
		} else {
			if ( fromwhat == 'sources' ) {
				submitval = t+' : ? ' ;
			} else {
				submitval = t+' : '+themedomain+'-'+la+'.'+x ;
				jQuery("#_origin_theme").val( curthemename );
				jQuery(".title_origin_theme").html( curthemename );
			}
		}
		  	show_file_states( fromwhat, place, curthemename, plugin, la, x, local, multiupload );
		  	jQuery("#xd-looping-start").val( submitval );
		  	if ( fromwhat == 'files' ) {
		  		var a = from_file_exists( fromwhat, place, curthemename, plugin, la, x, local, multiupload );
		  		if ( a == 'exists' ) {
		  			jQuery("#xd-looping-start").attr('disabled', false );
		  		} else {
		  			jQuery("#xd-looping-start").attr('disabled', true ); // error or not exists
		  		}
		  	}

		  	if ( fromwhat == 'sources' ) {
		  		if ( jQuery('#backup_pot').val() == 'onlypot' || jQuery('#backup_pot').val() == 'both' ) {
		  			jQuery( '#xd-file-state' ).show();
		  		} else {
					jQuery( '#xd-file-state' ).hide() ;
				}
			} else {
				jQuery( '#xd-file-state' ).show();
			}
		}

		jQuery('#_xd_file_extend').change(function() {
			var x = jQuery(this).val();
			if ( x == 'po') {
				jQuery('#po-comment-option').show();
				jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile) );
			} else {
				jQuery('#po-comment-option').hide();
				jQuery("#_xd_lang").find("option[value='"+potfile+"']").remove();
				jQuery("#_xd_lang").find("option[value='" + pluginpotfile + "']").remove();
			}
			bbtvalue ('');
		});

		jQuery('#backup_pot, #_xd_local, #_xd_multi_local').change(function() {
			bbtvalue ( '' );
		});

		jQuery('#_xd_place').change(function() {
			var x = jQuery(this).val();
			var ext = jQuery('#_xd_file_extend').val();
			var fromwhat = jQuery('#from-what').val();
			jQuery('#plugins-option').hide();
			if ( x == 'languages' || x == 'local' ) {
				jQuery("#_xd_lang").find("option[value='" + potfile + "']").remove();
				jQuery("#_xd_lang").find("option[value='" + pluginpotfile + "']").remove();
			} else if ( x == 'theme' && ext == 'po') {
				jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile) );
			} else if ( (x == 'theme' || x == 'parenttheme' ) && fromwhat == 'sources' ) {
				if (x == 'theme') {
					jQuery(".title_origin_theme").html( curthemename );
				} else {
					jQuery(".title_origin_theme").html( parentthemename );
				}
				jQuery('#backup_pot_label').html( '('+ potfile + '.pot)' );
			} else if ( x == 'plugin' || x == 'pluginwplang' ) {
				jQuery('#plugins-option').show();
				jQuery("#_xd_lang").find("option[value='" + potfile + "']").remove();
				jQuery("#_xd_lang").append( new Option( pluginpotfile , pluginpotfile) );
				if ( fromwhat == 'sources' ) {
					var plugin = jQuery('#_xd_plugin').val();
					jQuery('#backup_pot_label').html( ' ' );
					if ( 'string' == typeof (plugin) && plugin != '' ) {
						jQuery(".title_origin_theme").html( plugindatas[plugin]['domain'] );
					} else {
						jQuery(".title_origin_theme").html(' ');
					}
				}
			}
			bbtvalue ( '' );
		});

		jQuery('#_xd_plugin').change(function() {
			var x = jQuery(this).val();

			bbtvalue ('');
		});

		jQuery('#_xd_lang').change(function() {
			var x = jQuery(this).val();
			t = '';
			if ( potfile == x ) {
				t = 't';
				jQuery('#_xd_local').attr('checked',false);
				jQuery('#_xd_local').prop('disabled',true);
			} else {
				jQuery('#_xd_local').prop('disabled',false);
			};
			bbtvalue ( t );
		});


		var x = jQuery('#_xd_file_extend').val();
		if ( x == 'po' ) {
			jQuery('#po-comment-option').show();
			jQuery("#_xd_lang").append( new Option( potfile+'.pot', potfile ) );
		} else {
			jQuery('#po-comment-option').hide();
			jQuery("#_xd_lang").find("option[value='"+potfile+"']").remove();
		}

		bbtvalue ('');

	});
		//]]>
		</script>
	<?php
	}

	function xd_importing_init_settings () {
		add_settings_section( 'xd_importing_main', __( 'Importing the files', 'xili-dictionary' ), array(&$this,'xd_importing_setting_callback_main_section'), 'xd_importing' );

		//
		add_settings_field( '_xd_importing_type', __( 'Define the file to import', 'xili-dictionary' ), array(&$this,'xd_file_importing_setting_callback_row'), 'xd_importing', 'xd_importing_main' );
		register_setting( 'xd_importing_main', '_xd_importing_type', '_xd_importing_type_define' );

		// ajax section
		add_settings_section( 'xd_importing_tech', __( 'Processing...', 'xili-dictionary' ), array(&$this,'xd_setting_callback_process_section'), 'xd_importing' );

		// erasing rows step
		//add_settings_field( '_xd_looping_rows', __( 'Entries step', 'xili-dictionary' ), array(&$this,'xd_erasing_setting_callback_row'), 'xd_importing', 'xd_importing_tech' );
		register_setting( 'xd_importing_tech', '_xd_looping_rows', 'sanitize_title' );

		// Delay Time
		//add_settings_field( '_xd_looping_delay_time', __( 'Delay Time', 'xili-dictionary' ), array(&$this,'xd_erasing_setting_callback_delay_time'), 'xd_importing', 'xd_importing_tech' );
		register_setting ( 'xd_importing_tech', '_xd_looping_delay_time', 'intval' );

		/** scanning elements XD 2.9 **/

		add_settings_section( 'xd_scanning_main', __( 'Scanning the files', 'xili-dictionary' ), array(&$this,'xd_importing_setting_callback_main_section'), 'xd_scanning' );

		add_settings_field( '_xd_scanning_type', __( 'Define the sources', 'xili-dictionary' ), array(&$this,'xd_file_scanning_setting_callback_row'), 'xd_scanning', 'xd_scanning_main' );

		add_settings_section( 'xd_scanning_tech', __( 'Processing...', 'xili-dictionary' ), array(&$this,'xd_setting_callback_process_section'), 'xd_scanning' );

	}

	function xd_setting_callback_tech_section () {
		?>
		<p><?php _e( "These settings below are reserved for future uses, leave values 'as is'.", 'xili-dictionary' ); ?></p>
		<?php
	}

	function echo_js_plugins_datas () {

		echo 'var plugindatas = {';
		$list_plugins = get_plugins();

		foreach ($list_plugins as $plugin_path => $one_plugin) {
			$plugin_folder = str_replace (basename($plugin_path),'', $plugin_path) ;

			if ( $one_plugin ['TextDomain'] == "" ) {
				if ( '' != $plugin_folder ) {
					$domain_path = $this->detect_plugin_language_sub_folder($plugin_path);
					if ( '' == $this->plugin_text_domain ) {
						$plugin_text_domain = $this->detect_plugin_textdomain_sub_folder ($plugin_path);
					} else {
						$plugin_text_domain = $this->plugin_text_domain;
					}
				} else {
					$plugin_text_domain = 'no_plugin_folder';
				}
			} else {
				$plugin_text_domain = $one_plugin ['TextDomain'];
			}

			echo '"'. $plugin_path. '" : { "domain" : "' . $plugin_text_domain . '", "name" : "'.$one_plugin ['Name'].'"}, ' ;
		}
		echo '};';

	}


	/**
	 * plugin domain catalog ( hook plugin_locale )
	 */
	function get_plugin_domain_array ( $locale, $domain ) {

		if ( !isset ( $this->xili_settings['domains'][$domain] ) ) {
			$this->xili_settings['domains'][$domain] = 'disable';
			if ( is_admin() )
				update_option( 'xili_dictionary_settings', $this->xili_settings );
		}

		return $locale;
	}

	/**
	 * Main settings section description for the settings page
	 *
	 * @since
	 */
	function xd_importing_setting_callback_main_section() {

		if (isset($_GET['scan']) && $_GET['scan'] == "sources" ) {
			echo '<p>' . __( "Here it is possible to scan the source files and import translatable terms inside the dictionary.", 'xili-dictionary' ) . '</p>';
			echo '<p><em>' . sprintf( __('Before importing terms, verify that the %1$strash%2$s is empty !', 'xili-dictionary') , '<a href="edit.php?post_type='.XDMSG .'">', '</a>' ) . '</em></p>';
		} else {
			$extend = ( isset( $_GET['extend'] ) ) ? $_GET['extend'] : 'po';
			?>
			<p><?php printf(__( "Here it is possible to import the .%s file inside the dictionary.", 'xili-dictionary' ), '<strong>' . $extend . '</strong>' ) ; ?></p>
			<?php
		}
	}

	function xd_file_importing_setting_callback_row() {
		$extend = ( isset( $_GET['extend'] ) ) ? $_GET['extend'] : 'po';
		$place = 'theme';
		// pop up to build
		?>
	<div class="sub-field">
	<label for="_xd_file_extend"><?php _e( 'Type', 'xili-dictionary' ); ?>:&nbsp;&nbsp;<strong>.</strong></label>
	<select name="_xd_file_extend" id="_xd_file_extend" class='postform'>
		<option value="" <?php selected( "", $extend ); ?>> <?php _e('Select type...','xili-dictionary') ?> </option>
		<option value="po" <?php selected( "po", $extend ); ?>> <?php _e('PO file','xili-dictionary') ?> </option>
		<option value="mo" <?php selected( "mo", $extend ); ?>> <?php _e('MO file','xili-dictionary') ?> </option>
	</select>

	<p class="description"><?php _e( 'Type of file: .mo or .po', 'xili-dictionary' ); ?></p>
	</div>
<?php // _xd_multi_local
	if ( is_multisite() && is_super_admin() && !$this->xililanguage_ms ) {
?>
	<div class="sub-field">
	<label for="_xd_multi_local"><?php _e( 'Folder', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_multi_local" id="_xd_multi_local" class='postform'>
		<option value="blog-dir"> <?php _e('Site file (blogs.dir)','xili-dictionary') ?> </option>
		<option value="theme-dir"> <?php _e('Original theme file','xili-dictionary') ?> </option>
	</select>

	<p class="description"><?php _e( 'As superadmin, define origin of file', 'xili-dictionary' ); ?></p>
	</div>
<?php
	}
	// local file
	// languages directory //$mofile = WP_LANG_DIR . "/themes/{$domain}-{$locale}.mo";
?>
	<div class="sub-field">
	<label for="_xd_place"><?php _e( 'Place of msgs file', 'xili-dictionary' ); ?>:&nbsp;</label>
	<select name="_xd_place" id="_xd_place" class='postform'>
		<option value="theme" <?php selected( "theme", $place ); ?>> <?php _e('xx_XX file in theme','xili-dictionary') ?> </option>
		<option value="local" <?php selected( "local", $place ); ?>> <?php _e('local-xx_XX file in theme','xili-dictionary') ?> </option>

		<?php if ( is_child_theme () ) { ?>
			<option value="parenttheme" <?php selected( "parenttheme", $place ); ?>> <?php _e('xx_XX file in parent theme','xili-dictionary') ?> </option>
		<?php } ?>

		<option value="languages" <?php selected( "languages", $place ); ?>> <?php printf(__('%1$s-xx_XX in %2$s','xili-dictionary'), $this->theme_domain(), str_replace ( WP_CONTENT_DIR, '', WP_LANG_DIR . "/themes/") ); ?> </option>

		<option value="plugin" <?php selected( "plugin", $place ); ?>> <?php _e('language file from a plugin','xili-dictionary') ?> </option>
		<option value="pluginwplang" <?php selected( "pluginwplang", $place ); ?>> <?php printf(__('language file from a plugin in %1$s','xili-dictionary'), str_replace ( WP_CONTENT_DIR, '', WP_LANG_DIR . "/plugins/") ) ?> </option>
	</select>


	<p class="description"><?php _e( 'Define folder where is file.', 'xili-dictionary' ); ?></p>
	</div>

	<div id="plugins-option" class="sub-field" style="display:none;" >
		<?php $this->echo_div_plugins_option(); ?>
		<p class="description"><?php _e( 'Select the plugin from where to import language file.', 'xili-dictionary' ); ?><br /><?php _e( 'Each line includes Text Domain (if available in plugin sources header) and state of plugin.', 'xili-dictionary' ); ?></p>
	</div>

	<?php
	$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
		?>
		<div class="sub-field">
			<label for="_xd_lang"><?php _e( 'Language', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_lang" id="_xd_lang" class='postform'>
				<option value=""> <?php _e('Select language','xili-dictionary') ?> </option>

						<?php
						foreach ($listlanguages as $language) {
							$selected = ( isset ( $_GET[QUETAG] ) && $language->name == $_GET[QUETAG] ) ? "selected=selected" : "" ;
							echo '<option value="'.$language->name.'" '.$selected.' >'.__($language->description, 'xili-dictionary').'</option>';
						}

						?>
			</select>
			<p class="description"><?php _e( "Language of the file or POT type if available in theme's folder when importing .po file. Submit button is active if file exists!", 'xili-dictionary' ); ?></p>
		</div>
	<?php $this->echo_div_source_comments();

	}

	/**
	 * @since 2.9
	 */
	function echo_div_plugins_option () {

			$list = array_keys( $this->xili_settings['domains'] ) ; // detected plugins by domain in load_plugin_textdomain

			?>
			<label for="_xd_plugin"><?php _e( 'Plugin', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_plugin" id="_xd_plugin" class='postform'>
			<option value=""> <?php _e('Select the plugin','xili-dictionary') ?> </option>
			<?php // list of plugins
			$list_plugins = get_plugins();

			foreach ($list_plugins as $key_plugin => $one_plugin) {
				if ("" != $one_plugin['TextDomain']) {
					$text_domain = $one_plugin['TextDomain'];
				} else {
					// test with detected
					$plugin_folder = WP_PLUGIN_DIR . '/' . dirname ( $key_plugin );
					$text_domain = $this->plugin_textdomain_search ( $key_plugin, __('n. a.', 'xili-dictionary') );
				}
				$pb = ( in_array ($text_domain, $list ) && is_plugin_active($key_plugin) ) ? '' : '*' ; // domain not detected‡
				$active = (is_plugin_active($key_plugin)) ? ' (' . __('active','xili-dictionary') . ')' : "";
				echo '<option value="'. $key_plugin.'"> '. $one_plugin['Name'] .' {'.$text_domain. ' ' . $pb . '}' . $active . '</option>';
			}

			?>
			</select>

	<?php
	}

	/**
	 * @since 2.9
	 */
	function echo_div_source_comments () {
		?>
		<div class="sub-field">
			<label for="_origin_theme"><?php _e( 'Name of current source', 'xili-dictionary' ); ?>&nbsp;: <span class="title_origin_theme"><?php echo $this->get_option_theme_name(); ?></span>
			<input id="_origin_theme" name="_origin_theme" type="hidden" id="_origin_theme" value="<?php echo $this->get_option_theme_name(); ?>" /></label>
			<p class="description"><?php _e( 'Used to assign origin taxonomy. Below the state of the wanted file:', 'xili-dictionary' ); ?></p>
			<div id="xd-file-state"></div>
		</div>

		<div id="po-comment-option" class="sub-field" style="display:none;" >
		<label for="_importing_po_comments">&nbsp;<?php _e( 'What about comments', 'xili-dictionary' ); ?>:&nbsp;
		<select name="_importing_po_comments" id="_importing_po_comments">
			<option value="" ><?php _e('No change','xili-dictionary'); ?></option>
			<option value="replace" ><?php _e('Imported comments replace those in list','xili-dictionary'); ?></option>
			<option value="append" ><?php _e('Imported comments be appended...','xili-dictionary'); ?></option>
		</select>
		</label>
		</div>
		<?php
	}

	/**
	 * @since 2.9
	 */
	function xd_file_scanning_setting_callback_row() {
		?>
		<div class="sub-field">
			<label for="_xd_place"><?php _e( 'Place of source files', 'xili-dictionary' ); ?>:&nbsp;</label>
			<select name="_xd_place" id="_xd_place" class='postform'>
				<option value="theme" > <?php _e('from the current theme','xili-dictionary') ?> </option>
				<?php if ( is_child_theme ()) { ?>
				<option value="parenttheme" > <?php printf(__('from the current parent theme (%s)','xili-dictionary'), get_option( "template" ) ) ?> </option>
				<?php } ?>
				<option value="plugin" > <?php _e('from a selected plugin','xili-dictionary') ?> </option>
			</select>
			<p class="description"><?php _e( 'Define where to scan files.', 'xili-dictionary' ); ?></p>
		</div>

		<div id="plugins-option" class="sub-field" style="display:none;" >
			<?php $this->echo_div_plugins_option(); ?>
			<p class="description"><?php _e( 'Select the plugin from where to scan translatable term.', 'xili-dictionary' ); ?><br /><?php _e( 'Each line includes Text Domain (if available in plugin sources header) and state of plugin.', 'xili-dictionary' ); ?></p>
		</div>
		<?php
		$this->echo_div_source_comments();
		echo '<br />' . __('What about found msgid ?', 'xili-dictionary' );
		echo ' <span id="backup_pot_label"></span> ';
		echo '<select name="backup_pot" id="backup_pot" class="postform">';
			echo '<option value="onlydic" >'. __('only import in dictionary','xili-dictionary') . '</option>';
			echo '<option value="onlypot" >'. __('only import in a POT file','xili-dictionary') . '</option>';
			echo '<option value="both" >' . __('import and backup in POT','xili-dictionary') . '</option>';
		echo '</select>'; //2.11
	}

	function _xd_importing_type_define( $input ) {
		return $input;
	}

	function importing_process_callback () {
		check_ajax_referer( 'xd_importing_process' );

		if ( ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
			ini_set( 'memory_limit', '256M' );
			ini_set( 'implicit_flush', '1' );
			ignore_user_abort( true );
		}

		// Save step and count so that it can be restarted.
		if ( ! get_option( '_xd_importing_step' ) ) {
			update_option( '_xd_importing_step', 1 );
			update_option( '_xd_importing_start', 0 );
		}

		$step = (int) get_option( '_xd_importing_step',1 );
		$min = (int) get_option( '_xd_importing_start', 0 );
		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$max = ( $min + $count ) - 1;
		$start = $min;

		$from_what = $_POST['from-what'];

		$pomofile = ""; // future use

		$lang = ( ! empty( $_POST['_xd_lang'] ) ) ? $_POST['_xd_lang'] : 'en_US';
		if ( $from_what == 'sources') {
			$lang = '';
			$create_pot_file = ( in_array ( $_POST['backup_pot'], array('onlypot','both') ) ) ? true : false ;
		}
		$type = ( ! empty( $_POST['_xd_file_extend'] ) ) ? $_POST['_xd_file_extend'] : 'po';

		$local_file = $_POST['_xd_place'] ;
		$local_file2 = ( $local_file == 'local' ) ? $local_file.'-' : ( ( $local_file == 'languages' ) ? $this->theme_domain() .'-' : '' ) ;

		if ( $local_file == 'plugin' || $local_file == 'pluginwplang' ) {
			$plugin_path = ( ! empty( $_POST['_xd_plugin']) ) ? $_POST['_xd_plugin'] : 'undefined';
			$pomofile = $this->path_plugin_file( $plugin_path, $lang, $type, ( $_POST['_xd_place'] == "pluginwplang" ) ) ;
		} else {
			$plugin_path = "";
		}

		$multilocal = ( isset ( $_POST['_xd_multi_local'] ) && $_POST['_xd_multi_local'] == 'blog-dir' ) ? true : false; // find in original theme in multisite

		switch ( $step ) {

			// STEP 1. Clean all tables.
			case 1 :
				if ( $from_what == 'files') {
					$count_entries = $this->caching_file( $type, $lang, $local_file, $pomofile, $multilocal );
				} else {
					add_filter ( 'xd-pot-scanning-project', array(&$this, 'xd_pot_scanning_xili_project'), 10, 2 ) ;
					$result_array = $this->caching_pot_obj ( $local_file, $plugin_path, $create_pot_file );
					if ( $result_array ) $this->looping_output( $result_array['infos'] ) ;
					$count_entries = ( $result_array ) ? $result_array['count'] : false ;
				}

				if ( false != $count_entries ) {

					update_option( '_xd_importing_step', $step + 1 );
					update_option( '_xd_importing_start', 0 );
					update_option( '_xd_importing_count_entries', $count_entries );
					if ( $from_what == 'files') {
						$this->looping_output( sprintf( __('File %3$s%2$s.%1$s found ! (%4$s entries)', 'xili-dictionary' ), $type, $lang, $local_file2, $count_entries ) );
					} else {
						$this->looping_output( sprintf( __('Found %1$s entries in sources of %2$s', 'xili-dictionary' ), $count_entries, $local_file ) );
					}
				} else {

					delete_option( '_xd_importing_step' );
					delete_option( '_xd_importing_start' );
					delete_option( '_xd_cache_pomo_file' );
					delete_option( '_xd_importing_count_entries');

					if ( $from_what == 'files') {
						if ( false === strpos( $lang , '_' ) && $type == 'po' ) { $type = 'pot'; } // special case
						$this->looping_output( sprintf( __('Impossible to find file %3$s%2$s.%1$s (%4$s)', 'xili-dictionary' ), $type, $lang, $local_file2, $pomofile ), 'error' );

					} else {
						$this->looping_output( sprintf( __('Impossible to find entries in sources of %1$s', 'xili-dictionary' ), $local_file ) );
					}
				}
				break;

			// STEP 2. Loop
			case 2 :
				if (isset($_POST['backup_pot']) && $_POST['backup_pot'] == 'onlypot') {
					update_option( '_xd_importing_step', $step + 1 );
					update_option( '_xd_importing_start', 0 );
					$this->looping_output( __( 'msgs only in pot file', 'xili-dictionary' ), 'error' );

				} else {//2.11
					if ( $this->importing_msgs( $start, $lang ) ) {
						update_option( '_xd_importing_step', $step + 1 );
						update_option( '_xd_importing_start', 0 );

						if ( empty( $start ) ) {

							$this->looping_output( __( 'No msgs to import', 'xili-dictionary' ), 'error' );
						}

					} else {
						update_option( '_xd_importing_start', $max + 1 );

						$count_entries = get_option( '_xd_importing_count_entries', $max + 1 );
						$end = ( $count_entries > $max ) ? $max + 1 : $count_entries ;
						$this->looping_output( sprintf( __( 'Importing msgs (%1$s - %2$s)', 'xili-dictionary' ), $min, $end ) );

					}
				}
				break;

			default :
				delete_option( '_xd_importing_step' );
				delete_option( '_xd_importing_start' );
				delete_option( '_xd_cache_pomo_file' );
				delete_option( '_xd_importing_count_entries');

				$this->looping_output( __( 'Importation Complete', 'xili-dictionary' ), 'success' );

				// if not exists, update description of origin - here to avoid hanging loop
				$origin_theme = $_POST['_origin_theme'];
				$origin = get_term_by( 'name', $origin_theme, 'origin' );
				if ( $origin->description == "" ) {
					$list_plugins = get_plugins();
					if (in_array($origin_theme, array_keys($list_plugins))) {
						$description = sprintf(__('from plugin: %s', 'xili-dictionary'), $this->get_plugin_name( $origin_theme , '' ) );
					} else {
						$description = sprintf(__('from theme: %s', 'xili-dictionary'), $origin_theme );
					}
					wp_update_term( (int)$origin->term_id, 'origin', array('description' => $description ) );
				}
				break;
		}

	}

	function get_plugin_name ( $plugin_path , $from = '' ) {
		$list_plugins = get_plugins();
		if (isset ($list_plugins[$plugin_path])) {
			$cur_plugin = $list_plugins[$plugin_path];
			return ( $from . $cur_plugin ['Name'] );
		}
		return $plugin_path;
	}

	function path_plugin_file( $plugin_path, $lang, $type = 'po', $wp_lang_dir = false ) {
		// search language path
		$list_plugins = get_plugins();
		$cur_plugin = $list_plugins[$plugin_path];
		$plugin_folder = str_replace (basename($plugin_path),'', $plugin_path) ;

		if ( $cur_plugin ['DomainPath'] == "" && '' != $plugin_folder) {
			$domain_path = $this->detect_plugin_language_sub_folder($plugin_path);
		} else {
			$domain_path = $cur_plugin ['DomainPath'];
		}

		if ( $cur_plugin ['TextDomain'] == "" && '' != $plugin_folder) {
				if ( '' == $this->plugin_text_domain ) {
					$plugin_text_domain = $this->detect_plugin_textdomain_sub_folder($plugin_path);
				} else {
					$plugin_text_domain = $this->plugin_text_domain;
				}
		} else {
			$plugin_text_domain = $cur_plugin ['TextDomain'];
		}

		if ( $lang == 'plugin pot file') { // no pot in WP_LANG_DIR
			$full_path = str_replace ( '//' ,'/' , WP_PLUGIN_DIR . '/' . str_replace (basename($plugin_path),'', $plugin_path) . $domain_path . '/'. $plugin_text_domain .'.pot' ) ;
		} else {
			if ( $wp_lang_dir ) {
				$full_path = str_replace ( '//' ,'/' , WP_LANG_DIR.'/plugins/'. $plugin_text_domain . '-' . $lang . '.' . $type ) ;
			} else {
				$full_path = str_replace ( '//' ,'/' , WP_PLUGIN_DIR . '/' . str_replace (basename($plugin_path),'', $plugin_path) . $domain_path . '/'. $plugin_text_domain .'-'.$lang .'.' . $type ) ;
			}
		}

		return $full_path;
	}

	function detect_plugin_language_sub_folder ( $plugin_path ) {
		//
		$this->plugin_text_domain = "";
		$this->plugin_domain_path = "";
		$plugin_folder = str_replace (basename($plugin_path),'', $plugin_path) ;
		if ( '' != $plugin_folder) {
			$full_path = str_replace ( '//' ,'/' , WP_PLUGIN_DIR . '/' . $plugin_folder );
			$this->find_files($full_path, '/^.*\.(mo|po|pot)$/', array(&$this,'plugin_language_folder')) ;
			return str_replace ( $full_path, '' , $this->plugin_domain_path );
		} else {
			return '';
		}

	}

	function plugin_language_folder( $path, $filename ) {
				$file_part = explode ('.' , $filename );
				if ( '' == $this->plugin_text_domain && 'pot' != $file_part[1] ) {
					// case -xx_YY.
					if ( preg_match('/(^.*)-(\S\S_\S\S)\.(\So)$/', $filename, $matches) ) {
						$this->plugin_text_domain = $matches[1];
					} else if ( preg_match('/(^.*)-(\S\S)\.(\So)$/', $filename, $matches) ) {
					// case -ja.
						$this->plugin_text_domain = $matches[1];
					}
				}
				$this->plugin_domain_path = str_replace ( '//' ,'/' , $path ) ;
	}

	function detect_plugin_textdomain_sub_folder ( $plugin_path, $suffix = 'pot' ) {
		//
		$this->plugin_text_domain = "no{$suffix}file";
		$full_path = str_replace ( '//' ,'/' , WP_PLUGIN_DIR . '/' . str_replace (basename($plugin_path),'', $plugin_path) );
		$this->find_files($full_path, '/^.*\.('.$suffix.')$/', array(&$this,'plugin_textdomain_folder'), $suffix ) ;
		return $this->plugin_text_domain ;
	}

	function plugin_textdomain_search ( $key_plugin, $not_found = 'notfound') {
		if ( dirname ( $key_plugin ) != '.' ) {
			// seach mo, po or pot
			$text_domain = $this->detect_plugin_textdomain_sub_folder ( $key_plugin, 'pot' ) ;
			if ( $text_domain == 'nopotfile')
				$text_domain = $this->detect_plugin_textdomain_sub_folder ( $key_plugin, 'mo' ) ;
			if ( $text_domain == 'nomofile')
				$text_domain = $this->detect_plugin_textdomain_sub_folder ( $key_plugin, 'po' ) ;
			if ( $text_domain == 'nopofile')
				$text_domain = 'nolangfiles';

		} else {
			$text_domain = $not_found ;
		}
		return $text_domain ;
	}

	function plugin_textdomain_folder( $path, $filename, $suffix = 'pot' ) {
		if ( $suffix == 'pot' ) {
			$this->plugin_text_domain = str_replace ( '.'.$suffix, '', $filename ) ;
			return ;
		} else {
			// try to find core w/o lang
			if ( preg_match('/(^.*)-(\S\S_\S\S)\.(\So)$/', $filename, $matches) ) {
				$this->plugin_text_domain = $matches[1];
			} else if ( preg_match('/(^.*)-(\S\S)\.(\So)$/', $filename, $matches) ) {
				// case -ja.
				$this->plugin_text_domain = $matches[1];
			}
			return ;
		}
	}

	function get_origin_plugin_paths ( $empty = false ) {
		$plugin_paths = array();
		// get origins
		$origins = get_terms( 'origin' ) ;
		if ( !is_wp_error($origins) && $origins != array() ) {
			// if in list plugins
			$list_plugins_keys = array_keys(get_plugins());
			foreach ( $origins as $one_origin ){
				// if not empty
				if ( in_array( $one_origin->name, $list_plugins_keys )) {
					$plugin_paths[] = $one_origin->name;

				}}
		}

		return $plugin_paths;
	}

	/**
	 * to limit scanning for each plugin inside - can be used for other plugins
	 *
	 * filter : xd-pot-scanning-project
	 *
	 * @since 2.9
	 */
	function xd_pot_scanning_xili_project( $the_project, $plugin_key ) {

		switch ( $plugin_key ) {

			case 'xili-language/xili-language.php' :
				$the_project['excludes'] = array( 'xili-xl-bbp-addon.php', 'xili-includes/locales.php' );
				break;
			case 'xili-language/xili-xl-bbp-addon.php' :
				$the_project['includes'] = array( 'xili-xl-bbp-addon.php' );
				break;
		}

		return $the_project;
	}

	/**
	 * Used to import msgid from one specific file
	 *
	 *
 	 * @since 2.10.0
 	 */
	function import_msgid_from_one_wp_file ( $path, $file, $entries_to_exclude, $local = true, $add_msgstr = false ) {
		global $wp_version;
		$curlang = get_locale() ; // admin language of config - import id and str
		$this->get_list_languages(); // to prepare from_entries_to_xdmsg
		$entries = false;
		$the_file_project = array(
			'title'    => sprintf( 'File %s from WP version %s generated by ©xili-dictionary', $file, $wp_version ),
			'file'     => str_replace ( '//', '/' , $path . '/' . $file .'.pot'),
			'excludes' => array(),
			'includes' => array( $file . '.php'),
			'working_path' => $path
		);
		$XD_extractor = new XD_extractor ( array( $path . '/' . $file => $the_file_project  ) );
		$entries = $XD_extractor->generate_entries( $path . '/' . $file );
		// reduce with form content managed by xl
		if ( $file == 'comment-template' ) {

			foreach ( $entries_to_exclude as $key => $value ) {
				if ( $value == 'Comment') $value = 'noun'.chr(4).'Comment'; // backward compat
				if ( array_key_exists( $value, $entries->entries)) {
					unset($entries->entries[$value]);
				}
			}
		}
		if ( $local ) {
			foreach ( $entries->entries as $key => &$entry ){
				$entry->extracted_comments = $this->local_tag . ' ' . $entry->extracted_comments ;
				if ( $add_msgstr ) {
					if ('en_US' != $curlang ) {
						if ( $entry->is_plural ) {
							$the_translations = get_translations_for_domain( 'default' );
							$entry->translations[0] = $the_translations->translate_plural( $entry->singular, $entry->plural, 1 );
							$entry->translations[1] = $the_translations->translate_plural( $entry->singular, $entry->plural, 2 );
						} else {
							if ( $entry->context) {
								$entry->translations[0] = translate_with_gettext_context( $entry->singular, $entry->context );
							} else {
								$entry->translations[0] = translate ( $entry->singular );
							}
						}
					}

				}
			}
		}

		$lines = $this->from_entries_to_xdmsg( $entries, $file, false, $curlang );

		unset ($XD_extractor);
		return $lines;
	}

	private static function caching_pot_obj ( $place, $plugin_key, $backup_pot = false ) {
		global $xili_dictionary; // called by ajax

		$entries = false;

		if ( $place == 'plugin' && $plugin_key != '' ) {

			$project_key = dirname( $plugin_key ) ; // w/o php file
			$list_plugins = get_plugins();
			if ( isset ($list_plugins[$plugin_key]) && $project_key != '.' ) {
				$cur_plugin = $list_plugins[$plugin_key];

				$cur_plugin_TextDomain = $cur_plugin['TextDomain'] ;

				if ( $cur_plugin_TextDomain == '' )
					$cur_plugin_TextDomain = $xili_dictionary->plugin_textdomain_search ( $plugin_key );

				$the_plugin_project = array(
					'title'    => sprintf( '%s Version %s generated by ©xili-dictionary', $cur_plugin['Name'], $cur_plugin['Version'] ),
					'file'     => str_replace ( '//', '/' , WP_PLUGIN_DIR . '/' . $project_key .'/'. $cur_plugin['DomainPath'] .'/'. $cur_plugin_TextDomain .'.pot'),
					'excludes' => array(),
					'includes' => array(),
					'working_path' => WP_PLUGIN_DIR . '/' . $project_key
				) ;

				$projects = array ( $project_key => apply_filters( 'xd-pot-scanning-project', $the_plugin_project, $plugin_key ) ) ; // to adapt excludes and includes

				$XD_extractor = new XD_extractor ( $projects );

				$entries = $XD_extractor->generate_entries( $project_key );

				$params = ( in_array ( $_POST['backup_pot'], array('onlypot','both') ) ) ? $projects[$project_key] : array() ;

				unset ($XD_extractor);

			}
	  	} else if ( $place == 'theme' || $place == 'parenttheme' ) {

			$origin_theme = $xili_dictionary->get_option_theme_name( false ) ;

			if ( function_exists('the_theme_domain') ) { // in new xili-language
				$theme_text_domain = the_theme_domain();
			} else {
				$theme_text_domain = $xili_dictionary->get_option_theme_name( false ); // need analysis as in xl
			}

			$project_title = ( $place == 'theme' ) ? $origin_theme : get_option ('template') ;

			$folder = ( $place == 'theme' ) ? $xili_dictionary->langfolder : $xili_dictionary->parentlangfolder ;
			$active_theme_directory = ( $place == 'theme' ) ? get_stylesheet_directory() : get_template_directory() ;
			$file = $active_theme_directory . $folder . $theme_text_domain .'.pot' ;

			$the_theme_project = array(
				'title'    => $project_title . ' generated by xili-dictionary',
				'file'     => $file,
				'excludes' => array(),
				'includes' => array(),
				'working_path' => $active_theme_directory,
				) ;

			$projects = array ( $origin_theme => apply_filters( 'xd-pot-scanning-project', $the_theme_project, $plugin_key ) ) ; // to adapt excludes and includes
			$XD_extractor = new XD_extractor ( $projects );

			$entries = $XD_extractor->generate_entries( $origin_theme );
			unset ($XD_extractor);

		}
		if ( false !== $entries ) {
			// cache file
			update_option( '_xd_cache_pomo_file', $entries );
			$cur_key = ( $place == 'plugin' ) ? $project_key : $origin_theme ;
			$result = false ;
			if ( $backup_pot && is_writable( dirname($projects[$cur_key]['file'] ))) { // ok if file do not exists 2.9.2 - 17:00
				$temp_po = new PO;
				$temp_po->entries = $entries->entries;

				$temp_po->set_header( 'Project-Id-Version', $projects[$cur_key]['title'] );
				$temp_po->set_header( 'Report-Msgid-Bugs-To', 'http://dev.xiligroup.com/' );
				$temp_po->set_header( 'POT-Creation-Date', gmdate( 'Y-m-d H:i:s+00:00' ) );
				$temp_po->set_header( 'MIME-Version', '1.0' );
				$temp_po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
				$temp_po->set_header( 'Content-Transfer-Encoding', '8bit' );
				$temp_po->set_header( 'PO-Revision-Date', gmdate( 'Y' ) . '-MO-DA HO:MI+ZONE' );
				$temp_po->set_header( 'Last-Translator', 'FULL NAME <EMAIL@ADDRESS>' );
				$temp_po->set_header( 'Language-Team', 'LANGUAGE <EMAIL@ADDRESS>' );

				// Write POT file
				$result = $temp_po->export_to_file( $projects[$cur_key]['file'] );
				unset ($temp_po);
			}
			$c = count ( $entries->entries ) ;
			unset ($entries);
			$file_pot_title = ( $backup_pot && $result ) ? __( 'POT file updated', 'xili-dictionary') : __( 'POT file infos', 'xili-dictionary' ) ;
			$infos = $file_pot_title .':'. $xili_dictionary->display_file_states( $xili_dictionary->state_of_file($projects[$cur_key]['file'])) ;
			return array ( 'count' => $c, 'infos' => $infos );

		} else {
			unset ($entries);
			return false;
		}
	}


	private static function caching_file( $type, $lang, $local_file = '', $pomofile = '', $multilocal = true ) {
		global $xili_dictionary; // called by ajax

		// search file
			$temp_po = $xili_dictionary->import_POMO ( $type, $lang, $local_file, $pomofile, $multilocal );
		if ( false !== $temp_po ) {
			// cache file
			update_option( '_xd_cache_pomo_file', $temp_po );

			return count ( $temp_po->entries ) ;
		} else {
			return false;
		}
	}

	private static function importing_msgs( $start, $lang ) {
		global $xili_dictionary; // called by ajax

		$count = (int) ! empty( $_POST['_xd_looping_rows'] ) ? $_POST['_xd_looping_rows'] : 50;
		$importing_po_comments = ( isset ( $_POST['_importing_po_comments'] ) ) ? $_POST['_importing_po_comments'] : '' ;
		$origin_theme = $_POST['_origin_theme'];

		$xili_dictionary->get_list_languages();
		$temp_po = get_option( '_xd_cache_pomo_file', false );
		$count_entries = count( $temp_po->entries );
		if ( false !== $temp_po && ( $start < $count_entries ) ) {
			$i = 0;
			foreach ( $temp_po->entries as $pomsgid => $pomsgstr ) {
				$i++;
				if ( $i < $start ) continue;
				if ( $i > ($start + $count) -1 ) break;
				if ( $i > $count_entries ) break;
				$xili_dictionary->importing_mode = true ; // not manual
				// add local_tag if mo local file imported
				if (  $_POST['_xd_place'] == 'local' && $_POST['_xd_file_extend'] = 'mo' ) {
					$pomsgstr->extracted_comments = $xili_dictionary->local_tag . ' (imported from mo) ' . $pomsgstr->extracted_comments ;
				}
				$lines = $xili_dictionary->pomo_entry_to_xdmsg ( $pomsgid, $pomsgstr, $lang, array( 'importing_po_comments'=>$importing_po_comments, 'origin_theme'=>$origin_theme ) );
				$xili_dictionary->importing_mode = false ;
			}
			return false;
		}
		return true;
	}

	/**
	 * to search and import xml file
	 *
	 *
	 * @since 2.12
	 */
	function available_theme_mod_xml() {
		if ( is_child_theme () ) {
			$file_in_parent = file_exists ( get_template_directory() . '/wpml-config.xml' ) ;
			$file_in_child =  file_exists ( get_stylesheet_directory() . '/wpml-config.xml' ) ;
			return ( $file_in_child ) ? $file_in_child : $file_in_parent; // child has priority
		} else {
			$file_in_parent = file_exists ( get_stylesheet_directory() . '/wpml-config.xml' ) ;
			return $file_in_parent;
		}
	}

	function display_form_theme_mod_xml() {
		$output = __('A config.xml file is available.', 'xili-dictionary' );
		if ( extension_loaded( 'simplexml' ) ) {
			$output .= ' ' . __('Check below if you want to add in dictionary the terms described inside this file. <br />(Do not forget to fill values in customizer before.)', 'xili-dictionary' );
			$output .= '<br /><label for="xmlimport">';
			$output .= '<input type="checkbox" id="xmlimport" name="xmlimport" value="xmlimport" />' . __('import theme_mods datas', 'xili-dictionary' );
			$output .= '</label>';
		} else {
			$output .= ' ' . __('BUT current PHP install needs <em>simplexml</em> extension !', 'xili-dictionary');
		}
		return $output;
	}

	/**
	 * to read content of xml like wpml-config.xml
	 *
	 *
	 * @since 2.12
	 */
	function get_xml_contents ( $theme_slug = false ) {
		if ( is_child_theme () ) {
			$file_in_parent = file_exists ( $parent_file_path = get_template_directory() . '/wpml-config.xml' ) ;
			$file_in_child =  file_exists ( $child_file_path = get_stylesheet_directory() . '/wpml-config.xml' ) ;
			$file = ( $file_in_child ) ? $child_file_path : (( $file_in_parent ) ? $parent_file_path : false ) ;
		} else {
			$file_in_parent = file_exists ( $child_file_path = get_stylesheet_directory() . '/wpml-config.xml' ) ;
			$file = ( $file_in_parent ) ? $child_file_path : false ;
		}

		if ( extension_loaded( 'simplexml' ) && $file ) {
			if ( false === $theme_slug ) $theme_slug = get_option( 'stylesheet' );
			$xml_config = simplexml_load_file ( $file ) ; // new SimpleXMLElement($xmlstr);
			if ( isset ( $xml_config->{'admin-texts'}->key[0] ) ) {
				$theme_mod_list = array ($theme_slug => array() );
				foreach ( $xml_config->{'admin-texts'}->key[0]  as $one ) {
					$theme_mod_list[$theme_slug][] = (string) $one['name']; // force attribute to become string
				}
				return $theme_mod_list;
			}
		}
		return false;
	}

	/**
	 * detects other xili plugins used in email support form
	 *
	 *
	 * @since 2.3.2
	 */
	function check_other_xili_plugins () {
		$list = array();
		if ( class_exists( 'xili_language' ) ) $list[] = 'xili-language' ;
		if ( class_exists( 'xili_tidy_tags' ) ) $list[] = 'xili-tidy-tags' ;

		if ( class_exists( 'xilithemeselector' ) ) $list[] = 'xilitheme-select' ;
		if ( function_exists( 'insert_a_floom' ) ) $list[] = 'xili-floom-slideshow' ;
		if ( class_exists( 'xili_postinpost' ) ) $list[] = 'xili-postinpost' ;
		if ( class_exists( 'xili_re_un_attach_media' ) ) $list[] = 'xili re/un-attach media' ;
		return implode (', ',$list) ;
	}

	/**
	 * email support form
	 *
	 *
	 * @since 2.3.2
	 */
	function on_sidebox_mail_content ( $data ) {
		extract( $data );
		global $wp_version ;
		if ( '' != $emessage ) { ?>
			<h4><?php _e('Note:','xili-dictionary') ?></h4>
			<p><strong><?php echo $emessage;?></strong></p>
		<?php } ?>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo _e('Mail to dev.xiligroup', 'xili-dictionary'); ?></legend>
		<label for="ccmail"><?php _e('Cc:','xili-dictionary'); ?>
		<input class="widefat" id="ccmail" name="ccmail" type="text" value="<?php bloginfo ('admin_email') ; ?>" /></label><br /><br />
		<?php if ( false === strpos( get_bloginfo ('url'), 'local' ) ){ ?>
			<label for="urlenable">
				<input type="checkbox" id="urlenable" name="urlenable" value="enable" <?php if( isset( $this->xili_settings['url'] ) && $this->xili_settings['url']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php bloginfo ('url') ; ?>
			</label><br />
		<?php } else { ?>
			<input type="hidden" name="onlocalhost" id="onlocalhost" value="localhost" />
		<?php } ?>
		<br /><em><?php _e('When checking and giving detailled infos, support will be better !', 'xili-dictionary'); ?></em><br />
		<label for="themeenable">
			<input type="checkbox" id="themeenable" name="themeenable" value="enable" <?php if( isset( $this->xili_settings['theme'] ) && $this->xili_settings['theme']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "Theme name= ".get_option ('stylesheet') ; ?>
		</label><br />
		<?php if ('' != $this->get_WPLANG() ) {?>
		<label for="wplangenable">
			<input type="checkbox" id="wplangenable" name="wplangenable" value="enable" <?php if( isset( $this->xili_settings['wplang'] ) && $this->xili_settings['wplang']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "WPLANG= ".$this->get_WPLANG() ; ?>
		</label><br />
		<?php } ?>
		<label for="versionenable">
			<input type="checkbox" id="versionenable" name="versionenable" value="enable" <?php if( isset( $this->xili_settings['version-wp'] ) && $this->xili_settings['version-wp']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "WP version: ".$wp_version ; ?>
		</label><br /><br />
		<?php $list = $this->check_other_xili_plugins();
		if (''!= $list ) {?>
		<label for="xiliplugenable">
			<input type="checkbox" id="xiliplugenable" name="xiliplugenable" value="enable" <?php if( isset( $this->xili_settings['xiliplug'] ) && $this->xili_settings['xiliplug']=='enable') echo 'checked="checked"' ?> />&nbsp;<?php echo "Other xili plugins = ".$list ; ?>
		</label><br /><br />
		<?php } ?>
		<label for="webmestre"><?php _e('Type of webmaster:','xili-dictionary'); ?>
		<select name="webmestre" id="webmestre" style="width:100%;">
		<?php if ( !isset ( $this->xili_settings['webmestre-level'] ) ) $this->xili_settings['webmestre-level'] = '?' ; ?>
			<option value="?" <?php selected( $this->xili_settings['webmestre-level'], '?' ); ?>><?php _e('Define your experience as webmaster…','xili-dictionary'); ?></option>
			<option value="newbie" <?php selected( $this->xili_settings['webmestre-level'], "newbie" ); ?>><?php _e('Newbie in WP','xili-dictionary'); ?></option>
			<option value="wp-php" <?php selected( $this->xili_settings['webmestre-level'], "wp-php" ); ?>><?php _e('Good knowledge in WP and few in php','xili-dictionary'); ?></option>
			<option value="wp-php-dev" <?php selected( $this->xili_settings['webmestre-level'], "wp-php-dev" ); ?>><?php _e('Good knowledge in WP, CMS and good in php','xili-dictionary'); ?></option>
			<option value="wp-plugin-theme" <?php selected( $this->xili_settings['webmestre-level'], "wp-plugin-theme" ); ?>><?php _e('WP theme and /or plugin developper','xili-dictionary'); ?></option>
		</select></label>
		<br /><br />
		<label for="subject"><?php _e('Subject:','xili-dictionary'); ?>
		<input class="widefat" id="subject" name="subject" type="text" value="" /></label>
		<select name="thema" id="thema" style="width:100%;">
			<option value="" ><?php _e('Choose topic...','xili-dictionary'); ?></option>
			<option value="Message" ><?php _e('Message','xili-dictionary'); ?></option>
			<option value="Question" ><?php _e('Question','xili-dictionary'); ?></option>
			<option value="Encouragement" ><?php _e('Encouragement','xili-dictionary'); ?></option>
			<option value="Support need" ><?php _e('Support need','xili-dictionary'); ?></option>
		</select>
		<textarea class="widefat" rows="5" cols="20" id="mailcontent" name="mailcontent"><?php _e('Your message here…','xili-dictionary'); ?></textarea>
		</fieldset>
		<p>
		<?php _e('Before send the mail, check the infos to be sent and complete textarea. A copy (Cc:) is sent to webmaster email (modify it if needed).','xili-dictionary'); ?>
		</p>
		<?php //wp_nonce_field('xili-postinpost-sendmail'); ?>
		<div class='submit'>
		<input id='sendmail' name='sendmail' type='submit' tabindex='6' value="<?php _e('Send email','xili-dictionary') ?>" /></div>

		<div style="clear:both; height:1px"></div>
		<?php
	}

	/**
	 * Contextual help
	 *
	 * @since 1.2.2
	 */
	function add_help_text($contextual_help, $screen_id, $screen) {

		$more_infos =
		'<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="http://wiki.xiligroup.org" target="_blank">Xili Plugins Documentation and WIKI</a>', 'xili-dictionary') . '</p>' .
		'<p>' . __('<a href="http://wordpress.org/plugins/xili-dictionary/" target="_blank">Xili-dictionary Plugin Documentation</a>','xili-dictionary') . '</p>' .
		'<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili-dictionary') . '</p>' .
		'<p>' . __('<a href="http://dev.xiligroup.com/?post_type=forum" target="_blank">Support Forums</a>','xili-dictionary') . '</p>' ;

		if ( 'xdmsg_page_dictionary_page' == $screen->id ) {
			$about_infos =
			'<p>' . __('Things to remember to set xili-dictionary:','xili-dictionary') . '</p>' .
			'<ul>' .
			'<li>' . __('Verify that the theme is localizable (like WP bundled themes from twentyten to twentyfourteen).','xili-dictionary') . '</li>' .
			'<li>' . __('Define the list of targeted languages.','xili-dictionary') . '</li>' .
			'<li>' . __('Prepare a sub-folder like /languages/ for .po and .mo files in each language (use the default delivered with the theme or add the pot of the theme and put them inside.', 'xili-dictionary') . '</li>' .
			'<li>' . __('If you have files: import them to create a base dictionary. If not : add a term or use buttons import and/or build in metabox.', 'xili-dictionary') . '</li>' .
			'<li>' . __('For xili-language, xili-dictionary creates local-xx_YY files (like local-fr_FR for french) containing customized website inputs to avoid mixing with items of the theme itself. .mo is the file used and .po is the text form.', 'xili-dictionary') . '</li>' .
			'</ul>' ;

			$screen->add_help_tab( array(
				'id' => 'about-xili-dictionary',
				'title' => __('About xili-dictionary','xili-dictionary'),
				'content' => $about_infos,
			));
			$screen->add_help_tab( array(
				'id' => 'more-infos',
				'title' => __('For more infos','xili-dictionary'),
				'content' => $more_infos,
			));

		} elseif ( in_array ($screen->id, array('xdmsg', 'edit-xdmsg') ) ) {
			if ( 'edit-xdmsg' == $screen->id ) {

				$about_infos_edit_msg =
				'<p>' . __('Things to remember about the list of translations (msgstr) of term (msgid) with xili-dictionary:','xili-dictionary') . '</p>'.
				'<ul>' .
				'<li>' . __('Column Metas give infos about type of msg and his language','xili-dictionary') . '</li>' .
				'<li>' . __('Links in Metas redirect to msg in another language if translation exists.','xili-dictionary') . '</li>' .
				'<li>' . __('It is possible to filter according language or the Origin of msg.','xili-dictionary') . '</li>' .
				'<li>' . __('When type of MSG is ‘local’, the couple msgid/msgstr is saved in a local-xx_YY file of current theme. This translation overhides all the others.','xili-dictionary') . '</li>' .
				'</ul>' .
				'<p>' . sprintf(__('Look %s this page %s to find a dynamic full list and more tools.','xili-dictionary'), '<a href="edit.php?post_type=xdmsg&page=dictionary_page" >', '</a>' ) . '</p>';

				$screen->add_help_tab( array(
					'id' => 'about-xili-dictionary-msg',
					'title' => __('About MSG list page','xili-dictionary'),
					'content' => $about_infos_edit_msg,
				));

			} else { // edit or new
				$about_infos_msg =
				'<p>' . __('Things to remember before to create or edit a new translation (msgstr) or a term (msgid) with xili-dictionary:','xili-dictionary') . '</p>'.
				'<ul>' .
				'<li>' . __('As in po files, msgid must be unique if no context is specified.','xili-dictionary') . '</li>' .
				'<li>' . __('msgstr is attached (taxonomy) to a language.','xili-dictionary') . '</li>' .
				'<li>' . __('if msgstr is edited, the box with msg series show untranslated msg.','xili-dictionary') . '</li>' .
				'</ul>'.
				'<p>' . __('How to update mo files ?','xili-dictionary') . '</p>' .
				'<ul>' .
				'<li>' . sprintf(__('if msgstr is edited, the box - shortcuts to update mo files - is available to quickly update local-xx_YY.mo file w/o going to %s page.','xili-dictionary'), '<a href="edit.php?post_type=xdmsg&page=dictionary_page">'.__('Tools', 'xili-dictionary').'</a>' ) . __('The language of mo file is here the language of msgstr being edited.', 'xili-dictionary') . '</li>' .
				'</ul>' .
				'<p>' . sprintf(__('Look %s this page %s to find more tools.','xili-dictionary'), '<a href="edit.php?post_type=xdmsg&page=dictionary_page" >', '</a>' ) . '</p>';

				$screen->add_help_tab( array(
					'id' => 'about-xili-dictionary-msg',
					'title' => __('About MSG edit','xili-dictionary'),
					'content' => $about_infos_msg,
				));

				}

				$screen->add_help_tab( array(
					'id' => 'more-infos',
					'title' => __('For more infos','xili-dictionary'),
					'content' => $more_infos,
				));

			} else if ( $screen->id == 'edit-category' ) { // 2.3.8
				$wikilink = $this->wikilink . '/index.php/Xili-language:_languages_list_insertion_in_nav_menu';
				$to_remember =
				'<p><strong>' . __('Things to remember to insert terms of category in dictionary msg list:','xili-dictionary') . '</strong></p>' .
				'<ul>' .
				'<li>' . __('Import Categories terms button: Transfer / import terms (name, description) as msg in dictionary.','xili-dictionary') . '</li>' .
				'<li>' . __('Display this Categories Terms button: Switch to list of these msgs in dictionary.','xili-dictionary') . '</li>' .
				'<li>' . __('In table, column Language:.','xili-dictionary') .
				'<ul>' .
				'<li>' . __('Indications if terms are present in .mo files.','xili-dictionary') . '</li>' .
				'<li>' . __('Display in XD button: Show only name and description of the selected category in XD msg list.','xili-dictionary') . '</li>' .
				'</ul>' . '</li>' .
				'<li>' . __('Find more infos in forum and wiki.','xili-dictionary') . '</li>' .
				'</ul>'.
				'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $wikilink ) . '</p>' ;

				$screen->add_help_tab( array(
				'id' => 'xili-language-list',
				'title' => sprintf( __('About %s dictionary tools', 'xili-dictionary'), '[©xili]'),
				'content' => $to_remember,
			));

		} else if ( $screen->id == 'edit-origin' ) {
			$wikilink = $this->wikilink ;
			$to_remember =
			'<p><strong>' . __('Things to remember about Origin in xili-dictionary','xili-dictionary') . '</strong></p>' .
			'<ul>' .
			'<li>' . __('Origin is a taxonomy to describe the origin of a msgid. So it is possible to share a msg between several theme and rebuild a new language file.','xili-dictionary') . '</li>' .
			'<li>' . __('To manage language file from a plugin, it is important to have only one origin per plugin. The plugin uses this origin to know from where is the msg and to build in the good sub-folder the language file (mo or po).','xili-dictionary') . '</li>' .
			'<li>' . __('In multisite context (WordPress Network), only superadmin can save language files to plugin folder.','xili-dictionary') . '</li>' .
			'</ul>'.
			'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $wikilink ) . '</p>' ;

			$screen->add_help_tab( array(
				'id' => 'xd-edit-origin',
				'title' => sprintf( __('About %s dictionary origins', 'xili-dictionary'), '[©xili]'),
				'content' => $to_remember,
			));
			$screen->add_help_tab( array(
				'id' => 'more-infos',
				'title' => __('For more infos','xili-dictionary'),
				'content' => $more_infos,
			));
		} else if ( $screen->id == 'edit-writer' ) {
			$wikilink = $this->wikilink ;
			$to_remember =
			'<p><strong>' . __('Things to remember about Writer in xili-dictionary','xili-dictionary') . '</strong></p>' .
			'<ul>' .
			'<li>' . __('Writer is a hierarchic taxonomy to describe writer or translator of a msg. It can be used as is.','xili-dictionary') . '</li>' .
			'<li>' . __('The hierarchy can be used to group like in office or by language.','xili-dictionary') . '</li>' .
			'<li>' . __('More settings in next releases.' ,'xili-dictionary') . '</li>' .
			'</ul>'.
			'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $wikilink ) . '</p>';

			$screen->add_help_tab( array(
				'id' => 'xd-edit-origin',
				'title' => sprintf( __('About %s dictionary writers', 'xili-dictionary'), '[©xili]'),
				'content' => $to_remember,
			));
			$screen->add_help_tab( array(
				'id' => 'more-infos',
				'title' => __('For more infos','xili-dictionary'),
				'content' => $more_infos,
			));

		} else if ( $screen->id == 'xdmsg_page_import_dictionary_page' ) {
			if (isset ( $_GET['scan'] ) ) {
				$to_remember =
				'<p><strong>' . __('Things to remember about scanning source files and importing inside xili-dictionary','xili-dictionary') . '</strong></p>' .
				'<ul>' .
				'<li>' . __('Here, you can scan files from the current theme or a selected plugin to import translatable entries (terms) in dictionary.','xili-dictionary') . '</li>' .
				'<li>' . __('Each msg (id) become a “custom post type xdmsg” and ready to be translated. Use this way only if the author do not provide po or pot files.','xili-dictionary') . '</li>' .
				'<li>' . __('Msgid attributes are filled with Origin (theme or plugin) and comments available in sources to help translation.','xili-dictionary') . '</li>' .
				'<li>' . __('If you want to create a pot file just check the option. This file will be saved in the language sub-folder.','xili-dictionary') . '</li>' .
				'<li>' . __('During scanning process, a temporary window appears at bottom of the screen with some progressing infos.','xili-dictionary') . '</li>' .
				'</ul>'.
				'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $this->wikilink ) . '</p>' ;

				$screen->add_help_tab( array(
					'id' => 'xd-import-files',
					'title' => sprintf( __('About scanning source files with %s dictionary', 'xili-dictionary'), '[©xili]'),
					'content' => $to_remember,
				));

			} else {
				$to_remember =
				'<p><strong>' . __('Things to remember about importing mo/po files in xili-dictionary','xili-dictionary') . '</strong></p>' .
				'<ul>' .
				'<li>' . __('Importing process inserts po/mo files contents inside dictionary. Each msg (id or str) become a “custom post type xdmsg”.','xili-dictionary') . '</li>' .
				'<li>' . __('Files are coming from current theme or installed plugin languages folders.','xili-dictionary') . '</li>' .
				'<li>' . __('Msg attributes are filled with Origin, language, comment infos. A msgstr is linked to his msgid if it is a translation.','xili-dictionary') . '</li>' .
				'<li>' . __('During importing process, a temporary window appears at bottom of the screen with some progressing infos.','xili-dictionary') . '</li>' .
				'</ul>'.
				'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $this->wikilink ) . '</p>' ;

				$screen->add_help_tab( array(
					'id' => 'xd-import-files',
					'title' => sprintf( __('About %s dictionary files importing', 'xili-dictionary'), '[©xili]'),
					'content' => $to_remember,
				));
			}

		} else if ( 'xdmsg_page_download_dictionary_page' == $screen->id ) {

			$wikilink = $this->wikilink ;
			$to_remember =
			'<p><strong>' . __('Things to remember about download language files with xili-dictionary','xili-dictionary') . '</strong></p>' .
			'<ul>' .
			'<li>' . __('With this screen, it is possible to choose a language file (.mo or .po) to download to your computer. Useful for further works or archiving.','xili-dictionary') . '</li>' .
			'<li>' . __('When the selection is successful, click prepare and the link for download appears in the green message space under the title.','xili-dictionary') . '</li>' .
			'</ul>'.
			'<p>' . sprintf(__('<a href="%s" target="_blank">Xili Wiki Documentation</a>','xili-dictionary'), $wikilink ) . '</p>' ;

			$screen->add_help_tab( array(
				'id' => 'xd-download-files',
				'title' => sprintf( __('About %s dictionary download files', 'xili-dictionary'), '[©xili]'),
				'content' => $to_remember,
			));
			$screen->add_help_tab( array(
				'id' => 'more-infos',
				'title' => __('For more infos','xili-dictionary'),
				'content' => $more_infos,
			));
		}
		return $contextual_help;
	}

	// called by each pointer
	function insert_news_pointer ( $case_news ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer', false, array('jquery') );
			++$this->news_id;
			$this->news_case[$this->news_id] = $case_news;
	}

	// insert the pointers registered before
	function print_the_pointers_js () {
		if ( $this->news_id != 0 ) {
			for ($i = 1; $i <= $this->news_id; $i++) {
				$this->print_pointer_js ( $i );
			}
		}
	}

	function print_pointer_js ( $indice ) {

		$args = $this->localize_admin_js( $this->news_case[$indice], $indice );
		if ( $args['pointerText'] != '' ) { // only if user don't read it before
		?>
		<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function() {

			var strings<?php echo $indice; ?> = <?php echo json_encode( $args ); ?>;

			<?php /** Check that pointer support exists AND that text is not empty - inspired www.generalthreat.com */ ?>

			if(typeof(jQuery().pointer) != 'undefined' && strings<?php echo $indice; ?>.pointerText != '') {
				jQuery( strings<?php echo $indice; ?>.pointerDiv ).pointer({
					content : strings<?php echo $indice; ?>.pointerText,
					position: { edge: strings<?php echo $indice; ?>.pointerEdge,
						at: strings<?php echo $indice; ?>.pointerAt,
						my: strings<?php echo $indice; ?>.pointerMy,
						offset: strings<?php echo $indice; ?>.pointerOffset
					},
					close : function() {
						jQuery.post( ajaxurl, {
							pointer: strings<?php echo $indice; ?>.pointerDismiss,
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			}
		});
		//]]>
		</script>
		<?php
		}
	}

	/**
	 * News pointer for tabs
	 *
	 * @since 2.1.2
	 *
	 */
	function localize_admin_js( $case_news, $news_id ) {
		$about = __('Docs about xili-dictionary', 'xili-dictionary');
		$pointer_edge = '';
		$pointer_at = '';
		$pointer_my = '';
		switch ( $case_news ) {

			case 'xd_new_version' :
				$pointer_text = '<h3>' . esc_js( __( 'xili-dictionary updated', 'xili-dictionary') ) . '</h3>';
				$pointer_text .= '<p>' . esc_js( sprintf( __( 'xili-dictionary was updated to version %s', 'xili-dictionary' ) , XILIDICTIONARY_VER) ). '.</p>';

				$pointer_text .= '<p>' . esc_js( sprintf(__( 'This new version %s is able to import parent sources if child theme active, writers are displayed in list, also compatible with Polylang taxonomy and xml importing.', 'xili-dictionary'), XILIDICTIONARY_VER) ). '</p>';
				$pointer_text .= '<p>' . esc_js( sprintf(__( 'In previous version of %s, if you switch language of dashboard is other than in en_US, then the import process of sources msgid will try to import translations of chosen language.', 'xili-dictionary'), XILIDICTIONARY_VER) ). '</p>';

				$pointer_text .= '<p>' . esc_js( __( 'See submenu', 'xili-dictionary' ).' “<a href="edit.php?post_type=xdmsg&page=dictionary_page">'. __('Tools, Files po mo','xili-dictionary')."</a>”" ). '.</p>';
				$pointer_text .= '<p>' . esc_js( sprintf(__( 'Before to question dev.xiligroup support, do not forget to visit %s documentation', 'xili-dictionary' ), '<a href="http://wiki.xiligroup.org" title="'.$about.'" >wiki</a>' ) ). '.</p>';
				$pointer_dismiss = 'xd-new-version-'.str_replace('.', '-', XILIDICTIONARY_VER);
				$pointer_div = '#menu-posts-xdmsg';
				$pointer_edge = 'left';
				$pointer_my = 'left';
				$pointer_at = 'right';
				break;

			default: // nothing
				$pointer_text = '';
		}

		// inspired from www.generalthreat.com
		// Get the list of dismissed pointers for the user
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		if ( in_array( $pointer_dismiss, $dismissed ) && $pointer_dismiss == 'xd-new-version-'.str_replace('.', '-', XILIDICTIONARY_VER) ) {
			$pointer_text = '';
		} elseif ( in_array( $pointer_dismiss, $dismissed ) ) {
			$pointer_text = '';
		}

		return array(
			'pointerText' => html_entity_decode( (string) $pointer_text, ENT_QUOTES, 'UTF-8'),
			'pointerDismiss' => $pointer_dismiss,
			'pointerDiv' => $pointer_div,
			'pointerEdge' => ( '' == $pointer_edge ) ? 'top' : $pointer_edge ,
			'pointerAt' => ( '' == $pointer_at ) ? 'left top' : $pointer_at ,
			'pointerMy' => ( '' == $pointer_my ) ? 'left top' : $pointer_my ,
			//'pointerOffset' => $pointer_Offset, obsolete
			'newsID' => $news_id
		);
	} /* end of pointer infos */
} /* end of class */

/**
 * filter wp_upload_dir (/wp-includes/functions.php)
 *
 * @since 1.0.5
 */
 function xili_upload_dir() {
	add_filter ( 'upload_dir', 'xili_change_upload_subdir');
	$uploads= wp_upload_dir();
	remove_filter ('upload_dir','xili_change_upload_subdir');
	return $uploads;
 }
 function xili_change_upload_subdir ($pre_uploads = array()) {
	$pre_uploads['path'] = $pre_uploads['basedir']."/languages"; /* normally easy to create this subfolder */
	return $pre_uploads;
 }

/**
 * instantiation when xili-language is loaded
 */
function xili_dictionary_start () {
	global $xili_dictionary ; // for barmenu
	$xili_dictionary = new xili_dictionary();
	if ( is_admin() ){
		$plugin_path = dirname(__FILE__) ;
		require( $plugin_path . '/includes/class-extractor.php' );
	}
}
add_action( 'plugins_loaded', 'xili_dictionary_start', 20 ); // 20 = after xili-language and xili-dictionary

/**
 * @since 2.8.1 - XILIDICTIONARY_DEBUG on top
 */
function xili_xd_error_log ( $content = '' ) {

	if ( defined ('XILIDICTIONARY_DEBUG') && XILIDICTIONARY_DEBUG == true && defined ('WP_DEBUG') && WP_DEBUG == true && $content !='' ) error_log ( 'XD' . $content );

}


/* © xiligroup dev */
?>