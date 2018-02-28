<?php
require ('imgGo.class.php');

// 显示文件的基本信息
var_dump($_FILES['userfile']);

$config = array(
    'toType' => 'jpg',              //默认输出类型,可选值：'auto'(根据真实MIME转换)、'ori'(使用源文件扩展名类型转换)、'jpg'、'png'、'gif'
    'savePath' => './ImageSave',    //默认文件保存目录
    'returnData' => true,           //默认是否返回文件原始数据
    'returnBase64' => 'ori',        //默认是否返回Base64格式数据,可选值：false、'ori'(原始数据)、'src'(包含MIME)
    'newName' => 'New Image',       //新文件名
    'maxSize' => 1000000,              //限制文件处理最大值(单位KB)
    'compress' => array(
        'suffix' => '.compressed',      //压缩图后缀名
        'quality' => 90,                //生成压缩图的品质(1 to 100).品质越高,图像文件越大
        'toType' => 'jpg',               //单独设置(覆盖全局设置)
        'savePath' => './ImageSave',             //单独设置(覆盖全局设置)
        'returnData' => false,           //单独设置(覆盖全局设置)
        'returnBase64' => 'src',         //单独设置(覆盖全局设置)
    ),
    'thumb' => array(               //生成缩略图，默认array()不操作，如果开启则直接传递配置数组，默认值为$thumbConfig
        'suffix' => '.thumb',           //缩略图后缀名
        'width' => 128,                 //生成缩略图的宽度
        'height' => 128,                //生成缩略图的高度
        'quality' => 90,                //生成缩略图的品质(1 to 100).品质越高,图像文件越大
        'toType' => 'png',              //单独设置(覆盖全局设置)
        'savePath' => './ImageSave',       //单独设置(覆盖全局设置)
        'returnData' => false,          //单独设置(覆盖全局设置)
        'returnBase64' => 'src',        //单独设置(覆盖全局设置)
    ),
    'crop' => array(                //生成裁剪，默认array()不操作，如果开启则直接传递配置数组，默认值为$cropConfig
        'suffix' => '.crop',            //裁剪后后缀名
        'axisX' => '200',               //裁剪左上角坐标X
        'axisY' => '200',               //裁剪左上角坐标Y
        'axisW' => '320',               //裁剪左上角坐标X
        'axisH' => '240',               //裁剪左上角坐标Y
        'width' => 128,                 //裁剪后图像的宽度
        'height' => 128,                //裁剪后图像的高度
        'quality' => 90,                //裁剪后图像的品质(1 to 100).品质越高,图像文件越大
        'toType' => 'jpg',              //单独设置(覆盖全局设置)
        'savePath' => './ImageSave',       //单独设置(覆盖全局设置)
        'returnData' => false,          //单独设置(覆盖全局设置)
        'returnBase64' => 'src',        //单独设置(覆盖全局设置)
    )
);


$imgGo = new imgGo($_FILES['userfile']['tmp_name'],$config);
//$imgGo = new imgGo('http://localhost/Test/FileUpload/image.php?id=130',$config);

$info = $imgGo->getDataInfo();

var_dump($info);


// 必须返回格式为src的base64数据才能直接在<img>标签中使用
echo '<img src="'.$info['thumbBase64Data'].'" alt="Thumbnail">';
echo '<img src="'.$info['cropBase64Data'].'" alt="Thumbnail">';

/* 必须返回格式为ori的base64数据才能decode
header('Content-type: image/jpeg');
echo base64_decode($info['thumbBase64Data']);
*/