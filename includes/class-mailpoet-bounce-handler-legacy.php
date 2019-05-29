<?php
/**
 * The core plugin class. (Legacy)
 * This is deprecated since mailpoet v3.19.0
 * @since      1.0.0
 * @package    Bounce Handler Mailpoet
 * @subpackage bounce-handler-mailpoet/includes
 * @author     Tikweb <kasper@tikjob.dk>
 */

use MailPoet\Models\Setting;
use MailPoet\Models\Segment;

if(!class_exists('Mailpoet_Bounce_Handler')){

	class Mailpoet_Bounce_Handler 
	{
		/**
		 * Properties
		 */
		protected $page_name = 'mailpoet_page_mailpoet_bounce_handling';
		
		/**
		 * Initialize the class
		 */
		public static function init()
		{
			$_this_class = new Mailpoet_Bounce_Handler();
			return $_this_class;
		}

		/**
		 * Constructor
		 */
		public function __construct()
		{
			// Admin Menu
			add_action('admin_menu', array($this, 'admin_menus'), 32); // run the hook after mailpoet menu load

			// Admin Enqueue
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts')); // Used for css link, js not working here

			// Ajax request
			add_action('wp_ajax_mbh_save_bounce_settings', array($this, 'mbh_save_bounce_settings')); // Save bounce page settings
			add_action('wp_ajax_bounce_handler_logs',array($this,'bounce_handler_logs'));
			add_action('wp_ajax_mbh_delete_bounce_log', array($this, 'mbh_delete_bounce_log')); // Save bounce page settings
		}

		/**
		 * Admin menu
		 */
		public function admin_menus()
		{
			// Bounce Handling menu
			$page_title = __('Bounce Handling', 'bounce-handler-mailpoet');
			add_submenu_page('mailpoet-newsletters', $page_title, $page_title, 'manage_options', 'mailpoet_bounce_handling', array($this, 'bounce_handler_page'));

			// hidden submenu page to process realtime bounce detection
			$hook = add_submenu_page(null, $page_title, $page_title, 'manage_options', 'mailpoet_bounce_detect', array($this, 'bounce_detect_page'));

			add_action('load-' . $hook, function() {
				$this->bounce_detect_page();
				exit();
		    });

		}

		/**
		 * Bounce Handling page
		 */
		public function bounce_handler_page()
		{
			$this->necessary_css();
			$bounce = get_option('mbh_bounce_config');

			$mailpoet_conf = Setting::getValue('bounce');

			if ( isset($mailpoet_conf['address']) && !empty($mailpoet_conf['address']) ){
				$bounce['address'] = $mailpoet_conf['address'];
			}

			?>
			
			<div class="wrap">

			    <div id="mailpoet_settings">
			    	<h1 class="title"><?php _e('Bounce Handling', 'bounce-handler-mailpoet'); ?></h1>

					<!-- Notice -->
					<div id="mailpoet_notice_error" class="mailpoet_notice" style="display:none;"></div>
					<div id="mailpoet_notice_success" class="mailpoet_notice" style="display:none;"></div>

					<form id="bounce-handler-settings-form" method="post" autocomplete="off" class="mailpoet_form">
						
						<h2 class="nav-tab-wrapper" id="mailpoet_settings_tabs">
					        <a class="nav-tab nav-tab-active" href="#settings"><?php _e('Settings', 'bounce-handler-mailpoet'); ?></a>
							<a class="nav-tab" href="#actions"><?php _e('Actions & Notifications', 'bounce-handler-mailpoet'); ?></a>
							<a class="nav-tab" href="#logs" id="mbh_logs"><?php _e('Log', 'bounce-handler-mailpoet'); ?></a>
					    </h2>

					    <!-- Settings -->
						<div data-tab="settings" class="mailpoet_panel">
							<table class="form-table">
								<tbody>
									<tr>
										<div class="bounce-intro">
											<h3><?php _e('How does it work?', 'bounce-handler-mailpoet'); ?></h3>
											<ol>  
												<li><?php _e('Create an email account dedicated solely to bounce handling.', 'bounce-handler-mailpoet'); ?></li>  
												<li><?php _e('Fill out the form below so we can connect to it.', 'bounce-handler-mailpoet'); ?></li>  
												<li><?php _e('Take it easy, the plugin does the rest.', 'bounce-handler-mailpoet'); ?></li>
											</ol>
											<p class="description"><?php $desc = __('Need help? Check out <a href="%s" target="_blank">our guide</a> on how to fill out the form.', 'bounce-handler-mailpoet'); printf($desc, 'http://www.tikweb.dk/mail-bounce-handler/'); ?></p>
										</div>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[address]"><?php _e('Bounce Email', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<span class="mbh-help" title="<?php _e('Please set a single dedicated bounce address for bounce email, for example bounce@mailpoet.com','bounce-handler-mailpoet'); ?>"></span>
											<input size="52" type="text" id="bounce[address]" name="bounce[address]" value="<?php echo isset($bounce['address']) ? $bounce['address'] : ''; ?>"><br/>
											<label for="bounce[address]"><i><?php _e('Please set a single dedicated bounce address for bounce email, for example bounce@mailpoet.com','bounce-handler-mailpoet'); ?></i></label>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[hostname]"><?php _e('Hostname', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<span class="mbh-help" title="<?php _e('Host name of your mail server','bounce-handler-mailpoet'); ?>"></span>
											<input size="52" type="text" id="bounce[hostname]" name="bounce[hostname]" value="<?php $this->show_value($bounce['hostname']); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[login]"><?php _e('Login', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<span class="mbh-help" title="<?php _e('Email address','bounce-handler-mailpoet'); ?>"></span>
											<input size="52" type="text" id="bounce[login]" name="bounce[login]" value="<?php $this->show_value($bounce['login']); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[password]"><?php _e('Password', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<?php 
												$password = '';
												if(isset($bounce['password'])){
													$password = mbh_encrypt_decrypt('decrypt', $bounce['password']); 
												}
											?>
											<span class="mbh-help" title="<?php _e('Password for bounce email.','bounce-handler-mailpoet');?>"></span>
											<input size="52" type="password" id="bounce[password]" name="bounce[password]" value="<?php echo htmlspecialchars($password); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[port]"><?php _e('Port', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<span class="mbh-help" data-tooltip-content="#port-help"></span>
											<div class="tooltip_templates">
												<span id="port-help">
													<strong>Set a port number for connection</strong><br/>
													If you don't set port number than by default<br/>
													<strong>143</strong> set for <strong>IMAP</strong> connection<br/>
													<strong>110</strong> set for <strong>POP3</strong> connection<br/>

												</span>
											</div>
											<input size="10" type="text" id="bounce[port]" name="bounce[port]" value="<?php $this->show_value($bounce['port']); ?>">
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="bounce[connection_method]"><?php _e('Connection method', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<?php $conn_method = isset($bounce['connection_method']) ? $bounce['connection_method'] : ''; ?>
											<span class="mbh-help" title="<?php _e('Choose the best method to connect your mail server','bounce-handler-mailpoet'); ?>"></span>
											<select name="bounce[connection_method]" id="bounce[connection_method]">
												<option value="pop3" <?php selected($conn_method, 'pop3'); ?> >POP3</option>
												<option value="imap" <?php selected($conn_method, 'imap'); ?> >IMAP</option>
												<!-- <option value="pear" <?php selected($conn_method, 'pear'); ?> >POP3 without imap extension</option> -->
												<option value="nntp" <?php selected($conn_method, 'nntp'); ?> >NNTP</option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label><?php _e('Secure connection(SSL)', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>

											<p>
												<span class="mbh-help" data-tooltip-content="#secure-con-help"></span>
												<span class="tooltip_templates">
													<span id="secure-con-help">
														<strong>Set Yes</strong> if you want secure connection between this server to your mailserver.<br/>
														<strong>Set NO</strong> if you want a plain connection between this server to your mailserver.<br/>
														<i><strong>Note: You must verify wheather your mailserver support secure connection or not.<br/>If your mailserver not support secure connection please set No for this option.</strong></i>
													</span>
												</span>
												<?php $conn_secure = isset($bounce['secure_connection']) ? $bounce['secure_connection'] : '0'; ?>
												<label><input type="radio" name="bounce[secure_connection]" value="0" <?php checked($conn_secure, '0'); ?> ><?php _e('No', 'bounce-handler-mailpoet'); ?></label>
												<label><input type="radio" name="bounce[secure_connection]" value="1" <?php checked($conn_secure, '1'); ?> ><?php _e('Yes', 'bounce-handler-mailpoet'); ?></label>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label><?php _e('Self-signed certificates', 'bounce-handler-mailpoet'); ?></label>
										</th>
										<td>
											<p>
												<span class="mbh-help" data-tooltip-content="#self-signed-help"></span>
												<span class="tooltip_templates">
													<span id="self-signed-help">
														If your mailserver support Self Signed Certificate than set Yes, otherwise No.<br/>
														<i><strong>Note:</strong> for all common server option "No" will work, most of the time.</i>
													</span>
												</span>
												<?php $Self_signed = isset($bounce['self_signed_certificates']) ? $bounce['self_signed_certificates'] : '0'; ?>
												<label><input type="radio" name="bounce[self_signed_certificates]" value="0" <?php checked($Self_signed, '0'); ?> ><?php _e('No', 'bounce-handler-mailpoet'); ?></label>
												<label><input type="radio" name="bounce[self_signed_certificates]" value="1" <?php checked($Self_signed, '1'); ?> ><?php _e('Yes', 'bounce-handler-mailpoet'); ?></label>
											</p>
										</td>
									</tr>
									<tr>
										<td scope="row" class="left-padding-off">

											<label class="activate-bounce-label">
												<span class="mbh-help" title="<?php _e('Activate bounce check scheduler','bounce-handler-mailpoet'); ?>"></span>
												<?php $bounce_check = isset($bounce['activate_bounce_check']) ? $bounce['activate_bounce_check'] : '0'; ?>
												<input type="checkbox" value="1" name="bounce[activate_bounce_check]" <?php checked($bounce_check, '1'); ?> id="activate_bounce_check">
												<?php _e('Activate bounce and check every...', 'bounce-handler-mailpoet'); ?>
											</label>
										</td>
										<td>
											<?php $check_each = isset($bounce['bounce_check_each']) ? $bounce['bounce_check_each'] : ''; ?>
											<select name="bounce[bounce_check_each]" id="bounce_check_each">
												<option value="fifteen_min" <?php selected($check_each, 'fifteen_min'); ?> ><?php _e('15 minutes', 'bounce-handler-mailpoet'); ?></option>
												<option value="thirty_min" <?php selected($check_each, 'thirty_min'); ?> ><?php _e('30 minutes', 'bounce-handler-mailpoet'); ?></option>
												<option value="hourly" <?php selected($check_each, 'hourly'); ?> ><?php _e('1 hour', 'bounce-handler-mailpoet'); ?></option>
												<option value="two_hours" <?php selected($check_each, 'two_hours'); ?> ><?php _e('2 hours', 'bounce-handler-mailpoet'); ?></option>
												<option value="twicedaily" <?php selected($check_each, 'twicedaily'); ?> ><?php _e('Twice daily', 'bounce-handler-mailpoet'); ?></option>
												<option value="daily" <?php selected($check_each, 'daily'); ?> ><?php _e('Day', 'bounce-handler-mailpoet'); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<td scope="row" class="left-padding-off">
											<span class="mbh-help" title="<?php _e('Check if your credentials are working before bounce handling','bounce-handler-mailpoet'); ?>"></span>
											<button class="button" type="button" id="check-bounce-connection"><?php _e('Does it work? Try to connect.', 'bounce-handler-mailpoet'); ?></button>
										</td>
										<td><!-- Nothing goes here --></td>
									</tr>
								</tbody>
							</table>
							<?php 
							$settings_text = __('Save Changes', 'bounce-handler-mailpoet');
							submit_button($settings_text, 'primary', 'settings');
							?>
						</div> <!-- End of data-tab="basics" -->

					    <!-- Actions & Notifications -->
						<div data-tab="actions" class="mailpoet_panel">
							<p class="description"><?php _e('There are plenty of reasons for bounces. Configure what to do in each scenario.', 'bounce-handler-mailpoet'); ?></p>
							<ol>
								<li>
									<label for="bounce[mf_cond]"><?php _e('When mailbox is full after 3 tries', 'bounce-handler-mailpoet'); ?> : </label>
									<?php $mf_cond = isset($bounce['mf_cond']) ? $bounce['mf_cond'] : ''; ?>
									<select name="bounce[mf_cond]" id="bounce[mf_cond]">
										<option value="do_nothing" <?php selected($mf_cond, ''); ?> ><?php _e('Do nothing', 'bounce-handler-mailpoet'); ?></option>
										<option value="delete" <?php selected($mf_cond, 'delete'); ?> ><?php _e('Delete the user', 'bounce-handler-mailpoet'); ?></option>
										<option value="bounced" <?php selected($mf_cond, 'bounced'); ?> ><?php _e('Change status to \'Bounced\'', 'bounce-handler-mailpoet'); ?></option>
										<option value="unsub" <?php selected( $mf_cond, 'unsub' ); ?>><?php _e('Unsubscribe The User','bounce-handler-mailpoet'); ?></option>
										<option value="mf_addOrRemove" <?php selected( $mf_cond, 'mf_addOrRemove' ); ?>><?php _e('Disregard current list','bounce-handler-mailpoet'); ?></option>
									</select>
									<span class="hidden" id="mf-unsub-wraper">
										<?php $mf_add_remove = isset($bounce['mf_add_remove']) ? $bounce['mf_add_remove'] : 'do_nothing'; ?>
										<?php _e('and','bounce-handler-mailpoet');?> 
										<select name="bounce[mf_add_remove]" id="bounce[mf_add_remove]">
											<option value="add" <?php selected( $mf_add_remove,'add' ); ?>><?php _e('Add','bounce-handler-mailpoet');?></option>
											<option value="remove" <?php selected( $mf_add_remove,'remove' ); ?>><?php _e('Remove','bounce-handler-mailpoet');?></option>
											<option value="do_nothing" <?php selected( $mf_add_remove,'do_nothing' ); ?>><?php _e('Do nothing more','bounce-handler-mailpoet');?></option>
										</select>
										<span id="mf-hide-on-nothing">
										<?php $mf_list = isset($bounce['mf_list']) ? $bounce['mf_list'] : 'do_nothing'; ?>
										<?php _e('him for the list','bounce-handler-mailpoet');?>
										<select name="bounce[mf_list]" id="bounce[mf_list]">
											<?php 
												$sagments = Segment::getPublic()->findArray();
												if(!empty($sagments)): foreach($sagments as $sagment):
											?>
											<option value="<?php echo $sagment['id'];?>" <?php selected( $mf_list,$sagment['id'] ); ?>><?php echo $sagment['name'];?></option>
											<?php endforeach;?>
											<?php else : ?>
												<option value="1"><?php _e('No List Found','bounce-handler-mailpoet');?></option>
											<?php endif; ?>
										</select>
										<?php $mf_status = isset($bounce['mf_list']) ? $bounce['mf_list'] : ''; ?>
										<?php _e('as','bounce-handler-mailpoet');?>
										<select name="bounce[mf_status]" id="bounce[mf_status]">
											<option value="subs" <?php selected( $mf_status,'subs' );?>><?php _e('Subscriber','bounce-handler-mailpoet');?></option>
											<option value="bounced" <?php selected( $mf_status,'bounced' );?>><?php _e('Bounced','bounce-handler-mailpoet');?></option>
											<option value="unconfirmed" <?php selected( $mf_status,'unconfirmed' );?>><?php _e('Unconfirmed','bounce-handler-mailpoet');?></option>
											<option value="unsubs" <?php selected( $mf_status,'unsubs' );?>><?php _e('Unsubscriber','bounce-handler-mailpoet');?></option>
										</select>
										</span>
									</span>
								</li>
								<li>
									<label for="bounce[mailbox_not_available]"><?php _e('When mailbox is not available', 'bounce-handler-mailpoet'); ?> : </label>
									<?php $mna_cond = isset($bounce['mna_cond']) ? $bounce['mna_cond'] : ''; ?>
									<select name="bounce[mna_cond]" id="bounce[mna_cond]">
										<option value="do_nothing" <?php selected($mna_cond, ''); ?> ><?php _e('Do nothing', 'bounce-handler-mailpoet'); ?></option>
										<option value="delete" <?php selected($mna_cond, 'delete'); ?> ><?php _e('Delete the user', 'bounce-handler-mailpoet'); ?></option>
										<option value="bounced" <?php selected($mna_cond, 'bounced'); ?> ><?php _e('Change status to \'Bounced\'', 'bounce-handler-mailpoet'); ?></option>
										<option value="unsub" <?php selected( $mna_cond, 'unsub' ); ?>>Unsubscribe The User</option>
										<option value="mna_addOrRemove" <?php selected( $mna_cond, 'mna_addOrRemove' ); ?>><?php _e('Disregard current list','bounce-handler-mailpoet'); ?></option>
									</select>
									<?php $mna_add_remove = isset($bounce['mna_add_remove']) ? $bounce['mna_add_remove'] : 'do_nothing'; ?>
									<span class="hidden" id="mna-unsub-wraper">
										<?php _e('and','bounce-handler-mailpoet');?> 
										<select name="bounce[mna_add_remove]" id="bounce[mna_add_remove]">
											<option value="add" <?php selected( $mna_add_remove,'add' ); ?>><?php _e('Add','bounce-handler-mailpoet');?></option>
											<option value="remove" <?php selected( $mna_add_remove,'remove' ); ?>><?php _e('Remove','bounce-handler-mailpoet');?></option>
											<option value="do_nothing" <?php selected( $mna_add_remove,'do_nothing' ); ?>><?php _e('Do nothing more','bounce-handler-mailpoet');?></option>
										</select>
										<span id="mna-hide-on-nothing">
										<?php $mna_list = isset($bounce['mna_list']) ? $bounce['mna_list'] : ''; ?>
										<?php _e('him for the list','bounce-handler-mailpoet');?>
										<select name="bounce[mna_list]" id="bounce[mna_list]">
											<?php 
												$sagments = Segment::getPublic()->findArray();
												if(!empty($sagments)): foreach($sagments as $sagment):
											?>
											<option value="<?php echo $sagment['id'];?>" <?php selected( $mna_list,$sagment['id'] ); ?>><?php echo $sagment['name'];?></option>
											<?php endforeach;?>
											<?php else : ?>
												<option value="1"><?php _e('No List Found','bounce-handler-mailpoet');?></option>
											<?php endif; ?>
										</select>
										<?php $mna_status = isset($bounce['mna_status']) ? $bounce['mna_status'] : ''; ?>
										<?php _e('as','bounce-handler-mailpoet');?>
										<select name="bounce[mna_status]" id="bounce[mna_status]">
											<option value="subs" <?php selected( $mna_status,'subs' );?>><?php _e('Subscriber','bounce-handler-mailpoet');?></option>
											<option value="bounced" <?php selected( $mna_status,'bounced' );?>><?php _e('Bounced','bounce-handler-mailpoet');?></option>
											<option value="unconfirmed" <?php selected( $mna_status,'unconfirmed' );?>><?php _e('Unconfirmed','bounce-handler-mailpoet');?></option>
											<option value="unsubs" <?php selected( $mna_status,'unsubs' );?>><?php _e('Unsubscriber','bounce-handler-mailpoet');?></option>
										</select>
									</span>
								</span>
								</li>
								<li>
									<label for="bounce[human_being_forward]"><?php _e("When you need to confirm you're a human being, forward to", 'bounce-handler-mailpoet'); ?>: </label>
									<input id="bounce[human_being_forward]" size="30" type="text" name="bounce[human_being_forward]" value="<?php $this->show_value($bounce['human_being_forward']); ?>">
								</li>
								<li>
									<label for="bounce[spammer_forward]"><?php _e('When you are flagged as a spammer forward the bounced message to', 'bounce-handler-mailpoet'); ?> </label>
									<input id="bounce[spammer_forward]" size="30" type="text" name="bounce[spammer_forward]" value="<?php $this->show_value($bounce['spammer_forward']); ?>">
								</li>
								<li>
									<label for="bounce[weird_forward]"><?php _e("When the bounce is weird and we're not sure what to do, forward to", 'bounce-handler-mailpoet'); ?>: </label>
									<input id="bounce[weird_forward]" size="30" type="text" class="" name="bounce[weird_forward]" value="<?php $this->show_value($bounce['weird_forward']); ?>">
								</li>
							</ol>
							<?php 
							$action_text = __('Save Changes', 'bounce-handler-mailpoet');
							submit_button($action_text, 'primary', 'action'); 
							?>
						</div> <!-- End of data-tab="actions" -->
					</form>

						<!-- Bounce Log Tab Rendar -->
						<div data-tab="logs" class="mailpoet_panel">
							<h2><?php _e('Bounce Logs','bounce-handler-mailpoet'); ?></h2>
							<hr>
							<table id="bounce-log" class="display" cellspacing="0" width="100%">
							        <thead>
							            <tr>
							            	<th><span class="dashicons dashicons-marker"></span></th>
							                <th>#ID</th>
							                <th><?php _e('Bounced Email','bounce-handler-mailpoet'); ?></th>
							                <th><?php _e('Bounced Reason','bounce-handler-mailpoet'); ?></th>
							                <th><?php _e('Last Checked','bounce-handler-mailpoet'); ?></th>
							            </tr>
							        </thead>
							</table>
						</div> <!-- End Bounce Log Tab -->

			    </div> <!-- /#mailpoet_settings -->
			    
			    <!-- Bounce Connection check template -->
			    <script id="bounce_connect_check_template" type="text/x-handlebars-template">
			    	
			    	<div class="notice-wrapper">
						<!-- Show Spinner -->
						<div class="notice-spinner">
							<span class="spinner is-active" style="float:left;"></span>
							<div class="clear"></div>
						</div>
						
						<!-- Show Output -->
						<div id="connection-check-result" class="clearfix">
							<!-- Result will be show here -->
						</div>
			    	</div>

			    	<button type="button" id="process-bounce" class="button hidden process-bounce-btn"><?php _e('Process bounce handling now!', 'bounce-handler-mailpoet'); ?></button>
			    </script>

			</div> <!-- /.wrap -->

			<?php
			$this->necessary_script();
		}

		/**
		 * Realtime bounce detect page
		 * @return [type] [description]
		 */
		public function bounce_detect_page(){
			$bdt = new BounceDetect();
			$bdt->checkBounce();
		}

		/**
		 * Bounce Log Display handler
		 * @return [type] [description]
		 */
		public function bounce_handler_logs(){

			if ( wp_verify_nonce( $_GET['nonce'], '_mbh_bounce_log_' ) ){

				$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
				$length = isset($_GET['length']) ? (int) $_GET['length'] : 10;

				$column = ['', 'id','email','reason','last_checked'];

				$order = isset($_GET['order']) ? $_GET['order'][0]['dir'] : 'DESC';
				$oc = isset($_GET['order']) ? $_GET['order'][0]['column'] : 0;

				$search = isset($_GET['search']) ? $_GET['search']['value'] : null;

				
				if ( !empty($search) ){
					echo json_encode(MBH_Logger::search($start,$length, $column[$oc], $order, $search));
				} else {
					echo json_encode(MBH_Logger::all($start,$length, $column[$oc], $order, $search));
				}

			} else {

				echo json_encode(array(
					"data" => [],
					"recordsTotal" => 0,
					"recordsFiltered" => 0
					));
			}
			
			wp_die();
		}

		/**
		 * Enqueue scripts
		 */
		public function enqueue_scripts($page)
		{
			if($this->page_name == $page){
				//Stylesheet
				wp_enqueue_style('mailpoet-admin-style', plugins_url('/assets/css/admin.css',dirname(__FILE__)));
				wp_enqueue_style('mbh-datatables', plugins_url('/assets/css/jquery.dataTables.min.css',dirname(__FILE__)));
				wp_enqueue_style('mbh-datatables-select-css', plugins_url('/assets/css/select.dataTables.min.css',dirname(__FILE__)));
				wp_enqueue_style('mbh-datatables-button-css', plugins_url('/assets/css/buttons.dataTables.min.css',dirname(__FILE__)));
				wp_enqueue_style( 'mbh-tooltip-style', plugins_url('/assets/css/tooltipster.bundle.min.css',__DIR__));

				//Scripts
				wp_enqueue_script('vendor-mailpoet', plugins_url('/assets/js/vendor.js',dirname(__FILE__)), array(), null, true);
				wp_enqueue_script('mailpoet-mailpoet',plugins_url('/assets/js/mailpoet.js',dirname(__FILE__)), array(), null, true);
				wp_enqueue_script('admin_vendor-mailpoet', plugins_url('/assets/js/admin_vendor.js',dirname(__FILE__)), array(), null, true);
				wp_enqueue_script('admin-mailpoet', plugins_url('/assets/js/admin.js',dirname(__FILE__)), array(), null, true);
				wp_enqueue_script('mbh-datatables', plugins_url('/assets/js/jquery.dataTables.min.js',__DIR__), array('jquery'),'1.11', true);
				wp_enqueue_script('mbh-datatables-select-js', plugins_url('/assets/js/dataTables.select.min.js',__DIR__), array('mbh-datatables'),'1.2.7', true);
				wp_enqueue_script('mbh-datatables-button-js', plugins_url('/assets/js/dataTables.buttons.min.js',__DIR__), array('mbh-datatables'),'1.5.2', true);
				wp_enqueue_script( 'mbh-tooltip', plugins_url('/assets/js/tooltipster.bundle.min.js',__DIR__), array( 'jquery' ), '1.12', false );
			}
		} // End of enqueue_scripts
		
		/**
		 * Show value
		 */
		public function show_value(&$value)
		{
			echo isset($value) ? $value : '';
		}

		/**
		 * Save Bounce data
		 */
		public function mbh_save_bounce_settings()
		{
			header('Content-Type: application/json');
			$return_data = array();
			$error = array();

			$_POST = wp_unslash( $_POST );

			if(wp_verify_nonce($_POST['nonce'], '_tikweb_mailpoet_')){ // Check nonce
				if(isset($_POST['data']['bounce']) && is_array($_POST['data']['bounce'])){ // Check bounce is set and array
					
					// Senitize the data
					$bounce = array_map(array($this, 'senitize_field'), $_POST['data']['bounce']);

					//Valide Bounce Email
					if(!is_email($bounce['address'])){
						$error[] = __("Please set a valid email address for bounce email.","bounce-handler-mailpoet");
						$error[] = 'address';
					}

					// if all actions are empty than fire this.
					if ( !is_email($bounce['human_being_forward']) && !is_email($bounce['spammer_forward']) && !is_email($bounce['weird_forward']) ){
						$error[] = __("Please set a valid email address for actions!","bounce-handler-mailpoet");
						$error[] = 'human_being_forward';
					}

					//Valide human being Email
					if(!is_email($bounce['human_being_forward'])){
						$error[] = __("Please set a valid email address for forward rules when you need to confirm you're a human being.","bounce-handler-mailpoet");
						$error[] = 'human_being_forward';
					}

					//Valide spammer forward Email
					if(!is_email($bounce['spammer_forward'])){
						$error[] = __("Please set a valid email address for forward rules when you are flagged as a spammer.","bounce-handler-mailpoet");
						$error[] = 'spammer_forward';
					}

					//Valide weird not sure Email
					if(!is_email($bounce['weird_forward'])){
						$error[] = __("Please set a valid email address for forward rules when the bounce is weird and we're not sure what to do.","bounce-handler-mailpoet");
						$error[] = 'weird_forward';
					}

					//Email validate if
					if(empty($error)){

						// Hash the password
						if(isset($bounce['password'])){
							$bounce['password'] = mbh_encrypt_decrypt('encrypt', $bounce['password']);
						}

						$nested_cond = ['add_remove','list','status'];

						if ( ($bounce['mf_cond'] == 'mf_addOrRemove' || $bounce['mf_cond'] == 'unsub') && $bounce['mf_add_remove'] !='do_nothing' ){
							$bounce['mailbox_full'] = array(
								'cond' => $bounce['mf_cond'],
								'add_remove' => $bounce['mf_add_remove'],
								'list' => $bounce['mf_list'],
								'status' => $bounce['mf_status'],
							);
						} else {
							$bounce['mailbox_full'] = $bounce['mf_cond'];

							if ( $bounce['mf_cond'] != 'mf_addOrRemove' ){

								foreach ( $nested_cond as $key ) {
									unset($bounce['mf_'.$key]);
								}

							}

						}

						if ( ($bounce['mna_cond'] == 'mna_addOrRemove' || $bounce['mna_cond'] == 'unsub') && $bounce['mna_add_remove'] !='do_nothing' ){
							$bounce['mailbox_not_available'] = array(
								'cond' => $bounce['mna_cond'],
								'add_remove' => $bounce['mna_add_remove'],
								'list' => $bounce['mna_list'],
								'status' => $bounce['mna_status'],
							);
						} else {
							$bounce['mailbox_not_available'] = $bounce['mna_cond'];
							if ( $bounce['mna_cond'] != 'mna_addOrRemove' ){
								foreach ($nested_cond as $key) {
									unset($bounce['mna_'.$key]);
								}
							}
						}

						$bounce['last_updated'] = time();
						
						$save = update_option( 'mbh_bounce_config', $bounce, null );
						
						if($save == true){ // Data saved

							$mailpoet_conf = Setting::getValue('bounce');

							if ( !empty($bounce['address']) ){
								if ( trim($mailpoet_conf['address']) != trim($bounce['address']) ){
									$mailpoet_conf['address'] = $bounce['address'];
									Setting::setValue('bounce',$mailpoet_conf);
								}
							}
								
							$return_data['success'] = true;
							$return_data['error_data'] = __('Unable to save data.', 'bounce-handler-mailpoet');
							$return_data['error'] = false;
						}else{
							$return_data['error'] = true;
							$return_data['error_data'] = __('Unable to save data.', 'bounce-handler-mailpoet');
							$return_data['success'] = false;
						} // End if

					}else{ //Has any error
						$return_data['error'] = true;
						$return_data['error_data'] = $error[0];
						$return_data['success'] = false;
						$return_data['tag'] = $error[1];
					}//End validate if

				}else{
					$return_data['error'] = true;
					$return_data['error_data'] = __('Unable to save data.', 'bounce-handler-mailpoet');
					$return_data['success'] = false;
				} // End if
			}else{
				$return_data['error'] = true;
				$return_data['error_data'] = __('Unable to save data.', 'bounce-handler-mailpoet');
				$return_data['success'] = false;
			} // End if

			echo wp_json_encode($return_data);
			wp_die();
		} // End of mbh_save_bounce_settings

		/**
		* Delete log field
		*/
		public function mbh_delete_bounce_log()
		{
			if(wp_verify_nonce($_POST['nonce'], '_tikweb_mbh_delete_logs')){ // Check nonce
				global $wpdb;
				$log_id = $_POST['data'];
				// echo $log_id;
				$log_table = $wpdb->prefix . 'bounced_email_logs';
				$wpdb->query($wpdb->prepare(
									"DELETE FROM {$log_table} 
									WHERE id={$log_id}",
									1
				));
			}
			wp_die();
		} // End of mbh_delete_bounce_log
		
		/**
		 * Necessary CSS Style
		 */
		public function necessary_css()
		{
			?>
			<style type="text/css">
				.bounce-intro{
					border-bottom: 1px solid #ccc;
				}
				.bounce-intro .description{
					margin-bottom: 20px;
				}
				.form-table td.left-padding-off{
					padding-left: 0;
				}
				.activate-bounce-label{
				    padding-top: 5px;
				    display: block;
				}
				.mailpoet_popup_body .notice-wrapper{
					margin-bottom: 15px;
					max-width: 550px;
					min-width: 300px
				}
				table.dataTable.display tbody td{
					text-align: center !important;
				}
			</style>
			<?php
		}

		/**
		 * Necessary js Scripts
		 */
		public function necessary_script()
		{
			?>
			<script type="text/javascript">
				jQuery(function($) { // $ friendly

					// On dom loaded
					$(function() {

						/**
						 * Active settings tab
						 */
						var urlHashData = window.location.hash,
						tab;
						if(urlHashData == ""){
							tab = 'settings'; //Default tab
						}else{
							tab = urlHashData.replace("#", "");
						}

				        jQuery('a.nav-tab[href="#'+tab+'"]').addClass('nav-tab-active').blur();
				        if(jQuery('.mailpoet_panel[data-tab="'+ tab +'"]').length > 0) {
				         	jQuery('.mailpoet_panel[data-tab="'+ tab +'"]').show();
				        }

						// Activate bounce toggle
						if($('#activate_bounce_check').is(':checked')){
							$('#bounce_check_each').show();
						}else{
							$('#bounce_check_each').hide();
						}
						$('#activate_bounce_check').click(function(){
							$('#bounce_check_each').toggle();
						});

						showHide_UnsubWraper();
						showHide_DnWraper();
						var mf_sel = $('select[name="bounce[mf_cond]"]');

						$(mf_sel).on('change',function(){
							showHide_UnsubWraper();
						});

						var mna_sel = $('select[name="bounce[mna_cond]"]');
						$(mna_sel).on('change',function(){
							showHide_UnsubWraper();
						});

						var mna_dn = $('select[name="bounce[mna_add_remove]"]');
						var mf_dn = $('select[name="bounce[mf_add_remove]"]');

						$(mf_dn).on('change',function(){
							showHide_DnWraper();
						});

						$(mna_dn).on('change',function(){
							showHide_DnWraper();
						});

						function showHide_DnWraper(){

							var mna_dn = $('select[name="bounce[mna_add_remove]"]');
							var mf_dn = $('select[name="bounce[mf_add_remove]"]');

							if ( mf_dn.find(':selected').val() == 'do_nothing' ){
								$('span#mf-hide-on-nothing').addClass('hidden');
							} else {
								$('span#mf-hide-on-nothing').removeClass('hidden');
							}

							if ( mna_dn.find(':selected').val() == 'do_nothing' ){
								$('span#mna-hide-on-nothing').addClass('hidden');
							} else {
								$('span#mna-hide-on-nothing').removeClass('hidden');
							}

						}

						function showHide_UnsubWraper(){

							var mf_sel = $('select[name="bounce[mf_cond]"]');

							if ( mf_sel.find(':selected').val() == 'unsub' ||
								 mf_sel.find(':selected').val() == 'mf_addOrRemove' ){
								$('span#mf-unsub-wraper').removeClass('hidden');
							} else {
								$('span#mf-unsub-wraper').addClass('hidden');
							}

							var mna_sel = $('select[name="bounce[mna_cond]"]');

							if ( mna_sel.find(':selected').val() == 'unsub' || 
								 mna_sel.find(':selected').val() == 'mna_addOrRemove' ){
								$('span#mna-unsub-wraper').removeClass('hidden');
							} else {
								$('span#mna-unsub-wraper').addClass('hidden');
							}

						}

						// change settings tab by location hash
						function toPage(page){
							var cur_win = window.location.href;
							if ( cur_win.indexOf(page) === -1 ){
								if ( cur_win.indexOf('#') === -1 ){
									return cur_win+page;
								} else {
									var url = cur_win.split('#')[0];
									return url+page;
								}
							} else {
								return cur_win;
							}
						} // end toPage.

						// Highlight input selector border by adding a class.
						function highLight(selector) {
							var target = $('input[id="bounce['+selector+']"');
							if (target){
								target.addClass('goterr');
							}
						} // end highlight.

						// During highlight, if any other mandetory sibling input is empty than it will highlight that. Also it will remove hightlight from filled input.
						function highLightFriends(){
							var mDiv = $('div[data-tab="actions"]');
							var inputs = mDiv.find('input[type="text"]');

							inputs.each(function(k,v){

								if ( $(v).val() == '' ){
									$(v).addClass('goterr');
								} else {
									$(v).removeClass('goterr');
								}

							});
						} // end highLightFriends.

						// focus on input by selector
						function focusThat(selector) {
							var target = $('input[id="bounce['+selector+']"');
							if (target){
								target.focus();
							}
							target.focus();
						} // end focusThat

						// remove all input hightlights class
						function removeAllHighlight(){
							var allinp = $('form#bounce-handler-settings-form input');
							for (var i=0;i<allinp.length;i++){
								$(allinp[i]).removeClass('goterr');
							}
						}

						// On form submission
						$('#bounce-handler-settings-form').on('submit', function(e) {
							e.preventDefault();
							MailPoet.Modal.loading(true); //show loding

							// serialize form data
          					var settings_data = $(this).serializeObject();

          					var ajaxData = {
          						'action': 'mbh_save_bounce_settings',
        						'data': settings_data,
        						'nonce': "<?php echo wp_create_nonce('_tikweb_mailpoet_'); ?>"
          					};
          					// console.log(settings_data);
          					
          					$.ajax({
          						method: "POST",
          						url: "<?php echo admin_url('admin-ajax.php'); ?>",
          						data: ajaxData,
          						success: function(resonse, textStatus, jqXHR){
          							if(resonse.success == true){
	          							MailPoet.Notice.success(
											"<?php _e('Settings saved', 'bounce-handler-mailpoet'); ?>",
											{ scroll: true }
							            );
							            removeAllHighlight();
          							}
          							
          							if(resonse.error == true){

	          							MailPoet.Notice.error(
											resonse.error_data,
											{ scroll: true }
							            );

	          							if ( resonse.tag != 'address' ){
			          						location.replace(toPage('#actions'));
			          						focusThat(resonse.tag);
			          						highLight(resonse.tag);
			          						highLightFriends();
	          							}

	          							if ( resonse.tag == 'address' ){
	          								location.replace(toPage('#settings'));
	          								focusThat(resonse.tag);
			          						highLight(resonse.tag);
	          							}

          							}
          							
          							MailPoet.Modal.loading(false); //Hide loading
          						},
          						error: function(jqXHR, textStatus, errorThrown){
									MailPoet.Notice.error(
										"<?php _e('Error occurred, unable to save data.', 'bounce-handler-mailpoet'); ?>",
										{ scroll: true }
						            );
						            MailPoet.Modal.loading(false); //Hide loading
          						}
          					});
						});

						$('#check-bounce-connection').click(function(){
							MailPoet.Modal.popup({
								title: "<?php _e('Bounce handling connection test','bounce-handler-mailpoet'); ?>",
								template: jQuery('#bounce_connect_check_template').html()
				            });

				            // serialize form data
          					var checking_data = $('#bounce-handler-settings-form').serializeObject();
          					var checkAjaxData = {
          						'action': 'mbh_check_bounce_connection',
        						'data': checking_data,
          					};

          					$.ajax({
          						method: "POST",
          						url: "<?php echo admin_url('admin-ajax.php'); ?>",
          						data: checkAjaxData,
          						success: function(resonse, textStatus, jqXHR){
          							if(resonse.success == true){ // If connection is correct
          								let showResult = '<div class="notice notice-success"><ul>';
          								$.each(resonse.result, function(index, value){
											showResult += '<li>' + value + '</li>';
										});
										showResult += '</ul></div>';
	          							$('#connection-check-result').append(showResult);
	          							if ( resonse.total > 0 ){
	          								$('.hidden.process-bounce-btn').removeClass('hidden');
	          							}
	          							
          							}
          							
          							if(resonse.error == true){ // If connection is not correct
          								let showResult = '<div class="notice notice-error"><ul>';
          								$.each(resonse.result, function(index, value){
											showResult += '<li>' + value + '</li>';
										});
										showResult += '</ul></div>';
	          							$('#connection-check-result').append(showResult);
          							}
          							$('.spinner.is-active').remove();
          						},
          						error: function(jqXHR, textStatus, errorThrown){
          							MailPoet.Modal.close();
									MailPoet.Notice.error(
										"<?php _e('Error occurred, unable to check connection.', 'bounce-handler-mailpoet'); ?>",
										{ scroll: true }
						            );
          						}
          					});

          					// process bounce
          					$('#process-bounce').on('click',function(){
				            	var settings_data = $('#bounce-handler-settings-form').serializeObject();
	          					var ajaxData = {
	          						'action': 'mbh_save_bounce_settings',
	        						'data': settings_data,
	        						'nonce': "<?php echo wp_create_nonce('_tikweb_mailpoet_'); ?>"
	          					};
				            	$.ajax({
				            		method: "POST",
          							url: "<?php echo admin_url('admin-ajax.php'); ?>",
          							data: ajaxData,
          							success: function(resonse, textStatus, jqXHR){
          								$('#bdt').append('<iframe id="frame" src="admin.php?page=mailpoet_bounce_detect" style="width:550px;"></iframe>');
          							}
				            	})
								MailPoet.Modal.popup({
									title : "<?php _e('Bounce Detection','bounce-handler-mailpoet'); ?>",
									template : '<div id="bdt"></div>',
									width : 629,
									height : 412
								});
							}); //end process bounce
						}); // end #check-bounce-connection click

						$('#bounce-log').dataTable({
							columnDefs : [
								{
									targets: 0,
									className: 'select-checkbox',
									orderable: false,
									defaultContent: "",
									data: null
								},
								{
									targets: 1,
									data: 0,
								},
								{
									targets: 2,
									data: 1,
								},
								{
									targets: 3,
									data: 2,
								},
								{
									targets: 4,
									data: 3,
								},
								
							],
							select: {
					            style:    'multi',
					            selector: 'td:first-child'
					        },
					        dom: 'lfBrtip',
					        buttons: [
			                    { extend: 'selectAll', className: 'hide-if-js' },
			                    { extend: 'selectNone', className: 'hide-if-js' },
			                    {
			                    	text: "<?php _e('Delete', 'bounce-handler-mailpoet'); ?>",
			                    	className: 'mbh-delete-log disabled',
			                    }
			                ],
							ordering   : true,
							order : [[1,'asc']],
							searching  : true,
							processing : true,
							serverSide : true,
							ajax       : {
								url : "<?php echo admin_url('admin-ajax.php'); ?>",
								data : function(e){
									e.action = 'bounce_handler_logs',
									e.nonce = "<?php echo wp_create_nonce('_mbh_bounce_log_'); ?>"
								}
							},
				            language:{
					            	info: '<?php _e('Page','bounce-handler-mailpoet');?> _PAGE_ <?php _e('of','bounce-handler-mailpoet');?> _PAGES_',
					            	zeroRecords:'<?php _e('No logs found','bounce-handler-mailpoet');?> ',
					            	lengthMenu:' <?php _e('Show','bounce-handler-mailpoet');?> _MENU_ <?php _e('Logs.','bounce-handler-mailpoet');?> ',
					            	paginate : {
					            		'previous' : ' <?php _e('Previous','bounce-handler-mailpoet');?> ',
					            		'next' : ' <?php _e('Next','bounce-handler-mailpoet'); ?> '
					            	}
				            	},
				            'fnInitComplete' : addBouncedReasonFilter
						});	// log datable config.

						$('body').on('click', 'th .dashicons-marker', function(){
							$(this).removeClass('dashicons-marker');
							$(this).addClass('dashicons-dismiss');
							$(this).parents('#bounce-log_wrapper').find('.dt-buttons .buttons-select-all').click();
						});// bounce table bulk select
						$('body').on('click', 'th .dashicons-dismiss', function(){
							$(this).removeClass('dashicons-dismiss');
							$(this).addClass('dashicons-marker');
							$(this).parents('#bounce-log_wrapper').find('.dt-buttons .buttons-select-none').click();
						}); // bounce table bulk deselect
						$('body').on('click', 'td.select-checkbox, th[class^="sorting"]', function(){
							$(this).parents('#bounce-log').find('thead .select-checkbox .dashicons').removeClass('dashicons-dismiss').addClass('dashicons-marker');
						}); // when click on any selected row or click on any sorting icon at header, all checkbox will deselect
						$('body').on('click', '.select-checkbox', function(){
							var selectedItem = $('#bounce-log').find('.selected').length;
							if(selectedItem>0){
								$('.mbh-delete-log').removeClass('disabled');
							} else {
								$('.mbh-delete-log').addClass('disabled');
							}
						});	// remove disabled class from delete button when any log selected
						$('body').on('click', 'button.mbh-delete-log', function(){
							var ids = [];
							$('#bounce-log').find('.selected').each(function(key, val){
								var selectedId = $(val).find('td:nth-child(2)').text();
								ids.push(selectedId);
							});
							var con = confirm("<?php _e('Are you sure you want to delete this item?', 'bounce-handler-mailpoet'); ?>");
							if(con){
								$('#bounce-log').find('.selected').each(function(key, val){
									var selectedBounceId = $(val).find('td:nth-child(2)').text();
									var deleteLogAjaxData = {
										'action': 'mbh_delete_bounce_log',
										'data'	: selectedBounceId,
										'nonce'	: '<?php echo wp_create_nonce("_tikweb_mbh_delete_logs"); ?>'
									};
									$.ajax({
										url: '<?php echo admin_url('admin-ajax.php'); ?>',
										type: 'post',
										data: deleteLogAjaxData,
										success: function(response){
											$('#bounce-log').DataTable().ajax.reload();
										}
									});
								});
							}
														
						}); // delete mailpoet bounce logs		

						$('#mbh_logs').on('click',function(){
							$('#bounce-log').DataTable().ajax.reload();
						}); // reload ajax if click on logs tab from any other tab..

						function addBouncedReasonFilter(){

							var html = '<div class="br_filter" style="clear:both;float:right;">Filter Bounced Reason : <select id="bounced_reason"><option value="">All</option><option value="mailbox_not_available">Mailbox Not Available</option><option value="mailbox_full">Mailbox Full</option><option value="weird_forward">Weird Forward</option><option value="message_delayed">Message Delayed</option></select></div>';
							
							var sear = $('div#bounce-log_filter');
							sear.after( html );
						}

						function isEmail(email) {
						  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
						  return regex.test(email);
						}

						function datatableSearch( text ){
							$('#bounce-log').DataTable().search(text).draw();
						}

						$('div#bounce-log_wrapper').on('change', $('select#bounced_reason'),function(){
							
							var reson = $('select#bounced_reason').val();
							var srp = $('#bounce-log_filter input').val();
							
							if ( isEmail( srp ) ){
								return false;
							}
							datatableSearch( reson );
						}); 

						// enable tooltip
						$('.mbh-help').tooltipster();

					}); // Dom Loaded End
				});
			</script>
			<?php
		}

		/**
		 * Senitize Field data
		 */
		public function senitize_field($bounce_data)
		{
			return sanitize_text_field($bounce_data);
		}

	} // End of class

	/**
	 * Instentiate core class
	 */
	Mailpoet_Bounce_Handler::init();

} // End if
