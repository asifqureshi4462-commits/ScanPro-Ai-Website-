<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/security.php';

$pageTitle = 'Pricing';
$pageDescription = 'Compare ScanPro AI Free, Pro, and Ultra plans with monthly and yearly options, cloud vault limits, and transparent feature availability.';
$activePage = 'pricing';
$extraScripts = ['assets/js/pricing.js'];

$fallbackPlans = [
    [
        'slug'          => 'free',
        'name'          => 'Free',
        'tagline'       => 'Essential document scanning & offline vault',
        'price_monthly' => '0.00',
        'price_yearly'  => '0.00',
        'storage_label' => '5 GB Vault Storage',
        'is_featured'   => 0,
        'features_json' => json_encode([
            'Smart Camera Scanner & Edge Crop',
            '11 Real-Time Enhancement Filters',
            'Local Offline Document Vault',
            'Basic PDF Viewing & Organization',
            'Email & Password Web Account',
        ]),
    ],
    [
        'slug'          => 'pro',
        'name'          => 'Pro',
        'tagline'       => 'Full Gemini AI OCR, translation & PDF toolkit',
        'price_monthly' => '9.99',
        'price_yearly'  => '95.88',
        'storage_label' => '100 GB Cloud Vault',
        'is_featured'   => 1,
        'features_json' => json_encode([
            'Everything in Free',
            'Gemini AI OCR Text Extraction',
            'Executive Summaries & Document Q&A',
            '50+ Language Document Translation',
            'AES-128 PDF Encryption & Watermarking',
        ]),
    ],
    [
        'slug'          => 'ultra',
        'name'          => 'Ultra',
        'tagline'       => 'High-volume AI workflows & expanded vault',
        'price_monthly' => '19.99',
        'price_yearly'  => '191.88',
        'storage_label' => '1 TB Cloud Vault',
        'is_featured'   => 0,
        'features_json' => json_encode([
            'Everything in Pro',
            'Unlimited AI Table-to-CSV Extraction',
            'Exam Question Set Parsing Studio',
            'Priority Multi-Format Office Sync',
            'Priority Customer Support',
        ]),
    ],
];

