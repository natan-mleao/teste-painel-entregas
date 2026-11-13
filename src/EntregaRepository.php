<?php

class EntregaRepository
{
    public const DEPARTAMENTOS = ['Fiscal', 'Pessoal', 'Contábil'];
    public const STATUS = ['pendente', 'atrasada', 'entregue', 'entregue_atrasada'];

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
                // "entregue" continua sendo toda entrega com data (regra do README);
                // "entregue_atrasada" filtra só as entregues depois do vencimento.
                'entregue' => 'e.data_entrega IS NOT NULL',
                'entregue_atrasada' => 'e.data_entrega > e.vencimento',
                'atrasada' => 'e.data_entrega IS NULL AND e.vencimento < CURDATE()',
                'pendente' => 'e.data_entrega IS NULL AND e.vencimento >= CURDATE()',
            };
        }

        if (($filtros['competencia'] ?? '') !== '') {
            $condicoes[] = 'e.competencia = :competencia';
            $parametros['competencia'] = $filtros['competencia'];
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

    public function listarEmpresas(): array
    {
        $sql = 'SELECT id, razao_social, cnpj FROM empresas ORDER BY razao_social';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function listarObrigacoes(): array
    {
        $sql = 'SELECT id, nome, departamento FROM obrigacoes ORDER BY nome';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function empresaExiste(int $id): bool
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM empresas WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function obrigacaoExiste(int $id): bool
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM obrigacoes WHERE id = :id');
        $consulta->execute(['id' => $id]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function entregaExiste(int $empresaId, int $obrigacaoId, string $competencia): bool
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM entregas
              WHERE empresa_id = :empresa_id AND obrigacao_id = :obrigacao_id AND competencia = :competencia'
        );
        $consulta->execute([
            'empresa_id' => $empresaId,
            'obrigacao_id' => $obrigacaoId,
            'competencia' => $competencia,
        ]);

        return (int) $consulta->fetchColumn() > 0;
    }

    /**
     * Toda entrega nova nasce sem data de entrega (data_entrega = NULL).
     */
    public function criar(int $empresaId, int $obrigacaoId, string $competencia, string $vencimento): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO entregas (empresa_id, obrigacao_id, competencia, vencimento, data_entrega)
             VALUES (:empresa_id, :obrigacao_id, :competencia, :vencimento, NULL)'
        );
        $consulta->execute([
            'empresa_id' => $empresaId,
            'obrigacao_id' => $obrigacaoId,
            'competencia' => $competencia,
            'vencimento' => $vencimento,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Marca como entregues (data de hoje) apenas as entregas ainda não entregues.
     * A condição "data_entrega IS NULL" fica no próprio UPDATE: uma entrega já
     * entregue nunca tem a data alterada, mesmo que a API seja chamada diretamente.
     * Retorna quantas entregas foram de fato marcadas.
     */
    // $data = null usa a data de hoje do banco (CURDATE()); senão, a data escolhida (já validada na API).
    public function marcarComoEntregues(array $ids, ?string $data = null): int
    {
        $marcadores = implode(', ', array_fill(0, count($ids), '?'));

        $consulta = $this->pdo->prepare(
            "UPDATE entregas
                SET data_entrega = COALESCE(?, CURDATE())
              WHERE data_entrega IS NULL
                AND id IN ({$marcadores})"
        );
        $consulta->execute([$data, ...array_values($ids)]);

        return $consulta->rowCount();
    }

    public function resumo(): array
    {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(data_entrega IS NOT NULL), 0) AS entregues,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento >= CURDATE()), 0) AS pendentes,
                       COALESCE(SUM(data_entrega IS NULL AND vencimento < CURDATE()), 0) AS atrasadas,
                       COALESCE(SUM(data_entrega <= vencimento), 0) AS entregues_no_prazo,
                       COALESCE(SUM(data_entrega > vencimento), 0) AS entregues_com_atraso
                  FROM entregas";

        return array_map('intval', $this->pdo->query($sql)->fetch());
    }
}
