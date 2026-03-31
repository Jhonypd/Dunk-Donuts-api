<?php

$db = new PDO('sqlite:data/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = OFF");

try {
    $db->beginTransaction();

    // Cria nova tabela com estrutura corrigida
    $db->exec("
        CREATE TABLE IF NOT EXISTS produtos_nova (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            referencia TEXT NOT NULL,
            nome TEXT NOT NULL,
            descricao TEXT NULL,
            categoria INTEGER NOT NULL DEFAULT 2,
            inativo BOOLEAN NOT NULL DEFAULT 0,
            preco REAL NOT NULL DEFAULT 0.00
        );
    ");

    // Copia os dados da antiga para a nova
    $db->exec("
        INSERT INTO produtos_nova (id, referencia, nome, descricao, categoria, inativo, preco)
        SELECT 
            id,
            referencia,
            nome,
            descricao,
            COALESCE(categoria, 2),
            inativo,
            preco
        FROM produtos;
    ");

    // Remove tabela antiga
    $db->exec("DROP TABLE produtos");

    // Renomeia a nova
    $db->exec("ALTER TABLE produtos_nova RENAME TO produtos");

    $db->commit();

    echo "Tabela produtos atualizada com sucesso!";
} catch (Exception $e) {
    $db->rollBack();
    echo "Erro: " . $e->getMessage();
} finally {
    $db->exec("PRAGMA foreign_keys = ON");
}
