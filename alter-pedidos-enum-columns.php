<?php

declare(strict_types=1);

$db = new PDO('sqlite:data/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pedidosExiste = (bool) $db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'pedidos'")
    ->fetchColumn();

if (! $pedidosExiste) {
    echo "Tabela 'pedidos' não encontrada. Nada para atualizar." . PHP_EOL;
    exit(0);
}

function mapTipoEntrega(mixed $valor): int
{
    if (is_int($valor)) {
        return $valor === 1 ? 1 : 0;
    }

    $texto = strtolower(trim((string) $valor));

    return $texto === 'entrega' ? 1 : 0;
}

function mapFormaPagamento(mixed $valor): int
{
    if (is_int($valor)) {
        return match ($valor) {
            1 => 1,
            2 => 2,
            3 => 3,
            default => 0,
        };
    }

    $texto = strtolower(str_replace(['-', '_'], ' ', (string) $valor));

    return match ($texto) {
        'pix' => 1,
        'cartao credito', 'cartao de credito' => 2,
        'cartao debito', 'cartao de debito' => 3,
        default => 0,
    };
}

function mapStatus(mixed $valor): int
{
    if (is_int($valor)) {
        return match ($valor) {
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            default => 0,
        };
    }

    $texto = strtolower(str_replace(['-', '_'], ' ', (string) $valor));

    return match ($texto) {
        'em preparacao' => 1,
        'pronto' => 2,
        'entregue' => 3,
        'cancelado' => 4,
        default => 0,
    };
}

$db->exec('PRAGMA foreign_keys = OFF');

try {
    $db->beginTransaction();

    $db->exec('DROP TABLE IF EXISTS pedidos_tmp');
    $db->exec('CREATE TABLE pedidos_tmp (
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
    )');

    $select = $db->query('SELECT * FROM pedidos');
    $insert = $db->prepare('INSERT INTO pedidos_tmp (
        id,
        cliente_nome,
        cliente_whatsapp,
        data_entrega,
        hora_entrega,
        tipo_entrega,
        endereco,
        forma_pagamento,
        status,
        observacao,
        valor_total,
        created_at,
        updated_at
    ) VALUES (
        :id,
        :cliente_nome,
        :cliente_whatsapp,
        :data_entrega,
        :hora_entrega,
        :tipo_entrega,
        :endereco,
        :forma_pagamento,
        :status,
        :observacao,
        :valor_total,
        :created_at,
        :updated_at
    )');

    while ($pedido = $select->fetch(PDO::FETCH_ASSOC)) {
        $insert->execute([
            ':id' => $pedido['id'],
            ':cliente_nome' => $pedido['cliente_nome'],
            ':cliente_whatsapp' => $pedido['cliente_whatsapp'],
            ':data_entrega' => $pedido['data_entrega'],
            ':hora_entrega' => $pedido['hora_entrega'],
            ':tipo_entrega' => mapTipoEntrega($pedido['tipo_entrega'] ?? null),
            ':endereco' => $pedido['endereco'] ?? null,
            ':forma_pagamento' => mapFormaPagamento($pedido['forma_pagamento'] ?? null),
            ':status' => mapStatus($pedido['status'] ?? null),
            ':observacao' => $pedido['observacao'] ?? null,
            ':valor_total' => $pedido['valor_total'] ?? 0,
            ':created_at' => $pedido['created_at'] ?? null,
            ':updated_at' => $pedido['updated_at'] ?? null,
        ]);
    }

    $db->exec('DROP TABLE pedidos');
    $db->exec('ALTER TABLE pedidos_tmp RENAME TO pedidos');

    $db->commit();

    echo "Tabela 'pedidos' atualizada para utilizar valores numéricos." . PHP_EOL;
} catch (Throwable $e) {
    $db->rollBack();
    echo 'Falha ao migrar tabela pedidos: ' . $e->getMessage() . PHP_EOL;
    throw $e;
} finally {
    $db->exec('PRAGMA foreign_keys = ON');
}
