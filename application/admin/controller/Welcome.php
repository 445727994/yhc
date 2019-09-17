<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-04-24 01:22:38
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;

class Welcome extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
	public function _befor_index(&$where, &$join, $field) {

	}
	public function index() {
		$this->menu();
		$retarking = Db::name('picerror')->where('type', 1)->where('status', 1)->where('type', 1)->count();
		$time = strtotime(date('Y-m-d'));
      //->where('appoint_time', 'between', $time . "," . ($time + 24 * 3600))
		$appointment = Db::name('picerror')->where('type', 2)->where('status','=',1)->count();
        $exchange = Db::name('change')->where('status','=',0)->count();
      	$this->assign('change',  $exchange );
		$this->assign('retarking', $retarking);
		$this->assign('appointment', $appointment);
		$this->assign("num", [$retarking, $appointment]);
		$this->assign("count", $retarking + $appointment);
		return $this->fetch();
	}
	public function welcome() {
		$count = Db::name("information")->count();
		$retarking = Db::name('picerror')->where('type', 1)->where('status', 1)->where('type', 1)->count();
		$time = strtotime(date('Y-m-d'));
		$appointment = Db::name('picerror')->where('type', 2)->where('status','<>', 4)->where('appoint_time', 'between', $time . "," . ($time + 24 * 3600))->count();
      	$exchange = Db::name('change')->where('status','=',0)->count();
		$this->assign('count', $count);
      	$this->assign('change',  $exchange );
		$this->assign('retarking', $retarking);
		$this->assign('appointment', $appointment);
		return $this->fetch();
	}

}
