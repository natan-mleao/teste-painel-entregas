<?php

class EntregaRepository
{
    public const DEPARTAMENTOS = ['Fiscal', 'Pessoal', 'Contábil'];
    public const STATUS = ['pendente', 'atrasada', 'entregue'];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Filtros aceitos (todos opcionais e combináveis):
     * - empresa: parte da razão social;
     * - departamento: um de self::DEPARTAMENTOS;
     * - status: um de self::STATUS.
     */
    public function listar(array $filtros = []): array
    {
        $condicoes = [];
        $parametros = [];

        if (($filtros['empresa'] ?? '') !== '') {
            // % e _ são curingas do LIKE: escapados para serem buscados como texto.
            $condicoes[] = 'emp.razao_social LIKE :empresa';
            $parametros['empresa'] = '%' . addcslashes($filtros['empresa'], '%_\\') . '%';
        }

        if (($filtros['departamento'] ?? '') !== '') {
            $condicoes[] = 'o.departamento = :departamento';
            $parametros['departamento'] = $filtros['departamento'];
        }

        if (($filtros['status'] ?? '') !== '') {
            $condicoes[] = match ($filtros['status']) {
                'entregue' => 'e.data_entrega IS NOT NULL',
                'atrasada' => 'e.data_entrega IS NULL AND e.vencimento < CURDATE()',
                'pendente' => 'e.data_entrega IS NULL AND e.vencimento >= CURDATE()',
            };
        }

        $where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

        $sql = "SELECT e.id,
                       e.competencia,
                       e.vencimento,
                       e.data_entrega,
                       emp.id AS empresa_id,
                       emp.razao_social,
                       emp.cnpj,
                       o.nome AS obrigacao,
                       o.departamento
                  FROM entregas e
                  JOIN empresas emp ON emp.id = e.empresa_id
                  JOIN obrigacoes o ON o.id = e.obrigacao_id
                  {$where}
                 ORDER BY e.vencimento ASC, emp.razao_social ASC";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        $entregas = $consulta->fetchAll();

        foreach ($entregas as &$entrega) {
            $entrega['id'] = (int) $entrega['id'];
            $entrega['empresa_id'] = (int) $entrega['empresa_id'];
            $entrega['status'] = calcularStatus($entrega);
        }

        return $entregas;
    }

    public function resumo(): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(data_entrega IS NOT NULL), 0) AS entregues,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento >= CURDATE()), 0) AS pendentes,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento < CURDATE()), 0) AS atrasadas
                  FROM entregas";

        return array_map('intval', $this->pdo->query($sql)->fetch());
    }
}
