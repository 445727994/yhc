<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-14 11:10:56
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Appointment extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_index(&$where, &$join, &$field) {
		$join[] = ['information i', 'a.openid=i.openid'];
		$field = 'a.*,i.user_name,i.usercard,i.ps_number,i.schoolname,i.departmentname,i.class,i.major,a.id as id';
		$where[] = ['a.type', '=', '2'];
		$where[] = ['a.status', '<>', '4'];
	}
	public function _change_name() {
		return "Picerror";
	}
  	public function change_status(){
  		$status=input('status');
    	$res=Db::name('picerror')->where(['id'=>input('ids')])->update(['status'=>input('status')]);
 	if($res){
    return $this->return_msg("edit");
    }else{
    return $this->return_msg("edit_err");
    }
  	}
	public function _end_search(&$list) {
		$data = $list['data'];
  
		foreach ($data as $key => $value) {
			$data[$key]['status'] = get_status($value['status']);
			$data[$key]['need_send'] = $value['need_send'] == 1 ? "邮寄" : "自取";
			$data[$key]['appoint_time'] = date('Y-m-d H:i', trim($value['appoint_time']));
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
			//$data[$key]['view_subscribe'] = $value['view_subscribe'] == 1 ? "是" : "否";
			//$data[$key]['view_voluntarily'] = $value['view_voluntarily'] == 1 ? "是" : "否";
		}
		$list['data'] = $data;
	}
	public function _end_export(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['status'] = get_status($value['status']);
			$data[$key]['need_send'] = $value['need_send'] == 1 ? "邮寄" : "自取";
			$data[$key]['appoint_time'] = date('Y-m-d H:i', trim($value['appoint_time']));
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

}
