<?php

$db = new PDO('sqlite:data/database.sqlite');

$colunas = $db->query("PRAGMA table_info(produtos)")->fetchAll(PDO::FETCH_ASSOC);

$existe = false;

foreach ($colunas as $coluna) {
    if ($coluna['name'] === 'categoria') {
        $existe = true;
        break;
    }
}

if (!$existe) {
    $db->exec("ALTER TABLE produtos ADD COLUMN categoria TEXT;");
    echo "Coluna adicionada!";
} else {
    echo "Coluna já existe.";
}
