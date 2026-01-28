<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'content',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the user who created the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include user templates.
     */
    public function scopeUserTemplates($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Boot method to auto-generate slug from name.
     */
    protected static function booted(): void
    {
        static::creating(function (DocumentTemplate $template) {
            if (empty($template->slug) && !empty($template->name)) {
                $template->slug = Str::slug($template->name);
            }
        });

        static::updating(function (DocumentTemplate $template) {
            if ($template->isDirty('name') && empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    /**
     * Replace placeholders in the template content.
     */
    public function render(array $variables = []): string
    {
        $content = $this->content;

        // Default variables
        $defaults = [
            'date' => now()->format('Y-m-d'),
            'datetime' => now()->format('Y-m-d H:i'),
            'author' => auth()->user()?->name ?? 'Author',
        ];

        $variables = array_merge($defaults, $variables);

        foreach ($variables as $key => $value) {
            $content = str_replace(
                ['{{' . $key . '}}', '{{' . strtoupper($key) . '}}'],
                $value,
                $content
            );
        }

        return $content;
    }
}
