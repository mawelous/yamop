<?php
namespace Mawelous\Yamop\Tests;

use Mawelous\Yamop\Model;
use Mawelous\Yamop\Mapper;

class MapperTest extends BaseTest
{
		
	public function testFetchObject()
	{
		$data = $this->_getSimpleData();
		$mapper = new Mapper( '\Model\Simple' );
		$result = $mapper->fetchObject( $data );
		
		$this->assertInstanceOf( '\Model\Simple', $result );
		$this->assertSame( $data, get_object_vars( $result ) );
		
	}
	
	public function testFindReturnsMapper()
	{
		$data = $this->_saveSimpleData();
		
		$mapper = new Mapper( '\Model\Simple' );
		$return = $mapper->find( $data );
		
		$this->assertInstanceOf( '\Mawelous\Yamop\Mapper', $return );
		$this->assertTrue( \Model\Simple::$isCollectionNameCalled );
		
	}
	
	public function testFindSetsCursor()
	{
	
		$mapper = new Mapper( '\Model\Simple' );
	
		$emptyCursor = $mapper->getCursor();
		
		$this->assertSame( null, $emptyCursor );
		
		$mapper->find( array( 'not_existing' => 'nothing' ) );
		$cursor = $mapper->getCursor();

		$this->assertTrue( \Model\Simple::$isCollectionNameCalled );		
		$this->assertInstanceOf( '\MongoCursor', $cursor );
		$this->assertEquals( 0, $cursor->count() );
		
		$data = $this->_saveSimpleData();		
		
		$result = $mapper->find( $data );
		$cursor = $mapper->getCursor();

		$this->assertInstanceOf( '\MongoCursor', $cursor );
		$this->assertEquals( 1, $cursor->count() );

	
	}	
	
	public function testFindOneAsObjectNoSettings()
	{
		$data = $this->_saveSimpleData();
		
		$mapper = new Mapper( '\Model\Simple' );
		
		$object = $mapper->findOne( $data );
		
		$this->assertInstanceOf( '\Model\Simple', $object );
		$this->assertAttributeNotEmpty( '_id', $object );
		$this->assertAttributeInstanceOf( '\MongoId', '_id', $object );
		$this->assertAttributeNotEmpty( 'test', $object );		
	}
	
	public function testFindOneAsObjectAfterSettings()
	{
		$data = $this->_saveSimpleData();	

		$mapperOne = new Mapper( '\Model\Simple',  Mapper::FETCH_OBJECT );
		$objectOne = $mapperOne->findOne( $data );
		
		$this->assertInstanceOf( '\Model\Simple', $objectOne );
		
		$mapperTwo = new Mapper( '\Model\Simple' );
		$mapperTwo->setFetchType( Mapper::FETCH_OBJECT );
		$objectTwo = $mapperTwo->findOne( $data );
		
		$this->assertInstanceOf( '\Model\Simple', $objectTwo );
		
	}
	
	public function testFindOneAsArray()
	{
		$data = $this->_saveSimpleData();
	
		$mapperOne = new Mapper( '\Model\Simple',  Mapper::FETCH_ARRAY );
		$result = $mapperOne->findOne( $data );
	
		$this->assertInternalType( 'array', $result );
		$this->assertArrayHasKey( '_id', $result );
		$this->assertInstanceOf( '\MongoId', $result['_id'] );
		$this->assertArrayHasKey( 'test', $result );
	
		$mapperTwo = new Mapper( '\Model\Simple' );
		$mapperTwo->setFetchType( Mapper::FETCH_ARRAY );
		$result = $mapperTwo->findOne( $data );
	
		$this->assertInternalType( 'array', $result );
		$this->assertArrayHasKey( '_id', $result );
		$this->assertInstanceOf( '\MongoId', $result['_id'] );
		$this->assertArrayHasKey( 'test', $result );	
	
	}	
	
