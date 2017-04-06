<?php

use Think\Model;

class CommonModel extends Model {

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
    public function selData($map, $limit = 0, $field = '*', $order = '', $join = '', $group = '') {
        if (!empty($map['having'])) {//having的字符串条件
            $having = $map['having'];
            unset($map['having']);
        }

        // $isCache = $limit != 1 ? true : false;//是否加Redis缓存
        $isCache = false; //是否加Redis缓存

        return $this->field($field)->where($map)->having($having)->order($order)->join($join)->group($group)->limit($limit)->cache($isCache)->select();
    }

    public function selDatas($map, $having, $limit = 0, $field = '*', $order = '', $join = '', $group = '') {
        //if (!empty($map['having'])) {//having的字符串条件
        //$having = $map['having'];
        //unset($map['having']);
        //}
        // $isCache = $limit != 1 ? true : false;//是否加Redis缓存
        $isCache = false; //是否加Redis缓存

        return $this->field($field)->where($map)->having($having)->order($order)->join($join)->group($group)->limit($limit)->cache($isCache)->select();
    }

    /**
     * 更新操作
     * @param  array $map 查询条件
     * @param  array $data 数据
     * @return bool
     */
    public function upData($map, $data) {
        return $this->where($map)->save($data);
    }

    /**
     * 插入操作
     * @param  string $data 数据
     * @return int
     */
    public function addData($data) {
        return $this->data($data)->add();
    }

    /**
     * 删除 操作
     * @param  string $map 条件
     * @return int
     */
    public function delData($map) {
        return $this->where($map)->delete();
    }

    /**
     * 返回ID串
     * @param  array 查询的结果集
     * @return string ID串,如"1,2,5"
     */
    public function returnIDs($array) {
        $ids = '';
        if (!empty($array)) {
            foreach ($array as $v) {
                $ids .= $v['id'] . ',';
            }
        }

        return trim($ids, ',');
    }

    /**
     * 更新 数量 操作
     * @param  array $map 查询条件
     * @param  string $colunm 字段名
     * @param  string $num 要修改的数量
     * @return bool
     */
    public function setColunm($map, $colunm, $num) {
        return $this->where($map)->setInc($colunm, $num);
    }

    /**
     * 获取update表的 时间 操作
     * @return bool
     */
    public function getUpTableTime() {
        return $this->max('updateTime');
    }

    /**
     * 查询数量 操作
     * @param  array $map 查询条件
     * @return int
     */
    public function getNum($map) {
        return $this->where($map)->count();
    }

    /**
     * 查询是否评星
     * @access public
     * @param mixed $where 查询条件
     * @return boolean 1 收藏 0 没有收藏
     */
    public function hasStar($where) {
        return $this->where($where)->find() ? 1 : 0;
    }

    /**
     * 查询是否收藏
     * @access public
     * @param mixed $where 查询条件
     * @return boolean 1 收藏 0 没有收藏
     */
    public function isCollect($where) {
        return $this->where($where)->where(array('status' => '1'))->find() ? 1 : 0;
    }

    /**
     * 获取配置
     * @access public
     * @param string $key
     * @return string
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function getConfig($key) {
        return D('System')->readConfig($key);
    }

    /*
     * 根据ID分页翻页(性能太差，舍弃)
     * @param  array $map 查询条件（含having条件）
     * @param  string $limit 查询条数，默认是全部都查
     * @param  string $field 查询字段，默认是全部字段
     * @param  string $order 查询字段，默认是让数据库本身自适应
     * @return array
     */

