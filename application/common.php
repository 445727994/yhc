<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Db;
// 应用公共文件
function get_img($user) {
	$src = "/schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}/" . strtoupper($user['usercard']) . ".jpg";
	if (file_exists('.' . $src)) {
		return $src;
	} else {
		return '';
	}
}
function get_img_bycard($usercard) {
	$user = DB::name('information')->where('usercard', $usercard)->field('cj_time,times,schoolcode,usercard')->find();
	$src = "/schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}/" . strtoupper($user['usercard']) . ".jpg";
	if (file_exists('.' . $src)) {
		return $src;
	} else {
		return '';
	}
}
/**
 * 移动端判断
 */
function isMobile() {
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
		return true;
	}
	// 如果via信息含有wap则一定是移动设备
	if (isset($_SERVER['HTTP_VIA'])) {
		// 找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$clientkeywords = array('nokia',
			'sony',
			'ericsson',
			'mot',
			'samsung',
			'htc',
			'sgh',
			'lg',
			'sharp',
			'sie-',
			'philips',
			'panasonic',
			'alcatel',
			'lenovo',
			'iphone',
			'ipod',
			'blackberry',
			'meizu',
			'android',
			'netfront',
			'symbian',
			'ucweb',
			'windowsce',
			'palm',
			'operamini',
			'operamobi',
			'openwave',
			'nexusone',
			'cldc',
			'midp',
			'wap',
			'mobile',
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return true;
		}
	}
	// 协议法，因为有可能不准确，放到最后判断
	if (isset($_SERVER['HTTP_ACCEPT'])) {
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
			return true;
		}
	}
	return false;
}
function Directory($dir) {

	if (!is_dir($dir)) {
		$dirArr = explode('/', $dir); //当子目录没创建成功时，试图创建父目录，用explode()函数以'/'分隔符切割成一个数组
		array_pop($dirArr); //将数组中的最后一项（即子目录）弹出来，
		$newDir = implode('/', $dirArr); //重新组合成一个文件夹字符串
		Directory($newDir); //试图创建父目录
		@mkdir($dir, 0777);
	}

	return 'true';
}
function change_status($s) {
	switch ($s) {
	case '0':
		return "已提交等待对方确认";
		break;
	case '-1':
		return "对方拒绝交换";
		break;
	case '2':
		return "已确定交换,等待管理审核";
		break;
	case '3':
		return "已交换";
		break;
	case '4':
		return "被交换人照片不存在";
		break;
	case '10':
		return '审核不通过';
		break;
	default:
		return '';
		break;
	}

}

function get_status($s) {
	switch ($s) {
	case '0':
		return "已提交，等待处理";
		break;
	case '1':
		return "审核中";
		break;
	case '2':
		return "审核通过,图像制作中";
		break;
	case '3':
		return "已邮寄";
		break;
	case '4':
		return "未缴费 ";
		break;
	case '9':
		return '图片审核不通过,请重新上传规范图片';
		break;
	case '10':
		return '审核不通过';
	case '11':
		return '已预约成功';
	case '12':
		return '已处理';
	default:
		return '';
		break;
	}
}
function get_status_app($s) {
	switch ($s) {
	case '0':
		return "已提交，等待处理";
		break;
	case '1':
		return "审核中";
		break;
	case '2':
		return "审核通过,图像制作中";
		break;
	case '3':
		return "已邮寄";
		break;
	case '4':
		return "未缴费 ";
		break;
	case '9':
		return '图片审核不通过,请重新上传规范图片';
		break;
	case '10':
		return '审核不通过';
	case '11':
		return '已预约成功';
	case '12':
		return '已处理';
	default:
		return '';
		break;
	}
}
function get_status2($s) {
	switch ($s) {
	case '0':
		return "已提交，等待处理";
		break;
	case '1':
		return "已缴费,等待处理";
		break;
	case '2':
		return "审核通过,图像制作中";
		break;
	case '3':
		return "已邮寄";
		break;
	case '4':
		return "未缴费 ";
		break;
	case '9':
		return '图片审核不通过,请重新上传规范图片';
		break;
	case '10':
		return '审核不通过';
	case '11':
		return '已预约成功';
	case '12':
		return '已处理';
	default:
		return '';
		break;
	}
}

