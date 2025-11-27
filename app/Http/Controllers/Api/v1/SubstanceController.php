<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\SubstanceByCodeRequest;
use App\Http\Requests\Api\v1\SubstanceByInchikeyRequest;
use App\Http\Resources\v1\SubstanceResource;
use App\Models\Susdat\Substance;

/**
 * @group Substances
 *
 * APIs for retrieving substance data from the NORMAN Suspect List Exchange database.
 */
class SubstanceController extends Controller
{
    /**
     * Get substance by NORMAN ID
     *
     * Retrieve a substance using its NORMAN identifier (e.g., NS00004453).
     * Returns comprehensive substance data including chemical identifiers,
     * molecular properties, categories, and sources.
     *
     * @urlParam code string required The NORMAN substance ID with NS prefix. Example: NS00004453
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "norman_id": "NS00004453",
     *     "name": "Sulfaclozine",
     *     "name_dashboard": "Sulfaclozine",
     *     "name_iupac": "4-amino-N-(6-chloropyrazin-2-yl)benzenesulfonamide",
     *     "cas_number": "102-65-8",
     *     "smiles": "c1cc(ccc1N)S(=O)(=O)Nc2cncc(n2)Cl",
     *     "stdinchi": "InChI=1S/C10H9ClN4O2S/...",
     *     "stdinchikey": "QKLPUVXBJHRFQZ-UHFFFAOYSA-N",
     *     "pubchem_cid": "66890",
     *     "molecular_formula": "C10H9Cl1N4O2S1",
     *     "monoisotopic_mass": 284.013474,
     *     "status": "active",
     *     "categories": [{"name": "Pharmaceuticals", "abbreviation": "PHAR"}],
     *     "sources": [{"code": "S01", "name": "NORMAN SLE"}]
     *   }
     * }
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Substance not found",
     *   "code": "NS00004453"
     * }
     * @response 422 scenario="Invalid Format" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {"code": ["The code must be in format NS followed by 8 digits (e.g., NS00004453)."]}
     * }
     */
    public function getByCode(SubstanceByCodeRequest $request): SubstanceResource|\Illuminate\Http\JsonResponse
    {
        $code = $request->route('code');
        $codeWithoutPrefix = $request->getCodeWithoutPrefix();

        $substance = Substance::with(['categories', 'sources', 'canonical'])
            ->where('code', $codeWithoutPrefix)
            ->first();

        if (! $substance) {
            return response()->json([
                'success' => false,
                'message' => 'Substance not found',
                'code' => $code,
            ], 404);
        }

        return new SubstanceResource($substance);
    }

    /**
     * Get substance by InChIKey
     *
     * Retrieve a substance using its Standard InChIKey identifier.
     * The InChIKey must be in the standard 27-character format (e.g., QKLPUVXBJHRFQZ-UHFFFAOYSA-N).
     *
     * @urlParam inchikey string required The Standard InChIKey. Example: QKLPUVXBJHRFQZ-UHFFFAOYSA-N
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "norman_id": "NS00004453",
     *     "name": "Sulfaclozine",
     *     "stdinchikey": "QKLPUVXBJHRFQZ-UHFFFAOYSA-N",
     *     "status": "active"
     *   }
     * }
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Substance not found",
     *   "inchikey": "QKLPUVXBJHRFQZ-UHFFFAOYSA-N"
     * }
     * @response 422 scenario="Invalid Format" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {"inchikey": ["The InChIKey must be in valid format (e.g., QKLPUVXBJHRFQZ-UHFFFAOYSA-N)."]}
     * }
     */
    public function getByInchikey(SubstanceByInchikeyRequest $request): SubstanceResource|\Illuminate\Http\JsonResponse
    {
        $inchikey = $request->route('inchikey');

        $substance = Substance::with(['categories', 'sources', 'canonical'])
            ->where('stdinchikey', $inchikey)
            ->first();

        if (! $substance) {
            return response()->json([
                'success' => false,
                'message' => 'Substance not found',
                'inchikey' => $inchikey,
            ], 404);
        }

        return new SubstanceResource($substance);
    }
}