    public function GetDataAfterID($map, $limit = 0, $field = '*', $order = '', $join = '', $id = '') {
        if (!empty($map['having'])) {//having的字符串条件
            $having = $map['having'];
            unset($map['having']);
        }
        if (is_array($order)) {
            $order = array_merge(array('i' => 'asc'), $order);
        } else {
            $order = trim('i asc,' . $order, ',');
        }
        $field = trim($field . ',(@i :=@i + 1) AS i', ',');

        if (empty($id)) {
            $re = $this
                    ->field($field)
                    ->where($map)
                    ->having($having)
                    ->order($order)
                    ->join($join)
                    ->join(' inner join (SELECT @i := 0) AS it ')
                    ->limit($limit)
                    ->select();
            return $re;
        } else {
            $sql = $this
                    ->field($field)
                    ->where($map)
                    ->having($having)
                    ->order($order)
                    ->join($join)
                    ->join(' inner join (SELECT @i := 0) AS it ')
                    ->buildSql();
            $re2 = $this
                    ->table('( ' . $sql . ' ) as tmpTable')
                    ->field('id,i')
                    ->where(array('id' => $id))
                    ->limit(1)
                    ->select();

            if (empty($re2)) {
                return false;
            } else {
                $start = strpos($limit, ',');
                $start = empty($start) ? 0 : $start + 1;
                $limit3 = substr($limit, $start);

                $re3 = $this
                        ->field($field)
                        ->where($map)
                        ->having($having)
                        ->order($order)
                        ->join($join)
                        ->join(' inner join (SELECT @i := 0) AS it ')
                        ->limit($re2[0]['i'] . ',' . $limit3)
                        ->select();
                return $re3;
            }
        }
    }

    /*
     * 地图列表（海报、发现） 的专用 方法 ，不可随意调用
     * @param  array $map 查询条件
     * @param  int $minid 最小id
     * @param  int $maxid 最大id
     * @param  string $type 刷新/加载
     * @param  string $pageSize 分页
     * 走的是“type”的那3种逻辑
      type=0;maxid=0   ，就是刚进来的，没值，要刷新的
      maxid》0，就查 id》 maxid 的size条，  id正序
      type=1;   maxid =5,就查 5~10 的即可
      如果上面的结果不够、差8条，就查	id《 minid 的倒序的 8条，补充进去就行
     */
    /* 	public function baseOfGetMapData($map,$minid,$maxid,$type,$limit){
      $field = 'id,image,title,lng,lat';
      $order = 'id desc';

      //type:1加载(id取最小)，0刷新(id取最大)
      if(empty($type)){//刷新
      if($maxid == '0'){//刷新
      return $this->selData($map,$limit,$field,$order);
      }else{
      $map['id'] = array('gt',$maxid);
      // $order = 'id asc';//改成 正序
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
      } */


    /*
     * 普通列表（海报、发现、店铺、所有店、搜索） 的专用 方法 ，不可随意调用
     * @param  array $map 查询条件
      $map['selectWay'],用来生成having条件
      $map['selectWay']['distance'] 发现、按照 距离 扩大范围
      $map['selectWay']['praise'] 发现、按照 赞 缩小范围
      $map['selectWay']['poster'] 海报、按照 投放范围和距离 扩大范围
     * @param  string $order 排序
     * @param  string $field 字段
     * @param  string $limit 分页
     * @param  string $join 联查
     * 走的是“如果”的那一串逻辑
      distance 舍弃了
      只剩下 Poster 还在用
     */

    public function baseOfGetListData($map, $limit, $field = '', $order = '', $join = '') {
        $reArr = array();
        $i = 0;

        //判断 扩大范围 查询 的条件
        if (!empty($map['selectWay'])) {
            if (!empty($map['selectWay']['distance'])) {
                //以 距离 扩大查询
                $way = 'distance';
                $distance = $map['selectWay']['distance'];
            } else if (!empty($map['selectWay']['praise'])) {
                //以 赞数 扩大查询
                $way = 'praise';
                $praise = $map['selectWay']['praise'];
            } else if (!empty($map['selectWay']['poster'])) {
                //海报，以 投放范围 扩大查询
                $way = 'poster';
                $poster = $map['selectWay']['poster'];
            } else {
                return array();
            }
            unset($map['selectWay']);
        } else {
            return array(); //没有范围依据，终止查询
        }

        while (count($reArr) < $limit) {

            //循环中的 局部 范围上限，
            if ($way == 'distance')
                $maxTmp = (CalculationRange($distance / 1000)) * 1000 + $i * 1000; //如10*1000,20*1000,30*1000,40*1000,50*1000
            if ($way == 'praise')
                $maxTmp = C('FOUND_LIST_PRAISE_BY_SELECT') + $praise * $i;
            if ($way == 'poster')
                $maxTmp = C('POSTERS_LIST_DISTANCE_BY_SELECT') * $i;

            //配置的 总体的 范围上限
            if ($way == 'distance')
                $configRange = C('FOUND_LIST_DISTANCE'); //如 50000 米
            if ($way == 'praise')
                $configRange = C('FOUND_LIST_PRAISE'); //如 500
            if ($way == 'poster')
                $configRange = C('POSTERS_LIST_DISTANCE'); //如 50000 米

            if ($maxTmp <= $configRange) {
                //having条件
                if ($way == 'distance')
                    $map2 = ' ( distance > ' . $distance . ' and distance < (' . $maxTmp . ') ) '; //以 10*1000M 为范围进行环行距离延伸查询
                if ($way == 'praise')
                    $map2 = ' ( praiseNum <= (' . $praise . ') ) ';
                if ($way == 'poster')
                    $map2 = $poster;

                $map['having'] = $map2;

                $re2 = $this->selData($map, $limit, $field, $order, $join);
                // echo $this->getLastSql();die;
                // $re2 = $this->selData($map,$limit,$field,$order,$join);
                if (empty($re2)) {//当前范围 无数据
                    $i++;
                    continue;
                } else {
                    $reArr = array_merge($reArr, $re2);
                    if (count($reArr) >= $limit) {
                        $reArr = array_slice($reArr, 0, $limit);
                        break;
                    } else {
                        $i++;
                        continue;
                    }
                }
            } else {//超出范围，跳出
                break;
            }
        }
        return $reArr;
    }

