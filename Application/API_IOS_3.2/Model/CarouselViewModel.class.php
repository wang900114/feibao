<?php
use Think\Model\ViewModel;

// 轮播图片详情
class CarouselViewModel extends ViewModel {

	// 视图字段
	public $viewFields = array(
		'PictureTopCarousel' => array('id', 'dataId', 'image', 'addTime'),
		'News' => array('id'=>'dataId', 'cid', 'title', '_on'=>'News.id=PictureTopCarousel.dataId'),
		);
}