	public function testFindOneAsJson()
	{
		$data = $this->_saveSimpleData();
	
		$mapperOne = new Mapper( '\Model\Simple',  Mapper::FETCH_JSON );
		$result = $mapperOne->findOne( $data );
	
		$this->assertInternalType( 'string', $result );
		$decoded = json_decode( $result );
		$this->assertInstanceOf( 'stdClass', $decoded );
		$this->assertAttributeNotEmpty( '_id', $decoded );
		$this->assertAttributeInstanceOf( 'stdClass', '_id', $decoded );
		$this->assertAttributeNotEmpty( 'test', $decoded );
	
		$mapperTwo = new Mapper( '\Model\Simple' );
		$mapperTwo->setFetchType( Mapper::FETCH_JSON );
		$result = $mapperTwo->findOne( $data );
		
		$this->assertInternalType( 'string', $result );
		$decoded = json_decode( $result );
		$this->assertInstanceOf( 'stdClass', $decoded );
		$this->assertAttributeNotEmpty( '_id', $decoded );
		$this->assertAttributeInstanceOf( 'stdClass', '_id', $decoded );
		$this->assertAttributeNotEmpty( 'test', $decoded );		
	
	}	
	
	public function testFindById()
	{
		$mongoId = new \MongoId();
		$stringId = (string)$mongoId;
		$data = $this->_getSimpleData();
		$data[ '_id' ] = $mongoId;
		self::$_dbConnection->simple->insert( $data );
		
		$byString = ( new Mapper( '\Model\Simple') )->findById( $stringId );
		
		$this->assertInstanceOf( '\Model\Simple', $byString );
		
		$byMongoId = ( new Mapper( '\Model\Simple') )->findById( $mongoId );
		
		$this->assertInstanceOf( '\Model\Simple', $byMongoId );
	}
	
	public function testFindAndGetWithoutFetchSet()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple' );
		$result = $mapper->find()->get();
		
		$this->assertInternalType( 'array', $result );
		
		$keys = array_keys( $result );
		
