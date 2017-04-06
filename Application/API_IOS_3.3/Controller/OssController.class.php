<?php

/**
 * 阿里云OSS云存储系统操作案例
 * @author Charles <homercharles@qq.com>
 */
use Think\Controller;

class OssController extends Controller {

    public function _initialize() {
//        showTestInfo('xxsX');
    }

    public function upload() {
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 31457280000; // 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
        $upload->rootPath = './Uploads/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功
            showTestInfo($info);
            $this->success('上传成功！');
        }
    }
    
    public function sub(){
        echo <<<xxx
        <html>
<body>

<form action="http://api.dev.feibaokeji.com/index.php/API_IOS_3.3/Oss/upload" method="post"
enctype="multipart/form-data">
<label for="file">Filename:</label>
<input type="file" name="file" id="file" /> 
<br />
<input type="submit" name="submit" value="Submit" />
</form>

</body>
</html>
xxx;
    }

}
