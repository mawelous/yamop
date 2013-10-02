<?php
namespace Mawelous\Yamop;

/**
 * Represents object fetched from database
 *
 * @author Kamil Zieliński <kamilz@mawelous.com>
 */
class Model
{
	/**
	 * Collection name. Set it for each model.
	 * 
	 * @var string
	 */
	protected static $_collectionName = '';
	
	/**
	 * Database connection name set in mapper
	 *
	 * @var string
	 */
	protected static $_connectionName = null;	
	
	/**
	 * Specifies if created_at and updated_at fields
	 * should be added and serve automatically 
	 * @var bool
	 */
	public static $timestamps = false;
	
	/**
	 * Information about mapper class name
	 * @var string
	 */
	protected static $_mapperClassName = 'Mawelous\Yamop\Mapper';
	
	/**
	 * Informations about embedded object list
	 * that will be created while this object creation.
	 * Array key is a name of field that keeps array of objects.
	 * Array value specifies Model class to which objects should be mapped.
	 * 
	 * <code>
	 * 	protected static $_embeddedObjectList = array (
	 *		'tags'          => 'HackerTag',
	 *		'notifications' => 'Notification',
	 *	);	
	 * </code>
	 * 
	 * @var array
	 */
	protected static $_embeddedObjectList = array();
	
	/**
	 * Informations about embedded object 
	 * that will be created while this object creation.
	 * Array key is a name of field that keeps object.
	 * Array value specifies Model class to which objects should be mapped.
	 *
	 * <code>
	 *  protected static $_embeddedObject = array (
	 *		'address' => 'Address',
	 *  );	
	 * </code>
	 *
	 * @var array
	 */	
	protected static $_embeddedObject = array();
	
	/**
	 * Format of date to return with getDate funciton
	 * 
	 * @var string
	 */
	public static $dateFormat = 'm/d/y'; 	

	/**
	 * Format of time to return with getTime cunftion
	 * @var string
	 */
	public static $timeFormat = 'm/d/y H:i';

	/**
	 * Sets object variables
	 * 
	 * @param array $array
	 */
	public function __construct( $array = array() )
	{
		$this->fill( $array );
	}
	
	/**
	 * Fills object with variables.
	 * Sets embedded objects if they are registered in
	 * $_embeddedObjectLisr or $_embeddedObject.
	 * 
	 * @param array $array
	 */
	public function fill( $array = array() )
	{
		if ( !empty( $array ) ) {
			foreach ( $array as $key => $value ) {
				//checks if field contains embedded object list
				if( in_array( $key, array_keys ( static::$_embeddedObjectList ) ) && is_array( $value ) ) {
					$this->{$key} = array();
					foreach ( $value as $eKey => $eData ) {
						if( is_array( $eData ) ){
							$this->{$key}[ $eKey ] = new static::$_embeddedObjectList[ $key ] ( $eData );
						}
					}	
				}elseif( in_array( $key, array_keys ( static::$_embeddedObject ) ) && is_array( $value ) ) {
					$this->{$key} = new static::$_embeddedObject[ $key ] ( $value );
				}else {			
					$this->$key = $value;
				}
			}
			if ( !empty( $this->_id ) ) {
				$this->id = (string) $this->_id;
			}
		}
	}
	
	/**
	 * Gets proper Mapper for object
	 * 
	 * @param int $fetchType Fetch type as specified in Mapper constants
	 * @return Mapper
	 */
	public static function getMapper( $fetchType = Mapper::FETCH_OBJECT )
	{
		return new static::$_mapperClassName( get_called_class(), $fetchType );
	}
	
	/**
	 * Gets colletion name for model 
	 * It should be specified in $_collectionName
	 * 
	 * @throws \Exception
	 * @return string
	 */
	public static function getCollectionName()
	{
		if( empty( static::$_collectionName ) ){
			throw new \Exception( 'There\'s no collection name for ' . get_called_class() );
		}
		return static::$_collectionName;		
	}
	
	/**
	 * Gets database connection name for model
	 * It should be specified in $_databaseConnection
	 *
	 * @return string
	 */
	public static function getConnectionName()
	{
		return static::$_connectionName;
	}	

	/**
	 * Saves object to database.
	 * Adds timestamps if wanted in $timestamps.
	 * 
	 * @return mixed MongoCollection save result
	 */
	public function save()
	{
		if ( static::$timestamps ) {
			if ( !$this->hasMongoId() ) {
				$this->created_at = new \MongoDate();
			}
			$this->updated_at = new \MongoDate();
		}

		if( ! $this->hasMongoId() ){
			$this->_id = new \MongoId();
		}
		
		unset( $this->id );
		
		//deleteng string ids in embedded objects
		foreach ( static::$_embeddedObject as $fieldName => $embeddedObject ){
			if( isset( $this->$fieldName ) && !empty ( $this->$fieldName ) ){
				unset ( $embeddedObject->id );
			}
		}		
		
		foreach ( static::$_embeddedObjectList as $fieldName => $class ){
			if( isset( $this->$fieldName ) && !empty ( $this->$fieldName ) ){
				foreach( $this->$fieldName as $embeddedObject ){
					unset ( $embeddedObject->id );
				}
			}
		}
		
		$result = static::getMapper()->save( get_object_vars( $this ) );
		$this->id = (string) $this->_id;
		return $result;
		
	}
	
