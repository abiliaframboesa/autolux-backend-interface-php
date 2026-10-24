<?php
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query('SELECT COUNT(*) AS total FROM pecas');
$resultado = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Teste AutoLux</title>
</head>
<body>
    <h1>AutoLux</h1>
    <p>Ligação à base de dados estabelecida com sucesso!</p>
    <p>Número de peças registadas: <?= (int) $resultado['total'] ?></p>
</body>
</html>