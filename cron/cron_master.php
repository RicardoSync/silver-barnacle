<?php
echo "Iniciando CRON Monitor WISP...\n";
shell_exec("php " . escapeshellarg(__DIR__ . "/cron_pings.php"));
shell_exec("php " . escapeshellarg(__DIR__ . "/cron_recursos.php"));
shell_exec("php " . escapeshellarg(__DIR__ . "/cron_equipos.php"));
shell_exec("php " . escapeshellarg(__DIR__ . "/cron_alertas.php"));
echo "CRON Finalizado.\n";
