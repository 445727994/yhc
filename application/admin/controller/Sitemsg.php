<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-09-03 00:18:18
 */
namespace app\admin\controller;
use think\Controller;

class Sitemsg extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_insert(&$data) {
		if (isset($data['create_time'])) {
			$data['create_time'] = strtotime($data['create_time']);
		}
	}
	public function user_enter() {
		if (request()->isAjax()) {
			$page = 1;
			$page_size = 5;
			if (method_exists($this, '_change_name')) {
				$m_name = $this->_change_name();
			}
			$m_name = isset($m_name) ? $m_name : $this->controller_name;
			$model_name = "\\app\\admin\\model\\" . $m_name;

			$model = new $model_name();
			$where = $this->get_where();
			$order = '';
			if (!empty(input('field')) && !empty(input("order"))) {
				$order = input("field") . " " . input('order');
			}
			$join = array();
			$field = '';
			$list = $model->get_list_limit($where, $field, $order, 5);
			$this->addlog('访问列表页,页数:' . $page);
			if (method_exists($this, '_end_search')) {
				$this->_end_search($list);
			}
			return $list;
		}
		return $this->fetch('enter');
	}

}
