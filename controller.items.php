<?php

require_once 'common.php';

class items{
	public function getAll(){

		global $objGET;
		$since_id = isset($objGET->since_id)?$objGET->since_id:-1;
		$timestamp = isset($objGET->timestamp)?$objGET->timestamp:-1;

		echo JSON(getItems(array(
			'unread'=>$objGET->unread,
			'timestamp'=>$timestamp,
			'since_id'=>$since_id
		)));
	}
	public function getFeed(){
		global $objGET;

		$since_id = isset($objGET->since_id)?$objGET->since_id:-1;
		$timestamp = isset($objGET->timestamp)?$objGET->timestamp:-1;

		echo JSON(getItems(array(
			'unread'=>$objGET->unread,
			'feed_id'=>$objGET->id,
			'timestamp'=>$timestamp,
			'since_id'=>$since_id
		)));
	}
	public function getFeeds(){
		global $objGET;
		$since_id = isset($objGET->since_id)?$objGET->since_id:-1;
		$timestamp = isset($objGET->timestamp)?$objGET->timestamp:-1;

		echo JSON(getItems(array(
			'unread'=>$objGET->unread,
			'outline_id'=>$objGET->id,
			'timestamp'=>$timestamp,
			'since_id'=>$since_id
		)));
	}
	public function getStarred(){
		global $objGET;
		$since_id = isset($objGET->since_id)?$objGET->since_id:-1;
		$timestamp = isset($objGET->timestamp)?$objGET->timestamp:-1;

		echo JSON(getItems(array(
			'starred'=>1,
			'timestamp'=>$timestamp,
			'since_id'=>$since_id
		)));
	}
}

?>
