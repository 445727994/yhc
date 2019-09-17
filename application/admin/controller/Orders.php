<?php
/*
 * @auther 萤火虫 
 * @email  445727994@qq.com
 * @create_time 2019-05-19 20:23:49
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Orders extends Base{
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
  	public function _befor_index(&$where, &$join, &$field) {
		$join[] = ['information i', 'a.openid=i.openid'];
		$field = 'a.*,i.user_name,i.usercard,i.ps_number,i.schoolname,i.departmentname,i.class,i.major,a.id as id';
	}
	public function _end_search(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['uptime'] =date('Y-m-d H:i:s',$data[$key]['uptime']);
			
		}
		$list['data'] = $data;
	}

}
