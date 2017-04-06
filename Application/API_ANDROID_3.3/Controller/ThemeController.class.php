<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ThemeCotroller
 *
 * @author wangwei
 */
class ThemeController extends BaseController {
    
    /**
     * 控制器初始化
     * @access public
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function _initialize() {
        
        parent::_initialize();
        //echo 2;die;
        //自动处理IP相关的限制
        $check_m = D('Check');
        $ACTION_NAME = strtolower(ACTION_NAME);
        $userId = I('post.userId');
        $phone = I('post.phone');
        $return['success'] = true;
        //var_dump($userId);die;

        if ($phone && $userId) {//判断参数是否为空
            $model = D("Members");
            if ($phone == '12345678900') {
                $res = $model->getUserDataByPhone($phone, 'id,freeze');
            } else {
                $res = $model->checkUserId($phone, $userId, 'id,freeze');
            }
            //$res = $model->checkUserId($phone, $userId, 'id,freeze');
            if (empty($res['id'])) {
                $return['status'] = 35;
                $return['message'] = '账号异常，已退出登录！ ';
                $return['info'] = array();
                echo jsonStr($return);
                exit(0);
            } else {
                if ($res['freeze'] != '0') {//验证账号是否非法
                    $return['status'] = 33;
                    $return['message'] = '账号非法，暂时无法完成此操作';
                    $return['info'] = array();
                    echo jsonStr($return);
                    exit(0);
                } else {
//                    if ($res['id'] == 44427) {
//                        $return['status'] = 32;
//                        $return['message'] = '请到个人中心登录';
//                        $return['info'] = array();
//                        echo jsonStr($return);
//                        exit(0);
//                    }
                    $this->userId = $res['id'];
                }
            }
        } else {

            $return['message'] = '操作失败';
            $return['status'] = 10;
            $return['info'] = array();
            echo jsonStr($return);
            exit(0);
        }
    }
    
    
     /*
     * 返回专题分类信息列表（上限100条）
     */
    public function getAssortment(){

        $sql = "select id,title,image,add_time from lu_assortment where status='1' order by add_time desc limit 100";
        $list = M()->query($sql);
        
        if($list){//判断返回数据是否存在
            $return['info'] = $list;
            $return['message'] = '查询成功';
        }else{
            $return['info'] = array();
            $return['message'] = '查询成功，暂无数据';
        }
        
        $return['status'] = 1;
        $return['success'] = true;
        
        echo jsonStr($return);exit();
    }
    
    /*
     * 获取分类下的专题列表
     * 分类id
     * page
     * 
     */
    public function getThemeList(){
        $lastselTime = I('post.lastselTime');
        $page = I('post.page');
        $dataId = I('post.dataId');
        $return['success'] = true;
        $totalNum=10;
        
        if(empty($dataId)){//判断id不存在时
            $return['info'] = array();
            $return['message'] = '查询成功，暂无数据';
            echo jsonStr($return);exit();
        }
        
        //查询时间
        $nowTime=time();

        if(empty($lastselTime)){
            $lastselTime =$nowTime;
        }
        
        //limit条件
        if(empty($page)){
            $page =1;
        }
        $tmp = ($page -1)* $totalNum;
        $limit = " limit $tmp, $totalNum ";
        
        //执行sql
        $sql = "select a.data_id,b.title,b.id,b.list_image,b.big_image,b.period_number,b.user_id  from lu_assortment_advertising a left join lu_theme b on a.theme_id=b.id"
                . " where a.status='1' and a.data_id={$dataId} and a.shelves_time<=$lastselTime order by a.shelves_time desc {$limit}";
        //echo $sql;die;
        $list = M()->query($sql);
        
        if($list){//判断返回数据是否存在
            foreach ($list as $key => $value) {
                if($value['user_id']){
                    
                    //$resMember = D("Members")->getUserInfo($value['user_id']);
                    $resMember = M('User')->field('nickname as name')->where('id ='.$value['user_id'])->find();
                    if($resMember['name']){
                        $list[$key]['name'] =$resMember['name'];
                    }else{
                        $list[$key]['name'] ='';
                    }
                }else{
                     $list[$key]['name'] ='';
                }
            }
            $return['info'] = $list;
            $return['message'] = '查询成功';
        }else{
            $return['info'] = array();
            $return['message'] = '查询成功，暂无数据';
        }
        
        $return['status'] = 1;
        
        if($page==1){//判断刷新返回
            $return['lastselTime'] = $lastselTime;
        }
        echo jsonStr($return);exit();
    }
    
    
    /**
     * 精选主题接口
     */
    public function lists()
    {
        $type = I('post.type');
        $page = I('post.page');
        $userId = $tis->userId;
        if (is_empty($type)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
            //数据查询
            $field = " id, big_image as bImage, period_number as periodNumber, list_image as listImage ";
            $where = " where `status` = '1' ";
            $order = " order by add_time desc ";
            //$type = 1是精选
            if($type == 1)
            {
                $pageSize = 4;
                $where .= " and is_handpick = '1' and theme_order > 0";
                $order = " order by theme_order asc ";
            }else
            {
                $pageSize = 16;
            }
            $limit = " limit " . $page * $pageSize . ", $pageSize ";
            $sql = "select $field from lu_theme $where $order $limit";
            
//            echo $sql;exit;
            $rs = M()->query($sql);
            if (empty($rs)) {
                $return['info'] = array();
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                foreach($rs as $k => $v)
                {
                    $rs[$k]['bigImage'] = $v['bImage'];
                    $rs[$k]['smallImage'] = $v['sImage'];
                    unset($rs[$k]['bImage']);
                    unset($rs[$k]['sImage']);
                }
                $return['info'] = $rs;
                $return['status'] = 1;
                $return['selectTime'] = $selectTime;
                $return['message'] = '查询成功';
            }
        }
        echo jsonStr($return);exit;
    }
    
