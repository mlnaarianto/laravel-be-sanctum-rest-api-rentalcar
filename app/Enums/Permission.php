<?php

namespace App\Enums;

use MadBox\FilamentSpatiePermissions\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';
    case ManageCars = 'manage-cars';
    case ManageBookings = 'manage-bookings';
    case ManagePayments = 'manage-payments';
    case CreateBooking = 'create-booking';
    case ViewOwnBooking = 'view-own-booking';
    case CancelOwnBooking = 'cancel-own-booking';
    case ViewAssignedBooking = 'view-assigned-booking';
    case UpdateBookingStatus = 'update-booking-status';

    // Tambahkan method ini
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}