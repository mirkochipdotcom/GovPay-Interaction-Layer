<?php
namespace App\Database;

use PDO;

class EntrateRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    /**
     * Upsert di una tipologia proveniente dal Backoffice
     * @param array{idEntrata?:string,tipoEntrata?:array,ibanAccredito?:string,codiceContabilita?:string,abilitato?:bool} $e
     */
    public function upsertFromBackoffice(string $idDominio, array $e): void
    {
        $idEntrata = $e['idEntrata'] ?? ($e['tipoEntrata']['idEntrata'] ?? null);
        if (!$idEntrata) { return; }
        $descrizione = $e['tipoEntrata']['descrizione'] ?? null;
        $iban = $e['ibanAccredito'] ?? null;
        $codiceCont = $e['codiceContabilita'] ?? ($e['tipoEntrata']['codiceContabilita'] ?? null);
        $abilitatoBo = !empty($e['abilitato']);
        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO entrate_tipologie (id_dominio, id_entrata, descrizione, iban_accredito, codice_contabilita, abilitato_backoffice, effective_enabled, sorgente, created_at, updated_at)
                VALUES (:id_dominio, :id_entrata, :descrizione, :iban, :codice, :bo, :eff, "backoffice", :now, :now)
                ON DUPLICATE KEY UPDATE descrizione = VALUES(descrizione), iban_accredito = VALUES(iban_accredito), codice_contabilita = VALUES(codice_contabilita), abilitato_backoffice = VALUES(abilitato_backoffice), updated_at = VALUES(updated_at), effective_enabled = IF(override_locale IS NULL, VALUES(effective_enabled), effective_enabled)';
        $stmt = $this->pdo->prepare($sql);
        $eff = $abilitatoBo ? 1 : 0;
        $stmt->execute([
            ':id_dominio' => $idDominio,
            ':id_entrata' => $idEntrata,
            ':descrizione' => $descrizione,
            ':iban' => $iban,
            ':codice' => $codiceCont,
            ':bo' => $abilitatoBo ? 1 : 0,
            ':eff' => $eff,
            ':now' => $now,
        ]);
    }

    /**
     * Ritorna l’elenco tipologie per dominio con stato effettivo
     * @return array<int,array>
     */
    public function listByDominio(string $idDominio): array
    {
        $stmt = $this->pdo->prepare('SELECT id_entrata, descrizione, iban_accredito, codice_contabilita, abilitato_backoffice, override_locale, effective_enabled FROM entrate_tipologie WHERE id_dominio = :id ORDER BY id_entrata ASC');
        $stmt->execute([':id' => $idDominio]);
        return $stmt->fetchAll();
    }

    /** Imposta override locale (true/false) e aggiorna effective_enabled di conseguenza */
    public function setOverride(string $idDominio, string $idEntrata, ?bool $override): void
    {
        // Se override è null: rimuovi override e riallinea a abilitato_backoffice
        if ($override === null) {
            $sql = 'UPDATE entrate_tipologie SET override_locale = NULL, effective_enabled = abilitato_backoffice, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':now' => date('Y-m-d H:i:s'), ':dom' => $idDominio, ':ent' => $idEntrata]);
            return;
        }
        $sql = 'UPDATE entrate_tipologie SET override_locale = :ovr, effective_enabled = :eff, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ovr' => $override ? 1 : 0,
            ':eff' => $override ? 1 : 0,
            ':now' => date('Y-m-d H:i:s'),
            ':dom' => $idDominio,
            ':ent' => $idEntrata,
        ]);
    }
}
