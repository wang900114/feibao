<?php
use Think\Model;
//发现 评论 模型表
class CollectNewsLogViewModel extends CommonViewModel {

	public $viewFields = array(
		'CollectNewsLog' => array('id', 'addTime', 'dataId', 'status','_type'=>'LEFT' ),
		'News' => array('id'=>'tid', 'cid', 'image', 'title', 'summary', 'type', 'time', 'htmlPath'=>'detail', 'sharePath'=>'share', '_on'=>'News.id=CollectNewsLog.dataId'),
	);
}