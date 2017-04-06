<?php

use Think\Model;

//发现 收藏 模型表
class CollectFoundLogModel extends CommonModel {

    public $_validate = array(
            // array('name','require','必须填写城市名称'),
            // array('firstLetter','require','必须填写首字母'),
            // array('wordFirstLetter','require','必须填写拼音首字母'),
            // array('fullLetter','require','必须填写全拼'),
            // array('lng','require','必须有经度数值'),
            // array('lat','require','必须有纬度数值'),
    );

    /**
     * 查询收藏关系
     * @param int $userId 用户Id
     * @param int $dataId 发现Id
     * @author xiaofeng <yuanmingwei@feibaokeji.com>
     * @return array 查询结果
     */
    public function getFoundCollectStatus($userId, $dataId) {
        $where = array(
            'userId' => $userId,
            'dataId' => $dataId
        );
        $fields = 'id,status';
        $result = $this->where($where)->field($fields)->find();
        return $result;
    }

}
