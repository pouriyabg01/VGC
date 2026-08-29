<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A game the site puts on.
 *
 * Nothing points at this yet — tournaments still carry their game as a plain
 * string. This is the shelf the catalogue sits on, so it can be filled and
 * shown before anything depends on it.
 */
class Game extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'image'];

    /**
     * Where the cover can actually be fetched from.
     *
     * Null when no image was uploaded, so a caller can fall back rather than
     * render a broken one.
     */
    public function imageUrl(): ?string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : null;
    }
}
