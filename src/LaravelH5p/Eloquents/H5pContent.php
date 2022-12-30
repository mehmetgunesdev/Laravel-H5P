<?php

namespace InHub\LaravelH5p\Eloquents;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class H5pContent extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'title',
        'library_id',
        'parameters',
        'filtered',
        'slug',
        'embed_type',
        'disable',
        'content_type',
        'author',
        'source',
        'year_from',
        'year_to',
        'license',
        'license_version',
        'license_extras',
        'author_comments',
        'changes',
        'default_languge',
        'keywords',
        'description',
        'course_id'
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return object
     */
    public function get_user(): object
    {
        return (object)DB::table('users')->where('id', $this->user_id)->first();
    }
}
