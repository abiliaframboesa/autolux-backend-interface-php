<?php
require_once __DIR__ . '/config/database.php';

$erro = '';
$sucesso = '';
$quantidadesSelecionadas = [];
$clienteSelecionado = '';
$pagamentoSelecionado = '';

$metodosPagamento = [
    'Dinheiro',
    'Cartão',
    'Transferência bancária'
];

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteSelecionado = $_POST['cliente_id'] ?? '';
    $pagamentoSelecionado = $_POST['tipo_pagamento'] ?? '';
    $quantidadesSelecionadas = $_POST['quantidades'] ?? [];

    try {
        if (
            !is_string($clienteSelecionado) ||
            filter_var($clienteSelecionado, FILTER_VALIDATE_INT) === false ||
            (int) $clienteSelecionado <= 0
        ) {
            throw new Exception('Seleciona um cliente válido.');
        }

        if (
            !is_string($pagamentoSelecionado) ||
            !in_array($pagamentoSelecionado, $metodosPagamento, true)
        ) {
            throw new Exception('Seleciona um método de pagamento válido.');
        }

        if (!is_array($quantidadesSelecionadas)) {
            throw new Exception('As quantidades indicadas são inválidas.');
        }

        $itens = [];

        foreach ($quantidadesSelecionadas as $pecaId => $quantidade) {
            if (
                !is_string((string) $pecaId) ||
                filter_var($pecaId, FILTER_VALIDATE_INT) === false ||
                (int) $pecaId <= 0
            ) {
                throw new Exception('Foi indicada uma peça inválida.');
            }

            if (
                !is_string($quantidade) ||
                !ctype_digit($quantidade)
            ) {
                throw new Exception('As quantidades devem ser números inteiros não negativos.');
            }

            $quantidade = (int) $quantidade;

            if ($quantidade > 0) {
                $itens[(int) $pecaId] = $quantidade;
            }
        }

        if (empty($itens)) {
            throw new Exception('Seleciona pelo menos uma peça com quantidade superior a zero.');
        }

        $pdo->beginTransaction();

        // Confirmar que o cliente existe
        $stmtCliente = $pdo->prepare(
            'SELECT id FROM clientes WHERE id = ?'
        );
        $stmtCliente->execute([(int) $clienteSelecionado]);

        if (!$stmtCliente->fetch()) {
            throw new Exception('O cliente selecionado não existe.');
        }

        $total = 0;
        $linhasVenda = [];

        // Bloquear temporariamente as peças para verificar o stock
        foreach ($itens as $pecaId => $quantidade) {
            $stmtPeca = $pdo->prepare(
                'SELECT id, nome, preco, stock
                 FROM pecas
                 WHERE id = ?
                 FOR UPDATE'
            );
            $stmtPeca->execute([$pecaId]);
            $peca = $stmtPeca->fetch();

            if (!$peca) {
                throw new Exception('Uma das peças selecionadas não existe.');
            }

            if ($quantidade > (int) $peca['stock']) {
                throw new Exception(
                    'Stock insuficiente para a peça: ' . $peca['nome'] .
                    '. Stock disponível: ' . $peca['stock'] . '.'
                );
            }

            $precoUnitario = (float) $peca['preco'];
            $total += $precoUnitario * $quantidade;

            $linhasVenda[] = [
                'peca_id' => $pecaId,
                'quantidade' => $quantidade,
                'preco_unitario' => $precoUnitario
            ];
        }

        // Criar a venda
        $stmtVenda = $pdo->prepare(
            'INSERT INTO vendas (cliente_id, tipo_pagamento, total)
             VALUES (?, ?, ?)'
        );

        $stmtVenda->execute([
            (int) $clienteSelecionado,
            $pagamentoSelecionado,
            number_format($total, 2, '.', '')
        ]);

        $vendaId = (int) $pdo->lastInsertId();

        // Registar as peças e atualizar o stock
        $stmtLinha = $pdo->prepare(
            'INSERT INTO venda_pecas
             (venda_id, peca_id, quantidade, preco_unitario)
             VALUES (?, ?, ?, ?)'
        );

        $stmtStock = $pdo->prepare(
            'UPDATE pecas
             SET stock = stock - ?
             WHERE id = ? AND stock >= ?'
        );

        foreach ($linhasVenda as $linha) {
            $stmtLinha->execute([
                $vendaId,
                $linha['peca_id'],
                $linha['quantidade'],
                number_format($linha['preco_unitario'], 2, '.', '')
            ]);

            $stmtStock->execute([
                $linha['quantidade'],
                $linha['peca_id'],
                $linha['quantidade']
            ]);

            if ($stmtStock->rowCount() !== 1) {
                throw new Exception(
                    'Não foi possível atualizar o stock de uma das peças.'
                );
            }
        }

        $pdo->commit();

        $sucesso = 'Venda #' . $vendaId .
            ' registada com sucesso. Total: ' .
            number_format($total, 2, ',', '.') . ' €.';

        $clienteSelecionado = '';
        $pagamentoSelecionado = '';
        $quantidadesSelecionadas = [];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        if ($e instanceof PDOException) {
            error_log($e->getMessage());
            $erro = 'Ocorreu um erro na base de dados. A venda não foi registada.';
        } else {
            $erro = $e->getMessage();
        }
    }
}

