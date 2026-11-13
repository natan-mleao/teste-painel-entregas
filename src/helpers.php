<?php

// Fuso definido no início de toda requisição (todas as APIs carregam este arquivo),
// antes de qualquer date(); antes ele só era aplicado ao abrir a conexão.
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EntregaRepository.php';

function responderJson($dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function responderErro(Throwable $e): void
{
    // O detalhe técnico vai para o log do servidor, nunca para o navegador.
    error_log($e->getMessage());

    responderJson([
        'erro' => 'Não foi possível concluir a operação.',
    ], 500);
}

/**
 * Status possíveis:
 * - entregue: possui data de entrega, no dia do vencimento ou antes;
 * - entregue_atrasada: possui data de entrega posterior ao vencimento (melhoria);
 * - atrasada: não entregue e vencimento anterior a hoje;
 * - pendente: não entregue e vencimento hoje ou no futuro.
 */
function calcularStatus(array $entrega): string
{
    if (!empty($entrega['data_entrega'])) {
        return $entrega['data_entrega'] > $entrega['vencimento'] ? 'entregue_atrasada' : 'entregue';
    }

    // Datas no formato AAAA-MM-DD podem ser comparadas como texto.
    if ($entrega['vencimento'] < date('Y-m-d')) {
        return 'atrasada';
    }

    return 'pendente';
}
