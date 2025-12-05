<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Simple VIES wrapper service.
 *
 * Responsibilities:
 * - Normalize a provided NIF (with optional country prefix)
 * - Query the EU VIES SOAP service
 * - Cache results for a sensible period
 *
 * This keeps VIES logic in one place and out of controllers/frontend.
 */
class ViesService
{
    /**
     * Lookup a NIF in VIES.
     *
     * @param string $nif Raw user-provided NIF (may include country code)
     * @return array{valid: bool, name: ?string, address: ?string}
     * @throws \RuntimeException when SOAP is not available or other failures occur
     */
    public function lookup(string $nif): array
    {
        $nif = trim($nif);
        if ($nif === '') {
            throw new \InvalidArgumentException('missing_nif');
        }

        // Normalize: remove non-alphanumeric and uppercase
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $nif));

        if (preg_match('/^[A-Z]{2}/', $normalized)) {
            $country = substr($normalized, 0, 2);
            $vat = substr($normalized, 2);
        } else {
            $country = 'PT';
            $vat = $normalized;
        }

        if ($vat === '') {
            throw new \InvalidArgumentException('invalid_vat_number');
        }

        $cacheKey = sprintf('vies:%s:%s', $country, $vat);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        if (!extension_loaded('soap')) {
            throw new \RuntimeException('soap_not_enabled');
        }

        try {
            $client = new \SoapClient('https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl', [
                'cache_wsdl' => WSDL_CACHE_MEMORY,
            ]);

            $result = $client->checkVat([
                'countryCode' => $country,
                'vatNumber' => $vat,
            ]);

            $payload = [
                'valid' => (bool)($result->valid ?? false),
                'name' => isset($result->name) ? trim($result->name) : null,
                'address' => isset($result->address) ? trim($result->address) : null,
            ];

            // cache 24 hours
            Cache::put($cacheKey, $payload, now()->addHours(24));

            return $payload;
        } catch (\SoapFault $e) {
            throw new \RuntimeException('vies_soap_error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('vies_error: ' . $e->getMessage());
        }
    }
}