// Obter clientes e peças atualizados
$clientes = $pdo->query(
    'SELECT id, nome FROM clientes ORDER BY nome ASC'
)->fetchAll();

$pecas = $pdo->query(
    'SELECT id, referencia, nome, marca, preco, stock
     FROM pecas
     ORDER BY nome ASC'
)->fetchAll();

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoLux - Nova Venda</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
            background: #f4f6f8;
            color: #222;
        }

        h1 { color: #183153; }

        form, .secao {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: bold;
        }

        select, input, button {
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            max-width: 100%;
        }

        select, input { box-sizing: border-box; }

        button {
            margin-top: 18px;
            background: #183153;
            color: white;
            cursor: pointer;
        }

        .peca {
            border-top: 1px solid #ddd;
            padding: 12px 0;
        }

        .peca input { width: 120px; }

        .aviso { color: #555; }

        .mensagem {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 16px;
        }

        .erro {
            background: #ffe8e8;
            color: #8b0000;
        }

        .sucesso {
            background: #e5f6e9;
            color: #14532d;
        }
    </style>
</head>
<body>
    <h1>AutoLux — Registar Venda</h1>
    <p>Seleciona o cliente e as peças que pretende comprar.</p>

    <?php if ($erro !== ''): ?>
        <div class="mensagem erro" role="alert">
            <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== ''): ?>
        <div class="mensagem sucesso" role="status">
            <?= escapar($sucesso) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="vendas.php">
        <section class="secao">
            <h2>1. Dados do cliente</h2>

            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id" required>
                <option value="">Seleciona um cliente</option>

                <?php foreach ($clientes as $cliente): ?>
                    <option
                        value="<?= (int) $cliente['id'] ?>"
                        <?= (string) $clienteSelecionado === (string) $cliente['id'] ? 'selected' : '' ?>
                    >
                        <?= escapar($cliente['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tipo_pagamento">Tipo de pagamento</label>
            <select id="tipo_pagamento" name="tipo_pagamento" required>
                <option value="">Seleciona o pagamento</option>

                <?php foreach ($metodosPagamento as $metodo): ?>
                    <option
                        value="<?= escapar($metodo) ?>"
                        <?= $pagamentoSelecionado === $metodo ? 'selected' : '' ?>
                    >
                        <?= escapar($metodo) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </section>

        <section class="secao">
            <h2>2. Peças e quantidades</h2>
            <p class="aviso">
                Introduz a quantidade pretendida. As quantidades são
                novamente verificadas no servidor antes de guardar a venda.
            </p>

            <?php foreach ($pecas as $peca): ?>
                <?php $pecaId = (int) $peca['id']; ?>

                <div class="peca">
                    <label for="peca_<?= $pecaId ?>">
                        <?= escapar($peca['nome']) ?> —
                        <?= escapar($peca['referencia']) ?>
                    </label>

                    <p>
                        Marca: <?= escapar($peca['marca']) ?> |
                        Preço: <?= number_format((float) $peca['preco'], 2, ',', '.') ?> € |
                        Stock: <?= (int) $peca['stock'] ?>
                    </p>

                    <label for="peca_<?= $pecaId ?>">Quantidade</label>
                    <input
                        type="number"
                        id="peca_<?= $pecaId ?>"
                        name="quantidades[<?= $pecaId ?>]"
                        min="0"
                        max="<?= (int) $peca['stock'] ?>"
                        value="<?= escapar($quantidadesSelecionadas[$pecaId] ?? '0') ?>"
                        <?= (int) $peca['stock'] === 0 ? 'disabled' : '' ?>
                    >
                </div>
            <?php endforeach; ?>

            <button type="submit">Registar venda</button>
        </section>
    </form>

    <p><a href="catalogo.php">Voltar ao catálogo</a></p>
</body>
</html>
