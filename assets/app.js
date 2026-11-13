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
    corpo.innerHTML = `<tr><td colspan="7" class="tabela__vazio">${mensagem}</td></tr>`;
    return;
  }

  corpo.innerHTML = entregas.map((entrega) => `
    <tr>
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
    </tr>
  `).join('');
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

carregarResumo();
carregarEntregas();
