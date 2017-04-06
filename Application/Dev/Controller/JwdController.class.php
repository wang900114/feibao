<?php
use \Think\Controller;
class JwdController extends Controller {

	public function index() {
		$this->display();
	}

	public function get($id='1') {
		$id = !empty($_REQUEST[id])? $_REQUEST[id] : $id;
		$model = M ('city');
		$city = $model->where('id='.$id)->select();
		$count = $model->count();
		if($id <= $count){
			$geo = $this->geocoder($city[0]['name']);
			$map['firstLetter'] = getFirstChar($city[0]['name']);
			$map['wordFirstLetter'] = getFirstChars($city[0]['name']);
			$map['fullLetter'] = pinyin($city[0]['name']);
			$map = array_merge($map, $geo);
			$model->data($map)->where('id=' . $city[0]['id'])->save();
			$this->assign('info',$city[0]);
			$this->assign('id',$city[0]['id']);
			$this->assign('count',$count);

			session('jwdId',$city[0]['id']);

			$this->display();
		}else{
			session('jwdId','1');

			$this->show("<h1>全部处理完成</h1>");
		}


		C ( 'SHOW_RUN_TIME', 1 ); // 运行时间显示
		C ( 'SHOW_PAGE_TRACE', 1 );
	}

	/** 
	 * 获取城市中心点坐标
	 * @access private
	 * @param string city 城市名称
	 * @return array 经纬度坐标数组
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	private function geocoder($city='北京市') {
		$url = "http://api.map.baidu.com/geocoder/v2/?ak=0TntFpb8un7BL4GkN7fzsMpU&output=json&address={$city}";
		$data = file_get_contents($url);
		$data = json_decode( $data, true );
		if ( '0' == $data['status']) {
			return $data['result']['location'];
		}else{
			return array('lng'=>'', 'lat'=>'');
		}
	}

}