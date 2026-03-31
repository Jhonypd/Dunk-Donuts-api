<?php

$db = new PDO('sqlite:data/database.sqlite');

// Ativar foreign keys no SQLite
$db->exec("PRAGMA foreign_keys = ON");

// =====================
// PRODUTOS
// =====================
$db->exec("
    CREATE TABLE IF NOT EXISTS produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        referencia TEXT NOT NULL,
        nome TEXT NOT NULL,
        descricao TEXT NULL,
        categoria TEXT NULL,
        inativo BOOLEAN NOT NULL DEFAULT 0,
        preco REAL NOT NULL DEFAULT 0.00
    );
");

// =====================
// PEDIDOS
// =====================
$db->exec("
    CREATE TABLE IF NOT EXISTS pedidos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente_nome TEXT NOT NULL,
        cliente_whatsapp TEXT NOT NULL,
        
        data_entrega DATE NOT NULL,
        hora_entrega TIME NOT NULL,

        tipo_entrega INTEGER NOT NULL DEFAULT 0,
        endereco TEXT NULL,

        forma_pagamento INTEGER NOT NULL,

        status INTEGER NOT NULL DEFAULT 0,

        observacao TEXT NULL,
        valor_total REAL NOT NULL DEFAULT 0.00,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// =====================
// ITENS DO PEDIDO
// =====================
$db->exec("
    CREATE TABLE IF NOT EXISTS pedido_itens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pedido_id INTEGER NOT NULL,
        produto_id INTEGER NOT NULL,

        quantidade INTEGER NOT NULL,
        valor_unitario REAL NOT NULL,

        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
    );
");

echo "Tabelas criadas com sucesso!";