    /*
     * 普通列表（海报） 的专用 方法 ，不可随意调用
     * @param  array $map 查询条件
      $map['selectWay'],用来生成having条件
      $map['selectWay']['distance'] 发现、按照 距离 扩大范围
      $map['selectWay']['praise'] 发现、按照 赞 缩小范围
      $map['selectWay']['poster'] 海报、按照 投放范围和距离 扩大范围
     * @param  string $order 排序
     * @param  string $field 字段
     * @param  string $limit 分页
     * @param  string $join 联查
     * 走的是“如果”的那一串逻辑
     */
    /* public function PosterBaseOfGetListData($map,$limit,$field='',$order='',$join=''){
      $reArr = array();
      $i = 0;

      //判断 扩大范围 查询 的条件
      if(!empty($map['selectWay'])){
      if(!empty($map['selectWay']['distance'])){
      //以 距离 扩大查询
      $way = 'distance';
      $distance = $map['selectWay']['distance'];
      }else if(!empty($map['selectWay']['praise'])){
      //以 赞数 扩大查询
      $way = 'praise';
      $praise = $map['selectWay']['praise'];
      }else if(!empty($map['selectWay']['poster'])){
      //海报，以 投放范围 扩大查询
      $way = 'poster';
      $poster = $map['selectWay']['poster'];
      }else{
      return array();
      }
      unset($map['selectWay']);
      }else{
      return array();//没有范围依据，终止查询
      }

      while( count($reArr) < $limit ){

      //循环中的 局部 范围上限，
      if($way == 'distance') $maxTmp = (CalculationRange($distance/1000))*1000 + $i*1000;//如10*1000,20*1000,30*1000,40*1000,50*1000
      if($way == 'praise') $maxTmp = C('FOUND_LIST_PRAISE_BY_SELECT')+$praise*$i;
      if($way == 'poster') $maxTmp = C('POSTERS_LIST_DISTANCE_BY_SELECT')*$i;

      //配置的 总体的 范围上限
      if($way == 'distance') $configRange = C('FOUND_LIST_DISTANCE');//如 50000 米
      if($way == 'praise') $configRange = C('FOUND_LIST_PRAISE');//如 500
      if($way == 'poster') $configRange = C('POSTERS_LIST_DISTANCE');//如 50000 米

      if( $maxTmp <= $configRange ){
      //having条件
      if($way == 'distance') $map2 = ' ( distance > '.$distance.' and distance < ('.$maxTmp.') ) ';//以 10*1000M 为范围进行环行距离延伸查询
      if($way == 'praise') $map2 = ' ( praiseNum <= ('.$praise.') ) ';
      if($way == 'poster') $map2 = $poster;

      $map['having'] = $map2;

      $re2 = $this->selData($map,$limit,$field,$order,$join);
      // echo $this->getLastSql();die;
      // $re2 = $this->selData($map,$limit,$field,$order,$join);
      if(empty($re2)){//当前范围 无数据
      $i++;
      continue;
      }else{
      $reArr = array_merge($reArr,$re2);
      if(count($reArr) >= $limit){
      $reArr = array_slice($reArr, 0, $limit);
      break;
      }else{
      $i++;
      continue;
      }
      }
      }else{//超出范围，跳出
      break;
      }
      }
      return $reArr;
      } */

