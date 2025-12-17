<?php

namespace Modules\Auth\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Auth\Models\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
  public function __construct(public array $userIds) {}

  public function collection()
  {
    return User::with("roles")->whereIn("id", $this->userIds)->get();
  }

  public function headings(): array
  {
    return [
      "ID",
      "Nama",
      "Username",
      "Email",
      "Status",
      "Role",
      "Terakhir Aktif",
      "Tanggal Dibuat",
    ];
  }

  public function map($user): array
  {
    return [
      $user->id,
      $user->name,
      $user->username ?? "-",
      $user->email,
      $user->status?->value ?? "-",
      $user->getRoleNames()->implode(", ") ?: "-",
      $user->last_active_relative ?? "Belum pernah",
      $user->created_at?->locale("id")->format("d M Y H:i"),
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ["font" => ["bold" => true]],
    ];
  }
}
