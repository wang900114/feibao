<?php

class CommentsLocalnewsViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsLocalnews' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsLocalnews.userId'),
	);
}