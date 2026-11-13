<?php

require_once __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido.'], 405);
}

// Corpo esperado: {"ids": [1, 2, 3], "data_entrega": "AAAA-MM-DD"} (data opcional: sem ela, vale hoje)
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

// Bônus: data de entrega escolhida. Precisa ser uma data real e não pode ser futura.
$dataEntrega = $dados['data_entrega'] ?? null;
if ($dataEntrega === '') {
    $dataEntrega = null;
}
if ($dataEntrega !== null) {
    $objeto = is_string($dataEntrega) ? DateTime::createFromFormat('Y-m-d', $dataEntrega) : false;
    if ($objeto === false || $objeto->format('Y-m-d') !== $dataEntrega) {
        responderJson(['erro' => 'Informe uma data de entrega válida.'], 422);
    }
    if ($dataEntrega > date('Y-m-d')) {
        responderJson(['erro' => 'A data de entrega não pode ser futura.'], 422);
    }
}

try {
    $repositorio = new EntregaRepository(Database::conexao());
    $marcadas = $repositorio->marcarComoEntregues($idsValidos, $dataEntrega);

    if ($marcadas === 0) {
        responderJson(['erro' => 'Nenhuma entrega foi marcada: as selecionadas já estavam entregues ou não existem.'], 409);
    }

    $mensagem = $marcadas === 1
        ? '1 entrega marcada como entregue.'
        : "{$marcadas} entregas marcadas como entregues.";

    if ($dataEntrega !== null && $dataEntrega !== date('Y-m-d')) {
        $mensagem = rtrim($mensagem, '.') . ' em ' . DateTime::createFromFormat('Y-m-d', $dataEntrega)->format('d/m/Y') . '.';
    }

    $ignoradas = count($idsValidos) - $marcadas;
    if ($ignoradas > 0) {
        $mensagem .= " {$ignoradas} já estava(m) entregue(s) e não foi(ram) alterada(s).";
    }

    responderJson(['marcadas' => $marcadas, 'mensagem' => $mensagem]);
} catch (Throwable $e) {
    responderErro($e);
}
