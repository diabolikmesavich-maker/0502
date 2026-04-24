<?php
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM tovars WHERE id = $delete_id";
    
    if ($conn->query($delete_sql) === TRUE) {
        $message = "Запись успешно удалена";
    } else {
        $message = "Ошибка при удалении: " . $conn->error;
    }
}

$sql = "SELECT * FROM tovars";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Управление товарами</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        h3 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .delete-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }
    </style>
</head>
<body>

<h3>Управление списком товаров</h3>

<?php if (isset($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<table>
    <tr>
        <th>Номер</th>
        <th>Наименование</th>
        <th>Цена</th>
        <th>Количество</th>
        <th>Дата реализации</th>
        <th>Действие</th>
    </tr>
    
    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["name"] . "</td>";
            echo "<td>" . $row["cena"] . " руб.</td>";
            echo "<td>" . $row["kol"] . "</td>";
            echo "<td>" . ($row["srok"] ? $row["srok"] : '-') . "</td>";
            echo "<td><a href='?delete_id=" . $row["id"] . "' class='delete-btn' onclick='return confirm(\"Удалить товар?\")'>Удалить</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='no-data'>Нет товаров в базе данных</td></tr>";
    }
    
    $conn->close();
    ?>
</table>

</body>
</html>