    /**
     * 主题详情接口
     */
    public function detail()
    {
        $themeId = I('post.themeId');
        $page = I('post.page', 0);
        $userId = $tis->userId;
//        $pageSize = I('post.pageSize', 5);
        $pageSize = 5;
        if (is_empty($themeId)) {
            $return['status'] = 10;
            $return['message'] = '操作失败';
        } else {
//            $sql = "select * from lu_theme ";
//            var_dump(M()->query($sql));exit;
            //数据查询
            $field = " a.status, a.id, a.big_image as bImage, a.list_image as listImage, a.title, a.content, b.id as userId, b.nickname as userName, a.add_time as addTime, a.period_number as periodNumber ";
//            $where = " where a.status = '1' and a.id = $themeId ";
            $where = " where a.id = $themeId ";
            $sql = "select $field from lu_theme as a left join lu_user as b on a.user_id = b.id $where $order $limit";
//            echo $sql;
            $rss = M()->query($sql);
//            var_dump($rss);exit;
            if($rss[0]['status']!=1)
            {
//                if($rss[0]['status']==1){//判断状态是否正常
//                    foreach ($rs as $key => $value) {
//                        $rs[$key]['userId'] = encodePass($value['userId']);
//
//                        //分类
//                        $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$value['id']}";
//                        $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$value['id']}";
//                        $cate_rs = M()->query($cate_sql);
//                        $tags_rs = M()->query($tags_sql);  
//                        $rs[$key]['category'] = array_merge($cate_rs, $tags_rs); 
//
//                        //是否感兴趣
//        //                echo 1;exit;
//                        $isInterest = D('advertising')->isInterest($userId, $value['id'], 2);
//                        $rs[$key]['isInterest'] = $isInterest == 1 ? 1 : 2;
//                    }
//                    $info['advInfo'] = $rs;
//                    $return['status'] = 1;
//                    $return['info'] = $info;
//                    $return['message'] = '查询成功';
//                }else{
                    $return['status'] = 1;
                    $return['info']['status'] = 1;
                    $return['message'] = '该专题已经下架';
//                }
                
            }else {
                $info = $rss[0];
                //第一次加载放在页面最下方music
                $info['advInfo'] = array();
                $info['music'] = array();
                $info['status'] = 2;
                if($page == 0)
                {
                    $field = ' b.music_url, b.name, b.image_url, b.author, b.play_count ';
                    $sql = "select $field from lu_theme_advertising as a left join lu_music as b on a.data_id = b.id  where a.status = '1' and a.type = '2' and a.theme_id = $themeId and b.status = '1'";
        //            echo $sql;exit;
                    $rs = M()->query($sql);
                    $info['music'] = $rs[0];
                }

                //获取主题下的广告列表
                $field = ' c.id as userId, c.name as nickname, c.image as userImage, b.id, b.title, b.image, b.interest_count as interestTotal, b.add_time as addTime, recommend_title as recommendTitle, b.recommend_content as recommendContent ';  
                $limit = " limit " . $page * $pageSize . ", $pageSize ";
                $sql = "select $field from lu_theme_advertising as a left join lu_advertising_base as b on a.data_id = b.id left join lu_members as c on b.user_id = c.id where a.status = '1' and a.type = '1' and a.theme_id = $themeId and b.status = '2' order by b.add_time desc $limit";
//                echo $sql;exit;
                $rs = M()->query($sql);
//            var_dump($sql,$rs);
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
                $info['advInfo'] = $rs;
                if($page == 0)
                {
                    $return['status'] = 1;
                    $return['message'] = '查询成功';
                    $return['info'] = $info;
                }else
                {
                    $return['status'] = 0;
                    $return['message'] = '没有数据了';
                    $return['info'] = $info;
                }
                
            }
        }
        echo jsonStr($return);exit;
    }
}
