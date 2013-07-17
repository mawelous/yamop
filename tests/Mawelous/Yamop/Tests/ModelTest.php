<?php
namespace Mawelous\Yamop\Tests;

use \Mawelous\Yamop\Model;

class ModelTest extends BaseTest
{

	protected $_articleId;
	protected $_authorId;
	protected $_reviewIds;
	protected $_timestamp;

	public function setUp()
	{
		$this->_articleId = new \MongoId();
		$this->_authorId = new \MongoId();
		$this->_reviewIds = array( new \MongoId(), new \MongoId() );
		$this->_timestamp = strtotime( '13-12-2012' );		
	}
	
	public function testFill()
	{
		$article = $this->_getArticle();
		$articleData = $this->_getArticleData();
		$commentData = $this->_getCommentData();
		
		$this->assertSame( $articleData[ 'title' ], $article->title );
		$this->assertSame( $articleData[ 'text' ], $article->text);
		$this->assertInstanceOf( '\Model\Note', $article->note);
		
		$comment = current( $article->comments);
		$this->assertInstanceOf( '\Model\Comment', $comment );
		$this->assertSame( $commentData[ 'text' ], $comment->text );
		$this->assertSame( $commentData[ 'date' ]->sec, $comment->date->sec );
		
		return $article;
	}
	
	public function testGetMapper()
	{
		$this->assertInstanceOf( 'Mawelous\Yamop\Mapper', \Model\Article::getMapper() );
		$this->assertInstanceOf( '\Mapper\AuthorMapper', \Model\Author::getMapper() );
	}
	
	public function testCollectionName()
	{
		$this->assertSame( 'authors', \Model\Author::getCollectionName() );
	}
	
	/**
	 * @expectedException Exception
	 */	
	public function testNoCollectionName()
	{
		\Model\Comment::getCollectionName();
	}
	
	/**
	 * @depends testFill
	 */	
	public function testSave( \Model\Article $article )
	{
		$result = $article->save();
		
		$this->assertArrayHasKey( 'ok', $result );
		$this->assertEquals( 1, $result[ 'ok' ] );
		$this->assertObjectHasAttribute( 'id', $article );
		$this->assertObjectHasAttribute( '_id', $article );
		
		$rawArticle = self::$_dbConnection->articles
			->findOne( array( '_id' => $article->_id ) );
		
		$this->assertInternalType( 'array', $rawArticle );

		return $article;
		
	}
	
	/**
	 * @depends testSave
	 */	
	public function testSaveWithoutStringIds( \Model\Article $article )
	{

		$rawArticle = self::$_dbConnection->articles
			->findOne( array( '_id' => $article->_id  ) );
		
		$this->assertFalse( isset( $rawArticle['id'] ));
		$this->assertFalse( isset( $rawArticle['author']['id'] ));
		$this->assertFalse( isset( $rawArticle['comments'][0]['id'] ));
		
	}
	
	public function testRemove()
	{
		$article = $this->_insertArticle();		
		$article->remove();
		
		$result = self::$_dbConnection->articles->findOne( array( '_id' => $article->_id  ) );

		$this->assertSame( null, $result );
	}
	
	public function testFindById()
	{
		$article = $this->_insertArticle();

		$dbArticleByString = \Model\Article::findById( $article->id );
		$dbArticleByMongoId = \Model\Article::findById( $article->_id );

		$this->assertInstanceOf( '\Model\Article', $dbArticleByString );
		$this->assertInstanceOf( '\Model\Article', $dbArticleByMongoId );
		
	}
	
	public function testFindOne()
	{
		$article = $this->_insertArticle();
		
		$result = \Model\Article::findOne( array ('title' => $article->title ) );
		
		$this->assertInstanceOf( '\Model\Article', $result);
		$this->assertEquals( $article->id, $result->id );
	}
	
	public function testFind()
	{
		$article = $this->_insertArticle();
		
		$result = \Model\Article::find( array ('title' => $article->title ) );
		
		$this->assertInstanceOf( '\Mawelous\Yamop\Mapper', $result);
		
		$cursor = $result->getCursor();
		
		$this->assertEquals( 1, count( $cursor ) );
		
	}
	
	public function testJoinOneWithFieldName()
	{
		$article = $this->_getArticle();
		$author = $this->_insertAuthor();
		
		$article->joinOne( 'author_id', '\Model\Author', 'author' );
		$this->assertAttributeInstanceOf( '\Model\Author', 'author', $article );		
		
	}
	
