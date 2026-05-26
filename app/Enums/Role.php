<?php

namespace App\Enums;

use MadBox\FilamentSpatiePermissions\Contracts\RoleEnum;

enum Role: string implements RoleEnum
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case Operator = 'Operator';
    case Driver = 'Driver';
    case Customer = 'Customer';

    public function permissions(): array
    {
        return match ($this) {
            Role::SuperAdmin => Permission::cases(),
            Role::Admin => [
                Permission::ManageUsers,
                Permission::ManageCars,
                Permission::ManageBookings,
            ],
            Role::Operator => [
                Permission::ManageCars,
                Permission::ManageBookings,
            ],
            Role::Driver => [
                Permission::ViewAssignedBooking,
                Permission::UpdateBookingStatus,
            ],
            Role::Customer => [
                Permission::CreateBooking,
                Permission::ViewOwnBooking,
                Permission::CancelOwnBooking,
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
