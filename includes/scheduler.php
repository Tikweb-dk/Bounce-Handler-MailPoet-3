<?php

use MailPoet\Models\Setting;

/**
 * Action hook for thirty_min cron job
 * @return [type] [description]
 */
function thirty_min_hook(){

	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 

	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'thirty_min' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_thirty_min_worker','thirty_min_hook');

/**
 * Action hook for fifteen_min cron job
 * @return [type] [description]
 */
function fifteen_min_hook(){
	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 
	
	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'fifteen_min' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_fifteen_min_worker','fifteen_min_hook');


function hourly_bc_hook(){

	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 

	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'hourly' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_hourly_worker','hourly_bc_hook');

function two_hourly_bc_hook(){

	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 

	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'two_hours' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_two_hourly_worker','two_hourly_bc_hook');

function daily_bc_hook(){

	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 

	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'daily' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_daily_worker','daily_bc_hook');


function twicedaily_bc_hook(){

	$setting = get_option('mbh_bounce_config',[]); // get the settings value from database 

	// check if essential values are set else return false. 
	if ( !isset($setting['login']) && !isset($setting['password']) && !isset($setting['hostname']) ){
		return false;
	}

	if ( !isset($setting['activate_bounce_check']) ){
		return false;
	}

	if ( $setting['bounce_check_each'] != 'twicedaily' ){
		return false;
	}
	
	$bdt = new BounceDetect(true);
	$bdt->checkBounce();
}
add_action('bdt_twicedaily_worker','twicedaily_bc_hook');



/*
 * custom wp_cron identifier. 
 */
function bdt_cron_schedules($schedules){
    if(!isset($schedules["15min"])){
        $schedules["15min"] = array(
            'interval' => 15*60,
            'display' => __('Once every 15 minutes','bounce-handler-mailpoet'));
    }
    if(!isset($schedules["30min"])){
        $schedules["30min"] = array(
            'interval' => 30*60,
            'display' => __('Once every 30 minutes','bounce-handler-mailpoet'));
    }
    if(!isset($schedules["2hourly"])){
    	$schedules["2hourly"] = array(
            'interval' => 2*60*60,
            'display' => __('Once every 30 minutes','bounce-handler-mailpoet'));
    }
    return $schedules;
}

add_filter('cron_schedules','bdt_cron_schedules');



if (! wp_next_scheduled( 'bdt_fifteen_min_worker' )) {
	wp_schedule_event(time(), '15min', 'bdt_fifteen_min_worker');
}

if ( !wp_next_scheduled( 'bdt_thirty_min_worker' )) {
	wp_schedule_event(time(), '30min', 'bdt_thirty_min_worker');
}

if ( !wp_next_scheduled( 'bdt_hourly_worker' )) {
	wp_schedule_event(time(), 'hourly', 'bdt_hourly_worker');
}

if ( !wp_next_scheduled( 'bdt_two_hourly_worker' )) {
	wp_schedule_event(time(), '2hourly', 'bdt_two_hourly_worker');
}

if ( !wp_next_scheduled( 'bdt_daily_worker' )) {
	wp_schedule_event(time(), 'daily', 'bdt_daily_worker');
}

if ( !wp_next_scheduled( 'bdt_daily_worker' )) {
	wp_schedule_event(time(), 'twicedaily', 'bdt_twicedaily_worker');
}