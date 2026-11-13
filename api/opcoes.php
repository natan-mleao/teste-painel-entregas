<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

try {
    $repositorio = new EntregaRepository(Database::conexao());
    responderJson([
        'empresas' => $repositorio->listarEmpresas(),
        'obrigacoes' => $repositorio->listarObrigacoes(),
    ]);
} catch (Throwable $e) {
    responderErro($e);
}
