<?php

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscription;
use MailPoet\Mailer\Mailer;

class BounceDetect {

	public $settings;
	public $mailbox;
	public $emails;
	public $procMail;
	public $croned;
	public $detectEmail = '/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@([a-z0-9\-]+\.)+[a-z0-9]{2,8}/i';
	public $rules = [
			'status' => 'Status:(\s?\d\.\d\.\d)',
			'action' => 'Action:\s*?(.+)$',
			'rcpt'	 => '\w*-Recipient:\s*?rfc\d*\;\s*?(.+)$'
		];

	/**
	 * If call is using cron job , we will not buffer output.
	 * @param boolean $croned [description]
	 */
	public function __construct($croned=false){
		$this->croned = $croned;
	}

	/**
	 * decode encodeded string like unicode
	 * @param  string $input string
	 * @return string        return as decoded text
	 */
	public function decodeHeader($input) {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
        $this->charset = false;

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded = $matches[1];
            $charset = $matches[2];
            $encoding = $matches[3];
            $text = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach ($matches[1] as $value)
                        $text = str_replace('=' . $value, chr(hexdec($value)), $text);
                    break;
            }
            $this->charset = $charset;
            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }

    /**
     * imap connect, if error than return false and exit.
     * @return void 
     */
	public function connect(){
		imap_timeout(IMAP_OPENTIMEOUT,120);
		$this->mailbox = imap_open($this->servername, $this->settings->login, mbh_encrypt_decrypt('decrypt',$this->settings->password));

		$error = imap_last_error();

		if ( $error ){
			echo $error;
			exit();
		}
	}

	/**
	 * Bounce checking main function
	 */
	public function checkBounce(){

		@ini_set('max_execution_time', 1200); // Increase max execution time, if mail server is dirty it can handle. This setting could be useful during cronjob operation. 

		$this->getEssential();
		$this->connect();

		$totalMsg = imap_num_msg($this->mailbox);

		if ( !$totalMsg ){
			_e("Your inbox seems empty!", 'bounce-handler-mailpoet');
			return false;
		}

		if ( $totalMsg > 100 ){
			$totalMsg = 100;
		}

		ob_start();
		echo __("Processing", 'bounce-handler-mailpoet')." : <span id='incr'>0</span>/$totalMsg <br/>";

		for ( $email=1;$email < $totalMsg+1;$email++ ) {

			$overview = imap_fetch_overview($this->mailbox,$email,0);
			$body = imap_body($this->mailbox, $email, 0);
			$parsed_header = imap_headerinfo($this->mailbox, $email);
			$reason = $this->getBouncedCode($body);
			$mail = $this->getBouncedEmail($body,$parsed_header);
			$this->procMail = imap_fetchheader($this->mailbox,$email,0)."\n\n- - -\n\n".$body;
			
			// logger
			MBH_Logger::insert(array(
				'email' => @$mail,
				'reason' => @$reason['action'],
				'last_checked' => MBH_Logger::now()
				));

			// browser output stream
			$resp = "<html>";
			$resp .= "<head>";
			$resp .= "<style>body{font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif;}</style>";
			$resp .= "</head>";
			$resp .= "<body>";
			$resp .= "<div class='subject'><strong>$email. Subject : ".$this->decodeHeader($overview[0]->subject)."</strong><br/></div>";
			$resp .= "<div class='message'>";
			$resp .= $this->doAction(@$mail,@$reason['action']);
			$resp .= $this->deleteMsg($this->mailbox,$email);
			$resp .= "</div>";
			$resp .= "<script>";
			$resp .= "var counter = document.getElementById('incr');";
			$resp .= "function setCounter(val){ counter.innerHTML=val;}";
			$resp .= "</script>";

			$resp .= "<script>";
			$resp .= "setCounter($email);";
			$resp .= "</script>";
			$resp .= "</body>";
			$resp .= "</html>";
			
			// if request from cron than don't need to echo output.
			if ( !$this->croned ){
				echo $resp;
				echo ob_get_clean();
			}
			
			flush();	

		}
		$this->con_close();
	}

	/**
	 * fetch bounce handling connection and action from database using 'Mailpoet'
	 * settings class
	 */
	public function getSettings() {

		$this->settings = get_option( 'mbh_bounce_config' );
		$this->settings = json_decode(json_encode($this->settings));
		if ( empty($this->settings) ){
			_e("No Settings found!", 'bounce-handler-mailpoet');
			exit();
		}

	}

	/**
	 * Get bounce reason
	 * @param  string $msg mail string
	 * @return array      msg = human readable message to dispay
	 *                    action = todo action based on reason which set in database
	 */
	public function getBouncedCode($msg) {
		$rules = $this->getRules();
		$ret = ['msg'=>null,'action'=>null];
		
		foreach ($rules as $key => $value) {
			if ( preg_match('/'.$value['regex'].'/i',$msg,$result)){
				$ret['msg'] = $value['name'];
				if ($value['key'] === 'nohandle' ){
					$ret['action'] = 'weird_forward';
				}else{
					$ret['action'] = $value['key'];
				}
				
				return $ret;
			}
		}

		
	}

	/**
	 * list ignore email like sender , to address
	 * @param  object $header imap header object
	 * @return array         
	 */
	public function getIgnoreEmail($header){
		$ret = [];

		if ( !is_object($header) ){
			return $ret;
		}

		foreach ($header->to as $key => $value) {
			array_push($ret, $value->mailbox.'@'.$value->host);
		}

		foreach ($header->from as $key => $value) {
			array_push($ret, $value->mailbox.'@'.$value->host);
		}

		return $ret;
	}

	/**
	 * Get Bounced email by listing all emails, if no emails found than 
	 * return `to` email.
	 * @param  string $html          mail body
	 * @param  array $ignore_emails emails to ignore
	 * @return string                email
	 */
	public function getBouncedEmailBySearch($html,$ignore_emails){
		
		preg_match_all($this->detectEmail, $html, $result);
		
		if ( !isset($result[0]) ){
			return null;
		}

		$target = array_diff($result[0],$ignore_emails);

		if ( !$target ){
			return $ignore_emails[0];
		}

		$target = array_count_values($target);

		return array_search(max($target), $target);
	}

	/**
	 * Detect bounced email address
	 * @param  string $msg mail string
	 * @return string      detected email
	 */
	public function getBouncedEmail($msg,$header){

		if ( preg_match('#'. $this->rules['rcpt'] .'#is', $msg,$result) ){

		}

		if ( isset($result[1])){

			if(preg_match('#' . '([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})' . '#is',$result[1],$email)){
				return isset($email[0]) ? $email[0] : null;
			}

		}

		$ignore = $this->getIgnoreEmail($header);
		$bmail = $this->getBouncedEmailBySearch($msg,$ignore);

		return @$bmail;
	}

	/**
	 * Mark message to delete.
	 * @param  object $mailbox mailbox stream
	 * @param  int $msg_num stream message id
	 * @return string          Action message.
	 */
	public function deleteMsg($mailbox,$msg_num){
		imap_delete($mailbox, $msg_num);
		return ", message deleted.";
	}

	/**
	 * Set configuration 
	 */
	public function getEssential(){
		
		$this->getSettings();
		if ( empty($this->settings->login) || empty($this->settings->password) ) {
			_e("No login or Password to connect mailbox!", 'bounce-handler-mailpoet');
			exit();
		}

		// has port
		if( empty($this->settings->port) ){
            if ($this->settings->secure_connection && $this->settings->connection_method == 'imap')
                $this->settings->port = '993';
            elseif ($this->settings->connection_method == 'imap')
                $this->settings->port = '143';
            elseif ($this->settings->connection_method == 'pop3')
                $this->settings->port = '110';
        }

        // make server name
        
        $this->servername = '{'.$this->settings->hostname;
        $this->servername .= ':'.$this->settings->port;
        $this->servername .= '/'.$this->settings->connection_method;
        if ( $this->settings->secure_connection ){
        	$this->servername .= '/ssl';
        }

        if ( $this->settings->self_signed_certificates == '0' ) {
        	$this->servername .= '/novalidate-cert';
        }

        $this->servername .= '}';


	}

	/**
	 * Action todo on bounced email based on settings
	 * @param  string $email bounced email , we will find in our database if matched than will apply action
	 * @param  string $act   bounced reason and based on reason apply action.
	 */
	public function doAction($email,$act){

		$msgAct = "";
		$subs = $this->getSubscriber($email,$act);

		if ( !$subs ){
			
			$msgAct .= ' '.__("$email is not subscriber, ", 'bounce-handler-mailpoet').' ';

			if ( 
				isset($this->settings->{$act}->cond) &&
				$this->settings->{$act}->cond == 'mna_addOrRemove' &&
				$this->settings->{$act}->add_remove == 'add'
			){

				$subs = Subscriber::createOrUpdate(array('email' => $email));

			} else {

				return $msgAct;

			}

		}

		switch ($act) {

			case 'human_being_forward':
				$action = 'human_being_forward';
				$forward_mail = $this->settings->{$act};
				break;

			case 'spammer_forward':
				$action = 'spammer_forward';
				$forward_mail = $this->settings->{$act};
				break;

			case 'weird_forward':
				$action = 'weird_forward';
				$forward_mail = $this->settings->{$act};
				break;

			case 'mailbox_full':
				$action = $this->settings->{$act};
				break;

			case 'mailbox_not_available':
				$action = $this->settings->{$act};
				break;

			case 'spammer_forward':
				$action = $this->settings->{$act};
				break;
			
			default:
				$action = "do_nothing";
				break;
		}

		if ( is_object($action) ){
			$action = (array) $action;
		}

		switch ($action) {

			case 'unsub':
				if( $this->unsubscribe($subs) ){
					$msgAct .= ' '.__('Unsubscribed', 'bounce-handler-mailpoet').' ';
				}
				break;

			case 'delete':
				if ( $this->removeSubscriber($subs) ){
					$msgAct .= ' '.__('Subscriber removed', 'bounce-handler-mailpoet').' ';
				}
				break;

			case 'bounced':
				if ( $this->setBounced($subs) ){
					$msgAct .= ' '.__('Set status bounced', 'bounce-handler-mailpoet').' ';
				}
				break;

			case 'weird_forward':
				if ( $forward_mail ){
					$msgAct .= $this->forward($forward_mail,$action);
				} else {
					$msgAct .= " ".__('no destination set to forward this', 'bounce-handler-mailpoet')." ";
				}
				break;

			case 'spammer_forward':
				if ( $forward_mail ){
					$msgAct .= $this->forward($forward_mail,$action);
				} else {
					$msgAct .= " ".__('no destination set to forward this', 'bounce-handler-mailpoet')." ";
				}
				break;

			case 'human_being_forward':
				if ( $forward_mail ){
					$msgAct .= $this->forward($forward_mail,$action);
				} else {
					$msgAct .= " ".__('no destination set to forward this', 'bounce-handler-mailpoet')." ";
				}
				break;

			case 'do_nothing':
				$msgAct .= " ".__('no action done as per settings!','bounce-handler-mailpoet')." ";
				break;
			
			default:
				
				if ( is_array($action) && !empty($action) ){
					
					foreach ($action as $key => $value) {
						
						if ( $key == 'cond' && $value == 'unsub' ){
							$subs->status = Subscriber::STATUS_UNSUBSCRIBED;
							$subs->save();
							$msgAct .= ", Action Unsubscribed ,";
							continue;
						}

						if ( $key == 'add_remove' && $value == 'add' ){
							if ( !empty($action['list']) ){
								$this->addSubscription($subs,$action['list']);
								$msgAct .= " Subscriber Add to list ,";
							}
							continue;
						}

						if ( $key == 'add_remove' && $value == 'remove' ){
							if ( !empty($action['list']) ){
								$this->removeSubscription($subs,$action['list']);
								$msgAct .= " Subscriber Removed from list ,";
							}
							continue;
						}

						if ( $key == 'status' && $value == 'subs' ){
							$subs->status = Subscriber::STATUS_SUBSCRIBED;
							$subs->save();
							$msgAct .= " Subscriber Current Status : Subscribed ";
							continue;
						}

						if ( $key == 'status' && $value == 'bounced' ){
							$subs->status = Subscriber::STATUS_BOUNCED;
							$subs->save();
							$msgAct .= " Subscriber Current Status : Bounced ";
							continue;
						}

						if ( $key == 'status' && $value == 'unconfirmed' ){
							$subs->status = Subscriber::STATUS_UNCONFIRMED;
							$subs->save();
							$msgAct .= " Subscriber Current Status : Unconfirmed ";
							continue;
						}

						if ( $key == 'status' && $value == 'unsubs' ){
							$subs->status = Subscriber::STATUS_UNSUBSCRIBED;
							$subs->save();
							$msgAct .= " Subscriber Current Status : Unsubscribed ";
							continue;
						}
					}
				}
				
				break;
		}

		return $email .__(' Bounce reason : ', 'bounce-handler-mailpoet'). $act.' ,'. $msgAct;
	}

	/**
	 * check if string is segment ( list ) id
	 * @param  string  $str string
	 * @return boolean      [description]
	 */
	public function isSegmentId($str){
		if ($str){
			$prox = (int) $str;
			return $prox;
		} else {
			return false;
		}
	}

	public function removeSubscription($obj,$id){
		$ids[] = $id;

		$ret = SubscriberSegment::deleteSubscriptions($obj,$ids);

		return $ret;
	}

	public function getSubscriber($email,$act){

		if ( !$email ){
			return false;
		}

		$subs = Subscriber::findOne($email);

		if ( !$subs ){
			return false;
		}

		return $subs;
	}

	/**
	 * Change subscriber subscription from one segment to another
	 * @param object $obj     subscriber object
	 * @param int $list_id segment id
	 */
	public function addSubscription($obj,$list_id){
		if ( !$obj ){
			return false;
		}
		$ids[] = $list_id;
		$ret = SubscriberSegment::subscribeToSegments($obj,$ids);

		return $ret;
	}

	/**
	 * Unsubscribe a subscriber
	 * @param  object $obj subscriber object
	 * @return [type]      [description]
	 */
	public function unsubscribe($obj){
		
		if ( !$obj ){
			return false;
		}

		$obj->status = Subscriber::STATUS_UNSUBSCRIBED;
		$obj->save();
		
	}

	/**
	 * Set user email as bounced
	 * @param object $obj subscriber object
	 */
	public function setBounced($obj){

		if ( !$obj ){
			return false;
		}

		$obj->status = Subscriber::STATUS_BOUNCED;
		$ret = $obj->save();

		return $ret;
	}

	/**
	 * Move to trash a subscriber
	 * @param  object $obj subscriber object
	 * @return [type]      [description]
	 */
	public function removeSubscriber($obj){
		
		if ( !$obj ){
			return false;
		}
		
		$obj->status = Subscriber::STATUS_UNSUBSCRIBED;
		SubscriberSegment::unsubscribeFromSegments($obj,[]);
		$ret = $obj->trash();

		return $ret;
	}

	/**
	 * Forward email based on action
	 * @param  string $email  email to send
	 * @param  string $action forward action
	 * @return [type]         [description]
	 */
	public function forward($email,$action){

		switch ($action) {

			case 'human_being_forward':
				$subject = __('[MailPoet] Action required! You have to prove your identity in a subscriber email network','bounce-handler-mailpoet');
				break;

			case 'spammer_forward':
				$subject = __('[MailPoet] Action required! your newsletter marked as spam in a subscriber network','bounce-handler-mailpoet');
				break;

			case 'weird_forward':
				$subject = __("[MailPoet] Mail didn't detect as bounced. " ,'bounce-handler-mailpoet');
			
			default:
				# code...
				break;
		}


		$to = sprintf(
			      '%s <%s>',
			      $email,
			      $email
			    );
		$content = array(
				  'subject' => $subject,
				  'body' => array(
				    'html' => $this->procMail,
				    'text' => $this->procMail
				  )
				);
		$mlr = new Mailer();
		$mlr->send($content,$to);

		return " forwarded ";
	}

	/**
	 * Regex rules to detect bounced reason.
	 * @return [type] [description]
	 */
	public function getRules(){
		$arr = [
				[
					"key" => "mailbox_full",
					"name" => __('Mailbox Full', 'bounce-handler-mailpoet'),
					"title" => __('When mailbox is full', 'bounce-handler-mailpoet'),
					"regex" => '((mailbox|mailfolder|storage|quota|space) *(is)? *(over)? *(exceeded|size|storage|allocation|full|quota|maxi))|((over|exceeded|full) *(mail|storage|quota))'
				],
				[
					"key" => "mailbox_not_available",
					"name" => __('Mailbox not available', 'bounce-handler-mailpoet'),
					"title" => __('When mailbox is not available', 'bounce-handler-mailpoet'),
					"regex" => '(Invalid|no such|unknown|bad|des?activated|undelivered|inactive|unrouteable|delivery|mail ID|failed to|may not|no known user|email account) *(mail|destination|recipient|user|address|person|failure|has failed|does not exist|deliver to|exist|with this email|is closed)|RecipNotFound|status(-code)? *(:|=)? *5\.(1\.[1-6]|0\.0|4\.[0123467])|(user|mailbox|address|recipients?|host|account|domain) *(is|has been)? *(error|disabled|failed|unknown|unavailable|not *(found|available)|.{1,30}inactiv)|recipient *address *rejected|does *not *like *recipient|no *mailbox *here|user does.?n.t have.{0,20}account'
				],
				[
					"key" => "message_delayed",
					"name" => __('Message delayed', 'bounce-handler-mailpoet'),
					"title" => __('When message is delayed', 'bounce-handler-mailpoet'),
					"regex" => 'possible *mail *loop|too *many *hops|Action: *delayed|has.*been.*delayed|delayed *mail|temporary *failure'
				],
				[
					"key" => "failed_permanent",
					"name" => __('Failed Permanently', 'bounce-handler-mailpoet'),
					"title" => __('When failed permanently', 'bounce-handler-mailpoet'),
					"regex" => 'failed *permanently|permanent *(fatal)? *(failure|error)|Unrouteable *address|not *accepting *(any)? *mail'
				],
				[
					"key" => "action_required",
					"name" => __('Action Required', 'bounce-handler-mailpoet'),
					"title" => __('When you need to confirm you\'re a human being, forward to:', 'bounce-handler-mailpoet') ,
					"regex" => 'action *required|verif'
				],
				[
					"key" => "blocked_ip",
					"name" => __('Blocked IP', 'bounce-handler-mailpoet'),
					"title" => __('When you are flagged as a spammer forward the bounced message to', 'bounce-handler-mailpoet'),
					"regex" => 'is *(currently)? *blocked *by|block *list|spam *detected|(unacceptable|banned|offensive|filtered|blocked) *(content|message|e-?mail)|administratively *denied'
				],
				[
					"key" => "nohandle",
					"name" => __('Final Rule', 'bounce-handler-mailpoet'),
					"title" => __('When the bounce is weird and we\'re not sure what to do, forward to:','bounce-handler-mailpoet'),
					"regex" => '.'
				]
			];

			return $arr;
	}

	/**
	 * close imap connection
	 */
	public function con_close(){
		imap_expunge($this->mailbox);
		imap_close($this->mailbox);
	}
}
