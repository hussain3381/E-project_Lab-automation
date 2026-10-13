<?php
// Personal identity and dashboard choices belong to the signed-in account, not lab-wide settings.
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_page_access(__FILE__);
require_once __DIR__ . '/models/UserPreferences.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$phoneColumn = $conn->query(
    "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone' LIMIT 1"
)->num_rows > 0;
$phoneExpression = $phoneColumn ? 'phone' : 'NULL AS phone';
$accountStatement = $conn->prepare('SELECT name, username, email, ' . $phoneExpression . ', role, is_active FROM users WHERE id = ? LIMIT 1');
$accountStatement->bind_param('i', $userId);
$accountStatement->execute();
$account = $accountStatement->get_result()->fetch_assoc();
$accountStatement->close();
if (!$account) {
    app_logout_session();
    header('Location: login.php?disabled=1', true, 303);
    exit;
}

$preferences = UserPreferences::find($conn, $userId);
$pageError = (string) ($_SESSION['profile_error'] ?? '');
$pageSuccess = (string) ($_SESSION['profile_success'] ?? '');
unset($_SESSION['profile_error'], $_SESSION['profile_success']);
$pageTitle = 'My profile';
$pageEyebrow = 'PERSONAL WORKSPACE';
$pageDescription = 'Update your contact details and choose how your own dashboard behaves. These settings do not change anyone else’s access.';
$pageActionHtml = '<a class="button button-quiet" href="dashboard.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to overview</a>';
$pageStylesheet = 'assets/css/pages/profile.css';
require __DIR__ . '/views/layouts/app_start.php';
?>

<?php if ($pageError !== ''): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($pageSuccess !== ''): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($pageSuccess, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if (!$preferences['schema_available'] || !$phoneColumn): ?>
    <div class="alert alert-error profile-migration-note" role="alert">
        <strong>Profile preferences need the latest database update.</strong>
        <span>Back up the database, then apply additive migration <code>010_user_profiles_and_preferences.sql</code> (or the equivalent manual upgrade). Existing account data is kept.</span>
    </div>
<?php endif; ?>

<section class="profile-layout" data-reveal>
    <aside class="panel profile-summary">
        <div class="profile-avatar" aria-hidden="true">
            <?php
                $profileNameParts = preg_split('/\s+/', trim((string) $account['name'])) ?: [];
                $profileInitials = '';
                foreach ($profileNameParts as $profileNamePart) {
                    if ($profileNamePart !== '') {
                        $profileInitials .= strtoupper(substr($profileNamePart, 0, 1));
                    }
                    if (strlen($profileInitials) >= 2) break;
                }
                echo htmlspecialchars($profileInitials !== '' ? $profileInitials : 'LA', ENT_QUOTES, 'UTF-8');
            ?>
        </div>
        <p class="eyebrow">SIGNED-IN ACCOUNT</p>
        <h2><?php echo htmlspecialchars((string) $account['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <span class="status-pill is-info"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?php echo htmlspecialchars((string) ($_SESSION['role'] ?? $account['role']), ENT_QUOTES, 'UTF-8'); ?></span>
        <dl class="profile-meta">
            <div><dt>Username</dt><dd><?php echo htmlspecialchars((string) $account['username'], ENT_QUOTES, 'UTF-8'); ?></dd></div>
            <div><dt>Account status</dt><dd><?php echo (int) $account['is_active'] === 1 ? 'Active' : 'Inactive'; ?></dd></div>
        </dl>
        <p class="profile-summary-note">Username and role are managed by your Administrator. Contact details and personal display preferences can be changed below.</p>
    </aside>

    <form method="post" action="save-preferences.php" class="panel profile-form" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <header class="panel-header">
            <div><p class="eyebrow">YOUR DETAILS</p><h2>Profile and dashboard preferences</h2><p>Changes are saved to your account and follow you between devices.</p></div>
        </header>
        <div class="panel-body profile-form-body">
            <div class="field-grid">
                <div class="form-field">
                    <label for="profile-name">Full name</label>
                    <input id="profile-name" name="name" type="text" maxlength="120" value="<?php echo htmlspecialchars((string) $account['name'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required>
                </div>
                <div class="form-field">
                    <label for="profile-username">Username</label>
                    <input id="profile-username" type="text" value="<?php echo htmlspecialchars((string) $account['username'], ENT_QUOTES, 'UTF-8'); ?>" readonly aria-readonly="true">
                </div>
                <div class="form-field">
                    <label for="profile-email">Email address <span class="optional-mark">Optional</span></label>
                    <input id="profile-email" name="email" type="email" maxlength="190" value="<?php echo htmlspecialchars((string) ($account['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email">
                    <small class="form-hint">Your account email is stored in the database and can be used to sign in.</small>
                </div>
                <div class="form-field">
                    <label for="profile-phone">Phone number <span class="optional-mark">Optional</span></label>
                    <input id="profile-phone" name="phone" type="tel" maxlength="40" value="<?php echo htmlspecialchars((string) ($account['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="tel"<?php echo !$phoneColumn ? ' disabled' : ''; ?>>
                </div>
            </div>

            <div class="profile-divider"></div>
            <div class="profile-preferences-heading"><div><p class="eyebrow">DASHBOARD</p><h3>Personal display settings</h3></div><span class="status-pill">Saved to your account</span></div>
            <div class="field-grid">
                <div class="form-field">
                    <label for="profile-theme">Color theme</label>
                    <select id="profile-theme" name="theme">
                        <option value="dark"<?php echo $preferences['theme'] === 'dark' ? ' selected' : ''; ?>>Dark teal</option>
                        <option value="light"<?php echo $preferences['theme'] === 'light' ? ' selected' : ''; ?>>Light</option>
                    </select>
                    <small class="form-hint">This palette is used on your signed-in pages.</small>
                </div>
                <div class="profile-toggle-list">
                    <label class="preference-toggle">
                        <input type="checkbox" name="compact_dashboard" value="1"<?php echo $preferences['compact_dashboard'] ? ' checked' : ''; ?>>
                        <span><strong>Compact dashboard</strong><small>Reduce card spacing so more information fits on screen.</small></span>
                    </label>
                    <label class="preference-toggle">
                        <input type="checkbox" name="reduced_motion" value="1"<?php echo $preferences['reduced_motion'] ? ' checked' : ''; ?>>
                        <span><strong>Reduce interface motion</strong><small>Keep transitions and animated effects to a minimum.</small></span>
                    </label>
                </div>
            </div>
        </div>
        <footer class="profile-form-actions">
            <a class="button button-quiet" href="dashboard.php">Cancel</a>
            <button class="button button-primary" type="submit"<?php echo !$preferences['schema_available'] || !$phoneColumn ? ' disabled' : ''; ?>><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile</button>
        </footer>
    </form>
</section>
<?php require __DIR__ . '/views/layouts/app_end.php'; ?>
