<?php 

namespace Model;

use Mawelous\Yamop\Model;

class Simple extends Model
{
	
	protected static $_collectionName = 'simple';
	
	public static $isCollectionNameCalled = false;
	
	public static function getCollectionName()
	{
		self::$isCollectionNameCalled = true;
		return parent::getCollectionName();
	}
	
}
