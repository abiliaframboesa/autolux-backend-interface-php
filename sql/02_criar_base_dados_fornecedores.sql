CREATE DATABASE IF NOT EXISTS autolux_fornecedores
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE autolux_fornecedores;

CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150),
    telefone VARCHAR(30),
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS encomendas_fornecedor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT NOT NULL,
    data_encomenda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

CREATE TABLE IF NOT EXISTS encomenda_pecas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    encomenda_id INT NOT NULL,
    referencia_peca VARCHAR(50) NOT NULL,
    nome_peca VARCHAR(150) NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (encomenda_id) REFERENCES encomendas_fornecedor(id)
);