$plans = $fallbackPlans;
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT slug, name, tagline, price_monthly, price_yearly, storage_label, is_featured, features_json FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    $dbPlans = $stmt->fetchAll();
    if (!empty($dbPlans)) {
        $plans = $dbPlans;
    }
} catch (Throwable $e) {
    // Fallback plans remain active when MySQL schema is not yet imported
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$isLoggedIn = !empty($_SESSION['user_id']);
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">PLANS &amp; PRICING</span>
            <h1>Choose the Right Plan for Your <span class="text-gradient">Document Vault</span></h1>
            <p>
                Start free with essential scanning and account access, or review Pro and Ultra tiers structured for upcoming payment gateway integration.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <!-- Monthly / Yearly Toggle -->
            <div class="billing-switch-wrap">
                <span class="billing-label active" id="monthlyLabel">Monthly</span>
                <button
                    type="button"
                    class="toggle-switch"
                    id="billingToggleBtn"
                    role="switch"
                    aria-checked="false"
                    aria-label="Toggle yearly billing"
                >
                    <span class="toggle-switch-knob" style="display:block;"></span>
                </button>
                <span class="billing-label" id="yearlyLabel">Yearly</span>
                <span class="save-pill">Save 20%</span>
            </div>

            <div class="alert alert--info mb-3" id="billingNoticeBanner">
                <i class="fa-solid fa-circle-info" aria-hidden="true" style="margin-top:2px;"></i>
                <div class="alert-body">
                    <strong>Payment Integration Notice:</strong> Online checkout for Pro and Ultra plans is not enabled yet and will connect to a real payment provider in a future phase. New registrations start on the <strong>Free</strong> plan automatically.
                </div>
            </div>

            <!-- Plan Cards -->
            <div class="grid grid-3" id="pricingGrid">
                <?php foreach ($plans as $plan): ?>
                    <?php
                    $slug = (string) ($plan['slug'] ?? 'free');
                    $isFeatured = ((int) ($plan['is_featured'] ?? 0)) === 1;
                    $monthly = (float) ($plan['price_monthly'] ?? 0);
                    $yearlyTotal = (float) ($plan['price_yearly'] ?? 0);
                    $yearlyPerMonth = $yearlyTotal > 0 ? round($yearlyTotal / 12, 2) : 0.00;
                    $features = json_decode((string) ($plan['features_json'] ?? '[]'), true);
                    if (!is_array($features)) {
                        $features = [];
                    }
                    ?>
                    <article
                        class="card pricing-card<?= $isFeatured ? ' card--highlight' : '' ?>"
                        data-plan-slug="<?= e($slug) ?>"
                        data-monthly="<?= e(number_format($monthly, 2, '.', '')) ?>"
                        data-yearly-monthly="<?= e(number_format($yearlyPerMonth, 2, '.', '')) ?>"
                        data-yearly-total="<?= e(number_format($yearlyTotal, 2, '.', '')) ?>"
                    >
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                            <h3><?= e((string) $plan['name']) ?></h3>
                            <?php if ($isFeatured): ?>
                                <span class="card-badge card-badge--pro">Recommended</span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1"><?= e((string) ($plan['tagline'] ?? '')) ?></p>

                        <div class="pricing-price">
                            <span class="pricing-amount">$<?= e(number_format($monthly, 2)) ?></span>
                            <span class="pricing-cycle">/ month</span>
                        </div>
                        <p class="pricing-billed-note text-muted" style="font-size:0.8rem;min-height:20px;">
                            <?= $monthly <= 0 ? 'Free forever • No card required' : 'Billed monthly' ?>
                        </p>

                        <span class="card-badge card-badge--cyan mt-1" style="align-self:flex-start;">
                            <?= e((string) ($plan['storage_label'] ?? 'Cloud Vault')) ?>
                        </span>

                        <ul class="pricing-features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    <span><?= e((string) $feature) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($slug === 'free'): ?>
                            <a href="<?= $isLoggedIn ? 'dashboard.php' : 'register.php' ?>" class="btn btn-secondary btn-block"<?php if (!$isLoggedIn): ?> data-plan-modal="free" data-plan-name="<?= e((string) $plan['name']) ?>" data-monthly="0.00" data-yearly-total="0.00"<?php endif; ?>>
                                <?= $isLoggedIn ? 'View Active Plan in Dashboard' : 'Get Started Free' ?>
                            </a>
                        <?php else: ?>
                            <a
                                href="contact.php?subject=<?= urlencode((string) $plan['name'] . ' Plan Inquiry') ?>"
                                class="btn <?= $isFeatured ? 'btn-primary' : 'btn-secondary' ?> btn-block"
                                data-plan-modal="<?= e($slug) ?>"
                                data-plan-name="<?= e((string) $plan['name']) ?>"
                                data-monthly="<?= e(number_format($monthly, 2, '.', '')) ?>"
                                data-yearly-total="<?= e(number_format($yearlyTotal, 2, '.', '')) ?>"
                            >
                                <span>Get Started (Early Access Inquiry)</span>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Comparison Matrix -->
    <section class="section section--deep">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">PLAN COMPARISON</span>
                <h2>Compare Every Feature <span class="text-gradient">Side by Side</span></h2>
            </div>

            <div class="table-responsive">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Capability</th>
                            <th>Free</th>
                            <th>Pro</th>
                            <th>Ultra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Cloud Vault Storage</td>
                            <td>5 GB</td>
                            <td>100 GB</td>
                            <td>1 TB</td>
                        </tr>
                        <tr>
                            <td>Smart Camera &amp; Edge Detection</td>
                            <td><i class="fa-solid fa-check" style="color:var(--color-success);" aria-hidden="true"></i> Included</td>
                            <td><i class="fa-solid fa-check" style="color:var(--color-success);" aria-hidden="true"></i> Included</td>
                            <td><i class="fa-solid fa-check" style="color:var(--color-success);" aria-hidden="true"></i> Included</td>
                        </tr>
                        <tr>
                            <td>11 Enhancement Filters</td>
                            <td>Included</td>
                            <td>Included</td>
                            <td>Included</td>
                        </tr>
                        <tr>
                            <td>Gemini AI OCR &amp; Summaries</td>
                            <td>Limited Preview</td>
                            <td>Full Access</td>
                            <td>Unlimited Priority</td>
                        </tr>
                        <tr>
                            <td>Table-to-CSV &amp; Question Set AI</td>
                            <td>Basic Preview</td>
                            <td>Standard Quota</td>
                            <td>Unlimited</td>
                        </tr>
                        <tr>
                            <td>AES-128 PDF Encryption</td>
                            <td>Standard</td>
                            <td>Full Studio</td>
                            <td>Full Studio</td>
                        </tr>
                        <tr>
                            <td>Support Channel</td>
                            <td>Community / Email</td>
                            <td>Priority Email</td>
                            <td>Dedicated Priority</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Pricing FAQs -->
    <section class="section" id="faq">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">PRICING FAQS</span>
                <h2>Billing &amp; Plan <span class="text-gradient">Questions</span></h2>
            </div>

            <div class="faq-list">
                <div class="faq-item open">
                    <button type="button" class="faq-question" aria-expanded="true">
                        <span>Can I start using ScanPro AI without entering payment details?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        Yes. Creating an account assigns you the Free plan automatically with no credit card required.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>When will paid online upgrades for Pro and Ultra launch?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        Subscription tables and plan structures are integrated with MySQL so a real payment gateway can be connected in a future phase without rebuilding your account.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>How much do I save with yearly billing?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        Yearly billing reflects a 20% discount compared to 12 individual monthly payments. Use the Monthly / Yearly toggle above to preview both rates.
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>