<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-05-05 00:38:39
 */
namespace app\admin\controller;
use think\Controller;
use think\Db;
class Information extends Base {
	public function __construct() {
		parent::__construct();
	}
	public function _initialize() {

	}
  public function showimg(){
  $id=input('id');
    if(!$id){
    exit;
    }
    $info=Db::name('information')->where('id='.$id)->find();
    $this->csrf_token();
    $this->assign('info',$info);
	return $this->fetch();    
  }
	public function _befor_update(&$data) {
		$data['sex'] = isset($data['sex']) ? 1 : 0;
		$data['view_subscribe'] = isset($data['view_subscribe']) ? 1 : 0;
		$data['view_voluntarily'] = isset($data['view_voluntarily']) ? 1 : 0;
	}
	public function _befor_index(&$where, &$join = [], $field = '') {
		if (count($where) > 0) {
			foreach ($where as $key => $value) {
				if ($value[0] == 'is_ps') {
					$new = $value[2] == 1 ? ['ps_number', '>', '0'] : ['ps_number', '<', '0'];
					$where[$key] = $new;
				}
			}
		}
	}
	public function _befor_insert(&$data) {
		$data['sex'] = isset($data['sex']) ? 1 : 0;
		$data['view_subscribe'] = isset($data['view_subscribe']) ? 1 : 0;
		$data['view_voluntarily'] = isset($data['view_voluntarily']) ? 1 : 0;
	}
	public function _end_search(&$list) {
		$data = $list['data'];
		foreach ($data as $key => $value) {
			$data[$key]['sex'] = $value['sex'] == 1 ? "男" : "女";
			//$data[$key]['view_subscribe'] = $value['view_subscribe'] == 1 ? "是" : "否";
			//$data[$key]['view_voluntarily'] = $value['view_voluntarily'] == 1 ? "是" : "否";
		//	$data[$key]['is_confirm'] = $value['is_confirm'] == 1 ? "已确认" :($value['is_confirm'] == 2 ?"未确认");
		}
		$list['data'] = $data;
	}
	//批量编辑
	public function batch_edit() {
		if (request()->isAjax()) {
			$model_name = "\\app\\admin\\model\\" . request()->controller();
			$model = new $model_name();
			$where = $this->get_where();
			$this->_befor_index($where);
			if (empty(count($where))) {
				return $this->return_msg("select", 1);
			}
			$res = $model->_save(['view_subscribe' => input('type'), 'view_voluntarily' => input('type')], $where);
			if ($res) {
				return $this->return_msg("edit");
			}

			return $this->return_msg("edit_err");
		}
	}
	//批量编辑
	public function role_edit() {
		if (request()->isAjax()) {
			$model_name = "\\app\\admin\\model\\" . request()->controller();
			$model = new $model_name();
			$where = $this->get_where();
			$this->_befor_index($where);
			if (empty(count($where))) {
				return $this->return_msg("select", 1);
			}

			$res = $model->_save(['view_' . input('change') => input('status')], $where);
			if ($res) {
				return $this->return_msg("edit");
			}

			return $this->return_msg("edit_err");
		}
	}

