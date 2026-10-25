
<?php
require_once __DIR__ . '/config/database.php';

$apiUrl = 'http://localhost:3000';

$fornecedores = [];
$catalogoPecas = [];
$pecasPorId = [];

$mensagem = '';
$tipoMensagem = '';
$fornecedorSelecionado = '';

$linhasFormulario = [
    ['peca_id' => '', 'quantidade' => '1']
];

function escapar($valor) {
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| 1. Carregar fornecedores através da API Node.js
|--------------------------------------------------------------------------
*/
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

    if (
        is_array($dados) &&
        isset($dados['fornecedores']) &&
        is_array($dados['fornecedores'])
    ) {
        $fornecedores = $dados['fornecedores'];
    } elseif (
        is_array($dados) &&
        array_is_list($dados)
    ) {
        $fornecedores = $dados;
    }
}

/*
|--------------------------------------------------------------------------
| 2. Carregar o catálogo de peças
|--------------------------------------------------------------------------
*/
try {
    $stmt = $pdo->query(
        'SELECT
            id,
            referencia,
            nome,
            marca,
            fornecedor_id,
            tipo,
            preco,
            stock
         FROM pecas
         ORDER BY referencia ASC'
    );

    $catalogoPecas = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());

    $mensagem = 'Não foi possível carregar o catálogo de peças.';
    $tipoMensagem = 'erro';
}

foreach ($catalogoPecas as $peca) {
    $pecasPorId[(string) $peca['id']] = $peca;
}

