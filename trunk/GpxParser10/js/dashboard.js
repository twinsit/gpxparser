/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

Ext.require([
    'Ext.form.field.File',
    'Ext.form.Panel',
    'Ext.window.MessageBox',
    'Ext.data.Store',
    'Ext.chart.*',
    'Ext.Window'
]);

function initialize(filename) {
    var map = new google.maps.Map(document.getElementById("googleMapsPanel"), {
      mapTypeId: google.maps.MapTypeId.TERRAIN
    });
    var prova = filename;
   
    $.ajax({
     type: "GET",
     //url: "PratoValentino.gpx",
     url: prova,
     dataType: "xml",
     success: function(xml) {
       var points = [];
       var bounds = new google.maps.LatLngBounds ();
       $(xml).find("trkpt").each(function() {
         var lat = $(this).attr("lat");
         var lon = $(this).attr("lon");
         var p = new google.maps.LatLng(lat, lon);
         points.push(p);
         bounds.extend(p);
       });
 
       var poly = new google.maps.Polyline({
         // use your own style here
         path: points,
         strokeColor: "#FF00AA",
         strokeOpacity: .7,
         strokeWeight: 4
       });
       
       poly.setMap(map);
       
       // fit bounds to track
       map.fitBounds(bounds);
     }
    });
  }

