<?php

use Think\Model;

// 发现模型
class FriendModel extends CommonModel {

    /**
     * 我的朋友列表
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getListData($userId, $selectTime, $page, $pageSize) {
        $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
        $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            'f.addTime' => array('elt', $selectTime),
            'uid' => $userId,
            'f.status' => '1'
        );
        $order = ' m.integral desc';
        $result = $this->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        //echo $this->getLastSql();
        return $result;
    }

    /**
     * 搜索我的朋友列表
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getSearchListData($userId, $content, $selectTime, $page, $pageSize) {
        $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
        $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            'f.addTime' => array('elt', $selectTime),
            'uid' => $userId,
            'm.name' => array('like', '%' . $content . '%'),
            'f.status' => '1'
        );
        $where['_string'] = 'm.name !="飞报官方推荐" ';
        $order = ' m.integral desc';
        $result = $this->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        //echo $this->getLastSql();
        return $result;
    }

    /**
     * 获得店铺信息
     * @param sting $friendId
     * @return boolean
     */
    function getShopInfo($friendId) {
        $friendId = decodePass($friendId);
        if (empty($friendId)) {
            return false;
        }
        $where = array('userId' => $friendId);
        $fields = 'shopName AS title ,shopAddress AS address,shopContent AS content,shopPhone AS telphone,webUrl AS netAddress,image1,image2,image3,image4,image5';
        $result = M("members_shop")->where($where)->field($fields)->find();
        //echo M("members_shop")->getLastSql();
//        if ($result) {
//            $result['title'] = str_replace('"', "\"", $result['title']);
//            $result['shopContent'] = str_replace('"', "\"", $result['shopContent']);
//            $result['address'] = str_replace('"', "\"", $result['address']);
//        }
        return $result;
    }

    /**
     * 搜索朋友
     * @param int $userId
     * @param string $content
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function searchData($userId, $content, $page, $pageSize, $selectTime) {
        $where['name'] = array('like', '%' . $content . '%');
        $where['addTime'] = array('elt', $selectTime);
        //$where['id'] = array('neq', $userId);
        $where['freeze'] = '0';
        //$where['name']  = array('neq', '飞报官方推荐');
        $where['_string'] = 'name !="飞报官方推荐" ';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $fields = 'id AS userId,name AS nickName,image,integral,groupType';
        $order = " addTime desc";
        $result = M("members")->where($where)->field($fields)->order($order)->limit($limit)->select();
        //echo M("members")->getLastSql();die;
        
        if ($result) {
            foreach ($result as $key => $value) {
                $atteition = M("friend")->where(array('uid' => $userId, 'fuid' => $value['userId']))->find();
                if ($atteition['status'] == '1') {
                    $status = 1;
                } else if ($atteition['status'] == '2') {
                    $status = 2;
                } else {
                    $status = 2;
                }
                $result[$key]['isAtteition'] = $status; //1是关注 2是未关注
                $result[$key]['userId'] = encodePass($value['userId']);
                $result[$key]['isAuthentication'] = $value['groupType'] == 2 ? 2 : 1; // 1是未认证 2 是认证
                unset($result[$key]['groupType']);
            }
        }
        return $result;
    }

    /**
     * 关注朋友
     * @param int $userId
     * @param string $friendId
     */
    function attention($userId, $friendId, $type = '1') {
        $flag = 1;
        $fuid = decodePass($friendId);
        //$fuid = $friendId;
        //
        //if (empty($fuid) || $userId == $fuid) {
        //return false;
        //}

        $fresult = $this->getUserInfoById($fuid, 'id,freeze');
        if ($fresult) {
            if ($fresult['freeze'] != 0) {
                $flag = 2; //关注的朋友不是正常状态
            } else {
                $result = $this->getUserInfoById($userId, 'id,freeze');
                if ($result['freeze'] != 0) {
                    $flag = 3; //当前用户状态不正常
                } else {
                    $fwhere['uid'] = $userId;
                    $fwhere['fuid'] = $fuid;
                    $findResult = $this->where($fwhere)->find();
                    if ($type == '1') {//关注操作
                        if ($findResult['status'] == '1') {
                            $flag = 4; //已关注过
                        } else if ($findResult['status'] == '2') {
                            $where['uid'] = $userId;
                            $where['fuid'] = $fuid;
                            $data['addTime'] = time();
                            $data['status'] = '1';
                            $addResult = $this->data($data)->where($where)->save();
                            //echo $this->getLastSql();
                            $action = '添加关注';
                            if ($addResult) {
                                $flag = 5; //关注成功
                                $this->addFriendLog($userId, $fuid, $action); //写入日志
                            } else {
                                $flag = 6;
                            }
                        } else {
                            //关注频次计算 计算最近10 小于10秒
                            $aflag = $this->attentionFrequency($userId);
                            if ($aflag == 2) {
                                $flag = 8;
                            } else {
                                $data['uid'] = $userId;
                                $data['fuid'] = $fuid;
                                $data['gid'] = 1;
                                $data['num'] = 1;
                                $data['addTime'] = time();
                                $data['status'] = '1';
                                $data['note'] = "关注";
                                $addResult = $this->data($data)->add();
                                //echo $this->getLastSql();
                                $action = '添加关注';
                                if ($addResult) {
                                    $flag = 5; //关注成功
                                    $this->addFriendLog($userId, $fuid, $action); //写入日志
                                } else {
                                    $flag = 6;
                                }
                            }
                        }
                    } else {//取消关注
                        if ($findResult['status'] == '2') {
                            $flag = 5;
                        } else {
                            if ($findResult) {
                                $data['cancelTime'] = time();
                                $data['status'] = '2';
                                $addResult = $this->data($data)->where(array('uid' => $userId, 'fuid' => $fuid))->save();
                                $action = '取消关注';
                                if ($addResult) {
                                    $flag = 5; //关注成功
                                    $this->addFriendLog($userId, $fuid, $action); //写入日志
                                } else {
                                    $flag = 6;
                                }
                            } else {
                                $flag = 9; //关注记录不存在
                            }
                        }
                    }
                }
            }
        } else {
            $flag = 7; //关注朋友不存在
        }

        return $flag;
    }

