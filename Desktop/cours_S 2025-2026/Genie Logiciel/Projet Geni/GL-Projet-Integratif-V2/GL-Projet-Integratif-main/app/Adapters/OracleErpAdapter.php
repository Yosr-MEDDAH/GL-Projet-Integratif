<?php

namespace App\Adapters;

use App\Models\BonDeCommande;
use App\Models\FournisseursSansCompte;

/**
 * ============================================================
 * PATTERN : ADAPTER (Structure)
 * ============================================================
 *
 * Contexte :
 *   L'API Oracle ERP retourne des structures de données avec
 *   des noms de champs propres à Oracle (VendorId, VendorName,
 *   PONumber, etc.) qui ne correspondent pas au modèle interne
 *   de l'application (idErp, name, num_commande, etc.).
 *
 * Ce fichier contient :
 *   1. OracleErpApiInterface  → interface de l'API Oracle (Adaptee)
 *   2. OracleErpApiClient     → simulation de l'API Oracle réelle
 *   3. ErpAdapterInterface    → interface cible attendue par l'application
 *   4. OracleErpAdapter       → l'Adapter qui traduit Oracle → Application
 * ============================================================
 */


/* ─────────────────────────────────────────────────────────── *
 *  INTERFACE DE L'API ORACLE ERP (Adaptee interface)          *
 *  Représente les méthodes telles qu'elles existent           *
 *  dans le système Oracle.                                     *
 * ─────────────────────────────────────────────────────────── */
interface OracleErpApiInterface
{
    /**
     * Retourne un fournisseur Oracle par identifiant fiscal.
     * Structure Oracle : VendorId, VendorName, TaxId, Email, Phone, Address, Country
     */
    public function getVendorByTaxId(string $taxId): ?array;

    /**
     * Retourne tous les fournisseurs Oracle.
     */
    public function getAllVendors(): array;

    /**
     * Retourne un bon de commande Oracle par numéro.
     * Structure Oracle : PONumber, VendorTaxId, PaymentTermsDays, CreatedBy, HasInvoice, ErpId
     */
    public function getPurchaseOrderByNumber(string $poNumber): ?array;

    /**
     * Retourne tous les bons de commande Oracle d'un fournisseur.
     */
    public function getPurchaseOrdersByVendor(string $taxId): array;

    /**
     * Synchronise (crée ou met à jour) un fournisseur dans Oracle ERP.
     */
    public function upsertVendor(array $vendorData): bool;

    /**
     * Crée un bon de commande dans Oracle ERP.
     */
    public function createPurchaseOrder(array $poData): ?string; // retourne le PONumber
}


/* ─────────────────────────────────────────────────────────── *
 *  CLIENT ORACLE ERP RÉEL (Adaptee)                           *
 *  Implémentation concrète qui appelle l'API Oracle via HTTP. *
 * ─────────────────────────────────────────────────────────── */
class OracleErpApiClient implements OracleErpApiInterface
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct(string $baseUrl, string $apiKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;
    }

    public function getVendorByTaxId(string $taxId): ?array
    {
        $response = $this->request('GET', "/vendors?TaxId={$taxId}");
        return $response['items'][0] ?? null;
    }

    public function getAllVendors(): array
    {
        $response = $this->request('GET', '/vendors');
        return $response['items'] ?? [];
    }

    public function getPurchaseOrderByNumber(string $poNumber): ?array
    {
        $response = $this->request('GET', "/purchase-orders/{$poNumber}");
        return $response ?? null;
    }

    public function getPurchaseOrdersByVendor(string $taxId): array
    {
        $response = $this->request('GET', "/purchase-orders?VendorTaxId={$taxId}");
        return $response['items'] ?? [];
    }

    public function upsertVendor(array $vendorData): bool
    {
        $response = $this->request('POST', '/vendors/upsert', $vendorData);
        return isset($response['success']) && $response['success'] === true;
    }

    public function createPurchaseOrder(array $poData): ?string
    {
        $response = $this->request('POST', '/purchase-orders', $poData);
        return $response['PONumber'] ?? null;
    }

    /**
     * Exécute un appel HTTP vers l'API Oracle ERP.
     */
    private function request(string $method, string $endpoint, array $body = []): ?array
    {
        $client = new \GuzzleHttp\Client();

        try {
            $options = [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
            ];

            if (!empty($body)) {
                $options['json'] = $body;
            }

            $response = $client->request($method, $this->baseUrl . $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Log::error("[OracleErpApiClient] Erreur API Oracle: " . $e->getMessage());
            return null;
        }
    }
}


/* ─────────────────────────────────────────────────────────── *
 *  INTERFACE CIBLE (Target interface)                         *
 *  Ce que l'application attend comme interface ERP.           *
 * ─────────────────────────────────────────────────────────── */
interface ErpAdapterInterface
{
    /**
     * Récupère un fournisseur par identifiant fiscal.
     * Retourne un tableau compatible avec FournisseursSansCompte.
     */
    public function getFournisseurByIdFiscale(string $idFiscale): ?array;

    /**
     * Récupère tous les fournisseurs ERP et les synchronise localement.
     *
     * @return FournisseursSansCompte[]
     */
    public function syncAllFournisseurs(): array;

    /**
     * Récupère un bon de commande par son numéro.
     * Retourne un tableau compatible avec BonDeCommande.
     */
    public function getBonDeCommandeByNumber(string $numCommande): ?array;

    /**
     * Récupère tous les bons de commande d'un fournisseur et les synchronise.
     *
     * @return BonDeCommande[]
     */
    public function syncBonsDeCommandePourFournisseur(string $idFiscale): array;

