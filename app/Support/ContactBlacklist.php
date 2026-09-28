<?php

namespace App\Support;

use Illuminate\Http\Request;

class ContactBlacklist
{
    public static function blocks(Request $request): bool
    {
        $ip = (string) $request->ip();
        $email = strtolower(trim((string) $request->input('email', '')));
        $haystack = strtolower(trim(implode(' ', [
            (string) $request->input('name'),
            (string) $request->input('business'),
            (string) $request->input('message'),
            $email,
        ])));

        $list = config('contact.blacklist', []);

        if ($ip !== '' && self::inList($ip, $list['ips'] ?? [])) {
            return true;
        }

        if ($email !== '' && self::inList($email, $list['emails'] ?? [])) {
            return true;
        }

        $domain = self::emailDomain($email);
        if ($domain !== '' && self::inList($domain, $list['domains'] ?? [])) {
            return true;
        }

        foreach ($list['keywords'] ?? [] as $keyword) {
            $keyword = strtolower(trim((string) $keyword));
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $items
     */
    private static function inList(string $value, array $items): bool
    {
        $value = strtolower(trim($value));

        foreach ($items as $item) {
            if ($value === strtolower(trim((string) $item))) {
                return true;
            }
        }

        return false;
    }

    private static function emailDomain(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '';
        }

        return substr(strrchr($email, '@') ?: '', 1) ?: '';
    }
}
