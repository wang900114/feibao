<?php
use Think\Model;
//发现 评论 模型表
class CommentsFoundModel extends CommonModel {

	public $_validate	=	array(
	    // array('name','require','必须填写城市名称'),
	    // array('firstLetter','require','必须填写首字母'),
	    // array('wordFirstLetter','require','必须填写拼音首字母'),
	    // array('fullLetter','require','必须填写全拼'),
	    // array('lng','require','必须有经度数值'),
	    // array('lat','require','必须有纬度数值'),
	);
}