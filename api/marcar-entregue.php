<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

// Corpo esperado: {"ids": [1, 2, 3]}
$dados = json_decode(file_get_contents('php://input'), true);
$ids = is_array($dados) ? ($dados['ids'] ?? null) : null;

if (!is_array($ids) || count($ids) === 0) {
    responderJson(['erro' => 'Selecione ao menos uma entrega.'], 422);
}

$idsValidos = [];
foreach ($ids as $id) {
    $numero = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($numero === false) {
        responderJson(['erro' => 'Identificador de entrega inválido.'], 422);
    }
    $idsValidos[$numero] = $numero; // a chave remove repetidos
}

try {
    $repositorio = new EntregaRepository(Database::conexao());
    $marcadas = $repositorio->marcarComoEntregues($idsValidos);

    if ($marcadas === 0) {
        responderJson(['erro' => 'Nenhuma entrega foi marcada: as selecionadas já estavam entregues ou não existem.'], 409);
    }

    $mensagem = $marcadas === 1
        ? '1 entrega marcada como entregue.'
        : "{$marcadas} entregas marcadas como entregues.";

    $ignoradas = count($idsValidos) - $marcadas;
    if ($ignoradas > 0) {
        $mensagem .= " {$ignoradas} já estava(m) entregue(s) e não foi(ram) alterada(s).";
    }

    responderJson(['marcadas' => $marcadas, 'mensagem' => $mensagem]);
} catch (Throwable $e) {
    responderErro($e);
}
