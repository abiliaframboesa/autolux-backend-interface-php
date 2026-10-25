USE autolux_fornecedores;

START TRANSACTION;

-- 1. Inserir fornecedores fictícios
INSERT INTO fornecedores (nome, email, telefone) VALUES
('Bosch Portugal', 'encomendas@bosch.pt', '210123456'),
('Autozitânia', 'comercial@autozitania.pt', '219876543'),
('Sodicentro Peças', 'vendas@sodicentro.pt', '229456781'),
('Distribuição Auto Norte', 'geral@distribuicaoauto.pt', '223567890'),
('Pneus Portugal', 'encomendas@pneusportugal.pt', '214567890');

-- 2. Inserir encomendas
INSERT INTO encomendas_fornecedor (fornecedor_id, estado, total)
VALUES
(1, 'Pendente', 0),
(2, 'Enviada', 0),
(3, 'Recebida', 0),
(4, 'Pendente', 0),
(5, 'Enviada', 0);

-- 3. Inserir peças das encomendas
INSERT INTO encomenda_pecas
(encomenda_id, referencia_peca, nome_peca, quantidade, preco_unitario)
VALUES
(1, 'BOS-201', 'Filtro de óleo', 10, 9.50),
(1, 'BOS-204', 'Filtro de ar do motor', 5, 13.20),
(2, 'BRM-202', 'Pastilhas de travão dianteiras', 4, 32.50),
(2, 'BOS-207', 'Vela de ignição', 10, 6.80),
(3, 'VAR-203', 'Bateria automóvel 60Ah', 3, 78.50),
(3, 'CAS-206', 'Óleo de motor 5W-30 (1L)', 12, 11.25),
(4, 'VAL-208', 'Escovas limpa-vidros', 5, 15.00),
(5, 'MIC-205', 'Pneu 205/55 R16', 6, 82.00);

-- 4. Calcular os totais com base nas peças
UPDATE encomendas_fornecedor
SET total = (
    SELECT COALESCE(SUM(quantidade * preco_unitario), 0)
    FROM encomenda_pecas
    WHERE encomenda_id = encomendas_fornecedor.id
)
WHERE id IN (1, 2, 3, 4, 5);

COMMIT;