<footer class="cc-footer">
    <div class="container">
        <div class="row g-4">

            <div class="col-lg-5">
                <a href="/campusconnect/index.php" class="cc-footer-brand">
                    <span class="cc-brand-mark" aria-hidden="true">C</span>
                    <span>CampusConnect</span>
                </a>

                <p class="cc-footer-desc">
                    A college event management platform developed as a student full-stack web project. Contextualized for Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu, Hooghly (MAKAUT curriculum). Demonstrates real-time seat reservation, atomic concurrency locking, and door attendance tracking.
                </p>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <span class="cc-badge cc-badge-neutral">Core PHP 8 &bull; MySQL (PDO)</span>
                    <span class="cc-badge cc-badge-category">SKFGI Mankundu, Hooghly</span>
                </div>

                <p class="small text-muted mt-2 mb-0" style="font-size: 0.78rem; line-height: 1.5;">
                    <em>Notice: CampusConnect is a student-developed MVP for academic project demonstration and is not the official event announcement portal of SKFGI.</em>
                </p>
            </div>

            <div class="col-6 col-lg-3">
                <h3 class="cc-footer-heading">Platform Discovery</h3>
                <ul class="cc-footer-links">
                    <li>
                        <a href="/campusconnect/events.php">Explore All Events</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#categories">Interest Categories</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#how-it-works">How Registration Works</a>
                    </li>
                    <li>
                        <a href="/campusconnect/index.php#faq">Frequently Asked Questions</a>
                    </li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h3 class="cc-footer-heading">Access & Demo Accounts</h3>
                <ul class="cc-footer-links">
                    <?php if (isset($_SESSION['user']['id'])): ?>
                        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                            <li>
                                <a href="/campusconnect/admin/index.php">Admin Center</a>
                            </li>
                            <li>
                                <a href="/campusconnect/admin/events.php">Manage Catalog</a>
                            </li>
                            <li>
                                <a href="/campusconnect/admin/attendees.php">Door Attendees & Check-in</a>
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
                            <a href="/campusconnect/login.php">Sign In (with Demo Explorer)</a>
                        </li>
                        <li>
                            <a href="/campusconnect/register.php">Create Student Account</a>
                        </li>
                    <?php endif; ?>
                    <li class="pt-2 mt-2 border-top" style="border-color: rgba(233, 229, 243, 0.4) !important;">
                        <span class="small text-muted d-block">Campus: 1 Khan Road, Mankundu</span>
                        <span class="small text-muted d-block">MAKAUT, West Bengal</span>
                    </li>
                </ul>
            </div>

        </div>

        <hr class="cc-footer-divider">

        <div class="cc-footer-bottom">
            <p class="mb-0">
                &copy; <?= date('Y') ?> CampusConnect &bull; Developed by Ankit Pal &bull; SKFGI, Mankundu, Hooghly
            </p>

            <p class="mb-0 text-muted">
                Academic Project Demonstration &bull; MAKAUT, West Bengal
            </p>
        </div>
    </div>
</footer>

</body>
</html>
