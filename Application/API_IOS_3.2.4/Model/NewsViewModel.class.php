<?php
use Think\Model\ViewModel;

// 新闻详情模型
class NewsViewModel extends ViewModel {

	// 视图字段
	public $viewFields = array(
		'News' => array('id', 'image', 'title', 'cid', 'htmlPath' => 'content', 'sharePath' => 'share','summary' ),
	);

}