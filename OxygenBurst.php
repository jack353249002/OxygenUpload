<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/3
 * Time: 11:24
 */
/**
 *分片上传工具
 *
 */
class OxygenBurst
{
    private $token='';//会话
    private $cacheUrl='';//会话缓存地址
    private $conformityUrl='';//整合后存放地址
    private $suffix=''; //存储的后缀名
    private $total=0;//总共的个数
    private $successFileCount=0;//已经成功上传的个数
    private $nowFile=null;
    private $jsonInfor='';
    public function __construct($token,$cacheUrl,$conformityUrl,$suffix,$total,$successFileCount,$nowFile){
    public function __construct($token,$cacheUrl,$conformityUrl='',$suffix='',$total=0,$successFileCount=0,$nowFile=null){
        $this->token=$token;
        $this->cacheUrl=$cacheUrl;
        $this->conformityUrl=$conformityUrl;
        $this->suffix=$suffix;
        $this->total=$total;
        $this->successFileCount=$successFileCount;
        $this->nowFile=$nowFile;
        if(!file_exists($this->cacheUrl.'/'.$this->token)) {
            self::createTokenDir();
            $json=self::createJsonConfig();
            self::writeJsonToConfig($json,$this->cacheUrl.'/'.$this->token);
        }
        else{
            $this->jsonInfor=self::readJson($this->cacheUrl.'/'.$this->token.'/'.'config.json');
        }
    }
    /*上传文件*/
    public function uploadFile(){
        $obj= new OxygenUpload($this->cacheUrl.'/'.$this->token,'','file','',true);
        $obj->mandatorySuffixName='rep';
        $imgUrl=$obj->createFileMd5Name()->runUpload($this->nowFile)->returnUrl();
        $this->jsonInfor['nowFile'][]=$imgUrl;
        $json=json_encode($this->jsonInfor);
        self::writeJsonToConfig($json,$this->cacheUrl.'/'.$this->token);
    }
    /*创建md5会话*/
    static function createMd5Token(){
        $token=md5(uniqid());
        return $token;
    }
    /*按照会话生成会话目录*/
    public function createTokenDir(){
        if(!file_exists($this->cacheUrl.'/'.$this->token)){
            mkdir($this->cacheUrl.'/'.$this->token);
        }
        return true;
    }
    /*读取json信息*/
    private function readJson($fileUrl){
        $file=fopen($fileUrl, "r");
        $json=fread($file,filesize($fileUrl));
        $jsonData=json_decode($json,true);
        return $jsonData;
    }
    /*会话目录json信息文件生成工具*/
    private function createJsonConfig(){
        /*
         * 格式: {"token":会话名,"suffix":整合后的后缀名,"total":应该上传的文件总片数,"successFileCount":上传成功的文件数,
         * "nowFile":[文件1,文件2] 当前存储的文件列表路径}
         * */
        $dataTeample['token']=$this->token;
        $dataTeample['suffix']=$this->suffix;
        $dataTeample['successFileCount']=$this->successFileCount;
        $dataTeample['nowFile']=array();
        $dataTeample['total']=$this->total;
        $jsonData=json_encode($dataTeample);
        return $jsonData;
    }
    /*写入json数据到config.json中*/
    private function writeJsonToConfig($json,$saveUrl){
        $fileUrl=$saveUrl.'/'.'config.json';
        $file=fopen($fileUrl, "w+");
        if(fwrite($file,$json)){
            return true;
        }
        else{
            return false;
        }
    }
    /*按照令牌将文件合并成一个文件*/
    public function MergeFile(){
        $confArray=$this->jsonInfor;
        $baseUrl=$this->cacheUrl.'/'.$this->token;
        $fileArray=$confArray['nowFile'];
        foreach ($fileArray as &$value){
            $value=$baseUrl.'/'.$value;
        }
        $oxygenUpload= new OxygenUpload($this->conformityUrl,'','file','t1',true);
        $oxygenUpload->mandatorySuffixName=$this->suffix;
        $oxygenUpload->mergeFile($fileArray,$this->conformityUrl,$this->suffix);
    }

}