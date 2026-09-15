<?php
$csrf = new CSRF($session_class);
$activePage = ACTIVE_PAGE; ## active page (e.g. "index")  

$g_photo = $g_photo ?? 'profile-img.png';
$g_fullname = $g_fullname ?? null;
?>
<!-- ======= Header ======= -->
<header class="w-100 class-header-fixed">
    <div class="glass-topbar">
        <div class="container-xl py-3 px-3 d-flex justify-content-between align-items-center">
            <a class="dropdown-item d-flex align-items-center" href="<?php echo BASE_URL; ?>home">
                <div class="d-flex align-items-center nav-left">
                    <img src="<?php echo DISPLAY_LOGO; ?>" alt="Image" class="d-inline-block d-md-none header-logo" style="width: 45px">
                    <img src="<?php echo LOGO; ?>" alt="Image" class="d-none d-md-inline-block header-logo">
                </div>
            </a>

            <div class="d-flex gap-4 ms-2">
                <!-- datetime -->
                <div class="d-flex align-items-center text-white fw-normal fs-6">
                    <span id="now" class="d-none d-sm-inline-block"></span>
                </div>

                <nav class="header-nav ms-auto">
                    <ul class="d-flex align-items-center mb-0 list-unstyled">
                        <li class="nav-item dropdown">
                            <a class="nav-link nav-profile d-inline-flex align-items-center pe-0" href="#" id="dropdownMenuClickableInside" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <div class="rounded-circle overflow-hidden flex-shrink-0 d-inline-block position-relative" style="width: 50px; height: 50px; min-width: 50px; min-height: 50px; aspect-ratio: 1 / 1;">
                                    <!-- Avatar Image -->
                                    <img id="navHeaderProfilePic"
                                        src="<?= !empty($g_photo) ? BASE_UPLOAD_PROFILE_USER_PATH . htmlspecialchars($g_photo, ENT_QUOTES, 'UTF-8') : '' ?>"
                                        alt="User Profile"
                                        class="rounded-circle <?= empty($g_photo) ? 'd-none' : 'd-block' ?>"
                                        style="width: 100%; height: 100%; object-fit: cover; aspect-ratio: 1 / 1;"
                                        onerror="this.classList.remove('d-block'); this.classList.add('d-none'); document.getElementById('navHeaderInitialsFallback').classList.remove('d-none'); document.getElementById('navHeaderInitialsFallback').classList.add('d-flex');">

                                    <!-- Initials Fallback -->
                                    <div id="navHeaderInitialsFallback"
                                        class="dp-initials rounded-circle align-items-center justify-content-center fw-bold text-white <?= !empty($g_photo) ? 'd-none' : 'd-flex' ?>"
                                        style="width: 100%; height: 100%; background-color: #08184a; font-size: 0.95rem; aspect-ratio: 1 / 1;">
                                        <?= htmlspecialchars($g_initials ?? 'U', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </a>

                            <!-- Glass Dropdown Context Menu -->
                            <ul class="dropdown-menu dropdown-menu-end profile glass-dropdown" aria-labelledby="dropdownMenuClickableInside">
                                <li class="dropdown-header text-start">
                                    <h6 class="mb-0 text-custom"><?php echo $g_fullname; ?></h6>
                                </li>
                                <li class="my-1">
                                    <hr class="dropdown-divider border-secondary" style="opacity: 0.25;">
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="<?php echo BASE_URL; ?>profile">
                                        <i class="bi bi-person me-2"></i>
                                        <span>My Profile</span>
                                    </a>
                                </li>
                                <!-- <li>
                                    <a class="dropdown-item d-flex align-items-center" href="#!" data-bs-toggle="modal" data-bs-target="#accountInfoModal">
                                        <i class="bi bi-person me-2"></i><span>My Account</span>
                                    </a>
                                </li> -->
                                <li>
                                    <hr class="dropdown-divider border-secondary" style="opacity: 0.25;">
                                </li>
                                <li>
                                    <form id="logout_form" action="<?php echo BASE_URL; ?>logout" method="post" class="m-0">
                                        <button type="submit" class="dropdown-item d-flex align-items-center bg-transparent border-0 w-100" id="logoutSubmit" name="logoutSubmit" value="submitLogout">
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            <span>Sign Out</span>
                                        </button>
                                        <?php echo $csrf->input('token_logout_form', 'token_logout_form', 3600, 1); ?>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</header>
<!-- ======= End Header ======= -->