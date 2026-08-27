<?php

namespace App\Support;

/**
 * What counts as an acceptable proof screenshot.
 *
 * The API and the profile form both submit through the same trait, so they
 * read their rules, their messages and the wording shown to the player from
 * here — otherwise one side quietly accepts a file the other rejects.
 */
final class MatchScreenshot
{
    /** Formats a player may upload. */
    public const FORMATS = ['jpg', 'jpeg', 'png', 'webp'];

    /** Largest upload accepted, in kilobytes. */
    public const MAX_KB = 5120;

    /**
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return [
            'required',
            'file',
            'mimes:'.implode(',', self::FORMATS),
            'max:'.self::MAX_KB,
        ];
    }

    /**
     * Failure messages that name the limits, instead of Laravel's default
     * "must be a file of type: jpg, jpeg, png, webp".
     *
     * @param  string  $field  the key the rules are attached to
     * @return array<string, string>
     */
    public static function messages(string $field = 'screenshot'): array
    {
        return [
            $field.'.required' => 'Attach a screenshot of the final score.',
            $field.'.file' => 'The screenshot has to be an uploaded file.',
            $field.'.mimes' => 'The screenshot must be a '.self::formats().' image.',
            $field.'.max' => 'The screenshot must be under '.self::maxMb().' MB.',
        ];
    }

    /** The same limits in words, for the form and the API docs. */
    public static function hint(): string
    {
        return self::formats().' · up to '.self::maxMb().' MB';
    }

    /** "JPG, PNG or WEBP" — jpeg is the same format as jpg, so it is not listed twice. */
    public static function formats(): string
    {
        $shown = array_values(array_diff(array_map('strtoupper', self::FORMATS), ['JPEG']));
        $last = array_pop($shown);

        return $shown ? implode(', ', $shown).' or '.$last : $last;
    }

    /** The size cap in MB, without a trailing ".0" on whole numbers. */
    public static function maxMb(): string
    {
        return rtrim(rtrim(number_format(self::MAX_KB / 1024, 1), '0'), '.');
    }

    /** The accept attribute for the file input, so the picker filters too. */
    public static function accept(): string
    {
        return implode(',', array_map(fn (string $f) => '.'.$f, self::FORMATS));
    }
}
