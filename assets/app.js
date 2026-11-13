const API = 'api';

let entregas = [];

const rotulosStatus = {
  entregue: 'Entregue',
  pendente: 'Pendente',
  atrasada: 'Atrasada',
};

function escaparHtml(texto) {
  const div = document.createElement('div');
  div.textContent = texto ?? '';
  return div.innerHTML;
}

// Recebe AAAA-MM-DD e devolve DD/MM/AAAA, sem converter fuso horário.
function formatarData(data) {
  if (!data) {
    return '—';
  }
  const [ano, mes, dia] = data.split('-');
  return `${dia}/${mes}/${ano}`;
}

// Recebe AAAA-MM e devolve MM/AAAA.
function formatarCompetencia(competencia) {
  const [ano, mes] = competencia.split('-');
  return `${mes}/${ano}`;
}

function mostrarAviso(mensagem, tipo = 'sucesso') {
  const aviso = document.getElementById('aviso');
  aviso.textContent = mensagem;
  aviso.className = tipo === 'erro' ? 'aviso aviso--erro' : 'aviso';
  aviso.hidden = false;
  clearTimeout(mostrarAviso.timer);
  mostrarAviso.timer = setTimeout(() => { aviso.hidden = true; }, 4000);
}

async function carregarResumo() {
  try {
    const resposta = await fetch(`${API}/resumo.php`);
    const resumo = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(resumo.erro || 'Erro ao carregar o resumo.', 'erro');
      return;
    }

    document.getElementById('resumo-total').textContent = resumo.total;
    document.getElementById('resumo-entregues').textContent = resumo.entregues;
    document.getElementById('resumo-pendentes').textContent = resumo.pendentes;
    document.getElementById('resumo-atrasadas').textContent = resumo.atrasadas;
  } catch (erro) {
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }
}

// Monta os parâmetros da URL só com os filtros preenchidos.
// URLSearchParams codifica caracteres especiais (&, apóstrofo, acentos).
function parametrosDosFiltros() {
  const parametros = new URLSearchParams();
  const dados = new FormData(document.getElementById('form-filtros'));

  for (const [campo, valor] of dados) {
    if (valor.trim() !== '') {
      parametros.set(campo, valor.trim());
    }
  }

  return parametros;
}

// Evita que uma resposta antiga (de uma busca anterior) sobrescreva a mais recente.
let ultimaRequisicao = 0;

async function carregarEntregas() {
  const parametros = parametrosDosFiltros();
  const requisicao = ++ultimaRequisicao;

  document.getElementById('limpar-filtros').disabled = parametros.toString() === '';

  try {
    const url = parametros.toString() ? `${API}/entregas.php?${parametros}` : `${API}/entregas.php`;
    const resposta = await fetch(url);
    const dados = await resposta.json();

    if (requisicao !== ultimaRequisicao) {
      return;
    }

    if (!resposta.ok) {
      entregas = [];
      mostrarAviso(dados.erro || 'Erro ao carregar as entregas.', 'erro');
    } else {
      entregas = dados;
    }
  } catch (erro) {
    if (requisicao !== ultimaRequisicao) {
      return;
    }
    entregas = [];
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }

  renderizarTabela(parametros.toString() !== '');
}

function renderizarTabela(comFiltros = false) {
  const corpo = document.getElementById('lista-entregas');

  if (entregas.length === 0) {
    const mensagem = comFiltros
      ? 'Nenhuma entrega corresponde aos filtros aplicados.'
      : 'Nenhuma entrega encontrada.';
    corpo.innerHTML = `<tr><td colspan="9" class="tabela__vazio">${mensagem}</td></tr>`;
    atualizarSelecao();
    return;
  }

  corpo.innerHTML = entregas.map((entrega) => {
    const naoEntregue = entrega.status !== 'entregue';
    return `
    <tr>
      <td class="tabela__selecao">
        ${naoEntregue ? `<input type="checkbox" class="selecao-entrega" value="${entrega.id}" aria-label="Selecionar ${escaparHtml(entrega.obrigacao)} de ${escaparHtml(entrega.razao_social)}">` : ''}
      </td>
      <td>
        <span class="tabela__empresa">${escaparHtml(entrega.razao_social)}</span>
        <span class="tabela__cnpj">${escaparHtml(entrega.cnpj)}</span>
      </td>
      <td>${escaparHtml(entrega.obrigacao)}</td>
      <td>${escaparHtml(entrega.departamento)}</td>
      <td>${formatarCompetencia(entrega.competencia)}</td>
      <td>${formatarData(entrega.vencimento)}</td>
      <td>${formatarData(entrega.data_entrega)}</td>
      <td><span class="status status--${entrega.status}">${rotulosStatus[entrega.status]}</span></td>
      <td>
        ${naoEntregue ? `<button type="button" class="botao botao--secundario" data-marcar="${entrega.id}">Marcar como entregue</button>` : ''}
      </td>
    </tr>
  `;
  }).join('');

  atualizarSelecao();
}

