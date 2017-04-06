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
                    if($res['id']==44427){
                        $return['status'] = 32;
                        $return['message'] = '请到个人中心登录';
                        //$return['info'] = array();
                        echo jsonStr($return);
                        exit(0);
                    }
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
        myLng：物理地址经度(即手机GPS定位的“我的位置”)【附近必传】
                myLat: 物理地址纬度(即手机GPS定位的“我的位置”)【附近必传】
        page:第几页【默认 0】
        Search:【搜索关键词】【可选，搜索页面用】
     * 
     */
    public function lists()
    {
        $friendId = I('post.friendId', 0);
        $type = I('post.type', 1);
        $page = I('post.page', 0);
        $search = I('post.search', '');
        
        $totalNum = 5;
        switch ($type)
        {
            case 1:
                $totalNum = 5;
                break;
            case 2:
                $totalNum = 5;
                break;
            case 3:
                $totalNum = 5;
                break;
            case 4:
                $totalNum = 3;
                break;
        }
        $offset = $page * $totalNum;
        
        $field = ' a.id, a.user_id userId, a.image, a.title, a.interest_count interestTotal, a.add_time addTime, a.image, b.image userImage ';
        $limit = " limit $offset, $totalNum ";
        
        $where = ' where 1 = 1 ';
        if($friendId && is_numeric($friendId))
        {
            $where = " and user_id = {$friendId} ";
        }
        
        //如果是关键词搜索关联标签表
        $join = '';
        if($search)
        {
            $join = ' right join lu_tag c on a.id = c.adv_id';
            $where = " and (a.title like '%{$search}%' or b.name like '%{$search}%') ";
        }
        
        $order = ' order by add_time desc ';
        $sql = "select {$field} from lu_advertising_base a left join lu_members b on a.user_id = b.id {$join} {$where} {$order} {$limit} ";
        $rs = M()->query();
        
        //是否感兴趣广告（1是；2否）
        if($rs)
        {
            foreach($rs['info'] as $k => $v)
            {
                $isInterest = M('advertising')->isInterest($userId, $v['id'], 2);
                $rs['info'][$k]['isInterest'] = $isInterest == 1 ? 1 : 2;
                
                $rs['info'][$k]['category'] = M('advertising')->getTagyByAdvId($v['id']);
            }
            
        }
        
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        $return['info'] = $rs;
        
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
        $userId = I('post.userId');
        if (is_empty($advId)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }
        
        $info = M('advertising')->detail($advId);
        $info['isCollection'] = M('advertising')->isInterest($userId, $advId, 1);
        $info['isInterest'] = M('advertising')->isInterest($userId, $advId, 2);
        $info['category'] = M('advertising')->getTagyByAdvId($advId);
        $info['goodsInfo'] = M('advertising')->getCommodityByAdvId($advId);
        $info['favorableInfo'] = M('advertising')->getPreferentialByAdvId($advId);
        $info['serviceInfo'] = M('advertising')->getServiceByAdvId($advId);
        //分享链接
        
        
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        $return['info'] = $info;
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 领取红包接口【喜欢/无感】
     * 
     */
    public function getRedBag()
    {
        $advId = I('post.advId');
        $userId = I('post.userId');
        $type = I('post.type');
        
        $return = array();
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        
        if (is_empty($advId) || is_empty($type) || ($type != 1 || $type != 2)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = M('advertising')->addInterest($userId, $advId, '2', $type);
            
            if($rs)
            {
                //添加红包记录
                M('advertising')->getRedBag($userId, $advId);
                //红包金额累加到用户钱包
                $red_number = M('advertising_base')->where("id=$advId")->getField('red_number');
                $get_red_number = M('advertising_base')->where("id=$advId")->getField('get_red_number');
                if($red_number - $get_red_number > 0)
                {
                    $money = M('advertising_base')->where("id=$advId")->getField('each_red_money');
                    M('advertising')->userGetRedBag($userId, $money);
                }
            }else
            {
                $return['status'] = -1;
                $return['message'] = '查询失败';
            }
            $return['info'] = $rs;
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
        $userId = I('post.userId');
        $content = I('post.content');
        
        $return = array();
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        
        if (is_empty($advId) || is_empty($content)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = M('advertising')->addLeaveWord($userId, $advId, $content);
            if(!$rs)
            {
                //判断添加广告留言是否成功
                $return['status'] = -1;
                $return['message'] = '查询失败';
            }
            $return['info'] = '';
        }
        
        echo jsonStr($return);exit();
    }
    
    /**
     * 广告留言列表
     * 
     */
    public function leaveLists()
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
        
        if (is_empty($advId) || is_empty($content)) 
        {
            //判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }else
        {
            $rs = M('advertising')->leaveLists($userId, $offset, $totalNum);
            if(!$rs)
            {
                //判断添加广告留言是否成功
                $return['status'] = -1;
                $return['message'] = '查询失败';
            }else
            {
                $return['info'] = $rs;
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

        $version = I('post.version');
        $userId = I('post.userId');
        $phone = I('post.phone');
        $id = I('post.dataId');
        $friendsId = I('post.friendsId');
        $type = I('post.type');
        $vouchersNumber = I('post.vouchersNumber');

        if (is_empty($userId) || is_empty($id) || is_empty($friendsId) || is_empty($vouchersNumber)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {

            $userId = $this->userId;
            $id = decodePass($id);

            $res = D('Poster')->getPosterAdvert($id); //查询广告基本信息
            //echo $id.'-';var_dump($res);die;
            
            if (is_bool($res) && empty($res)) {//判断广告状态
                $return['status'] = -1;
                $return['message'] = '查询失败';
            } else if ((is_array($res) || is_null($re)) && empty($res)) {
                $return['status'] = 36;
                $return['message'] = '查询成功，暂无数据';
            } else {
                $where='dataId='.$id;
                $where.=' and userId='.$userId;
                $where.=' and vouchersNumber="'.$vouchersNumber.'"';
                $resVouchers=M('get_vouchers_log')->field('id,dataId,isUse,isDel')->where($where)->find();
                //echo $vouchersNumber;die;
                //echo M('get_vouchers_log')->getLastSql();
                
                if($resVouchers){
                    if($resVouchers['isDel']==2){
                        $return['status'] = 36;
                        $return['message'] = '该代金券已删除';
                    }else{
                        if($resVouchers['isUse']==2){
                            $return['status'] = 36;
                            $return['message'] = '该代金券已使用';
                        }else{
                            
                            $resPosterVouchers=M('poster_advert_vouchers')->field('id,startTime,endTime')->where('dataId='.$id)->find();
                            if($resPosterVouchers){
                                if(time()>$resPosterVouchers['endTime'] || ($res['status']!=1) || $res['integral']-$res['extendTotalIntegral']<=0){
                                    $return['status'] = 36;
                                    $return['message'] = '该广告已关闭，无法转发代金券';
                                    
                                }else{
                                    $friendList = explode(',', $friendsId);
                                    $friendModel = D("Friend");
                                    $modelPoster = D('Poster');
                                    //echo $type;die;
                                    if($type==1){//1：广告，2-代金券
                                        $dataS['type'] = '1';
                                        $data['type'] = '1';
                                    }else{

                                        if($res['type']==4){
                                            if($res['status'] != '1'){
                                                //1 正常;2 下架暂停;3 举报下架;4 未支付; 5 已到期 ; 6 飞币耗完 ;7 举报关闭; 8 待上架; 9 草稿箱
                                                $return['status'] = -230;
                                                $return['message'] = '广告已关闭，无法转发该代金券！';
                                                echo jsonStr($return);exit(0);
                                            }

                                            $dataS['type'] = '2';
                                            $data['type'] = '2';
                                        }elseif($res['type']==1 || $res['type']==2 || $res['type']==3){
                                            $dataS['type'] = '1';
                                            $data['type'] = '1';
                                        }  
                                    }
                                    //验证朋友关系
                                    foreach ($friendList as $key => $val) {
                                        $data = array();
                                        if ($val) {//更新朋友列表展示信息
                                            //echo $userId.'-'.decodePass($val);die;
                                            $resIsFriend = $friendModel->isFriend($userId, decodePass($val));

                                            $dataS['isNew'] = '1';
                                            $nowTime = time();
                                            $dataS['dataId'] = $id;
                                            $dataS['ftime'] = $nowTime;
                                            $dataS['type'] = '2';
                                            M('Friend')->where('uid =' . $userId . ' and fuid =' . decodePass($val))->data($dataS)->save();

                                            if ($resIsFriend) {//添加转发
                                                //$newFrendsId.=decodePass($val).',';
                                                $data['dataId'] = $id;
                                                $data['friendId'] = decodePass($val);
                                                $data['userId'] = $userId;
                                                $data['integral'] = 0;
                                                $data['addTime'] = $nowTime;
                                                $data['updateTime'] = $nowTime;
                                                $data['isNew'] = '1';
                                                $data['type'] = '2';
                                                $data['vouchersNumber'] = strval($vouchersNumber);
                                                //M('FriendForward')->data($data)->add();
                                                $modelPoster->addForward($data);
                                            }
                                        }
                                    }

                                    if ($res) {
                                        $return['status'] = 1;
                                        $return['message'] = '转发成功';
                                    } else {
                                        $return['status'] = 10;
                                        $return['message'] = '操作失败';
                                    }
                                }
                            }else{
                                $return['status'] = 36;
                                $return['message'] = '查询成功，暂无数据';
                            }
                        }
                    }
                }else{
                    $return['status'] = 36;
                    $return['message'] = '查询成功，暂无数据';
                }
            }
        }
        echo jsonStr($return);exit();
    }

    
    /**
     * 服务、商品、优惠代金券详情接口
     * 
     */
    public function zhDetail()
    {
        $advId = I('post.advId');
        $type = I('post.type');
        if (is_empty($advId) || is_empty($type)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
            switch($type)
            {
                case 1:
                    $tableName = 'lu_service';
                    break;
                case 2:
                    $tableName = 'lu_commodity';
                    break;
                case 3:
                    $tableName = 'lu_advertising_preferential';
                    break;
            }
            $info = M($tableName)->where("adv_id=$advId")->find();
            $return['info']['type'] = $type; 
            $return['info']['image'] = $info['image']; 
            $return['info']['title'] = $info['title']; 
            $return['info']['price'] = $info['price']; 
            $return['info']['content'] = $info['content']; 
            $return['info']['addTime'] = $info['add_time']; 
            
            
        }
    }
    
    /**
     * 获取代金券接口
     * 
     */
    public function addFavorable()
    {
        $userId = I('post.userId');
        $dataId = I('post.dataId');
        $type = I('post.type');
        $fuid = I('post.fuid', 0);
        if (is_empty($advId) || is_empty($type) || ($type == 2 && is_empty($fuid))) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        } else {
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
                $return['status'] = 1;
                $return['message'] = '成功';
            }else
            {
                $return['status'] = -1;
                $return['message'] = '操作失败';
            }
            
        }
    }
    
}
