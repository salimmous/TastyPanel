<?php

namespace App\Services;

use App\Models\PlatformSetting;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Settings;

class SamlService
{
    public function settings(): array
    {
        return PlatformSetting::getData();
    }

    public function enabled(): bool
    {
        $settings = $this->settings();

        return (bool) ($settings['saml_enabled'] ?? false);
    }

    public function buildAuth(): Auth
    {
        return new Auth($this->buildSettings());
    }

    public function buildMetadata(): array
    {
        $settings = new Settings($this->buildSettings(), true);
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        return [
            'xml' => $metadata,
            'errors' => $errors,
        ];
    }

    public function extractUser(array $attributes, ?string $nameId = null): array
    {
        $settings = $this->settings();
        $emailKey = $settings['saml_attribute_email'] ?? 'email';
        $nameKey = $settings['saml_attribute_name'] ?? 'name';
        $groupsKey = $settings['saml_attribute_groups'] ?? 'groups';

        $email = $this->firstAttribute($attributes, $emailKey);
        if (! $email && $nameId && filter_var($nameId, FILTER_VALIDATE_EMAIL)) {
            $email = $nameId;
        }

        $name = $this->firstAttribute($attributes, $nameKey) ?? $email ?? 'SAML User';
        $groups = $attributes[$groupsKey] ?? [];

        return [
            'email' => $email,
            'name' => $name,
            'groups' => $groups,
        ];
    }

    public function allowedDomains(): array
    {
        $settings = $this->settings();
        $raw = $settings['saml_allowed_domains'] ?? '';
        if (! $raw) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function buildSettings(): array
    {
        $settings = $this->settings();
        $appUrl = rtrim(config('app.url'), '/');

        $spEntityId = $settings['saml_sp_entity_id'] ?? ($appUrl.'/admin/saml/metadata');
        $acsUrl = $settings['saml_acs_url'] ?? ($appUrl.'/admin/saml/acs');
        $sloUrl = $settings['saml_slo_url'] ?? ($appUrl.'/admin/saml/logout');

        $sp = [
            'entityId' => $spEntityId,
            'assertionConsumerService' => [
                'url' => $acsUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ],
            'singleLogoutService' => [
                'url' => $sloUrl,
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'NameIDFormat' => $settings['saml_nameid_format']
                ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        ];

        $idp = $this->resolveIdp($settings);

        return [
            'strict' => false,
            'debug' => (bool) config('app.debug'),
            'sp' => $sp,
            'idp' => $idp,
        ];
    }

    private function resolveIdp(array $settings): array
    {
        $parsed = [];
        if (! empty($settings['saml_idp_metadata_xml'])) {
            try {
                $parsed = IdPMetadataParser::parseXML($settings['saml_idp_metadata_xml']);
            } catch (\Throwable $e) {
                $parsed = [];
            }
        } elseif (! empty($settings['saml_idp_metadata_url'])) {
            try {
                $parsed = IdPMetadataParser::parseRemoteXML($settings['saml_idp_metadata_url']);
            } catch (\Throwable $e) {
                $parsed = [];
            }
        }

        if (isset($parsed['idp'])) {
            $parsed = $parsed['idp'];
        }

        $idp = [
            'entityId' => $settings['saml_idp_entity_id'] ?? ($parsed['entityId'] ?? ''),
            'singleSignOnService' => [
                'url' => $settings['saml_idp_sso_url'] ?? ($parsed['singleSignOnService']['url'] ?? ''),
                'binding' => $parsed['singleSignOnService']['binding'] ?? 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'singleLogoutService' => [
                'url' => $settings['saml_idp_slo_url'] ?? ($parsed['singleLogoutService']['url'] ?? ''),
                'binding' => $parsed['singleLogoutService']['binding'] ?? 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'x509cert' => $settings['saml_idp_x509'] ?? ($parsed['x509cert'] ?? ''),
        ];

        return $idp;
    }

    private function firstAttribute(array $attributes, string $key): ?string
    {
        if (isset($attributes[$key]) && is_array($attributes[$key]) && isset($attributes[$key][0])) {
            return $attributes[$key][0];
        }

        foreach ($attributes as $attrKey => $values) {
            if (strcasecmp($attrKey, $key) === 0 && is_array($values) && isset($values[0])) {
                return $values[0];
            }
        }

        return null;
    }
}
