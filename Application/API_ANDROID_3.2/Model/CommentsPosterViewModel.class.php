<?php

class CommentsPosterViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsPoster' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsPoster.userId'),
	);
}