<?php
namespace Mawelous\Yamop\Tests;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    protected static $_server = 'mongodb://127.0.0.1:27017';
    protected static $_database1 = 'yamop_tests1';
    protected static $_database2 = 'yamop_tests2';
    protected static $_dbConnection1;
    protected static $_dbConnection2;
    
    public static function setUpBeforeClass()
    {
    	$connection = new \MongoClient( self::$_server );
    	self::$_dbConnection1 = $connection->{self::$_database1};
    	self::$_dbConnection2 = $connection->{self::$_database2};
    	
    	\Mawelous\Yamop\Mapper::setDatabase( 
    		array( 'first' => self::$_dbConnection1,
    		       'second' => self::$_dbConnection2  )
  	   );
    }
    
    public function testSaveWithoutSpecifiedConnection()
    {
    	$simple = new \Model\Simple( $this->_getSimpleData() );
    	$simple->save();
    	
    	$rawObject = self::$_dbConnection1->simple
    		->findOne( array( '_id' => $simple->_id ) );

    	$this->assertInternalType( 'array', $rawObject );
    	
    	return $simple;

    }
     
    public function testFindWithoutSpecifiedConnection()
    {
    	$this->_saveDataInFirst();
    	$found = \Model\Simple::findOne( $this->_getSimpleData() );
    	$this->assertInstanceOf( '\Model\Simple', $found );
    }    
    
    public function testSaveWithSpecifiedConnection()
    {
    	$connected = new \Model\Connected( $this->_getSimpleData() );
    	$connected->save();
    	 
    	$rawObject = self::$_dbConnection2->connected
    		->findOne( array( '_id' => $connected->_id ) );
    
    	$this->assertInternalType( 'array', $rawObject );
    	 
    	return $connected;
    
    }
    
    public function testFindWithSpecifiedConnection()
    {
    	$this->_saveDataInSecond();
    	$connected = \Model\Connected::findOne( $this->_getSimpleData() );
    	$this->assertInstanceOf( '\Model\Connected', $connected );
    }    

    public function tearDown()
    {
    	self::$_dbConnection1->drop();
    	self::$_dbConnection2->drop();
    }
    
    protected function _getSimpleData()
    {
    	return array( 'test' => 'test' );
    }
    
    protected function _saveDataInFirst()
    {
    	self::$_dbConnection1->simple->insert( $this->_getSimpleData() );
    	return $this->_getSimpleData();
    }    
    
    protected function _saveDataInSecond()
    {
    	self::$_dbConnection2->connected->insert( $this->_getSimpleData() );
    	return $this->_getSimpleData();
    }    
}