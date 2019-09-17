<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-05 00:38:39
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Picerror extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _befor_edit(&$data) {
		if ($data) {
			$inf = DB::name('information')->where("openid", $data['openid'])->find();
			$data = json_decode(json_encode($data, true), true);
			unset($inf['id']);
			$data = $inf ? array_merge($data, $inf) : $data;
			if ($data['ps_number']) {

			}
		}
	}

	public function _end_update(&$data) {
		$s = DB::name('picerror')->where("id", $data['id'])->find();
		$user = DB::name('information')->where("openid", $s['openid'])->find();
		$src = "/schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}";
		Directory('.' . $src);
		$src .= "/{$user['usercard']}.jpg";
		if ($s['status'] == "11") {
          //更新用户状态到confirm2
          /*
			if ($s['picsrc'] != $src) {
				if (file_exists("." . $src) && file_exists("." . $s['picsrc'])) {
					unlink("." . $src);
				}
				rename('.' . $s['picsrc'],'./' . $src);
				//DB::name('picerror')->where("id", $data['id'])->update(['picsrc' => $src]);
			}
            */
		}
		if ($data['ps_number'] && empty($user['ps_number'])) {
			DB::name('information')->where("usercard", $data['usercard'])->update(['ps_number' => $data['ps_number']]);
		}
	}

	public function _befor_index(&$where, &$join, &$field) {
		$join[] = ['information i', 'a.openid=i.openid'];
		$field = 'a.*,i.user_name,i.usercard,i.ps_number,i.schoolname,i.departmentname,i.class,i.major,a.id as id';
		$where[] = ['a.type', '=', '1'];
		//$where[] = ['a.status', '<>', '4'];
	}
	public function _end_search(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['status'] = get_status($value['status']);
			$data[$key]['need_send'] = $value['need_send'] == 1 ? "邮寄" : "自取";
			switch ($value['error_type']) {
			case '1':
				$data[$key]['error_type'] = "不是本人";
				break;
			case '2':
				$data[$key]['error_type'] = "闭眼";
				break;
			default:
				$data[$key]['error_type'] = "补拍";
				break;
			}
		}
		$list['data'] = $data;
	}
	public function _end_export(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['status'] = get_status($value['status']);
			$data[$key]['need_send'] = $value['need_send'] == 1 ? "邮寄" : "自取";
			switch ($value['error_type']) {
			case '1':
				$data[$key]['error_type'] = "不是本人";
				break;
			case '2':
				$data[$key]['error_type'] = "闭眼";
				break;
			default:
				$data[$key]['error_type'] = "补拍";
				break;
			}
		}
		$list['data'] = $data;
	}
	public function userexport() {

	}
}
