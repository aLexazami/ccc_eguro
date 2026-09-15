<?php
// Core Config & Dependencies
$db_connect = $db_connect ?? null;

require_once HELPER;

# =========================================================================================================
$slug_mask = isset($_ROUTE_PARAMS['slug']) ? trim($_ROUTE_PARAMS['slug']) : '';
$employee_profile = $helper->selectDataExists('employee_profile', ['card_uid' => $slug_mask]) ?? [];

// Redirect to 404 if profile doesn't exist
if (empty($employee_profile)) {
    http_response_code(404);
    require HTTP_404;
    exit();
}

$user_id  = $employee_profile['user_id'] ?? 0;
$users    = $helper->selectDataExists('users', ['id' => $user_id]) ?? [];
$employee = $helper->selectDataExists('employee', ['user_id' => $user_id]) ?? [];
$student  = $helper->selectDataExists('student', ['user_id' => $user_id]) ?? [];

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
    "website"           => $employee_profile['website'] ?? "",
    "address"           => $employee_profile['address'] ?? "Old Municipal Site, Barreto St, Brgy. VII, Poblacion, Calamba City, Laguna, Philippines",
    "hours"             => $employee_profile['hours'] ?? "",
    "social_options"    => $employee_profile['social_options'] ?? "",
];

// Computed Fields
$profile["initials"]    = getNameInitials($fName . " " . $lName);
$profile["phone_url"]   = getSocialUrl("phone", $profile['phone']);
$profile["website_url"] = getSocialUrl("website", $profile['website']);
$profile['company_logo'] = CCC_LOGO;
$profile['company_logo_white'] = CCC_WHITE_LOGO;
/***
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

/***
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

/***
 * Transforms generic handle inputs or absolute URLs into structured platform schemes.
 */
function getSocialUrl($platform, $value)
{
    $value = trim($value ?? '');
    if (empty($value)) {
        return '#';
    }
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

$hasAvatar   = !empty($profile['avatar']);
$avatarStyle = $hasAvatar ? "style=\"--avatar-img:url('" . htmlspecialchars($profile['avatar'], ENT_QUOTES, 'UTF-8') . "');\"" : "";

$hasBanner   = !empty($profile['banner']);
$bannerStyle = $hasBanner ? "style=\"background-image:url('" . htmlspecialchars($profile['banner'], ENT_QUOTES, 'UTF-8') . "');\"" : "";
$bannerClass = $hasBanner ? "profile-card-header" : "profile-card-header profile-card-header-fallback";

$initials = getNameInitials($fName . " " . $lName);

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
    'dribbble'   => ['name' => 'Dribbble',  'icon' => 'fa-brands fa-dribbble']
];

$profile['name'] = $activeName;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport">
    <meta content="<?php echo htmlspecialchars(SCHOOL_NAME, ENT_QUOTES, 'UTF-8'); ?>" name="description">
    <meta content="" name="keywords">
    <meta content="" name="author">
    <title><?= htmlspecialchars($profile['name']) . ' • ' . htmlspecialchars($profile['company']); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo CCC_FAVICON; ?>">
    <link rel="apple-touch-icon" href="<?php echo CCC_FAVICON; ?>">
    <?php include LINK_DATA_PATH; ?>
</head>

<body class="profile-body <?= !$hasAvatar ? 'no-avatar' : '' ?>" <?= $avatarStyle ?> data-color="<?= htmlspecialchars($cardTheme, ENT_QUOTES, 'UTF-8') ?>" data-layout="<?= htmlspecialchars($cardLayout, ENT_QUOTES, 'UTF-8') ?>">
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
                    <span id="cardPreviewName"><?= htmlspecialchars($activeName, ENT_QUOTES, 'UTF-8') ?></span><?php if (!empty($profile['verified'])): ?><i class="bi bi-patch-check-fill verified-badge" title="Verified Profile"></i><?php endif; ?>
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
</body>

<?php include SCRIPT_DATA_PATH; ?>

<script>
    function downloadVCard() {
        const profile = <?= json_encode([
                            'name'    => $profile['name'] ?? '',
                            'title'   => $profile['title'] ?? '',
                            'company' => $profile['company'] ?? '',
                            'phone'   => $profile['phone'] ?? '',
                            'email'   => $profile['email'] ?? '',
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
    }
</script>

</html>