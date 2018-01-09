<?php
header('Content-Type: text/html; charset=windows-1251');
mb_internal_encoding("CP1251");

require('lib/phpQuery.php');
require('lib/PHPExcel.php');
ini_set('max_execution_time', 1000000);

$EAN = $_POST['EAN'] ? $_POST['EAN'] : 0;
$name = $_POST['name'] ? $_POST['name'] : 0;
$cena1 = $_POST['cena1'] ? $_POST['cena1'] : 0;
$cena2 = $_POST['cena2'] ? $_POST['cena2'] : 0;
$save = $_POST['save'];
$path_to_file = $_FILES['excel']['name'];
start();

$uploaddir = 'R:/tmp/';
$file_path = $uploaddir . $path_to_file;
move_uploaded_file($_FILES['excel']['tmp_name'], $file_path);

$our_products = get_information_from_excel_and_create_list_products($file_path, $EAN, $name, $cena1, $cena2);

if ($our_products) {

    $pairs = array();
    $count = 0;

    foreach ($our_products as $product) {

        $bookOZ = get_information_on_OZ_by_EAN($product);

        $bookBiblio = get_information_on_Biblio_by_EAN($product);

        $pairs[$count] = array();
        $res_price = create_res_price($product, $bookOZ, $bookBiblio);
        array_push($pairs[$count], $product);
        array_push($pairs[$count], $bookOZ);
        array_push($pairs[$count], $bookBiblio);
        array_push($pairs[$count], $res_price);

        $count++;
    }

    if ($save) write_in_new_excel_file("data_report", $pairs);
    else { 
        print_in_web_page($pairs);
        stop();
    }

} else {
    //echo 'Нет данных!';
}


function print_in_web_page($pairs)
{
    echo $text = '
<table border=1 cellspacing=0 style="font-size: 11pt; margin-top: 20pt">
<thead>
<tr>
	<td>EAN</td>
	<td>Наименование</td>
	<td>Цена 1</td>
	<td>Цена закупки</td>
	<td>Biblio цена</td>
	<td>OZ цена</td>
	<td>Результирующая цена</td>
	<td>OZ наименование</td>
	<td>OZ вес</td>
	<td>Biblio наименование</td>
<tr>
</thead>
<tbody>
	' .
        create_tbody($pairs)
        . '
</tbody>
</table>
';
echo '<a href="index.php">Вернуться назад к заполнению</a>';
    return $text;
}

function create_tbody($pairs)
{
    $tbody = '';
    if ($pairs)
        foreach ($pairs as $pair) {
            $tbody .= create_row($pair);
        }
    return $tbody;
}

function create_row($pair)
{
    $text =
        '<tr>'

        . '<td>' . $pair[0]['EAN'] . '</td>'
        . '<td>' . $pair[0]['name'] . '</td>'
        . '<td>' . $pair[0]['cena1'] . '</td>'
        . '<td>' . $pair[0]['cena2'] . '</td>'
        . '<td' . ($pair[2]['price'] ? '>' : ' style="background-color: #FF9">') . $pair[2]['price'] . '</td>'
        . '<td' . ($pair[1]['price'] ? '>' : ' style="background-color: #FF9">') . $pair[1]['price'] . '</td>'
        . '<td>' . $pair[3] . '</td>'
        . '<td' . ($pair[1]['name'] ? '>' : ' style="background-color: #FF9">') . $pair[1]['name'] . '</td>'
        . '<td' . ($pair[1]['ves'] ? '>' : ' style="background-color: #FF9">') . $pair[1]['ves'] . '</td>'
        . '<td' . ($pair[2]['name'] ? '>' : ' style="background-color: #FF9">') . $pair[2]['name'] . '</td>'

        . '</tr>';
    return $text;
}

function create_res_price($product, $bookOZ, $bookBiblio)
{
    $price_zakup = $product['cena2'];
    $price_OZ = $bookOZ['price'];
    $price_Biblio = $bookBiblio['price'];
    $floor_price = $price_zakup * 1.2;
    $top_price = $price_zakup * 2.5;

    if ($price_OZ == 0 && $price_Biblio == 0) {

        $res_price = $price_zakup * 1.7;

    } elseif ($price_OZ && $price_Biblio) {

        $res_price = $price_OZ > $price_Biblio ? $price_Biblio * 0.96 : $price_OZ * 0.88;

    } elseif ($price_OZ && !$price_Biblio) {

        $res_price = $price_OZ * 0.88;

    } elseif (!$price_OZ && $price_Biblio) {

        $res_price = $price_Biblio * 0.96;

    }

    ($res_price < $floor_price) ? $res_price = $floor_price : '';
    ($res_price > $top_price) ? $res_price = $top_price : '';

    return number_format($res_price, 2, '.', '');
}

