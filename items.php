<?php

require_once 'common.php';

function getItems($options){
	global $db;
	$default_options = array(
		'offset'=>0,
		'pagesize'=>50,
		'user_id'=>getUserId(),
		'unread'=>0,
		'starred'=>0,
		'timestamp'=> -1,
		'since_id'=> -1
	);

	$options = merge_options($default_options, $options);

	$offset = (int)$options->offset;
	$pagesize = (int)$options->pagesize;
	$user_id = (int)$options->user_id;

	$whereclause = array();

	if ($options->unread){
		$whereclause[] = "((t2.read_until_id < items.id AND t1.read IS NULL) OR t1.read=0)";
	}
	if ($options->starred){
		$whereclause[] = "(t1.starred=1)";
	}
	if($options->timestamp>0 && $options->since_id>0){
		$timestamp = (int)$options->timestamp;
		$since_id = (int)$options->since_id;
		$whereclause[] ="(items.pubDate < $timestamp OR (items.pubDate = $timestamp AND items.id < $since_id))";
	//var_dump($whereclause);	
	}

	// outline_option specifies outline_id or feed_id, otherwise all items will be listed.
	$outline_option = '';
	if (isset($options->outline_id) && $options->outline_id){
		$outline_option = 'AND t2.outline_id = '.((int)$options->outline_id);
	}
	if (isset($options->feed_id) && $options->feed_id){
		$outline_option = 'AND t2.feed_id = '.((int)$options->feed_id);
	}

	$whereclause = addWhere(join(' AND ', $whereclause));

	/*
	 * show outline
	 *
	 * $sql =<<<sqlend
		SELECT items.id, items.title, items.link, items.pubDate, items.description, items.feed_id, feeds.title AS outline, ((t2.read_until_id >= items.id AND t1.read IS NULL) OR t1.read) AS read, t1.starred FROM items 
			INNER JOIN feed_statuses AS t2 ON items.feed_id = t2.feed_id AND t2.user_id = $user_id $outline_option
			LEFT JOIN feeds ON feeds.id = items.feed_id
			LEFT JOIN item_statuses AS t1 ON t1.item_id = items.id AND t1.user_id = $user_id
			$whereclause
			ORDER BY items.pubDate DESC, items.id DESC
			LIMIT $offset, $pagesize;
sqlend;*/

	$sql =<<<sqlend
		SELECT items.id, items.title, items.link, items.pubDate, items.description, items.feed_id, ((t2.read_until_id >= items.id AND t1.read IS NULL) OR t1.read) AS read, t1.starred FROM items 
			INNER JOIN feed_statuses AS t2 ON items.feed_id = t2.feed_id AND t2.user_id = $user_id $outline_option
			LEFT JOIN item_statuses AS t1 ON t1.item_id = items.id AND t1.user_id = $user_id
			$whereclause
			ORDER BY items.pubDate DESC, items.id DESC
			LIMIT $pagesize;
sqlend;

	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute();
	$rs = $conn->fetchAll(PDO::FETCH_OBJ);

	$result = array();

	foreach($rs as $a){
		$a->read = ($a->read == 1);
		$a->starred = ($a->starred == 1);
		$result[] = $a;
	}
	return $result;
}

function setItemRead($options){
	global $db;
		$default_options = array(
		'item'=>-1,
		'user'=>-1,
		'read'=>1
	);
	$options = merge_options($default_options, $options);

	if(!is_numeric($options->user) || $options->user < 0 || !is_numeric($options->item) || $options->item < 0){
		return false;
	}
	$item = $options->item;
	$user = $options->user;

	$sql = 'SELECT id FROM item_statuses WHERE item_id=:item AND user_id=:user;';
	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute(array(':item'=>$item, ':user'=>$user));
	if(count($conn->fetchAll()) == 0){
		$sql = 'INSERT INTO item_statuses(item_id, user_id, read) VALUES(:item, :user, :read);';

	}else{
		$sql = 'UPDATE item_statuses SET read=:read WHERE item_id=:item AND user_id=:user;';
	}
	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute(array(':item'=>$item, ':user'=>$user, ':read'=>$options->read));

	$sql =<<<sqlend

UPDATE feed_statuses SET read=(
	SELECT COUNT(items.id) FROM items
		INNER JOIN feed_statuses AS t2 ON items.feed_id = t2.feed_id AND t2.user_id =:user AND t2.feed_id = (
			SELECT feed_id FROM items
				WHERE id = :item		
		)
		LEFT JOIN item_statuses AS t1 ON t1.item_id = items.id AND t1.user_id =:user
		WHERE t1.read=1 
	) 
	WHERE feed_id=(SELECT feed_id FROM items WHERE id=:item) AND user_id=:user;'

sqlend;
	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute(array(':item'=>$item, ':user'=>$user));

	return true;
}

function setItemStarred($options){
	global $db;
		$default_options = array(
		'item'=>-1,
		'user'=>-1,
		'starred'=>1
	);
	$options = merge_options($default_options, $options);

	if(!is_numeric($options->user) || $options->user < 0 || !is_numeric($options->item) || $options->item < 0){
		return false;
	}
	$item = $options->item;
	$user = $options->user;

	$sql = 'SELECT id FROM item_statuses WHERE item_id=:item AND user_id=:user;';
	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute(array(':item'=>$item, ':user'=>$user));
	if(count($conn->fetchAll()) == 0){
		$sql = 'INSERT INTO item_statuses(item_id, user_id, starred) VALUES(:item, :user, :starred);';

	}else{
		$sql = 'UPDATE item_statuses SET starred=:starred WHERE item_id=:item AND user_id=:user;';
	}
	$conn = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$conn->execute(array(':item'=>$item, ':user'=>$user, ':starred'=>$options->starred));
	return true;
}

