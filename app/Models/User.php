<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;

#[Fillable(["name", "email", "password", "accepts_marketing"])]
#[Hidden(["password", "remember_token"])]

class User extends Authenticatable implements MustVerifyEmail
{
    public const ASSISTANT_ID = 0;
    public const ASSISTANT_NAME = "NERD IA";
    public const ASSISTANT_EMAIL = "nerd-ia@internal";

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "accepts_marketing" => "boolean",
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function sgcCompanies(): BelongsToMany
    {
        return $this->belongsToMany(SgcCompany::class, "sgc_company_user", "user_id", "company_id")
            ->withPivot("role")
            ->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles()->where("slug", $slug)->exists();
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasRole(Role::SYSTEM_ADMIN);
    }

    public function canLogin(): bool
    {
        return $this->id !== self::ASSISTANT_ID;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     legal_name: string,
     *     trade_name: string|null,
     *     document: string|null,
     *     role: string|null,
     *     is_owner: bool,
     * }>
     */
    public function sgcCompaniesForAuth(): array
    {
        return $this->sgcCompanies()
            ->orderBy("sgc_companies.trade_name")
            ->orderBy("sgc_companies.legal_name")
            ->get([
                "sgc_companies.id",
                "sgc_companies.legal_name",
                "sgc_companies.trade_name",
                "sgc_companies.document",
                "sgc_companies.owner_id",
                "sgc_company_user.role",
            ])
            ->map(fn (SgcCompany $company) => [
                "id" => $company->id,
                "legal_name" => $company->legal_name,
                "trade_name" => $company->trade_name,
                "document" => $company->document,
                "role" => $company->pivot->role,
                "is_owner" => $company->owner_id === $this->id,
            ])
            ->values()
            ->all();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
