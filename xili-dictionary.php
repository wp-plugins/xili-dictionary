<?php
/*
Plugin Name: xili-dictionary
Plugin URI: http://dev.xiligroup.com/xili-dictionary/
Description: ONLY for >= WP 2.8. This plugin is a tool using wordpress's taxonomy for localized themes or multilingual themes managed by xili-language - a powerful tool to create .mo file(s) on the fly in the theme's folder and more... -
Author: MS
Version: 1.0
Author URI: http://dev.xiligroup.com
*/

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

define('XILIDICTIONARY_VER','1.0.0');

include_once(ABSPATH . WPINC . '/pomo/po.php'); /* not included in wp-settings */

class xili_dictionary {
	
	var $subselect = ''; /* used to subselect by msgid or languages*/
	var $xililanguage = ''; /* neveractive isactive wasactive */
	var $tempoutput = "";
	var $langfolder ='/'; /* where po or mo files */
	var $xili_settings; /* saved in options */
	
	function xili_dictionary($langsfolder = '/') {
		/* activated when first activation of plug */
		register_activation_hook(__FILE__,array(&$this,'xili_dictionary_activation'));
		
		/* get current settings - name of taxonomy - name of query-tag */
		$this->xililanguage_state();
		$this->xili_settings = get_option('xili_dictionary_settings');
		if(empty($this->xili_settings)) {
			$this->xili_dictionary_activation();
			$this->xili_settings = get_option('xili_dictionary_settings');			
		}
		define('DTAXONAME',$this->xili_settings['taxonomy']);
		define('XDDICTLANGS','xl-'.DTAXONAME.'-langs');
		/** * @since 1.0 */
		define('XPLURAL','[XPLURAL]'); /* to separate singular and plural entries */
		
		
		/* add new taxonomy in available taxonomies here dictionary terms */
		register_taxonomy( DTAXONAME, 'post',array('hierarchical' => true, 'update_count_callback' => ''));
		/* groups of terms by langs */
		register_taxonomy( XDDICTLANGS, 'term',array('hierarchical' => false, 'update_count_callback' => ''));
		
			/* test if version changed */
		$version = $this->xili_settings['version'];
		if ($version == '0.1') {
				/* update relationships for grouping existing dictionary terms */
			$this->update_terms_langs_grouping();
			$this->xili_settings['version'] = '0.2';
			update_option('xili_dictionary_settings', $this->xili_settings);
		}
		
		
		
		
		/* Actions */
		add_action('admin_menu', array(&$this,'xili_add_dict_pages'));
		add_action('init', array(&$this, 'init_textdomain'));
		add_filter('plugin_action_links',  array(&$this,'xilidict_filter_plugin_actions'), 10, 2);
				
	}
	function xili_dictionary_activation() {
		$this->xili_settings = get_option('xili_dictionary_settings');
			if(empty($this->xili_settings)) { 
				$submitted_settings = array(
			    	'taxonomy'		=> 'dictionary',
			    	'langs_folder' => '',
			    	'version' 		=> '0.2'
		    	);
				update_option('xili_dictionary_settings', $submitted_settings);	
			} 
			 	    
	}
	
