/*
   Licensed to the Apache Software Foundation (ASF) under one or more
   contributor license agreements.  See the NOTICE file distributed with
   this work for additional information regarding copyright ownership.
   The ASF licenses this file to You under the Apache License, Version 2.0
   (the "License"); you may not use this file except in compliance with
   the License.  You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
var showControllersOnly = false;
var seriesFilter = "";
var filtersOnlySampleSeries = true;

/*
 * Add header in statistics table to group metrics by category
 * format
 *
 */
function summaryTableHeader(header) {
    var newRow = header.insertRow(-1);
    newRow.className = "tablesorter-no-sort";
    var cell = document.createElement('th');
    cell.setAttribute("data-sorter", false);
    cell.colSpan = 1;
    cell.innerHTML = "Requests";
    newRow.appendChild(cell);

    cell = document.createElement('th');
    cell.setAttribute("data-sorter", false);
    cell.colSpan = 3;
    cell.innerHTML = "Executions";
    newRow.appendChild(cell);

    cell = document.createElement('th');
    cell.setAttribute("data-sorter", false);
    cell.colSpan = 7;
    cell.innerHTML = "Response Times (ms)";
    newRow.appendChild(cell);

    cell = document.createElement('th');
    cell.setAttribute("data-sorter", false);
    cell.colSpan = 1;
    cell.innerHTML = "Throughput";
    newRow.appendChild(cell);

    cell = document.createElement('th');
    cell.setAttribute("data-sorter", false);
    cell.colSpan = 2;
    cell.innerHTML = "Network (KB/sec)";
    newRow.appendChild(cell);
}

/*
 * Populates the table identified by id parameter with the specified data and
 * format
 *
 */
function createTable(table, info, formatter, defaultSorts, seriesIndex, headerCreator) {
    var tableRef = table[0];

    // Create header and populate it with data.titles array
    var header = tableRef.createTHead();

    // Call callback is available
    if(headerCreator) {
        headerCreator(header);
    }

    var newRow = header.insertRow(-1);
    for (var index = 0; index < info.titles.length; index++) {
        var cell = document.createElement('th');
        cell.innerHTML = info.titles[index];
        newRow.appendChild(cell);
    }

    var tBody;

    // Create overall body if defined
    if(info.overall){
        tBody = document.createElement('tbody');
        tBody.className = "tablesorter-no-sort";
        tableRef.appendChild(tBody);
        var newRow = tBody.insertRow(-1);
        var data = info.overall.data;
        for(var index=0;index < data.length; index++){
            var cell = newRow.insertCell(-1);
            cell.innerHTML = formatter ? formatter(index, data[index]): data[index];
        }
    }

    // Create regular body
    tBody = document.createElement('tbody');
    tableRef.appendChild(tBody);

    var regexp;
    if(seriesFilter) {
        regexp = new RegExp(seriesFilter, 'i');
    }
    // Populate body with data.items array
    for(var index=0; index < info.items.length; index++){
        var item = info.items[index];
        if((!regexp || filtersOnlySampleSeries && !info.supportsControllersDiscrimination || regexp.test(item.data[seriesIndex]))
                &&
                (!showControllersOnly || !info.supportsControllersDiscrimination || item.isController)){
            if(item.data.length > 0) {
                var newRow = tBody.insertRow(-1);
                for(var col=0; col < item.data.length; col++){
                    var cell = newRow.insertCell(-1);
                    cell.innerHTML = formatter ? formatter(col, item.data[col]) : item.data[col];
                }
            }
        }
    }

    // Add support of columns sort
    table.tablesorter({sortList : defaultSorts});
}

