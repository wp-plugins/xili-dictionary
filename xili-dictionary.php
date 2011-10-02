<?php
/*
Plugin Name: xili-dictionary
Plugin URI: http://dev.xiligroup.com/xili-dictionary/
Description: ONLY for >= WP 3.0 - This plugin is a tool using wordpress's taxonomy for localized themes or multilingual themes managed by xili-language - a powerful tool to create .mo file(s) on the fly in the theme's folder and more... -
Author: dev.xiligroup - MS
Version: 1.3.6
Author URI: http://dev.xiligroup.com
License: GPLv2
*/

# beta 1.3.6 - 111001 - fixes import mo (rebuild hierarchy)
# beta 1.3.5 - 110628 - new folder organization - fixes only > 3.1
# beta 1.3.4 - 110521 - compatible with xili-language premium - fixes (for previous version < 3.0 use previous release)
# beta 1.3.3 - 101205 - now able to use ja.mo and ja.po for japanese, fixes db issues.
# beta 1.3.2 - 101203 - fixes for mode standalone w/o xili-language
# beta 1.3.1 - 101128 - add translation links for each target lang.
# beta 1.3.0 - 101107 - js in list with dataTables library
# beta 1.2.2 - 101101 - contextual help - remove filter of delete term Tracs #15264 - add help context
# beta 1.2.1 - 101030 - repairs a bug created by beta xili-language 1.8.1
# beta 1.2.0 - 101028 - compatibility with child theme and xili-language >= 1.8.1 - better folder detection
# beta 1.1.1 - 100627 - fixes issues in multisite mode (empty .mo)
# beta 1.1.0 - 100625 - list of terms with more infos in multisite mode, fixes some issues
# beta 1.1.0 - 100614 - fixes some issues, add some UI features and keep new features from xili-language 1.6.0 
# beta 1.0.6 - 100503 - fixes some issues in differential saving (theme's mo and others).
# beta 1.0.5 - 100417 - add features to save .mo of blog structure in uploads folder - compatible with xili-language 1.5.2
# beta 1.0.4 - 100410 - minor modifications for WP 3.0 and WPMU
# beta 1.0.3 - fixes some directories issues in (rare) xamp servers and in theme's terms import. Create .po with empty translations.
# beta 1.0.2 - JS and vars, create lang list, if xili-language absent, for international themes - lot of fixes
# beta 1.0.1 - add scripts for form with plural msg (id or str)
# beta 1.0.0 - use pomo libraries and classes - ONLY >= 2.8
# beta 0.9.9 - fixes existing msgid terms - better log display when importing theme's terms
# beta 0.9.8.2 - more html tags in msg str or id
# beta 0.9.8.1 - some fixes for IIS server and PHP 5.2.1
# beta 0.9.8 - WP 2.8 - fix query error
# beta 0.9.7.3 <- to - see readme.txt - from  0.9.4
# beta 0.9.3 - first published - 090131 MS


# This plugin is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This plugin is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this plugin; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

define('XILIDICTIONARY_VER','1.3.6');

include_once(ABSPATH . WPINC . '/pomo/po.php'); /* not included in wp-settings */

class xili_dictionary {
	
	var $subselect = ''; /* used to subselect by msgid or languages*/
	var $xililanguage = ''; /* neveractive isactive wasactive */
	var $xililanguagepremium = false;
	var $tempoutput = "";
	var $langfolder ='/'; /* where po or mo files */
	var $xili_settings; /* saved in options */
	var $ossep = "/"; /* for recursive file search in xamp */
	var $notwp3 = true; // to detect WP3 since alpha
	
	function xili_dictionary($langsfolder = '/') {
		global $wp_version;
		/* activated when first activation of plug */
		$this->notwp3 = (version_compare($wp_version, '3.0-alpha', '<')) ? true : false;
		register_activation_hook(__FILE__,array(&$this,'xili_dictionary_activation'));
		$this->ossep = strtoupper(substr(PHP_OS,0,3)=='WIN')?'\\':'/'; /* for rare xamp servers*/
		/* get current settings - name of taxonomy - name of query-tag */
		$this->xililanguage_state();
		$this->xili_settings = get_option('xili_dictionary_settings'); // print_r($this->xili_settings);
		if(empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$this->xili_dictionary_activation();
			$this->xili_settings = get_option('xili_dictionary_settings');			
		}
		define('DTAXONAME', $this->xili_settings['taxonomy']); 
		define('XDDICTLANGS','xl-'.DTAXONAME.'-langs');
		/** * @since 1.0 */
		define('XPLURAL','[XPLURAL]'); /* to separate singular and plural entries */
		
		
		
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
		add_action('admin_init', array(&$this,'admin_init') ); // 1.3.0
		add_action('admin_menu', array(&$this,'xili_add_dict_pages'));
		add_action('init', array(&$this, 'init_textdomain'));
		add_action('init', array(&$this, 'xili_dictionary_register_taxonomies')); /* wp 3.0 WPMU */
		add_filter('plugin_action_links',  array(&$this,'xilidict_filter_plugin_actions'), 10, 2);
		/* special to detect theme changing since 1.1.9 */
		add_action('switch_theme', array(&$this,'xd_theme_switched'));
		if (!$this->notwp3) {
			add_action( 'contextual_help', array(&$this,'add_help_text'), 10, 3 ); /* 1.2.2 */
		}
		if (function_exists('is_child_theme') && is_child_theme() ) { // 
			if ( $this->xili_settings['langs_in_root_theme'] == 'root' ) { // for future uses
				$this->get_template_directory = get_template_directory();
			} else {
				$this->get_template_directory = get_stylesheet_directory();
			}
		} else {
			$this->get_template_directory = get_template_directory();
		}
		if ( class_exists('xili_language_ms') ) $this->xililanguagepremium = true; // 1.3.4
										
	}
	/* wp 3.0 WPMU */
	function xili_dictionary_register_taxonomies () {
		/* add new taxonomy in available taxonomies here dictionary terms */
		register_taxonomy( DTAXONAME, 'post',array('hierarchical' => true, 'label'=>false, 'rewrite' => false, 'update_count_callback' => '','show_ui' => false));
		/* groups of terms by langs */
		register_taxonomy( XDDICTLANGS, 'term',array('hierarchical' => false, 'label'=>false, 'rewrite' => false, 'update_count_callback' => ''));
		
		if ( is_admin() ) {
			global $wp_roles;
			
			if ( current_user_can ('activate_plugins') ) {
				$wp_roles->add_cap ('administrator', 'xili_dictionary_set');
			}
		}
		
	}	
	
