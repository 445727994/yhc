<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-15 23:57:59
 */
namespace app\admin\controller;
use think\Controller;

class Classcount extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function index() {
		if (request()->isAjax()) {
			$page = (int) input('page') ? (int) input('page') : 1;
			$page_size = (int) input('limit') ? (int) input('limit') : 10;
			$model_name = "\\app\\admin\\model\\Information";
			$model = new $model_name();
			$where = $this->get_where();
			$order = '';
			$field = '*,sum(case when ps_number >0 then 1 else 0 end) as ps_num,
			sum(case when ps_number >0 then 0 else 1 end) as ps_not';
			if (!empty(input('field')) && !empty(input("order"))) {
				$order = input("field") . " " . input('order');
			}
			$list = $model->group_list($where, 'class,cj_time,times', $field, $order, $page, $page_size);
			return $list;
		}
		return $this->fetch();
	}
	//重定义导出
	public function export() {
		if (request()->isAjax()) {
			$page = (int) input('page') ? (int) input('page') : 1;
			$page_size = (int) input('limit') ? (int) input('limit') : 10;
			$model_name = "\\app\\admin\\model\\Information";
			$model = new $model_name();
			$where = $this->get_where();
			$order = '';
			$field = '*,sum(case when ps_number >0 then 1 else 0 end) as ps_num,sum(case when ps_number >0 then 0 else 1 end) as ps_not';
			if (!empty(input('field')) && !empty(input("order"))) {
				$order = input("field") . " " . input('order');
			}
			$list = $model->export_group($where, 'class,cj_time,times', $field, $order);
			return $list;
		}
		return $this->fetch();
	}
}
