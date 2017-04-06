<?php

class CommentsNewsNormalViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsNewsNormal' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsNewsNormal.userId'),
	);
}