citycodeinchina
===============

A method to get the statistic usage city code publish by www.stats.gov.cn

step:
  1. create database and table areas
  2. modify the $dbconf in the getdata.php.(the db operation using pdo driver)
  3. download all the pages : wget -r -c -np -L -P ./2017 http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html
  4. move the downloaded pages : mv ./2017/www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/* ./2017/
  5. get datas from pages : php getdata.php
  
table:
    areas (itemid,cityname,citycode,parentid,nodelevel,nodepath,citycate)
    