	/*add admin menu and associated page*/
	function xili_add_dict_pages() {
		 $this->thehook = add_management_page(__('Dictionary','xili-dictionary'), __('Dictionary','xili-dictionary'), 'import', 'dictionary_page', array(&$this,'xili_dictionary_settings'));
		  add_action('load-'.$this->thehook, array(&$this,'on_load_page'));
		 
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
		load_plugin_textdomain('xili-dictionary',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
		
		if (!defined('THEME_LANGS_FOLDER')) { /* set or detect at least a .mo or .po */
			if ($this->xili_settings['langs_folder'] == "") {
				$this->find_files(get_template_directory().'/', '/.[mp]o$/', array(&$this,'searchpath'));
				update_option('xili_dictionary_settings', $this->xili_settings);
			}
			define('THEME_LANGS_FOLDER',$this->xili_settings['langs_folder']); // for bkwd compatibility with xili-dictionary
		}
	}
	function searchpath($path, $filename) { 
		$this->xili_settings['langs_folder'] = str_replace(get_template_directory(),'',$path);
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
		    			$extend = substr($dictioline->slug,-5);
			    		$lang = get_term_by('slug',$extend,TAXONAME,OBJECT);
			    		if ($lang) { 
			    			$term = $lang->name; 
			    		} else { 
			    			$term = $extend; 
			    		}
						$args = array( 'alias_of' => '', 'description' => 'Dictionary Group in '.$term, 'parent' => 0, 'slug' =>$extend);
				    	$theids = wp_insert_term( $term, XDDICTLANGS, $args);
			    		wp_set_object_terms((int) $dictioline->term_id, $extend, XDDICTLANGS,false);
		    		}
		    	}
		    }
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
		<p><?php echo $message;?></p>
		<?php
	}
	
	function  on_sidebox_2_content() { ?>
	 	
		<p><?php _e('xili-dictionary is a plugin (compatible with xili-language) to build a multilingual dictionary saved in the taxonomy tables of WordPress. With this dictionary, it is possible to create and update .mo file in the current theme folder. And more...','xili-dictionary') ?></p>
		<fieldset style="margin:2px; padding:12px 6px; border:1px solid #ccc;"><legend><?php echo __("Theme's informations:",'xili-dictionary').' ('.get_option("template").')'; ?></legend>
	 		<p>
				<?php $langfolder = (defined('THEME_LANGS_FOLDER')) ? THEME_LANGS_FOLDER : $this->xili_settings['langs_folder'];
				echo __("Languages sub-folder:",'xili-dictionary').' '. $langfolder; ?><br />
	 			<?php _e('Available MO files:','xili-dictionary'); echo '<br />';
	 			$this->find_files(get_template_directory().'/', '/.mo$/', array(&$this,'available_mo_files')) ;?>
	 		</p>
	 	</fieldset>
		
		<?php
	}
	
	function on_normal_1_content($data) { 
		extract($data); 
		$sortparent = (($this->subselect == '') ? '' : '&amp;tagsgroup_parent_select='.$this->subselect );
		?>
			<table class="widefat">
				<thead>
				<tr>
					<th scope="col"><a href="?page=dictionary_page"><?php _e('ID') ?></a></th>
			        <th scope="col"><a href="?page=dictionary_page&amp;orderby=name<?php echo $sortparent; ?>"><?php _e('Text') ?></a></th>
			        
			        <th scope="col"><a href="?page=dictionary_page&amp;orderby=slug<?php echo $sortparent; ?>"><?php _e('Slug','xili-dictionary') ?></a></th>
			        <th scope="col" ><?php _e('Group','xili-dictionary') ?></th>
			        
			        <th colspan="2"><?php _e('Action') ?></th>
				</tr>
				</thead>
				<tbody id="the-list">
			<?php $this->xili_dict_row($orderby,$tagsnamelike,$tagsnamesearch); /* the lines */
			?>
				</tbody>
			</table>
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
		if ($action=='export' || $action=='import' || $action=='exportpo' ) { ?>
			
			<label for="language_file">
			<select name="language_file" ><?php
	   			$extend = WPLANG;
	   			$listlanguages = get_terms(TAXONAME, array('hide_empty' => false));
				if (is_wp_error($listlanguages) || empty($listlanguages)) { ?>
			
		  			<option value=""><?php _e('default','xili-dictionary'); ?></option>
		  			<option value="fr_FR" <?php if ($extend == 'fr_FR') { ?> selected="selected"<?php } ?> ><?php _e('french','xili-dictionary'); ?></option>
	            	<option value="en_US" <?php if ($extend == 'en_US') { ?> selected="selected"<?php } ?> ><?php _e('english','xili-dictionary'); ?></option>
	            	<option value="de_DE" <?php if ($extend == 'de_DE') { ?> selected="selected"<?php } ?> ><?php _e('german','xili-dictionary'); ?></option>
            		<option value="it_IT" <?php if ($extend == 'it_IT') { ?> selected="selected"<?php } ?> ><?php _e('italian','xili-dictionary'); ?></option>
            		<option value="es_ES" <?php if ($extend == 'es_ES') { ?> selected="selected"<?php } ?> ><?php _e('spanish','xili-dictionary'); ?></option>
            		<option value="fr_CA" <?php if ($extend == 'fr_CA') { ?> selected="selected"<?php } ?> ><?php _e('canadian','xili-dictionary'); ?></option>
	            	
	         
	     	<?php } else { 
	     	
	     			foreach ($listlanguages as $reflanguage) {
	     				echo '<option value="'.$reflanguage->name.'"'; 
	     				if ($extend == $reflanguage->name) { 
	     					echo ' selected="selected"';
	     				} 
	     				echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
	     			
	     			}
	     		} ?>
	     	</select></label>
	     	<br />&nbsp;<br />
			<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br />
		<?php
		} elseif ($action=='importcats' || $action=='erasedictionary' || $action=='importcurthemeterms') {
			?>
			
			<br />&nbsp;<br />
			<input class="button" type="submit" name="reset" value="<?php echo $cancel_text ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
			<input class="button-primary" type="submit" name="submit" value="<?php echo $submit_text ?>" /><br />
		<?php
	
			
		} else {
			//print_r($dictioline);
			
			/* rules for edit dictioline */
			$noedit = "" ; $noedited = "" ;
			if ($action=='edit' && $dictioline->parent == 0)   {
				$noedited = 'disabled="disabled"';
				$extend = "";
			} elseif ($action=='edit') {
			/* search default */
				$extend = substr($dictioline->slug,-5);
			} elseif ($action=='delete' && $dictioline->parent == 0) {	
				$noedit = 'disabled="disabled"';
				$extend = "";
			} elseif ($action=='delete') {
				$noedit = 'disabled="disabled"';
				$extend = substr($dictioline->slug,-5);
			}		
			?>
		<table class="editform" width="100%" cellspacing="2" cellpadding="5">
			<tr>
				<th scope="row" valign="top" align="right" width="25%"><label for="dictioline_description">
					<?php 
					if ($action=='edit' && $dictioline->parent == 0) {
						_e('Full msgid (original)','xili-dictionary'); 
					} elseif ($action=='edit') {
						_e('Full msgstr (translation)','xili-dictionary');
					} else {
						_e('Full msg (id or str)','xili-dictionary');
					} ?> :&nbsp;</label>
				</th>
				<td align="left">
				<?php if ($action=='edit') {
					 	if (false === strpos($dictioline->description,XPLURAL) || ''== $dictioline->description ) { ?>
							<textarea name="dictioline_description" id="dictioline_description" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $dictioline->description; ?></textarea>
					<?php } else {
			 				if  ($dictioline->parent == 0) {
			 					$original = explode(XPLURAL, $dictioline->description);
			 					?>
			 					<p><?php _e('Singular','xili-dictionary'); ?></p>
			 					<textarea name="dictioline_description_sin" id="dictioline_description_sin" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $original[0]; ?></textarea>
			 					<p><?php _e('Plural','xili-dictionary'); ?></p>
			 					<textarea name="dictioline_description_plu" id="dictioline_description_sin" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $original[1]; ?></textarea>
			 				<?php } else { ?>
			 					<p><?php _e('Separate plural texts by '.XPLURAL,'xili-dictionary'); ?></p>
			 					<textarea name="dictioline_description" id="dictioline_description" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $dictioline->description; ?></textarea>
			 				<?php }
						} 
					} else {
						?>	<p><?php _e('Separate plural texts by '.XPLURAL,'xili-dictionary'); ?></p>
							<textarea name="dictioline_description" id="dictioline_description" cols="50" rows="3"  <?php echo $noedit; ?> ><?php echo $dictioline->description; ?></textarea>
					<?php
					} ?>
				</td>
			
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top" align="right"><label for="dictioline_lang"><?php _e('Language','xili-dictionary') ?></label> :&nbsp;</th>
				<td>
	  				<select name="dictioline_lang" id="dictioline_lang"  <?php echo $noedit.$noedited;?> >
	  					<option value="" <?php if ($extend == '') { ?> selected="selected"<?php } ?>>
	  						<?php _e('default','xili-dictionary'); ?></option>
	  			<?php $listlanguages = get_terms(TAXONAME, array('hide_empty' => false,'slug' => $curlang));
	if (is_wp_error($listlanguages) || empty($listlanguages)) { ?> 
	  					<option value="fr_fr" <?php if ($extend == 'fr_fr') { ?> selected="selected"<?php } ?> ><?php _e('french','xili-dictionary'); ?></option>
            			<option value="en_us" <?php if ($extend == 'en_us') { ?> selected="selected"<?php } ?> ><?php _e('english','xili-dictionary'); ?></option>
            			<option value="de_de" <?php if ($extend == 'de_de') { ?> selected="selected"<?php } ?> ><?php _e('german','xili-dictionary'); ?></option>
            			<option value="it_it" <?php if ($extend == 'it_it') { ?> selected="selected"<?php } ?> ><?php _e('italian','xili-dictionary'); ?></option>
            			<option value="es_es" <?php if ($extend == 'es_es') { ?> selected="selected"<?php } ?> ><?php _e('spanish','xili-dictionary'); ?></option>
            			<option value="fr_ca" <?php if ($extend == 'fr_ca') { ?> selected="selected"<?php } ?> ><?php _e('canadian','xili-dictionary'); ?></option>
         
     		<?php } else {
     		foreach ($listlanguages as $reflanguage) {
     			echo '<option value="'.$reflanguage->slug.'"'; 
     			if ($extend == $reflanguage->slug) { 
     				echo ' selected="selected"';
     			} 
     			echo ">".__($reflanguage->description,'xili-dictionary').'</option>';	
     			
     		}
     	}
   	 ?>    
                	</select>
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
					 if ($action=='edit') {
					 	if ($dictioline->parent == 0) {
					 		_e('Original','xili-dictionary');
					 	} else {
					 		_e('Translation','xili-dictionary');
					 	}
					 } else {
					 	_e('Original or translation','xili-dictionary'); 
					 } 
					 ?></legend>
			  			<?php $this->xili_select_row($term_id,$dictioline); /* choice of parent line */?>
			  		</fielset>
             	</td>
			</tr>
			<tr>
				<th scope="row" valign="top" align="right"><label for="alias_of"><?php _e('Alias of','xili-dictionary') ?></label> :&nbsp;</th>
				<td><input name="alias_of" id="alias_of" type="text" value="" size="40" <?php echo $noedit; ?> /></td>
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
	
	function on_normal_3_content($data) { ?>
		<h4 id="manage_file"><?php _e('The files','xili-dictionary') ;?></h4>
	   	<?php 
	   	switch ($this->xililanguage) {
	   			case 'neveractive';
	   				echo '<p>'._e('xili-language plugin is not present !','xili-dictionary').'</p>';
	   				break;
	   			case 'wasactive';
	   				echo '<p>'._e('xili-language plugin is not activated !','xili-dictionary').'</p>';
	   				break;
	   			} 
	   	$linkstyle = "text-decoration:none; text-align:center; display:block; width:70%; margin:0px 1px 1px 30px; padding:4px 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;" ;
	   	$linkstyle1 = $linkstyle." border:1px #33f solid;";
	   	$linkstyle2 = $linkstyle." border:1px #ccc solid;";
	   	?>
	   		<a style="<?php echo $linkstyle1 ; ?>" href="?action=export&amp;page=dictionary_page" title="<?php _e('Create or Update mo file in current theme folder','xili-dictionary') ?>"><?php _e('Export mo file','xili-dictionary') ?></a>
	   	  	&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="?action=import&amp;page=dictionary_page" title="<?php _e('Import an existing .po file from current theme folder','xili-dictionary') ?>"><?php _e('Import po file','xili-dictionary') ?></a>
	   	  	&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="?action=exportpo&amp;page=dictionary_page" title="<?php _e('Create or Update po file in current theme folder','xili-dictionary') ?>"><?php _e('Export po file','xili-dictionary') ?></a>
	   	<h4 id="manage_categories"><?php _e('The categories','xili-dictionary') ;?></h4>
	   		<a style="<?php echo $linkstyle2 ; ?>" href="?action=importcats&amp;page=dictionary_page" title="<?php _e('Import name and description of categories','xili-dictionary') ?>"><?php _e('Import terms of categories','xili-dictionary') ?></a>
	   	<h4 id="manage_dictionary"><?php _e('Dictionary','xili-dictionary') ;?></h4>
   		<a style="<?php echo $linkstyle2 ; ?>" href="?action=erasedictionary&amp;page=dictionary_page" title="<?php _e('Erase all terms of dictionary ! (but not .mo or .po files)','xili-dictionary') ?>"><?php _e('Erase all terms','xili-dictionary') ?></a>
   		&nbsp;<br /><a style="<?php echo $linkstyle2 ; ?>" href="?action=importcurthemeterms&amp;page=dictionary_page" title="<?php _e('Import all terms from current theme files - alpha test -','xili-dictionary') ?>"><?php _e('Import all terms from current theme','xili-dictionary') ?></a>	
	
	<?php
	}
	/* @since 090423 - */
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
		  				if ($this->xililanguage == 'isactive') echo $this->build_grouplist();
		  				
		  				?>
		  	</select>			
		  	<br /> <p class="submit"><input type="submit" id="subselection" name="subselection" value="<?php _e('Sub select…','xili-dictionary'); ?>" /></p>
			</fieldset>
		<?php
	}
	
	/*
	 * build the list of group of languages for dictionary
	 *
	 *
	 *
	 */
	 function build_grouplist () {
	 	$listdictlanguages = get_terms(XDDICTLANGS, array('hide_empty' => false));
	 	foreach($listdictlanguages as $dictlanguage) {
	 		$checked = ($this->subselect == $dictlanguage->slug) ? 'selected="selected"' :'' ; 
		  				$optionlist .= '<option value="'.$dictlanguage->slug.'" '.$checked.' >'.__('Only:','xili-dictionary').' '.$dictlanguage->name.'</option>'; 
	 	}	
	 	return $optionlist;
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
	 		$found_term = get_term($found['term_id'],DTAXONAME);
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
		$this->langfolder = (defined('THEME_LANGS_FOLDER') && THEME_LANGS_FOLDER !='/')  ? '/'.str_replace("/","",THEME_LANGS_FOLDER).'/' : "/";
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
		if (isset($_GET['action'])) :
			$action=$_GET['action'];
			$term_id = $_GET['term_id'];
		endif;
		
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
		
		$message = $action." = " ;
	switch($action) {
		
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
			$termname = $_POST['dictioline_description']; /**/
			/*create the slug with term and add prefix*/
			$possible = true;
			if (''!=$_POST['dictioline_lang']) {
			  if ('no_parent' != $_POST['dictioline_parent'] ) {
				$parent_id = $_POST['dictioline_parent'];
				$parent_term = get_term($parent_id,DTAXONAME,OBJECT,'edit');
				$sslug = $parent_term->slug.'_'.$_POST['dictioline_lang'] ;
				
				$lang = $_POST['dictioline_lang'];
			  } else {
			  	$message .= __('No parent term defined','xili-dictionary');
			  	$possible = false;
			  }
			} else {
				$lang = '';
				/* is content plural */
				if (false === strpos($termname,XPLURAL)) {			
					$sslug = htmlentities($termname);
						
				} else {
					$plurals = explode (XPLURAL,$termname);
					$sslug = htmlentities($plurals[0]);	/* in term slug only first*/
				}	
			}
			$sslug = $this->test_and_create_slug($_POST['dictioline_description'], $sslug, $lang);	
			//echo '---'.$sslug;
			if ($possible) {
				$aliasof = $_POST['alias_of'];			
				$args = array('alias_of' => $aliasof, 'description' => $_POST['dictioline_description'], 'parent' => $_POST['dictioline_parent'], 'slug' => $sslug);
				
			    $rterm_id = $this->xili_dic_insert_term( $termname, DTAXONAME, $args);
			    
			    if (''!=$_POST['dictioline_lang']) {
				    if (is_wp_error($rterm_id)) {$message .= ' ---- error ---- '; $possible = false ;} else {
				    	wp_set_object_terms((int) $rterm_id['term_id'], $_POST['dictioline_lang'], XDDICTLANGS,false);	
				    }
			    } else {
			    	if (is_wp_error($rterm_id)) { $message .= ' ---- error ---- '; $possible = false ; } else { $message .= " (". $rterm_id['term_id'] .") "; }
			    }
			}
		    $actiontype = "add";
		    if ($possible) $message .= " - ".__('A new term was added.','xili-dictionary');
		     break;
		    
		case 'edit';
		    $actiontype = "edited";
		    
		    $dictioline = get_term($term_id,DTAXONAME,OBJECT,'edit');
		    $submit_text = __('Update &raquo;','xili-dictionary');
		    $formtitle = 'Edit term';
		    $message .= " - ".__('Term to update.','xili-dictionary');
		    break;
		    
		case 'edited';
		    $actiontype = "add";
		    $term = $_POST['dictioline_term_id'];
		    $termname = $_POST['dictioline_name']; 
		    $sslug = $_POST['dictioline_slug'];
			if (''!=$_POST['dictioline_lang']) {
				$parent_id = $_POST['dictioline_parent'];
				$parent_term = get_term($parent_id,DTAXONAME,OBJECT,'edit');
				$sslug = $parent_term->slug.'_'.$_POST['dictioline_lang'] ;
				$originalortrans = $_POST['dictioline_description'];
			} else {
				if (''!=$_POST['dictioline_description_sin']) {
				   $originalortrans = $_POST['dictioline_description_sin'].XPLURAL.$_POST['dictioline_description_plu'];
				} else {
				   $originalortrans = $_POST['dictioline_description'];
				}
			}
			
			$args = array('name'=>$termname, 'alias_of' => $_POST['alias_of'], 'description' => $originalortrans , 'parent' => $_POST['dictioline_parent'], 'slug' => $sslug);
		    
		    $this->xili_dic_update_term($term, DTAXONAME, $args);
		    
			if (''!=$_POST['dictioline_lang']) {
			    if (is_wp_error($rterm_id)) {$message .= ' ---- error ---- ';} else {
			    	wp_set_object_terms((int) $rterm_id['term_id'], $_POST['dictioline_lang'], XDDICTLANGS,false);	
			    }
		    }
			$message .= " - ".__('A term was updated.','xili-dictionary').' '.$_POST['dictioline_term_id'];
		    break;
		    
		case 'delete';
		    $actiontype = "deleting";
		    $submit_text = __('Delete &raquo;','xili-dictionary');
		    $formtitle = 'Delete term ?';
		    $dictioline = get_term($term_id,DTAXONAME,OBJECT,'edit');
		    
		    $message .= " - ".__('A term to delete. CLICK MENU DICTIONARY TO CANCEL !','xili-dictionary');
		    
		    break;
		    
		case 'deleting';
		    $actiontype = "add";
		    $term_id = $_POST['dictioline_term_id'];
		    wp_delete_object_term_relationships( $term_id, XDDICTLANGS ); 
		    wp_delete_term( $term_id, DTAXONAME, $args);
		    $message .= " - ".__('A term was deleted.','xili-dictionary');
		    $term_id = 0; /* 0.9.7.2 */
		    break;
		    
		case 'export';
			 $actiontype = "exporting";
			 $formtitle = __('Export mo file','xili-dictionary');
			 $formhow = __('To create a .mo file, choose language and click below.','xili-dictionary');
			 $submit_text = __('Export &raquo;','xili-dictionary');
		     break;
		case 'exporting';
			$actiontype = "add";
			$selectlang = $_POST['language_file'];
		     if ("" != $selectlang){
		     	//$this->xili_create_mo_file(strtolower($selectlang));
		     	$mo = $this->from_twin_to_POMO ($selectlang);
		     	if (false === $this->Save_MO_to_file ($selectlang , $mo )) {
		     		$message .= ' '.sprintf(__('error during exporting in  %1s.mo file.','xili-dictionary'),$selectlang);
		     	} else {
		     		$message .= ' '.sprintf(__('exported in %1s.mo file.','xili-dictionary'),$selectlang);
		     	}
		     }	else {
		     	$message .= ' : error "'.$selectlang.'"';
		     }	
		     break;
		     
		case 'exportpo';
			 $actiontype = "exportingpo";
			 $formtitle = __('Export po file','xili-dictionary');
			 $formhow = __('To export terms in a .po file, choose language and click below.','xili-dictionary');
			 $submit_text = __('Export &raquo;','xili-dictionary');
		     break;
		case 'exportingpo';
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
		case 'importing';
			$actiontype = "add";
		    $message .= ' '.__('line imported from po file: ','xili-dictionary');
		    $selectlang = $_POST['language_file'];
		    
		    $po = $this->pomo_import_PO ($selectlang); //print_r($po);
		    if (false !== $po ) $twintexts = $this->from_PO_to_twin ($po);
			//$twintexts = $this->xili_read_pofile ($readfile);
			if (is_array($twintexts)) {
				$nblines = $this->xili_import_in_tables($twintexts,$selectlang); 
				$message .= __('id lines = ','xili-dictionary').$nblines[0].' & ' .__('str lines = ','xili-dictionary').$nblines[1].' & ' .__('str lines up = ','xili-dictionary').$nblines[2];
			} else {
				
		    $readfile = get_template_directory().$this->langfolder.$selectlang.'.po';
				$message .= ' '.$readfile.__('po file is not present.','xili-dictionary');
			}	
		    break;
		    	
		case 'importcats';
			$actiontype = "importingcats";
		    $formtitle = __('Import terms of categories','xili-dictionary');
		    $formhow = __('To import terms of the current categories, click below.','xili-dictionary');
			$submit_text = __('Import category’s terms &raquo;','xili-dictionary'); 
		    break;
		
		case 'importingcats';
			$actiontype = "add";
		    $message .= ' '.__('terms imported from WP: ','xili-dictionary');
		    
		    $catterms = $this->xili_read_catsterms();
			
			if (is_array($catterms)) {
				$nbterms = $this->xili_importcatsterms_in_tables($catterms); 
				$message .= __('names = ','xili-dictionary').$nbterms[0].' & ' .__('descs = ','xili-dictionary').$nbterms[1];
			} else {
				$message .= ' '.$readfile.__('category’terms pbs!','xili-dictionary');
			}	
		    break;
		 case 'erasedictionary';
			$actiontype = "erasingdictionary";
		    $formtitle = __('Erase all terms','xili-dictionary');
		    $formhow = __('To erase terms of the dictionary, click below. (before, create a .po if necessary!)');
			$submit_text = __('Erase all terms &raquo;','xili-dictionary'); 
		    break;
		 case 'erasingdictionary';
			$actiontype = "add";
		    $message .= ' '.__('All terms erased !','xili-dictionary'); 
		    $listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false));
		    if (!empty($listdictiolines)) {
		    	foreach ($listdictiolines as $dictioline) {
		    		wp_delete_object_term_relationships( $dictioline->term_id, XDDICTLANGS );
		    		wp_delete_term($dictioline->term_id, DTAXONAME, $args);
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
		    $message .= ' '.__('All terms imported !','xili-dictionary');
		    	$themeterms = $this->scan_import_theme_terms(0);
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
		
		add_meta_box('xili-dictionary-normal-2', __('Multilingual Terms','xili-dictionary'), array(&$this,'on_normal_1_content'), $this->thehook , 'normal', 'core'); /* list of terms*/
		add_meta_box('xili-dictionary-normal-1', __($formtitle,'xili-dictionary'), array(&$this,'on_normal_2_content'), $this->thehook , 'normal', 'core'); /* input form */
		
		
		/* form datas in array for do_meta_boxes() */
		$data = array('message'=>$message,'messagepost'=>$messagepost,'action'=>$action, 'formtitle'=>$formtitle, 'dictioline'=>$dictioline,'submit_text'=>$submit_text,'cancel_text'=>$cancel_text, 'formhow'=>$formhow, 'orderby'=>$orderby,'term_id'=>$term_id, 'tagsnamesearch'=>$tagsnamesearch, 'tagsnamelike'=>$tagsnamelike);
		?>
		<div id="xili-dictionary-settings" class="wrap" style="min-width:850px">
			<?php screen_icon('tools'); ?>
			<h2><?php _e('Dictionary','xili-dictionary') ?></h2>
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
						 	
					<h4><a href="http://dev.xiligroup.com/xili-dictionary" title="Plugin page and docs" target="_blank" style="text-decoration:none" ><img style="vertical-align:middle" src="<?php echo WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/xilidico-logo-32.gif'; ?>" alt="xili-dictionary logo"/>  xili-dictionary</a> - © <a href="http://dev.xiligroup.com" target="_blank" title="<?php _e('Author'); ?>" >xiligroup.com</a>™ - msc 2007-9 - v. <?php echo XILIDICTIONARY_VER; ?></h4>
							
					</div>
				</div>
		</form>
		</div>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->thehook; ?>');
			});
			//]]>
		</script> 
		<?php	//end settings div 
		}
	
	/** * private function for admin page : select of parents */
		function xili_select_row($term_id=0,$editterm,$listby='name') {
		// select all terms if current term is new
			if ($term_id == 0) {
				$listterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => '0'));
				echo '<small>'.__('Choose the original term (msgid) to translate','xili-dictionary').'<br /></small>'; ?>
				<select name="dictioline_parent" id="dictioline_parent" style="width:100%;">
		  				<option value="no_parent" ><?php _e('no parent (= msgid)','xili-dictionary'); ?></option>
				<?php
				foreach ($listterms as $curterm) {
					echo '<option value="'.$curterm->term_id.'" >'.substr($curterm->slug,0,50).' ('.$curterm->term_id.') </option>';
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
						echo '<small>'.__('translated as','xili-dictionary').": </small>";
						echo '<ul>';
						foreach ($listterms as $curterm) { 
							$edit = "<a href='?action=edit&amp;page=dictionary_page&amp;term_id=".$curterm->term_id."' >".__( 'Edit' )."</a>";
							echo '<li value="'.$curterm->term_id.'" >'.$this->display_singular_or_plural ($curterm->description, true).' <small>'.$edit.'</small></li>';
						}
						echo '</ul>';
					} else {
						echo __('not yet translated','xili-dictionary')."<br /><br />";	
					}	
	     		} else {
	     			echo '<small>'.__('translation of','xili-dictionary').": </small>";
	     			$edit = "<a href='?action=edit&amp;page=dictionary_page&amp;term_id=".$editterm->parent."' >".__( 'Edit' )."</a>";
	     			$parent_term = get_term($editterm->parent,DTAXONAME,OBJECT,'edit');
	     			echo $this->display_singular_or_plural ($parent_term->description, true).' <small>'.$edit.'</small>'; ?>
	     			<input type="hidden" name="dictioline_parent" value="<?php echo $parent_term->term_id ?>" />	
	     	<?php }	
			}	
		}
	/** * private function for admin page : one line of taxonomy */
		
	function xili_dict_row($listby='name',$tagsnamelike='',$tagsnamesearch='') { 
		global $default_lang;	
		/*list of dictiolines*/
	
		if ($this->subselect == 'msgid' || $this->subselect == '') {
			$parentselect = '';
			if ($this->subselect == 'msgid') $parentselect = '0';
			$listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false,'orderby' => 	$listby,'get'=>'all','name__like'=>$tagsnamelike,'search'=>$tagsnamesearch, 'parent'=>$parentselect));
		} else {
			/*  */
			$group = is_term($this->subselect,XDDICTLANGS);
			$listdictiolines = get_terms_of_groups(array($group['term_id']),XDDICTLANGS,DTAXONAME, array('hide_empty' => false,'orderby' => 	$listby,'get'=>'all','name__like'=>$tagsnamelike,'search'=>$tagsnamesearch));
		}
		if (empty($listdictiolines) && $tagsnamelike=='' && $tagsnamesearch=='') : /*create a default line with the default language (as in config)*/
			$term = 'term';
			$args = array( 'alias_of' => '', 'description' => "term", 'parent' => 0, 'slug' =>'');
			wp_insert_term( $term, DTAXONAME, $args);
			$listdictiolines = get_terms(DTAXONAME, array('hide_empty' => false));
		endif;
		if (empty($listdictiolines)) {
			echo '<p>'.__('try another sub-selection !','xili-dictionary').'</p>';
		} else {
			$subselect = (($tagsnamelike=='') ? '' : '&amp;tagsnamelike='.$tagsnamelike);
			$subselect .= (($tagsnamesearch=='') ? '' : '&amp;tagsnamesearch='.$tagsnamesearch);
					
			foreach ($listdictiolines as $dictioline) {
				
				$class = (( defined( 'DOING_AJAX' ) && DOING_AJAX ) || " class='alternate'" == $class ) ? '' : " class='alternate'";
		
				$dictioline->count = number_format_i18n( $dictioline->count );
				$posts_count = ( $dictioline->count > 0 ) ? "<a href='edit.php?lang=$dictioline->term_id'>$dictioline->count</a>" : $dictioline->count;	
			
				$edit = "<a href='?action=edit&amp;page=dictionary_page".$subselect."&amp;term_id=".$dictioline->term_id."' >".__( 'Edit' )."</a></td>";	
				/* delete link */
				$edit .= "<td><a href='?action=delete&amp;page=dictionary_page".$subselect."&amp;term_id=".$dictioline->term_id."' class='delete'>".__( 'Delete' )."</a>";	
				/* modify to allow all html tags in msg str or id -  0.9.8.2*/
				$line="<tr id='cat-$dictioline->term_id'$class>
				<th scope='row' style='text-align: center'>$dictioline->term_id</th>
				
				<td>".$this->display_singular_or_plural($dictioline->description)."</td>
				
				<td>".$dictioline->slug."</td>
				<td align='center'>$dictioline->term_group</td>
				  
				<td>$edit</td>\n\t</tr>\n"; /*to complete*/
				echo $line;
			}	
		}
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
	 */
	function pomo_import_PO ($lang = "") {
		$po = new PO();
		$pofile = get_template_directory().$this->langfolder.$lang.'.po'; 
		if ( !$po->import_from_file( $pofile ) ) {
			return false;
		} else { 
			return $po;
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
		//print_r($twinlines);
		return $twinlines;
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
		$listterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => '')); //print_r($listterms);
			foreach ($listterms as $curterm) {
				if ($curterm->parent == 0) {		
					/* select child to create translated term */
					$listchildterms = get_terms(DTAXONAME, array('hide_empty' => false,'parent' => $curterm->term_id));
					foreach ($listchildterms as $curchildterm) {
						if (substr($curchildterm->slug,-5) == strtolower($curlang)) {
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
							}
						}
					}
				}		
			}
		//print_r ($mo);
		return $mo;
	}
	
	/**
	 * Save MO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 */	
	function Save_MO_to_file ($curlang , $mo )	{
		$filename = substr($curlang,0,3).strtoupper(substr($curlang,-2));
		$filename .= '.mo';
		$createfile = get_template_directory().$this->langfolder.$filename;
		if (false === $mo->export_to_file($createfile)) return false;
	}
	
	/**
	 * Save PO object to file
	 *
	 *
	 * @since 1.0 - only WP >= 2.8.4
	 */	
	function Save_PO_to_file ($curlang , $po )	{
		$filename = substr($curlang,0,3).strtoupper(substr($curlang,-2));
		$filename .= '.po';
		$createfile = get_template_directory().$this->langfolder.$filename;
		//print_r($po);
		if (false === $po->export_to_file($createfile)) return false;
	}
	/** 
	 * thanks to http://urbangiraffe.com/articles/translating-wordpress-themes-and-plugins/2/#plural_forms
	 * @since 1.0 - only WP >= 2.8
	 */	
	function plural_forms_rule($curlang) {	
		$curlang = substr($curlang,0,3).strtoupper(substr($curlang,-2));
		$rulesarrays = array(
		'nplurals=1; plural=0' => array('tr_TR','ja_JA'),
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
				$parent_term = get_term($insertid,DTAXONAME,OBJECT,'edit');
				 
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
					if (!is_wp_error($result)) wp_set_object_terms((int) $result['term_id'], strtolower($translang), XDDICTLANGS,false);
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
				$parent_term = get_term($cur_id['term_id'],DTAXONAME,OBJECT,'edit');
				
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
							wp_set_object_terms((int) $result['term_id'], strtolower($translang), XDDICTLANGS,false);
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
		return array($nbname,$nbdesc);
	}
	
		
	function scan_import_theme_terms($callback,$display) {
		$path = get_template_directory();
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
		  $path = rtrim(str_replace("\\", "/", $path), '/') . '/';
		  $matches = Array();
		  $entries = Array();
		  $dir = dir($path);
		  while (false !== ($entry = $dir->read())) {
		    $entries[] = $entry;
		  }
		  $dir->close();
		  foreach ($entries as $entry) {
		    $fullname = $path . $entry;
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
  		echo str_replace(".mo","",$filename ). " ("."/".str_replace("/","",str_replace(get_template_directory(),'',$path)).")<br />";
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
 * instantiation when xili-language is loaded
 */
function dictionary_start () {
	global $wp_version;
	if ($wp_version >= '2.8') $xili_dictionary = new xili_dictionary(); /* instantiation php4 for last century servers replace by =& */ 
}
add_action('plugins_loaded','dictionary_start'); 

/* © xiligroup dev 091102 18:00 */

?>