	function xili_dictionary_activation() {
		$this->xili_settings = get_option('xili_dictionary_settings');
		if(empty($this->xili_settings) || $this->xili_settings['taxonomy'] != 'dictionary') { // to fix
			$submitted_settings = array(
		    	'taxonomy'		=> 'dictionary',
		    	'langs_folder' => '',
		    	'version' 		=> '1.0'
	    	);
			update_option('xili_dictionary_settings', $submitted_settings);	
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
	
	function admin_init()
    {
        /* Register our script. */
        wp_register_script( 'datatables', plugins_url('js/jquery.dataTables.min.js', __FILE__ ) );
        wp_register_style( 'table_xdstyle', plugins_url('/css/xd_table.css', __FILE__ ) );
    }
	
	/** * add js in admin * @updated 1.0.2 */
	function xili_add_js() {
		wp_enqueue_script( 'xd-plural', plugins_url('include/plural.php?var='.XPLURAL , __FILE__ ), array('jquery'), XILIDICTIONARY_VER);
	}
	
	/** *add admin menu and associated page */
	function xili_add_dict_pages() {
		 $this->thehook = add_management_page(__('Xili Dictionary','xili-dictionary'), __('xili Dictionary','xili-dictionary'), 'import', 'dictionary_page', array(&$this,'xili_dictionary_settings'));
		  add_action('load-'.$this->thehook, array(&$this,'on_load_page'));
		  add_action( "admin_print_scripts-".$this->thehook, array(&$this,'xili_add_js'));	
		  add_action( 'admin_print_scripts-'.$this->thehook, array(&$this,'admin_enqueue_scripts') );
		  add_action( 'admin_print_styles-'.$this->thehook, array(&$this,'admin_enqueue_styles') );	 
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
			$settings_link = '<a href="tools.php?&amp;page=dictionary_page">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links); // before other links
		}
		return $links;
	}
	
	function init_textdomain() {
	/*multilingual for admin pages and menu*/
		
		load_plugin_textdomain('xili-dictionary', false, 'xili-dictionary/languages' );
		
		$this->xili_settings['langs_folder'] = 'unknown';
		if (!$this->notwp3 && class_exists('xili_language')) {
			global $xili_language ;
			$langs_folder = $xili_language->xili_settings['langs_folder']; // set by override_load_textdomain filter
			if ( $this->xili_settings['langs_folder'] != $langs_folder ) { 
		 		$this->xili_settings['langs_folder'] = $langs_folder ;
		 		update_option('xili_dictionary_settings', $this->xili_settings);
		 	}
		} else {
		if ( file_exists( $this->get_template_directory ) ) // when theme was unavailable
			$this->find_files($this->get_template_directory, '/^.*\.(mo|po|pot)$/', array(&$this,'searchpath'));
			
		if (!defined('THEME_LANGS_FOLDER') && $this->notwp3)
				define('THEME_LANGS_FOLDER',$this->xili_settings['langs_folder']); // for bkwd compatibility
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
			if (empty($xl_settings)) {
				$this->xililanguage = 'neveractive';
			} else {
				$this->xililanguage = 'wasactive';
			}			
		}	
	}
	/** * @since 1.02 */
	function fill_default_languages_list() {
		if ($this->xililanguage == 'neveractive' || $this->xililanguage == 'wasactive') {
			
			if 	(!$this->xili_settings[XDDICTLANGS]) {
				
				$default_langs_array = array( 
					'en_us' => array('en_US', 'english'),
					'fr_fr' => array('fr_FR', 'french'),
					'de_de' => array('de_DE', 'german'),
					'es_es' => array('es_ES', 'spanish'),
					'it_it' => array('it_IT', 'italian'),
					'pt_pt' => array('pt_PT', 'portuguese'),
					'ru_ru' => array('ru_RU', 'russian'),
					'zh_cn' => array('zh_CN', 'chinese'),
					'ja_ja' => array('ja_JA', 'japanese'),
					'ar_ar' => array('ar_AR', 'arabic')
				);
				/* add wp admin lang */
				if (defined ('WPLANG')) { 
					$lkey = strtolower(WPLANG);
					if (!array_key_exists($lkey, $default_langs_array)) $default_langs_array[$lkey] = array (WPLANG, WPLANG);
				}
				$this->xili_settings[XDDICTLANGS] = $default_langs_array;
				update_option('xili_dictionary_settings', $this->xili_settings);
			}
		}
	}
	
	/**
	 * private function to update grouping of terms if xili-language is active
	 *
	 *
	 *
	 */
	function update_terms_langs_grouping() {
		if ($this->xililanguage == 'isactive') {
			
			$listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false,'get'=>'all'));
			if (!empty($listdictiolines)) {
		    	foreach ($listdictiolines as $dictioline) {
		    		/* check slug before creating relationship if parent = O select msgid */
		    		if ($dictioline->parent != 0) {
		    			$extend = $this->extract_extend ( $dictioline->slug ) ; // substr($dictioline->slug,-5);
			    		$lang = get_term_by('slug',$extend,TAXONAME,OBJECT);
			    		if ( $lang ) { 
			    			$term = $lang->slug; 
			    		} else { 
			    			$term = $extend; 
			    		}
						$args = array( 'alias_of' => '', 'description' => 'Dictionary Group in '.$term, 'parent' => 0, 'slug' =>$extend);
				    	$theids = wp_update_term( $term, XDDICTLANGS, $args); // 1.8.8 instead insert
			    		wp_set_object_terms((int) $dictioline->term_id, $extend, XDDICTLANGS,false);
		    		}
		    	}
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
	 	
		<p><?php _e('xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. With this dictionary, it is possible to create and update .mo file in the current theme folder. And more...','xili-dictionary') ?></p>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo __("Theme's informations:",'xili-dictionary').' ('. $theme_name .')'; ?></legend>
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
	 * @updated 1.3.0 with datatables js (ex widefat)
	 *
	 */
	function on_normal_1_content($data) { 
		extract($data); 
		$sortparent = (($this->subselect == '') ? '' : '&amp;tagsgroup_parent_select='.$this->subselect );
		?>
			<div id="topbanner">
			</div>
			<div id="tableupdating">
			</div>
			<table class="display" id="termstable">
				<thead>
				<tr>
					<th scope="col" class="center colid"><a href="?page=dictionary_page"><?php _e('ID') ?></a></th>
			        <th scope="col" class="coltexte"><a href="?page=dictionary_page&amp;orderby=name<?php echo $sortparent; ?>"><?php _e('Text') ?></a></th>
			        
			        <th scope="col" class="colslug"><a href="?page=dictionary_page&amp;orderby=slug<?php echo $sortparent; ?>"><?php _e('Slug','xili-dictionary') ?></a></th>
			        <th scope="col" class="colgroup center"><?php _e('Group','xili-dictionary') ?></th>
			        
			        <th colspan="2"><?php _e('Action') ?></th>
				</tr>
				</thead>
				<tbody id="the-list">
			<?php $this->xili_dict_row($orderby,$tagsnamelike,$tagsnamesearch); /* the lines */
			?>
				</tbody>
			</table>
			<div id="bottombanner">
			</div>
			<?php if ($action=='edit' || $action=='delete') :?>
				<p>(<a href="?action=add&page=dictionary_page"><?php _e('Add a term','xili-dictionary') ?></a>)</p>
	   		<?php endif;	
	}	
	
	function on_normal_2_content($data) { 
		extract($data); 
		
	 	/* the create - edit - delete form */ ?>
	 	<div style="background:#f5f5fe;">
	 	<p id="add_edit"><?php _e($formhow,'xili-dictionary') ?></p>
		<?php 
		if ($action=='export' || $action=='importmo' || $action=='import' || $action=='exportpo' ) { 
			if ( function_exists('is_child_theme') && is_child_theme() ) { // 1.8.1 and WP 3.0
							$theme_name = get_option("stylesheet").' '.__('child of','xili-language').' '.get_option("template"); 
						} else {
							$theme_name = get_option("template"); 
						}
			
			?>
			
			<label for="language_file">
			<select name="language_file" ><?php
	   			$extend = WPLANG;
	   			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
				if (is_wp_error($listlanguages) || empty($listlanguages)) { 
					$langs_array = $this->xili_settings[XDDICTLANGS];
						echo '<option value="" >...</option>';	            	
	         		foreach ($langs_array as $lkey => $reflanguage) {
	         			echo '<option value="'.$reflanguage[0].'"'; 
	     				if ($extend == $reflanguage[0]) { 
	     					echo ' selected="selected"';
	     				} 
	     				echo ">".__($reflanguage[1],'xili-dictionary').'</option>';
	         		}
	     	 } else { 
	     	
	     			foreach ($listlanguages as $reflanguage) {
	     				echo '<option value="'.$reflanguage->name.'"'; 
	     				if ($extend == $reflanguage->name) { 
	     					echo ' selected="selected"';
	     				} 
	     				echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
	     			
	     			}
	     		} 
	     		if ($action=='import') { // to import .pot of current domain 1.0.5
	     			if (function_exists('the_theme_domain')) {// in new xili-language
	     				echo '<option value="'.the_theme_domain().'" >'.the_theme_domain().'.pot</option>';
	    			} else {
						
						echo '<option value="'.$theme_name.'" >'.$theme_name.'.pot</option>';
					}	
	     		}
	     		?>
	     	</select></label> <?php 
	     	if ($this->notwp3 === false) {
	     		if ( ($action=='export' || $action=='exportpo' ) && is_multisite() && is_super_admin() && $this->xililanguagepremium ) { ?>
	     		<p><?php printf (__('Verify before that you are authorized to write in languages folder in theme named: %s','xili-dictionary'), $theme_name ) ?></p>
	     		<?php }
		     	if ( $action=='export' && is_multisite() && is_super_admin() && !$this->xililanguagepremium ) { ?>
		     	<label for="only-theme">&nbsp;
		     	<?php _e('SuperAdmin: only as theme .mo','xili-dictionary') ?> <input id="only-theme" name="only-theme" type="checkbox" value="only" />
		     	</label>
	     	<?php } else { ?>
	     		<input id="only-theme" name="only-theme" type="hidden" value="only" />
	     	<?php }
	     	} ?><br />&nbsp;<br />
			<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br /></div>
		<?php
		} elseif ($action=='importbloginfos' || $action=='importcats' || $action=='erasedictionary' || $action=='importcurthemeterms') {
			?>
			
			<br />&nbsp;<br />
			<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
			<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br /></div>
		<?php
	
			
		} else {
			 /* rules for edit dictioline */
			$noedit = "" ; $noedited = "" ;
			if ($action=='edit' && $dictioline->parent == 0)   {
				$noedited = 'disabled="disabled"';
				$extend = "";
			} elseif ($action=='edit') {
			/* search default */
				$extend = $this->extract_extend ( $dictioline->slug );
			} elseif ($action=='delete' && $dictioline->parent == 0) {	
				$noedit = 'disabled="disabled"';
				$extend = "";
			} elseif ($action=='delete') {
				$noedit = 'disabled="disabled"';
				$extend = $this->extract_extend ( $dictioline->slug );
			}		
			?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
			<?php if ($action=='edit' || $action=='delete') { 
					$areacontent = $dictioline->description;
					$textareas = explode(XPLURAL,$dictioline->description);
					$firstcontent = $textareas[0]; /* also always before js splitting*/
				} else {
					$areacontent = "";
					$firstcontent = "";
				}
					?>
				<th scope="row" valign="top" align="right" width="25%"><label for="dictioline_description1">
					<?php 
					if ($action=='edit' && $dictioline->parent == 0) {
						_e('Full msgid (original)','xili-dictionary'); 
					} elseif ($action=='edit') {
						_e('Full msgstr (translation)','xili-dictionary');
					} else {
						_e('Full msg (id or str)','xili-dictionary');
					} ?> :&nbsp;</label>
					<textarea style="visibility:hidden" name="dictioline_description" id="dictioline_description" cols="12" rows="3"  disabled="disabled" ><?php echo $areacontent; ?></textarea>
					<?php if (isset($_GET['from_term_id'])) {
						$from_term_id = $_GET['from_term_id'];	 
						$parent_term = get_term( (int) $from_term_id, DTAXONAME,OBJECT, 'edit');	
						$parentmsgid = $parent_term->description ;
						//$parentmsgid1 = $this->display_singular_or_plural ($parent_term->description, true);	// the first
					?>
					<input style="font-size:80%" type="button" id="btinsert" value="<?php _e('Write original text','xili-dictionary') ?>" />
					<input type="hidden" id="originalmsgid" value="<?php echo $parentmsgid; ?>">
					
					<?php } ?>
				</th>
				<td align="left">
				
				<input type="hidden" id="termnblines" name="termnblines" value="1" />
				
				<div id="input1" style="margin-bottom:4px;" class="clonedInput">
				  <p id="areatitle1"><?php _e('Singular text','xili-dictionary'); ?></p>
				  <textarea style="visibility:visible" class="plural" name="dictioline_description1" id="dictioline_description1" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $firstcontent; ?></textarea>
						
				</div>
				<?php if ($action != 'delete') { ?>
					<div>
						<span style="display:block; float:right; width:60%"><small>&nbsp;<?php _e('Use only plural terms if theme contains _n() functions','xili-dictionary') ?></small></span>
						<input type="button" id="btnAdd" value="<?php _e('Add a plural','xili-dictionary') ?>" />
						<input type="button" id="btnDel" value="<?php _e('Delete a plural','xili-dictionary') ?>" />
						
					</div>
				<?php } ?>
				</td>
			
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top" align="right"><label for="dictioline_lang"><?php _e('Language','xili-dictionary') ?></label> :&nbsp;</th>
				<td>
	  				<select name="dictioline_lang" id="dictioline_lang"  <?php echo $noedit.$noedited;?> >
	  					<option value="" <?php if ($extend == '') { ?> selected="selected"<?php } ?>>
	  						<?php 
	  						if (isset($_GET['from_term_id'])) {
	  							_e('Choose…','xili-dictionary');
	  						} else {
	  							_e('default','xili-dictionary'); 
	  						}
	  						?></option>
	  			<?php $listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'get' => 'all')); 
					if (is_wp_error($listlanguages) || empty($listlanguages)) { 
						$langs_array = $this->xili_settings[XDDICTLANGS];
							foreach ($langs_array as $slug => $reflanguage) {
	         				echo '<option value="'.$reflanguage[0].'"';
	         				if ( isset($_GET['tarlang']) ) {
			     				if ( $_GET['tarlang'] == $slug ) echo ' selected="selected"';
			     			} elseif ($extend == strtolower($reflanguage[0])) { 
	     						echo ' selected="selected"';
	     					} 
	     					echo ">".__($reflanguage[1],'xili-dictionary').'</option>';
	         			}
	         		
	         		 } else {
			     		foreach ($listlanguages as $reflanguage) {
			     			echo '<option value="'.$reflanguage->slug.'"'; 
			     			
			     			if ( isset($_GET['tarlang']) ) {
			     				if ( $_GET['tarlang'] == $reflanguage->slug ) echo ' selected="selected"';
			     			} elseif ( $extend == $reflanguage->slug ) { 
			     				echo ' selected="selected"';
			     			} 
			     			echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
			     			
			     		}
     	}
   	 ?>    
                	</select>
                	<?php if (isset($_GET['from_term_id'])) {
                	echo '<small style="color:red;" > <-'.__("Don't forget to verify the language of translation !","xili-language").'</small>';
                	} ?>
	  			</td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="dictioline_slug"><?php _e('Term slug','xili-dictionary') ?></label> :&nbsp;</th>
				<td><input name="dictioline_slug" id="dictioline_slug" type="text" readonly="readonly" value="<?php echo attribute_escape($dictioline->slug); ?>" size="40" <?php echo $noedit; ?> /></td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top" align="right"><label for="dictioline_parent"><?php _e('Relationship (msgid)','xili-dictionary') ?></label> :&nbsp;</th>
				<td>
					<fieldset style="margin:2px; padding:10px 5px; border:1px solid #ccc;"><legend><?php
					 if ($action=='edit' || $action=='delete') {
					 	if ($dictioline->parent == 0) {
					 		_e('Original','xili-dictionary');
					 	} else {
					 		_e('Translation','xili-dictionary');
					 	}
					 } else {
					 	if (isset($_GET['from_term_id'])) {
					 		_e('Original','xili-dictionary');
					 	} else {
					 		_e('Original or translation','xili-dictionary'); 
					 	}
					 } 
					 ?></legend>
			  			<?php $this->xili_select_row( $term_id, $dictioline ); /* choice of parent line */?>
			  		</fielset>
             	</td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="alias_of"><?php _e('Alias of','xili-dictionary') ?></label> :&nbsp;</th>
				<td><input name="alias_of" id="alias_of" type="text" value="" size="40" <?php echo $noedit; ?> />&nbsp;<small>xili-dictionary ID (<?php echo $term_id; ?>)</small></td>
			</tr>
		<?php if ($action=='edit') { ?>
			<tr>
				<th scope="row" valign="top" align="right" style="color:#eee;"><label for="dictioline_name"><?php _e('Text') ?> :&nbsp;</label></th>
				<td style="color:#eee;"><?php echo attribute_escape($dictioline->name); ?></td>
			</tr>
		<?php } ?>
			<tr>
				<th><input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /></th>
				<td> 
					<p class="submit"><input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" /></p>
				</td>
			</tr>
		</table></div>
		<?php 
			if ($action=='edit' || $action=='delete') { ?>
				<input type="hidden" name="dictioline_term_id" value="<?php echo $dictioline->term_id ?>" />
			<?php } 
			if ($action=='edit') { ?>
				<input type="hidden" name="dictioline_name" id="dictioline_name"  value="<?php echo attribute_escape($dictioline->name); ?>" />
			<?php }
		}
	} 
	/** * @updated 1.0.2 * manage files */
	function on_normal_3_content($data) { ?>
		<h4 id="manage_file"><?php _e('The files','xili-dictionary') ;?></h4>
	   	<?php 
	   			
	   	$linkstyle = "text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;" ;
	   	$linkstyle3 = "text-decoration:none; text-align:center; display:inline-block; width:16%; margin:0px 1px 1px 10px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;  border:1px #ccc solid;" ;
	   	$linkstyle1 = $linkstyle." border:1px #33f solid;";
	   	$linkstyle2 = $linkstyle." border:1px #ccc solid;";
	   	?>
	   		<a style="<?php echo $linkstyle1 ; ?>" href="?action=export&amp;page=dictionary_page" title="<?php _e('Create or Update mo file in current theme folder','xili-dictionary') ?>"><?php _e('Export mo file','xili-dictionary') ?></a>
	   	  	&nbsp;<br /><?php _e('Import po/mo file','xili-dictionary') ?>:<a style="<?php echo $linkstyle3 ; ?>" href="?action=import&amp;page=dictionary_page" title="<?php _e('Import an existing .po file from current theme folder','xili-dictionary') ?>">PO</a>
	   	  	<a style="<?php echo $linkstyle3 ; ?>" href="?action=importmo&amp;page=dictionary_page" title="<?php _e('Import an existing .mo file from current theme folder','xili-dictionary') ?>">MO</a><br />
	   	  	&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="?action=exportpo&amp;page=dictionary_page" title="<?php _e('Create or Update po file in current theme folder','xili-dictionary') ?>"><?php _e('Export po file','xili-dictionary') ?></a>
	   	<h4 id="manage_categories"><?php _e('The categories','xili-dictionary') ;?></h4>
	   		<a style="<?php echo $linkstyle2 ; ?>" href="?action=importcats&amp;page=dictionary_page" title="<?php _e('Import name and description of categories','xili-dictionary') ?>"><?php _e('Import terms of categories','xili-dictionary') ?></a>
	   	<h4 id="manage_categories"><?php _e('The website infos (title, sub-title and more…)','xili-dictionary') ;?></h4>
	   		<a style="<?php echo $linkstyle2 ; ?>" href="?action=importbloginfos&amp;page=dictionary_page" title="<?php _e('Import infos of web site and more','xili-dictionary') ?>"><?php _e("Import terms of website's infos",'xili-dictionary') ?></a>	
	   	<h4 id="manage_dictionary"><?php _e('Dictionary','xili-dictionary') ;?></h4>
   		<a style="<?php echo $linkstyle2 ; ?>" href="?action=erasedictionary&amp;page=dictionary_page" title="<?php _e('Erase all terms of dictionary ! (but not .mo or .po files)','xili-dictionary') ?>"><?php _e('Erase all terms','xili-dictionary') ?></a>
   		&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="?action=importcurthemeterms&amp;page=dictionary_page" title="<?php _e('Import all terms from current theme files - alpha test -','xili-dictionary') ?>"><?php _e('Import all terms from current theme','xili-dictionary') ?></a>	
	
	<?php
	}
	/** * @since 090423 - */
	function on_normal_4_content($data=array()) {
		extract($data);
		?>
			<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Sub list of terms','xili-dictionary'); ?></legend>
			<label for="tagsnamelike"><?php _e('Starting with:','xili-dictionary') ?></label> 
			<input name="tagsnamelike" id="tagsnamelike" type="text" value="<?php echo $tagsnamelike; ?>" /><br />
			<label for="tagsnamesearch"><?php _e('Containing:','xili-dictionary') ?></label> 
			<input name="tagsnamesearch" id="tagsnamesearch" type="text" value="<?php echo $tagsnamesearch; ?>" />
			<p class="submit"><input type="submit" id="tagssublist" name="tagssublist" value="<?php _e('Sub select…','xili-dictionary'); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" id="notagssublist" name="notagssublist" value="<?php _e('No select…','xili-dictionary'); ?>" /></p>
			</fieldset>
			
			<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Selection by language','xili-dictionary'); ?></legend>
			<select name="tagsgroup_parent_select" id="tagsgroup_parent_select" style="width:100%;">
		  				<option value="no_select" ><?php _e('No sub-selection','xili-dictionary'); ?></option>
		  				<?php $checked = ($this->subselect == "msgid") ? 'selected="selected"' :'' ; 
		  				echo '<option value="msgid" '.$checked.' >'.__('Only MsgID (en_US)','xili-dictionary').'</option>'; 		  	
		  				echo $this->build_grouplist();		  				
		  				?>
		  	</select>			
		  	<br /> <p class="submit"><input type="submit" id="subselection" name="subselection" value="<?php _e('Sub select…','xili-dictionary'); ?>" /></p>
			</fieldset>
		<?php
	}
	/** * @since 1.0.2 * only if xili-language plugin is absent */ 
	function on_normal_5_content($data=array()) {
		extract($data);
		?>
		<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Language to delete','xili-dictionary'); ?></legend>
			<p><?php _e('Only the languages list is here modified (but not the dictionary\'s contents)','xili-dictionary'); ?></p>
			<select name="langs_list" id="langs_list" style="width:100%;">
		  				<option value="no_select" ><?php _e('Select...','xili-dictionary'); ?></option>
		  				<?php echo $this->build_grouplist('');
		  				?>
		  	</select>
		  	<br />
		  	<p class="submit"><input type="submit" id="lang_delete" name="lang_delete" value="<?php _e('Delete a language','xili-dictionary'); ?>" /></p></fieldset><br />
		  	<fieldset style="margin:2px; padding:3px; border:1px solid #ccc;"><legend><?php _e('Language to add','xili-dictionary'); ?></legend>
		  	<label for="lang_ISO"><?php _e('ISO (xx_YY)','xili-dictionary') ?></label>:&nbsp; 
			<input name="lang_ISO" id="lang_ISO" type="text" value="" size="5"/><br />
			<label for="lang_name"><?php _e('Name (eng.)','xili-dictionary') ?></label>:&nbsp; 
			<input name="lang_name" id="lang_name" type="text" value="" size="20" />
			<br />
			<p class="submit"><input type="submit" id="lang_add" name="lang_add" value="<?php _e('Add a language','xili-dictionary'); ?>" /></p>
		</fieldset>
	<?php }
	/**
	 * build the list of group of languages for dictionary
	 *
	 * @updated 1.0.2
	 *
	 */
	 function build_grouplist ($left_line = 'Only:') {
	 	if ($this->xililanguage == 'isactive') {
	 		$listdictlanguages = get_terms(XDDICTLANGS, array('hide_empty' => false));
	 		foreach($listdictlanguages as $dictlanguage) {
	 			$checked = ($this->subselect == $dictlanguage->slug) ? 'selected="selected"' :'' ; 
		  		$optionlist .= '<option value="'.$dictlanguage->slug.'" '.$checked.' >'.__('Only:','xili-dictionary').' '.$dictlanguage->name.'</option>'; 
	 		}
	 	} else {
	 		$langs_array = $this->xili_settings[XDDICTLANGS];
			foreach ($langs_array as $lkey => $dictlanguage) {
	         	$checked = ($this->subselect == $lkey) ? 'selected="selected"' :'' ; 
		  		$optionlist .= '<option value="'.$lkey.'" '.$checked.' >'.__($left_line,'xili-dictionary').' '.$dictlanguage[0].'</option>'; 
			}
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
	 	if ( !$term_info = term_exists($lang_slug, $taxo_dictgroup) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int($term_info) )
				continue;
			$args = array( 'alias_of' => '', 'description' => 'Dictionary Group in '.$lang_slug );	
			$term_info = wp_insert_term($lang_slug, $taxo_dictgroup, $args); //print_r ($term_info);
		}
	 	
	 	
	 	wp_set_object_terms( $term_id, $lang_slug, $taxo_dictgroup, $bool );
	 
	 }
	 /**
	  * @since 0.9.9
	  * 
	  *
	  */
	 function test_and_create_slug($description, $slug, $lang='') {
	 	$slug = sanitize_title(str_replace(' ','_',$slug));
	 	//echo $slug;
	 	$found = is_term($slug,DTAXONAME);
	 	if ($found){
	 		/* compare description*/
	 		$found_term = get_term( (int) $found['term_id'], DTAXONAME );
	 		if ($found_term->description == $description) {
	 			return $slug;
	 		} else {
	 			if ( '' == $lang) {
	 				//echo 'nul';
	 				$slug = $slug.'z';
	 			   	return $this->test_and_create_slug($description, $slug, $lang);
	 			} else {
	 				//echo $slug;
	 				$theslug = str_replace('_'.$lang,'',$slug);
	 				$theslug = $theslug.'z'.'_'.$lang; /* risk the parent term cannot be created after */
	 				return $this->test_and_create_slug($description, $theslug, $lang);
	 			}
	 		}
	 	} else {
	 		//echo 'pas nul';
	 		return $slug;
	 	}
	 }
	/** * @since 1.0.0 */
	function xili_dic_update_term($term, $taxonomy, $args) {
		remove_filter('pre_term_description', 'wp_filter_kses'); /* 0.9.8.2 to allow more tag in msg str or id */
		$rterm_id = wp_update_term( $term, $taxonomy, $args);
		add_filter('pre_term_description', 'wp_filter_kses');
		return $rterm_id;
	}
	function xili_dic_insert_term($termname, $taxonomy, $args) {
		remove_filter('pre_term_description', 'wp_filter_kses'); /* 0.9.8.2 to allow more tag in msg str or id */
		$rterm_id = wp_insert_term( $termname, $taxonomy, $args);
		add_filter('pre_term_description', 'wp_filter_kses');
		return $rterm_id;
	}
		
