<section id="vendorPasswordSection" class="ops-section" aria-label="Password settings" style="margin-top:12px;">
    <div class="ops-header">
        <p class="ops-title">Password</p>
        <span class="ops-chip">Security</span>
    </div>
    <p class="profile-help">Use a strong password and update it periodically for account security.</p>

    <details class="ops-form">
        <summary class="btn btn-secondary" style="cursor:pointer;display:inline-flex;">Change Password</summary>
        <form method="POST" action="/portal/vendor/password/update" style="margin-top:12px;">
            @csrf
            <div class="profile-grid">
                <div class="profile-field"><label for="current_password">Current Password</label><input id="current_password" name="current_password" class="profile-input" type="password" minlength="8" required></div>
                <div class="profile-field"><label for="password">New Password</label><input id="password" name="password" class="profile-input" type="password" minlength="8" required></div>
                <div class="profile-field"><label for="password_confirmation">Confirm New Password</label><input id="password_confirmation" name="password_confirmation" class="profile-input" type="password" minlength="8" required></div>
            </div>
            <button class="btn btn-primary" type="submit">Update Password</button>
        </form>
    </details>
</section>
