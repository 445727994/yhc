<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-14 14:24:58
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Change extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_index(&$where, &$join, &$field) {

	}

	public function _befor_edit(&$data) {
		$this->assign("user1", Db::name("information")->where("usercard", $data['card1'])->find());
		$this->assign("user2", Db::name("information")->where("usercard", $data['card2'])->find());
	}
	public function _befor_update(&$data) {
		$old = Db::name('change')->where('id', $data['id'])->find();
		if ($data['status'] == 3 && $old['status'] != 3) {
			//审核通过 自动交换
			$user1 = Db::name('information')->where('usercard', $old['card1'])->find();
			$user2 = Db::name('information')->where('usercard', $old['card2'])->find();
			if ($user1['schoolcode'] != $user2['schoolcode']) {
				Db::name('change')->where('id', $data['id'])->update(['status' => 10]);
				exit(json_encode($this->return_msg("审核不通过,交换人学校编码不一致", 1), true));
			}
			if ($user1['cj_time'] != $user2['cj_time']) {
				Db::name('change')->where('id', $data['id'])->update(['status' => 10]);
				exit(json_encode($this->return_msg("审核不通过,交换人采集年份不一致", 1), true));
			}
			$usercard = $old['card1'];
			$now_usercard = $old['card2'];
			$uniqid = uniqid();
			$path1 = "./schoolimg/{$user1['cj_time']}/{$user1['times']}/{$user1['schoolcode']}/";
			$path2 = "./schoolimg/{$user2['cj_time']}/{$user2['times']}/{$user2['schoolcode']}/";
			$img1 = "." . get_img($user1);
			$img2 = "." . get_img($user2);
			if (!file_exists($img1)) {
				exit(json_encode($this->return_msg("发起人图像不存在", 1), true));
			}
			if (!file_exists($img2)) {
				return $this->return_msg("", 1);
				exit(json_encode($this->return_msg("交换人图像不存在", 1), true));
			}
			rename($img1, $path2 . $uniqid . ".jpg");
			rename($img2, $path1 . $usercard . ".jpg");
			rename($path2 . $uniqid . ".jpg", $path2 . $now_usercard . ".jpg");

		}
	}
	public function _end_search(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['status'] = change_status($value['status']);
			//$data[$key]['view_subscribe'] = $value['view_subscribe'] == 1 ? "是" : "否";
			//$data[$key]['view_voluntarily'] = $value['view_voluntarily'] == 1 ? "是" : "否";
		}
		$list['data'] = $data;
	}
}
