<?php
/**
 * SPDX-License-Identifier: EUPL-1.2
 * License: European Union Public Licence v1.2 (EUPL-1.2)
 */
namespace App\Database;

use App\Logger;
use PDO;

class EntrateRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPDO();
    }

    /**
     * Restituisce le tipologie di pendenza abilitate per il dominio
     * @return array<int,array{id_entrata:string,descrizione:string}>
     */
    public function listAbilitateByDominio(string $idDominio): array
    {
        $stmt = $this->pdo->prepare('SELECT id_entrata, COALESCE(descrizione_locale, descrizione) AS descrizione, tipo_contabilita FROM entrate_tipologie WHERE id_dominio = :id AND abilitato_backoffice = 1 AND external_url IS NULL ORDER BY COALESCE(descrizione_locale, descrizione) ASC');
        $stmt->execute([':id' => $idDominio]);
        return $stmt->fetchAll();
    }

    /**
     * Upsert di una tipologia proveniente dal Backoffice
    * @param array{idEntrata?:string,tipoEntrata?:array,ibanAccredito?:string,codiceContabilita?:string,tipoBollo?:string,abilitato?:bool} $e
     */
    public function upsertFromBackoffice(string $idDominio, array $e): void
    {
        $idEntrata = $e['idEntrata'] ?? ($e['tipoEntrata']['idEntrata'] ?? null);
        if (!$idEntrata) {
            return;
        }

    $descrizione = $e['tipoEntrata']['descrizione'] ?? null;
    $iban = $e['ibanAccredito'] ?? null;
    $codiceCont = $e['codiceContabilita'] ?? ($e['tipoEntrata']['codiceContabilita'] ?? null);
    $tipoBollo = $e['tipoBollo'] ?? ($e['tipoEntrata']['tipoBollo'] ?? null);
    $tipoContabilita = $e['tipoContabilita'] ?? ($e['tipoEntrata']['tipoContabilita'] ?? null);
    $abilitatoBo = !empty($e['abilitato']);
    $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO entrate_tipologie (id_dominio, id_entrata, descrizione, iban_accredito, codice_contabilita, tipo_bollo, tipo_contabilita, abilitato_backoffice, sorgente, created_at, updated_at)
        VALUES (:id_dominio, :id_entrata, :descrizione, :iban, :codice, :tipo_bollo, :tipo_contabilita, :bo, "backoffice", :now_created, :now_updated)
        ON DUPLICATE KEY UPDATE descrizione = VALUES(descrizione), iban_accredito = VALUES(iban_accredito), codice_contabilita = VALUES(codice_contabilita), tipo_bollo = VALUES(tipo_bollo), tipo_contabilita = VALUES(tipo_contabilita), abilitato_backoffice = VALUES(abilitato_backoffice), updated_at = VALUES(updated_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_dominio' => $idDominio,
            ':id_entrata' => $idEntrata,
            ':descrizione' => $descrizione,
            ':iban' => $iban,
            ':codice' => $codiceCont,
            ':tipo_bollo' => $tipoBollo,
            ':tipo_contabilita' => $tipoContabilita,
            ':bo' => $abilitatoBo ? 1 : 0,
            ':now_created' => $now,
            ':now_updated' => $now,
        ]);
    }

    /**
     * Sanitizza un valore di codice entrata per rispettare il pattern accettato dall'API
     */
    private static function sanitizeCodEntrata(?string $value): ?string
    {
        if ($value === null) return null;
        $san = preg_replace('/[^A-Za-z0-9\-_.]/', '', $value);
        $san = substr($san, 0, 35);
        return $san === '' ? null : $san;
    }

    /**
     * Recupera i dettagli di una singola tipologia (iban, codice contabilita, tipo_bollo, descrizione)
     * @return array<string,mixed>|null
     */
    public function findDetails(string $idDominio, string $idEntrata): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id_entrata, descrizione, descrizione_locale, iban_accredito, codice_contabilita, tipo_bollo, tipo_contabilita, abilitato_backoffice, override_locale, external_url, COALESCE(descrizione_locale, descrizione) AS descrizione_effettiva FROM entrate_tipologie WHERE id_dominio = :dom AND id_entrata = :ent LIMIT 1');
        $stmt->execute([':dom' => $idDominio, ':ent' => $idEntrata]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Ritorna l’elenco tipologie per dominio
     * @return array<int,array>
     */
    public function listByDominio(string $idDominio): array
    {
    $stmt = $this->pdo->prepare('SELECT id_entrata, descrizione, descrizione_locale, COALESCE(descrizione_locale, descrizione) AS descrizione_effettiva, iban_accredito, codice_contabilita, tipo_contabilita, abilitato_backoffice, override_locale, external_url FROM entrate_tipologie WHERE id_dominio = :id ORDER BY id_entrata ASC');
        $stmt->execute([':id' => $idDominio]);
        return $stmt->fetchAll();
    }


    /** @return array{id_entrata:string,abilitato_backoffice:int,override_locale:?int,external_url:?string}|null */
    public function findOne(string $idDominio, string $idEntrata): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id_entrata, abilitato_backoffice, override_locale, external_url FROM entrate_tipologie WHERE id_dominio = :dom AND id_entrata = :ent LIMIT 1');
        $stmt->execute([':dom' => $idDominio, ':ent' => $idEntrata]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Imposta override locale (true/false) */
    public function setOverride(string $idDominio, string $idEntrata, ?bool $override): void
    {
        $now = date('Y-m-d H:i:s');
        if ($override === null) {
            $sql = 'UPDATE entrate_tipologie SET override_locale = NULL, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':now' => $now, ':dom' => $idDominio, ':ent' => $idEntrata]);
            return;
        }
        $sql = 'UPDATE entrate_tipologie SET override_locale = :ovr, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':ovr' => $override ? 1 : 0,
            ':now' => $now,
            ':dom' => $idDominio,
            ':ent' => $idEntrata,
        ]);
    }

    /** Imposta l'URL esterna per la tipologia */
    public function setExternalUrl(string $idDominio, string $idEntrata, ?string $url): void
    {
        $sql = 'UPDATE entrate_tipologie SET external_url = :url, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':url' => ($url === null || $url === '') ? null : $url,
            ':now' => date('Y-m-d H:i:s'),
            ':dom' => $idDominio,
            ':ent' => $idEntrata,
        ]);
    }

    /** Aggiorna la descrizione locale (override) della tipologia. */
    public function updateDescrizione(string $idDominio, string $idEntrata, string $descrizione): void
    {
        $sql = 'UPDATE entrate_tipologie SET descrizione_locale = :descrizione, updated_at = :now WHERE id_dominio = :dom AND id_entrata = :ent';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':descrizione' => $descrizione,
            ':now' => date('Y-m-d H:i:s'),
            ':dom' => $idDominio,
            ':ent' => $idEntrata,
        ]);
    }

}
