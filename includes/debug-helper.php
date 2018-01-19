<?php

function save($path,$cont){
	if( is_array($cont) && is_object($cont) ){
		$cont = print_r($cont,true);
	}
	file_put_contents($path, $cont);
}