	/* Dashboard - Manage - Dictionary */
	function xili_dictionary_settings() { 
		$formtitle = __('Add a term','xili-dictionary');
		$formhow = " ";
		$submit_text = __('Add &raquo;','xili-dictionary');
		$cancel_text = __('Cancel');
		$langfolderset = $this->xili_settings['langs_folder'];
		$this->langfolder = ($langfolderset !='/')  ? '/'.str_replace("/","",$langfolderset).'/' : '/';
		$tagsnamelike = $_POST['tagsnamelike'];
		if (isset($_GET['tagsnamelike']))
		    $tagsnamelike = $_GET['tagsnamelike']; /* if link from table */
		$tagsnamesearch = $_POST['tagsnamesearch'];
		if (isset($_GET['tagsnamesearch']))
			$tagsnamesearch = $_GET['tagsnamesearch'];
		
		if (isset($_POST['reset'])) {
			$action=$_POST['reset'];
		
		} elseif (isset($_POST['action'])) {
			$action=$_POST['action'];
		}
		
		if (isset($_GET['action'])) {
			$action=$_GET['action'];
			$term_id = $_GET['term_id'];
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
		
		$message = $action." = " ; $msg = 0;
	switch($action) {
		
		case 'lang_delete';
				$reflang = $_POST['langs_list'];
				$wp_lang = (defined('WPLANG')) ? strtolower(WPLANG) : 'en_us'; 
				if ($reflang != 'no_select' &&  $reflang != 'en_us' &&  $reflang != $wp_lang) {
					unset($this->xili_settings[XDDICTLANGS][$reflang]);
					update_option('xili_dictionary_settings', $this->xili_settings);
					$message .= ' '.$reflang.' deleted';
				} else { 
					$message .= ' nothing to delete';
				}				
				
				$actiontype = "add";
				break;
								
		case 'lang_add';
				$reflang = ('' != $_POST['lang_ISO'] ) ? $_POST['lang_ISO'] : "???";
				$reflangname = ('' !=$_POST['lang_name']) ? $_POST['lang_name'] : $reflang; 
				if ($reflang != '???' && ( ( strlen($reflang) == 5 && substr($reflang,2,1) == '_') ) || ( strlen($reflang) == 2 ) ) {
					$lkey = strtolower($reflang);
					$reflang = ( strlen($reflang) == 5 ) ? strtolower(substr($reflang,0,3)).strtoupper(substr($reflang,-2)) : $reflang ;
					$msg = (array_key_exists($lkey, $this->xili_settings[XDDICTLANGS])) ? ' updated' : ' added';
					$this->xili_settings[XDDICTLANGS][$lkey] = array($reflang,$reflangname);
					update_option('xili_dictionary_settings', $this->xili_settings);
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
		
		case 'add':
			check_admin_referer( 'xilidicoptions' );
			$originalortrans = $_POST['dictioline_description1'];
			$nblines = $_POST['termnblines']; /**/
			if ( $nblines > 1 )  {
				for ($i = 2; $i <= $nblines; $i++) {
					$originalortrans .= XPLURAL.$_POST['dictioline_description'.$i];
				}
			}
			
			/*create the slug with term and add prefix*/
			$possible = true;
			if (''!=$_POST['dictioline_lang']) {
			  if ('no_parent' != $_POST['dictioline_parent'] ) {
				$parent_id = $_POST['dictioline_parent'];
				$parent_term = get_term( (int)$parent_id, DTAXONAME, OBJECT, 'edit' );
				$sslug = $parent_term->slug.'_'.$_POST['dictioline_lang'] ;
				
				$lang = $_POST['dictioline_lang'];
			  } else {
			  	$message .= __('No parent term defined','xili-dictionary');
			  	$possible = false;
			  }
			} else {
				$lang = '';
				/* is content plural */
				if (false === strpos($originalortrans,XPLURAL)) {			
					$sslug = htmlentities($originalortrans);
						
				} else {
					$plurals = explode (XPLURAL,$originalortrans);
					$sslug = htmlentities($plurals[0]);	/* in term slug only first*/
				}	
			}
			$sslug = $this->test_and_create_slug($originalortrans, $sslug, $lang);	
			
			if ($possible) {
				$aliasof = $_POST['alias_of'];			
				$args = array('alias_of' => $aliasof, 'description' => $originalortrans, 'parent' => $_POST['dictioline_parent'], 'slug' => $sslug);
				
			    $rterm_id = $this->xili_dic_insert_term( $originalortrans, DTAXONAME, $args);
			    
			    if (''!=$_POST['dictioline_lang']) {
				    if (is_wp_error($rterm_id)) {$message .= ' ---- error ---- '; $possible = false ;} else {
				    	$this->xd_wp_set_object_terms((int) $rterm_id['term_id'], $_POST['dictioline_lang'], XDDICTLANGS,false);	
				    }
			    } else {
			    	if (is_wp_error($rterm_id)) { $message .= ' ---- error ---- '; $possible = false ; } else { $message .= " (". $rterm_id['term_id'] .") "; }
			    }
			}
		    $actiontype = "add";
		    if ($possible) $message .= " - ".__('A new term was added.','xili-dictionary'); $msg = 1;
		     break;
		    
		case 'edit';
		    $actiontype = "edited";
		    
		    $dictioline = get_term( (int)$term_id, DTAXONAME, OBJECT, 'edit');
		    $submit_text = __('Update &raquo;','xili-dictionary');
		    $formtitle = 'Edit term';
		    $message .= " - ".__('Term to update.','xili-dictionary');
		    break;
		    
		case 'edited';
			check_admin_referer( 'xilidicoptions' );
		    $actiontype = "add";
		    $term = $_POST['dictioline_term_id'];
		    $termname = $_POST['dictioline_name']; 
		    $sslug = $_POST['dictioline_slug'];
		    $originalortrans = $_POST['dictioline_description1'];
			$nblines = $_POST['termnblines']; /**/
			if ( $nblines > 1 )  {
				for ($i = 2; $i <= $nblines; $i++) {
					$originalortrans .= XPLURAL.$_POST['dictioline_description'.$i];
				}
			}
		    
			if (''!=$_POST['dictioline_lang']) {
				$parent_id = $_POST['dictioline_parent'];
				$parent_term = get_term( (int)$parent_id, DTAXONAME, OBJECT, 'edit' );
				$sslug = $parent_term->slug.'_'.$_POST['dictioline_lang'] ;
			}
			
			$args = array('name'=>$termname, 'alias_of' => $_POST['alias_of'], 'description' => $originalortrans , 'parent' => $_POST['dictioline_parent'], 'slug' => $sslug);
		    
		    $this->xili_dic_update_term($term, DTAXONAME, $args);
		    
			if (''!=$_POST['dictioline_lang']) {
			    if (is_wp_error($rterm_id)) {$message .= ' ---- error ---- ';} else {
			    	$this->xd_wp_set_object_terms((int) $rterm_id['term_id'], $_POST['dictioline_lang'], XDDICTLANGS,false);	
			    }
		    }
			$message .= " - ".__('A term was updated.','xili-dictionary').' '.$_POST['dictioline_term_id']; $msg = 2;
		    break;
		    
		case 'delete';
		    $actiontype = "deleting";
		    $submit_text = __('Delete &raquo;','xili-dictionary');
		    $formtitle = 'Delete term ?';
		    $dictioline = get_term( (int)$term_id, DTAXONAME, OBJECT, 'edit');
		    
		    $message .= " - ".__('A term to delete. CLICK MENU DICTIONARY TO CANCEL !','xili-dictionary');
		    
		    break;
		    
		case 'deleting';
			check_admin_referer( 'xilidicoptions' );
		    $actiontype = "add";
		    $term_id = $_POST['dictioline_term_id'];
		    wp_delete_object_term_relationships( $term_id, XDDICTLANGS );
		    remove_action( 'delete_term',                '_wp_delete_tax_menu_item'       ); 
		    wp_delete_term( $term_id, DTAXONAME, $args);
		    add_action( 'delete_term',                '_wp_delete_tax_menu_item'       );
		    $message .= " - ".__('A term was deleted.','xili-dictionary'); $msg = 3;
		    $term_id = 0; /* 0.9.7.2 */
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
		     	if ($this->notwp3 === false) {
			     	if (is_multisite()) { /* complete theme's language with db structure languages (cats, desc,…) in uploads */
						//global $wpdb;
	    				//$thesite_ID = $wpdb->blogid;
	   					$superadmin = ($_POST['only-theme'] == 'only') ? true : false ;
	   					$message .= ($_POST['only-theme'] == 'only') ? "- exported only in theme - " : "- exported in uploads - " ;
	   					$mo = $this->from_twin_to_POMO_wpmu ($selectlang,'mo', $superadmin);
	   					if (($uploads = xili_upload_dir()) && false === $uploads['error'] ) {
	   						$file = ($superadmin === true) ? "" : $uploads['path']."/".$selectlang.".mo";
	   					} 
	    			} else {
			     		$mo = $this->from_twin_to_POMO ($selectlang);
			     	}
		     	} else {
		     		$mo = $this->from_twin_to_POMO ($selectlang);
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
		     	$po = $this->from_twin_to_POMO ($selectlang,'po');
		     	//if ($this->xili_exportterms_inpo(strtolower($selectlang))) {
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
		    
		    $po = $this->pomo_import_PO ($selectlang); 
		    if (false !== $po ) $twintexts = $this->from_PO_to_twin ($po);
			
			if (is_array($twintexts)) {
				$nblines = $this->xili_import_in_tables($twintexts,$selectlang); 
				delete_option(DTAXONAME."_children");
				// Regenerate {$taxonomy}_children - since 1.1.0
				_get_term_hierarchy(DTAXONAME);
				$message .= __('id lines = ','xili-dictionary').$nblines[0].' & ' .__('str lines = ','xili-dictionary').$nblines[1].' & ' .__('str lines up = ','xili-dictionary').$nblines[2];
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
		    if (false !== $mo ) $twintexts = $this->from_MO_to_twin ($mo);
		    if (is_array($twintexts)) {
		    	$nblines = $this->xili_import_in_tables($twintexts,$selectlang);
		    	delete_option(DTAXONAME."_children");
				// Regenerate {$taxonomy}_children - since 1.6.6 fixes
				_get_term_hierarchy(DTAXONAME); 
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
		    $message .= ' '.__('terms imported from WP: ','xili-dictionary'); $msg = 4;
		    
		    $infosterms = $this->xili_read_infosterms();
			
			if ($infosterms > 0) {
				$message .= ' ('.$infosterms.') '.__('imported with success','xili-dictionary');
			} else {
				$message .= ' '.__('already imported','xili-dictionary');
			}	
		    break;
		  	
		case 'importcats';
			$actiontype = "importingcats";
		    $formtitle = __('Import terms of categories','xili-dictionary');
		    $formhow = __('To import terms of the current categories, click below.','xili-dictionary');
			$submit_text = __('Import category’s terms &raquo;','xili-dictionary'); 
		    break;
		
		case 'importingcats';
			check_admin_referer( 'xilidicoptions' );
			$actiontype = "add";
		    $message .= ' '.__('terms imported from WP: ','xili-dictionary'); $msg = 4;
		    
		    $catterms = $this->xili_read_catsterms();
			
			if (is_array($catterms)) {
				$nbterms = $this->xili_importcatsterms_in_tables($catterms); 
				$message .= __('names = ','xili-dictionary').$nbterms[0].' & ' .__('descs = ','xili-dictionary').$nbterms[1];
			} else {
				$message .= ' '.__('category’terms pbs!','xili-dictionary');
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
		    $listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false));
		    if (!empty($listdictiolines)) {
		    	foreach ($listdictiolines as $dictioline) {
		    		wp_delete_object_term_relationships( $dictioline->term_id, XDDICTLANGS );
		    		remove_action( 'delete_term',                '_wp_delete_tax_menu_item'       );
		    		wp_delete_term($dictioline->term_id, DTAXONAME, $args);
		    		add_action( 'delete_term',                '_wp_delete_tax_menu_item'       ); //1.2.2
		    	}
		    	
		    	$dictioline = null;
		    }
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
				$nbterms = $this->xili_importthemeterms_in_tables($themeterms); 
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
		
		add_meta_box('xili-dictionary-normal-1', __($formtitle,'xili-dictionary'), array(&$this,'on_normal_2_content'), $this->thehook , 'normal', 'core'); /* input form */
		add_meta_box('xili-dictionary-normal-2', __('Multilingual Terms','xili-dictionary'), array(&$this,'on_normal_1_content'), $this->thehook , 'normal', 'core'); /* list of terms*/
		
		// since 1.2.2 - need to be upgraded...
		if ($msg == 0 && $message != '' ) $msg = 6 ; //by temporary default
		$themessages[1] = __('A new term was added.','xili-dictionary');
		$themessages[2] = __('A term was updated.','xili-dictionary');
		$themessages[3] = __('A term was deleted.','xili-dictionary');
		$themessages[4] = __('terms imported from WP: ','xili-dictionary');
		$themessages[5] = __('All terms imported !','xili-dictionary');
		$themessages[6] = 'beta testing log: '.$message ;
		$themessages[7] = __('All terms erased !','xili-dictionary');
		
		/* form datas in array for do_meta_boxes() */
		$data = array('message'=>$message,'messagepost'=>$messagepost,'action'=>$action, 'formtitle'=>$formtitle, 'dictioline'=>$dictioline,'submit_text'=>$submit_text,'cancel_text'=>$cancel_text, 'formhow'=>$formhow, 'orderby'=>$orderby,'term_id'=>$term_id, 'tagsnamesearch'=>$tagsnamesearch, 'tagsnamelike'=>$tagsnamelike);
		?>
		<div id="xili-dictionary-settings" class="wrap" style="min-width:850px">
			<?php screen_icon('tools'); ?>
			<h2><?php _e('Dictionary','xili-dictionary') ?></h2>
			<?php if (0!= $msg ) { // 1.2.2 ?>
			<div id="message" class="updated fade"><p><?php echo $themessages[$msg]; ?></p></div>
			<?php } ?>
			<form name="add" id="add" method="post" action="tools.php?page=dictionary_page">
				<input type="hidden" name="action" value="<?php echo $actiontype ?>" />
				<?php wp_nonce_field('xili-dictionary-settings'); ?>
				<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); 
				/* 0.9.6 add has-right-sidebar for next wp 2.8*/ ?>
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div id="side-info-column" class="inner-sidebar">
						<?php do_meta_boxes($this->thehook, 'side', $data); ?>
					</div>
					<div id="post-body" class="has-sidebar has-right-sidebar">
						<div id="post-body-content" class="has-sidebar-content" style="min-width:360px">
					
	   					<?php do_meta_boxes($this->thehook, 'normal', $data); ?>
						</div>
						 	
					<h4><a href="http://dev.xiligroup.com/xili-dictionary" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo plugins_url( 'images/xilidico-logo-32.png', __FILE__ ) ; ?>" alt="xili-dictionary logo"/>  xili-dictionary</a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-11 - v. <?php echo XILIDICTIONARY_VER; ?></h4>
							
					</div>
				</div>
				<?php wp_nonce_field('xilidicoptions'); ?>
		</form>
		</div>
		<script type="text/javascript">
		
			//<![CDATA[
			jQuery(document).ready( function($) {
				
				var termsTable = $('#termstable').dataTable( {
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
						{ "bSearchable": false, "sWidth" : "35px" },
						{ "sWidth" : "50%" },
						{ "bSortable": true, "bSearchable": false  },
						{ "bSortable": false, "bSearchable": false,  "sWidth" : "35px" },
						{ "bSortable": false, "bSearchable": false, "sWidth" : "140px" } ]
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
	
	/** * private function for admin page : select of parents */
		function xili_select_row( $term_id = 0, $editterm, $listby = 'name' ) {
		// select all terms if current term is new
			if (isset($_GET['from_term_id'])) {
				$from_term_id = $_GET['from_term_id'] ;
				$message = __('Preselected original term (msgid) to translate','xili-dictionary');
			} else {
				$message = __('Choose the original term (msgid) to translate','xili-dictionary');
			}
			
			if ($term_id == 0) {
				$listterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => '0'));
				echo '<small>'.$message.'<br /></small>'; ?>
				<select name="dictioline_parent" id="dictioline_parent" style="width:100%;">
		  				<option value="no_parent" ><?php _e('no parent (= msgid)','xili-dictionary'); ?></option>
				<?php
				foreach ($listterms as $curterm) {
					$sel = ($from_term_id == $curterm->term_id) ? "selected=selected" : "";
					echo '<option '.$sel.' value="'.$curterm->term_id.'" >'.substr($curterm->slug,0,50).' ('.$curterm->term_id.') </option>';
				} 
				?>
				</select>
				<br />
	    		<?php 
	     	} else {
	     	
	     		if ($editterm->parent == 0) {
	     			$listterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => $term_id)); 
					// display childs
					if (!empty($listterms)) {
						echo '<small style="font-weight:bold;">'.__('translated as','xili-dictionary').": <br /><br /></small>";
						echo '<ul>';
						$yettranslated = array();
						foreach ($listterms as $curterm) { 
							$edit = "<a href='?action=edit&amp;page=dictionary_page&amp;term_id=".$curterm->term_id."' >".__( 'Edit' )."</a>";
							$slug  = $this->extract_extend ( $curterm->slug ); 
							$yettranslated[] = $slug ;
							$target = ( strlen ($slug) == 5 ) ? strtolower(substr($slug,0,3)).strtoupper(substr($slug,-2)) : $slug ;
							echo '<li title="'.$curterm->term_id.'" >&nbsp;&nbsp;('.$target.') '.$this->display_singular_or_plural ($curterm->description, true).' <small>'.$edit.'</small></li>';
							
						}
						echo '</ul><br />';
						/* untranslated target languages 1.3.1 */
						echo __('add a translation','xili-dictionary').'&nbsp;:&nbsp;';
						/* list target languages 1.3.1 */
						
						$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'get' => 'all'));
						if ( is_wp_error($listlanguages) || empty($listlanguages) ) { 
							$langs_array = $this->xili_settings[XDDICTLANGS]; 
							foreach ($langs_array as $slug => $language) {
	         				 	if ( !in_array ( $language->slug, $yettranslated ) )
									echo '<a href="?page=dictionary_page&amp;from_term_id='.$term_id.'&amp;tarlang='.$slug.'">'.$language[0].'</a>&nbsp;&nbsp;';
							}
						} else {
							foreach ( $listlanguages as $language ) { 
								if ( !in_array ( $language->slug, $yettranslated ) )
								echo '<a href="?page=dictionary_page&amp;from_term_id='.$term_id.'&amp;tarlang='.$language->slug.'">'.$language->name.'</a>&nbsp;&nbsp;';
							}	
						}
					} else {
						echo __('not yet translated','xili-dictionary')."<br />";	
						echo __('add a translation','xili-dictionary').'&nbsp;:&nbsp;';
						/* list target languages 1.3.1 */
						
						$listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'get' => 'all'));
						if ( is_wp_error($listlanguages) || empty($listlanguages) ) { 
							$langs_array = $this->xili_settings[XDDICTLANGS]; 
							foreach ($langs_array as $slug => $language) {
	         				 	echo '<a href="?page=dictionary_page&amp;from_term_id='.$term_id.'&amp;tarlang='.$slug.'">'.$language[0].'</a>&nbsp;&nbsp;';
							}
						} else {
						
							foreach ( $listlanguages as $language ) {
								echo '<a href="?page=dictionary_page&amp;from_term_id='.$term_id.'&amp;tarlang='.$language->slug.'">'.$language->name.'</a>&nbsp;&nbsp;';
							}
						}		
					}	
	     		} else {
	     			echo '<small>'.__('translation of','xili-dictionary').": </small>";
	     			$edit = "<a href='?action=edit&amp;page=dictionary_page&amp;term_id=".$editterm->parent."' >".__( 'Edit' )."</a>";
	     			$parent_term = get_term( (int)$editterm->parent, DTAXONAME, OBJECT, 'edit' );
	     			echo $this->display_singular_or_plural ($parent_term->description, true).' <small>'.$edit.'</small>'; ?>
	     			<input type="hidden" name="dictioline_parent" value="<?php echo $parent_term->term_id ?>" />	
	     	<?php }	
			}	
		}
		
		 
		