function search_type($key, $value) {
	$array_eq = ['id'];
	$array_like = ['keywords', 'name', 'title'];
	if (in_array($key, $array_eq)) {
		return array("=", $value);
	}
	if (in_array($key, $array_like)) {
		return array("like", "%" . $value . "%");
	}
	;
	if (strpos($key, '|')) {
		return array("like", "%" . $value . "%");
	}
	;
	return array("like", "%" . $value . "%");
}
function get_select($sa, $k, $show = 0) {
	$html = '<div class="layui-input-inline">';
	switch ($sa['type']) {
	case 'select':
		$html .= '<select name="' . $k . '" style="width:' . $sa['width'] . 'px">';
		$option = "";
		foreach ($sa['value'] as $key => $value) {
			if (isset($sa['default']) && $sa['default'] == $key) {
				$option .= '<option value="' . $key . '" selected="selected">' . $value . '</option>';
			} else {
				$option .= '<option value="' . $key . '">' . $value . '</option>';
			}

		}
		$html .= $option . '<select></div>';
		break;
	case 'select_table':
		$array = Db::name($sa['value'])->field($sa['list_key'] . ',' . $sa['list_value'])->where($sa['where'])->group($sa['group'])->order('id desc')->select();
		$html .= '<select id="schoolcode" lay-filter="school" name="' . $k . '" style="width:' . $sa['width'] . 'px">';
		$option = "";
		$option = "<option value=''>请选择" . $sa['name'] . "</option>";
		foreach ($array as $key => $value) {
			$option .= '<option value="' . $value[$sa['list_key']] . '">' . $value[$sa['list_value']] . '</option>';
		}
		$html .= $option . '<select></div>';
		if ($k == 'schoolcode' && $show == 1) {
			$htmls = '<select lay-filter="depart" id="depart" name="departmentname" style="width:' . $sa['width'] . 'px">';
			$option = "";
			$option = "<option value=''>请选择院系</option>";
			$htmls .= $option . '<select>';
			$html_class = '<select id="class" name="class" style="width:' . $sa['width'] . 'px">';
			$option = "";
			$option = "<option value=''>请选择班级</option>";
			$html_class .= $option . '<select>';
		}
		break;
	case 'input':
		$html .= '<input class="layui-input" name="' . $k . '" value="' . input($sa['name']) . '"  placeholder="' . $sa['name'] . '" style="width:' . $sa['width'] . 'px" autocomplete="off">';
		break;
	case 'date':
		$id = 'date' . mt_rand(10000, 99999);
		if (isset($sa['num'])) {
			$html .= '<input class="layui-input" name="' . $k . '_start" id="' . $id . '" value="' . input($sa['name']) . '"  placeholder="' . $sa['name'] . '"  autocomplete="off">
			<script>layui.use("laydate", function(){var laydate = layui.laydate;laydate.render({elem: "#' . $id . '"})});</script>';
			$html .= '<input class="layui-input" name="' . $k . '_end" id="' . $id . '1" value="' . input($sa['name']) . '"  placeholder="' . $sa['name'] . '"  autocomplete="off">
			<script>layui.use("laydate", function(){var laydate = layui.laydate;laydate.render({elem: "#' . $id . '1"})});</script>';
		} else {
			$html .= '<input class="layui-input" name="' . $k . '_between" id="' . $id . '" value="' . input($sa['name']) . '"  placeholder="' . $sa['name'] . '"  autocomplete="off">
			<script>layui.use("laydate", function(){var laydate = layui.laydate;laydate.render({elem: "#' . $id . '"})});</script>';
		}

		break;
	case 'date_start':
		$id = 'date' . mt_rand(10000, 99999);
		$html .= '<input class="layui-input" name="' . $k . '_start" id="' . $id . '" value="' . input($sa['name']) . '"  placeholder="' . $sa['name'] . '"  autocomplete="off">
		<script>layui.use("laydate", function(){var laydate = layui.laydate;laydate.render({elem: "#' . $id . '"})});</script>';

		break;
	default:
		$option = "";
		break;
	}
	if (isset($htmls)) {
		$htmls = '<div class="layui-input-inline">' . $htmls . "</div>";
	}
	echo $html;
	if (isset($htmls)) {
		$htmls = '<div class="layui-input-inline">' . $htmls . "</div>" . '<div class="layui-input-inline">' . $html_class . "</div>";
		echo $htmls;
	}

}
function genTree($items, $id = 'id', $pid = 'pid', $son = 'children') {
	$tree = array(); //格式化的树
	$tmpMap = array(); //临时扁平数据
	foreach ($items as $item) {
		$item['url'] = '';
		if (!empty($item['action'])) {
			$str = $item['module'] . '/' . $item["controller"] . '/' . $item["action"];
			$item['url'] = url($str) . "?" . $item['param'];
		}
		$tmpMap[$item[$id]] = $item;
	}
	foreach ($items as $item) {
		if (isset($tmpMap[$item[$pid]])) {
			$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
		} else {
			$tree[] = &$tmpMap[$item[$id]];
		}
	}
	unset($tmpMap);
	return $tree;
}
function delete_dir_file($dir_name) {
	$result = false;
	if (is_dir($dir_name)) {
		//检查指定的文件是否是一个目录
		if ($handle = opendir($dir_name)) {
			//打开目录读取内容
			while (false !== ($item = readdir($handle))) {
				//读取内容
				if ($item != '.' && $item != '..') {
					if (is_dir($dir_name . DS . $item)) {
						delete_dir_file($dir_name . DS . $item);
					} else {
						unlink($dir_name . DS . $item); //删除文件
					}
				}
			}
			closedir($handle); //打开一个目录，读取它的内容，然后关闭
			if (rmdir($dir_name)) {
				//删除空白目录
				$result = true;
			}
		}
	}
	return $result;
}
function validation_filter_id_card($id_card) {
	if (strlen($id_card) == 18) {
		return idcard_checksum18($id_card);
	} elseif ((strlen($id_card) == 15)) {
		$id_card = idcard_15to18($id_card);
		return idcard_checksum18($id_card);
	} else {
		return false;
	}
}
// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base) {
	if (strlen($idcard_base) != 17) {
		return false;
	}
	//加权因子
	$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
	//校验码对应值
	$verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
	$checksum = 0;
	for ($i = 0; $i < strlen($idcard_base); $i++) {
		$checksum += substr($idcard_base, $i, 1) * $factor[$i];
	}
	$mod = $checksum % 11;
	$verify_number = $verify_number_list[$mod];
	return $verify_number;
}
// 将15位身份证升级到18位
function idcard_15to18($idcard) {
	if (strlen($idcard) != 15) {
		return false;
	} else {
		// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
		if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
			$idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
		} else {
			$idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
		}
	}
	$idcard = $idcard . idcard_verify_number($idcard);
	return $idcard;
}
// 18位身份证校验码有效性检查
function idcard_checksum18($idcard) {
	if (strlen($idcard) != 18) {
		return false;
	}
	$idcard_base = substr($idcard, 0, 17);
	if (idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
		return false;
	} else {
		return true;
	}
}