/*
|--------------------------------------------------------------------------
| 3. Processar o formulário
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fornecedorSelecionado = trim(
        (string) ($_POST['fornecedor_id'] ?? '')
    );

    $idsPecas = $_POST['peca_id'] ?? [];
    $quantidades = $_POST['quantidade'] ?? [];

    $linhasFormulario = [];

    if (is_array($idsPecas) && is_array($quantidades)) {
        foreach ($idsPecas as $i => $idPeca) {
            $linhasFormulario[] = [
                'peca_id' => (string) $idPeca,
                'quantidade' => (string) ($quantidades[$i] ?? '1')
            ];
        }
    }

    if (count($linhasFormulario) === 0) {
        $linhasFormulario = [
            ['peca_id' => '', 'quantidade' => '1']
        ];
    }

    $erro = '';
    $pecas = [];

    // Confirmar que o fornecedor existe na resposta da API.
    $fornecedorId = filter_var(
        $fornecedorSelecionado,
        FILTER_VALIDATE_INT
    );

    $fornecedorValido = false;

    if ($fornecedorId !== false && $fornecedorId > 0) {
        foreach ($fornecedores as $fornecedor) {
            if (
                isset($fornecedor['id']) &&
                (string) $fornecedor['id'] ===
                (string) $fornecedorId
            ) {
                $fornecedorValido = true;
                break;
            }
        }
    }

    if (!$fornecedorValido) {
        $erro = 'Seleciona um fornecedor válido.';

    } elseif (
        !is_array($idsPecas) ||
        !is_array($quantidades) ||
        count($idsPecas) === 0 ||
        count($idsPecas) !== count($quantidades)
    ) {
        $erro = 'Os dados das peças são inválidos.';

    } else {

        foreach ($idsPecas as $i => $idPeca) {

            $idPeca = (string) $idPeca;
            $quantidadeTexto = trim(
                (string) ($quantidades[$i] ?? '')
            );

            if (
                !ctype_digit($idPeca) ||
                !isset($pecasPorId[$idPeca])
            ) {
                $erro = 'Seleciona uma peça válida do catálogo.';
                break;
            }

            if (
                !preg_match('/^[1-9][0-9]*$/', $quantidadeTexto) ||
                (float) $quantidadeTexto > 2147483647
            ) {
                $erro = 'A quantidade deve ser um número inteiro positivo.';
                break;
            }

            $pecaCatalogo = $pecasPorId[$idPeca];

            // Confirmar que a peça pertence ao fornecedor escolhido.
            if (
                (string) ($pecaCatalogo['fornecedor_id'] ?? '') !==
                (string) $fornecedorId
            ) {
                $erro = 'A peça ' .
                    $pecaCatalogo['referencia'] .
                    ' não está associada ao fornecedor selecionado.';
                break;
            }

            // Não permitir a mesma peça em várias linhas.
            foreach ($pecas as $pecaAdicionada) {
                if (
                    $pecaAdicionada['referencia_peca'] ===
                    $pecaCatalogo['referencia']
                ) {
                    $erro = 'A peça ' .
                        $pecaCatalogo['referencia'] .
                        ' já foi adicionada. Altera a quantidade na linha existente.';
                    break 2;
                }
            }

            // Obter a referência, o nome e o preço da base de dados.
            $pecas[] = [
                'referencia_peca' => $pecaCatalogo['referencia'],
                'nome_peca' => $pecaCatalogo['nome'],
                'quantidade' => (int) $quantidadeTexto,
                'preco_unitario' => (float) $pecaCatalogo['preco']
            ];
        }
    }

    if ($erro === '' && count($pecas) === 0) {
        $erro = 'Adiciona pelo menos uma peça à encomenda.';
    }

    if ($erro !== '') {
        $mensagem = $erro;
        $tipoMensagem = 'erro';

    } else {

        /*
         * Enviar a encomenda à API Node.js.
         */
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
            $mensagem = 'Não foi possível contactar a API. ' .
                'Confirma que o servidor Node.js está a funcionar.';
            $tipoMensagem = 'erro';

        } else {

            $resultado = json_decode($resposta, true);
            $codigoHttp = 0;

            foreach (($http_response_header ?? []) as $cabecalho) {
                if (
                    preg_match(
                        '/^HTTP\/\S+\s+(\d+)/',
                        $cabecalho,
                        $correspondencia
                    )
                ) {
                    $codigoHttp = (int) $correspondencia[1];
                }
            }

            if (
                $codigoHttp === 201 &&
                is_array($resultado)
            ) {
                $mensagem = 'Encomenda #' .
                    ($resultado['id'] ?? '') .
                    ' criada com sucesso. Total: ' .
                    number_format(
                        (float) ($resultado['total'] ?? 0),
                        2,
                        ',',
                        '.'
                    ) . ' €.';

                $tipoMensagem = 'sucesso';
                $fornecedorSelecionado = '';

                $linhasFormulario = [
                    ['peca_id' => '', 'quantidade' => '1']
                ];

            } else {
                $mensagem = is_array($resultado)
                    ? ($resultado['erro'] ??
                        'Não foi possível criar a encomenda.')
                    : 'A API devolveu uma resposta inválida.';

                $tipoMensagem = 'erro';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4. Preparar os dados para o JavaScript
|--------------------------------------------------------------------------
*/
$catalogoJson = json_encode(
    $catalogoPecas,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

if ($catalogoJson === false) {
    $catalogoJson = '[]';
}

$linhasJson = json_encode(
    $linhasFormulario,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

if ($linhasJson === false) {
    $linhasJson = '[]';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nova encomenda - AutoLux</title>

    <link rel="stylesheet" href="assets/menu.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            color: #222;
        }

        .conteudo-pagina {
            width: min(1100px, 92%);
            margin: 30px auto;
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
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 4px;
            background: white;
        }

        .peca {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(90px, 1fr) auto;
            gap: 14px;
            align-items: end;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #ddd;
        }

        .campo {
            min-width: 0;
        }

        .preco {
            background: #eef2f7;
        }

        .detalhes-peca {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .resumo {
            background: #eef2f7;
            padding: 14px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
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

        .sucesso, .erro {
            padding: 12px;
            border-radius: 4px;
        }

        .sucesso {
            background: #d1e7dd;
            color: #0f5132;
        }

        .erro {
            background: #f8d7da;
            color: #842029;
        }

        .sem-pecas {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }

        @media (max-width: 650px) {
            .peca {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/includes/menu.php'; ?>

<main class="conteudo-pagina">

    <h1>Nova encomenda a fornecedor</h1>

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
            Não foi possível carregar fornecedores. Confirma que a API
            Node.js está ativa e que existem fornecedores registados.
        </div>

    <?php elseif (count($catalogoPecas) === 0): ?>

        <div class="painel">
            Não existem peças no catálogo ou não foi possível carregá-las.
            Confirma a tabela <code>pecas</code> na base de dados
            <code>autolux_vendas</code>.
        </div>

    <?php else: ?>

        <form method="POST" id="form-encomenda">

            <div class="painel">
                <h2>1. Selecionar fornecedor</h2>

                <label for="fornecedor_id">Fornecedor</label>

                <select
                    id="fornecedor_id"
                    name="fornecedor_id"
                    required
                >
                    <option value="">-- Seleciona um fornecedor --</option>

                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option
                            value="<?= escapar($fornecedor['id'] ?? '') ?>"
                            <?= (
                                $fornecedorSelecionado !== '' &&
                                $fornecedorSelecionado ===
                                (string) ($fornecedor['id'] ?? '')
                            ) ? 'selected' : '' ?>
                        >
                            <?= escapar(
                                $fornecedor['nome'] ??
                                'Fornecedor sem nome'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="painel">
                <h2>2. Escolher as peças</h2>

                <p>
                    Depois de escolheres o fornecedor, aparecerão
                    automaticamente as peças associadas a esse fornecedor.
                    Cada opção mostra a referência, o nome, o preço e o stock.
                </p>

                <div id="lista-pecas">

                    <?php foreach ($linhasFormulario as $linha): ?>

                        <div class="peca">

                            <div class="campo">
                                <label>Peça disponível</label>

                                <select
                                    name="peca_id[]"
                                    class="selecao-peca"
                                    required
                                >
                                    <option value="">
                                        -- Seleciona primeiro o fornecedor --
                                    </option>
                                </select>

                                <span class="detalhes-peca info-peca"></span>
                            </div>

                            <div class="campo">
                                <label>Quantidade</label>

                                <input
                                    type="number"
                                    name="quantidade[]"
                                    class="quantidade"
                                    min="1"
                                    step="1"
                                    value="<?= escapar($linha['quantidade']) ?>"
                                    required
                                >
                            </div>

                            <button
                                type="button"
                                class="remover"
                            >
                                Remover
                            </button>

                        </div>

                    <?php endforeach; ?>

                </div>

                <button
                    type="button"
                    class="secundario"
                    id="adicionar-peca"
                >
                    + Adicionar peça
                </button>

                <div class="resumo">
                    Total estimado da encomenda:
                    <span id="total-encomenda">0,00 €</span>
                </div>
            </div>

            <button type="submit" class="principal">
                Criar encomenda
            </button>

        </form>

    <?php endif; ?>

</main>

<script>
(function () {
    const catalogoPecas = <?= $catalogoJson ?>;
    const linhasGuardadas = <?= $linhasJson ?>;

    const lista = document.getElementById('lista-pecas');
    const formulario = document.getElementById('form-encomenda');
    const seletorFornecedor = document.getElementById('fornecedor_id');

    // Não executar se o formulário não estiver disponível.
    if (!lista || !formulario || !seletorFornecedor) {
        return;
    }

    function formatarPreco(valor) {
        return Number(valor).toLocaleString('pt-PT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    }

    /*
     * Devolver apenas as peças do fornecedor selecionado.
     */
    function obterPecasDoFornecedor() {
        const fornecedorId = seletorFornecedor.value;

        if (!fornecedorId) {
            return [];
        }

        return catalogoPecas.filter(function (peca) {
            return String(peca.fornecedor_id) === String(fornecedorId);
        });
    }

    /*
     * Atualizar o menu de peças de uma linha.
     */
    function atualizarOpcoes(linha, idSelecionado = '') {
        const select = linha.querySelector('.selecao-peca');
        const infoPeca = linha.querySelector('.info-peca');

        const pecasDisponiveis = obterPecasDoFornecedor();

        select.innerHTML = '';

        const opcaoInicial = document.createElement('option');
        opcaoInicial.value = '';

        if (!seletorFornecedor.value) {
            opcaoInicial.textContent =
                '-- Seleciona primeiro o fornecedor --';

            select.appendChild(opcaoInicial);
            infoPeca.textContent = '';
            atualizarTotal();
            return;
        }

        if (pecasDisponiveis.length === 0) {
            opcaoInicial.textContent =
                '-- Este fornecedor não tem peças associadas --';

            select.appendChild(opcaoInicial);
            infoPeca.textContent =
                'Confirma os valores de fornecedor_id no catálogo.';

            atualizarTotal();
            return;
        }

        opcaoInicial.textContent = '-- Seleciona uma peça --';
        select.appendChild(opcaoInicial);

        pecasDisponiveis.forEach(function (peca) {
            const opcao = document.createElement('option');

            opcao.value = String(peca.id);

            opcao.textContent =
                peca.referencia + ' — ' +
                peca.nome + ' — ' +
                formatarPreco(peca.preco) +
                ' — Stock: ' + peca.stock;

            if (String(peca.id) === String(idSelecionado)) {
                opcao.selected = true;
            }

            select.appendChild(opcao);
        });

        atualizarDetalhes(linha);
    }

    /*
     * Mostrar o preço e o stock da peça escolhida.
     */
    function atualizarDetalhes(linha) {
        const select = linha.querySelector('.selecao-peca');
        const infoPeca = linha.querySelector('.info-peca');

        const peca = catalogoPecas.find(function (item) {
            return String(item.id) === select.value;
        });

        if (
            !peca ||
            String(peca.fornecedor_id) !== String(seletorFornecedor.value)
        ) {
            infoPeca.textContent = '';
            atualizarTotal();
            return;
        }

        infoPeca.textContent =
            'Referência: ' + peca.referencia +
            ' | Marca: ' + peca.marca +
            ' | Stock atual: ' + peca.stock +
            ' | Preço unitário: ' + formatarPreco(peca.preco);

        atualizarTotal();
    }

    /*
     * Adicionar uma linha de peça.
     */
    function adicionarPeca() {
        const modelo = lista.querySelector('.peca');

        if (!modelo) {
            return;
        }

        const novaLinha = modelo.cloneNode(true);

        novaLinha.querySelector('.selecao-peca').innerHTML =
            '<option value="">-- Seleciona uma peça --</option>';

        novaLinha.querySelector('.quantidade').value = '1';
        novaLinha.querySelector('.info-peca').textContent = '';

        lista.appendChild(novaLinha);

        atualizarOpcoes(novaLinha);
        atualizarTotal();
    }

    /*
     * Remover uma linha.
     */
    function removerPeca(botao) {
        const linhas = lista.querySelectorAll('.peca');

        if (linhas.length > 1) {
            botao.closest('.peca').remove();
            atualizarTotal();
        } else {
            alert('A encomenda deve conter pelo menos uma peça.');
        }
    }

    /*
     * Calcular o total estimado.
     */
    function atualizarTotal() {
        let total = 0;

        lista.querySelectorAll('.peca').forEach(function (linha) {
            const select = linha.querySelector('.selecao-peca');
            const quantidade = Number(
                linha.querySelector('.quantidade').value
            );

            const peca = catalogoPecas.find(function (item) {
                return String(item.id) === select.value;
            });

            if (
                peca &&
                String(peca.fornecedor_id) ===
                    String(seletorFornecedor.value) &&
                Number.isInteger(quantidade) &&
                quantidade > 0
            ) {
                total += Number(peca.preco) * quantidade;
            }
        });

        document.getElementById('total-encomenda').textContent =
            formatarPreco(total);
    }

    /*
     * Quando muda o fornecedor, limpar as seleções anteriores.
     */
    seletorFornecedor.addEventListener('change', function () {
        lista.querySelectorAll('.peca').forEach(function (linha) {
            atualizarOpcoes(linha);
        });

        atualizarTotal();
    });

    /*
     * Atualizar os detalhes quando a peça ou a quantidade muda.
     */
    lista.addEventListener('change', function (evento) {
        if (evento.target.classList.contains('selecao-peca')) {
            atualizarDetalhes(evento.target.closest('.peca'));
        }
    });

    lista.addEventListener('input', function (evento) {
        if (evento.target.classList.contains('quantidade')) {
            atualizarTotal();
        }
    });

    /*
     * Botões de adicionar e remover.
     */
    document.getElementById('adicionar-peca')
        .addEventListener('click', adicionarPeca);

    lista.addEventListener('click', function (evento) {
        const botao = evento.target.closest('.remover');

        if (botao) {
            removerPeca(botao);
        }
    });

    /*
     * Restaurar os dados do formulário depois de um erro.
     */
    const linhasIniciais = lista.querySelectorAll('.peca');

    linhasGuardadas.forEach(function (linhaGuardada, indice) {
        let linha = linhasIniciais[indice];

        if (!linha && indice > 0) {
            adicionarPeca();

            const todasAsLinhas = lista.querySelectorAll('.peca');
            linha = todasAsLinhas[indice];
        }

        if (!linha) {
            return;
        }

        const idPeca = String(linhaGuardada.peca_id || '');

        atualizarOpcoes(linha, idPeca);

        linha.querySelector('.quantidade').value =
            String(linhaGuardada.quantidade || '1');
    });

    atualizarTotal();
})();
</script>

</body>
</html>
