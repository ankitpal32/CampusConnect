<footer class="cc-footer">
    <div class="container">
        <div class="row g-4">

            <div class="col-lg-5">
                <a href="/campusconnect/index.php" class="cc-footer-brand">
                    <span class="cc-brand-mark" aria-hidden="true">C</span>
                    <span>CampusConnect</span>
                </a>

                <p class="cc-footer-desc">
                    A college event management platform built with Core PHP, MySQL, and Bootstrap. Helps students discover campus activities and organizers manage attendance.
                </p>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <span class="cc-badge cc-badge-neutral">Core PHP 8 and MySQL</span>
                    <span class="cc-badge cc-badge-success">Active Platform</span>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <h3 class="cc-footer-heading">Navigation</h3>
                <ul class="cc-footer-links">
                    <li>
                        <a href="/campusconnect/events.php">All Events</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#categories">Categories</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#about">About Platform</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#faq">Frequently Asked Questions</a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h3 class="cc-footer-heading">Account Access</h3>
                <ul class="cc-footer-links">
                    <?php if (isset($_SESSION['user']['id'])): ?>
                        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                            <li>
                                <a href="/campusconnect/admin/index.php">Admin Dashboard</a>
                            </li>
                            <li>
                                <a href="/campusconnect/admin/events.php">Manage Events</a>
                            </li>
                            <li>
                                <a href="/campusconnect/admin/attendees.php">Event Attendees</a>
                            </li>
                            <li>
                                <a href="/campusconnect/admin/reports.php">Platform Reports</a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="/campusconnect/dashboard.php">Student Dashboard</a>
                            </li>
                            <li>
                                <a href="/campusconnect/my-registrations.php">My Registrations</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li>
                            <a href="/campusconnect/login.php">Sign In</a>
                        </li>
                        <li>
                            <a href="/campusconnect/register.php">Create Student Account</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <hr class="cc-footer-divider">

        <div class="cc-footer-bottom">
            <p class="mb-0">
                &copy; <?= date('Y') ?> CampusConnect. College event management platform.
            </p>

            <p class="mb-0">
                MySQL database integration, capacity controls, and attendance tracking.
            </p>
        </div>
    </div>
</footer>

</body>
</html>
