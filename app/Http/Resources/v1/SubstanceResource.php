<?php

declare(strict_types=1);

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubstanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'norman_id' => $this->prefixed_code,
            'name' => $this->name,
            'name_dashboard' => $this->name_dashboard,
            'name_chemspider' => $this->name_chemspider,
            'name_iupac' => $this->name_iupac,
            'cas_number' => $this->cas_number,
            'smiles' => $this->smiles,
            'smiles_dashboard' => $this->smiles_dashboard,
            'stdinchi' => $this->stdinchi,
            'stdinchikey' => $this->stdinchikey,
            'pubchem_cid' => $this->pubchem_cid,
            'chemspider_id' => $this->chemspider_id,
            'dtxid' => $this->dtxid,
            'molecular_formula' => $this->molecular_formula,
            'monoisotopic_mass' => $this->mass_iso,
            'average_mass' => $this->average_mass,
            'status' => $this->status,
            'external_links' => $this->external_links,
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($cat) => [
                'name' => $cat->name,
                'abbreviation' => $cat->abbreviation,
            ])),
            'sources' => $this->whenLoaded('sources', fn () => $this->sources->map(fn ($source) => [
                'code' => $source->code,
                'name' => $source->sanitized_name,
            ])),
            'metadata' => [
                'synonyms' => $this->metadata_synonyms,
                'cas_alternatives' => $this->metadata_cas,
                'ms_ready' => $this->metadata_ms_ready,
                'general' => $this->metadata_general,
            ],
            'canonical' => $this->when($this->canonical_id !== null, fn () => [
                'norman_id' => $this->canonical?->prefixed_code,
                'message' => 'This substance has been merged. Use the canonical norman_id instead.',
            ]),
        ];
    }
}
