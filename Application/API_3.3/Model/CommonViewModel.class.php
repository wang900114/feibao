<?php
use Think\Model\ViewModel;

class CommonViewModel extends ViewModel {
	
	/**
     * 查询操作
     * @param  array $map 查询条件
     * @param  string $limit 查询条数，默认是全部都查
     * @param  string $field 查询字段，默认是全部字段
     * @param  string $order 查询字段，默认是让数据库本身自适应
     * @param  string $join 联查
     * @param  string $group 分组
     * @return array
     */
	public function selData($map,$limit=0,$field='*',$order='',$join='',$group=''){
		if(!empty($map['having'])){//having的字符串条件
			$having = $map['having'];
			unset($map['having']);
		}
		
		// $isCache = $limit != 1 ? true : false;//是否加Redis缓存
		$isCache = false;//是否加Redis缓存
		
		return $this->field($field)->where($map)->having($having)->order($order)->join($join)->group($group)->limit($limit)->cache($isCache)->select();
	}
	
	/**
     * 更新操作
     * @param  array $map 查询条件
     * @param  array $data 数据
     * @return bool
     */
	public function upData($map,$data){
		return $this->where($map)->save($data);
	}
	
	/**
     * 插入操作
     * @param  string $data 数据
     * @return int
     */
	public function addData($data){
		return $this->data($data)->add();
	}
	
	/**
     * 更新 数量 操作
     * @param  array $map 查询条件
     * @param  string $colunm 字段名
     * @param  string $num 要修改的数量
     * @return bool
     */
	public function setColunm($map,$colunm,$num){
		return $this->where($map)->setInc($colunm,$num);
	}
	
	/**
     * 获取update表的 时间 操作
     * @return bool
     */
	public function getUpTableTime(){
		return $this->max('updateTime');
	}
	
	/**
     * 查询数量 操作
     * @param  array $map 查询条件
     * @return int
     */
	public function getNum($map){
		return $this->where($map)->count();
	}

	/**
	 * 查询是否收藏
	 * @access public
	 * @param mixed $where 查询条件
	 * @return boolean 1 收藏 0 没有收藏
	 */
	public function isCollect($where)
	{
		return $this->where($where)->where(array('status'=>'1'))->find() ? 1 : 0;
	}

	/**
	 * 获取配置
	 * @access public
	 * @param string $key
	 * @return string
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function getConfig($key) {
		$ret = M('Config')->where( array('key'=>$key) )->find();
		return $ret['value'];
	}
}
?>