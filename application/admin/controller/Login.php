<?php
namespace app\admin\controller;
use app\admin\model\Admin;
use think\Controller;
use think\Db;

/*
Auther :  萤火虫
email  : 445727994@qq.com
 */
class Login extends Controller {
	public function __construct() {
		parent::__construct();
		if (empty(cache("webconfig"))) {
			cache("webconfig", DB::name("webconfig")->find());
		}
		if (empty(cache("lang"))) {
			cache("lang", (include EXTEND_PATH . "/lang/" . request()->module() . "/base.php"));
		}
		$this->Lang = Cache("lang");
		$this->assign('web', cache('webconfig'));
	}
	public function index() {
		if (request()->isAjax()) {
			$data = input('post.');
			$this->check_csrf($data);
			$res = $this->validate($data, "Login");
			if (true !== $res) {
				return $this->return_msg($res, 1);
			}
			$Admin = new Admin();
			$user = $Admin->where(["username" => $data['username']])->find();
			if (!$user) {
				return $this->return_msg('not_exist', 1);
			}
			$check_pass = md5(md5($data['password']) . sha1($user['salt']));
			if ($check_pass != $user['password']) {
				return $this->return_msg('password_error', 1);
			}
			session("user_info", $user);
			return $this->return_msg("success");
		}
		$this->csrf_token();
		return $this->fetch();
	}
	public function create_num($userid = "") {
		if (empty($userid)) {
			$userid = input("user_id");
		}

		if ($userid == "") {
			exit;
		}
		$user = Db::name("information")->where(['id' => $userid])->find();
		if ($user['ps_number']) {
			return $user['ps_number'];
		}
		$where = ['schoolcode' => $user['schoolcode'], 'cj_time' => $user['cj_time']];
		$num = Db::name('ps_log')->where($where)->find();
		if (!$num) {
			$data = ['schoolcode' => $user['schoolcode'], 'cj_time' => $user['cj_time'], 'start' => 5000, 'now' => 5001];
			Db::name('ps_log')->where($where)->insert($data);
			$ps_num = $user['schoolcode'] . $user['cj_time'] . $user['times'] . "85001";
		} else {
			$ps_num = $user['schoolcode'] . $user['cj_time'] . $user['times'] . '8' . ($num['now'] + 1);
		}
		$ps_num_check = Db::name("information")->where(['ps_number' => $ps_num])->find();
		if ($ps_num_check) {
			Db::name('ps_log')->where($where)->update(['now' => ($num['now'] + 1)]);
			return $this->create_num($userid);
		}
		return $ps_num;
	}
	protected function csrf_token() {
		//表单token 验证  防止csrf
		$token_key = config("token_key");
		$microtime = microtime();
		$rand_num = rand(10, 9999999);
		$token = md5(md5($token_key) . md5($microtime) . sha1($rand_num));
		$this->assign("microtime", $microtime);
		$this->assign("rand_num", $rand_num);
		$this->assign('token', $token);
	}
	protected function check_csrf($data) {
		//验证表单
		$token_key = config("token_key");
		$token = md5(md5($token_key) . md5($data['microtime']) . sha1($data['rand_num']));
		if ($token != $data["token"]) {
			exit(json_encode($this->return_msg("csrf")));
		} else {
			return true;
		}
	}
	protected function return_msg($key, $code = 0, $type = "") {
		if (isset($this->Lang['return_msg'][$key])) {
			return $this->Lang['return_msg'][$key];
		} else {
			return array("msg" => $type, "code" => $code);
		}
	}
	public function logout() {
		session("user_info", null);
		$this->redirect("/adm");
	}
}
