// relatorio.js — Yehi Or
// window.echarts disponivel globalmente via app.js

const CORES = [
  '#F5C518', '#3b82f6', '#22c55e',
  '#ef4444', '#8b5cf6', '#f97316',
];

const CINZA   = '#6b7280';
const BG_CARD = '#1e2130';

const data   = window.chartData ?? {};
const pizza  = data.pizza  ?? [];
const barras = data.barras ?? {};

// ── Grafico de Pizza ──────────────────────────────────────────
const elPizza = document.getElementById('chart-pizza');
if (elPizza) {
  const chartPizza = window.echarts.init(elPizza, 'dark');
  chartPizza.setOption({
    backgroundColor: 'transparent',
    tooltip: {
      trigger:   'item',
      formatter: '{b}: {c} ({d}%)',
    },
    legend: {
      orient:    'vertical',
      right:     '2%',
      top:       'center',
      textStyle: { color: '#e8e8f0', fontSize: 12 },
    },
    series: [{
      type:   'pie',
      radius: ['40%', '70%'],
      center: ['38%', '50%'],
      avoidLabelOverlap: true,
      itemStyle: { borderRadius: 6, borderColor: BG_CARD, borderWidth: 2 },
      label:     { show: false },
      emphasis:  { label: { show: true, fontSize: 14, fontWeight: 'bold', color: '#fff' } },
      data: pizza.map((item, i) => ({
        ...item,
        itemStyle: { color: CORES[i % CORES.length] },
      })),
    }],
  });
  window.addEventListener('resize', () => chartPizza.resize());
}

// ── Grafico de Barras ─────────────────────────────────────────
const elBarras = document.getElementById('chart-barras');
if (elBarras) {
  const chartBarras = window.echarts.init(elBarras, 'dark');
  chartBarras.setOption({
    backgroundColor: 'transparent',
    tooltip: {
      trigger:     'axis',
      axisPointer: { type: 'shadow' },
    },
    legend: {
      top:       12,
      textStyle: { color: '#e8e8f0' },
      data:      ['Pendentes', 'Resolvidos'],
    },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: {
      type:      'category',
      data:      barras.meses ?? [],
      axisLabel: { color: CINZA },
      axisLine:  { lineStyle: { color: 'rgba(255,255,255,.1)' } },
    },
    yAxis: {
      type:        'value',
      minInterval: 1,
      axisLabel:   { color: CINZA },
      splitLine:   { lineStyle: { color: 'rgba(255,255,255,.06)' } },
    },
    series: [
      {
        name:        'Pendentes',
        type:        'bar',
        stack:       'total',
        barMaxWidth: 40,
        itemStyle:   { color: '#F5C518', borderRadius: [0, 0, 4, 4] },
        data:        barras.pendentes ?? [],
      },
      {
        name:        'Resolvidos',
        type:        'bar',
        stack:       'total',
        barMaxWidth: 40,
        itemStyle:   { color: '#22c55e', borderRadius: [4, 4, 0, 0] },
        data:        barras.resolvidos ?? [],
      },
    ],
  });
  window.addEventListener('resize', () => chartBarras.resize());
}