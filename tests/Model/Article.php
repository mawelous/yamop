<?php 

namespace Model;

use Mawelous\Yamop\Model;

class Article extends Model
{
	protected static $_collectionName = 'articles';
	
	protected static $_embeddedObject = array(
			'note' => '\Model\Note'
	);

	protected static $_embeddedObjectList = array (
			'comments' => '\Model\Comment',
	);
}
