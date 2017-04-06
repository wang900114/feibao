<?php
use Think\Controller;
/**
  * 本地新闻接口
  */
class FoundNewsController extends BaseController {
	/**
	 * 本地新闻列表
	 * @access public
	 * @param string $cityId 城市ID
	 * @param string $type 1加载(翻页)；0刷新（默认值）
	 * @param string $id
	 * @param string $pageSize
	 * @param string $lastUpdateTime
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function localNewsList() {

		// 获取参数
		$cityId = I('post.cityId');
		$type = I('post.type', 0);
		$id = I('post.id', 0);
		$pageSize = I('post.pageSize', 5);
		$lastUpdateTime = I('post.lastUpdateTime', 0);

		// 参数检测
		if (is_empty($cityId) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->ret['info'] = (object)array();
			$this->apiCallback($this->ret);
		}

		// 查询服务器数据更新时间
		$serverUpdateTime = D('Common')->getConfig('localnews_update_time');

		// 获取新闻列表
		// 查询条件
		$where = array( 'cityId'=>$cityId, 'examine'=>'1');
		if ($type) {
			$where['id'] = array('lt', $id);
			// $where['updateTime'] = array('between',array(strtotime(date("Y-m-d",strtotime('-30 day'))),strtotime(date("Y-m-d",strtotime('+1 day')))-1));//翻页、只显示30天内的
			$where['time'] = array('between',array(strtotime(date("Y-m-d",strtotime('-30 day'))),strtotime(date("Y-m-d",strtotime('+1 day')))-1));//翻页、只显示30天内的
		}else{
			$where['id'] = array('egt', $id);
		}
		$field = 'id,image,title,summary,htmlPath,sharePath,time'; // 查询字段
		$order['id'] = 'desc';

		$newsModel = D('LocalNews');
		$total = $newsModel->where($where)->count();
		$list = $newsModel->selData($where, $pageSize, $field, $order);

		// 尾页判断
		$isLastPage = ($total-$pageSize)>0 ? '0' : '1';

		// 构建数据
		if ($total==0) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '没有数据了';
			$this->ret['info'] = (object)array();
		} elseif (empty($list)) {
			$this->ret['status'] = -1;
			$this->ret['message'] = '查询失败';
			$this->ret['info']['isLastPage'] = 1;
			$this->ret['info']['list'] = (object)array();
		} else {
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info']['isLastPage'] = $isLastPage;
			$this->ret['info']['nowUpdateTime'] = time();
			$this->ret['info']['list'] = $list;
		}

		$this->apiCallback($this->ret);

	}

	/**
	 * 本地新闻详情
	 * @access public
	 * @param string $id
	 * @param string $userId
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function localNewsDetail() {
		// 获取参数
		$id = I('post.id');
		$userId = I('post.userId');

		// 参数检测
		if (is_empty($id) || is_empty($userId) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->ret['info'] = (object)array();
			$this->apiCallback($this->ret);
		}

		// 新闻内容
		$newsModel = D('LocalNews');
		$where = array('id'=>$id, 'examine'=>'1' );
		$field = 'id,title,image,source,time,htmlPath as content,summary';
		$news = $newsModel->where($where)->field($field)->find();


		if (empty($news)) {
			$this->ret['status'] = 0;
			$this->ret['message'] = '数据不存在';
			$this->ret['info'] = (object)array();
		}else{
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			$this->ret['info'] = $news;

			// 评论数
			$this->ret['info']['commentNum'] = M('CommentsLocalnews')->where(array('dataId'=>$id) )->count();
			//赞数量
			$this->ret['info']['praiseNum'] = M('PraiseLocalnewsLog')->where(array('dataId'=>$id) )->count();
			//赞
			$this->ret['info']['isPraise'] = M('PraiseLocalnewsLog')->where(array('dataId'=>$id), array('userId'=>$userId) )->find() ? 1 : 0;
			// 收藏
			$this->ret['info']['isCollect'] = M('CollectLocalnewsLog')->where(array('dataId'=>$id), array('userId'=>$userId) )->find() ? 1 : 0;
		}
		
		// 反馈
		$this->apiCallback($this->ret);

	}
	

	
	

	/**
	 * 本地新闻搜索
	 * @access public
	 * @param string $cityId:城市id
	 * @param string $type 1加载；0刷新（默认值）
	 * @param string $id
	 * @param string $pageSize
	 * @param string $search 关键字
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function searchNews() {
		// 获取参数
		$cityId = I('post.cityId');
		$type = I('post.type', 0);
		$id = I('post.id', 0);
		$pageSize = I('post.pageSize', 5);
		$search = I('post.search', '', 'trim');


		// 参数检测
		if (is_empty($cityId) || is_empty( $search) ) {
			$this->ret['status'] = -888;
			$this->ret['message'] = '参数不完整';
			$this->ret['info'] = (object)array();
			$this->apiCallback($this->ret);
		}

		// 查询条件
		C('DB_LIKE_FIELDS','title'); // 设置模糊查询字段
		$where['title']  = $search;
		$where['cityId'] = $cityId;
		$where['_logic'] = 'and';
		if ($type) {
			$where['id'] = array('lt', $id);
		}else{
			$where['id'] = array('egt', $id);
		}
		$field = 'id,image,title,summary,htmlPath,sharePath,time'; // 查询字段
		$field .= ',if(isnull(b.num),0,b.num) as n';
		// $order['id'] = 'desc';
		$order = 'b.num desc,time desc';

		$newsModel = D('LocalNews');
		// $total = $newsModel->where($where)->count();
		$join = 'LEFT JOIN (select dataId,count(id) as num from '.C('DB_PREFIX').'comments_localnews group by dataId) as b on '.C('DB_PREFIX').'local_news.id = b.dataId';
		$list = $newsModel->selData($where, $pageSize, $field, $order, $join);
		// echo $newsModel->getLastSql();die;

		// 尾页判断
		// $isLastPage = ($total-$pageSize)>0 ? '0' : '1';

		// 构建数据
		if( (is_array($list) || is_null($list)) && empty($list) ){
			$this->ret['status'] = 0;
			$this->ret['message'] = '没有数据了';
			$this->ret['info'] = array();
		}else if(is_bool($list) && empty($list)){
			$this->ret['status'] = -1;
			$this->ret['message'] = '查询失败';
			$this->ret['info'] = array();
		} else {
			$this->ret['status'] = 1;
			$this->ret['message'] = '查询成功';
			// $this->ret['info']['isLastPage'] = $isLastPage;
			// $this->ret['info']['nowUpdateTime'] = time();
			// $this->ret['info']['list'] = $list;
			
			foreach($list as $k=>$v){
				//$list[$k]['distance'] = '123';//距离
				$list[$k]['praiseNum'] = M('PraiseLocalnewsLog')->where(array('status'=>'1', 'dataId'=>$v['id']))->count('id');//赞的数量
				//$list[$k]['publisher'] = '北方的狼';//本地新闻的发布人
				//$list[$k]['publisherId'] = '1';//本地新闻的发布人I的
			}

			$this->ret['info'] = $list;
		}

		$this->apiCallback($this->ret);

	}

}