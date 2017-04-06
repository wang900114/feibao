<?php
//会员 消息 列表
class MessageUserViewModel extends CommonViewModel {

	public $_validate	=	array();

	//表示本Model是由 lu_message、lu_message_user 两个表合并而来的
   public $viewFields = array(
			'Message'=>array(
						'id'=>'id2',
						'addTime'=>'addTime2',
						'content','startTime','endTime','type'
					),
			'MessageUser'=>array(
						'id','addTime',
						'userId','dataId','isRead',
						'_on'=>'Message.id=MessageUser.dataId'
						),
		);
}