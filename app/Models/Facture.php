<?php

namespace App\Models;

use App\States\FactureStateFactory;
use App\States\FactureStateInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'invoice_name',
        'organization',
        'department',
        'billing_date',
        'consumption_period',
        'currency',
        'amount',
        'type_facture_id',
        'invoice_file_path',
        'reception_date',
        'payment_period',
        'fournisseur_id',
        'borderau_id',
        'bon_de_commande_id',
        'etat_id', // Ajout du champ 'etat_id'
        'isArchived',
        'created_by',
        'agent_bof_id',
        'pieces_jointes',
        'objet_facture_id',
        'numOp',
        'idFiscale',
        'structureOrd'
    ];

    public function typeFacture()
    {
        return $this->belongsTo(TypesFactures::class, 'type_facture_id');
    }

    public function fournisseur()
    {
        return $this->belongsTo(User::class)->where('role_id', 3);
    }

    public function etat()
    {
        return $this->belongsTo(Etat::class);
    }

    public function bordereau()
    {
        return $this->belongsTo(Bordereau::class);
    }

    public function bonDeCommande()
    {
        return $this->belongsTo(BonDeCommande::class);
    }

    public function etapes()
    {
        return $this->hasMany(Etapes::class);
    }

    public function objetFacture()
    {
        return $this->belongsTo(ObjetFacture::class, 'objet_facture_id');
    }

    protected $casts = [
        'pieces_jointes' => 'array',
    ];

    public function getState(): FactureStateInterface
    {
        return FactureStateFactory::resolve($this);
    }

    public function valider(string $agentRole): void
    {
        $this->getState()->valider($this, $agentRole);
    }

    public function rejeter(string $agentRole): void
    {
        $this->getState()->rejeter($this, $agentRole);
    }

    // ============================================================
    // GRASP — Information Expert
    // Les méthodes ci-dessous exploitent les données que la
    // classe Facture POSSÈDE déjà. Elle est donc l'experte
    // désignée pour répondre à ces questions.
    // ============================================================

    /**
     * Indique si la facture est en attente de traitement.
     *
     * etat_id = 1 → état initial "Soumise / En attente"
     *
     * GRASP : Facture connaît son propre etat_id → elle répond.
     */
    public function estEnAttente(): bool
    {
        return $this->etat_id === 1;
    }

    /**
     * Indique si la facture a été rejetée.
     *
     * etat_id = 3 → état "Rejetée"
     *
     * GRASP : Facture connaît son propre etat_id → elle répond.
     */
    public function estRejetee(): bool
    {
        return $this->etat_id === 3;
    }

    /**
     * Indique si la facture est entièrement payée.
     *
     * etat_id = 2 ET validePar = 'Agent Trésorerie'
     * → combinaison qui identifie l'état final "Payée"
     *
     * GRASP : Facture connaît etat_id ET validePar → elle répond.
     */
    public function estPayee(): bool
    {
        return $this->etat_id === 2 && $this->validePar === 'Agent Trésorerie';
    }

    /**
     * Retourne le nombre d'étapes de validation déjà enregistrées
     * pour cette facture.
     *
     * GRASP : Facture possède la relation etapes() → elle répond.
     *
     * Usage : $facture->getNombreEtapes()
     */
    public function getNombreEtapes(): int
    {
        return $this->etapes()->count();
    }

    /**
     * Retourne le nom lisible de l'état courant de la facture.
     *
     * Délègue au State actuel (patron State) tout en restant
     * accessible directement depuis la Facture.
     *
     * GRASP : Facture connaît son State → elle peut exposer le nom.
     */
    public function getNomEtatCourant(): string
    {
        return $this->getState()->getNomEtat();
    }

    /**
     * Retourne le prochain agent attendu dans le circuit de validation.
     *
     * GRASP : Facture connaît son State → elle peut déléguer et exposer.
     *
     * @return string|null  Nom du rôle attendu, ou null si circuit terminé.
     */
    public function getProchainAgent(): ?string
    {
        return $this->getState()->getProchainAgent();
    }

    /**
     * Indique si le montant de la facture dépasse un seuil donné.
     *
     * GRASP : Facture connaît son propre attribut amount → elle répond.
     *
     * @param  float  $seuil  Montant de référence (par défaut : 100 000)
     */
    public function estAMontantEleve(float $seuil = 100000.0): bool
    {
        return $this->amount > $seuil;
    }

    /**
     * Indique si la facture a été créée par un fournisseur
     * (et non par un agent BOF).
     *
     * GRASP : Facture connaît fournisseur_id → elle répond.
     */
    public function estCreeeParFournisseur(): bool
    {
        return $this->fournisseur_id !== null;
    }

    /**
     * Retourne le montant de la facture formaté avec devise.
     *
     * @return string
     */
    public function getMontantFormate(): string
    {
        $devise = $this->currency ?? 'TND';
        return number_format((float) $this->amount, 3, '.', ' ') . ' ' . $devise;
    }

    /**
     * Retourne l'étape courante (dernier agent qui a traité la facture).
     *
     * @return string|null
     */
    public function getEtapeCourante(): ?string
    {
        return $this->validePar;
    }
}
