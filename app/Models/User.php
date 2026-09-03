<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['username', 'full_name', 'email', 'phone', 'address', 'avatar_path', 'role', 'password', 'branch_id', 'signature_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->branch_id && in_array($user->role, ['cashier', 'purchasing', 'operator', 'sales'])) {
                $branchId = self::resolveBranchFromUsername($user->username);
                if ($branchId) {
                    $user->branch_id = $branchId;
                }
            }
        });
    }

    public static function resolveBranchFromUsername($username)
    {
        $username = strtolower($username);
        
        $branchKeyword = '';
        if (str_contains($username, 'grandwis')) {
            $branchKeyword = 'Grand Wisata';
        } elseif (str_contains($username, 'btr')) {
            $branchKeyword = 'BTR Bekasi';
        } elseif (str_contains($username, 'tambun')) {
            $branchKeyword = 'Tambun';
        } elseif (str_contains($username, 'pusat')) {
            $branchKeyword = 'Pusat';
        }

        if ($branchKeyword) {
            $branch = \App\Models\Branch::where('nama_cabang', 'like', '%' . $branchKeyword . '%')->first();
            return $branch ? $branch->id : null;
        }

        // Default to Pusat if not specified
        $pusat = \App\Models\Branch::where('nama_cabang', 'like', '%Pusat%')->first();
        return $pusat ? $pusat->id : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin()
    {
        return strcasecmp($this->username, 'KINGAshabil') === 0;
    }

    public function isOwner()
    {
        return $this->role === 'owner' || $this->isSuperAdmin();
    }

    public function isPurchasing()
    {
        return $this->role === 'purchasing';
    }

    public function isManager()
    {
        return in_array($this->role, ['manager', 'manajer_cabang']);
    }

    public function canAccessBranch($branchId)
    {
        if ($this->isOwner() || $this->isSuperAdmin()) {
            return true;
        }
        return $this->branch_id == $branchId;
    }

    public function isCashier()
    {
        return $this->role === 'cashier';
    }

    public function isOperator()
    {
        return in_array($this->role, ['operator', 'sales']);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class);
    }
}
