<?php
use Think\Model;

// 本地新闻模型
class LocalNewsModel extends CommonModel {

	protected $_map = array(
		'detail' => 'htmlPath',
		'share' => 'sharePath',
	);

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
	
	/**
     * 重写 查询 操作
     * @param  array $map 查询条件
     * @param  string $limit 查询条数，默认是全部都查
     * @return array
     */
	public function selData($map,$limit=0,$field='*',$order='',$join=''){
		$map['examine'] = '1';//审核通过
		$re = parent::selData($map,$limit,$field,$order,$join);
		return $re;
	}
}