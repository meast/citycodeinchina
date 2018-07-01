<?php
/*
    charset:utf-8
    @author:meast
    @date:2014-09-19
    @usage:
        1. Rec. download datas from http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html by using wget
            wget -r -c -np -L http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html
        2. mv all files in download directory include child directory and files to directory 2017 in current directory.
        3. create database and table: areas (itemid,cityname,citycode,parentid,nodelevel,nodepath,citycate).
        4. modify the value of $dbconf in this file.(using pdo).
        5. run this program to save datas into database.
    @requirement:
        1. ext mbstring and pdo(and the pdo_[DB] in your config) must be loaded.
    # 1. 省, 2. 地级市, 3. 县级市/区/直筒子镇, 4. 街道/镇, 5. 社区/村
    # 直筒子市:
        省, 地级市, 镇, 社区/村
        (广东省东莞市 4419, 广东省中山市 4420, 海南省三沙市 4603, 海南省儋州市 4604, 甘肃省嘉峪关市 6202 )
        特别处理:
            4419,4420,4604
        其中,
            海南省三沙市 4603 下辖先按岛划分 符合默认规则, 不需要特殊处理 
            甘肃省嘉峪关市 6202 有单独的一个市辖区 符合默认规则, 可以不做特殊处理
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

$datadir = __DIR__ . '/2017';

# 省份文件
$f1 = $datadir . '/index.html';
getcodes1($f1);

$arr2 = glob($datadir . '/*.html');
$arr3 = glob($datadir . '/*/*.html');
$arr4 = glob($datadir . '/*/*/*.html');
$arr5 = glob($datadir . '/*/*/*/*.html');

foreach($arr2 as $k => $v)
{
    if($v != $datadir . '/index.html')
    {
        getcodes1($v, 1);
    }
}

foreach($arr3 as $k => $v)
{
    if($v != $datadir . '/index.html')
    {
        getcodes1($v, 2);
    }
}

foreach($arr4 as $k => $v)
{
    if($v != $datadir . '/index.html')
    {
        getcodes1($v, 3);
    }
}

foreach($arr5 as $k => $v)
{
    if($v != $datadir . '/index.html')
    {
        getcodes1($v, 4);
    }
}



function getcodes1($file, $node = 0)
{
    $arrspecial = array('4419', '4420', '4604', '6202');
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
        $nodelv = 1;
        if($mar)
        {
            foreach($matches as $k => $v)
            {
                $isspecial = false;
                if($p['filename'] == 'index')
                {
                    $v['parentid'] = 0;
                    $v['nodepath'] = '0';
                }else{
                    $v['parentid'] = $p['filename'];
                    $prefix = $p['filename'];
                    if(strlen($p['filename']) >= 4) {
                        $prefix = substr($p['filename'], 0, 4);
                        if(in_array($prefix, $arrspecial)) {
                            $isspecial = true;
                        }
                    }
                    switch(strlen($p['filename']))
                    {
                        case 9:
                            # 镇街文件,读取村委社区数据
                            $nodelv = 5;
                            if($isspecial) {
                                $nodelv = 4;
                            }
                            break;
                        case 6:
                            # 县区文件,读取镇街数据
                            $nodelv = 4;
                            break;
                        case 4:
                            # 地级市文件,读取县区数据
                            $nodelv = 3;
                            break;
                        case 2:
                            # 省级文件,读取地级市数据
                            $nodelv = 2;
                            break;
                        default:
                            break;
                    }
                }
                $v['nodepath'] = getnodepath($v['citycode'], $nodelv, $isspecial);
                $v['citycode'] = formatcitycode($v['citycode'], $nodelv, $isspecial);
                if($v['parentid'] > 0)
                    $v['parentid'] = formatcitycode($v['parentid'], ($nodelv - 1), $isspecial);
                $v['nodelevel'] = $nodelv;
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

function formatcitycode($citycode, $nodelv = 1, $isspecial = false)
{
    if($nodelv < 4)
    {
        if(strlen($citycode) <= 6)
        {
            return str_pad($citycode, 6, '0', STR_PAD_RIGHT);
        }else{
            if($isspecial && $nodelv >= 3) {
                if(strlen($citycode) >= 9) {
                    return substr($citycode, 0, 9);
                } else {
                    return str_pad($citycode, 9, '0', STR_PAD_RIGHT);
                }
            }
            return substr($citycode, 0, 6);
        }
    }else{
        if(strlen($citycode) <= 12)
        {
            return str_pad($citycode, 12, '0', STR_PAD_RIGHT);
        }
    }
    return $citycode;
}


function getnodepath($citycode, $nodelv, $isspecial = false)
{
    if($nodelv > 1)
    {
        $nodepath = '';
        switch($nodelv)
        {
            case 5:
                # 村委社区
                $nodepath = '0,' . substr($citycode, 0, 2) . '0000,' . substr($citycode, 0, 4) . '00,' . substr($citycode, 0, 6) . ',' . substr($citycode, 0, 9) . '000';
                break;
            case 4:
                # 镇街/直筒子村
                $nodepath = '0,' . substr($citycode, 0, 2) . '0000,' . substr($citycode, 0, 4) . '00,' . substr($citycode, 0, 6);
                if($isspecial) {
                    $nodepath = '0,' . substr($citycode, 0, 2) . '0000,' . substr($citycode, 0, 4) . '00,' . substr($citycode, 0, 9);
                }
                break;
            case 3:
                # 县区/直筒子镇
                $nodepath = '0,' . substr($citycode, 0, 2) . '0000,' . substr($citycode, 0, 4) . '00';
                break;
            case 2:
                # 地级市
                $nodepath = '0,' . substr($citycode, 0, 2) . '0000';
                break;
            default:
                break;
        }
        return $nodepath;
    }else{
        # nodelevel=1,means province.
        return 0;
    }
}
