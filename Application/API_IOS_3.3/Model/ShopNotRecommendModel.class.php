<?php
use Think\Model;

// 店铺模型
class ShopModel extends CommonModel {

	/* 自动完成规则 */
	public $_auto = array(

	);
	
	protected $_map = array(
	);

	/* 自动验证规则 */
	public $_validate	=	array(
		array('name','require','必须填写店铺名称'),
		array('password','require','密码不能为空'),
		array('phone','require','手机号码不能为空'),
		array('phone','isMobileNumber','手机号码不合法',self::EXISTS_VALIDATE,'callback',self::EXISTS_VALIDATE),
		array('phone','isUniqueMobile','手机号码不唯一',self::EXISTS_VALIDATE,'callback',self::EXISTS_VALIDATE),
	);

	/* 
	 * 获取推荐店铺10条
	 * 以 城市id 获取后台编辑的推荐店铺列表，按后台编辑的顺序排序。
	 */
	function getRecommendStores(){
		$myLng    = I('post.myLng','','trim');
		$myLat    = I('post.myLat','','trim');
		$cityId   = I('post.cityId','','trim,intval');
		$pageSize = C('SHOP_RECOMMEND_LIMIT');

		$map = array(
			'cityId'    => $cityId,
			'status'    => '1',//店铺状态：1开业；2休业；3审核
			'examine'   => '1',//审核通过
			'recommend' => '1'//推荐店铺
		);

		$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4';
		$order  = 'sort asc';
		
		$res    = $this->selData($map,$pageSize,$field,$order);

		if( !empty($res) ){
			foreach( $res as $key => $val ){
				if( !empty($myLng) && !empty($myLat) ){
					$res[$key]['distance'] = GetDistance($myLng, $myLat, $val['lng'], $val['lat']);
				}else{
					$res[$key]['distance'] = '';
				}
				
				if(!empty($val['tag1'])) $res[$key]['tag'][] = $val['tag1'];
				if(!empty($val['tag2'])) $res[$key]['tag'][] = $val['tag2'];
				if(!empty($val['tag3'])) $res[$key]['tag'][] = $val['tag3'];
				if(!empty($val['tag4'])) $res[$key]['tag'][] = $val['tag4'];
				unset($res[$key]['tag1']);
				unset($res[$key]['tag2']);
				unset($res[$key]['tag3']);
				unset($res[$key]['tag4']);
			}
		}
		return $res;
	}
	
	/* 
	 * 获取推荐店铺的 ID串
	 */
	function getRecommendStoreIds(){
		$re = $this->getRecommendStores();
		return $this->returnIDs($re);
	}

	/* 
	 * 获取人气店铺10条 
	 * 以 城市id 获取 星级前10 的人气店铺列表，
	 * 按星级排序（星级相同，则以距离顺序排序）
	 */
	function getPopularityStores(){
		$myLng    = I('post.myLng','','trim');
		$myLat    = I('post.myLat','','trim');
		$cityId   = I('post.cityId','','trim,intval');
		$pageSize = C('SHOP_POPULARITY_LIMIT');

		$map = array(
			'cityId'    => $cityId,
			'status'    => '1',//店铺状态：1开业；2休业；3审核
			'examine'   => '1',//审核通过
		);
		$map['star'] = array('gt','0');//评星必须大于0
		
		if( !empty($myLng) && !empty($myLat) ){
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.$myLng.','.$myLat.') as distance';
		}else{
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,0 as distance';
		}
		$order  = 'star desc,distance asc';
		
		
		
		$res    = $this->selData($map,$pageSize,$field,$order);

		if( !empty($res) ){
			foreach( $res as $key => $val ){
				if(!empty($val['tag1'])) $res[$key]['tag'][] = $val['tag1'];
				if(!empty($val['tag2'])) $res[$key]['tag'][] = $val['tag2'];
				if(!empty($val['tag3'])) $res[$key]['tag'][] = $val['tag3'];
				if(!empty($val['tag4'])) $res[$key]['tag'][] = $val['tag4'];
				unset($res[$key]['tag1']);
				unset($res[$key]['tag2']);
				unset($res[$key]['tag3']);
				unset($res[$key]['tag4']);
			}
		}
		return $res;
	}

