<?php
namespace Mawelous\Yamop;

/**
 * Gets data from database and returns expected results in expected type,
 * specially objects. Allows to perform join like operations.   
 * 
 * @author Kamil Zieliński <kamilz@mawelous.com>
 *
 */
class Mapper
{

	/**
	 * Represents type of fetching
	 * 
	 * @var int
	 */
	const FETCH_OBJECT = 1;
	const FETCH_ARRAY  = 2;
	const FETCH_JSON   = 3;
	
	/**
	 * Mongo connection to database
	 *
	 * @var MongoDB
	 */
	protected static $_database;
	
	/**
	 * Type of fetch method, constans defined in this class
	 * 
	 * @var int
	 */
	protected $_fetchType = self::FETCH_OBJECT;

	/**
	 * Class to return
	 *
	 * @var string
	 */
	protected $_modelClassName;
	
	/**
	 * MongoCursor returned with find
	 * 
	 * @var \MongoCursor
	 */
	protected $_cursor;
	
	/**
	 * Keeps information about join like operations to perform 
	 *  
	 * @var array
	 */
	protected $_joins = array();

	/**
	 * Sest model class name and fetch type
	 * 
	 * @param string $modelClass
	 * @param int $fetchType One of constants
	 * @throws \Exception
	 */
	public function __construct( $modelClass = null, $fetchType = self::FETCH_OBJECT )
	{	
		if( !empty( $modelClass ) ){
			$this->_modelClassName = $modelClass;
		} elseif ( empty( $this->_modelClassName ) ) {
			throw new \Exception( 'Mapper needs to know model class.' );
		}
		
		if ( !static::$_database instanceof \MongoDB ) {
				throw new \Exception( 'Give me some database. You can pass it with setDatabase function.' );
		}
		
		$this->setFetchType( $fetchType );
	}

	/**
	 * Allows to call standard MongoCollection functions
	 * like count, update
	 * 
	 * @param string $functionName
	 * @param array $functionArguments
	 */
	public function __call( $functionName, $functionArguments )
	{
		$modelClass = $this->_modelClassName;
		$collectionName = $modelClass::getCollectionName();
		return call_user_func_array(
			array( static::$_database->$collectionName, $functionName ),
			$functionArguments
		);
	}

	/**
	 * Acts exactly like MongoCollection find but
	 * sets it result to $_cursor and returns this object
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return Mapper
	 */
	public function find( $query = array(), $fields = array() )
	{
		$modelClass = $this->_modelClassName;
		$collectionName = $modelClass::getCollectionName();
		$this->_cursor = static::$_database->$collectionName->find( $query, $fields );
		return $this;
	}

	/**
	 * Acts like MongoColletsion function but returns
	 * result of expected type
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return array|string|Model
	 */
	public function findOne( $query, $fields = array() )
	{
		$modelClass = $this->_modelClassName;
		$collectionName = $modelClass::getCollectionName();
		
		switch ( $this->_fetchType ) {
			case self::FETCH_ARRAY:
				return static::$_database->$collectionName->findOne( $query, $fields );
			break;
			
			case self::FETCH_JSON:
				return json_encode( static::$_database->$collectionName->findOne( $query, $fields ) );
			break;
			
			default:
				return $this->fetchObject( static::$_database->$collectionName->findOne( $query, $fields ) );
			break;
		}		
	}

	/**
	 * Gets document by its id
	 * 
	 * @param string|MongoId $id
	 * @return array|string|Model
	 */
	public function findById( $id )
	{
		if ( !$id instanceof \MongoId ) {
			$id = new \MongoId( $id );
		}
		return $this->findOne( array( '_id' => $id ) );
	}

	/**
	 * Fetches result as object
	 * 
	 * @param array $result
	 * @return Model|null
	 */
	public function fetchObject( $result )
	{
		return is_array( $result ) ? new $this->_modelClassName( $result ) : null;
	}

	/**
	 * Performs sort on MongoCursor
	 * 
	 * @param array $array
	 * @return Mapper
	 */
	public function sort( $array )
	{
		$this->_checkCursor();
		$this->_cursor->sort( $array );
		return $this;
	}

	/**
	 * Performs limit on MongoCursor
	 *
	 * @param int $num
	 * @return Mapper
	 */	
	public function limit( $num )
	{
		$this->_checkCursor();
		$this->_cursor->limit( $num );
		return $this;
	}
	
	/**
	 * Performs skip on MongoCursor
	 *
	 * @param int $num
	 * @return Mapper
	 */	
	public function skip( $num )
	{
		$this->_checkCursor();
		$this->_cursor->skip( $num );
		return $this;
	}	
	
	/**
	 * Writes informations about joins
	 * 
	 * @param string $variable Field name in database that keeps MongoId of other object
	 * @param string $class Model name which should be created
	 * @param string $toVariable Name od variable to which it should be writen
	 * @return Mapper
	 */
	public function join( $variable, $class, $toVariable = null )
	{	
		$this->_joins[] = array ( 'variable' => $variable,
								  'class' => $class,
								  'to_variable' => $toVariable
			);
		return $this;
	}

