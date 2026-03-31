<?php

declare(strict_types=1);

namespace Application\Service;

use Application\DTO\ProdutoAlterarDTO;
use Application\DTO\ProdutoDeleteDTO;
use Application\DTO\ProdutoDTO;
use Application\Enum\CategoriaProdutos;
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
        $categoriaEntrada = $dados->categoria;
        $categoria = $categoriaEntrada instanceof CategoriaProdutos
            ? $categoriaEntrada
            : CategoriaProdutos::tryFrom((int) ($categoriaEntrada ?? CategoriaProdutos::NORMAIS->value))
            ?? CategoriaProdutos::NORMAIS;


        $produto = new ProdutoDTO(
            nome: $nome,
            preco: $preco,
            descricao: $dados->descricao,
            referencia: $referencia,
            inativo: $inativo,
            categoria: $categoria,
        );

        $this->repository->inserir($produto);
    }

    public function alterar(ProdutoAlterarDTO $dados): void
    {
        $id = (int) $dados->id;

        if ($id <= 0) {
            throw new \InvalidArgumentException('ID do produto é obrigatório.');
        }

        $produtoAtual = $this->repository->buscarPorId($id);

        if (!$produtoAtual) {
            throw new \InvalidArgumentException('Produto não encontrado.');
        }

        // =========================
        // NOME
        // =========================
        $nome = null;
        if ($dados->nome !== null) {
            $nome = trim($dados->nome);

            if ($nome === '') {
                throw new \InvalidArgumentException('Nome do produto não pode ser vazio.');
            }

            $tamanhoNome = $this->getTextLength($nome);
            if (
                $tamanhoNome < self::NOME_MINIMO_CARACTERES ||
                $tamanhoNome > self::NOME_MAXIMO_CARACTERES
            ) {
                throw new \InvalidArgumentException('O nome precisa ter entre 5 e 100 caracteres.');
            }
        }

        // =========================
        // REFERÊNCIA
        // =========================
        $referencia = null;
        if ($dados->referencia !== null) {
            $referencia = trim($dados->referencia);

            if ($referencia === '') {
                throw new \InvalidArgumentException('Referência do produto não pode ser vazia.');
            }

            $tamanhoReferencia = $this->getTextLength($referencia);
            if (
                $tamanhoReferencia < self::REFERENCIA_MINIMO_CARACTERES ||
                $tamanhoReferencia > self::REFERENCIA_MAXIMO_CARACTERES
            ) {
                throw new \InvalidArgumentException('A referência precisa ter entre 3 e 30 caracteres.');
            }
        }

        // =========================
        // PREÇO
        // =========================
        $preco = null;
        if ($dados->preco !== null) {
            $preco = (float) $dados->preco;

            if ($preco <= 0) {
                throw new \InvalidArgumentException('Preço do produto deve ser maior que zero.');
            }
        }

        // =========================
        // INATIVO
        // =========================
        $inativo = null;
        if ($dados->inativo !== null) {
            $inativo = (bool) $dados->inativo;
        }

        // =========================
        // CATEGORIA
        // =========================
        $categoria = null;
        if ($dados->categoria !== null) {
            $categoriaEntrada = $dados->categoria;

            $categoria = $categoriaEntrada instanceof CategoriaProdutos
                ? $categoriaEntrada
                : CategoriaProdutos::tryFrom((int) $categoriaEntrada);

            if (!$categoria) {
                throw new \InvalidArgumentException('Categoria inválida.');
            }
        }

        // =========================
        // DESCRIÇÃO
        // =========================
        $descricao = null;
        if ($dados->descricao !== null) {
            $descricao = trim($dados->descricao);

            if ($descricao === '') {
                throw new \InvalidArgumentException('Descrição não pode ser vazia.');
            }
        }

        // =========================
        // DUPLICIDADE
        // Só valida se nome ou referência vieram
        // =========================
        if ($nome !== null || $referencia !== null) {
            $nomeFinal = $nome ?? $produtoAtual['nome'];
            $referenciaFinal = $referencia ?? $produtoAtual['referencia'];

            $produtoExistente = $this->buscaNomeReferenciaExistente($nomeFinal, $referenciaFinal, true);

            if ($produtoExistente && (int) $produtoExistente['id'] !== $id) {
                throw new \InvalidArgumentException(
                    'Já existe um produto com o nome "' . $nomeFinal . '" e/ou referência "' . $referenciaFinal . '".'
                );
            }
        }

        // Garante que pelo menos 1 campo foi enviado
        if (
            $nome === null &&
            $referencia === null &&
            $preco === null &&
            $categoria === null &&
            $descricao === null &&
            $inativo === null
        ) {
            throw new \InvalidArgumentException('Nenhum campo válido foi informado para alteração.');
        }

        $produto = new ProdutoAlterarDTO(
            id: $id,
            nome: $nome,
            preco: $preco,
            categoria: $categoria,
            descricao: $descricao,
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

    public function obterPorId(int $id): ?array
    {
        $produto = $this->repository->buscarPorId($id);

        if (empty($produto)) {
            throw new \InvalidArgumentException('Produto não encontrado.');
        }

        return $produto;
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
