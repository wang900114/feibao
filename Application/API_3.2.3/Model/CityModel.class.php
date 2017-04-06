<?php

use Think\Model;

class CityModel extends CommonModel {

    public $_validate = array(
            // array('name','require','必须填写城市名称'),
            // array('firstLetter','require','必须填写首字母'),
            // array('wordFirstLetter','require','必须填写拼音首字母'),
            // array('fullLetter','require','必须填写全拼'),
            // array('lng','require','必须有经度数值'),
            // array('lat','require','必须有纬度数值'),
    );
    
    //判断城市id是否存在，并返回城市信息
    public function getCityId($cityId,$field='id'){
        
        $map[0]['id'] = $cityId;
        
        //$field='id,guid,layer,parent_id,name,firstLetter,wordFirstLetter,fullLetter,lng,lat';
        $re = $this->table('__CITY__')->where($map)->field($field)->find();

        return $re;
    }

    /*
     * 获取城市列表
     */
    public function getListOfAllCity() {

        $condition[0]['layer'] = '2';
        $condition[0]['name'][] = array('neq', '市辖区');
        $condition[0]['name'][] = array('neq', '市');
        $condition[0]['name'][] = array('neq', '县');
        $condition[0]['name'][] = array('neq', '省直辖行政单位');
        $condition[0]['name'][] = array('neq', '省直辖县级行政单位');

        $map[0]['layer'] = '1';
        $map[0]['name'] = array('like', '%市');
        $map['_complex'] = $condition;
        $map['_logic'] = 'or';

        $re = $this
                ->table('__CITY__')
                ->where($map)
                ->field('id,guid,layer,parent_id,name,firstLetter,wordFirstLetter,fullLetter,lng,lat')
                ->select();

        return $re;
    }

    /*
     * 获取城市列表
     */

    public function getUpdateCity($addTime) {
        //$condition[0]['layer'] = '2';
        //$condition[0]['name'][] = array('neq', '市辖区');
        //$condition[0]['name'][] = array('neq', '市');
        //$condition[0]['name'][] = array('neq', '县');
        //$condition[0]['name'][] = array('neq', '省直辖行政单位');
        //$condition[0]['name'][] = array('neq', '省直辖县级行政单位');

        //$map[0]['layer'] = array('neq','3');
        //$map[0]['addTime'] = array('egt',$addTime);
        //$map[0]['name'] = array('like', '%市');
        //$map['_complex'] = $condition;
        //$map['_logic'] = 'and';
        $where ='layer = 2 or id in (1,2,9,22)';
        
        if($addTime){
            $where ='(layer = 2 or id in (1,2,9,22)) and addTime>'.$addTime;
        }
/*
        $re = $this
                ->table('__CITY__')
                ->where('')
                ->field('id,guid,layer,parent_id,name,firstLetter,wordFirstLetter,fullLetter,lng,lat')
                ->select();
        */
        
        $re = M('City')->field('id,guid,layer,parent_id,name,firstLetter,wordFirstLetter,fullLetter,lng,lat')->where($where)->select();
        //echo M('City')->getLastSql();die;
        return $re;
    }

}

?>