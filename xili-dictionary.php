<?php
/*
Plugin Name: xili-dictionary
Plugin URI: http://dev.xiligroup.com/xili-dictionary/
Description: A tool using wordpress's CPT and taxonomy for localized themes or multilingual themes managed by xili-language - a powerful tool to create .mo file(s) on the fly in the theme's folder and more... - ONLY for >= WP 3.2.1 - 
Author: dev.xiligroup - MS
Version: 2.0.0-rc3
Author URI: http://dev.xiligroup.com
License: GPLv2
*/


# beta 2.0.0-rc3 - 120406 - pre-tests  WP3.4: fixes metaboxes columns, conditional edit link in list
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


define('XILIDICTIONARY_VER','2.0.0-rc3');

include_once(ABSPATH . WPINC . '/pomo/po.php'); /* not included in wp-settings */
//
class xili_dictionary {
	
	var $subselect = ''; /* used to subselect by msgid or languages*/
	var $xililanguage = ''; /* neveractive isactive wasactive */
	var $xililanguagepremium = false;
	var $tempoutput = "";
	var $langfolder =''; /* where po or mo files */
	var $xili_settings; /* saved in options */
	var $ossep = "/"; /* for recursive file search in xamp */
		
	// 2.0 new vars
	var $xdmsg = "xdmsg";
	var $xd_settings_page = "edit.php?post_type=xdmsg&amp;page=dictionary_page"; // now in CPT menu
	var $page_screen_id = 'xdmsg_page_dictionary_page'; // help id
	// post meta
	var $ctxt_meta = '_xdmsg_ctxt'; // to change to xdctxt
	var $msgtype_meta = '_xdmsg_msgtype'; // to hidden
	var $msgchild_meta = '_xdmsg_msgchild';
	var $msglang_meta = '_xdmsg_msglangs';
	var $msgidlang_meta = '_xdmsg_msgid_id'; // origin of the msgstr
	var $msg_extracted_comments = '_xdmsg_extracted_comments';
	var $msg_translator_comments = '_xdmsg_translator_comments';
	var $msg_flags = '_xdmsg_flags';
	
	
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
	
	function xili_dictionary($langsfolder = '/') {
		global $wp_version;
		/* activated when first activation of plug */
		
		register_activation_hook( __FILE__, array( &$this,'xili_dictionary_activation') );
		
		$this->ossep = strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/'; /* for rare xamp servers*/
		
		/* get current settings - name of taxonomy - name of query-tag */
		$this->xililanguage_state();
		$this->xili_settings = get_option('xili_dictionary_settings'); // print_r($this->xili_settings);
		if(empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$this->xili_dictionary_activation();
			$this->xili_settings = get_option('xili_dictionary_settings');			
		}
		
		/* test if version changed */
		$version = $this->xili_settings['version'];
		if ($version <= '0.2') {
				/* update relationships for grouping existing dictionary terms */
			$this->update_terms_langs_grouping();
			$this->xili_settings['version'] = '1.0';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		$this->fill_default_languages_list();
		/* Actions */
		/* admin */
		add_action( 'admin_init', array(&$this,'admin_init') ); // 1.3.0
		add_action( 'admin_menu', array(&$this,'xili_add_dict_pages') );
		
		add_action( 'admin_menu', array(&$this, 'add_custom_box_in_post_msg') );
		
		add_action( 'init', array(&$this, 'xili_dictionary_register_taxonomies')); // and init
		
		
		// 2.0
		define ( 'XDMSG', $this->xdmsg ); // CPT to change from msg to xdmsg (less generic) 20120217
		add_action( 'init', array(&$this, 'post_type_msg') ); 
		
		if ( is_admin() ) {
		 	add_filter( 'manage_posts_columns', array(&$this,'xili_manage_column_name') ,9 , 1);
			add_filter( "manage_pages_custom_column", array(&$this,'xili_manage_column_row'), 9, 2); // hierarchic
			add_filter( 'manage_edit-'.XDMSG.'_sortable_columns', array(&$this,'msgcontent_column_register_sortable') );
			add_filter( 'request', array(&$this,'msgcontent_column_orderby' ) );
			
			if ( !class_exists ('xili_language' ) )
				add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_languages_posts') );
			
			add_action( 'restrict_manage_posts', array(&$this,'restrict_manage_writer_posts') );
			add_action( 'pre_get_posts', array(&$this,'wpse6066_pre_get_posts' ) );
			
			add_action( 'wp_print_scripts', array(&$this,'auto_save_unsetting' ), 2 ); // before other
			add_filter( 'user_can_richedit', array(&$this, 'disable_richedit_for_cpt') );
			
			if ( defined ('WP_DEBUG') &&  WP_DEBUG != true ) {
				add_filter( 'page_row_actions', array(&$this, 'remove_quick_edit'), 10, 1); // before to solve metas column
			}
			add_action( 'save_post', array(&$this, 'custom_post_type_title'),11 ,2); // 
			add_action( 'save_post', array(&$this, 'msgid_post_new_create'),12 ,2 );
			add_action( 'save_post', array(&$this, 'update_msg_comments'),13, 2 ); // comments and contexts
			
			add_action( 'before_delete_post', array(&$this, 'msgid_post_links_delete') );
			add_action( 'admin_print_styles-post.php', array(&$this, 'print_styles_xdmsg_edit') );
			add_action( 'admin_print_styles-post-new.php', array(&$this, 'print_styles_xdmsg_edit') );
			add_action( 'admin_print_styles-edit.php', array(&$this, 'print_styles_xdmsg_list') ); // list of msgs
		}
		
		add_filter( 'plugin_action_links',  array(&$this,'xilidict_filter_plugin_actions'), 10, 2);
		
		/* special to detect theme changing since 1.1.9 */
		add_action( 'switch_theme', array(&$this,'xd_theme_switched') );
		
		add_action( 'contextual_help', array(&$this,'add_help_text'), 10, 3 ); /* 1.2.2 */
		
		
		if ( class_exists('xili_language_ms') ) $this->xililanguagepremium = true; // 1.3.4
										
	}
	
		
	/* wp 3.0 WPMU */
	function xili_dictionary_register_taxonomies () {
		
		if ( is_admin() ) {
			global $wp_roles;
			
			if ( current_user_can ('activate_plugins') ) {
				$wp_roles->add_cap ('administrator', 'xili_dictionary_set');
			}
		}
		if (function_exists('is_child_theme') && is_child_theme() ) { // move here from init 1.4.1
			if ( isset( $this->xili_settings['langs_in_root_theme'] ) && $this->xili_settings['langs_in_root_theme'] == 'root' ) { // for future uses
				$this->get_template_directory = get_template_directory();
			} else {
				$this->get_template_directory = get_stylesheet_directory();
			}
		} else {
			$this->get_template_directory = get_template_directory();
		}
		$this->init_textdomain();
		
		// new method for languages 2.0
		$this->internal_list = $this->default_language_taxonomy ();
	
		if ( $this->internal_list ) { // test if empty
			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
			if ( $listlanguages == array() ) {
				$this->create_default_languages();
			}
		}
		$thegroup = get_terms( TAXOLANGSGROUP, array('hide_empty' => false,'slug' => 'the-langs-group'));
		$this->langs_group_id = $thegroup[0]->term_id;
		$this->langs_group_tt_id = $thegroup[0]->term_taxonomy_id;	
	}	
	
	function xili_dictionary_activation() {
		$this->xili_settings = get_option('xili_dictionary_settings');
		if ( empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$submitted_settings = array(
		    	'taxonomy'		=> 'dictionary',
		    	'langs_folder' => '',
		    	'version' 		=> '1.0'
	    	);
			update_option('xili_dictionary_settings', $submitted_settings);	
		} 			 	    
	}
	
	function post_type_msg() {
	
		$labels = array(
	    'name' => _x('xili-dictionary©', 'post type general name', 'xili-dictionary'),
	    'singular_name' => _x('Msg', 'post type singular name', 'xili-dictionary'),
	    'add_new' => __('New msgid', 'xili-dictionary'),
	    'add_new_item' => __('Add New Msgid', 'xili-dictionary'),
	    'edit_item' => __('Edit Msg', 'xili-dictionary'),
	    'new_item' => __('New Msg', 'xili-dictionary'),
	    'view_item' => __('View Msg', 'xili-dictionary'),
	    'search_items' => __('Search Msg', 'xili-dictionary'),
	    'not_found' =>  __('No Msg found', 'xili-dictionary'),
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
	    'query_var' => true,
	    'rewrite' => false,
	    'capability_type' => 'post',
	    'show_in_menu' => current_user_can ('xili_dictionary_set'), // ?? if not admin
	    'hierarchical' => true,
	    'menu_position' => null,
	    'supports' => array('author','editor', 'excerpt','custom-fields','page-attributes'),
	    'taxonomies' => array ('appearance', 'writer', 'origin' ),
	    'rewrite' => array( 'slug' => XDMSG, 'with_front' => FALSE, ),
	    'menu_icon' => plugins_url( 'images/xilidico-logo-16.png', __FILE__ ) // 16px16
	 ); 
	  	register_post_type(XDMSG,$args);
		
	 	register_taxonomy( 'writer', array(XDMSG), 
	 		array( 'hierarchical' => true, 
	 			'label' => __('Writer','xili-dictionary'), 
	 			'rewrite' => true,
	 			'query_var' => 'writer_name',
	 			'public' => false,
				'show_ui' => true,
	 			) 
	 	);
	 	/*
	 	register_taxonomy( 'appearance', array(XDMSG), 
	 		array( 'hierarchical' => true,  // theme and child 
	 			'label' => __('Theme','xili-dictionary'), 
	 			'rewrite' => true,
	 			'query_var' => 'theme_slug',
	 			'public' => false,
				'show_ui' => true,
	 			) 
	 	);
	 	*/
		register_taxonomy( 'origin', array(XDMSG),
			array('hierarchical' => false,
			 	'label' => __('Origin','xili-dictionary'),
			 	'query_var' => 'origin',
			 	'rewrite' => array('slug' => 'origin' )
			)
		);	
	}

	/**
	 * register language taxonomy if no xili_language - 'update_count_callback' => array(&$this, '_update_post_lang_count'),
	 *
	 *
	 */
	function default_language_taxonomy () {
		if ( ! defined ( 'TAXONAME' )  ) {
			if ( ! defined ( 'QUETAG' ) ) define ('QUETAG', 'lang');
			define ('TAXONAME', 'language');
			register_taxonomy( TAXONAME, 'post', array('hierarchical' => false, 'label' => false, 'rewrite' => false ,  'show_ui' => false, '_builtin' => false, 'query_var' => QUETAG ));
			define('TAXOLANGSGROUP', 'languages_group');
			register_taxonomy( TAXOLANGSGROUP, 'term', array('hierarchical' => false, 'update_count_callback' => '', 'show_ui' => false, 'label'=>false, 'rewrite' => false, '_builtin' => false ));
			$thegroup = get_terms(TAXOLANGSGROUP, array('hide_empty' => false,'slug' => 'the-langs-group'));
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
			echo '<!---- xd css ----->';
			echo '<style type="text/css" media="screen">';
			echo '#msg-states { width:69%;  float:left; overflow:hidden;}';
			echo '#msg-states-comments { width:27%; margin-left: 70%; border-left:1px #666 solid;  padding:10px 10px 0;  }';
			echo '#msg-states-comments .xdversion { font-size:80%; text-align:right; }';
			echo '.alert { color:red;}';
			echo '.editing { color:blue; background:yellow;}';
			echo '.msg-saved { background:#ffffff !important; border:1px dotted #999; padding:5px; margin-bottom:5px;}';
			echo '.column-msgtrans {width: 20%;}';
			echo '</style>'; 
	 	}
	 }
	 
	 /**
	 * add styles in list of msgs screen icon32-posts-xdmsg
	 *
	 */
	 function print_styles_xdmsg_list ( ) { 
	 	 
	 	if ( isset( $_GET['post_type']) && $_GET['post_type'] == XDMSG ) { 
	 
	 		echo '<!---- xd css ----->';
			echo '<style type="text/css" media="screen">';
	 		echo '.alert { color:red;}';
	 		echo '.column-language { width: 80px; }';
	 		echo '.column-msgcontent { width: 40%; }';
	 		echo '.column-msgpostmeta { width: 150px; }';
	 		echo '.column-author { width: 80px !important; }';
	 		echo '.column-title { width: 160px !important; }';
	 
	 		echo '#icon-edit.icon32-posts-xdmsg { background:transparent url('.plugins_url( 'images/xilidico-logo-32.png', __FILE__ ) . ') no-repeat !important ; }';
			echo '</style>';
 	    
	 	}	
	 }
	
	/**
	 * create default languages if no xili_language
	 *
	 *
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
		wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
		
		/* default values */
		if ( ''!= WPLANG && ( strlen( WPLANG )==5 || strlen( WPLANG ) == 2 ) ) : // for japanese
			$this->default_lang = WPLANG;
		else:
			$this->default_lang = 'en_US';
		endif;
		
		$term = $this->default_lang;
		$desc = $this->default_lang;
 		$slug = strtolower( $this->default_lang ) ; // 2.3.1
 		if (!defined('WPLANG') || $this->default_lang == 'en_US' || $this->default_lang == '' ) {
 			$term = 'fr_FR'; $desc = 'French'; $slug = 'fr_fr' ;
 		}
 		$args = array( 'alias_of' => '', 'description' => $desc, 'parent' => 0, 'slug' => $slug);
 		
 		$theids = $this->safe_lang_term_creation ( $term, $args ) ;
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
		if (is_wp_error($res)) { 
			return $res ;
		} else { 
			$theids = $res;
		}
		return $theids ;		
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
		if ( isset ( $_GET['post_type'] ) &&  $_GET['post_type'] == XDMSG ) 
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
        		$title = 'MSG:'.$post_id;
        		$what['post_title'] = $title ;
        	}
        	
