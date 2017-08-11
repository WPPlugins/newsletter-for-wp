<?php
require "wrapper.php";
/**
 * Takes care of requests to the NewsLetter API
 *
 * @access public
 * @uses WP_HTTP
 * @since 1.0
 */
class NL4WP_API {

	/**
	 * @var string The URL to the NewsLetter API
	 */
	protected $api_url = 'https://api.newsletter.com/2.0/';

	/**
	 * @var string The API key to use
	 */
	protected $api_key = '';

	/**
	 * @var string The error message of the latest API request (if any)
	 */
	protected $error_message = '';

	/**
	 * @var int The error code of the last API request (if any)
	 */
	protected $error_code = 0;

	/**
	 * @var boolean Boolean indicating whether the user is connected with NewsLetter
	 */
	protected $connected;

	/**
	 * @var object The full response object of the latest API call
	 */
	protected $last_response;

	/**
	 * Constructor
	 *
	 * @param string $api_key
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;

		$dash_position = strpos( $api_key, '-' );
		if( $dash_position !== false ) {
			$this->api_url = 'https://' . substr( $api_key, $dash_position + 1 ) . '.api.newsletter.com/2.0/';
		}
	}

	/**
	 * Show an error message to administrators
	 *
	 * @param string $message
	 *
	 * @return bool
	 */
	private function show_error( $message ) {

		if( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if( ! function_exists( 'add_settings_error' ) ) {
			return false;
		}

		add_settings_error( 'nl4wp-api', 'nl4wp-api-error', $message, 'error' );
		return true;
	}

	/**
	 * @param $message
	 *
	 * @return bool
	 */
	private function show_connection_error( $message ) {
		$message .= '<br /><br />' . sprintf( '<a href="%s">' . __( 'Read more about common connectivity issues.', 'newsletter-for-wp' ) . '</a>', 'https://nl4wp.com/kb/solving-connectivity-issues/#utm_source=wp-plugin&utm_medium=newsletter-for-wp&utm_campaign=settings-notice' );
		return $this->show_error( $message );
	}

	/**
	 * Pings the NewsLetter API to see if we're connected
	 *
	 * The result is cached to ensure a maximum of 1 API call per page load
	 *
	 * @return boolean
	 */
	public function is_connected() {

		if( is_bool( $this->connected ) ) {
			return $this->connected;
		}

		$result = $this->call( 'helper/ping' );
		$this->connected = false;

		if( is_object( $result ) ) {

			// Msg key set? All good then!
			if( ! empty( $result->msg ) ) {
				$this->connected = true;
				return true;
			}

			// Uh oh. We got an error back.
			if( isset( $result->error ) ) {
				$this->show_error( 'NewsLetter Error: ' . $result->error );
			}
		}

		return $this->connected;
	}

	/**
	 * Sends a subscription request to the NewsLetter API
	 *
	 * @param string $list_id The list id to subscribe to
	 * @param string $email The email address to subscribe
	 * @param array $merge_vars Array of extra merge variables
	 * @param string $email_type The email type to send to this email address. Possible values are `html` and `text`.
	 * @param boolean $double_optin Should this email be confirmed via double opt-in?
	 * @param boolean $update_existing Update information if this email is already on list?
	 * @param boolean $replace_interests Replace interest groupings, only if update_existing is true.
	 * @param boolean $send_welcome Send a welcome e-mail, only if double_optin is false.
	 *
	 * @return boolean|string True if success, 'error' if error
	 */
	public function subscribe($list_id, $email, array $merge_vars = array(), $email_type = 'html', $double_optin = true, $update_existing = false, $replace_interests = true, $send_welcome = false ) {
		$data = array(
			'id' => $list_id,
			'email' => array( 'email' => $email),
			'merge_vars' => $merge_vars,
			'email_type' => $email_type,
			'double_optin' => $double_optin,
			'update_existing' => $update_existing,
			'replace_interests' => $replace_interests,
			'send_welcome' => $send_welcome
		);

		$response = $this->call( 'lists/subscribe', $data );

		if( is_object( $response ) && isset( $response->email ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the Groupings for a given List
	 * @param int $list_id
	 * @return array|boolean
	 */
	public function get_list_groupings( $list_id ) {
		$result = $this->call( 'lists/interest-groupings', array( 'id' => $list_id ) );

		if( is_array( $result ) ) {
			return $result;
		}

		return false;
	}

	/**
	 * @param array $list_ids Array of ID's of the lists to fetch. (optional)
	 *
	 * @return bool
	 */
	public function get_lists( $list_ids = array() ) {
		$args = array(
			'limit' => 100,
			'sort_field' => 'web',
			'sort_dir' => 'ASC',
		);

		// set filter if the $list_ids parameter was set
		if( count( $list_ids ) > 0 ) {
			$args['filters'] = array(
				'list_id' => implode( ',', $list_ids )
			);
		}

		$result = $this->call( 'lists/list', $args );

		if( is_object( $result ) && isset( $result->data ) ) {
			return $result->data;
		}

		return false;
	}

	/**
	 * Get the lists an email address is subscribed to
	 *
	 * @param array|string $email
	 *
	 * @return array
	 */
	public function get_lists_for_email( $email ) {

		if( is_string( $email ) ) {
			$email = array(
				'email' => $email,
			);
		}

		$result = $this->call( 'helper/lists-for-email', array( 'email' => $email ) );

		if( ! is_array( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Get lists with their merge_vars for a given array of list id's
	 * @param array $list_ids
	 * @return array|boolean
	 */
	public function get_lists_with_merge_vars( $list_ids ) {
		$result = $this->call( 'lists/merge-vars', array('id' => $list_ids ) );

		if( is_object( $result ) && isset( $result->data ) ) {
			return $result->data;
		}

		return false;
	}

	/**
	 * Gets the member info for one or multiple emails on a list
	 *
	 * @param string $list_id
	 * @param array $emails
	 * @return array
	 */
	public function get_subscriber_info( $list_id, array $emails ) {

		if( is_string( $emails ) ) {
			$emails = array( $emails );
		}

		$result = $this->call( 'lists/member-info', array(
				'id' => $list_id,
				'emails'  => $emails
			)
		);

		if( is_object( $result ) && isset( $result->data ) ) {
			return $result->data;
		}

		return false;
	}

	/**
	 * Checks if an email address is on a given list
	 *
	 * @param string $list_id
	 * @param string $email
	 * @return boolean
	 */
	public function list_has_subscriber( $list_id, $email ) {
		$member_info = $this->get_subscriber_info( $list_id, array( array( 'email' => $email ) ) );

		if( is_array( $member_info ) && isset( $member_info[0] ) ) {
			return ( $member_info[0]->status === 'subscribed' );
		}

		return false;
	}

	/**
	 * @param        $list_id
	 * @param array|string $email
	 * @param array  $merge_vars
	 * @param string $email_type
	 * @param bool   $replace_interests
	 *
	 * @return bool
	 */
	public function update_subscriber( $list_id, $email, $merge_vars = array(), $email_type = 'html', $replace_interests = false ) {

		// default to using email for updating
		if( ! is_array( $email ) ) {
			$email = array(
				'email' => $email
			);
		}

		$result = $this->call( 'lists/update-member', array(
				'id' => $list_id,
				'email'  => $email,
				'merge_vars' => $merge_vars,
				'email_type' => $email_type,
				'replace_interests' => $replace_interests
			)
		);

		if( is_object( $result ) ) {

			if( isset( $result->error ) ) {
				return false;
			} else {
				return true;
			}

		}

		return false;
	}

	/**
	 * Unsubscribes the given email or luid from the given NewsLetter list
	 *
	 * @param string       $list_id
	 * @param array|string $struct
	 * @param bool         $delete_member
	 * @param bool         $send_goodbye
	 * @param bool         $send_notification
	 *
	 * @return bool
	 */
	public function unsubscribe( $list_id, $struct, $send_goodbye = true, $send_notification = false, $delete_member = false ) {

		if( ! is_array( $struct ) ) {
			// assume $struct is an email
			$struct = array(
				'email' => $struct
			);
		}

		$response = $this->call( 'lists/unsubscribe', array(
				'id' => $list_id,
				'email' => $struct,
				'delete_member' => $delete_member,
				'send_goodbye' => $send_goodbye,
				'send_notify' => $send_notification
			)
		);

		if( is_object( $response ) ) {

			if ( isset( $response->complete ) && $response->complete ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see https://apidocs.newsletter.com/api/2.0/ecomm/order-add.php
	 *
	 * @param array $order_data
	 *
	 * @return boolean
	 */
	public function add_ecommerce_order( array $order_data ) {
		$response = $this->call( 'ecomm/order-add', array( 'order' => $order_data ) );

		if( is_object( $response ) ) {

			// complete means success
			if ( isset( $response->complete ) && $response->complete ) {
				return true;
			}

			// 330 means order was already added: great
			if( isset( $response->code ) && $response->code == 330 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see https://apidocs.newsletter.com/api/2.0/ecomm/order-del.php
	 *
	 * @param string $store_id
	 * @param string $order_id
	 *
	 * @return bool
	 */
	public function delete_ecommerce_order( $store_id, $order_id ) {

		$data = array(
			'store_id' => $store_id,
			'order_id' => $order_id
		);

		$response = $this->call( 'ecomm/order-del', $data );

		if( is_object( $response ) ) {
			if ( isset( $response->complete ) && $response->complete ) {
				return true;
			}

			// Invalid order (order not existing). Good!
			if( isset( $response->code ) && $response->code == 330 ) {
				return true;
			}
		}

		return false;
	}



	/**
	 * Calls the NewsLetter API
	 *
	 * @uses WP_HTTP
	 *
	 * @param string $method
	 * @param array $data
	 *
	 * @return object
	 */
	public function call( $method, array $data = array() ) {

		$this->empty_last_response();

		// do not make request when no api key was provided.
		if( empty( $this->api_key ) ) {
			return false;
		}

		// do not make request if helper/ping failed already
		if( $this->connected === false ) {
			return false;
		}

		//$data['apikey'] = $this->api_key;
//print_r($method);
		switch ($method) {
			case 'helper/ping':
						
				voxmail_init( $this->api_key);
				$result=voxmail_info();
				
				if ($result) 
					$data=(object) array('msg'=>'ok');
				else
					$data=(object) array('err'=>voxmail_errormessage());
			break;
			case 'lists/list':
				
				$groups= voxmail_audience_list();
				$result=voxmail_info();
				print_r($result);
				$groups= array_filter($groups, function($v) {
		    				return $v["handler"] == "servicesubscription"; //visibility register??
							});
				$list=(object) array(
					'id'=>1,
					'name'=>'VOXmail Contatti',
					'web_id'=>'vox',
					'stats'=>(object) array(
						'member_count'=>voxmail_user_count(),
						'grouping_count'=>count($groups)
					)
				);
				
				$data =(object) array('data'=> array($list));
				$response = 1;
			break;
			case 'lists/interest-groupings':
					$groups= voxmail_audience_list();
					$groups= array_filter($groups, function($v) {
			    				return $v["visibility"] == "register"; //visibility register??
								});
				
					$data = array();
					$interests = array();
					foreach ($groups as $group) {
						$interests[] = (object) array(
						                		"id" => $group["aid"],
						                		"name" => $group["caption"]
						    				);	
					}

					$data[] = (object) array (
							"id" => 10,
			        		"name" => "Gruppi di Interesse",
			        		"form_field" => "checkboxes",
			        		"groups" => $interests
			        );
					$result=1;


			default:
				$this->error_code=999;
				$this->error_message='Metodo non supportato';
			break;

		}
		


		//$response = wp_remote_post( $url, $request_args );

		// test for wp errors
		if( is_wp_error( $response ) ) {
			// show error message to admins
			$this->show_connection_error( "Error connecting to NewsLetter: " . $response->get_error_message() );
			return false;
		}



		// decode response body
		//$body = wp_remote_retrieve_body( $response );
		//$data = json_decode( $body );

		/*
		if( is_null( $data ) ) {

			$code = (int) wp_remote_retrieve_response_code( $response );
			if( $code !== 200 ) {
				$message = sprintf( 'The NewsLetter API server returned the following response: <em>%s %s</em>.', $code, wp_remote_retrieve_response_message( $response ) );

				// check for Akamai firewall response
				if( $code === 403 ) {
					preg_match('/Reference (.*)/', $body, $matches );

					if( ! empty( $matches[1] ) ) {
						$message .= '</strong><br /><br />' . sprintf( 'This usually means that your server is blacklisted by NewsLetter\'s firewall. Please contact NewsLetter support with the following reference number: %s </strong>', $matches[1] );
					}
				}

				$this->show_connection_error( $message );
			}

			return false;
		}
		*/
		// store response
		if ($result==0)
		{
				$this->error_message = voxmail_errormessage();
				$this->error_code = (int) voxmail_errorcode();
		}
		if( is_object( $data ) ) {
			$this->last_response = $data;


			return $data;
		}

		return $data;
	}

	/**
	 * Checks if an error occured in the most recent request
	 * @return boolean
	 */
	public function has_error() {
		return ( ! empty( $this->error_message ) );
	}

	/**
	 * Gets the most recent error message
	 * @return string
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Gets the most recent error code
	 *
	 * @return int
	 */
	public function get_error_code() {
		return $this->error_code;
	}

	/**
	 * Get the most recent response object
	 *
	 * @return object
	 */
	public function get_last_response() {
		return $this->last_response;
	}

	/**
	 * Empties all data from previous response
	 */
	private function empty_last_response() {
		$this->last_response = null;
		$this->error_code = 0;
		$this->error_message = '';
	}

	/**
	 * Get the request headers to send to the NewsLetter API
	 *
	 * @return array
	 */
	private function get_headers() {

		$headers = array(
			'Accept' => 'application/json'
		);

		// Copy Accept-Language from browser headers
		if( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$headers['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		return $headers;
	}
}
