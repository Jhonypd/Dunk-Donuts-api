<?php

declare(strict_types=1);

namespace Application\Enum;

enum StatusPedido: int
{
    case PENDENTE = 0;
    case EM_PREPARACAO = 1;
    case PRONTO = 2;
    case ENTREGUE = 3;
    case CANCELADO = 4;

    public static function isFinal(self $status): bool
    {
        return in_array($status, [self::ENTREGUE, self::CANCELADO], true);
    }
}
