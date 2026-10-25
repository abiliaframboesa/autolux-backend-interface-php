<?php
$apiUrl = 'http://localhost:3000';

$fornecedores = [];
$mensagem = '';
$tipoMensagem = '';
$dadosFormulario = [];

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Obter fornecedores através da API Node.js
$contextoGet = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$respostaFornecedores = @file_get_contents(
    $apiUrl . '/fornecedores',
    false,
    $contextoGet
);

if ($respostaFornecedores !== false) {
    $dados = json_decode($respostaFornecedores, true);

    if (isset($dados['fornecedores']) && is_array($dados['fornecedores'])) {
        $fornecedores = $dados['fornecedores'];
    } elseif (is_array($dados) && array_is_list($dados)) {
        $fornecedores = $dados;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornecedorId = filter_input(
        INPUT_POST,
        'fornecedor_id',
        FILTER_VALIDATE_INT
    );

    $referencias = $_POST['referencia_peca'] ?? [];
    $nomes = $_POST['nome_peca'] ?? [];
    $quantidades = $_POST['quantidade'] ?? [];
    $precos = $_POST['preco_unitario'] ?? [];

    $pecas = [];
    $erro = '';

    if (!$fornecedorId || $fornecedorId <= 0) {
        $erro = 'Seleciona um fornecedor válido.';
    } elseif (
        !is_array($referencias) ||
        !is_array($nomes) ||
        !is_array($quantidades) ||
        !is_array($precos)
    ) {
        $erro = 'Os dados das peças são inválidos.';
    } else {
        $numeroLinhas = count($referencias);

        if (
            count($nomes) !== $numeroLinhas ||
            count($quantidades) !== $numeroLinhas ||
            count($precos) !== $numeroLinhas
        ) {
            $erro = 'Os dados das peças não estão completos.';
        } else {
            for ($i = 0; $i < $numeroLinhas; $i++) {
                $referencia = trim((string) $referencias[$i]);
                $nome = trim((string) $nomes[$i]);
                $quantidadeTexto = trim((string) $quantidades[$i]);
                $precoTexto = str_replace(
                    ',',
                    '.',
                    trim((string) $precos[$i])
                );

                if (
                    $referencia === '' ||
                    $nome === '' ||
                    !preg_match('/^[1-9][0-9]*$/', $quantidadeTexto) ||
                    !is_numeric($precoTexto) ||
                    (float) $precoTexto < 0
                ) {
                    $erro = 'Verifica a referência, o nome, a quantidade e o preço de cada peça.';
                    break;
                }

                $pecas[] = [
                    'referencia_peca' => $referencia,
                    'nome_peca' => $nome,
                    'quantidade' => (int) $quantidadeTexto,
                    'preco_unitario' => (float) $precoTexto
                ];
            }
        }
    }

    if ($erro === '' && count($pecas) === 0) {
        $erro = 'Adiciona pelo menos uma peça à encomenda.';
    }

    if ($erro !== '') {
        $mensagem = $erro;
        $tipoMensagem = 'erro';
    } else {
        $payload = json_encode([
            'fornecedor_id' => (int) $fornecedorId,
            'pecas' => $pecas
        ], JSON_UNESCAPED_UNICODE);

        $contextoPost = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);

        $resposta = @file_get_contents(
            $apiUrl . '/encomendas',
            false,
            $contextoPost
        );

        if ($resposta === false) {
            $mensagem = 'Não foi possível contactar a API. Confirma que o servidor Node.js está a funcionar.';
            $tipoMensagem = 'erro';
        } else {
            $resultado = json_decode($resposta, true);
            $codigoHttp = 0;

            if (isset($http_response_header)) {
                foreach ($http_response_header as $cabecalho) {
                    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $cabecalho, $correspondencia)) {
                        $codigoHttp = (int) $correspondencia[1];
                    }
                }
            }

            if ($codigoHttp === 201 && is_array($resultado)) {
                $mensagem = 'Encomenda #' .
                    escapar($resultado['id'] ?? '') .
                    ' criada com sucesso. Total: ' .
                    number_format((float) ($resultado['total'] ?? 0), 2, ',', '.') .
                    ' €.';
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = $resultado['erro'] ??
                    'Não foi possível criar a encomenda.';
                $tipoMensagem = 'erro';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova encomenda - AutoLux</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            color: #222;
            background: #f5f6f8;
        }

        h1, h2 {
            color: #183153;
        }

        .painel {
            background: white;
            padding: 22px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        label {
            display: block;
            margin: 12px 0 5px;
            font-weight: bold;
        }

        input, select {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
        }

        .peca {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ddd;
        }

        button {
            padding: 11px 16px;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
            margin: 8px 8px 0 0;
        }

        .principal {
            background: #183153;
            color: white;
        }

        .secundario {
            background: #e1e7ef;
            color: #183153;
        }

        .remover {
            background: #f8d7da;
            color: #842029;
        }

        .sucesso {
            padding: 12px;
            background: #d1e7dd;
            color: #0f5132;
            border-radius: 4px;
        }

        .erro {
            padding: 12px;
            background: #f8d7da;
            color: #842029;
            border-radius: 4px;
        }

        @media (max-width: 700px) {
            .peca {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>AutoLux — Nova encomenda a fornecedor</h1>

    <p>
        <a href="fornecedores.php">Consultar fornecedores</a> |
        <a href="encomendas.php">Consultar encomendas</a>
    </p>

    <?php if ($mensagem !== ''): ?>
        <p class="<?= escapar($tipoMensagem) ?>">
            <?= escapar($mensagem) ?>
        </p>
    <?php endif; ?>

    <?php if (count($fornecedores) === 0): ?>
        <div class="painel">
            Não foi possível carregar fornecedores. Confirma que a API Node.js
            está ativa e que existem fornecedores registados.
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="painel">
                <h2>1. Selecionar fornecedor</h2>

                <label for="fornecedor_id">Fornecedor</label>
                <select id="fornecedor_id" name="fornecedor_id" required>
                    <option value="">-- Seleciona um fornecedor --</option>

                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option
                            value="<?= escapar($fornecedor['id'] ?? '') ?>"
                            <?= (
                                isset($_POST['fornecedor_id']) &&
                                (string) $_POST['fornecedor_id'] ===
                                (string) ($fornecedor['id'] ?? '')
                            ) ? 'selected' : '' ?>
                        >
                            <?= escapar($fornecedor['nome'] ?? 'Fornecedor sem nome') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="painel">
                <h2>2. Peças da encomenda</h2>

                <div id="lista-pecas">
                    <div class="peca">
                        <div>
                            <label>Referência</label>
                            <input name="referencia_peca[]" required>
                        </div>

                        <div>
                            <label>Nome da peça</label>
                            <input name="nome_peca[]" required>
                        </div>

                        <div>
                            <label>Quantidade</label>
                            <input
                                name="quantidade[]"
                                type="number"
                                min="1"
                                step="1"
                                required
                            >
                        </div>

                        <div>
                            <label>Preço unitário (€)</label>
                            <input
                                name="preco_unitario[]"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            >
                        </div>

                        <button
                            type="button"
                            class="remover"
                            onclick="removerPeca(this)"
                        >
                            Remover
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    class="secundario"
                    onclick="adicionarPeca()"
                >
                    + Adicionar peça
                </button>
            </div>

            <button type="submit" class="principal">
                Criar encomenda
            </button>
        </form>
    <?php endif; ?>

    <script>
        function adicionarPeca() {
            const lista = document.getElementById('lista-pecas');
            const modelo = lista.querySelector('.peca');
            const novaPeca = modelo.cloneNode(true);

            novaPeca.querySelectorAll('input').forEach(function (campo) {
                campo.value = '';
            });

            lista.appendChild(novaPeca);
        }

        function removerPeca(botao) {
            const lista = document.getElementById('lista-pecas');

            if (lista.querySelectorAll('.peca').length > 1) {
                botao.closest('.peca').remove();
            } else {
                alert('A encomenda deve conter pelo menos uma peça.');
            }
        }
    </script>
</body>
</html>
