<?php
require_once __DIR__ . '/config/database.php';

// Obter os valores dos filtros
$marca = trim($_GET['marca'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$precoMin = trim($_GET['preco_min'] ?? '');
$precoMax = trim($_GET['preco_max'] ?? '');

// Construir a consulta SQL
$sql = 'SELECT id, referencia, nome, marca, tipo, preco, stock
        FROM pecas
        WHERE 1 = 1';

$parametros = [];

if ($marca !== '') {
    $sql .= ' AND marca = :marca';
    $parametros['marca'] = $marca;
}

if ($tipo !== '') {
    $sql .= ' AND tipo = :tipo';
    $parametros['tipo'] = $tipo;
}

if ($precoMin !== '' && is_numeric($precoMin) && $precoMin >= 0) {
    $sql .= ' AND preco >= :preco_min';
    $parametros['preco_min'] = (float) $precoMin;
}

if ($precoMax !== '' && is_numeric($precoMax) && $precoMax >= 0) {
    $sql .= ' AND preco <= :preco_max';
    $parametros['preco_max'] = (float) $precoMax;
}

$sql .= ' ORDER BY nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$pecas = $stmt->fetchAll();

// Obter marcas e tipos existentes para preencher os filtros
$marcas = $pdo->query(
    'SELECT DISTINCT marca FROM pecas ORDER BY marca'
)->fetchAll(PDO::FETCH_COLUMN);

$tipos = $pdo->query(
    'SELECT DISTINCT tipo FROM pecas ORDER BY tipo'
)->fetchAll(PDO::FETCH_COLUMN);

// Função para apresentar texto com segurança
function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoLux - Catálogo de Peças</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            background: #f4f6f8;
            color: #222;
        }

        h1 {
            color: #183153;
        }

        form, .peca {
            background: white;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        input, select, button {
            padding: 9px;
            border: 1px solid #bbb;
            border-radius: 4px;
        }

        button {
            background: #183153;
            color: white;
            cursor: pointer;
        }

        .pecas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 15px;
        }

        .peca h2 {
            font-size: 18px;
        }

        .preco {
            font-size: 20px;
            font-weight: bold;
            color: #176b42;
        }
    </style>
</head>
<body>
    <h1>AutoLux — Catálogo de Peças</h1>
    <p>Consulta as peças disponíveis e utiliza os filtros para encontrar o que procuras.</p>

    <form method="GET" action="catalogo.php">
        <div class="filtros">
            <div class="campo">
                <label for="marca">Marca</label>
                <select name="marca" id="marca">
                    <option value="">Todas as marcas</option>
                    <?php foreach ($marcas as $opcao): ?>
                        <option value="<?= escapar($opcao) ?>"
                            <?= $marca === $opcao ? 'selected' : '' ?>>
                            <?= escapar($opcao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="tipo">Tipo de peça</label>
                <select name="tipo" id="tipo">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($tipos as $opcao): ?>
                        <option value="<?= escapar($opcao) ?>"
                            <?= $tipo === $opcao ? 'selected' : '' ?>>
                            <?= escapar($opcao) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="preco_min">Preço mínimo (€)</label>
                <input type="number" name="preco_min" id="preco_min"
                    min="0" step="0.01"
                    value="<?= escapar($precoMin) ?>">
            </div>

            <div class="campo">
                <label for="preco_max">Preço máximo (€)</label>
                <input type="number" name="preco_max" id="preco_max"
                    min="0" step="0.01"
                    value="<?= escapar($precoMax) ?>">
            </div>

            <button type="submit">Filtrar peças</button>
            <a href="catalogo.php">Limpar filtros</a>
        </div>
    </form>

    <p>Peças encontradas: <strong><?= count($pecas) ?></strong></p>

    <div class="pecas">
        <?php if (count($pecas) === 0): ?>
            <p>Não foram encontradas peças com os filtros selecionados.</p>
        <?php else: ?>
            <?php foreach ($pecas as $peca): ?>
                <article class="peca">
                    <h2><?= escapar($peca['nome']) ?></h2>
                    <p>Referência: <?= escapar($peca['referencia']) ?></p>
                    <p>Marca: <?= escapar($peca['marca']) ?></p>
                    <p>Tipo: <?= escapar($peca['tipo']) ?></p>
                    <p class="preco">
                        <?= number_format((float) $peca['preco'], 2, ',', '.') ?> €
                    </p>
                    <p>Stock disponível: <?= (int) $peca['stock'] ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
