<?php

class MBH_Logger {

    static $primary_key = 'id';

    private static function _table() {
        global $wpdb;
        return $wpdb->prefix . 'bounced_email_logs';
    }

    private static function _fetch_sql( $value, $by = null ) {
        global $wpdb;

        if ( empty($by) ){
            $by = static::$primary_key;
        }
        $sql = sprintf( 'SELECT * FROM %s WHERE %s = %%s', self::_table(), $by );
        return $wpdb->prepare( $sql, $value );
    }

    static function all( $start = null, $limit = null, $order_by_type = 'id', $order_by = 'DESC' ){
        
        global $wpdb;

        $ret = array();

        if ( $start === '' || $start === null ){
            $start = 0;
        }

        if ( empty($limit) ){
            $limit = 10;
        }

        $sql = sprintf('SELECT SQL_CALC_FOUND_ROWS * from %s order by %s %s limit %s offset %s',self::_table(), $order_by_type, $order_by, $limit, $start);

        $ret['data'] = $wpdb->get_results($sql,ARRAY_N);
        $total = $wpdb->get_var("SELECT FOUND_ROWS();");
        $ret['recordsTotal'] = $total;
        $ret['recordsFiltered'] = $total;

        return $ret;
    }

    static function search( $start = null, $limit = null, $order_by_type = 'id', $order_by = 'DESC', $search = null ){
        global $wpdb;

        $ret = array();

        if ( $start === '' || $start === null ){
            $start = 0;
        }

        if ( empty($limit) ){
            $limit = 10;
        }

        if ( is_email( $search ) ){
            $row = 'email';
        }

        if ( is_numeric( $search ) ){
            $row = 'id';
        }

        if ( !empty($search) && !isset($row) ){
            $row = 'reason';
        }

        if ( empty($search) ){
            $row = '*';
        }

        $sql = sprintf('SELECT SQL_CALC_FOUND_ROWS * from %s where %s="%s" order by %s %s limit %s offset %s',self::_table(), $row, $search, $order_by_type, $order_by, $limit, $start);

        $ret['data'] = $wpdb->get_results($sql,ARRAY_N);
        $total = $wpdb->get_var("SELECT FOUND_ROWS();");
        $ret['recordsTotal'] = $total;
        $ret['recordsFiltered'] = $total;

        return $ret;
    }

    static function get( $value, $by = null ) {
        global $wpdb;
        return $wpdb->get_row( self::_fetch_sql( $value, $by ) );
    }

    static function insert( $data ) {
        global $wpdb;
        $wpdb->insert( self::_table(), $data );
    }

    static function update( $data, $where ) {
        global $wpdb;
        $wpdb->update( self::_table(), $data, $where );
    }

    static function delete( $value ) {
        global $wpdb;
        $sql = sprintf( 'DELETE FROM %s WHERE %s = %%s', self::_table(), static::$primary_key );
        return $wpdb->query( $wpdb->prepare( $sql, $value ) );
    }

    static function insert_id() {
        global $wpdb;
        return $wpdb->insert_id;
    }

    static function time_to_date( $time ) {
        return gmdate( 'Y-m-d H:i:s', $time );
    }

    static function now() {
        return self::time_to_date( time() );
    }

    static function date_to_time( $date ) {
        return strtotime( $date . ' GMT' );
    }

}
