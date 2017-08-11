<?php

/**
* This class takes care of all form related functionality
 *
 * @access private
 * @ignore
*/
class NL4WP_Form_Asset_Manager {

	/**
	 * @var NL4WP_Form_Output_Manager
	 */
	protected $output_manager;

	/**
	 * @var bool
	 */
	protected $scripts_loaded = false;

	/**
	 * @var string
	 */
	public $filename_suffix;

	/**
	 * Constructor
	 *
	 * @param NL4WP_Form_Output_Manager $output_manager
	 */
	public function __construct( NL4WP_Form_Output_Manager $output_manager ) {
		$this->output_manager = $output_manager;
		$this->filename_suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	}

	/**
	 * Init all form related functionality
	 */
	public function initialize() {
		$this->register_assets();
		$this->add_hooks();
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		// load checkbox css if necessary
		add_action( 'wp_enqueue_scripts', array( $this, 'load_stylesheets' ) );
		add_action( 'nl4wp_output_form', array( $this, 'load_scripts' ) );
		add_action( 'wp_footer', array( $this, 'print_javascript' ), 999 );
	}

	/**
	 * Register the various JS files used by the plugin
	 */
	public function register_assets() {
		global $wp_scripts;

		$suffix = $this->filename_suffix;

		// register client-side API script
		wp_register_script( 'nl4wp-forms-api', NL4WP_PLUGIN_URL . 'assets/js/forms-api'. $suffix .'.js', array(), NL4WP_VERSION, true );

		// register placeholder script, which will later be enqueued for IE only
		wp_register_script( 'nl4wp-placeholders', NL4WP_PLUGIN_URL . 'assets/js/third-party/placeholders.min.js', array(), NL4WP_VERSION, true );
		$wp_scripts->add_data( 'nl4wp-placeholders', 'conditional', 'lte IE 9' );

		// register stylesheets
		$stylesheets = array(
			'basic',
			'themes'
		);
		foreach( $stylesheets as $stylesheet ) {
			wp_register_style( 'nl4wp-form-' . $stylesheet, NL4WP_PLUGIN_URL . 'assets/css/form-' . $stylesheet .$suffix . '.css', array(), NL4WP_VERSION );
		}

		/**
		 * Runs right after all assets (scripts & stylesheets) for forms have been registered
		 *
		 * @since 3.0
		 *
		 * @param string $suffix The suffix to add to the filename, before the file extension. Is usually set to ".min".
		 * @ignore
		 */
		do_action( 'nl4wp_register_form_assets', $suffix );
	}

	/**
	 * Load the various stylesheets
	 */
	public function load_stylesheets( ) {

		$stylesheets = (array) get_option( 'nl4wp_form_stylesheets', array() );

		/**
		 * Filters the stylesheets to be loaded
		 *
		 * Should be an array of stylesheet handles previously registered using `wp_register_style`.
		 * Each value is prefixed with `nl4wp-form-` to get the handle.
		 *
		 * Return an empty array if you want to disable the loading of all stylesheets.
		 *
		 * @since 3.0
		 * @param array $stylesheets Array of valid stylesheet handles
		 */
		$stylesheets = (array) apply_filters( 'nl4wp_form_stylesheets', $stylesheets );

		foreach( $stylesheets as $stylesheet ) {
			$handle = 'nl4wp-form-' . $stylesheet;
			// TODO: check if stylesheet handle is registered?
			wp_enqueue_style( $handle );
		}

		/**
		 * @ignore
		 */
		do_action( 'nl4wp_load_form_stylesheets', $stylesheets );

		return true;
	}

	/**
	 * Get configuration object for client-side use.
	 *
	 * @return array
	 */
	public function get_javascript_config() {

		$submitted_form = nl4wp_get_submitted_form();

		if( ! $submitted_form ) {
			return array();
		}

		$config = array(
			'submitted_form' => array(
				'id' => $submitted_form->ID,
				'data' => $submitted_form->data,
				'action' => $submitted_form->config['action'],
				'element_id' => $submitted_form->config['element_id'],
			)
		);

		if( $submitted_form->has_errors() ) {
			$config['submitted_form']['errors'] = $submitted_form->errors;
		}

		$auto_scroll = 'default';

		/**
		 * Filters the `auto_scroll` setting for when a form is submitted.
		 *
		 * Accepts the following  values:
		 *
		 * - false
		 * - "default"
		 * - "animated"
		 *
		 * @param boolean|string $auto_scroll
		 * @since 3.0
		 */
		$config['auto_scroll'] = apply_filters( 'nl4wp_form_auto_scroll', $auto_scroll );

		return $config;
	}

	/**
	 * Load JavaScript files
	 * @return bool
	 */
	public function load_scripts() {

		if( $this->scripts_loaded ) {
			return false;
		}

		// print dummy JS
		$this->print_dummy_javascript();

		// load API script
		wp_localize_script( 'nl4wp-forms-api', 'nl4wp_forms_config', $this->get_javascript_config() );
		wp_enqueue_script( 'nl4wp-forms-api' );

		// load placeholder polyfill if browser is Internet Explorer
		if( ! empty( $GLOBALS['is_IE'] ) ) {
			wp_enqueue_script( 'nl4wp-placeholders' );
		}

		$this->scripts_loaded = true;
		return true;
	}

	/**
	 * Prints dummy JavaScript which allows people to call `nl4wp.forms.on()` before the JS is loaded.
	 */
	public function print_dummy_javascript() {
		$file = NL4WP_PLUGIN_DIR . "assets/js/forms-dummy-api{$this->filename_suffix}.js";
		echo '<script type="text/javascript">';
		include $file;
		echo '</script>';
	}

	/**
	* Returns the NewsLetter for WP form mark-up
	*
	* @return string
	*/
	public function print_javascript() {

		// don't print any scripts if this page has no forms
		if( empty( $this->output_manager->printed_forms ) ) {
			return false;
		}

		// make sure scripts are loaded
		$this->load_scripts();

		// print inline scripts depending on printed fields
		echo '<script type="text/javascript">';
		echo '(function() {';

		// include general form enhancements
		include  dirname( __FILE__ ) . '/views/js/general-form-enhancements.js';

		// include url fix
		if( in_array( 'url', $this->output_manager->printed_field_types ) ) {
			include dirname( __FILE__ ) . '/views/js/url-fields.js';
		}

		// include date polyfill?
		if( in_array( 'date', $this->output_manager->printed_field_types ) ) {
			include dirname( __FILE__ ) . '/views/js/date-fields.js';
		}

		echo '})();';
		echo '</script>';

		/**
		 * Runs right after inline JavaScript is printed, just before the closing </body> tag.
		 *
		 * This function will only run if the current page contains at least one form.
		 *
		 * @ignore
		 */
		do_action( 'nl4wp_print_forms_javascript' );
	}


}
