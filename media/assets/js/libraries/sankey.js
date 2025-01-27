/*!
 * SankeyChart - Lightweight javascript library to create sankey charts
 * https://github.com/roumilb/sankey-chart
 * Author: https://github.com/roumilb
 * Date: 2025-01-17
 * License: MIT
 */
var SankeyChart = function () {
    'use strict';
    !function (t, e) {
        void 0 === e && (e = {});
        var n = e.insertAt;
        if (t && 'undefined' != typeof document) {
            var i = document.head || document.getElementsByTagName('head')[0], l = document.createElement('style');
            l.type = 'text/css', 'top' === n && i.firstChild ? i.insertBefore(l, i.firstChild) : i.appendChild(l), l.styleSheet
                                                                                                                   ? l.styleSheet.cssText = t
                                                                                                                   : l.appendChild(document.createTextNode(t));
        }
    }('.sankey_chart_container{\n\tdisplay: flex;\n\tflex-direction: row;\n\tgap: 1rem;\n\n\t#sankey_chart_svg_container{\n\t\tposition: absolute;\n\t\tz-index: 1;\n\n\t\t.sankey_chart_link{\n\t\t\ttransition: .2s;\n\t\t\tcursor: pointer;\n\t\t}\n\t}\n\n\t.sankey_chart_node_container{\n\t\tdisplay: flex;\n\t\tflex-direction: column;\n\t\tgap: .5rem;\n\t\twidth: 20px;\n\n\t\t.sankey_chart_node{\n\t\t\twidth: 100%;\n\t\t\tz-index: 2;\n\t\t\tcursor: pointer;\n\t\t\tposition: relative;\n\n\t\t\t.sankey_chart_node_label{\n\t\t\t\tposition: absolute;\n\t\t\t\tleft: 100%;\n\t\t\t\tpadding-left: 5px;\n\t\t\t\ttop: 5px;\n\t\t\t\tuser-select: none;\n\t\t\t\ttransition: .2s;\n\n\t\t\t\t&:hover{\n\t\t\t\t\tfont-weight: bold;\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t}\n\n\t.sankey_chart_grow{\n\t\tflex-grow: 1;\n\t}\n}\n\n');
    const t = {
        chartElement: null,
        chartDimensions: {
            width: 0,
            height: 0
        },
        dataAsTree: null,
        maxHeightNodeValue: 0,
        htmlElement: [],
        linksPath: [],
        nodeValues: {},
        parentChildren: {},
        rawData: null,
        nodeClickCallback: null,
        linkClickCallback: null,
        nodeElements: null,
        linkElements: null,
        isInteractive: !1,
        interactiveType: 'click',
        interactiveOn: 'node',
        nodeDisplayStates: {},
        iterationHidden: null,
        defaultValues: {
            link: {
                fillColor: 'rgba(229, 246, 254, 0.5)',
                hoverFillColor: 'rgba(0, 164, 255, 0.5)'
            },
            node: {backgroundColor: '#00a4ff'}
        },
        labels: {},
        display: function (t) {
            if (this.setData(t), this.configureChartElement(), this.fillNodeValues(), this.sortDataAsTree(), !this.dataAsTree) {
                throw new Error('Invalid data, data as tree is required');
            }
            this.fillNodeHtmlElement(this.dataAsTree,
                0,
                !0
            ), this.fillLinksPath(), this.addHtml(), this.drawPath(), this.setNodeElements(), this.setLinkElements(), this.setNodeClick(), this.setLinkClick(), this.setNodeHover(), this.setLinkHover();
        },
        setData: function (t) {
            var e, n, i, l;
            if (!t.id) throw new Error('Invalid data, id is required');
            if (this.chartElement = document.getElementById(t.id), !this.chartElement) throw new Error(`Element with id ${t.id} not found`);
            if (!t.data || 0 === t.data.length) throw new Error('Invalid data, data is required and should have at least one element');
            if (this.rawData = t.data, this.chartDimensions = {
                width: this.chartElement.clientWidth,
                height: this.chartElement.clientHeight
            }, !this.chartDimensions.width || !this.chartDimensions.height) {
                throw new Error('Invalid dimensions, please set the width and height of the element');
            }
            this.maxHeightNodeValue = Math.max(...t.data.map((t => t.value))), t.nodeClickCallback
                                                                               && 'function'
                                                                               == typeof t.nodeClickCallback
                                                                               && (this.nodeClickCallback = t.nodeClickCallback), t.linkClickCallback
                                                                                                                                  && 'function'
                                                                                                                                  == typeof t.linkClickCallback
                                                                                                                                  && (this.linkClickCallback = t.linkClickCallback), t.labels
                                                                                                                                                                                     && (this.labels = t.labels), t.link
                                                                                                                                                                                                                  && (t.link.backgroundColor
                                                                                                                                                                                                                  && (this.defaultValues.link.fillColor = t.link.backgroundColor), t.link.hoverBackgroundColor
                                                                                                                                                                                                                  && (this.defaultValues.link.hoverFillColor = t.link.hoverBackgroundColor)), t.node
                                                                                                                                                                                                                                                                                              && t.node.backgroundColor
                                                                                                                                                                                                                                                                                              && (this.defaultValues.node.backgroundColor = t.node.backgroundColor), this.isInteractive = null
                                                                                                                                                                                                                                                                                                                                                                                          !== (e = t.interactive)
                                                                                                                                                                                                                                                                                                                                                                                          && void 0
                                                                                                                                                                                                                                                                                                                                                                                          !== e
                                                                                                                                                                                                                                                                                                                                                                                          && e, this.interactiveType = null
                                                                                                                                                                                                                                                                                                                                                                                                                       !== (n = t.interactiveType)
                                                                                                                                                                                                                                                                                                                                                                                                                       && void 0
                                                                                                                                                                                                                                                                                                                                                                                                                       !== n
                                                                                                                                                                                                                                                                                                                                                                                                                       ? n
                                                                                                                                                                                                                                                                                                                                                                                                                       : 'click', this.interactiveOn = null
                                                                                                                                                                                                                                                                                                                                                                                                                                                       !== (i = t.interactiveOn)
                                                                                                                                                                                                                                                                                                                                                                                                                                                       && void 0
                                                                                                                                                                                                                                                                                                                                                                                                                                                       !== i
                                                                                                                                                                                                                                                                                                                                                                                                                                                       ? i
                                                                                                                                                                                                                                                                                                                                                                                                                                                       : 'node', this.iterationHidden = null
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        !== (l = t.iterationHidden)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        && void 0
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        !== l
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        ? l
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        : null;
        },
        configureChartElement: function () {
            this.chartElement && this.chartElement.classList.add('sankey_chart_container');
        },
        sortDataAsTree: function () {
            if (!this.rawData) throw new Error('Invalid data, data is required');
            const t = {};
            this.rawData.forEach((e => {
                t[e.source] || (t[e.source] = {
                    id: e.source,
                    children: []
                }), t[e.target] || (t[e.target] = {
                    id: e.target,
                    children: []
                });
            })), this.rawData.forEach((e => {
                t[e.source].children.push(t[e.target]);
            }));
            const e = new Set(this.rawData.map((t => t.target))), n = this.rawData.find((t => !e.has(t.source)));
            if (!n) throw new Error('Invalid data, no root node found');
            const i = n.source;
            this.dataAsTree = [t[i]];
        },
        fillNodeValues: function () {
            if (!this.rawData) throw new Error('Invalid data, data is required');
            this.rawData.forEach((t => {
                this.parentChildren[t.source] || (this.parentChildren[t.source] = []), this.nodeValues[t.target]
                                                                                       || (this.nodeValues[t.target] = 0), this.parentChildren[t.source].push(t.target), this.nodeValues[t.target] += t.value;
            }));
        },
        getHeightForNode: function (t) {
            return t / this.maxHeightNodeValue * this.chartDimensions.height;
        },
        fillNodeHtmlElement: function (t, e, n = !1) {
            this.htmlElement[e] || (this.htmlElement[e] = []), t.forEach((t => {
                let i = 0;
                if (i = n ? this.getHeightForNode(this.maxHeightNodeValue) : this.nodeValues[t.id] ? this.getHeightForNode(this.nodeValues[t.id]) : 0, !i) {
                    return;
                }
                const l = this.labels[t.id] ? this.labels[t.id] : t.id,
                    a = this.iterationHidden && e >= this.iterationHidden,
                    r = this.iterationHidden && e === this.iterationHidden - 1;
                this.nodeDisplayStates[t.id] = !a && !r;
                const o = a ? 'none' : 'flex', s = `height: ${i}px; background: ${this.defaultValues.node.backgroundColor}; display: ${o}`;
                return this.htmlElement[e].push(`<div id="node_${t.id}" class="sankey_chart_node" style="${s}">\n                                            <div class="sankey_chart_node_label">${l}</div>\n                                          </div>`), t.children.length
                                                                                                                                                                                                                                                                  ? this.fillNodeHtmlElement(t.children,
                        e + 1
                    )
                                                                                                                                                                                                                                                                  : void 0;
            }));
        },
        fillLinksPath: function () {
            for (const [t, e] of Object.entries(this.parentChildren)) this.linksPath.push(...e.map((e => `<path fill="${this.defaultValues.link.fillColor}" class="sankey_chart_link" id="${t}-${e}"></path>`)));
        },
        addHtml: function () {
            if (!this.chartElement) throw new Error('Invalid chart element');
            this.chartElement.innerHTML = this.htmlElement.map(((t, e) => 0 === e
                                                                          ? `<div class="sankey_chart_node_container">${t.join('')}</div>`
                                                                          : `<div class="sankey_chart_grow"></div><div class="sankey_chart_node_container">${t.join(
                    '')}</div>`))
                                              .join(''), this.chartElement.innerHTML += `<svg id="sankey_chart_svg_container" width="${this.chartDimensions.width}" height="${this.chartDimensions.height}" viewBox="0 0 ${this.chartDimensions.width} ${this.chartDimensions.height}"></svg>`;
            const t = document.getElementById('sankey_chart_svg_container');
            if (!t) throw new Error('Invalid svg container');
            t.innerHTML = this.linksPath.join('');
        },
        drawPath: function () {
            for (const [t, e] of Object.entries(this.parentChildren)) {
                let n = 0;
                e.forEach((e => {
                    const i = this.getLink(t, e),
                        l = i.value / (this.nodeValues[t] ? this.nodeValues[t] : i.value),
                        a = document.getElementById(`${t}-${e}`),
                        r = document.getElementById(`node_${t}`),
                        o = document.getElementById(`node_${e}`);
                    if (!o) return void console.log(`Target node ${e} not found`);
                    if (!r || !a) throw new Error('Invalid sourceNode or targetNode element');
                    if (o && 'none' === o.style.display) return void a.setAttribute('d', '');
                    const s = r.getBoundingClientRect(), d = o.getBoundingClientRect(), h = s.height * l + n;
                    if (!this.chartElement) throw new Error('Invalid chart element');
                    const c = this.chartElement.getBoundingClientRect(), u = [
                        {
                            x: s.x - c.x + s.width,
                            y: s.y - c.y + n
                        },
                        {
                            x: d.x - c.x,
                            y: d.y - c.y
                        },
                        {
                            x: d.x - c.x,
                            y: d.y - c.y + d.height
                        },
                        {
                            x: s.x - c.x + s.width,
                            y: s.y - c.y + h
                        }
                    ];
                    n = u[3].y - (s.y - c.y);
                    const f = u[0].x + (u[1].x - u[0].x) / 2,
                        k = `M ${u[0].x} ${u[0].y}\n                                 C ${f} ${u[0].y}, ${f} ${u[1].y}, ${u[1].x} ${u[1].y}\n                                 L ${u[2].x} ${u[2].y}\n                                 C ${f} ${u[2].y}, ${f} ${u[3].y}, ${u[3].x} ${u[3].y}`;
                    a.setAttribute('d', k);
                }));
            }
        },
        getLink: function (t, e) {
            if (!this.rawData) throw new Error('Invalid data, data is required');
            return this.rawData.find((n => n.source === t && n.target === e));
        },
        setNodeElements: function () {
            this.nodeElements = document.querySelectorAll('.sankey_chart_node');
        },
        setLinkElements: function () {
            this.linkElements = document.querySelectorAll('.sankey_chart_link');
        },
        setNodeClick: function () {
            if (!this.nodeElements) throw new Error('Invalid node elements');
            this.nodeElements.forEach((t => {
                t.addEventListener(this.interactiveType, (() => {
                    const e = t.id.replace('node_', ''), n = this.getAllNodeChildrenFromNode(e);
                    if (this.nodeClickCallback) {
                        let t = null === n;
                        if (!t) {
                            let e = 0;
                            n.forEach((t => {
                                document.getElementById(`node_${t}`) && e++;
                            })), t = 0 === e;
                        }
                        this.nodeClickCallback(e, t);
                    }
                    this.setInteractiveClick('node', e);
                }));
            }));
        },
        setLinkClick: function () {
            if (!this.linkElements) throw new Error('Invalid link elements');
            this.linkElements.forEach((t => {
                t.addEventListener('click', (() => {
                    const [e, n] = t.id.split('-');
                    this.linkClickCallback && this.linkClickCallback(this.getLink(e, n)), this.setInteractiveClick('link', n);
                }));
            }));
        },
        setInteractiveClick: function (t, e) {
            if (!this.isInteractive || this.interactiveOn !== t) return;
            const n = this.getAllNodeChildrenFromNode(e), i = void 0 === this.nodeDisplayStates[e] || this.nodeDisplayStates[e];
            if (this.nodeDisplayStates[e] = !i, !n) return;
            const l = i ? 'none' : 'block';
            n.forEach((t => {
                this.nodeDisplayStates[t] = !i;
                const e = document.getElementById(`node_${t}`);
                e && (e.style.display = l);
            })), this.drawPath();
        },
        setNodeHover: function () {
            if (!this.nodeElements || !this.linkElements) throw new Error('Invalid node or link elements');
            this.nodeElements.forEach((e => {
                e.addEventListener('mouseover', (function () {
                    const e = t.getHistoryForNode(this.id.replace('node_', ''));
                    for (let n = 0 ; n < e.length - 1 ; n++) {
                        const i = document.getElementById(`${e[n]}-${e[n + 1]}`);
                        i && (i.style.fill = t.defaultValues.link.hoverFillColor);
                    }
                    this.addEventListener('mouseout', (() => {
                        t.linkElements.forEach((e => {
                            e.style.fill = t.defaultValues.link.fillColor;
                        }));
                    }));
                }));
            }));
        },
        setLinkHover: function () {
            if (!this.linkElements) throw new Error('Invalid link elements');
            this.linkElements.forEach((e => {
                e.addEventListener('mouseover', (function () {
                    this.style.fill = t.defaultValues.link.hoverFillColor, this.addEventListener('mouseout', (() => {
                        t.linkElements.forEach((e => {
                            e.style.fill = t.defaultValues.link.fillColor;
                        }));
                    }));
                }));
            }));
        },
        getHistoryForNode: function (t) {
            if (!this.dataAsTree) throw new Error('Invalid data, data is required');
            const e = (n, i) => {
                if (i.push(n.id), n.id === t) return i;
                if (n.children.length) {
                    for (let t of n.children) {
                        const n = e(t, [...i]);
                        if (n) return n;
                    }
                }
                return null;
            };
            for (let t of this.dataAsTree) {
                const n = e(t, []);
                if (n) return n;
            }
            return null;
        },
        getAllNodeChildrenFromNode: function (t) {
            if (!this.dataAsTree) throw new Error('Invalid data, data is required');
            const e = (n, i, l) => {
                if (l && i.push(n.id), n.id === t && (l = !0), n.children.length) for (let t of n.children) e(t, i, l);
                return i;
            };
            for (let t of this.dataAsTree) {
                const n = e(t, [], !1);
                if (n.length) return n;
            }
            return null;
        }
    };
    return t;
}();
