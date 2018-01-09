<html>
<head>
    <title>Помощник для OZ и Biblio</title>
    <meta charset="windows-1251">
    <style>
        form {
            margin: 20pt auto;
            text-align: center;
        }

        div.mainTov {
            position: relative;
            width: 500pt;
            padding: 5pt;
            overflow: hidden;
            vertical-align: middle;
            margin: 0px;
            min-height: 180pt;
        }

        div.imageTov {
            float: left;
            text-align: center;
            width: 30%;
        }

        div.infTov {
            float: left;
            width: 70%;
        }

        img {
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 148pt;
        }

        table.mainTable {
            margin: 0 auto;
        }

        table.productTable {
            width: 100%;
        }

        table.productTable tr td:first-child {
            width: 35%;
        }

        thead {
            font-weight: bold;
        }

        td {
            vertical-align: top;
            border-bottom: 1px solid black;
        }

        a {
            display: block;
            height: 100%;
        }

        .spendTime {
            position: absolute;
            top: 0px;
            right: 0px;
        }

        #searchField {
            width: 300pt;
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
        }

    </style>
</head>

<body>

<form action="Helperv2.php" method="post">
    <p>
        <input id="searchField" type="text" name="EAN">
        <input id="submitField" type="submit" value="Поиск"><br>
        <input type="checkbox" name="forOnce"
            <?php
            echo $_POST['forOnce'] ? 'checked' : '';
            ?>
        > Искать только первое совпадение?
    </p>
</form>

<?php
require('lib/phpQuery.php');
ini_set('max_execution_time', 1000000);
$spend_time = 0;
zamer();
$array_For_Biblio = '';
$array_For_OZ = '';
$count_found_Biblio = 0;
$count_found_OZ = 0;

$request = $_POST['EAN'];
$forOnce = $_POST['forOnce'];

if ($request) {
    $httpBibl = 'https://biblio.by/';
    $httpOZ = 'https://oz.by';

    $preficsBibl = 'catalogsearch/result/index/?cat=4921&dir=desc&order=relevance&q=';
    $preficsOZ = '/search/?availability=1%3B2&c=1101523&sort=relev_desc&q=';

    $pathBibl = convert_To_utf8($httpBibl . $preficsBibl . str_replace(' ', '+', $request));
    $pathOZ = convert_To_utf8($httpOZ . $preficsOZ . str_replace(' ', '+', $request));

    parse_Biblio_And_Get_Result($pathBibl);
    parse_OZ_And_Get_Result($pathOZ);

    print_Table();
}

stop_time();


/*
*
*	Функции для отображения верстки информации о книгах
*
*/
function get_Result_Book($book)
{
    $text = '
<div class="mainTov">
	<div class="imageTov">
	<a href="' . $book['link'] . '" target="_blank">
		<img src="' . $book['img'] . '">
	</a>
	</div>
	<div class="infTov">' .
        '<table class="productTable">
	<tbody>' .
        '<tr><td>Наименование: </td><td>' . $book['name'] . '</td></tr>' .
        '<tr><td>Автор(-ы): </td><td>' . $book['author'] . '</td></tr>' .
        '<tr><td>Год: </td><td>' . $book['year'] . '</td></tr>' .
        '<tr><td>Количество страниц: </td><td>' . $book['kol_str'] . '</td></tr>' .
        '<tr><td>Издательство: </td><td>' . $book['izd'] . '</td></tr>' .
        '<tr><td>Формат: </td><td>' . $book['format'] . '</td></tr>' .
        '<tr><td>Штрих: </td><td>' . $book['EAN'] . '</td></tr>' .
        '<tr><td>ISBN: </td><td>' . $book['isbn'] . '</td></tr>' .
        '<tr><td>Цена: </td><td><strong>' . $book['price'] . '</strong></td></tr>' .
        //'<tr><td>Рекомендуемая цена: </td><td><strong>'.$book['res_price'].'</strong></td></tr>'.
        '</tbody>
		</table>' .
        '</div>
</div>';
    return $text;
}

function print_Table()
{
    global $count_found_Biblio;
    global $count_found_OZ;
    global $array_For_Biblio;
    global $array_For_OZ;
    global $pathBibl;
    global $pathOZ;
    $text = '
<table class="mainTable" cellpadding=5 cellspacing=0 border=1>
	<thead>
		<tr>
			<td><a target="_blank" href = "' . $pathBibl . '">Biblio.by ' . $count_found_Biblio . '</a></td>
			<td><a target="_blank" href = "' . $pathOZ . '">OZ.by ' . $count_found_OZ . '</a></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>' . $array_For_Biblio . '</td>
			<td>' . $array_For_OZ . '</td>
		</tr>
	</tbody>
</table>';
    echo $text;
    return $text;
}

/*
*
*
* Для OZ
*
*
*/

function parse_OZ_And_Get_Result($request)
{
    global $array_For_OZ;
    global $forOnce;
    global $count_found_OZ;

    if ($data = get_web_page($request)) {

        $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

        if (count($document->find('div.search-info-results__content'))) {

            $array_For_OZ .= 'Не найдено товаров';

        } else if (count($document->find('div.top-filters'))) {
            $links = get_Links_Tovars_OZ($document);

            if ($forOnce) {
                $book = get_Information_On_OZ($links[0]);
                $array_For_OZ .= get_Result_Book($book);
            } else {
                $count_tovars = 5;
                foreach ($links as $link) {
                    if ($count_tovars > 0) {
                        $book = get_Information_On_OZ($link);
                        $array_For_OZ .= get_Result_Book($book);
                        $count_tovars--;
                    }
                }
            }
        } else {
            $count_found_OZ = 1;
            $book = get_Information_On_OZ($request);
            $array_For_OZ .= get_Result_Book($book);
        }
    } else {
        echo "Ошибка при запросе!";
    }
}

