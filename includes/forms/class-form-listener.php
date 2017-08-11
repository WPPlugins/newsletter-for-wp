<?php

/**
 * Class NL4WP_Form_Listener
 *
 * @since 3.0
 * @access private
 * @ignore
 */
class NL4WP_Form_Listener {

	/**
	 * @var NL4WP_Form The submitted form instance
	 */
	public $submitted_form;

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Listen for submitted forms
	 *
	 * @param $data
	 * @return bool
	 */
	public function listen( array $data ) {

		if( ! isset( $data['_nl4wp_form_id'] ) ) {
			return false;
		}

		/**
		 * @var NL4WP_Request $request
		 */
		$request = nl4wp('request');

		try {
			$form = nl4wp_get_form( $request->params->get( '_nl4wp_form_id' ) );
		} catch( Exception $e ) {
			return false;
		}

		// where the magic happens
		$form->handle_request( $request );
		$form->validate();

		// store submitted form
		$this->submitted_form = $form;

		// did form have errors?
		if( ! $form->has_errors() ) {

			// form was valid, do something
			$method = 'process_' . $form->get_action() . '_form';
			call_user_func( array( $this, $method ), $form );
		} else {
			$this->get_log()->info( sprintf( "Form %d > Submitted with errors: %s", $form->ID, join( ', ', $form->errors ) ) );
		}

		$this->respond( $form );

		return true;
	}

	/**
	 * Process a subscribe form.
	 *
	 * @param NL4WP_Form $form
	 */
	public function process_subscribe_form( NL4WP_Form $form ) {
		$api = $this->get_api();
		$result = false;
		$email_type = $form->get_email_type();
		$merge_vars = $form->data;

		/**
		 * Filters merge vars which are sent to NewsLetter, only fires for form requests.
		 *
		 * @param array $merge_vars
		 * @param NL4WP_Form $form
		 */
		$merge_vars = (array) apply_filters( 'nl4wp_form_merge_vars', $merge_vars, $form );

		// create a map of all lists with list-specific merge vars
		$map = new NL4WP_Field_Map( $merge_vars, $form->get_lists() );

		// loop through lists
		foreach( $map->list_fields as $list_id => $merge_vars ) {
			// send a subscribe request to NewsLetter for each list
			$result = $api->subscribe( $list_id, $form->data['EMAIL'], $merge_vars, $email_type, $form->settings['double_optin'], $form->settings['update_existing'], $form->settings['replace_interests'], $form->settings['send_welcome'] );
		}

		// do stuff on failure
		if( ! $result ) {

			if( $api->get_error_code() == 214 ) {
				// handle "already_subscribed" as a soft-error
				$form->errors[] = 'already_subscribed';
				$this->get_log()->warning( sprintf( "Form %d > %s is already subscribed to the selected list(s)", $form->ID, $form->data['EMAIL'] ) );
			} else {
				// log error
				$this->get_log()->error( sprintf( 'Form %d > NewsLetter API error: %s', $form->ID, $api->get_error_message() ) );

				// add error code to form object
				$form->errors[] = 'error';
			}

			return;
		}

		$this->get_log()->info( sprintf( "Form %d > Successfully subscribed %s", $form->ID, $form->data['EMAIL'] ) );

		/**
		 * Fires right after a form was used to subscribe.
		 *
		 * @since 3.0
		 *
		 * @param NL4WP_Form $form Instance of the submitted form
		 */
		do_action( 'nl4wp_form_subscribed', $form, $map->formatted_data, $map->pretty_data );
	}

	/**
	 * @param NL4WP_Form $form
	 */
	public function process_unsubscribe_form( NL4WP_Form $form ) {
		$api = $this->get_api();
		$result = null;

		foreach( $form->get_lists() as $list_id ) {
			$result = $api->unsubscribe( $list_id, $form->data['EMAIL'] );
		}

		if( ! $result ) {
			// not subscribed is a soft-error
			if( in_array( $api->get_error_code(), array( 215, 232 ) ) ) {
				$form->add_error( 'not_subscribed' );
				$this->get_log()->info( sprintf( 'Form %d > %s is not subscribed to the selected list(s)', $form->ID, $form->data['EMAIL'] ) );
			} else {
				$form->add_error( 'error' );
				$this->get_log()->error( sprintf( 'Form %d > NewsLetter API error: %s', $form->ID, $api->get_error_message() ) );
			}
		}

		/**
		 * Fires right after a form was used to unsubscribe.
		 *
		 * @since 3.0
		 *
		 * @param NL4WP_Form $form Instance of the submitted form.
		 */
		do_action( 'nl4wp_form_unsubscribed', $form );
	}

	/**
	 * @param NL4WP_Form $form
	 */
	public function respond( NL4WP_Form $form ) {

		$success = ! $form->has_errors();

		if( $success ) {

			/**
			 * Fires right after a form is submitted without any errors (success).
			 *
			 * @since 3.0
			 *
			 * @param NL4WP_Form $form Instance of the submitted form
			 */
			do_action( 'nl4wp_form_success', $form );

		} else {

			/**
			 * Fires right after a form is submitted with errors.
			 *
			 * @since 3.0
			 *
			 * @param NL4WP_Form $form The submitted form instance.
			 */
			do_action( 'nl4wp_form_error', $form );

			// fire a dedicated event for each error
			foreach( $form->errors as $error ) {

				/**
				 * Fires right after a form was submitted with errors.
				 *
				 * The dynamic portion of the hook, `$error`, refers to the error that occurred.
				 *
				 * Default errors give us the following possible hooks:
				 *
				 * - nl4wp_form_error_error                     General errors
				 * - nl4wp_form_error_spam
				 * - nl4wp_form_error_invalid_email             Invalid email address
				 * - nl4wp_form_error_already_subscribed        Email is already on selected list(s)
				 * - nl4wp_form_error_required_field_missing    One or more required fields are missing
				 * - nl4wp_form_error_no_lists_selected         No NewsLetter lists were selected
				 *
				 * @since 3.0
				 *
				 * @param   NL4WP_Form     $form        The form instance of the submitted form.
				 */
				do_action( 'nl4wp_form_error_' . $error, $form );
			}

		}

		/**
		 * Fires right before responding to the form request.
		 *
		 * @since 3.0
		 *
		 * @param NL4WP_Form $form Instance of the submitted form.
		 */
		do_action( 'nl4wp_form_respond', $form );

		// do stuff on success (non-AJAX only)
		if( $success && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

			// do we want to redirect?
			$redirect_url = $form->get_redirect_url();
			if ( ! empty( $redirect_url ) ) {
				wp_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * @return NL4WP_API
	 */
	protected function get_api() {
		return nl4wp('api');
	}

	/**
	 * @return NL4WP_Debug_Log
	 */
	protected function get_log() {
		return nl4wp('log');
	}

}