		$this->assertEquals( (string)$data[0]['_id'], $keys[0] );
		$this->assertInstanceOf( '\Model\Simple', current( $result ) );
		$this->assertCount( count( $data ), $result );
	}
	
	public function testFindAndGetWithFetchObjectSet()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple', Mapper::FETCH_OBJECT );
		$result = $mapper->find()->get();
	
		$this->assertInternalType( 'array', $result );
	
		$keys = array_keys( $result );
	
		$this->assertEquals( (string)$data[0]['_id'], $keys[0] );
		$this->assertInstanceOf( '\Model\Simple', current( $result ) );
		$this->assertCount( count( $data ), $result );
		
		$mapper = new Mapper( '\Model\Simple' );
		$mapper->setFetchType( Mapper::FETCH_OBJECT );
		$result = $mapper->find()->get();
		
		$this->assertInternalType( 'array', $result );
		
		$keys = array_keys( $result );
		
		$this->assertEquals( (string)$data[0]['_id'], $keys[0] );
		$this->assertInstanceOf( '\Model\Simple', current( $result ) );
		$this->assertCount( count( $data ), $result );		
	}	
	
	public function testFindAndGetWithFetchArraySet()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple', Mapper::FETCH_ARRAY );
		$result = $mapper->find()->get();
	
		$this->assertInternalType( 'array', $result );
	
		$keys = array_keys( $result );
	
		$this->assertEquals( (string)$data[0]['_id'], $keys[0] );
		$current = current( $result );
		$this->assertInternalType( 'array', $current );
		$this->assertEquals( (string)$data[0]['_id'], (string) $current['_id'] );
		$this->assertCount( count( $data ), $result );
	
		$mapper = new Mapper( '\Model\Simple' );
		$mapper->setFetchType( Mapper::FETCH_ARRAY );
		$result = $mapper->find()->get();
	
		$this->assertInternalType( 'array', $result );
	
		$keys = array_keys( $result );
	
		$this->assertEquals( (string)$data[0]['_id'], $keys[0] );
		$current = current( $result );
		$this->assertInternalType( 'array', $current );
		$this->assertEquals( (string)$data[0]['_id'], (string) $current['_id'] );
		$this->assertCount( count( $data ), $result );
		
	}
		
	public function testFindAndGetWithFetchJsonSet()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple', Mapper::FETCH_JSON );
		$result = $mapper->find()->get();
	
		$this->assertInternalType( 'string', $result );
		
		$result = json_decode( $result );	
		$current = current( $result );
		
		$this->assertInternalType( 'array', $result );
		$this->assertInstanceOf( 'stdClass', $current );
		$this->assertAttributeNotEmpty( '_id', $current );
		$this->assertCount( count( $data ), $result );		
	
		$mapper = new Mapper( '\Model\Simple' );
		$mapper->setFetchType( Mapper::FETCH_JSON );
		$result = $mapper->find()->get();
	
		$this->assertInternalType( 'string', $result );
		
		$result = json_decode( $result );
		$current = current( $result );
		
		$this->assertInternalType( 'array', $result );
		$this->assertInstanceOf( 'stdClass', $current );
		$this->assertAttributeNotEmpty( '_id', $current );
		$this->assertCount( count( $data ), $result );	
	
	}	
	
	public function testFindAndGetCursor()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple', Mapper::FETCH_JSON );
		$result = $mapper->find()->getCursor();	

		$this->assertInstanceOf( '\MongoCursor', $result );
		$this->assertEquals( count( $data ), $result->count() );
	}
	
	/**
	 * @expectedException Exception
	 */
	public function testSortWithoutFind()
	{
		( new Mapper( 'Model\Simple' ) )->sort();
	}
	
	/**
	 * @expectedException Exception
	 */
	public function testLimitWithoutFind()
	{
		( new Mapper( 'Model\Simple' ) )->limit();
	}

	/**
	 * @expectedException Exception
	 */
	public function testSkipWithoutFind()
	{
		( new Mapper( 'Model\Simple' ) )->skip();
	}	
	
	public function testSort()
	{
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple' );
		$sortedAsc = $mapper->find()->sort( array( 'letter' => 1 ) )->get();
		
		$first = array_shift( $sortedAsc );
		$last = array_pop( $sortedAsc );
		
		$this->assertAttributeEquals( 'a', 'letter', $first );
		$this->assertAttributeEquals( 'c', 'letter', $last );
		
	}
	
	public function testLimit()
	{
		$limit = 2;
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple' );
		$limited = $mapper->find()->limit( $limit )->get();
	
		$this->assertCount( $limit, $limited );
	
	}

	public function testSkip()
	{
		$skip = 2;
		$data = $this->_saveListData();
		$mapper = new Mapper( '\Model\Simple' );
		$limited = $mapper->find()->skip( $skip )->get();
	
		$this->assertCount( count( $data ) - $skip, $limited );
	
	}	
	
	public function testJoinWithVariable()
	{
		$this->_saveArticleWithAuthor();
		
		$articles = \Model\Article::getMapper()->find()->join( 'author', '\Model\Author', 'authorObject' )->get();
		$article = array_shift( $articles );
		
		$this->assertAttributeNotEmpty( 'authorObject', $article );
		$this->assertAttributeInstanceOf( '\Model\Author', 'authorObject', $article );
		
	}
	
	public function testJoinWithoutVariable()
	{
		$this->_saveArticleWithAuthor();
	
		$articles = \Model\Article::getMapper()->find()->join( 'author', '\Model\Author' )->get();
		$article = array_shift( $articles );
	
		$this->assertAttributeNotEmpty( 'author', $article );
		$this->assertAttributeInstanceOf( '\Model\Author', 'author', $article );
	
	}	
	
	public function testJoinToNull()
	{
		$this->_saveArticleWithAuthor();
		self::$_dbConnection->articles->insert( array ( 'title' => 'test', 'author' => null ) );
	
		$articles = \Model\Article::getMapper()->find()->join( 'author', '\Model\Author' )->get();
		$article = array_shift( $articles );
	
		$this->assertAttributeNotEmpty( 'author', $article );
		$this->assertAttributeInstanceOf( '\Model\Author', 'author', $article );
		
		$article = array_shift( $articles );
		$this->assertAttributeSame( null, 'author', $article );	
	
	}	
	
	protected function _getSimpleData()
	{
		return array( 'test' => 'test' );
	}
	
	protected function _getListData()
	{
		return array(
				array( 'letter' => 'b', '_id' => new \MongoId( '51d57f68b7846c9816000003' ) ),
				array( 'letter' => 'c'),
				array( 'letter' => 'a')
				);
	}
	
	protected function _saveSimpleData()
	{
		self::$_dbConnection->simple->insert( $this->_getSimpleData() );
		return $this->_getSimpleData();
	}
	
	protected function _saveListData()
	{
		self::$_dbConnection->simple->batchInsert( $this->_getListData() );
		return $this->_getListData();		
	}
	
	protected function _saveArticleWithAuthor()
	{
		$author = array( 'name' => 'test' );
		self::$_dbConnection->authors->save( $author );
		self::$_dbConnection->articles->insert( array ( 'title' => 'test', 'author' => $author['_id'] ) );
	}
	
}