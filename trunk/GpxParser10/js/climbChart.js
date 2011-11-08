/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


Ext.define('Ext.chart.series.Climb', {
   extend:  'Ext.chart.series.Area',
   requires: ['Ext.chart.axis.Axis', 'Ext.draw.Color', 'Ext.fx.Anim'],
   type: 'climb',
   alias: 'series.climb',
   
   getPaths: function() {
        var me = this,
            chart = me.chart,
            store = chart.substore || chart.store,
            first = true,
            bounds = me.getBounds(),
            bbox = bounds.bbox,
            items = me.items = [],
            componentPaths = [],
            componentPath,
            paths = [],
            i, ln, x, y, xValue, yValue, xPrev, yPrev, acumY, areaIndex, prevAreaIndex, areaElem, path;

        ln = bounds.xValues.length;
        xPrev = 0;
        yPrev = bbox.y + bbox.height;
        
        for (i = 0; i < ln; i++) {
            xValue = bounds.xValues[i];
            yValue = bounds.yValues[i];
            x = bbox.x + (xValue - bounds.minX) * bounds.xScale;
            acumY = 0;
            for (areaIndex = 0; areaIndex < bounds.areasLen; areaIndex++) {
                
                if (me.__excludes[areaIndex]) {
                    continue;
                }
                if (!componentPaths[areaIndex]) {
                    componentPaths[areaIndex] = [];
                }
                areaElem = yValue[areaIndex];      
                acumY += areaElem;
                
                if (areaElem == 0) 
                    y = bbox.y + bbox.height;
                else
                    y = bbox.y + bbox.height - (acumY - bounds.minY) * bounds.yScale;
                
                if (!paths[areaIndex]) {
                    paths[areaIndex] = ['M', bbox.x,  bbox.y + bbox.height];
                    paths[areaIndex].push('L', x, y);
                    componentPaths[areaIndex].push(['L', x, y]);
                } else {
                        
                    if (i != 0 && bounds.yValues[i-1][areaIndex] == 0 && areaElem != 0 ) {
                        paths[areaIndex].push('L', xPrev, yPrev);
                        componentPaths[areaIndex].push(['L', xPrev, yPrev]);
                    }
                    paths[areaIndex].push('L', x, y);
                    componentPaths[areaIndex].push(['L', x, y]);
                    if (i < ln - 1 && bounds.yValues[i+1][areaIndex] == 0 && areaElem != 0) {
                        paths[areaIndex].push('L', x, bbox.y + bbox.height);
                        componentPaths[areaIndex].push(['L', x, bbox.y + bbox.height]);
                    }
                    if (i == (ln -1) && areaElem != 0) {
                        paths[areaIndex].push('L', x, bbox.y + bbox.height);
                        componentPaths[areaIndex].push(['L', x, bbox.y + bbox.height]);
                    }
                }
                if (areaElem != 0)
                    yPrev = y;
                if (!items[areaIndex]) {
                    items[areaIndex] = {
                        pointsUp: [],
                        pointsDown: [],
                        series: me
                    };
                }
                items[areaIndex].pointsUp.push([x, y]);
            }
            xPrev = x;
            
        }
        
        
        /*for (areaIndex = 0; areaIndex < bounds.areasLen; areaIndex++) {
            
            if (me.__excludes[areaIndex]) {
                continue;
            }
            path = paths[areaIndex];
            
            if (areaIndex == 0 || first) {
                first = false;
                path.push('L', x, bbox.y + bbox.height,
                          'L', bbox.x, bbox.y + bbox.height,
                          'Z');
            }
            
            else {
                componentPath = componentPaths[prevAreaIndex];
                componentPath.reverse();
                path.push('L', x, componentPath[0][2]);
                for (i = 0; i < ln; i++) {
                    path.push(componentPath[i][0],
                              componentPath[i][1],
                              componentPath[i][2]);
                    items[areaIndex].pointsDown[ln -i -1] = [componentPath[i][1], componentPath[i][2]];
                }
                path.push('L', bbox.x, path[2], 'Z');
            }
            prevAreaIndex = areaIndex;
        } */
        return {
            paths: paths,
            areasLen: bounds.areasLen
        };
    },
    
    drawSeries: function() {
        var me = this,
            chart = me.chart,
            store = chart.substore || chart.store,
            surface = chart.surface,
            animate = chart.animate,
            group = me.group,
            endLineStyle = Ext.apply(me.seriesStyle, me.style),
            colorArrayStyle = me.colorArrayStyle,
            colorArrayLength = colorArrayStyle && colorArrayStyle.length || 0,
            areaIndex, areaElem, paths, path, rendererAttributes;

        me.unHighlightItem();
        me.cleanHighlights();

        if (!store || !store.getCount()) {
            return;
        }
        
        paths = me.getPaths();

        if (!me.areas) {
            me.areas = [];
        }

        for (areaIndex = 0; areaIndex < paths.areasLen; areaIndex++) {
            
            if (me.__excludes[areaIndex]) {
                continue;
            }
            if (!me.areas[areaIndex]) {
                me.items[areaIndex].sprite = me.areas[areaIndex] = surface.add(Ext.apply({}, {
                    type: 'path',
                    group: group,
                    
                    path: paths.paths[areaIndex],
                    stroke: endLineStyle.stroke || colorArrayStyle[areaIndex % colorArrayLength],
                    fill: colorArrayStyle[areaIndex % colorArrayLength]
                }, endLineStyle || {}));
            }
            areaElem = me.areas[areaIndex];
            path = paths.paths[areaIndex];
            if (animate) {
                
                rendererAttributes = me.renderer(areaElem, false, { 
                    path: path,
                    
                    fill: colorArrayStyle[areaIndex % colorArrayLength],
                    stroke: endLineStyle.stroke || colorArrayStyle[areaIndex % colorArrayLength]
                }, areaIndex, store);
                
                me.animation = me.onAnimate(areaElem, {
                    to: rendererAttributes
                });
            } else {
                rendererAttributes = me.renderer(areaElem, false, { 
                    path: path,
                    
                    hidden: false,
                    fill: colorArrayStyle[areaIndex % colorArrayLength],
                    stroke: endLineStyle.stroke || colorArrayStyle[areaIndex % colorArrayLength]
                }, areaIndex, store);
                me.areas[areaIndex].setAttributes(rendererAttributes, true);
            }
        }
        me.renderLabels();
        me.renderCallouts();
    }
   
});