citycodeinchina
===============

A method to get the statistic usage city code publish by www.stats.gov.cn

step:
  1. create database and table areas
  2. modify the $dbconf in the getdata.php.(the db operation using pdo driver)
  3. download all the pages : wget -r -c -np -L -P ./2013 http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/index.html
  4. move the downloaded pages : mv ./2013/www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/* ./2013/
  5. get datas from pages : php getdata.php
