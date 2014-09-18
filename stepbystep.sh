#!/bin/bash

step1() {
    wget -r -c -np -L -P ./2013 http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/index.html
}

step2() {
    mv ./2013/www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2013/* ./2013/
}

step3() {
    php getdata.php
}

step1 && step2 && step3
