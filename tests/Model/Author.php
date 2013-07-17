<?php

namespace Model;

use Mawelous\Yamop\Model;

class Author extends Model
{
	protected static $_collectionName = 'authors';

	protected static $_mapperClassName = '\Mapper\AuthorMapper';
}