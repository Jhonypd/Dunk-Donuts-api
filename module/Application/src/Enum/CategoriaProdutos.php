<?php

namespace Application\Enum;

enum CategoriaProdutos: int
{
    case NOVIDADES = 0;
    case RECHEADOS = 1;
    case NORMAIS = 2;
    case GOURMES = 3;

    case PROMOCOES = 4;
}
