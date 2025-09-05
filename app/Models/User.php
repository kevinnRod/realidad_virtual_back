<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, HasRoles;

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

    public function enrollments(): HasMany { return $this->hasMany(StudyEnrollment::class); }
    public function consents(): HasMany { return $this->hasMany(Consent::class); }
    public function screenings(): HasMany { return $this->hasMany(EligibilityScreening::class); }
    public function vrSessions(): HasMany { return $this->hasMany(VrSession::class); }
    public function vitals(): HasMany { return $this->hasMany(Vital::class); }
    public function questionnaireAssignments(): HasMany { return $this->hasMany(QuestionnaireAssignment::class); }
    public function adverseEvents(): HasMany { return $this->hasMany(AdverseEvent::class); }
}
