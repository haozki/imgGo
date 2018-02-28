<?php
/**
 * imgGo Class 通用图片处理类
 * Version: 1.2.1-alpha
 * Created by Haozki.
 *
 * 数据返回样例：
    array(
        'thumbWidth' => int
        'thumbHeight' => int
        'thumbSize' => int
        'thumbType' => string
        'thumbMIME' => string
        'thumbPath' => string
        'thumbData' => string
        'thumbBase64Data' => string
        'cropWidth' => int
        'cropHeight' => int
        'cropSize' => int
        'cropType' => string
        'cropMIME' => string
        'cropPath' => string
        'cropData' => string
        'cropBase64Data' => string
        'oriMIME' => string
        'oriWidth' => int
        'oriHeight' => int
    )
 */
class imgGo
{
    /* 初始化时全局配置$config时，与$thumbConfig、$cropConfig等独立配置中的同名配置项将会被$config中的配置项覆盖 */
    private $config = array(        //配置信息
        'toType' => 'jpg',              //默认输出类型,可选值：'auto'(根据真实MIME转换)、'ori'(使用源文件扩展名类型转换)、'jpg'、'png'、'gif'
        'savePath' => '',               //默认文件保存目录(默认留空不写入文件系统)
        'returnData' => false,          //默认是否返回文件原始数据
        'returnBase64' => false,        //默认是否返回Base64格式数据,可选值：false、'ori'(原始数据)、'src'(包含MIME)
        'newName' => '',                //新文件名
        'maxSize' => 1000,              //限制文件处理最大值(单位KB)
        'compress' => array(),
        'thumb' => array(),             //生成缩略图，默认array()不操作，如果开启则直接传递配置数组，默认值为$thumbConfig
        'crop' => array()               //生成裁剪，默认array()不操作，如果开启则直接传递配置数组，默认值为$cropConfig
    );
    private $compressConfig = array(
        'suffix' => '.compressed',      //压缩图后缀名
        'quality' => 90,                //生成压缩图的品质(1 to 100).品质越高,图像文件越大
        'toType' => null,               //单独设置(覆盖全局设置)
        'savePath' => null,             //单独设置(覆盖全局设置)
        'returnData' => null,           //单独设置(覆盖全局设置)
        'returnBase64' => null,         //单独设置(覆盖全局设置)
    );
    private $thumbConfig = array(   //缩略图选项(如果初始化类时没有给定该数组则默认不生成缩略图)
        'suffix' => '.resized',           //缩略图后缀名
        'width' => 128,                 //生成缩略图的宽度
        'height' => 128,                //生成缩略图的高度
        'quality' => 90,                //生成缩略图的品质(1 to 100).品质越高,图像文件越大
        'toType' => null,               //单独设置(覆盖全局设置)
        'savePath' => null,             //单独设置(覆盖全局设置)
        'returnData' => null,           //单独设置(覆盖全局设置)
        'returnBase64' => null,         //单独设置(覆盖全局设置)
    );
    private $cropConfig = array(    //裁剪选项(如果初始化类时没有给定该数组则默认不裁剪)
        'suffix' => '.cropped',            //裁剪后后缀名
        'axisX' => '',                  //裁剪左上角坐标X
        'axisY' => '',                  //裁剪左上角坐标Y
        'axisW' => '',                  //裁剪后图像的宽度
        'axisH' => '',                  //裁剪后图像的高度
        'width' => '',                  //缩放宽度
        'height' => '',                 //缩放高度
        'quality' => 90,                //裁剪后图像的品质(1 to 100).品质越高,图像文件越大
        'toType' => null,               //单独设置(覆盖全局设置)
        'savePath' => null,             //单独设置(覆盖全局设置)
        'returnData' => null,           //单独设置(覆盖全局设置)
        'returnBase64' => null,         //单独设置(覆盖全局设置)
    );
    private $fileObject;            //文件对象
    private $isFile = false;        //是否是文件系统物理路径下的文件
    private $handleMode;            //文件处理方式
    private $oriWidth;              //文件初始宽度
    private $oriHeight;             //文件初始高度
    private $oriMIME;               //文件初始MIME类型
    private $realType;              //文件真实MIME类型
    private $saveName;              //默认输出文件名
    private $dataInfo = array();    //最后返回的信息
    private $stateInfo;             //状态信息,
    private $stateMap = array(      //状态映射表
        'COMPLETE_OK' => 'COMPLETE_OK',
        //图片处理完成，未出错

        'FILE_READ_ERROR' => 'FILE_READ_ERROR',
        //文件读取错误，文件不完整或不是一个文件

        'FILE_TYPE_LIMIT' => 'FILE_TYPE_LIMIT',
        //文件扩展名类型不在接受处理的范围之内

        'UNKNOWN_MIME_TYPE' => 'UNKNOWN_MIME_TYPE',
        //文件的真实MIME类型不在接受处理的范围之内

        /*----------------------------------*/

        'FOLDER_MAKE_FAILED' => 'FOLDER_MAKE_FAILED',
        //创建目标目录失败

        'FILE_NAME_EXIST' => 'FILE_NAME_EXIST',
        //文件系统中已经存在同名文件

        'FILE_WRITE_ERROR' => 'FILE_WRITE_ERROR',
        //文件写入错误

        /*----------------------------------*/

        //'BINARY_DATA_GENERATE_ERROR' => 'BINARY_DATA_GENERATE_ERROR',
        //原始二进制数据生成出错

        //'BASE64_DATA_GENERATE_ERROR' => 'BASE64_DATA_GENERATE_ERROR',
        //BASE64格式数据生成出错

        /*----------------------------------*/

        'UNKNOWN_ERROR' => 'UNKNOWN_ERROR' ,
        //未知错误
    );

