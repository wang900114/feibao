<?php
use Think\Model;
//发现 评论 模型表
class CollectLocalnewsLogViewModel extends CommonViewModel {

	public $viewFields = array(
		'CollectLocalnewsLog' => array('id', 'addTime', 'dataId', 'status','_type'=>'LEFT' ),
		'LocalNews' => array('id'=>'tid', 'image', 'title', 'summary', 'time', 'htmlPath'=>'detail', 'sharePath'=>'share', '_on'=>'LocalNews.id=CollectLocalnewsLog.dataId'),
	);
}