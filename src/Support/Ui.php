<?php

namespace Abdalmolood\AiSecurityGuardian\Support;

use Illuminate\Support\Str;

class Ui
{
    public function t(string $key, array $replace = [], ?string $locale = null): string
    {
        return trans("ai-security-guardian::ui.$key", $replace, $locale);
    }

    public function localeName(string $locale): string
    {
        return $this->t("locales.$locale", [], $locale);
    }

    public function availableLocales(): array
    {
        return array_values(array_filter(config('ai-security-guardian.ui.available_locales', ['en', 'ar'])));
    }

    public function isRtl(?string $locale = null): bool
    {
        $locale = $locale ?: app()->getLocale();

        return in_array($locale, config('ai-security-guardian.ui.rtl_locales', ['ar']), true);
    }

    public function direction(?string $locale = null): string
    {
        return $this->isRtl($locale) ? 'rtl' : 'ltr';
    }

    public function label(string $group, ?string $value, ?string $fallback = null): string
    {
        if ($value === null || $value === '') {
            return $fallback ?? $this->t('common.unknown');
        }

        $key = "ai-security-guardian::ui.values.$group.$value";
        $translated = trans($key);

        if ($translated !== $key) {
            return $translated;
        }

        return $fallback ?? Str::headline(str_replace('_', ' ', $value));
    }

    public function severity(?string $value): string
    {
        return $this->label('severity', $value);
    }

    public function findingStatus(?string $value): string
    {
        return $this->label('finding_status', $value);
    }

    public function scanStatus(?string $value): string
    {
        return $this->label('scan_status', $value);
    }

    public function patchStatus(?string $value): string
    {
        return $this->label('patch_status', $value);
    }

    public function testsStatus(?string $value): string
    {
        return $this->label('test_status', $value);
    }

    public function boolean(bool $value): string
    {
        return $value ? $this->t('common.enabled') : $this->t('common.disabled');
    }
}
