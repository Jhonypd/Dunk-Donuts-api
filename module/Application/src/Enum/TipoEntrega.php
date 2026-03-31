<?php

declare(strict_types=1);

namespace Application\Enum;

enum TipoEntrega: int
{
    case RETIRADA = 0;
    case ENTREGA = 1;
}
