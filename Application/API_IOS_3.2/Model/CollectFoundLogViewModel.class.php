<?php
use Think\Model;
//发现 评论 模型表
class CollectFoundLogViewModel extends CommonViewModel {

	public $viewFields = array(
		'CollectFoundLog' => array('id', 'addTime', 'userId', 'status','_type'=>'LEFT'),
		'Found' => array('id'=>'tid', 'image', 'title', 'time', 'htmlPath'=>'detail', 'sharePath'=>'share','lng','lat', 'hot', '_on'=>'Found.id=CollectFoundLog.dataId','_type'=>'LEFT'),
		'Members' => array('name' => 'publisher', '_on'=>'Members.id=Found.userId'),
	);
}