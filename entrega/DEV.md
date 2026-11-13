# Entrega – Teste de Desenvolvimento

**Nome:** Natanael Leão
**Tempo utilizado:** cerca de 2 horas

## O que foi feito

- [x] **Filtros da listagem**
  - `api/entregas.php` (GET) aceita `empresa`, `departamento` e `status` na URL, combináveis.
  - `EntregaRepository::listar()` monta o `WHERE` só com os filtros informados, sempre com *prepared statement*.
  - Empresa: `LIKE` por qualquer parte da razão social. A collation `utf8mb4_unicode_ci` já ignora maiúsculas e acentos; `%` e `_` são escapados para serem buscados como texto. Apóstrofo e `&` funcionam (parâmetros do PDO no servidor e `URLSearchParams` na tela).
  - Status filtrado no SQL com as mesmas regras do resumo (`CURDATE()`).
  - Departamento e status são validados contra listas permitidas (400 para valor inválido).
  - Tela: busca aplicada ao digitar (espera de 300 ms) e selects ao trocar; botão **Limpar filtros** (desabilitado sem filtro ativo); mensagem quando nenhuma entrega corresponde.
- [x] **Cadastro de entregas**
  - `api/opcoes.php` (GET) lista empresas e obrigações do banco para os selects.
  - `api/entregas.php` (POST, JSON) valida no servidor: empresa e obrigação existentes, competência `AAAA-MM` (mês 01–12) e vencimento como data real (ex.: rejeita 30/02).
  - Respostas: 201 criado, 422 erro de validação (com o erro de cada campo), 409 entrega duplicada.
  - Toda entrega nasce com `data_entrega` vazia.
  - Tela: validação também no navegador, erro exibido embaixo de cada campo, botão desabilitado durante o envio, lista e resumo atualizados após salvar.
- [x] **Marcar como entregue (uma linha)**
- [x] **Marcar várias como entregues (seleção)**
  - `api/marcar-entregue.php` (POST, `{"ids": [...], "data_entrega": "AAAA-MM-DD"}`) grava a data escolhida no modal (padrão: hoje); sem `data_entrega`, usa a data de hoje do banco (`CURDATE()`).
  - Botão por linha e caixas de seleção só nas entregas não entregues; botão em lote mostra a quantidade selecionada e fica desabilitado sem seleção.
  - Após a ação: mensagem com quantas foram marcadas, lista e resumo atualizados, filtros mantidos.
- [x] **Bônus:** selecionar todas as entregas visíveis de uma vez (caixa no cabeçalho da tabela).
- [x] **Bônus:** filtro por competência (campo mês/ano nos filtros, combinável com os demais; a API valida `AAAA-MM` e responde 400 se inválido).
- [x] **Bônus:** escolher a data de entrega, que não pode ser futura.
  - Ao clicar em marcar (na linha ou em lote), abre um modal para escolher a data de conclusão, já preenchida com hoje e sem permitir dia futuro; Cancelar ou Esc não altera nada e mantém a seleção.
  - `api/marcar-entregue.php` aceita `data_entrega` (a tela sempre envia a data escolhida; chamada sem ela usa `CURDATE()`): valida data real e rejeita data futura (422). A regra de não alterar entregas já entregues continua no `UPDATE ... WHERE data_entrega IS NULL`.
- [x] **Melhoria proposta:** aviso ao cadastrar uma entrega com vencimento já passado.
  - Na tela, antes de enviar, abre um modal de confirmação (`<dialog>` nativo) informando que a entrega será cadastrada como atrasada; Cancelar ou Esc não envia nada.
  - No servidor, o cadastro continua permitido (o README não proíbe vencimento no passado: pode ser o registro de algo que já venceu); a mensagem de sucesso é a mesma dos outros cadastros.

- [x] **Melhoria proposta:** novo status **Entregue com atraso** e resumo reorganizado.
  - Uma entrega com data de entrega posterior ao vencimento aparece como **Entregue com atraso** (`entregue_atrasada`), com etiqueta própria na tabela.
  - Resumo: card maior para o **Total**, depois **Pendentes**, **Atrasadas** e **Entregues**, com o detalhe "no prazo" e "com atraso" embaixo.
  - Compatível com o README: "Entregues" continua contando toda entrega com data, então Entregues + Pendentes + Atrasadas = Total. No filtro, **Entregue (todas)** mantém o comportamento original e **Entregue com atraso** filtra só as entregues após o vencimento. `api/resumo.php` ganhou os campos `entregues_no_prazo` e `entregues_com_atraso`, sem remover nenhum.

## Decisões tomadas

