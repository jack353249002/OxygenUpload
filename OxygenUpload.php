<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/29
 * Time: 16:07
 */
/*上传工具*/
class OxygenUpload{
    private $saveUrl='';//图片保存地址目录
    private $cacheUrl='';//缓存地址
    private $saveName=''; //保存后的文件名
    private $ddr=['file'=>1,'base64'=>2];//传输类型
    private $ddrSelectEd=null;//类型编号
    private $key='file';//设置file的键
    private $fileInfor=array();
    private $returnUrl=''; //返回的路径
    private $mandatorySuffix=false;//是否开启强制后缀默认关闭false
    public $mandatorySuffixName='exe'; //强制后缀名
    public function __construct($saveUrl,$cacheUrl,$selectDDR,$saveName,$mandatorySuffix=false){
        $this->saveUrl=$saveUrl;
        $this->cacheUrl=$cacheUrl;
        $this->saveName=$saveName;
        $this->ddrSelectEd=$this->ddr["{$selectDDR}"];
        $this->mandatorySuffix=$mandatorySuffix;
    }
    public function runUpload($file){
        if($this->ddrSelectEd==1) {
            self::uploadFile($file);
        }
        if($this->ddrSelectEd==2){
            self::baseToImage($file);
        }
        return $this;
    }
    /*普通postfile上传*/
    private function uploadFile($file){
        $fileCache=$file;
        self::getFileInfor($fileCache);
        $tmpfile=$this->fileInfor['tmp_name'];
        $name=$this->saveName;
        if(!file_exists($this->saveUrl)){
            mkdir($this->saveUrl,0777,true);
        }
        if($this->mandatorySuffix){
            $createFile = $this->saveUrl . '/' . $name . '.' . $this->mandatorySuffixName;
            $this->returnUrl = $this->returnUrl . '/' . $name . '.' .$this->mandatorySuffixName;
        }
        else {
            $createFile = $this->saveUrl . '/' . $name . '.' . $this->fileInfor['suffix'];
            $this->returnUrl = $this->returnUrl . '/' . $name . '.' . $this->fileInfor['suffix'];
        }
        move_uploaded_file($tmpfile,$createFile);
        return true;
    }
    /*获取图片信息*/
    public function getFileInfor($fileArray){
        $this->fileInfor['name']=$fileArray['name'];
        /*获取图片类型*/
        $typeArray=explode("/",$fileArray['type']);
        $this->fileInfor['type']=$typeArray[0];
        $this->fileInfor['suffix']=$typeArray[1]; //后缀名
        $this->fileInfor['size']=$fileArray['size'];
        $this->fileInfor['tmp_name']=$fileArray['tmp_name'];
        return $this;
    }
    /*base64转文件*/
    public function baseToImage($baseCode){
        $baseInfor=self::getBaseInfor($baseCode);
        $type=$baseInfor['fileType'];
        $newFile=$this->saveUrl.'/';
        $newFile = $newFile.$this->saveName.".{$type}";
        $this->returnUrl=$this->returnUrl.'/'.$this->saveName.".{$type}";
        $imgShows=base64_decode($baseInfor['fileBody']);
        if(!file_exists($this->saveUrl)){
            mkdir($this->saveUrl,0777,true);
        }
        if (file_put_contents($newFile,$imgShows)) {
                return true;
        }
    }
    /*返回图片路径*/
    public function returnUrl(){

        return $this->returnUrl;
    }
    /*获取base中文件信息*/
    private function getBaseInfor($baseCode){
        $baseInfor=explode(',',$baseCode);//base64信息
        $fileInfor=$baseInfor[0];
        $fileBody=$baseInfor[1];//文件主体
        $splittingPart1=explode(';',$fileInfor); //一次拆分获取传输类型
        $baseType=$splittingPart1[1];//获取数据类型
        $splittingPart2=explode('/',$splittingPart1[0]);//二次拆分获取文件后缀名
        $fileType=$splittingPart2[1];//获取后缀名
        $splittingPart3=explode(':',$splittingPart2[0]);//三次拆分获取文件属性
        $attribute=$splittingPart3[1];
        return array('fileBody'=>$fileBody,'baseType'=>$baseType,'fileType'=>$fileType,'attribute'=>$attribute);

    }
    /*生成随机名字*/
    public function createFileMd5Name(){
        $name=md5(uniqid());
        $this->saveName=$name;
        return $this;
    }
    /*按照日期生成文件夹*/
    public function createDirForDate(){
        $ymd = date('Ymd');
        $this->saveUrl=$this->saveUrl.'/'.$ymd;
        $this->returnUrl='/'.$ymd;
        return $this;
    }
    /*合并指定文件夹下的所有文件*/
    public function mergeFile($url,$saveUrl,$suffix){
        if(file_exists($url)){
            $fileTable=self::fileSorting($url);
            $blockInfo=array();
            foreach ($fileTable as &$value){
                $blockInfo[]=$url.'/'.$value['name'];
            }
            $saveFile=$saveUrl.'/'.$this->saveName.'.'.$suffix;
            if($saveFile){
                $fp   = fopen($saveFile,"wb");
            }else{
                $fp   = fopen($saveUrl,"wb");
            }
            foreach ($blockInfo as $block_file) {
                $handle = fopen($block_file,"rb");
                fwrite($fp,fread($handle,filesize($block_file)));
                fclose($handle);
                unset($handle);
            }
            fclose ($fp);
            unset($fp);
        }
        return true;
    }
    /*文件排序*/
    private function fileSorting($dir){
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $i = 0;
                while (($file = readdir($dh)) !== false) {
                    if ($file != "." && $file != "..") {
                        $files[$i]["name"] = $file;//获取文件名称
                        $files[$i]["size"] = round((filesize($file)/1024),2);//获取文件大小
                        $files[$i]["time"] = date("Y-m-d H:i:s",filemtime($file));//获取文件最近修改日期
                        $i++;
                    }
                }
            }
            closedir($dh);
            foreach($files as $k=>$v){
                $size[$k] = $v['size'];
                $time[$k] = $v['time'];
                $name[$k] = $v['name'];
            }
            array_multisort($time,SORT_DESC,SORT_STRING, $files);//按时间排序
            //array_multisort($name,SORT_DESC,SORT_STRING, $files);//按名字排序
            //array_multisort($size,SORT_DESC,SORT_NUMERIC, $files);//按大小排序
            return $files;
        }
    }
}