// ---------- Marcar como entregue ----------

function idsSelecionados() {
  return [...document.querySelectorAll('.selecao-entrega:checked')].map((caixa) => Number(caixa.value));
}

// Atualiza o botão em lote (contador e disponibilidade) e a caixa "selecionar todas".
function atualizarSelecao() {
  const caixas = document.querySelectorAll('.selecao-entrega');
  const quantidade = idsSelecionados().length;

  const botao = document.getElementById('marcar-selecionadas');
  botao.disabled = quantidade === 0;
  botao.textContent = `Marcar selecionadas como entregues (${quantidade})`;

  const todas = document.getElementById('selecionar-todas');
  todas.disabled = caixas.length === 0;
  todas.checked = caixas.length > 0 && quantidade === caixas.length;
  todas.indeterminate = quantidade > 0 && quantidade < caixas.length;
}

async function marcarComoEntregues(ids) {
  try {
    const resposta = await fetch(`${API}/marcar-entregue.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ids }),
    });
    const resultado = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(resultado.erro || 'Não foi possível marcar as entregas.', 'erro');
    } else {
      mostrarAviso(resultado.mensagem);
    }
  } catch (erro) {
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }

  // Recarrega mantendo os filtros aplicados (carregarEntregas lê o formulário de filtros).
  carregarEntregas();
  carregarResumo();
}

// Filtros: aplicados ao digitar (com pequena espera) e ao trocar uma opção.
let esperaBusca;
const formFiltros = document.getElementById('form-filtros');

formFiltros.addEventListener('submit', (evento) => evento.preventDefault());
formFiltros.addEventListener('change', carregarEntregas);
document.getElementById('filtro-empresa').addEventListener('input', () => {
  clearTimeout(esperaBusca);
  esperaBusca = setTimeout(carregarEntregas, 300);
});
document.getElementById('limpar-filtros').addEventListener('click', () => {
  formFiltros.reset();
  carregarEntregas();
  document.getElementById('filtro-empresa').focus();
});

// ---------- Cadastro de entregas ----------

async function carregarOpcoesCadastro() {
  try {
    const resposta = await fetch(`${API}/opcoes.php`);
    const opcoes = await resposta.json();

    if (!resposta.ok) {
      mostrarAviso(opcoes.erro || 'Erro ao carregar empresas e obrigações.', 'erro');
      return;
    }

    preencherSelect('cadastro-empresa', opcoes.empresas, (empresa) => empresa.razao_social);
    preencherSelect('cadastro-obrigacao', opcoes.obrigacoes, (obrigacao) => `${obrigacao.nome} (${obrigacao.departamento})`);
  } catch (erro) {
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  }
}

// Cria as opções com textContent (sem montar HTML), então nomes com "&" ou apóstrofo aparecem certos.
function preencherSelect(id, itens, rotulo) {
  const select = document.getElementById(id);
  for (const item of itens) {
    const opcao = document.createElement('option');
    opcao.value = item.id;
    opcao.textContent = rotulo(item);
    select.append(opcao);
  }
}

function limparErrosCadastro(form) {
  form.querySelectorAll('.campo__erro').forEach((erro) => erro.remove());
  form.querySelectorAll('.campo--erro').forEach((campo) => campo.classList.remove('campo--erro'));
}

// Mostra a mensagem embaixo de cada campo com erro.
function mostrarErrosCadastro(form, erros) {
  for (const [nome, mensagem] of Object.entries(erros)) {
    const campo = form.elements[nome]?.closest('.campo');
    if (!campo) {
      continue;
    }
    campo.classList.add('campo--erro');
    const texto = document.createElement('span');
    texto.className = 'campo__erro';
    texto.textContent = mensagem;
    campo.append(texto);
  }
}

// Validação na tela (bônus); o servidor valida de novo de qualquer forma.
// Data de hoje no formato AAAA-MM-DD pelo horário local
// (toISOString usaria UTC e, à noite, já devolveria o dia seguinte).
function hojeIso() {
  const hoje = new Date();
  const mes = String(hoje.getMonth() + 1).padStart(2, '0');
  const dia = String(hoje.getDate()).padStart(2, '0');
  return `${hoje.getFullYear()}-${mes}-${dia}`;
}

// Abre o modal de confirmação e devolve uma Promise: true = confirmou, false = cancelou.
// Usa o <dialog> nativo: showModal() bloqueia a página por trás e a tecla Esc já cancela.
function confirmarModal(texto) {
  const modal = document.getElementById('modal-confirmacao');
  document.getElementById('modal-texto').textContent = texto;

  return new Promise((resolver) => {
    const responder = (evento) => {
      const botao = evento.target.closest('[data-resposta]');
      if (botao) modal.close(botao.dataset.resposta);
    };
    modal.addEventListener('click', responder);
    modal.addEventListener('close', () => {
      modal.removeEventListener('click', responder);
      resolver(modal.returnValue === 'sim');
    }, { once: true });

    modal.returnValue = '';
    modal.showModal();
  });
}

function validarCadastro(dados) {
  const erros = {};
  if (!dados.empresa_id) erros.empresa_id = 'Selecione a empresa.';
  if (!dados.obrigacao_id) erros.obrigacao_id = 'Selecione a obrigação.';
  if (!dados.competencia) erros.competencia = 'Informe a competência (mês e ano).';
  if (!dados.vencimento) erros.vencimento = 'Informe um vencimento válido.';
  return erros;
}

document.getElementById('form-cadastro').addEventListener('submit', async (evento) => {
  evento.preventDefault();
  const form = evento.target;
  const botao = form.querySelector('button[type="submit"]');
  const dados = Object.fromEntries(new FormData(form));

  limparErrosCadastro(form);

  const errosTela = validarCadastro(dados);
  if (Object.keys(errosTela).length > 0) {
    mostrarErrosCadastro(form, errosTela);
    mostrarAviso('Corrija os campos destacados.', 'erro');
    return;
  }

  // Melhoria: avisa quando o vencimento já passou, porque a entrega nasceria atrasada.
  // Datas AAAA-MM-DD podem ser comparadas como texto.
  if (dados.vencimento < hojeIso()) {
    const confirmar = await confirmarModal(
      `O vencimento ${formatarData(dados.vencimento)} já passou, então esta entrega será cadastrada como atrasada. Deseja continuar?`
    );
    if (!confirmar) {
      document.getElementById('cadastro-vencimento').focus();
      return;
    }
  }

  botao.disabled = true;
  try {
    const resposta = await fetch(`${API}/entregas.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados),
    });
    const resultado = await resposta.json();

    if (!resposta.ok) {
      mostrarErrosCadastro(form, resultado.campos || {});
      mostrarAviso(resultado.erro || 'Não foi possível cadastrar a entrega.', 'erro');
      return;
    }

    form.reset();
    mostrarAviso(resultado.mensagem);
    carregarEntregas();
    carregarResumo();
  } catch (erro) {
    mostrarAviso('Não foi possível conectar à API.', 'erro');
  } finally {
    botao.disabled = false;
  }
});

// Um único listener na tabela atende os botões e caixas criados a cada renderização.
document.getElementById('lista-entregas').addEventListener('click', (evento) => {
  const botao = evento.target.closest('[data-marcar]');
  if (botao) {
    botao.disabled = true;
    marcarComoEntregues([Number(botao.dataset.marcar)]);
  }
});

document.getElementById('lista-entregas').addEventListener('change', (evento) => {
  if (evento.target.matches('.selecao-entrega')) {
    atualizarSelecao();
  }
});

document.getElementById('selecionar-todas').addEventListener('change', (evento) => {
  document.querySelectorAll('.selecao-entrega').forEach((caixa) => {
    caixa.checked = evento.target.checked;
  });
  atualizarSelecao();
});

document.getElementById('marcar-selecionadas').addEventListener('click', (evento) => {
  const ids = idsSelecionados();
  if (ids.length > 0) {
    evento.target.disabled = true;
    marcarComoEntregues(ids);
  }
});

carregarOpcoesCadastro();
carregarResumo();
carregarEntregas();
