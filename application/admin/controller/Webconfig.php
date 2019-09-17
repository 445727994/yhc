<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-05 16:58:28
 */
namespace app\admin\controller;
use think\Controller;

class Webconfig extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_edit(&$data) {

	}
	public function _befor_update(&$data) {
		$data['sex'] = isset($data['sex']) ? 1 : 0;
	}
	public function _befor_insert(&$data) {
		$data['is_limit'] = isset($data['is_limit']) ? 1 : 0;
	}
}
