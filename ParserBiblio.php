<html>
<head>
    <title>Парсер Biblio</title>
    <meta charset="windows-1251">
</head>
<body>
<?php
require('lib/phpQuery.php');
ini_set('max_execution_time', 1000000);

$list_Pages = array();
$list_Tov = array();

$spend_time = 0;
zamer();

$http = 'https://biblio.by/biblio-books.html?___SID=U&limit=24&p=';

$fp = fopen('counter.csv', "r+");

create_List_Pages(1, 1);

echo 'Ссылки страниц добавлены в массив, начало цикла добавления ссылок на товары в массив: ' . date("h:i:s") . '<br>';

foreach ($list_Pages as $page_path) {
    add_Tov_To_List_From_Page($page_path);
}

echo 'Ссылки товаров добавлены в массив, начало цикла записи товаров: ' . date("h:i:s") . '<br>';

foreach ($list_Tov as $tov_path) {
    $arr = get_Information_About_Book($tov_path);
    echo 'Товар записан: ' . date("h:i:s") . '<br>';
    echo '<strong>' . $arr['name'] . '</strong>' . '<br>';
    echo $arr['author'] . '<br>';
    echo $arr['year'] . '<br>';
    echo $arr['izd'] . '<br>';
    echo $arr['kol_str'] . '<br>';
    echo $arr['format'] . '<br>';
    echo $arr['EAN'] . '<br>';
    echo $arr['isbn'] . '<br>';
    echo $arr['price'] . '<br>';

}

close_File();
stop_time();


function get_Information_About_Book($path)
{
    $data = get_web_page($path);
    $html = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    $text = convert($html->find('h1', 0)->text());

    $inf['name'] = trim(preg_replace("/&quot|;/", '', $text));

    $additionals = $html->find('p.aditional-attribute');
    $text = '';

    foreach ($additionals as $additional) {
        $text .= convert(trim(pq($additional)->text())) . ';';
        $text = preg_replace('/\s+/', ' ', $text);
    }

    preg_match('/Автор\(-ы\):[ ]+(.+?)[;]/', $text, $res);
    $inf['author'] = $res[1];

    preg_match('/Год издания:[ ]+(.+?)[;]/', $text, $res);
    $inf['year'] = $res[1];

    preg_match('/Издательства:[ ]+(.+?)[;]/', $text, $res);
    $inf['izd'] = $res[1];

    preg_match('/Количество страниц:[ ]+(.+?)[;]/', $text, $res);
    $inf['kol_str'] = $res[1];

    preg_match('/Формат:[ ]+(.+?)[;]/', $text, $res);
    $inf['format'] = $res[1];

    preg_match('/EAN:[ ]+(.+?)[;]/', $text, $res);
    $inf['EAN'] = $res[1];

    preg_match('/ISBN:[ ]+(.+?)[;]/', $text, $res);
    $inf['isbn'] = $res[1];

    $price = $html->find('div.product-view', 0)->find('span.price', 0)->text();
    preg_match('/[0-9]*,[0-9]*/', $price, $res);
    $inf['price'] = trim($res[0]);

    $res_price = str_replace(',', '.', $inf['price']) * 0.8;
    $res_price = str_replace('.', ',', $res_price);

    $res_str = $inf['name'] . ';' . $inf['author'] . ';' . $inf['year'] . ';' . $inf['izd'] . ';' . $inf['kol_str'] . ';'
        . $inf['format'] . ';' . $inf['EAN'] . ';' . $inf['isbn'] . ';' . $inf['price'] . ';' . $res_price . "\r\n";

    write_To_File($res_str);

    return $inf;
}

function create_List_Pages($start, $end)
{
    global $http;
    global $list_Pages;

    for ($i = $start; $i <= $end; $i++) {
        array_push($list_Pages, $http . $i);
    }
}

function add_Tov_To_List_From_Page($path)
{
    global $list_Tov;
    $data = get_web_page($path);
    $html = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    if ($html == true) {

        $links_href = $html->find('h2.product-name a');

        foreach ($links_href as $link) {
            array_push($list_Tov, pq($link)->attr('href'));
        }
    }
}

function write_To_File($res_str)
{
    global $fp;
    $test = fwrite($fp, $res_str);
    return $test;
}

function close_File()
{
    global $fp;
    fclose($fp);
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

function zamer()
{
    global $spend_time;
    $hour = (int)date("h");
    $minute = (int)date("i");
    $second = (int)date("s");
    $sum = $hour * 3600 + $minute * 60 + $second;
    $spend_time = $sum;
    echo 'Старт скрипта: ' . $hour . ':' . $minute . ':' . $second . '<br>';
}

function stop_time()
{
    global $spend_time;
    global $list_Tov;
    $hour = (int)date("h");
    $minute = (int)date("i");
    $second = (int)date("s");
    $sum = $hour * 3600 + $minute * 60 + $second;
    echo 'Конец скрипта: ' . $hour . ':' . $minute . ':' . $second . '<br>';
    $spend_time = $sum - $spend_time;
    echo 'Потрачено времени: ' . $spend_time . ' сек.<br>';
    echo 'Скорость добавления: ' . $spend_time / count($list_Tov) . ' секунд для 1 товара<br>';
}

function convert($arg_1)
{
    return iconv("utf-8", "cp1251", $arg_1);
}

?>
</body>
</html>