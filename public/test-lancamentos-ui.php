<?php
// Define BASE_URL
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', $protocol . '://' . $host . $path . '/');
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Test API Lançamentos</title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }

        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 5px;
        }

        button:hover {
            background: #0056b3;
        }

        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .info {
            color: blue;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔍 Test API Lançamentos</h1>
        <p>Data atual: <strong><?= date('Y-m-d') ?></strong></p>

        <div>
            <button onclick="testAPI()">🚀 Test API /api/lancamentos</button>
            <button onclick="testDirectDB()">🗄️ Test Direct DB Query</button>
            <button onclick="clearResults()">🧹 Clear</button>
        </div>

        <div id="results"></div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';

        function log(msg, type = 'info') {
            const div = document.createElement('div');
            div.className = type;
            div.innerHTML = msg;
            document.getElementById('results').appendChild(div);
        }

        function clearResults() {
            document.getElementById('results').innerHTML = '';
        }

        async function testAPI() {
            clearResults();
            log('<h3>Testing API Endpoint...</h3>');

            const month = '2026-01';
            const url = `${BASE_URL}api/lancamentos?month=${month}`;

            log(`📡 URL: <a href="${url}" target="_blank">${url}</a>`, 'info');

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                log(`✅ Response Status: ${response.status} ${response.statusText}`, 'success');
                log(`📦 Response Headers:<pre>${JSON.stringify([...response.headers.entries()], null, 2)}</pre>`);

                const text = await response.text();
                log(`📄 Raw Response (first 500 chars):<pre>${text.substring(0, 500)}</pre>`);

                try {
                    const data = JSON.parse(text);
                    log(`📊 Parsed JSON:`, 'success');
                    log(`<pre>${JSON.stringify(data, null, 2)}</pre>`);

                    if (Array.isArray(data)) {
                        log(`✅ Response is Array with ${data.length} items`, 'success');
                    } else if (data && Array.isArray(data.data)) {
                        log(`✅ Response has data array with ${data.data.length} items`, 'success');
                    } else {
                        log(`⚠️ Unexpected response format`, 'error');
                    }
                } catch (e) {
                    log(`❌ JSON Parse Error: ${e.message}`, 'error');
                }

            } catch (error) {
                log(`❌ Fetch Error: ${error.message}`, 'error');
                console.error('Fetch error:', error);
            }
        }

        async function testDirectDB() {
            clearResults();
            log('<h3>Testing Direct DB Query...</h3>');

            const url = `${BASE_URL}test-api-lancamentos.php`;

            try {
                const response = await fetch(url);
                const text = await response.text();
                log(`<pre>${text}</pre>`);
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        }

        // Auto-run on load
        window.onload = () => {
            setTimeout(testAPI, 500);
        };
    </script>
</body>

</html>