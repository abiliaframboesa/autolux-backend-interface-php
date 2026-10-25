USE autolux_vendas;

INSERT INTO clientes (nome, email, telefone) VALUES
('Rui Ferreira', 'rui.ferreira@gmail.com', '934672815'),
('Carla Mendes', 'carla.mendes@outlook.pt', '916284753'),
('Oficina Martins & Filhos', 'geral@oficinamartins.pt', '229847316'),
('André Oliveira', 'andre.oliveira@gmail.com', '962518407'),
('Sofia Ribeiro', 'sofia.ribeiro@hotmail.com', '938416275');

INSERT INTO pecas (referencia, nome, marca, fornecedor_id, tipo, preco, stock) VALUES
('BOS-201', 'Filtro de óleo', 'Bosch', 1, 'Filtros', 12.50, 19),
('BRM-202', 'Pastilhas de travão dianteiras', 'Brembo', 2, 'Travões', 38.90, 0),
('VAR-203', 'Bateria automóvel 60Ah', 'Varta', 3, 'Baterias', 92.50, 0),
('BOS-204', 'Filtro de ar do motor', 'Bosch', 1, 'Filtros', 16.80, 0),
('MIC-205', 'Pneu 205/55 R16', 'Michelin', 4, 'Pneus', 89.90, 0),
('CAS-206', 'Óleo de motor 5W-30 (1L)', 'Castrol', 5, 'Lubrificantes', 14.25, 0),
('BOS-207', 'Vela de ignição', 'Bosch', 1, 'Ignição', 7.90, 0),
('VAL-208', 'Escovas limpa-vidros', 'Valeo', 2, 'Limpeza', 19.50, 0);