	/*
	 * 获取普通店铺(附近店铺) 
	 * 以中心点经纬度获取附近的可见的店铺，
	 * 店铺状态为休业或停业整顿或删除的为用户不可见的；
	 * 与物理地址经纬度的距离大于50公里的数据为用户不可见的。
	 * 按店铺id倒序排序
	 * 可能有 人气店铺 的重复数据
	 */
	function getCommonStores(){
		$id       = I('post.id','','trim');
		$lng      = I('post.lng','','trim');
		$lat      = I('post.lat','','trim');
		$type     = I('post.type','','trim');
		$myLng    = I('post.myLng','','trim');
		$myLat    = I('post.myLat','','trim');
		$pageSize = I('post.pageSize','500','trim,intval');

		$map = array(
			'status'    => '1',//店铺状态：1开业；2休业；3审核
			'examine'   => '1',//审核通过
			'recommend' => '0'//非推荐店铺
		);

		if( !empty($myLng) && !empty($myLat) ){
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.$lng.','.$lat.') as distanceRange,distance(lng,lat,'.$myLng.','.$myLat.') as distance';
		}else{
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.$lng.','.$lat.') as distanceRange,0 as distance';
		}
		$map['having'] = ' distanceRange < '.C('SHOP_COMMON_RANGE');//大于50公里的数据不可见
			
		// $order = 'id desc';//按照id排序，就显示不了中心点的位置关系，故而舍弃
		$order = 'distanceRange asc';
		
		//type:1加载(id取最小)，0刷新(id取最大)
		if(empty($type)){
			return $this->selData($map,$pageSize,$field,$order);
		}else{//加载；前台传这个数据里面的最小id给后台、走加载逻辑
			//先看看数据库有没有（防止这段时间里面、这个id被关闭、删除、揭完了）
			$mapTmp['id'] = $id;
			$mapTmp['status'] = '1';
			$mapTmp['examine'] = '1';
			$mapTmp['recommend'] = '0';
			$reTmp = $this->selData($mapTmp,1,'id,lng,lat');
			if(empty($reTmp)){
				return -10;//无数据、非法传参
			}else{
				// $map['id'] = array('neq',$id);

				//以这个 id的距离 重新查询大于这个距离的20条数据
				if(empty($reTmp[0]['lng'])){
					$distance2 = 0;//计算距离
				}else{
					$distance2 = GetDistance($reTmp[0]['lng'],$reTmp[0]['lat'],$lng,$lat);//计算距离
				}
				$map['having'] = ' distanceRange >= '.$distance2.' and distanceRange <='.C('SHOP_COMMON_RANGE');//查询方式，以 距离 扩大 查询
				
				$arrRe = $this->selData($map,'',$field,$order);
				// echo $this->getLastSql();die;
				if(!empty($arrRe)){
					$keyOfId = 0;
					foreach($arrRe as $k => $v){
						if($v['id'] == $id){
							$keyOfId = $k;
							break;
						}
					}
					return array_slice($arrRe,$keyOfId+1,$pageSize);
				}else{
					return array();
				}
			}
		}
	}

	/* 
	 * 获取搜索店铺列表 
	 * 问题：因数据库星级都是0，故而必须传myLat、用“星级相同、按距离排序”实现翻页
	 */
	function getSearchStores(){
		$id       = I('post.id','','trim');
		$lng      = I('post.lng','','trim');//无用参数
		$lat      = I('post.lat','','trim');//无用参数
		$myLng    = I('post.myLng','','trim,float2int');
		$myLat    = I('post.myLat','','trim,float2int');
		$type     = I('post.type','','trim');
		$search   = I('post.search','','trim');
		$pageSize = I('post.pageSize','500','trim,intval');
		$cityId = I('post.cityId');
		
		$map = array(
			'status'    => '1',//店铺状态：1开业；2休业；3审核
			'examine'   => '1',//审核通过
		);

		$map['tag1|tag2|tag3|tag4'] = array('like','%'.$search.'%');
		$map['cityId'] = $cityId;
		
		if( !empty($myLng) && !empty($myLat) ){
			// $field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.$lng.','.$lat.') as distanceRange';
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.I('post.myLng').','.I('post.myLat').') as distance';
		}else{
			// $field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,distance(lng,lat,'.$lng.','.$lat.') as distanceRange';
			$field  = 'id,image,name,lng,lat,star,tag1,tag2,tag3,tag4,0 as distance';
		}
		
		
		// $map['having'] = ' distanceRange < '.C('SHOP_COMMON_RANGE');
		
		$order  = 'star desc,distance asc';//搜索附近的数据，无法按照店铺星级排序
		// $order  = 'distanceRange asc';
		// $res = $this->selData($map,$pageSize,$field,$order);
		// echo $this->getLastSql();die;
		
		//type:1加载(id取最小)，0刷新(id取最大)
		if(empty($type)){
			return $this->selData($map,$pageSize,$field,$order);
		}else{//加载；前台传这个数据里面的最小id给后台、走加载逻辑
			//先看看数据库有没有（防止这段时间里面、这个id被关闭、删除、揭完了）
			$mapTmp['id'] = $id;
			$mapTmp['status'] = '1';
			$mapTmp['examine'] = '1';
			$reTmp = $this->selData($mapTmp,1,'id,star,lng,lat');
			if(empty($reTmp)){
				return -10;//无数据、非法传参
			}else{
				$map['id'] = array('neq',$id);

				//以这个 id的 星级 重新查询小于这个星级的20条数据
				$map['star'] = array('ELT',$reTmp[0]['star']);
				
				if( !empty($myLng) && !empty($myLat) ){
					$map['having'] = 'distance > '.GetDistance($reTmp[0]['lng'],$reTmp[0]['lat'],I('post.myLng'),I('post.myLat'));
				}
				
				return $this->selData($map,$pageSize,$field,$order);
			}
		}
	}

