<?php
use Think\Controller;
/** 短链系统
 * Description of ShortLinkController
 *
 * @author wangping <wangping@feibaokeji.com>
 */
class ShortLinkController extends CommonController {
    
    //生成短链
    function code62($x) {
        $show = '';
        while ($x > 0) {
            $s = $x % 62;
            if ($s > 35) {
                $s = chr($s + 61);
            } elseif ($s > 9 && $s <= 35) {
                $s = chr($s + 55);
            }
            $show .= $s;
            $x = floor($x / 62);
        }
        return $show;
    }

    function url($url) {
        $url = crc32($url);
        $result = sprintf("%u", $url);
        return code62($result);
    }

    function url_md5($url) {
        $url = md5($url);
        $url = crc32(md5($url));
        $result = sprintf("%u", $url);
        return code62($result);
    }
    
    /*
     * 短链处理
     */
    public function disposeUrl(){
        $url = I('post.url');
        $url = 'www.baidu.com';
        if($url){
            $data['oldUrl'] = $url;
            $data['newUrl'] = $this->url($url);
            $data['uniqueness'] = $this->url_md5($url);
            
            $data['addTime'] = time();
            $result = M('ShortLink')->data($data)->add();
            if($result){
                $return = array(
                    'status' => 1,
                    'message' => '保存成功！',
                    'id' => $result,
                );
            }else{
                $return = array(
                    'status' => 2,
                    'message' => '保存失败！',
                    'id' => 0,
                );
            }
        }else{
            $return = array(
                'status' => 4,
                'message' => '参数错误！',
                'id' => 0,
            );
        }
        echo jsonStr($return);
    }
}