function write_in_new_excel_file($filename, $in_data)
{
    $pExcel = new PHPExcel();
    $pExcel->setActiveSheetIndex(0);
    $aSheet = $pExcel->getActiveSheet();

    $aSheet->getColumnDimension('A')->setAutoSize(true);
    $aSheet->getColumnDimension('B')->setWidth(50);
    $aSheet->getColumnDimension('C')->setAutoSize(true);
    $aSheet->getColumnDimension('D')->setAutoSize(true);
    $aSheet->getColumnDimension('E')->setAutoSize(true);
    $aSheet->getColumnDimension('F')->setAutoSize(true);
    $aSheet->getColumnDimension('G')->setAutoSize(true);
    $aSheet->getColumnDimension('H')->setWidth(50);
    $aSheet->getColumnDimension('I')->setAutoSize(true);
    $aSheet->getColumnDimension('J')->setWidth(50);

    $aSheet->getStyle('A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
    $aSheet->getStyle('B')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $aSheet->getStyle('C')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $aSheet->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $aSheet->getStyle('E')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $aSheet->getStyle('F')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $aSheet->getStyle('G')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
    $aSheet->getStyle('H')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    $aSheet->getStyle('I')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
    $aSheet->getStyle('J')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

    setCell($aSheet, 'A1', 'EAN');
    setCell($aSheet, 'B1', 'Наименование');
    setCell($aSheet, 'C1', 'Цена 1');
    setCell($aSheet, 'D1', 'Цена Закупки');
    setCell($aSheet, 'E1', 'Biblio цена');
    setCell($aSheet, 'F1', 'OZ цена');
    setCell($aSheet, 'G1', 'Результат цены');
    setCell($aSheet, 'H1', 'OZ наименование');
    setCell($aSheet, 'I1', 'OZ вес');
    setCell($aSheet, 'J1', 'Biblio наименование');

    $row = 2;

    foreach ($in_data as $pair) {

        setCell($aSheet, 'A' . $row, $pair[0]['EAN']);
        setCell($aSheet, 'B' . $row, $pair[0]['name']);
        setCell($aSheet, 'C' . $row, $pair[0]['cena1']);
        setCell($aSheet, 'D' . $row, $pair[0]['cena2']);
        setCell($aSheet, 'E' . $row, $pair[2]['price']);
        setCell($aSheet, 'F' . $row, $pair[1]['price']);
        setCell($aSheet, 'G' . $row, $pair[3]);
        setCell($aSheet, 'H' . $row, $pair[1]['name']);
        setCell($aSheet, 'I' . $row, $pair[1]['ves']);
        setCell($aSheet, 'J' . $row, $pair[2]['name']);

        $row++;
    }


    //$objWriter = new PHPExcel_Writer_Excel2007($pExcel);
    //$objWriter->save('simple.xlsx');

    header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition:attachment;filename="'.$filename.'.xlsx"');
    $objWriter = new PHPExcel_Writer_Excel2007($pExcel);
    $objWriter->save('php://output');
}

function setCell($aSheet, $cell_row, $text)
{
    $text = convert_To_utf8($text);
    if ($text) {
        $aSheet->setCellValue($cell_row, $text);
    } else {
        $aSheet->getStyle($cell_row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $aSheet->getStyle($cell_row)->getFill()->getStartColor()->setRGB('FFFF99');
        $aSheet->setCellValue($cell_row, $text);
    }
}


function get_information_from_excel_and_create_list_products($file_path, $coll_EAN, $coll_name,
                                                             $coll_cena1, $coll_cena2)
{
    $products = array();
    if ($coll_EAN != 0 || $coll_name != 0 || $coll_cena1 != 0 || $coll_cena2 != 0) {

        $excel = readExcelFile($file_path);

        foreach ($excel as $excel_row) {

            if ($coll_EAN) {
                $EAN = $excel_row[$coll_EAN - 1];
            } else {
                break;
            }
            $tovar['EAN'] = $EAN;

            if ($coll_name) {
                $name = convert($excel_row[$coll_name - 1]);
            }
            $tovar['name'] = $name;

            if ($coll_cena1) {
                $cena1 = $excel_row[$coll_cena1 - 1];
            }
            $tovar['cena1'] = $cena1;

            if ($coll_cena2) {
                $cena2 = $excel_row[$coll_cena2 - 1];
            }
            $tovar['cena2'] = $cena2;

            array_push($products, $tovar);
        }
    } else {
        //echo 'Нет данных!';
    }

    return $products;
}


function get_information_on_Biblio_by_EAN($product)
{
    $pathBiblio = 'https://biblio.by/catalogsearch/result/?dir=desc&order=relevance&q=' . $product['EAN'];
    $data = get_web_page($pathBiblio);
    $html = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    if (count($html->find('div[class*="products-grid row-fluid"]:first'))) {

        $link = $html->find('h2.product-name a:first')->attr('href');
        $book = get_Information_book_Biblio_by_link($link);

    } else {

        $book['status'] = 0;
        $book['link'] = $pathBiblio;
        $book['name'] = '';
        $book['img'] = 0;
        $book['price'] = 0;
        $book['ves'] = 0;
        $book['res_price'] = 0;

    }

    return $book;
}

function get_Information_book_Biblio_by_link($link_Tovar)
{
    $data = get_web_page($link_Tovar);
    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    $name = convert(pq($document->find('h1:first'))->text());

    $inf['status'] = 1;
    $inf['link'] = $link_Tovar;
    $inf['name'] = trim(preg_replace("/&quot|;/", '', $name));
    $inf['img'] = $document->find('div[class*="product-img-box span5"]:first')->find('img:first')->attr('src');

    $additionals = $document->find('p.aditional-attribute');
    $text = '';
    foreach ($additionals as $additional) {
        $text .= convert(trim(pq($additional)->text())) . ';';
        $text = preg_replace('/\s+/', ' ', $text);
    }

    /*
    preg_match('/Автор\(-ы\):[ ]+(.+?)[;]/', $text, $res);
    echo $inf['author'] = $res[1];
    echo '<br>';

    preg_match('/Год издания:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['year'] = $res[1];
    echo '<br>';

    preg_match('/Издательства:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['izd'] = $res[1];
    echo '<br>';

    preg_match('/Количество страниц:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['kol_str'] = $res[1];
    echo '<br>';

    preg_match('/Формат:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['format'] = $res[1];
    echo '<br>';

    preg_match('/EAN:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['EAN'] = $res[1];
    echo '<br>';

    preg_match('/ISBN:[ ]+(.+?)[;]/', $text, $res);
    echo $inf['isbn'] = $res[1];
    echo '<br>';
    */

    $price = $document->find('div.product-view', 0)->find('span.price', 0)->text();
    preg_match('/[0-9]*,[0-9]*/', $price, $res);
    $inf['price'] = str_replace(',', '.', trim($res[0]));

    $inf['ves'] = 0;

    $inf['res_price'] = number_format($inf['price'] * 0.8, 2);

    return $inf;
}

function get_information_on_OZ_by_EAN($product)
{

    $pathOZ = 'https://oz.by/search/?q=' . $product['EAN'];
    $data = get_web_page($pathOZ);
    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    if (!count($document->find('div.search-info-results'))) {

        $name = $document->find('div.b-product-title__heading:first');
        $price = $document->find('div.b-product-control__row:first');

        $inf['status'] = '1';
        $inf['link'] = $pathOZ;
        $inf['name'] = trim(convert(pq($name)->text()));
        $inf['price'] = str_replace(',', '.', trim(preg_replace('/[^0-9,]/', '', convert(pq($price)->text()))));

        if (!$inf['price']) {
            $inf['price'] = 0;
        }

        $text = '';

        $description = $document->find('div.b-description__container-col tr');

        foreach ($description as $tr) {
            $text .= convert(trim(pq($tr)->text())) . ';';
            $text = preg_replace('/\s+/', ' ', $text);
        }

        /*preg_match('/Издательство[ ]+(.+?)[;]/', $text, $res);
        $inf['izd'] = $res[1];

        preg_match('/Год издания[ ]+(.+?)[;]/', $text, $res);
        $inf['year'] = $res[1];

        preg_match('/Страниц[ ]+(.+?)[;]/', $text, $res);
        $inf['kol_str'] = $res[1];

        preg_match('/Формат[ ]+(.+?)[;]/', $text, $res);
        $inf['format'] = $res[1];

        preg_match('/ISBN[ ]+(.+?)[;]/', $text, $res);
        $inf['isbn'] = $res[1];

        $inf['EAN'] = str_replace('-','', $inf['isbn']);
        */

        preg_match('/Вес[ ]+(.+?)[;]/', $text, $res);
        $ves = preg_replace('/[^0-9,]/', '', $res[1]);
        $inf['ves'] = $ves ? $ves : 0;

        $inf['res_price'] = number_format($inf['price'] * 0.8, 2);

    } else {
        $inf['status'] = 0;
        $inf['link'] = $pathOZ;
        $inf['name'] = '';
        $inf['img'] = 0;
        $inf['price'] = 0;
        $inf['ves'] = 0;
        $inf['res_price'] = 0;
    }

    return $inf;

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

function readExcelFile($filepath)
{
    $inputFileType = PHPExcel_IOFactory::identify($filepath);  // узнаем тип файла, excel может хранить файлы в разных форматах, xls, xlsx и другие
    $objReader = PHPExcel_IOFactory::createReader($inputFileType); // создаем объект для чтения файла
    $objPHPExcel = $objReader->load($filepath); // загружаем данные файла в объект
    $ar = $objPHPExcel->getActiveSheet()->toArray(); // выгружаем данные из объекта в массив
    return $ar; //возвращаем массив
}

function convert($arg_1)
{
    return iconv("utf-8", "cp1251", $arg_1);
}

function convert_To_utf8($arg_1)
{
    return iconv("cp1251", "utf-8", $arg_1);
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
        '<div style="position: fixed; right: 0pt; top: 0pt;">Потрачено времени: '
	 . number_format($end - $start, 2) . ' сек' .
        '</div>';
}
?>