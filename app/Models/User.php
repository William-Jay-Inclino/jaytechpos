<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Refunds created/initiated by this user (e.g., cashier).
     */
    public function refundsInitiated()
    {
        return $this->hasMany(Refund::class, 'user_id');
    }

    /**
     * Refunds approved/managed by this user (e.g., manager).
     */
    public function refundsApproved()
    {
        return $this->hasMany(Refund::class, 'manager_id');
    }

    /**
     * A user (cashier/seller) can have many sales.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'user_id');
    }

    public function utangTrackings()
    {
        return $this->hasMany(UtangTracking::class);
    }

    public function utangPayments()
    {
        return $this->hasMany(UtangPayment::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function productCategories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    // Helper methods for utang management
    public function getTotalUtangManaged(): float
    {
        return $this->utangTrackings()->sum('total_amount');
    }

    public function getActiveUtangCount(): int
    {
        return $this->utangTrackings()->active()->count();
    }

    public function getOverdueUtangCount(): int
    {
        return $this->utangTrackings()->overdue()->count();
    }

    public function getTotalPaymentsProcessed(): float
    {
        return $this->utangPayments()->sum('payment_amount');
    }
}