    /**
     * 构造函数
     * @param string $fileObject 文件对象
     * @param array $config 全局配置项
     */
    public function __construct($fileObject, $config)
    {
        $this->fileObject = $fileObject;
        $this->stateInfo = $this->stateMap['COMPLETE_OK'];
        $this->initialize($config);
        $this->getFileInfo();
        $this->handle();
    }

    /**
     * 初始化配置项
     * @param array $config  配置项
     * @return mixed
     */
    private function initialize($config)
    {
        // 初始化用户配置项到默认配置(用户配置覆盖默认全局配置)
        foreach ($config as $key => $val)
        {
            if (isset($this->config[$key]))
            {
                $this->config[$key] = $val;
            }
        }
        if (!empty($this->config['compress']))
        {
            // 使用用户配置中的全局配置项覆盖默认缩略图配置项
            foreach ($this->config as $key => $val)
            {
                if (array_key_exists($key,$this->compressConfig))
                {
                    $this->compressConfig[$key] = $val;
                }
            }
            // 使用用户缩略图配置项覆盖默认缩略图配置项
            foreach ($this->config['compress'] as $key => $val)
            {
                if (isset($this->compressConfig[$key]))
                {
                    $this->compressConfig[$key] = $val;
                }
            }
        }
        if (!empty($this->config['thumb']))
        {
            // 使用用户配置中的全局配置项覆盖默认缩略图配置项
            foreach ($this->config as $key => $val)
            {
                if (array_key_exists($key,$this->thumbConfig))
                {
                    $this->thumbConfig[$key] = $val;
                }
            }
            // 使用用户缩略图配置项覆盖默认缩略图配置项
            foreach ($this->config['thumb'] as $key => $val)
            {
                if (isset($this->thumbConfig[$key]))
                {
                    $this->thumbConfig[$key] = $val;
                }
            }
        }
        if (!empty($this->config['crop']))
        {
            // 使用用户配置中的全局配置项覆盖默认裁剪配置项
            foreach ($this->config as $key => $val)
            {
                if (array_key_exists($key,$this->cropConfig))
                {
                    $this->cropConfig[$key] = $val;
                }
            }
            // 使用用户裁剪配置项覆盖默认裁剪配置项
            foreach ($this->config['crop'] as $key => $val)
            {
                if (isset($this->cropConfig[$key]))
                {
                    $this->cropConfig[$key] = $val;
                }
            }
        }
    }

    /**
     * 上传错误检查
     * @param $errCode  错误代码
     * @return string
     */
    private function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap['UNKNOWN_ERROR'] : $this->stateMap[$errCode];
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getDataInfo()
    {
        $this->dataInfo['oriMIME'] = $this->oriMIME;
        $this->dataInfo['oriWidth'] = $this->oriWidth;
        $this->dataInfo['oriHeight'] = $this->oriHeight;
        if ($this->stateInfo != 'COMPLETE_OK'){
            $this->dataInfo['stateInfo'] = $this->stateInfo;
        }
        return $this->dataInfo;
    }

