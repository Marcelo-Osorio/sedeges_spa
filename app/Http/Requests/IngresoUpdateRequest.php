<?php

namespace App\Http\Requests;

use App\Rules\IngresoDetalleRule;
use Illuminate\Foundation\Http\FormRequest;

class IngresoUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "codigo" => "required|unique:ingresos,codigo," . $this->id,
            "almacen_id" => "required",
            "donacion" => "required",
            "proveedor" => "required|string",
            "con_fondos" => "nullable|string",
            "nro_factura" => "nullable|string",
            "pedido_interno" => "required_if:donacion,SI|nullable|string",
            "ingreso_detalles" => ["required", "array", "min:1", new IngresoDetalleRule],
            "fecha_ingreso" => "required|date",
            "hora_ingreso" => "required",
            "para" => "required_if:donacion,SI|nullable|string",
            "observaciones" => "nullable|string",
            "fecha_nota" => "nullable|date",
            "fecha_factura" => "nullable|date",
        ];
    }

    public function messages(): array
    {
        return [
            "codigo.required" => "Este campo es obligatorio",
            "codigo.unique" => "Este código ya fue registrado",
            "almacen_id.required" => "Este campo es obligatorio",
            "donacion.required" => "Este campo es obligatorio",
            "proveedor.required" => "Este campo es obligatorio",
            "proveedor.string" => "Debes ingresar un texto",
            "ingreso_detalles.required" => "Debes ingresar al menos 1 producto",
            "ingreso_detalles.array" => "Debes enviar un array de productos",
            "ingreso_detalles.min" => "Debes agregar al menos :min productos",
            "con_fondos.string" => "Debes ingresar un texto",
            "nro_factura.string" => "Debes ingresar un texto",
            "pedido_interno.required_if" => "Debes completar este campo",
            "pedido_interno.string" => "Debes ingresar un texto",
            "fecha_ingreso.required" => "Este campo es obligatorio",
            "hora_ingreso.required" => "Este campo es obligatorio",
            "para.required_if" => "Este campo es obligatorio",
            "para.string" => "Debes ingresar un texto",
            "observaciones.string" => "Debes ingresar un texto",
            "fecha_nota.date" => "Debes ingresar una fecha valida",
            "fecha_factura.date" => "Debes ingresar una fecha valida",
        ];
    }
}
