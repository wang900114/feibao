<?php
use Think\Controller;

class ToplineController extends BaseController {
	public function _initialize() {
		parent::_initialize();
		A('API_3.2/Public')->testPublicToken();//验证 公共 token
	}
		
	/**
	 * 返回新闻列表数据
	 * @access public
	 * @param string $type 1加载(翻页)；0刷新（默认值）
	 * @param string $id
	 * @param string $pageSize
	 * @param string $lastUpdateTime
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function newsList() {

		// 获取参数
		$type = I('post.type');
		$id = I('post.id');
		$pageSize = I('post.pageSize');
		$lastUpdateTime = I('post.lastUpdateTime');

		// 参数检测
		/* 上线时检测
		if (is_empty($type) || is_empty($id) || is_empty( $pageSize) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->apiCallback($this->ret);
		}
		*/
		// 设置模型
		$newsModel = D('News');

		if (empty($type)) {
			// 获取轮播图
			$carousel = array();
			$limit = '5';
			$fieldCarousel = 'id,image,title,source,time';
			$whereCarousel = array( 'cid'=>'5', 'examine'=>'1');
			$orderCarousel['id'] = 'desc' ;
			$carousel = $newsModel->selData($whereCarousel, $limit, $fieldCarousel, $orderCarousel);
			if(empty($carousel)) $carousel = array();
		}else{
			$carousel = array();
		}
		
		// 获取新闻列表
		// 查询条件
		$where = array( 'cid'=>'6', 'examine'=>'1', 'top'=>'0');
		if ($type) {
			$where['id'] = array('lt', $id);
			// $where['updateTime'] = array('between',array(strtotime(date("Y-m-d",strtotime('-1 week'))),strtotime(date("Y-m-d",strtotime('+1 day')))-1));//翻页、只显示7天内的
			$where['time'] = array('between',array(strtotime(date("Y-m-d",strtotime('-1 week'))),strtotime(date("Y-m-d",strtotime('+1 day')))-1));//翻页、只显示7天内的
		}else{
			$where['id'] = array('egt', $id);
		}
		$field = 'id,image,title,summary,time,type,htmlPath,sharePath,source'; // 查询字段
		$order['id'] = 'desc';

		if (empty($type)) {
			// 查询新闻置顶
			$topNews = array();
			$topNewsLimit = C('top_news_limit'); // 数量
			$topNewsWhere = array( 'cid'=>'6', 'examine'=>'1', 'top'=>'1');
			$topNews = $newsModel->selData($topNewsWhere, $topNewsLimit, $field, $order);
			if(empty($topNews)) $topNews = array();
		}else{
			$topNews = array();
		}

		// 普通新闻
		$list = array();
		$total = $newsModel->getNum($where);
		$list = $newsModel->selData($where, $pageSize, $field, $order);

		// 尾页判断
		$isLastPage = ($total-$pageSize)>0 ? '0' : '1';

