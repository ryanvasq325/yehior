import Charts from '../components/charts.js';

new Charts().setId('chartsale').getData('/admin/gestao/getsalesdata').BAR().render();
new Charts().setId('chartabc').getData('/admin/gestao/getabcranking').PIE().render();