Ext.onReady(function(){
   
    var bodyElement = Ext.getBody();
   
    /* Doesn't work with Internet Explorer */
    var winH = 700;
    var winW = 1024;
    if (!Ext.isIE) {
        winW = window.innerWidth - 10;
        winH = window.innerHeight - 10;
    }
    
    Ext.define('Points', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'ele', type: 'float'},
            {name: 'interval',  type: 'float'},
            {name: 'ts',       type: 'float'},
            {name: 'dist',   type: 'float'},
            {name: 'track1', type: 'float'},
            {name: 'track2', type: 'float'},
            {name: 'track3', type: 'float'},
            {name: 'track4', type: 'float'},
            {name: 'track5', type: 'float'},
            {name: 'track6', type: 'float'},
            {name: 'track7', type: 'float'},
            {name: 'track8', type: 'float'},
            {name: 'track9', type: 'float'},
            {name: 'track10', type: 'float'},
            {name: 'gradient', type: 'float'}
        ]
    });
    
    var gpxStore = new Ext.data.Store({
       autoLoad: false,
       proxy: {
        type: 'ajax',
        //url: "./getGpxData.php",
        reader: {
            type: 'json',
            root: 'root'
        }
       },
       model: 'Points'
    }); 
    
    var gpxSavedStore = new Ext.data.Store({
        fields: ['fileName', 'title'],
        autoload: true,
        proxy: {
        type: 'ajax',
            url: "./getStoredGpxTracks.php",
            reader: {
                type: 'json',
                root: 'root'
            }
        }
        //data: [{fileName:'prova', title:'prova1'}, {fileName: 'prova2', title: 'prova3'}]
    })
    
    var speedStore = new Ext.data.Store({
        fields: ['data'],
        data: {data: 4}
    });
    
    var slopeStore = new Ext.data.Store({
        fields: ['data'],
        data: {data: 6}
    });
    
    var hSpeedStore = new Ext.data.Store({
        fields: ['data'],
        data: {data: 90}
    });
    
    var fields = ['track1', 
                  'track2', 
                  'track3', 
                  'track4', 
                  'track5', 
                  'track6', 
                  'track7', 
                  'track8', 
                  'track9', 
                  'track10'
                 ];
    
    var colors = [
                  'rgb(0, 255, 0)',  // track1
                  'rgb(85, 255, 0)',
                  'rgb(163, 255, 0)',
                  'rgb(192, 255, 0)',
                  'rgb(241, 255, 0)',
                  'rgb(254, 243, 0)',
                  'rgb(255, 192, 0)',
                  'rgb(255, 149, 0)',
                  'rgb(255, 83,0)',
                  'rgb(255, 24, 0)' // track10
                 ];
                  
    Ext.chart.theme.Climb = Ext.extend(Ext.chart.theme.Base, {
        constructor: function(config) {
            Ext.chart.theme.Base.prototype.constructor.call(this, Ext.apply({
                colors: colors
            }, config));
        }
    }); 
    
    var elevationChart = new Ext.chart.Chart({
            id: 'chartCmp',
            width: winW - 410,
            height: 2*(winH - 30)/ 3 ,
            colspan: 3,
            frame: true,
            xtype: 'chart',
            style: 'background:#fff',
            animate: true,
            store: gpxStore,
            theme: 'Climb:gradients',
            axes: [{
                type: 'Numeric',
                grid: true,
                position: 'left',
                fields: 'ele',
                title: 'Elevation',
                adjustMinimumByMajorUnit: 0
            }, {
                type: 'Category',
                position: 'bottom',
                fields: 'dist',
                title: 'Meter',
                adjustMinimumByMajorUnit: false,
                adjustMaximumByMajorUnit: false,
                //maximum: 8600,
                //grid: true,
                majorTickSteps: 10,
                //minorTickSteps: 5,
                label: {
                    rotate: {
                        degrees: 315
                    }
                }
            }],
            series: [{
                type: 'climb',
                highlight: {
                    size: 7,
                    radius: 7
                },
                axis: 'left',
                xField: 'dist',
                yField:  fields,
                style: {
                    stroke: '#666',
                    opacity: 0.86
                },
                tips: {
                    trackMouse: true,
                    width: 200,
                    renderer: function(storeItem, item) {
                        
                        var dist = storeItem.get('dist');
                        var prevDist = gpxStore.getAt(storeItem.index - 1).get('dist');
                        var speed = (dist - prevDist)/ (storeItem.get('interval')) * 3.6;
                        var ele = storeItem.get('ele');
                        var prevEle = gpxStore.getAt(storeItem.index - 1).get('ele');
                        var verticalSpeed = (ele - prevEle)/ (storeItem.get('interval')) * 3600;
                        
                        var slope = storeItem.get('gradient') > 0 ? storeItem.get('gradient') < .45 ? Ext.util.Format.round(storeItem.get('gradient') * 100, 0) : 45 : 0;
                        
                        speedStore.loadData([{'data': speed < 50 ? speed : 50}]);
                        slopeStore.loadData([{'data': slope}]);
                        hSpeedStore.loadData([{'data': verticalSpeed > 0 ? verticalSpeed : 0 }]);
                        //var prova = Ext.getCmp('speedGauge'); //.setTitle("Speed: " + speed);
                        Ext.getCmp('speedGauge').axes.items[0].setTitle("Speed: " + Ext.util.Format.round(speed, 2) + " Km/h");
                        Ext.getCmp('slopeGauge').axes.items[0].setTitle("Slope: " + Ext.util.Format.round(storeItem.get('gradient') * 100, 0) + " %");
                        Ext.getCmp('vSpeedGauge').axes.items[0].setTitle("Vertical Speed: " + Ext.util.Format.round(verticalSpeed, 0) + " m/h");
                        
                        this.setTitle("Distance: " + storeItem.get('dist') +
                            "<br />Height: " + storeItem.get('ele') +
                            "<br />Slope: " + (Ext.util.Format.round(storeItem.get('gradient') * 100, 0)) + " % <br />" +
                            "Speed: " + Ext.util.Format.round(speed, 2) + "Km/h" +
                            "<br /> Vertical Speed: " + Ext.util.Format.round(verticalSpeed, 2) + "m/h");
                        
                        // Refresh gauge
                    }  
                }
            }]
         });
    
    var panel = Ext.create('Ext.Panel', {
       
        id: 'main-panel',
        baseCls:'x-plain',
        //renderTo: Ext.getBody(),
        //renderTo: 'extjs_div',  
        layout: {
            type: 'table',
            columns: 3
        },
        defaults: {frame:true, width: (winW - 410) /3, height: (winH - 80)/ 3},
        items:[
            elevationChart,{
            //title:'Item 2',
            xtype: 'chart',
            id: 'speedGauge',
            insetPadding: 30,
            animate: true,
            style: 'background:#fff',
            animate: {
                easing: 'elasticIn',
                duration: 1000
            },
            store: speedStore,
            flex: 1,
            axes: [{
                type: 'gauge',
                position: 'gauge',
                title: "Speed",
                minimum: 0,
                maximum: 50,
                steps: 10,
                margin: 7
            }],
            series: [{
                type: 'gauge',
                field: 'data',
                donut: false,
                colorSet: ['rgb(0, 255, 0)', '#ddd']
            }]
            
        },{
            xtype: 'chart',
            id: 'slopeGauge',
            insetPadding: 30,
            animate: true,
            style: 'background:#fff',
            animate: {
                easing: 'elasticIn',
                duration: 1000
            },
            store: slopeStore,
            flex: 1,
            axes: [{
                type: 'gauge',
                position: 'gauge',
                title: "Slope",
                minimum: 0,
                maximum: 45,
                steps: 10,
                margin: 7
            }],
            series: [{
                type: 'gauge',
                field: 'data',
                donut: false,
                
                showInLegend: true,
                colorSet: ['rgb(255, 24, 0)', '#ddd']
            }]
        },{
            xtype: 'chart',
            id: 'vSpeedGauge',
            insetPadding: 30,
            animate: true,
            style: 'background:#fff',
            animate: {
                easing: 'elasticIn',
                duration: 1000
            },
            store: hSpeedStore,
            flex: 1,
            axes: [{
                type: 'gauge',
                position: 'gauge',
                title: "Vertical Speed",
                minimum: 0,
                maximum: 1000,
                steps: 10,
                margin: 7
            }],
            series: [{
                type: 'gauge',
                field: 'data',
                donut: false,
                colorSet: ['#3AA8CB', '#ddd']
            }]
        }]
    });
    
    var showPanel = new Ext.form.Panel({
        bodyPadding: 5,
        //height: 100,
        width: 340,
        border: 0,
        margin: 5,
        defaults: {
            anchor: '100%',
            allowBlank: true,
            msgTarget: 'side',
            labelWidth: 100
        },
        items: [
            {
                xtype: 'combo',
                fieldLabel: 'Stored tracks',
                store: gpxSavedStore,
                displayField: 'title',
                valueField: 'fileName',
                name: 'gpxName'
            }
        ],
        buttons: [{
            text: 'Load',
            handler: function(){
                var form = this.up('form').getForm();
                var values = form.getValues();
                //alert(form.getValues('gpxName'));
                initialize("tracks/" + values.gpxName);
                gpxStore.load({url: "getGpxData.php?filename=" + values.gpxName});
            }
        }]
    });
    
    var uploadPanel = new Ext.form.Panel({
        //title: 'File Upload Form',
        bodyPadding: 5,
        width: 340,
        //height: (winH - 320)/ 2,
        //frame: true,
        border: 0,
        margin: 5,
        defaults: {
            anchor: '100%',
            allowBlank: false,
            msgTarget: 'side',
            labelWidth: 50
        },
        items: [/*{
            xtype: 'textfield',
            fieldLabel: 'Name'
        },*/{
            xtype: 'filefield',
            id: 'form-file',
            emptyText: 'Select a gpx file',
            fieldLabel: 'File',
            name: 'fileGpx'/*,
            buttonText: '',
            buttonConfig: {
                iconCls: 'upload-icon'
            }*/
        }],
    buttons: [{
            text: 'Upload',
            handler: function(){
                var form = this.up('form').getForm();
                if(form.isValid()){
                    form.submit({
                        url: 'fileUpload.php',
                        waitMsg: 'Uploading your file...',
                        success: function(fp, o) {
                            initialize("Tracks/" + o.result.file);
                            gpxStore.load({url: "getGpxData.php?filename=" + o.result.file});
                            gpxSavedStore.load();
                        }
                    });
                }
            }
        }/*,{
            text: 'Reset',
            handler: function() {
                this.up('form').getForm().reset();
            }
        }*/]
    });
    
    var mapsPanel = new Ext.form.Panel ({
       frame: true,
       width: 340,
       height: (winH - 90)/ 2,
       margin: 5,
       id: 'googleMapsPanel'
    });
   
    var dashBoard = Ext.create('Ext.form.Panel', {
        title: 'Track elevation',
        renderTo: Ext.get('extjs_div'),
        width: winW,
        height: winH,
        frame: true,
        bodyPadding: 5,
        margin: 5,
        fieldDefaults: {
            labelAlign: 'left',
            msgTarget: 'side'
        },
    
        layout: {
            type: 'hbox',
            align: 'stretch'
        },
        items: [{
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            width: (winW - 390),
            margin: '0 5 0 0',
            items: [
                panel
            ]
        },
        {
            layout: 'vbox',
            width: 360,//(winW - 40)* 0.3,
            margin: '0 0 0 0',
            items: [
                showPanel,
                uploadPanel,
                mapsPanel
            ]
        }]
   });
   
   
});