    /**
     * Exporte un fournisseur local vers Oracle ERP.
     */
    public function exportFournisseurVersErp(FournisseursSansCompte $fournisseur): bool;

    /**
     * Crée un bon de commande dans Oracle ERP depuis un modèle local.
     */
    public function exportBonDeCommandeVersErp(BonDeCommande $bonDeCommande): ?string;
}


/* ─────────────────────────────────────────────────────────── *
 *  L'ADAPTER CONCRET                                          *
 *  Traduit les données Oracle ERP ↔ modèle interne.           *
 * ─────────────────────────────────────────────────────────── */
class OracleErpAdapter implements ErpAdapterInterface
{
    public function __construct(
        private OracleErpApiInterface $oracleApi
    ) {}

    /**
     * Oracle retourne : VendorId, VendorName, TaxId, Email, Phone, Address, Country
     * On traduit vers : idErp, name, idFiscale, email, phone, adress, nationnalites
     */
    public function getFournisseurByIdFiscale(string $idFiscale): ?array
    {
        $oracleVendor = $this->oracleApi->getVendorByTaxId($idFiscale);

        if ($oracleVendor === null) {
            return null;
        }

        return $this->traduireVendorVersApp($oracleVendor);
    }

    public function syncAllFournisseurs(): array
    {
        $oracleVendors = $this->oracleApi->getAllVendors();
        $result        = [];

        foreach ($oracleVendors as $oracleVendor) {
            $donnees = $this->traduireVendorVersApp($oracleVendor);

            $fournisseur = FournisseursSansCompte::updateOrCreate(
                ['idFiscale' => $donnees['idFiscale']],
                $donnees
            );

            $result[] = $fournisseur;
        }

        return $result;
    }

    /**
     * Oracle retourne : PONumber, VendorTaxId, PaymentTermsDays, CreatedBy, HasInvoice, ErpId
     * On traduit vers : num_commande, four_idFiscale, delai_paiement, created_by, hasInvoice, idErp
     */
    public function getBonDeCommandeByNumber(string $numCommande): ?array
    {
        $oraclePO = $this->oracleApi->getPurchaseOrderByNumber($numCommande);

        if ($oraclePO === null) {
            return null;
        }

        return $this->traduirePOVersApp($oraclePO);
    }

    public function syncBonsDeCommandePourFournisseur(string $idFiscale): array
    {
        $oracleOrders = $this->oracleApi->getPurchaseOrdersByVendor($idFiscale);
        $result       = [];

        foreach ($oracleOrders as $oraclePO) {
            $donnees = $this->traduirePOVersApp($oraclePO);

            $bon = BonDeCommande::updateOrCreate(
                ['num_commande' => $donnees['num_commande']],
                $donnees
            );

            $result[] = $bon;
        }

        return $result;
    }

    /**
     * Traduit un modèle App → format Oracle et envoie à l'API.
     */
    public function exportFournisseurVersErp(FournisseursSansCompte $fournisseur): bool
    {
        $oracleVendor = $this->traduireAppVersVendor($fournisseur);
        return $this->oracleApi->upsertVendor($oracleVendor);
    }

    public function exportBonDeCommandeVersErp(BonDeCommande $bonDeCommande): ?string
    {
        $oraclePO = $this->traduireAppVersPO($bonDeCommande);
        return $this->oracleApi->createPurchaseOrder($oraclePO);
    }

    /* ──────────────────────────────────────────────────────── *
     *  MÉTHODES PRIVÉES DE TRADUCTION (mapping des champs)    *
     * ──────────────────────────────────────────────────────── */

    /**
     * Oracle vendor → tableau compatible App (FournisseursSansCompte)
     */
    private function traduireVendorVersApp(array $oracle): array
    {
        return [
            'idErp'        => $oracle['VendorId']   ?? null,
            'name'         => $oracle['VendorName']  ?? null,
            'idFiscale'    => $oracle['TaxId']        ?? null,
            'email'        => $oracle['Email']        ?? null,
            'phone'        => $oracle['Phone']        ?? null,
            'adress'       => $oracle['Address']      ?? null,
            'nationnalites'=> $oracle['Country']      ?? null,
        ];
    }

    /**
     * FournisseursSansCompte → format Oracle vendor
     */
    private function traduireAppVersVendor(FournisseursSansCompte $f): array
    {
        return [
            'VendorId'   => $f->idErp,
            'VendorName' => $f->name,
            'TaxId'      => $f->idFiscale,
            'Email'      => $f->email,
            'Phone'      => $f->phone,
            'Address'    => $f->adress,
            'Country'    => $f->nationnalites,
        ];
    }

    /**
     * Oracle PO → tableau compatible App (BonDeCommande)
     */
    private function traduirePOVersApp(array $oracle): array
    {
        return [
            'num_commande'    => $oracle['PONumber']          ?? null,
            'four_idFiscale'  => $oracle['VendorTaxId']       ?? null,
            'delai_paiement'  => $oracle['PaymentTermsDays']  ?? null,
            'created_by'      => $oracle['CreatedBy']          ?? null,
            'hasInvoice'      => $oracle['HasInvoice']         ?? 0,
            'idErp'           => $oracle['ErpId']              ?? null,
        ];
    }

    /**
     * BonDeCommande → format Oracle PO
     */
    private function traduireAppVersPO(BonDeCommande $b): array
    {
        return [
            'PONumber'         => $b->num_commande,
            'VendorTaxId'      => $b->four_idFiscale,
            'PaymentTermsDays' => $b->delai_paiement,
            'CreatedBy'        => $b->created_by,
            'HasInvoice'       => $b->hasInvoice,
            'ErpId'            => $b->idErp,
        ];
    }
}
