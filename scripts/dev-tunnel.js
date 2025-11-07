const localtunnel = require('localtunnel');
const fs = require('fs');
const path = require('path');

const port = Number(process.env.TUNNEL_PORT || 80);
const subdomain =
    process.env.TUNNEL_SUBDOMAIN ||
    `lukrato-${Math.random().toString(36).slice(2, 8)}`;
const outputPath = path.resolve(__dirname, '../storage/logs/tunnel-url.json');

async function main() {
    try {
        const tunnel = await localtunnel({ port, subdomain });
        const payload = {
            url: tunnel.url,
            port,
            subdomain,
            startedAt: new Date().toISOString(),
            pid: process.pid,
        };

        fs.writeFileSync(outputPath, JSON.stringify(payload, null, 2), 'utf-8');
        console.log(
            `Tunnel ativo em ${payload.url} -> http://localhost:${port} (PID ${payload.pid})`,
        );

        tunnel.on('close', () => {
            console.log('Tunnel encerrado.');
            fs.writeFileSync(
                outputPath,
                JSON.stringify(
                    {
                        ...payload,
                        closedAt: new Date().toISOString(),
                    },
                    null,
                    2,
                ),
                'utf-8',
            );
            process.exit(0);
        });
    } catch (err) {
        const errorPayload = {
            error: err?.message || String(err),
            port,
            subdomain,
            failedAt: new Date().toISOString(),
        };
        fs.writeFileSync(
            outputPath,
            JSON.stringify(errorPayload, null, 2),
            'utf-8',
        );
        console.error('Falha ao iniciar tunnel:', err);
        process.exit(1);
    }
}

main();
