<nav class="c-navbar">
    <div class="c-navbar__brand">
        ACES<span>v3</span>
    </div>

    <div class="c-navbar__actions">

        <button id="theme-toggle" class="c-theme-toggle" aria-label="Toggle theme">
            <svg id="theme-icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5" />
                <line x1="12" y1="1" x2="12" y2="3" />
                <line x1="12" y1="21" x2="12" y2="23" />
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                <line x1="1" y1="12" x2="3" y2="12" />
                <line x1="21" y1="12" x2="23" y2="12" />
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
            </svg>
        </button>

        <div class="c-chip" id="user-chip">
            <?php if (!empty($_SESSION['avatar'])): ?>
                <img src="/acesv2/assets/img/uploads/<?= htmlspecialchars($_SESSION['avatar']) ?>"
                    class="c-chip__avatar"
                    alt="User Avatar"
                    style="object-fit: cover; width: 32px; height: 32px; border-radius: 50%;">
            <?php else: ?>
                <div class="c-chip__avatar"><?= htmlspecialchars($user['initials']) ?></div>
            <?php endif; ?>

            <div class="c-chip__info">
                <span class="c-chip__name"><?= htmlspecialchars($user['name']) ?></span>
                <span class="c-chip__role"><?= htmlspecialchars($user['role']) ?></span>
            </div>

            <span class="c-chip__chevron">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </span>

            <div class="c-dropdown" id="chip-drop">
                <?php if ($isAdmin): ?>
                    <a href="index.php?page=settings" class="c-dropdown__item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        Settings
                    </a>
                    <div class="c-dropdown__divider"></div>
                <?php endif; ?>

                <a href="javascript:void(0)" class="c-dropdown__item c-dropdown__item--logout" onclick="confirmLogout()"> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <path d="M16 17l5-5-5-5" />
                        <path d="M21 12H9" />
                    </svg>
                    Sign Out
                    </button>
                </a>
            </div>
        </div>
</nav>