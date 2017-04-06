<?php

class CommentsNewsTopViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsNewsTop' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsNewsTop.userId'),
	);
}