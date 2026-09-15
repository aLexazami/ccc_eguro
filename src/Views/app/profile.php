<?php
// Core Config & Dependencies
$db_connect = $db_connect ?? null;
$g_user_id = $g_user_id ?? null;
$g_email_address = $g_email_address ?? null;

// Server Execution Limits (uncomment ONLY for long-running scripts like reports/imports)
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;

// Initialize Security & Fetch Database Records
$csrf = new CSRF($session_class);

$users            = $helper->selectDataExists('users', ['id' => $g_user_id]) ?? [];
$employee         = $helper->selectDataExists('employee', ['user_id' => $g_user_id]) ?? [];
$student          = $helper->selectDataExists('student', ['user_id' => $g_user_id]) ?? [];
$employee_profile = $helper->selectDataExists('employee_profile', ['user_id' => $g_user_id]) ?? [];

// Dynamic Name Extraction
$fName        = $users['first_name'] ?? '';
$mName        = $users['middle_name'] ?? '';
$lName        = $users['last_name'] ?? '';
$suffix       = $users['suffix'] ?? '';
$post_nominal = $users['post_nominal'] ?? '';

// Dynamic Contact & Title Extraction
$userEmail = $users['email'] ?? '';
$userPhone = $users['contact_no'] ?? '';
$userTitle = !empty($employee_profile['title']) ? $employee_profile['title'] : ($employee['position'] ?? '');

// Format primary name via formatter class instance
$dynamicName = $helper->formatFullName($fName, $mName, $lName, $suffix, $post_nominal, 'title');

// Fallback Profile Data Definition from `employee_profile`
$profile = [
    "name"              => !empty($dynamicName) ? $dynamicName : "",
    "name_format"       => $employee_profile['name_format'] ?? 'full',
    "post_nominal"      => $post_nominal,
    "post_nominal_flag" => $employee_profile['post_nominal_flag'],
    "verified"          => true,
    "title"             => $userTitle,
    "company"           => $employee_profile['company'] ?? "City College of Calamba",
    "bio"               => $employee_profile['bio'] ?? "",
    "avatar"            => !empty($employee['profile_pic']) ? BASE_UPLOAD_PROFILE_EMP_PATH . $employee['profile_pic'] : "",
    "banner"            => !empty($employee['cover_photo']) ? BASE_UPLOAD_COVER_EMP_PATH . $employee['cover_photo'] : "",
    "card_layout"       => $employee_profile['card_layout'] ?? "classic",
    "card_theme"        => $employee_profile['card_theme'] ?? "emerald",
    "card_uid"          => $employee_profile['card_uid'] ?? $helper->generateCardUid(4, '', 'employee_profile', 'card_uid'),
    "phone"             => $userPhone,
    "email"             => $userEmail,
    "website"           => $employee_profile['website'] ?? "https://ccc.edu.ph",
    "address"           => $employee_profile['address'] ?? "Old Municipal Site, Barreto St, Brgy. VII, Poblacion, Calamba City, Laguna, Philippines",
    "hours"             => $employee_profile['hours'] ?? "",
    "social_options"    => $employee_profile['social_options'] ?? "",
];

// Computed Fields
$profile["initials"]           = getNameInitials($fName . " " . $lName);
$profile["phone_url"]          = getSocialUrl("phone", $profile['phone']);
$profile["website_url"]        = getSocialUrl("website", $profile['website']);
$profile['company_logo']       = CCC_LOGO;
$profile['company_logo_white'] = CCC_WHITE_LOGO;

/**
 * Generates uppercase 2-letter initials from a full name string.
 */
function getNameInitials($name)
{
    $name = trim($name ?? '');
    if (empty($name)) {
        return '??';
    }

    $cleanName = preg_replace('/[^\w\s-]/', '', $name);
    $words     = array_values(array_filter(preg_split('/[\s-]+/', $cleanName)));

    if (empty($words)) {
        return '??';
    }

    if (count($words) === 1) {
        return strtoupper(substr($words[0], 0, 2));
    }

    $first = $words[0][0] ?? '';
    $last  = $words[count($words) - 1][0] ?? '';

    return strtoupper($first . $last);
}

/**
 * Normalizes Philippine mobile numbers into standard dialer strings (639xxxxxxxxx).
 */
function formatPhPhone($number)
{
    $digits = preg_replace('/[^0-9]/', '', $number ?? '');

    if (empty($digits)) {
        return '#';
    }

    if (substr($digits, 0, 2) === '09' && strlen($digits) === 11) {
        $digits = '63' . substr($digits, 1);
    } elseif (substr($digits, 0, 1) === '9' && strlen($digits) === 10) {
        $digits = '63' . $digits;
    }

    return $digits;
}

/**
 * Transforms generic handle inputs or absolute URLs into structured platform schemes.
 */
function getSocialUrl($platform, $value)
{
    $value = trim($value ?? '');
    if (empty($value)) {
        return '#';
    }

    // Passthrough direct URLs or custom app schemes
    if (preg_match("~^(?:f|ht)tps?://~i", $value) || strpos($value, 'viber://') === 0) {
        return $value;
    }

    $cleanValue = ltrim($value, '@');
    $platform   = strtolower($platform);

    switch ($platform) {
        case 'phone':
            $phone = formatPhPhone($cleanValue);
            return $phone !== '#' ? "tel:+{$phone}" : '#';

        case 'website':
            return "https://" . $cleanValue;

        case 'viber':
            $phone = formatPhPhone($cleanValue);
            return $phone !== '#' ? "viber://chat?number={$phone}" : '#';

        case 'whatsapp':
            $phone = formatPhPhone($cleanValue);
            return $phone !== '#' ? "https://wa.me/{$phone}" : '#';

        case 'linkedin':
            return "https://linkedin.com/in/{$cleanValue}";

        case 'facebook':
            return "https://facebook.com/{$cleanValue}";

        case 'instagram':
            return "https://instagram.com/{$cleanValue}";

        case 'tiktok':
            return "https://tiktok.com/@{$cleanValue}";

        case 'youtube':
            return "https://youtube.com/@{$cleanValue}";

        case 'github':
            return "https://github.com/{$cleanValue}";

        case 'x/twitter':
        case 'twitter':
            return "https://x.com/{$cleanValue}";

        case 'telegram':
            return "https://t.me/{$cleanValue}";

        case 'threads':
            return "https://www.threads.net/@{$cleanValue}";

        case 'behance':
            return "https://behance.net/{$cleanValue}";

        case 'pinterest':
            return "https://pinterest.com/{$cleanValue}";

        case 'snapchat':
            return "https://snapchat.com/add/{$cleanValue}";

        case 'dribbble':
            return "https://dribbble.com/{$cleanValue}";

        default:
            return '#';
    }
}

