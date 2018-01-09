<html>
<head>
    <title>Скачиваем картинки с OZ</title>
    <meta http-equiv="content-type" content="text/html; charset=windows-1251"/>
    <style>
        form {
            margin: 20pt auto;
            text-align: center;
        }

        img {
            display: block;
            margin: 0 auto;
            width: 300pt;
        }

        a {
            color: red;
            display: block;
            margin: 0 auto;
            font-size: 20pt;
            font-weight: bold;
        }

        .spendTime {
            position: absolute;
            top: 0px;
            right: 0px;
        }

        .searchField {
            width: 200pt;
            margin-right: 20px;
            box-shadow: 0px 0px 10px rgba(154, 136, 170, 0.9);
            height: 32pt;
            padding: 10pt;
            font-size: 14pt;
        }

        #submitField {
            width: 150pt;
            border-radius: 120pt;
            height: 50pt;
            background-color: honeydew;
            outline: none;
            margin-top: 20px;
        }
    </style>
</head>


<body>
<form action="ParserImagesFromOZ.php" method="post">
    <p>
        <input class="searchField" type="text" name="start">
        <input class="searchField" type="text" name="end"><br>
        <input class="searchField" type="text" name="start_tov">
        <input class="searchField" type="text" name="end_tov"><br>
        <input id="submitField" type="submit" value="Поиск"></p>
    <input type="checkbox" name="forOnce"
        <?php
        echo $_POST['forOnce'] ? 'checked' : '';
        ?>
    > Скачать по штрихкоду?
    </p>
</form>

<?php
require('lib/phpQuery.php');
ini_set('max_execution_time', 1000000);

$spend_time = 0;
zamer();
$count_not_found_names = 1;
$count_saved_images = 0;

$param = $_POST['start'];
$end = $_POST['end'];
$start_tov = $_POST['start_tov'];
$end_tov = $_POST['end_tov'];
$forOnce = $_POST['forOnce'];

$array_links_to_pages_with_books = array();
$array_URL_books = array();

$httpOZ = 'https://oz.by';
$preficsOZ = '/search/?q=';
$request = $httpOZ . $preficsOZ . str_replace('-', '', $param);

$path_save_images = 'images/';
$httpOZ_path = 'https://oz.by/books/?availability=1%3B2&f=1&sort=best_desc&page=';

if ($forOnce && $param) {
    $book = get_Information_about_book($request);
    save_images_from_book($book);
}

if ($param && !$forOnce) {

    create_array_links_to_pages_with_books($param ? $param : 1, $end ? $end : $param);

    parse_all_pages();

    print_all_URL_books();

    save_image_URLs_books_from_to($start_tov, $end_tov);

}

stop_time();

function save_image_URLs_books_from_to($start = 1, $end = 96)
{
    global $array_URL_books;

    if ($start > 96 || $start < 0 || $start == null) $start = 1;
    if ($end > 96 || $end < 0 || $end == null) $end = count($array_URL_books);

    if ($start == 1 && $end == 96) {
        foreach ($array_URL_books as $link_book) {
            $book = get_Information_about_book($link_book);
            save_images_from_book($book);
        }
    } else {
        for ($start; $start <= $end; $start++) {
            echo $start . '<br>';
            $book = get_Information_about_book($array_URL_books[$start - 1]);
            save_images_from_book($book);
        }
    }
}

function create_array_links_to_pages_with_books($start = 1, $end = 1)
{
    global $array_links_to_pages_with_books;
    global $httpOZ_path;
    for ($start; $start <= $end; $start++) {
        array_push($array_links_to_pages_with_books, $httpOZ_path . $start);
    }
}

function parse_all_pages()
{
    global $array_links_to_pages_with_books;

    foreach ($array_links_to_pages_with_books as $link) {
        add_links_books_to_array_from_page($link);
    }
}

function add_links_books_to_array_from_page($path_page_with_many_books)
{
    global $array_URL_books;
    $links_books = get_links_books($path_page_with_many_books);

    foreach ($links_books as $link) {
        array_push($array_URL_books, $link);
    }
}

function get_links_books($path)
{
    global $httpOZ;
    $data = get_web_page($path);
    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    $pages = array();
    $tovars = $document->find("ul[id='goods-table'] li")->not("[style*='display:none']");
    foreach ($tovars as $tovar) {
        array_push($pages, $httpOZ . pq($tovar)->find('a:first')->attr('href'));
    }
    return $pages;
}

function print_all_URL_books()
{
    global $array_URL_books;
    foreach ($array_URL_books as $link) {
        echo $link . '<br>';
    }
}

function save_images_from_book($book)
{
    global $path_save_images;
    global $count_not_found_names;
    global $count_saved_images;

    if ($book['all_images']) {
        $count_saved_images++;
        echo $count_saved_images . '. ' . $book['name'] . ' <br>';
        $part_name_sub_image = '_';
        $count_image = 1;

        if ($book['ean'] != '') {
            $ean = $book['ean'];
            echo $ean . '<br>';
        } else {
            $ean = 'Not_found_ean_' . $count_not_found_names;
            echo '<a target="_blank" href ="' . $book['link'] . '">' . $book['name'] . '</a><br>';
            $count_not_found_names++;
        }

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

        echo '<img src="' . $book['src_image'] . '"><br>';


    } else {
        echo 'Не найдено картинок!';
    }
}

function save_Image($url, $path)
{
    $file = file_get_contents($url);
    file_put_contents($path . '.jpg', $file);
}

function get_Information_about_book($pathOZ)
{

    $data = get_web_page(convert_To_utf8($pathOZ));

    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    if (count($document->find('div.search-info-results__content'))) {
        echo 'Не найдена книга!' . '<br>';
    } else {
        $name = $document->find('div.b-product-title__heading:first');
        $main_image = $document->find('div.b-product-photo a:first');
        $all_images = $document->find('div.b-product__media a');

        $all_images_href_list = array();
        foreach ($all_images as $image) {
            array_push($all_images_href_list, pq($image)->attr('href'));
        }
        unset($all_images_href_list[count($all_images_href_list) - 1]);

        $inf['name'] = convert(pq($name)->text());
        $inf['link'] = convert($pathOZ);
        $inf['src_image'] = convert(pq($main_image)->attr('href'));
        $inf['all_images'] = $all_images_href_list;

        $text = '';


        $description = $document->find('div.b-description__container-col tr');
        foreach ($description as $tr) {
            $text .= convert(trim(pq($tr)->text())) . ';';
            $text = preg_replace('/\s+/', ' ', $text);
        }

        preg_match('/ISBN[ ]+(.+?)[;]/', $text, $res);
        $inf['isbn'] = trim($res[1]);

        $inf['ean'] = trim(str_replace('-', '', $inf['isbn']));

        return $inf;
    }
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
}

function stop_time()
{
    global $spend_time;
    global $array_URL_books;
    global $count_saved_images;
    $hour = (int)date("h");
    $minute = (int)date("i");
    $second = (int)date("s");
    $sum = $hour * 3600 + $minute * 60 + $second;
    $spend_time = $sum - $spend_time;
    echo '
<div class="spendTime">
Потрачено времени: ' . $spend_time . ' сек.<br>
Добавлено в список книг: ' . count($array_URL_books) . '<br>' .
        'Сохранено книг: ' . $count_saved_images . '<br>' .
        '</div>';
}

function convert_To_utf8($arg_1)
{
    return iconv("cp1251", "utf-8", $arg_1);
}

function convert($arg_1)
{
    return iconv("utf-8", "cp1251", $arg_1);
}

?>
</body>
</html>