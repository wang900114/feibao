<?php

class CommentsNewsCarouselViewModel extends CommonViewModel {
	public $viewFields = array(
		'CommentsNewsCarousel' => array('id', 'content', 'time'),
		'Members' => array('id'=>'userId', 'name', 'image'=>'head', '_on'=>'Members.id=CommentsNewsCarousel.userId'),
	);
}