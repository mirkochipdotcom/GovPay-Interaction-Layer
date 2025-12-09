<?php
declare(strict_types=1);

use App\Database\Connection;

require __DIR__ . '/../vendor/autoload.php';

echo "Esecuzione migrazioni SQL...\n";

$dir = realpath(__DIR__ . '/../migrations') ?: (__DIR__ . '/../migrations');
if (!is_dir($dir)) {
	echo "Nessuna directory migrazioni trovata: {$dir}\n";
	exit(0);
}

$files = glob($dir . '/*.sql');
if (!$files) {
	echo "Nessun file .sql trovato in {$dir}\n";
	exit(0);
}
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

try {
	$pdo = Connection::getPDO();
} catch (Throwable $e) {
	fwrite(STDERR, "Connessione DB fallita: " . $e->getMessage() . "\n");
	exit(1);
}

foreach ($files as $f) {
	echo "- Eseguo: " . basename($f) . "... ";
	try {
		$sql = file_get_contents($f);
		if ($sql === false) { throw new RuntimeException('Impossibile leggere il file'); }
		$pdo->exec($sql);
		echo "OK\n";
	} catch (Throwable $e) {
		echo "ERRORE\n";
		fwrite(STDERR, '  ' . $e->getMessage() . "\n");
	}
}

echo "Migrazioni completate.\n";