    /**
     * 获取文件基本信息
     * @return string
     */
    private function getFileInfo()
    {
        /* 当PHP版本低于5.4版本时getimagesize()可以识别base64格式的数据
            User note in php.net:
                getimagesizefromstring function for < 5.4:
                if (!function_exists('getimagesizefromstring')) {
                    function getimagesizefromstring($string_data)
                    {
                        $uri = 'data://application/octet-stream;base64,' . base64_encode($string_data);
                        return getimagesize($uri);
                    }
                }
        */
        if ($imageInfo = @getimagesize($this->fileObject)){
            $this->handleMode = 'stream';
        }elseif($imageInfo = @getimagesizefromstring($this->fileObject)){
            $this->handleMode = 'string';
        }else{
            $this->stateInfo = $this->getStateInfo('FILE_READ_ERROR');
            return;
        }

        if (is_file($this->fileObject)){
            $this->isFile = true;
        }

        // 在此处同时获取图片的原始宽高
        $this->oriWidth = $imageInfo[0];
        $this->oriHeight = $imageInfo[1];
        $this->oriMIME = $imageInfo['mime'];

        $types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
        $this->realType = $types[$imageInfo[2]];
        return true;
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        $ext = strrchr($this->fileObject , '.' );
        if ($ext){
            return strtolower($ext);
        }else{
            // Linux等系统下PHP上传的临时文件名没有.tmp扩展名，这里为了避开这个问题
            return '.tmp';
        }
    }

    /**
     * 检查文件是否已经存在
     * $param string $action 操作类型
     * @return bool
     */
    private function checkExist($action)
    {
        return file_exists($this->makeTarget($action));
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        // 检查文件类型是否在接受处理的扩展名范围之内
        if ($this->isFile){
            $allowExt = array(".gif",".png",".jpg",".jpeg",".tmp"); //默认接受处理的文件扩展名
            if (!in_array($this->getFileExt(),$allowExt)){
                $this->stateInfo = $this->getStateInfo('FILE_TYPE_LIMIT');
                return;
            }
        }

        // 检测文件真实类型是否为能够处理的图片类型
        if (!$this->realType){
            $this->stateInfo = $this->getStateInfo('UNKNOWN_MIME_TYPE');
            return;
        }
    }

    /**
     * 设置路径格式
     * $param string $path 保存路径原始值
     * @return string
     */
    private function setSavePath($path)
    {
        if ($path != ''){
            return rtrim($path, '/') . '/';
        }else{
            return $path;
        }
    }

    /**
     * 设置保存时的全局默认文件名
     * @return string
     */
    private function setSaveName()
    {
        if ($this->config['newName'] != ''){
            $saveName = $this->config['newName'];
        }else{
            if ($this->isFile){
                $saveName = pathinfo($this->fileObject,PATHINFO_FILENAME);
            }else{
                $saveName = 'IMG_'.date('YmdHis');
            }
        }
        return $saveName;
    }
    /**
     * 设置保存时的输出格式
     * $param string $toType 需要保存的格式配置项
     * @return string
     */
    private function setSaveType($toType)
    {
        switch ($toType)
        {
            case 'auto':
                $saveType = $this->realType();
                break;
            /* 当选项为'ori'时，如果处理的是文件则使用源文件的扩展名
               如果是字符串则使用真实的MIME类型判断输出文件的扩展名 */
            case 'ori':
                if ($this->isFile){
                    $saveType = $this->getFileExt();
                }else{
                    $saveType = $this->realType();
                }
                break;
            case 'gif':
                $saveType = 'gif';
                break;
            case 'jpg':
            case 'jpeg':
                $saveType = 'jpeg';
                break;
            case 'png':
                $saveType = 'png';
                break;
        }
        return $saveType;
    }

    /**
     * 设置保存时的MIME类型
     * $param string $action 操作类型
     * @return string
     */
    private function setSaveMime($action)
    {
        return 'image/'.$this->{$action.'Config'}['toType'];
    }

    /**
     * 设置保存时的扩展名
     * $param string $action 操作类型
     * @return string
     */
    private function setSaveExt($action)
    {
        $exts = array('gif' => '.gif', 'jpeg' => '.jpg', 'png' => '.png');
        return $exts[$this->{$action.'Config'}['toType']];
    }

