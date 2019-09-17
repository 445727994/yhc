<?php
namespace app\admin\controller;
use org\Field;
use org\Tableyhc;
use think\Cache;
use think\Config;
use think\Controller;
use think\Db;

/*
Auther :  萤火虫
email  : 445727994@qq.com
 */
class Base extends Controller {
	protected $user_id;
	protected $Lang;
	protected $controller_name;
	public function __construct() {
		parent::__construct();
		header("Content-type:text/html;charset=utf-8");
		$this->user_id = session("user_info")['id'];
		$cache_key = request()->module() . "/" . request()->controller();
		if (empty(cache("lang"))) {
			if (file_exists(EXTEND_PATH . "/lang/" . request()->module() . "/base.php")) {
				cache("lang", (include EXTEND_PATH . "/lang/" . request()->module() . "/base.php"));
			}
		}
		if (empty(cache("user_role" . $this->user_id))) {
			cache("user_role" . $this->user_id, json_decode(session("user_info")['role'], true));
		}
		$this->check_role();
		$this->Lang = Cache("lang");
		if (!session("user_info")) {
			$this->redirect('admin/login/index');
		}

		//网站SEO信息
		//$url = request()->module() . "/" . lcfirst(request()->controller()) . '/' . request()->action();
		if (empty(cache("lang_" . request()->controller()))) {
			$file = EXTEND_PATH . "/lang/" . request()->module() . "/" . lcfirst(request()->controller()) . ".php";
			$lang_controller = '';
			if (file_exists($file)) {
				$lang_controller = include $file;
			}

			cache("lang_" . request()->controller(), $lang_controller);
		}
		if (empty(cache("webconfig"))) {
			cache("webconfig", DB::name("webconfig")->find());
		}
		$this->controller_name = request()->controller();
		if (method_exists($this, '_change_name')) {
			$this->controller_name = $this->_change_name();
		}
		$this->assign('web', cache('webconfig'));
		$this->assign('lang', cache("lang_" . request()->controller()));
		$this->assign("time", $this->Lang['time']);
		$this->assign("operate", $this->Lang['operate']);
	}
	public function clear() {
		$path = '../runtime/';
		delete_dir_file($path . 'cache');
		delete_dir_file($path . 'temp');
		return $this->return_msg("缓存清除成功,正在刷新页面");
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
	protected function menu() {
		if (empty(cache("list_menu" . $this->user_id))) {
			$model_name = "\\app\\admin\\model\\Menu";
			$Menu = new $model_name();
			if (empty(cache("list_menu_roleids" . $this->user_id))) {
				$user_role = cache("user_role" . $this->user_id);
				$role_arr = [];
				if (!empty($user_role)) {
					foreach ($user_role as $key => $value) {
						$role_arr[] = (int) $value;
					}
				}
				cache('list_menu_roleids' . $this->user_id, $role_arr);
			}
			$where['is_menu'] = 1;
			if ($this->user_id != 2) {
				$cache = cache('list_menu_roleids' . $this->user_id);
				$where['id'] = $cache;
			}
			$list = $Menu->where($where)->order('id asc')->select()->toArray();
			$list_menu = genTree($list);
			cache("list_menu" . $this->user_id, $list_menu);
		}
		$this->assign('list_menu', cache('list_menu' . $this->user_id));
	}
	protected function get_where() {
		$where = array(); //查询
		$like_array = ['title', 'mobile1|mobile2', 'monitor1|monitor2', 'user_name', 'i.user_name', 'wx_user', 'a.wx_user'];
		$date_array = ['create_time_start', 'update_time_start', 'create_time_end', 'update_time_end', 'a.create_time_start', 'a.create_time_end', 'a.update_time_start', 'a.update_time_end'];
		if (!empty(input('search_data'))) {
			$search_data = input('search_data');
			foreach ($search_data as $key => $value) {
				if (!empty($value['value'])) {
					if (in_array($value['name'], $like_array)) {
						$where[] = [$value['name'], 'like', '%' . $value['value'] . '%'];
					} else if (strpos($value['name'], 'time_start')) {
						$where[] = [rtrim($value['name'], '_start'), '>', strtotime($value['value'])];
					} else if (strpos($value['name'], 'time_end')) {
						$where[] = [rtrim($value['name'], '_end'), '<', strtotime($value['value'])];
					} else if (strpos($value['name'], 'time_between')) {
						$where[] = [substr($value['name'], 0, -8), 'between', strtotime($value['value']) . ',' . (strtotime($value['value']) + 24 * 3600)];
					} else if ($value['name'] == 'bp') {
						$where[] = ['ps_number', 'regexp', '^[0-9]{7,9}' . $value['value'] . "[0-9]{3}"];
					} else {
						$where[] = [$value['name'], '=', $value['value']];
					}
				}
			}
		}
		return $where;
	}
	//公共方法 列表页
	public function index() {
		if (request()->isAjax()) {
			$page = (int) input('page') ? (int) input('page') : 1;
			$page_size = (int) input('limit') ? (int) input('limit') : 10;
			if (method_exists($this, '_change_name')) {
				$m_name = $this->_change_name();
			}
			$m_name = isset($m_name) ? $m_name : $this->controller_name;
			$model_name = "\\app\\admin\\model\\" . $m_name;

			$model = new $model_name();
			$where = $this->get_where();
			$order = '';
			if (!empty(input('field')) && !empty(input("order"))) {
				$order = input("field") . " " . input('order');
			}
			$join = array();
			$field = '';
			if (method_exists($this, '_befor_index')) {
				$this->_befor_index($where, $join, $field);
			}

			$list = $model->get_list($where, $field, $order, $page, $page_size, $join);
			$this->addlog('访问列表页,页数:' . $page);
			if (method_exists($this, '_end_search')) {
				$this->_end_search($list);
			}
			return $list;
		}
		return $this->fetch();
	}
	//公共方法 导出
	public function export() {
		if (request()->isAjax()) {
			ini_set("emory_limit", "1024M");
			if (method_exists($this, '_change_name')) {
				$m_name = $this->_change_name();
			}
			$m_name = isset($m_name) ? $m_name : $this->controller_name;
			$model_name = "\\app\\admin\\model\\" . $m_name;
			$model = new $model_name();
			$where = $this->get_where();

			$order = '';
			if (!empty(input('field')) && !empty(input("order"))) {
				$order = input("field") . " " . input('order');
			}
			$join = array();
			$field = '';
			if (method_exists($this, '_befor_index')) {
				$this->_befor_index($where, $join, $field);
			}
			$this->addlog('导出数据', $where);
			$list = $model->export_list($where, $field, $order, $join);
			if (method_exists($this, '_end_export')) {
				$this->_end_export($list);
			}
			return $list;
		}
		return $this->fetch();
	}
	public function check_csrf($data) {
		//验证表单
		$token_key = config("token_key");
		$token = md5(md5($token_key) . md5($data['microtime']) . sha1($data['rand_num']));
		if ($token != $data["token"]) {
			exit(json_encode($this->return_msg("csrf")));
		} else {
			return true;
		}
	}
	//公共方法  添加
	public function add() {
		if (request()->isAjax()) {
			$data = input("post.");
			$this->check_csrf($data);
			if (method_exists($this, '_befor_insert')) {
				$this->_befor_insert($data);
			}
			$res = $this->validate($data, $this->controller_name);
			if (true !== $res) {
				return $this->return_msg($res, 1);
			}
			$model_name = "\\app\\admin\\model\\" . $this->controller_name;
			$model = new $model_name();
			$res = $model->_save($data);
			if ($res) {
				return $this->return_msg("add");
			}
			$this->addlog('添加数据', $where);
			return $this->return_msg("add_err");
		}
		if (method_exists($this, '_befor_add')) {
			$this->_befor_add($data);
		}
		$this->csrf_token();
		return $this->fetch();
	}
	//公共方法  编辑
	public function edit() {
		$model_name = "\\app\\admin\\model\\" . $this->controller_name;
		$model = new $model_name();
		if (request()->isAjax()) {
			$data = input("post.");
			//验证表单
			$token_key = config("token_key");
			$token = md5(md5($token_key) . md5($data['microtime']) . sha1($data['rand_num']));
			if ($token != $data["token"]) {
				return false;
			}
			if (method_exists($this, '_befor_update')) {
				$this->_befor_update($data);
			}
			$res = $this->validate($data, $this->controller_name);
			if (true !== $res) {
				return $this->return_msg($res, 1);
			}
			$res = $model->_save($data);
			if ($res) {
				if (method_exists($this, '_end_update')) {
					$this->_end_update($data);
				}
				$this->addlog('编辑数据', $data);
				return $this->return_msg("edit");
			}
			return $this->return_msg("edit_err");
		}
		$id = (int) input("id");
		$type = input("type");
		if ($type == 'next') {
			$data = $model->where(input("k") . "= '" . input('utype') . "'")->find();

		} else {
			$data = $model->find($id);
		}
		if (method_exists($this, '_befor_edit')) {
			$this->_befor_edit($data);
		}
		$this->assign("info", $data);
		$this->csrf_token();
		return $this->fetch();
	}

//公共方法  查看
	public function detail() {
		$this->controller_name = $this->controller_name;
		if (method_exists($this, '_change_name')) {
			$this->controller_name = $this->_change_name();
		}
		$model_name = "\\app\\admin\\model\\" . $this->controller_name;
		$model = new $model_name();
		$id = (int) input("id");
		$data = $model->find($id);
		if (method_exists($this, '_befor_edit')) {
			$this->_befor_edit($data);
		}
		$this->assign("info", $data);
		$this->addlog('查看数据', $data);
		$this->csrf_token();
		return $this->fetch();
	}

	//公共方法 删除
	public function del() {
		$model_name = "\\app\\admin\\model\\" . $this->controller_name;
		$model = new $model_name();
		if (request()->isAjax()) {
			$data = input("post.");
			if (method_exists($this, '_befor_del')) {
				$this->_befor_del();
			}
			$del_data = $model->where($data)->all();
			$res = $model->_del($data);
			$this->addlog('删除数据', $del_data);
			if ($res) {
				return $this->return_msg("del");
			}
			return $this->return_msg("del_err");
		}
		return $this->return_msg("del_err");
	}

	//单元格编辑
	public function field_edit() {

		if (request()->isAjax()) {
			$model_name = "\\app\\admin\\model\\" . $this->controller_name;

			$model = new $model_name();
			$data['id'] = (int) input("id");
			$data[input("field")] = input("value");
			if (method_exists($this, '_befor_field')) {
				$this->_befor_update($data, $model);
			}
			$res = $model->_save($data);
			if ($res) {
				$this->addlog('快速编辑', $data);
				return $this->return_msg("edit");
			}

			return $this->return_msg("edit_err");
		}
	}
	public function clear_log() {
		Db::query('truncate table yhc_adminlog');
		$this->addlog('清除日志');
		return $this->return_msg("清除成功,正在刷新页面");
	}
	//滑动checked编辑
	public function checked_edit() {
		if (request()->isAjax()) {
			$model_name = "\\app\\admin\\model\\" . $this->controller_name;
			$model = new $model_name();
			$data['id'] = (int) input("id");
			$data[input("field")] = input("value") == "false" ? 0 : 1;
			$res = $model->_save($data);
			if ($res) {
				$this->addlog('快速编辑', $data);
				return $this->return_msg("edit");
			}
			return $this->return_msg("edit_err");
		}
	}
	public function get_depart() {
		$schoolcode = input("schoolcode");
		if (empty($schoolcode)) {
			return $this->list_msg([]);
		}
		$depart = DB::name('information')->where(['schoolcode' => $schoolcode])->group('departmentname')->field('id,departmentname')->order("id asc")->select();
		return $this->list_msg($depart);
	}
	public function get_class() {
		$schoolcode = input("schoolcode");
		if (empty($schoolcode)) {
			return $this->list_msg([]);
		}

		$class = DB::name('information')->where(['schoolcode' => $schoolcode, 'departmentname' => input("departmentname")])->field('id,class')->group('class')->order("id asc")->select();
		return $this->list_msg($class);
	}
	protected function _save($name, $data, $where) {
		if (count($where)) {
			$res = DB::name($name)->where($where)->update($data);
		} else {
			$res = DB::name($name)->save($data);
		}
		return $res;
	}
	protected function list_msg($list) {

		if (!isset($list['count'])) {
			$list['list'] = $list;
			$list['count'] = 0;
		}
		return array("code" => 0, "msg" => "suc", "count" => $list['count'], "data" => $list['list']);
	}

	protected function return_msg($key, $code = 0) {
		if (isset($this->Lang['return_msg'][$key])) {
			return $this->Lang['return_msg'][$key];
		} else {
			return array("msg" => $key, "code" => $code);
		}
	}
	protected function get_seo($url) {
		$seo_result = Cache::get("seo_" . $url);
		if ($seo_result) {
			$this->assign("seo", $seo_result);
		} else {
			$S = new Seo();
			$seo_result = $S->where("url like '%" . $url . "%'")->find();
			if (empty($seo)) {
				$seo_result = $S->find(1);
			}
			Cache::set("seo_" . $url, $seo_result);
			$this->assign("seo", $seo_result);
		}

	}
	//自动创建部分代码
	//
	//
	public function start() {
		///admin/Base/start?table=Webconfig&table_name=管理
		//生成controller

		$module = empty(input('model')) ? request()->module() : input('model');
		$name = input('table');
		//创建controller
		$this->create($module, "controller", $name);
		//创建model
		$this->create($module, 'model', $name);
		//创建validate
		$this->create($module, 'validate', $name);
		//  $create_type = input('type');
		//type=1 只创建edit视图  =2 创建3个视图
		$Field = new Field();
		$table = input('table');
		$type_path = APP_PATH . DS . request()->module() . DS . 'view' . DS . lcfirst($table);
		if (!is_dir($type_path)) {
			mkdir($type_path, '755', true);
		}
		$lang_path = EXTEND_PATH . DS . "lang" . DS . request()->module();
		if (!is_dir($lang_path)) {
			mkdir($lang_path, '755', true);
		}
		//增添语言文件
		$this->create_lang($table);
		//创建edit视图
		$table_msg = $Field->get_view($table, 'edit');

		if (file_exists($type_path . DS . 'edit.html')) {
			echo "edit视图已存在<br>";
		} else {
			$edit = file_put_contents($type_path . DS . 'edit.html', $table_msg);
			if ($edit) {
				echo "edit视图写入成功<br>";
			}
		}
		if (input('type') == 1) {
			return '';
		}

		//创建index视图
		$table_msg = $Field->get_view($table, 'index');
		if (file_exists($type_path . DS . 'index.html')) {
			echo "<br>index视图已存在<br>";
		} else {
			$index = file_put_contents($type_path . DS . 'index.html', $table_msg);
			if ($index) {
				echo "<br>index视图写入成功<br>";
			}
		}

		//创建add视图
		$table_msg = $Field->get_view($table, 'add');

		if (file_exists($type_path . DS . 'add.html')) {
			echo "add视图已存在<br>";
		} else {
			$add = file_put_contents($type_path . DS . 'add.html', $table_msg);
			if ($add) {
				echo "add视图写入成功<br>";
			}
		}

		//$text = $Field->return_input();
		//$field['controller']['text'] = 'ccccc';

	}
	protected function tables($type = 1) {
		$tables = Db::query('SHOW TABLES'); //print_r($tables);
		$tbs = array();
		if ($type == 1) {
			foreach ($tables as $t) {
				$tb = explode("_", $t["Tables_in_" . config('database.database')]);
				unset($tb[0]);
				foreach ($tb as $k => $v) {$tb[$k] = ucfirst($v);};
				$tbs[] = implode("", $tb);
			}
		} else {
			foreach ($tables as $t) {
				$tbs[] = $t["Tables_in_" . config('database.database')];
			}
		}

		return $tbs;
	}

	protected function columns($table) {
		$columns = Db::query("SHOW FULL COLUMNS FROM " . $table);
		return $columns;
	}
	/**
	 * [创建controller/model文件]
	 * @param  string $create_module [模块名]
	 * @param  string $type          [controller/model]
	 * @param  string $create_name   [文件名]
	 * @param  array  $replace       [替换内容]
	 * @return [type]                [none]
	 */
	protected function create($create_module = 'admin', $type = 'controller', $create_name = "", $replace = array()) {
		//读取固定文件
		$example_files = $type . '_exam.php';
		$create_name = ucfirst($create_name);
		$create_path = APP_PATH . $create_module . '/' . $type . '/' . $create_name . '.php';
		//不存在 再创建 防止覆盖 修改后的代码
		if (!file_exists($create_path)) {
			if (!file_exists(APP_PATH . $create_module . '/' . $type)) {
				if (!file_exists(APP_PATH . 'admin/')) {
					mkdir(APP_PATH . $create_module . '/');
				}
				mkdir(APP_PATH . $create_module . '/' . $type);
			}
			$files = file_get_contents(EXTEND_PATH . "org" . DS . "view" . DS . "html" . DS . $example_files);
			//固定替换模块  -》c or m  or v
			$files = str_replace('!$module!', $create_module, $files);
			$files = str_replace('!$table!', $create_name, $files);
			$fixed = ['!$time!' => date('Y-m-d H:i:s'), '!$Auther!' => "萤火虫", '!$email!' => '445727994@qq.com', '!$rule!' => '', '!$msg!' => '', '!$add!' => '', '!$edit!' => ''];
			$replace = array_merge($replace, $fixed);
			foreach ($replace as $k => $v) {
				$files = str_replace($k, $v, $files);
			}
			$res = file_put_contents($create_path, $files);
			if ($res) {
				echo APP_PATH . $create_module . "/" . $type . "/" . $create_name . "生成成功" . "<br>";
			} else {
				echo APP_PATH . $create_module . "/" . $type . "/" . $create_name . "生成失败" . "<br>";
			}
		} else {
			echo APP_PATH . $create_module . "/" . $type . "/" . $create_name . " 已存在" . "<br>";
		}
	}
	protected function create_lang($table) {
		$name = input("table_name");
		$Tableyhc = new Tableyhc();
		$data = $Tableyhc->get_field($table);
		$lang[$table] = [
			'index' => $name . "列表",
			'add' => "添加" . $name,
			'edit' => "编辑" . $name,
			'search_array' => [
				'id' => '输入id查找',
			],
		];
		if (count($data) == count($data, 1)) {
			$lang[$table]['field'][$data['field_name']] = $data['yhc_name'];
		} else {
			foreach ($data as $k => $v) {
				$lang[$table]['field'][$v['field_name']] = $v['yhc_name'];
			}
		}

		$res_msg = $this->put_in($lang[$table]);
		$msg = "<?php \n \$LANG= [\n" . $res_msg . "\n]; \nreturn \$LANG;";
		//数组每行写入
		if (file_exists(EXTEND_PATH . DS . "lang" . DS . request()->module() . DS . lcfirst($table) . '.php')) {
			echo "语言文件已存在！";
			return;
		}
		$res = file_put_contents(EXTEND_PATH . DS . "lang" . DS . request()->module() . DS . lcfirst($table) . '.php', $msg);
		if ($res) {
			echo "语言写入成功！";
		} else {
			echo "语言写入失败！";
		}
	}
	protected function put_in($array = array()) {
		$return_msg = "";
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$return_msg .= "'$key'=>[\n" . $this->put_in($value) . "],\n";
			} else {
				$return_msg .= "'$key'=>'$value',\n";

			}
		}

