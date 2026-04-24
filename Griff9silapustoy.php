<?php
$servername = "localhost";
$username = "bgcdarep";
$password = "i3h7Gj";
$dbname = "bgcdarep_m1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if (isset($_POST['delete_selected']) && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    $deleted_count = 0;
    
    foreach ($selected_ids as $id) {
        $id = intval($id); 
        $delete_sql = "DELETE FROM tovars WHERE id = $id";
        
        if ($conn->query($delete_sql) === TRUE) {
            $deleted_count++;
        }
    }
    
    if ($deleted_count > 0) {
        $message = "Успешно удалено записей: " . $deleted_count;
    } else {
        $message = "Ошибка при удалении записей";
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
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
        
        .checkbox-col {
            width: 30px;
            text-align: center;
        }
        
        .actions {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .delete-selected-btn {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .delete-selected-btn:hover {
            background-color: #d32f2f;
        }
        
        .select-all {
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body>

<h3>Управление списком товаров</h3>

<?php if (isset($message)): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post" action="">
    <div class="actions">
        <label class="select-all">
            <input type="checkbox" id="selectAll" onclick="toggleAll(this)"> Выбрать все
        </label>
        <button type="submit" name="delete_selected" class="delete-selected-btn" onclick="return confirm('Удалить выбранные товары?')">Удалить выбранные</button>
    </div>

    <table>
        <tr>
            <th class="checkbox-col"></th>
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
                echo "<td class='checkbox-col'><input type='checkbox' name='selected_ids[]' value='" . $row["id"] . "' class='item-checkbox'></td>";
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
</form>

<script>
function toggleAll(source) {
    checkboxes = document.getElementsByClassName('item-checkbox');
    for(var i=0; i<checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</body>
</html>