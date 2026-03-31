<?php

declare(strict_types=1);

namespace Application\Enum;

enum FormaPagamento: int
{
    case DINHEIRO = 0;
    case PIX = 1;
    case CARTAO_CREDITO = 2;
    case CARTAO_DEBITO = 3;
}
