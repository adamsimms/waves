<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ErddapTest extends TestCase
{
    public function test_optional_float_rejects_nan_string(): void
    {
        $this->assertNull(erddap_optional_float('NaN'));
        $this->assertNull(erddap_optional_float('nan'));
        $this->assertNull(erddap_optional_float(''));
    }

    public function test_optional_float_accepts_numeric_values(): void
    {
        $this->assertSame(2.8, erddap_optional_float(2.8));
        $this->assertSame(141.0, erddap_optional_float('141'));
    }

    public function test_resolve_float_falls_back_when_missing(): void
    {
        $this->assertSame(12.5, erddap_resolve_float('NaN', 12.5));
        $this->assertSame(4.0, erddap_resolve_float(4, 12.5));
    }

    public function test_wind_components_use_meteorological_direction(): void
    {
        $components = erddap_wind_components(10.0, 0.0, []);

        $this->assertEqualsWithDelta(0.0, $components['wind_x'], 0.0001);
        $this->assertEqualsWithDelta(-10.0, $components['wind_y'], 0.0001);
    }

    public function test_wind_components_scale_previous_vector_when_direction_missing(): void
    {
        $previous = [
            'wind_x' => 3.0,
            'wind_y' => 4.0,
        ];

        $components = erddap_wind_components(10.0, null, $previous);

        $this->assertEqualsWithDelta(6.0, $components['wind_x'], 0.0001);
        $this->assertEqualsWithDelta(8.0, $components['wind_y'], 0.0001);
    }

    public function test_build_station_data_maps_named_fields(): void
    {
        $data = erddap_build_station_data([
            'station_name' => 'smb_st_johns',
            'time' => '2026-07-01T10:30:01Z',
            'wind_spd_avg' => 2.8,
            'wind_dir_avg' => 141,
            'wave_ht_max' => 1.5,
            'wave_period_max' => 7.4,
        ], erddap_default_station_data());

        $this->assertSame('smb_st_johns', $data['station_name']);
        $this->assertSame(107.4, $data['size']);
        $this->assertSame(7.4, $data['wave_period']);
        $this->assertSame(1.5, $data['choppiness']);
        $this->assertSame(141.0, $data['wind_dir']);
    }

    public function test_build_station_data_preserves_previous_values_for_nan(): void
    {
        $previous = erddap_default_station_data();
        $previous['wind'] = 6.0;
        $previous['choppiness'] = 2.0;
        $previous['size'] = 120.0;

        $data = erddap_build_station_data([
            'station_name' => 'smb_st_johns',
            'time' => '2026-07-01T10:30:01Z',
            'wind_spd_avg' => 'NaN',
            'wave_ht_max' => 'NaN',
            'wave_period_max' => 'NaN',
        ], $previous);

        $this->assertSame(6.0, $data['wind']);
        $this->assertSame(2.0, $data['choppiness']);
        $this->assertSame(120.0, $data['size']);
        $this->assertSame(20.0, $data['wave_period']);
    }

    public function test_normalize_station_data_adds_missing_wind_components(): void
    {
        $normalized = erddap_normalize_station_data([
            'wind' => 10.0,
            'wind_dir' => 90.0,
        ]);

        $this->assertArrayHasKey('wind_x', $normalized);
        $this->assertArrayHasKey('wind_y', $normalized);
        $this->assertEqualsWithDelta(-10.0, $normalized['wind_x'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $normalized['wind_y'], 0.0001);
    }

    public function test_row_to_fields_maps_column_names(): void
    {
        $fields = erddap_row_to_fields(
            ['station_name', 'wind_spd_avg'],
            ['smb_st_johns', 3.2]
        );

        $this->assertSame('smb_st_johns', $fields['station_name']);
        $this->assertSame(3.2, $fields['wind_spd_avg']);
    }
}
