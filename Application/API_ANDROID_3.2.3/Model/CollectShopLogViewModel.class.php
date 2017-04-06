<?php
use Think\Model;
//发现 评论 模型表
class CollectShopLogViewModel extends CommonViewModel {

	public $viewFields = array(
		'CollectShopLog' => array('id', 'addTime', 'userId', 'status'),
		'Shop' => array('id'=>'tid', 'image', 'name', 'address', 'tag1', 'tag2', 'tag3', 'tag4', 'lng', 'lat', '_on'=>'Shop.id=CollectShopLog.dataId'),
	);
}