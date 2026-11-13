<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

try {
    $repositorio = new EntregaRepository(Database::conexao());
    responderJson($repositorio->resumo());
} catch (Throwable $e) {
    responderErro($e);
}
