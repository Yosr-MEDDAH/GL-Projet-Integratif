<?php

namespace App\Exports;

use App\Models\Facture;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesProviderExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $fournisseurId;

    public function __construct($id)
    {
        $this->fournisseurId = $id;
    }

    public function collection()
    {
        return Facture::select('number', 'invoice_name', 'organization', 'department', 'billing_date', 'consumption_period', 'currency', 'amount', 'payment_period', 'created_by')
            ->where('fournisseur_id', $this->fournisseurId)->get();
    }


    public function headings(): array
    {
        return [
            'Numéro de facture',
            'Nom de la facture',
            'Organisation',
            'Département',
            'Date de facturation',
            'Période de consommation',
            'Devise',
            'Montant',
            'Période de paiement',
            'Créé par'
        ];
    }
}
