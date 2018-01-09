<html>
<head>
<title>������� �������� Harvest</title>
<meta charset="utf-8">
</head>
<body>

<?php
include('lib/simple_html_dom.php');
$allLinksCatalog = array();
$allLinks = array();
$spend_time = 0;
zamer();
$http = 'http://www.harvest.minsk.by';
ini_set('max_execution_time', 1000000);

echo '�����'.date("h:i:s").'<br>';
parse_Catalog_With_Paginator(get_Parts_Of_Catalog());

foreach($allLinksCatalog as $link){
	add_Links_From_Page($link);
}
echo '������ ���� ���������� ������'.date("h:i:s").'<br>';

foreach($allLinks as $link) {
	get_Info_By_Tovar($link);
}
echo "���������� ���� ���������� ��������".date("h:i:s").'<br>';

stop_time();

//print_Info_By_Tovar('http://www.harvest.minsk.by/items/100-vidatnih-dzeyacho%D1%9E--viktar-tura%D1%9E_2648');

//print_All_Links_From($allLinksCatalog);
//print_All_Links_From($allLinks);

function print_Info_By_Tovar($path) {
	global $http;
	$html = file_get_html($path);
	
	$div = $html -> find('div.item-params', 0);
	$information = convert($div->plaintext);		
	$src_image = $http.$html->find('div[@class=item_full_image] img', 0)->src;

	echo '<img src="'.$src_image.'">'.$information.'<br>';

	$html->clear();
	unset($html);
}



//������� ��������� ����������� ������� ������ �� ������
//������� �������� ��������� ������� ������ � �����������
//���������� ������ �� �����
function get_Info_By_Tovar($path) {
	global $http;
	$html = file_get_html($path);
	if($html == TRUE) {
	
	$div = $html -> find('div.item-params', 0);
	$information = convert($div->plaintext);

	preg_match('/EAN:[ ]+(.*),/', $information, $res);	

	$name_image = '1';	
	if ($res[1]) {
		$name_image = $res[1];
	}
	
	
	$src_image = $http.$html->find('div[@class=item_full_image] img', 0)->src;

	save_Image($src_image, $name_image);

	$html->clear();
	unset($html);
	}
}

//���������� �������� �� ����
function save_Image($path, $name) {
	$file = file_get_contents($path);
	file_put_contents($name.'.jpg', $file);
}

//������� ��������� � ���������� ������ ������ ������ �� ������ ��� ����� �� ���������� ��������
function add_Links_From_Page($path) {
	global $allLinks;
	global $http;

	$html = file_get_html($path);

	$links = $html->find('//div[@class="items clearFix"]//div[@class="item"]//div[@class="item-name"]/a');

	foreach($links as $link) {
		$res = str_replace('�','%D1%9E',convert($link->href));
		array_push($allLinks, $http.$res);
	}

	$html->clear();
	unset($html);
}

//��� ������ � ���������

//���������� ������ ������ �� ������� �����
function get_Parts_Of_Catalog() {
	global $http;
	$links = array();
//	array_push($links, $http.'/category/biografii-memuari-aforizmi');
//	array_push($links, $http.'/category/category211');
//	array_push($links, $http.'/category/detyam--i-roditelyam');
//	array_push($links, $http.'/category/category214');
//	array_push($links, $http.'/category/category187');
//	array_push($links, $http.'/category/category188');
//	array_push($links, $http.'/category/category195');
//	array_push($links, $http.'/category/category210');
//	array_push($links, $http.'/category/category221');
//	array_push($links, $http.'/category/category189');
//	array_push($links, $http.'/category/category190');
//	array_push($links, $http.'/category/category224');
//	array_push($links, $http.'/category/category196');
//	array_push($links, $http.'/category/category200');
//	array_push($links, $http.'/category/category186');
//	array_push($links, $http.'/category/category207');
//	array_push($links, $http.'/category/category213');
//	array_push($links, $http.'/category/category212');
	return $links;
}

//���������� � ���������� ������ ������ ������ �� ��� ������ � �� ��� �������� ���������
function parse_Catalog_With_Paginator($links) {
	global $allLinksCatalog;

	foreach($links as $link) {
		array_push($allLinksCatalog, $link);
		add_Paths_From_Pagination($link);
	}
}

//��������� � ���������� ������ ������ �� �������� � �������� ��� �������� �� ��������� ������� �� ������
function add_Paths_From_Pagination($path) {
	global $allLinksCatalog;
	$count_pages = find_Count_Pages($path);

	for ($i = 2; $i <= $count_pages; $i++) {
		array_push($allLinksCatalog, $path.'?p='.$i);
	}
}

//������� ���������� ������� � ����������
function find_Count_Pages($path) {
	$html = file_get_html($path);
	$links = $html->find('//div[@class="paginator"]/a');

	$count_pages = count($links) - 1;
	if ($count_pages > 1) {
		unset($html);
		return $count_pages;
	} else {
		unset($html);
		return 0;
	}
	
	$html->clear();
	unset($html);
}

//�������

function zamer() {
	global $spend_time;
	$hour = (int) date("h");
	$minute = (int) date("i");
	$second = (int) date("s");
	$sum = $hour * 3600 + $minute * 60 + $second;
	$spend_time = $sum;
	echo '����� �������: '.$hour.':'.$minute.':'.$second.'<br>';
}

function stop_time() {
	global $spend_time;
	global $list_Tov;
	$hour = (int) date("h");
	$minute = (int) date("i");
	$second = (int) date("s");
	$sum = $hour * 3600 + $minute * 60 + $second;
	echo '����� �������: '.$hour.':'.$minute.':'.$second.'<br>';
	$spend_time = $sum - $spend_time;
	echo '��������� �������: '.$spend_time.' ���.<br>';
	echo '�������� ����������: '.$spend_time/count($list_Tov).' ������ ��� 1 ������<br>';
}

function print_All_Links_From($array) {
	foreach ($array as $kniga) {
		echo $kniga.'<br>';
	}
}

function convert($arg_1) {
	return iconv("utf-8","cp1251",$arg_1);
}
?>
</body>
</html>