    /**
     * 验证充值限制
     * 
     * @param int $uid
     * @param int $money
     * @return boolean true-可充值，false-不可充值
     */
    public function ck_reserve($uid, $money = 1) {

        //验证总额
        $total = D('PosterBillLog')->getTotalMoneyToday();
        //获得每天充值总金额
        $reserve_pd = $this->getConfig("reserve_pd");
        if ($total + $money >= $reserve_pd) {
            //return array('status' => 2, 'msg' => '总额超限');
            return array('status' => 2, 'msg' => '今天话费已领完，请明天再试！');
        }


        //$reserve = C('RESERVE');

        $count = D('PosterBillLog')->getCountToday($money);
        //验证每种充值数量
        if ($count + 1 > $this->getConfig("reserve_" . $money)) {
            //return array('status' => 3, 'msg' => '当前充值金额种类数量超限');
            return array('status' => 3, 'msg' => '当前面值已领完，请尝试其它面值！');
        }

        //验证个人今日充值额度
        $integral_c = $this->getConfig("integral_pu_pd");
        $integral_u = D('PosterBillLog')->getUserTotalIntegralToday($uid);

        //限制每人每天兑换飞币一次
        if ($integral_u) {
            //return array('status' => 6, 'msg' => '每天只可兑换一次');
            return array('status' => 6, 'msg' => '亲，每天最多兑换一次哦！');
        }
        //echo D('PosterBillLog')->getLastSql();die;
         //$percent = C('PERCENT');
        //2015-01-16 xiaofeng 修改 从数据库中读取信息
        $percent = percent();
        if (($integral_u + $percent[$money]) > $integral_c) {
            $start_time = D('PosterBillLog')->getTodayStart();

            $redis = new \Org\Util\Redis();
            $overflow_time = $redis->get("integral_overflow_{$uid}") && $overflow_time >= $start_time - 86400 && (D("Members")->setUserWrongful($uid) && D("Invite")->setAssociateUserStatus($uid)) || $redis->set("integral_overflow_{$uid}", time(), 86400 * 7);

            //return array('status' => 4, 'msg' => '个人额度超限');
            return array('status' => 4, 'msg' => '亲，积分兑换超限！');
        }

        //充值时间限制
        $start_time = D('PosterBillLog')->getTodayStart();
        //获取每天开始的时间
        $start_hour = (int) $this->getConfig("start_hour");
        //获取每天结束的时间
        $end_hour = (int) $this->getConfig("end_hour");
        if (time() < ($start_time + $start_hour * 3600) || time() > ($start_time + $end_hour * 3600)) {
            //return array('status' => 5, 'msg' => '当前时间不可充值');
            return array('status' => 5, 'msg' => '亲，当前时间不可充值！');
        }

        return array('status' => 1, 'msg' => '成功');
    }
 /**
     * 读取需要数据和同步数据链路控制
     * 读取需要的数据,如果未发现则同步数据并读取
     * 
     * @param array $where 需要的数据的读取条件
     * @return array 返回需要的数据
     */
    public function readDataAndSynchronousDataLinkControl($where) {
        $config_member = D('config_member');
        $config = D("config");
        //从缓存读取需要的数据

        $data = $config_member->where($where)->find();
        //var_dump($data);die;
        //如果未取到数据,则更新相关数据到缓存表并读取相关数据
        if (!$data) {
            //删除缓存表所有数据
            $config_member->where("1")->delete();
            $all_data = $config->select();
            foreach ($all_data as $k => $v) {
                $config_member->add($v);
            }
            //读取需要的数据
            $data = $config_member->where($where)->find();
        }
        return $data;
    }

    /**
     * 读取指定的配置
     * 
     * @param type $key 需要读取的字段
     * @return type 返回需要读取的字段的值
     */
    public function readConfig($key) {
        $where['key'] = $key;
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        return $result['value'];
    }
}
