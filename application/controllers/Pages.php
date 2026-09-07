<?php


class Pages extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Legacy SBM modules are not available in the Learning Gap system.
        // This prevents direct URL access after their menus are removed.
        $method = strtolower((string) $this->router->fetch_method());
        $learning_gap_roles = array('school', 'division', 'division_head', 'region');
        $legacy_sbm_methods = array(
            'district_list', 'school_list_division_only', 'division_checklist_completed_details',
            'division_list', 'school_list_region', 'report_division_submission',
            'report_overall_accomplishments', 'region_checklist_completed_report', 'report_sgc'
        );
        if ($this->session->logged_in && in_array($this->session->position, $learning_gap_roles, true)
            && (strpos($method, 'sbm_') === 0 || strpos($method, 'tapr_') === 0
                || strpos($method, 'tana_') === 0 || in_array($method, $legacy_sbm_methods, true))) {
            show_error('This legacy SBM feature is not available in the Least Learned Competencies Monitoring system.', 403);
        }
    }

    private function is_user_manager()
    {
        return in_array($this->session->position, array('admin', 'division', 'ict'), true);
    }

    private function is_division_user_manager()
    {
        return in_array($this->session->position, array('division', 'ict'), true);
    }

    private function require_user_manager()
    {
        if (!$this->session->logged_in || !$this->is_user_manager()) {
            show_error('You are not authorized to manage users.', 403);
        }
    }

    private function require_division_settings_access()
    {
        if (!$this->session->logged_in || $this->session->position !== 'division') {
            show_error('Only division users can access division settings.', 403);
        }
    }

    private function require_regional_learning_area_access()
    {
        if (!$this->session->logged_in || $this->session->position !== 'region') {
            show_error('Only regional users can manage learning areas and competencies.', 403);
        }
    }

    private function regional_learning_area_division_id($requested_id = 0)
    {
        $divisions = $this->Page_model->regional_divisions((int) $this->session->region);
        if (empty($divisions)) {
            show_error('No divisions are assigned to your regional account.', 403);
        }
        $requested_id = (int) $requested_id;
        foreach ($divisions as $division) {
            if ($requested_id === (int) $division->id) {
                return $requested_id;
            }
        }
        return (int) $divisions[0]->id;
    }

    private function require_division_dashboard_access()
    {
        if (!$this->session->logged_in || $this->session->position !== 'division') {
            show_error('Only division users can access this page.', 403);
        }
    }

    private function require_region_dashboard_access()
    {
        if (!$this->session->logged_in || $this->session->position !== 'region') {
            show_error('Only regional users can access this page.', 403);
        }
    }

    private function build_checklist_report_groups($records)
    {
        $groups = array();

        foreach ($records as $record) {
            $district_name = trim((string) (isset($record->district_name) ? $record->district_name : ''));
            $division_name = trim((string) (isset($record->division_name) ? $record->division_name : ''));
            $district_id = isset($record->district_id) ? (string) $record->district_id : '';
            $division_id = isset($record->division_id) ? (string) $record->division_id : '';

            if ($district_name === '') {
                $district_name = 'Unassigned District';
            }

            if ($division_name === '') {
                $division_name = 'Division';
            }

            $group_key = $division_id . ':' . $district_id . ':' . mb_strtolower($district_name, 'UTF-8');

            if (!isset($groups[$group_key])) {
                $groups[$group_key] = array(
                    'district_name' => $district_name,
                    'division_name' => $division_name,
                    'district_id' => $district_id,
                    'division_id' => $division_id,
                    'records' => array()
                );
            }

            $groups[$group_key]['records'][] = $record;
        }

        foreach ($groups as &$group) {
            usort($group['records'], function ($left, $right) {
                return strcasecmp(
                    trim((string) $left->schoolName),
                    trim((string) $right->schoolName)
                );
            });
        }
        unset($group);

        $group_list = array_values($groups);
        usort($group_list, function ($left, $right) {
            $district_compare = strcasecmp($left['district_name'], $right['district_name']);

            if ($district_compare !== 0) {
                return $district_compare;
            }

            return strcasecmp($left['division_name'], $right['division_name']);
        });

        return $group_list;
    }

    private function build_checklist_filter_options($records, $id_field, $name_field, $fallback_label)
    {
        $options = array();

        foreach ($records as $record) {
            $option_id = trim((string) (isset($record->{$id_field}) ? $record->{$id_field} : ''));
            $option_name = trim((string) (isset($record->{$name_field}) ? $record->{$name_field} : ''));

            if ($option_name === '') {
                $option_name = $fallback_label;
            }

            $option_key = $option_id . ':' . mb_strtolower($option_name, 'UTF-8');

            if (!isset($options[$option_key])) {
                $options[$option_key] = array(
                    'id' => $option_id,
                    'name' => $option_name,
                );
            }
        }

        $option_list = array_values($options);

        usort($option_list, function ($left, $right) {
            return strcasecmp($left['name'], $right['name']);
        });

        return $option_list;
    }

    private function filter_checklist_records($records, $field_name, $selected_value)
    {
        if ($selected_value === '') {
            return $records;
        }

        return array_values(array_filter($records, function ($record) use ($field_name, $selected_value) {
            return trim((string) (isset($record->{$field_name}) ? $record->{$field_name} : '')) === $selected_value;
        }));
    }

    private function get_division_checklist_share_secret()
    {
        $secret = trim((string) config_item('encryption_key'));

        if ($secret !== '') {
            return $secret;
        }

        return hash('sha256', implode('|', array(
            APPPATH,
            FCPATH,
            (string) config_item('base_url'),
            (string) $this->config->item('cookie_prefix'),
            'division-checklist-share',
        )));
    }

    private function build_division_checklist_share_token($division_id, $fy)
    {
        $payload = trim((string) $division_id) . '|' . trim((string) $fy);

        return hash_hmac('sha256', $payload, $this->get_division_checklist_share_secret());
    }

    private function build_division_checklist_share_url($division_id, $fy, $selected_filter = '')
    {
        $query = array(
            'token' => $this->build_division_checklist_share_token($division_id, $fy),
        );
        $selected_filter = trim((string) $selected_filter);

        if ($selected_filter !== '') {
            $query['district_id'] = $selected_filter;
        }

        return base_url(
            'Pages/division_checklist_completed_shared/'
            . rawurlencode((string) $division_id)
            . '/'
            . rawurlencode((string) $fy)
        ) . '?' . http_build_query($query);
    }

    private function validate_division_checklist_share_request($division_id, $fy)
    {
        $token = trim((string) $this->input->get('token', true));

        if ($division_id === '' || $fy === '' || $token === '') {
            show_error('This shared checklist link is invalid.', 403);
        }

        $expected_token = $this->build_division_checklist_share_token($division_id, $fy);

        if (!hash_equals($expected_token, $token)) {
            show_error('This shared checklist link is invalid.', 403);
        }
    }

    private function build_division_checklist_completed_report_data($division_id, $fy, $options = array())
    {
        $division_id = trim((string) $division_id);
        $fy = trim((string) $fy);
        $print_mode = !empty($options['print_mode']);
        $share_mode = !empty($options['share_mode']);
        $share_token = $share_mode
            ? $this->build_division_checklist_share_token($division_id, $fy)
            : '';
        $records = $this->Page_model->division_completed_checklist_report_rows($division_id, $fy);
        $filter_options = $this->build_checklist_filter_options(
            $records,
            'district_id',
            'district_name',
            'Unassigned District'
        );
        $selected_filter = trim((string) $this->input->get('district_id', true));
        $valid_filter_ids = array_map(function ($option) {
            return (string) $option['id'];
        }, $filter_options);

        if ($selected_filter !== '' && !in_array($selected_filter, $valid_filter_ids, true)) {
            $selected_filter = '';
        }

        $filtered_records = $this->filter_checklist_records($records, 'district_id', $selected_filter);
        $query_string = $selected_filter !== ''
            ? '?district_id=' . rawurlencode($selected_filter)
            : '';
        $default_title = $print_mode
            ? 'Division Checklist Completion Printable Version'
            : 'Division Checklist Completion Report';
        $default_hero_description = $print_mode
            ? 'Printable version of finalized Self-Assessment Checklist submissions in your division, grouped by district and arranged alphabetically by school.'
            : 'Review finalized Self-Assessment Checklist submissions in your division, grouped by district and arranged alphabetically by school.';
        $default_back_url = $print_mode
            ? base_url('Pages/division_checklist_completed_details') . $query_string
            : base_url();
        $default_back_label = $print_mode ? 'Back to Interactive Report' : 'Back to Dashboard';
        $default_filter_action_url = $print_mode
            ? base_url('Pages/division_checklist_completed_printable')
            : base_url('Pages/division_checklist_completed_details');
        $default_printable_url = $print_mode
            ? ''
            : base_url('Pages/division_checklist_completed_printable') . $query_string;
        $default_shareable_url = (!$print_mode && !$share_mode)
            ? $this->build_division_checklist_share_url($division_id, $fy, $selected_filter)
            : '';
        $default_filter_reset_url = $default_filter_action_url;
        $default_filter_hidden_fields = array();

        if ($share_mode) {
            $default_title = 'Shared Division Checklist Completion Report';
            $default_hero_description = '';
            $default_back_url = '';
            $default_back_label = '';
            $default_filter_action_url = base_url(
                'Pages/division_checklist_completed_shared/'
                . rawurlencode((string) $division_id)
                . '/'
                . rawurlencode((string) $fy)
            );
            $default_filter_reset_url = $this->build_division_checklist_share_url($division_id, $fy);
            $default_filter_hidden_fields = array(
                'token' => $share_token,
            );
            $default_printable_url = '';
        }

        return array(
            'title' => array_key_exists('title', $options)
                ? (string) $options['title']
                : $default_title,
            'hero_title' => array_key_exists('hero_title', $options)
                ? (string) $options['hero_title']
                : 'Completed Self-Assessment Checklist Report',
            'hero_description' => array_key_exists('hero_description', $options)
                ? (string) $options['hero_description']
                : $default_hero_description,
            'report_scope' => 'division',
            'report_badge' => array_key_exists('report_badge', $options)
                ? (string) $options['report_badge']
                : 'Fiscal Year ' . $fy,
            'back_url' => array_key_exists('back_url', $options)
                ? (string) $options['back_url']
                : $default_back_url,
            'back_label' => array_key_exists('back_label', $options)
                ? (string) $options['back_label']
                : $default_back_label,
            'records' => $filtered_records,
            'district_groups' => $this->build_checklist_report_groups($filtered_records),
            'filter_title' => 'Filter By District',
            'filter_description' => 'Select one district to narrow the completed checklist report for this division.',
            'filter_action_url' => array_key_exists('filter_action_url', $options)
                ? (string) $options['filter_action_url']
                : $default_filter_action_url,
            'filter_reset_url' => array_key_exists('filter_reset_url', $options)
                ? (string) $options['filter_reset_url']
                : $default_filter_reset_url,
            'filter_param' => 'district_id',
            'filter_placeholder' => 'All Districts',
            'filter_icon' => 'mdi-map-marker-multiple',
            'filter_options' => $filter_options,
            'filter_hidden_fields' => array_key_exists('filter_hidden_fields', $options) && is_array($options['filter_hidden_fields'])
                ? $options['filter_hidden_fields']
                : $default_filter_hidden_fields,
            'selected_filter' => $selected_filter,
            'print_mode' => $print_mode,
            'printable_url' => array_key_exists('printable_url', $options)
                ? (string) $options['printable_url']
                : $default_printable_url,
            'share_mode' => $share_mode,
            'shareable_url' => array_key_exists('shareable_url', $options)
                ? (string) $options['shareable_url']
                : $default_shareable_url,
        );
    }

    private function get_division_checklist_completed_report_data($print_mode = false)
    {
        return $this->build_division_checklist_completed_report_data(
            $this->session->division,
            $this->session->fy,
            array(
                'print_mode' => $print_mode,
            )
        );
    }

    private function get_rate_indicator_context($question_key)
    {
        $indicator_number = (int) preg_replace('/\D+/', '', (string) $question_key);
        $indicator = $indicator_number > 0
            ? $this->Page_model->one_cond_row('sbm_sub_indicator', 'i_no', $indicator_number)
            : null;
        $principle = ($indicator && !empty($indicator->priciple_id))
            ? $this->Page_model->one_cond_row('sbm_indicator', 'id', $indicator->priciple_id)
            : null;

        return array(
            'rate_indicator_description' => ($indicator && !empty($indicator->description))
                ? $indicator->description
                : '',
            'rate_indicator_principle' => ($principle && !empty($principle->indicator))
                ? $principle->indicator
                : '',
        );
    }

    private function get_managed_user($id)
    {
        $user = $this->Page_model->one_cond_row('users', 'id', $id);

        if (!$user) {
            show_404();
        }

        if ($this->is_division_user_manager() && (string) $user->p_id !== (string) $this->session->division) {
            show_error('You can only manage users under your division.', 403);
        }

        return $user;
    }

    private function is_allowed_managed_position($position)
    {
        if ($this->session->position === 'admin') {
            return true;
        }

        $position_record = $this->Page_model->one_cond_row('position', 'pos', $position);

        return $position_record && !in_array($position, array('admin', 'region', 'division'), true);
    }

    private function validate_managed_district($district_id)
    {
        if (!$this->is_division_user_manager() || empty($district_id)) {
            return;
        }

        $district = $this->Page_model->one_cond_row('district', 'id', $district_id);

        if (!$district || (string) $district->division_id !== (string) $this->session->division) {
            show_error('The selected district is not under your division.', 403);
        }
    }


    public function view()
    {

        if ($this->session->position == 'admin') {
            $page = "dashboard";
            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');
            $indicator_numbers = array_map(function ($indicator) {
                return (int) $indicator->i_no;
            }, $data['sbm_sub']);

            $data['sbm_sub_by_principle'] = array();
            foreach ($data['sbm_sub'] as $indicator) {
                $data['sbm_sub_by_principle'][(string) $indicator->priciple_id][] = $indicator;
            }

            $region_id = (int) $this->session->region;
            $setup_summary = $this->Page_model->region_division_setup_summary($region_id);

            $data['division_count'] = $this->Page_model->region_division_count($region_id);
            $data['district_count'] = $this->Page_model->region_district_count($region_id);
            $data['registered_school_count'] = $this->Page_model->region_school_count($region_id);
            $data['user_count'] = $this->Page_model->region_user_count($region_id);
            $data['sgc_counts'] = $this->Page_model->region_sgc_counts($region_id);
            $data['sbm_rate_counts'] = $this->Page_model->region_sbm_rate_counts(
                $region_id,
                $this->session->fy,
                $indicator_numbers
            );
            $data['completed_checklist_count'] = $this->Page_model->region_sbm_completed_count(
                $region_id,
                $this->session->fy
            );
            $data['encoded_total_schools'] = isset($setup_summary['encoded_total_schools'])
                ? (int) $setup_summary['encoded_total_schools']
                : 0;
            $data['configured_division_count'] = isset($setup_summary['configured_division_count'])
                ? (int) $setup_summary['configured_division_count']
                : 0;
            $data['signup_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['registered_school_count'] / $data['encoded_total_schools']) * 100
                : 0;
            $data['checklist_completion_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['completed_checklist_count'] / $data['encoded_total_schools']) * 100
                : 0;
            $data['title'] = "Dashboard";
        } elseif ($this->session->position == 'division' || $this->session->position == 'division_head') {
            $page = "dashboard_scope_learning_gap";
            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');
            $indicator_numbers = array_map(function ($indicator) {
                return (int) $indicator->i_no;
            }, $data['sbm_sub']);

            $data['sbm_sub_by_principle'] = array();
            foreach ($data['sbm_sub'] as $indicator) {
                $data['sbm_sub_by_principle'][(string) $indicator->priciple_id][] = $indicator;
            }

            $data['sgc_counts'] = $this->Page_model->division_sgc_counts($this->session->division);
            $data['sbm_rate_counts'] = $this->Page_model->division_sbm_rate_counts(
                $this->session->division,
                $this->session->fy,
                $indicator_numbers
            );
            $data['district_count'] = count(
                $this->Page_model->one_cond('district', 'division_id', $this->session->division)
            );
            $data['division'] = $this->Page_model->get_division_setup($this->session->division);
            $data['registered_school_count'] = $this->Page_model->division_school_count($this->session->division);
            $data['completed_checklist_count'] = $this->Page_model->division_sbm_completed_count(
                $this->session->division,
                $this->session->fy
            );
            $data['encoded_total_schools'] = !empty($data['division']->total_schools)
                ? (int) $data['division']->total_schools
                : 0;
            $data['signup_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['registered_school_count'] / $data['encoded_total_schools']) * 100
                : 0;
            $data['checklist_completion_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['completed_checklist_count'] / $data['encoded_total_schools']) * 100
                : 0;

            $data['title'] = "Dashboard";
        } elseif ($this->session->position == 'region') {
            $page = "dashboard_scope_learning_gap";
            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');
            $indicator_numbers = array_map(function ($indicator) {
                return (int) $indicator->i_no;
            }, $data['sbm_sub']);

            $data['sbm_sub_by_principle'] = array();
            foreach ($data['sbm_sub'] as $indicator) {
                $data['sbm_sub_by_principle'][(string) $indicator->priciple_id][] = $indicator;
            }

            $region_id = (int) $this->session->region;
            $setup_summary = $this->Page_model->region_division_setup_summary($region_id);

            $data['division_count'] = $this->Page_model->region_division_count($region_id);
            $data['district_count'] = $this->Page_model->region_district_count($region_id);
            $data['registered_school_count'] = $this->Page_model->region_school_count($region_id);
            $data['user_count'] = $this->Page_model->region_user_count($region_id);
            $data['sgc_counts'] = $this->Page_model->region_sgc_counts($region_id);
            $data['sbm_rate_counts'] = $this->Page_model->region_sbm_rate_counts(
                $region_id,
                $this->session->fy,
                $indicator_numbers
            );
            $data['completed_checklist_count'] = $this->Page_model->region_sbm_completed_count(
                $region_id,
                $this->session->fy
            );
            $data['encoded_total_schools'] = isset($setup_summary['encoded_total_schools'])
                ? (int) $setup_summary['encoded_total_schools']
                : 0;
            $data['configured_division_count'] = isset($setup_summary['configured_division_count'])
                ? (int) $setup_summary['configured_division_count']
                : 0;
            $data['signup_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['registered_school_count'] / $data['encoded_total_schools']) * 100
                : 0;
            $data['checklist_completion_percentage'] = $data['encoded_total_schools'] > 0
                ? ($data['completed_checklist_count'] / $data['encoded_total_schools']) * 100
                : 0;

            $data['title'] = "Dashboard";
        } elseif ($this->session->position == 'district') {
            $page = "dashboard_district";
            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');
            $indicator_numbers = array_map(function ($indicator) {
                return (int) $indicator->i_no;
            }, $data['sbm_sub']);

            $data['sbm_sub_by_principle'] = array();
            foreach ($data['sbm_sub'] as $indicator) {
                $data['sbm_sub_by_principle'][(string) $indicator->priciple_id][] = $indicator;
            }

            $district_id = (int) $this->session->district;
            $data['district'] = $this->Page_model->one_cond_row('district', 'id', $district_id);
            $data['division'] = $this->Page_model->one_cond_row('division', 'id', $this->session->division);
            $data['school_total'] = $this->Page_model->district_school_count($district_id);
            $data['sgc_counts'] = $this->Page_model->district_sgc_counts($district_id);
            $data['checklist_submission_count'] = $this->Page_model->district_submission_count(
                'sbm',
                $district_id,
                $this->session->fy
            );
            $data['ta_submission_count'] = $this->Page_model->district_submission_count(
                'sbm_ta',
                $district_id,
                $this->session->fy
            );
            $data['action_plan_submission_count'] = $this->Page_model->district_submission_count(
                'sgod_action_plan',
                $district_id,
                $this->session->fy
            );
            $data['completed_checklist_count'] = $this->Page_model->district_sbm_completed_count(
                $district_id,
                $this->session->fy
            );
            $data['tech_entry_count'] = $this->Page_model->district_tech_entry_count(
                $district_id,
                $this->session->fy
            );
            $data['sbm_rate_counts'] = $this->Page_model->district_sbm_rate_counts(
                $district_id,
                $this->session->fy,
                $indicator_numbers
            );

            $data['title'] = "Dashboard";
        } else {
            $page = "dashboard_school_learning_gap";
            $data['title'] = "Dashboard";
        }

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    /**
     * Learning Gap Monitoring form and role-scoped summaries.
     * School users encode only their own records. Division and regional users
     * can review aggregate data within the organisational scope of their account.
     */
    public function learning_gap($entry_mode = false)
    {
        if (!$this->session->logged_in) {
            redirect(base_url('log_in'));
        }

        $position = (string) $this->session->position;
        if (!in_array($position, array('school', 'division', 'division_head', 'region', 'admin'), true)) {
            show_error('You are not authorized to access Learning Gap Monitoring.', 403);
        }

        $scope = array('type' => 'school', 'id' => (string) $this->session->username);
        if (in_array($position, array('division', 'division_head'), true)) {
            $scope = array('type' => 'division', 'id' => (int) $this->session->division);
        } elseif (in_array($position, array('region', 'admin'), true)) {
            $scope = array('type' => 'region', 'id' => (int) $this->session->region);
        }

        $data['title'] = 'Learning Gap Monitoring';
        $data['entry_mode'] = (bool) $entry_mode;
        $data['scope'] = $scope;
        $data['is_school'] = $position === 'school';
        $data['grade_filter'] = trim((string) $this->input->get('grade_level', true));
        $data['records'] = $this->Page_model->learning_gap_records($scope);
        if ($data['grade_filter'] !== '') {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                return (string) $record->grade_level === $data['grade_filter'];
            }));
        }
        $data['summary'] = $this->Page_model->learning_gap_summary($scope);
        $data['learning_area_filter'] = $scope['type'] === 'division'
            ? trim((string) $this->input->get('learning_area', true))
            : '';
        $data['term_filter'] = $scope['type'] === 'division'
            ? trim((string) $this->input->get('term', true))
            : '';
        $data['learning_area_filters'] = $scope['type'] === 'division'
            ? $this->Page_model->division_learning_area_options((int) $scope['id'])
            : array();
        $data['has_ranking_filter'] = $scope['type'] === 'division'
            && $data['learning_area_filter'] !== '' && $data['term_filter'] !== '';
        $data['competency_ranking'] = $data['has_ranking_filter']
            ? $this->Page_model->learning_gap_competency_ranking(
                (int) $scope['id'],
                $data['learning_area_filter'],
                $data['term_filter']
            )
            : array();
        $data['division_summary'] = $scope['type'] === 'region'
            ? $this->Page_model->learning_gap_division_summary($scope['id'])
            : array();
        $data['school'] = $data['is_school']
            ? $this->Common->one_cond_row('schools', 'schoolID', $this->session->username)
            : null;
        $data['division'] = $data['school'] && !empty($data['school']->division_id)
            ? $this->Page_model->one_cond_row('division', 'id', $data['school']->division_id)
            : null;
        $data['district'] = $data['school'] && !empty($data['school']->district_id)
            ? $this->Page_model->one_cond_row('district', 'id', $data['school']->district_id)
            : null;
        $data['edit_record'] = null;
        $edit_id = (int) $this->input->get('edit', true);
        if ($data['is_school'] && $edit_id > 0 && !$data['entry_mode']) {
            redirect('Pages/learning_gap_entry?edit=' . $edit_id);
            return;
        }
        if ($data['is_school'] && $edit_id > 0) {
            $data['edit_record'] = $this->Page_model->learning_gap_record($edit_id, (string) $this->session->username);
            if (!$data['edit_record']) {
                $this->session->set_flashdata('danger', 'The selected record was not found in your school account.');
                redirect('Pages/learning_gap');
                return;
            }
        }

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/learning_gap', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function learning_gap_entry()
    {
        if (!$this->session->logged_in || $this->session->position !== 'school') {
            show_error('Only school accounts can access Learning Gap data entry.', 403);
        }
        $this->learning_gap(true);
    }

    /** Display submitted learning-gap records separately from the summary page. */
    public function learning_gap_records()
    {
        if (!$this->session->logged_in) {
            redirect(base_url('log_in'));
        }

        $position = (string) $this->session->position;
        if (!in_array($position, array('school', 'division', 'division_head', 'region', 'admin'), true)) {
            show_error('You are not authorized to access learning gap records.', 403);
        }

        $scope = array('type' => 'school', 'id' => (string) $this->session->username);
        if (in_array($position, array('division', 'division_head'), true)) {
            $scope = array('type' => 'division', 'id' => (int) $this->session->division);
        } elseif (in_array($position, array('region', 'admin'), true)) {
            $scope = array('type' => 'region', 'id' => (int) $this->session->region);
        }

        $data['title'] = 'Submitted Learning Gap Records';
        $data['is_school'] = $position === 'school';
        $data['grade_filter'] = trim((string) $this->input->get('grade_level', true));
        $data['division_filter'] = $scope['type'] === 'region'
            ? (int) $this->input->get('division_id', true)
            : 0;
        $data['learning_area_filter'] = trim((string) $this->input->get('learning_area', true));
        $data['term_filter'] = trim((string) $this->input->get('term', true));
        $data['competency_filter'] = trim((string) $this->input->get('competency', true));
        $data['has_record_filters'] = $data['division_filter'] > 0
            || $data['grade_filter'] !== ''
            || $data['learning_area_filter'] !== ''
            || $data['term_filter'] !== ''
            || $data['competency_filter'] !== '';
        $data['records'] = $this->Page_model->learning_gap_records($scope);
        if ($data['division_filter'] > 0) {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                return (int) $record->division_id === $data['division_filter'];
            }));
        }
        if ($data['grade_filter'] !== '') {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                return (string) $record->grade_level === $data['grade_filter'];
            }));
        }
        if ($data['learning_area_filter'] !== '') {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                return (string) $record->learning_area === $data['learning_area_filter'];
            }));
        }
        if ($data['term_filter'] !== '') {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                return (string) $record->term === $data['term_filter'];
            }));
        }
        if ($data['competency_filter'] !== '') {
            $data['records'] = array_values(array_filter($data['records'], function ($record) use ($data) {
                $competencies = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $record->least_learned_competency)));
                return in_array($data['competency_filter'], $competencies, true);
            }));
        }

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/learning_gap_records', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    /** Division-only school submission coverage for Learning Gap Monitoring. */
    public function school_submission_monitoring()
    {
        if (!$this->session->logged_in || $this->session->position !== 'division') {
            show_error('Only division users can access school submission monitoring.', 403);
        }

        $data['title'] = 'School Submission Monitoring';
        $data['schools'] = $this->Page_model->learning_gap_school_submission_summary(
            (int) $this->session->division
        );
        $data['submitted_school_count'] = count(array_filter($data['schools'], function ($school) {
            return (int) $school->record_count > 0;
        }));
        $data['total_school_count'] = count($data['schools']);
        $data['division'] = $this->Page_model->one_cond_row(
            'division',
            'id',
            (int) $this->session->division
        );

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/school_submission_monitoring', $data);
        $this->load->view('templates/footer_dt');
    }

    public function learning_gap_save()
    {
        if (!$this->session->logged_in || $this->session->position !== 'school') {
            show_error('Only school users can encode learning gap records.', 403);
        }

        $this->form_validation->set_rules('grade_level', 'Grade Level', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('learning_area', 'Learning Area / Subject', 'trim|required|max_length[150]');
        $this->form_validation->set_rules('term', 'Trimester', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('class_proficiency_level', 'Class Proficiency Level', 'trim|numeric|greater_than_equal_to[0]|less_than_equal_to[100]');
        $this->form_validation->set_rules('proficiency_level', 'Proficiency Level', 'trim|max_length[100]');
        $this->form_validation->set_rules('learners_assessed', 'Number of Learners Assessed', 'trim|required|integer|greater_than_equal_to[0]');
        $this->form_validation->set_rules('learners_with_gap', 'Number of Learners with Learning Gap', 'trim|required|integer|greater_than_equal_to[0]');

        $selected_competencies = $this->input->post('least_learned_competency', true);
        $has_competency = is_array($selected_competencies)
            ? count(array_filter($selected_competencies, function ($competency) {
                return trim((string) $competency) !== '';
            })) > 0
            : trim((string) $selected_competencies) !== '';
        if ($this->form_validation->run() === FALSE || !$has_competency) {
            $message = validation_errors('', ' ');
            if (!$has_competency) {
                $message .= ' Select at least one least learned competency.';
            }
            $this->session->set_flashdata('danger', trim($message));
            redirect('Pages/learning_gap');
            return;
        }

        $assessed = (int) $this->input->post('learners_assessed', true);
        $with_gap = (int) $this->input->post('learners_with_gap', true);
        if ($with_gap > $assessed) {
            $this->session->set_flashdata('danger', 'Learners with a learning gap cannot exceed learners assessed.');
            redirect('Pages/learning_gap');
            return;
        }

        $is_update = (int) $this->input->post('record_id', true) > 0;
        if (!$this->Page_model->save_learning_gap_record((string) $this->session->username)) {
            $this->session->set_flashdata('danger', 'Your school profile is not set up. Please contact your division administrator.');
            redirect('Pages/learning_gap');
            return;
        }
        $this->session->set_flashdata('success', $is_update
            ? 'Learning gap record updated for your school account.'
            : 'Learning gap record saved for your school account.');
        redirect('Pages/learning_gap');
    }

    public function learning_gap_delete($id = 0)
    {
        if (!$this->session->logged_in || $this->session->position !== 'school') {
            show_error('Only school users can remove learning gap records.', 403);
        }

        $this->Page_model->delete_learning_gap_record((int) $id, (string) $this->session->username);
        $this->session->set_flashdata('success', 'Learning gap record removed.');
        redirect('Pages/learning_gap');
    }

    public function learning_area_setup()
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->get('division_id', true));
        $data['title'] = 'Learning Area Setup';
        $data['divisions'] = $this->Page_model->regional_divisions((int) $this->session->region);
        $data['selected_division_id'] = $division_id;
        $data['areas'] = $this->Page_model->learning_area_settings($division_id);
        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/learning_area_setup', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function learning_area_setup_save()
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->post('division_id', true));
        $this->form_validation->set_rules('grade_level', 'Grade Level', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('learning_area', 'Learning Area', 'trim|required|max_length[150]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('danger', validation_errors('', ' '));
        } elseif ($this->Page_model->add_learning_area_setting($division_id)) {
            $this->session->set_flashdata('success', 'Learning area added to the grade-level setup.');
        } else {
            $this->session->set_flashdata('danger', 'That learning area is already set up for the selected grade level.');
        }
        redirect('Pages/learning_area_setup?division_id=' . $division_id);
    }

    public function learning_area_setup_delete($id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->get('division_id', true));
        $this->Page_model->delete_learning_area_setting((int) $id, $division_id);
        $this->session->set_flashdata('success', 'Learning area removed from this division setup.');
        redirect('Pages/learning_area_setup?division_id=' . $division_id);
    }

    public function learning_competency_setup($area_id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->get('division_id', true));
        $data['area'] = $this->Page_model->learning_area_setting((int) $area_id, $division_id);
        if (!$data['area']) {
            show_404();
        }
        $data['title'] = 'Learning Competencies';
        $data['competencies'] = $this->Page_model->learning_competencies(
            (int) $this->session->region,
            (string) $data['area']->grade_level,
            (string) $data['area']->learning_area
        );
        $data['view_only'] = false;
        $data['selected_division_id'] = $division_id;
        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/learning_competency_setup', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function learning_competencies_view($area_id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->get('division_id', true));
        $data['area'] = $this->Page_model->learning_area_setting((int) $area_id, $division_id);
        if (!$data['area']) {
            show_404();
        }
        $data['title'] = 'Learning Competencies';
        $data['competencies'] = $this->Page_model->learning_competencies(
            (int) $this->session->region,
            (string) $data['area']->grade_level,
            (string) $data['area']->learning_area
        );
        $data['view_only'] = true;
        $data['selected_division_id'] = $division_id;
        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/learning_competency_setup', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function learning_competency_setup_save($area_id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->post('division_id', true));
        $area = $this->Page_model->learning_area_setting((int) $area_id, $division_id);
        if (!$area) {
            show_404();
        }
        $this->form_validation->set_rules('competency', 'Learning Competency', 'trim|required|max_length[1000]');
        $this->form_validation->set_rules('term', 'Term', 'trim|required|in_list[Term 1,Term 2,Term 3]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('danger', validation_errors('', ' '));
        } elseif ($this->Page_model->add_learning_competency((int) $this->session->region, $area)) {
            $this->session->set_flashdata('success', 'Learning competency added.');
        } else {
            $this->session->set_flashdata('danger', 'That learning competency is already added for the selected term.');
        }
        redirect('Pages/learning_competency_setup/' . (int) $area->id . '?division_id=' . $division_id);
    }

    public function learning_competency_setup_delete($area_id = 0, $competency_id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->get('division_id', true));
        $area = $this->Page_model->learning_area_setting((int) $area_id, $division_id);
        if (!$area) {
            show_404();
        }
        $this->Page_model->delete_learning_competency((int) $competency_id, (int) $this->session->region);
        $this->session->set_flashdata('success', 'Learning competency removed.');
        redirect('Pages/learning_competency_setup/' . (int) $area->id . '?division_id=' . $division_id);
    }

    public function learning_competency_setup_update_term($area_id = 0, $competency_id = 0)
    {
        $this->require_regional_learning_area_access();
        $division_id = $this->regional_learning_area_division_id($this->input->post('division_id', true));
        $area = $this->Page_model->learning_area_setting((int) $area_id, $division_id);
        if (!$area) {
            show_404();
        }

        $this->form_validation->set_rules('term', 'Term', 'trim|required|in_list[Term 1,Term 2,Term 3]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('danger', validation_errors('', ' '));
        } elseif ($this->Page_model->update_learning_competency_term((int) $competency_id, (int) $this->session->region)) {
            $this->session->set_flashdata('success', 'Learning competency term updated.');
        } else {
            $this->session->set_flashdata('danger', 'The learning competency could not be updated.');
        }
        redirect('Pages/learning_competency_setup/' . (int) $area->id . '?division_id=' . $division_id);
    }

    public function learning_area_options()
    {
        if (!$this->session->logged_in || $this->session->position !== 'school') {
            show_error('Only school accounts can request learning area options.', 403);
        }
        $school = $this->Common->one_cond_row('schools', 'schoolID', $this->session->username);
        $grade = trim((string) $this->input->get('grade_level', true));
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(array(
                'areas' => $school && $grade !== ''
                    ? $this->Page_model->learning_area_options((int) $school->division_id, $grade)
                    : array()
            )));
    }

    public function learning_competency_options()
    {
        if (!$this->session->logged_in || $this->session->position !== 'school') {
            show_error('Only school accounts can request learning competency options.', 403);
        }
        $school = $this->Common->one_cond_row('schools', 'schoolID', $this->session->username);
        $grade = trim((string) $this->input->get('grade_level', true));
        $area = trim((string) $this->input->get('learning_area', true));
        $term = trim((string) $this->input->get('term', true));
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(array(
                'competencies' => $school && $grade !== '' && $area !== '' && $term !== ''
                    ? $this->Page_model->learning_competency_options((int) $school->region_id, $grade, $area, $term)
                    : array()
            )));
    }


    public function profilelist()
    {

        $page = "profile_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Profile List";

        $data['data'] = $this->Page_model->no_cond('profile');

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function qr()
    {

        $page = "qr";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "QR Code";

        $data['data'] = $this->Page_model->one_cond_row('profile', 'id', $this->uri->segment(3));

        $this->load->view('pages/' . $page, $data);
    }

    public function verify()
    {

        $page = "qr_verify";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "QR Code";

        $data['data'] = $this->Page_model->one_cond_row('profile', 'id', $this->uri->segment(3));

        $this->load->view('pages/' . $page, $data);
    }

    public function profile_new()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('name', 'Fullname', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "profile_add";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "New Entry";


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->profile_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/profilelist');
        }
    }



    // user settings area //

    public function userlist()
    {
        $this->require_user_manager();

        if ($this->is_division_user_manager()) {
            redirect(base_url() . 'pages/userlist_division');
        }

        $page = "user_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "User List";
        $data['division_scope'] = false;

        // Fetch all users for client-side DataTables
        $data['users'] = $this->Page_model->no_cond('users');

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function userlist_ajax()
    {
        // Bypass CSRF validation for AJAX requests
        $this->config->set_item('csrf_protection', false);

        error_log("userlist_ajax called - CSRF bypassed");

        $this->require_user_manager();

        error_log("userlist_ajax - user manager check passed");

        // DataTables parameters
        $draw = $this->input->post('draw');
        $start = $this->input->post('start');
        $length = $this->input->post('length');
        $search = $this->input->post('search')['value'];
        $order = $this->input->post('order');
        $columns = $this->input->post('columns');

        error_log("userlist_ajax called - draw: $draw, start: $start, length: $length, search: $search");

        // Build query
        $this->db->select('id, fname, mname, lname, username, position');

        // Search
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('fname', $search);
            $this->db->or_like('mname', $search);
            $this->db->or_like('lname', $search);
            $this->db->or_like('username', $search);
            $this->db->or_like('position', $search);
            $this->db->group_end();
        }

        // Get total records before filtering
        $total_records = $this->db->count_all_results('users', false);
        error_log("Total records: $total_records");

        // Order
        if (!empty($order)) {
            $column_index = $order[0]['column'];
            $column_name = $columns[$column_index]['data'];
            $direction = $order[0]['dir'];

            // Map column names to database fields
            $column_map = array(
                'account' => 'lname',
                'username' => 'username',
                'position' => 'position'
            );

            if (isset($column_map[$column_name])) {
                $this->db->order_by($column_map[$column_name], $direction);
            }
        } else {
            $this->db->order_by('lname, fname', 'ASC');
        }

        // Get filtered records count
        $filtered_records = $this->db->count_all_results('', false);
        error_log("Filtered records: $filtered_records");

        // Apply pagination
        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        // Get data
        $data = $this->db->get('users')->result();
        error_log("Data count: " . count($data));

        // Format data for DataTables
        $formatted_data = array();
        foreach ($data as $row) {
            $display_name = mb_convert_case(
                trim((!empty($row->lname) ? $row->lname . ', ' : '') . $row->fname . (!empty($row->mname) ? ' ' . substr($row->mname, 0, 1) . '.' : '')),
                MB_CASE_TITLE,
                'UTF-8'
            );
            $initials = strtoupper(substr($row->fname, 0, 1) . (!empty($row->lname) ? substr($row->lname, 0, 1) : ''));

            $formatted_data[] = array(
                'account' => $display_name,
                'initials' => $initials,
                'username' => $row->username,
                'position' => $row->position,
                'id' => $row->id
            );
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $formatted_data
        );

        error_log("Response: " . json_encode($response));

        echo json_encode($response);

        // Re-enable CSRF protection
        $this->config->set_item('csrf_protection', true);
    }

    public function userlist_division()
    {
        $this->require_user_manager();

        $page = "user_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Division User List";
        $data['division_scope'] = true;

        $data['users'] = $this->Page_model->one_cond('users','p_id',$this->session->division);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_by_district()
    {

        $page = "school_list_by_district";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Division List";

        // Get divisions with total_schools field
        $divisions = $this->Common->one_cond_select('division', 'description,id,total_schools', 'region_id', $this->session->region);

        // Calculate signup statistics for each division
        foreach ($divisions as $division) {
            // Count signed up schools for this division
            $this->db->where('division_id', $division->id);
            $signed_up_count = $this->db->count_all_results('schools');

            $total_schools = $division->total_schools ? (int) $division->total_schools : 0;

            // Calculate percentages
            if ($total_schools > 0) {
                $signup_percentage = round(($signed_up_count / $total_schools) * 100, 1);
                $not_signup_percentage = round((($total_schools - $signed_up_count) / $total_schools) * 100, 1);
            } else {
                $signup_percentage = 0;
                $not_signup_percentage = 0;
            }

            $division->signed_up_count = $signed_up_count;
            $division->signup_percentage = $signup_percentage;
            $division->not_signup_percentage = $not_signup_percentage;
        }

        $data['data'] = $divisions;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function update_total_schools()
    {
        // Bypass CSRF validation for AJAX requests
        $this->config->set_item('csrf_protection', false);

        $division_id = $this->input->post('division_id');
        $total_schools = $this->input->post('total_schools');

        error_log("update_total_schools called - division_id: " . $division_id . ", total_schools: " . $total_schools);
        error_log("POST data: " . print_r($_POST, true));

        if ($division_id && $total_schools !== null) {
            $this->db->where('id', $division_id);
            $result = $this->db->update('division', array('total_schools' => $total_schools));

            error_log("Update result: " . ($result ? "success" : "failed"));
            error_log("Affected rows: " . $this->db->affected_rows());

            if ($result) {
                echo json_encode(array('success' => true));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Database update failed'));
            }
        } else {
            error_log("Invalid parameters - division_id: " . ($division_id ? 'set' : 'not set') . ", total_schools: " . ($total_schools !== null ? 'set' : 'not set'));
            echo json_encode(array('success' => false, 'message' => 'Invalid parameters'));
        }

        // Re-enable CSRF protection
        $this->config->set_item('csrf_protection', true);
    }

    public function schools()
    {
        $page = "schools";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        $data['data'] = $this->Common->two_join_one_cond_not_gb('schools','district', 'a.recID,id,description,schoolID,schoolName,a.division_id,a.district_id','a.district_id = id', 'a.division_id', $this->uri->segment(3),'schoolName','ASC');

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function schools_district()
    {
        $page = "schools";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        $data['data'] = $this->Common->two_join_one_cond_not_gb('schools','district', 'a.recID,id,description,schoolID,schoolName,a.division_id,a.district_id','a.district_id = id', 'a.district_id', $this->uri->segment(3),'schoolName','ASC');

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function schools_division()
    {
        $page = "schools";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        $data['data'] = $this->Common->two_join_one_cond_not_gb('schools','district', 'a.recID,id,description,schoolID,schoolName,a.division_id,a.district_id','a.district_id = id', 'a.division_id', $this->uri->segment(3),'schoolName','ASC');

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function sbm_rate_list()
    {
        $page = "sbm_list_rate";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";
        $fy  = $this->session->fy;
        $q   = $this->uri->segment(3);
        $val = $this->uri->segment(4);
        $district = $this->session->district;

        if (!preg_match('/^q([1-9]|[1-3][0-9]|4[0-2])$/', $q) || !in_array((int) $val, array(1, 2, 3, 4), true)) {
            show_404();
        }

        $data['data'] = $this->db
            ->select("CAST(a.school_id AS CHAR) AS school_id, COALESCE(MAX(NULLIF(TRIM(b.schoolID), '')), CAST(a.school_id AS CHAR)) AS schoolID, MAX(b.division_id) AS division_id, COALESCE(MAX(NULLIF(TRIM(b.schoolName), '')), '') AS schoolName", false)
            ->from('sbm a')
            ->join('schools b', 'TRIM(CAST(a.school_id AS CHAR)) = TRIM(b.schoolID)', 'left', false)
            ->where('a.fy', $fy)
            ->where('a.' . $q, $val)
            ->where('b.district_id', $district)
            ->group_by('a.school_id')
            ->order_by('schoolName', 'ASC')
            ->get()
            ->result();
        $data['rate_scope'] = 'district';
        $data['rate_question'] = $q;
        $data['rate_value'] = (int) $val;
        $data = array_merge($data, $this->get_rate_indicator_context($q));
        $data['division_names'] = $this->Page_model->division_names_by_ids(array_map(function ($row) {
            return $row->division_id;
        }, $data['data']));

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    function data_privacy()
    {
        $page = "pages/data_privacy";

        if (!file_exists(APPPATH . 'views/' . $page . '.php')) {
            show_404();
        }
        $data['title'] = "Data Privacy";
        $this->load->view($page, $data);
    }
    public function sbm_rate_list_division()
    {
        $page = "sbm_list_rate";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";
        $fy  = $this->session->fy;
        $q   = $this->uri->segment(3);
        $val = $this->uri->segment(4);
        $division = $this->session->division;

        if (!preg_match('/^q([1-9]|[1-3][0-9]|4[0-2])$/', $q) || !in_array((int) $val, array(1, 2, 3, 4), true)) {
            show_404();
        }

        $data['data'] = $this->db
            ->select("CAST(a.school_id AS CHAR) AS school_id, COALESCE(MAX(NULLIF(TRIM(b.schoolID), '')), CAST(a.school_id AS CHAR)) AS schoolID, MAX(b.division_id) AS division_id, COALESCE(MAX(NULLIF(TRIM(b.schoolName), '')), '') AS schoolName", false)
            ->from('sbm a')
            ->join('schools b', 'TRIM(CAST(a.school_id AS CHAR)) = TRIM(b.schoolID)', 'left', false)
            ->where('a.fy', $fy)
            ->where('a.' . $q, $val)
            ->where('a.division', $division)
            ->where('a.stat', 1)
            ->group_by('a.school_id')
            ->order_by('schoolName', 'ASC')
            ->get()
            ->result();
        $data['rate_scope'] = 'division';
        $data['rate_question'] = $q;
        $data['rate_value'] = (int) $val;
        $data = array_merge($data, $this->get_rate_indicator_context($q));
        $division_row = $this->Page_model->one_cond_row('division', 'id', $division);
        $data['division_names'] = array(
            (string) $division => $division_row ? $division_row->description : 'Division'
        );

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function sbm_rate_list_region()
    {
        $page = "sbm_list_rate";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";
        $fy  = $this->session->fy;
        $q   = $this->uri->segment(3);
        $val = $this->uri->segment(4);
        $region = $this->session->region;

        if (!preg_match('/^q([1-9]|[1-3][0-9]|4[0-2])$/', $q) || !in_array((int) $val, array(1, 2, 3, 4), true)) {
            show_404();
        }

        $data['data'] = $this->db
            ->select("CAST(a.school_id AS CHAR) AS school_id, COALESCE(MAX(NULLIF(TRIM(b.schoolID), '')), CAST(a.school_id AS CHAR)) AS schoolID, MAX(b.division_id) AS division_id, COALESCE(MAX(NULLIF(TRIM(b.schoolName), '')), '') AS schoolName", false)
            ->from('sbm a')
            ->join('schools b', 'TRIM(CAST(a.school_id AS CHAR)) = TRIM(b.schoolID)', 'left', false)
            ->where('a.fy', $fy)
            ->where('a.' . $q, $val)
            ->where('a.region', $region)
            ->where('a.stat', 1)
            ->group_by('a.school_id')
            ->order_by('schoolName', 'ASC')
            ->get()
            ->result();
        error_log("sbm_rate_list_region - fy: $fy, q: $q, val: $val, region: $region, count: " . count($data['data']));
        $data['rate_scope'] = 'region';
        $data['rate_question'] = $q;
        $data['rate_value'] = (int) $val;
        $data = array_merge($data, $this->get_rate_indicator_context($q));
        $data['division_names'] = $this->Page_model->division_names_by_ids(array_map(function ($row) {
            return $row->division_id;
        }, $data['data']));

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function user_new()
    {
        $this->require_user_manager();

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules('gender', 'Gender', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "user_add";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "New User";
            $data['division'] = $this->Page_model->one_cond('division', 'region_id', 12);
            if ($this->is_division_user_manager()) {
                $data['pos'] = array_values(array_filter(
                    $this->Page_model->no_cond('position'),
                    function ($position) {
                        return $this->is_allowed_managed_position($position->pos);
                    }
                ));
                $data['districts'] = $this->Page_model->one_cond('district', 'division_id', $this->session->division);
            }else{
                $data['pos'] = $this->Page_model->no_cond('position');  
            }


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $fname = $this->input->post('fname');
            $lname = $this->input->post('lname');
            $username = $this->input->post('username');
            $position = $this->input->post('position');

            if (!$this->is_allowed_managed_position($position)) {
                show_error('You are not authorized to create this type of user.', 403);
            }

            $this->validate_managed_district($this->input->post('d_id'));

            $config['allowed_types'] = 'jpg|png';
            $config['upload_path'] = './uploads/';
            $this->load->library('upload', $config);

            $this->upload->do_upload('file');
            $check = $this->Page_model->check_dup_user($fname, $lname, $username);
            if ($check->num_rows() >= 1) {
                $this->session->set_flashdata('danger', 'Dubplicate Entry.');
                redirect(base_url() . 'pages/user_new');
            } else {
                $this->Page_model->user_insert();
                $this->session->set_flashdata('success', 'Successfully saved.');
                redirect(base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist'));
            }
        }
    }
    public function user_update()
    {
        $this->require_user_manager();

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('gender', 'Gender', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "user_edit";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Update User";
            $data['data'] = $this->get_managed_user($this->uri->segment(3));


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->get_managed_user($this->input->post('id'));
            $this->Page_model->user_update();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist'));
        }
    }

    public function user_delete()
    {
        $this->require_user_manager();

        $id = $this->uri->segment(3);
        $user = $this->get_managed_user($id);

        if ((string) $user->id === (string) $this->session->id) {
            $this->session->set_flashdata('danger', 'You cannot delete your own account.');
            redirect(base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist'));
        }

        $this->Page_model->delete_with_attach('users', $id, $user->image);
        $this->session->set_flashdata('danger', 'Successfully deleted.');
        redirect(base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist'));
    }

    public function school_delete()
    {
        if (!$this->session->logged_in || ($this->session->position !== 'admin' && $this->session->position !== 'division')) {
            show_error('Only admin and division users can delete schools.', 403);
        }

        $rec_id = $this->uri->segment(3);

        if (empty($rec_id)) {
            show_404();
        }

        $school = $this->Page_model->one_cond_row('schools', 'recID', $rec_id);

        if (!$school) {
            show_404();
        }

        $school_id = $school->schoolID;

        // For division users, verify school belongs to their division
        if ($this->session->position === 'division' && $school->division_id != $this->session->division) {
            show_error('You can only delete schools from your own division.', 403);
        }

        // Check if school has completed Self-Assessment and Action Plan
        $has_action_plan = $this->Page_model->submission_school_ids('sgod_action_plan', $this->session->fy, [$school_id]);
        $has_self_assessment = $this->Page_model->submission_school_ids('sbm', $this->session->fy, [$school_id]);

        if (!empty($has_action_plan[$school_id]) && !empty($has_self_assessment[$school_id])) {
            $this->session->set_flashdata('danger', 'Cannot delete school: School has completed Self-Assessment and Action Plan.');
            redirect(base_url() . 'pages/school_list');
        }

        // Log audit trail before deletion
        $this->Page_model->log_audit_trail('DELETE', 'schools', $school_id, $school, null);

        // Delete school and associated user
        $this->db->where('schoolID', $school_id);
        $this->db->delete('schools');
        $this->db->where('username', $school_id);
        $this->db->delete('users');

        $this->session->set_flashdata('success', 'Successfully deleted.');
        redirect(base_url() . 'pages/school_list');
    }

    public function confirm_signup()
    {

        $user = $this->Page_model->confirm_signup();
        $this->session->set_flashdata('success', 'Successfully Confirmed.');
        redirect(base_url() . 'pages/log_in');
    }

    public function cp()
    {
        $this->require_user_manager();
        $this->get_managed_user($this->input->post('id'));
        $this->Page_model->user_pass();
        $this->session->set_flashdata('success', 'Successfully updated.');
        redirect(base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist'));
    }

    public function user_reset_password()
    {
        $this->require_user_manager();

        $id = $this->input->post('id');
        $user = $this->get_managed_user($id);
        $password = $this->Page_model->random_password();
        $redirect_url = base_url() . ($this->is_division_user_manager() ? 'pages/userlist_division' : 'pages/userlist');

        if (
            $this->is_division_user_manager()
            && $this->input->post('return_to') === 'district_account'
        ) {
            $redirect_url = base_url() . 'pages/district_account/' . rawurlencode($this->session->division);
        }

        if ($this->Page_model->reset_user_password($id, $password)) {
            $message = 'Password reset for <strong>' . html_escape($user->username)
                . '</strong>. Temporary password: <strong><code>' . html_escape($password) . '</code></strong>';
            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('danger', 'Unable to reset the password.');
        }

        redirect($redirect_url);
    }

    public function profile()
    {
        if (!$this->session->logged_in || $this->session->position !== 'admin') {
            show_error('You are not authorized to update user profile pictures.', 403);
        }

        $id = $this->input->post('id');
        $user = $this->Page_model->one_cond_row('users', 'id', $id);
        $config['allowed_types'] = 'jpg|png|jpeg|gif|';
        $config['upload_path'] = './uploads/';
        $this->load->library('upload', $config);

        if ($this->upload->do_upload('file')) {
            $file = "uploads/" . $user->image;

            if (!empty($user->image) && file_exists($file)) {
                unlink($file);
            }
            $this->Page_model->user_update_profile();
            $this->session->set_flashdata('success', 'Successfully updated.');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            print_r($this->upload->display_errors());
        }
    }

    public function user_profile()
    {
        $id = $this->session->username;
        $user = $this->Page_model->one_cond_row('users', 'username', $id);
        $config['allowed_types'] = 'jpg|png|jpeg|gif|';
        $config['upload_path'] = './uploads/';
        $config['max_size']      = 1024; // 1MB
        $this->load->library('upload', $config);

        if ($this->upload->do_upload('file')) {
            $file = "uploads/" . $user->image;

            if (!empty($user->image) && file_exists($file)) {
                unlink($file);
            }
            $this->Page_model->users_update_profile();
            $this->session->set_flashdata('success', 'Successfully updated.');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->session->set_flashdata('danger', $this->upload->display_errors());
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function log_in()
    {

        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('username', 'username', 'required');
        $this->form_validation->set_rules('password', 'uassword', 'required');

        if ($this->form_validation->run() == FALSE) {
            $page = 'home';

            if (!file_exists(APPPATH . 'views/' . $page . '.php')) {
                show_404();
            }

            $this->load->view($page, array('title' => 'Homepage'));
        } else {

            $user_id = $this->Page_model->login();

            if ($user_id) {

                $user_data = array(
                    'id' => $user_id['id'],
                    'username' => $user_id['username'],
                    'position' => $user_id['position'],
                    'user' => $user_id['fname'] . ' ' . $user_id['mname'] . ' ' . $user_id['lname'],
                    'region' => $user_id['r_id'],
                    'division' => $user_id['p_id'],
                    'district' => $user_id['d_id'],
                    'virified' => $user_id['virified'],
                    'logged_in' => true

                );

                $this->session->set_userdata($user_data);
                $this->session->set_userdata('fy', date('Y'));
                $this->session->set_flashdata('user_log', 'You are now loged in as '
                    . $this->session->position);
                
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => true]);
                    return;
                }
                
                redirect(base_url());
            } else {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => 'Invalid login. Either your account is not verified (please check your email for the verification link) or your username or password is incorrect.']);
                    return;
                }
                
                $this->session->set_flashdata('failed', 'Invalid username or password. Please try again.');
                redirect(base_url() . 'homepage');
            }
        }
    }
    public function lock_user_screen()
    {

        $this->form_validation->set_error_delimiters('<div class="error">', '</div>');
        $this->form_validation->set_rules('password', 'password', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "lock_screen";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $this->load->view('pages/' . $page);
        } else {

            $user_id = $this->Page_model->lock_screen();

            if ($user_id) {

                $user_data = array(
                    'username' => $user_id['username'],
                    'user' => $user_id['fname'] . ' ' . $user_id['mname'] . ' ' . $user_id['lname'],
                    'position' => $user_id['position'],
                    'office' => $user_id['office'],
                    'image' => $user_id['image'],
                    'id' => $user_id['id'],
                    'com_id' => $user_id['company_id'],
                    'logged_in' => true

                );

                $this->session->set_userdata($user_data);
                $this->session->set_flashdata('user_log', 'You are now loged in as '
                    . $this->session->position);
                redirect(base_url());
            } else {
                $this->session->set_flashdata('failed', 'Password not match');
                redirect(base_url() . 'lock_user_screen');
            }
        }
    }
    public function logout()
    {

        $this->session->unset_userdata('id');
        $this->session->unset_userdata('username');
        $this->session->unset_userdata('position');
        $this->session->unset_userdata('office');
        $this->session->unset_userdata('logged_in');

        $this->session->set_flashdata('failed', 'You are logged out.');
        redirect(base_url() . 'homepage');
    }
    public function lock()
    {
        $this->session->unset_userdata('id');
        $this->session->unset_userdata('position');
        $this->session->unset_userdata('logged_in');

        $this->session->set_flashdata('danger', 'You are now Lock Screen Mode');
        redirect(base_url() . 'lock_user_screen');
    }

    public function get_provinces()
    {
        $region_id = $this->input->post('region_id');
        $provinces = $this->db->get_where('province', ['region_id' => $region_id])->result();
        echo json_encode($provinces);
    }

    public function get_districts()
    {
        $province_id = $this->input->post('province_id');
        $districts = $this->db->get_where('district', ['province_id' => $province_id])->result();
        echo json_encode($districts);
    }

    public function school_list()
    {

        $page = "district_schools";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";
        $data['division_school_scope'] = false;

        if ($this->session->position == 'admin') {
            // Load all schools for admin without filters - optimized with district and division joins
            $this->db->select('s.schoolID, s.schoolName, s.district_id, s.division_id, d.description as district_name, div.description as division_name');
            $this->db->from('schools s');
            $this->db->join('district d', 's.district_id = d.id', 'left');
            $this->db->join('division div', 's.division_id = div.id', 'left');
            $this->db->order_by('s.schoolName', 'ASC');
            $data['data'] = $this->db->get()->result();
            
            $school_ids = array_map(function ($school) {
                return $school->schoolID;
            }, $data['data']);

            $data['submission_status'] = array(
                'sgod_action_plan' => $this->Page_model->submission_school_ids('sgod_action_plan', $this->session->fy, $school_ids),
                'sbm' => $this->Page_model->submission_school_ids('sbm', $this->session->fy, $school_ids),
                'sbm_ta' => $this->Page_model->submission_school_ids('sbm_ta', $this->session->fy, $school_ids)
            );
            $data['district'] = null;
            $data['selected_submission'] = 'sbm';
            $data['is_admin_view'] = true;
        } else {
            // Original behavior for non-admin users
            $data['data'] = $this->Common->two_join_two_cond('sbm', 'schools', 'a.school_id,a.district,b.district_id, b.schoolID,b.schoolName,a.fy', 'a.school_id = b.schoolID', 'a.fy', $this->session->fy, 'a.district', $this->session->district, 'b.schoolName', 'ASC');
            $school_ids = array_map(function ($school) {
                return $school->schoolID;
            }, $data['data']);

            $data['submission_status'] = array(
                'sgod_action_plan' => $this->Page_model->submission_school_ids('sgod_action_plan', $this->session->fy, $school_ids),
                'sbm' => $this->Page_model->submission_school_ids('sbm', $this->session->fy, $school_ids),
                'sbm_ta' => $this->Page_model->submission_school_ids('sbm_ta', $this->session->fy, $school_ids)
            );
            $data['district'] = $this->Page_model->one_cond_row('district', 'id', $this->session->division);
            $data['selected_submission'] = 'sbm';
            $data['is_admin_view'] = false;
        }

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_list_region()
    {
        $this->require_region_dashboard_access();

        $page = "school_list_region";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        // Load all schools with division info
        $data['data'] = $this->db
            ->select('s.schoolID, s.schoolName, d.description as division_name')
            ->from('schools s')
            ->join('division d', 's.division_id = d.id', 'left')
            ->order_by('s.schoolName', 'ASC')
            ->get()
            ->result();

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_list_division_only()
    {
        $this->require_division_dashboard_access();

        $page = "school_list_division_only";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        // Load schools for this division only with district info
        $data['data'] = $this->db
            ->select('s.recID, s.schoolID, s.schoolName, d.description as district_name')
            ->from('schools s')
            ->join('district d', 's.district_id = d.id', 'left')
            ->where('s.division_id', $this->session->division)
            ->order_by('s.schoolName', 'ASC')
            ->get()
            ->result();

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_profile_region()
    {
        $this->require_region_dashboard_access();

        $school_id = $this->uri->segment(3);

        if (!$school_id) {
            show_404();
        }

        $data['title'] = "School Profile";
        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $school_id);

        if (!$data['school']) {
            show_404();
        }

        // Get school details with division
        $data['school']->division = $this->Common->one_cond_row('division', 'id', $data['school']->division_id);
        $data['school']->district = $this->Common->one_cond_row('district', 'id', $data['school']->district_id);

        // Get SBM checklist data
        $data['sbm'] = $this->Common->two_cond_row('sbm', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get TA form data
        $data['ta'] = $this->Common->two_cond_row('sbm_ta', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get TANA form data
        $data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get Action Plan data
        $data['action_plan'] = $this->Common->two_cond_row('sgod_action_plan', 'school_id', $school_id, 'fy', $this->session->fy);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/school_profile_region', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_profile_division()
    {
        $this->require_division_dashboard_access();

        $school_id = $this->uri->segment(3);

        if (!$school_id) {
            show_404();
        }

        $data['title'] = "School Profile";
        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $school_id);

        if (!$data['school']) {
            show_404();
        }

        // Verify school belongs to this division
        if ($data['school']->division_id != $this->session->division) {
            show_404();
        }

        // Get school details with division
        $data['school']->division = $this->Common->one_cond_row('division', 'id', $data['school']->division_id);
        $data['school']->district = $this->Common->one_cond_row('district', 'id', $data['school']->district_id);

        // Get SBM checklist data
        $data['sbm'] = $this->Common->two_cond_row('sbm', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get TA form data
        $data['ta'] = $this->Common->two_cond_row('sbm_ta', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get TANA form data
        $data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $school_id, 'fy', $this->session->fy);

        // Get Action Plan data
        $data['action_plan'] = $this->Common->two_cond_row('sgod_action_plan', 'school_id', $school_id, 'fy', $this->session->fy);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/school_profile_region', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function school_list_division()
    {

        $page = "district_schools";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";
        $table = $this->uri->segment(4);
        $allowed_tables = array('sgod_action_plan', 'sbm', 'sbm_ta');

        if (!in_array($table, $allowed_tables, true)) {
            show_404();
        }

        $district_id = (int) $this->uri->segment(3);

        // Get all schools in the district, not just those with submissions to the selected table
        $data['data'] = $this->db
            ->select('schoolID, schoolName')
            ->from('schools')
            ->where('district_id', $district_id)
            ->order_by('schoolName', 'ASC')
            ->get()
            ->result();
        $school_ids = array_map(function ($school) {
            return $school->schoolID;
        }, $data['data']);

        $data['submission_status'] = array(
            'sgod_action_plan' => $this->Page_model->submission_school_ids('sgod_action_plan', $this->session->fy, $school_ids),
            'sbm' => $this->Page_model->submission_school_ids('sbm', $this->session->fy, $school_ids),
            'sbm_ta' => $this->Page_model->submission_school_ids('sbm_ta', $this->session->fy, $school_ids)
        );
        $data['district'] = $this->Page_model->one_cond_row('district', 'id', $district_id);
        $data['selected_submission'] = $table;
        $data['division_school_scope'] = true;


        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function district_list_division()
    {

        $page = "division_district";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School List";

        $table = $this->uri->segment(4);
        $allowed_tables = array('sgod_action_plan', 'sbm', 'sbm_ta');

        if (!in_array($table, $allowed_tables, true)) {
            show_404();
        }

        $division_id = (int) $this->uri->segment(3);

        //$data['data'] = $this->Page_model->one_cond('schools','p_id',$this->session->p_id);
        //$data['data'] = $this->Page_model->schools_with_district($this->uri->segment(3));
        $data['data'] = $this->Common->two_join_two_cond_gb($table, 'schools', 'a.school_id,a.district,b.district_id, b.schoolID,b.schoolName,a.division', 'a.school_id = b.schoolID', 'fy', $this->session->fy, 'a.division', $division_id, 'b.schoolName', 'ASC','a.school_id');
        $school_ids = array_map(function ($school) {
            return $school->schoolID;
        }, $data['data']);

        $data['submission_status'] = array(
            'sgod_action_plan' => $this->Page_model->submission_school_ids('sgod_action_plan', $this->session->fy, $school_ids),
            'sbm' => $this->Page_model->submission_school_ids('sbm', $this->session->fy, $school_ids),
            'sbm_ta' => $this->Page_model->submission_school_ids('sbm_ta', $this->session->fy, $school_ids)
        );
        $data['selected_submission'] = $table;
        $data['division'] = $this->Page_model->one_cond_row('division', 'id', $division_id);
        $data['districts'] = $this->Page_model->get_districts_by_division($division_id);


        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function change_fy()
    {
        $new_fy = $this->input->post('new_fy');
        if (!empty($new_fy)) {
            $this->session->set_userdata('fy', $new_fy);
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function school_new()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('schoolID', 'School ID', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "school_add";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Add New School";
            $data['district'] = $this->Page_model->one_cond('district', 'division_id', $this->session->p_id);


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {

            $this->Page_model->school_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/school_list/' . $this->input->post('schoolType'));
        }
    }

    public function get_district_by_division()
    {
        $division_id = $this->input->post('division_id');

        $districts = $this->Page_model->get_districts_by_division($division_id);
        echo json_encode($districts);
    }

    function sbm_action_plan()
    {

        $page = "action_plan_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Action Plan for Implementation of SBM";

        $data['data'] = $this->Common->two_cond('sgod_action_plan', 'school_id', $this->session->username, 'fy', $this->session->fy);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }


    function sbm_action_plan_pview()
    {

        $page = "action_plan_pview";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Action Plan for Implementation of SBM";

        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $this->session->username);
        $data['data'] = $this->Common->two_cond('sgod_action_plan', 'fy', $this->session->fy, 'school_id', $this->session->username);

        $this->load->view('pages/' . $page, $data);
    }

    function sbm_action_plan_pview_district()
    {

        $page = "action_plan_pview";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Action Plan for Implementation of SBM";

        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $this->uri->segment(3));
        $data['data'] = $this->Common->two_cond('sgod_action_plan', 'fy', $this->session->fy, 'school_id', $this->uri->segment(3));

        $this->load->view('pages/' . $page, $data);
    }

    public function sbm_action_plan_list()
    {
        $this->require_region_dashboard_access();

        $school_id = $this->uri->segment(3);

        if (!$school_id) {
            show_404();
        }

        $page = "action_plan_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Action Plan for Implementation of SBM";
        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $school_id);
        $data['data'] = $this->Common->two_cond('sgod_action_plan', 'school_id', $school_id, 'fy', $this->session->fy);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function action_plan_new()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('activity', 'Activity', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "action_plan_new";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "New Action Plan for Implementation of SBM";


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {

            $this->Page_model->action_plan_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/sbm_action_plan');
        }
    }

    public function sbm_action_plan_update()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('activity', 'Activity', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "action_plan_edit";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Update Action Plan for Implementation of SBM";
            $data['data'] = $this->Common->one_cond_row('sgod_action_plan', 'id', $this->uri->segment(3));

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {

            $this->Page_model->action_plan_update();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/sbm_action_plan');
        }
    }

    public function sbm_checklist()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            $data['sbmc'] = $this->Common->two_cond_row('sbm', 'school_id', $this->session->username, 'fy', $this->session->fy);

            $page = !$data['sbmc'] ? 'sbm_form' : 'sbm_form_update';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "New Action Plan";

            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_checklist_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/sbm_checklist');
        }
    }

    public function checklist_district()
    {

        $school_id = $this->uri->segment(3);
        $data['sbmc'] = $this->Common->two_cond_row('sbm', 'school_id', $school_id, 'fy', $this->session->fy);

        $page = 'sbm_form_update';

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "New Action Plan";

        $data['sbm'] = $this->Common->no_cond('sbm_indicator');
        $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');

        // Get school information
        $school = $this->Common->one_cond_row('schools', 'schoolID', $school_id);
        $data['school_name'] = !empty($school) ? $school->schoolName : '';

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function sbm_checklist_update()
    {
        $this->Page_model->sbm_checklist_update();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/sbm_checklist');
    }
    function sbm_checklist_final()
    {
        $this->Page_model->sbm_cecklist_lock_unloc(1);
        $this->session->set_flashdata('success', 'Checklist finalized. PDF download is starting.');
        redirect(base_url() . 'Pages/sbm_checklist_pdf');
    }

    public function sbm_checklist_pdf()
    {
        $page = 'sbm_checklist_pdf';

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $position = strtolower(trim((string) $this->session->position));
        $is_school_user = $position === 'school';
        $view_school_id = $is_school_user ? (string) $this->session->username : (string) $this->uri->segment(3);

        if ($view_school_id === '') {
            show_404();
        }

        $data['sbmc'] = $this->Common->two_cond_row('sbm', 'school_id', $view_school_id, 'fy', $this->session->fy);

        if (!$data['sbmc'] || !isset($data['sbmc']->stat) || (int) $data['sbmc']->stat !== 1) {
            $this->session->set_flashdata('danger', 'Finalize the checklist first before generating its PDF.');

            if ($is_school_user) {
                redirect(base_url() . 'Pages/sbm_checklist');
            } else {
                redirect(base_url() . 'Pages/checklist_district/' . rawurlencode($view_school_id));
            }

            return;
        }

        $data['title'] = 'SBM Checklist PDF';
        $data['view_school_id'] = $view_school_id;
        $data['school'] = $this->Common->one_cond_row('schools', 'schoolID', $view_school_id);
        $data['division'] = ($data['school'] && !empty($data['school']->division_id))
            ? $this->Page_model->one_cond_row('division', 'id', $data['school']->division_id)
            : null;
        $data['district'] = ($data['school'] && !empty($data['school']->district_id))
            ? $this->Page_model->one_cond_row('district', 'id', $data['school']->district_id)
            : null;
        $data['sbm'] = $this->Common->no_cond('sbm_indicator');
        $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');

        $this->load->view('pages/' . $page, $data);
    }

    public function tapr_form()
    {
        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            $data['sbmc'] = $this->Common->two_cond_row('sbm_ta', 'school_id', $this->session->username, 'fy', $this->session->fy);

            $page = !$data['sbmc'] ? 'sbm_ta' : 'sbm_ta_update';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Needs Assessment Form";

            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_ta_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tapr_form');
        }
    }

    public function tapr_form_update()
    {
        $record = $this->Common->two_cond_row('sbm_ta', 'school_id', $this->session->username, 'fy', $this->session->fy);

        if ($record && isset($record->stat) && (int) $record->stat === 1) {
            $this->session->set_flashdata('danger', 'This TA form is finalized and locked. Coordinate with your division reviewer if revisions are needed.');
            redirect(base_url() . 'pages/tapr_form');
            return;
        }

        $this->Page_model->sbm_ta_update();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/tapr_form');
    }

    public function tana_form()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            $data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $this->session->username, 'fy', $this->session->fy);

            $page = !$data['tana'] ? 'sbm_tana' : 'sbm_tana_update';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Needs Assessment Form";

            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tana_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tana_form');
        }
    }

    public function tana_summary()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            //$data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $this->session->username, 'fy', $this->session->fy);
            //$data['indicator'] = $this->Common->no_cond('sbm_sub_indicator');

            $page = 'sbm_tana_top_list';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Needs Assessment Priority Basis";

            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');

            $ta = $this->Common->two_cond_row('sbm_ta', 'school_id', $this->session->username, 'fy', $this->session->fy);
            $averages = $this->Page_model->get_averages($this->session->username, $this->session->fy);
            arsort($averages);

            $top20 = array();
            foreach ($averages as $index => $avg) {
                $col = 'q' . $index;
                $text = ($ta && isset($ta->$col) && $ta->$col !== null) ? trim((string) $ta->$col) : '';

                if ($text === '') {
                    continue;
                }

                $top20[$index] = $avg;

                if (count($top20) >= 20) {
                    break;
                }
            }

            $data['averages'] = $top20;


            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tana_summary_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tana_summary');
        }
    }

    public function tana_summary_division()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            //$data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $this->session->username, 'fy', $this->session->fy);
            //$data['indicator'] = $this->Common->no_cond('sbm_sub_indicator');

            $page = 'sbm_tana_top_list_division';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Needs Assessment Priority Basis";
            $data['data'] = $this->Page_model->get_seq_one_two();
            $data['ivy'] = $this->Common->two_cond_order_by('division_tana','fy',$this->session->fy,'division',$this->session->division,'sequence','ASC');



            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tana_summary_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tana_summary');
        }
    }

    public function tana_summary_region()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('district', 'District', 'required');

        if ($this->form_validation->run() == FALSE) {

            //$data['tana'] = $this->Common->two_cond_row('tana', 'school_id', $this->session->username, 'fy', $this->session->fy);
            //$data['indicator'] = $this->Common->no_cond('sbm_sub_indicator');

            $page = 'tana_region';



            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Needs Assessment Priority Basis";
            $data['data'] = $this->Page_model->two_cond('division_tana','region',$this->session->region, 'fy', $this->session->fy);
            $data['ivy'] = $this->Common->two_cond_order_by('region_tana','fy',$this->session->fy,'region',$this->session->region,'sequence','ASC');



            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tana_summary_region_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tana_summary');
        }
    }

    public function tana_summary_update()
    {
        $this->Page_model->delete_two_cond('tana_summary','fy',$this->session->fy,'school_id',$this->session->username);
        $this->Page_model->sbm_tana_summary_insert();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/tana_summary');
    }

    public function tana_division()
    {
        $this->Page_model->tana_division_insert();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/tana_summary_division');
    }

    public function tana_division_autogenerate()
    {
        $result = $this->Page_model->tana_division_autogenerate();

        if (!empty($result['status'])) {
            $message = 'Successfully generated ' . $result['count'] . ' thematic ' . ($result['count'] === 1 ? 'analysis' : 'analyses') . ' from ' . $result['source_count'] . ' priority ' . ($result['source_count'] === 1 ? 'concern' : 'concerns') . '.';

            if (!empty($result['truncated'])) {
                $message .= ' Only the top 20 recurring concerns were kept.';
            }

            $this->session->set_flashdata('success', $message);
        } else {
            $this->session->set_flashdata('danger', !empty($result['message']) ? $result['message'] : 'Unable to auto-generate thematic analysis.');
        }

        redirect(base_url() . 'pages/tana_summary_division');
    }

    public function tana_region()
    {
        $this->Page_model->tana_region_insert();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/tana_summary_region');
    }

    public function tana_form_update()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_school_user = $position === 'school';

        if ($is_school_user) {
            $this->Page_model->sbm_tana_update();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/tana_form');
            return;
        }

        $this->Page_model->sbm_tana_update();
        $this->session->set_flashdata('success', 'Successfully saved.');
        redirect(base_url() . 'pages/tana_form');
    }

    function sbm_ta_final()
    {
        $this->Page_model->sbm_ta_lock_unloc(1);
        $this->session->set_flashdata('success', 'TA report finalized successfully. It is now locked until a division reviewer unlocks it.');
        redirect(base_url() . 'Pages/tapr_form');
    }

    public function sbm_ta_unlock()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_division_user = $position === 'division';

        if (!$is_division_user) {
            show_error('Only division users can unlock TA reports.', 403);
        }

        $this->ensure_unlock_request_table();

        $ta_id = $this->uri->segment(3);
        if ($ta_id) {
            // Mark any pending unlock requests for this TA as approved
            $this->db->where('ta_id', $ta_id)
                ->where('status', 'pending')
                ->update('unlock_request', array(
                    'status' => 'approved',
                    'processed_date' => date('Y-m-d H:i:s'),
                    'processed_by' => $this->session->username
                ));
        }

        $this->Page_model->sbm_ta_lock_unloc(0);
        $this->session->set_flashdata('success', 'TA report unlocked successfully. The school can now edit it again.');

        // Check if this is called from division view (has school_id parameter)
        $school_id = $this->uri->segment(4);
        if ($school_id) {
            redirect(base_url() . 'Pages/tapr_form_district/' . rawurldecode($school_id));
        } else {
            redirect(base_url() . 'Pages/tapr_form');
        }
    }

    private function ensure_unlock_request_table()
    {
        // Create table if it doesn't exist
        $this->db->query("CREATE TABLE IF NOT EXISTS `unlock_request` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ta_id` int(11) NOT NULL,
          `school_id` varchar(255) NOT NULL,
          `division_id` int(11) NOT NULL,
          `requested_by` varchar(255) NOT NULL,
          `request_date` datetime NOT NULL,
          `status` enum('pending','approved','cleared') DEFAULT 'pending',
          `processed_date` datetime DEFAULT NULL,
          `processed_by` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `ta_id` (`ta_id`),
          KEY `school_id` (`school_id`),
          KEY `division_id` (`division_id`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Check if request_type column exists, if not add it
        $columns = $this->db->query("SHOW COLUMNS FROM `unlock_request` LIKE 'request_type'")->num_rows();
        if ($columns == 0) {
            $this->db->query("ALTER TABLE `unlock_request` ADD COLUMN `request_type` enum('ta','checklist') NOT NULL DEFAULT 'ta' AFTER `id`");
            $this->db->query("ALTER TABLE `unlock_request` ADD INDEX `request_type` (`request_type`)");
        }

        // Check if checklist_id column exists, if not add it
        $columns = $this->db->query("SHOW COLUMNS FROM `unlock_request` LIKE 'checklist_id'")->num_rows();
        if ($columns == 0) {
            $this->db->query("ALTER TABLE `unlock_request` ADD COLUMN `checklist_id` int(11) DEFAULT NULL AFTER `ta_id`");
            $this->db->query("ALTER TABLE `unlock_request` ADD INDEX `checklist_id` (`checklist_id`)");
        }

        // Make ta_id nullable if it's not
        $this->db->query("ALTER TABLE `unlock_request` MODIFY COLUMN `ta_id` int(11) DEFAULT NULL");
    }

    public function sbm_ta_unlock_request()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_school_user = $position === 'school';

        if (!$is_school_user) {
            show_error('Only school users can request TA report unlocks.', 403);
        }

        $ta_id = $this->uri->segment(3);
        if (empty($ta_id)) {
            show_404();
        }

        // Ensure table exists
        $this->ensure_unlock_request_table();

        // Get the TA record to get school_id
        $ta_record = $this->Common->one_cond_row('sbm_ta', 'id', $ta_id);
        if (!$ta_record) {
            show_404();
        }

        // Get school to get division_id
        $school = $this->Common->one_cond_row('schools', 'schoolID', $ta_record->school_id);
        if (!$school) {
            show_error('School not found.', 404);
        }

        // Check if request already exists
        $existing_request = $this->db->where('ta_id', $ta_id)
            ->where('status', 'pending')
            ->get('unlock_request')
            ->row();

        if ($existing_request) {
            $this->session->set_flashdata('danger', 'An unlock request is already pending for this TA report.');
            redirect(base_url() . 'Pages/tapr_form');
        }

        // Create unlock request
        $request_data = array(
            'request_type' => 'ta',
            'ta_id' => $ta_id,
            'school_id' => $ta_record->school_id,
            'division_id' => $school->division_id,
            'requested_by' => $this->session->username,
            'request_date' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        );

        $this->db->insert('unlock_request', $request_data);

        $this->session->set_flashdata('success', 'Unlock request sent to your division reviewer. You will be notified when it is approved.');
        redirect(base_url() . 'Pages/tapr_form');
    }

    public function sbm_checklist_unlock_request()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_school_user = $position === 'school';

        if (!$is_school_user) {
            show_error('Only school users can request checklist unlocks.', 403);
        }

        $checklist_id = $this->uri->segment(3);
        if (empty($checklist_id)) {
            show_404();
        }

        $this->ensure_unlock_request_table();

        // Get the checklist record to get school_id
        $checklist_record = $this->Common->one_cond_row('sbm', 'id', $checklist_id);
        if (!$checklist_record) {
            show_404();
        }

        // Get school to get division_id
        $school = $this->Common->one_cond_row('schools', 'schoolID', $checklist_record->school_id);
        if (!$school) {
            show_error('School not found.', 404);
        }

        // Check if request already exists
        $existing_request = $this->db->where('checklist_id', $checklist_id)
            ->where('status', 'pending')
            ->get('unlock_request')
            ->row();

        if ($existing_request) {
            $this->session->set_flashdata('danger', 'An unlock request is already pending for this checklist.');
            redirect(base_url() . 'Pages/sbm_checklist');
        }

        // Create unlock request
        $request_data = array(
            'request_type' => 'checklist',
            'checklist_id' => $checklist_id,
            'school_id' => $checklist_record->school_id,
            'division_id' => $school->division_id,
            'requested_by' => $this->session->username,
            'request_date' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        );

        $this->db->insert('unlock_request', $request_data);

        $this->session->set_flashdata('success', 'Unlock request sent to your division reviewer. You will be notified when it is approved.');
        redirect(base_url() . 'Pages/sbm_checklist');
    }

    public function clear_unlock_requests()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_division_user = $position === 'division';

        if (!$is_division_user) {
            show_error('Only division users can clear unlock requests.', 403);
        }

        $this->ensure_unlock_request_table();

        $this->db->where('division_id', $this->session->division)
            ->where('status', 'pending')
            ->update('unlock_request', array('status' => 'cleared'));

        $this->session->set_flashdata('success', 'All pending unlock requests have been cleared.');
        redirect(base_url());
    }

    public function unlock_requests()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_division_user = $position === 'division';

        if (!$is_division_user) {
            show_error('Only division users can view unlock requests.', 403);
        }

        $this->ensure_unlock_request_table();

        $page = "unlock_requests";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "TA Unlock Requests";
        $data['requests'] = $this->db->where('division_id', $this->session->division)
            ->order_by('request_date', 'DESC')
            ->get('unlock_request')
            ->result();

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function unlock_request_view()
    {
        $position = strtolower(trim((string) $this->session->position));
        $is_division_user = $position === 'division';

        if (!$is_division_user) {
            show_error('Only division users can view unlock requests.', 403);
        }

        $this->ensure_unlock_request_table();

        $request_id = $this->uri->segment(3);
        if (empty($request_id)) {
            show_404();
        }

        $request = $this->db->where('id', $request_id)
            ->where('division_id', $this->session->division)
            ->get('unlock_request')
            ->row();

        if (!$request) {
            show_404();
        }

        $school = $this->Common->one_cond_row('schools', 'schoolID', $request->school_id);
        $ta_record = $this->Common->one_cond_row('sbm_ta', 'id', $request->ta_id);

        $page = "unlock_request_view";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "TA Unlock Request Details";
        $data['request'] = $request;
        $data['school'] = $school;
        $data['ta_record'] = $ta_record;

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }





    public function action_plan_delete()
    {
        $this->Page_model->delete('sgod_action_plan', 'id', 3);
        $this->session->set_flashdata('danger', 'Successfully deleted.');
        redirect(base_url() . 'pages/sbm_action_plan');
    }

    public function tapr_form_district()
    {
        if ($this->form_validation->run() == FALSE) {
            $view_school_id = rawurldecode((string) $this->uri->segment(3));

            $data['sbm_remark'] = $this->Common->two_cond_row('sbm_remark_admin', 'school_id', $view_school_id, 'fy', $this->session->fy);

            $page = !$data['sbm_remark'] ? 'sbm_ta_district' : 'sbm_ta_district_update';

            //$page = 'sbm_ta_district';

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Technical Assistance Provision Review";

            $data['sbm'] = $this->Common->no_cond('sbm_indicator');
            $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');
            $data['sbmc_count'] = $this->Common->two_cond_count_row('sbm_ta', 'school_id', $view_school_id, 'fy', $this->session->fy);
            $data['sbmc'] = $this->Common->two_cond_row('sbm_ta', 'school_id', $view_school_id, 'fy', $this->session->fy);
            $data['view_school_id'] = $view_school_id;
            $data['school'] = $this->db->query(
                "SELECT * FROM schools WHERE TRIM(schoolID) = ? LIMIT 1",
                array(trim($view_school_id))
            )->row();
            if (!$data['school'] && $data['sbmc']) {
                $data['school'] = $this->db->query(
                    "SELECT * FROM schools WHERE TRIM(schoolID) = ? LIMIT 1",
                    array(trim((string) $data['sbmc']->school_id))
                )->row();
            }
            $data['checklist_record'] = $this->Common->two_cond_row('sbm', 'school_id', $view_school_id, 'fy', $this->session->fy);
            $data['division'] = ($data['school'] && !empty($data['school']->division_id))
                ? $this->Page_model->one_cond_row('division', 'id', $data['school']->division_id)
                : null;
            $data['district'] = ($data['school'] && !empty($data['school']->district_id))
                ? $this->Page_model->one_cond_row('district', 'id', $data['school']->district_id)
                : null;

            //$data['lock'] = $this->Common->three_cond_count_row('sbm_ta', 'school_id', $this->uri->segment(3), 'fy', $this->session->fy,'stat',1);



            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        }
    }

    function tapr_admin()
    {
        $this->Page_model->sbm_cecklist_admin_insert();
        $this->session->set_flashdata('success', 'Saved successfully.');
        redirect(base_url() . 'Pages/tapr_form_district/' . $this->input->post('school_id'));
    }
    function tapr_district_update()
    {
        $this->Page_model->sbm_cecklist_admin_update();
        $this->session->set_flashdata('success', 'Saved successfully.');
        redirect(base_url() . 'Pages/tapr_form_district/' . $this->input->post('school_id'));
    }


    public function sbm_district_tech()
    {

        $page = 'sbm_district_tech';

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Technical Assistance Provision Form";

        $data['data'] = $this->Common->two_cond('sbm_tech', 'district', $this->session->district, 'fy', $this->session->fy);
        $data['district'] = $this->Page_model->one_cond_row('district', 'id', $this->session->district);


        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function sbm_district_tech_new()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('ta_rec', 'Technical Assisstance', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = 'sbm_district_tech_new';

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }


            $data['title'] = "Create Technical Assistance Entry";
            $data['district'] = $this->Page_model->one_cond_row('district', 'id', $this->session->district);

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tech_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/sbm_district_tech');
        }
    }

    public function sbm_district_tech_edit()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');

        $this->form_validation->set_rules('ta_rec', 'Technical Assisstance', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = 'sbm_district_tech_update';

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['data'] = $this->Common->one_cond_row('sbm_tech', 'id', $this->uri->segment(3));
            $data['title'] = "Update Technical Assistance Entry";
            $data['district'] = $this->Page_model->one_cond_row('district', 'id', $this->session->district);

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->sbm_tech_update();
            $this->session->set_flashdata('success', 'Successfully saved.');
            redirect(base_url() . 'pages/sbm_district_tech');
        }
    }

    public function sbm_district_tech_del()
    {
        $id = (int) $this->uri->segment(3);
        $entry = $this->Common->one_cond_row('sbm_tech', 'id', $id);

        if (!$entry || (int) $entry->district !== (int) $this->session->district) {
            $this->session->set_flashdata('danger', 'Technical assistance entry not found.');
            redirect(base_url() . 'pages/sbm_district_tech');
            return;
        }

        $this->Page_model->delete('sbm_tech', 'id', $id);
        $this->session->set_flashdata('danger', 'Successfully deleted.');
        redirect(base_url() . 'pages/sbm_district_tech');
    }



    // function sbm_list()
    // {
    // 	$_SESSION['sbm_fy'] = $this->input->post('fy');
    // 	$result['title'] = "SCHOOL-BASED FEEDING PROGRAM";
    // 	$result['sbm'] = $this->Common->no_cond('sbm_indicator');
    // 	$result['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');

    // 	$sbm = $this->Common->one_cond_count_row('sbm', 'school_id', $this->uri->segment(3));
    // 	$result['sbmc'] = $this->Common->one_cond_row('sbm', 'school_id', $this->uri->segment(3));

    // 	$result['sbmcr'] = $this->Common->one_cond_row('sbm_remark', 'school_id', $this->uri->segment(3));
    // 	$sbmr = $this->Common->one_cond_count_row('sbm_remark', 'school_id', $this->uri->segment(3));


    // 	$this->load->view('sbm_list', $result);
    // }

    public function sbm_list()
    {

        $page = 'sbm_list';

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Technical Assisstance Provision Form";

        $data['sbm'] = $this->Common->no_cond('sbm_indicator');
        $data['sbm_sub'] = $this->Common->no_cond('sbm_sub_indicator');

        $data['data'] = $this->Common->two_cond('sbm_tech', 'district', $this->session->district, 'fy', $this->session->fy);


        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    public function district_list()
    {

        $page = "district";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "List Of District";

        $data['data'] = $this->db
            ->where('division_id', $this->session->division)
            ->order_by('description', 'ASC')
            ->get('district')
            ->result();
        $data['submission_counts'] = array(
            'sgod_action_plan' => $this->Page_model->district_submission_counts(
                'sgod_action_plan',
                $this->session->division,
                $this->session->fy
            ),
            'sbm' => $this->Page_model->district_submission_counts(
                'sbm',
                $this->session->division,
                $this->session->fy
            ),
            'sbm_ta' => $this->Page_model->district_submission_counts(
                'sbm_ta',
                $this->session->division,
                $this->session->fy
            )
        );

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function division_list()
    {

        $page = "division";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Division List";

        //$data['data'] = $this->Page_model->one_cond('schools','p_id',$this->session->p_id);
        $divisions = $this->Page_model->one_cond('division', 'region_id', 12);

        // Calculate signup statistics for each division
        foreach ($divisions as $division) {
            // Count signed up schools for this division
            $this->db->where('division_id', $division->id);
            $signed_up_count = $this->db->count_all_results('schools');

            $total_schools = $division->total_schools ? (int) $division->total_schools : 0;

            // Calculate percentages
            if ($total_schools > 0) {
                $signup_percentage = round(($signed_up_count / $total_schools) * 100, 1);
                $not_signup_percentage = round((($total_schools - $signed_up_count) / $total_schools) * 100, 1);
            } else {
                $signup_percentage = 0;
                $not_signup_percentage = 0;
            }

            $division->signed_up_count = $signed_up_count;
            $division->signup_percentage = $signup_percentage;
            $division->not_signup_percentage = $not_signup_percentage;

            // Count Self-Assessment (sbm) submissions for this division
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_count = $this->db->count_all_results('sbm');

            // Count TNA (sbm_ta) submissions for this division
            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_ta_count = $this->db->count_all_results('sbm_ta');

            error_log("Division ID: {$division->id}, SBM count: {$sbm_count}, SBM_TA count: {$sbm_ta_count}");

            // Calculate completion percentages
            if ($total_schools > 0) {
                $sbm_completion_percentage = round(($sbm_count / $total_schools) * 100, 1);
                $sbm_ta_completion_percentage = round(($sbm_ta_count / $total_schools) * 100, 1);
            } else {
                $sbm_completion_percentage = 0;
                $sbm_ta_completion_percentage = 0;
            }

            $division->sbm_count = $sbm_count;
            $division->sbm_completion_percentage = $sbm_completion_percentage;
            $division->sbm_ta_count = $sbm_ta_count;
            $division->sbm_ta_completion_percentage = $sbm_ta_completion_percentage;
        }

        $data['data'] = $divisions;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function report_division_submission()
    {
        $page = "report_division_submission";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Division Submission Report";

        // Get divisions based on user role
        if ($this->session->position == 'region') {
            $divisions = $this->Page_model->one_cond('division', 'region_id', $this->session->region);
        } elseif ($this->session->position == 'admin') {
            $divisions = $this->Page_model->no_cond('division');
        } else {
            $divisions = array();
        }

        // Calculate statistics for each division
        foreach ($divisions as $division) {
            // Count signed up schools
            $this->db->where('division_id', $division->id);
            $signed_up_count = $this->db->count_all_results('schools');

            $total_schools = $division->total_schools ? (int) $division->total_schools : 0;

            // Calculate signup percentages
            if ($total_schools > 0) {
                $signup_percentage = round(($signed_up_count / $total_schools) * 100, 1);
                $not_signup_percentage = round((($total_schools - $signed_up_count) / $total_schools) * 100, 1);
            } else {
                $signup_percentage = 0;
                $not_signup_percentage = 0;
            }

            $division->signed_up_count = $signed_up_count;
            $division->signup_percentage = $signup_percentage;
            $division->not_signup_percentage = $not_signup_percentage;

            // Count submissions for each form type
            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $action_plan_count = $this->db->count_all_results('sgod_action_plan');

            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_count = $this->db->count_all_results('sbm');

            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_ta_count = $this->db->count_all_results('sbm_ta');

            // Calculate completion percentages
            if ($total_schools > 0) {
                $action_plan_percentage = round(($action_plan_count / $total_schools) * 100, 1);
                $sbm_percentage = round(($sbm_count / $total_schools) * 100, 1);
                $sbm_ta_percentage = round(($sbm_ta_count / $total_schools) * 100, 1);
            } else {
                $action_plan_percentage = 0;
                $sbm_percentage = 0;
                $sbm_ta_percentage = 0;
            }

            $division->action_plan_count = $action_plan_count;
            $division->action_plan_percentage = $action_plan_percentage;
            $division->sbm_count = $sbm_count;
            $division->sbm_percentage = $sbm_percentage;
            $division->sbm_ta_count = $sbm_ta_count;
            $division->sbm_ta_percentage = $sbm_ta_percentage;
        }

        $data['data'] = $divisions;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function report_overall_accomplishments()
    {
        $page = "report_overall_accomplishments";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Overall Accomplishments Comparison";

        // Get divisions based on user role
        if ($this->session->position == 'region') {
            $divisions = $this->Page_model->one_cond('division', 'region_id', $this->session->region);
        } elseif ($this->session->position == 'admin') {
            $divisions = $this->Page_model->no_cond('division');
        } else {
            $divisions = array();
        }

        // Calculate statistics for each division
        foreach ($divisions as $division) {
            // Count signed up schools
            $this->db->where('division_id', $division->id);
            $signed_up_count = $this->db->count_all_results('schools');

            $total_schools = $division->total_schools ? (int) $division->total_schools : 0;

            // Calculate signup percentages
            if ($total_schools > 0) {
                $signup_percentage = round(($signed_up_count / $total_schools) * 100, 1);
            } else {
                $signup_percentage = 0;
            }

            // Count submissions for each form type
            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $action_plan_count = $this->db->count_all_results('sgod_action_plan');

            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_count = $this->db->count_all_results('sbm');

            $this->db->reset_query();
            $this->db->where('division', $division->id);
            $this->db->where('fy', $this->session->fy);
            $sbm_ta_count = $this->db->count_all_results('sbm_ta');

            // Calculate completion percentages
            if ($total_schools > 0) {
                $action_plan_percentage = round(($action_plan_count / $total_schools) * 100, 1);
                $sbm_percentage = round(($sbm_count / $total_schools) * 100, 1);
                $sbm_ta_percentage = round(($sbm_ta_count / $total_schools) * 100, 1);
            } else {
                $action_plan_percentage = 0;
                $sbm_percentage = 0;
                $sbm_ta_percentage = 0;
            }

            $division->signup_percentage = $signup_percentage;
            $division->action_plan_percentage = $action_plan_percentage;
            $division->sbm_percentage = $sbm_percentage;
            $division->sbm_ta_percentage = $sbm_ta_percentage;
        }

        $data['data'] = $divisions;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function report_sgc()
    {
        $page = "report_sgc";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "School Governance Council Report";

        // Get divisions based on user role
        if ($this->session->position == 'region') {
            $divisions = $this->Page_model->one_cond('division', 'region_id', $this->session->region);
        } elseif ($this->session->position == 'admin') {
            $divisions = $this->Page_model->no_cond('division');
        } else {
            $divisions = array();
        }

        // Calculate SGC statistics for each division
        foreach ($divisions as $division) {
            // Count schools by SGC status
            $this->db->where('division_id', $division->id);
            $this->db->where('sgc', 1);
            $not_yet_organized = $this->db->count_all_results('schools');

            $this->db->reset_query();
            $this->db->where('division_id', $division->id);
            $this->db->where('sgc', 2);
            $organized_not_functional = $this->db->count_all_results('schools');

            $this->db->reset_query();
            $this->db->where('division_id', $division->id);
            $this->db->where('sgc', 3);
            $functional = $this->db->count_all_results('schools');

            // Total schools with SGC data
            $total_sgc = $not_yet_organized + $organized_not_functional + $functional;

            // Total schools in division
            $total_schools = $division->total_schools ? (int) $division->total_schools : 0;
            $not_yet_responded = max(0, $total_schools - $total_sgc);

            $division->not_yet_organized = $not_yet_organized;
            $division->organized_not_functional = $organized_not_functional;
            $division->functional = $functional;
            $division->total_sgc = $total_sgc;
            $division->total_schools = $total_schools;
            $division->not_yet_responded = $not_yet_responded;
        }

        $data['data'] = $divisions;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function sbm_rate_divisions_list()
    {

        $page = "sbm_rate_division_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Division List";

        //$data['data'] = $this->Page_model->one_cond('schools','p_id',$this->session->p_id);
        $data['data'] = $this->Page_model->one_cond('division', 'region_id', 12);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    // public function signup(){

    //     $page = "school_signup";

    //     if(!file_exists(APPPATH.'views/pages/'.$page.'.php')){
    //         show_404();
    //     }

    //     $data['title'] = "Division List"; 

    //     //$data['data'] = $this->Page_model->one_cond('schools','p_id',$this->session->p_id);
    //     $data['division'] = $this->Page_model->one_cond('division','region_id',12);

    //     $this->load->view('pages/'.$page, $data);

    // }

    public function signup()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        ', '</div>');
        $this->form_validation->set_rules('schoolID', 'School ID', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');
        $this->form_validation->set_rules('schoolName', 'school Name', 'required');
        $this->form_validation->set_rules('schoolEmail', 'School Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('division_id', 'Division', 'trim|required');
        $this->form_validation->set_rules('d_id', 'District/Cluster', 'trim|required');
        $this->form_validation->set_rules('sgc', 'School Governance Council', 'trim|required');
        $this->form_validation->set_rules('category', 'Category', 'trim|required');
        $this->form_validation->set_rules('schoolType', 'Offerings', 'trim|required');

        if ($this->form_validation->run() == FALSE) {

            $page = "school_signup";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }
            $data['division'] = $this->Page_model->one_cond('division', 'region_id', 12);
            $selected_division_id = (int) $this->input->post('division_id');
            $data['districts'] = $selected_division_id > 0
                ? $this->Page_model->get_districts_by_division($selected_division_id)
                : array();


            $this->load->view('pages/' . $page, $data);
            } else {

                $recaptcha = $this->input->post('g-recaptcha-response');
                $secret = trim('6LedsqorAAAAAJLksDbaUK9OIhlM-6bNeR52eXbo');

                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha}");
                $responseKeys = json_decode($response, true);

                if (!$responseKeys["success"]) {
                    $this->session->set_flashdata('danger', 'reCAPTCHA verification failed. Please try again.');
                    redirect(base_url() . 'log_in');
                }


            $renren = $this->input->post('renren');
            $ivykate = $this->input->post('ivykate');
            $ivankyle = $this->input->post('ivankyle');
            $ic = $this->input->post('ic');

            $schoolID = $this->input->post('schoolID');
            $user_email = $this->input->post('schoolEmail');

            if (!empty($renren) || !empty($ivykate) || !empty($ivankyle) || !empty($ic)) {
                $this->session->set_flashdata('danger', 'I Got you');
                redirect(base_url() . 'private');
            }

            $check = $this->Common->one_cond_count_row('schools', 'schoolID', $schoolID)->num_rows();
            $user_check = $this->Common->one_cond_count_row('users', 'email', $user_email)->num_rows();

            if ($check == 0 && $user_check == 0) {
                $this->Page_model->insert_school();
                $this->Page_model->insert_user();
                $pass = base_url() . 'Pages/confirm_signup/' . $this->db->insert_id();
            } else {
                $this->session->set_flashdata('failed', 'Duplicate entry found. The record already exists.');
                redirect(base_url() . 'log_in');
            }

            $email = $this->input->post('schoolEmail');
            $name = $this->input->post('schoolName');
            $username = $this->input->post('schoolID');
            $pn = $this->input->post('password');
            

            //Email Notification
            $this->load->config('email');
            $this->load->library('email');
            $mail_message = '
                    <html>
                    <head>
                    <style>
                        body {
                        font-family: "Segoe UI", Roboto, Arial, sans-serif;
                        background-color: #f0f4f8;
                        margin: 0;
                        padding: 20px;
                        }
                        .email-wrapper {
                        max-width: 600px;
                        margin: auto;
                        background-color: #ffffff;
                        border-radius: 10px;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                        }
                        .email-header {
                        background-color: #a00000;
                        color: white;
                        padding: 20px;
                        text-align: center;
                        }
                        .email-header h2 {
                        margin: 0;
                        font-size: 24px;
                        }
                        .email-body {
                        padding: 30px 25px;
                        color: #333333;
                        }
                        .email-body p {
                        font-size: 16px;
                        line-height: 1.6;
                        }
                        .credentials-box {
                        background-color: #e9f2ff;
                        padding: 15px;
                        border-left: 4px solid #a00000;
                        margin: 20px 0;
                        border-radius: 6px;
                        }
                        .credentials-box p {
                        margin: 0;
                        font-weight: bold;
                        color: #a00000;
                        }
                        .email-footer {
                        background-color: #f7f7f7;
                        padding: 15px;
                        text-align: center;
                        font-size: 14px;
                        color: #666666;
                        }
                        .cb{
                            margin-top:20px;
                            background-color:#a00000;
                            color:#ffffff !important;
                            padding:7px 15px;
                            text-decoration:none;
                            border-radius:6px;
                            font-size:16px;
                            font-weight:bold;
                            display:inline-block;
                        }
                    </style>
                    </head>
                    <body>
                    <div class="email-wrapper">
                        <div class="email-header">
                        <h2>Welcome to FTAD OneView</h2>
                        </div>
                        <div class="email-body">
                        <p>Dear ' . htmlspecialchars($name) . ',</p>
                        <p>Your profile has been successfully encoded into the <strong>FTAD OneView</strong> system. Please find your login credentials below:</p>

                        <div class="credentials-box">
                            <p>Username: ' . htmlspecialchars($username) . '</p>
                            <p>Password: ' . htmlspecialchars($pn) . '</p>
                            <a class="cb" href="' . htmlspecialchars($pass) . '">' . 'Confirm' . '</a>
                        </div>

                        <p>Kindly keep this information secure and do not share it with anyone.</p>
                        <p>Should you have any issues accessing your account, please contact your system administrator.</p>

                        <p style="margin-top: 30px;">Thanks & Regards,<br><strong>FTAD OneView Team</strong></p>
                        </div>
                        <div class="email-footer">
                        © ' . date('Y') . ' Department of Education
                        </div>
                    </div>
                    </body>
                    </html>';

            $this->email->from('no-reply@lxeinfotechsolutions.com', 'FTAD OneView Team')
                ->to($email)
                ->subject('Account Created')
                ->message($mail_message);
            $this->email->send();

            //$this->session->set_flashdata('success', 'School account has been registered successfully. Your username and password have been sent to your email.');
            $this->session->set_flashdata('success', 'The school account has been successfully registered. You may now sign in using your credentials.');
            redirect(base_url() . 'log_in');
        }
    }


    public function signup_district()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        ', '</div>');
        $this->form_validation->set_rules('schoolID', 'Username', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "district_signup";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }
            $data['division'] = $this->Page_model->one_cond('division', 'region_id', 12);


            $this->load->view('pages/' . $page, $data);
            } else {

                $recaptcha = $this->input->post('g-recaptcha-response');
                $secret = trim('6LedsqorAAAAAJLksDbaUK9OIhlM-6bNeR52eXbo');

                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha}");
                $responseKeys = json_decode($response, true);

                if (!$responseKeys["success"]) {
                    $this->session->set_flashdata('danger', 'reCAPTCHA verification failed. Please try again.');
                    redirect(base_url() . 'log_in');
                }


            $renren = $this->input->post('renren');
            $ivykate = $this->input->post('ivykate');
            $ivankyle = $this->input->post('ivankyle');
            $ic = $this->input->post('ic');

            $districtID = $this->input->post('d_id');
            $username = $this->input->post('schoolID');

            if (!empty($renren) || !empty($ivykate) || !empty($ivankyle) || !empty($ic)) {
                $this->session->set_flashdata('danger', 'I Got you');
                redirect(base_url() . 'private');
            }

            $user = $this->Common->one_cond_count_row('users', 'username', $username)->num_rows();

            if ($user > 0) { 
                $this->session->set_flashdata('failed', 'Duplicate entry found. The record already exists.');
                redirect(base_url('log_in'));
            }

            $check = $this->Common->two_cond_count_row('users', 'd_id', $districtID,'position','district')->num_rows();

            if ($check == 0) {
                $this->Page_model->insert_district_user(); 
                $pass = base_url() . 'Pages/confirm_signup/' . $this->db->insert_id();
            } else {
                $this->session->set_flashdata('failed', 'Duplicate entry found. The record already exists.');
                redirect(base_url() . 'log_in');
            }

            $district = $this->Common->one_cond_row('district', 'id',$this->input->post('d_id'));

            $email = $this->input->post('schoolEmail');
            $name = $district->description;
            $username = $this->input->post('schoolID');
            $pn = $this->input->post('password');
            

            //Email Notification
            $this->load->config('email');
            $this->load->library('email');
            $mail_message = '
                    <html>
                    <head>
                    <style>
                        body {
                        font-family: "Segoe UI", Roboto, Arial, sans-serif;
                        background-color: #f0f4f8;
                        margin: 0;
                        padding: 20px;
                        }
                        .email-wrapper {
                        max-width: 600px;
                        margin: auto;
                        background-color: #ffffff;
                        border-radius: 10px;
                        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                        }
                        .email-header {
                        background-color: #a00000;
                        color: white;
                        padding: 20px;
                        text-align: center;
                        }
                        .email-header h2 {
                        margin: 0;
                        font-size: 24px;
                        }
                        .email-body {
                        padding: 30px 25px;
                        color: #333333;
                        }
                        .email-body p {
                        font-size: 16px;
                        line-height: 1.6;
                        }
                        .credentials-box {
                        background-color: #e9f2ff;
                        padding: 15px;
                        border-left: 4px solid #a00000;
                        margin: 20px 0;
                        border-radius: 6px;
                        }
                        .credentials-box p {
                        margin: 0;
                        font-weight: bold;
                        color: #a00000;
                        }
                        .email-footer {
                        background-color: #f7f7f7;
                        padding: 15px;
                        text-align: center;
                        font-size: 14px;
                        color: #666666;
                        }
                        .cb{
                            margin-top:20px;
                            background-color:#a00000;
                            color:#ffffff !important;
                            padding:7px 15px;
                            text-decoration:none;
                            border-radius:6px;
                            font-size:16px;
                            font-weight:bold;
                            display:inline-block;
                        }
                    </style>
                    </head>
                    <body>
                    <div class="email-wrapper">
                        <div class="email-header">
                        <h2>Welcome to FTAD OneView</h2>
                        </div>
                        <div class="email-body">
                        <p>Dear ' . htmlspecialchars($name) . ',</p>
                        <p>Your profile has been successfully encoded into the <strong>FTAD OneView</strong> system. Please find your login credentials below:</p>

                        <div class="credentials-box">
                            <p>Username: ' . htmlspecialchars($username) . '</p>
                            <p>Password: ' . htmlspecialchars($pn) . '</p>
                            <a class="cb" href="' . htmlspecialchars($pass) . '">' . 'Confirm' . '</a>
                        </div>

                        <p>Kindly keep this information secure and do not share it with anyone.</p>
                        <p>Should you have any issues accessing your account, please contact your system administrator.</p>

                        <p style="margin-top: 30px;">Thanks & Regards,<br><strong>FTAD OneView Team</strong></p>
                        </div>
                        <div class="email-footer">
                        © ' . date('Y') . ' Department of Education
                        </div>
                    </div>
                    </body>
                    </html>';

            $this->email->from('no-reply@lxeinfotechsolutions.com', 'FTAD OneView Team')
                ->to($email)
                ->subject('Account Created')
                ->message($mail_message);
            $this->email->send();

            //$this->session->set_flashdata('success', 'School account has been registered successfully. Your username and password have been sent to your email.');
            $this->session->set_flashdata('success', 'The district account has been successfully registered. You may now sign in using your credentials.');
            redirect(base_url() . 'log_in');
        }
    }


    function school()
    {

        $page = "school_info";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Action Plan";

        $data['data'] = $this->Common->one_cond_row('schools', 'schoolID', $this->uri->segment(2));

        if (!$data['data']) {
            show_404();
        }

        $data['division'] = $this->Page_model->one_cond_row('division', 'id', $data['data']->division_id);
        $data['district'] = $this->Page_model->one_cond_row('district', 'id', $data['data']->district_id);

        $this->load->view('templates/header');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_basic');
    }

    


    function homepage()
    {

        $page = "home";

        if (!file_exists(APPPATH . 'views/' . $page . '.php')) {
            show_404();
        }
        $data['title'] = "Homepage";
        $this->load->view($page, $data);
    }

    function authors()
    {

        $page = "authors";
        $this->load->view('templates/css_author');
        if (!file_exists(APPPATH . 'views/' . $page . '.php')) {
            show_404();
        }
        $data['title'] = "Authors";
        $this->load->view('templates/nav');
        $this->load->view($page, $data);
    }

    function about()
    {

        $page = "about";

        if (!file_exists(APPPATH . 'views/' . $page . '.php')) {
            show_404();
        }
        $data['title'] = "About";
        $this->load->view('templates/css_about');
        $this->load->view('templates/nav');
        $this->load->view($page, $data);
    }

    public function tana_division_delete()
    {
        $this->Page_model->delete('division_tana', 'id', 3);
        $this->session->set_flashdata('danger', 'Successfully deleted.');
        redirect(base_url() . 'pages/tana_summary_division');
    }

    public function tana_region_delete()
    {
        $this->Page_model->delete('region_tana', 'id', 3);
        $this->session->set_flashdata('danger', 'Successfully deleted.');
        redirect(base_url() . 'pages/tana_summary_region');
    }

    public function forgot_password()
    {

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        ', '</div>');
        $this->form_validation->set_rules('email', 'Email', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "fp";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $this->load->view('pages/' . $page);
        } else {
            
            $email_check = $this->Common->one_cond_count_row('users', 'email', $this->input->post('email'));
            
            if ($email_check->num_rows() == 0) {
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => false, 'message' => 'We could not find your email address.']);
                    return;
                }
                $this->session->set_flashdata('failed', 'We could not find your email address.');
                redirect(base_url() . 'Pages/forgot_password');
            } else {
                $this->Page_model->update_request_password();
                if ($this->input->is_ajax_request()) {
                    echo json_encode(['success' => true, 'message' => 'The new password has been sent to your email.']);
                    return;
                }
                $this->session->set_flashdata('success', 'The new password has been sent to your email.');
                redirect(base_url() . 'log_in');
            }
        }
    }

    public function school_update()
    {
        $school_id = $this->uri->segment(3);

        if (empty($school_id)) {
            $school_id = $this->input->post('schoolID', true);
        }

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('schoolName', 'School Name', 'required');

        if ($this->form_validation->run() == FALSE) {

            $page = "school_update";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Update School Information";
            // Try to find by schoolID first, if not found try by recID
            $data['data'] = $this->Common->one_cond_row('schools', 'schoolID', $school_id);
            
            if (!$data['data']) {
                $data['data'] = $this->Common->one_cond_row('schools', 'recID', $school_id);
            }

            if (!$data['data']) {
                show_404();
            }

            $data['division'] = $this->Page_model->one_cond('division', 'region_id', 12);
            $data['districts'] = $this->Page_model->get_districts_by_division($data['data']->division_id);

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {

            $this->Page_model->school_updates();
            $this->Page_model->update_district_id();
            $this->Page_model->user_updates();
            $this->Page_model->dd_updates();
            $this->session->set_flashdata('success', 'Successfully saved.');

            $school_id = trim((string) $this->input->post('schoolID', true));
            $division_id = trim((string) $this->input->post('division_id', true));
            $district_id = trim((string) $this->input->post('d_id', true));
            $redirect_url = base_url();

            if ($this->session->position === 'division') {
                $redirect_url = base_url() . 'pages/schools_division/' . rawurlencode($division_id !== '' ? $division_id : (string) $this->session->division);
            } elseif ($this->session->position === 'ict') {
                $redirect_url = base_url() . 'pages/schools/' . rawurlencode($division_id !== '' ? $division_id : (string) $this->session->division);
            } elseif ($this->session->position === 'district') {
                $redirect_url = base_url() . 'pages/schools_district/' . rawurlencode($district_id !== '' ? $district_id : (string) $this->session->district);
            } elseif (in_array($this->session->position, array('admin', 'region'), true)) {
                $redirect_url = base_url() . 'pages/school_by_district';
            } elseif ($school_id !== '') {
                $redirect_url = base_url() . 'school/' . rawurlencode($school_id);
            }

            redirect($redirect_url);
        }
    }

    public function district_account()
    {

        $page = "district_list_division";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = "Profile List";

        $data['district'] = $this->Page_model->one_cond('district','division_id',$this->session->division);
        $overview = $this->Page_model->division_account_overview($this->session->division);
        $data['schools_by_district'] = $overview['schools_by_district'];
        $data['school_usernames'] = $overview['school_usernames'];
        $data['school_account_ids'] = $overview['school_account_ids'];
        $data['district_user_counts'] = $overview['district_user_counts'];
        $data['school_count'] = $overview['school_count'];
        $data['division'] = $this->Page_model->get_division_setup($this->session->division);
        $data['encoded_total_schools'] = !empty($data['division']->total_schools)
            ? (int) $data['division']->total_schools
            : 0;
        $data['signup_percentage'] = $data['encoded_total_schools'] > 0
            ? ($data['school_count'] / $data['encoded_total_schools']) * 100
            : 0;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function district_new()
    {
        if (!$this->session->logged_in || !in_array($this->session->position, array('division', 'Admin'), true)) {
            show_error('Only division and admin users can access district settings.', 403);
        }

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('description', 'District Name', 'required');

        if ($this->form_validation->run() == FALSE) {
            $page = "district_add";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Add New District";
            
            if ($this->session->position === 'Admin') {
                $data['divisions'] = $this->Page_model->no_cond('division');
            } else {
                $data['division'] = $this->Page_model->one_cond_row('division', 'id', $this->session->division);
            }

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->district_insert();
            $this->session->set_flashdata('success', 'Successfully saved.');
            
            $division_id = $this->input->post('division_id');
            redirect(base_url() . 'pages/district_account/' . $division_id);
        }
    }

    public function district_update()
    {
        if (!$this->session->logged_in || !in_array($this->session->position, array('division', 'Admin'), true)) {
            show_error('Only division and admin users can access district settings.', 403);
        }

        $district_id = $this->uri->segment(3);

        if (empty($district_id)) {
            $district_id = $this->input->post('id', true);
        }

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('description', 'District Name', 'required');

        if ($this->form_validation->run() == FALSE) {
            $page = "district_update";

            if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
                show_404();
            }

            $data['title'] = "Update District";
            $data['district'] = $this->Page_model->one_cond_row('district', 'id', $district_id);

            if (!$data['district']) {
                show_404();
            }

            if ($this->session->position !== 'Admin' && (string) $data['district']->division_id !== (string) $this->session->division) {
                show_error('You can only update districts under your division.', 403);
            }

            $data['division'] = $this->Page_model->one_cond_row('division', 'id', $data['district']->division_id);

            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
        } else {
            $this->Page_model->district_update();
            $this->session->set_flashdata('success', 'Successfully updated.');
            
            $division_id = $this->input->post('division_id');
            redirect(base_url() . 'pages/district_account/' . $division_id);
        }
    }

    public function district_delete()
    {
        if (!$this->session->logged_in || !in_array($this->session->position, array('division', 'Admin'), true)) {
            show_error('Only division and admin users can delete districts.', 403);
        }

        $district_id = $this->uri->segment(3);

        if (empty($district_id)) {
            show_404();
        }

        $district = $this->Page_model->one_cond_row('district', 'id', $district_id);

        if (!$district) {
            show_404();
        }

        // Division users can only delete districts in their own division
        if ($this->session->position === 'division' && (int) $district->division_id !== (int) $this->session->division) {
            show_error('You can only delete districts within your own division.', 403);
        }

        $this->Page_model->district_delete($district_id);
        $this->session->set_flashdata('success', 'Successfully deleted.');
        redirect(base_url() . 'pages/district_account/' . $district->division_id);
    }

    public function division_setup()
    {
        $this->require_division_settings_access();

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>', '</div>');
        $this->form_validation->set_rules('description', 'Division Name', 'trim|required');
        $this->form_validation->set_rules('total_schools', 'Total Number of Schools', 'trim|required|regex_match[/^[0-9]+$/]');

        $page = "division_setup";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $division_id = (int) $this->session->division;
        $data['division'] = $this->Page_model->get_division_setup($division_id);

        if (!$data['division']) {
            show_404();
        }

        $data['title'] = "Division Setup";
        $data['region'] = $this->Page_model->one_cond_row('region', 'id', $data['division']->region_id);
        $data['district_count'] = $this->Page_model->division_district_count($division_id);
        $data['actual_school_count'] = $this->Page_model->division_school_count($division_id);

        if ($this->form_validation->run() == FALSE) {
            $this->load->view('templates/header');
            $this->load->view('templates/menu');
            $this->load->view('pages/' . $page, $data);
            $this->load->view('templates/footer');
            $this->load->view('templates/footer_basic');
            return;
        }

        $this->Page_model->update_division_setup($division_id);
        $this->session->set_flashdata('success', 'Division setup updated successfully.');
        redirect(base_url() . 'pages/division_setup');
    }

    public function division_sgc_details()
    {
        $this->require_division_dashboard_access();

        $status = (int) $this->uri->segment(3);
        $status_labels = array(
            1 => 'Not Yet Organized',
            2 => 'Organized, Not Functional',
            3 => 'Functional'
        );

        if (!isset($status_labels[$status])) {
            show_404();
        }

        $page = "division_count_details";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data['title'] = 'SGC Details';
        $data['hero_title'] = $status_labels[$status] . ' Schools';
        $data['hero_description'] = 'View schools in your division filtered by School Governance Council status.';
        $data['detail_label'] = 'SGC Status';
        $data['detail_badge'] = $status_labels[$status];
        $data['detail_type'] = 'sgc';
        $data['back_url'] = base_url();
        $data['records'] = $this->Page_model->division_schools_by_sgc_status($this->session->division, $status);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function division_sgc_category_printable()
    {
        $this->require_division_dashboard_access();

        $status = (int) $this->uri->segment(3);
        $category_id = (int) $this->uri->segment(4);

        $status_labels = array(
            1 => 'Not Yet Organized',
            2 => 'Organized, Not Functional',
            3 => 'Functional'
        );

        $category_labels = array(
            1 => 'Elementary',
            2 => 'Integrated (Elem & JHS)',
            3 => 'Integrated (Elem, JHS, & SHS)',
            4 => 'Secondary (JHS only)',
            5 => 'Secondary (JHS & SHS)',
            6 => 'SHS - Stand Alone'
        );

        if (!isset($status_labels[$status]) || !isset($category_labels[$category_id])) {
            show_404();
        }

        $all_records = $this->Page_model->division_schools_by_sgc_status($this->session->division, $status);

        // Filter by category
        $filtered_records = array();
        foreach ($all_records as $record) {
            if ((int) $record->category === $category_id) {
                $filtered_records[] = $record;
            }
        }

        $data['title'] = 'SGC Category Schools';
        $data['status_label'] = $status_labels[$status];
        $data['category_label'] = $category_labels[$category_id];
        $data['records'] = $filtered_records;
        $data['division'] = $this->Page_model->one_cond_row('division', 'id', $this->session->division);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/division_sgc_category_printable', $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function division_checklist_completed_details()
    {
        $this->require_division_dashboard_access();

        $page = "checklist_completion_report";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data = $this->get_division_checklist_completed_report_data(false);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function division_checklist_completed_printable()
    {
        $this->require_division_dashboard_access();

        $page = "checklist_completion_report";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $data = $this->get_division_checklist_completed_report_data(true);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    public function division_checklist_completed_shared($division_id = null, $fy = null)
    {
        $page = "checklist_completion_report";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $division_id = trim((string) $division_id);
        $fy = trim((string) $fy);

        if ($division_id === '' || $fy === '' || !ctype_digit($division_id) || !ctype_digit($fy)) {
            show_404();
        }

        $this->validate_division_checklist_share_request($division_id, $fy);

        $data = $this->build_division_checklist_completed_report_data(
            $division_id,
            $fy,
            array(
                'share_mode' => true,
                'back_url' => '',
                'back_label' => '',
                'printable_url' => '',
                'shareable_url' => '',
            )
        );

        $this->load->view('templates/header_public', $data);
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer_public');
        $this->load->view('templates/footer_basic');
    }

    public function region_checklist_completed_report()
    {
        $this->require_region_dashboard_access();

        $page = "checklist_completion_report";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $records = $this->Page_model->region_completed_checklist_report_rows(
            $this->session->region,
            $this->session->fy
        );
        $filter_options = $this->build_checklist_filter_options(
            $records,
            'division_id',
            'division_name',
            'Division'
        );
        $selected_filter = trim((string) $this->input->get('division_id', true));
        $valid_filter_ids = array_map(function ($option) {
            return (string) $option['id'];
        }, $filter_options);

        if ($selected_filter !== '' && !in_array($selected_filter, $valid_filter_ids, true)) {
            $selected_filter = '';
        }

        $filtered_records = $this->filter_checklist_records($records, 'division_id', $selected_filter);

        $data['title'] = 'Regional Checklist Completion Report';
        $data['hero_title'] = 'Completed Self-Assessment Checklist Report';
        $data['hero_description'] = 'Review finalized Self-Assessment Checklist submissions across the region, grouped by district and arranged alphabetically by school.';
        $data['report_scope'] = 'region';
        $data['report_badge'] = 'Fiscal Year ' . $this->session->fy;
        $data['back_url'] = base_url();
        $data['records'] = $filtered_records;
        $data['district_groups'] = $this->build_checklist_report_groups($filtered_records);
        $data['filter_title'] = 'Filter By Division';
        $data['filter_description'] = 'Select one division to narrow the completed checklist report for this region.';
        $data['filter_action_url'] = base_url('Pages/region_checklist_completed_report');
        $data['filter_param'] = 'division_id';
        $data['filter_placeholder'] = 'All Divisions';
        $data['filter_icon'] = 'mdi-domain';
        $data['filter_options'] = $filter_options;
        $data['selected_filter'] = $selected_filter;

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    function sbm_checklist_unlock()
	{
        $position = strtolower(trim((string) $this->session->position));
        $is_division_user = $position === 'division';

        if (!$is_division_user) {
            show_error('Only division users can unlock checklists.', 403);
        }

        $this->ensure_unlock_request_table();

        $checklist_id = $this->uri->segment(3);
        if ($checklist_id) {
            // Mark any pending unlock requests for this checklist as approved
            $this->db->where('checklist_id', $checklist_id)
                ->where('status', 'pending')
                ->update('unlock_request', array(
                    'status' => 'approved',
                    'processed_date' => date('Y-m-d H:i:s'),
                    'processed_by' => $this->session->username
                ));
        }

		$this->Page_model->sbm_cecklist_lock_unloc(0);
		$this->session->set_flashdata('success', 'Checklist unlocked successfully. The school can now edit it again.');
        redirect($_SERVER['HTTP_REFERER']);
	}

    public function district_userlist_by_division()
    {
        $this->require_user_manager();

        $page = "user_list";

        if (!file_exists(APPPATH . 'views/pages/' . $page . '.php')) {
            show_404();
        }

        $id = $this->uri->segment(3);
        $this->validate_managed_district($id);
        $district = $this->Page_model->one_cond_row('district', 'id', $id);

        if (!$district) {
            show_404();
        }

        $data['title'] = "District User Accounts";
        $data['division_scope'] = true;
        $data['district_user_scope'] = true;
        $data['district'] = $district;
        $data['district_user_back_url'] = base_url() . 'pages/district_account/' . rawurlencode($district->division_id);
        $data['users'] = $this->Page_model->two_cond('users','position','district','d_id',$id);

        $this->load->view('templates/header_dt');
        $this->load->view('templates/menu');
        $this->load->view('pages/' . $page, $data);
        $this->load->view('templates/footer');
        $this->load->view('templates/footer_dt');
    }

    function update_tana_summary()
	{
		$this->Page_model->tana_summary_del();
		$this->session->set_flashdata('success', 'Saved successfully.');
		redirect($_SERVER['HTTP_REFERER']);
	}

    function final_tana_summary()
	{
		$this->session->set_flashdata('success', 'TANA summary updates are always editable for school accounts.');
		redirect($_SERVER['HTTP_REFERER']);
	}

     function change_password_user()
	{
		if (!$this->session->logged_in) {
			redirect(base_url('homepage'));
			return;
		}
		$this->form_validation->set_rules('current_password', 'Current Password', 'required');
		$this->form_validation->set_rules('password', 'New Password', 'required|min_length[8]');
		$this->form_validation->set_rules('password_confirm', 'Confirm New Password', 'required|matches[password]');
		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('danger', 'Provide your current password and matching new passwords of at least 8 characters.');
		} elseif (!$this->Page_model->user_password_change()) {
			$this->session->set_flashdata('danger', 'Your current password is incorrect.');
		} else {
			$this->session->set_flashdata('success', 'Password successfully changed.');
		}
		redirect($_SERVER['HTTP_REFERER']);
	}

    function change_password_user_division()
	{
		$this->Page_model->division_user_password_change();
		$this->session->set_flashdata('success', 'Password successfully changed.');
		redirect($_SERVER['HTTP_REFERER']);
	}

    function add_school_user()
	{
        $schoolName = rawurldecode($this->uri->segment(4));
        $school_id = $this->uri->segment(3);
        $district = $this->uri->segment(5);
        $division = $this->uri->segment(6);
        
		$this->Page_model->add_school_user($school_id,$schoolName,$district,$division);
		$this->session->set_flashdata('success', 'Password successfully changed.');
		redirect($_SERVER['HTTP_REFERER']);
	}
    





















}
