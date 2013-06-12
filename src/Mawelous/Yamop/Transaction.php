<?php

namespace Mawelous\Yamop;

class Transaction
{
	public static $functions = array();
	
	public static function add ( $function )
	{
		static::$functions[] = $function;
	}
	
	public static function rollback()
	{
		return array_walk(static::$functions, 'call_user_func');
	}
	
}