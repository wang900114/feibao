<?php
use Think\Model;

// 发现轮播图模型
class PictureFoundModel extends CommonModel {

	// protected $tableName = '';

	/* 自动验证规则 */
	public $_validate	=	array(
		//array('imei','require','IMEI Required', self::EXISTS_VALIDATE ),
		//array('cityId','require','City ID Required', self::EXISTS_VALIDATE ),
		//array('provinceId','require','Province ID Required', self::EXISTS_VALIDATE ),
		// array('name','require','name必须！'),
	);

	/* 自动完成规则 */
	public $_auto = array(
		// array('image', 'Public/Images/member.gif', self::MODEL_INSERT),
		// array('addTime', 'time', self::MODEL_INSERT,'function'),
	);
}