- **Estrutura:** mantida a organização do projeto. Consultas em `src/EntregaRepository.php`, endpoints em `api/`, tela em `index.html` e `assets/`. O GET e o POST de entregas ficam no mesmo endpoint (`api/entregas.php`), em funções separadas.
- **Duplicidade em duas camadas:** o PHP verifica antes de salvar, para dar uma mensagem clara (409). Além disso, adicionei uma restrição `UNIQUE (empresa_id, obrigacao_id, competencia)` no `database/painel_entregas.sql`, que garante a regra mesmo se dois cadastros iguais chegarem ao mesmo tempo; esse erro do banco (1062) é convertido na mesma mensagem. **É preciso reimportar o `.sql`** para a restrição existir.
- **"Já entregue não pode ser alterada":** a condição `data_entrega IS NULL` fica dentro do próprio `UPDATE`. Assim a regra vale mesmo chamando a API diretamente, sem depender da tela e sem risco de duas requisições simultâneas. Se nenhuma das entregas enviadas puder ser marcada, a API responde 409.
- **Datas e fuso:** a data de entrega gravada é a escolhida no modal (validada no servidor, nunca futura). O "hoje" do status, do resumo e da validação usa o mesmo fuso em todas as camadas: `America/Sao_Paulo` definido no início de toda requisição (`src/helpers.php`), `SET time_zone = '-03:00'` na conexão do MySQL e a data local no navegador.
- **Segurança:**
  - todas as consultas com *prepared statements*, inclusive a lista de IDs do `IN (...)`, com marcadores gerados;
  - valores de listas (departamento, status) validados contra listas permitidas;
  - parâmetros que não sejam texto (ex.: `empresa[]=x`) são tratados como vazios;
  - na tela, conteúdo do banco inserido com escape (`escaparHtml`, que também escapa aspas para uso seguro em atributos como `aria-label`) ou `textContent`;
  - `responderErro()` não devolve mais a mensagem interna da exceção para o navegador; o detalhe vai para o log do servidor.
- **Status HTTP:** 400 para parâmetros inválidos, 422 para dados inválidos, 409 para conflito (duplicada / já entregue), 201 para criação, 405 para método errado.
- **Tela:** um único *listener* no corpo da tabela atende os botões e caixas criados a cada renderização (delegação de eventos). Respostas antigas de busca são ignoradas se uma busca mais nova já foi feita.

## Como testar

1. Reimporte `database/painel_entregas.sql` no phpMyAdmin e abra http://localhost/painel-entregas.
2. **Filtros:**
   - digite `d'avila` → Padaria D'Ávila; digite `café &` → Café & Prosa;
   - combine Departamento **Pessoal** + Status **Atrasada**;
   - digite `xyz` → mensagem de nenhuma entrega encontrada;
   - **Limpar filtros** → lista completa e botão desabilitado.
   - escolha uma **Competência** → só entregas daquele mês, combinável com os outros filtros;
   - API: `api/entregas.php?status=xyz` ou `?competencia=2026-13` → 400.
3. **Cadastro:**
   - cadastre uma entrega → mensagem de sucesso, entrega na lista e Total do resumo +1;
   - cadastre a mesma de novo → mensagem de entrega duplicada;
   - envie com campos vazios → erro em cada campo;
   - informe um vencimento anterior a hoje → modal de confirmação; Cancelar não salva; confirmar cadastra e a entrega aparece como Atrasada.
4. **Marcar como entregue:**
   - botão de uma linha → 1 marcada, status e resumo atualizados;
   - selecione várias → o botão em lote mostra a quantidade → marcadas todas;
   - com um filtro aplicado, marque uma → o filtro continua;
   - caixa do cabeçalho → seleciona todas as visíveis.
   - ao marcar, o modal abre com a data de hoje; escolha uma data passada → a coluna mostra a data escolhida e a mensagem cita a data; data futura → recusada (no modal e na API com 422); Cancelar → nada muda.
   - API direta com uma entrega já entregue (Console do navegador):
     ```js
     fetch('api/marcar-entregue.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ids: [1]})}).then(r => r.json()).then(console.log)
     ```
     → 409, e a data não muda.

## O que ficou faltando ou você faria com mais tempo

- Paginação da lista, para escritórios com muitas entregas.
- Bônus "desfazer uma entrega marcada por engano" (voltar para pendente): não fiz porque conflita com a regra obrigatória de que uma entrega entregue não pode ter a data alterada, nem pela API. Com mais tempo, faria como uma ação separada e explícita (endpoint próprio, com confirmação), registrando cada reversão em um **log de alterações** com o usuário que a realizou, a data/hora e a data de entrega anterior. Assim a correção de um engano fica possível sem perder a rastreabilidade, e a marcação normal continua sem poder alterar uma entrega já entregue.