    /**
     * 写入朋友日志
     * @param int $userId
     * @param int $fuid
     * @param string $action
     */
    function addFriendLog($userId, $fuid, $action) {
        $dataLog = array(
            'uid' => $userId,
            'fuid' => $fuid,
            'action' => $action,
            'addTime' => time(),
        );
        $logResult = M("friend_log")->data($dataLog)->add();
    }

    /**
     * 通过用户Id得到用户信息
     * @param int $id
     * @param string $field
     * @return array
     */
    function getUserInfoById($id, $fields = '*') {
        $where['id'] = $id;
        $result = M("members")->where($where)->field($fields)->find();
        return $result;
    }

    /**
     * 关注频次计算
     * @param int $uid
     * @return int
     */
    function attentionFrequency($uid) {
        $fwhere['uid'] = $uid;
        $fwhere['addTime'] = array("gt", mktime(0, 0, 0, date("m"), date("d")));
        $data = $this->where($fwhere)->field('addTime')->limit(100)->order("addTime desc")->select();
        $flag = 1;
        //echo $this->getLastSql();
        $count = count($data);
        $second = 10;
        if ($count > C("ATTENTION_NUMBER_BASIC")) {
            //频次计算
            $flag = getAttentionWrong($data, $second);
        }
        return $flag;
    }

    /**
     * 屏蔽朋友
     * @param int $userId
     * @param string $friendId
     */
    function shield($userId, $friendId, $type = '1') {
        $flag = 1;
        $fuid = decodePass($friendId);
        //$fuid = $friendId;
        if (empty($fuid) || $userId == $friendId) {
            return false;
        }
        $fresult = $this->getUserInfoById($fuid, 'id,freeze');
        if ($fresult) {
            if ($fresult['freeze'] != 0) {
                $flag = 2; //屏蔽的朋友不是正常状态
            } else {
                $result = $this->getUserInfoById($userId, 'id,freeze');
                if ($result['freeze'] != 0) {
                    $flag = 3; //当前用户状态不正常
                } else {
                    $fwhere['uid'] = $userId;
                    $fwhere['fuid'] = $fuid;
                    $findResult = M("friend_shield")->where($fwhere)->find();
                    //echo M("friend_shield")->getLastSql();
                    if ($type == '1') {//屏蔽操作
                        if ($findResult['status'] == '1') {
                            $flag = 4; //已屏蔽过
                        } else if ($findResult['status'] == '2') {
                            $where['uid'] = $userId;
                            $where['fuid'] = $fuid;
                            $data['addTime'] = time();
                            $data['status'] = '1';
                            $addResult = M("friend_shield")->data($data)->where($where)->save();
                            //echo $this->getLastSql();
                            $action = '添加关注';
                            if ($addResult) {
                                $flag = 5; //屏蔽成功
                                $this->friendShieldLog($userId, $fuid, $action); //写入日志
                            } else {
                                $flag = 6;
                            }
                        } else {
                            //屏蔽频次计算 计算最近10 小于10秒
                            $aflag = $this->attentionFrequency($userId);
                            if ($aflag == 2) {
                                $flag = 8;
                            } else {
                                $data['uid'] = $userId;
                                $data['fuid'] = $fuid;
                                $data['addTime'] = time();
                                $data['status'] = '1';
                                $addResult = M("friend_shield")->data($data)->add();
                                //echo $this->getLastSql();
                                $action = '添加屏蔽';
                                if ($addResult) {
                                    $flag = 5; //关注成功
                                    $this->friendShieldLog($userId, $flag, $action);
                                } else {
                                    $flag = 6;
                                }
                            }
                        }
                    } else {//取消屏蔽
                        if ($findResult['status'] == '2') {
                            $flag = 5;
                        } else {
                            if ($findResult) {
                                $data['cancelTime'] = time();
                                $data['status'] = '2';
                                $addResult = M("friend_shield")->data($data)->where(array('uid' => $userId, 'fuid' => $fuid))->save();
                                $action = '取消屏蔽';
                                if ($addResult) {
                                    $flag = 5;
                                    $this->friendShieldLog($userId, $flag, $action);
                                } else {
                                    $flag = 6;
                                }
                            } else {
                                $flag = 9; //屏蔽记录不存在
                            }
                        }
                    }
                }
            }
        } else {
            $flag = 7; //屏蔽朋友不存在
        }
        return $flag;
    }

    /**
     * 写入朋友非法日志
     * @param int $userId
     * @param int $fuid
     * @param string $action
     */
    function friendShieldLog($userId, $fuid, $action) {
        $dataLog = array(
            'uid' => $userId,
            'fuid' => $fuid,
            'action' => $action,
            'addTime' => time(),
        );
        $logResult = M("friend_shield_log")->data($dataLog)->add();
    }

    /**
     * 屏蔽频次计算
     * @param int $uid
     * @return int
     */
    function shieldFrequency($uid) {
        $fwhere['uid'] = $uid;
        $fwhere['addTime'] = array("gt", mktime(0, 0, 0, date("m"), date("d")));
        $data = $this->where($fwhere)->field('addTime')->limit(20)->order("addTime desc")->select();
        $flag = 1;
        //echo $this->getLastSql();
        $count = count($data);
        $second = 10;
        if ($count > 3) {
            //频次计算
            $flag = getInviteWrong($data, $second);
        }
        return $flag;
    }

