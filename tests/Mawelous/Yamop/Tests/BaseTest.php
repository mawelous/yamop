<?php
namespace Mawelous\Yamop\Tests;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{

    protected static $_server = 'mongodb://127.0.0.1:27017';
    protected static $_database = 'yamop_tests';
    protected static $_dbConnection;
    
    public static function setUpBeforeClass()
    {
    	$connection = new \MongoClient( self::$_server );
    	self::$_dbConnection = $connection->{self::$_database};
    	\Mawelous\Yamop\Mapper::setDatabase( self::$_dbConnection );
    }

    public function tearDown()
    {
    	self::$_dbConnection->drop();
    }
}