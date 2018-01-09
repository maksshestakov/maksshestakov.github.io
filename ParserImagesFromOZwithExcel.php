<html>
<head>
    <title>Сохранение картинок с OZ по штрих-коду</title>
    <meta http-equiv="content-type" content="text/html; charset=windows-1251"/>
    <style>
        body {
            text-align: center;
        }

        form {
            margin: 20pt auto;
        }

        table {
            margin: 20pt auto;
        }

        td {
            padding: 5px;
        }
	
	img {
	    width: 40%;
	}
    </style>
</head>
<body>

<?php
start();
mb_internal_encoding("cp1251");
require('lib/phpQuery.php');
require('lib/PHPExcel.php');
ini_set('max_execution_time', 1000000);

$coll_EAN = $_POST['EAN'] ? $_POST['EAN'] : '';
$file = $_FILES['excel'];
$dir_upload = 'R:/tmp/';
$request = 'https://oz.by/search/?f=1&q=';
$path_save_images = 'images/';

$count_not_found_names = 1;
$count_saved_images = 0;

create_dir($path_save_images);
create_start_page();

if (!$coll_EAN && $file['size']) {	
	echo 'Не выбран столбец штрих-кода<br>';
} elseif ($coll_EAN && !$file['size']) {
	echo 'Не выбран файл<br>';
} elseif ($coll_EAN && $file['size']) {
	upload_file($dir_upload, $file['name'], $file['tmp_name']);

	$list_EANs = get_list_EANs_from_excel($dir_upload.$file['name'], $coll_EAN);
	
	foreach ($list_EANs as $EAN) {
	    $book = get_Information_about_book($request . $EAN);
	    save_images_from_book($book);
	}

} else {

}
stop();

function get_Information_about_book($path)
{
    global $EAN;
    $data = get_web_page($path);

    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    if (count($document->find('div.top-filters')) || count($document->find('div.search-info-results__content'))) {
        echo 'Не найдена книга!' . '<br>';
	echo '<a href="'.$path.'" style="font-size: 16pt; color: red;">'.$path.'</a><br>';
    } else {
        $name = $document->find('div.b-product-title__heading:first');
        $main_image = $document->find('div.b-product-photo a:first');
        $all_images = $document->find('div.b-product__media a');

        $all_images_href_list = array();
        foreach ($all_images as $image) {
            array_push($all_images_href_list, pq($image)->attr('href'));
        }
        unset($all_images_href_list[count($all_images_href_list) - 1]);

        $inf['name'] = pq($name)->text();
        $inf['link'] = $path;
        $inf['src_image'] = pq($main_image)->attr('href');
        $inf['all_images'] = $all_images_href_list;
        $inf['ean'] = $EAN;

        return $inf;
    }
}

function save_images_from_book($book)
{
    global $path_save_images;
    global $count_not_found_names;
    global $count_saved_images;

    if ($book['all_images']) {
        $count_saved_images++;
        echo '<br><br><br>'.$count_saved_images . '. ' . $book['name'] . ' <br>';
        $part_name_sub_image = '_';
        $count_image = 1;
	
	$ean = $book['ean'];

	echo 'Штрих-код: '. $ean . '<br>';
	echo 'Были скачаны картинки: <br>';
        foreach ($book['all_images'] as $href_image) {
            echo $href_image . '<br>';
            if (count($book['all_images']) == $count_image) {
                save_Image($href_image, $path_save_images . $ean);
            } else {
                save_Image($href_image, $path_save_images . $ean . $part_name_sub_image . $count_image);
                $count_image++;
            }
        }

        echo '<img src="' . $book['src_image'] . '"><br><br><br>';

    } else {
        echo 'Не найдено картинок!<br><br><br>';
    }
}

function save_Image($url, $path)
{
    $file = file_get_contents($url);
    file_put_contents($path . '.jpg', $file);
}

function get_list_EANs_from_excel($path_to_file, $coll_EAN) {
$list_EANs = array();
if ($coll_EAN) {

    $excel = readExcelFile($path_to_file);
    foreach ($excel as $excel_row) {
	$EAN = $excel_row[$coll_EAN - 1];
	if ($EAN) {
	    array_push($list_EANs, $EAN);
	} else {
	    break;
	}
    }    
}

return $list_EANs;
}

function readExcelFile($filepath)
{
    $inputFileType = PHPExcel_IOFactory::identify($filepath);  // узнаем тип файла, excel может хранить файлы в разных форматах, xls, xlsx и другие
    $objReader = PHPExcel_IOFactory::createReader($inputFileType); // создаем объект для чтения файла
    $objPHPExcel = $objReader->load($filepath); // загружаем данные файла в объект
    $ar = $objPHPExcel->getActiveSheet()->toArray(); // выгружаем данные из объекта в массив
    return $ar; //возвращаем массив
}

function create_start_page() {
echo $start = '
<form enctype="multipart/form-data" action="ParserImagesFromOZwithExcel.php" method="POST">
    <table>
        <tr>
            <td>Введите номер столбца штрих-кода:</td>
            <td><input class="searchField" type="text" name="EAN" required value ="'.$_POST['EAN'].'"></td>
        </tr>
        <tr>
            <td>Выберите файл:</td>
            <td><input type="file" name="excel" required
                       accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/excel">
            </td>
        </tr>
    </table>
    <input id="submitField" type="submit" value="Выполнить"><br>
</form>
';
}

function get_web_page($url)
{
    $uagent = "Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.14";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/lib/cacert.pem');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
    curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
    curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
    curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа (не много ли!?)

    $content = curl_exec($ch);

    curl_close($ch);

    return $content;
}

function upload_file($dir, $name_file, $tmp_name) {
    if ($dir && $name_file && $tmp_name) {
	$file_path = $dir . $name_file;
	move_uploaded_file($tmp_name, $file_path);
    }
}

function create_dir($path_save_images) {
    if (!file_exists($path_save_images)) {
        mkdir($path_save_images);
    }
}

function start()
{
    global $start;
    $start = microtime(1);
}

function stop()
{
    global $start;
    $end = microtime(1);
    echo
        '<div style="position: fixed; right: 0pt; bottom: 0pt;">Потрачено времени: '
	 . number_format($end - $start, 2) . ' сек' .
        '</div>';
}
?>
</body>
</html>