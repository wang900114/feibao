<?php
/*
 * 关键字统计(发现、海报、店铺)
*/
class CountController extends CommonController {
	public function _initialize(){
		header('Content-Type:text/html; charset=UTF-8'); 
		//header('Content-Type:application/json; charset=UTF-8');
	}
	/*
	 * 添加统计
	 * @param string $str 搜索关键词
	 * @param string $type 类型(1发现、2海报、3店铺)
	 * @author Jine <luxikun@andlisoft.com>
	*/
    public function index($str='',$type){
		if(!empty($str) || strlen($str)>0){
			switch($type){
				case 1:
					$keywords = D('CountKeywordsFound');
					break;
				case 2:
					$keywords = D('CountKeywordsPoster');
					break;
				case 3:
					$keywords = D('CountKeywordsShop');
					break;
			}
		
			$map['keywords'] = $str;
			$re = $keywords->selData($map);
			if(empty($re)){//新增
				$map['addTime'] = time();
				$re = $keywords->addData($map);
			}else{//加一
				$re = $keywords->setColunm($map,'number',1);
			}
		}
	}
}