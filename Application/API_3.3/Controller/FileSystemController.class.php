<?php
use Think\Controller;
/** 文件系统
 * Description of FileSystemController
 *
 * @author wangping <wangping@feibaokeji.com>
 */
class FileSystemController extends CommonController {
    
    /*
     * 文件处理
     */
    public function filePathDispose($url){
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if($url){
            $data['oldUrl'] = $url;
            $data['uniqueness'] = md5($url);
            $data['addTime'] = time();
            $result = M('FileSystem')->data($data)->add();
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
        return $return;
    }
}
