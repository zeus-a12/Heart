<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراقبة معدل ضربات القلب ونسبة الأكسجين</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .chart-container {
            width: 90%;
            max-width: 600px;
            margin: 0 auto;
            overflow-x: auto;
        }
        .data-list-container {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 300px;
            overflow-y: auto;
        }
        .data-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .data-list li {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .data-list li:last-child {
            border-bottom: none;
        }
        .data-list li .status {
            font-weight: bold;
        }
        .data-list li .status.warning {
            color: red;
        }
        .data-list li .status.safe {
            color: green;
        }
        .filter-section {
            margin: 20px;
        }
        .export-button {
            margin: 10px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .export-button:hover {
            background-color: #45a049;
        }
        .mobile-notification {
            display: none;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 20px;
            }
            .chart-container {
                width: 100%;
            }
            .data-list-container {
                width: 100%;
            }
            .mobile-notification {
                display: block;
            }
        }
    </style>
</head>
<body>

    <h1>مراقبة معدل ضربات القلب ونسبة الأكسجين</h1>
    
    <div class="filter-section">
        <label for="filter">تصفية حسب الحالة:</label>
        <select id="filter">
            <option value="all">الكل</option>
            <option value="safe">طبيعي</option>
            <option value="warning">تحذير</option>
        </select>
        <button class="export-button" onclick="exportData()">تصدير البيانات</button>
    </div>

    <div class="chart-container">
        <canvas id="myChart" width="400" height="200"></canvas>
    </div>

    <div class="data-list-container">
        <ul class="data-list" id="dataList"></ul>
    </div>

    <div class="mobile-notification">
        <p>تم تحسين الواجهة للأجهزة المحمولة. يرجى التمرير لأسفل لعرض البيانات بالكامل.</p>
    </div>

    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'معدل ضربات القلب (BPM)',
                    data: [],
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false
                }, {
                    label: 'نسبة الأكسجين (%)',
                    data: [],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { title: { display: true, text: 'الوقت' }},
                    y: { beginAtZero: true }
                },
                plugins: {
                    zoom: {
                        zoom: {
                            wheel: { enabled: true },
                            pinch: { enabled: true },
                            mode: 'xy',
                        }
                    }
                }
            }
        });

        let allData = [];

        function fetchData() {
            fetch('fetch_data.php')
                .then(response => response.json())
                .then(data => {
                    const time = new Date().toLocaleTimeString();
                    data.time = time;
                    allData.push(data);

                    // تحديث الرسم البياني
                    myChart.data.labels.push(time);
                    myChart.data.datasets[0].data.push(data.heartRate);
                    myChart.data.datasets[1].data.push(data.bloodOxygen);
                    
                    if (myChart.data.labels.length > 10) {
                        myChart.data.labels.shift();
                        myChart.data.datasets[0].data.shift();
                        myChart.data.datasets[1].data.shift();
                    }
                    
                    myChart.update();
                    
                    // تحديث القائمة
                    updateList();

                    if (data.warning) {
                        alert("تحذير: قيم غير طبيعية!");
                        playWarningSound();
                    }
                });
        }

        function updateList() {
            const filter = document.getElementById('filter').value;
            const filteredData = filter === 'all' ? allData : allData.filter(d => filter === 'safe' ? !d.warning : d.warning);

            const dataList = document.getElementById('dataList');
            dataList.innerHTML = '';

            filteredData.forEach(data => {
                const listItem = document.createElement('li');
                listItem.innerHTML = `
                    <span>الوقت: ${data.time}</span>
                    <span>معدل ضربات القلب: ${data.heartRate} BPM</span>
                    <span>نسبة الأكسجين: ${data.bloodOxygen}%</span>
                    <span class="status ${data.warning ? 'warning' : 'safe'}">
                        ${data.warning ? 'تحذير' : 'طبيعي'}
                    </span>
                `;
                dataList.appendChild(listItem);
            });
        }

        function playWarningSound() {
            const audio = new Audio('warning.mp3');
            audio.play();
        }

        function exportData() {
            const csvContent = "data:text/csv;charset=utf-8," 
                + allData.map(d => `${d.time},${d.heartRate},${d.bloodOxygen},${d.warning ? 'تحذير' : 'طبيعي'}`).join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "health_data.csv");
            document.body.appendChild(link);
            link.click();
        }

        document.getElementById('filter').addEventListener('change', updateList);

        setInterval(fetchData, 5000);
    </script>

</body>
</html>