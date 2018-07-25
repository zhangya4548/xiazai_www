<?php
/**
 * Created by PhpStorm.
 * User: zhangguangqiang
 * Date: 2018/7/24
 * Time: 上午11:01
 */
require __DIR__ . '/vendor/autoload.php';

$api = new \Lib\ApiService();

$param_arr = getopt('w:n:');
if (true === empty($param_arr['w']))
{
    die('参数错误,示例: php index.php -w http://www.baidu.com -n index.html');
}


if (true === empty($param_arr['n']))
{
    die('参数错误,示例: php index.php -w http://www.baidu.com -n index.html');
}

$name    = $param_arr['n'];
$url     = $param_arr['w'];
$wwwInfo = parse_url($url);
$scheme  = $wwwInfo['scheme'] . '://';

//创建默认文件夹
$host      = $wwwInfo['host'];
$indexName = $host . '/' . $name;
$cssDir    = $host . '/css';
$jsDir     = $host . '/js';
$imagesDir = $host . '/images';
Directory($cssDir);
Directory($jsDir);
Directory($imagesDir);


$method  = 'get';
$headers = [
    'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language'           => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7,ja;q=0.6',
    'Cache-Control'             => 'max-age=0',
    'Connection'                => 'keep-alive',
    // 'Cookie'                    => 'VPTimeZone=Asia%2FChongqing; gr_user_id=4b3c21dd-ff41-4c6d-99d0-0ac9998e511f;',
    // 'Host'                      => 'www.xxx.com',
    'Upgrade-Insecure-Requests' => '1',
    'User-Agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',
];


$params = [];

$res = $api->request($method, $url, $headers, $params);

if ((int)$res['statusCode'] !== 200)
{
    die('获取html异常');
}

$html = $res['raw'];

//生成页面
file_put_contents($indexName, $html);

//获取本地页面
$html = file_get_contents($indexName);

//生成css
$html = createCss($cssDir, $imagesDir, $api, $headers, $html, $scheme, $host);

//取出所有href连接
preg_match_all('#src="(.*?)"#is', $html, $result);
$srcArr = $result[1] ?? [];

//生成js
$html = createJs($srcArr, $jsDir, $api, $headers, $html, $scheme, $host);


//生成img
$html = createImg($srcArr, $imagesDir, $api, $headers, $html, $scheme, $host);

file_put_contents($indexName, $html);

die('下载完成');


//生成img
function createImg($srcArr, $imagesDir, $api, $headers, $html, $scheme, $host)
{
    if (false === empty($srcArr))
    {
        foreach ($srcArr as $k => $v)
        {
            if (!substr_count($v, '.gif') && !substr_count($v, '.jpg') && !substr_count($v, '.jpeg') && !substr_count($v, '.png'))
            {
                continue;
            }

            //待替换标记
            $pPash = $v;


            if(substr($v,0,2) === '//'){
                $v = 'http:' . $v;
            }

            if (!substr_count($v, $scheme))
            {
                $v = $scheme . $host . $v;
            }

            //如果文件名有版本号参数的要去掉
            //http://static.dota2.vpgcdn.com/css/bootstrap.min.css?v=201807191714
            if (substr_count($v, '?'))
            {
                $tmp = explode('?', $v);
                $v   = $tmp[0];
            }

            //img文件名
            $imgName = basename($v);

            //生成地址
            $imgPash = $imagesDir . '/' . $imgName;

            //生成文件
            $imgInfo = $api->request('get', $v, $headers);
            if ((int)$imgInfo['statusCode'] !== 200)
            {
                die('获取' . $pPash . '异常' . $v);
            }
            file_put_contents($imgPash, $imgInfo['raw']);

            //替换img路径
            $rePash = './images/' . $imgName;
            $html   = str_replace($pPash, $rePash, $html);
        }
    }
    return $html;
}

