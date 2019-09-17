<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-04-24 01:22:38
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Wxuser extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_index(&$where, &$join, &$field) {
		$join[] = ['information i', 'a.user_id=i.id'];
		$field = 'a.*,i.*,a.id as id';
		if (count($where) > 0) {
			foreach ($where as $key => $value) {
				if ($value[0] == 'is_bind') {
					$new = $value[2] == 1 ? ['a.user_id', '>', '0'] : ['a.user_id', '<', '1'];
					$where[$key] = $new;

				}
			}
		}

	}
	public function _befor_edit(&$data) {
		if ($data) {
			$inf = DB::name('information')->where("id", $data['user_id'])->find();
			$data = json_decode(json_encode($data, true), true);
			unset($inf['id']);
			$data = $inf ? array_merge($data, $inf) : $data;
		}
	}
	public function unbind() {
		$id = (int) input("id");
		$user = DB::name("wxuser")->where("id", $id)->find();
		if (!$user) {
			return $this->return_msg('参数错误，请刷新页面重试', 1);
		}
		DB::name("wxuser")->where("id", $id)->update(['user_id' => NULL]);
      	DB::name("picerror")->where("openid",$user['openid'])->delete();
      	DB::name("orders")->where("openid",$user['openid'])->delete();
		DB::name("information")->where("id", $user['user_id'])->update(['mobile' => NULL, 'openid' => NULL]);
		return $this->return_msg('解绑成功');
	}
}
