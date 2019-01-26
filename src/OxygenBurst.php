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
namespace oxygen\upload;
class OxygenBurst
{
    private $token='';//文件会话(指纹)
    private $cacheUrl='';//会话缓存地址
    private $conformityUrl='';//整合后存放地址
    private $suffix=''; //存储的后缀名
    private $total=0;//总共的个数
    private $nowFile=null;
    private $jsonInfor=null;
    private $shardFingerprint='';//分片指纹
    private $fragmentedIndexes=null;//分片索引
    public function __construct($token,$shardFingerprint,$cacheUrl,$conformityUrl='',$suffix='',$total=0,$fragmentedIndexes=null,$nowFile=null){
        $this->token=$token;
        $this->cacheUrl=$cacheUrl;
        $this->conformityUrl=$conformityUrl;
        $this->suffix=$suffix;
        $this->total=$total;
        $this->nowFile=$nowFile;
        $this->shardFingerprint=$shardFingerprint;
        $this->fragmentedIndexes=$fragmentedIndexes;
        if(!file_exists($this->cacheUrl.'/'.$this->token)) {
            self::createTokenDir();
            $json=self::createJsonConfig();
            self::writeJsonToConfig($json,$this->cacheUrl.'/'.$this->token);
        }
        else{
            $this->jsonInfor=self::readJson($this->cacheUrl.'/'.$this->token.'/'.'config.json');
        }
    }
    /*获取指定令牌下的配置信息*/
    public function getConfigForToken(){
        return $this->jsonInfor;
    }
    /*上传文件*/
    public function uploadFile(){
        $obj= new OxygenUpload($this->cacheUrl.'/'.$this->token,'','file',$this->shardFingerprint,true);
        $obj->mandatorySuffixName='rep';
        $imgUrl=$obj->runUpload($this->nowFile)->returnUrl();
        $fileObj['Fingerprint']=$this->shardFingerprint;
        $fileObj['url']=$imgUrl;
        $fileObj['fragmentedIndexes']=$this->fragmentedIndexes;
        $this->jsonInfor['nowFile']["{$this->shardFingerprint}"]=$fileObj;
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
    /*查找指定会话是否存在*/
    public function getTokenIsHave(){
        if(!file_exists($this->cacheUrl.'/'.$this->token)){
            return false;
        }
        return true;
    }
    /*查找指定指纹是否存在*/
    public function getShardFingerprint(){
        if(isset($this->jsonInfor['nowFile']["{$this->shardFingerprint}"])){
            return true;
        }
        else{
            return false;
        }
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
         * "nowFile":[{"文件指纹":"文件.rep"},{"文件指纹":"指纹.rep"}] 当前存储的文件列表路径}
         * */
        $dataTeample['token']=$this->token;
        $dataTeample['suffix']=$this->suffix;
        $dataTeample['nowFile']=array();
        $dataTeample['total']=$this->total;
        $dataTeample['state']=0; //状态0.未完成1.完成
        $this->jsonInfor=$dataTeample;
        $jsonData=json_encode($dataTeample);
        return $jsonData;
    }
    /*
     * 写入json数据到config.json中
     * json:json字符串
     * saveUrl:保存的路径
     * */
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
    /*
     * 按照令牌将文件合并成一个文件
     * saveName:文件名称
     * */
    public function MergeFile($saveName){
        $confArray=$this->jsonInfor;
        $baseUrl=$this->cacheUrl.'/'.$this->token;
        $fileArray=$confArray['nowFile'];
        $fileUrl=array();
        foreach ($fileArray as $key=>$value){
            $fileUrl[]=$baseUrl.'/'.$value['url'];
        }
        $oxygenUpload= new OxygenUpload($this->conformityUrl,'','file',$saveName,true);
        $oxygenUpload->mandatorySuffixName=$this->suffix;
        $oxygenUpload->mergeFile($fileUrl,$this->conformityUrl,$this->suffix);
        $this->jsonInfor['state']=1;
        $json=json_encode($this->jsonInfor);
        self::writeJsonToConfig($json,$this->cacheUrl.'/'.$this->token);
        return true;
    }

}