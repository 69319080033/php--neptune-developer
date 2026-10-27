<?php
/**
 * Dashboard: ระบบแจ้งข่าวสารและกิจกรรมภายในแผนก
 *
 * การเชื่อมต่อฐานข้อมูล:
 * - ถ้า config.php สร้างตัวแปร mysqli ชื่อ $conn หรือ $mysqli ระบบจะลองดึงข้อมูลจาก MySQL
 * - ชื่อตาราง/ฟิลด์ที่คาดหวังอยู่ในหมายเหตุท้ายไฟล์
 * - หากยังไม่มีฐานข้อมูลหรือ query ไม่สำเร็จ จะแสดงข้อมูลตัวอย่างแทน
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$db = null;
if (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} elseif (isset($mysqli) && $mysqli instanceof mysqli) {
    $db = $mysqli;
}
if ($db instanceof mysqli) {
    mysqli_set_charset($db, 'utf8mb4');
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function queryRows(?mysqli $db, string $sql): array {
    if (!$db) return [];
    try {
        $result = $db->query($sql);
        if (!$result) return [];
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows;
    } catch (Throwable $ex) {
        return [];
    }
}

function queryCount(?mysqli $db, string $sql): ?int {
    $rows = queryRows($db, $sql);
    return isset($rows[0]['total']) ? (int)$rows[0]['total'] : null;
}

// ข้อมูลตัวอย่าง ใช้แสดงหน้าเว็บได้ก่อนสร้าง/เชื่อมฐานข้อมูล
$demoNews = [
    ['id'=>1, 'title'=>'แจ้งกำหนดการประชุมประจำแผนก', 'content'=>'ขอเชิญบุคลากรและนักเรียนเข้าร่วมประชุมตามกำหนดการของแผนก', 'category'=>'ประชาสัมพันธ์', 'created_at'=>date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['id'=>2, 'title'=>'กำหนดส่งผลงานโครงงานประจำภาคเรียน', 'content'=>'ตรวจสอบกำหนดส่งผลงานและเตรียมเอกสารให้ครบถ้วนก่อนวันส่ง', 'category'=>'การเรียน', 'created_at'=>date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['id'=>3, 'title'=>'แจ้งการดูแลห้องปฏิบัติการคอมพิวเตอร์', 'content'=>'กรุณาปิดเครื่องคอมพิวเตอร์และจัดเก็บอุปกรณ์หลังใช้งานทุกครั้ง', 'category'=>'แจ้งเตือน', 'created_at'=>date('Y-m-d H:i:s', strtotime('-3 days'))],
];
$demoAnnouncements = [
    ['id'=>1, 'title'=>'โปรดตรวจสอบกำหนดการสอบปลายภาค', 'priority'=>'สูง', 'created_at'=>date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['id'=>2, 'title'=>'แจ้งเปลี่ยนห้องเรียนชั่วคราว', 'priority'=>'ปกติ', 'created_at'=>date('Y-m-d H:i:s', strtotime('-2 days'))],
];
$demoEvents = [
    ['id'=>1, 'title'=>'ประชุมประจำแผนก', 'event_date'=>date('Y-m-d H:i:s', strtotime('+3 days')), 'location'=>'ห้องประชุม 1', 'status'=>'กำลังจะมาถึง'],
    ['id'=>2, 'title'=>'อบรมความปลอดภัยไซเบอร์', 'event_date'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'location'=>'ห้องปฏิบัติการคอมพิวเตอร์', 'status'=>'เปิดรับสมัคร'],
    ['id'=>3, 'title'=>'กิจกรรมแข่งขันทักษะวิชาชีพ', 'event_date'=>date('Y-m-d H:i:s', strtotime('+14 days')), 'location'=>'หอประชุม', 'status'=>'กำลังจะมาถึง'],
];

// ลองดึงข้อมูลจริงจากฐานข้อมูล หากตารางมีอยู่และชื่อฟิลด์ตรงตามที่กำหนด
$dbNews = queryRows($db, "SELECT id, title, content, category, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
$dbAnnouncements = queryRows($db, "SELECT id, title, priority, created_at FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
$dbEvents = queryRows($db, "SELECT id, title, event_date, location, status FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 5");

$news = $dbNews ?: $demoNews;
$announcements = $dbAnnouncements ?: $demoAnnouncements;
$events = $dbEvents ?: $demoEvents;

$totalNews = queryCount($db, "SELECT COUNT(*) AS total FROM news WHERE status = 'published'");
$totalAnnouncements = queryCount($db, "SELECT COUNT(*) AS total FROM announcements WHERE status = 'published'");
$totalEvents = queryCount($db, "SELECT COUNT(*) AS total FROM events");
$upcomingEvents = queryCount($db, "SELECT COUNT(*) AS total FROM events WHERE event_date >= NOW()");

// ถ้าฐานข้อมูลยังไม่พร้อม ให้การ์ดสรุปสัมพันธ์กับข้อมูลตัวอย่าง
$totalNews = $totalNews ?? count($demoNews);
$totalAnnouncements = $totalAnnouncements ?? count($demoAnnouncements);
$totalEvents = $totalEvents ?? count($demoEvents);
$upcomingEvents = $upcomingEvents ?? count($demoEvents);

$stats = [
    ['label'=>'ข่าวสารที่เผยแพร่', 'value'=>$totalNews, 'unit'=>'ข่าว', 'icon'=>'bi-newspaper', 'tone'=>'blue'],
    ['label'=>'ประกาศภายในแผนก', 'value'=>$totalAnnouncements, 'unit'=>'ประกาศ', 'icon'=>'bi-megaphone', 'tone'=>'sky'],
    ['label'=>'กิจกรรมทั้งหมด', 'value'=>$totalEvents, 'unit'=>'กิจกรรม', 'icon'=>'bi-calendar-event', 'tone'=>'mint'],
    ['label'=>'กิจกรรมที่กำลังจะมาถึง', 'value'=>$upcomingEvents, 'unit'=>'กิจกรรม', 'icon'=>'bi-alarm', 'tone'=>'coral'],
];

$menu = [
    ['icon'=>'bi-grid-1x2', 'label'=>'แดชบอร์ด', 'href'=>'index.php', 'active'=>true],
    ['icon'=>'bi-newspaper', 'label'=>'ข่าวสาร', 'href'=>'news.php'],
    ['icon'=>'bi-megaphone', 'label'=>'ประกาศ', 'href'=>'announcements.php'],
    ['icon'=>'bi-calendar-event', 'label'=>'กิจกรรม', 'href'=>'events.php'],
    ['icon'=>'bi-calendar3', 'label'=>'ปฏิทินกิจกรรม', 'href'=>'calendar.php'],
    ['icon'=>'bi-gear', 'label'=>'จัดการระบบ', 'href'=>'admin.php'],
];
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด | ระบบข่าวสารและกิจกรรมภายในแผนก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0f19;
            --surface: #131b2e;
            --surface-hover: #19233c;
            --sidebar: #090d16;
            --sidebar-text: #94a3b8;
            --sidebar-muted: #64748b;
            --border: #1e293b;
            --border-light: #2d3b55;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --primary: #38bdf8;
            --primary-soft: rgba(56, 189, 248, 0.14);
            --sky: #60a5fa;
            --sky-soft: rgba(96, 165, 250, 0.14);
            --coral: #f87171;
            --coral-soft: rgba(248, 113, 113, 0.14);
            --mint: #34d399;
            --mint-soft: rgba(52, 211, 153, 0.14);
            --sidebar-w: 250px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
        }

        .text-secondary {
            color: var(--muted) !important;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar) !important;
            border-right: 1px solid var(--border) !important;
        }

        @media (min-width: 992px) {
            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
            }

            .main {
                margin-left: var(--sidebar-w);
            }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 1.25rem;
            color: #fff;
            text-decoration: none;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.25);
        }

        .brand-name {
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.4;
            color: #f1f5f9;
        }

        .menu-title {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--sidebar-muted);
            padding: 1rem 1.5rem .5rem;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: .15rem .75rem;
            padding: .7rem .9rem;
            border-radius: 10px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: .94rem;
            transition: background .15s, color .15s;
        }

        .side-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .side-link.active {
            background: #0284c7;
            color: #fff;
            font-weight: 500;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
        }

        /* ---------- Topbar ---------- */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .search {
            max-width: 360px;
        }

        .search .form-control,
        .search .input-group-text {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text);
        }

        .search .form-control:focus {
            background: #17223b;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(56, 189, 248, 0.2);
            color: #fff;
        }

        .search .form-control::placeholder {
            color: #64748b;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--muted);
            transition: all .15s ease;
        }

        .icon-btn:hover {
            background: var(--border);
            color: #fff;
            border-color: var(--border-light);
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 600;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .dropdown-menu {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .dropdown-item {
            color: #cbd5e1;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
        }

        .dropdown-divider {
            border-color: var(--border);
        }

        /* ---------- Panel / Cards ---------- */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        .stat-card {
            padding: 1.2rem;
            height: 100%;
            transition: transform .15s, box-shadow .15s, border-color .15s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            border-color: var(--border-light);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.35rem;
        }

        .stat-label {
            color: var(--muted);
            font-size: .88rem;
        }

        .stat-value {
            font-size: 1.9rem;
            font-weight: 700;
            line-height: 1.2;
            color: #f8fafc;
        }

        .stat-unit {
            font-size: .82rem;
            color: var(--muted);
        }

        .tone-blue {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .tone-sky {
            background: var(--sky-soft);
            color: var(--sky);
        }

        .tone-coral {
            background: var(--coral-soft);
            color: var(--coral);
        }

        .tone-mint {
            background: var(--mint-soft);
            color: var(--mint);
        }

        /* ---------- Sections & Content ---------- */
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: #f8fafc;
        }

        .news-item {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .news-item:last-child {
            border-bottom: 0;
        }

        .news-item h3 {
            font-size: .95rem;
            font-weight: 600;
            margin-bottom: .35rem;
            color: #f1f5f9;
        }

        .news-item p {
            font-size: .86rem;
            color: #94a3b8;
            margin-bottom: .55rem;
            line-height: 1.5;
        }

        .meta {
            font-size: .78rem;
            color: #64748b;
        }

        .category {
            display: inline-block;
            border-radius: 999px;
            padding: .25rem .65rem;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: .75rem;
            font-weight: 500;
        }

        .announcement-item {
            padding: .85rem 0;
            border-bottom: 1px solid var(--border);
        }

        .announcement-item:last-child {
            border-bottom: 0;
        }

        .priority {
            font-size: .73rem;
            border-radius: 999px;
            padding: .25rem .55rem;
            font-weight: 500;
        }

        .priority-high {
            background: var(--coral-soft);
            color: var(--coral);
        }

        .priority-normal {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .event-date {
            width: 56px;
            min-width: 56px;
            border-radius: 12px;
            background: var(--primary-soft);
            color: var(--primary);
            text-align: center;
            padding: .45rem .2rem;
        }

        .event-date strong {
            font-size: 1.2rem;
            line-height: 1.1;
            display: block;
            color: var(--primary);
        }

        .event-date span {
            font-size: .72rem;
        }

        .event-row {
            display: flex;
            gap: .8rem;
            padding: .85rem 0;
            border-bottom: 1px solid var(--border);
        }

        .event-row:last-child {
            border-bottom: 0;
        }

        .empty {
            padding: 1.5rem;
            text-align: center;
            color: var(--muted);
            font-size: .9rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
        <div class="d-flex align-items-start justify-content-between">
            <a class="brand" href="index.php" id="sidebarLabel">
                <span class="brand-logo"><i class="bi bi-megaphone-fill"></i></span>
                <span class="brand-name">ระบบข่าวสารและกิจกรรม<br>ภายในแผนก</span>
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none m-3" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="ปิดเมนู"></button>
        </div>
        <div class="menu-title">เมนูหลัก</div>
        <nav>
            <?php foreach ($menu as $item): ?>
                <a class="side-link<?= !empty($item['active']) ? ' active' : '' ?>" href="<?= e($item['href']) ?>"
                    <?= !empty($item['active']) ? ' aria-current="page"' : '' ?>>
                    <i class="bi <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="menu-title">บัญชีผู้ใช้</div>
        <a class="side-link" href="profile.php"><i class="bi bi-person-circle"></i><span>โปรไฟล์</span></a>
        <a class="side-link" href="logout.php"><i class="bi bi-box-arrow-left"></i><span>ออกจากระบบ</span></a>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3 px-3 px-lg-4 py-3">
                <button class="icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar"
                    aria-controls="sidebar" aria-label="เปิดเมนู">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <form class="input-group search d-none d-md-flex" action="news.php" method="get">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="ค้นหาข่าวสารหรือกิจกรรม..." aria-label="ค้นหา">
                </form>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <a class="icon-btn d-grid place-items-center text-center pt-2 text-decoration-none" href="announcements.php" aria-label="ประกาศ">
                        <i class="bi bi-bell"></i>
                    </a>
                    <div class="dropdown">
                        <button class="btn d-flex align-items-center gap-2 p-1 border-0" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar">A</span>
                            <span class="text-start d-none d-sm-block lh-sm">
                                <span class="d-block fw-medium text-light">ผู้ดูแลระบบ</span>
                                <small class="text-secondary">Admin</small>
                            </span>
                            <i class="bi bi-chevron-down small text-secondary"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>ตั้งค่า</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-3 p-lg-4">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
                <div>
                    <h1 class="h4 fw-semibold mb-1 text-light">แดชบอร์ด</h1>
                    <p class="text-secondary mb-0">ภาพรวมข่าวสารและกิจกรรมภายในแผนก ณ วันที่ <?= date('d/m/Y') ?></p>
                </div>
                <a href="news-create.php" class="btn px-3"
                    style="background: #0284c7; border-color: #0284c7; color: #fff; font-weight: 500;">
                    <i class="bi bi-plus-lg me-1"></i> เพิ่มข่าวสาร
                </a>
            </div>

            <div class="row g-3 mb-4">
                <?php foreach ($stats as $s): ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="panel stat-card d-flex align-items-center gap-3">
                            <div class="stat-icon tone-<?= e($s['tone']) ?>">
                                <i class="bi <?= e($s['icon']) ?>"></i>
                            </div>
                            <div>
                                <div class="stat-label"><?= e($s['label']) ?></div>
                                <div class="stat-value"><?= e($s['value']) ?></div>
                                <div class="stat-unit"><?= e($s['unit']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-7">
                    <section class="panel h-100">
                        <div class="d-flex align-items-center justify-content-between p-3 px-4">
                            <h2 class="section-title"><i class="bi bi-newspaper me-2" style="color:var(--primary)"></i>ข่าวสารล่าสุด</h2>
                            <a href="news.php" class="small text-decoration-none" style="color:var(--primary)">ดูข่าวทั้งหมด <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <?php if ($news): foreach ($news as $item): ?>
                            <article class="news-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <h3><?= e($item['title'] ?? 'ไม่มีหัวข้อข่าว') ?></h3>
                                    <span class="category"><?= e($item['category'] ?? 'ข่าวสาร') ?></span>
                                </div>
                                <p><?= e(mb_strimwidth(strip_tags((string) ($item['content'] ?? '')), 0, 145, '…', 'UTF-8')) ?></p>
                                <div class="meta"><i class="bi bi-clock me-1"></i><?= !empty($item['created_at']) ? e(date('d/m/Y H:i', strtotime($item['created_at']))) : '-' ?></div>
                            </article>
                        <?php endforeach; else: ?>
                            <div class="empty">ยังไม่มีข่าวสาร</div>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="col-12 col-xl-5">
                    <section class="panel mb-3">
                        <div class="d-flex align-items-center justify-content-between p-3 px-4">
                            <h2 class="section-title"><i class="bi bi-megaphone me-2" style="color:var(--coral)"></i>ประกาศสำคัญ</h2>
                            <a href="announcements.php" class="small text-decoration-none" style="color:var(--primary)">ดูทั้งหมด</a>
                        </div>
                        <div class="px-4 pb-3">
                            <?php if ($announcements): foreach ($announcements as $item): $isHigh = in_array(($item['priority'] ?? ''), ['สูง', 'high', 'urgent'], true); ?>
                                <div class="announcement-item">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div class="fw-medium small"><?= e($item['title'] ?? 'ไม่มีหัวข้อประกาศ') ?></div>
                                        <span class="priority <?= $isHigh ? 'priority-high' : 'priority-normal' ?>"><?= e($item['priority'] ?? 'ปกติ') ?></span>
                                    </div>
                                    <div class="meta mt-2"><i class="bi bi-calendar3 me-1"></i><?= !empty($item['created_at']) ? e(date('d/m/Y', strtotime($item['created_at']))) : '-' ?></div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="empty">ยังไม่มีประกาศ</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="d-flex align-items-center justify-content-between p-3 px-4">
                            <h2 class="section-title"><i class="bi bi-calendar-event me-2" style="color:var(--mint)"></i>กิจกรรมที่กำลังจะมาถึง</h2>
                            <a href="events.php" class="small text-decoration-none" style="color:var(--primary)">ดูกิจกรรม</a>
                        </div>
                        <div class="px-4 pb-3">
                            <?php if ($events): foreach ($events as $item): $eventTimestamp = !empty($item['event_date']) ? strtotime($item['event_date']) : false; ?>
                                <div class="event-row">
                                    <div class="event-date">
                                        <?php if ($eventTimestamp): ?>
                                            <strong><?= date('d', $eventTimestamp) ?></strong>
                                            <span><?= e(date('M', $eventTimestamp)) ?></span>
                                        <?php else: ?>
                                            <strong>--</strong>
                                            <span>ไม่ระบุ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small mb-1 text-light"><?= e($item['title'] ?? 'ไม่ระบุชื่อกิจกรรม') ?></div>
                                        <div class="meta mb-1"><i class="bi bi-clock me-1"></i><?= $eventTimestamp ? e(date('H:i', $eventTimestamp) . ' น.') : 'ไม่ระบุเวลา' ?></div>
                                        <div class="meta"><i class="bi bi-geo-alt me-1"></i><?= e($item['location'] ?? 'ไม่ระบุสถานที่') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="empty">ยังไม่มีกิจกรรมที่กำลังจะมาถึง</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
            <div class="text-center text-secondary small mt-4">ระบบแจ้งข่าวสารและกิจกรรมภายในแผนก</div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
