<?php

namespace App\Services;

use App\Models\OrganizationDomain;

class OrganizationDomainVerifier
{
    public function verify(OrganizationDomain $domain): bool
    {
        $records = $this->txtRecords($domain->dnsRecordName());
        $expected = $domain->dnsRecordValue();

        return collect($records)->contains(fn (string $record) => hash_equals($expected, trim($record, '"')));
    }

    protected function txtRecords(string $hostname): array
    {
        $records = dns_get_record($hostname, DNS_TXT);

        return collect($records ?: [])->map(fn (array $record) => (string) ($record['txt'] ?? ''))->filter()->values()->all();
    }
}
