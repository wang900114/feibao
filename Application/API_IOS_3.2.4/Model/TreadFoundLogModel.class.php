<?php

use Think\Model;

/**
 * 踩数据处理类
 * @author xiaofeng <yuanmingwei@feibaokeji.com>
 */
class TreadFoundLogModel extends CommonModel {

    /**
     * 根据会员ID与发现ID 查询是否踩过
     * @param int $userId
     * @param int $dataId
     * @return array
     */
    public function treadFoundByDidAndUid($userId, $dataId) {
        $map['userId'] = $userId;
        $map['dataId'] = $dataId;
        $result = $this->where($map)->find();
        return $result;
    }

    /**
     * 添加现在踩信息
     * @param int $userId
     * @param int $dataId
     * @return boole
     */
    public function treadFoundAdd($userId, $dataId) {
        $data = array(
            'userId' => $userId,
            'dataId' => $dataId,
            'addTime' => time()
        );
        $res = $this->add($data);
        if ($res) {
            //更新发现表 踩数量
            D('found')->setColunm(array('id' => $dataId), 'treadNum', 1);
        }
        return $res;
    }

    /**
     * 按发现ID查询 
     * @param int $id 发现Id 
     * @return array
     */
    public function getFoundById($id) {
        $where = array(
            'id' => $id,
            'del' => "1"
        );
        $data = D("Found")->where($where)->field("*")->find();
        return $data;
    }

}
