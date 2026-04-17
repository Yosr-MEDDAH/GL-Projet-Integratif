<?php

namespace App\Strategies;

use App\Models\Facture;

class TypeBasedValidationStrategyResolver implements ValidationStrategyResolverInterface
{
    /** @var array<string, ValidationStrategyInterface> */
    private array $strategies;

    private ValidationStrategyInterface $defaultStrategy;

    public function __construct()
    {
        $this->strategies = [
            'LC' => new ValidationLCStrategy(),
            'Oper' => new ValidationOperStrategy(),
        ];

        $this->defaultStrategy = new Validation3WMStrategy();
    }

    public function resolve(Facture $facture): ValidationStrategyInterface
    {
        $typeFacture = $facture->typeFacture()->first();
        $nom = $typeFacture ? $typeFacture->nom : '3WM';

        return $this->strategies[$nom] ?? $this->defaultStrategy;
    }
}