    /**
     * 生成目标文件路径
     * $param string $action 操作类型
     * @return string
     */
    private function makeTarget($action)
    {
        return $this->{$action.'Config'}['savePath'] . $this->saveName . $this->{$action.'Config'}['suffix'] . $this->setSaveExt($action);
    }

    /**
     * 生成目标文件夹
     * @return bool
     */
    private function makeFolder($action)
    {
        return mkdir($this->{$action.'Config'}['savePath'], 0777, true);
    }

    /**
     * 主处理方法
     * @return mixed
     */
    private function handle()
    {
        // 检查文件类型
        $this->checkType();

        if ($this->stateInfo == $this->stateMap['COMPLETE_OK']){
            // 设置输出文件名(所有图片操作输出共用一个基本文件名+后缀)
            $this->saveName = $this->setSaveName();

            /* 创建压缩图 */
            if (!empty($this->config['compress'])){
                $this->handleData('compress');
            }
            
            /* 创建缩略图 */
            if (!empty($this->config['thumb'])){
                $this->handleData('thumb');
            }

            /* 创建裁剪图像 */
            if (!empty($this->config['crop'])){
                $this->handleData('crop');
            }
        }
    }

    /**
     * 生成压缩图
     * @return string
     */
    private function makeCompress()
    {
        // 源图数据
        $srcImage = $this->openFile();

        /* 按照比例确定宽高基准生成指定尺寸的缩略图 */
        $oriWidth = $this->oriWidth;
        $oriHeight = $this->oriHeight;

        $dstImage = imagecreatetruecolor($oriWidth,$oriHeight);
        $backgroundColor = imagecolorallocate($dstImage, 255, 255, 255);
        imagefill($dstImage, 0, 0, $backgroundColor);

        if ($this->compressConfig['toType'] == 'png'){
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $oriWidth, $oriHeight, $oriWidth, $oriHeight);

        $newImage = $this->generateFile($dstImage,'compress');

        imagedestroy($dstImage);
        imagedestroy($srcImage);

        return $newImage;
    }
    
    /**
     * 生成缩略图
     * @return string
     */
    private function makeThumb()
    {
        // 源图数据
        $srcImage = $this->openFile();

        /* 按照比例确定宽高基准生成指定尺寸的缩略图 */
        $oriWidth = $this->oriWidth;
        $oriHeight = $this->oriHeight;
        $oriRatio = $oriWidth / $oriHeight;
        $limitWidth = $this->thumbConfig['width'];
        $limitHeight = $this->thumbConfig['height'];
        $limitRatio = $limitWidth / $limitHeight;

        if ($limitRatio > $oriRatio) {
            $srcHeight = $limitHeight;
            $srcWidth = $oriRatio * $limitHeight;
        } else {
            $srcHeight = $limitWidth / $oriRatio;
            $srcWidth = $limitWidth;
        }

        $this->dataInfo['thumbWidth'] = (int)floor($srcWidth);
        $this->dataInfo['thumbHeight'] = (int)floor($srcHeight);

        $dstImage = imagecreatetruecolor($srcWidth,$srcHeight);
        $backgroundColor = imagecolorallocate($dstImage, 255, 255, 255);
        imagefill($dstImage, 0, 0, $backgroundColor);

        if ($this->thumbConfig['toType'] == 'png'){
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $srcWidth, $srcHeight, $oriWidth, $oriHeight);

        $newImage = $this->generateFile($dstImage,'thumb');

        imagedestroy($dstImage);
        imagedestroy($srcImage);

        return $newImage;
    }

