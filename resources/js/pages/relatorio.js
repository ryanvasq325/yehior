import Charts from '../components/charts.js';

new Charts().setId('chart-pizza').getData('/admin/status/bytipo').PIE().render();
new Charts().setId('chart-barras').getData('/admin/status/bymes').BAR().render();