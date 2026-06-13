import Requests from './requests.js';

export default class Charts {

    #id = null;
    #url = null;
    #type = null;

    #request = new Requests();

    setId(id) {
        this.#id = id;
        this.#url = null;
        this.#type = null;
        return this;
    }

    getData(url) {
        this.#url = url;
        return this;
    }

    BAR() {
        this.#type = 'bar';
        return this;
    }

    PIE() {
        this.#type = 'pie';
        return this;
    }

    async render() {
        const el = document.getElementById(this.#id);
        if (!el) {
            console.error(`Charts: elemento #${this.#id} não encontrado.`);
            return;
        }

        const chart = echarts.init(el);
        chart.showLoading();

        try {
            const json = await this.#request.get(this.#url);
            const option = this.#type === 'bar' ? this.#buildBAR(json) : this.#buildPIE(json);
            chart.hideLoading();
            chart.setOption(option);
            window.addEventListener('resize', () => chart.resize());
        } catch (err) {
            chart.hideLoading();
            console.error(`Charts: erro ao buscar "${this.#url}" →`, err.message);
        }
    }

    #buildBAR(json) {
        return {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
            },
            legend: {},
            xAxis: [{ type: 'category', data: json.labels }],
            yAxis: [{ type: 'value' }],
            series: json.series.map(s => ({
                name: s.name,
                type: 'bar',
                stack: s.stack ?? null,
                emphasis: { focus: 'series' },
                data: s.values,
            })),
        };
    }

    #buildPIE(json) {
        return {
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend: { orient: 'vertical', left: 'left' },
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                padAngle: 5,
                itemStyle: { borderRadius: 8 },
                data: json.labels.map((label, i) => ({
                    name: label,
                    value: json.values[i],
                })),
                emphasis: {
                    label: { show: true, fontSize: 16, fontWeight: 'bold' },
                },
            }],
        };
    }
}