    /**
     *  他的发现列表 
     * @param int $userId 当前人Id
     * @param string $friendId 朋友ID
     * @param int $page 当前页数
     * @param int $pageSize 每次取值数量
     * @param int $selectTime 取数据时间
     * @return array
     */
    public function getFoundData($userId, $friendId, $page = 1, $pageSize = 10, $selectTime = 0) {
        $fuid = decodePass($friendId);
        //$fuid = $friendId;
        $result = array();
        $page = $page < 1 ? 1 : $page;
        $field = 'f.id,f.content,f.userId,f.treadNum AS stampnumber,f.praiseNum AS praisenumuber,f.time,f.commentNum AS commentnumuber,f.sharePath AS sharehtml ,f.lng,f.lat ,m.name AS nickname,m.image AS userImage,f.hotflag,f.uniqueMark';
        $join = " AS f LEFT JOIN __MEMBERS__ AS m ON f.userId = m.id and  f.userId=" . $fuid;
        $fieldPic = 'dataId,image,thumbUrl';
        $selectTime = $selectTime ? $selectTime : time();
        $map = array(
            'f.del' => '1',
            'f.userId' => $fuid,
            'f.time' => array('elt', $selectTime)
        );
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M("found")->field($field)->where($map)->order("f.time DESC")->join($join)->limit($limit)->select();
        //print_r(count($result));
        //echo M("found")->getLastSql();
        if ($result) {
            $result = getRelations($result, $userId, 0, 0);
        }

        return $result;
    }
    
    
    /*
     * 3.2.4获取飞报官方首页
     */
    public function officialForward($userId){
        
        $res = M('OfficialForward')->order('id desc')->limit(1)->select();
        
        if($res[0]){
            $result = array();
            //广告状态
            if($res[0]['type']==1){
                $result[0]['forwardType']=1;
            }else{
                $result[0]['forwardType']=2;
            }
            
            $result[0]['officialId'] = encodePass($res[0]['id']);
            $result[0]['type'] = 1 ;
            $result[0]['isForward']=2;//转发转出状态 1：转出，2：转来
            $result[0]['mold']=1;//类型1-广告，2-代金券
            
            $officialUserId=$res[0]['userId'];
            
            //查询广告信息
            $resPosterAdvert =M('PosterAdvert')->field('imageUrl,type,title,id')->where('id = '.$res[0]['dataId'])->find();
            
            //代金券特殊处理
            if($resPosterAdvert['type']==4 && $res[0]['type']==2){
                $result[0]['mold']=2;
                $resultVouchers = M('poster_advert_vouchers')->where('dataId='.$res[0]['dataId'])->field('number,vouchersPrice as give,content,startTime,endTime,shopName,vouchersIntegral')->find();

                $result[0]['give']=$resultVouchers['give'];
                $result[0]['number']=$resultVouchers['number'];
                $result[0]['vouchersStartTime']=$resultVouchers['startTime'];
                $result[0]['vouchersEndTime']=$resultVouchers['endTime'];
                $result[0]['vouchersIntegral']=$resultVouchers['vouchersIntegral'];
                $result[0]['shopName']=$resultVouchers['shopName'];
                $result[0]['content']=$resultVouchers['content'];

                //判断是否已经获取代金券
                $resultGetVouchers = M('get_vouchers_log')->where('dataId='.$res[0]['dataId'].' and userId='.$userId.' and fuid='.$res[0]['userId'])->field('id,isDraw')->find();
                //$resultGetVouchers = M('get_vouchers_log')->where('dataId='.$value['id'].' and userId='.$userId)->field('id,isDraw,fuid')->find();
                
                $result[0]['contentNews']='您收到了一张代金券';
                
                if($resultGetVouchers){
                    $result[0]['isNew'] = 1;
                    if($resultGetVouchers['isDraw']==1){
                        $result[0]['isNew'] = 2;
                    }
                }
            }
            
            $result[0]['getVouchersId'] = encodePass($res[0]['getVouchersId']);
            $result[0]['addTime'] =$res[0]['addTime'];
            $result[0]['title'] = base64_encode(jsonStrWithOutBadWordsNew($resPosterAdvert['title']));
            $result[0]['imageUrl'] = $resPosterAdvert['imageUrl'];
            $result[0]['id'] = encodePass($res[0]['dataId']);
            
            //查询飞报官方个人信息
            $uwhere['id'] = $officialUserId;
            $ufields = 'image,name,groupType';
            $userData = M("members")->where($uwhere)->field($ufields)->find();
            if ($userData) {
                $result[0]['nickName'] = $userData['name'];
                $result[0]['image'] = $userData['image'];
                $result[0]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
            }
            
            $result[0]['forwardType'] = 1;
            $result[0]['fuid'] = encodePass($officialUserId);
            $result[0]['dataId'] = encodePass($res[0]['dataId']);
            $result[0]['isNew'] = 2;
            $result[0]['userId'] = encodePass($res[0]['userId']);
            
        }
        
        return $result;
    }
    
    
    
    
    
