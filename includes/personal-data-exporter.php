<?php

/**
 * Personal data exporter utility for GDPR compliance.
 */


if ( !class_exists('MbhGdpr') ) :

	class MbhGdpr{

		public static function listen_request(){
			add_filter( 'wp_privacy_personal_data_exporters', array('MbhGdpr','register_exporter') ); // listen for data exporters
			add_filter( 'wp_privacy_personal_data_erasers', array('MbhGdpr','register_eraser') ); // listen for data eraser
		}

		/**
		 * Register data exporter functionalities
		 */
		public static function register_exporter( $exporters ){

		  $exporters['bounce-handler-mailpoet'] = array(
		    'exporter_friendly_name' => __( 'Mailpoet Bounce Handler', 'bounce-handler-mailpoet' ),
		    'callback' => array('MbhGdpr','process_export'),
		  );

		  return $exporters;

		} // End of register_exporter


		/**
		 * Register data eraser functionalities.
		 */
		public static function register_eraser( $erasers ){

			$erasers['bounce-handler-mailpoet'] = array(
				'eraser_friendly_name'	=> 	__( 'Mailpoet Bounce Handler','bounce-handler-mailpoet'),
				'callback'				=> array('MbhGdpr','process_erase')
			);

			return $erasers;

		} // End of register_eraser


		/**
		 * Process Export 
		 */
		public static function process_export( $email_address, $page ){

			$emails = MBH_Logger::search(0,10,'id','DESC',trim($email_address));
			$export_items = array();

			if ( !empty($emails['data']) ):

				foreach ( $emails['data'] as $key => $value ) {
					
					$group_id = 'bounce-log';
					$group_label = __('Mailpoet Bounce Log','bounce-handler-mailpoet');
					$item_id = $group_id.'-'.$value[0];

					$data = array(
						array(
							'name'		=> __('Bounce Reason','bounce-handler-mailpoet'),
							'value'		=> $value[2]
						),
						array(
							'name'		=> __('Detected At','bounce-handler-mailpoet'),
							'value'		=> $value[3]
						)
					);

			      $export_items[] = array(
			        'group_id' => $group_id,
			        'group_label' => $group_label,
			        'item_id' => $item_id,
			        'data' => $data,
			      );

				}

			endif;

			return array(
				'data'	=> $export_items,
				'done'	=> true
			);

		} // End of process_export


		/**
		 * Process log removal.
		 */
		public static function process_erase( $email_address, $page ){

			$emails = MBH_Logger::search(0,10,'id','DESC',trim($email_address));
			$item_removed = false;

			foreach ($emails['data'] as $key => $value) {
				
				if ( isset($value[0]) && is_numeric($value[0]) ){

					MBH_logger::delete( $value[0] );
					$item_removed = true;
				}

			}

			$ret = array(
		      'items_removed' => $item_removed,
		      'items_retained' => false,
		      'done' => true,
			);

			if ( $item_removed ){
				$ret['messages'][] = __('Mailpoet Bounce Handler log removed!','bounce-handler-mailpoet');
			} else {
				$ret['messages'] = array();
			}

			return $ret;

		} // End of process_erase

	}

endif;

MbhGdpr::listen_request();