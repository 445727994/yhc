<?php
namespace app\admin\model;
use think\Model;

/*
Auther :  萤火虫
email  : 445727994@qq.com
 */
class Common extends Model {
	public function _save($arr, $where = []) {
		if (count($where) || isset($arr['id'])) {
			//编辑
			if (isset($arr['id'])) {
				$where[] = ['id', '=', $arr['id']];
				unset($arr['id']);
			}
			$arr['update_time'] = time();

			return $this->allowField(true)->save($arr, $where);
		} else {
			//新增
			$arr['create_time'] = isset($arr['create_time']) ? $arr['create_time'] : time();
			$arr['update_time'] = isset($arr['update_time']) ? $arr['update_time'] : time();
			return $this->allowField(true)->save($arr);
		}
	}

	public function _del($arr) {
		return $this->where($arr)->delete();
	}
	public function get_list($where = 1, $field = "*", $order = '', $page = 1, $page_size = 10, $join = array()) {
		if (method_exists($this, "_before_serach")) {
			$this->_before_serach($where);
		}
		if (empty(count($join))) {
			$order = empty($order) ? "id desc" : $order;
			$list = $this->where($where)->field($field)->limit(($page - 1) * $page_size, $page_size)->order($order)->select();
			$count = $this->where($where)->field($field)->count();

		} else {
			$order = empty($order) ? "a.id desc" : $order;
			$list = $this->alias('a')->leftjoin($join)->field($field)->where($where)->limit(($page - 1) * $page_size, $page_size)->order($order)->select();

			$count = $this->alias('a')->leftjoin($join)->where($where)->field($field)->count();
		}
		if ($count > 0 && isset($list[0]['create_time'])) {
			foreach ($list as $key => $value) {
				$list[$key]['create_time'] = isset($value['create_time']) ? date("Y-m-d H:i:s", $value['create_time']) : "";
				$list[$key]['update_time'] = isset($value['update_time']) ? date("Y-m-d H:i:s", $value['update_time']) : "";
			}
		}
		return $this->list_msg($list, $count);
	}
	public function get_list_limit($where = 1, $field = "*", $order = '', $limit = 5) {
		if (method_exists($this, "_before_serach")) {
			$this->_before_serach($where);
		}
		$order = empty($order) ? "id desc" : $order;
		$list = $this->where($where)->field($field)->limit($limit)->order($order)->select();

		$count = count($list);
		if ($count > 0 && isset($list[0]['create_time'])) {
			foreach ($list as $key => $value) {
				$list[$key]['create_time'] = isset($value['create_time']) ? date("Y-m-d H:i:s", $value['create_time']) : "";
				$list[$key]['update_time'] = isset($value['update_time']) ? date("Y-m-d H:i:s", $value['update_time']) : "";
			}
		}
		return $this->list_msg($list, $count);
	}
	public function group_list($where = 1, $group, $field = "*", $order = '', $page = 1, $page_size = 10, $join = array()) {
		if (method_exists($this, "_before_serach")) {
			$this->_before_serach($where);
		}

		if (empty(count($join))) {
			$order = empty($order) ? "id desc" : $order;
			$list = $this->where($where)->field($field)->group($group)->limit(($page - 1) * $page_size, $page_size)->order($order)->select();
			$count = $this->where($where)->field($field)->group($group)->count();
		} else {
			$order = empty($order) ? "a.id desc" : $order;

			$list = $this->alias('a')->leftjoin($join)->field($field)->where($where)->group($group)->limit(($page - 1) * $page_size, $page_size)->order($order)->select();

			$count = $this->alias('a')->leftjoin($join)->where($where)->group($group)->field($field)->count();
		}
		if ($count > 0 && isset($list[0]['create_time'])) {
			foreach ($list as $key => $value) {
				$list[$key]['create_time'] = isset($value['create_time']) ? date("Y-m-d H:i:s", $value['create_time']) : "";
				$list[$key]['update_time'] = isset($value['update_time']) ? date("Y-m-d H:i:s", $value['update_time']) : "";
			}
		}
		return $this->list_msg($list, $count);
	}

	public function export_list($where = 1, $field = "*", $order = '', $join = array()) {
		if (method_exists($this, "_before_serach")) {
			$this->_before_serach($where);
		}
		if (empty(count($join))) {
			$order = empty($order) ? "id desc" : $order;
			$list = $this->where($where)->field($field)->order($order)->select();
			$count = $this->where($where)->field($field)->count();

		} else {
			$order = empty($order) ? "a.id desc" : $order;

			$list = $this->alias('a')->leftjoin($join)->field($field)->where($where)->order($order)->select();

			$count = $this->alias('a')->leftjoin($join)->where($where)->field($field)->count();
		}
		if ($count > 0 && isset($list[0]['create_time'])) {
			foreach ($list as $key => $value) {
				$list[$key]['create_time'] = isset($value['create_time']) ? date("Y-m-d H:i:s", $value['create_time']) : "";
				$list[$key]['update_time'] = isset($value['update_time']) ? date("Y-m-d H:i:s", $value['update_time']) : "";
			}
		}
		return $this->list_msg($list, $count);
	}
	public function export_group($where = 1, $group, $field = "*", $order = '', $join = array()) {
		if (method_exists($this, "_before_serach")) {
			$this->_before_serach($where);
		}

		if (empty(count($join))) {
			$order = empty($order) ? "id desc" : $order;
			$list = $this->where($where)->field($field)->group($group)->order($order)->select();
			$count = $this->where($where)->field($field)->group($group)->count();
		} else {
			$order = empty($order) ? "a.id desc" : $order;

			$list = $this->alias('a')->leftjoin($join)->field($field)->where($where)->group($group)->order($order)->select();

			$count = $this->alias('a')->leftjoin($join)->where($where)->group($group)->field($field)->count();
		}
		if ($count > 0 && isset($list[0]['create_time'])) {
			foreach ($list as $key => $value) {
				$list[$key]['create_time'] = isset($value['create_time']) ? date("Y-m-d H:i:s", $value['create_time']) : "";
				$list[$key]['update_time'] = isset($value['update_time']) ? date("Y-m-d H:i:s", $value['update_time']) : "";
			}
		}
		return $this->list_msg($list, $count);
	}
	protected function list_msg($list, $count) {
		return array("code" => 0, "msg" => "suc", "count" => $count, "data" => $list);
	}
}