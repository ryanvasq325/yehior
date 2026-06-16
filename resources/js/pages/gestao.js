import Charts from '../components/charts.js';

new Charts().setId('chartsale').getData('/admin/status/getsalesdata').BAR().render();
new Charts().setId('chartabc').getData('/admin/status/getabcranking').PIE().render();