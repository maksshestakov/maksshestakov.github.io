<html>
<head>
    <title>������ ��� OZ � Biblio</title>
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
    </style>
</head>
<body>
<form enctype="multipart/form-data" action="ParserPrice.php" method="POST">
    <table>
        <tr>
            <td>������� ����� ������� �����-����:</td>
            <td><input class="searchField" type="text" name="EAN"></td>
        </tr>
        <tr>
            <td>������� ����� ������� ������������:</td>
            <td><input class="searchField" type="text" name="name"></td>
        </tr>
        <tr>
            <td>������� ����� ������� ����1:</td>
            <td><input class="searchField" type="text" name="cena1"></td>
        </tr>
        <tr>
            <td>������� ����� ������� ����2:</td>
            <td><input class="searchField" type="text" name="cena2"></td>
        </tr>
        <tr>
            <td>�������� ����:</td>
            <td><input type="file" name="excel"
                       accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/excel">
            </td>
        </tr>
	<tr>
	    <td>��������� � ����?</td>
	    <td>
		<input type="checkbox" name="save" checked
            <?php echo $_POST['save'] ? 'checked' : '';?>
		>
	    </td>
	</tr>

    </table>
    <input id="submitField" type="submit" value="���������"><br>
</form>
</body>
</html>