	public function leading_out() {
		$tableheader = array('拍摄序号', '考生号', '学历层次', '学号', '姓名', '性别', '身份证号码', '所在校别', '院校名称', '院校代码', '专业', '院系名称', '院系代码', '班级', '采集年份', '学历类别');
		$field = array("ps_number", 'exam_id', "study_level", "study_id", "user_name", "sex", "usercard", "schooldistinction", "schoolname", "schoolcode", "major", "departmentname", "departmentcode", "class", "cj_time", "educational_type");
		$this->_excel('information', '学生列表-', $tableheader, $field);
	}
	//excel导入
	public function leading_in() {
		set_time_limit(0);
		$pass = input("pass");
		$user_name = session('user_name');
		$check_pass = md5(md5($pass) . sha1(session("user_info")['salt']));
		$user_pass = session("user_info")['password'];
		if ($user_pass != $check_pass) {
			$file = \request()->file("excel");
			if (empty($file)) {return $this->error("请选择上传文件");}
			$file_postfix_arr = explode('.', $file->getInfo()['name']);
			$file_postfix = strtolower(end($file_postfix_arr));
			$allow = array("xls", "xlsx");
			if (!in_array($file_postfix, $allow)) {
				return $this->error("上传文件后缀名不允许，请上传xls,xlsx文件");
			}
			$info = $file->rule("uniqid")->move('./excels');
			if ($info) {
				$type = $info->getExtension();
				//开始excel导入
				$path = "./excels/" . $info->getSaveName();
				$field = array(
					"ps_number", "exam_id", "study_level", "study_id", "user_name", "sex", "usercard", "schooldistinction", "schoolname", "schoolcode", "major", "departmentname", "departmentcode", "class", "cj_time", "educational_type", 'times');
				$unique = 1;
				//检索
				include_once EXTEND_PATH . '/PHPExcel/PHPExcel.php';
				$PHPExcel = new \PHPExcel();
				if (strtolower($type) == "xlsx") {
					$reader = \PHPExcel_IOFactory::createReader('Excel2007'); //设置以Excel2007格式
				} else if (strtolower($type) == "xls") {
					$reader = \PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel2003格式
				}
				$PHPExcel = $reader->load($path); // 载入excel文件
				$sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
				$highestRow = $sheet->getHighestRow(); // 取得总行数
				$highestColumm = $sheet->getHighestColumn(); // 取得总列数
				/** 循环读取每个单元格的数据 */
				$new_data = [];
				$cf_data = [];
				for ($row = 2; $row <= $highestRow; $row++) {
//行数是以第1行开始，这里示例中excel有3列字段
					$i = "A";
					$arr = array();
					foreach ($field as $kf => $vf) {
                      if($vf=='sex'){
                          $arr[$vf] = trim($sheet->getCell($i . $row)->getValue())=="男"?1:0;
                      }else{
                      	$arr[$vf] = trim($sheet->getCell($i . $row)->getValue());
                      }
						$i++;
					}
                      $info=Db::name('information')->where('usercard='.$arr['usercard'] . " and cj_time=".$arr['cj_time'] .' and times='.$arr['times'] )->find();
                  if($info){
                  	exit('身份证号码'.$arr['usercard'].',采集时间'.$arr['cj_time']."采集次数".$arr['times'].'数据已存在');
                  }
					$new_data[] = $arr;
				}
				//$checked_data = excel_check($type, $path, $field);
				$name = "\\app\\admin\\model\\Information";
				$Information = new $name();
				if (!empty(count($cf_data))) {
					$this->export_array($cf_data);
					exit;
				} else {
					if (!empty(count($new_data))) {
						$Information->saveAll($new_data);
					}
				}
				return $this->success('导入成功');
			} else {
				return $this->error("上传文件错误");
			}
		} else {
			return $this->error("密码错误");
		}
	}
	//导出excel
	public function export_array($data) {
		set_time_limit(0);
		ini_set("memory_limit", "512M");
		date_default_timezone_set('Asia/chongqing');
		$PHPExcel = new \PHPExcel();
		//表头数组
		$tableheader = array('拍摄序号', '考生号', '学历层次', '学号', '姓名', '性别', '身份证号码', '所在校别', '院校名称', '院校代码', '专业', '院系名称', '院系代码', '班级', '采集年份', '学历类别');
		$field = array("ps_number", 'exam_id', "study_level", "study_id", "user_name", "sex", "usercard", "schooldistinction", "schoolname", "schoolcode", "major", "departmentname", "departmentcode", "class", "cj_time", "educational_type");
		$PHPSheet = $PHPExcel->getActiveSheet();
		//设置导出的数据 宽度
		$PHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
		$PHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$PHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
		$PHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
		$PHPSheet->setTitle("demo"); //给当前活动sheet设置名称
		$i = "A";
		foreach ($tableheader as $k => $v) {
			$PHPSheet->setCellValue($i . "1", $v);
			$i++;
		}
		//遍历所有的数据
		$num = 1;
		foreach ($data as $k => $v) {
			//这里的num要给a++
			$num += 1;
			$i = "A";
			foreach ($field as $kf => $vf) {
				$PHPSheet->setCellValueExplicit($i . $num, $v[$vf], \PHPExcel_Cell_DataType::TYPE_STRING);
				$i++;
			}
		}

		ob_clean();
		$PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, "Excel2007"); //创建生成的格式
		header('Content-Disposition: attachment;filename="cf' . time('YmdHis') . '.xlsx"'); //下载下来的表格名
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		$PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
	}

}
