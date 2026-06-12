import Charts from '../components/charts.js';

Charts.setId('chartsale').getData('/admin/gestao/getsalesdata').BAR().render();
Charts.setId('chartabc').getData('/admin/gestao/getabcranking').PIE().render();