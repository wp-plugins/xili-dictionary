<?php
/**
 * XD extractor
 *
 * Contains the methods for extracting from files.
 * Code is based on: http://i18n.trac.wordpress.org/browser/tools/trunk/makepot.php
 * Thanks to Geert De Deckere from WooCommerce
 *
 * @since 2.9.0
 * @package xili-dictionary
 *
 */
class XD_extractor {

	/**
	 * @var string Filesystem directory path for the WooCommerce plugin (with trailing slash)
	 */
	public $xili_dictionary_path;

	/**
	 * @var array All available projects with their settings
	 */
	public $projects;

	/**
	 * @var object StringExtractor
	 */
	public $extractor;

	/**
	 * @var where to extract
	 */
	var $working_path;

	/**
	 * @var current text domain (future use if direct POT generation w/o inserting in XD)
	 */
	public $text_domain;

	/**
	 * @var array Rules for StringExtractor
	 */
	public $rules = array(
		'_'               => array( 'string' ),
		'__'              => array( 'string' ),
		'_e'              => array( 'string' ),
		'_c'              => array( 'string' ),
		'_n'              => array( 'singular', 'plural' ),
		'_n_noop'         => array( 'singular', 'plural' ),
		'_nc'             => array( 'singular', 'plural' ),
		'__ngettext'      => array( 'singular', 'plural' ),
		'__ngettext_noop' => array( 'singular', 'plural' ),
		'_x'              => array( 'string', 'context' ),
		'_ex'             => array( 'string', 'context' ),
		'_nx'             => array( 'singular', 'plural', null, 'context' ),
		'_nx_noop'        => array( 'singular', 'plural', 'context' ),
		'_n_js'           => array( 'singular', 'plural' ),
		'_nx_js'          => array( 'singular', 'plural', 'context' ),
		'esc_attr__'      => array( 'string' ),
		'esc_html__'      => array( 'string' ),
		'esc_attr_e'      => array( 'string' ),
		'esc_html_e'      => array( 'string' ),
		'esc_attr_x'      => array( 'string', 'context' ),
		'esc_html_x'      => array( 'string', 'context' ),
	);

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $projects = array() ) {
		global $xili_dictionary ;
		// Default path = current_theme
		$this->working_path = get_stylesheet_directory();
		$this->text_domain = 'default' ;

		// All available projects with their settings
		$this->projects = array_merge ( array(
			$this->text_domain => array(
				'title'    => 'default',
				'file'     => $this->working_path . '/languages/'. $this->text_domain .'.pot',
				'excludes' => array(),
				'includes' => array(),
				'working_path' => $this->working_path
				)
		), $projects ) ;

		// Ignore some strict standards notices caused by extract/extract.php
		error_reporting(E_ALL);

		// Load required files and objects
		require_once $xili_dictionary->plugin_path . '/includes/' . 'extract/extract-wp.php';
		$this->extractor = new StringExtractor( $this->rules );
	}


	/**
	 * POT generator
	 *
	 * @param string $project "woocommerce" or "woocommerce-admin"
	 * @return bool true on success, false on error
	 */
	public function generate_entries ( $project = '' ) {
		// Unknown project
		if ( !$project || empty( $this->projects[ $project ] ) )
			return false;

		// Project config
		$config = $this->projects[ $project ];

		// Extract translatable strings from the WooCommerce plugin
		$originals = $this->extractor->extract_from_directory( $config['working_path'], $config['excludes'], $config['includes'] );

		return $originals;
	}

	/**
	 * get_first_lines function.
	 *
	 * @access public
	 * @param mixed $filename
	 * @param int $lines (default: 30)
	 * @return string|bool
	 */
	public static function get_first_lines($filename, $lines = 30) {
		$extf = fopen($filename, 'r');
		if (!$extf) return false;
		$first_lines = '';
		foreach(range(1, $lines) as $x) {
			$line = fgets($extf);
			if (feof($extf)) break;
			if (false === $line) {
				return false;
			}
			$first_lines .= $line;
		}
		return $first_lines;
	}

	/**
	 * get_addon_header function.
	 *
	 * @access public
	 * @param mixed $header
	 * @param mixed &$source
	 * @return string|bool
	 */
	public static function get_addon_header($header, &$source) {
		if (preg_match('|'.$header.':(.*)$|mi', $source, $matches))
			return trim($matches[1]);
		else
			return false;
	}
}
