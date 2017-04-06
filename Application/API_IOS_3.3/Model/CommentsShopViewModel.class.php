<?php

class CommentsShopViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsShop' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsShop.userId'),
	);
}