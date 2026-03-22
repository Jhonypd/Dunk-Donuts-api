<?php

namespace Application\Enum;

enum CategoriaProdutos: string
{
    case NOVIDADES = 'novidades';
    case RECHEADOS = 'recheados';
    case NORMAIS = 'normais';
    case GOURMES = 'gourmes';

    case PROMOCOES = 'promocoes';
}
