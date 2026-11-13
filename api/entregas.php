<?php

require_once __DIR__ . '/../src/helpers.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    listarEntregas();
}

if ($metodo === 'POST') {
    cadastrarEntrega();
}

responderJson(['erro' => 'Método não permitido.'], 405);

function listarEntregas(): void
{
    // Parâmetros da URL; um valor que não seja texto (ex.: empresa[]=x) é tratado como vazio.
    $filtros = [];
    foreach (['empresa', 'departamento', 'status', 'competencia'] as $campo) {
        $valor = $_GET[$campo] ?? '';
        $filtros[$campo] = is_string($valor) ? trim($valor) : '';
    }

    if ($filtros['departamento'] !== '' && !in_array($filtros['departamento'], EntregaRepository::DEPARTAMENTOS, true)) {
        responderJson(['erro' => 'Departamento inválido. Use Fiscal, Pessoal ou Contábil.'], 400);
    }

    if ($filtros['status'] !== '' && !in_array($filtros['status'], EntregaRepository::STATUS, true)) {
        responderJson(['erro' => 'Status inválido. Use pendente, atrasada ou entregue.'], 400);
    }

    if ($filtros['competencia'] !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $filtros['competencia'])) {
        responderJson(['erro' => 'Competência inválida. Use o formato AAAA-MM.'], 400);
    }

    try {
        $repositorio = new EntregaRepository(Database::conexao());
        responderJson($repositorio->listar($filtros));
    } catch (Throwable $e) {
        responderErro($e);
    }
}

function cadastrarEntrega(): void
{
    $dados = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dados)) {
        responderJson(['erro' => 'Envie os dados da entrega em JSON.'], 400);
    }

    $empresaId = filter_var($dados['empresa_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $obrigacaoId = filter_var($dados['obrigacao_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $competencia = is_string($dados['competencia'] ?? null) ? trim($dados['competencia']) : '';
    $vencimento = is_string($dados['vencimento'] ?? null) ? trim($dados['vencimento']) : '';

    $erros = [];

    if ($empresaId === false) {
        $erros['empresa_id'] = 'Selecione a empresa.';
    }

    if ($obrigacaoId === false) {
        $erros['obrigacao_id'] = 'Selecione a obrigação.';
    }

    // Competência no formato AAAA-MM, com mês de 01 a 12.
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia)) {
        $erros['competencia'] = 'Informe a competência (mês e ano).';
    }

    if (!dataValida($vencimento)) {
        $erros['vencimento'] = 'Informe um vencimento válido.';
    }

    if ($erros) {
        responderJson(['erro' => implode(' ', $erros), 'campos' => $erros], 422);
    }

    try {
        $repositorio = new EntregaRepository(Database::conexao());

        if (!$repositorio->empresaExiste($empresaId)) {
            responderJson(['erro' => 'Empresa não encontrada.', 'campos' => ['empresa_id' => 'Empresa não encontrada.']], 422);
        }

        if (!$repositorio->obrigacaoExiste($obrigacaoId)) {
            responderJson(['erro' => 'Obrigação não encontrada.', 'campos' => ['obrigacao_id' => 'Obrigação não encontrada.']], 422);
        }

        if ($repositorio->entregaExiste($empresaId, $obrigacaoId, $competencia)) {
            responderJson(['erro' => 'Já existe uma entrega desta obrigação para esta empresa nesta competência.'], 409);
        }

        $id = $repositorio->criar($empresaId, $obrigacaoId, $competencia, $vencimento);

        responderJson(['id' => $id, 'mensagem' => 'Entrega cadastrada com sucesso.'], 201);
    } catch (PDOException $e) {
        // 1062 = chave única violada (cadastro duplicado feito ao mesmo tempo por outra pessoa).
        if (($e->errorInfo[1] ?? null) === 1062) {
            responderJson(['erro' => 'Já existe uma entrega desta obrigação para esta empresa nesta competência.'], 409);
        }
        responderErro($e);
    } catch (Throwable $e) {
        responderErro($e);
    }
}

// Confere formato AAAA-MM-DD e se a data existe (ex.: rejeita 2026-02-30).
function dataValida(string $data): bool
{
    $objeto = DateTime::createFromFormat('Y-m-d', $data);

    return $objeto !== false && $objeto->format('Y-m-d') === $data;
}