        	if ( $post->post_content == '' )  {
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
			$type  = get_post_meta ( $post_id, $this->msgtype_meta, true);
			
			if ( $type == 'msgid_plural' ) {
				
				$parent = get_post($post_id)->post_parent;
				$res = get_post_meta ( $parent, $this->msgchild_meta, false );     
				$thechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
				if ( $res != '' ) {
					unset ( $thechilds['msgid']['plural'] ) ;
					update_post_meta ( $parent, $this->msgchild_meta, $thechilds );
				}
				
			} elseif ( $type != 'msgid' ) {
				$langs = get_the_terms( $post_id, TAXONAME ); //error_log ( serialize ( $langs ) );
				$target_lang = $langs[0]->name ; //error_log ( $target_lang ); - verify when deletion ----
				// id of msg id or parent
				if ( $type == 'msgstr' && $target_lang != '' ) {
					$msgid_ID = get_post_meta ( $post_id, $this->msgidlang_meta , true);
					$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false );
					$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
					if ( $res != '' && is_array ( $thelangs ) ) { 
						//error_log ( serialize ( $thelangs ) ) ;
						unset ( $thelangs['msgstrlangs'][$target_lang]['msgstr'] ) ;
						update_post_meta ( $msgid_ID, $this->msglang_meta, $thelangs ); // update id post_meta
					}	
				} elseif ( false !== strpos( $type, 'msgstr_' ) && $target_lang != '' ) {
					$indices = explode ('_', $type);
					$msgid_ID = get_post_meta ( $post_id, $this->msgidlang_meta , true);
					if ( $indices[1] == 0 ) {
						$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false );
						$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
						if ( $res != '' && is_array ( $thelangs ) ) {
							unset ( $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ) ;
							// delete childs or trash ??
							// ?? recursive 
							update_post_meta ( $msgid_ID, $this->msglang_meta, $thelangs ); // update id post_meta
						}
					} else {
						$res = get_post_meta ( $msgid_ID, $this->msglang_meta, false ); //error_log ( serialize ( $res ) ) ;
						$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
						if ( $res != '' && is_array ( $thelangs )  ) {
							if ( isset ( $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ) ) {
								$parent = $thelangs['msgstrlangs'][$target_lang]['msgstr_0'] ;
								$res = get_post_meta ( $parent, $this->msgchild_meta, false );     
								$thechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
								if ( $res != '' ) { error_log ('unset='.$indices[1]);
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
		if ( isset($_POST['_inline_edit']) ) return;
		if ( isset( $_GET['bulk_edit']) ) return;
		if ( get_post_type( $post_id ) == XDMSG ) {
			if ( !wp_is_post_revision( $post_id ) && $this->importing_mode != true ) {
				
				//$temp_post = $this->temp_get_post ( $post_id );
				$type = get_post_meta ( $post_id, $this->msgtype_meta, true ) ;
				if (  $type == "" ) 
					update_post_meta ( $post_id, $this->msgtype_meta, 'msgid' );
				
				$result = $this->msgid_exists ( $post->post_content );
				if ( $result === false ||  $result[0] == $post_id ) {
					return ;
				} else {
					if ( $type ==  get_post_meta ( $result[0], $this->msgtype_meta, true ) && $type == 'msgid') {
 						$newcontent = sprintf( __('msgid exists as %d with content: %s','xili-dictionary'), $result[0], $post->post_content ) ;
						$where = array( 'ID' => $post_id );
	        			$wpdb->update( $wpdb->posts, array( 'post_content' => $newcontent ), $where );
					}
				}
			}
		}
	}
	
	/**
	 * Main "dashboard" box in msg edit to display and link the series of msg
	 *
	 * @since 2.0
	 *
	 *
	 */
	function add_custom_box_in_post_msg () {
		$singular_name = "series";
		add_meta_box('msg_state', sprintf(__("msg %s",'xili-dictionary'), $singular_name), array(&$this,'msg_state_box'), XDMSG , 'normal','high');
	}
	
	function msg_state_box () {
	  global $post_ID ;
	  ?>
<div id="msg-states">
	  <?php
	  
	  $this->msg_status_display ( $post_ID ); 
	  
	  ?>
</div>
<div id="msg-states-comments">
	  <?php
	  $this->msg_status_comments ( $post_ID );
	 
	  ?>
	<p class="xdversion">XD v. <?php echo XILIDICTIONARY_VER; ?></p>
</div>
<div style="clear:both"></div>
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
				$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_content = %s AND post_type = %s", $content, XDMSG );
				
			} else {
				$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) INNER JOIN $wpdb->postmeta as mt1 ON ($wpdb->posts.ID = mt1.post_id) WHERE post_content = %s AND post_type = %s AND $wpdb->postmeta.meta_key='ctxt' AND mt1.meta_key='ctxt' AND mt1.meta_value = %s ", $content, XDMSG, $ctxt);
			}
			
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
			$posts_query = $wpdb->prepare("SELECT ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) INNER JOIN $wpdb->postmeta as mt1 ON ($wpdb->posts.ID = mt1.post_id) WHERE post_content = %s AND post_type = %s AND $wpdb->postmeta.meta_key='{$this->msgidlang_meta}' AND mt1.meta_key='{$this->msgidlang_meta}' AND mt1.meta_value = %s ", $content, XDMSG, $msgid);
		
		
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
	
	/**
	 * import po and mo in cpts series
	 *
	 * @since 2.0
	 * @return 
	 */
	function from_pomo_to_cpts ( $po , $curlang = 'en_US' ) {
		$nblines = array( 0, 0); // id, str count
		$this->importing_mode = true ;
		foreach ( $po->entries as $pomsgid => $pomsgstr ) {
			// test if msgid exists
			$result = $this->msgid_exists ( $pomsgstr->singular, $pomsgstr->context ) ;
			
			if ( $result === false ) {
				// create the msgid
				$type = 'msgid';
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $pomsgstr->singular, $pomsgstr->context, $type, 0, $pomsgstr ) ;
				$nblines[0]++ ;
			} else {
				$msgid_post_ID = $result[0];
				if ( $this->importing_po_comments != '' ) {
					$this->insert_comments( $msgid_post_ID, $pomsgstr, $this->importing_po_comments );
				}
			}
			
			if ( $pomsgstr->is_plural != null ) {
				// create msgid plural (child of msgid)
				// $pomsgstr->plural, $msgid_post_ID
				$result = $this->msgid_exists ( $pomsgstr->plural ) ;
				if ( $result === false ) 
				      $msgid_post_ID_plural = $this->insert_one_cpt_and_meta( $pomsgstr->plural, null, 'msgid_plural' , $msgid_post_ID, $pomsgstr );
				
			}
			
			// create msgstr - taxonomy 
			
			if ( $pomsgstr->is_plural == null ) {
				
				$msgstr_content = ( isset( $pomsgstr->translations[0]) ) ? $pomsgstr->translations[0] : "" ;
				if ( $msgstr_content != "" ) {
					// test exists with taxo before
					$result = $this->msgstr_exists ( $msgstr_content, $msgid_post_ID, $curlang ) ;
					if ( $result === false ) {
						$msgstr_post_ID = $this->insert_one_cpt_and_meta( $msgstr_content, null, 'msgstr', 0, $pomsgstr );
						wp_set_object_terms( $msgstr_post_ID, $curlang, TAXONAME );
						$nblines[1]++ ;
					} else {
						$msgstr_post_ID = $result[0];
					}
				
				// create link according lang
				
					$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
					$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
					$thelangs['msgstrlangs'][$curlang]['msgstr'] = $msgstr_post_ID;
					update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
					update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_post_ID );
				}
				
				
			} else {
				// $pomsgstr->translations 
				$i=0; $parentplural = 0;
				foreach ( $pomsgstr->translations as $onetranslation ) {
					$msgstr_plural = 'msgstr_' . $i ;
					$parent = ( $i == 0 ) ? 0 : $parentplural ;
					if ( $onetranslation != "" ) {
						// test exists with taxo before
						$result = $this->msgstr_exists ( $onetranslation, $msgid_post_ID, $curlang ) ;
						if ( $result === false ) {
					 		$msgstr_post_ID_plural = $this->insert_one_cpt_and_meta( $onetranslation, null, $msgstr_plural , $parent, $pomsgstr );
							wp_set_object_terms( $msgstr_post_ID_plural, $curlang, TAXONAME );
							$nblines[1]++ ;
						} else {
							$msgstr_post_ID_plural = $result[0];
						}
						update_post_meta ( $msgstr_post_ID_plural, $this->msgidlang_meta, $msgid_post_ID );
					}
			 
					if ( $i == 0 ) { 
						$parentplural = $msgstr_post_ID_plural; 
						
						// create link according lang in msgid
						$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
						$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
						$thelangs['msgstrlangs'][$curlang][$msgstr_plural] = $msgstr_post_ID_plural;
						update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
												
					} // only first str
					
					$i++;
				}
			}
			
		}
		$this->importing_mode = false ;
		return $nblines;	
	}
	
	/**
	 * import a msg line 
	 *
	 * @since 2.0
	 * @return ID
	 */
	function insert_one_cpt_and_meta ( $content, $context = null, $type , $parent = 0, $entry = null  ) {
		global $user_ID;
		/* 	if (!empty($entry->translator_comments)) $po[] = PO::comment_block($entry->translator_comments);
				if (!empty($entry->extracted_comments)) $po[] = PO::comment_block($entry->extracted_comments, '.');
				if (!empty($entry->references)) $po[] = PO::comment_block(implode(' ', $entry->references), ':');
				if (!empty($entry->flags)) $po[] = PO::comment_block(implode(", ", $entry->flags), ',');
			*/
		if ( null != $entry ) {
			$references = (!empty($entry->references)) ? implode ( ' #: ' ,  $entry->references ) : '' ;
			$flags = (!empty($entry->flags)) ? implode ( ', ' ,  $entry->flags ) : '' ;
			$extracted_comments =  (!empty($entry->extracted_comments)) ? $entry->extracted_comments : '' ;
			$translator_comments = (!empty($entry->translator_comments)) ? $entry->translator_comments : '' ;
		} else {
			$references = "";
			$flags = "";
			$extracted_comments = "";
			$translator_comments = "";
			
		} 
		
		
		$params = array('post_status' => 'publish', 'post_type' => XDMSG, 'post_author' => $user_ID,
		'ping_status' => get_option('default_ping_status'), 'post_parent' => $parent,
		'menu_order' => 0, 'to_ping' =>  '', 'pinged' => '', 'post_password' => '',
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
				if ( $flags != "") update_post_meta ( $post_id, $this->msg_flags, $flags );
			}
			
			if ( $type == 'msgstr' || $type == 'msgstr_0' ) {
				if ( $translator_comments != "") update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
			}
		// update postmeta children
		// create array
			if ( $parent != 0 ) {
				
				$res = get_post_meta ( $parent, $this->msgchild_meta, false );
				$thechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
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
	 * called by from_pomo_to_cpts
	 *
	 */
	function insert_comments( $post_id, $entry, $import_comment_mode = 'replace' ) {
		
		$references = (!empty($entry->references)) ? implode ( ' #: ' ,  $entry->references ) : '' ;
		$flags = (!empty($entry->flags)) ? implode ( ', ' ,  $entry->flags ) : '' ;
		$extracted_comments =  (!empty($entry->extracted_comments)) ? $entry->extracted_comments : '' ;
		$translator_comments = (!empty($entry->translator_comments)) ? $entry->translator_comments : '' ;
		
		if ( $import_comment_mode == 'replace' ) {
			// update references in excerpt
			$postarr = wp_get_single_post( $post_id, ARRAY_A ) ;
			
			$postarr['post_excerpt'] = $references;
			
			wp_insert_post( $postarr );
			
			// update comments in meta
			if ( $extracted_comments != "" ) update_post_meta ( $post_id, $this->msg_extracted_comments, $extracted_comments );
			if ( $translator_comments != "") update_post_meta ( $post_id, $this->msg_translator_comments, $translator_comments );
			if ( $flags != "") update_post_meta ( $post_id, $this->msg_flags, $flags );
		
		} elseif ( $import_comment_mode == 'append' ) { // don't erase existing comments - can be risked
			
			
		}
		
	}
	
	/**
	 * new columns in cpt list
	 *
	 */
	function xili_manage_column_name( $columns ) { // must be verified
	
		if ( isset ( $_GET['post_type'] ) &&  $_GET['post_type'] == XDMSG )   { 
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
			if ( !class_exists ( 'xili_language' ) ) {
				$columns[TAXONAME] = __('Language','xili-language');
			}
			$columns = array_merge($columns, $end);
		}
		return $columns;
		
	}
	
	function xili_manage_column_row  ( $column , $id ) {
		global $post;
		
		if ($column == 'msgcontent' && $post->post_type == XDMSG ) 
			echo htmlspecialchars( $post->post_content );
		if ($column == 'msgpostmeta' && $post->post_type == XDMSG ) {	
			
			$this->msg_link_display ( $id );
		}
		if ($column == 'language' && $post->post_type == XDMSG ) {
			if ( !class_exists ( 'xili_language' ) ) {
				$terms = wp_get_object_terms( $id, TAXONAME);
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
 					echo $terms[0]->name;	
				}
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
	 *
	 */
	function restrict_manage_languages_posts () {
		$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
		?>
<select name="lang" id="lang" class='postform'>
	<option value=""> <?php _e('View all languages','xili-dictionary') ?> </option>
			
			<?php foreach ($listlanguages as $language)  {
				$selected = ( isset ( $_GET[QUETAG] ) && $language->slug == $_GET[QUETAG] ) ? "selected=selected" : "" ;
				echo '<option value="'.$language->slug.'" '.$selected.' >'.__($language->description, 'xili-dictionary').'</option>';
			}
			?>
</select>
		<?php
	}
	
	/**
	 * Add Languages selector in edit.php edit after Category Selector (hook: restrict_manage_posts) only if no XL
	 *
	 * @since 2.0
	 *
	 */
	function restrict_manage_writer_posts () {
		if ( isset ( $_GET['post_type'] ) && $_GET['post_type'] == XDMSG ) {
			$listwriters = get_terms('writer', array('hide_empty' => false));
			if ( $listwriters != array() )  {
				$selected = "";
				if ( isset ( $_GET['writer_name'] )  ) { 
					$selected = $_GET['writer_name'];
				}
				$dropdown_options = array(
						'taxonomy' => 'writer',
						'show_option_all' => __( 'View all writers' ),
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
	function wpse6066_pre_get_posts( &$wp_query )
	{
    	if ( $wp_query->is_tax ) {  ;
        	if ( is_numeric( $wp_query->get( 'writer_name' ) ) ) {
            	// Convert numberic terms to term slugs for dropdown
            	
            	$term = get_term_by( 'term_id', $wp_query->get( 'writer_name' ), 'writer' );
         
            	if ( $term ) {
                	$wp_query->set( 'writer_name', $term->slug );
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
		
		$type  = get_post_meta ( $id, $this->msgtype_meta, true);
		// search msgid
		if ( $type == 'msgid' ) {
			$target_id = $id;
		} elseif ( $type == 'msgid_plural' ) {
			$temp_post = $this->temp_get_post ( $id );
			$target_id = $temp_post->post_parent;
		} else {
			$target_id = get_post_meta ( $id, $this->msgidlang_meta, true);	
		}
		
		if ( $temp_post = $this->temp_get_post ( $target_id ) ) {
		
			$ctxt = get_post_meta ( $target_id, $this->ctxt_meta, true );
			if ( $ctxt != "" && $type != 'msgid' ) printf ( '<strong>ctxt:</strong> %s <br /><br />'  , $ctxt );
			if ( $type == 'msgid' ) {
				if ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'addctxt' ) {
					?>
<label for="add_ctxt"><?php _e('Context','xili-dictionary') ; ?></label>
<input id="add_ctxt" name="add_ctxt"  value="<?php echo $ctxt; ?>" style="width:80%" />
					<?php
					
				} else {
					if ( $ctxt != "" ) { 
						printf ( '<strong>ctxt:</strong> %s <br /><br />'  , $ctxt );
						printf( __('&nbsp;<a href="%s" >Edit context</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=addctxt' );
					} else {
						// link to add ctxt
						printf( __('&nbsp;<a href="%s" >Create context</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=addctxt' );
						
					}
				}
			}
			echo '<p>';
				$extracted_comments = get_post_meta ( $target_id, $this->msg_extracted_comments, true );
				if ( $extracted_comments != "" ) printf ( __('Extracted comments: %s', 'xili-dictionary').'<br />', $extracted_comments );
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
<input id="add_translator_comments" name="add_translator_comments"  value="<?php echo $translator_comments; ?>" style="width:80%" />
				<?php
			}
			
			
			$lines = $temp_post->post_excerpt;
			if ( $lines != "") {
				echo '<p>'; 
				printf ( __('Lines: %s', 'xili-dictionary').'<br />', $lines ); 
				echo '</p>';
			}
			echo '<p><strong>'.sprintf(__('Return to <a href="%s" title="Go to msg list">msg list</a>','xili-dictionary'), $this->xd_settings_page).'</strong></p>';
			echo ( $this->create_line_lang != "" ) ? '<p><strong>'.$this->create_line_lang.'</strong></p>' : "-";
		} else {
			printf ( __('The msgid (%d) was deleted. The msg series must be recreated and commented.','xili-dictionary' ), $target_id );
			echo '<p><strong>'.sprintf(__('Return to <a href="%s" title="Go to msg list">msg list</a>','xili-dictionary'), $this->xd_settings_page).'</strong></p>';
		}
	}
	
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
		}
	}
	
	
	function msg_status_display ( $id ) {
		global $post;
		$spanred = '<span class="alert">';
		$spanend = '</span>';
		
		$type  = get_post_meta ( $id, $this->msgtype_meta, true);
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
			if ( defined ('WP_DEBUG') &&  WP_DEBUG == true ) {
				echo '<div class="msg-saved" >';
				printf( __('%s saved as: <em>%s</em>', 'xili-dictionary'), $this->msg_str_labels[$type], $post->post_content );
				echo '</div>';
			}
			$res = get_post_meta ( $msgid_id, $this->msgchild_meta, false ); 
			$thechilds =  ( is_array ( $res ) &&  array() != $res  ) ? $res[0]  : array();
			
			$res = get_post_meta ( $msgid_id, $this->msglang_meta, false );
			$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
			// action to create child and default line - single or plural...
			if ( isset ($_GET['msgaction'] ) && isset ($_GET['langstr']) ) {
				$target_lang = $_GET['langstr'];
				if ( $_GET['msgaction'] == 'msgstr'  && !isset( $thelangs['msgstrlangs'][$target_lang] ) )  {
				// create post
					if ( !isset ( $thechilds['msgid']['plural'] ) ) {
						
						$msgstr_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate:', 'xili-dictionary').$temp_post_msg_id->post_content , null, 'msgstr' , 0 );
						wp_set_object_terms( $msgstr_post_ID, $target_lang, TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['msgstr'] = $msgstr_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_id );
					 	
						sprintf( 'msgstr created in %s <br/>', $target_lang ) ;
					
					} else {
						// create msgstr_0
						$msgstr_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate (msgstr[0]): ', 'xili-dictionary').$temp_post_msg_id->post_content , null, 'msgstr_0' , 0 );
						wp_set_object_terms( $msgstr_post_ID, $target_lang, TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['msgstr_0'] = $msgstr_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_id );
						
						sprintf( 'msgstr[0] created in %s <br/>', $target_lang ) ;
						
						// create msgstr_1
						$temp_post_msg_id_plural = $this->temp_get_post ( $thechilds['msgid']['plural']  );
						$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
						$msgstr_1_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate (msgstr[1]): ', 'xili-dictionary'). $content_plural , null, 'msgstr_1' , $msgstr_post_ID );
						wp_set_object_terms( $msgstr_1_post_ID, $target_lang, TAXONAME );
						$thelangs['msgstrlangs'][$target_lang]['plural'][1] = $msgstr_1_post_ID;
						update_post_meta ( $msgid_id, $this->msglang_meta, $thelangs );
						update_post_meta ( $msgstr_1_post_ID, $this->msgidlang_meta, $msgid_id );
						
						sprintf( 'msgstr[1] created in %s <br/>', $target_lang ) ;
					}
				}
			}  elseif ( isset ($_GET['msgaction']) && $_GET['msgaction'] == 'msgid_plural'  && !isset( $thelangs['msgstrlangs'] ) ) {
					//error_log ('-------- plural');
					$msgid_plural_post_ID = $this->insert_one_cpt_and_meta ( __('XD say id to plural: ', 'xili-dictionary').$temp_post_msg_id->post_content , null, 'msgid_plural' , $msgid_id );
					$res = get_post_meta ( $msgid_id, $this->msgchild_meta, false ); 
					$thechilds =  ( is_array ( $res ) &&  array() != $res  ) ? $res[0]  : array();			
			}
			
			
			// display current saved content
			
			//if ( $type != "msgid" ) {
				$line = __('msgid:', 'xili-dictionary'); 
				$line .=  '&nbsp;<strong>'. htmlspecialchars($temp_post_msg_id->post_content ) . '</strong>' ;
				if ( $post->ID != $msgid_id ) 
					$line .= sprintf( __('( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'),'post.php?post='.$msgid_id.'&action=edit', $msgid_id, __('Edit') ) ;
				$this->hightlight_line ( $line, $type, 'msgid' );
			//}
			if ( isset ( $thechilds['msgid']['plural'] ) ) {
				$post_status = get_post_status ( $thechilds['msgid']['plural'] ) ;
				$line = "";
				if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
				$line .= __('msgid_plural:', 'xili-dictionary') . '&nbsp;';
				if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;
				$temp_post_msg_id_plural = $this->temp_get_post ( $thechilds['msgid']['plural']  );
				$content_plural = htmlspecialchars( $temp_post_msg_id_plural->post_content );
				$line .= '<strong>'. $content_plural . '</strong> ' ;
				if ( $post->ID != $thechilds['msgid']['plural'] ) 
					$line .= sprintf( __('( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'),'post.php?post='.$thechilds['msgid']['plural'].'&action=edit', $thechilds['msgid']['plural'], __('Edit') ) ;
				$this->hightlight_line ( $line, $type, 'msgid_plural'  );
				
				
			} else {
				if ( !isset ( $thelangs['msgstrlangs'] ) && !isset ( $thechilds['msgid']['plural'] ) ) { // not yet translated
					printf( __('&nbsp;<a href="%s" >Create msgid_plural</a>', 'xili-dictionary'), 'post.php?post='.$id.'&action=edit&msgaction=msgid_plural' );
					echo '<br />';
				}
			}
			
			// display series
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			if (isset ( $thelangs['msgstrlangs'] ) ) {
				$translated_langs = array ();
				echo '<br /><table class="widefat"><thead><tr><th class="column-msgtrans">';
				_e( 'translated in', 'xili-dictionary');
				echo '</th><th>msgstr</th></tr></thead><tbody>';
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
						$translated_langs[] = $curlang;  // detect content empty
						$typeref = 'msgstr_0';
					}
							
					if ( $strid != 0 ) {
						$target_lang = implode ( ' ', wp_get_object_terms( $id, TAXONAME, $args = array( 'fields' => 'names')) );
						echo '<tr><th>';
						printf( '%s : ', $curlang );
						echo '</th><td>';
						$temp_post = $this->temp_get_post ( $strid  );
						$content = htmlspecialchars( $temp_post->post_content );
						$line = "";			
						if ( $str_plural ) $line .= "[0] ";
									
						$line .= '<strong>'. $content . '</strong>' ;
						$post_status = get_post_status ( $strid );
						if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
						if ( $post->ID != $strid )
							$line .= sprintf( ' ( <a href="%s" title="link to:%d">%s</a> )<br />', 'post.php?post='.$strid.'&action=edit', $strid, __('Edit') ) ;
						if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;
						
						$this->hightlight_line_str ( $line, $type, $typeref, $curlang, $target_lang );
									
						if ( $str_plural ) {
							$res = get_post_meta ( $strid, $this->msgchild_meta, false );
							$strthechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
							foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
								$temp_post = $this->temp_get_post ( $strchildid  );
								$content = htmlspecialchars( $temp_post->post_content );
								$line = "";
								$post_status = get_post_status ( $strid );
								if ( $post_status == "trash" || $post_status === false ) $line .= $spanred;
								$line .= sprintf ( '[%s] ', $key );
								if ( $post_status == "trash" || $post_status === false ) $line .= $spanend;
								if ( $post->ID != $strchildid )
									$line .= sprintf ( '<strong>%s</strong> ( %s )<br />', $content, '<a href="post.php?post='.$strchildid.'&action=edit" title="link to:'.$strchildid.'">'.__('Edit').'</a>' ) ;
								$this->hightlight_line_str ( $line, $type, 'msgstr_'.$key, $curlang, $target_lang );
										
							}
										// if possible against current lang add links - compare to count of $strthechilds['msgstr']['plural']
											
						}
						echo '</td></tr>';
					}
							
				}
				echo '</tbody></table>';
				$this->create_line_lang = "";
				if ( count ($translated_langs) !=  count ($listlanguages) )  {
							//echo '<br />';
				$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
				foreach ( $listlanguages as $tolang ) {
					if ( !in_array ( $tolang->name , $translated_langs )  ) 
					 	$this->create_line_lang .= sprintf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', 'post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name );				
					 }
				}
			} else {
				$this->create_line_lang = "";
				if ( !isset ($_POST['msgaction'] ) || ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'msgid_plural' ) ) {
					 _e( 'not yet translated.', 'xili-dictionary'); 
					
					$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
					 foreach ( $listlanguages as $tolang ) {
					 	$this->create_line_lang .= sprintf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', 'post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name );				
					 }
				}
			}
		} else {
			
			printf ( __('The msgid (%d) was deleted. The msg series must be recreated.','xili-dictionary' ), $msgid_id );
		}
	}
	
	function hightlight_line ( $line, $cur_type, $type ) {
		if ( $cur_type == $type) {
			echo '<span class="editing">'.$line.'</span>';
		} else {
			echo $line;
		}
	}
	
	function hightlight_line_str ( $line, $cur_type, $type, $cur_lang, $lang ) {
		if ( $cur_type == $type && $cur_lang == $lang ) {
			echo '<span class="editing">'.$line.'</span>';
		} else {
			echo $line;
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
			$type  = get_post_meta ( $id, $this->msgtype_meta, true);
			
			$res = get_post_meta ( $id, $this->msgchild_meta, false ); 
			$thechilds =  ( is_array ( $res ) &&  array() != $res  ) ? $res[0]  : array();
			
			$res = get_post_meta ( $id, $this->msglang_meta, false );
			$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
			
			if ( $type == 'msgid' ) {
				$ctxt = get_post_meta ( $id, $this->ctxt_meta, true ); 
				if ( $post->post_status == "trash" )  echo $spanred;
				if ( $display ) {
					echo '<div class="msg-saved" >';
					printf( __('msgid saved as: <em>%s</em>', 'xili-dictionary'), ( $post->post_content ) );
					echo '</div>';
				} else {
					echo 'msgid';
				}
				if ( $post->post_status == "trash" ) echo $spanend;
				echo '<br />';
				if ( $ctxt != "" && !$display ) printf ( 'ctxt: %s <br />'  , $ctxt ); 
				
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
						$temp_post = $this->temp_get_post ( $thechilds['msgid']['plural']  );
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
				$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
				// action to create child and default line - single or plural...
				if ( isset ($_GET['msgaction'] ) && isset ($_GET['langstr']) && $display) {
					$target_lang = $_GET['langstr'];
					if ( $_GET['msgaction'] == 'msgstr'  && !isset( $thelangs['msgstrlangs'][$target_lang] ) )  {
					// create post
						if ( !isset ( $thechilds['msgid']['plural'] ) ) {
							
							$msgstr_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate: ', 'xili-dictionary').$post->post_content , null, 'msgstr' , 0 );
							wp_set_object_terms( $msgstr_post_ID, $target_lang, TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['msgstr'] = $msgstr_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $id );
						 	
							sprintf( 'msgstr created in %s <br/>', $target_lang ) ;
						
						} else {
							// create msgstr_0
							$msgstr_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate (msgstr[0]): ', 'xili-dictionary').$post->post_content , null, 'msgstr_0' , 0 );
							wp_set_object_terms( $msgstr_post_ID, $target_lang, TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['msgstr_0'] = $msgstr_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $id );
							
							sprintf( 'msgstr[0] created in %s <br/>', $target_lang ) ;
							
							// create msgstr_1
							$msgstr_1_post_ID = $this->insert_one_cpt_and_meta ( __('XD say to translate (msgstr[1]): ', 'xili-dictionary'). $content_plural , null, 'msgstr_1' , $msgstr_post_ID );
							wp_set_object_terms( $msgstr_1_post_ID, $target_lang, TAXONAME );
							$thelangs['msgstrlangs'][$target_lang]['plural'][1] = $msgstr_1_post_ID;
							update_post_meta ( $id, $this->msglang_meta, $thelangs );
							update_post_meta ( $msgstr_1_post_ID, $this->msgidlang_meta, $msgid_id );
							
							sprintf( 'msgstr[1] created in %s <br/>', $target_lang ) ;
						}
					} elseif ( $_GET['msgaction'] == 'msgid_plural'  && !isset( $thelangs['msgstrlangs'][$target_lang] ) ) {
						
						$msgid_plural_post_ID = $this->insert_one_cpt_and_meta ( __('XD say id to plural: ', 'xili-dictionary').$post->post_content , null, 'msgid_plural' , $id );
							
					}
				}
				$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
				if (isset ( $thelangs['msgstrlangs'] ) ) {
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
							$translated_langs[] = $curlang;  // detect content empty
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
								$temp_post = $this->temp_get_post ( $strid  );
								$content = htmlspecialchars( $temp_post->post_content );
								
								if ( $str_plural ) echo "[0] ";
								
								echo '<strong>'. $content . '</strong>' ;
								$post_status = get_post_status ( $strid );
								if ( $post_status == "trash" || $post_status === false ) echo $spanred;
							 		printf( ' ( <a href="%s" title="link to:%d">%s</a> )<br />', 'post.php?post='.$strid.'&action=edit', $strid, __('Edit') ) ;
								if ( $post_status == "trash" || $post_status === false ) echo $spanend;
								
								if ( $str_plural ) {
									$res = get_post_meta ( $strid, $this->msgchild_meta, false );
									$strthechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
									foreach ( $strthechilds['msgstr']['plural'] as $key => $strchildid ) {
										$temp_post = $this->temp_get_post ( $strchildid  );
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
					if ( $display )  echo '</tbody></table>';
					$this->create_line_lang = "";
					if ( $display && ( count ($translated_langs) !=  count ($listlanguages) ) ) {
						//echo '<br />';
						$this->create_line_lang = __('Create msgstr in: ', 'xili-dictionary');
						foreach ( $listlanguages as $tolang ) {
							if ( !in_array ( $tolang->name , $translated_langs )  ) 
				 				$this->create_line_lang .= sprintf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', 'post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name );				
				 		}
					}
						
				} else { // no translation
					if ( !isset ($_POST['msgaction'] ) || ( isset ($_GET['msgaction'] ) && $_GET['msgaction'] == 'msgid_plural' ) ) {
				 		_e( 'not yet translated.', 'xili-dictionary'); 
				 		echo '&nbsp';
				 		if ( $display ) { 
				 			_e('Create msgstr in: ', 'xili-dictionary');
				 				
				 			foreach ( $listlanguages as $tolang ) {
				 				printf( '&nbsp;<a href="%s" >'.$tolang->name.'</a>', 'post.php?post='.$id.'&action=edit&msgaction=msgstr&langstr='.$tolang->name );				
				 			}
				 		}
					}
				}	
				
				
			} elseif (  $type != '' ) {
				
				$msgid_ID = get_post_meta ( $id, $this->msgidlang_meta , true);
				
			
				
				
				if ( $display  && ( $type == 'msgid_plural' || ( false !== strpos( $type, 'msgstr_' ) && substr( $type, -1 ) !='0')  ) ) {
					$temp_post = $this->temp_get_post ( $post->post_parent  );
					$content = htmlspecialchars( $temp_post->post_content ) ;
					$target_lang = implode ( ' ', wp_get_object_terms( $id, TAXONAME, $args = array( 'fields' => 'names')) );
					$is_plural = true;
				} elseif ( $display ) {
					$temp_post = $this->temp_get_post ( $msgid_ID  );
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
							printf( __('msgid plural of: <strong>%s</strong> ( <a href="%s" title="link to:%d" >%s</a> )<br />', 'xili-dictionary'), $content,'post.php?post='.$post->post_parent.'&action=edit', $post->post_parent, __('Edit')  );
						} else {
							printf( __('msgid plural of: <a href="%s" >%d</a><br />', 'xili-dictionary'),'post.php?post='.$post->post_parent.'&action=edit',$post->post_parent ) ;
						}
						if ( $span_parent ) echo $spanend ;
						
						
						break;
					case 'msgstr':
						if ( $display  ) echo '<strong>'.$target_lang."</strong> translation of: <strong>" . $content . '</strong> ';
						if ( $span_msgid ) echo $spanred ;
						if ( $display  ) {
							printf( __('( <a href="%s" title = "link of:%d">%s</a> )<br />', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID, __('Edit') );
						} else {
							
							printf( __('msgstr of: <a href="%s" >%d</a><br />', 'xili-dictionary'), 'post.php?post='.$msgid_ID.'&action=edit', $msgid_ID );
						}
						if ( $span_msgid ) echo $spanend ;
						//if ( $display ) echo  '<strong>'.$content .'</strong>';
						break;
					
					default:
						if ( false !== strpos( $type, 'msgstr_' ) ) {
							$indices = explode ('_', $type);
							$indice = $indices[1];
							$edit_id = ( $indice == 0 ) ? $msgid_ID : $post->post_parent ;
							
							if ( $display ) {
								if ( $is_plural ) {
									printf(__( '<strong>%s</strong> plural of: <strong>%s</strong>( <a href="%s" title="link to:%d">%s</a> )<br />', ''),$target_lang,  $content, 'post.php?post='.$edit_id.'&action=edit' , $edit_id, __('Edit') );
								} else {
									printf(__( '<strong>%s</strong> translation of: <strong>%s</strong>( <a href="%s" title="link to:%d">%s</a> )<br />', ''),$target_lang,  $content, 'post.php?post='.$edit_id.'&action=edit' , $edit_id, __('Edit') );
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
	 * @since 2.4
	 */
	function auto_save_unsetting() {
		global $hook_suffix, $post ;
		if ( isset($_GET['post_type']) )
			$type = $_GET['post_type'];
		
		if ( ( $hook_suffix == 'post-new.php' && $type == XDMSG ) || ( $hook_suffix == 'post.php' && $post->post_type == XDMSG  )) {
						
						wp_dequeue_script('autosave');
						//wp_deregister_script('autosave');
						//$wp_scripts->queue = array_diff( $wp_scripts->queue , array('autosave')  );
						//error_log( serialize($wp_scripts ->queue ) );
						
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
	 * @since 1.3.0 for js
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_script( 'datatables', plugins_url('js/jquery.dataTables.min.js', __FILE__ ) , array( 'jquery' ), '1.7.4', true );
	}
	
	function admin_enqueue_styles() {
		wp_enqueue_style('table_xdstyle'); // style of js table
	}
	
	function admin_init() {
        /* Register our script. */
        wp_register_script( 'datatables', plugins_url('js/jquery.dataTables.min.js', __FILE__ ) );
        wp_register_style( 'table_xdstyle', plugins_url('/css/xd_table.css', __FILE__ ) );
    }
	
	/** *add admin menu and associated page */
	function xili_add_dict_pages() {
		
		//$this->thehook = add_management_page(__('Xili Dictionary','xili-dictionary'), __('xili Dictionary','xili-dictionary'), 'import', 'dictionary_page', array(&$this,'xili_dictionary_settings'));
		
		$this->thehook = add_submenu_page( 'edit.php?post_type='.XDMSG, __('Xili Dictionary','xili-dictionary'), __('Tools, Files po mo','xili-dictionary'), 'import', 'dictionary_page', array(&$this,'xili_dictionary_settings') );
		
		 add_action('load-'.$this->thehook, array(&$this,'on_load_page'));
		  	
		 add_action( 'admin_print_scripts-'.$this->thehook, array(&$this,'admin_enqueue_scripts') );
		 add_action( 'admin_print_styles-'.$this->thehook, array(&$this,'admin_enqueue_styles') );	
		 
		 // Add to end of admin_menu action function
		global $submenu;
		$submenu['edit.php?post_type='.XDMSG][5][0] = __('Msg list','xili-dictionary'); // sub menu
		$post_type_object = get_post_type_object(XDMSG);
		$post_type_object->labels->name = __('XD Msg list','xili-dictionary'); // title list screen
	}
	
	function on_load_page() {
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');
			
			add_meta_box('xili-dictionary-sidebox-1', __('Message','xili-dictionary'), array(&$this,'on_sidebox_1_content'), $this->thehook , 'side', 'core');
			add_meta_box('xili-dictionary-sidebox-2', __('Info','xili-dictionary'), array(&$this,'on_sidebox_2_content'), $this->thehook , 'side', 'core');
			
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
		
		load_plugin_textdomain('xili-dictionary', false, 'xili-dictionary/languages' );
		
		if ( class_exists('xili_language') ) {
			global $xili_language ;
			$langs_folder = $xili_language->xili_settings['langs_folder']; // set by override_load_textdomain filter
			if ( $this->xili_settings['langs_folder'] != $langs_folder ) { 
		 		$this->xili_settings['langs_folder'] = $langs_folder ;
		 		update_option('xili_dictionary_settings', $this->xili_settings);
		 	}
		} else {
			if ( file_exists( $this->get_template_directory ) ) // when theme was unavailable
				$this->find_files($this->get_template_directory, '/^.*\.(mo|po|pot)$/', array(&$this,'searchpath'));
		}
	}
	
	/* call by findfiles */
	function searchpath($path, $filename) { 
		 $langs_folder = str_replace($this->get_template_directory,'',$path); // updated 1.2.0
		 if ( $this->xili_settings['langs_folder'] != $langs_folder ) { 
		 	$this->xili_settings['langs_folder'] = $langs_folder ;
		 	update_option('xili_dictionary_settings', $this->xili_settings);
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
			
			if 	(!$this->xili_settings['xl-dictionary-langs']) {
				
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
				if ( defined ('WPLANG') ) { 
					$lkey = strtolower(WPLANG);
					if (!array_key_exists($lkey, $default_langs_array)) $default_langs_array[$lkey] = array (WPLANG, WPLANG);
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
 	
 	function popupmenu_language_list ( $action = "" ) { ?>
<select name="target_lang" ><?php
	   			$extend = WPLANG;
	   			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
				foreach ($listlanguages as $reflanguage) {
	     			echo '<option value="'.$reflanguage->name.'"'; 
	     			if ($extend == $reflanguage->name) { 
	     				echo ' selected="selected"';
	     			} 
	     				echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
	     			
	     		}
	     		
	     		if ( $action=='import' ) { // to import .pot of current domain 1.0.5
	     			if (function_exists('the_theme_domain')) {// in new xili-language
	     				echo '<option value="'.the_theme_domain().'" >'.the_theme_domain().'.pot</option>';
	    			} else {
						
						echo '<option value="'.$theme_name.'" >'.$theme_name.'.pot</option>';
					}	
	     		}
	     		?>
</select>
 	<?php 
 	}
	
	/**
	 * private functions for dictionary_settings
	 * @since 0.9.3
	 *
	 * fill the content of the boxes (right side and normal)
	 * 
	 */
	
	function  on_sidebox_1_content($data) { 
		extract($data);
		?>
<h4><?php _e('Note:','xili-dictionary') ?></h4>
<p><?php echo $message; ?></p>
		<?php
	}
	
	function  on_sidebox_2_content() { 
		$template_directory = $this->get_template_directory;
		if ( function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 and WP 3.0
			$theme_name = get_option("stylesheet").' '.__('child of','xili-language').' '.get_option("template"); 
		} else {
			$theme_name = get_option("template"); 
		}
		
		if ( $this->xililanguagepremium ) {
	   		echo '<p><em>'.__('xili-language premium is active !','xili-dictionary').'</em></p>';
	   	
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
<p><?php _e('xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. With this dictionary, it is possible to create and update .mo file in the current theme folder. And more...','xili-dictionary') ?>
</p>
<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;">
	<legend><?php echo __("Theme's informations:",'xili-dictionary').' ('. $theme_name .')'; ?></legend>
	<p>
				<?php $langfolder = $this->xili_settings['langs_folder'];
				echo __("Languages sub-folder:",'xili-dictionary').' '. $langfolder; ?><br />
	 			<?php 
	 			if ( $langfolder == 'unknown' ) { ?><span style='color:red'><?php
	 			_e("No languages files are present in theme's folder or theme's sub-folder: <strong>add at least a .po or a .mo inside.</strong><br /> Errors will occur if you try to import or export!",'xili-dictionary'); echo '<br />';	?></span> <?php
	 			} else {
	 			_e('Available MO files:','xili-dictionary'); echo '<br />';
	 			if ( file_exists( $this->get_template_directory ) ) // when theme was unavailable
	 				$this->find_files($this->get_template_directory, '/.mo$/', array(&$this,'available_mo_files')) ;
	 			}
	 			
	 			?>
	</p>
</fieldset>
		
		<?php
	}
	
	
	
	/**
	 * @since 2.0 with datatables js (ex widefat)
	 *
	 */
	function on_normal_cpt_content_list( $data ) { 
		extract($data); 
		$sortparent = (($this->subselect == '') ? '' : '&amp;tagsgroup_parent_select='.$this->subselect );
		?>
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
			<th scope="col" class="colgroup center"><?php _e('Save status','xili-dictionary') ?></th>
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
	
	function on_normal_2_files_dialog ( $data ) {
		extract( $data ); 
		?>
<div style="background:#f5f5fe;">
	<p id="add_edit"><?php _e($formhow,'xili-dictionary') ?></p>
		<?php 
		if ( $action=='export' || $action=='importmo' || $action=='import' || $action=='exportpo' ) { 
			if ( function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 and WP 3.0
							$theme_name = get_option("stylesheet").' '.__('child of','xili-language').' '.get_option("template"); 
						} else {
							$theme_name = get_option("template"); 
						}
			
			?>
	<label for="language_file">
		<select name="language_file" ><?php
	   			$extend = WPLANG;
	   			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' );//get_terms(TAXONAME, array('hide_empty' => false));
				
     			foreach ($listlanguages as $reflanguage) {
     				echo '<option value="'.$reflanguage->name.'"'; 
     				if ($extend == $reflanguage->name) { 
     					echo ' selected="selected"';
     				} 
     				echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
     			
     			}
	     		
	     		if ( $action=='import' ) { // to import .pot of current domain 1.0.5
	     			if ( function_exists('the_theme_domain') ) {// in new xili-language
	     				echo '<option value="'.the_theme_domain().'" >'.the_theme_domain().'.pot</option>';
	    			} else {
						
						echo '<option value="'.$theme_name.'" >'.$theme_name.'.pot</option>';
					}	
	     		}
	     		?>
		</select>
	</label> <?php 
	
				if ( $action == 'import' ) {	// import po comment option 2.0-rc2
				
				?>
				<label for="importing_po_comments">&nbsp;<?php _e( 'What about comments', 'xili-dictionary' ); ?>:&nbsp;
				<select name="importing_po_comments" id="importing_po_comments">
					<option value="" ><?php _e('No change','xili-dictionary'); ?></option>
					<option value="replace" ><?php _e('Imported comments replace those in list','xili-dictionary'); ?></option>
					<option value="append" ><?php _e('Imported comments be appended...','xili-dictionary'); ?></option>
				</select>	
				</label>
				<?php
				} 
	     	
	     		if ( ($action=='export' || $action=='exportpo' ) && is_multisite() && is_super_admin() && $this->xililanguagepremium ) { ?>
	<p><?php printf (__('Verify before that you are authorized to write in languages folder in theme named: %s','xili-dictionary'), $theme_name ) ?>
	</p>
	     		<?php }
		     	if ( $action=='export' && is_multisite() && is_super_admin() && !$this->xililanguagepremium ) { ?>
	<label for="only-theme">&nbsp;
		     	<?php _e('SuperAdmin: only as theme .mo','xili-dictionary') ?>
		<input id="only-theme" name="only-theme" type="checkbox" value="only" />
	</label>
	     	
	     	<?php } ?>
	<br />&nbsp;<br />
	<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
	<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br />
</div>
		<?php
		} elseif ($action=='importbloginfos' || $action=='importtaxonomy' || $action=='erasedictionary' || $action=='importcurthemeterms') {
			
			if ( $action == 'importtaxonomy' ) { ?>
<label for="taxonomy_name"><?php _e('Slug:','xili-dictionary') ?></label>
<input name="taxonomy_name" id="taxonomy_name" type="text" value="<?php echo ( $selecttaxonomy != '') ? $selecttaxonomy : 'category'; ?>" /><br />
			<?php }
			?>
<br />&nbsp;<br />
<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br />
</div>
		<?php
	
			
		} else {
			echo 'This box is used for input dialog, leave it opened and visible...';
		}	
		?>
</div><?php
	}
	
	
	/** 
	 * @updated 1.0.2 
	 * manage files 
	 */
	function on_normal_3_content( $data ) { 
		extract( $data );
		?>
<h4 id="manage_file"><?php _e('The files','xili-dictionary') ;?></h4>
	   	<?php 
	   			
	   	$linkstyle = "text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;" ;
	   	$linkstyle3 = "text-decoration:none; text-align:center; display:inline-block; width:16%; margin:0px 1px 1px 10px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;  border:1px #ccc solid;" ;
	   	$linkstyle1 = $linkstyle." border:1px #33f solid;";
	   	$linkstyle2 = $linkstyle." border:1px #ccc solid;";
	   	?>
<a style="<?php echo $linkstyle1 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=export'; ?>" title="<?php _e('Create or Update mo file in current theme folder','xili-dictionary') ?>"><?php _e('Export mo file','xili-dictionary') ?></a>
&nbsp;<br /><?php _e('Import po/mo file','xili-dictionary') ?>:<a style="<?php echo $linkstyle3 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=import'; ?>" title="<?php _e('Import an existing .po file from current theme folder','xili-dictionary') ?>">PO</a>
<a style="<?php echo $linkstyle3 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=importmo'; ?>" title="<?php _e('Import an existing .mo file from current theme folder','xili-dictionary') ?>">MO</a><br />
&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=exportpo'; ?>" title="<?php _e('Create or Update po file in current theme folder','xili-dictionary') ?>"><?php _e('Export po file','xili-dictionary') ?></a>
<h4 id="manage_categories"><?php _e('The taxonomies','xili-dictionary') ;?></h4>
<a style="<?php echo $linkstyle2 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=importtaxonomy'; ?>" title="<?php _e('Import name and description of taxonomy','xili-dictionary') ?>"><?php _e('Import terms of taxonomy','xili-dictionary') ?></a>
<h4 id="manage_categories"><?php _e('The website infos (title, sub-title and more…)','xili-dictionary') ;?></h4>
	   	<?php if ( class_exists ('xili_language') && XILILANGUAGE_VER > '2.3.9'	) {
	   		_e ( '…and comment, locale, date terms.', 'xili-dictionary' ); echo '<br /><br />';
	   	} ?>
<a style="<?php echo $linkstyle2 ; ?>" href="<?php echo $this->xd_settings_page.'&amp;action=importbloginfos'; ?>" title="<?php _e('Import infos of web site and more','xili-dictionary') ?>"><?php _e("Import terms of website's infos",'xili-dictionary') ?></a>
	
	<?php // erase and import theme 
	}
	
	/** 
	 * @since 090423 - 
	 * Sub selection box
	 */
	function on_normal_4_content($data=array()) {
		extract($data);
		?>
<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
	<legend><?php _e('Sub list of terms','xili-dictionary'); ?></legend>
	<label for="tagsnamelike"><?php _e('Starting with:','xili-dictionary') ?></label>
	<input name="tagsnamelike" id="tagsnamelike" type="text" value="<?php echo $tagsnamelike; ?>" /><br />
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
	function on_normal_5_content($data=array()) {
		extract($data);
		?>
<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
	<legend><?php _e('Language to delete','xili-dictionary'); ?></legend>
	<p><?php _e('Only the languages list is here modified (but not the dictionary\'s contents)','xili-dictionary'); ?>
	</p>
	<select name="langs_list" id="langs_list" style="width:100%;">
		<option value="no_select" ><?php _e('Select...','xili-dictionary'); ?></option>
		  				<?php echo $this->build_grouplist('');
		  				?>
	</select>
	<br />
	<p class="submit">
		<input type="submit" id="lang_delete" name="lang_delete" value="<?php _e('Delete a language','xili-dictionary'); ?>" />
	</p>
</fieldset><br />
<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;">
	<legend><?php _e('Language to add','xili-dictionary'); ?></legend>
	<label for="lang_ISO"><?php _e('ISO (xx_YY)','xili-dictionary') ?></label>:&nbsp;
	<input name="lang_ISO" id="lang_ISO" type="text" value="" size="5"/><br />
	<label for="lang_name"><?php _e('Name (eng.)','xili-dictionary') ?></label>:&nbsp;
	<input name="lang_name" id="lang_name" type="text" value="" size="20" />
	<br />
	<p class="submit">
		<input type="submit" id="lang_add" name="lang_add" value="<?php _e('Add a language','xili-dictionary'); ?>" />
	</p>
</fieldset>
	<?php }
	
	/**
	 * build the list of group of languages for dictionary
	 *
	 * @updated 1.0.2
	 *
	 */
	 function build_grouplist ($left_line = 'Only:') {
	 	  
	 		$listdictlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
	 		$optionlist = "";
	 		foreach($listdictlanguages as $dictlanguage) {
	 			$checked = ($this->subselect == $dictlanguage->slug) ? 'selected="selected"' :'' ; 
		  		$optionlist .= '<option value="'.$dictlanguage->slug.'" '.$checked.' >'.$dictlanguage->name .' ('.$dictlanguage->description.')</option>'; 
	 		}
	 	
	 	return $optionlist;
	 }
	 
	 /**
	  * @since 1.3.3
	  * 
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
		$action = "";
		
		$term_id = 0;
		$formtitle = __('Dialog box','xili-dictionary');
		$formhow = " ";
		$submit_text = __('Do &raquo;','xili-dictionary');
		$cancel_text = __('Cancel');
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ( $langfolderset !='' )  ? $langfolderset.'/' : '/';
		// doublon 
		$this->langfolder = str_replace ("//","/", $this->langfolder ); // upgrading... 2.0 and sub folder sub
		$selecttaxonomy = "";
		
		$tagsnamelike = ( isset ( $_POST['tagsnamelike'] ) ) ? $_POST['tagsnamelike'] : '';
		if (isset($_GET['tagsnamelike']))
		    $tagsnamelike = $_GET['tagsnamelike']; /* if link from table */
		$tagsnamesearch = ( isset ( $_POST['tagsnamesearch'] ) ) ? $_POST['tagsnamesearch'] : '';
		if (isset($_GET['tagsnamesearch']))
			$tagsnamesearch = $_GET['tagsnamesearch'];
		
		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];
		
		} elseif (isset($_POST['action'])) {
			$action=$_POST['action'];
		}
		
		if (isset($_GET['action'])) {
			$action=$_GET['action'];
			
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
		if(isset($_POST['tagsgroup_parent_select']) && $_POST['tagsgroup_parent_select'] != 'no_select') {
				$this->subselect = $_POST['tagsgroup_parent_select'];
			} else {
				$this->subselect = '';
			}
		if (isset($_GET['tagsgroup_parent_select']))
			$this->subselect = $_GET['tagsgroup_parent_select'];
				
		if (isset($_POST['subselection'])) {
			$action='subselection';
		}
		
		$message = ''; //$action." = " ; 
		$msg = 0;
	switch($action) {
		
		case 'lang_delete';
				$reflang = $_POST['langs_list'];
				$wp_lang = (defined('WPLANG')) ? strtolower(WPLANG) : 'en_us'; 
				if ($reflang != 'no_select' &&  $reflang != 'en_us' &&  $reflang != $wp_lang) {
					$ids = term_exists($reflang, TAXONAME);
					if ( $ids ) {
						if ( is_wp_error( $ids )  ) {
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
				if ($reflang != '???' && ( ( strlen($reflang) == 5 && substr($reflang,2,1) == '_') ) || ( strlen($reflang) == 2 ) ) {
					
					$args = array( 'alias_of' => '', 'description' => $reflangname, 'parent' => 0, 'slug' =>strtolower( $reflang ));
					$theids = $this->safe_lang_term_creation ( $reflang, $args );
					wp_set_object_terms($theids['term_id'], 'the-langs-group', TAXOLANGSGROUP);
					$message .= ' '.$reflang.$msg;
				} else {
					$message .= ' error ('.$reflang.') ! no add';
				}
				
				$actiontype = "add";
				break;
				
		case 'subselection';
				$tagsnamelike = $_POST['tagsnamelike'];
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
				$message .= ' sub list of terms starting with '.$_POST['tagsnamelike'];
				$actiontype = "add";
				break;
				    
		case 'export';
			 $actiontype = "exporting";
			 $formtitle = __('Export mo file','xili-dictionary');
			 $formhow = __('To create a .mo file, choose language and click below.','xili-dictionary');
			 $submit_text = __('Export &raquo;','xili-dictionary');
		     break;
		     
		case 'exporting';
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
		     if ("" != $selectlang){
		     	//$this->xili_create_mo_file(strtolower($selectlang));
		     	$file = "";
		     	
		     	if ( is_multisite() ) { /* complete theme's language with db structure languages (cats, desc,…) in uploads */
					//global $wpdb;
    				//$thesite_ID = $wpdb->blogid;
   					$superadmin = ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'only') ? true : false ;
   					$message .= ( isset ( $_POST['only-theme'] ) && $_POST['only-theme'] == 'only') ? "- exported only in theme - " : "- exported in uploads - " ;
   					$mo = $this->from_cpt_to_POMO_wpmu ( $selectlang, 'mo', $superadmin );
   					if (($uploads = xili_upload_dir()) && false === $uploads['error'] ) {
   						$file = ( $superadmin === true ) ? "" : $uploads['path']."/".$selectlang.".mo";
   					} 
    			} else {
		     		
		     		$mo = $this->from_cpt_to_POMO ($selectlang);
		     	}
		     	
		     	if ( false === $this->Save_MO_to_file ($selectlang , $mo, $file ) ) { 
		     		$message .= $file.' '.sprintf(__('error during exporting in  %1s.mo file.','xili-dictionary'),$selectlang);
		     	} else {
		     		$message .= ' '.sprintf(__('exported in %1s.mo file.','xili-dictionary'),$selectlang);
		     	}
		     }	else {
		     	$message .= ' : error "'.$selectlang.'"';
		     }
		     $msg = 6 ;	
		     break;
		     
		case 'exportpo';
			 $actiontype = "exportingpo";
			 $formtitle = __('Export po file','xili-dictionary');
			 $formhow = __('To export terms in a .po file, choose language and click below.','xili-dictionary');
			 $submit_text = __('Export &raquo;','xili-dictionary');
		     break;
		     
		case 'exportingpo';
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
		     if ("" != $selectlang){
		     	
		     	$po = $this->from_cpt_to_POMO ($selectlang,'po');
		     	
		     	if (false === $this->Save_PO_to_file ($selectlang , $po )) {	
		     		$message .= ' '.sprintf(__('error during exporting in  %1s.po file.','xili-dictionary'),$selectlang);
		     	} else {
		     		$message .= ' '.sprintf(__('exported in %1s.po file.','xili-dictionary'),$selectlang);
		     	}	
		     } else {
		     	$message .= ' : error "'.$selectlang.'"';
		     }	
		     break; 
		         
		case 'import';
			$actiontype = "importing";
		    $formtitle = __('Import po file','xili-dictionary');
		    $formhow = __('To import terms of the current .po, choose language and click below.','xili-dictionary');
			$submit_text = __('Import &raquo;','xili-dictionary'); 
		    break;
		    
		case 'importmo';
			$actiontype = "importingmo";
		    $formtitle = __('Import mo file','xili-dictionary');
		    $formhow = __('To import terms of the current .mo, choose language and click below.','xili-dictionary');
			$submit_text = __('Import &raquo;','xili-dictionary'); 
		    break;
		    
		case 'importing';
			$actiontype = "add";
		    $message .= ' '.__('line imported from po file: ','xili-dictionary');
		    $selectlang = $_POST['language_file'];
		    $this->importing_po_comments = $_POST['importing_po_comments']; // 2.0-rc2 
		    
		    $po = $this->pomo_import_PO ($selectlang); 
		    if (false !== $po ) {
				$nblines = $this->from_pomo_to_cpts ( $po, $selectlang ) ; //echo "new method"; 
		    
				$message .= __('id lines = ','xili-dictionary').$nblines[0].' & ' .__('str lines = ','xili-dictionary').$nblines[1] ;
			} else {
				
		    	$readfile = $this->get_template_directory.$this->langfolder.$selectlang.'.po';
				$message .= ' '.$readfile.' > '.__('po file is not present.','xili-dictionary');
			}	
		    break;
		
		case 'importingmo';
			$actiontype = "add";
		    $message .= ' '.__('line imported from mo file: ','xili-dictionary');
		    $selectlang = $_POST['language_file'];
		    $mo = $this->pomo_import_MO ($selectlang);
		    
		    if (false !== $mo ) {
		    	$this->from_pomo_to_cpts ( $mo, $selectlang ) ; echo "new method";
		    
				$message .= __('id lines = ','xili-dictionary').$nblines[0].' & ' .__('str lines = ','xili-dictionary').$nblines[1].' & ' .__('str lines up = ','xili-dictionary').$nblines[2];
		    } else {				
		    	$readfile = $this->get_template_directory.$this->langfolder.$selectlang.'.mo';
				$message .= ' '.$readfile.' > '.__('mo file is not present.','xili-dictionary');
			}	
		    break;
		
		case 'importbloginfos'; // bloginfos and others since 1.1.0 
			$actiontype = "importingbloginfos";
		    $formtitle = __('Import terms of blog info and others…','xili-dictionary');
		    $formhow = __('To import terms of blog info and others…, click below.','xili-dictionary');
			$submit_text = __('Import blog info terms &raquo;','xili-dictionary');
			break;
		
		case 'importingbloginfos'; // bloginfos and others since 1.1.0
		  	check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
		    
		    $infosterms = $this->xili_import_infosterms_cpt (); 
			 
			$msg = 4;
			if ($infosterms[1] > 0) {
				$message .= ' ('.$infosterms[1].'/'.$infosterms[0].') '.__('imported with success','xili-dictionary');
			} else {
				$message .= ' '.__('already imported','xili-dictionary') . ' (' .$infosterms[0].') ';
			}	
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
		    	$nbterms = $this->xili_read_catsterms_cpt( $selecttaxonomy ); //$this->xili_read_catsterms();
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
			$actiontype = "add";
		    $message .= ' '.__('All terms erased !','xili-dictionary'); $msg = 7;
		    // for next update
		    break; 
		    
		 case 'importcurthemeterms';
		 	$actiontype = "importingcurthemeterms";
		    $formtitle = __('Import all terms from current theme','xili-dictionary');
		    $formhow = __('To import terms of the current theme, click below.','xili-dictionary');
			$submit_text = __('Import all terms &raquo;','xili-dictionary'); 
			
			$this->tempoutput = '<strong>'.__('List of scanned files:','xili-dictionary').'</strong><br />';
			$themeterms = $this->scan_import_theme_terms(array(&$this,'build_scanned_files'),2);
			$formhow = $this->tempoutput.'<br /><br /><strong>'.$formhow .'</strong>';
			
		    break;
		 
		 case 'importingcurthemeterms';   
		    $actiontype = "add";
		    $message .= ' '.__('All terms imported !','xili-dictionary'); $msg = 5 ;
		    	$themeterms = $this->scan_import_theme_terms(array(&$this,'build_scanned_files'),0);
		    if (is_array($themeterms)) {
				//$nbterms = $this->xili_importthemeterms_in_tables($themeterms); 
				$message .= __('terms = ','xili-dictionary').$nbterms;
			} else {
				$message .= ' '.$readfile.__('theme’s terms pbs!','xili-dictionary');
			}
		    break;  
	     case 'reset';    
			    $actiontype = "add";
			    break;    
		default:
		    $actiontype = "add";
		    $message .= ' '.__('Find above the list of terms.','xili-dictionary');
		        
		}
		/* register the main boxes always available */
		
		add_meta_box('xili-dictionary-sidebox-3', __('Import & export','xili-dictionary'), array(&$this,'on_normal_3_content'), $this->thehook , 'side', 'core'); /* files */
		add_meta_box('xili-dictionary-sidebox-4', __('Terms list management','xili-dictionary'), array(&$this,'on_normal_4_content'), $this->thehook , 'side', 'core'); /* files */
		if ($this->xililanguage != 'isactive')
				add_meta_box('xili-dictionary-sidebox-5', __('Languages list management','xili-dictionary'), array(&$this,'on_normal_5_content'), $this->thehook , 'side', 'core'); /* Languages list when xili-language is absent */
		
		add_meta_box('xili-dictionary-normal-1', __( $formtitle, 'xili-dictionary'), array(&$this,'on_normal_2_files_dialog'), $this->thehook , 'normal', 'core'); /* input form */
		add_meta_box('xili-dictionary-normal-cpt', __('Multilingual Terms','xili-dictionary'), array(&$this,'on_normal_cpt_content_list'), $this->thehook , 'normal', 'core'); /* list of terms*/
		
		
		// since 1.2.2 - need to be upgraded...
		if ($msg == 0 && $message != '' ) $msg = 6 ; //by temporary default
		$themessages[1] = __('A new term was added.','xili-dictionary');
		$themessages[2] = __('A term was updated.','xili-dictionary');
		$themessages[3] = __('A term was deleted.','xili-dictionary');
		$themessages[4] = __('terms imported from WP: ','xili-dictionary') . $message;
		$themessages[5] = __('All terms imported !','xili-dictionary');
		$themessages[6] = 'beta testing log: '.$message ;
		$themessages[7] = __('All terms erased !','xili-dictionary');
		$themessages[8] = __('Error when adding !','xili-dictionary');
		$themessages[9] = __('Error when updating !','xili-dictionary');
		
		/* form datas in array for do_meta_boxes() */
		$data = array('message'=>$message, 'action'=>$action, 'formtitle'=>$formtitle, 'submit_text'=>$submit_text,'cancel_text'=>$cancel_text, 'formhow'=>$formhow, 'orderby'=>$orderby,'term_id'=>$term_id, 'tagsnamesearch'=>$tagsnamesearch, 'tagsnamelike'=>$tagsnamelike, 'selecttaxonomy' =>$selecttaxonomy);
		if ( isset ( $dictioline ) )  $data['dictioline'] = $dictioline ;
		
		?>
<div id="xili-dictionary-settings" class="wrap columns-2" style="min-width:850px">
			<?php screen_icon('tools'); ?>
	<h2><?php _e('Dictionary','xili-dictionary') ?></h2>
			<?php if (0!= $msg ) { // 1.2.2 ?>
	<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php }
			 
			global $wp_version;
		if ( version_compare($wp_version, '3.3.9', '<') ) {
			$poststuff_class = 'class="metabox-holder has-right-sidebar"';
			$postbody_class = "";
			$postleft_id = "";
			$postright_id = "side-info-column";
			$postleft_class = "";
			$postright_class = "inner-sidebar";
		} else { // 3.4
			$poststuff_class = "";
			$postbody_class = 'class="metabox-holder columns-2"';
			$postleft_id = 'id="postbox-container-2"';
			$postright_id = "postbox-container-1";
			$postleft_class = 'class="postbox-container"';
			$postright_class = "postbox-container";
		}
			?>
	<form name="add" id="add" method="post" action="<?php echo $this->xd_settings_page; ?>">
		<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-dictionary-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); 
				/* 0.9.6 add has-right-sidebar for next wp 2.8*/ ?>
		<div id="poststuff"  <?php echo $poststuff_class; ?> >
			
			<div id="post-body" <?php echo $postbody_class; ?> >
				<div id="<?php echo $postright_id; ?>" class="<?php echo $postright_class; ?>">
						<?php do_meta_boxes($this->thehook, 'side', $data); ?>
				</div>
				<div id="post-body-content" >
					<div <?php echo $postleft_id; ?> <?php echo $postleft_class; ?> style="min-width:360px">
	   					<?php do_meta_boxes($this->thehook, 'normal', $data); ?>
					</div>
					<h4><a href="http://dev.xiligroup.com/xili-dictionary" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/xilidico-logo-32.png', __FILE__ ) ; ?>" alt="xili-dictionary logo"/>  xili-dictionary</a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-12 - v. <?php echo XILIDICTIONARY_VER; ?></h4>
				</div>
			</div>
			<br class="clear" />
		</div>
				<?php wp_nonce_field('xilidicoptions'); ?>
	</form>
</div>
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
						"sLengthMenu": "<?php _e('Show _MENU_ entries','xili-dictionary') ?>",
						"sSearch": "<?php _e('Filter terms:','xili-dictionary') ?>"

					},	
					"aaSorting": [[1,'asc']],
					"aoColumns": [ 
						{ "bSearchable": false, "sWidth" : "30px" },
						{ "sWidth" : "60%" },
						{ "bSortable": false, "bSearchable": false  },
						{ "bSortable": false, "bSearchable": false,  "sWidth" : "105px" },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "70px" } ]
				} );
				
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->thehook; ?>');
				// for text_area list
				});
			//]]>
		</script> 
		<?php	//end settings div 
		}

		
	/** 
	 * create an array of mo content of theme (maintained by super-admin)	
	 *
	 * @since 1.1.0
	 */
	 function get_pomo_from_theme() {
	 	$theme_mos = array();
	 	if ( defined ('TAXONAME') ) {
	 		$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
	 		foreach ($listlanguages as $reflanguage) {
	     		$res = $this->pomo_import_MO ($reflanguage->name);
	     		if (false !== $res) $theme_mos[$reflanguage->slug] = $res->entries;
	 		}
	 	}
	 	return $theme_mos;	
	 }	
	 
	 /** 
	 * create an array of mo content of theme (maintained by admin of current site)
	 * currently contains the msgid which are not in theme mo
	 *
	 * @since 1.2.0
	 */
	 function get_pomo_from_site() {
	 	$theme_mos = array();
	 	if ( defined ('TAXONAME') ) {
	 		$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
	 		foreach ($listlanguages as $reflanguage) {
	     		$res = $this->import_mo_file_wpmu ($reflanguage->name, false); // of current site
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
 		//error_log ( $this->subselect );
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
 					error_log ('-only-');
 				} else {
 					// msgid + language
 					$curlang = $this->subselect;
 					$special_query = 'idlang' ;
 				}		
 		}	
 		if ( $special_query ==  'idlang' ) {
 			$listdictiolines = $this->get_cpt_msgids( $curlang ) ;
 		} elseif ( $special_query ==  'strlang' ) {
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
 		$this->theme_mos = $this->get_pomo_from_theme();
 		if ( is_multisite() ) $this->file_site_mos = $this->get_pomo_from_site(); // since 1.2.0 - mo of site
 		//print_r( $this->file_site_mos );
 		foreach ( $listdictiolines as $dictioline ) {
			
			$class = (( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";
 			
 			$type  = get_post_meta ( $dictioline->ID, $this->msgtype_meta, true);
 			$context  = get_post_meta ( $dictioline->ID, $this->ctxt_meta, true);
 			
 			$res = $this->is_saved_cpt_in_theme( $dictioline->post_content, $type, $context );
 			$save_state = $res[0]; // improve for str and multisite
 			
 			if ( is_multisite() ) $save_state .= '<br /> locally:'.$res[1];
 			
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
	 * test if line is in entries
	 * @since 2.4.0
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
					if ( false !== strpos ( $type, 'msgstr_'  ) ) {
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
	
	/**
	 * Detect if cpt are saved in theme's languages folder
	 * @since 2.0
	 * 
	 */
	function is_saved_cpt_in_theme( $msg, $type, $context = "" ) {
		$thelist = array();
		$thelistsite = array();
		$outputsite = "";
		$output = "";
		if ( defined ('TAXONAME') ) {
			$listlanguages = $this->get_terms_of_groups_lite ( $this->langs_group_id, TAXOLANGSGROUP,TAXONAME, 'ASC' ); //get_terms(TAXONAME, array('hide_empty' => false));
			
		 	foreach ($listlanguages as $reflanguage) {
		 		if ( isset($this->theme_mos[$reflanguage->slug]) ) { 
		 			if ( $this->is_intheme_mos ( $msg, $type, $this->theme_mos[$reflanguage->slug], $context ) )
		 				$thelist[] = $reflanguage->name.".mo";		 							 			
		 		} else {
		 			//$thelist[] = $reflanguage->name."?"; 
		 		}
		 		
		 		if ( is_multisite() ) {
		 			if ( isset($this->file_site_mos[$reflanguage->slug]) ) { 
		 				if ( $this->is_intheme_mos ( $msg, $type, $this->file_site_mos[$reflanguage->slug], $context ) )
		 					$thelistsite[] = $reflanguage->name.".mo";		 							 			
		 			} else {
		 				//$thelistsite[] = $reflanguage->name."?"; 
		 			}
		 		}
		 		
		 	}
		 	
			$output = ($thelist == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in theme's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in theme's files: ","xili-dictionary").'" >'. implode(', ',$thelist).'</small></small>';
			
			if ( is_multisite() ) {
				
				$outputsite = ($thelistsite == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in site's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in site's files: ","xili-dictionary").'" >'. implode(', ',$thelistsite).'</small></small>';
				
			}
			
			return array ( $output, $outputsite ) ;
		}
	}
	
	
	/**
	 * Import PO file in class PO 
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 * @updated 1.05 - import .pot if domain name - fixed 1.3.1
	 */
	function pomo_import_PO ($lang = "") {
		$po = new PO();
		$t = "";
		if ( function_exists('the_theme_domain') ) {
				$t = ($lang == the_theme_domain()) ? 't': ''; /* from UI to select .pot */
		} else {
				if ( function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 and WP 3.0
					$theme_name = get_option("stylesheet").' '.__('child of','xili-dictionary').' '.get_option("template"); 
				} else {
					$theme_name = get_option("template"); 
				}
				$t = ( $lang == $theme_name ) ? 't': ''; /* from UI to select .pot */
		}
		
		$pofile = $this->get_template_directory.$this->langfolder.$lang.'.po'.$t;
		if (file_exists($pofile)) { 
			if ( !$po->import_from_file( $pofile ) ) {
				return false;
			} else { 
				return $po;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Import MO file in class PO 
	 *
	 *
	 * @since 1.0.2 - only WP >= 2.8.4
	 * @updated 1.0.5 - for wpmu
	 * @param lang
	 * @param $mofile since 1.0.5
	 */
	function pomo_import_MO ($lang = "", $mofile = "") {
		$mo = new MO();
		if ('' == $mofile)
			$mofile = $this->get_template_directory.$this->langfolder.$lang.'.mo';
		if (file_exists($mofile)) {
			if ( !$mo->import_from_file( $mofile ) ) {
				return false;
			} else { 
				//error_log (' icci'.$mofile);
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
	function import_mo_file_wpmu ($lang = "", $istheme = true){
	  if ($istheme == true) {
	  	return $this->pomo_import_MO ($lang);
	  } else {
	  		global $wpdb;
    			$thesite_ID = $wpdb->blogid; 
    			if (($uploads = wp_upload_dir()) && false === $uploads['error'] ) {
					//if ($thesite_ID > 1) {
						$mofile = $uploads['basedir']."/languages/".$lang.'.mo'; //normally inside theme's folder if root wpmu
						
						return $this->pomo_import_MO ($lang, $mofile);
					//} else {
						//return false; // normally inside theme's folder if root wpmu
					//}
    			} else {
    				return false;
    			}
	  }
	}
	
	
	/**
	 * convert twinlines (msgid - msgstr) to MOs in wpmu
	 * @since 1.0.4
	 * @updated 2.0
	 * @params as from_twin_to_POMO and $superadmin 
	 */	
	function from_cpt_to_POMO_wpmu ($curlang, $obj='mo', $superadmin = false)	{
	    // the table array
	    $table_mo = $this->from_cpt_to_POMO($curlang); 
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
	   	if (false  === $superadmin) {
	   		// special for superadmin who don't need diff.
			// the pomo array available in theme's folder 
	   		$theme_mo = $this->import_mo_file_wpmu ( $curlang, true );
	   	  	if ( false !== $theme_mo ) {
	   	  		// without keys available in theme' mo
	   			$site_mo->entries =  array_diff_key ( $table_mo->entries, $theme_mo->entries  ); // those differents ex. categories
	   			// those with same keys but translations[0] diff
	   			$diff_mo_trans = array_uintersect_assoc ( $table_mo->entries, $theme_mo->entries, array(&$this,'test_translations')  ) ;
	   			
	   			$site_mo->entries += $diff_mo_trans ;
	   			//print_r ( array_keys ( $diff_mo_trans ));
	   			
	   		}
	   	}
	   	return $site_mo;
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
	 */
	function get_cpt_msgids( $curlang, $pomo = "mo" ) {
		global $wpdb;
		
		if ( $pomo == "mo" ) {
			$posts_query = $wpdb->prepare("SELECT $wpdb->posts.* FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) INNER JOIN $wpdb->postmeta as mt1 ON ($wpdb->posts.ID = mt1.post_id) INNER JOIN $wpdb->postmeta as mt2 ON ($wpdb->posts.ID = mt2.post_id) INNER JOIN $wpdb->postmeta as mt3 ON ($wpdb->posts.ID = mt3.post_id)  WHERE post_status = %s AND post_type = %s AND $wpdb->postmeta.meta_key='{$this->msgtype_meta}' AND mt1.meta_key='{$this->msgtype_meta}' AND mt1.meta_value = %s AND mt2.meta_key='{$this->msglang_meta}' AND mt3.meta_key='{$this->msglang_meta}' AND mt3.meta_value LIKE %s ", 'publish', XDMSG ,'msgid', '%'.$curlang.'%' );
		
		return $wpdb->get_results($posts_query);
		
		} else {
			// to have also empty translation
			$meta_key_val = $this->msgtype_meta; 
 			$meta_value_val = 'msgid';
			return get_posts( array(
				'numberposts' => -1, 'offset' => 0,
				'category' => 0, 'orderby' => 'ID',
				'order' => 'ASC', 'include' => array(),
				'exclude' => array(), 'meta_key' => $meta_key_val,
				'meta_value' =>$meta_value_val, 'post_type' => XDMSG,
				'suppress_filters' => true
			) );
		}
	}
	
	/**
	 * return msgstr object (array translation)
	 * @since 2.0
	 */
	function get_cpt_msgstr( $cur_msgid_ID, $curlang, $plural = false ) {
		$res = get_post_meta ( $cur_msgid_ID, $this->msglang_meta, false );
		$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
			if ( $plural ) {
				
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
				if ( isset ( $thelangs['msgstrlangs'][$curlang]['msgstr'] ) ) {
					$cur_msgstr_ID = $thelangs['msgstrlangs'][$curlang]['msgstr'];
				// get_content
				//echo ' - '.$cur_msgstr_ID;
 					return get_post( $cur_msgstr_ID  );
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
	 */	
	function from_cpt_to_POMO ( $curlang, $obj='mo' ) {
		global $user_identity,$user_url,$user_email;
		if ($obj == 'mo') {
			$mo = new MO(); /* par default */
		} else {
			$mo = new PO();
		}
		/* header */
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
		
		$mo->set_headers($mo->make_headers($translation));
		/* entries */
		
		$list_msgids = $this->get_cpt_msgids( $curlang, $obj ); // msgtype = msgid && $curlang in 
		
		//print_r( $list_msgids );
		
		foreach ( $list_msgids as $cur_msgid ) { 
			
			if ( $cur_msgid->post_content == '++' ) continue; // no empty msgid
				
			$getctxt = get_post_meta( $cur_msgid->ID , $this->ctxt_meta, true ) ;
			$cur_msgid->ctxt = ( $getctxt == "" ) ? false : $getctxt;
					
			$cur_msgid->plural = false ;
			$res = get_post_meta ( $cur_msgid->ID, $this->msgchild_meta, false );
 			$thechilds =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : false;
 			//error_log( serialize ( $thechilds ) );
 			if ( $thechilds ) {
 				
 				
 				if ( isset ( $thechilds['msgid']['plural'] ) ) {
 					$cur_msgid->plural = true ;
 					$plural_ID = $thechilds['msgid']['plural'];
 				
 					$post_child_msgid = get_post( $plural_ID ); 
 					$cur_msgid->plural_post_content = $post_child_msgid -> post_content ;
 				}
 			}
					
			//$res = get_post_meta ( $cur_msgid->ID, $this->msglang_meta, false );
						
				/* select child in curlang */
			$list_msgstr = $this->get_cpt_msgstr( $cur_msgid->ID, $curlang, $cur_msgid->plural ); // array of objects if plural
			///if ( false === $list_msgstr ) error_log ( '----'.$cur_msgid->ID );		 
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
						if ( false === $cur_msgid->ctxt ) {  // PLURAL
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
					// * 	- translator_comments (string) -- comments left by translators
	 				// * 	- extracted_comments (string) -- comments left by developers
	 				// * 	- references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
					// * 	- flags (array) -- flags like php-format
					
					$comment_array = array(); // $list_msgstr because in msgstr (20120318)
					
					$translator_comments = get_post_meta ( $list_msgstr->ID, $this->msg_translator_comments, true );
					if ( $translator_comments != '' )  $comment_array['translator_comments'] = $translator_comments;
					$extracted_comments = get_post_meta ( $cur_msgid->ID, $this->msg_extracted_comments, true );
					if ( $extracted_comments != '' )  $comment_array['extracted_comments'] = $extracted_comments;
					if ( $cur_msgid->post_excerpt != '' ) {
						$references = explode ('#: ', $cur_msgid->post_excerpt );
						$comment_array['references'] = $references;
					}
					$flags = get_post_meta ( $cur_msgid->ID, 'flags', true );
					if ( $flags != '' )  {
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
					$entry = & new Translation_Entry( array_merge ( $entry_array, $comment_array ) );
					
					$mo->add_entry($entry); 
					$noentry = false;
				}
			}
			/* to create po with empty translations */
			if ($obj == 'po' && $noentry == true) {
				$entry = & new Translation_Entry(array('singular'=>$cur_msgid->post_content,'translations'=> ""));
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
	 * @updated 1.0.5 - wpmu 
	 */	
	function Save_MO_to_file ($curlang , $mo, $createfile = "" )	{
		$filename = ( strlen ($curlang) == 5 ) ? substr($curlang,0,3).strtoupper(substr($curlang,-2)) : $curlang;
		$filename .= '.mo';
		if ("" == $createfile)
			$createfile = $this->get_template_directory.$this->langfolder.$filename;
		//echo $createfile;	
		if (false === $mo->export_to_file($createfile)) return false;
	}
	
	/**
	 * Save PO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 */	
	function Save_PO_to_file ($curlang , $po )	{
		$filename = ( strlen ($curlang) == 5 ) ? substr($curlang,0,3).strtoupper(substr($curlang,-2)) : $curlang;
		$filename .= '.po';
		$createfile = $this->get_template_directory.$this->langfolder.$filename;
		if (false === $po->export_to_file($createfile)) return false;
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
		$this->importing_mode = true ;
		$nbname = array ( 0, 0 ); // to import, imported
		$terms_to_import = array();
		$temp = array ();
		$temp['msgid'] = get_bloginfo( 'blogname', 'display' );
		$temp['extracted_comments'] = 'bloginfo - blogname';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = get_bloginfo( 'description', 'display' );	
		$temp['extracted_comments'] = 'bloginfo - description';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = addslashes ( get_option('time_format') );
		$temp['extracted_comments'] = 'bloginfo - time_format';
		$terms_to_import[] = $temp ;
		$temp['msgid'] = addslashes ( get_option('date_format') );
		$temp['extracted_comments'] = 'bloginfo - date_format';
		$terms_to_import[] = $temp ;
		$nbname[0] += 4;
		if ( class_exists ('xili_language') ) {
			global $xili_language;
			foreach ( $xili_language->comment_form_labels as $key => $label) {
				$temp['msgid'] =  $label ;
				$temp['extracted_comments'] = 'comment_form_labels '.$key;
				$terms_to_import[] = $temp ;
			}
			$nbname[0] += count( $xili_language->comment_form_labels );
		
			if ( XILILANGUAGE_VER > '2.3.9' ) { // msgid and msgstr
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
				if ( 'en_US' == get_locale() ) {
					foreach ( $wp_locale_array_trans as $key => $value ) {
						$temp['msgid'] =  $key ;
						$temp['extracted_comments'] = 'wp_locale '.$key;
						$terms_to_import[] = $temp ;
					}
				}
			}
		}
		
		foreach ( $terms_to_import as $term )  {
			
			$result = $this->msgid_exists ( $term['msgid'] ) ;
			
			$t_entry = array();
			$t_entry['extracted_comments'] = $term['extracted_comments'] ;
			$entry = (object) $t_entry ;
			
			if ( $result === false ) {
				// create the msgid
				
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $term['msgid'], null, 'msgid', 0, $entry ) ;
				$nbname[1]++;
			} else {
				$msgid_post_ID = $result[0];
				// add comment in existing ?
			}
			$nbname[0]++;
		}
		
		$curlang = get_locale() ; // admin language of config - import id and str
		if ( class_exists ('xili_language') && XILILANGUAGE_VER > '2.3.9' && 'en_US' != $curlang ) {
			
			foreach ( $wp_locale_array_trans as $key => $value ) {
				
				$t_entry = array();
				$t_entry['extracted_comments'] = 'wp_locale '.$key ;
				$entry = (object) $t_entry ;
				
				$result = $this->msgid_exists ( $key ) ;
				
				if ( $result === false ) {
					// create the msgid
					
					$msgid_post_ID = $this->insert_one_cpt_and_meta( $key, null, 'msgid', 0, $entry ) ;
					$nbname[1]++;
				} else {
					$msgid_post_ID = $result[0];
					// add comment
				}
				
				$result = $this->msgstr_exists ( $value, $msgid_post_ID, $curlang ) ;
				if ( $result === false ) {
					$msgstr_post_ID = $this->insert_one_cpt_and_meta( $value, null, 'msgstr', 0, $entry );
					$nbname[1]++;
					wp_set_object_terms( $msgstr_post_ID, $curlang, TAXONAME );
				} else {
					$msgstr_post_ID = $result[0];
				}
				
				// create link according lang
				
				$res = get_post_meta ( $msgid_post_ID, $this->msglang_meta, false );
				$thelangs =  ( is_array ( $res ) &&  array() != $res ) ? $res[0]  : array();
				$thelangs['msgstrlangs'][$curlang]['msgstr'] = $msgstr_post_ID;
				update_post_meta ( $msgid_post_ID, $this->msglang_meta, $thelangs );
				update_post_meta ( $msgstr_post_ID, $this->msgidlang_meta, $msgid_post_ID );
						
			}
			
			$nbname[0] += count( $wp_locale_array_trans ) ; 	
		}
		
		$this->importing_mode = false ;
		return $nbname;
		
	}
	
	/** 
	 * taxonomy's terms in array (name - description)
	 * by default taxonomy
	 *
	 */
	function xili_read_catsterms_cpt( $taxonomy = 'category' ){
		$this->importing_mode = true ;
		$nbnames = array ( 0, 0 ); // term, description
		$listcategories = get_terms( $taxonomy, array('hide_empty' => false));
		foreach ($listcategories as $category) {
			
			$result = $this->msgid_exists ( $category->name ) ;
			
			$t_entry = array();
			$t_entry['extracted_comments'] = sprintf ( 'from %s with slug %s', $taxonomy, $category->slug ) ;
			$entry = (object) $t_entry ;
			
			if ( $result === false ) {
				// create the msgid 
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $category->name, null, 'msgid', 0, $entry ) ;
				$nbnames[0]++;
			} else {
				$msgid_post_ID = $result[0];
				// add comment in existing ?
			}
			
			
			$result = $this->msgid_exists ( $category->description ) ;
			
			$t_entry = array();
			$t_entry['extracted_comments'] = sprintf ( 'from %s with slug %s', $taxonomy, $category->slug ) ;
			$entry = (object) $t_entry ;
			
			if ( $result === false ) {
				// create the msgid
				$msgid_post_ID = $this->insert_one_cpt_and_meta( $category->description, null, 'msgid', 0, $entry ) ;
				$nbnames[1]++;
			} else {
				$msgid_post_ID = $result[0];
				// add comment in existing ?
			}
			
		}
		
		$this->importing_mode = false ;
		return $nbnames;
	}
		
	/**
	 * Scan terms in themes files
	 *
	 */
	function scan_import_theme_terms( $callback, $display ) {
		$path = $this->get_template_directory ;
		$themefiles = array();
		 
		$dir_handle = @opendir($path) or die("Unable to open $path"); 
	 
		while ($file = readdir($dir_handle)) { 
	
			if (substr($file,0,1) == "_" || substr($file,0,1) == "." || substr($file,-4) != ".php") 
					continue; 
			 
			$themefiles[] = $file;
		} 
	
		 
		closedir($dir_handle); 
		 
		$resultterms = array();
		foreach ($themefiles as $themefile) {
		
			if( ! is_file( $path.'/'.$themefile) ) 
				{ 
	    			$dualtexts = __('error'); 
				} elseif ($themefile != 'functions.php'){  
					$lines = @file( $path.'/'.$themefile); 
		 			$t=0;
					foreach ($lines as $line) { 
						$i = preg_match_all("/_[_e]\('(.*)', ?'/Ui", $line, $matches,PREG_PATTERN_ORDER);
	 					if ($i > 0) { 
							$resultterms = array_merge ($resultterms, $matches[1]);
							$t += $i; 
						}
			 		}
					if ($display >= 1) 
						call_user_func($callback, $themefile, $t);
				} 
		 }
		if ($display == 2) 
			call_user_func($callback, $themefile, $t, $resultterms);
			
		return $resultterms;
	}
	function build_scanned_files ($themefile, $t, $resultterms = array()) {
		if ($resultterms == array()) {
			$this->tempoutput .= "- ".$themefile." (".$t.") ";
		} else {
			$this->tempoutput .= "<br /><strong>".__('List of found terms','xili-dictionary').": </strong><br />";
			$this->tempoutput .= implode (', ',$resultterms);
		}
	}
	
	/**
	 * Recursive search of files in a path
	 * @since 1.0
	 *
	 */
	 function find_files($path, $pattern, $callback) {
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
		      $this->find_files($fullname, $pattern, $callback);
		    } else if (is_file($fullname) && preg_match($pattern, $entry)) {
		      call_user_func($callback, $path , $entry);
		    }
		  }
	}
	/**
	 * display lines of files in special sidebox
	 * @since 1.0
	 */
	function available_mo_files($path , $filename) {
  		$langfolder = str_replace($this->get_template_directory,'',$path);
  		if  ( $langfolder == "" ) $langfolder = '/';
  		$shortfilename = str_replace(".mo","",$filename );
  		$message = ( ( strlen($shortfilename)!=5 && strlen($shortfilename) != 2 ) || ( false === strpos( $shortfilename, '_' ) && strlen($shortfilename)==5 ) ) ? '<span style="color:red;">'.__('Uncommon filename','xili-dictionary').'</span>' : '';
  		echo str_replace(".mo","",$filename ). " (". $langfolder . ") " . $message . "<br />";
	}
	
	/**
	 * Contextual help
	 *
	 * @since 1.2.2
	 */
	 function add_help_text($contextual_help, $screen_id, $screen) { 
	  //print_r($screen);
	  if ( $this->page_screen_id == $screen->id ) {
	    $contextual_help =
	      '<p>' . __('Things to remember to set xili-dictionary:','xili-dictionary') . '</p>' .
	      '<ul>' .
	      '<li>' . __('Verify that the theme is localizable (like kubrick, fusion or twentyten).','xili-dictionary') . '</li>' .
	      '<li>' . __('Define the list of targeted languages.','xili-dictionary') . '</li>' .
	      '<li>' . __('Prepare a sub-folder .po and .mo files for each language (use the default delivered with the theme or add the pot of the theme and put them inside.', 'xili-dictionary') . '</li>' .
	      '<li>' . __('If you have files: import them to create a base dictionary. If not : add a term or use buttons of import and export metabox.', 'xili-dictionary') . '</li>' .
	      '</ul>' .
	      
	      '<p><strong>' . __('For more information:') . '</strong></p>' .
	      '<p>' . __('<a href="http://wiki.xiligroup.org" target="_blank">Xili Plugins Documentation and WIKI</a>', 'xili-dictionary') . '</p>' .
	      '<p>' . __('<a href="http://dev.xiligroup.com/xili-dictionary" target="_blank">Xili-dictionary Plugin Documentation</a>','xili-dictionary') . '</p>' .
	      '<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili-dictionary') . '</p>' .
	      '<p>' . __('<a href="http://forum2.dev.xiligroup.com/" target="_blank">Support Forums</a>','xili-dictionary') . '</p>' ;
	  }
	  return $contextual_help;
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
	
		return $listterms;
	}	

} /* end of class */


/**
 *  filter wp_upload_dir (/wp-includes/functions.php)
 *
 * @since 1.0.5
 */
 function xili_upload_dir() {
 	add_filter ('upload_dir','xili_change_upload_subdir');
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
function dictionary_start () {
	global $wp_version, $xili_dictionary ; // for barmenu
	if ( $wp_version >= '3.2' ) $xili_dictionary = new xili_dictionary(); /* instantiation php4 for last century servers replace by =& */ 
}
add_action( 'plugins_loaded', 'dictionary_start', 20 ); 

/* © xiligroup dev 20120402 */

?>