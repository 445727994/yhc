<?php
namespace app\admin\controller;
use app\admin\model\Admin as A;
use think\Controller;
use think\Db;

/*
Auther :  萤火虫
email  : 445727994@qq.com
 */
class Admin extends Base {
	protected $user_id;
	protected $Lang;
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_update(&$data) {
		$data['salt'] = md5(uniqid(microtime(true), true));
		$data['password'] = md5(md5($data['password']) . sha1($data['salt']));
	}
	public function _befor_insert(&$data) {
		$data['salt'] = md5(uniqid(microtime(true), true));
		$data['password'] = md5(md5($data['password']) . sha1($data['salt']));
	}
	public function role() {
		$this->csrf_token();
		$id = input("id");
		if (!$id) {
			return false;
		}
		if (request()->isAjax()) {
			$authids = input("authids");
			$res = Db::name("admin")->where("id=" . input("id"))->update(['role' => json_encode($authids, true)]);
			if ($res) {
				return $this->return_msg('suc');
			} else {
				return $this->return_msg("error");
			}
		}
		$admin = new Admin();
		$this->assign('info', A::get($id));
		return $this->fetch();
	}
	public function get_menu() {
		$id = input("id");
		if (!$id) {
			return false;
		}
		$menu = Db::name('menu')->field("id ,title as name,pid")->all();
		$checked = Db::name('admin')->where("id=" . $id)->value("role");
		$checked = json_decode($checked, true);
		$arr = [];
		if (!empty($checked)) {
			foreach ($checked as $key => $value) {
				$arr[] = (int) $value;
			}
		}
		return $this->list_msg(['list' => $menu, 'checkedId' => $arr]);
	}
}