$(document).ready(function() {

    // Customize table sorter default options
    $.extend( $.tablesorter.defaults, {
        theme: 'blue',
        cssInfoBlock: "tablesorter-no-sort",
        widthFixed: true,
        widgets: ['zebra']
    });

    var data = {"OkPercent": 100.0, "KoPercent": 0.0};
    var dataset = [
        {
            "label" : "FAIL",
            "data" : data.KoPercent,
            "color" : "#FF6347"
        },
        {
            "label" : "PASS",
            "data" : data.OkPercent,
            "color" : "#9ACD32"
        }];
    $.plot($("#flot-requests-summary"), dataset, {
        series : {
            pie : {
                show : true,
                radius : 1,
                label : {
                    show : true,
                    radius : 3 / 4,
                    formatter : function(label, series) {
                        return '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">'
                            + label
                            + '<br/>'
                            + Math.round10(series.percent, -2)
                            + '%</div>';
                    },
                    background : {
                        opacity : 0.5,
                        color : '#000'
                    }
                }
            }
        },
        legend : {
            show : true
        }
    });

    // Creates APDEX table
    createTable($("#apdexTable"), {"supportsControllersDiscrimination": true, "overall": {"data": [0.8576305906452715, 500, 1500, "Total"], "isController": false}, "titles": ["Apdex", "T (Toleration threshold)", "F (Frustration threshold)", "Label"], "items": [{"data": [0.8598484848484849, 500, 1500, "07 Listado, otra página"], "isController": false}, {"data": [0.8795066413662239, 500, 1500, "08 Perdidas"], "isController": false}, {"data": [0.8735955056179775, 500, 1500, "06 Consulta con filtros: perros cachorros"], "isController": false}, {"data": [0.8605313092979127, 500, 1500, "09 Encontradas"], "isController": false}, {"data": [0.8896103896103896, 500, 1500, "01 Pantalla de ingreso"], "isController": false}, {"data": [0.74487895716946, 500, 1500, "02 Inicio de sesión"], "isController": false}, {"data": [0.7942271880819367, 500, 1500, "03 Mi cuenta"], "isController": false}, {"data": [0.8500931098696461, 500, 1500, "04 Inicio"], "isController": false}, {"data": [0.8787593984962406, 500, 1500, "06b Consulta con filtros: gatos adultos por raza"], "isController": false}, {"data": [0.9171428571428571, 500, 1500, "10 Cerrar sesión"], "isController": false}, {"data": [0.8878504672897196, 500, 1500, "05 Listado en adopción"], "isController": false}]}, function(index, item){
        switch(index){
            case 0:
                item = item.toFixed(3);
                break;
            case 1:
            case 2:
                item = formatDuration(item);
                break;
        }
        return item;
    }, [[0, 0]], 3);

    // Create statistics table
    createTable($("#statisticsTable"), {"supportsControllersDiscrimination": true, "overall": {"data": ["Total", 5858, 0, 0.0, 435.73130761351996, 119, 10732, 367.0, 700.0, 821.0, 1382.8199999999997, 9.619142388923372, 245.31463146086003, 7.5551211300932355], "isController": false}, "titles": ["Label", "#Samples", "FAIL", "Error %", "Average", "Min", "Max", "Median", "90th pct", "95th pct", "99th pct", "Transactions/s", "Received", "Sent"], "items": [{"data": ["07 Listado, otra página", 528, 0, 0.0, 443.0000000000003, 190, 4909, 354.5, 704.0000000000002, 837.55, 2067.8500000000304, 0.8922490929645821, 12.426734420071886, 0.7344034240692642], "isController": false}, {"data": ["08 Perdidas", 527, 0, 0.0, 414.15370018975307, 206, 3675, 350.0, 645.3999999999999, 809.9999999999998, 1308.4000000000046, 0.9025317127722794, 10.70846933211797, 0.7359511525047395], "isController": false}, {"data": ["06 Consulta con filtros: perros cachorros", 534, 0, 0.0, 449.192883895131, 190, 10732, 357.5, 701.0, 831.5, 1560.2999999999959, 0.8879606503729768, 12.33657422775276, 0.7561539913332379], "isController": false}, {"data": ["09 Encontradas", 527, 0, 0.0, 429.4117647058824, 187, 5092, 358.0, 685.4, 818.5999999999999, 1348.1600000000035, 0.8947049426081582, 10.643622863722177, 0.7321901776422234], "isController": false}, {"data": ["01 Pantalla de ingreso", 539, 0, 0.0, 400.2319109461966, 176, 5175, 339.0, 630.0, 721.0, 1437.2000000000023, 0.8938818019595746, 5.651939603793939, 0.09689539064210233], "isController": false}, {"data": ["02 Inicio de sesión", 537, 0, 0.0, 552.2849162011162, 307, 1469, 508.0, 824.0, 931.3, 1349.1800000000003, 0.9086263667466439, 1.3664888718650698, 0.9027637068064068], "isController": false}, {"data": ["03 Mi cuenta", 537, 0, 0.0, 500.74115456238343, 239, 3442, 436.0, 747.5999999999999, 901.1999999999999, 1505.0800000000002, 0.9064805765013901, 155.35071834523828, 0.7374007033453691], "isController": false}, {"data": ["04 Inicio", 537, 0, 0.0, 436.55679702048417, 202, 2849, 371.0, 667.0, 810.3, 1528.72, 0.9034229129584829, 17.114837517412344, 0.7296198720865873], "isController": false}, {"data": ["06b Consulta con filtros: gatos adultos por raza", 532, 0, 0.0, 423.9022556390979, 186, 5214, 340.0, 666.5999999999998, 800.3499999999999, 1734.4199999999826, 0.8961297973264338, 12.563165070014351, 0.7709866713326057], "isController": false}, {"data": ["10 Cerrar sesión", 525, 0, 0.0, 336.4704761904763, 119, 3360, 245.0, 591.6000000000004, 735.0999999999997, 1705.7800000000009, 0.8965699794215842, 1.3177127138959808, 0.8440365821898508], "isController": false}, {"data": ["05 Listado en adopción", 535, 0, 0.0, 404.4635514018693, 208, 2671, 354.0, 649.4000000000003, 756.8, 1010.5999999999995, 0.9039376198562148, 12.590487812154159, 0.737097570878847], "isController": false}]}, function(index, item){
        switch(index){
            // Errors pct
            case 3:
                item = item.toFixed(2) + '%';
                break;
            // Mean
            case 4:
            // Mean
            case 7:
            // Median
            case 8:
            // Percentile 1
            case 9:
            // Percentile 2
            case 10:
            // Percentile 3
            case 11:
            // Throughput
            case 12:
            // Kbytes/s
            case 13:
            // Sent Kbytes/s
                item = item.toFixed(2);
                break;
        }
        return item;
    }, [[0, 0]], 0, summaryTableHeader);

    // Create error table
    createTable($("#errorsTable"), {"supportsControllersDiscrimination": false, "titles": ["Type of error", "Number of errors", "% in errors", "% in all samples"], "items": []}, function(index, item){
        switch(index){
            case 2:
            case 3:
                item = item.toFixed(2) + '%';
                break;
        }
        return item;
    }, [[1, 1]]);

        // Create top5 errors by sampler
    createTable($("#top5ErrorsBySamplerTable"), {"supportsControllersDiscrimination": false, "overall": {"data": ["Total", 5858, 0, "", "", "", "", "", "", "", "", "", ""], "isController": false}, "titles": ["Sample", "#Samples", "#Errors", "Error", "#Errors", "Error", "#Errors", "Error", "#Errors", "Error", "#Errors", "Error", "#Errors"], "items": [{"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}, {"data": [], "isController": false}]}, function(index, item){
        return item;
    }, [[0, 0]], 0);

});
