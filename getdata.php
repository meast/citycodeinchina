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
        $nodelv = 1;
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
                    switch(strlen($p['filename']))
                    {
                        case 9:
                            # 镇街文件,读取村委社区数据
                            $nodelv = 5;
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
                $v['nodepath'] = getnodepath($v['citycode'], $nodelv);
                $v['citycode'] = formatcitycode($v['citycode'], $nodelv);
                if($v['parentid'] > 0)
                    $v['parentid'] = formatcitycode($v['parentid'], ($nodelv - 1));
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

function formatcitycode($citycode, $nodelv = 1)
{
    if($nodelv < 4)
    {
        if(strlen($citycode) <= 6)
        {
            return str_pad($citycode, 6, '0', STR_PAD_RIGHT);
        }else{
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


function getnodepath($citycode, $nodelv)
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
                # 镇街
                $nodepath = '0,' . substr($citycode, 0, 2) . '0000,' . substr($citycode, 0, 4) . '00,' . substr($citycode, 0, 6);
                break;
            case 3:
                # 县区
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