function get_Links_Tovars_OZ($document)
{
    global $count_found_OZ;
    global $httpOZ;
    $pages = array();
    $tovars = $document->find('li.viewer-type-card__li ');
    foreach ($tovars as $tovar) {
        array_push($pages, $httpOZ . pq($tovar)->find('a:first')->attr('href'));
    }
    $count_found_OZ = count($pages);
    return $pages;
}


function get_Information_On_OZ($pathOZ)
{
    $data = get_web_page(convert_To_utf8($pathOZ));

    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    $name = $document->find('div.b-product-title__heading:first');
    $author = $document->find('div.b-product-title__author a');
    $image = $document->find('div.b-product-photo a');
    $price = $document->find('div.b-product-control__row:first');

    $inf['name'] = convert(pq($name)->text());
    $inf['author'] = convert(pq($author)->text());
    $inf['link'] = convert($pathOZ);
    $inf['img'] = convert(pq($image)->attr('href'));

    $text = '';
    $description = $document->find('div.b-description__container-col tr');

    foreach ($description as $tr) {
        $text .= convert(trim(pq($tr)->text())) . ';';
        $text = preg_replace('/\s+/', ' ', $text);
    }

    preg_match('/Издательство[ ]+(.+?)[;]/', $text, $res);
    $inf['izd'] = $res[1];

    preg_match('/Год издания[ ]+(.+?)[;]/', $text, $res);
    $inf['year'] = $res[1];

    preg_match('/Страниц[ ]+(.+?)[;]/', $text, $res);
    $inf['kol_str'] = $res[1];

    preg_match('/Формат[ ]+(.+?)[;]/', $text, $res);
    $inf['format'] = $res[1];

    preg_match('/ISBN[ ]+(.+?)[;]/', $text, $res);
    $inf['isbn'] = $res[1];

    $inf['EAN'] = str_replace('-', '', $inf['isbn']);

    preg_match('/Вес[ ]+(.+?)[;]/', $text, $res);
    $inf['ves'] = preg_replace('/[^0-9,]/', '', $res[1]);

    $inf['price'] = preg_replace('/[^0-9,]/', '', convert(pq($price)->text()));

    $inf['res_price'] = str_replace('.', ',', str_replace(',', '.', $inf['price']) * 0.8);

    return $inf;
}

/*
*
*
* Для библио
*
*
*/

function parse_Biblio_And_Get_Result($request)
{
    global $array_For_Biblio;
    global $forOnce;

    if ($data = get_web_page($request)) {
        $html = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

        if (count($html->find('p.note-msg:first'))) {
            $array_For_Biblio .= "Не найдено товаров";
        } else
            if (count($html->find('div[class*="products-grid row-fluid"]:first'))) {
                $links = get_Links_Tovars_Biblio($html);

                if ($forOnce) {

                    $book = get_Information_On_Biblio($links[0]);
                    $array_For_Biblio .= get_Result_Book($book);

                } else {

                    $count_tovars = 5;
                    foreach ($links as $link) {

                        if ($count_tovars > 0) {
                            $book = get_Information_On_Biblio($link);
                            $array_For_Biblio .= get_Result_Book($book);
                            $count_tovars -= 1;
                        }
                    }
                }
            } else {
                $array_For_Biblio .= "Не найдено товаров";
            }
    } else {
        $array_For_Biblio .= "Ошибка при запросе!";
    }
}

function get_Links_Tovars_Biblio($html)
{
    global $count_found_Biblio;
    $pages = array();
    $tovars = $html->find('h2.product-name a');

    foreach ($tovars as $tovar) {
        array_push($pages, pq($tovar)->attr('href'));
    }

    $count_found_Biblio = count($pages);
    return $pages;
}

function get_Information_On_Biblio($link_Tovar)
{
    $data = get_web_page($link_Tovar);
    $document = phpQuery::newDocumentHTML($data, $charset = 'cp1251');

    $name = convert(pq($document->find('h1:first'))->text());

    $inf['link'] = $link_Tovar;
    $inf['name'] = trim(preg_replace("/&quot|;/", '', $name));
    $inf['img'] = $document->find('div[class*="product-img-box span5"]:first')->find('img:first')->attr('src');

    $additionals = $document->find('p.aditional-attribute');
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

    $price = $document->find('div.product-view', 0)->find('span.price', 0)->text();
    preg_match('/[0-9]*,[0-9]*/', $price, $res);
    $inf['price'] = trim($res[0]);

    $res_price = str_replace(',', '.', $inf['price']) * 0.8;
    $inf['res_price'] = str_replace('.', ',', $res_price);

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
    $hour = (int)date("h");
    $minute = (int)date("i");
    $second = (int)date("s");
    $sum = $hour * 3600 + $minute * 60 + $second;
    $spend_time = $sum - $spend_time;
    echo '<div class="spendTime">Потрачено времени: ' . $spend_time . ' сек.</div>';
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