	/**
	 * Removes object form database, totally.
	 * 
	 * @return bool|array MongoCollection remove result
	 */	
	public function remove()
	{
		return static::getMapper()->remove( array( '_id' => $this->_id ) );
	}

	/**
	 * Gets object by its id.
	 * 
	 * @param string|MongoId $id Can be passed as string or MongoId
	 * @return Model
	 */
	public static function findById( $id )
	{
		return static::getMapper()->findById( $id );
	}
	
	/**
	 * Gets object by query.
	 * Refferer to Mapper's findOne
	 *
	 * @param array $query Query as for findOne in mongodb driver
	 * @param array $fields
	 * @return Model
	 */
	public static function findOne( $query = array(), $fields = array() )
	{
		return static::getMapper()->findOne( $query, $fields );
	}	
	
	/**
	 * Refferer to Mapper's find.
	 * Gets Mapper object with cursor set.
	 *
	 * @param array $query Query as for findOne in mongodb driver
	 * @param array $fields
	 * @return Model
	 */
	public static function find( $query = array(), $fields = array() )
	{
		return static::getMapper()->find( $query, $fields );
	}	
	
	/**
	 * Checks if _id is set in object
	 * @return boolean
	 */
	public function hasMongoId()
	{
		return isset( $this->_id ) && !empty( $this->_id );
	}

	/**
	 * Return date as string - converts MongoDate
	 *  
	 * @param string $field Field name that keeps date
	 * @param string $format Format for date function
	 * @return srting
	 */
	public function getDate( $field, $format = null )
	{
		$format = $format == null ? static::$dateFormat : $format;		
		return isset($this->$field->sec) ? date( $format, $this->$field->sec ) : null;
	}

	/**
	 * Return time as string - converts MongoDate
	 *
	 * @param string $field Field name that keeps time
	 * @param string $format Format for date function
	 * @return srting
	 */	
	public function getTime( $field, $format = null )
	{
		$format = $format == null ? static::$timeFormat : $format;
		return isset($this->$field->sec) ? date( $format, $this->$field->sec ) : null;
	}
	
	/**
	 * Sets id (both string and MongoId version) 
	 * @param string|MongoId $id
	 */	
	public function setId($id)
	{
		if(!$id instanceof \MongoId){
			$id = new \MongoId($id);
		}
		
		$this->_id = $id;
		$this->id = (string) $id;
	}
	
	/**
	 * Perform join like operation to many objects.
	 * Allows to get all object related to this one
	 * by array of mongo ids that is kept in $variable
	 * 
	 * As simple as that
	 * <code>
	 * 	$user->joinMany( 'posts', 'Post' ); 	
	 * </code>
	 * 
	 * @param string $variable Field keeping array of mongo ids
	 * @param string $class Model class name of joined objects
	 * @param string $toVariable Variable that should keep new results, same if null given.
	 * @param array $fields Fields to return if you want to limit 
	 * @return Model
	 */
	public function joinMany( $variable, $class, $toVariable = null, $fields = array() )
	{		
		if( isset( $this->$variable ) && is_array( $this->$variable ) && !empty( $this->$variable ) ){
			if( empty( $toVariable ) ){
				$toVariable = $variable;
			}			
			$this->$toVariable = $class::getMapper()->find( array ('_id' => array ('$in' => $this->$variable) ), $fields )->get();
		}
		return $this;		
	}
	
	/**
	 * Perform join like operation to one object.
	 * Allows to get object related to this one
	 * by MongoId that is kept in $variable
	 *
	 * As simple as that
	 * <code>
	 * 	$user->joinOne( 'article', 'Article' );
	 * </code>
	 *
	 * @param string $variable Field keeping MongoId of object you want to join
	 * @param string $class Model class name of joined object
	 * @param string $toVariable Variable that should keep new result, same if null given.
	 * @param array $fields Fields to return if you want to limit
	 * @return Model
	 */	
	public function joinOne( $variable, $class, $toVariable = null, $fields = array() )
	{
		if( isset($this->$variable) && !empty( $this->$variable ) ){
			if( empty( $toVariable ) ){
				$toVariable = $variable;
			}
			$this->$toVariable = $class::getMapper()->findOne( array ('_id' => $this->$variable ), $fields );
		}
		return $this;
	}	

}