	/**
     * @param  string $token 令牌token:令牌【必填项】
	 * @param  string $version:版本号(如“1.2”)
	 * @param  string $lng:经度【必填项】
	 * @param  string $lat：纬度【必填项】
	 * @param  string $myLng: 物理地址经度(我的位置)
	 * @param  string $myLat: 物理地址纬度(我的位置)
	 * @param  string $range:范围，比如“100”，服务器自动添加单位“米”【必填项】
	 * @param  string $type:类型：1加载，0刷新(默认值) 【必填项】
	 * @param  string $pageSize：每次取数据量，比如500条【必填项】
	 * @param  stringid: 店铺id（上一次返回最小店铺id）【必填项】
     * @return JSON 返回店铺列表的json字符串
     */
	public function getListData(){
		$return['success'] = true;
		//获取参数
		$maxid    = I('post.maxId','','trim');
		$minid    = I('post.minId','','trim');
		$lng      = I('post.lng','','trim');
		$lat      = I('post.lat','','trim');
		$type     = I('post.type','','trim');
		$range    = I('post.range','5000','trim');
		$token    = I('post.token','','trim');
		$userId   = I('post.version','1.2','trim');
		$limit = I('post.pageSize','500','trim');
		
		$map['status']  = '1';//店铺状态：1开业；2休业；3审核
		$map['examine'] = '1';//审核通过
		
		//计算经纬度范围
		$arrRange=GetRange($lng,$lat,$range);
		$map['lng'] = array('between',array($arrRange["minLng"],$arrRange["maxLng"]));
		$map['lat'] = array('between',array($arrRange["minLat"],$arrRange["maxLat"]));
		
		//走的是“type”的那3种逻辑
		$field  = 'id,image,name,lng,lat';
		$order = 'id desc';
		
		//type:1加载(id取最小)，0刷新(id取最大)
		if(empty($type)){//刷新
			if($maxid == '0'){//刷新
				return $this->selData($map,$limit,$field,$order);
			}else{
				$map['id'] = array('gt',$maxid);
				return $this->selData($map,$limit,$field,$order);
			}
		}else{//加载
			$map['id'] = array('gt',$maxid);
			$re = $this->selData($map,$limit,$field,$order);
			$otherLimit = $limit - count($re);
			
			if( $otherLimit > 0 ){//数量不够
				unset($map['id']);
				$map['id'] = array('lt',$minid);
				$re2 = $this->selData($map,$otherLimit,$field,$order);
				return array_merge($re,$re2);//最后的结果合计是 id倒序
			}
			
			return $re;
		}
	}

	/**
	 * 店铺存在性检测
	 * @param string $value
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function isExisted($value='') {
		$where = array(
			"id"	=> $value,
		);
		$id = $this->where($where)->field('id')->find();
		return is_null($id) ? true : false;
	}

	/**
	 * 密码校验
	 * @param string $value
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function passwordIsRight($value='', $storeId) {
		$result = $this->where(array('id'=>$storeId))->find();
		return ($result['password'] === $value) ? true : false ;
	}

	/**
	 * 手机号码检测
	 * @param string $value
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function isMobileNumber($value='') {
		return isPhoneNumber($value);
	}

	/**
	 * 手机号码uniqe检测
	 * @param string $value
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function isUniqueMobile($value='') {
		$storeId = I('post.storeId');
		$where = array(
			"id"	=> array('neq', $storeId),
			"phone"	=> $value,
		);
		$id = $this->where($where)->field('id')->find();
		return is_null($id) ? true : false;
	}

	/**
	 * 店铺登陆状态
	 * @param string $value
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function checkLogin($userId, $storeId, $password) {
		$where = array(
			'id'		=> $storeId,
			'userId'	=> $userId,
			'password'	=> $password,
		);
		return $status = $this->where($where)->find() ? true : false;
	}

}