# Yamop
### yet another mongo ODM for PHP

- [What's that?](#whatsthat)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [getting](#getting)
    - [deleting, updateing](#deleting)
    - [embeded objects](#embeded)
    - [relations](#related)
    - [output format](#output)
    - [pagination](#pagination)
    - [timestamps](#timestamps)
    - [dates and time](#datetime)
    - [transactions](#transactions)


<a name="whatsthat"></a>
## What's that? 
This is another, open source, simple (just 3 files) ODM for [MongoDB](http://www.mongodb.org/).
It works like standard php driver but returns objects instead of arrays (as ODM). Querying stays the same.
One of the coolest things are joins which allow you to query for related objects.
List of features:

- [String ids](#stringId) (easy to use for links etc.)
- [Embeded objects](#embeded)
- [Related objects](#related) (performing "join like" operations)
- [Json format](#output)
- [Paginator](#pagination)
- [Timestamps](#timestamps) (created_at and updated_at) fields served on demand.
- [Printing dates and time](#ditetime)
- [Transactions](#transactions) (PHP error support only)

<a name="requirements"></a>
## Requirements
+ PHP 5.3+
+ MongoDB Driver

<a name="installation"></a>
## Instalation 

You can simply download it [here](https://github.com/mawelous/yamop) or use [Composer](http://getcomposer.org/). 
That's what you need to add to `composer.json`

```yml
    "mawelous/yamop": "dev-master"
```

When you have it just those lines are needed in your code. You can pass any `MongoDB` instanse to `setDatabase` function.

```php
    $connection = new \MongoClient( 'your_host' );
    \Mawelous\Yamop\Mapper::setDatabase( $connection->db_name );
```    

Now extend `Mawelous\Yamop\Model` with any of your models:

```php
    class User extends \Mawelous\Yamop\Model
    {
        protected static $_collectionName = 'users';    
    }
```
Congratulations! That's it. You can start working with data.

<a name="usage"></a>
##Usage

Each object has `_id` which is `MongoId` and `id` which is its string representation. 

Every field in database is writen to object as property.
This is sample document.
```json
     {
       "_id": ObjectId("51b6ea4fb7846c9410000001"),
       "name": "John Doe",
       "birthdate": ISODate("2013-05-25T12:15:25.0Z"),
       "email": "john@something.com"
    }    
```
It will be converted to something like
```php
    object(User)[44]
      public '_id' => 
        object(MongoId)[46]
          public '$id' => string '51b6ea4fb7846c9410000001' (length=24)
      public 'name' => string 'John Doe' (length=8)
      public 'birthdate' => 
        object(MongoDate)[47]
          public 'sec' => int 1369484125
          public 'usec' => int 0
      public 'email' => string 'john@something.com' (length=18)
      public 'id' => string '51b6ea4fb7846c9410000001' (length=24)
  
```

### Getting data
Want to get document by id? There is a simple way.

<a name="srtingId"></a>
```php
    $stringId = '51a61930b7846c400f000002';
    $user = User::findById( $stringId )
    
    //or
    $mongoId = new MongoId( '51a61930b7846c400f000002' );
    $user = User::findById( $mongoId )
    
```
#### Introducing Mapper - find functions
There is `Mapper` class in Yamop which is responsible for getting data. I separeted it from `Model` so it can stay as data container. If you want to find object with more complicated query you need mapper. You can get it easily using `getMapper` function.
```php
    $user = User::getMapper()->findOne( array( 'email' => 'john@something.com' ) );
```
`findOne` works exactly like native [`findOne`](#http://php.net/manual/en/mongocollection.findone.php) but it returns wanted object. As as second param you can pass array of fields. So params and querying stays the same. Great!

`find` also works like native [`find`](#http://www.php.net/manual/en/mongocollection.find.php) but it returns `Mapper`. You can perform other operations on it like `sort`, `limit`, `skip` which works like native or `get` which returns array of objects
```php
    $messages = Message::getMapper()
        ->find( array( 'to_id' => new mongoId( $stringId ), 'to_status' => Message::STATUS_UNREAD ) )
        ->sort( array( 'created_at' => -1 ) )
        ->limit(10)
        ->get(); 
```
<a name="deleting"></a>
### Delete, update
Deleting is simple
```php
    $user->remove();
```
Tu update you can use `save` method
```php
    $user->email = 'new@email.com';
    $user->save();
```
Those methods return same results as native `remove` and `save`. If you want to use multiple update use native function like [this](#multiple-update).

### Mapper
You can exted `Mapper` if you want to add more methods. For example I created UserMapper with method that posts message on user's facebook wall. If you want to register different mapper for model just type in it:
```php
    protected static $_mapperClassName = 'UserMapper';    
```
And now
```php
    User::getMapper()
```
will return UserMapper instance. Of course you can just create new mapper with constructor.
```php
    $userMapper = new UserMapper;
    
    //or using default mapper
    $userMapper = new \Mawelous\Yamop\Mapper( 'User' );
```
<a name="multiple-update"></a>
### count, multiple update
All methods called on mapper that are not its own are passed to `MongoCollection` that it represents. So you can use `update`, `count`, `ensureIndex` normally.
```php
    //count
    Message::getMapper()->count( array( 'to_id' => $userId, 'to_status' => Message::STATUS_UNREAD ) );

    //update
    Contest::getMapper()->update(
            array('status' => Contest::STATUS_READY_DRAFT,
                  'start_date' => array ('$lte' => new MongoDate(strtotime('midnight')) )),
            array('$set' => array( 'status' => Contest::STATUS_ACTIVE) ),
            array('multiple' => true)
        );
```
<a name="embeded"></a>
### Embeded objects
Do you have objects within the main object? Yamop will convert it automatically. Just let him know.
```php
class User extends Model
{
    protected static $_collectionName = 'users';
    protected static $_mapperClassName = 'UserMapper';  

    //One Address object embeded in address property
    protected static $_embededObject = array (
            'address' => 'Address',
    );

    //Many Notification objects embeded in array that is kept ass notifications
    protected static $_embededObjectList = array (
        'notifications' => 'Notification',
    );  

...    
```

<a name="related"></a>
### Related objects
If there are relations between objects (sure there are) and you would like to "join" them it's simpler that you would expect.
You don't have to register it anywhere. In my opinion it's better to do this explicit and avoid queries in background. Here's the magic.

#### One

Use `joinOne` function on `Model` that takes three params. First is name of property which keeps `MongoId` of related obeject, second is related object class, and third property to which it should be writen.
```php
    $user = User::findById( new MongoId( $stringId ) )->joinOne( 'contest_id', 'Contest', 'contest')
    
    //and there it is
    $contest = $user->contest;
```
#### Many

Use `joinMany` function on `Model` with similar three params. First is name of property which keeps array of `MongoId`, second is related object class, and third property to which it should be writen.
```php
    $user = User::findById( new MongoId( $stringId ) )->joinMany( 'contests', 'Contest', 'contests')
```

If you want to join items to list of items use `join` on `Mapper`. Three params as in `joinOne`.
```php
    $commentsList = Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->join( 'user_id', 'User', 'author' )
        ->limit( 10 )
        ->get();
```
<a name="output"></a>
### Output format
Default fetching mode converts arrays to objects but you can also get array or json with `getArray` and `getJson`.
```php
    //first possibility
    Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->getArray();
        
    Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->getJson();
    
    /* second possibility
        three fetch types as constants in Mapper
        FETCH_OBJECT
        FETCH_ARRAY 
        FETCH_JSON  
    */
    Comment::getMapper( \Mawelous\Yamop\Mapper::FETCH_JSON )
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->get();
        
    /* third possibility */
    Comment::getMapper()
        ->setFetchType(\Mawelous\Yamop\Mapper::FETCH_JSON )
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->get();        
```
<a name="pagination"></a>
### Pagination
Yamop can supprot pagination with a little of your help. It has `getPaginator` method which takse two params. First is page number, second number of items per page.
```php
    User::getMapper()
        ->find( 'status' => array ( '$ne' => User::STATUS_DELETED )) )
        ->sort( array( $field => $direction ) )
        ->getPaginator( $page, $perPage );
```
Your framework probably has its paginator. Before you use `getPaginator` method you have to implement `_createPaginator` function in a mapper that extends `Mawelous\Yamop\Mapper`. I made it for Laravel like this
```php
<?php

class Mapper extends \Mawelous\Yamop\Mapper
{
    protected function _createPaginator($results, $totalCount, $perPage)
    {
        return \Paginator::make( $results, $totalCount, $perPage ); 
    }
}
```
<a name="timestamps"></a>
### Timestamps
It's common to have `created_at` and `updated_at` in our objects. It you want to get them work and set automatically for your `Model` just delare it.
```php
class User extends Model
{
    ...
    public static $timestamps = true;   
    ....
```

<a name="datetime"></a>
### Printing date and time
Whether you have timestamp or not from time to time you would like to print dates and time. I advice to keep dates as `MongoDate`. Then you can echo it with `getTime` or `getDate` methods which takse two params. First is property name, second string that represents format passed to `data` function
```php
    //date as string
    $user->getDate( 'birthdate', 'Y/m/d' );
    
    //time as string
    $user->getTime( 'created_at', 'Y/m/d H:i');
    
    //time as string using default format set in $dateFormat
    $user->getTime( 'created_at' );    
```

`Mawelous\Yamop\Model` has its default date format defined in public static `$dateFormat` property and time format in `$timeFormat`. You can override it if you want.

<a name="transactions"></a>
### Transactions

That is just experiment. It's addition to Yamop which works independently. It doesn't support [two phase commit](#http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/) but at least if something wrong happens in your php it will convert changes.
That what `Mawelous\Yamop\Transaction` is for. First you have to handle errors and run `rollback` mathod within it. I made it like this
```php
    set_error_handler( function($code, $error, $file, $line) {
        Transaction::rollback();
        require_once path('sys').'error'.EXT;
        Laravel\Error::native($code, $error, $file, $line);
    });
```
Then you can start using `add` funciton. With `add` you... add code to revert changes you made with save or update. You can use clousure to do that. Here's an example.

```php
    User::getMapper()->update(
        array('_id' => array ( '$in' => $userIds )),
        array('$inc' => array ('active_contests' => -1 )),
        array('multiple' => true)
    );
    
    Transaction::add( function () use ( $userIds ) {
        User::getMapper()->update(
            array('_id' => array ( '$in' => $userIds )),
            array('$inc' => array ('active_contests' => 1 )),
            array('multiple' => true)
            );
    });
```
That's all folks! Enjoy!
