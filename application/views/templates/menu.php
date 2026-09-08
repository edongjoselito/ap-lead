<body>

    <!-- Begin page -->
    <div id="wrapper">


        <!-- Topbar Start -->
        <div class="navbar-custom">
            <ul class="list-unstyled topnav-menu float-right mb-0">

                <?php if ($this->session->position == 'division') : ?>
                    <?php
                    // Ensure table exists before querying
                    $this->db->query("CREATE TABLE IF NOT EXISTS `unlock_request` (
                      `id` int(11) NOT NULL AUTO_INCREMENT,
                      `request_type` enum('ta','checklist') NOT NULL DEFAULT 'ta',
                      `ta_id` int(11) DEFAULT NULL,
                      `checklist_id` int(11) DEFAULT NULL,
                      `school_id` varchar(255) NOT NULL,
                      `division_id` int(11) NOT NULL,
                      `requested_by` varchar(255) NOT NULL,
                      `request_date` datetime NOT NULL,
                      `status` enum('pending','approved','cleared') DEFAULT 'pending',
                      `processed_date` datetime DEFAULT NULL,
                      `processed_by` varchar(255) DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      KEY `request_type` (`request_type`),
                      KEY `ta_id` (`ta_id`),
                      KEY `checklist_id` (`checklist_id`),
                      KEY `school_id` (`school_id`),
                      KEY `division_id` (`division_id`),
                      KEY `status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    $pending_requests = $this->db->where('division_id', $this->session->division)
                        ->where('status', 'pending')
                        ->order_by('request_date', 'DESC')
                        ->get('unlock_request')
                        ->result();
                    $request_count = count($pending_requests);
                    ?>
                    <li class="dropdown notification-list">
                        <a class="nav-link dropdown-toggle arrow-none waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                            <i class="mdi mdi-bell-outline noti-icon"></i>
                            <?php if ($request_count > 0) : ?>
                                <span class="noti-icon-badge"><?= $request_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-lg">
                            <div class="dropdown-item noti-title">
                                <h5 class="m-0">
                                    <span class="float-right">
                                        <a href="<?= base_url(); ?>Pages/clear_unlock_requests" class="text-dark">
                                            <small>Clear All</small>
                                        </a>
                                    </span>
                                    Unlock Requests
                                </h5>
                            </div>
                            <?php if ($request_count > 0) : ?>
                                <?php foreach ($pending_requests as $request) : ?>
                                    <?php
                                    $school = $this->Common->one_cond_row('schools', 'schoolID', $request->school_id);
                                    $school_name = $school ? $school->schoolName : 'Unknown School';
                                    $request_type_label = $request->request_type == 'ta' ? 'TA Report' : 'Checklist';
                                    ?>
                                    <a href="<?= base_url(); ?>Pages/unlock_request_view/<?= $request->id; ?>" class="dropdown-item notify-item active">
                                        <div class="notify-icon">
                                            <i class="mdi mdi-lock-open-variant-outline text-primary"></i>
                                        </div>
                                        <p class="notify-details">
                                            <strong><?= html_escape($school_name); ?></strong>
                                            <small class="text-muted">Requested <?= html_escape($request_type_label); ?> unlock</small>
                                            <small class="text-muted"><?= date('M d, H:i', strtotime($request->request_date)); ?></small>
                                        </p>
                                    </a>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="dropdown-item notify-item">
                                    <div class="notify-icon">
                                        <i class="mdi mdi-check-circle-outline text-success"></i>
                                    </div>
                                    <p class="notify-details">
                                        <strong>No pending requests</strong>
                                        <small class="text-muted">All unlock requests have been processed</small>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <a href="<?= base_url(); ?>Pages/unlock_requests" class="dropdown-item notify-item text-center text-primary">
                                View All Requests
                            </a>
                        </div>
                    </li>
                <?php endif; ?>

                <li class="dropdown notification-list">
                    <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <?php $image = $this->Common->one_cond_row_select('users','username,image','username',$this->session->username); if (empty($image->image ?? null)) {?>
                            <img src="<?= base_url(); ?>assets/images/users/avatar-1.jpg" alt="user-image" class="rounded-circle">
                        <?php }else{ ?>
                            <img src="<?= base_url(); ?>uploads/<?= $image->image; ?>" alt="user-image" class="rounded-circle">
                        <?php } ?>
                        <span class="pro-user-name ml-1">
                            <?= $this->session->user; ?> <i class="mdi mdi-chevron-down"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right profile-dropdown ">
                        <!-- item-->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome !</h6>
                        </div>

                        <!-- item-->
                        <?php if($this->session->position == 'school'){?>
                            <a href="<?= base_url(); ?>school/<?= $this->session->username; ?>" class="dropdown-item notify-item">
                        <?php }else{ ?>
                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <?php } ?>
                            <i class="mdi mdi-account-outline"></i>
                            <span>Profile</span>
                        </a>

                        <!-- item-->
                        <a href="<?= base_url(); ?>lock" class="dropdown-item notify-item" data-toggle="modal" data-target="#ivankylecrodua">
                            <i class="mdi mdi-lock-outline"></i>
                            <span>Change Profile Image</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- item-->
                        <a href="<?= base_url(); ?>logout" class="dropdown-item notify-item">
                            <i class="mdi mdi-logout-variant"></i>
                            <span>Logout</span>
                        </a>

                    </div>
                </li>




            </ul>

            <!-- LOGO -->
            <div class="logo-box">
                <a href="<?= base_url(); ?>" class="logo text-center logo-dark">
                    <span class="logo-lg">
                        <img src="<?= base_url(); ?>assets/logo.png" alt="Least Learned Competencies Monitoring" height="42">
                        <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                    </span>
                    <span class="logo-sm">
                        <!-- <span class="logo-lg-text-dark">V</span> -->
                        <img src="<?= base_url(); ?>assets/logo.png" alt="Least Learned Competencies Monitoring" height="28">
                    </span>
                </a>

                <a href="<?= base_url(); ?>" class="logo text-center logo-light">
                    <span class="logo-lg">
                        <img src="<?= base_url(); ?>assets/logo.png" alt="Least Learned Competencies Monitoring" height="42">
                        <!-- <span class="logo-lg-text-dark">Velonic</span> -->
                    </span>
                    <span class="logo-sm">
                        <!-- <span class="logo-lg-text-dark">V</span> -->
                        <img src="<?= base_url(); ?>assets/logo.png" alt="Least Learned Competencies Monitoring" height="28">
                    </span>
                </a>
            </div>

            <!-- LOGO -->


            <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
                <li>
                    <button class="button-menu-mobile waves-effect">
                        <i class="mdi mdi-menu"></i>
                    </button>
                </li>


            </ul>
        </div>
        <!-- end Topbar --> <!-- ========== Left Sidebar Start ========== -->

        <div class="left-side-menu">

            <div class="slimscroll-menu">

                <!--- Sidemenu -->
                <div id="sidebar-menu">
                    <ul class="metismenu" id="side-menu">

                        <li class="menu-title">Navigation</li>

                        <li><a href="<?= base_url(); ?>" class="waves-effect"><i class="mdi mdi-view-dashboard"></i><span> Dashboard </span></a></li>
                        <?php if ($this->session->position == 'district') { ?>
                            <li><a href="<?= base_url(); ?>pages/schools_district/<?= $this->session->district; ?>" class="waves-effect"><i class="fas fa-school"></i><span>Schools</span></a></li>
                            <li>
                                <a href="<?= base_url(); ?>Pages/school_list" class="waves-effect">
                                    <i class="fas fa-book-reader"></i>
                                    <span> SBM </span>
                                </a>

                            </li>
                            <li>
                                <a href="<?= base_url(); ?>Pages/sbm_district_tech" class="waves-effect">
                                    <i class="fas fa-wrench"></i>
                                    <span> Technical Assisstance </span>
                                </a>

                            </li>
                            <li><a href="#" class="waves-effect" data-toggle="modal" data-target="#renren"><i class="fas fa-lock"></i><span>Change Password</span></a></li>
                        <?php } ?>

                        <?php if ($this->session->position == 'school') { ?>

                            <li><a href="<?= base_url(); ?>Pages/learning_gap" class="waves-effect"><i class="fas fa-chart-bar"></i><span> Learning Gap Monitoring</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_records" class="waves-effect"><i class="fas fa-list"></i><span> My Learning Gap Records</span></a></li>

                            <li><a href="#" class="waves-effect" data-toggle="modal" data-target="#renren"><i class="fas fa-lock"></i><span>Change Password</span></a></li>
                        <?php } ?>

                        <?php if ($this->session->position == 'division') { ?>
                            <!-- <li><a href="<?= base_url(); ?>pages/schools_division/<?= $this->session->division; ?>" class="waves-effect"><i class="fas fa-school"></i><span>Schools</span></a></li> -->
                            <li>
                                <a href="<?= base_url(); ?>Pages/learning_gap" class="waves-effect"><i class="fas fa-chart-bar"></i><span> Learning Gap Overview</span></a>
                            </li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_school_summary" class="waves-effect"><i class="fas fa-school"></i><span> Learning Gap Summary</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_records" class="waves-effect"><i class="fas fa-list"></i><span> Submitted Learning Gap</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/school_submission_monitoring" class="waves-effect"><i class="mdi mdi-clipboard-check-outline"></i><span> School Submissions</span></a></li>
                            <li>
                                <a href="javascript: void(0);" class="waves-effect">
                                    <i class="fas fa-cogs"></i>
                                    <span> Division Settings</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="<?= base_url(); ?>pages/district_account/<?= $this->session->division; ?>">Districts</a></li>
                                    <li><a href="<?= base_url(); ?>pages/userlist_division">Manage Users</a></li>
                                    <li><a href="<?= base_url(); ?>pages/schools_division/<?= $this->session->division; ?>">Manage Schools</a></li>
                                    <li><a href="<?= base_url(); ?>Pages/division_setup">Division Setup</a></li>
                                </ul>
                            </li>
                            <li><a href="#" class="waves-effect" data-toggle="modal" data-target="#renren"><i class="fas fa-lock"></i><span>Change Password</span></a></li>

                        <?php } ?>

                        <?php if ($this->session->position == 'division_head') { ?>
                            <!-- <li><a href="<?= base_url(); ?>pages/schools_division/<?= $this->session->division; ?>" class="waves-effect"><i class="fas fa-school"></i><span>Schools</span></a></li> -->
                            
                            <li><a href="<?= base_url(); ?>Pages/learning_gap" class="waves-effect"><i class="fas fa-chart-bar"></i><span> Learning Gap Overview</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_school_summary" class="waves-effect"><i class="fas fa-school"></i><span> Learning Gap Summary</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_records" class="waves-effect"><i class="fas fa-list"></i><span> Submitted Learning Gap</span></a></li>
                            <li><a href="#" class="waves-effect" data-toggle="modal" data-target="#renren"><i class="fas fa-lock"></i><span>Change Password</span></a></li>

                        <?php } ?>

                        <?php if ($this->session->position == 'region') { ?>

                            <li><a href="<?= base_url(); ?>Pages/learning_gap" class="waves-effect"><i class="fas fa-chart-bar"></i><span> Learning Gap Overview</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_school_summary" class="waves-effect"><i class="fas fa-school"></i><span> Learning Gap Summary</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_gap_records" class="waves-effect"><i class="fas fa-list"></i><span> Submitted Learning Gap</span></a></li>
                            <li><a href="<?= base_url(); ?>Pages/learning_area_setup" class="waves-effect"><i class="mdi mdi-tune"></i><span> Learning Area Setup</span></a></li>
                            <li><a href="#" class="waves-effect" data-toggle="modal" data-target="#renren"><i class="fas fa-lock"></i><span>Change Password</span></a></li>

                        <?php } ?>

                        <?php if ($this->session->position == 'admin') { ?>
                            <li>
                                <a href="javascript: void(0);" class="waves-effect">
                                    <i class="fas fa-school"></i>
                                    <span> Schools</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="<?= base_url(); ?>pages/school_by_district">Schools By Division</a></li>
                                    <li><a href="<?= base_url(); ?>pages/school_list">List of Schools</a></li>
                                </ul>
                            </li>
                            <li><a href="<?= base_url(); ?>pages/userlist" class="waves-effect"><i class="mdi mdi-account-supervisor"></i><span> Manage Users </span></a></li>
                            <li>
                                <a href="javascript: void(0);" class="waves-effect">
                                    <i class="mdi mdi-chart-line"></i>
                                    <span> Reports</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <ul class="nav-second-level" aria-expanded="false">
                                    <li><a href="<?= base_url(); ?>Pages/report_division_submission">Division Submission</a></li>
                                    <li><a href="<?= base_url(); ?>Pages/report_overall_accomplishments">Overall Accomplishments</a></li>
                                    <li><a href="<?= base_url(); ?>Pages/report_sgc">School Governance Council</a></li>
                                </ul>
                            </li>
                        <?php } ?>

                        <?php if ($this->session->position == 'ict') { ?>
                            <li><a href="<?= base_url(); ?>pages/schools/<?= $this->session->division; ?>" class="waves-effect"><i class="fas fa-school"></i><span>Schools</span></a></li>
                            <li><a href="<?= base_url(); ?>pages/userlist_division" class="waves-effect"><i class="mdi mdi-account-supervisor"></i><span> Manage Users </span></a></li>
                        <?php } ?>
                    </ul>

                </div>
                <!-- End Sidebar -->

                <div class="clearfix"></div>

            </div>
            <!-- Sidebar -left -->

        </div>
        <!-- Left Sidebar End -->


        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">
