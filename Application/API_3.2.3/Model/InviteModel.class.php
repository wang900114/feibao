<?php

/**
 * 邀请码处理类
 * 
 */
use Think\Model;

class InviteModel extends CommonModel {

    /**
     * 邀请码处理
     * 
     * 
     * 
     * @param int $uid 邀请人ID
     * @param int $user_flag 邀请人类型(真机还是虚拟机)
     * @param int $nuid 新用户ID
     * @param int $nuser_flag 新注册用户(被邀请人)的类型(真机还是虚拟机)
     * @return boolean
     */
    public function inviteSaveData($uid, $user_flag, $nuid, $nuser_flag) {

        //邀请人和被邀请人是同一个人的时候直接返回
        if ($uid == $nuid) {
            return 5;
        }
        $flag = 1;
        if ($uid && $nuid) {
            //查询会员表中是否存在此用户
            $seletData = array(
                'id' => $uid
            );
            $userData = D("Members")->selData($seletData, 1, 'id,freeze,mobileflag');
            //邀请用户非正常状态
            if ($userData[0]['freeze']) {
                return 7;
            }

            //查询会员表中是否存在此用户
            $seletData = array(
                'id' => $nuid
            );
            $userNewData = D("Members")->selData($seletData, 1, 'id,freeze,mobileflag');
            //新用户非正常状态
            if ($userNewData[0]['freeze']) {
                return 8;
            }

            //邀请人是否填写过被邀请人的邀请码,如果填过,则返回失败代码9
            $check_where = array();
            $check_where['uid'] = $nuid;
            $check_where['nuid'] = $uid;
            $check_invite = M("invite")->where($check_where)->find();
            if ($check_invite) {
                return 9;
            }
            //当一个空用户调用接口的填写一个有效用户为其邀请人时,视为非法用户非法操作,记录此有效用户信息,设定其为非法用户,检查其关联账户,全部设为非法用户;
            //当两个用户中任意一个不存在或者非法或者冻结的时候,直接返回.不增加双方任何一个人的飞币,曹洪猛,20150108修改
            if (empty($userNewData) or empty($userData) or $userNewData[0]['freeze'] or $userData[0]['freeze']) {

                //设置用户为非法状态
//                D("Members")->setUserWrongful($uid);
                //设置被邀请人下相关的用户状态为非法 现只涉及一层关系->曹洪猛2015/01/06修改,暂停关联账户提示
//                $this->setAssociateUserStatus($uid);
                $flag = 5;
                //当两个用户均存在并且均为正常用户的时候执行此功能
            } else if ($userData && $userNewData && empty($userNewData[0]['freeze']) && empty($userData[0]['freeze'])) {
                //xiaofeng 2014-12-30 曹说先暂停此功能
                //老用户的状态是否正常
                // if ($userData[0]['freeze'] > 0) {
                //$flag = 7;
                // } else if ($userNewData[0]['freeze'] > 0) {
                //新用户的状态是否正常
                // $flag = 8;
                // } else {
                //计算用户邀请频次
                $resultFlag = $this->getDataNewInvite($uid, 20, 1);

                if ($resultFlag == 1) {
                    //满足条件 写入数据库
                    $seletDataInvite = array(
                        'nuid' => $nuid
                    );
                    $resultInvte = D("invite")->selData($seletDataInvite);
                    //var_dump($resultInvte);die;

                    if (!$resultInvte) {
                        //邀请获得飞币,被邀请人
                        $memberPoints = $this->readConfig("invite_member_points_num");
                        //此次邀请获得飞币,邀请人
                        $peoplePoints = $this->readConfig("invite_people_points_num");
                        $insertData = array(
                            'uid' => intval($uid),
                            'uidmobileflag' => strval($user_flag),
                            'nuid' => intval($nuid),
                            'nuidmobileflag' => strval($nuser_flag),
                            'addTime' => time()
                        );
                        //此处判断是在当两个用户均为正常用户的时候判断其到底是真机还是模拟器
                        //如果两个用户中断均为真机或者均为模拟器或者邀请人为模拟器,则给邀请人记录中记录获得邀请飞币
                        if ($nuser_flag == $user_flag || $user_flag == '2') {
                            $insertData['integral'] = $peoplePoints;
                        }
                        $insertID = D("invite")->addData($insertData);
                        if ($insertID) {
                            //此处判断是在当两个用户均为正常用户的时候判断其到底是真机还是模拟器
                            if ($nuser_flag == $user_flag) {
                                //当邀请人和被邀请人用户属性相同的时候,即为均为真机或者均为模拟器
                                //双方均增加飞币
                                $result = D("Members")->addUsersIntegral($nuid, $memberPoints);
                                $result = D("Members")->addUsersIntegral($uid, $peoplePoints);
                            } elseif ($nuser_flag != $user_flag) {
                                //当其中一个用户是虚拟用户时
                                //只给虚拟的用户增加飞币
                                if ($nuser_flag == 2) {
                                    D("Members")->addUsersIntegral($nuid, $memberPoints);
                                    $peoplePoints = 0;
                                } else {
                                    D("Members")->addUsersIntegral($uid, $peoplePoints);
                                    $memberPoints = 0;
                                }
                            }


                            //此处判断错误,曹洪猛20150108,启用上边的判断
//                            if ($nuser_flag == $user_flag && $userData[0]['freeze'] == $userNewData[0]['freeze'] && ($nuser_flag == 1) && ($userNewData['freeze'] == '0')) {//当邀请人和被邀请人都为真实用户、正常用户时
//                                $result = D("Members")->addUsersIntegral($nuid, $memberPoints);
//                                $result = D("Members")->addUsersIntegral($uid, $peoplePoints);
//                            } else {
//                                $memberPoints = 0;
//                                $peoplePoints = 0;
//                                $result = D("Members")->addUsersIntegral($nuid, 0);
//                                $result = D("Members")->addUsersIntegral($uid, 0);
//                            }
                            //插入邀请人日志记录表
                            $logDataPeople = array(
                                'userId' => $uid,
                                'mobileflag' => strval($user_flag),
                                'remark' => '邀请新会员',
                                'points' => $peoplePoints,
                                'addTime' => time()
                            );
                            M("invite_points_log")->data($logDataPeople)->add();
                            //插入被邀请人得飞币日志记录表
                            $logDataMember = array(
                                'userId' => $nuid,
                                'mobileflag' => strval($nuser_flag),
                                'remark' => '被邀请入驻',
                                'points' => $memberPoints,
                                'addTime' => time()
                            );
                            M("invite_points_log")->data($logDataMember)->add();

                            $flag = 2;
                            //飞币大于0时发推送信息
                            if ($peoplePoints > 0) {
                                $jpush = A('Admin/JPush');
                                $content = '您的小伙伴受邀入驻飞报了，恭喜您获得了' . $peoplePoints . '飞币';
                                D('Members')->addMemberDope($uid, $content, '1', $peoplePoints, '', '12');
                                //$res = $jpush->pushNoticeWithOutDB($uid, $content);
                            }
                            if ($memberPoints > 0) {
                                $jpush = A('Admin/JPush');
                                $content = '欢迎您受小伙伴邀请入驻飞报，恭喜您获得了' . $memberPoints . '飞币';
                                D('Members')->addMemberDope($nuid, $content, '1', $memberPoints, '', '13');
                                //$res = $jpush->pushNoticeWithOutDB($nuid, $content);
                            }
                        } else {
                            $flag = 3;
                        }
                    } else {
                        $flag = 4;
                    }
                } else {
                    $flag = 6;
                }
                //}
            } else {
                $flag = 5;
            }
        }
        return $flag;
    }

