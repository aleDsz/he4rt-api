<?php

namespace App\Entities\Auth;

use App\Entities\Category\Product\Product;
use App\Entities\Coupons\Coupon;
use App\Entities\Levelup\Level;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

use Silber\Bouncer\Database\HasRolesAndAbilities;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, Notifiable, HasRolesAndAbilities;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'discord_id',
        'level',
        'current_exp',
        'money',
        'git',
        'name',
        'nickname',
        'language',
        'about',
        'daily',
        'reputation',
        'twitch'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];

    protected $dates = ['daily'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function levelup()
    {
        return $this->hasOne(Level::class, 'id', 'level');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function reputation()
    {
        return $this->belongsToMany(User::class,
            'reputation_logs',
            'user_id',
            'receiver_id'
        )->withTimestamps();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class,
            'user_products',
            'user_id',
            'product_id'
        )->withTimestamps();
    }
}
