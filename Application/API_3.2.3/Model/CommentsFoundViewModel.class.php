<?php
class CommentsFoundViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsFound' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsFound.userId'),
	);
}