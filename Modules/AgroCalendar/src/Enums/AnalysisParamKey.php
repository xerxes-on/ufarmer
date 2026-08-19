<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Enums;

/**
 * Individual measurement keys inside an analysis row. Mirrors
 * Modules\FieldData\Enums\AnalysisParamKey in ufarm-ai-calendar — the values
 * MUST stay in sync because Eldor's SoilAnalysisAgentService reads them by
 * string match.
 */
enum AnalysisParamKey: string
{
    case pH = 'pH';
    case EC = 'EC_dS_m';
    case SAR = 'SAR';
    case CEC = 'CEC';
    case Texture = 'texture';
    case OrganicMatter = 'organic_matter_percent';
    case N = 'N_mg_kg';
    case P = 'P_mg_kg';
    case K = 'K_mg_kg';
    case Ca = 'Ca_mg_kg';
    case Mg = 'Mg_mg_kg';
    case Na = 'Na_mg_kg';
    case Fe = 'Fe_mg_kg';
    case Mn = 'Mn_mg_kg';
    case Zn = 'Zn_mg_kg';
    case B = 'B_mg_kg';
    case Cu = 'Cu_mg_kg';
    case FC = 'FC';
    case WP = 'WP';
    case BulkDensity = 'bulk_density';
    case HCO3 = 'HCO3_meq_L';
    case Cl = 'Cl_meq_L';
    case ECWater = 'EC_water';
    case RSC = 'RSC';
    case TotalCarbonates = 'total_carbonates';
    case ActiveCarbonates = 'active_carbonates';
    case WaterEC = 'EC';
    case WaterNa = 'Na';
    case WaterCl = 'Cl';
    case WaterHCO3 = 'HCO3';

    /**
     * Which AnalysisDetailKey bucket this parameter belongs to on the wire,
     * given the enclosing analysis type (pH/SAR mean different things in
     * a soil vs a water report).
     */
    public function detailGroup(AnalysisType $type): AnalysisDetailKey
    {
        if ($type === AnalysisType::Water) {
            return AnalysisDetailKey::WaterQuality;
        }

        if ($type === AnalysisType::Leaf) {
            if (in_array($this, [self::Fe, self::Mn, self::Zn, self::B, self::Cu], true)) {
                return AnalysisDetailKey::Micronutrients;
            }

            return AnalysisDetailKey::Macronutrients;
        }

        return match ($this) {
            self::pH, self::EC, self::SAR, self::CEC, self::Texture, self::OrganicMatter => AnalysisDetailKey::SoilProperties,

            self::N, self::P, self::K, self::Ca, self::Mg, self::Na => AnalysisDetailKey::Macronutrients,

            self::Fe, self::Mn, self::Zn, self::B, self::Cu => AnalysisDetailKey::Micronutrients,

            self::TotalCarbonates, self::ActiveCarbonates => AnalysisDetailKey::Carbonates,

            self::FC, self::WP, self::BulkDensity => AnalysisDetailKey::HydraulicProperties,

            self::HCO3, self::Cl, self::ECWater, self::RSC,
            self::WaterEC, self::WaterNa, self::WaterCl, self::WaterHCO3 => AnalysisDetailKey::WaterQuality,
        };
    }

    /**
     * @return array<self>
     */
    public static function soilKeys(): array
    {
        return [
            self::pH, self::EC, self::SAR, self::CEC, self::Texture, self::OrganicMatter,
            self::N, self::P, self::K, self::Ca, self::Mg, self::Na,
            self::Fe, self::Mn, self::Zn, self::B, self::Cu,
            self::TotalCarbonates, self::ActiveCarbonates,
            self::FC, self::WP, self::BulkDensity,
        ];
    }

    /**
     * @return array<self>
     */
    public static function waterKeys(): array
    {
        return [
            self::WaterEC, self::WaterNa, self::WaterCl, self::WaterHCO3,
            self::HCO3, self::Cl, self::ECWater, self::RSC, self::SAR, self::pH,
        ];
    }

    /**
     * @return array<self>
     */
    public static function leafKeys(): array
    {
        return [
            self::N, self::P, self::K, self::Ca, self::Mg,
            self::Fe, self::Mn, self::Zn, self::B, self::Cu,
        ];
    }

    /**
     * @return array<self>
     */
    public static function keysFor(AnalysisType $type): array
    {
        return match ($type) {
            AnalysisType::Soil => self::soilKeys(),
            AnalysisType::Water => self::waterKeys(),
            AnalysisType::Leaf => self::leafKeys(),
        };
    }

    /**
     * @return array<string>
     */
    public static function valuesFor(AnalysisType $type): array
    {
        return array_map(static fn (self $k) => $k->value, self::keysFor($type));
    }
}