$allowedAccess = SYSTEM_FLAG === "PROD";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include META_DATA_PATH; ?>
    <?php include LINK_DATA_PATH; ?>
    <style>
        #accountTabs .nav-link {
            color: #64748b;
            transition: all 0.2s ease-in-out;
        }

        #accountTabs .nav-link.active {
            background-color: #08184a !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(8, 24, 74, 0.15);
        }

        #accountInfoModal .form-control:focus,
        #accountInfoModal .btn-outline-secondary:focus {
            border-color: #08184a !important;
            box-shadow: 0 0 0 0.2rem rgba(8, 24, 74, 0.15);
        }

        .main-card-container {
            max-width: 900px;
            margin: 2rem auto;
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100 body-custom">
    <div class="content-wrapper flex-grow-1">
        <?php include HEADER_PATH; ?>
        <main class="base-main mb-5">
            <div class="intro-section pb-1">
                <div class="container-xl py-4 px-3">
                    <!-- <span class="font-size-s text-white"><?php echo html(SYSTEM_NAME); ?> Services</span> -->
                    <p class="fs-1 fw-bold text-white">Account and Profile Details</p>
                    <p class="intro-section__description text-white">To access the appropriate system, click the button on the card below that corresponds to the page you need.</p>
                </div>
            </div>
            
            <div class="container-xl  <?php echo ($allowedAccess ? 'col-lg-11 col-md-12' : 'col-lg-8 col-md-12'); ?> mt-5" style="max-width: 2040px;">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3 mb-4" id="accountTabs" role="tablist" style="border: 1px solid #e2e8f0;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                                <i class="bi bi-person-badge me-1"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="username-tab" data-bs-toggle="tab" data-bs-target="#username-pane" type="button" role="tab">
                                <i class="bi bi-at me-1"></i>Username
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-pane" type="button" role="tab">
                                <i class="bi bi-envelope-at me-1"></i>Recovery Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab">
                                <i class="bi bi-shield-lock me-1"></i>Password
                            </button>
                        </li>

                        <?php if ($allowedAccess): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee-pane" type="button" role="tab">
                                    <i class="bi bi-briefcase me-1"></i>Employee Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="student-tab" data-bs-toggle="tab" data-bs-target="#student-pane" type="button" role="tab">
                                    <i class="bi bi-mortarboard me-1"></i>Student Profile
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-3 py-2 fw-semibold small d-flex align-items-center justify-content-center gap-1" id="card-tab" data-bs-toggle="tab" data-bs-target="#card-pane" type="button" role="tab">
                                    <i class="bi bi-person-vcard me-1"></i>Employee Digital Profile
                                </button>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content" id="accountTabsContent">

                        <!-- GENERAL INFO PANE -->
                        <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
                            <div class="p-3 mb-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #08184a;">
                                <small class="text-secondary d-flex align-items-center gap-2">
                                    <i class="bi bi-info-circle-fill text-dark fs-6"></i> Managing your general user information and profile picture.
                                </small>
                            </div>

                            <div id="general_info_msg_alert" class="alert alert-danger d-none py-2 mb-3 rounded-3" role="alert"></div>

                            <div class="col-12 border p-3 rounded-3 mb-3"><!-- PROFILE PICTURE DISPLAY & UPLOAD -->
                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-person-bounding-box me-2"></i>Profile Picture
                                </h6>
                                <div class="card border-0 bg-light p-3 rounded-3 mb-1" style="border: 1px solid #e2e8f0 !important;">
                                    <form id="form_update_user_avatar" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="action_type" id="avatarActionType" value="upload">

                                        <div class="d-flex align-items-center gap-4 flex-wrap">
                                            <div class="text-center">
                                                <div class="profile-container position-relative">
                                                    <img id="userProfilePicPreview" src="<?= !empty($users['profile_pic']) ? BASE_UPLOAD_PROFILE_USER_PATH . htmlspecialchars($users['profile_pic'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Profile Picture" class="rounded-circle img-thumbnail shadow-sm <?= empty($users['profile_pic']) ? 'd-none' : '' ?>" style="width: 110px; height: 110px; object-fit: cover;">

                                                    <div id="userProfileInitialsFallback" class="profile-initials <?= !empty($users['profile_pic']) ? 'd-none' : '' ?>">
                                                        <?= htmlspecialchars($g_initials ?? 'U', ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex-grow-1">
                                                <label for="userProfilePic" class="form-label fw-semibold text-dark small mb-1">Upload New Picture</label>
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;">
                                                        <i class="bi bi-image"></i>
                                                    </span>
                                                    <input type="file" name="profile_pic" id="userProfilePic" class="form-control border-start-0 rounded-end-3" accept=".jpeg,.jpg,.png,.webp" style="border-color: #cbd5e1;" onchange="previewImage(this, 'userProfilePicPreview')">
                                                </div>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">Supported formats: JPEG, PNG, WEBP (Max: 5MB)</small>
                                            </div>

                                            <div class="align-self-end d-flex gap-2">
                                                <button type="button" id="btnUserRemovePhoto" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold <?= empty($users['profile_pic']) ? 'd-none' : '' ?>">
                                                    <i class="bi bi-trash-fill"></i>&ensp;Remove Picture
                                                </button>

                                                <button type="submit" id="btnUserSavePhoto" class="btn btn-sm text-white rounded-pill px-4 fw-bold" style="background-color: #08184a;">
                                                    <i class="bi bi-check2"></i>&ensp;Save Picture
                                                </button>
                                            </div>
                                        </div>
                                        <?php echo $csrf->input('token_update_general_profile', 'token_update_general_profile', 3600, 1); ?>
                                    </form>
                                </div>
                            </div>

                            <!-- USER DETAILS FORM -->
                            <div class="col-12 border p-3 rounded-3">
                                <form id="form_update_general_info" method="post">
                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                        <i class="bi bi-person-vcard me-2"></i>Personal Details
                                    </h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold text-dark small mb-1">First Name</label>
                                            <input type="text" name="first_name" class="form-control rounded-3 bg-light border-1" value="<?php echo htmlspecialchars($users['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;" readonly disabled>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold text-dark small mb-1">Middle Name</label>
                                            <input type="text" name="middle_name" class="form-control rounded-3 bg-light border-1" value="<?php echo htmlspecialchars($users['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;" readonly disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold text-dark small mb-1">Last Name</label>
                                            <input type="text" name="last_name" class="form-control rounded-3 bg-light border-1" value="<?php echo htmlspecialchars($users['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;" readonly disabled>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label fw-semibold text-dark small mb-1">Extension Name</label>
                                            <input type="text" name="suffix" class="form-control rounded-3 bg-light border-1" value="<?php echo htmlspecialchars($users['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;" readonly disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold text-dark small mb-1">Post Nominal (Suffix Titles)</label>
                                            <input type="text" name="post_nominal" class="form-control rounded-3 border-1" value="<?php echo htmlspecialchars($users['post_nominal'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                            <small class="text-muted">e.g., PhD, LPT, RN</small>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Sex <span class="required-field"></span></label>
                                            <select name="sex" class="form-select rounded-3" style="border-color: #cbd5e1;">
                                                <option value="male" <?php echo (strtolower($users['sex'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo (strtolower($users['sex'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="birth_date" class="form-label fw-semibold text-dark small mb-1">Birth Date <span class="required-field"></span></label>
                                            <input type="text" name="birth_date" id="birth_date" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="birth_place" class="form-label fw-semibold text-dark small mb-1">Birth Place</label>
                                            <input type="text" name="birth_place" id="birth_place" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['birth_place'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1"><b>Civil Status</b></label>
                                            <select name="civil_status" id="civil_status" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['civil_status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                                <option value="" disabled>Select Civil Status</option>
                                                <?php
                                                foreach (CIVIL_STATUS as $civil_status) {
                                                    echo '<option value="' . $civil_status . '">' . ucfirst($civil_status) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Nationality</label>
                                            <input type="text" name="nationality" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['nationality'] ?? 'Filipino', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Contact Number <span class="required-field"></span></label>
                                            <input type="text" name="contact_no" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['contact_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xxxxxxxxx" style="border-color: #cbd5e1;">
                                            <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                        <i class="bi bi-person-lines-fill me-2"></i>Address Details
                                    </h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold text-dark small mb-1">Home Address</label>
                                            <input type="text" name="home_address" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['home_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                            <small class="text-muted">House No./Street, Subdivision/Sitio</small>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Barangay</label>
                                            <input type="text" name="brgy" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['brgy'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">City/Municipality</label>
                                            <input type="text" name="city" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Province</label>
                                            <input type="text" name="province" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['province'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                        <i class="bi bi-person-rolodex me-2"></i>Emergency Contact Details
                                    </h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold text-dark small mb-1">Full Name</label>
                                            <input type="text" name="e_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['e_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Relationship</label>
                                            <input type="text" name="e_relationship" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['e_relationship'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Contact Number</label>
                                            <input type="text" name="e_contact" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['e_contact'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                            <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold text-dark small mb-1">Address</label>
                                            <input type="text" name="e_address" class="form-control rounded-3" value="<?php echo htmlspecialchars($users['e_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="border-color: #cbd5e1;">
                                            <small class="text-muted">House No./Street, Subdivision/Sitio, Barangay, City, Province</small>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                        <i class="bi bi-shield-lock me-2"></i>Account & Institutional Parameters
                                    </h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold text-dark small mb-1">Username</label>
                                            <input type="text" class="form-control rounded-3 bg-light border-1" id="displayUsername" value="<?php echo htmlspecialchars($g_username ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold text-dark small mb-1">CCC Institutional Email</label>
                                            <input type="email" class="form-control rounded-3 bg-light border-1" value="<?php echo htmlspecialchars($g_email_address ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" id="btn_save_general_info" style="background-color: #08184a;">
                                            Save General Info
                                        </button>
                                    </div>
                                    <?php echo $csrf->input('token_update_general_info', 'token_update_general_info', 3600, 1); ?>
                                </form>
                            </div>
                        </div>

                        <!-- USERNAME PANE -->
                        <div class="tab-pane fade" id="username-pane" role="tabpanel">
                            <form id="form_update_username" method="post">
                                <div id="username_msg_alert" class="alert alert-danger d-none py-2 mb-3 rounded-3" role="alert"></div>
                                <div class="mb-3">
                                    <label for="newUsername" class="form-label fw-semibold text-dark small mb-1">New Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-at"></i></span>
                                        <input type="text" name="username" id="newUsername" class="form-control border-start-0" placeholder="e.g. ccc_misd" autocomplete="off" required style="border-color: #cbd5e1;">
                                    </div>
                                    <div id="username_availability_feedback" class="form-text small mt-1"></div>
                                </div>
                                <div class="p-3 rounded-3 mb-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">Username Requirements</span>
                                    <ul class="list-unstyled mb-0 mt-2 small text-secondary" id="username-rules">
                                        <li id="rule-user-length" class="mb-1"><i class="bi bi-x-circle text-danger me-2"></i>6 to 20 characters in length</li>
                                        <li id="rule-user-chars" class="mb-1"><i class="bi bi-x-circle text-danger me-2"></i>Letters, numbers, underscores, or hyphens only</li>
                                        <li id="rule-user-diff" class="mb-0"><i class="bi bi-x-circle text-danger me-2"></i>Different from your current username</li>
                                    </ul>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" id="btn_save_username" disabled style="background-color: #08184a;">Update Username</button>
                                </div>
                                <?php echo $csrf->input('token_update_username', 'token_update_username', 3600, 1); ?>
                            </form>
                        </div>

                        <!-- EMAIL PANE -->
                        <div class="tab-pane fade" id="email-pane" role="tabpanel">
                            <form id="form_update_email" method="post">
                                <div id="email_msg_alert" class="alert alert-danger d-none py-2 mb-3 rounded-3" role="alert"></div>
                                <div class="mb-4">
                                    <label for="accRecoveryEmail" class="form-label fw-semibold text-dark small mb-1">New Recovery Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" id="accRecoveryEmail" class="form-control border-start-0 rounded-end-3" value="<?php echo htmlspecialchars($g_recovery_email ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" required style="border-color: #cbd5e1;">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" id="btn_save_email" style="background-color: #08184a;">Update Email</button>
                                </div>
                                <?php echo $csrf->input('token_update_email', 'token_update_email', 3600, 1); ?>
                            </form>
                        </div>

                        <!-- PASSWORD PANE -->
                        <div class="tab-pane fade" id="password-pane" role="tabpanel">
                            <form id="form_update_password" method="post">
                                <div id="password_msg_alert" class="alert alert-danger d-none py-2 mb-3 rounded-3" role="alert"></div>
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label fw-semibold text-dark small mb-1">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-lock"></i></span>
                                        <input type="password" name="currentPassword" id="currentPassword" class="form-control border-start-0 border-end-0" required autocomplete="current-password" style="border-color: #cbd5e1;">
                                        <button class="btn btn-outline-secondary rounded-end-3 toggle-password" type="button" data-target="#currentPassword" style="border-color: #cbd5e1;"><i class="bi bi-eye"></i></button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label fw-semibold text-dark small mb-1">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-key"></i></span>
                                        <input type="password" name="newPassword" id="newPassword" class="form-control border-start-0 border-end-0" required autocomplete="new-password" style="border-color: #cbd5e1;">
                                        <button class="btn btn-outline-secondary rounded-end-3 toggle-password" type="button" data-target="#newPassword" style="border-color: #cbd5e1;"><i class="bi bi-eye"></i></button>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div id="password-strength-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div id="password-strength-text" class="form-text small text-secondary mt-1">Strength: Enter password</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label fw-semibold text-dark small mb-1">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-check2-circle"></i></span>
                                        <input type="password" name="confirmPassword" id="confirmPassword" class="form-control border-start-0 border-end-0" required autocomplete="new-password" style="border-color: #cbd5e1;">
                                        <button class="btn btn-outline-secondary rounded-end-3 toggle-password" type="button" data-target="#confirmPassword" style="border-color: #cbd5e1;"><i class="bi bi-eye"></i></button>
                                    </div>
                                    <div id="password_match_feedback" class="form-text small mt-1"></div>
                                </div>
                                <div class="p-3 rounded-3 mb-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <span class="text-uppercase fw-bold text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">Password Requirements</span>
                                    <ul class="list-unstyled mb-0 mt-2 small text-secondary" id="password-rules">
                                        <li id="rule-length" class="mb-1"><i class="bi bi-x-circle text-danger me-2"></i>At least 8 characters long</li>
                                        <li id="rule-case" class="mb-1"><i class="bi bi-x-circle text-danger me-2"></i>Mix of uppercase & lowercase letters</li>
                                        <li id="rule-number" class="mb-1"><i class="bi bi-x-circle text-danger me-2"></i>At least one number or symbol</li>
                                        <li id="rule-match" class="mb-0"><i class="bi bi-x-circle text-danger me-2"></i>Passwords must match</li>
                                    </ul>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" id="btn_save_password" disabled style="background-color: #08184a;">Change Password</button>
                                </div>
                                <?php echo $csrf->input('token_update_password', 'token_update_password', 3600, 1); ?>
                            </form>
                        </div>

                        <?php if ($allowedAccess): ?>
                            <!-- EMPLOYEE PANE -->
                            <div class="tab-pane fade" id="employee-pane" role="tabpanel">
                                <div class="p-3 mb-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #08184a;">
                                    <small class="text-secondary d-flex align-items-center gap-2">
                                        <i class="bi bi-briefcase-fill text-dark fs-6"></i> Employee profile parameters and display assets.
                                    </small>
                                </div>

                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-person-lines-fill me-2"></i>General Information
                                </h6>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Employee ID</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($employee['employee_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Personnel Classification</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($employee['personnel_classification'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Employment Status</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($employee['employment_status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Position/Job Title</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($employee['position'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-images me-2"></i>Display Assets
                                </h6>

                                <div class="row g-4 mb-3">
                                    <!-- Employee Avatar Form -->
                                    <div class="col-md-6">
                                        <form id="form_update_employee_avatar" method="post" enctype="multipart/form-data" class="card h-100 border-0 bg-light p-3 rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                            <label for="empProfilePic" class="form-label fw-semibold text-dark small mb-2">Employee Profile Picture</label>

                                            <div class="text-center mb-3">
                                                <div class="profile-container">
                                                    <img id="empProfilePicPreview"
                                                        src="<?= !empty($employee['profile_pic']) ? BASE_UPLOAD_PROFILE_EMP_PATH . htmlspecialchars($employee['profile_pic'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                                        alt="Profile Picture"
                                                        class="avatar rounded-circle img-thumbnail shadow-sm <?= empty($employee['profile_pic']) ? 'd-none' : '' ?>"
                                                        style="width: 110px; height: 110px; object-fit: cover;">

                                                    <div id="empProfileInitialsFallback" class="profile-initials <?= !empty($employee['profile_pic']) ? 'd-none' : '' ?>">
                                                        <?= htmlspecialchars($g_initials ?? 'U') ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="input-group mb-3">
                                                <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-person-circle"></i></span>
                                                <input type="file" name="emp_profile_pic" id="empProfilePic" class="form-control border-start-0 rounded-end-3" accept="image/*" style="border-color: #cbd5e1;" onchange="previewImage(this, 'empProfilePicPreview')">
                                            </div>

                                            <div class="mt-auto d-flex justify-content-end gap-2">
                                                <button type="button" id="btnEmpRemovePhoto" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold <?= empty($employee['profile_pic']) ? 'd-none' : '' ?>">
                                                    <i class="bi bi-trash-fill"></i>&ensp;Remove Picture
                                                </button>

                                                <button type="submit" id="btnEmpSavePhoto" class="btn btn-sm text-white rounded-pill px-4 fw-bold" style="background-color: #08184a;">
                                                    <i class="bi bi-check2"></i>&ensp;Save Picture
                                                </button>
                                            </div>

                                            <?php echo $csrf->input('token_update_emp_avatar', 'token_update_emp_avatar', 3600, 1); ?>
                                        </form>
                                    </div>

                                    <!-- Employee Cover Form -->
                                    <div class="col-md-6">
                                        <form id="form_update_employee_cover" method="post" enctype="multipart/form-data" class="card h-100 border-0 bg-light p-3 rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                            <label for="empCoverPhoto" class="form-label fw-semibold text-dark small mb-2">Employee Cover Photo</label>

                                            <div class="text-center mb-3">
                                                <img id="empCoverPhotoPreview" src="<?= !empty($employee['cover_photo']) ? BASE_UPLOAD_COVER_EMP_PATH . htmlspecialchars($employee['cover_photo'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Cover Preview" class="rounded-3 shadow-sm w-100 <?= empty($employee['cover_photo']) ? 'd-none' : '' ?>" style="height: 160px; object-fit: cover;">

                                                <div id="empCoverInitialsFallback" class="cover-profile-header cover-profile-header-fallback rounded-3 <?= !empty($employee['cover_photo']) ? 'd-none' : '' ?>">
                                                    <div class="cover-banner-watermark-initials"><?= htmlspecialchars($g_initials ?? 'U') ?></div>
                                                </div>
                                            </div>

                                            <div class="input-group mb-3">
                                                <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-image"></i></span>
                                                <input type="file" name="emp_cover_photo" id="empCoverPhoto" class="form-control border-start-0 rounded-end-3" accept="image/*" style="border-color: #cbd5e1;" onchange="previewImage(this, 'empCoverPhotoPreview')">
                                            </div>

                                            <div class="mt-auto d-flex justify-content-end gap-2">

                                                <button type="button" id="btnEmpRemoveCover" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold <?= empty($employee['cover_photo']) ? 'd-none' : '' ?>">
                                                    <i class="bi bi-trash-fill"></i>&ensp;Remove Cover
                                                </button>

                                                <button type="submit" id="btnEmpSaveCover" class="btn btn-sm text-white rounded-pill px-4 fw-bold" style="background-color: #08184a;">
                                                    <i class="bi bi-check2"></i>&ensp;Save Cover
                                                </button>
                                            </div>

                                            <?php echo $csrf->input('token_update_emp_cover', 'token_update_emp_cover', 3600, 1); ?>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- STUDENT PANE -->
                            <div class="tab-pane fade" id="student-pane" role="tabpanel">
                                <div class="p-3 mb-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #08184a;">
                                    <small class="text-secondary d-flex align-items-center gap-2">
                                        <i class="bi bi-mortarboard-fill text-dark fs-6"></i> Student record profile parameters and display assets.
                                    </small>
                                </div>

                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-person-vcard-fill me-2"></i>General Information
                                </h6>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Student ID Number</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($student['student_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold text-dark small mb-1">Program/Course</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?php echo htmlspecialchars($student['course'] ?? $student['program'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>" readonly disabled style="border-color: #cbd5e1;">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                    <i class="bi bi-images me-2"></i>Display Assets
                                </h6>

                                <div class="row g-4 mb-3">
                                    <!-- Student Avatar Form -->
                                    <div class="col-md-6">
                                        <form id="form_update_student_avatar" method="post" enctype="multipart/form-data" class="card h-100 border-0 bg-light p-3 rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                            <label for="stdProfilePic" class="form-label fw-semibold text-dark small mb-2">Student Profile Picture</label>

                                            <div class="text-center mb-3">
                                                <div class="profile-container">
                                                    <img id="stdProfilePicPreview" src="<?= !empty($student['profile_pic']) ? BASE_UPLOAD_PROFILE_STD_PATH . htmlspecialchars($student['profile_pic'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Profile Picture" class="avatar rounded-circle img-thumbnail shadow-sm <?= empty($student['profile_pic']) ? 'd-none' : '' ?>" style="width: 110px; height: 110px; object-fit: cover;" onerror="this.classList.add('d-none'); $('#stdProfileInitialsFallback').removeClass('d-none');">

                                                    <div id="stdProfileInitialsFallback" class="profile-initials <?= !empty($student['profile_pic']) ? 'd-none' : '' ?>">
                                                        <?= htmlspecialchars($g_initials ?? 'U') ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="input-group mb-3">
                                                <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-person-square"></i></span>
                                                <input type="file" name="std_profile_pic" id="stdProfilePic" class="form-control border-start-0 rounded-end-3" accept="image/*" style="border-color: #cbd5e1;" onchange="previewImage(this, 'stdProfilePicPreview')">
                                            </div>

                                            <div class="mt-auto d-flex justify-content-end gap-2">
                                                <button type="button" id="btnStdRemovePhoto" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold <?= empty($student['profile_pic']) ? 'd-none' : '' ?>">
                                                    <i class="bi bi-trash-fill"></i>&ensp;Remove Picture
                                                </button>

                                                <button type="submit" id="btnStdSavePhoto" class="btn btn-sm text-white rounded-pill px-4 fw-bold" style="background-color: #08184a;">
                                                    <i class="bi bi-check2"></i>&ensp;Save Picture
                                                </button>
                                            </div>

                                            <?php echo $csrf->input('token_update_std_avatar', 'token_update_std_avatar', 3600, 1); ?>
                                        </form>
                                    </div>

                                    <!-- Student Cover Form -->
                                    <div class="col-md-6">
                                        <form id="form_update_student_cover" method="post" enctype="multipart/form-data" class="card h-100 border-0 bg-light p-3 rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                            <label for="stdCoverPhoto" class="form-label fw-semibold text-dark small mb-2">Student Cover Photo</label>

                                            <div class="text-center mb-3">
                                                <img id="stdCoverPhotoPreview" src="<?= !empty($student['cover_photo']) ? BASE_UPLOAD_COVER_STD_PATH . htmlspecialchars($student['cover_photo'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Cover Preview" class="rounded-3 shadow-sm w-100 <?= empty($student['cover_photo']) ? 'd-none' : '' ?>" style="height: 160px; object-fit: cover;" onerror="this.classList.add('d-none'); $('#stdCoverInitialsFallback').removeClass('d-none');">

                                                <div id="stdCoverInitialsFallback" class="cover-profile-header cover-profile-header-fallback rounded-3 <?= !empty($student['cover_photo']) ? 'd-none' : '' ?>">
                                                    <div class="cover-banner-watermark-initials"><?= htmlspecialchars($g_initials ?? 'U') ?></div>
                                                </div>
                                            </div>

                                            <div class="input-group mb-3">
                                                <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;"><i class="bi bi-card-image"></i></span>
                                                <input type="file" name="std_cover_photo" id="stdCoverPhoto" class="form-control border-start-0 rounded-end-3" accept="image/*" style="border-color: #cbd5e1;" onchange="previewImage(this, 'stdCoverPhotoPreview')">
                                            </div>

                                            <div class="mt-auto d-flex justify-content-end gap-2">
                                                <button type="button" id="btnStdRemoveCover" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold <?= empty($student['cover_photo']) ? 'd-none' : '' ?>">
                                                    <i class="bi bi-trash-fill"></i>&ensp;Remove Picture
                                                </button>

                                                <button type="submit" id="btnStdSaveCover" class="btn btn-sm text-white rounded-pill px-4 fw-bold" style="background-color: #08184a;">
                                                    <i class="bi bi-check2"></i>&ensp;Save Picture
                                                </button>
                                            </div>

                                            <?php echo $csrf->input('token_update_std_cover', 'token_update_std_cover', 3600, 1); ?>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- DIGITAL BUSINESS CARD PANE -->
                            <div class="tab-pane fade" id="card-pane" role="tabpanel">
                                <form id="form_update_business_card" method="post">
                                    <div id="card_msg_alert" class="alert alert-danger d-none py-2 mb-3 rounded-3" role="alert"></div>

                                    <?php
                                    $postNominal = $profile['post_nominal'] ?? '';
                                    $showPostNominal = !isset($profile['post_nominal_flag']) || (bool)$profile['post_nominal_flag'];
                                    $activePostNominal = $showPostNominal ? $postNominal : '';

                                    // Formatted Name Options via Class Methods
                                    $nameFormats = [
                                        'full'    => $helper->formatFullName($fName, $mName, $lName, $suffix, $activePostNominal, 'title'),
                                        'initial' => $helper->formatInitialName($fName, $mName, $lName, $suffix, $activePostNominal, 'title'),
                                        'short'   => $helper->formatFullName($fName, '', $lName, $suffix, $activePostNominal, 'title'),
                                        'formal'  => $helper->formatLastNameFirst($fName, $mName, $lName, $suffix, $activePostNominal, 'title')
                                    ];

                                    // Formatted Name Options WITH post-nominals (stored as data attributes for instant JS toggling)
                                    $nameFormatsWithPN = [
                                        'full'    => $helper->formatFullName($fName, $mName, $lName, $suffix, $postNominal, 'title'),
                                        'initial' => $helper->formatInitialName($fName, $mName, $lName, $suffix, $postNominal, 'title'),
                                        'short'   => $helper->formatFullName($fName, '', $lName, $suffix, $postNominal, 'title'),
                                        'formal'  => $helper->formatLastNameFirst($fName, $mName, $lName, $suffix, $postNominal, 'title')
                                    ];

                                    // Formatted Name Options WITHOUT post-nominals
                                    $nameFormatsWithoutPN = [
                                        'full'    => $helper->formatFullName($fName, $mName, $lName, $suffix, '', 'title'),
                                        'initial' => $helper->formatInitialName($fName, $mName, $lName, $suffix, '', 'title'),
                                        'short'   => $helper->formatFullName($fName, '', $lName, $suffix, '', 'title'),
                                        'formal'  => $helper->formatLastNameFirst($fName, $mName, $lName, $suffix, '', 'title')
                                    ];

                                    $selectedNameFormat = $profile['name_format'] ?? 'full';
                                    $activeName         = !empty($nameFormats[$selectedNameFormat]) ? $nameFormats[$selectedNameFormat] : '';
                                    $activeTitle        = $profile['title'];
                                    $activeCompany      = $profile['company'];
                                    $activeBio          = $profile['bio'];
                                    $cardLayout         = $profile['card_layout'];
                                    $cardTheme          = $profile['card_theme'];
                                    $cardUid            = $profile['card_uid'];
                                    $cardURL            = BASE_URL . "digital-profile/" . $profile['card_uid'];

                                    $profile['name'] = $activeName;

                                    $hasAvatar   = !empty($profile['avatar']);
                                    $avatarStyle = $hasAvatar ? "style=\"--avatar-img:url('" . htmlspecialchars($profile['avatar'], ENT_QUOTES, 'UTF-8') . "');\"" : "";
                                    $hasBanner   = !empty($profile['banner']);
                                    $bannerStyle = $hasBanner ? "style=\"background-image:url('" . htmlspecialchars($profile['banner'], ENT_QUOTES, 'UTF-8') . "');\"" : "";
                                    $bannerClass = $hasBanner ? "profile-card-header" : "profile-card-header profile-card-header-fallback";
                                    $initials    = getNameInitials($fName . " " . $lName);

                                    // Social Options JSON decoding
                                    $socialOptions = !empty($profile['social_options']) ? json_decode($profile['social_options'], true) : [];
                                    if (!is_array($socialOptions)) {
                                        $socialOptions = [];
                                    }

                                    // List of all supported social networks
                                    $allSocials = [
                                        'viber'     => ['name' => 'Viber',     'icon' => 'fa-brands fa-viber'],
                                        'whatsapp'  => ['name' => 'WhatsApp',  'icon' => 'fa-brands fa-whatsapp'],
                                        'linkedin'  => ['name' => 'LinkedIn',  'icon' => 'fa-brands fa-linkedin-in'],
                                        'facebook'  => ['name' => 'Facebook',  'icon' => 'fa-brands fa-facebook-f'],
                                        'instagram' => ['name' => 'Instagram', 'icon' => 'fa-brands fa-instagram'],
                                        'tiktok'    => ['name' => 'TikTok',    'icon' => 'fa-brands fa-tiktok'],
                                        'youtube'   => ['name' => 'YouTube',   'icon' => 'fa-brands fa-youtube'],
                                        'github'    => ['name' => 'GitHub',    'icon' => 'fa-brands fa-github'],
                                        'twitter'   => ['name' => 'X/Twitter', 'icon' => 'fa-brands fa-x-twitter'],
                                        'telegram'  => ['name' => 'Telegram',  'icon' => 'fa-brands fa-telegram'],
                                        'threads'   => ['name' => 'Threads',   'icon' => 'fa-brands fa-threads'],
                                        'behance'   => ['name' => 'Behance',   'icon' => 'fa-brands fa-behance'],
                                        'pinterest' => ['name' => 'Pinterest', 'icon' => 'fa-brands fa-pinterest'],
                                        'snapchat'  => ['name' => 'Snapchat',  'icon' => 'fa-brands fa-snapchat'],
                                        'dribbble'  => ['name' => 'Dribbble',  'icon' => 'fa-brands fa-dribbble']
                                    ];
                                    ?>

                                    <!-- Hidden Inputs for Form State -->
                                    <input type="hidden" name="card_layout" id="input_card_layout" value="<?= htmlspecialchars($cardLayout, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="card_theme" id="input_card_theme" value="<?= htmlspecialchars($cardTheme, ENT_QUOTES, 'UTF-8') ?>">

                                    <!-- CARD UID DISPLAY -->
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold text-dark small mb-1">Your Digital Business Card UID</label>
                                        <div class="input-group mb-2">
                                            <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;">
                                                <i class="bi bi-qr-code"></i>
                                            </span>
                                            <input type="text" id="cardUidDisplay" name="card_uid" class="form-control border-start-0 border-end-0 bg-light" readonly value="<?= htmlspecialchars($cardUid, ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                            <button class="btn btn-outline-secondary rounded-end-3" type="button" id="btnCopyCardUid" style="border-color: #cbd5e1;">
                                                <i class="bi bi-clipboard me-1"></i>Copy UID
                                            </button>
                                        </div>
                                        <div id="copy_uid_feedback" class="form-text small mb-3"></div>

                                        <label class="form-label fw-semibold text-dark small mb-1">Public Card URL</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 rounded-start-3" style="border-color: #cbd5e1;">
                                                <i class="bi bi-link-45deg"></i>
                                            </span>
                                            <input type="text" id="cardUrlDisplay" class="form-control border-start-0 border-end-0 bg-light" readonly value="<?= htmlspecialchars($cardURL, ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                            <button class="btn btn-outline-secondary" type="button" id="btnCopyCardUrl" style="border-color: #cbd5e1;">
                                                <i class="bi bi-clipboard me-1"></i>Copy Link
                                            </button>
                                            <a href="<?= htmlspecialchars($cardURL, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-primary rounded-end-3" style="border-color: #cbd5e1;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>View
                                            </a>
                                        </div>
                                        <div id="copy_url_feedback" class="form-text small mt-1"></div>
                                    </div>

                                    <div class="row g-4">
                                        <!-- LEFT COLUMN: FORM CONTROLS -->
                                        <div class="col-lg-7">
                                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                                <i class="bi bi-sliders me-2"></i>Display & Theme Configuration
                                            </h6>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label for="layoutSelect" class="form-label fw-semibold text-dark small mb-1">Card Layout</label>
                                                    <select id="layoutSelect" class="form-select rounded-3" style="border-color: #cbd5e1;" onchange="updateLayout(this.value)">
                                                        <option value="classic" <?= $cardLayout === 'classic' ? 'selected' : '' ?>>Classic Banner</option>
                                                        <option value="centered" <?= $cardLayout === 'centered' ? 'selected' : '' ?>>Centered Minimal</option>
                                                        <option value="hero-image" <?= $cardLayout === 'hero-image' ? 'selected' : '' ?>>Hero Avatar</option>
                                                        <option value="landscape" <?= $cardLayout === 'landscape' ? 'selected' : '' ?>>Horizontal View</option>
                                                        <option value="full-overlay" <?= $cardLayout === 'full-overlay' ? 'selected' : '' ?>>Glass Overlay</option>
                                                        <option value="neo-brutalism" <?= $cardLayout === 'neo-brutalism' ? 'selected' : '' ?>>Neo Brutalism</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="nameFormatSelect" class="form-label fw-semibold text-dark small mb-1">Name Display Format</label>
                                                    <select name="name_format" id="nameFormatSelect" class="form-select rounded-3" style="border-color: #cbd5e1;" onchange="updateNameFormat(this)">
                                                        <option value="full"
                                                            data-name="<?= htmlspecialchars($nameFormats['full'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-with-pn="<?= htmlspecialchars($nameFormatsWithPN['full'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-without-pn="<?= htmlspecialchars($nameFormatsWithoutPN['full'], ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $selectedNameFormat === 'full' ? 'selected' : '' ?>>First Middle Last Extension</option>
                                                        <option value="initial"
                                                            data-name="<?= htmlspecialchars($nameFormats['initial'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-with-pn="<?= htmlspecialchars($nameFormatsWithPN['initial'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-without-pn="<?= htmlspecialchars($nameFormatsWithoutPN['initial'], ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $selectedNameFormat === 'initial' ? 'selected' : '' ?>>First M. Last Extension</option>
                                                        <option value="short"
                                                            data-name="<?= htmlspecialchars($nameFormats['short'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-with-pn="<?= htmlspecialchars($nameFormatsWithPN['short'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-without-pn="<?= htmlspecialchars($nameFormatsWithoutPN['short'], ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $selectedNameFormat === 'short' ? 'selected' : '' ?>>First Last Extension</option>
                                                        <option value="formal"
                                                            data-name="<?= htmlspecialchars($nameFormats['formal'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-with-pn="<?= htmlspecialchars($nameFormatsWithPN['formal'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-name-without-pn="<?= htmlspecialchars($nameFormatsWithoutPN['formal'], ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $selectedNameFormat === 'formal' ? 'selected' : '' ?>>Last, First M. Extension</option>
                                                    </select>
                                                </div>

                                                <!-- Post-Nominal Toggle -->
                                                <div class="col-md-12">
                                                    <div class="border rounded-3 p-2 bg-light d-flex justify-content-between align-items-center" style="border-color: #cbd5e1 !important;">
                                                        <div>
                                                            <label for="switch_post_nominal" class="form-label fw-semibold text-dark small mb-0 d-block">Include Post-Nominal Titles</label>
                                                            <span class="text-muted small">Show suffix titles (e.g., <?= htmlspecialchars($postNominal, ENT_QUOTES, 'UTF-8') ?>) in name preview</span>
                                                        </div>
                                                        <div class="form-check form-switch mb-0">
                                                            <input type="hidden" name="show_post_nominal" value="0">
                                                            <input class="form-check-input" type="checkbox" role="switch" name="show_post_nominal" id="switch_post_nominal" value="1" <?= $showPostNominal ? 'checked' : '' ?> onchange="togglePostNominal(this)">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Custom Card Details -->
                                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                                <i class="bi bi-person-badge-fill me-2"></i>Card Profile Details
                                            </h6>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label for="customTitleInput" class="form-label fw-semibold text-dark small mb-1">Position/Title</label>
                                                    <input type="text" name="title" id="customTitleInput" class="form-control rounded-3" placeholder="Override default title..." value="<?= htmlspecialchars($activeTitle, ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="companyInput" class="form-label fw-semibold text-dark small mb-1">Company/Organization</label>
                                                    <input type="text" name="company" id="companyInput" class="form-control rounded-3" placeholder="Company name..." value="<?= htmlspecialchars($activeCompany, ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="cardBioInput" class="form-label fw-semibold text-dark small mb-1">Professional Bio</label>
                                                    <textarea name="bio" id="cardBioInput" class="form-control rounded-3" rows="2" placeholder="Brief summary of your skills or role..." style="border-color: #cbd5e1;"><?= htmlspecialchars($activeBio, ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="websiteInput" class="form-label fw-semibold text-dark small mb-1">Website URL</label>
                                                    <input type="text" name="website" id="websiteInput" class="form-control rounded-3" placeholder="example.com" value="<?= htmlspecialchars($profile['website'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="hoursInput" class="form-label fw-semibold text-dark small mb-1">Working Hours</label>
                                                    <input type="text" name="hours" id="hoursInput" class="form-control rounded-3" placeholder="Mon-Fri: 9:00 AM - 5:00 PM" value="<?= htmlspecialchars($profile['hours'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="addressInput" class="form-label fw-semibold text-dark small mb-1">Office Address</label>
                                                    <input type="text" name="address" id="addressInput" class="form-control rounded-3" placeholder="Full office address..." value="<?= htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="border-color: #cbd5e1;">
                                                </div>
                                            </div>

                                            <!-- Social Profiles & Toggle Visibility -->
                                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2" style="font-size: 0.9rem;">
                                                <i class="bi bi-share-fill me-2"></i>Social Links & Visibility Settings
                                            </h6>

                                            <div class="row g-3 mb-4">
                                                <?php foreach ($allSocials as $key => $social):
                                                    $url     = $socialOptions[$key]['url'] ?? '';
                                                    $enabled = !empty($socialOptions[$key]['enabled']);
                                                ?>
                                                    <div class="col-md-6">
                                                        <div class="border rounded-3 p-2 bg-light" style="border-color: #cbd5e1 !important;">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <label class="form-label fw-semibold text-dark small mb-0">
                                                                    <i class="<?= $social['icon'] ?> me-1"></i><?= $social['name'] ?>
                                                                </label>
                                                                <div class="form-check form-switch mb-0">
                                                                    <input class="form-check-input" type="checkbox" role="switch" name="social_options[<?= $key ?>][enabled]" value="1" id="switch_<?= $key ?>" <?= $enabled ? 'checked' : '' ?>>
                                                                    <label class="form-check-label small text-muted" for="switch_<?= $key ?>">Show</label>
                                                                </div>
                                                            </div>
                                                            <input type="text" name="social_options[<?= $key ?>][url]" class="form-control form-control-sm rounded-2" placeholder="Profile URL or Username..." value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- RIGHT COLUMN: LIVE CARD CUSTOMIZER PREVIEW -->
                                        <div class="col-lg-5">
                                            <div class="sticky-top" style="top: 20px;">
                                                <label class="form-label fw-semibold text-dark small mb-2 d-block">Live Card Preview</label>

                                                <!-- CONTROLS BAR (COLOR PICKER ONLY) -->
                                                <div class="controls-bar justify-content-center" data-color="<?= htmlspecialchars($cardTheme, ENT_QUOTES, 'UTF-8') ?>">
                                                    <div class="picker-group">
                                                        <div class="dot dot-emerald" title="Emerald" onclick="updateColor('emerald')"></div>
                                                        <div class="dot dot-ocean" title="Ocean" onclick="updateColor('ocean')"></div>
                                                        <div class="dot dot-sunset" title="Sunset" onclick="updateColor('sunset')"></div>
                                                        <div class="dot dot-violet" title="Violet" onclick="updateColor('violet')"></div>
                                                        <div class="dot dot-royal-blue" title="Royal Blue" onclick="updateColor('royal-blue')"></div>
                                                        <div class="dot dot-navy-luxe" title="Navy Luxe" onclick="updateColor('navy-luxe')"></div>
                                                        <div class="dot dot-forest-green" title="Forest Green" onclick="updateColor('forest-green')"></div>
                                                        <div class="dot dot-crimson-red" title="Crimson Red" onclick="updateColor('crimson-red')"></div>
                                                        <div class="dot dot-amber-gold" title="Amber Gold" onclick="updateColor('amber-gold')"></div>
                                                        <div class="dot dot-cyan-breeze" title="Cyan Breeze" onclick="updateColor('cyan-breeze')"></div>
                                                        <div class="dot dot-electric-lime" title="Electric Lime" onclick="updateColor('electric-lime')"></div>
                                                        <div class="dot dot-deep-space" title="Deep Space" onclick="updateColor('deep-space')"></div>
                                                        <div class="dot dot-pitch-black" title="Pitch Black" onclick="updateColor('pitch-black')"></div>
                                                        <div class="dot dot-dark" title="Dark" onclick="updateColor('dark')"></div>
                                                        <div class="dot dot-glass" title="Glass" onclick="updateColor('glassmorphism')"></div>
                                                        <div class="dot dot-pink" title="Glass Pink" onclick="updateColor('glass-pink')"></div>
                                                        <div class="dot dot-blue" title="Glass Blue" onclick="updateColor('glass-blue')"></div>
                                                        <div class="dot dot-dark-blue" title="Glass Dark Blue" onclick="updateColor('glass-dark-blue')"></div>
                                                        <div class="dot dot-dark-green" title="Glass Dark Green" onclick="updateColor('glass-dark-green')"></div>
                                                        <div class="dot dot-red" title="Glass Red" onclick="updateColor('glass-red')"></div>
                                                        <div class="dot dot-gold" title="Glass Gold" onclick="updateColor('glass-gold')"></div>
                                                        <div class="dot dot-cyan" title="Glass Cyan" onclick="updateColor('glass-cyan')"></div>
                                                        <div class="dot dot-deep-navy" title="Glass Deep Navy" onclick="updateColor('glass-deep-navy')"></div>
                                                    </div>
                                                </div>

                                                <div id="cardPreviewContainer" class="profile-body <?= !$hasAvatar ? 'no-avatar' : '' ?>" data-color="<?= htmlspecialchars($cardTheme, ENT_QUOTES, 'UTF-8') ?>" data-layout="<?= htmlspecialchars($cardLayout, ENT_QUOTES, 'UTF-8') ?>" <?= $avatarStyle ?>>
                                                    <div class="profile-card-wrapper">
                                                        <!-- COMPANY LOGO BADGE -->
                                                        <div class="company-logo-badge">
                                                            <img src="<?= htmlspecialchars($profile['company_logo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" class="logo-color">
                                                            <img src="<?= htmlspecialchars($profile['company_logo_white'] ?? $profile['company_logo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo White" class="logo-white">
                                                        </div>

                                                        <!-- BANNER HEADER -->
                                                        <div class="<?= $bannerClass ?>" <?= $bannerStyle ?>>
                                                            <?php if (!$hasBanner): ?>
                                                                <div class="banner-watermark-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                            <div class="hero-fallback-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                                                        </div>

                                                        <!-- AVATAR CONTAINER -->
                                                        <div class="avatar-container">
                                                            <?php if ($hasAvatar): ?>
                                                                <img src="<?= htmlspecialchars($profile['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($activeName, ENT_QUOTES, 'UTF-8') ?>" class="avatar">
                                                            <?php else: ?>
                                                                <div class="avatar-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- CARD BODY -->
                                                        <div class="profile-card-body">
                                                            <div class="overlay-fallback-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>

                                                            <div class="profile-title">
                                                                <h1>
                                                                    <span id="cardPreviewName"><?= htmlspecialchars($activeName, ENT_QUOTES, 'UTF-8') ?></span>
                                                                    <?= !empty($profile['verified']) ? '<i class="bi bi-patch-check-fill verified-badge"></i>' : ''; ?>
                                                                </h1>
                                                            </div>

                                                            <div class="subtitle">
                                                                <span id="cardPreviewTitle"><?= htmlspecialchars(!empty($activeTitle) ? $activeTitle . ' • ' : '', ENT_QUOTES, 'UTF-8') ?></span>
                                                                <span id="cardPreviewCompany"><?= htmlspecialchars($activeCompany, ENT_QUOTES, 'UTF-8') ?></span>
                                                            </div>

                                                            <p class="bio" id="cardPreviewBio"><?= htmlspecialchars($activeBio, ENT_QUOTES, 'UTF-8') ?></p>

                                                            <div class="action-buttons">
                                                                <?php if (!empty($profile['phone'])): ?>
                                                                    <a href="tel:<?= htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="profile-btn profile-btn-primary">
                                                                        <i class="bi bi-telephone-fill"></i>&ensp;Call Now
                                                                    </a>
                                                                <?php endif; ?>
                                                                <button type="button" onclick="downloadVCard()" class="profile-btn profile-btn-secondary">
                                                                    <i class="bi bi-person-vcard-fill"></i>&ensp;Save Contact
                                                                </button>
                                                            </div>

                                                            <!-- CONTACT LIST -->
                                                            <div class="contact-list">
                                                                <?php if (!empty($profile['email'])): ?>
                                                                    <a href="mailto:<?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?>" class="contact-item">
                                                                        <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                                                                        <div class="contact-text">
                                                                            <label class="contact-textlabel">Email</label>
                                                                            <span id="cardPreviewEmail" class="contact-textspan"><?= htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                                                        </div>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if (!empty($profile['phone'])): ?>
                                                                    <a href="tel:<?= htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8') ?>" class="contact-item">
                                                                        <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                                                                        <div class="contact-text text-start">
                                                                            <label class="contact-textlabel">Phone</label>
                                                                            <span id="cardPreviewPhone" class="contact-textspan"><?= htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                                                                        </div>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if (!empty($profile['website'])): ?>
                                                                    <a href="<?= htmlspecialchars($profile['website_url'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="contact-item">
                                                                        <div class="contact-icon"><i class="bi bi-globe"></i></div>
                                                                        <div class="contact-text text-start">
                                                                            <label class="contact-textlabel">Website</label>
                                                                            <span class="contact-textspan"><?= htmlspecialchars($profile['website'], ENT_QUOTES, 'UTF-8') ?></span>
                                                                        </div>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if (!empty($profile['address'])): ?>
                                                                    <div class="contact-item">
                                                                        <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                                                                        <div class="contact-text text-start">
                                                                            <label class="contact-textlabel">Office Address</label>
                                                                            <span class="contact-textspan"><?= htmlspecialchars($profile['address'], ENT_QUOTES, 'UTF-8') ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if (!empty($profile['hours'])): ?>
                                                                    <div class="contact-item">
                                                                        <div class="contact-icon"><i class="bi bi-clock"></i></div>
                                                                        <div class="contact-text text-start">
                                                                            <label class="contact-textlabel">Working Hours</label>
                                                                            <span class="contact-textspan"><?= htmlspecialchars($profile['hours'], ENT_QUOTES, 'UTF-8') ?></span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- DYNAMIC SOCIAL LINKS -->
                                                            <?php
                                                            $hasActiveSocials = false;
                                                            foreach ($allSocials as $key => $social) {
                                                                if (!empty($socialOptions[$key]['enabled']) && !empty($socialOptions[$key]['url'])) {
                                                                    $hasActiveSocials = true;
                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                            <div id="cardPreviewSocialSection" class="<?= !$hasActiveSocials ? 'd-none' : '' ?>">
                                                                <div class="social-heading">Connect Socially</div>
                                                                <div class="social-grid" id="cardPreviewSocials">
                                                                    <?php foreach ($allSocials as $key => $social):
                                                                        $url = $socialOptions[$key]['url'] ?? '';
                                                                        $enabled = !empty($socialOptions[$key]['enabled']);
                                                                        if ($enabled && !empty($url)): ?>
                                                                            <a href="<?= htmlspecialchars(getSocialUrl($key, $url), ENT_QUOTES, 'UTF-8') ?>" class="social-card" title="<?= htmlspecialchars($social['name'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                                                                <i class="<?= $social['icon'] ?>"></i>
                                                                            </a>
                                                                    <?php endif;
                                                                    endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM SAVE FOOTER -->
                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                        <button type="submit" class="btn text-white rounded-pill px-4 fw-bold" id="btnSaveCardDetails" style="background-color: #08184a;">
                                            <i class="bi bi-save me-1"></i>Save All Details
                                        </button>
                                    </div>

                                    <?php echo $csrf->input('token_update_card', 'token_update_card', 3600, 1); ?>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include FOOTER_PATH; ?>
</body>
<?php include SCRIPT_DATA_PATH; ?>

<script>
    (function() {
        // PERSIST ACTIVE TAB ACROSS RELOADS
        const TAB_STORAGE_KEY = 'accountTabs_activeTab';
        const tabButtons = document.querySelectorAll('#accountTabs button[data-bs-toggle="tab"]');

        function activateTab(targetSelector) {
            // Toggle nav buttons
            tabButtons.forEach(function(btn) {
                const isTarget = btn.getAttribute('data-bs-target') === targetSelector;
                btn.classList.toggle('active', isTarget);
                btn.setAttribute('aria-selected', isTarget ? 'true' : 'false');
            });

            // Toggle tab panes
            document.querySelectorAll('#accountTabsContent .tab-pane').forEach(function(pane) {
                const isTarget = ('#' + pane.id) === targetSelector;
                pane.classList.toggle('show', isTarget);
                pane.classList.toggle('active', isTarget);
            });
        }

        // Save target on Bootstrap tab switch
        tabButtons.forEach(function(btn) {
            btn.addEventListener('shown.bs.tab', function(e) {
                localStorage.setItem(TAB_STORAGE_KEY, btn.getAttribute('data-bs-target'));
            });
        });

        // Restore tab on page load
        const savedTab = localStorage.getItem(TAB_STORAGE_KEY);
        if (savedTab && document.querySelector(savedTab)) {
            activateTab(savedTab);
        }

        function hideError() {
            document.querySelectorAll('.badge').forEach(err => err.style.display = 'none');
        }
        hideError();

        /** Set server time **/
        var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>;
        var serverOffset = moment(set_server_time).diff(new Date());
        var now_server = moment();
        now_server.add(serverOffset, 'milliseconds');
        now_server.subtract(15, 'year');
        var dateLimit = now_server.format('YYYY-MM-DD');

        /** Datepicker **/
        $('#birth_date').datetimepicker({
            format: 'YYYY-MM-DD',
            maxDate: dateLimit,
            useCurrent: false
        });

        let currentUsername = "<?php echo htmlspecialchars($g_username ?? '', ENT_QUOTES, 'UTF-8'); ?>";
        let isUsernameAvailable = false;
        let typingTimer;
        const doneTypingInterval = 500;

        function updateRuleState(ruleId, isValid) {
            const $el = $(ruleId);
            if (!$el.length) return;
            const $icon = $el.find('i');

            if (isValid) {
                $el.removeClass('text-secondary text-danger').addClass('text-success');
                $icon.removeClass('bi-x-circle bi-x-circle-fill text-danger').addClass('bi-check-circle-fill text-success');
            } else {
                $el.removeClass('text-success').addClass('text-secondary');
                $icon.removeClass('bi-check-circle-fill text-success').addClass('bi-x-circle text-danger');
            }
        }

        function showAlert(type, title, message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    title: title,
                    text: message,
                    timer: type === 'success' ? 2000 : undefined,
                    showConfirmButton: type !== 'success'
                });
            } else {
                alert(message);
            }
        }

        // USER PROFILE PICTURE HANDLERS
        $('#btnUserRemovePhoto').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove your profile picture?')) {
                $('#avatarActionType').val('remove');
                $('#userProfilePic').val('');
                $('#form_update_user_avatar').submit();
            }
        });

        $("#form_update_user_avatar").submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_general_profile');
            const actionType = $('#avatarActionType').val();
            const $btnSave = $('#btnUserSavePhoto');
            const $btnRemove = $('#btnUserRemovePhoto');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                    $btnRemove.prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('Save Picture').prop('disabled', false);
                    $btnRemove.prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_general_profile']").val(res.token);

                    if (res.success === true || res.success === "true") {
                        if (actionType === 'remove') {
                            $('#userProfilePicPreview').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#userProfileInitialsFallback').removeClass('d-none').addClass('d-flex');
                            $btnRemove.addClass('d-none');
                            $('#navHeaderProfilePic').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#navHeaderInitialsFallback').removeClass('d-none').addClass('d-flex');
                        } else if (res.image_url) {
                            const cleanImageUrl = res.image_url + '?t=' + new Date().getTime();
                            $('#userProfilePicPreview').attr('src', cleanImageUrl).removeClass('d-none').addClass('d-block');
                            $('#userProfileInitialsFallback').addClass('d-none').removeClass('d-flex');
                            $btnRemove.removeClass('d-none');
                            $('#navHeaderProfilePic').attr('src', cleanImageUrl).removeClass('d-none').addClass('d-block');
                            $('#navHeaderInitialsFallback').addClass('d-none').removeClass('d-flex');
                        }

                        $('#userProfilePic').val('');
                        $('#avatarActionType').val('upload');
                        showAlert('success', actionType === 'remove' ? 'Avatar Removed' : 'Avatar Updated', res.message || 'Profile picture updated successfully.');
                    } else {
                        showAlert('error', 'Update Failed', res.message || 'Failed to update profile picture.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("UserAvatarError:", error);
                    showAlert('error', 'Server Error', 'An error occurred while updating the profile picture.');
                }
            });
        });

        // GENERAL INFO HANDLER
        $("#form_update_general_info").submit(function(e) {
            e.preventDefault();
            const $form = $(this);
            const $alertBox = $('#general_info_msg_alert').addClass('d-none');
            const $btnSave = $('#btn_save_general_info');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: $form.serialize() + '&action=update_general_info',
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('Save General Info').prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_general_info']").val(res.token);
                    if (res.success === true || res.success === "true") {
                        showAlert('success', 'Profile Updated', res.message || 'General information updated successfully.');
                    } else {
                        $alertBox.removeClass('d-none').text(res.message || 'Failed to update general info.');
                    }
                },
                error: function() {
                    $alertBox.removeClass('d-none').text('Server communication error. Please try again.');
                }
            });
        });

        // USERNAME HANDLER
        $("#form_update_username").submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const alertBox = $('#username_msg_alert').addClass('d-none');
            const newName = $('#newUsername').val().trim();
            const formData = form.serialize() + '&action=update_username';

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                dataType: "json",
                beforeSend: function() {
                    $('#btn_save_username').html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...').prop('disabled', true);
                },
                complete: function() {
                    $('#btn_save_username').html('Update Username');
                },
                success: function(data) {
                    if (data.token) $("input[name='token_update_username']").val(data.token);
                    if (data.success === true || data.success === "true") {
                        currentUsername = newName;
                        $('#displayUsername, #profileEmpID').val(newName);
                        $('#accountInfoModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Username Updated',
                            text: data.message || 'Your username has been updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        $('#btn_save_username').prop('disabled', false);
                        alertBox.removeClass('d-none').text(data.message || 'Failed to update username.');
                    }
                },
                error: function() {
                    $('#btn_save_username').prop('disabled', false);
                    alertBox.removeClass('d-none').text('Server error. Unable to connect.');
                }
            });
        });

        // EMAIL HANDLER
        $("#form_update_email").submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const alertBox = $('#email_msg_alert').addClass('d-none');
            const formData = form.serialize() + '&action=update_email';

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                dataType: "json",
                beforeSend: function() {
                    $('#btn_save_email').html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...').prop('disabled', true);
                },
                complete: function() {
                    $('#btn_save_email').html('Update Email').prop('disabled', false);
                },
                success: function(data) {
                    if (data.token) $("input[name='token_update_email']").val(data.token);
                    if (data.success === true || data.success === "true") {
                        $('#accountInfoModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Updated',
                            text: data.message || 'Your recovery email has been updated.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alertBox.removeClass('d-none').text(data.message || 'Failed to update email.');
                    }
                },
                error: function() {
                    alertBox.removeClass('d-none').text('Server error. Unable to connect.');
                }
            });
        });

        // PASSWORD HANDLER
        $("#form_update_password").submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const alertBox = $('#password_msg_alert').addClass('d-none');
            const formData = form.serialize() + '&action=update_password';

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                dataType: "json",
                beforeSend: function() {
                    $('#btn_save_password').html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...').prop('disabled', true);
                },
                complete: function() {
                    $('#btn_save_password').html('Change Password');
                },
                success: function(data) {
                    if (data.token) $("input[name='token_update_password']").val(data.token);
                    if (data.success === true || data.success === "true") {
                        $('#accountInfoModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Changed',
                            text: data.message || 'Your password has been changed successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        validatePasswordForm();
                        alertBox.removeClass('d-none').text(data.message || 'Failed to update password.');
                    }
                },
                error: function() {
                    validatePasswordForm();
                    alertBox.removeClass('d-none').text('Server error. Unable to connect.');
                }
            });
        });


        // EMPLOYEE AVATAR HANDLERS
        $(document).on('click', '#btnEmpRemovePhoto', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove your profile picture?')) {
                $('#empProfilePic').val('');
                submitEmployeeAvatar('remove');
            }
        });

        $("#form_update_employee_avatar").submit(function(e) {
            e.preventDefault();
            submitEmployeeAvatar('upload');
        });

        function submitEmployeeAvatar(actionType) {
            const form = document.getElementById('form_update_employee_avatar');
            const formData = new FormData(form);
            formData.append('action', 'update_employee_avatar');
            formData.append('avatar_action_type', actionType);

            const $btnSave = $('#btnEmpSavePhoto');
            const $btnRemove = $('#btnEmpRemovePhoto');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                    $btnRemove.prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('<i class="bi bi-check2"></i>&ensp;Save Picture').prop('disabled', false);
                    $btnRemove.prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_emp_avatar']").val(res.token);

                    if (res.success === true || res.success === "true") {
                        if (actionType === 'remove') {
                            $('#empProfilePicPreview').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#empProfileInitialsFallback').removeClass('d-none');
                            $btnRemove.addClass('d-none');
                        } else if (res.image_url) {
                            const cleanUrl = res.image_url + '?t=' + new Date().getTime();
                            $('#empProfilePicPreview').attr('src', cleanUrl).removeClass('d-none').addClass('d-block');
                            $('#empProfileInitialsFallback').addClass('d-none');
                            $btnRemove.removeClass('d-none');
                        }

                        $('#empProfilePic').val('');
                        showAlert('success', actionType === 'remove' ? 'Photo Removed' : 'Photo Updated', res.message || 'Employee profile picture updated successfully.');
                    } else {
                        showAlert('error', 'Update Failed', res.message || 'Failed to update employee profile picture.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("EmployeeAvatarError:", error);
                    showAlert('error', 'Server Error', 'An error occurred while updating the employee profile picture.');
                }
            });
        }

        // EMPLOYEE COVER PHOTO HANDLERS
        $(document).on('click', '#btnEmpRemoveCover', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove the cover photo?')) {
                $('#empCoverPhoto').val('');
                submitEmployeeCover('remove_cover');
            }
        });

        $("#form_update_employee_cover").submit(function(e) {
            e.preventDefault();
            submitEmployeeCover('upload_cover');
        });

        function submitEmployeeCover(actionType) {
            const form = document.getElementById('form_update_employee_cover');
            const formData = new FormData(form);
            formData.append('action', 'update_employee_cover');
            formData.append('cover_action_type', actionType);

            const $btnSave = $('#btnEmpSaveCover');
            const $btnRemove = $('#btnEmpRemoveCover');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                    $btnRemove.prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('<i class="bi bi-check2"></i>&ensp;Save Cover').prop('disabled', false);
                    $btnRemove.prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_emp_cover']").val(res.token);

                    if (res.success === true || res.success === "true") {
                        if (actionType === 'remove_cover') {
                            $('#empCoverPhotoPreview').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#empCoverInitialsFallback').removeClass('d-none');
                            $btnRemove.addClass('d-none');
                        } else if (res.image_url) {
                            const cleanUrl = res.image_url + '?t=' + new Date().getTime();
                            $('#empCoverPhotoPreview').attr('src', cleanUrl).removeClass('d-none').addClass('d-block');
                            $('#empCoverInitialsFallback').addClass('d-none');
                            $btnRemove.removeClass('d-none');
                        }

                        $('#empCoverPhoto').val('');
                        showAlert('success', actionType === 'remove_cover' ? 'Cover Removed' : 'Cover Updated', res.message || 'Employee cover photo updated successfully.');
                    } else {
                        showAlert('error', 'Update Failed', res.message || 'Failed to update employee cover photo.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("EmployeeCoverError:", error);
                    showAlert('error', 'Server Error', 'An error occurred while updating the employee cover photo.');
                }
            });
        }

        // STUDENT AVATAR HANDLERS
        $(document).on('click', '#btnStdRemovePhoto', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove your profile picture?')) {
                $('#stdProfilePic').val('');
                submitStudentAvatar('remove');
            }
        });

        $("#form_update_student_avatar").submit(function(e) {
            e.preventDefault();
            submitStudentAvatar('upload');
        });

        function submitStudentAvatar(actionType) {
            const form = document.getElementById('form_update_student_avatar');
            const formData = new FormData(form);
            formData.append('action', 'update_student_avatar');
            formData.append('avatar_action_type', actionType);

            const $btnSave = $('#btnStdSavePhoto');
            const $btnRemove = $('#btnStdRemovePhoto');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                    $btnRemove.prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('<i class="bi bi-check2"></i>&ensp;Save Picture').prop('disabled', false);
                    $btnRemove.prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_std_avatar']").val(res.token);

                    if (res.success === true || res.success === "true") {
                        if (actionType === 'remove') {
                            $('#stdProfilePicPreview').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#stdProfileInitialsFallback').removeClass('d-none');
                            $btnRemove.addClass('d-none');
                        } else if (res.image_url) {
                            const cleanUrl = res.image_url + '?t=' + new Date().getTime();
                            $('#stdProfilePicPreview').attr('src', cleanUrl).removeClass('d-none').addClass('d-block');
                            $('#stdProfileInitialsFallback').addClass('d-none');
                            $btnRemove.removeClass('d-none');
                        }

                        $('#stdProfilePic').val('');
                        showAlert('success', actionType === 'remove' ? 'Photo Removed' : 'Photo Updated', res.message || 'Student profile picture updated successfully.');
                    } else {
                        showAlert('error', 'Update Failed', res.message || 'Failed to update student profile picture.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("StudentAvatarError:", error);
                    showAlert('error', 'Server Error', 'An error occurred while updating the student profile picture.');
                }
            });
        }

        // STUDENT COVER PHOTO HANDLERS
        $(document).on('click', '#btnStdRemoveCover', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove the cover photo?')) {
                $('#stdCoverPhoto').val('');
                submitStudentCover('remove_cover');
            }
        });

        $("#form_update_student_cover").submit(function(e) {
            e.preventDefault();
            submitStudentCover('upload_cover');
        });

        function submitStudentCover(actionType) {
            const form = document.getElementById('form_update_student_cover');
            const formData = new FormData(form);
            formData.append('action', 'update_student_cover');
            formData.append('cover_action_type', actionType);

            const $btnSave = $('#btnStdSaveCover');
            const $btnRemove = $('#btnStdRemoveCover');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                    $btnRemove.prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('<i class="bi bi-check2"></i>&ensp;Save Cover').prop('disabled', false);
                    $btnRemove.prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_std_cover']").val(res.token);

                    if (res.success === true || res.success === "true") {
                        if (actionType === 'remove_cover') {
                            $('#stdCoverPhotoPreview').addClass('d-none').removeClass('d-block').attr('src', '');
                            $('#stdCoverInitialsFallback').removeClass('d-none');
                            $btnRemove.addClass('d-none');
                        } else if (res.image_url) {
                            const cleanUrl = res.image_url + '?t=' + new Date().getTime();
                            $('#stdCoverPhotoPreview').attr('src', cleanUrl).removeClass('d-none').addClass('d-block');
                            $('#stdCoverInitialsFallback').addClass('d-none');
                            $btnRemove.removeClass('d-none');
                        }

                        $('#stdCoverPhoto').val('');
                        showAlert('success', actionType === 'remove_cover' ? 'Cover Removed' : 'Cover Updated', res.message || 'Student cover photo updated successfully.');
                    } else {
                        showAlert('error', 'Update Failed', res.message || 'Failed to update student cover photo.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("StudentCoverError:", error);
                    showAlert('error', 'Server Error', 'An error occurred while updating the student cover photo.');
                }
            });
        }

        // PASSWORD VALIDATION LOGIC
        function validatePasswordForm() {
            const currentPass = ($('#currentPassword').val() || '').trim();
            const pass = $('#newPassword').val() || '';
            const confirm = $('#confirmPassword').val() || '';

            const hasLength = pass.length >= 8;
            const hasCase = /[a-z]/.test(pass) && /[A-Z]/.test(pass);
            const hasNumberOrSymbol = /[0-9!@#$%^&*(),.?":{}|<>]/.test(pass);
            const isMatching = pass !== "" && pass === confirm;

            updateRuleState('#rule-length', hasLength);
            updateRuleState('#rule-case', hasCase);
            updateRuleState('#rule-number', hasNumberOrSymbol);
            updateRuleState('#rule-match', isMatching);

            let score = 0;
            if (hasLength) score += 33;
            if (hasCase) score += 33;
            if (hasNumberOrSymbol) score += 34;

            const $bar = $('#password-strength-bar');
            const $text = $('#password-strength-text');

            if (pass.length === 0) {
                $bar.css('width', '0%').removeClass('bg-warning bg-success').addClass('bg-danger');
                $text.text('Strength: Enter password');
            } else if (score < 66) {
                $bar.css('width', score + '%').removeClass('bg-warning bg-success').addClass('bg-danger');
                $text.text('Strength: Weak');
            } else if (score < 100) {
                $bar.css('width', score + '%').removeClass('bg-danger bg-success').addClass('bg-warning');
                $text.text('Strength: Medium');
            } else {
                $bar.css('width', '100%').removeClass('bg-danger bg-warning').addClass('bg-success');
                $text.text('Strength: Strong');
            }

            const $feedback = $('#password_match_feedback');
            if (confirm.length > 0) {
                if (isMatching) {
                    $feedback.removeClass('text-danger').addClass('text-success').text('Passwords match');
                } else {
                    $feedback.removeClass('text-success').addClass('text-danger').text('Passwords do not match');
                }
            } else {
                $feedback.text('');
            }

            const canSubmit = currentPass.length > 0 && hasLength && hasCase && hasNumberOrSymbol && isMatching;
            $('#btn_save_password').prop('disabled', !canSubmit);
        }

        $(document).on('input keyup change', '#currentPassword,#newPassword,#confirmPassword', validatePasswordForm);

        $(document).on('click', '.toggle-password', function() {
            const targetSelector = $(this).data('target');
            const $targetInput = $(targetSelector);
            const $icon = $(this).find('i');
            if (!$targetInput.length) return;

            const isPassword = $targetInput.attr('type') === 'password';
            $targetInput.attr('type', isPassword ? 'text' : 'password');
            $icon.toggleClass('bi-eye', !isPassword).toggleClass('bi-eye-slash', isPassword);
        });

        // USERNAME VALIDATION LOGIC
        function validateUsernameRules() {
            const val = ($('#newUsername').val() || '').trim();
            const validLength = val.length >= 6 && val.length <= 20;
            const validChars = /^[a-zA-Z0-9_-]+$/.test(val);
            const isDifferent = val !== "" && val.toLowerCase() !== currentUsername.toLowerCase();

            updateRuleState('#rule-user-length', validLength);
            updateRuleState('#rule-user-chars', validChars);
            updateRuleState('#rule-user-diff', isDifferent);

            if (!validLength || !validChars || !isDifferent || !isUsernameAvailable) {
                $('#btn_save_username').prop('disabled', true);
            }
            return {
                validLength,
                validChars,
                isDifferent
            };
        }

        function performLiveUsernameCheck() {
            isUsernameAvailable = false;
            const rules = validateUsernameRules();
            const username = ($('#newUsername').val() || '').trim();
            const $feedback = $('#username_availability_feedback');

            if (!rules.validLength || !rules.validChars || !rules.isDifferent) {
                $feedback.removeClass('text-success text-danger').addClass('text-muted').text('');
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: {
                    action: 'check_username',
                    username: username,
                    token_update_username: $("input[name='token_update_username']").val()
                },
                dataType: "json",
                beforeSend: function() {
                    $feedback.removeClass('text-danger text-success').addClass('text-muted').html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Checking availability...');
                },
                success: function(data) {
                    if (data.available === true) {
                        isUsernameAvailable = true;
                        $feedback.removeClass('text-danger text-muted').addClass('text-success').html('<i class="bi bi-check-circle-fill me-1"></i>Username is available!');
                        $('#btn_save_username').prop('disabled', false);
                    } else {
                        isUsernameAvailable = false;
                        $feedback.removeClass('text-success text-muted').addClass('text-danger').html('<i class="bi bi-x-circle-fill me-1"></i>' + (data.message || 'Username is already taken.'));
                        $('#btn_save_username').prop('disabled', true);
                    }
                },
                error: function() {
                    isUsernameAvailable = false;
                    $feedback.removeClass('text-success text-muted').addClass('text-danger').text('Error checking availability. Try again.');
                    $('#btn_save_username').prop('disabled', true);
                }
            });
        }

        $(document).on('keyup input', '#newUsername', function() {
            clearTimeout(typingTimer);
            isUsernameAvailable = false;
            const rules = validateUsernameRules();

            if (rules.validLength && rules.validChars && rules.isDifferent) {
                $('#username_availability_feedback').removeClass('text-danger text-success').addClass('text-muted').html('<span class="spinner-border spinner-border-sm me-1"></span>Typing...');
                typingTimer = setTimeout(performLiveUsernameCheck, doneTypingInterval);
            } else {
                $('#username_availability_feedback').text('');
            }
        });


        //  DIGITAL PROFILE

        // Remove or replace the old handleCopy and click handlers:
        function handleCopy(inputId, feedbackId, label) {
            const inputElem = document.getElementById(inputId);
            if (!inputElem) return;

            // 1. Select the text synchronously (Crucial for iOS)
            inputElem.focus();
            inputElem.select();
            inputElem.setSelectionRange(0, 99999); // Ensures selection works on iOS WebKit

            let copySuccessful = false;

            // 2. Try modern Clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(inputElem.value)
                    .then(() => showFeedback(true))
                    .catch(() => fallbackCopy());
            } else {
                fallbackCopy();
            }

            // 3. Fallback method using execCommand for older mobile browsers & iOS WebViews
            function fallbackCopy() {
                try {
                    copySuccessful = document.execCommand('copy');
                    showFeedback(copySuccessful);
                } catch (err) {
                    showFeedback(false);
                }
            }

            // 4. Show success or error feedback
            function showFeedback(isSuccess) {
                if (isSuccess) {
                    $(feedbackId).removeClass('text-danger').addClass('text-success').text(label + ' copied to clipboard!');
                } else {
                    $(feedbackId).removeClass('text-success').addClass('text-danger').text('Failed to copy.');
                }

                // Deselect input text
                window.getSelection().removeAllRanges();
                inputElem.blur();

                setTimeout(() => $(feedbackId).text(''), 3000);
            }
        }

        $(document).on('click', '#btnCopyCardUid', function(e) {
            e.preventDefault();
            handleCopy('cardUidDisplay', '#copy_uid_feedback', 'Card UID');
        });

        $(document).on('click', '#btnCopyCardUrl', function(e) {
            e.preventDefault();
            handleCopy('cardUrlDisplay', '#copy_url_feedback', 'Card URL');
        });

        window.togglePostNominal = function(checkbox) {
            const select = document.getElementById('nameFormatSelect');
            const isChecked = checkbox.checked;

            Array.from(select.options).forEach(option => {
                option.setAttribute('data-name', isChecked ? option.getAttribute('data-name-with-pn') : option.getAttribute('data-name-without-pn'));
            });

            updateNameFormat(select);
        }

        window.updateNameFormat = function(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const previewName = document.getElementById('cardPreviewName');
            if (previewName && selectedOption) {
                previewName.textContent = selectedOption.getAttribute('data-name');
            }
        };

        $(document).on('input', '#customTitleInput', function() {
            const val = $(this).val().trim();
            $('#cardPreviewTitle').text(val ? val + ' • ' : '');
        });

        $(document).on('input', '#companyInput', function() {
            $('#cardPreviewCompany').text($(this).val().trim());
        });

        $(document).on('input', '#cardBioInput', function() {
            $('#cardPreviewBio').text($(this).val().trim());
        });

        $(document).on('input', '#websiteInput', function() {
            const val = $(this).val().trim();
            const $item = $('#cardPreviewContainer .contact-item:has(.bi-globe)');
            if (val) {
                $item.removeClass('d-none').find('span').text(val);
                $item.attr('href', val.startsWith('http') ? val : 'https://' + val);
            } else {
                $item.addClass('d-none');
            }
        });

        $(document).on('input', '#hoursInput', function() {
            const val = $(this).val().trim();
            const $item = $('#cardPreviewContainer .contact-item:has(.bi-clock)');
            if (val) {
                $item.removeClass('d-none').find('span').text(val);
            } else {
                $item.addClass('d-none');
            }
        });

        $(document).on('input', '#addressInput', function() {
            const val = $(this).val().trim();
            const $item = $('#cardPreviewContainer .contact-item:has(.bi-geo-alt-fill)');
            if (val) {
                $item.removeClass('d-none').find('span').text(val);
            } else {
                $item.addClass('d-none');
            }
        });

        const socialIconsMap = {
            viber: 'fa-brands fa-viber',
            whatsapp: 'fa-brands fa-whatsapp',
            linkedin: 'fa-brands fa-linkedin-in',
            facebook: 'fa-brands fa-facebook-f',
            instagram: 'fa-brands fa-instagram',
            tiktok: 'fa-brands fa-tiktok',
            youtube: 'fa-brands fa-youtube',
            github: 'fa-brands fa-github',
            twitter: 'fa-brands fa-x-twitter',
            telegram: 'fa-brands fa-telegram',
            threads: 'fa-brands fa-threads',
            behance: 'fa-brands fa-behance',
            pinterest: 'fa-brands fa-pinterest',
            snapchat: 'fa-brands fa-snapchat',
            dribbble: 'fa-brands fa-dribbble'
        };

        function formatSocialUrl(key, input) {
            if (!input) return '#';
            input = input.trim();
            if (/^https?:\/\//i.test(input)) return input;
            const handle = input.replace(/^@/, '');

            switch (key) {
                case 'whatsapp':
                    return `https://wa.me/${input.replace(/[^0-9]/g, '')}`;
                case 'viber':
                    return `viber://chat?number=${input.replace(/[^0-9+]/g, '')}`;
                case 'telegram':
                    return `https://t.me/${handle}`;
                case 'instagram':
                    return `https://instagram.com/${handle}`;
                case 'facebook':
                    return `https://facebook.com/${handle}`;
                case 'twitter':
                    return `https://x.com/${handle}`;
                case 'linkedin':
                    return `https://linkedin.com/in/${handle}`;
                case 'github':
                    return `https://github.com/${handle}`;
                case 'youtube':
                    return `https://youtube.com/@${handle}`;
                case 'tiktok':
                    return `https://tiktok.com/@${handle}`;
                default:
                    return `https://${input}`;
            }
        }

        function rebuildSocialPreview() {
            const $grid = $('#cardPreviewSocials');
            const $section = $('#cardPreviewSocialSection');
            if (!$grid.length) return;

            $grid.empty();
            let count = 0;

            $('input[name^="social_options"]').each(function() {
                const nameAttr = $(this).attr('name');
                const match = nameAttr.match(/^social_options\[([^\]]+)\]\[url\]$/);
                if (match) {
                    const key = match[1];
                    const rawVal = $(this).val().trim();
                    const isEnabled = $('#switch_' + key).is(':checked');

                    if (isEnabled && rawVal !== '') {
                        const formattedUrl = formatSocialUrl(key, rawVal);
                        const iconClass = socialIconsMap[key] || 'bi-link-45deg';
                        const socialName = key.charAt(0).toUpperCase() + key.slice(1);
                        const linkHtml = `<a href="${escapeHtml(formattedUrl)}" class="social-card" title="${escapeHtml(socialName)}" target="_blank" rel="noopener noreferrer"><i class="${iconClass}"></i></a>`;

                        $grid.append(linkHtml);
                        count++;
                    }
                }
            });

            // Hide header & section if no active social media items remain
            if (count > 0) {
                $section.removeClass('d-none');
            } else {
                $section.addClass('d-none');
            }
        }

        function escapeHtml(str) {
            return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        $(document).on('input change', 'input[name^="social_options"], [id^="switch_"]', function() {
            rebuildSocialPreview();
        });

        $("#form_update_business_card").submit(function(e) {
            e.preventDefault();
            const $form = $(this);
            const $alertBox = $('#card_msg_alert').addClass('d-none');
            const $btnSave = $('#btnSaveCardDetails');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/account-process",
                data: $form.serialize() + '&action=update_business_card',
                dataType: "json",
                beforeSend: function() {
                    $btnSave.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...').prop('disabled', true);
                },
                complete: function() {
                    $btnSave.html('<i class="bi bi-save me-1"></i>Save All Details').prop('disabled', false);
                },
                success: function(res) {
                    if (res.token) $("input[name='token_update_card']").val(res.token);
                    if (res.success === true || res.success === "true") {
                        showAlert('success', 'Card Details Saved', res.message || 'Digital business card updated successfully.');
                    } else {
                        $alertBox.removeClass('d-none').text(res.message || 'Failed to save business card details.');
                    }
                },
                error: function() {
                    $alertBox.removeClass('d-none').text('Server communication error. Please try again.');
                }
            });
        });
    })();

    //  DIGITAL PROFILE
    window.updateLayout = function(val) {
        const $input = $('#input_card_layout');
        if ($input.length) $input.val(val);

        const $preview = $('#cardPreviewContainer');
        if ($preview.length) $preview.attr('data-layout', val);

        const $select = $('#layoutSelect');
        if ($select.length && $select.val() !== val) $select.val(val);
    };

    // window.updateColor = function(colorTheme) {
    //     const $input = $('#input_card_theme');
    //     if ($input.length) $input.val(colorTheme);

    //     const $preview = $('#cardPreviewContainer');
    //     if ($preview.length) $preview.attr('data-color', colorTheme);
    // };

    window.updateColor = function(colorTheme) {
        const $input = $('#input_card_theme');
        if ($input.length) $input.val(colorTheme);

        const $preview = $('#cardPreviewContainer');
        if ($preview.length) $preview.attr('data-color', colorTheme);

        const $controls = $('.controls-bar');
        if ($controls.length) $controls.attr('data-color', colorTheme);
    };

    // Global image preview handler
    window.previewImage = function(input, targetPreviewId) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, GIF, WEBP).');
                input.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('File size exceeds 5MB limit.');
                input.value = '';
                return;
            }

            $('#avatarActionType').val('upload');
            const reader = new FileReader();

            reader.onload = function(e) {
                let $preview = targetPreviewId ? $('#' + targetPreviewId) : $('#userProfilePicPreview');
                if ($preview.length) {
                    $preview.attr('src', e.target.result).removeClass('d-none').show();
                    $preview.siblings('.profile-initials, .avatar-initials, [id$="InitialsFallback"]').addClass('d-none').removeClass('d-flex');
                }
            };

            reader.readAsDataURL(file);
        }
    };

    window.downloadVCard = function() {
        const profile = <?= json_encode([
                            'name'    => $profile['name']    ?? '',
                            'title'   => $profile['title']   ?? '',
                            'company' => $profile['company'] ?? '',
                            'phone'   => $profile['phone']   ?? '',
                            'email'   => $profile['email']   ?? '',
                            'website' => $profile['website'] ?? '',
                            'address' => $profile['address'] ?? ''
                        ]) ?>;

        const vcardLines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            `FN:${profile.name}`,
            `N:;${profile.name};;;`
        ];

        if (profile.title) vcardLines.push(`TITLE:${profile.title}`);
        if (profile.company) vcardLines.push(`ORG:${profile.company}`);
        if (profile.phone) vcardLines.push(`TEL;TYPE=CELL:${profile.phone}`);
        if (profile.email) vcardLines.push(`EMAIL;TYPE=INTERNET:${profile.email}`);
        if (profile.website) vcardLines.push(`URL:${profile.website}`);
        if (profile.address) vcardLines.push(`ADR;TYPE=WORK:;;${profile.address};;;`);

        vcardLines.push('END:VCARD');

        const vcardData = vcardLines.join('\r\n');
        const blob = new Blob([vcardData], {
            type: 'text/vcard;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const fileName = (profile.name || 'contact').trim().replace(/[^a-zA-Z0-9]/g, '_');

        link.href = url;
        link.setAttribute('download', `${fileName}.vcf`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };
</script>

</html>