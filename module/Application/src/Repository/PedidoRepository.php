<?php

declare(strict_types=1);

namespace Application\Repository;

use Laminas\Db\Adapter\Adapter;

class PedidoRepository
{
    public function __construct(private Adapter $db) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $status = null): array
    {
        if ($status !== null) {
            $result = $this->db->query(
                'SELECT * FROM pedidos WHERE status = ? ORDER BY created_at DESC',
                [$status]
            );
        } else {
            $result = $this->db->query('SELECT * FROM pedidos ORDER BY created_at DESC', []);
        }

        $pedidos = [];
        foreach ($result as $pedido) {
            $pedidos[] = (array) $pedido;
        }

        return $pedidos;
    }

    /**
     * @param array<string, mixed> $pedido
     * @param array<int, array<string, mixed>> $itens
     */
    public function inserir(array $pedido, array $itens): int
    {
        $connection = $this->db->getDriver()->getConnection();
        $connection->beginTransaction();

        try {
            $this->db->query(
                'INSERT INTO pedidos (
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
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )',
                $pedido
            );

            $pedidoId = (int) $this->db->getDriver()->getLastGeneratedValue();

            foreach ($itens as $item) {
                $this->db->query(
                    'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, valor_unitario)
                     VALUES (:pedido_id, :produto_id, :quantidade, :valor_unitario)',
                    [
                        'pedido_id' => $pedidoId,
                        'produto_id' => $item['produto_id'],
                        'quantidade' => $item['quantidade'],
                        'valor_unitario' => $item['valor_unitario'],
                    ]
                );
            }

            $connection->commit();

            return $pedidoId;
        } catch (\Throwable $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $dados
     * @param array<int, array<string, mixed>>|null $itens
     */
    public function atualizar(int $id, array $dados, ?array $itens = null): void
    {
        $connection = $this->db->getDriver()->getConnection();
        $connection->beginTransaction();

        try {
            if (! empty($dados)) {
                $campos = [];
                $params = ['id' => $id];

                foreach ($dados as $campo => $valor) {
                    $campos[] = sprintf('%s = :%s', $campo, $campo);
                    $params[$campo] = $valor;
                }

                $campos[] = 'updated_at = CURRENT_TIMESTAMP';

                $this->db->query(
                    sprintf('UPDATE pedidos SET %s WHERE id = :id', implode(', ', $campos)),
                    $params
                );
            } else {
                $this->db->query(
                    'UPDATE pedidos SET updated_at = CURRENT_TIMESTAMP WHERE id = :id',
                    ['id' => $id]
                );
            }

            if ($itens !== null) {
                $this->db->query('DELETE FROM pedido_itens WHERE pedido_id = ?', [$id]);

                foreach ($itens as $item) {
                    $this->db->query(
                        'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, valor_unitario)
                         VALUES (:pedido_id, :produto_id, :quantidade, :valor_unitario)',
                        [
                            'pedido_id' => $id,
                            'produto_id' => $item['produto_id'],
                            'quantidade' => $item['quantidade'],
                            'valor_unitario' => $item['valor_unitario'],
                        ]
                    );
                }
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollback();
            throw $e;
        }
    }

    public function atualizarStatus(int $id, int $status, ?string $observacao = null): void
    {
        $sql = 'UPDATE pedidos SET status = :status, updated_at = CURRENT_TIMESTAMP';
        $params = [
            'id' => $id,
            'status' => $status,
        ];

        if ($observacao !== null) {
            $sql .= ', observacao = :observacao';
            $params['observacao'] = $observacao;
        }

        $sql .= ' WHERE id = :id';

        $this->db->query($sql, $params);
    }

    public function buscarPorId(int $id): ?array
    {
        $result = $this->db->query('SELECT * FROM pedidos WHERE id = ?', [$id]);
        $pedido = $result->current();

        return $pedido ? (array) $pedido : null;
    }

    public function buscarItensPorPedido(int $pedidoId): array
    {
        $result = $this->db->query('SELECT * FROM pedido_itens WHERE pedido_id = ?', [$pedidoId]);
        $itens = [];

        foreach ($result as $item) {
            $itens[] = (array) $item;
        }

        return $itens;
    }

    /**
     * @param int[] $pedidoIds
     * @return array<int, array<string, mixed>>
     */
    public function buscarItensPorPedidos(array $pedidoIds): array
    {
        if (empty($pedidoIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($pedidoIds), '?'));
        $sql = "
            SELECT
                pi.id,
                pi.pedido_id,
                pi.produto_id,
                pi.quantidade,
                pi.valor_unitario,
                p.nome AS produto_nome
            FROM pedido_itens pi
            LEFT JOIN produtos p ON p.id = pi.produto_id
            WHERE pi.pedido_id IN ({$placeholders})
            ORDER BY pi.id ASC
        ";

        $result = $this->db->query($sql, $pedidoIds);
        $itens = [];
        foreach ($result as $item) {
            $itens[] = (array) $item;
        }

        return $itens;
    }
}