	/**
	 * Performs proper skip and limit to get
	 * data package that can be wraped in paginator
	 * 
	 * @param int $page
	 * @param int $perPage
	 */
	public function getPaginator( $page = 1, $perPage = 10 )
	{
		$this->_checkCursor();
		$total = $this->_cursor->count();
		$this->_cursor->skip( ( $page -1 ) * $perPage )->limit( $perPage );
		$result = $this->get();
		return $this->_createPaginator($result, $total, $perPage);
	}


	/**
	 * Returns the data
	 * 
	 * @result array|string Array of arrays or json string
	 */
	public function get()
	{
		$this->_checkCursor();
		
		switch ( $this->_fetchType ) {
			case self::FETCH_ARRAY:
				$this->_checkCursor();
				return $this->_performJoins( iterator_to_array( $this->_cursor ) );
			break;
			
			case self::FETCH_JSON:
				$this->_checkCursor();
				return json_encode($this->_performJoins( iterator_to_array( $this->_cursor ) ));	
			break;			
			
			default:
				$result = array();
				foreach ( $this->_cursor as $key => $item ) {
					$result[$key] = $this->fetchObject( $item );
				}
				return $this->_performJoins($result);
			break;
		}
	}
	
	/**
	 * Gets data as array
	 * 
	 * @return array
	 */
	public function getArray()
	{
		$this->_fetchType = self::FETCH_ARRAY;
		return $this->get();
	}
	
	/**
	 * Gets data as json
	 * 
	 * @return string
	 */
	public function getJson()
	{
		$this->_fetchType = self::FETCH_JSON;
		return $this->get();		
	}
	
	/**
	 * Sets fetch type
	 * 
	 * @param int $fetchType Use one of contsnts
	 * @throws \Exception
	 * @return Mapper
	 */
	public function setFetchType( $fetchType )
	{
		if( !in_array( $fetchType, array( self::FETCH_OBJECT, self::FETCH_JSON, self::FETCH_ARRAY ) ) ){
			throw new \Exception( 'Please use of of provided methotd for fetch' );
		}
		
		$this->_fetchType = $fetchType;
		
		return $this;
	}
	
	/**
	 * Sets database. That needs to be performed before you can get any data. 
	 * 
	 * @param MongoDB $database
	 * @return void
	 */
	public static function setDatabase( \MongoDB $database )
	{
		static::$_database = $database;
	}
	
	/**
	 * Used to create your own paginator.
	 * Needs to be implemented if you want to use getPaginator function.
	 * 
	 * @param mixed $results
	 * @param int $totalCount
	 * @param int $perPage
	 * @throws \Exception
	 */
	protected function _createPaginator( $results, $totalCount, $perPage )
	{
		throw new \Exception('If you want to get paginator '.
			'please extend mapper class implementing _createPaginator function. '.
			'You have a set of params. Return whatever you want.');
	}
	
	/**
	 * Checks if cursor already exists so you can perform operations on it.
	 * 
	 * @throws \Exception
	 */
	protected function _checkCursor()
	{
		if ( empty( $this->_cursor ) ) {
			throw new \Exception( 'There is no cursor, so you can not get anything' );
		}		
	}
	
	/**
	 * Magically performs join like operations registered with join function.
	 * Allows to connect every document in cursor to the other by MongoId
	 * kept as variable in base document. 
	 * 
	 * @param array $array
	 * @throws \Exception
	 * @return array
	 */
	protected function _performJoins( array $array )
	{		
		foreach ( $this->_joins as $join ){
			
			reset( $array );
					
			$toVariable = !empty( $join['to_variable'] ) ? $join['to_variable'] : $join['variable'];
			$variable = $join['variable'];
			$class = $join['class'];

			$ids = array();
			if( !empty ( $array ) && is_object( current ( $array ) )){
				foreach ( $array as $item ){
					if( !isset( $item->$variable ) || !($item->$variable instanceof \MongoId) ){
						throw new \Exception( 'Some item doesn\'t have a variable you\'re searching for or this variable is not a MongoId instance' );
					}
					$ids[] = $item->$variable;
				}
				$joined = $class::getMapper()->find( array ( '_id' => array ('$in' => $ids) ) )->get();
				foreach ( $array as $item ){
					$item->$toVariable = $joined[ (string) $item->$variable ];
				}
			} elseif ( !empty ( $array ) ){
				foreach ( $array as $item ){
					if( !isset( $item[ $variable ] ) || !($item[ $variable ] instanceof \MongoId) ){
						throw new \Exception( 'Some item doesn\'t have a variable you\'re searching for or this variable is not a MongoId instance' );
					}
					$ids[] = $item[ $variable ];
				}
				$joined = $class::getMapper()->find( array ( '_id' => array ('$in' => $ids) ) )->getArray();				
				foreach ( $array as &$item ){
					$item[$toVariable] = $joined[ (string) $item[ $variable ] ];
				}	
			}
		}	
		return $array;
	}

}
