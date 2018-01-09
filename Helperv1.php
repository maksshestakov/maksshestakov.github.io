<html>
<head>
    <title>Помощник для Biblio</title>
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
<form action="Helperv1.php" method="post">
    <input id="searchField" type="text" name="EAN">
    <input id="submitField" type="submit" value="Поиск"><br>
    <input type="checkbox" name="forOnce"
        <?php
        echo $_POST['forOnce'] ? 'checked' : '';
        ?>
    > Искать только первое совпадение?
</form>

<?php
require('lib/simple_html_dom.php');
ini_set('max_execution_time', 1000000);
$spend_time = 0;
zamer();
$array_For_Biblio = '';
$request = $_POST['EAN'];
$forOnce = $_POST['forOnce'];
$count_found_Biblio = 0;

if ($request) {
	$httpBibl = 'https://biblio.by/';
	$preficsBibl = 'catalogsearch/result/index/?cat=4921&dir=desc&order=relevance&q=';
	$pathBibl = convert_To_utf8($httpBibl . $preficsBibl . str_replace(' ', '+', $request));

	parse_Biblio_And_Get_Result($pathBibl);
	print_Table();
}

stop_time();


/*
*
*	Функции
*
*/
function get_Result_Book($book)
{
    $text =
        '<div class="mainTov">
	<div class="imageTov">
		<a href="' . $book['link'] . '" target="_blank">' .
        '<img src="' . $book['img'] . '" width="100%">
		</a>
	</div>
	<div class="infTov">' .
        '<table class="productTable">
			<tbody>
			<tr><td>Наименование: </td><td>' . $book['name'] . '</td></tr>' .
        '<tr><td>Автор(-ы): </td><td>' . $book['author'] . '</td></tr>' .
        '<tr><td>Год: </td><td>' . $book['year'] . '</td></tr>' .
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
    global $array_For_Biblio;
    global $count_found_Biblio;
    global $pathBibl;
    $text =
        '<table class="mainTable" cellpadding=5 cellspacing=0 border=1>
	<thead>
		<tr>
			<td><a target="_blank" href="' . $pathBibl . '">Biblio.by ' . $count_found_Biblio . '</a></td>
		</tr>
	</thead>
	<tbody>
	<tr>
		<td>' . $array_For_Biblio . '</td>
	</tr>
	</tbody>
</table>';
    echo $text;
    return $text;
}

function parse_Biblio_And_Get_Result($request)
{
    global $array_For_Biblio;
    global $forOnce;
    if ($html = file_get_html($request)) {

        if ($html->find('p.note-msg', 0)) {
            $array_For_Biblio .= "Не найдено товаров";
        } else if ($html->find('div[class="products-grid row-fluid"]', 0)) {
            $links = get_Links_Tovars($html);

            if ($forOnce) {
                $book = get_Information_On_Biblio($links[0]);
                $array_For_Biblio .= get_Result_Book($book);
            } else {
                $count_tovars = 5;
                foreach ($links as $link) {
                    if ($count_tovars > 0) {
                        $book = get_Information_On_Biblio($link);
                        $array_For_Biblio .= get_Result_Book($book);
                        $count_tovars--;
                    }
                }
            }
        }

    } else {
        $array_For_Biblio .= "Ошибка при запросе!";
    }
    $html->clear();
    unset($html);
}

function get_Links_Tovars($html)
{
    global $count_found_Biblio;
    $pages = array();
    $tovars = $html->find('h2.product-name');
    foreach ($tovars as $tovar) {
        array_push($pages, $tovar->find('a', 0)->href);
    }
    $count_found_Biblio = count($pages);
    return $pages;
}

function get_Information_On_Biblio($link_Tovar)
{
    $html = file_get_html($link_Tovar);
    $name = convert($html->find('h1', 0)->plaintext);

    $inf['link'] = $link_Tovar;
    $inf['name'] = trim(preg_replace("/&quot|;/", '', $name));
    $inf['img'] = $html->find('div[class="product-img-box span5"]', 0)->find('img', 0)->src;

    $additionals = $html->find('p.aditional-attribute');
    $text = '';
    foreach ($additionals as $additional) {
        $text .= convert(trim($additional->plaintext)) . ';';
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

    $price = $html->find('div.product-view', 0)->find('span.price', 0)->plaintext;
    preg_match('/[0-9]*,[0-9]*/', $price, $res);
    $inf['price'] = trim($res[0]);

    $res_price = str_replace(',', '.', $inf['price']) * 0.8;
    $inf['res_price'] = str_replace('.', ',', $res_price);

    $html->clear();
    unset($html);

    return $inf;
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