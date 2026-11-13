# Painel de Entregas – Teste técnico de Desenvolvimento

Bem-vindo(a) ao teste técnico da vaga interna de **Desenvolvedor(a) Júnior**.

## Contexto

Escritórios contábeis precisam acompanhar, para cada empresa cliente, a entrega das obrigações acessórias do mês: DAS, DCTFWeb, FGTS Digital, eSocial, balancete e outras. O **Painel de Entregas** mostra essas entregas em uma lista, com um resumo por status.

Hoje o painel só exibe os dados. Sua missão é torná-lo útil no dia a dia: filtrar a lista, cadastrar novas entregas e registrar quando elas forem feitas.

## Regras

- **Tempo:** 2 horas.
- **Tecnologias:** PHP, MySQL, JavaScript, HTML e CSS puros, sem frameworks ou bibliotecas externas.
- **Estrutura:** siga a organização que já existe no projeto (consultas em `src/`, endpoints em `api/`, tela em `index.html` e `assets/`). Você pode criar novos arquivos.
- **Consultas:** pode consultar documentação, internet e ferramentas de IA, desde que saiba explicar na entrevista tudo o que entregou.
- **Commits:** faça commits pequenos, com mensagens claras. O histórico também é avaliado.
- **Prioridade:** não se preocupe em terminar tudo. Preferimos funcionalidades completas e bem feitas a muitas pela metade.

## Como rodar o projeto (XAMPP)

1. Baixe o arquivo `painel-entregas.zip` pelo link do Google Drive enviado a você e extraia o conteúdo em `C:\xampp\htdocs`. Ao final, deve existir a pasta `C:\xampp\htdocs\painel-entregas`, com este README dentro.
2. Abra o **XAMPP Control Panel** e inicie (**Start**) o **Apache** e o **MySQL**.
3. Acesse **http://localhost/phpmyadmin**, abra a aba **Importar**, escolha o arquivo `database/painel_entregas.sql` e clique em **Importar**. Isso cria o banco `painel_entregas` com dados de exemplo.
4. Acesse **http://localhost/painel-entregas** e confira se a lista de entregas aparece.

A conexão usa o padrão do XAMPP (usuário `root`, sem senha). Se a sua instalação for diferente, ajuste `src/config.php`.

Para **voltar os dados ao estado inicial**, importe o arquivo `database/painel_entregas.sql` de novo. As datas de exemplo são geradas a partir do dia da importação, então sempre haverá entregas pendentes, atrasadas e entregues.

## Estrutura do projeto

```
database/            Script do banco (estrutura e dados de exemplo)
src/                 PHP: conexão, regras e consultas
api/                 Endpoints da API (retornam JSON)
assets/              JavaScript e CSS da tela
index.html           Tela do painel
entrega/DEV.md       Modelo para você documentar sua entrega
```

### Banco de dados

| Tabela | Conteúdo |
| --- | --- |
| `empresas` | Empresas clientes: razão social, CNPJ e regime tributário |
| `obrigacoes` | Obrigações acessórias e o departamento responsável (Fiscal, Pessoal, Contábil) |
| `entregas` | Uma obrigação de uma empresa em uma competência (AAAA-MM), com vencimento e data de entrega (vazia enquanto não for entregue) |

### API existente

| Método | Endpoint | Descrição |
| --- | --- | --- |
| GET | `api/entregas.php` | Lista todas as entregas |
| GET | `api/resumo.php` | Totais por status |

## Regras de negócio

- **Status** de cada entrega:
    - **Entregue:** tem data de entrega.
    - **Atrasada:** não foi entregue e o vencimento é anterior a hoje.
    - **Pendente:** não foi entregue e o vencimento é hoje ou uma data futura.
- O **resumo** (Total, Entregues, Pendentes, Atrasadas) deve estar sempre de acordo com o banco: Entregues + Pendentes + Atrasadas = Total.
- Datas são exibidas no formato dd/mm/aaaa e competências no formato mm/aaaa.

---

## O desafio

### 1. Filtros da listagem

Adicione filtros acima da lista:

- **Empresa:** busca pela razão social, por qualquer parte do nome, sem diferenciar maiúsculas e minúsculas e aceitando qualquer caractere (acentos, apóstrofos, "&" etc.).
- **Departamento:** Fiscal, Pessoal ou Contábil.
- **Status:** Pendente, Atrasada ou Entregue.

