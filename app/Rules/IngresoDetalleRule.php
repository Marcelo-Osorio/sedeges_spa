<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IngresoDetalleRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $item) {
            if (!$item["partida_id"] || !$item["producto_id"] || !$item["unidad_medida_id"] || !$item["cantidad"] || !$item["costo"] || !$item["total"]) {
                $fail("Debes completar todos los campos de la tabla");
            }
        }
    }
}