//生成js
function createJs($srcArr, $jsDir, $api, $headers, $html, $scheme, $host)
{
    if (false === empty($srcArr))
    {
        foreach ($srcArr as $k => $v)
        {
            if (!substr_count($v, '.js'))
            {
                continue;
            }

            //待替换标记
            $pPash = $v;

            //如果文件名有版本号参数的要去掉
            //http://static.dota2.vpgcdn.com/css/bootstrap.min.css?v=201807191714
            if (substr_count($v, '?'))
            {
                $tmp = explode('?', $v);
                $v   = $tmp[0];
            }

            if(substr($v,0,2) === '//'){
                $v = 'http:' . $v;
            }else{
                if (!substr_count($v, $scheme))
                {
                    $v = $scheme . $host . $v;
                }
            }




            //js文件名
            $jsName = basename($v);

            //生成地址
            $jsPash = $jsDir . '/' . $jsName;

            //生成文件
            $jsInfo = $api->request('get', $v, $headers);
            if ((int)$jsInfo['statusCode'] !== 200)
            {
                die('获取' . $pPash . '异常' . $v);
            }
            file_put_contents($jsPash, $jsInfo['raw']);

            //替换js路径
            $rePash = './js/' . $jsName;
            $html   = str_replace($pPash, $rePash, $html);
        }
    }
    return $html;
}

//生成css
function createCss($cssDir, $imagesDir, $api, $headers, $html, $scheme, $host)
{
    //取出所有href连接
    preg_match_all('#href="(.*?)"#is', $html, $result);
    $cssArr = $result[1] ?? [];

    if (false === empty($cssArr))
    {
        foreach ($cssArr as $k => $v)
        {
            if (!substr_count($v, '.css'))
            {
                continue;
            }

            //待替换标记
            $pPash = $v;

            //如果文件名有版本号参数的要去掉
            //http://static.dota2.vpgcdn.com/css/bootstrap.min.css?v=201807191714
            if (substr_count($v, '?'))
            {
                $tmp = explode('?', $v);
                $v   = $tmp[0];
            }


            if(substr($v,0,2) === '//'){
                $v = 'http:' . $v;
            }else{
                if (!substr_count($v, $scheme))
                {
                    $v = $scheme . $host . $v;
                }
            }

            //css文件名
            $cssName = basename($v);

            //生成地址
            $cssPash = $cssDir . '/' . $cssName;

            //生成文件
            $cssInfo = $api->request('get', $v, $headers);
            if ((int)$cssInfo['statusCode'] !== 200)
            {
                die('获取' . $cssName . '异常' . $v);
            }

            //取出所有图片连接
            preg_match_all('#url\((.*?)\)#is', $cssInfo['raw'], $resultB);
            if ($resultB[1])
            {
                $tmpB      = '';
                $tmpC      = '';
                $tmpD      = '';
                $imgPash   = '';
                $imgInfo   = '';
                $reImgPash = '';
                foreach ($resultB[1] as $kk => $vv)
                {
                    $tmpB = $vv;

                    $tmpD = basename($vv);
                    //生成地址
                    $imgPash = $imagesDir . '/' . $tmpD;


                    if (!substr_count($vv, $scheme))
                    {
                        $vv = $scheme . $host . '/' . $vv;
                    }

                    //生成文件
                    $imgInfo = $api->request('get', $vv, $headers);
                    if ((int)$imgInfo['statusCode'] === 200)
                    {
                        //die('获取' . $tmpD . '异常');
                        file_put_contents($imgPash, $imgInfo['raw']);

                        //替换css路径
                        $reImgPash      = '../images/' . $tmpD;
                        $cssInfo['raw'] = str_replace($tmpB, $reImgPash, $cssInfo['raw']);
                    }
                }

                //var_dump($cssInfo['raw'],$resultB[1]);die();
            }

            file_put_contents($cssPash, $cssInfo['raw']);

            //替换css路径
            $rePash = './css/' . $cssName;
            $html   = str_replace($pPash, $rePash, $html);

            // var_dump(explode('/',$v));
            //$html = str_replace();

        }
    }
    return $html;
}


//新建文件夹
function Directory($dir)
{

    return is_dir($dir) or Directory(dirname($dir)) and mkdir($dir, 0755);

}