Requisitos:

- Os filtros são aplicados pela **API**: `api/entregas.php` passa a aceitar parâmetros na URL e a consulta ao banco retorna só o que corresponde.
- Os filtros podem ser combinados.
- Deve haver uma forma de limpar os filtros.
- Quando nenhuma entrega corresponder, a tela mostra uma mensagem.

### 2. Cadastro de entregas

Crie um formulário para cadastrar uma nova entrega:

| Campo | Regra |
| --- | --- |
| Empresa | Obrigatório. Escolhida entre as empresas cadastradas |
| Obrigação | Obrigatório. Escolhida entre as obrigações cadastradas |
| Competência | Obrigatório. Mês e ano |
| Vencimento | Obrigatório. Data válida |

Requisitos:

- As listas de empresas e obrigações vêm do banco.
- As validações devem existir **no servidor** (PHP). Validar também na tela é um bônus.
- Não pode existir mais de uma entrega para a mesma empresa, obrigação e competência.
- Toda entrega nova nasce sem data de entrega.
- Após salvar, a lista e o resumo são atualizados e aparece uma mensagem de sucesso. Em caso de erro, a mensagem explica o que corrigir.

### 3. Marcar como entregue

- Cada linha **não entregue** ganha um botão **Marcar como entregue**.
- Cada linha não entregue ganha também uma caixa de seleção. Com uma ou mais linhas selecionadas, o botão **Marcar selecionadas como entregues** fica disponível e indica quantas estão selecionadas.
- A data de entrega registrada é a data de hoje.
- Uma entrega que já foi entregue não pode ter a data alterada, nem se alguém chamar a API diretamente.
- Após a ação, a lista e o resumo são atualizados, os filtros aplicados continuam valendo e aparece uma mensagem com quantas entregas foram marcadas.

### Bônus (opcional)

- Filtro por competência.
- Opção de selecionar todas as entregas visíveis de uma vez.
- Escolher a data de entrega, que não pode ser futura.
- Desfazer uma entrega marcada por engano.

### 4. Documentação

Preencha `entrega/DEV.md` com o que fez, as decisões que tomou e como testar.

---

## Como entregar

A pasta do projeto já é um repositório Git, com um commit inicial. Você só precisa fazer seus commits durante o teste e, ao final, publicar no GitHub.

**Antes de começar**, configure seu nome e e-mail no Git (no terminal do VS Code, dentro da pasta do projeto):

```
git config user.name "Seu Nome"
git config user.email "seu.email@acessorias.com"
```

**Durante o teste**, faça commits pequenos, com mensagens claras:

```
git add .
git commit -m "Adiciona filtro por departamento"
```

**Ao terminar:**

1. No [GitHub](https://github.com), crie um repositório **novo e vazio** (sem README, sem .gitignore e sem licença), com o nome `teste-painel-entregas`. Ele deve ser **público**, para que possamos avaliar.
2. No terminal, dentro da pasta do projeto, conecte e envie o projeto (troque `SEU-USUARIO` pelo seu usuário do GitHub):
   ```
   git remote add origin https://github.com/SEU-USUARIO/teste-painel-entregas.git
   git push -u origin main
   ```
   Se o Git pedir login, uma janela do navegador vai abrir para você entrar na sua conta do GitHub.
3. Abra o link do repositório em uma janela anônima do navegador e confira se ele abre e se seus commits estão lá.
4. Envie um e-mail para **fernando.sabino@acessorias.com** com:
    - **Assunto:** Teste técnico Dev – Seu Nome
    - **Corpo:** o link do repositório

O e-mail deve ser enviado até o horário de término do teste. Commits feitos depois desse horário não serão considerados.

## Como você será avaliado(a)

- **Funcionamento:** cada funcionalidade faz o que os requisitos pedem, inclusive nos casos de erro.
- **Qualidade do código:** organização, legibilidade, consistência com o projeto e cuidados com segurança.
- **Commits e documentação:** histórico claro e `DEV.md` completo.

Na entrevista, você vai apresentar sua entrega e explicar suas escolhas. Boa sorte!