     /**
     * 3.2.3朋友转给我的广告列表 此项更新后-测试无问题后删除
     * @param int $userId
     * @param int $lastTime
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function forwardNew($userId, $lastTime, $page = 1, $pageSize = 20, $selectTime) {

        $where = array(
            //'f.fuid' => $userId,
            'f.ftime' => array('elt', $selectTime)
        );
        $fields = 'f.isNew,f.type as forwardType,p.title,f.dataId,p.id,f.uid as userId,f.fuid,f.ftime as addTime,p.imageUrl,p.type';
        $join = ' AS f RIGHT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $where['_string'] = " f.fuid={$userId} or f.uid={$userId}"; //去除屏蔽的朋友
        $order = 'f.ftime desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('friend')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
        
        //获取官方转发首页
        
        //echo M('friend')->getLastSql();
        if ($result) {
            //$getVouchersId='';
            foreach ($result as $key => $value) {
                $result[$key]['getVouchersId']='';
                
                if($value['userId']==$userId){//转发转出状态 1：转出，2：转来
                    $result[$key]['isForward']=1;
                    $flag=1;
                }else{
                    $result[$key]['isForward']=2;
                    $flag=2;
                }
                
                if($value['userId']==$value['fuid']){
                    $tmpUserId = $value['userId'];
                }else{
                    if($value['userId']==$userId){
                        $tmpUserId = $value['fuid'];
                    }else{
                        $tmpUserId = $value['userId'];
                    }
                }
                
                $result[$key]['mold']=1;
                //echo $value['type'].'-'.$value['forwardType'];die;
                if($value['type']==4 && $value['forwardType']==2){//判断代金券
                    $result[$key]['mold']=2;
                    $resultVouchers = M('poster_advert_vouchers')->where('dataId='.$value['id'])->field('number,vouchersPrice as give,content,startTime,endTime,shopName,vouchersIntegral')->find();
                    
                    $result[$key]['give']=$resultVouchers['give'];
                    $result[$key]['number']=$resultVouchers['number'];
                    $result[$key]['vouchersStartTime']=$resultVouchers['startTime'];
                    $result[$key]['vouchersEndTime']=$resultVouchers['endTime'];
                    $result[$key]['vouchersIntegral']=$resultVouchers['vouchersIntegral'];
                    $result[$key]['shopName']=$resultVouchers['shopName'];
                    $result[$key]['content']=$resultVouchers['content'];
                            
                    //判断是否已经获取代金券
                    $resultGetVouchers = M('get_vouchers_log')->where('dataId='.$value['id'].' and userId='.$userId.' and fuid='.$vaule['userId'])->field('id,isDraw')->find();
                    //$resultGetVouchers = M('get_vouchers_log')->where('dataId='.$value['id'].' and userId='.$userId)->field('id,isDraw,fuid')->find();
                    if($resultGetVouchers){
                        $result[$key]['isNew'] = 1;
                        if($resultGetVouchers['isDraw']==1){
                            $result[$key]['isNew'] = 2;
                        }
                    }else{
                        if($flag==2){
                            $result[$key]['contentNews']='您收到了一张代金券';
                        }
                    }
                }
                
                //var_dump($value['mold']);die;
                $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                $result[$key]['userId'] = encodePass($tmpUserId);
                $result[$key]['id'] = encodePass($value['id']);
                //$uwhere['id'] = $value['userId'];
                $uwhere['id'] = $tmpUserId;
                $ufields = 'image,name,groupType';
                $userData = M("members")->where($uwhere)->field($ufields)->find();
                if ($userData) {
                    $result[$key]['nickName'] = $userData['name'];
                    $result[$key]['image'] = $userData['image'];
                    $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                }
                
                $result[$key]['forwardType'] = 2;
                $result[$key]['fuid'] = encodePass($value['fuid']);
                $result[$key]['dataId'] = encodePass($value['dataId']);
            }
        }
        return $result;
    }

    
    
    

    /**
     * 3.2朋友转给我的广告列表 此项更新后-测试无问题后删除
     * @param int $userId
     * @param int $lastTime
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function forward($userId, $lastTime, $page = 1, $pageSize = 20, $selectTime) {
/*
        $where = array(
            'f.fuid' => $userId,
            'f.ftime' => array('elt', $selectTime)
        );
        $fields = 'f.isNew,p.title,p.id,f.uid as userId,f.ftime as addTime,p.imageUrl';
        $join = ' AS f RIGHT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $where['_string'] = "f.uid not in(SELECT fuid FROM lu_friend_shield WHERE uid={$userId} AND status = '1')"; //去除屏蔽的朋友
        $order = 'f.ftime desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('friend')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
 */
        $where = array(
            //'f.fuid' => $userId,
            'f.ftime' => array('elt', $selectTime)
        );
        $fields = 'f.isNew,p.title,p.id,f.uid as userId,f.fuid,f.ftime as addTime,p.imageUrl';
        $join = ' AS f RIGHT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $where['_string'] = " f.fuid={$userId} or f.uid={$userId}"; //去除屏蔽的朋友
        $order = 'f.ftime desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('friend')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
        //echo M('friend')->getLastSql();
        if ($result) {
            foreach ($result as $key => $value) {
                if($value['userId']==$userId){//转发转出状态 1：转出，2：转来
                    $result[$key]['isForward']=1; 
                }else{
                    $result[$key]['isForward']=2; 
                }
                
                if($value['userId']==$value['fuid']){
                    $tmpUserId = $value['userId'];
                }else{
                    if($value['userId']==$userId){
                        $tmpUserId = $value['fuid'];
                    }else{
                        $tmpUserId = $value['userId'];
                    }
                }
                
                $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                //$result[$key]['userId'] = encodePass($value['userId']);
                $result[$key]['userId'] = encodePass($tmpUserId);
                $result[$key]['id'] = encodePass($value['id']);
                //$uwhere['id'] = $value['userId'];
                $uwhere['id'] = $tmpUserId;
                $ufields = 'image,name,groupType';
                $userData = M("members")->where($uwhere)->field($ufields)->find();
                if ($userData) {
                    $result[$key]['nickName'] = $userData['name'];
                    $result[$key]['image'] = $userData['image'];
                    $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                }
                $result[$key]['forwardType'] = 2;
            }
        }
        return $result;
    }

    
    /**
     * 3.2搜索朋友转给我的广告列表
     * @param int $userId
     * @param string $content
     * @param int $lastTime
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function searchForwardNew($userId, $content, $lastTime, $page = 1, $pageSize = 20, $selectTime) {

        $field = 'm.id';
        $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            //'uid' => $userId,
            'm.name' => array('like', '%' . $content . '%')
        );
        $fresult = $this->field($field)->where($where)->join($join)->select();
        //echo  $this->getLastSql();die;

        if ($fresult) {
            $idStr = '';
            foreach ($fresult as $key => $value) {
                $idStr .= $value['id'] . ',';
            }
            $idStr = substr($idStr, 0, -1);
            $where = array(
                //'f.fuid' => $userId,
                //'f.uid' => array('in', $idStr),
                'f.ftime' => array('elt', $selectTime)
            );
            
            $fields = 'f.isNew,p.title,f.type as forwardType,p.id,f.fuid,f.uid,f.uid as userId,f.ftime as addTime,p.imageUrl,p.type';
            $join = ' AS f RIGHT JOIN lu_poster_advert AS p ON f.dataId=p.id';
            
            //$where['_string'] = "f.fuid not in(SELECT fuid FROM lu_friend_shield WHERE uid={$userId} AND status = '1')"; //去除屏蔽的朋友
            $where['_string'] = "(f.fuid ={$userId} and f.uid in ({$idStr})) or (f.uid ={$userId} and f.fuid in ({$idStr})) ";
            $order = 'f.ftime desc';
            $limit = ($page - 1) * $pageSize . "," . $pageSize;
            $result = M('friend')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
            //echo M('friend')->getLastSql();
            
            if ($result) {
                //$getVouchersId='';
                foreach ($result as $key => $value) {
                    $result[$key]['getVouchersId']='';
                    
                    if($value['userId']==$userId){//转发转出状态 1：转出，2：转来
                        $result[$key]['isForward']=1;
                        $flag=1;
                    }else{
                        $result[$key]['isForward']=2;
                        $flag=2;
                    }

                    if($value['userId']==$value['fuid']){
                        $tmpUserId = $value['userId'];
                    }else{
                        if($value['uid']==$userId){
                            $tmpUserId = $value['fuid'];

                        }else{
                            $tmpUserId = $value['uid'];
                        }
                    }

                    $result[$key]['mold']=1;
                    if($value['type']==4 && $value['forwardType']==2){//判断代金券
                        $result[$key]['mold']=2;
                        $resultVouchers = M('poster_advert_vouchers')->where('dataId='.$value['id'])->field('number,vouchersPrice as give,content,startTime,endTime,shopName,vouchersIntegral')->find();
                        
                        if($resultVouchers){
                            $result[$key]['give']=$resultVouchers['give'];
                            $result[$key]['number']=$resultVouchers['number'];
                            $result[$key]['vouchersStartTime']=$resultVouchers['startTime'];
                            $result[$key]['vouchersEndTime']=$resultVouchers['endTime'];
                            $result[$key]['vouchersIntegral']=$resultVouchers['vouchersIntegral'];
                            $result[$key]['shopName']=$resultVouchers['shopName'];
                            $result[$key]['content']=$resultVouchers['content'];
                            
                            //判断是否已经获取代金券
                            $resultGetVouchers = M('get_vouchers_log')->where('dataId='.$value['id'].' and userId='.$userId.' and fuid='.$vaule['userId'])->field('id,isDraw')->find();
                            //$resultGetVouchers = M('get_vouchers_log')->where('dataId='.$value['id'].' and userId='.$userId)->field('id,isDraw')->find();
                            if($resultGetVouchers){
                                //$getVouchersId.=$resultGetVouchers['id'].','.encodePass($resultGetVouchers['fuid']).';';
                                $result[$key]['isNew'] = 1;
                                if($resultGetVouchers['isDraw']==1){
                                    $result[$key]['isNew'] = 2;
                                }
                            }else{
                                if($flag==2){
                                    $result[$key]['contentNews']='你收到一张代金券';
                                }
                            }
                        }
                    }
                    
                    //$result[$key]['getVouchersId'] =$getVouchersId;
                    $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                    $uwhere['id'] = $tmpUserId;
                    $result[$key]['userId'] = encodePass($tmpUserId);
                    $result[$key]['id'] = encodePass($value['id']);
                    
                    $ufields = 'image,name,groupType';
                    $userData = M("members")->where($uwhere)->field($ufields)->find();
                    //echo M("members")->getLastSql();die;
                    if ($userData) {
                        $result[$key]['nickName'] = $userData['name'];
                        $result[$key]['image'] = $userData['image'];
                        $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                    }
                    $result[$key]['uid'] = encodePass($value['uid']);
                    $result[$key]['fuid'] = encodePass($value['fuid']);
                    $result[$key]['forwardType'] = 2;
                }
            }
        }
        return $result;
    }
    
    
    
    
    
    
    
    /**
     * 3.2搜索朋友转给我的广告列表
     * @param int $userId
     * @param string $content
     * @param int $lastTime
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function searchForward($userId, $content, $lastTime, $page = 1, $pageSize = 20, $selectTime) {

        $field = 'm.id';
        $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            //'uid' => $userId,
            'm.name' => array('like', '%' . $content . '%')
        );
        $fresult = $this->field($field)->where($where)->join($join)->select();
        //echo  $this->getLastSql();die;

        if ($fresult) {
            $idStr = '';
            foreach ($fresult as $key => $value) {
                $idStr .= $value['id'] . ',';
            }
            $idStr = substr($idStr, 0, -1);
            $where = array(
                'f.fuid' => $userId,
                'f.ftime' => array('elt', $selectTime),
                'f.uid' => array('in', $idStr)
            );
            $fields = 'f.isNew,p.title,p.id,f.uid as userId,f.ftime as addTime,p.imageUrl';
            $join = ' AS f RIGHT JOIN lu_poster_advert AS p ON f.dataId=p.id';
            
            //$where['_string'] = "f.fuid not in(SELECT fuid FROM lu_friend_shield WHERE uid={$userId} AND status = '1')"; //去除屏蔽的朋友
            $where['_string'] = "(f.fuid ={$userId} and f.uid in ({$idStr})) or (f.uid ={$userId} and f.fuid in ({$idStr})) ";
            $order = 'f.ftime desc';
            $limit = ($page - 1) * $pageSize . "," . $pageSize;
            $result = M('friend')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
            //echo M('friend')->getLastSql();
            if ($result) {
                foreach ($result as $key => $value) {
                    $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                    $result[$key]['userId'] = encodePass($value['userId']);
                    $result[$key]['id'] = encodePass($value['id']);
                    $uwhere['id'] = $value['userId'];
                    $ufields = 'image,name,groupType';
                    $userData = M("members")->where($uwhere)->field($ufields)->find();
                    if ($userData) {
                        $result[$key]['nickName'] = $userData['name'];
                        $result[$key]['image'] = $userData['image'];
                        $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                    }
                }
                $result[$key]['forwardType'] = 2;
            }
        }

        return $result;
    }

    /**
     * 取指定会员屏蔽的朋友
     * @param int $uid 会员Id
     * @param string $fields 字段
     * @return array
     */
    function getShieldFriendByUid($uid, $fields = '*') {
        $where = array(
            'uid' => $uid,
            'status' => '1'
        );
        $result = M("friend_shield")->where($where)->field($fields)->select();
        return $result;
    }

    /**
     * 验证会员是否是朋友关系
     * @param int $userId 会员Id
     * @param string $friend 朋友id
     * @return true/false
     */
    public function isFriend($userId, $friend) {
        $where = 'uid=' . $userId . ' and fuid =' . $friend . ' and status ="1"';
        $result = M("Friend")->where($where)->field('uid')->find();
        //echo M("Friend")->getLastSql();die;

        if ($result['uid']) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    
    
    /**
     * 3.2.4官方推荐之间的转发广告列表
     * @param int $friendId
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * 
     * @return array
     */
    function personalOfficialForward($friendId, $userId, $page = 1, $pageSize = 20, $selectTime) {
        $friendId = decodePass($friendId);

        $where = array(
            'f.addTime' => array('elt', $selectTime)
        );
        
        $where['_string'] = "f.userId={$friendId}";
        
        
        $join = ' AS f LEFT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $fields = 'p.title,f.vouchersNumber as forwardVouchersNumber,p.id,p.imageUrl,f.type as forwardType,p.type,p.weburl,p.shareUrl,p.collectTotal,p.warnPhone,p.userId,f.userId as uid,f.addTime,f.id as noteId,f.getStatus,f.getVouchersId';
        $order = 'f.id desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('official_forward')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
        //echo M('official_forward')->getLastSql();
        
        if ($result) {

            foreach ($result as $key => $value) {
                
                //查询getvouchersId
                
                $resultOfficialLog = M('official_forward_log')->where('officialForwardId='.$value['noteId'].' and friendId='.$userId)->field('getVouchersId')->find();
                if($resultOfficialLog['getVouchersId']){
                    $TmpGetVouchersId=$resultOfficialLog['getVouchersId'];
                }else{
                    $TmpGetVouchersId = $value['getVouchersId'];
                }
                
                //默认转来关系
                $result[$key]['isForward']=2;
                $flag = 1;
                
                $result[$key]['mold']=1;

                if($value['type']==4 && $value['forwardType']==2){//判断代金券
                    $result[$key]['mold']=2;
                    $resultVouchers = M('poster_advert_vouchers')->where('dataId='.$value['id'])->field('number,vouchersPrice as give,dataId,vouchersIntegral,content,startTime,endTime,shopName,vouchersIntegral')->find();
                    //echo M('poster_advert_vouchers')->getLastSql().'<br/>'.'<br/>';

                    if($resultVouchers){
                        $result[$key]['give']=$resultVouchers['give'];
                        $result[$key]['number']=$resultVouchers['number'];
                        $result[$key]['vouchersStartTime']=$resultVouchers['startTime'];
                        $result[$key]['vouchersEndTime']=$resultVouchers['endTime'];
                        $result[$key]['vouchersIntegral']=$resultVouchers['vouchersIntegral'];
                        $result[$key]['shopName']=$resultVouchers['shopName'];
                        $result[$key]['content']=$resultVouchers['content'];
                        $result[$key]['dataId']=  encodePass($resultVouchers['dataId']);
                        
                        //判断查询条件
                        $res=M('get_vouchers_log')->field('vouchersNumber,isDel')->where('userId='.$userId.' and dataId='.$value['id'].' and id='.$TmpGetVouchersId)->order('id desc')->find();
                        //echo M('get_vouchers_log')->getLastSql();die;
                        //查询代金券编号
                        //$resOfficial=M('OfficialForwardLog')->field('vouchersNumber')->where('friendId='.$userId.' and dataId='.$value['id'].' and getVouchersId='.$value['getVouchersId'])->find();
                        $result[$key]['vouchersNumber']=$res['vouchersNumber'];
                        
                        if($res['isDel']==2){//判断删除状态
                            $result[$key]['voucherStatus']=5;
                            
                        }else{
                            
                            //此处官方转发是否获取进行判断状态
                            $resIsForward=M('OfficialForwardLog')->field('getStatus')->where('dataId='.$value['id'].' and friendId='.$userId.' and officialForwardId='.$value['noteId'])->find();
                            
                            if($resIsForward['getStatus']){
                                if($resIsForward['getStatus']=='1'){//未获取
                                    $result[$key]['isNew'] = 1;
                                    $result[$key]['voucherStatus'] = 4;
                                    $result[$key]['contentNews']='代金券未打开';
                                }elseif($resIsForward['getStatus']=='2'){
                                    $result[$key]['isNew'] = 2;
                                    $result[$key]['voucherStatus'] = 2;
                                    $result[$key]['contentNews']='哎呀，已经有这张代金券了！';
                                }elseif($resIsForward['getStatus']=='3'){
                                    $result[$key]['isNew'] = 2;
                                    $result[$key]['voucherStatus'] = 3;
                                    $result[$key]['contentNews']='哎呀错过了！代金券已作废';
                                }else{
                                    $result[$key]['isNew'] = 2;
                                    $result[$key]['voucherStatus'] = 1;
                                    $result[$key]['contentNews']='你已获取该代金券';
                                }
                            }else{
                                $result[$key]['isNew'] = 1;
                                $result[$key]['voucherStatus'] = 4;
                                $result[$key]['contentNews']='代金券未打开';
                            }
                        }
                    }
                }
                
                //if($result[$key]['isForward']==1){
                    //$result[$key]['vouchersNumber']=$value['forwardVouchersNumber'];
                //}
                
                $result[$key]['forwardId'] =  encodePass($value['noteId']);
                $result[$key]['officialId']= encodePass($value['noteId']);
                
                $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                
                $uwhere['id'] = $friendId;
                $ufields = 'image,name,groupType,integral';
                $userData = M("members")->where($uwhere)->field($ufields)->find();
                if ($userData) {
                    $result[$key]['nickname'] = $userData['name'];
                    $result[$key]['userImage'] = $userData['image'];
                    $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                }
            }
        }
        //var_dump($result);die;
        return $result;
    }
    
    
    
    /**
     * 3.2.3朋友之间的转发广告列表
     * @param int $friendId
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * 
     * @return array
     */
    function personalForwardNew($friendId, $userId, $page = 1, $pageSize = 20, $selectTime) {
        $friendId = decodePass($friendId);
        
        $where = array(
            'f.addTime' => array('elt', $selectTime)
        );
        
        //$where['_string'] = "f.userId not in(SELECT fuid FROM lu_friend_shield WHERE uid={$userId} AND status = '1')"; //去除屏蔽的朋友
        //if($friendId==$userId){
            //$where['_string'] = "f.userId={$userId} and  f.friendId={$friendId}";
        //}else{
            $where['_string'] = "(f.userId={$userId} and  f.friendId={$friendId}) or ( f.friendId={$userId} and f.userId={$friendId})";
        //}
        
        $join = ' AS f LEFT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $fields = 'p.title,f.vouchersNumber as forwardVouchersNumber,p.id,p.imageUrl,f.type as forwardType,p.type,p.weburl,p.shareUrl,p.collectTotal,p.warnPhone,p.userId,f.userId as uid,f.friendId,f.addTime,f.id as noteId,f.getStatus,f.getVouchersId';
        $order = 'f.addTime desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('friend_forward')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
        //echo M('friend_forward')->getLastSql();die;
        
        if ($result) {

            $dataS['isNew'] = '2';
            //M('Friend')->where('uid =' . $friendId . ' and fuid =' . $userId . ' and isNew="1"')->data($dataS)->save();
            
            //判断uid和fuid之间的转发关系
            if($friendId==$userId){
                M('Friend')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->data($dataS)->save();
            }else{
                $resFriend=M('Friend')->field('fuid,uid')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->find();
                
                if($resFriend['uid']){
                    M('Friend')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->data($dataS)->save();
                }else{
                    $resFriends=M('Friend')->field('fuid,uid')->where('uid =' .$friendId . ' and fuid =' .  $userId. ' and isNew="1"')->find();
                    
                    if($resFriends['uid']){
                        M('Friend')->where('uid =' .$friendId . ' and fuid =' .  $userId . ' and isNew="1"')->data($dataS)->save();
                    }
                }
            }
            
            //echo M('FriendForward')->getLastSql();die;
            foreach ($result as $key => $value) {
                if($value['uid']==$userId){//判断转出转来关系
                    $result[$key]['isForward']=1;
                    $flag = 1;
                }else{
                    $result[$key]['isForward']=2;
                    $flag = 2;
                }
                
                if($value['uid']==$value['friendId']){
                    $tmpUserId = $value['uid'];
                }else{
                    if($value['uid']==$userId){
                        $tmpUserId = $value['friendId'];
                    }else{
                        $tmpUserId = $value['uid'];
                    }
                }
                
                $result[$key]['mold']=1;

                if($value['type']==4 && $value['forwardType']==2){//判断代金券
                    $result[$key]['mold']=2;
                    $resultVouchers = M('poster_advert_vouchers')->where('dataId='.$value['id'])->field('number,vouchersPrice as give,dataId,vouchersIntegral,content,startTime,endTime,shopName,vouchersIntegral')->find();

                    if($resultVouchers){
                        $result[$key]['give']=$resultVouchers['give'];
                        $result[$key]['number']=$resultVouchers['number'];
                        $result[$key]['vouchersStartTime']=$resultVouchers['startTime'];
                        $result[$key]['vouchersEndTime']=$resultVouchers['endTime'];
                        $result[$key]['vouchersIntegral']=$resultVouchers['vouchersIntegral'];
                        $result[$key]['shopName']=$resultVouchers['shopName'];
                        $result[$key]['content']=$resultVouchers['content'];
                        $result[$key]['dataId']=  encodePass($resultVouchers['dataId']);
                        
                        //判断查询条件
                        $res=M('get_vouchers_log')->field('vouchersNumber,isDel')->where('userId='.$userId.' and dataId='.$value['id'].' and id='.$value['getVouchersId'])->find();
                        $result[$key]['vouchersNumber']=$res['vouchersNumber'];
                        
                        if($res['isDel']==2){//判断删除状态
                            $result[$key]['voucherStatus']=5;
                            
                        }else{
                            if($value['getStatus']=='1'){//未获取
                                $result[$key]['isNew'] = 1;
                                $result[$key]['voucherStatus'] = 4;
                                $result[$key]['contentNews']='代金券未打开';
                            }elseif($value['getStatus']=='2'){
                                $result[$key]['isNew'] = 2;
                                $result[$key]['voucherStatus'] = 2;
                                $result[$key]['contentNews']='哎呀，已经有这张代金券了！';
                            }elseif($value['getStatus']=='3'){
                                $result[$key]['isNew'] = 2;
                                $result[$key]['voucherStatus'] = 3;
                                $result[$key]['contentNews']='哎呀错过了！代金券已作废';
                            }else{
                                $result[$key]['isNew'] = 2;
                                $result[$key]['voucherStatus'] = 1;
                                $result[$key]['contentNews']='你已获取该代金券';
                            }
                        }
                    }
                }
                
                if($result[$key]['isForward']==1){
                    $result[$key]['vouchersNumber']=$value['forwardVouchersNumber'];
                }
                
                $result[$key]['forwardId'] =  encodePass($value['noteId']);
                //var_dump($result);die;
                $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                $uwhere['id'] = $tmpUserId;
                $ufields = 'image,name,groupType,integral';
                $userData = M("members")->where($uwhere)->field($ufields)->find();
                if ($userData) {
                    $result[$key]['nickname'] = $userData['name'];
                    $result[$key]['userImage'] = $userData['image'];
                    $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                }
            }
        }
        return $result;
    }
    
    
    

    /**
     * 3.2朋友之间的转发广告列表--本次更新后删除
     * @param int $friendId
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @param int $selectTime
     * @return array
     */
    function personalForward($friendId, $userId, $page = 1, $pageSize = 20, $selectTime) {
        $friendId = decodePass($friendId);
        $where = array(
            //'f.userId' => $friendId,
            //'f.friendId' => $userId,
            'f.addTime' => array('elt', $selectTime)
        );
        
        //$where['_string'] = "f.userId not in(SELECT fuid FROM lu_friend_shield WHERE uid={$userId} AND status = '1')"; //去除屏蔽的朋友
        if($friendId==$userId){
            $where['_string'] = "f.userId={$userId} and  f.friendId={$friendId}";
        }else{
            $where['_string'] = "(f.userId={$userId} and  f.friendId={$friendId}) or ( f.friendId={$userId} and f.userId={$friendId})";
        }
        
        $join = ' AS f LEFT JOIN lu_poster_advert AS p ON f.dataId=p.id';
        $fields = 'p.title,p.id,p.imageUrl,p.weburl,p.shareUrl,p.collectTotal,p.warnPhone,p.userId,f.userId as uid,f.friendId,f.addTime,f.id as noteId';
        $order = 'f.addTime desc';
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $result = M('friend_forward')->where($where)->field($fields)->order($order)->limit($limit)->join($join)->select();
        //echo M('friend_forward')->getLastSql();die;
        
        if ($result) {

            $dataS['isNew'] = '2';
            //M('Friend')->where('uid =' . $friendId . ' and fuid =' . $userId . ' and isNew="1"')->data($dataS)->save();
            
            //判断uid和fuid之间的转发关系
            if($friendId==$userId){
                M('Friend')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->data($dataS)->save();
            }else{
                $resFriend=M('Friend')->field('fuid,uid')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->find();
                
                if($resFriend['uid']){
                    M('Friend')->where('uid =' . $userId. ' and fuid =' . $friendId . ' and isNew="1"')->data($dataS)->save();
                }else{
                    $resFriends=M('Friend')->field('fuid,uid')->where('uid =' .$friendId . ' and fuid =' .  $userId. ' and isNew="1"')->find();
                    
                    if($resFriends['uid']){
                        M('Friend')->where('uid =' .$friendId . ' and fuid =' .  $userId . ' and isNew="1"')->data($dataS)->save();
                    }
                }
            }
            
            //echo M('FriendForward')->getLastSql();die;
            foreach ($result as $key => $value) {
                if($value['uid']==$userId){
                    $result[$key]['isForward']=1; 
                }else{
                    $result[$key]['isForward']=2; 
                }
                
                if($value['uid']==$value['friendId']){
                    $tmpUserId = $value['uid'];
                }else{
                    if($value['uid']==$userId){
                        $tmpUserId = $value['friendId'];
                    }else{
                        $tmpUserId = $value['uid'];
                    }
                }
                
                
                $result[$key]['title'] = base64_encode(jsonStrWithOutBadWordsNew($value['title']));
                //$result[$key]['userId'] = encodePass($value['userId']);
                //$result[$key]['id'] = encodePass($value['id']);
                //$uwhere['id'] = $value['userId'];
                $uwhere['id'] = $tmpUserId;
                $ufields = 'image,name,groupType,integral';
                $userData = M("members")->where($uwhere)->field($ufields)->find();
                if ($userData) {
                    $result[$key]['nickname'] = $userData['name'];
                    $result[$key]['userImage'] = $userData['image'];
                    $result[$key]['isAuthentication'] = $userData['groupType'] < 2 ? 1 : 2;
                }
                //$result[$key]['id'] = encodePass($value['id']);
                //$result[$key]['isNew'] = $value['addTime'] > $lastTime ? 1 : 2;
            }
        }
        return $result;
    }

}