		// 构建数据
		if ($total==0) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '没有数据了';
			$this->ret['info']['nowUpdateTime'] = time();
			$this->ret['info']['carousel'] = array();
			$this->ret['info']['top'] = array();
			$this->ret['info']['list'] = array();
		} elseif (empty($list)) {
			$this->ret['status'] = -1;
			$this->ret['message'] = '查询失败';
		} else {
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info']['isLastPage'] = $isLastPage;
			$this->ret['info']['nowUpdateTime'] = time();
			$this->ret['info']['carousel'] = $carousel;
			$this->ret['info']['top'] = $topNews;
			// $this->ret['info']['list']['isIncrement'] = '1';
			$this->ret['info']['list'] = $list;
		}

		$this->apiCallback($this->ret);

	}

	/**
	 * 新闻详情数据
	 * @access public
	 * @param string $id
	 * @param string $userId
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function detail() {
		
		// 获取参数
		$id = I('post.id');
		$userId = I('post.userId');

		// 参数检测
		/* 上线时检测
		if (is_empty($id) || is_empty($userId) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->apiCallback($this->ret);
		}
		*/

		// 新闻内容
		$newsModel = D('NewsView');
		$where = array('id'=>$id, 'cid'=>'6','examine'=>'1' );
		$res = $newsModel ->where($where)->find();

		if (empty($res)) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '没有数据了';
			$this->ret['info'] = (object)array();
		}else{
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info'] = $res;

			// 收藏
			$this->ret['info']['isCollect'] = D('CollectNewsLog')->where(array('dataId'=>$id,'userId'=>$userId))->find() ? 1 : 0;
			// 赞
			$this->ret['info']['isPraise'] = D('PraiseNewsNormalLog')->where(array('dataId'=>$id,'userId'=>$userId))->find() ? 1 : 0;
			// 赞的数量
			$this->ret['info']['praise'] = D('PraiseNewsNormalLog')->getNum(array('dataId'=>$id));
			// 评论数
			$this->ret['info']['comments'] = D('CommentsNewsNormal')->getNum(array('dataId'=>$id));
		}

		// 反馈
		$this->apiCallback($this->ret);
	}

	/**
	 * 新闻搜索
	 * @access public
	 * @param string $type 1加载；0刷新（默认值）
	 * @param string $id
	 * @param string $pageSize
	 * @param string $search 关键字
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function search() {

		// 获取参数
		$type = I('post.type');
		$id = I('post.id');
		$pageSize = I('post.pageSize');
		$search = I('post.search', '', 'trim');

		// 参数检测
		/* 上线时检测
		if (is_empty($type) || is_empty($id) || is_empty( $pageSize)  || is_empty( $search) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->apiCallback($this->ret);
		}
		*/

		// 查询条件
		// C('DB_LIKE_FIELDS','title|content'); // 设置模糊查询字段
		C('DB_LIKE_FIELDS','title'); // 设置模糊查询字段
		// $where['title|content'] = $search;
		$where['title'] = $search;
		// $where['cid'] = 6;
		// $where['_logic'] = 'and';
		$where['examine'] = '1';
		// if ($type) {
			// $where['id'] = array('lt', $id);
		// }else{
			// $where['id'] = array('egt', $id);
		// }
		$field = 'id,cid,image,title,summary,IF (cid=5, 2, type) AS type,htmlPath,sharePath,source,time';//轮播图 type 写死为2
		$field .= ',if(isnull(b.num),0,b.num) as n';
		// $order['id'] = 'desc';
		$order = 'b.num desc,time desc';

		// 查询
		$newsModel = D('News');
		// $total = $newsModel->where($where)->count();
		$join = 'LEFT JOIN (select dataId,count(id) as num from '.C('DB_PREFIX').'comments_news_normal group by dataId) as b on '.C('DB_PREFIX').'news.id = b.dataId';
		// $res = $newsModel->selData($where, $pageSize, $field, $order, $join);
		$res = $newsModel->selData($where, '', $field, $order, $join);
		// echo $newsModel->getLastSql();die;
		
		$re = array();

		if(empty($type)){//刷新
			if(!empty($res)){
				$keyOfId = '';
				foreach($res as $k => $v){
					if($v['id'] == $id){
						$keyOfId = $k;
						break;
					}
				}
				// if(is_empty($keyOfId)) $keyOfId = count($res);//找不到id的序号，默认就取全部
				// var_dump($keyOfId);
				//刷新时，是从0开始向后截取
				$keyOfId = is_empty($keyOfId) ? $pageSize : $keyOfId;
				$re = array_slice($res,0,$keyOfId);
			}
		}else{//翻页
			if(!empty($res)){
				$keyOfId = 0;
				foreach($res as $k => $v){
					if($v['id'] == $id){
						$keyOfId = $k;
						break;
					}
				}
				// var_dump($keyOfId);
				//翻页时，是从 id 开始向后截取
				$re = array_slice($res,$keyOfId+1,$pageSize);
			}
		}
		
		
		// 尾页判断
		// $isLastPage = ($total-$pageSize) > 0 ? '0' : '1';

		if (empty($re)) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '没有数据了';
			$this->ret['info']['list'] = array();
			$this->ret['info']['isLastPage'] = 1;
		}
		// elseif (empty($res)) {
			// $this->ret['status'] = -1;
			// $this->ret['info']['list'] = array();
			// $this->ret['message'] = '查询失败';
		// } 
		else {
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info']['isLastPage'] = $isLastPage;
			$this->ret['info']['list'] = $re;
		}

		// 反馈
		$this->apiCallback($this->ret);
	}

	/**
	 * 图集详情（轮播）
	 * @access public
	 * @param string $id
	 * @param string $userId
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function carouselDetail() {
		
		// 获取参数
		$id = I('post.id');
		$userId = I('post.userId');

		// 参数检测
		/* 上线时检测
		if (is_empty($id) || is_empty($userId) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->apiCallback($this->ret);
		}
		*/

		// 图集内容
		$newsModel = D('NewsView');
		$map = array('id'=>$id, 'examine'=>'1' );
		$res = $newsModel ->where($map)->find();
		
		if( !empty($res) ){//轮播图审核通过，才能继续查询
			$carouselModel = D('PictureTopCarousel');
			$where = array('dataId'=> $id);
			$field = 'id,name,image,summary';
			$res = $carouselModel->selData($where, null, $field);
		}

		if (empty($res)) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '数据不存在';
			$this->ret['info'] = (object)array();
		}else{
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info']['data'] = $res;

			// 收藏
			$this->ret['info']['isCollect'] = D('CollectNewsLog')->where(array('dataId'=>$id,'userId'=>$userId))->find() ? 1 : 0;
			// 赞
			$this->ret['info']['isPraise'] = D('PraiseNewsCarouselLog')->where(array('dataId'=>$id,'userId'=>$userId))->find() ? 1 : 0;
			// 赞的数量
			$this->ret['info']['praise'] = D('PraiseNewsCarouselLog')->getNum(array('dataId'=>$id));
			// 评论数
			$this->ret['info']['total'] = D('CommentsNewsCarousel')->getNum(array('dataId'=>$id));
			
			$newsWhere = array('id'=>$id, );
			$newsField = 'source,sharePath,time,summary';
			$newsInfo = D('News')->selData($newsWhere, 1, $newsField);
			
			// 来源
			$this->ret['info']['source'] = $newsInfo[0]['source'];
			
			// 简介
			$this->ret['info']['summary'] = $newsInfo[0]['summary'];
			
			// 分享页面的连接
			$this->ret['info']['sharePath'] = $newsInfo[0]['share'];
			$this->ret['info']['time'] = $newsInfo[0]['time'];
		}
		
		// 反馈
		$this->apiCallback($this->ret);
	}

}