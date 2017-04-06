<?php
use Think\Model;

// 新闻 模型
class NewsModel extends CommonModel {
	
	protected $_map = array(
		'detail' => 'htmlPath',
		'content' => 'htmlPath',
		'share' => 'sharePath',
	);

}