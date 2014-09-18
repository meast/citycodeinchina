citycodeinchina
===============

A method to get the statistic usage city code publish by www.stats.gov.cn

step:
  1. create database and table areas
  2. modify the $dbconf in the getdata.php.(the db operation using pdo driver)
  3. modify the stepbystep.sh ,correct the path of php if your php is not in $PATH
  4. run the stepbystep.sh