	/** 
	 * create an array of mo content of theme (maintained by super-admin)	
	 *
	 * @since 1.1.0
	 */
	 function get_pomo_from_theme() {
	 	$theme_mos = array();
	 	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	 	foreach ($listlanguages as $reflanguage) {
	     	$res = $this->pomo_import_MO ($reflanguage->name);
	     	if (false !== $res) $theme_mos[$reflanguage->slug] = $res->entries;
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
	 	$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
	 	foreach ($listlanguages as $reflanguage) {
	     	$res = $this->import_mo_file_wpmu ($reflanguage->name, false); // of current site
	     	if (false !== $res) $theme_mos[$reflanguage->slug] = $res->entries;
	 	}
	 	return $theme_mos;	
	 }
	 
	 
		
	/** * private function for admin page : one line of taxonomy */
		
	function xili_dict_row($listby='name',$tagsnamelike='',$tagsnamesearch='') { 
		global $default_lang;	
		/*list of dictiolines*/
	
		if ($this->subselect == 'msgid' || $this->subselect == '') {
			$parentselect = '';
			if ($this->subselect == 'msgid') $parentselect = '0'; 
			$listdictiolines = get_terms (DTAXONAME, array('hide_empty' => false,'orderby' => 	$listby,'get'=>'all','name__like'=>$tagsnamelike,'search'=>$tagsnamesearch, 'parent'=>$parentselect));	
		} else {
			/*  */
			$group = is_term($this->subselect,XDDICTLANGS);
			$listdictiolines = get_terms_of_groups(array($group['term_id']),XDDICTLANGS,DTAXONAME, array('hide_empty' => false,'orderby' => 	$listby,'get'=>'all','name__like'=>$tagsnamelike,'search'=>$tagsnamesearch));
		}
		if (empty($listdictiolines) && $tagsnamelike=='' && $tagsnamesearch=='') { /*create a default line with the default language (as in config)*/
			/*$term = 'term';
			$args = array( 'alias_of' => '', 'description' => "term", 'parent' => 0, 'slug' =>'');
			wp_insert_term( $term, DTAXONAME, $args);
			$listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false)); */
			echo '<p style="color:red" >'.__('Add a term or import a language file (pot, po or mo) available in your theme.','xili-dictionary').'</p>';
		} elseif ( empty($listdictiolines) ) {
			echo '<p>'.__('try another sub-selection !','xili-dictionary').'</p>';
		} else {
			// wpmu
			if (function_exists('is_multisite') && is_multisite()) {
			
				$this->theme_mos = $this->get_pomo_from_theme();
				$this->file_site_mos = $this->get_pomo_from_site(); // since 1.2.0 - mo of site
			}
			//
			$subselect = (($tagsnamelike=='') ? '' : '&amp;tagsnamelike='.$tagsnamelike);
			$subselect .= (($tagsnamesearch=='') ? '' : '&amp;tagsnamesearch='.$tagsnamesearch);
					
			foreach ($listdictiolines as $dictioline) {
				
				$class = (( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";
		
				$dictioline->count = number_format_i18n( $dictioline->count );
				$posts_count = ( $dictioline->count > 0 ) ? "<a href='edit.php?lang=$dictioline->term_id'>$dictioline->count</a>" : $dictioline->count;	
			
				$edit = "<a href='?action=edit&amp;page=dictionary_page".$subselect."&amp;term_id=".$dictioline->term_id."' >".__( 'Edit' )."</a></td>";	
				/* delete link */
				$edit .= "<td class='center' ><a href='?action=delete&amp;page=dictionary_page".$subselect."&amp;term_id=".$dictioline->term_id."' class='delete'>".__( 'Delete' )."</a>";	
				if (function_exists('is_multisite') && is_multisite()) {
					if ($dictioline->parent == 0) { 
						$techinfos = "<strong>".$dictioline->slug."</strong>";
						$techinfos .= $this->is_saved_in_theme($dictioline->description);
					} else {
						$key = $this->extract_extend ( $dictioline->slug );
						$parent_term = get_term( (int)$dictioline->parent, DTAXONAME, OBJECT, 'edit' ); 
						$msg = $parent_term->description; 
						$msgid = "";
						if (false === strpos($msg,XPLURAL)) {
							$msgid = $msg;
						} else {
							$msgidplural = explode(XPLURAL,$msg);
							$msgid = $msgidplural[0];
						}
						$keyfile = ( strlen ( $key ) == 5 ) ? substr($key,0,3).strtoupper(substr($key,-2)) : $key ;
						if ( is_array($this->theme_mos[$key]) && array_key_exists(htmlspecialchars_decode($msgid), $this->theme_mos[$key]) ) {
							$mess =	'<span style="color:green" title="'.__("translation saved in theme's",'xili-dictionary').'">'.sprintf(__('saved in %s.mo of theme','xili-dictionary'),$keyfile);	
						} elseif ( is_array($this->file_site_mos[$key]) && array_key_exists(htmlspecialchars_decode($msgid), $this->file_site_mos[$key]) ) { // since 1.2.0 check each language of current site
							$mess =	'<span style="color:blue" title="'.__("translation saved in site's",'xili-dictionary').'">'.sprintf(__('saved in %s.mo of site','xili-dictionary'),$keyfile);
						} else {
							$mess = '<span style="color:brown" title="'.__('not saved in theme or site .mo','xili-dictionary').'">...</span>';
						}
						
						$techinfos = "<em>".$dictioline->slug."</em><br /><small> ".$mess."</small>";
					}
				} else {
					if ($dictioline->parent == 0) {
						$techinfos = "<strong>".$dictioline->slug."</strong>";
					} else {
						$techinfos = "<em>".$dictioline->slug."</em>";
					}	
				}
				/* modify to allow all html tags in msg str or id -  0.9.8.2*/
				$line="<tr id='cat-$dictioline->term_id'$class>
				<td scope='row' style='text-align: center'>$dictioline->term_id</td>
				
				<td>".$this->display_singular_or_plural($dictioline->description)."</td>
				
				<td>".$techinfos."</td>
				<td class='center'>$dictioline->term_group</td>
				  
				<td class='center'>$edit</td>\n\t</tr>\n"; /*to complete*/
				echo $line;
			}	
		}
	}
	
	function is_intheme_mos ($msgid, $entries) {
	//if (array_key_exists($msgid, $entries) {
		
	//} else {
		
	//}
		foreach ($entries as $entry) {
			$diff = strcmp(htmlspecialchars_decode($msgid),$entry->singular);
			if ( $diff != 0) {echo $msgid.' i= '.strlen($msgid); echo $entry->singular.') e= '.strlen($entry->singular); }
			if ( $diff == 0) return true;
		}	
	return false;
	}
	
	/**
	 * Detect if translations are saved in theme's languages folder
	 * @since 1.0.4 - WPMU
	 *
	 */
	function is_saved_in_theme($msg) {
		$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
		$thelist = array();
		$msgid = "";
		if (false === strpos($msg,XPLURAL)) {
			$msgid = $msg;
		} else {
			$msgidplural = explode(XPLURAL,$msg);
			$msgid = $msgidplural[0];
		}
	 	foreach ($listlanguages as $reflanguage) {
	 		if (isset($this->theme_mos[$reflanguage->slug])) {
			 	$mess = (array_key_exists($msgid,$this->theme_mos[$reflanguage->slug])) ? $reflanguage : ""; 
				if ("" != $mess) $thelist[] = $reflanguage->name.".mo";
	 		}
	 	}
	 	
		$output = ($thelist == array()) ? '<br /><small><span style="color:black" title="'.__("No translations saved in theme's .mo files","xili-dictionary").'">**</span></small>' : '<br /><small><span style="color:green" title="'.__("Original with translations saved in theme's files: ","xili-dictionary").'" >'. implode(', ',$thelist).'</small></small>';
		return $output;
	}
	
	function display_singular_or_plural ($msg, $onlyfirst = false) {
		if (false === strpos($msg,XPLURAL)) {
			return wp_filter_kses($msg);
		} else {
			$list = explode (XPLURAL,$msg);
			if ($onlyfirst === false) {
				$list = array_map('wp_filter_kses',$list);
				return implode('<br />',$list);
			} else {
				return wp_filter_kses($list[0]);
			}
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
					$theme_name = get_option("stylesheet").' '.__('child of','xili-language').' '.get_option("template"); 
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
	 * the PO object to twinlines (msgid - msgstr) for list
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 */	
	function from_PO_to_twin ($po)	{
		$twinlines = array();
		foreach ($po->entries as $pomsgid => $pomsgstr) {
			if ($pomsgstr->is_plural == null) {
				$twinlines[$pomsgid] = $pomsgstr->translations[0];
			} else {
				$keytwin = $pomsgstr->singular.XPLURAL.$pomsgstr->plural;
				$twinlines[$keytwin] = implode (XPLURAL, $pomsgstr->translations);
			}
			
		} 
		return $twinlines;
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
	 * the MO object to twinlines (msgid - msgstr) for list
	 *
	 *
	 * @since 1.0.2 - only WP >= 2.8.4
	 */	
	function from_MO_to_twin ($mo)	{
		$twinlines = array();
		foreach ($mo->entries as $pomsgid => $pomsgstr) {
			if ($pomsgstr->is_plural == null) {
				$twinlines[$pomsgid] = $pomsgstr->translations[0];
			} else {
				$keytwin = $pomsgstr->singular.XPLURAL.$pomsgstr->plural;
				$twinlines[$keytwin] = implode (XPLURAL, $pomsgstr->translations);
			}
			
		}
		return $twinlines;
	}
	
	/**
	 * convert twinlines (msgid - msgstr) to MOs in wpmu
	 * @since 1.0.4
	 *
	 * @params as from_twin_to_POMO and $superadmin 
	 */	
	function from_twin_to_POMO_wpmu ($curlang, $obj='mo', $superadmin = false)	{
	    // the table array
	    $table_mo = $this->from_twin_to_POMO($curlang);
	    $site_mo = $table_mo; 
	   	// array diff
	   	if (false  === $superadmin) {
	   		// special for superadmin who don't need diff.
			// the pomo array available in theme's folder 
	   		$theme_mo = $this->import_mo_file_wpmu ($curlang, true);
	   	  	if (false !== $theme_mo) {
	   			$site_mo->entries =  array_diff_key($table_mo->entries,$theme_mo->entries); 
	   			// without keys available in theme' mo
	   		}
	   	}
	   	return $site_mo;
	}
	
	/**
	 * convert twinlines (msgid - msgstr) to MO
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 */	
	function from_twin_to_POMO ($curlang, $obj='mo')	{
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
		$listterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => '')); 
			foreach ($listterms as $curterm) { 
				if ($curterm->parent == 0) {		
					/* select child to create translated term */
					$listchildterms = get_terms(DTAXONAME, array('hide_empty' => false, 'parent' => $curterm->term_id));
					$noentry = true; /* to create po with empty translation */ 
					if ( $listchildterms ) { //print_r($listchildterms);

						
						foreach ($listchildterms as $curchildterm) { 
							if ( $this->extract_extend ($curchildterm->slug ) == strtolower($curlang) ) { 
								if ($obj == 'mo') { 
									if (false === strpos($curterm->description,XPLURAL)) {
										$mo->add_entry($mo->make_entry($curterm->description, $curchildterm->description));
									} else {
										$msgidplural = explode(XPLURAL,$curterm->description);
										$original = implode(chr(0),$msgidplural);
										$msgstrplural = explode(XPLURAL,$curchildterm->description);
										$translation = implode(chr(0),$msgstrplural);
										
										$mo->add_entry($mo->make_entry($original, $translation));
									}	
								} else { /* po */ 
									if (false === strpos($curterm->description,XPLURAL)) {
										$entry = & new Translation_Entry(array('singular'=>$curterm->description,'translations'=> explode(XPLURAL, $curchildterm->description)));
									} else {
										$msgidplural = explode(XPLURAL,$curterm->description);
										$msgstrplural = explode(XPLURAL,$curchildterm->description);
										$entry = & new Translation_Entry(array('singular' => $msgidplural[0],'plural' => $msgidplural[1], 'is_plural' =>1, 'translations' => $msgstrplural)); 	
									}
									$mo->add_entry($entry); 
									$noentry = false;
								}
							}
						}
					}
					/* to create po with empty translations */
					if ($obj == 'po' && $noentry == true) {
						$entry = & new Translation_Entry(array('singular'=>$curterm->description,'translations'=> ""));
						$mo->add_entry($entry);
					}
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
	 */	
	function plural_forms_rule($curlang) {	
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
		
	/*
	 * import array of twintexts in terms tables
	 *
	 * @since 0.9.0
	 * @updated 0.9.7 to set in langs group
	 * @updated 1.0.0 to manage plural
	 *	
	 * @param array of msgid/msgstr, language (xx_XX)
	 *
	 */
	function xili_import_in_tables($twintexts=Array(),$translang) {
		$nbline = 0;
		$nbtline = 0;
	 	foreach ($twintexts as $key => $line) {
	 		
	 		/* is content plural */
				if (false === strpos($key,XPLURAL)) {			
					$thekey = htmlentities($key);			
				} else {
					$plurals = explode (XPLURAL,$key);
					$thekey = htmlentities($plurals[0]);	/* in term slug only first*/
				}	
	 		
	    	// verify if origin msgid term exist
			$cur_id = is_term($thekey, DTAXONAME);
			if ($cur_id == 0) {
				// create term 
				$args = array('description' => $key, 'slug'=>sanitize_title($thekey));
				$cur_id = is_term(sanitize_title($thekey),DTAXONAME);
				if ($cur_id == 0) {
					$result = $this->xili_dic_insert_term(htmlentities($thekey), DTAXONAME, $args);
					$insertid = $result['term_id'];
					$nbline++;
				} else {
					$insertid = $cur_id['term_id'];	
				}	
				$parent_term = get_term( (int)$insertid, DTAXONAME, OBJECT, 'edit');
				 
				/* create the translated term */
				$sslug = $parent_term->slug.'_'.strtolower($translang);
				$args = array('parent' => $parent_term->term_id, 'slug' => $sslug,'description' => $line);
				$existing = get_term_by('slug', $sslug, DTAXONAME, OBJECT);
				if ($existing == null && !is_wp_error($existing)) { /* perhaps in another lang */
					/* msgstr don't exist */
					/* is content plural */
					if (false === strpos($line,XPLURAL)) {			
						$theline = htmlentities($line);			
					} else {
						$plurals = explode (XPLURAL,$line);
						$theline = htmlentities($plurals[0]);	/* in term slug only first*/
					}	
	 		 
					$result = $this->xili_dic_insert_term($theline, DTAXONAME, $args);
					if (!is_wp_error($result)) 
						$this->xd_wp_set_object_terms((int) $result['term_id'], strtolower($translang), XDDICTLANGS,false);
					$nbtline++;	
				} else {
					/* test slug of existing term */
					if ($line != $existing->description) {
						$this->xili_dic_update_term($existing->term_id, DTAXONAME, $args);
						$nbuline++;
					}
				}		
			} else {
			/* echo msgid exist */
				$parent_term = get_term( (int)$cur_id['term_id'], DTAXONAME, OBJECT, 'edit' );
				
				/* verify translated term msgstr */
				if (''!=$line) {
					
					$sslug = $parent_term->slug.'_'.strtolower($translang);
					$args = array('parent' => $parent_term->term_id, 'slug' => $sslug, 'description' => $line);
					$existing = get_term_by('slug', $sslug, DTAXONAME, OBJECT);
					if ($existing == null && !is_wp_error($existing)) {
						/* no term msgstr */
						/* is content plural */
						if (false === strpos($line,XPLURAL)) {			
							$theline = htmlentities($line);			
						} else {
							$plurals = explode (XPLURAL,$line);
							$theline = htmlentities($plurals[0]);	/* in term slug only first*/
						}
						$result = $this->xili_dic_insert_term($theline, DTAXONAME, $args);
						if (!is_wp_error($result)) 
							$this->xd_wp_set_object_terms((int) $result['term_id'], strtolower($translang), XDDICTLANGS,false);
						$nbtline++;
					} else {
						/* term exists */ 
						if ($line != $existing->description) {
							$this->xili_dic_update_term($existing->term_id, DTAXONAME, $args);
							$nbuline++;
						}
					}		
				} /* empty line */
			} /* parent exist */
	 	} /* loop */
	 	return array($nbline,$nbtline,$nbuline);  //root id lines, translated lines and updated translated lines
	}
	/** bloginfo term and others in table * @since 1.6.0 */
	function xili_read_infosterms () {
		$nbname = 0;
		$terms_to_import = array();
		$terms_to_import[] = get_bloginfo( 'blogname', 'display' );
		$terms_to_import[] = get_bloginfo( 'description', 'display' );	
		$terms_to_import[] = get_option('time_format');
		$terms_to_import[] = get_option('date_format');
		
		if ( class_exists ('xili_language') ) {
			global $xili_language;
			if (!$xili_language->notwp3) {
				foreach ($xili_language->comment_form_labels as $key => $label) {
					$terms_to_import[] =  $label ;
				}
			}
		}
		
		foreach ( $terms_to_import as $term )  {
			$cur_id = is_term($term, DTAXONAME);
			if ($cur_id == 0) {
				$args = array('description' => $term);
				$result = $this->xili_dic_insert_term( $term, DTAXONAME, $args);
				$nbname++;
			}
		}
		return $nbname;
	}
	
	/* cat's terms in array (name - slug - description)*/
	function xili_read_catsterms(){
		$listcategories = get_terms('category', array('hide_empty' => false));
		foreach ($listcategories as $category) {
			$catterms[] = Array($category->name,$category->description);
		}
		return $catterms;
	}
	/* array in tables */
	function xili_importcatsterms_in_tables($catterms= Array()){
		$nbname = 0;
		$nbdesc = 0;
		foreach ($catterms as $onecat) {
	     
			$cur_id = is_term($onecat[0], DTAXONAME);
			if ($cur_id == 0) {
				 
				$args = array('description' => $onecat[0]);
				$result = $this->xili_dic_insert_term( $onecat[0], DTAXONAME, $args);
				$nbname++;
			}
			$cur_id = is_term(htmlentities($onecat[1]), DTAXONAME);
			if ($cur_id == 0 && ""!=$onecat[1]) {
				 
				$args = array('description' => $onecat[1]);
				$result = $this->xili_dic_insert_term(htmlentities($onecat[1]), DTAXONAME, $args);
				$nbdesc++;
			}
		}
		return array($nbname,$nbdesc); // quantities imported
	}
	
		
	function scan_import_theme_terms($callback,$display) {
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
	/*
	 * Import theme terms array in table 
	 *
	 *
	 */
	function xili_importthemeterms_in_tables($themeterms= Array()){
		$nbname = 0;
		 
		foreach ($themeterms as $onecat) {
	     
			$cur_id = is_term($onecat, DTAXONAME);
			if ($cur_id == 0) {
				
				$args = array('description' => $onecat);
				$result = $this->xili_dic_insert_term(htmlentities($onecat), DTAXONAME, $args);
				$nbname++;
			}
		}
		return $nbname;
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
  		//echo $filename . " in : " . "/".str_replace("/","",str_replace(get_template_directory(),'',$path)) . "<br />";
  		echo str_replace(".mo","",$filename ). " (".$this->ossep.str_replace($this->ossep,"",str_replace($this->get_template_directory,'',$path)).")<br />";
	}
	
	/**
	 * Contextual help
	 *
	 * @since 1.2.2
	 */
	 function add_help_text($contextual_help, $screen_id, $screen) { 
	  // $contextual_help = var_dump($screen); // use this to help determine $screen->id
	  //echo $contextual_help;
	  //echo $screen_id;
	  //print_r($screen);
	  if ('tools_page_dictionary_page' == $screen->id ) {
	    $contextual_help =
	      '<p>' . __('Things to remember to set xili-dictionary:','xili-dictionary') . '</p>' .
	      '<ul>' .
	      '<li>' . __('Verify that the theme is localizable (like kubrick, fusion or twentyten).','xili-dictionary') . '</li>' .
	      '<li>' . __('Define the list of targeted languages.','xili-dictionary') . '</li>' .
	      '<li>' . __('Prepare a sub-folder .po and .mo files for each language (use the default delivered with the theme or add the pot of the theme and put them inside.','xili-dictionary') . '</li>' .
	      '<li>' . __('If you have files: import them to create a base dictionary. If not : add a term or use buttons of import and export metabox.','xili-dictionary') . '</li>' .
	      '</ul>' .
	      
	      '<p><strong>' . __('For more information:') . '</strong></p>' .
	      '<p>' . __('<a href="http://dev.xiligroup.com/xili-dictionary" target="_blank">Xili-dictionary Plugin Documentation</a>','xili-dictionary') . '</p>' .
	      '<p>' . __('<a href="http://codex.wordpress.org/" target="_blank">WordPress Documentation</a>','xili-dictionary') . '</p>' .
	      '<p>' . __('<a href="http://forum2.dev.xiligroup.com/" target="_blank">Support Forums</a>','xili-dictionary') . '</p>' ;
	  }
	  return $contextual_help;
	}		

} /* end of class */

/**** Functions that improve taxinomy.php ****/

/**
 * get terms and add order in term's series that are in a taxonomy 
 * (not in class for general use)
 * 
 * @since 0.9.8.2 - provided here if xili-tidy-tags plugin is not used
 *
 */
if (!function_exists('get_terms_of_groups')) { 
	function get_terms_of_groups ($group_ids, $taxonomy, $taxonomy_child, $order = '') {
		global $wpdb;
		if ( !is_array($group_ids) )
			$group_ids = array($group_ids);
		$group_ids = array_map('intval', $group_ids);
		$group_ids = implode(', ', $group_ids);
		$theorderby = '';
		$where = '';
		$defaults = array('orderby' => 'term_order', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => '', 'exclude_tree' => '', 'include' => '',
		'number' => '', 'slug' => '', 'parent' => '',
		'name__like' => '',
		'pad_counts' => false, 'offset' => '', 'search' => '');
		
		if (is_array($order)) { 
			 
			$r = &$order;
			$r = array_merge($defaults, $r);
			extract($r);
			
			if ($order == 'ASC' || $order == 'DESC') {
				if ('term_order'== $orderby) {
					$theorderby = ' ORDER BY tr.'.$orderby.' '.$order ;
				} elseif ('count'== $orderby || 'parent'== $orderby) {
					$theorderby = ' ORDER BY tt2.'.$orderby.' '.$order ;
				} elseif ('term_id'== $orderby || 'name'== $orderby) {
					$theorderby = ' ORDER BY t.'.$orderby.' '.$order ;
				}
			}
			
			if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";
		
			if ( '' != $parent ) {
				$parent = (int) $parent;
				$where .= " AND tt2.parent = '$parent'";
			}
		
			if ( $hide_empty && !$hierarchical )
				$where .= ' AND tt2.count > 0'; 
			 
			if ( !empty($number) && '' == $parent ) {
				if( $offset )
					$limit = ' LIMIT ' . $offset . ',' . $number;
				else
					$limit = ' LIMIT ' . $number;
		
			} else {
				$limit = '';
			}
		
			if ( !empty($search) ) {
				$search = like_escape($search);
				$where .= " AND (t.name LIKE '%$search%')";
			}
		
		} else { 
			 
			if ($order == 'ASC' || $order == 'DESC') $theorderby = ' ORDER BY tr.term_order '.$order ;
		}	
		$query = "SELECT t.*, tt2.term_taxonomy_id, tt2.description,tt2.parent, tt2.count, tt2.taxonomy, tr.term_order FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->terms AS t ON t.term_id = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt2 ON tt2.term_id = tr.object_id WHERE tt.taxonomy IN ('".$taxonomy."') AND tt2.taxonomy = '".$taxonomy_child."' AND tt.term_id IN (".$group_ids.") ".$where.$theorderby.$limit;
		 //echo $query;
		$listterms = $wpdb->get_results($query);
		if (!$listterms)
				return array();
		return $listterms;
	}
}

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
	global $wp_version;
	if ($wp_version >= '2.8') $xili_dictionary = new xili_dictionary(); /* instantiation php4 for last century servers replace by =& */ 
}
add_action('plugins_loaded','dictionary_start'); 

/* © xiligroup dev 20110521 */

?>