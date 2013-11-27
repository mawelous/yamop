# Yamop
### Yet another MongoDB ODM for PHP

- [What's that?](#whatsthat)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [getting](#getting)
    - [save, update, delete](#save)
    - [embedded objects](#embedded)
    - [relations](#related)
    - [output format](#output)
    - [pagination](#pagination)
    - [timestamps](#timestamps)
    - [dates and time](#datetime)
    - [transactions](#transactions)
    - [advanced](#advanced)
        - [multiple connections](#multiple_connections)
- [Issues](#issues)
- [License](#license)

<a name="whatsthat"></a>
## What's that? 
This is yet another, open source, and very simple ODM for [MongoDB](http://www.mongodb.org/).
It works like the standard MongoDB PHP extension interface but returns objects instead of arrays (as ODM). Queries stay the same.
One of its coolest features are joins which allow you to query for related objects.

List of features:

- [String IDs](#stringid) (easier linking in views)
- [Embedded objects](#embedded)
- [Related objects](#related) (performing "join like" operations)
- [JSON format](#output)
- [Paginator](#pagination)
- [Timestamps](#timestamps) (created_at and updated_at fields added on demand)
- [Printing date and time](#datetime)
- [Transactions](#transactions) (PHP error support only)

<a name="requirements"></a>
## Requirements
+ PHP 5.3+
+ PHP MongoDB Extension

<a name="installation"></a>
## Installation 

You can simply download it [here](https://github.com/mawelous/yamop) or use [Composer](http://getcomposer.org/).

In the `require` key inside the `composer.json` file add the following

```yml
    "mawelous/yamop": "dev-master"
```

Save it and run the Composer update command

    $ composer update

After Composer is done you only need to add the following lines to your code

```php
    $connection = new \MongoClient( 'your_host' );
    \Mawelous\Yamop\Mapper::setDatabase( $connection->your_db_name );
```

You can pass any `MongoDB` instance to the `setDatabase` function.

Now extend `Mawelous\Yamop\Model` from within any of your models:

```php
    class User extends \Mawelous\Yamop\Model
    {
        protected static $_collectionName = 'users';    
    }
```

That's it!

<a name="usage"></a>
##Usage

Each object has an `_id`, which is a `MongoId`, and an `id` key which is its string representation.

Every document in `MongoDB` is returned as an object, every key is a property - here a sample document inside `MongoDB`

```json
     {
       "_id": ObjectId("51b6ea4fb7846c9410000001"),
       "name": "John Doe",
       "birthdate": ISODate("2013-05-25T12:15:25.0Z"),
       "email": "john@something.com"
    }    
```
The document above would be represented in PHP as follows:

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
There are two possibilities to pass properties to object

```php
    // properties as array
    $user = new User( array( 'name' => 'John', 'email' => 'email@email.com' ) );
    
    // or each property separately
    $user = new User;
    $user->name = 'John';
    $user->emial = 'email@email.com';
```

<a name="getting"></a>
### Getting data
Want to get a document by its id? There is a simple way.

<a name="stringid"></a>
```php
    $user = User::findById( '51a61930b7846c400f000002' )
    //or
    $mongoId = new MongoId( '51a61930b7846c400f000002' );
    $user = User::findById( $mongoId )
```

Getting one document by query is simple too. Method `findOne` works exactly like native [`findOne`](#http://php.net/manual/en/mongocollection.findone.php) but it returns an object. As second parameter you can pass an array of fields. This means the parameters and queries stay the same, which is pretty great!

```php
    $user = User::findOne( array( 'email' => 'user@mail.com' ) );
    //or
    $user = User::findOne( array( 'email' => 'user@mail.com' ), array( 'email', 'username', 'birthdate' ) );
```

#### Introducing Mapper
There is a `Mapper` class in Yamop which is responsible for retrieving data. I separated it from `Model` so it can stay as data container. If you want to create more complicated queries you want to use the mapper. You can get it by using the `getMapper` method or creating new instance of it passing model class as string.

```php
    //first possibility
    $mapper = User::getMapper();
    //second possibility
    $mapper = new Mawelous\Yamop\Mapper( 'User' );
```

#### Find methods
`findOne` introduced before for `Model` is `Mapper's` method. `Model` just refers to it. You could call it like this

```php   
    //findOne with Mapper
    $user = User::getMapper()->findOne( array( 'email' => 'user@mail.com' ) );
```

There is a `find` method that gets more then one document. It also works like native [`find`](#http://www.php.net/manual/en/mongocollection.find.php) but it returns a `Mapper`. You can then perform other operations on it like `sort`, `limit`, `skip` which all work like native as well.
To get result as array of objects use `get` method.

```php
    //You can call it directly with Model
    $messages = Message::find( array( 'to_id' => new MongoId( $stringId ), 'to_status' => Message::STATUS_UNREAD ) )
        ->sort( array( 'created_at' => -1 ) )
        ->limit( 10 )
        ->get(); 

    //or using Mapper itself
    $messages = Message::getMapper()
        ->find( array( 'to_id' => new MongoId( $stringId ), 'to_status' => Message::STATUS_UNREAD ) )
        ->sort( array( 'created_at' => -1 ) )
        ->limit( 10 )
        ->get(); 
```

`findAndModify` is equivalent to native [`findAndModify`](#http://www.php.net/manual/en/mongocollection.findandmodify.php) but serves objects.

<a name="save"></a>
### Save, Update and Delete
`save` method is used to create and update objects. That's the code to create new object and write it to the database

```php
    $user = new User( array( 'name' => 'John', 'email' => 'email@email.com' ) );
    $user->save();
```
You can get `_id` of newly created object just after `save`.

Deleting is simple

```php
    $user->remove();
```

Those methods return the same results as the native `remove` and `save` methods. If you want to update multiple documents use the native function like [here](#multiple-update).

### Extending Mapper
You can extend `Mapper` if you want to add more methods. For example I created UserMapper which has a method that posts a message on an user's Facebook wall. Just let `Mapper` know which `Model` class to use.

```php
class UserMapper extends Mawelous\Yamop\Mapper
{   
    protected $_modelClassName = 'User';    
    
    public function findActiveUsers( $limit = 10, $sort = 'birthdate' )
    {
        //method code
    }    
}    
```

If you want to register a different `Mapper` for a model just declare it in the model

```php
class User extends Model
{
    ...
    protected static $_mapperClassName = 'UserMapper';
    ...
```

Now you just execute the `Mapper`

```php
    $mapper = User::getMapper();
    //and then
    $mapper->findActiveUsers( 5 );
```

This will return an instance of UserMapper. You can also just create a new mapper

```php
    $userMapper = new UserMapper; 
    //and then
    $userMapper->findActiveUsers( 5 );
```

<a name="multiple-update"></a>
### Count, Indexes, multi update and others

All methods called on `Mapper` that are not present are passed to the original [`MongoCollection`](#http://php.net/manual/en/class.mongocollection.php). So you can use `update`, `count`, `batchInsert`, `ensureIndex` and even `drop` directly with the native methods.

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
    //drop
    Contest::getMapper()->drop();
```

<a name="embedded"></a>
### Embedded objects

Do you have more objects within the current object? Yamop will convert it automatically. Just let it know.

```php
class User extends \Mawelous\Yamop\Model
{
    protected static $_collectionName = 'users';

    // One Address object embedded in address property
    protected static $_embeddedObject = array (
        'address' => 'Address',
    );
    // Many Notification objects embedded in array that is kept ass notifications
    protected static $_embeddedObjectList = array (
        'notifications' => 'Notification',
    );
}     
```
Then it will convert object embedded in `address` field to `Address` PHP object and `notifications` array of objects to array of `Notification` PHP objects. All embedded objects can be pure models - they can only extend `\Mawelous\Yamop\Model`.


<a name="related"></a>
### Related objects

If there are relations between objects (there are sometimes) and you would like to "join" them, it's simpler than you would expect, even with `MongoDB`. All you need is to keep the `MongoId` of the related object within your base object.

You don't have to register it anywhere. In my opinion it's better to do this explicit and avoid queries in background. 

Here's the magic:

#### One

The `joinOne` method in every `Model` takes three parameters. First is the name of the property which keeps the `MongoId` of the related object, second is the related objects class, and third, optional, is the property name it will be joined at.

```php
    // contest_id property holds MongoId of related Contest object
    $user = User::findById( new MongoId( $stringId ) )->joinOne( 'contest_id', 'Contest', 'contest')
    // and there it is
    $contest = $user->contest;
```

#### Many

The `joinMany` method in every `Model` has also three parameters. First is the name of the property which keeps an array of `MongoId`, second is the related objects class, and third, optional, is the property name it will be joined at.

```php
    // contests field is array of MongoIds
    $user = User::findById( new MongoId( $stringId ) )->joinMany( 'contests', 'Contest', 'contests')
    // and you have array of contests there
    $contests = $user->contests;
```

If you want to join items to a list of items use `join` in a `Mapper`. Three parameters as in `joinOne`.

```php
    $commentsList = Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->join( 'user_id', 'User', 'author' )
        ->limit( 10 )
        ->get();
```

<a name="output"></a>
### Output format

Default fetching mode converts arrays to objects but you can also get array or JSON with `getArray` and `getJson`.
As default `getArray` returns array with keys holding `MongoId` as string. If you want to receive numeric array call it with false param `getArray(false)`

```php
    //first possibility
    Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->getArray();
        
    Comment::getMapper()
        ->find( array( 'contest_id' => new MongoId( $contestId ) ) )
        ->getJson();
    
    /* second possibility
        four fetch types as constants in Mapper
        FETCH_OBJECT
        FETCH_ARRAY
        FETCH_NUMERIC_ARRAY
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

You can also get the native `MongoCursor` by calling the `getCursor` method.

<a name="pagination"></a>
### Pagination

Yamop supports pagination with a little help from you. It has a `getPaginator` method which has three parameters. First is the amount of items per page, second is the current page number, and the third is a variable which you can pass to your paginator. All three are optional.

```php
    User::getMapper()
        ->find( 'status' => array ( '$ne' => User::STATUS_DELETED )) )
        ->sort( array( $field => $direction ) )
        ->getPaginator( $perPage, $page, $options );
```

Your framework probably has its own paginator. Before you use the `getPaginator` method you have to implement the `_createPaginator` method in a mapper that extends `Mawelous\Yamop\Mapper`.

[Laravel](http://laravel.com) would be extended like this:

```php
<?php

class Mapper extends \Mawelous\Yamop\Mapper
{
    protected function _createPaginator($results, $totalCount, $perPage, $page, $options)
    {
        return \Paginator::make( $results, $totalCount, $perPage ); 
    }
}
```

<a name="timestamps"></a>
### Timestamps

It's common to have a `created_at` and `updated_at` key in our objects. If you want to have them be set automatically for your `Model`, just declare it:

```php
class User extends Model
{
    ...
    public static $timestamps = true;   
    ....
```

<a name="datetime"></a>
### Printing date and time

Whether you have a timestamp or not, you might still like to print the date or time. It's recommend to keep dates as `MongoDate` this way you can echo it with `getTime` or `getDate` which takes two parameters. First is the `MongoDate` property name, second is a string that represents format passed to the PHP `date` function:

```php
    //date as string
    $user->getDate( 'birthdate', 'Y/m/d' );
    //time as string
    $user->getTime( 'created_at', 'Y/m/d H:i');
    //time as string using default format set in $dateFormat
    $user->getTime( 'created_at' );    
```

`Mawelous\Yamop\Model` has its default date format defined in the public static `$dateFormat` property and a time format in `$timeFormat`. You can override it if you like.

<a name="transactions"></a>
### Transactions

**EXPERIMENTAL!** - It's an addition to Yamop which works independently. It doesn't support a [two phase commit](#http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/) but at least it can revert changes.

That's what `Mawelous\Yamop\Transaction` is for. First you have to handle errors and run the `rollback` method within it. 

Similar to this:

```php
    set_error_handler( function($code, $error, $file, $line) {
        Transaction::rollback();
        require_once path('sys').'error'.EXT;
        Laravel\Error::native($code, $error, $file, $line);
    });
```

Then you can start using the `add` method. With `add` you add code to revert changes you made with save or update. You can use a closure to do that. 

Here an example:

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
Now when error happens `rollback` will invoke all added methods.

<a name="advanced"></a>
### Advanced

<a name="multiple_connections"></a>
#### Multiple connections
It's simple to have different connections for models. `setDatabase` can take `MongoDb` or array of `MongoDbs` as param. Keys in array are connection names.
```php
    $db1 = ( new \MongoClient( 'your_first_host' ) )->first_db;
    $db2 = ( new \MongoClient( 'your_second_host' ) )->second_db;
    \Mawelous\Yamop\Mapper::setDatabase( array( 'default' => $db1, 'special' => $db2 ) );
```
This is how you specify connection name in model.
```php
    protected static $_connectionName = 'special';
```
If it's not specified model will use first connection.

<a name="issues"></a>
## Issues

Any issues or questions please [report here](https://github.com/Mawelous/yamop/issues)

<a name="license"></a>
## License

Yamop is free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT)
