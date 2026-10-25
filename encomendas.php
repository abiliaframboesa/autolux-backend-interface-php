
<?php
$url = 'http://localhost:3000/encomendas';

$contexto = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$resposta = @file_get_contents($url, false, $contexto);
$encomendas = [];
$erro = null;

if ($resposta === false) {
    $erro = 'Não foi possível contactar a API de encomendas.';
} else {
    $dados = json_decode($resposta, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        $erro = 'A API devolveu uma resposta inválida.';
    } elseif (isset($dados['encomendas']) && is_array($dados['encomendas'])) {
        $encomendas = $dados['encomendas'];
    } elseif (array_is_list($dados)) {
        $encomendas = $dados;
    } else {
        $erro = 'Não foi possível interpretar a lista de encomendas.';
    }
}

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AutoLux - Encomendas a fornecedores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 16px;
            background: #f5f6f8;
            color: #222;
        }
        h1 { color: #17365d; }
        .cartao {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th { background: #17365d; color: white; }
        .erro { color: #a32121; }
    </style>
</head>
<body>
    <h1>AutoLux — Encomendas a fornecedores</h1>
    <p>Lista de encomendas obtida através da API Node.js.</p>

    <div class="cartao">
        <?php if ($erro !== null): ?>
            <p class="erro"><?= escapar($erro) ?></p>
        <?php elseif (count($encomendas) === 0): ?>
            <p>Não existem encomendas para apresentar.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fornecedor</th>
                        <th>Data</th>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($encomendas as $encomenda): ?>
                        <tr>
                            <td><?= escapar($encomenda['id'] ?? '') ?></td>
                            <td><?= escapar($encomenda['fornecedor_nome'] ?? $encomenda['nome_fornecedor'] ?? $encomenda['fornecedor'] ?? '') ?></td>
                            <td><?= escapar($encomenda['data_encomenda'] ?? $encomenda['data'] ?? '') ?></td>
                            <td><?= escapar($encomenda['estado'] ?? '') ?></td>
                            <td><?= escapar($encomenda['total'] ?? $encomenda['valor_total'] ?? '') ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
