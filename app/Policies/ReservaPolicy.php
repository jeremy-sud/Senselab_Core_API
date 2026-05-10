<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\Usuario;

class ReservaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->tienePermiso('ver-reservas');
    }

    public function view(Usuario $usuario, Reserva $reserva): bool
    {
        return $usuario->empresa_id === $reserva->empresa_id && $usuario->tienePermiso('ver-reservas');
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->tienePermiso('crear-reservas');
    }

    public function update(Usuario $usuario, Reserva $reserva): bool
    {
        return $usuario->empresa_id === $reserva->empresa_id && $usuario->tienePermiso('editar-reservas');
    }

    public function delete(Usuario $usuario, Reserva $reserva): bool
    {
        return $usuario->empresa_id === $reserva->empresa_id && $usuario->tienePermiso('eliminar-reservas');
    }
}
