<?php
// filename: smtp-port-check.php
// Purpose: Test if your VPS can open outbound connections to SMTP ports (25, 465, 587)

$tests = [
    'smtp.gmail.com' => [25, 465, 587],
    'smtp.mail.yahoo.com' => [25, 465, 587],
    'smtp.office365.com' => [25, 587],
    'smtp.sendgrid.net' => [25, 465, 587],
    'smtp.mailgun.org' => [25, 465, 587],
];

function checkPort(string $host, int $port, int $timeout = 5): array
{
    $isSsl = ($port === 465);
    $target = $isSsl ? "ssl://{$host}:{$port}" : "{$host}:{$port}";
    $errno = 0;
    $errstr = '';
    $start = microtime(true);
    $fp = @stream_socket_client($target, $errno, $errstr, $timeout);
    $elapsed = (microtime(true) - $start);

    if ($fp) {
        stream_set_timeout($fp, 2);
        $banner = @fgets($fp, 4096) ?: '';
        fclose($fp);
        return [
            'ok' => true,
            'latency_ms' => (int) round($elapsed * 1000),
            'banner' => trim($banner),
            'error' => null,
        ];
    }

    return [
        'ok' => false,
        'latency_ms' => (int) round($elapsed * 1000),
        'banner' => '',
        'error' => $errstr ?: "Error code {$errno}",
    ];
}

// Run tests
$results = [];
$summary = [
    25 => ['ok' => 0, 'fail' => 0, 'total' => 0],
    465 => ['ok' => 0, 'fail' => 0, 'total' => 0],
    587 => ['ok' => 0, 'fail' => 0, 'total' => 0],
];

foreach ($tests as $host => $ports) {
    foreach ($ports as $port) {
        $res = checkPort($host, $port);
        $results[$host][$port] = $res;

        if (isset($summary[$port])) {
            $summary[$port]['total']++;
            if ($res['ok']) {
                $summary[$port]['ok']++;
            } else {
                $summary[$port]['fail']++;
            }
        }
    }
}

function getServiceName($port)
{
    return match ($port) {
        25 => 'SMTP',
        465 => 'SMTPS (SSL)',
        587 => 'Submission',
        default => "Port $port",
    };
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Connectivity Test</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #22c55e;
            --error: #ef4444;
            --border: #334155;
            --accent: #3b82f6;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            margin: 0;
            padding: 2rem;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 2rem;
            text-align: center;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-secondary);
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .status-ok {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .status-fail {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        /* Results Table */
        .results-section {
            background: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            vertical-align: top;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .host-name {
            font-weight: 600;
            color: var(--accent);
        }

        .port-grid {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .port-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
            font-size: 0.875rem;
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .indicator.ok {
            background-color: var(--success);
            box-shadow: 0 0 8px var(--success);
        }

        .indicator.fail {
            background-color: var(--error);
        }

        .latency {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-left: auto;
        }

        .banner-text {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-family: monospace;
            opacity: 0.7;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .port-grid {
                flex-direction: column;
            }

            .banner-text {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>SMTP Port Connectivity</h1>
            <div class="subtitle">Outbound connection test from this server</div>
        </header>

        <div class="summary-grid">
            <?php foreach ($summary as $port => $data):
                $rate = $data['total'] > 0 ? round(($data['ok'] / $data['total']) * 100) : 0;
                $statusClass = $rate > 0 ? 'status-ok' : 'status-fail';
                ?>
                <div class="card">
                    <div class="card-title"><?php echo getServiceName($port); ?> (<?php echo $port; ?>)</div>
                    <div class="card-value">
                        <?php echo $data['ok']; ?> / <?php echo $data['total']; ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo $rate; ?>% Success
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="results-section">
            <table>
                <thead>
                    <tr>
                        <th>Host</th>
                        <th>Results</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $host => $portResults): ?>
                        <tr>
                            <td width="30%">
                                <div class="host-name"><?php echo $host; ?></div>
                            </td>
                            <td>
                                <div class="port-grid">
                                    <?php foreach ($portResults as $port => $res): ?>
                                        <div class="port-item" title="<?php echo $res['error'] ?? $res['banner']; ?>">
                                            <span class="indicator <?php echo $res['ok'] ? 'ok' : 'fail'; ?>"></span>
                                            <div>
                                                <div>
                                                    <strong><?php echo $port; ?></strong>
                                                    <span style="opacity:0.5">|</span>
                                                    <?php echo $res['ok'] ? 'OK' : 'FAIL'; ?>
                                                </div>
                                                <?php if ($res['ok']): ?>
                                                    <div class="latency"><?php echo $res['latency_ms']; ?>ms</div>
                                                    <?php if (!empty($res['banner'])): ?>
                                                        <span
                                                            class="banner-text"><?php echo htmlspecialchars(substr($res['banner'], 0, 50)); ?>...</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="latency" style="color: var(--error)"><?php echo $res['error']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p style="text-align: center; margin-top: 2rem; color: var(--text-secondary); font-size: 0.875rem;">
            Tip: If at least one host shows OK for a given port, outbound connectivity for that port is functioning.
        </p>
    </div>
</body>

</html>