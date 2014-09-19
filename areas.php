<?php
/*
    charset:utf-8
*/
include __DIR__ . '/orm_base.php';
class areas extends Orm_Base
{
    public $table = 'areas';
    public $pk = 'itemid';
    public $field = array(
        'itemid' => array('type' => 'int', 'comment' => '主键编号'),
        'cityname' => array('type' => 'int', 'comment' => '城市名称'),
        'citycode' => array('type' => 'int', 'comment' => '城市编号'),
        'parentid' => array('type' => 'int', 'comment' => '父级编号'),
        'nodelevel' => array('type' => 'int', 'comment' => '层次深度'),
        'nodepath' => array('type' => 'text', 'comment' => '层次序列'),
        'citycate' => array('type' => 'text', 'comment' => '城市分类'),
    );
}