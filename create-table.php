<?php

$db = new PDO('sqlite:data/database.sqlite');

$db->exec("
    CREATE TABLE produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        referencia TEXT NOT NULL,
        nome TEXT NOT NULL,
        descricao TEXT NULL,
        inativo BOOLEAN NOT NULL DEFAULT 0,
        preco REAL NOT NULL DEFAULT 0.00
    );
");

echo "Tabela criada!";