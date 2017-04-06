<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdvertisingController
 *
 * @author wangwei
 */
import('\Org\Util\Redis');
class AdvertisingController extends CommonController {
    //put your code here
    
    /**
     * 初始化
     */
    public function _initialize() {
        parent::_initialize();
        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;
        if ($phone && $userId) {//判断参数是否为空
            $model = D("Members");
            if($phone=='12345678900'){
                $res = $model->getUserDataByPhone($phone , 'id,freeze');
            }else{
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
            }
            //$res = $model->checkUserId($phone, $userId, 'id,freeze');
            
            if (empty($res['id'])) {// 验证唯一码是否正确
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';
                //$return['info'] = array();
                echo jsonStr($return);
                exit(0);
            }else{
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    //$return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                }else{
//                    if($res['id']==44427){
//                        $return['status'] = 32;
//                        $return['message'] = '请到个人中心登录';
//                        //$return['info'] = array();
//                        echo jsonStr($return);
//                        exit(0);
//                    }
                    $this->userId = $res['id'];
                }
            }
        } else {
            $return['message'] = '操作失败';
            $return['status'] = 10;
            //$return['info'] = array();
            echo jsonStr($return);
            exit(0);
        }
    }
    
            
        /**
     * 返回最新广告数据【每次10条】
     * 
     *  friendId：广告所属id【选填项】
        type：【默认 1可选】
        【1： 最新广告】
        【2： 精选广告】
        【3： 附近广告】
        【4： 精选页面轮播区】
        【5： 官方策划】
        【6： 精选页面官方策划】
        myLng：物理地址经度(即手机GPS定位的“我的位置”)【附近必传】
                myLat: 物理地址纬度(即手机GPS定位的“我的位置”)【附近必传】
        page:第几页【默认 0】
        Search:【搜索关键词】【可选，搜索页面用】
     * 
     */
        public function lists()
    {
        $userId = $this->userId;
        $friendIds = I('post.friendId', 0);
        
        $type = I('post.type', 1);
        $lastAddTime = I('post.lastAddTime', 0);
//        $page = I('post.page', 0);
        $search = I('post.search', '');
        $cateId = I('post.cateId', '');
        $tagsId = I('post.tagsId', '');
        $myLng = I('post.myLng', '');
        $myLat = I('post.myLat', '');
        $isMe = I('post.isMe', 2);
        $field = ' scheme_time, scheme_url, banner_url, handpick_time,a.id, a.user_id userId, b.name as nickname, a.image, a.title, a.interest_count interestTotal, a.add_time addTime, a.image, b.image userImage, a.lng, a.lat ';
        
        $where = " where 1 = 1 ";
        if($isMe != 1)
        {
            $where .= " and a.status = '2' ";
        }else
        {
            $where .= " and (a.status = '2' or a.status = '3') ";
        }
        
        $order = ' order by add_time desc ';
        $totalNum = 5;
        switch ($type)
        {
            case 1:
                $totalNum = 5;
                break;
            case 2:
                $totalNum = 5;
                $where .= " and is_handpick = '1' and banner_url != 2 ";
                $order = ' order by handpick_time desc ';
                break;
            case 3:
                $totalNum = 5;
                
                if(empty($myLng) || empty($myLat))
                {
                    $myLng = 116.39564503788;
                    $myLat = 39.92998577808;
                }
                //读取经度转化为距离的系数
                $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

                //读取维度转化为距离的系数
                $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
                $flag = 0.5; //5表示10000米

                $order = " order by ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc, a.id desc";
                if(!$lastAddTime)
                {
                    $lastAddTime = 0;
                }
                $limit = " limit $lastAddTime, $totalNum ";
                $where .= " and (a.push_type ='1' or a.push_type ='2')  ";
                break;
            case 4:
                $where .= " and is_banner = '1' and banner_order > 0";
                $order = ' order by banner_order asc ';
                $totalNum = 5;
                break;
            case 5:
                $totalNum = 5;
                $where .= " and is_scheme = '1' ";
                $order = ' order by scheme_time desc ';
                break;
            case 6:
                $totalNum = 2;
                $where .= " and is_scheme = '1' and scheme_order > 0 ";
                $order = ' order by scheme_order asc ';
                break;
        }
        if($type != 3)
        {
            $limit = " limit $totalNum ";
        }
        
        //朋友广告列表
        if($friendIds)
        {
            $friendId = decodePass($friendIds);
            if(empty($friendId))
            {
                $friendId = dataDecode($friendIds);
                $where .= " and b.uniqueId = '{$friendId}' ";
            }else{
                $where .= " and a.user_id = {$friendId} ";
            }
        }
        //分页起始位置
        if($lastAddTime)
        {
            if($type == 2)
            {
                $where .= " and a.handpick_time < $lastAddTime ";
            }else 
            if($type == 3)
            {

                $redis = new \Org\Util\Redis();
                //var_dump($redis);die;
                if($lastAddTime>0){

                    $flow_time = $redis->get($userId);
                    //echo $flow_time;
                }else{
                    $tmpTime = time();
                    $redis->set($userId,$tmpTime);
                    $flow_time = $tmpTime;
                }
                
                if($flow_time>0){
                    $where .= " and a.add_time < $flow_time ";
                }
                
//                $where .= " and a.scheme_time < $lastAddTime ";
            }
            else if($type == 5)
            {
                $where .= " and a.scheme_time < $lastAddTime ";
            }
            else 
            {
                $where .= " and a.add_time < $lastAddTime ";
            }
        }else{
            if($type == 3){
                $redis = new \Org\Util\Redis();
                $tmpTime = time();
                $redis->set($userId,$tmpTime);
                $flow_time = $tmpTime;
                
                $where .= " and a.add_time < $flow_time ";
            }
        }
        
        //如果是关键词搜索关联标签表
        $join = '';
        if($search)
        {
            $join .= ' right join lu_poster_type_relation_new as d on a.id = d.dataId right join lu_poster_tags c on c.cid = d.typeId ';
            $where .= " and (a.title like '%{$search}%' or c.name like '%{$search}%') ";
            
            //如果关键字是标签 添加标签搜索记录
            $poster_tags_sql = "select cid from lu_poster_tags where name = '{$search}' order by cid desc limit 1";
            $poster_tags = M()->query($poster_tags_sql);
            if($poster_tags)
            {
                $tags_log_sql = "select id from lu_cate_tags_search_log where dataId = {$poster_tags[0]['cid']} and type = '1'";
                $tags_total = M()->query($tags_log_sql);
                if($tags_total)
                {
                    $tags_log_sql = "update lu_cate_tags_search_log set total = total + 1 where id = {$tags_total[0]['id']}";
                    M()->query($tags_log_sql);
                }else
                {
                    $tags_log_sql = "insert into lu_cate_tags_search_log (dataId, type, total) values ({$poster_tags[0]['cid']}, '1', 1)";
                    M()->execute($tags_log_sql);
                }
            }
            
        }
        
        //分类id搜索
        if($cateId)
        {
            $join .= ' right join lu_poster_type_relation_new as f on a.id = f.dataId right join lu_poster_category e on e.cid = f.typeId ';
            $where .= " and e.cid = {$cateId} and f.categoryType = '2' ";
        }
        
        //标签id搜索
        if($tagsId)
        {
            $join .= ' right join lu_poster_type_relation_new as f on a.id = f.dataId right join lu_poster_tags e on e.cid = f.typeId ';
            $where .= " and e.cid = {$tagsId} and f.categoryType = '3' ";
            
            //添加标签搜索记录
            $tags_log_sql = "select id from lu_cate_tags_search_log where dataId = $tagsId and type = '1'";
            $tags_total = M()->query($tags_log_sql);
            if($tags_total)
            {
                $tags_log_sql = "update lu_cate_tags_search_log set total = total + 1 where id = {$tags_total[0]['id']}";
                M()->query($tags_log_sql);
            }else
            {
                $tags_log_sql = "insert into lu_cate_tags_search_log (dataId, type, total) values ($tagsId, '1', 1)";
                M()->execute($tags_log_sql);
            }
        }
        
        $sql = "select DISTINCT {$field} from lu_advertising_base a left join lu_members b on a.user_id = b.id {$join} {$where} {$order} {$limit} ";
        //echo $sql;
//        exit;
        $rs = M()->query($sql);
        //是否感兴趣广告（1是；2否）
        
        if($rs)
        {
            $arr_num = count($rs);
            foreach ($rs as $key => $value) {
                $rs[$key]['userId'] = encodePass($value['userId']);
                
                //分类
                $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$value['id']}";
                $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$value['id']}";
                $cate_rs = M()->query($cate_sql);
                $tags_rs = M()->query($tags_sql);  
                $rs[$key]['category'] = array_merge($cate_rs, $tags_rs); 
                
                //是否感兴趣
//                echo 1;exit;
                $isInterest = D('advertising')->isInterest($userId, $value['id'], 2);
                $rs[$key]['isInterest'] = $isInterest == 1 ? 1 : 2;
                
                if($arr_num == $key + 1)
                {
                    $return['lastAddTime'] = $value['addTime'];
                    if($type == 2)
                    {
                        $return['lastAddTime'] = $value['handpick_time'];
                    }
                    else if($type == 5)
                    {
                        $return['lastAddTime'] = $value['scheme_time'];
                    }
                    else if($type == 3)
                    {
                        $lastAddTime += $totalNum;
                        $return['lastAddTime'] = $lastAddTime;
//                        $return['lastAddTime'] = $value['lat'] + $value['lng'];
                    }
                }
                if($type == 3)
                {
                    $distance = $this->getDistance($myLat, $myLng, $value['lat'], $value['lng']);
                    if($distance < 100)
                    {
                        $rs[$key]['distance'] = base64_encode('<100m');
                    }else if($distance < 501)
                    {
                        $rs[$key]['distance'] = base64_encode('100m ~ 500m');
                    }else if($distance < 1001)
                    {
                        $rs[$key]['distance'] = base64_encode('501m ~ 1km');
                    }else if($distance < 2001)
                    {
                        $rs[$key]['distance'] = base64_encode('1km ~ 2km');
                    }else if($distance < 5001)
                    {
                        $rs[$key]['distance'] = base64_encode('2km ~ 5km');
                    }else if($distance < 10001)
                    {
                        $rs[$key]['distance'] = base64_encode('5km ~ 10km');
                    }else
                    {
                        $rs[$key]['distance'] = base64_encode('>10km');
                    }
                }else if($type == 4)
                {
                    $rs[$key]['image'] = $value['banner_url'];
                }else if( $type == 5 || $type == 6 )
                {
                    $rs[$key]['image'] = $value['scheme_url'];
                }
//                unset($rs[$key]['lng']);
//                unset($rs[$key]['lat']);
                
                //分享链接暂时拼接长连接
                $rs[$key]['shareUrl'] = "http://{$_SERVER['HTTP_HOST']}/index.php/Home/Advertising/shareHtml?html=shareaddetails&dataId={$value['id']}";
            }
            $return['info'] = $rs;
            $return['message'] = '查询成功';
            
        }else {
            $return['lastAddTime'] = $lastAddTime;
            $return['status'] = 36;
            $return['info'] = array();
            $return['message'] = '查询成功，暂无数据';
        }
        
        $return['status'] = 1;
        $return['success'] = true;
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 返回最新广告数据【每次10条】
     * 
     *  friendId：广告所属id【选填项】
        type：【默认 1可选】
        【1： 最新广告】
        【2： 精选广告】
        【3： 附近广告】
        【4： 精选页面轮播区】
        【5： 官方策划】
        myLng：物理地址经度(即手机GPS定位的“我的位置”)【附近必传】
                myLat: 物理地址纬度(即手机GPS定位的“我的位置”)【附近必传】
        page:第几页【默认 0】
        Search:【搜索关键词】【可选，搜索页面用】
     * 
     */
    public function listsTest()
    {
        $userId = $this->userId;
        $friendIds = I('post.friendId', 0);
        
        $type = I('post.type', 1);
        $lastAddTime = I('post.lastAddTime', 0);
//        $page = I('post.page', 0);
        $search = I('post.search', '');
        $cateId = I('post.cateId', '');
        $tagsId = I('post.tagsId', '');
        $myLng = I('post.myLng', '');
        $myLat = I('post.myLat', '');
        $isMe = I('post.isMe', 2);
//        $isMe = 1;
//        $myLng = 116.493908;
//        $myLat = 39.922885;
//        $myLng = 116.39564503788;
//        $myLat = 39.92998577808;
        $field = ' scheme_url, banner_url, handpick_time,a.id, a.user_id userId, b.name as nickname, a.image, a.title, a.interest_count interestTotal, a.add_time addTime, a.image, b.image userImage, a.lng, a.lat ';
        
        $where = " where 1 = 1 ";
        if($isMe != 1)
        {
            $where .= " and a.status = '2' ";
        }else
        {
            $where .= " and (a.status = '2' or a.status = '3') ";
        }
        
        $order = ' order by add_time desc ';
        $totalNum = 5;
        switch ($type)
        {
            case 1:
                $totalNum = 5;
                break;
            case 2:
                $totalNum = 5;
                $where .= " and is_handpick = '1' and banner_url != 2 ";
                $order = ' order by handpick_time desc ';
                break;
            case 3:
                $totalNum = 5;
                
                if(empty($myLng) || empty($myLat))
                {
                    $myLng = 116.39564503788;
                    $myLat = 39.92998577808;
                }
                //读取经度转化为距离的系数
                $MAP_LNG_BASIC = C("MAP_LNG_BASIC");

                //读取维度转化为距离的系数
                $MAP_LAT_BASICC = C("MAP_LAT_BASIC");
                $flag = 0.5; //5表示10000米
//                $lngMax = $myLng + $MAP_LNG_BASIC * $flag;
//                $latMax = $myLat + $MAP_LAT_BASICC * $flag;
//                $lngMin = $myLng - $MAP_LNG_BASIC * $flag;
//                $latMin = $myLat - $MAP_LAT_BASICC * $flag;

                $order = " order by ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC}  asc";
                if(!$lastAddTime)
                {
                    $lastAddTime = 0;
                }
                $limit = " limit $lastAddTime, $totalNum ";
                $where .= " and (a.push_type ='1' or a.push_type ='2')  ";
//                $where .= " and ABS(lng-{$myLng})/{$MAP_LNG_BASIC} + ABS(lat-{$myLat})/{$MAP_LAT_BASICC} > $lastAddTime and a.push_type ='1'  ";
//                $where.= ' and a.pushType ="1" and a.lng >' . $lngMin . ' and a.lng <' . $lngMax . ' and a.lat >' . $latMin . ' and a.lat <' . $latMax;
//                $arrReC = M('PosterAdvert')->field($field)->where($whereFirst)->order($order)->select();
//                var_dump(M()->getlastsql(),$arrReC);exit;
                break;
            case 4:
                $where .= " and is_banner = '1' ";
                $totalNum = 5;
                break;
            case 5:
                $totalNum = 5;
                $where .= " and is_scheme = '1' ";
                break;
        }
        if($type != 3)
        {
            $limit = " limit $totalNum ";
        }
        
        //朋友广告列表
        if($friendIds)
        {
            $friendId = decodePass($friendIds);
            if(empty($friendId))
            {
                $friendId = dataDecode($friendIds);
                $where .= " and b.uniqueId = '{$friendId}' ";
            }else{
                $where .= " and a.user_id = {$friendId} ";
            }
        }
        //分页起始位置
        if($lastAddTime)
        {
            if($type == 2)
            {
                $where .= " and a.handpick_time < $lastAddTime ";
            }
//            else if($type == 5)
//            {
//                $where .= " and a.scheme_time < $lastAddTime ";
//            }
            else if($type != 3)
            {
                $where .= " and a.add_time < $lastAddTime ";
            }
        }
        
        //如果是关键词搜索关联标签表
        $join = '';
        if($search)
        {
            $join .= ' right join lu_poster_type_relation_new as d on a.id = d.dataId right join lu_poster_tags c on c.cid = d.typeId ';
            $where .= " and (a.title like '%{$search}%' or c.name like '%{$search}%') ";
            
            //如果关键字是标签 添加标签搜索记录
            $poster_tags_sql = "select cid from lu_poster_tags where name = '{$search}' order by cid desc limit 1";
            $poster_tags = M()->query($poster_tags_sql);
            if($poster_tags)
            {
                $tags_log_sql = "select id from lu_cate_tags_search_log where dataId = {$poster_tags[0]['cid']} and type = '1'";
                $tags_total = M()->query($tags_log_sql);
                if($tags_total)
                {
                    $tags_log_sql = "update lu_cate_tags_search_log set total = total + 1 where id = {$tags_total[0]['id']}";
                    M()->query($tags_log_sql);
                }else
                {
                    $tags_log_sql = "insert into lu_cate_tags_search_log (dataId, type, total) values ({$poster_tags[0]['cid']}, '1', 1)";
                    M()->execute($tags_log_sql);
                }
            }
            
        }
        
        //分类id搜索
        if($cateId)
        {
            $join .= ' right join lu_poster_type_relation_new as f on a.id = f.dataId right join lu_poster_category e on e.cid = f.typeId ';
            $where .= " and e.cid = {$cateId} and f.categoryType = '2' ";
        }
        
        //标签id搜索
        if($tagsId)
        {
            $join .= ' right join lu_poster_type_relation_new as f on a.id = f.dataId right join lu_poster_tags e on e.cid = f.typeId ';
            $where .= " and e.cid = {$tagsId} and f.categoryType = '3' ";
            
            //添加标签搜索记录
            $tags_log_sql = "select id from lu_cate_tags_search_log where dataId = $tagsId and type = '1'";
            $tags_total = M()->query($tags_log_sql);
            if($tags_total)
            {
                $tags_log_sql = "update lu_cate_tags_search_log set total = total + 1 where id = {$tags_total[0]['id']}";
                M()->query($tags_log_sql);
            }else
            {
                $tags_log_sql = "insert into lu_cate_tags_search_log (dataId, type, total) values ($tagsId, '1', 1)";
                M()->execute($tags_log_sql);
            }
        }
        
        $sql = "select DISTINCT {$field} from lu_advertising_base a left join lu_members b on a.user_id = b.id {$join} {$where} {$order} {$limit} ";
//        echo $sql;
//        exit;
        $rs = M()->query($sql);
        //是否感兴趣广告（1是；2否）
        
        if($rs)
        {
            $arr_num = count($rs);
            foreach ($rs as $key => $value) {
                $rs[$key]['userId'] = encodePass($value['userId']);
                
                //分类
                $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$value['id']}";
                $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$value['id']}";
                $cate_rs = M()->query($cate_sql);
                $tags_rs = M()->query($tags_sql);  
                $rs[$key]['category'] = array_merge($cate_rs, $tags_rs); 
                
                //是否感兴趣
//                echo 1;exit;
                $isInterest = D('advertising')->isInterest($userId, $value['id'], 2);
                $rs[$key]['isInterest'] = $isInterest == 1 ? 1 : 2;
                
                if($arr_num == $key + 1)
                {
                    $return['lastAddTime'] = $value['addTime'];
                    if($type == 2)
                    {
                        $return['lastAddTime'] = $value['handpick_time'];
                    }
//                    else if($type == 5)
//                    {
//                        $return['lastAddTime'] = $value['scheme_time'];
//                    }
                    else if($type == 3)
                    {
                        $lastAddTime += $totalNum;
                        $return['lastAddTime'] = $lastAddTime;
//                        $return['lastAddTime'] = $value['lat'] + $value['lng'];
                    }
                }
                if($type == 3)
                {
                    $distance = $this->getDistance($myLat, $myLng, $value['lat'], $value['lng']);
                    if($distance < 100)
                    {
                        $rs[$key]['distance'] = base64_encode('<100m');
                    }else if($distance < 501)
                    {
                        $rs[$key]['distance'] = base64_encode('100m ~ 500m');
                    }else if($distance < 1001)
                    {
                        $rs[$key]['distance'] = base64_encode('501m ~ 1km');
                    }else if($distance < 2001)
                    {
                        $rs[$key]['distance'] = base64_encode('1km ~ 2km');
                    }else if($distance < 5001)
                    {
                        $rs[$key]['distance'] = base64_encode('2km ~ 5km');
                    }else if($distance < 10001)
                    {
                        $rs[$key]['distance'] = base64_encode('5km ~ 10km');
                    }else
                    {
                        $rs[$key]['distance'] = base64_encode('>10km');
                    }
                }else if($type == 4)
                {
                    $rs[$key]['image'] = $value['banner_url'];
                }else if($type == 5)
                {
                    $rs[$key]['image'] = $value['scheme_url'];
                }
//                unset($rs[$key]['lng']);
//                unset($rs[$key]['lat']);
                
                //分享链接暂时拼接长连接
                $rs[$key]['shareUrl'] = "http://{$_SERVER['HTTP_HOST']}/index.php/Home/Advertising/shareHtml?html=shareaddetails&dataId={$value['id']}";
            }
            $return['info'] = $rs;
            $return['message'] = '查询成功';
            
        }else {
            $return['lastAddTime'] = $lastAddTime;
            $return['status'] = 36;
            $return['info'] = array();
            $return['message'] = '查询成功，暂无数据';
        }
        
        $return['status'] = 1;
        $return['success'] = true;
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 返回广告详情数据
     * 
     * 
     */
    public function detail()
    {
        $advId = I('post.advId');
        $userId = $this->userId;
        if (is_empty($advId)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }
        
        $detail = D('advertising')->detail($advId);
        if(!is_array($detail) || empty($detail))
        {
            $return['status'] = 1;
            $return['info']['status'] = 1;
            $return['message'] = '该广告已下架';
        }else{
            $advUserId = $detail[0]['userId'];
            $info = $detail[0];
            $info['userId'] = encodePass($info['userId']);
            $info['isCollection'] = D('advertising')->isCollection($userId, $advId);
            $info['isInterest'] = D('advertising')->isInterest($userId, $advId, 2);
            //分类
            $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$advId}";
            $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$advId}";
            $cate_rs = M()->query($cate_sql);
            $tags_rs = M()->query($tags_sql);  
            $info['category'] = $cate_rs && $tags_rs ? array_merge($cate_rs, $tags_rs) :array();
            $info['goodsInfo'] = D('advertising')->getCommodityByAdvId($advId);
            foreach($info['goodsInfo'] as $k => $v)
            {
                if($v['price'])
                {
                    $info['goodsInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
                }
            }
            $info['favorableInfo'] = D('advertising')->getPreferentialByAdvId($advId);
            foreach($info['favorableInfo'] as $k => $v)
            {
                $sql = " select count(*) as count from lu_vouchers_log where user_id = $userId and preferential_id = {$v['id']} and isUse = '2' and isDel = '2' ";
                $isReceive = M()->query($sql);
                $info['favorableInfo'][$k]['isReceive'] = $isReceive[0]['count'] > 0 ? 1 : 2;

                if($v['price'] && $v['type'] == 2)
                {
                    $info['favorableInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
                }
            }

            $info['serviceInfo'] = D('advertising')->getServiceByAdvId($advId);
            foreach($info['serviceInfo'] as $k => $v)
            {
                if($v['price'])
                {
                    $info['serviceInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
                }
            }

            $info['activityInfo'] = D('advertising')->getActivityByAdvId($advId);
            foreach($info['activityInfo'] as $k => $v)
            {
                if($v['price'])
                {
                    $info['activityInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
                }
            }
            //分享链接暂时拼接长连接
            $info['shareUrl'] = base64_encode("http://{$_SERVER['HTTP_HOST']}/index.php/Home/Advertising/shareHtml?html=shareaddetails&dataId={$info['id']}");

            //是否关注
    //        $map = array(
    //            'userId' => intval($userId),
    //            'focusId' => intval($advUserId)
    //        );
    //        $isFocus = D('focus_merchants')->where($map)->find();
    //        $info['isFocus'] = $isFocus ? 1 : 2;
            $map = array(
                'uid' => intval($userId),
                'fuid' => intval($advUserId),
                'status' => '1'
            );
            $isFocus = D('friend')->where($map)->find();
            $info['isFocus'] = $isFocus ? 1 : 2;
            //关注数量
            $sql = "select count(*) as total from lu_friend where fuid = {$advUserId} and status = '1'";
            $focusNum = M()->query($sql);
            $info['focusNum'] = $focusNum[0]['total'];

            $return['status'] = 1;
            $return['message'] = '';
            $return['success'] = true;
            $return['info'] = $info;
    //        var_dump($return);

            //添加浏览量
            $sql = "update lu_advertising_base set browse_count = browse_count + 1 where id = $advId";
            M()->execute($sql);
        }
        echo jsonStr($return);exit();
    }
    
    /**
     * 领取红包接口【喜欢/无感】
     * 
     */
    public function getRedBag()
    {
        $advId = I('post.advId');
        $userId = $this->userId;
        $type = I('post.type');
        
        $return = array();
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        if (empty($advId) || empty($type)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            //是否已领取红包
            $sql = "select count(*) as count from lu_red_bag_log where user_id = $userId and adv_id = $advId";
            $exist_red = M()->query($sql);
            $exist_red = $exist_red[0]['count'];
            if($exist_red)
            {
                $return['status'] = 1;
                $return['message'] = '已经领取过红包';
                $return['blackStr'] = "已经领取过红包~";
                $return['blueStr'] = '不要气馁，试试其他广告';
            }else
            {
                $rs = D('advertising')->addInterest($userId, $advId, '2', $type);
                if($rs)
                {
                    //是否还有红包
                    $red_number = M('advertising_base')->where("id=$advId")->getField('red_number');
                    $get_red_number = M('advertising_base')->where("id=$advId")->getField('get_red_number');
                    if($red_number - $get_red_number > 0)
                    {
                        //领取红包添加记录
                        $each_red_money = M('advertising_base')->where("id=$advId")->getField('each_red_money');
                        $money = $this->fenZhuanYuan($each_red_money);
                        D('advertising')->getRedBag($userId, $advId, $each_red_money);

                        $sql = "update lu_advertising_base set get_red_number = get_red_number + 1 where id = $advId";
                        M()->execute($sql);
                        
                        
                        //红包金额累加到用户钱包
                        D('advertising')->userGetRedBag($userId, $each_red_money);
//                        $sql = "update lu_members set money = money + {$each_red_money}, total_money = total_money + {$each_red_money} where id = $userId";
//                        M()->execute($sql);
//                        var_dump($red_money);exit;
                        $return['blackStr'] = "恭喜，获得{$money}元";
                        $return['blueStr'] = '广告红包奖励已存入现金钱包';
                        $return['status'] = 1;
                        $return['message'] = '查询成功';
                    }else
                    {
                        $return['blackStr'] = "与奖励擦肩而过~";
                        $return['blueStr'] = '不要气馁，试试其他广告';
                        $return['status'] = 1;
                        $return['message'] = '查询成功';
                    }
                }else
                {
                    $return['blackStr'] = "已经领取过红包~";
                    $return['blueStr'] = '不要气馁，试试其他广告';
                    $return['status'] = 1;
                    $return['message'] = '已经领取过红包';
                }
                $return['info'] = $rs;
            }
        }
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 添加广告留言
     * 
     */
    public function addLeave()
    {
        $advId = I('post.advId');
        $userId = $this->userId;
        $content = I('post.content');
        
        $return = array();
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        
        if (empty($advId) || empty($content)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = D('advertising')->addLeaveWord($userId, $advId, $content);
            //判断添加广告留言是否成功
            if(!$rs)
            {
                $return['status'] = -1;
                $return['message'] = '留言失败';
            }else
            {
                $return['status'] = 1;
                $return['message'] = '留言成功';
            }
        }
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 广告留言列表
     * 
     */
    public function leaveList()
    {
        $advId = I('post.advId');
        $userId = I('post.userId');
        $page = I('post.page', 0);
        
        $totalNum = 5;
        $offset = $page * $totalNum;
        
        $return = array();
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        
        if (is_empty($advId)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = D('advertising')->leaveLists($advId, $offset, $totalNum);
//            var_dump($rs);exit;
            if(!$rs)
            {
                $return['status'] = 36;
                $return['info'] = array();
                $return['message'] = '查询成功，暂无数据';
            }else {
                $return['status'] = 1;
                $return['info'] = $rs;
                $return['message'] = '查询成功';
            }
        }
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 3.2.3添加转发朋友接口
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $frendsId:朋友id串【必填项】
     * @return json 广告数据的JSON字符串
     */
    public function addForward() {
        $return['success'] = true;

        $userId = $this->userId;
        $id = I('post.method');
//        $id = decodePass($id);
        $friendsId = I('post.friendsId');
        $type = I('post.type');
//        $vouchersNumber = I('post.vouchersNumber');
//$id = 26;
//$type = 1;
//$friendsId = array("95401","42900");
//$friendsId = json_encode($friendsId);
//$friendsId = '["6284d8634Q5jbtAAsJUQE"]';
        if (empty($userId) || empty($id) || empty($friendsId) ) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            
            $friendsArr = json_decode(str_replace('&quot;', '"', $friendsId), true);
//            $friendsStr = implode(',', $friendsArr);
//            var_dump($friendsId,str_replace('&quot;', '"', $friendsId),$friendsArr);exit;
            
            switch ($type)
            {
                case 1:
                    $res = D('Advertising')->detail($id); //查询广告基本信息
                    break;
                case 2:
                    $sql = "select * from lu_service where id = $id and status = '1'";
                    $res = M()->query($sql);
                    break;
                case 3:
                    $sql = "select * from lu_advertising_preferential where id = $id and status = '1'";
                    $res = M()->query($sql);                    
                    break;
                case 4:
                    $sql = "select * from lu_commodity where id = $id and status = '1'";
                    $res = M()->query($sql);
                    break;
                case 5:
                    $sql = "select * from lu_activity where id = $id and status = '1'";
                    $res = M()->query($sql);
                    break;
            }
            
            if ((is_array($res) || is_null($res)) && empty($res)) {//判断状态
                $return['status'] = -1;
                $return['message'] = '转发失败';
            } else {
                $insert_str = "";
                $addTime = time();
                foreach($friendsArr as $k => $v)
                {
                    $v = decodePass($v);
                    $insert_str .= "($userId, $v, $id, $addTime, '$type'),";
                    
                    //判断消息表是否有记录
                    $sql2 = "select * from lu_new_message where `userId` = $userId and `friendId` = $v ";
                    $rs2 = M()->query($sql2);
                    if(empty($rs2))
                    {
                        $sql2 = "insert into lu_new_message (`userId`, `friendId`, `updateTime`) values ($userId, $v, $addTime) ";
                        M()->execute($sql2);
                    }else
                    {
                        $sql2 = "update lu_new_message set `updateTime` = $addTime, `isNew` = '1' where `userId` = $userId and `friendId` = $v ";
                        M()->execute($sql2);
                    }
                    
                    $sql3 = "select * from lu_new_message where `userId` = $v and `friendId` = $userId ";
                    $rs3 = M()->query($sql3);
                    if(empty($rs3))
                    {
                        $sql3 = "insert into lu_new_message (`userId`, `friendId`, `updateTime`) values ($v, $userId, $addTime) ";
                        M()->execute($sql3);
                    }else
                    {
                        $sql3 = "update lu_new_message set `updateTime` = $addTime, `isNew` = '1' where `userId` = $v and `friendId` = $userId ";
                        M()->execute($sql3);
                    }
                }
                $insert_str = substr ($insert_str, 0, -1);
                $sql = "insert into lu_friend_forward_new (`userId`, `friendId`, `dataId`, `addTime`, `type`) values $insert_str";
//                echo $sql; exit;
                M()->execute($sql);
                $return['status'] = 36;
                $return['message'] = '转发成功';
            }
        }
        echo jsonStr($return);exit();
    }

    /**
     * 服务、商品、优惠代金券、活动详情接口
     * 
     */
    public function zhDetail()
    {
        $advId = I('post.advId');
        $type = I('post.type');
        $user = $this->userId;
        if (is_empty($advId) || is_empty($type)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            switch($type)
            {
                case 1:
                    $tableName = 'lu_service';
                    $field = " *, name as title";
                    break;
                case 2:
                    $tableName = 'lu_commodity';
                    $field = " *, name as title, buy_url as buyLink";
                    break;
                case 3:
                    $tableName = 'lu_advertising_preferential';
                    $field = "*, number as price";
                    break;
                case 4:
                    $tableName = 'lu_activity';
                    $field = "*, name as title, buy_url as buyLink";
                    break;
            }
            $sql = "SELECT $field FROM `$tableName` WHERE ( id=$advId ) LIMIT 1";
            $info = M()->query($sql);
            if($info && is_array($info)){
                $info = $info[0];
                //分享链接暂时拼接长连接
                switch($type)
                {
                    case 1:
                        $htmlName = 'shareservice';
                        break;
                    case 2:
                        $htmlName = 'shareproduct';
                        break;
                    case 3:
                        $htmlName = 'sharediscount';
                        break;
                    case 4:
                        $htmlName = 'shareactivity';
                        break;
                }
                $return['info']['shareUrl'] = base64_encode("http://{$_SERVER['HTTP_HOST']}/index.php/Home/Advertising/shareHtml?html={$htmlName}&dataId={$info['id']}");
                
                if($info['price'] && $info['type'] != 1)
                {
                    $info['price'] = $this->fenZhuanYuan($info['price']);
                }
                
                if($type == 2 || $type == 4)
                {
                    $return['info']['buyLink'] = $info['buyLink'];
                }
                
                $return['info']['type'] = $type; 
//                $return['info']['shareUrl'] = $type; 
                $return['info']['image'] = $info['image']; 
                $return['info']['title'] = $info['title']; 
                $return['info']['price'] = $info['price']; 
                $return['info']['content'] = $info['content']; 
                $return['info']['status'] = $info['status']; 
                $return['info']['advId'] = $info['adv_id']; 
                $return['info']['addTime'] = $info['add_time'];
                
                //优惠/代金券
                if($type == 3)
                {
                    $return['info']['startDay'] = $info['start_date']; 
                    $return['info']['endDay'] = $info['end_date']; 
                    
                    $return['info']['startTime'] = date('H:i', $info['start_time'] + 16 * 3600);
                    $return['info']['endTime'] = date('H:i', $info['end_time'] + 16 * 3600); 
                    
                    $return['info']['isAllDay'] = $info['is_all_day']; 
                    $return['info']['method'] = $info['type']; 
                    $return['info']['conditionsUse'] = $info['conditions']; 
                    
                    $sql = " select count(*) as count from lu_vouchers_log where user_id = $user and preferential_id = {$info['id']} and isUse = '2'  and isDel = '2' ";
                    $isReceive = M()->query($sql);
                    
                    $return['info']['isReceive'] = $isReceive[0]['count'] > 0 ? 1 : 2;                     
                }
            
                $return['status'] = 1;
                $return['message'] = '查询成功';
            } else {
                $return['status'] = 10;
                $return['message'] = '查询成功，暂无数据';
            }
        }
        header("Content-Type: application/json; charset=utf-8");
        echo jsonStr($return);exit;
    }
    
    
    /**
     * 举报接口 - 增加举报信息
     * @param  string $token 令牌
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID(举报人)
     * @param  string $category 举报内容的类别
     * @return JSON 是否成功 status -1 失败  1 成功
     */
    public function addAccusation() {
        $return['success'] = true;
        //获取参数
        $dataId = I('post.dataId');
        $userId = $this->userId;
        $category = I('post.category');
        $content = I('post.content', '', 'trim');
        $options = I('post.options', '6');
        $return['status'] = 10;
        $return['message'] = '操作失败';
        $return['success'] = true;
        if (is_empty($dataId) || is_empty($category) || is_empty($options)) {
            $return['status'] = 10;
        } else {
            if (in_array($category, array(1, 2, 3))) {
                switch ($category) {
                    case 1:
                        $map['id'] = $dataId;
                        $self = M('advertising_base')->where($map)->find();
                        if ($self['user_id'] == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                    case 2:
                        $dataId = decodePass($dataId);
                        $map['id'] = $dataId;
                        $self = M('Found')->where($map)->find();
                        if ($self['userId'] == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                    case 3:

                        if ($dataId == $userId) {
                            $return['status'] = -631;
                            $return['message'] = '不能举报自己';
                            echo jsonStr($return);
                            exit(0);
                        }
                        break;
                }
                

                //查询数据是否正常显示（删除等信息不做以下操作）
                if ($category == 2) {
                    $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("id")->find();
                }
                if ($category == 1) {
                    //$data = M("poster_advert")->where(array('id' => $dataId, "status" => 1))->field("id")->find();
//                    $data = M("poster_advert")->where(array('id' => $dataId))->field("id,status,integral,endTime,exposeTotalIntegral,extendTotalIntegral")->find();
                    $data = M("advertising_base")->where(array('id' => $dataId))->field("id,status")->find();
                    //echo M("poster_advert")->getLastSql();die;
                    
                    if($data){//验证广告是否正常
                        if($data['status']!=2){
                            $return['status'] = 10;
                            $return['message'] = '广告已下架';
                            echo jsonStr($return);exit();
                        }
//                        else{
//                            //echo $data['integral'].'-'.$data['exposeTotalIntegral'].'-'.$data['extendTotalIntegral'];die;
//                            if($data['integral']-$data['exposeTotalIntegral']-$data['extendTotalIntegral']<=0 || time()>$data['endTime']){
//                                $return['status'] = 10;
//                                $return['message'] = '广告已下架';
//                                echo jsonStr($return);exit();
//                            }
//                        }
                    }
                }
                if ($category == 3) {
                    $dataId = decodePass($dataId);
                    $data = D("Members")->where(array('id' => $dataId, "freeze" => "0"))->field("id")->find();
                }
                if (empty($data)) {
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                    //$return['success'] = true;
                    $return['flag'] = '0';
                } else {
                    $data = array(
                        'userId' => $userId,
                        'dataId' => $dataId,
                        'content' => $content,
                        'moduleType' => $category,
                        'type' => $options,
                        'addTime' => time()
                    );
                    $res = M('accusation_new')->add($data);
                    if ($res == true) {
                        $return['status'] = 1;
                        $return['message'] = '举报成功';
                    } else {
                        $return['status'] = 10;
                        $return['message'] = '操作失败';
                    }
                }
            }
        }
        header("Content-Type: application/json; charset=utf-8");
        //$this->apiCallback($return);
        echo jsonStr($return);
        exit(0);
    }
    
    /**
     * 获取代金券接口
     * 
     */
    public function addFavorable()
    {
        $dataId = I('post.advId');
        $userId = $this->userId;
        $type = I('post.type');
        $fuid = I('post.fuid', 0);
        if (is_empty($dataId) || is_empty($type) || ($type == 2 && is_empty($fuid))) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $sql = "select status, end_date, end_time from lu_advertising_preferential where id = $dataId";
            $flag = M()->query($sql);
//            var_dump('<pre>',$flag, date('Y-m-d H:i:s', $flag[0]['end_date']));exit;
            if(isset($flag[0]['status']) && $flag[0]['status'] == 1 && $flag[0]['end_date'] + $flag[0]['end_time'] > time())
            {
                //是否已领取
                $sql = " select count(*) as count from lu_vouchers_log where user_id = {$userId} and preferential_id = {$dataId} and isUse = '2'  and isDel = '2' ";
                $isReceive = M()->query($sql);
                $isReceiveStr = $isReceive[0]['count'] > 0 ? 1 : 2; 
                $return['test'] = $isReceiveStr;
                if($isReceiveStr == 2)
                {
                    $time = time();
                    $data['user_id'] = $userId;
                    $data['preferential_id'] = $dataId;
                    $data['type'] = $type;
                    $data['fuid'] = $fuid;
                    $data['vouchers_num'] = $time;
                    $data['add_time'] = $time;
                    $flag = M('vouchers_log')->add($data);

                    if($flag)
                    {
                        //生成二维码
                        $tmpJson['fb_type'] = 4;//1：个人，2-广告（优惠、商品），3-公益，4-代金券，5-商家扫码
                        $tmpJson['number'] = $time;
                        $tmpJson['vouchersId'] = encodePass($flag);

                        $val = urlencode(base64_encode(jsonStr($tmpJson)));
                        $text = C('DOWNLOAD_ADDRESS');
                        $text .= '?' . $val; //二维码内信息
                        $nowDay=date("Y-m-d");
                        //$file = '/home/wwwroot/dev/Uploads/vouchersCode/'.$nowDay.'/';
                        //$file = '/home/wwwroot/apiol/Uploads/vouchersCode/'.$nowDay.'/';
                        $file = 'Uploads/vouchersCode/'.$nowDay.'/';

                        if(!is_dir($file)){//判断目录是否存在
                            mkdir($file);
                        }
                        $url = 'Uploads/vouchersCode/'.$nowDay.'/'; //存储地址

                        $urlLast = encodePass($resVouchers['id']) . time() . '.jpg';
                        D('Members')->qrcode($text, ROOT .'/'. $url . $urlLast, 'H', '5');
                        $str = $url . $urlLast;

                        $sql = " update lu_vouchers_log set codeImageUrl = '$str' where id = $flag ";
                        M()->execute($sql);

                        $return['status'] = 1;
                        $return['message'] = '成功';
                    }else
                    {
                        $return['status'] = -1;
                        $return['message'] = '操作失败';
                    } 
                }else
                {
                    $return['status'] = 9;
                    $return['message'] = '优惠/代金券已领取';
                }
            }else
            {
                $return['status'] = 36;
                $return['message'] = '优惠/代金券已过期';
            }
            
        }
        $return['success'] = true;
        header('Content-Type:application/json; charset=UTF-8');
        echo json_encode($return);exit;
    }
    
     /*
     * 获取我的代金券列表
     */
    public function getMyVouchersList(){
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $page = I('post.page', 0);
        $pageSize = I('post.pageSize', 10);
        $categoryType = I('post.category');
            
        if (is_empty($categoryType)) {//判断参数是否有缺失
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            
            $category=2;
            if($categoryType==1){
                $category=1;
            }
            
            $dataList = D('Advertising')->getMyVouchersList($userId,$page,$pageSize,$category);
            
            if (empty($dataList)) {
                $return['status'] = 36;
                $return['message'] = '暂无任何代金券';
            }else{
                foreach($dataList as $k => $v)
                {
                    if($v['type'] == 2)
                    {
                        $dataList[$k]['price'] = $this->fenZhuanYuan($v['price']);
                    }
                    
                    if($categoryType==1)
                    {
                        $time = time();
                        if($v['start_date'] > $time ||  strtotime(date('Ymd',$time)) + $v['start_time'] > $time || strtotime(date('Ymd',$time)) + $v['end_time'] < $time)
                        {
                            $dataList[$k]['voucherStatus'] = 1;//未开始
                        }
                        if($v['isUse'] == 2)
                        {
                            $dataList[$k]['voucherStatus'] = 2;//未使用
                        }
                    }else
                    {
                        if($v['isUse'] == 1)
                        {
                            $dataList[$k]['voucherStatus'] = 4;//已使用
                        }else{
                            $dataList[$k]['voucherStatus'] = 3;//已作废
                        }
                    }
                }
                $return['info'] = $dataList;
                $return['status'] = 1;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit();
    }
    
    /**
     * 收藏接口 - 添加收藏信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $dataId 数据ID
     * @param  string $userId 会员ID
     * @param  string $type 收藏内容的类别
     * @return JSON 	
     */
    public function addCollect() {
        $return['success'] = true;
        
        //获取参数
        //$token = I('post.token', '1.2', 'trim');
        //$userId = I('post.userId');
        $userId = $this->userId;
        $type = I('post.type');
        $dataId = I('post.id');
        $version = I('post.version', '1.2', 'trim');
        $modelName = 'CollectPosterLogNew';
//        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($userId) || is_empty($type)) {
            $return['status'] = 10;
            //$return['message'] = '传参不完整';
            $return['message'] = '操作失败2345678';
        } else {
            //查询数据是否正常显示（删除等信息不做以下操作）
            if ($type == 4) {
                $data = D("Found")->where(array('id' => $dataId, "del" => "1"))->field("*")->find();
            }
            if ($type == 3) {
                $data = M('advertising_base')->where(array('id' => $dataId))->field("*")->find();
                
                if($data){//验证广告是否正常
//                    if($data['status']!=1 || ($data['integral']-$data['exposeTotalIntegral']-$data['extendTotalIntegral']<=0) || time()>=$data['endTime']){
                    if($data['status']!=2){
//                    if($data['status']!=1 || time()>=$data['endTime']){
                        $return['status'] = 10;
                        $return['message'] = '广告已下架';
                        echo jsonStr($return);exit();
                    }elseif(time()<$data['startTime']){
                        $return['status'] = 10;
                        $return['message'] = '广告尚未开始';
                        echo jsonStr($return);exit();
                    }
                }
            }
            if ($type == 2) {
                $data = D("Shop")->where(array('id' => $dataId, "status" => "1"))->field("*")->find();
            }

            if (empty($data)) {
                $return['status'] = 10;
                //$return['message'] = '数据不存在、或非法传参';
                $return['message'] = '操作失败';
                $return['flag'] = 0;
            } else {
                $map['userId'] = $userId;
                $map['dataId'] = $dataId;
                if ($type == 3) {
                    $exist = M('CollectPosterLogNew')->where($map)->find();
                    M('advertising_base')->where(array('id' => $dataId))->setInc('collection_count', 1);
                } else {
                    $exist = D($modelName)->where($map)->find();
                }
                //echo M('CollectPosterLogNew')->getLastSql();die;
                if (!empty($exist)) {
                    $return['status'] = -611;
                    $return['message'] = '请勿重复收藏'.is_bool($exist). !empty($exist);
                    $return['flag'] = 1;
                } else {
                    $data = array(
                        'userId' => intval($userId),
                        'dataId' => intval($dataId),
                        'addTime' => time()
                    );

                    $res = D($modelName)->add($data);
                    //echo D($modelName)->getLastSql();die;

                    if ($res == true) {
                        $return['status'] = 1;
                        $return['message'] = '收藏成功';
                        $return['flag'] = 1;
                    } else {
                        $return['status'] = -1;
                        $return['message'] = '操作失败';
                        $return['flag'] = 0;
                    }
                }
            }
        }
        header('Content-Type:application/json; charset=UTF-8');
        echo jsonStr($return);exit();
    }

    /**
     * 收藏接口 - 取消收藏信息
     * @param  string $token 令牌
     * @param  string $version 版本号(如"1.2")
     * @param  string $d 数据ID
     * @param  string $userId 会员ID
     * @param  string $type 收藏内容的类别
     * @return JSON 	
     */
    public function cancelCollect() {
        //$token = I('post.token', '', 'trim');
        $dataId = I('post.id', '', 'trim');
        //$userId = I('post.userId', '', 'trim');
        $userId = $this->userId;
        $version = I('post.version', '1.2', 'trim');
        $type = I('post.type');
        $modelName = 'CollectPosterLogNew';
        
        header('Content-Type:application/json; charset=UTF-8');
//        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($userId) || is_empty($type)) {
            $return['status'] = 10;
            //$return['message'] = '传参不完整';
            $return['message'] = '操作失败';
            $return['success'] = true;
            echo jsonStr($ret);
        } else {
          if ($type == 3) {
                $data = M('advertising_base')->where(array('id' => $dataId))->field("*")->find();
                if($data){//验证广告是否正常
                    if($data['status']!=2){
                        $return['status'] = 10;
                        $return['message'] = '广告已下架';
                        echo jsonStr($return);exit();
                    }elseif(time()<$data['startTime']){
                        $return['status'] = 10;
                        $return['message'] = '广告尚未开始';
                        echo jsonStr($return);exit();
                    }
                }
            }

            $map = array(
                'userId' => intval($userId),
                'dataId' => intval($dataId)
            );

            $res = M($modelName)->where($map)->delete();
//            var_dump(M($modelName)->getlastsql(),$res);
            
            $map['userId'] = $userId;
            $map['dataId'] = $dataId;
            $exist = D($modelName)->where($map)->find();
            if (is_bool($exist) || !empty($exist)) {
                $flag = 1;
            } else {
                $flag = 0;
            }

            if ($res == true) {
                if ($type == 3) {
                    M('advertising_base')->where(array('id' => $dataId))->setDec('collection_count', 1);
                }

                $return['status'] = 1;
                $return['success'] = true;
                $return['message'] = '取消成功';
                $return['flag'] = $flag;
                echo jsonStr($return);exit();
            } else {
                $return['status'] = -1;
                $return['success'] = true;
                $return['message'] = '取消失败';
                $return['flag'] = $flag;
                echo jsonStr($return);exit();
            }
        }
    }

    /**
     * 搜索页面接口
     */
    public function searchView()
    {
        $return['success'] = true;
        
        $info['hotSearch'] = D("Advertising")->hotSearch();
        $info['category'] = D("Advertising")->cateList();

        $return['info'] = $info;
        $return['status'] = 1;
        $return['message'] = '操作成功';
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 3.2会员分享接口
     * @param  string $version:版本号(如“1.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $dataId:广告的ID
     * @param  string $type:广告分享类型 分享类型：1表示新浪微博，2表示微信好友，3表示微信朋友圈，4表示qq，5表示人人，6表示qq空间
     * @return json 广告数据的JSON字符串
     */
    public function share() {
        //检测是否能通过检测
        $this->checkKey();

        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $type = I('post.type');
        $id = I('post.dataId');

        if (is_empty($userId) || is_empty($id) || is_empty($type)) {
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            $userId = $this->userId;
            $id = decodePass($id);
            //查询广告数据
            $field = 'id,integral,title,status,proRedEnd,proRedStart,exLowPrompt,exHighPrompt,exposeTotalIntegral,extendTotalIntegral,addTime';
            $res = D('Poster')->getPosterAdvert($id, $field);

            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($re)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else if ($res['status'] != '1') {
                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                $return['status'] = -230;
                $return['message'] = '广告已关闭';
            } else {
                $tmpIntegral = $res['integral'] - ( $res['exposeTotalIntegral'] + $res['extendTotalIntegral']);
                if ($tmpIntegral > 0) {

                    //添加飞币 
                    $integral = $this->calculateUserShareIntegral($userId, $id, $res['proRedStart'], $res['proRedEnd'], $tmpIntegral);

                    //写入广告分享日志表
                    $data = array();
                    $data['dataId'] = $id;
                    $data['userId'] = $userId;
                    $data['integral'] = $integral;
                    $data['status'] = $integral ? "1" : "0";
                    $data['addTime'] = time();
                    $data['type'] = $type;
                    //$data['mobileflag'] = $this->_check->user_flag;
                    $reShareLog = M("share_poster_log")->add($data);
                    //echo  M("share_poster_log")->getLastSql();die;

                    if ($reShareLog) {//判断添加分享记录是否成功
                        D('Poster')->addClickTotal($id, 2);

                        //根据获取不同的飞币值，提示不同的提示语
                        $return['message'] = '分享成功';
                        if ($integral) {
                            $result = D("Members")->addUsersIntegral($userId, $integral);

                            $content = '你通过分享“' . $res['title'] . '”的获取飞币，送飞币';
                            D("Members")->addMemberDope($userId, $content, '1', $integral, $id, '9');

                            M('PosterAdvert')->where('id =' . $id)->setInc("extendTotal", 1);
                            M('PosterAdvert')->where('id =' . $id)->setInc("extendTotalIntegral", $integral);
                        }

                        if ($integral == $res['proRedEnd']) {//等于最大值时
                            if ($res['exHighPrompt']) {
                                $return['message'] = $res['exHighPrompt'];
                            }
                        } elseif ($integral > $res['proRedStart'] && $integral < $res['proRedEnd']) {
                            $return['message'] = '分享成功,你已经获取飞币';
                        } else {
                            if ($integral == $res['proRedStart']) {
                                if ($res['exLowPrompt']) {
                                    $return['message'] = $res['exLowPrompt'];
                                }
                            } else {
                                if ($integral > 0) {
                                    $return['message'] = '分享成功,你已经获取飞币';
                                }
                            }
                        }

                        $return['status'] = 1;
                    } else {
                        $return['status'] = 10;
                        $return['message'] = '操作失败';
                    }
                } else {
                    $return['status'] = 1;
                    $return['message'] = '分享成功';
                }
            }
        }
        echo jsonStr($return);exit(0);
    }
    
    function fenZhuanYuan($fen){
        $fen = $fen / 100;
        $pos = strpos($fen, '.');
        if($pos){
            $str = substr($fen, $pos + 1);
            if(strlen($str) == 1){
                return $fen . '0';
            }else{
                return $fen;
            }
        }else{
            return $fen . '.00';
        }
    }
    
    /*
     * 城市选择接口
     */
    public function getCityLists()
    {
        $userId = I('post.userId');
        $phone = I('post.phone');
        //热门城市id    北上广深
        $hot_city = array(1, 9, 234, 236);
        $hot_city = implode(',', $hot_city);
        
        $hot_sql = "select id as cityId, name as cityName, lng, lat from lu_city where id in ($hot_city)";
        $return['info']['hotCity'] = M()->query($hot_sql);
        
        $other_sql = "select a.id as cityId, a.name as cityName, a.lng, a.lat from lu_city as a right join lu_push_city as b on a.id = b.cityId where a.id not in ($hot_city)";
        $return['info']['otherCity'] = M()->query($other_sql);
        
        $return['success'] = true;
        
        $return['status'] = '1';
        $return['message'] = '查询成功';

        echo json_encode($return);exit;
    }
    
    public function myCollectList()
    {
        $return['success'] = true;
        $return['status'] = 0;
        $page = I('post.page', 0);
        $pageSize = I('post.pageSize', 10);
        $userId = $this->userId;
        $offset = $page * $pageSize;
        if (is_empty($pageSize) || is_empty($userId) || is_empty($page)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $field = ' c.id as userId, c.name as nickname, c.image as userImage, b.id, b.title, b.image, b.add_time as addTime, b.interest_count as interestTotal ';           
            $sql = "select $field from lu_collect_poster_log_new as a left join lu_advertising_base as b on a.dataId = b.id left join lu_members as c on b.user_id = c.id where a.status = '1' and a.userId = $userId and b.status = '2' order by a.addTime desc limit $offset, $pageSize";
//            echo $sql;exit;
            $rs = M()->query($sql);
            //是否感兴趣广告（1是；2否）
            if($rs)
            {
                $arr_num = count($rs);
                foreach ($rs as $key => $value) {
                    $rs[$key]['userId'] = encodePass($value['userId']);

                    //分类
                    $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$value['id']}";
                    $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$value['id']}";
                    $cate_rs = M()->query($cate_sql);
                    $tags_rs = M()->query($tags_sql);  
                    $rs[$key]['category'] = array_merge($cate_rs, $tags_rs); 

                    //是否感兴趣
    //                echo 1;exit;
                    $isInterest = D('advertising')->isInterest($userId, $value['id'], 2);
                    $rs[$key]['isInterest'] = $isInterest == 1 ? 1 : 2;
                }
                $return['status'] = 1;
                $return['info'] = $rs;
                $return['message'] = '查询成功';
            }else {
                $return['status'] = 0;
                $return['message'] = '没有数据了';
                $return['info'] = array();
            }
        }

        echo jsonStr($return);exit();
    }
    /** 
    * @desc 根据两点间的经纬度计算距离 
    * @param float $lat 纬度值 
    * @param float $lng 经度值 
    */
    function getDistance($lat1, $lng1, $lat2, $lng2) 
    { 
        $earthRadius = 6367000; //approximate radius of earth in meters 

        /* 
        Convert these degrees to radians 
        to work with the formula 
        */

        $lat1 = ($lat1 * pi() ) / 180; 
        $lng1 = ($lng1 * pi() ) / 180; 

        $lat2 = ($lat2 * pi() ) / 180; 
        $lng2 = ($lng2 * pi() ) / 180; 

        /* 
        Using the 
        Haversine formula 

        http://en.wikipedia.org/wiki/Haversine_formula 

        calculate the distance 
        */

        $calcLongitude = $lng2 - $lng1; 
        $calcLatitude = $lat2 - $lat1; 
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2); 
        $stepTwo = 2 * asin(min(1, sqrt($stepOne))); 
        $calculatedDistance = $earthRadius * $stepTwo; 

        return round($calculatedDistance); 
    } 
    
    /*
     * 清空我的代金券
     */
    public function delMyVouchers(){
        $return['success'] = true;

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $vouchersId = I('post.vouchersId');
        //echo $version.'-'.$userId.'-'.$phone.'-'.$vouchersId;
        if(is_empty($vouchersId) ){//判断参数是否有缺失
            $return['status'] = 10;
            $return['message'] = '操作失败';
        }else{
            $userId = $this->userId;
            
            $res=M('vouchers_log')->field('id,isDel')->where('user_id='.$userId.' and id='.$vouchersId)->find();
            //echo M('get_vouchers_log')->getLastSql();die;
//            var_dump($res, M()->getlastsql());exit;
            if($res){
                if($res['isDel']==1){
                    $return['status'] = 1;
                    $return['message'] = '此代金券已删除';
                }else{
                    $dataDel['isDel']='1';
                    //$res = M('get_vouchers_log')->where(' vouchersId =' . $vouchersId . ' and userId=' . $userId)->delete();
                    $resDel=M('vouchers_log')->where('user_id='.$userId.' and id='.$vouchersId)->save($dataDel);
                    //echo M('get_vouchers_log')->getLastSql();die;
//                                var_dump($resDel, M()->getlastsql());exit;
                    //恢复获取设置
                    //$resDel=M('get_vouchers_log')->where('userId='.$userId.' and vouchersId='.$vouchersId)->save($dataDel);
                    
                    //if($resDel){//判断删除是否成功
                        $return['status'] = 1;
                        $return['message'] = '删除成功';
                    //}else{
                        //$return['status'] = 10;
                        //$return['message'] = '操作失败';
                    //}
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '操作失败';
            }
        }
        echo jsonStr($return);exit();
    }
    
    /*
     * 添加扫码记录
     * @param  string $version:版本号(如“3.2”)
     * @param  string $userId：会员唯一码
     * @param  string $phone：会员注册手机号
     * @param  string $type：1：扫会员，2：扫广告，3：扫代金券，4：商家扫码【必填项】
     * @param  string $dataId:广告的ID
     * @param  string vouchersNum:代金券编码
     * @return json 数据的JSON字符串
     */
    public function scanCode(){
        $return['success'] = true;
        
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        
        $type = I('post.type');
        $dataId = I('post.dataId');
        $vouchersNum = I('post.vouchersNum');
        
        if (is_empty($version) || is_empty($userId) || is_empty($phone) ||  is_empty($type)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else{
            $userId = $this->userId;
            $dataId = decodePass($dataId);
            
            //type：1：扫会员，2：扫广告，3：扫代金券，4：自动登录【必填项】
            if($type==1){
                $data['type']='1';
                if($dataId){
                    $data['dataId']=$dataId;
                }
            }elseif($type==2){
                $data['type']='2';
                if($dataId){
                    $data['dataId']=$dataId;
                }
            }elseif($type==3){
                $data['type']='3';
                if($vouchersNum){
                    $data['vouchersNum'] = $vouchersNum;
                    $sql = " select id from lu_vouchers_log where vouchers_num  = $vouchersNum ";
                    $resVouchers = M()->query($sql);
                    if($resVouchers && $resVouchers[0]['id']){
                        $data['dataId']=$resVouchers[0]['id'];
                    }
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '操作失败';
                echo jsonStr($return);
                exit();
            }
            
            $data['userId']=$userId;
            $data['addTime']=time();
            
            $res = M('scan_vouchers_log')->data($data)->add();
            //if($res){
                $return['status'] = 1;
                $return['message'] = '添加成功';
            //}else{
                //$return['status'] = 10;
                //$return['message'] = '操作失败';
            //}
        }
        echo jsonStr($return);exit();
    }
	
    /*
     * 查找代金券
     */
    public function checkVouchers(){
        $return['success'] = true;
        
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $vouchersId = I('post.vouchersId');
        
        if(is_empty($version) || is_empty($userId) || is_empty($phone) ||  is_empty($vouchersId)){//验证参数
            $return['status'] = -888;
            $return['message'] = '请输入代金券码';
        }else{
            //验证获取记录
            $where='vouchers_num="'.$vouchersId.'"';
            $resGetVouchers = M('vouchers_log')->field('*')->where($where)->find();
            if($resGetVouchers){//判断是否获取

                //先验证代金券是否有效
                $where="a.id={$resGetVouchers['preferential_id']} and a.status = '1'";

                $sql = "select a.*,b.user_id from lu_advertising_preferential as a left join lu_advertising_base as b on a.adv_id = b.id where $where limit 1";
                $resVouchers = M()->query($sql);

                $userId = $this->userId;
                //判断代金券是否存在
                if($resVouchers){
                    $resVouchers = $resVouchers[0];
                    //判断广告发布人与扫码人是否为同一人
                    if($resVouchers['user_id'] == $userId)
                    {
                        if($resGetVouchers['isUse']==1){
                            $return['status'] = 10;
                            $return['message'] = '该代金券已使用';
                            echo jsonStr($return);exit();
                        }else{
                            //if($resVouchers['endTime']>=time() && ($resPoster['integral']-$resPoster['extendTotalIntegral']>0) && $resPoster['status']==1){
                            $time = time() + 8 * 3600;
                            if(
                                    $resVouchers['start_date'] + $resVouchers['start_time'] >= time() 
                                    || $resVouchers['end_date'] + $resVouchers['end_time'] <= time() 
                                    || ($time % 86400) < $resVouchers['start_time']
                                    || ($time % 86400) > $resVouchers['end_time']
                                )
                            {//广告未开始或已结束
                                $return['status'] = 10;
                                $return['message'] = '该代金券无法识别';
                            }else if($resVouchers['end_date'] + $resVouchers['end_time'] >= time()){

                                $resInfo['id'] =  $resVouchers['id'];
                                $resInfo['reminder'] = '验证了一张代金券';
                                if($resVouchers['type'] == 1)
                                {
                                    $resInfo['give'] = $resVouchers['number'] . '折';
                                }else
                                {
                                    $resInfo['give'] = $this->fenZhuanYuan($resVouchers['number']);
                                }
                                
                                $resInfo['number'] = $resGetVouchers['vouchers_num'];
                                $resInfo['content'] = $resVouchers['conditions'];
                                $resInfo['shopName'] = $resVouchers['title'];
                                $resInfo['startTime'] = $resVouchers['start_date'];
                                $resInfo['endTime'] = $resVouchers['end_date'];
                                $resInfo['dataId'] = $resVouchers['adv_id'];   

                                $return['info'] = $resInfo;

                                $return['status'] = 1;
                                $return['message'] = '查找成功';

                            }else{
                                $return['status'] = 10;
                                $return['message'] = '该代金券已失效';
                            }
                        }
                    }else
                    {
                        $return['status'] = 10;
                        $return['message'] = '对不起！无法识别该代金券';
                        echo jsonStr($return);
                        exit();
                    }
						
                }else{
                    $return['status'] = 10;
                    $return['message'] = '对不起！无法识别该代金券';
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '对不起！无法识别该代金券';
                echo jsonStr($return);
                exit();
            }
        }
        echo jsonStr($return);exit();
    }
    
    
    /*
     * 我的扫码记录列表
     * @param  string $version:版本号
     * @param  string UserId: 会员唯一码
     * @param  string phone：注册会员手机号码
     * @param  string type:1加载；0刷新
     * @param  string selectTime：时间
     * @param  string pageSize：每页显示数量
     * @param  string page：页码
     */
    public function myScanCodeList(){
        $return['success'] = true;
        
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $type = I('post.type');
        $selectTime = I('post.selectTime');
        $pageSize = I('post.pageSize', 20);
        $page = I('post.page', 1);
        if(is_empty($version) || is_empty($userId) || is_empty($phone) ||  is_empty($pageSize) || is_empty($page)){//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else{
            $userId = $this->userId;
            //echo $userId;die;
            $model = D('Poster');
            $res = $model->getMyScanCodeList($userId,$selectTime,$type,$page,$pageSize);
            $return['status'] = 1;
            if($res){
                foreach($res as $key=>$value){
                    if($value['type']==1){//个人
                        $model = D('Members');
                        $resUser = $model->getUserInfo($value['dataId']);
                        if($resUser){
                            $res[$key]['title'] = $resUser['name'];
                        }else{
                            $res[$key]['title'] ='朋友已消失';
                        }
                        $res[$key]['returnStatus'] =1;
                    }elseif($value['type']==2){//广告
                        $field='title';
                        $resPoster = M('advertising_base')->field($field)->where('id ='.$value['dataId'])->find();
                        if($resPoster){
                            $res[$key]['title'] = $resPoster['title'];
                        }else{
                            $res[$key]['title'] ='该广告已下架';
                        }
                        $res[$key]['returnStatus'] =2;
                    }elseif($value['type']==3){//商家扫码代金券
                        $field='preferential_id as vouchersId';
                        $where='vouchers_num="'.$value['vouchersNum'].'"';
                        $resGetVouchers = M('vouchers_log')->field($field)->where($where)->find();
                        if($resGetVouchers){
                            $field='id as vouchersId,number as give,title,type';
                            $resVouchers = M('advertising_preferential')->field($field)->where('id ='.$resGetVouchers['vouchersId'])->find();
                            if($resVouchers){                                
                                //编号备注：个人代金券编号
                                if($resVouchers['type'] == 1)
                                {
                                    $res[$key]['title'] = '（'.$value['vouchersNum'].'） '.$resVouchers['give'].'折 '.$resVouchers['title'];
                                }else{
                                    $res[$key]['title'] = '（'.$value['vouchersNum'].'） ￥'.$this->fenZhuanYuan($resVouchers['give']).' '.$resVouchers['title'];
                                }
                                
                                $res[$key]['number'] = $value['vouchersNum'];
                                $res[$key]['vouchersId'] = $resVouchers['vouchersId'];
                                if($resVouchers['type'] == 2)
                                {
                                    $res[$key]['give'] = $this->fenZhuanYuan($resVouchers['give']);
                                }else{
                                    $res[$key]['give'] = $resVouchers['give'];
                                }
                                $res[$key]['content'] = $resVouchers['title'];
                            }else{
                                $res[$key]['title'] ='该代金券不存在';
                            }
                            //$res[$key]['returnStatus'] =3;
                        }else{
                            $res[$key]['title'] ='该代金券不存在';
                        }
                        $res[$key]['returnStatus'] =3;
                    }else{
                        $res[$key]['title'] = '登录PC端广告平台管理';
                        $res[$key]['returnStatus'] =4;
                    }
                    $res[$key]['id'] = encodePass($value['id']);
                    $res[$key]['userId'] =encodePass($value['userId']);
                }
                $return['info'] = $res;
                        
                $return['message'] = '查询成功';
            }else{
                $return['message'] = '查询成功，暂无数据';
            }
        }
        echo jsonStr($return);exit();
    }

    /*
     * 清空我的扫码记录
     */
    public function delMySanCode(){
        $return['success'] = true;
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        
        if(is_empty($version) || is_empty($userId) || is_empty($phone)){//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else{
            $userId = $this->userId;
            
            $model = D('Poster');
            $res = $model->delMySanCode($userId);
            
            if($res){
                $return['status'] = 1;
                $return['message'] = '清空成功';
            }else{
                $return['status'] = 1;
                $return['message'] = '操作失败';
            }
            
        }  
        echo jsonStr($return);exit();
    }
    /*
     * 商家确认代金券
     */
    public function passVouchers(){
        $return['success'] = true;
        
        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $vouchersId = I('post.dataId');
        
        if(is_empty($version) || is_empty($userId) || is_empty($phone) ||  is_empty($vouchersId)){//验证参数
            $return['status'] = -888;
            $return['message'] = '请输入代金券码';
        }else{
            //验证获取记录
            $where='vouchers_num="'.$vouchersId.'"';
            $resGetVouchers = M('vouchers_log')->field('*')->where($where)->find();
            if($resGetVouchers){//判断是否获取

                //先验证代金券是否有效
                $where="a.id={$resGetVouchers['preferential_id']} and a.status = '1'";
                //$resVouchers=M('advertising_preferential')->field('*')->where($where)->find();
                //echo M('poster_advert_vouchers')->getLastSql();die;

                $sql = "select a.*,b.user_id from lu_advertising_preferential as a left join lu_advertising_base as b on a.adv_id = b.id where $where limit 1";
                $resVouchers = M()->query($sql);
                $resVouchers = $resVouchers[0];

                $userId = $this->userId;
                //判断代金券是否存在
                if($resVouchers){
                    //判断广告发布人与扫码人是否为同一人
                    if($resVouchers['user_id'] == $userId)
                    {
                        if($resGetVouchers['isUse']==1){
                            $return['status'] = 10;
                            $return['message'] = '该代金券已使用';
                            echo jsonStr($return);exit();
                        }else{
                            //if($resVouchers['endTime']>=time() && ($resPoster['integral']-$resPoster['extendTotalIntegral']>0) && $resPoster['status']==1){
                            if($resVouchers['end_date'] + $resVouchers['end_time']>=time()){
                                $time = time();
                                $sql = "update lu_vouchers_log set use_time = $time, isUse = '1' where id = {$resGetVouchers['id']}";	
                                M()->execute($sql);
                                
                                $return['status'] = 1;
                                $return['message'] = '恭喜你成功验证一张代金券';
//                                $return['message'] = '恭喜你获得一张代金券';

                            }else{
                                $return['status'] = 10;
                                $return['message'] = '该代金券已失效';
                            }
                        }
                    }else
                    {
                        $return['status'] = 10;
                        $return['message'] = '对不起！无法识别该代金券';
                        echo jsonStr($return);
                        exit();
                    }
						
                }else{
                    $return['status'] = 10;
                    $return['message'] = '操作失败';
                }
            }else{
                $return['status'] = 10;
                $return['message'] = '对不起！无法识别该代金券';
                echo jsonStr($return);
                exit();
            }
        }
        echo jsonStr($return);exit();
    }
    
    /**
     * 关注商家
     */
    public function focusShop()
    {
        $dataId = I('post.shopId', '', 'trim');
        $userId = $this->userId;
        
        header('Content-Type:application/json; charset=UTF-8');
        $dataId = decodePass($dataId);
        if (is_empty($dataId) || is_empty($userId)) {
            $return['status'] = 10;
            $return['message'] = '传参不完整';
            $return['success'] = true;
            echo jsonStr($ret);
        } else {

            $map = array(
                'userId' => $userId,
                'focusId' => $dataId
            );

            $exist = D('focus_merchants')->where($map)->find();
            $return['status'] = 1;
            if (empty($exist)) {
                $map['addTime'] = time();
                $rs = D('focus_merchants')->add($map);
                
                
                if($rs)
                {
                    $return['flag'] = 1;
                    $return['message'] = '关注成功';
                }else
                {
                    $return['flag'] = 2;
                    $return['message'] = '关注失败';
                }
                $return['success'] = true;
                echo jsonStr($return);exit();
            } else {
                $return['flag'] = 3;
                $return['success'] = true;
                $return['message'] = '已关注过商家';
                echo jsonStr($return);exit();
            }
        }
    }
    
    
    /*
     * 广告获取
     */
    public function advGetLog()
    {
        $page = I('post.page', 0);
        $pageSize = 10;
        $userId = $this->userId;
        //数据查询
        $field = " a.money, a.add_time as addTime, b.title ";
        $order = " order by a.add_time desc ";
        $limit = " limit " . $page * $pageSize . ", $pageSize ";
        $sql = "select $field from lu_red_bag_log as a left join lu_advertising_base as b on a.adv_id = b.id where a.user_id = {$userId} $order $limit";
//            echo $sql;exit;
        $rs = M()->query($sql);
        if (empty($rs)) {
            $return['info'] = array();
            $return['status'] = 36;
            $return['message'] = '查询成功，暂无数据';
        } else {
            foreach($rs as $k => $v)
            {
                $rs[$k]['money'] = $this->fenZhuanYuan($v['money']);
            }
            $return['info'] = $rs;
            $return['status'] = 1;
            $return['selectTime'] = $selectTime;
            $return['message'] = '查询成功';
        }
        echo jsonStr($return);exit;
    }
    
    /*
     * app获取
     */
    public function appGetLog()
    {
        $page = I('post.page', 0);
        $pageSize = 10;
        $userId = $this->userId;
        //数据查询
        $field = " a.money, a.addTime, b.title ";
        $order = " order by a.addTime desc ";
        $limit = " limit " . $page * $pageSize . ", $pageSize ";
        $sql = "select $field from lu_app_user_get_money_log as a left join lu_app_package as b on a.appId = b.id where a.userId = {$userId} $order $limit";
//            echo $sql;exit;
        $rs = M()->query($sql);
        if (empty($rs)) {
            $return['info'] = array();
            $return['status'] = 36;
            $return['message'] = '查询成功，暂无数据';
        } else {
            foreach($rs as $k => $v)
            {
//                $rs[$k]['money'] = $this->fenZhuanYuan($v['money']);
                $rs[$k]['money'] = '+' . $v['money'];
            }
            $return['info'] = $rs;
            $return['status'] = 1;
            $return['selectTime'] = $selectTime;
            $return['message'] = '查询成功';
        }
        echo jsonStr($return);exit;
    }
}