    /**
     * 计算用户邀请频次
     * flag 1 为正常 2 为不正常
     * @param int $uid
     * @param int $limit 取数据库条数
     * @param int $second 最小时间（秒）
     * @return flag
     */
    public function getDataNewInvite($uid, $limit = 20, $second = 10) {
        $seletDataInvite = array(
            //时间倒序 当天最新数据
            'addTime' => array("gt", mktime(0, 0, 0, date("m"), date("d"))),
            'uid' => $uid
        );
        $flag = 1;
        //得到最近一次写入数据库的时间
        $data = D("invite")->selData($seletDataInvite, $limit, "addTime", " addTime desc");
        $count = count($data);
        if ($count > 3) {
            //频次计算
            $flag = getInviteWrong($data, $second);
            if ($flag == 2) {
                //设置用户为非法状态
                D("Members")->setUserWrongful($uid);
                //设置被邀请人下相关的用户状态为非法 现只涉及一层关系,暂时停止设置关联账户为非法的动作
//                $this->setAssociateUserStatus($uid);
            }
        }
        return $flag;
    }

    /**
     * 设置被邀请人下相关的用户状态为非法
     * @param int $uid 被邀请人ID
     * @return int
     */
    public function setAssociateUserStatus($uid) {
        return 1; //暂时中止此方法的执行,曹洪猛,20150106
        $where = array(
            "uid" => $uid
        );
        $flag = 1;
        //查询与$uid相关的用户
        $data = D("invite")->where($where)->field("nuid")->select();
        if ($data) {
            foreach ($data as $key => $value) {
                D("Members")->where(array("id" => $value['nuid']))->data(array('freeze' => '2'))->save();
                //插入非法用户记录表
                D("Members")->wrongfulLog($value['nuid']);
                $flag = 2;
            }
        }
        return $flag;
    }

    /**
     * 后台脚本处理非法用户
     */
    function inquireInvite() {
        $data = M("invite")->join(" AS i LEFT JOIN __MEMBERS__ AS m ON i.uid = m.id")->group(" i.uid")->field("m.freeze,m.id")->where("m.freeze='0'")->select();
        if ($data) {
            foreach ($data as $key => $value) {
                //计算用户邀请频次 每天最近100条数据 小于10秒
                $this->getDataNewInvite($value['id'], 100, 10);
            }
        }
    }

    /**
     * 接口操作日志表
     * @param array $dataArray
     */
    function recordOperatingLog($dataArray) {
        $data = array(
            'userId' => $dataArray['uid'],
            'nuserId' => $dataArray['nuid'],
            'addTime' => time(),
            'remark' => $dataArray['remark']
        );
        M("invite_operate_log")->data($data)->add();
    }

}
