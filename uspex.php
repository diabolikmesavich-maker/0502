<?php
$i = isset($_GET["i"]) ? intval($_GET["i"]) : 0;

if($i==1) $st = "Данные успешно добавлены";
if($i==2) $st = "Записи успешно удалены";
if($i==3) $st = "Записи успешно обновлены";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Успешно</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .message-container {
            max-width: 600px;
            margin: 100px auto;
            background-color: white;
            padding: 40px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .big {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 30px;
        }
        
        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 16px;
        }
        
        a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <h4 class="big"><?php echo $st; ?></h4>
        <a href="deda.php">На главную</a>
    </div>
</body>
</html>