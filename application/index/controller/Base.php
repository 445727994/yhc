<?php
namespace app\index\controller;
use think\Controller;
use think\Db;

class Base extends Controller {
	protected $user;
	public function __construct() {
		header("Content-type:text/html;charset=utf-8");
		parent::__construct();
		if (!isMobile()) {
			header('Location:./index.html');
			exit;
		}
		$this->user = session('user');
		if (empty($this->user)) {
			$this->redirect("Wechatuser/index");
		}
		if (empty($this->user['user_id'])) {
			$this->redirect("Wechatuser/binding", array("return_url" => $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]));
		}
		if (empty(cache("webconfig"))) {
			cache("webconfig", DB::name("webconfig")->find());
		}
		$webconfig = cache("webconfig");
		if ($webconfig['is_open'] == 1) {

			if (!strstr($webconfig['limit_user'], session('user')['openid'])) {
				header('Location:http://hngror.com/wh.html');
				exit;
			}

		}

		//	$user = Db::name("wxuser")->find();
		//		session('user', $user);
		switch (request()->action()) {
		case 'index':
			$check = 0;

			break;
		case 'search':
			$check = 1;
			break;
		case 'correct_errors':
			$check = 2;
			break;
		default:
			$check = 3;
			break;
		}
		if (request()->controller() == 'User') {
			$check = 3;
		}
		$orders = Db::name('orders')->where(['openid' => $this->user['openid']])->find();
		$information = Db::name('information')->where(['openid' => $this->user['openid']])->field('ps_number,id,view_subscribe,view_voluntarily')->find();
		if (!$information) {
			$this->redirect("Wechatuser/binding");
		}
		if (empty($information['ps_number'])) {
			if ($orders && $orders['status'] == 0 && request()->action() != 'pay') {
				$this->redirect("pay");
			}
		}
		$this->assign('information', $information);
		$this->assign('check', $check);
		$this->assign('webconfig', cache("webconfig"));
	}
	protected function return_msg($key, $code = 0, $url = '', $btn = '') {
		if (request()->isAjax()) {
			return array("msg" => $key, "code" => $code);
		} else {
			$this->assign('msg', $key);
			$this->assign('btn', $btn);
			$this->assign('url', $url);
			return $this->fetch($code);
		}

	}
	protected function webtitle($name = "") {
		$title = empty($name) ? cache('webconfig')['title'] : $name;
		$this->assign("title", $title);
	}
	protected function checkimg($userinfo) {
		if (!empty($userinfo['ps_number'])) {
			if (empty(get_img($userinfo))) {
				$this->redirect('index/index');
				exit;
			}
		}
	}
	public function upload() {
		$img = $_POST['imgbase64'];
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {
			$type = "." . $result[2];
			if (!in_array($result[2], ['jpg', 'JPG', 'jpeg', 'JPEG'])) {
				exit(json_encode(['code' => 1, 'msg' => '图片只能为jpg格式图片'], true));
			}
			$path = "./upload/" . date("Y-m-d") . "/" . uniqid() . $type;
		}
		if (!is_dir("./upload/" . date("Y-m-d") . "/")) {
			$res = mkdir("./upload/" . date("Y-m-d") . "/", 0766);
		}
		$img = base64_decode(str_replace($result[1], '', $img));
		$res = @file_put_contents(strtolower($path), $img);
		if ($res) {
			exit(json_encode(['code' => 0, 'msg' => '上传成功', 'src' => trim($path, '.')], true));
		}

	}

}
