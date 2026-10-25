<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <link rel="stylesheet" href="assets/menu.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoLux | Gestão</title>
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
        
        header {
            background: #183153;
            color: white;
            padding: 28px 20px;
        }

        .conteudo {
            width: min(1100px, 92%);
            margin: 0 auto;
        }

        header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        header p {
            margin: 0;
            color: #dce5f1;
        }

        main {
            padding: 32px 0;
        }

        .cartoes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
        }

        .cartao {
            display: block;
            padding: 24px;
            background: white;
            border-radius: 10px;
            border: 1px solid #e0e5ec;
            text-decoration: none;
            color: inherit;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .cartao:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
        }

        .cartao h2 {
            margin-top: 0;
            color: #183153;
            font-size: 20px;
        }

        .cartao p {
            margin-bottom: 0;
            line-height: 1.5;
            color: #5d6875;
        }

        .seta {
            display: inline-block;
            margin-top: 18px;
            color: #245da8;
            font-weight: bold;
        }

        footer {
            padding: 20px;
            text-align: center;
            color: #687482;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/menu.php'; ?>
    <main class="conteudo">
        <h1>Página inicial</h1>
        <p>Seleciona a área que pretendes consultar ou gerir.</p>

        <div class="cartoes">
            <a class="cartao" href="catalogo.php">
                <h2>Catálogo de peças</h2>
                <p>Consulta as peças disponíveis, os preços, as marcas e o stock.</p>
                <span class="seta">Abrir catálogo →</span>
            </a>

            <a class="cartao" href="vendas.php">
                <h2>Vendas</h2>
                <p>Regista vendas a clientes e consulta a disponibilidade das peças.</p>
                <span class="seta">Registar venda →</span>
            </a>

            <a class="cartao" href="fornecedores.php">
                <h2>Fornecedores</h2>
                <p>Consulta os fornecedores obtidos através da API Node.js.</p>
                <span class="seta">Ver fornecedores →</span>
            </a>

            <a class="cartao" href="encomendas.php">
                <h2>Encomendas</h2>
                <p>Consulta as encomendas registadas aos fornecedores.</p>
                <span class="seta">Ver encomendas →</span>
            </a>

            <a class="cartao" href="encomendas_nova.php">
                <h2>Nova encomenda</h2>
                <p>Cria uma encomenda e adiciona as peças e quantidades pretendidas.</p>
                <span class="seta">Criar encomenda →</span>
            </a>
        </div>
    </main>

    <footer>
        AutoLux — Sistema de gestão
    </footer>
</body>
</html>
