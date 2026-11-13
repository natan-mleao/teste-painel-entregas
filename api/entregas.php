<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

// Parâmetros da URL; um valor que não seja texto (ex.: empresa[]=x) é tratado como vazio.
$filtros = [];
foreach (['empresa', 'departamento', 'status'] as $campo) {
    $valor = $_GET[$campo] ?? '';
    $filtros[$campo] = is_string($valor) ? trim($valor) : '';
}

if ($filtros['departamento'] !== '' && !in_array($filtros['departamento'], EntregaRepository::DEPARTAMENTOS, true)) {
    responderJson(['erro' => 'Departamento inválido. Use Fiscal, Pessoal ou Contábil.'], 400);
}

if ($filtros['status'] !== '' && !in_array($filtros['status'], EntregaRepository::STATUS, true)) {
    responderJson(['erro' => 'Status inválido. Use pendente, atrasada ou entregue.'], 400);
}

try {
    $repositorio = new EntregaRepository(Database::conexao());
    responderJson($repositorio->listar($filtros));
} catch (Throwable $e) {
    responderErro($e);
}