		return $return_msg;
	}
	public function create_num($usercard = "") {
		if ($usercard == "") {
			$usercard = input("usercard");
		}
		if ($usercard == "") {
			return "";
		}
		$user = Db::name("information")->where(['usercard' => $usercard])->find();
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
			return $this->create_num($usercard);
		}
		return $ps_num;
	}
	//excel
	protected function _excel($table, $excelname, $tableheader, $field, $type = '_leading_out', $width = array()) {
		include_once EXTEND_PATH . '/PHPExcel/PHPExcel.php';
		$function_name = '_excel' . $type;
		return $this->$function_name($table, $excelname, $tableheader, $field, $width = array());
	}
	protected function _excel_leading_out($table, $excelname, $tableheader, $field, $width = array()) {
		set_time_limit(0);
		ini_set("memory_limit", "1024M");
		date_default_timezone_set('Asia/chongqing');
		//大于2000 分页导出
		$i = "A";
		$PHPExcel = new \PHPExcel();
		$PHPSheet = $PHPExcel->getActiveSheet();
		foreach ($tableheader as $k => $v) {
			$PHPSheet->setCellValue($i . "1", $v);
			$PHPExcel->getActiveSheet()->getColumnDimension($i)->setWidth(isset($width[$k]) ? $width[$k] : "15");
			$i++;
		}
		$data = input();

		input('search_data', $data);

		$where = $this->get_where();
		if (method_exists($this, '_befor_index')) {
			$this->_befor_index($where);
		}

		$count = DB::name($table)->where($where)->count();
		$pagesize = 2000;
		$page_all = (int) ceil($count / $pagesize);
		//遍历所有的数据
		$num = 1;
		$page = 1;
		//获取分类数据
		for ($page = 1; $page <= $page_all; $page++) {
			$data = Db::name('information')->where($where)->limit(($page - 1) * $pagesize, $pagesize)->order('id asc')->select();
			foreach ($data as $k => $v) {
				//这里的num要给a++
				$num += 1;
				$i = "A";
				foreach ($field as $kf => $vf) {
					$v[$vf] = $vf == 'sex' ? ($v[$vf] == 1 ? "男" : '女') : $v[$vf];
					$PHPSheet->setCellValueExplicit($i . $num, $v[$vf], \PHPExcel_Cell_DataType::TYPE_STRING);
					$i++;
				}
			}
		}
		ob_clean();
		$PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
		header('Content-Disposition: attachment;filename="' . $excelname . time('YmdHis') . '.xlsx"'); //下载下来的表格名
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		$PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
	}
	protected function check_role() {
		if ($this->user_id == 2) {
			return true;
		}
		$where = ['module' => strtolower(request()->module()), 'controller' => strtolower(request()->controller()), 'action' => strtolower(request()->action())];
		$menu_id = DB::name('menu')->where($where)->value("id");
		if ($menu_id) {
			if (!in_array($menu_id, cache("user_role" . $this->user_id))) {
				$this->error('无权限！', url('welcome/welcome'));
			}
		}
	}
	protected function addlog($msg, $data = '') {
		Db::name("adminlog")->insert(['admin' => session("user_info")['username'] . "(id:" . $this->user_id . ")", 'url' => request()->module() . "/" . request()->controller() . "/" . request()->action(), 'msg' => $msg, 'create_time' => time(), 'data' => json_encode($data)]);
	}
}
