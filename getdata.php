<?php
/*
    charset:utf-8
    @author:meast
    @date:2014-09-19
    @usage:
        1. Rec. download datas from http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/index.html by using wget
            wget -r -c -np -L http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/index.html
        2. mv all files in download directory include child directory and files to directory 2013 in current directory.
        3. create database and table: areas (itemid,cityname,citycode,parentid,nodepath,citycate).
        4. modify the value of $dbconf in this file.(using pdo).
        5. run this program to save datas into database.
    @requirement:
        1. ext mbstring and pdo(and the pdo_[DB] in your config) must be loaded.
*/
include __DIR__ . '/areas.php';

if(!extension_loaded('mbstring'))
{
    exit('mbstring is require!check your php.ini pls.');
}
if(!extension_loaded('pdo'))
{
    exit('pdo is require!check your php.ini pls.');
}

$dbconf = array('default' => 
    array('dsn' => 'sqlite:' . dirname(__FILE__) . '/areas.db', 'username' => '', 'password' => '')
);
areas::$dbconf = $dbconf;


# 省份文件
$f1 = __DIR__ . '/2013/index.html';
getcodes1($f1);

$arr2 = glob(__DIR__ . '/2013/*.html');
$arr3 = glob(__DIR__ . '/2013/*/*.html');
$arr4 = glob(__DIR__ . '/2013/*/*/*.html');
$arr5 = glob(__DIR__ . '/2013/*/*/*/*.html');

foreach($arr2 as $k => $v)
{
    if($v != __DIR__ . '/2013/index.html')
    {
        getcodes1($v);
    }
}

foreach($arr3 as $k => $v)
{
    if($v != __DIR__ . '/2013/index.html')
    {
        getcodes1($v);
    }
}

foreach($arr4 as $k => $v)
{
    if($v != __DIR__ . '/2013/index.html')
    {
        getcodes1($v);
    }
}

foreach($arr5 as $k => $v)
{
    if($v != __DIR__ . '/2013/index.html')
    {
        getcodes1($v);
    }
}



function getcodes1($file)
{
    $pattern1 = '|<a\shref=\'(?<citycode>\d+)\.html\'>(?<cityname>.+?)(<br/>)?</a>|'; # <br/> tag exists in the 2013/index.html only.
    $pattern2 = '|<tr.+?><td><a\shref=\'.+?\'>(?<citycode>\d+)</a></td><td><a\shref=\'.+?\'>(?<cityname>.+?)</a></td></tr>|';
    $pattern3 = '|<tr.+?><td>(?<citycode>\d+)</td><td>(?<citycate>\d+)</td><td>(?<cityname>.+?)</td></tr>|';
    if(file_exists($file))
    {
        $p = pathinfo($file);
        $str1 = file_get_contents($file);
        $str1 = mb_convert_encoding($str1, 'utf-8', 'gbk');
        $pattern = '';
        if(strlen($p['filename']) > 6)
        {
            # 村委 社区，有个"城乡分类"的
            $pattern = $pattern3;
        }else{
            if(strlen($p['filename']) == '5')
                $pattern = $pattern1; # index.html
            else
                $pattern = $pattern2;
        }
        
        $mar = preg_match_all($pattern, $str1, $matches, PREG_SET_ORDER);
        if($mar)
        {
            foreach($matches as $k => $v)
            {
                if($p['filename'] == 'index')
                {
                    $v['parentid'] = 0;
                    $v['nodepath'] = '0';
                }else{
                    $v['parentid'] = $p['filename'];
                    $p1 = pathinfo($p['dirname']);
                    $p2 = pathinfo($p1['dirname']);
                    $p3 = pathinfo($p2['dirname']);
                    $p4 = pathinfo($p3['dirname']);
                    switch(strlen($p['filename']))
                    {
                        case 9:
                            # 村委 社区
                            $v['nodepath'] = '0,' . formatcitycode($p3['filename']) . ',' . formatcitycode($p2['filename']) . ',' . formatcitycode($p1['filename']) . ',' . formatcitycode($p['filename']);
                        case 6:
                            # 镇街
                            $v['nodepath'] = '0,' . formatcitycode($p2['filename']) . ',' . formatcitycode($p1['filename']) . ',' . formatcitycode($p['filename']);
                            break;
                        case 4:
                            # 县区
                            $v['nodepath'] = '0,' . formatcitycode($p1['filename']) . ',' . formatcitycode($p['filename']);
                            break;
                        case 2:
                            # 地级市,节点为 0,省级编号
                            $v['nodepath'] = '0,' . formatcitycode($p['filename']);
                            break;
                        default:
                            break;
                    }
                }
                $v['citycode'] = formatcitycode($v['citycode']);
                if($v['parentid'] > 0)
                    $v['parentid'] = formatcitycode($v['parentid']);
                
                $areas = new areas();
                $c = $areas -> where('citycode=' . $v['citycode']) -> fOne('count(*)');
                if($c == 0)
                {
                    $id = $areas -> insert($v);
                    echo $id . ':citycode is ' . $v['citycode'] . PHP_EOL;
                }else{
                    echo 'exists citycode ' . $v['citycode'] . PHP_EOL;
                }
            }
        }else{
            echo 'no data match' . PHP_EOL;
        }
    }else{
        echo 'file not exists' . PHP_EOL;
    }
}

function formatcitycode($citycode)
{
    if(strlen($citycode) < 6)
    {
        return str_pad($citycode, 6, '0', STR_PAD_RIGHT);
    }
    return $citycode;
}
