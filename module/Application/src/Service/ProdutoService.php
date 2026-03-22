<?php

declare(strict_types=1);

namespace Application\Service;

use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeleteDTO;
use Application\DTO\ProdutoDTO;
use Application\Repository\ProdutoRepository;

class ProdutoService
{
    private const NOME_MINIMO_CARACTERES = 5;
    private const NOME_MAXIMO_CARACTERES = 100;
    private const REFERENCIA_MINIMO_CARACTERES = 3;
    private const REFERENCIA_MAXIMO_CARACTERES = 30;

    private $repository;

    public function __construct(ProdutoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listar(bool $incluirInativos = false): array
    {


        $produtos = $this->repository->buscarTodos($incluirInativos);

        return [
            'produtos' => $produtos,
        ];
    }

    public function criar(ProdutoDTO $dados): void
    {
        $nome = trim($dados->nome);

        if ($nome === '') {
            throw new \InvalidArgumentException('Nome do produto é obrigatório.');
        }

        $referencia = trim($dados->referencia);

        if ($this->buscaNomeReferenciaExistente($nome, $referencia)) {
            throw new \InvalidArgumentException('Já existe um produto com o nome "' . $nome . '" e/ou referência "' . $referencia . '".');
        }

        $tamanhoNome = $this->getTextLength($nome);
        if (
            $tamanhoNome < self::NOME_MINIMO_CARACTERES
            || $tamanhoNome > self::NOME_MAXIMO_CARACTERES
        ) {
            throw new \InvalidArgumentException('O nome precisa ter entre 5 e 100 caracteres.');
        }

        $referencia = trim($dados->referencia);
        if ($referencia === '') {
            throw new \InvalidArgumentException('Referência do produto é obrigatória.');
        }

        $tamanhoReferencia = $this->getTextLength($referencia);
        if (
            $tamanhoReferencia < self::REFERENCIA_MINIMO_CARACTERES
            || $tamanhoReferencia > self::REFERENCIA_MAXIMO_CARACTERES
        ) {
            throw new \InvalidArgumentException('A referência precisa ter entre 3 e 30 caracteres.');
        }

        $preco = $dados->preco;
        if ($preco === 0.0) {
            throw new \InvalidArgumentException('Preço do produto é obrigatório.');
        }
        if ($preco <= 0) {
            throw new \InvalidArgumentException('Preço do produto deve ser maior que zero.');
        }

        $inativo = (bool) $dados->inativo;

        $produto = new ProdutoDTO(
            nome: $nome,
            preco: $preco,
            descricao: $dados->descricao,
            referencia: $referencia,
            inativo: $inativo,
        );

        $this->repository->inserir($produto);
    }

    public function alterar(ProdutoAlterarDTO $dados): void
    {
        $nome = trim($dados->nome);

        if ($nome === '') {
            throw new \InvalidArgumentException('Nome do produto é obrigatório.');
        }

        $referencia = trim($dados->referencia);

        $produtoExistente = $this->buscaNomeReferenciaExistente($nome, $referencia, true);

        if ($produtoExistente && $produtoExistente['id'] !== $dados->id) {
            throw new \InvalidArgumentException('Já existe um produto com o nome "' . $nome . '" e/ou referência "' . $referencia . '".');
        }

        $tamanhoNome = $this->getTextLength($nome);
        if (
            $tamanhoNome < self::NOME_MINIMO_CARACTERES
            || $tamanhoNome > self::NOME_MAXIMO_CARACTERES
        ) {
            throw new \InvalidArgumentException('O nome precisa ter entre 5 e 100 caracteres.');
        }

        $referencia = trim($dados->referencia);
        if ($referencia === '') {
            throw new \InvalidArgumentException('Referência do produto é obrigatória.');
        }

        $tamanhoReferencia = $this->getTextLength($referencia);
        if (
            $tamanhoReferencia < self::REFERENCIA_MINIMO_CARACTERES
            || $tamanhoReferencia > self::REFERENCIA_MAXIMO_CARACTERES
        ) {
            throw new \InvalidArgumentException('A referência precisa ter entre 3 e 30 caracteres.');
        }

        $preco = $dados->preco;
        if ($preco === 0.0) {
            throw new \InvalidArgumentException('Preço do produto é obrigatório.');
        }
        if ($preco <= 0) {
            throw new \InvalidArgumentException('Preço do produto deve ser maior que zero.');
        }

        $inativo = (bool) $dados->inativo;

        $produto = new ProdutoAlterarDTO(
            id: $dados->id,
            nome: $nome,
            preco: $preco,
            descricao: $dados->descricao,
            referencia: $referencia,
            inativo: $inativo,
        );

        $this->repository->alterar($produto);
    }

    public function deletar(ProdutoDeleteDTO $ids): array
    {
        if (empty($ids->ids)) {
            return [
                'mensagem' => 'Não foi possível deletar os produtos, verifique as informações e tente novamente.'
            ];
        }

        $total = count($ids->ids);
        $existentes = 0;

        foreach ($ids->ids as $id) {
            if ($this->repository->buscarPorId($id)) {
                $existentes++;
            }
        }

        // falha total
        if ($existentes === 0) {
            return [
                'mensagem' => 'Nenhum dos produtos informados foram encontrados para deletar.'
            ];
        }

        // deleta os que existem
        $this->repository->deletar($ids);

        // parcial
        if ($existentes < $total) {
            return [
                'mensagem' => 'Não foi possível deletar todos os produtos.'
            ];
        }

        // sucesso total
        return [
            'mensagem' => 'Produtos deletados com sucesso.'
        ];
    }

    private function getTextLength(string $texto): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($texto);
        }

        return strlen($texto);
    }

    private function buscaNomeReferenciaExistente(string $nome, string $referencia, bool $incluirInativos = false): ?array
    {
        $produtos = $this->repository->buscarTodos($incluirInativos);

        foreach ($produtos as $produto) {
            $produtoInativoRaw = $produto['inativo'] ?? false;
            $produtoInativo = filter_var($produtoInativoRaw, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);
            if ($produtoInativo ?? false) {
                continue;
            }

            $produtoNome = (string) ($produto['nome'] ?? '');
            $produtoReferencia = (string) ($produto['referencia'] ?? '');

            if (
                strcasecmp($produtoNome, $nome) === 0
                || strcasecmp($produtoReferencia, $referencia) === 0
            ) {
                return $produto;
            }
        }

        return null;
    }
}
