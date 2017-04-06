<?php
use Think\Model;

// 揭海报 模型
class ExposePosterLogModel extends CommonModel {

	// protected $tableName = 'expose_poster_log';

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
	
	/*
	 * 获取 我的海报 列表
	 */
	// function getMyPosters(){
		// $token = I('post.token');
		// $version = I('post.version');
		// $userId = I('post.userId');
		// $myLng = I('post.myLng','','float2int');
		// $myLat = I('post.myLat','','float2int');
		// $type = I('post.type');
		// $id = I('post.id');
		// $pageSize = I('post.pageSize');
		
		// $map[''] = '';
	// }
}