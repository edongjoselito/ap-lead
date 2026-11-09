<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Everything that differs between the regional site and a single-division site.
 * Views read names and logos from here instead of hardcoding Region XI.
 *
 * Saved by the admin at Pages/site_settings.
 *
 * @return array known, is_division, division_id, division_name, name, office,
 *               area, place, logo, logo_alt
 */
function site_brand()
{
    static $brand = null;
    if ($brand !== null) return $brand;

    // Error pages can render before the models load, or because the database is
    // down. The settings are unknown then, so name no office rather than a wrong one.
    $CI = class_exists('CI_Controller', false) ? CI_Controller::get_instance() : null;
    if (!$CI || !isset($CI->Page_model)) {
        return array(
            'known' => false, 'is_division' => false, 'division_id' => 0, 'division_name' => '',
            'name' => '', 'office' => '', 'area' => '', 'place' => '', 'logo' => '', 'logo_alt' => '',
        );
    }

    $settings = $CI->Page_model->site_settings();
    $division = $settings['deployment_mode'] === 'division' ? $settings['division'] : null;

    if (!$division) {
        return $brand = array(
            'known' => true,
            'is_division' => false,
            'division_id' => 0,
            'division_name' => '',
            'name' => 'Region XI',
            'office' => 'Regional Office XI',
            'area' => 'the Davao Region',
            'place' => 'Region XI - Davao Region',
            'logo' => 'assets/r11-logo.jpg',
            'logo_alt' => 'Department of Education Region XI seal',
        );
    }

    $division_name = trim((string) $division->description);
    $name = 'SDO ' . $division_name;
    $logo = !empty($division->homepage_logo) ? (string) $division->homepage_logo : '';

    return $brand = array(
        'known' => true,
        'is_division' => true,
        'division_id' => (int) $division->id,
        'division_name' => $division_name,
        'name' => $name,
        'office' => $name,
        'area' => $name,
        'place' => 'Schools Division Office of ' . $division_name,
        // The regional seal reads "Region XI", so a division without a logo of
        // its own (or whose file is gone) gets the national DepEd seal instead.
        'logo' => $logo !== '' && is_file(FCPATH . $logo) ? $logo : 'assets/images/report/ke.png',
        'logo_alt' => $name . ' logo',
    );
}
