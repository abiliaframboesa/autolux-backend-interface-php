<?php
$url = 'http://localhost:3000/fornecedores';

$contexto = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$resposta = @file_get_contents($url, false, $contexto);

$fornecedores = [];
$erro = null;

if ($resposta === false) {
    $erro = 'Não foi possível contactar a API de fornecedores.';
} else {
    $dados = json_decode($resposta, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        $erro = 'A API devolveu uma resposta inválida.';
    } else {
        // A API pode devolver diretamente uma lista ou
        // um objeto que contenha a lista em "fornecedores".
        if (isset($dados['fornecedores']) && is_array($dados['fornecedores'])) {
            $fornecedores = $dados['fornecedores'];
        } elseif (array_is_list($dados)) {
            $fornecedores = $dados;
        } else {
            $erro = 'Não foi possível interpretar a lista de fornecedores.';
        }
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
    <title>AutoLux - Fornecedores</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
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
    <h1>AutoLux — Fornecedores</h1>
    <p>Lista de fornecedores obtida através da API Node.js.</p>

    <div class="cartao">
        <?php if ($erro !== null): ?>
            <p class="erro"><?= escapar($erro) ?></p>
        <?php elseif (count($fornecedores) === 0): ?>
            <p>Não existem fornecedores para apresentar.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <tr>
                            <td><?= escapar($fornecedor['id'] ?? '') ?></td>
                            <td><?= escapar($fornecedor['nome'] ?? $fornecedor['name'] ?? '') ?></td>
                            <td><?= escapar($fornecedor['email'] ?? '') ?></td>
                            <td><?= escapar($fornecedor['telefone'] ?? $fornecedor['telemovel'] ?? $fornecedor['phone'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
