<?php
/**
 * Handel bounce functionality
 * @since      1.0.0
 * @package    Bounce Handler MailPoet
 * @subpackage bounce-handler-mailpoet/includes
 * @author     Tikweb <kasper@tikjob.dk>
 */

if(!class_exists('Mailpoet_Handle_Bounces')){

	class Mailpoet_Handle_Bounces
	{
		/**
		 * Properties
		 */
		protected $bounce_form_data;

		/**
		 * Initialize the class
		 */
		public static function init()
		{
			$_this_class = new Mailpoet_Handle_Bounces();
			return $_this_class;
		}

		/**
		 * Constructor
		 */
		public function __construct()
		{
			
			// Ajax request
			add_action('wp_ajax_mbh_check_bounce_connection', array($this, 'mbh_check_bounce_connection')); // Check bounce connection
		}

		/**
		 * Check bounce connection
		 */
		public function mbh_check_bounce_connection()
		{
			header('Content-Type: application/json');
			$return_data = array();
			if(isset($_POST['data']['bounce']) && is_array($_POST['data']['bounce'])){
				$bounce = $_POST['data']['bounce'];
				$this->bounce_form_data = $bounce;
				if($bounce['connection_method'] == 'pear'){ // Check connection method
					$return_data = $this->pear_connection_check();
				}else{
					$return_data = $this->imap_connection_check();
				} // End if

			}else{
				$return_data['error'] = true;
				$return_data['success'] = false;
				$return_data['result'] = array(
					$this->__('Please fill out all the fields.')
				);
			} // End if
			echo wp_json_encode($return_data);
			wp_die();
		}

		/**
		 * With text domain
		 * @return translated text
		 */
		public function __($text)
		{
			return __($text, 'bounce-handler-mailpoet');
		}

		/**
		 * Check connection for pop3, imap, nntp
		 */
		public function imap_connection_check()
		{
			$return_data = array();
			if(!extension_loaded('imap') || !function_exists('imap_open')){
				return $this->imap_not_loaded();
			}

			imap_timeout(IMAP_OPENTIMEOUT, 20); //Set the time out
			//Port
			$port = isset($this->bounce_form_data['port']) ? $this->bounce_form_data['port'] : '';
	        // Check secure connection
	        if(isset($this->bounce_form_data['secure_connection']) && $this->bounce_form_data['secure_connection'] == '1'){
				$secure = 'ssl';
        	}else{
        		$secure = '';
        	}
        	// Check self signed certificates
        	if(isset($this->bounce_form_data['self_signed_certificates']) && $this->bounce_form_data['self_signed_certificates'] == '1'){
				$selfsigned = 1;
        	}else{
        		$selfsigned = 0;
        	}
        	//Connection method
	        $protocol = $this->bounce_form_data['connection_method'];
	        $serverName = '{' . $this->bounce_form_data['hostname'];

	        //If port empty
	        if(empty($port)){
	            if ($secure == 'ssl' && $protocol == 'imap')
	                $port = '993';
	            elseif ($protocol == 'imap')
	                $port = '143';
	            elseif ($protocol == 'pop3')
	                $port = '110';
	        }

	        $serverName .= ':' . $port; // Set port with server
	        
	        //if secure
	        if(!empty($secure)) $serverName .= '/' . $secure;

	        //if self signed
	        if($selfsigned) $serverName .= '/novalidate-cert';

	        if(!empty($protocol)) $serverName .='/service=' . $protocol; // Set connection method
        	$serverName .= '}'; //End server name

        	$login = trim($this->bounce_form_data['login']);
	        $password = trim($this->bounce_form_data['password']);
        	$mailbox = imap_open($serverName, $login, $password);
        	// If connection problem;
        	if(!$mailbox){
        		$return_data['error'] = true;
				$return_data['success'] = false;
            	$return_data['result'][] = sprintf($this->__('Error connecting to %s'), $serverName);
            	$alerts = imap_alerts();
            	$errors = imap_errors();
            	if($alerts) $return_data['result'][] = $alerts;
            	if($errors) $return_data['result'][] = $errors;
            	imap_close($mailbox);
            	return $return_data;
            }
            //If connect successfully
            $return_data['success'] = true;
        	$return_data['error'] = false;
        	$return_data['result'][] = sprintf($this->__('Successfully connected to %s'), $login);
        	$msg_num = imap_num_msg($mailbox);
        	$return_data['total'] = $msg_num;
        	if(empty($msg_num)){
        		$return_data['result'][] = $this->__('There are no bounced messages to process right now!');
        	}else{
            	$return_data['result'][] = sprintf($this->__('There are %s messages in your mailbox'), $msg_num);
            }
            
            imap_close($mailbox);
			return $return_data;
		}

		/**
		 * If imap extension not found
		 */
		public function imap_not_loaded()
		{
			$return_data = array();
			$return_data['error'] = true;
			$return_data['success'] = false;
			$prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
    		$extension = $prefix . 'imap.' . PHP_SHLIB_SUFFIX;
			$err_msg = $this->__('The extension %s could not be loaded, please change your PHP configuration to enable it or use the pop3 method without imap extension');
			$return_data['result'][] = sprintf($err_msg, $extension);
			return $return_data;
		}

		/**
		 * Check connection for pear
		 */
		public function pear_connection_check()
		{
			$return_data = array();
			// Include the pear library
			require_once MBH_ROOT_PATH . '/includes/pear/pop3.php';
			$net_pop3 = new Net_POP3();
			$net_pop3->setTimeOut(5);

			//Get port
			$port = intval($this->bounce_form_data['port']);
        	if(empty($port)) $port = '110/pop3/notls';
        	$hostname = $this->bounce_form_data['hostname'];
        	if(isset($this->bounce_form_data['secure_connection']) && $this->bounce_form_data['secure_connection'] == '1'){
				$secure = 'ssl';
        	}else{
        		$secure = '';
        	}
        	//We don't add back the ssl:// or tls:// if it's already there
        	if(!empty($secure) && !strpos($hostname, '://')){
        		$server_name = $secure . '://' . $hostname;
        	}
            $pear_connect = $net_pop3->connect($server_name, $port);
            //If unable to connect
            if( !$pear_connect ){
	            $return_data['error'] = true;
				$return_data['success'] = false;
				$err_msg = $this->__('Error connecting to the server %s : %s');
            	$return_data['result'][] = sprintf($err_msg, $hostname, $port);
            	$net_pop3->disconnect();
            	return $return_data;
	        }
	        $login = trim($this->bounce_form_data['login']);
	        $password = trim($this->bounce_form_data['password']);

	        $pear_login = $net_pop3->login($login, $password, 'USER');
	        if($pear_login === true && !is_a($pear_login, 'PEAR_Error')){
	        	
	        	$return_data['success'] = true;
	        	$return_data['error'] = false;
	        	$return_data['result'][] = sprintf($this->__('Successfully connected to %s'), $login);
	        	$msg_num = $net_pop3->numMsg();
	        	if(empty($msg_num)){
	        		$return_data['result'][] = $this->__('There are no bounced messages to process right now!');
	        	}else{
                	$return_data['result'][] = sprintf($this->__('There are %s messages in your mailbox'), $msg_num);
                }

	        }else{ //If unable to login
	            $return_data['error'] = true;
				$return_data['success'] = false;
            	$return_data['result'][] = sprintf($this->__('Identication error %s : %s'), $login, $password);
            	$return_data['result'][] = $pear_login->message;
	        }
	        $net_pop3->disconnect();
			return $return_data;
		}



	} // End of class

	/**
	 * Instentiate bounce handel class
	 */
	Mailpoet_Handle_Bounces::init();
}