	public function testJoinOneWithoutFieldName()
	{
		$article = $this->_getArticle();
		$author = $this->_insertAuthor();
	
		$article->joinOne( 'author_id', '\Model\Author' );
		$this->assertAttributeInstanceOf( '\Model\Author', 'author_id', $article );
		
	}

	public function testJoinOneWithLimitedFields()
	{
		$article = $this->_getArticle();
		$author = $this->_insertAuthor();
		
		$article->joinOne( 'author_id', '\Model\Author', 'author', array( 'name' ) );
	
		$article->joinOne( 'author_id', '\Model\Author' );
		$this->assertAttributeInstanceOf( '\Model\Author', 'author', $article );
		$this->assertFalse( isset( $article->author->email ) );
	
	}	
	
	public function testJoinManyWithFieldName()
	{
		$article = $this->_getArticle();
		$article->review_ids = $this->_reviewIds;
		$this->_insertReviews();
		
		$article->joinMany( 'review_ids', '\Model\Review', 'reviews' );
		
		$this->assertInternalType( 'array', $article->reviews );	

		$review = array_shift( $article->reviews );
		$this->assertInstanceOf( '\Model\Review', $review );		
		
	}
	
	public function testJoinManyWithoutFieldName()
	{
		$article = $this->_getArticle();
		$article->reviews = $this->_reviewIds;
		$this->_insertReviews();
	
		$article->joinMany( 'reviews', '\Model\Review' );
	
		$this->assertInternalType( 'array', $article->reviews );
	
		$review = array_shift( $article->reviews );
		$this->assertInstanceOf( '\Model\Review', $review );
	
	}	
	
	public function testJoinManyWithLimitedFields()
	{
		$article = $this->_getArticle();
		$article->reviews = $this->_reviewIds;
		$this->_insertReviews();
	
		$article->joinMany( 'reviews', '\Model\Review', 'reviews', array( 'title' ) );
	
		$this->assertInternalType( 'array', $article->reviews );
	
		$review = array_shift( $article->reviews );
		$this->assertInstanceOf( '\Model\Review', $review );
		$this->assertFalse( isset( $review->text ) );
	
	}	
	
	public function testDateFormat()
	{
		$article = $this->_insertArticle();
		
		$this->assertSame( date( Model::$dateFormat, $this->_timestamp ), $article->getDate( 'date' ) );
		$this->assertSame( date( 'Y', $this->_timestamp ), $article->getDate( 'date', 'Y' ) );
	}
	
	public function testTimeFormat()
	{
		$article = $this->_insertArticle();
	
		$this->assertSame( date( Model::$timeFormat, $this->_timestamp ), $article->getTime( 'date' ) );
		$this->assertSame( date( 'Y-m-s H:i', $this->_timestamp ), $article->getDate( 'date', 'Y-m-s H:i' ) );
	}	
	
	protected function _getArticle()
	{
		$article = new \Model\Article;
		$article->fill( $this->_getArticleData() );
		return $article;
	}
	
	protected function _getAuthor()
	{
		$author = new \Model\Author;
		$author->fill( $this->_getAuthorData() );
		return $author;
	}	
	
	protected function _getCommentData(){
		return array ( 'date' => new \MongoDate( 12345 ),
			'text' => 'Comment text');
	}
	
	protected function _getAuthorData(){
		return array ( 
				'_id' => $this->_authorId,
				'name' => 'John Doe',
				'email' => 'john@mail.com');
	}	
	
	protected function _getArticleData() 
	{
		return array(
			'_id' => $this->_articleId,
			'author_id'=> $this->_authorId,		 
			'title' => 'Lorem',
			'text' => 'Sample text',
			'note' => array( 'text' => 'Note text'),
			'comments' => array ( $this->_getCommentData() ),
			'date' => new \MongoDate( $this->_timestamp )
			);	
	}
	
	protected function _insertArticle()
	{
		$article = $this->_getArticle();
		self::$_dbConnection->articles->insert( $article );
		return $article;		
	}
	
	protected function _insertAuthor()
	{
		$author = $this->_getAuthor();
		self::$_dbConnection->authors->insert( $author );
		return $author;		
	}
	
	protected function _insertReviews()
	{
		
		$reviewsData = array(
			array( '_id'   => $this->_reviewIds[0],
				   'title' => 'review1',
					'text' => 'text' ),
			array( '_id'   => $this->_reviewIds[1],
				   'title' => 'review2',
				   'text'  => 'text' )			
				);
		
		self::$_dbConnection->reviews->batchInsert( $reviewsData );
		
	}
	
}