    /**
     * 裁剪图像
     * @return string
     */
    private function makeCrop()
    {
        // 源图数据
        $srcImage = $this->openFile();

        /* 按照比例确定宽高基准生成指定尺寸的缩略图 */
        $oriWidth = $this->oriWidth;
        $oriHeight = $this->oriHeight;
        $axisX = $this->cropConfig['axisX'];
        $axisY = $this->cropConfig['axisY'];
        $axisW = $this->cropConfig['axisW'];
        $axisH = $this->cropConfig['axisH'];

        if ($this->cropConfig['width'] && $this->cropConfig['height']){
            $cropRatio = $axisW / $axisH;
            $limitWidth = $this->cropConfig['width'];
            $limitHeight = $this->cropConfig['height'];
            $limitRatio = $limitWidth / $limitHeight;
            // 设定尺寸限制基准
            if ($limitRatio > $cropRatio) {
                $srcHeight = $limitHeight;
                $srcWidth = $cropRatio * $limitHeight;
            } else {
                $srcHeight = $limitWidth / $cropRatio;
                $srcWidth = $limitWidth;
            }
        }else{
            $srcHeight = $axisW;
            $srcWidth = $axisH;
        }

        $this->dataInfo['cropWidth'] = (int)floor($srcWidth);
        $this->dataInfo['cropHeight'] = (int)floor($srcHeight);

        $dstImage = imagecreatetruecolor($srcWidth,$srcHeight);
        $backgroundColor = imagecolorallocate($dstImage, 255, 255, 255);
        imagefill($dstImage, 0, 0, $backgroundColor);

        if ($this->cropConfig['toType'] == 'png'){
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }
        imagecopyresampled($dstImage, $srcImage, 0, 0, $axisX, $axisY, $srcWidth, $srcHeight, $axisW, $axisH);

        $newImage = $this->generateFile($dstImage,'crop');

        imagedestroy($dstImage);
        imagedestroy($srcImage);

        return $newImage;
    }

    /**
     * 处理最终图像的返回信息
     * $param string $action 操作类型
     * @return mixed
     */
    private function handleData($action)
    {
        $thisConfig =& $this->{$action.'Config'};

        // 将配置转化为合法可用值
        $thisConfig['toType'] = $this->setSaveType($thisConfig['toType']);
        $thisConfig['savePath'] = $this->setSavePath($thisConfig['savePath']);

        $imageData = $this->{'make'.$action}();

        $this->dataInfo[$action.'Size'] = strlen($imageData);
        $this->dataInfo[$action.'Type'] = $thisConfig['toType'];
        $this->dataInfo[$action.'MIME'] = $saveMIME = $this->setSaveMime($action);

        // 写入文件数据到目录
        if ($thisConfig['savePath'] != ''){
            $this->dataInfo[$action.'Path'] = $this->saveFile($imageData,$action);
        }

        // 保存原始文件数据到返回数组
        if ($thisConfig['returnData']){
            $this->dataInfo[$action.'Data'] = $imageData;
        }

        // 保存Base64格式数据到返回数组
        if ($thisConfig['returnBase64']){
            if ($thisConfig['returnBase64'] === 'ori'){
                $this->dataInfo[$action.'Base64Data'] = base64_encode($imageData);
            }
            if ($thisConfig['returnBase64'] === 'src'){
                $this->dataInfo[$action.'Base64Data'] = 'data:'.$saveMIME.';base64,' . base64_encode($imageData);
            }
        }
    }

    /**
     * 打开源图像
     * @return string
     */
    private function openFile()
    {
        $file = $this->fileObject;

        // 根据源文件真实格式生成对应图像源文件
        if ($this->handleMode == 'stream'){
            $createMode = 'imagecreatefrom'.$this->realType;
            return $createMode($file);
        }
        if ($this->handleMode == 'string'){
            return imagecreatefromstring($file);
        }
    }

    /**
     * 产生处理后的图像
     * $param string $data 要处理的图像数据
     * $param string $action 操作类型
     * @return string
     */
    private function generateFile($data,$action)
    {
        ob_start();
        // 输出缩略图数据
        if ($this->{$action.'Config'}['toType'] != 'jpeg'){
            $OutputMode = 'image'.$this->{$action.'Config'}['toType'];
            $OutputMode($data);
        }else{
            imagejpeg($data,null,$this->{$action.'Config'}['quality']);
        }
        return ob_get_clean();
    }

    /**
     * 输出文件到文件系统
     * $param string $data 要处理的图像数据
     * $param string $action 操作类型
     * @return mixed
     */
    private function saveFile($data,$action)
    {
        // 生成最终的目标位置
        $saveTarget = $this->makeTarget($action);

        // 处理目录文件夹
        if (!file_exists($this->{$action.'Config'}['savePath'])){
            if (!$this->makeFolder($action)){
                return $this->getStateInfo('FOLDER_MAKE_FAILED');
            }
        }

        // 判断是否有重名文件
        if ($this->checkExist($action)){
            return $this->getStateInfo('FILE_NAME_EXIST');
        }

        // 写入数据
        if (!file_put_contents($saveTarget, $data)){
            return $this->getStateInfo('FILE_WRITE_ERROR');
        }else{
            return realpath($saveTarget);
        }
    }
}