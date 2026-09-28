<?php
require_once '../config/database.php';
checkAccess('guide');

$page_title = 'My Reports';
$user_id = $_SESSION['user_id'];

// Get guide profile
$stmt = $pdo->prepare("SELECT * FROM guide_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// If no profile exists, create one
if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO guide_profiles (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $profile = [
        'experience_years' => 0,
        'languages' => '',
        'specialization' => '',
        'hourly_rate' => 0,
        'rating' => 0,
        'total_reviews' => 0,
        'availability' => 'available'
    ];
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_tours = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(adults + children) as total FROM bookings WHERE guide_id = ? AND status = 'confirmed'");
$stmt->execute([$user_id]);
$current_guests = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(adults + children) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_guests_served = $stmt->fetch()['total'] ?? 0;

// Get completed tours
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.guide_id = ? AND b.status = 'completed'
                       ORDER BY b.end_date DESC");
$stmt->execute([$user_id]);
$completed = $stmt->fetchAll();

// Get confirmed tours (upcoming)
$stmt = $pdo->prepare("SELECT b.*, u.name as customer_name,
                              p.name as package_name, p.destination
                       FROM bookings b 
                       JOIN users u ON b.customer_id = u.id 
                       JOIN packages p ON b.package_id = p.id 
                       WHERE b.guide_id = ? AND b.status = 'confirmed' AND b.start_date >= CURDATE()
                       ORDER BY b.start_date ASC");
$stmt->execute([$user_id]);
$upcoming = $stmt->fetchAll();

// ---------- FIXED: Monthly completed tours (last 6 months) ----------
$monthly_tours = $pdo->prepare("
    SELECT DATE_FORMAT(end_date, '%Y-%m') as month, COUNT(*) as tours
    FROM bookings
    WHERE guide_id = ? AND status = 'completed' AND end_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$monthly_tours->execute([$user_id]);
$monthly_tours_data = $monthly_tours->fetchAll();

// ---------- FIXED: Monthly average rating from feedback table ----------
$monthly_ratings = $pdo->prepare("
    SELECT DATE_FORMAT(b.end_date, '%Y-%m') as month, AVG(f.rating) as avg_rating
    FROM bookings b
    JOIN feedback f ON f.booking_id = b.id
    WHERE b.guide_id = ? AND b.status = 'completed' AND f.rating IS NOT NULL
      AND b.end_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");
$monthly_ratings->execute([$user_id]);
$monthly_ratings_data = $monthly_ratings->fetchAll();

// Current confirmed guests (for pie chart)
$stmt = $pdo->prepare("SELECT SUM(adults + children) as total FROM bookings WHERE guide_id = ? AND status = 'confirmed' AND start_date >= CURDATE()");
$stmt->execute([$user_id]);
$current_guests_total = $stmt->fetch()['total'] ?? 0;

// Total guests served (completed)
$stmt = $pdo->prepare("SELECT SUM(adults + children) as total FROM bookings WHERE guide_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$served_guests_total = $stmt->fetch()['total'] ?? 0;

// Optional: total revenue from completed bookings (if payments table exists)
$total_revenue = 0;
// Uncomment if payments table is available and has guide_id
// $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE guide_id = ? AND status = 'completed'");
// $stmt->execute([$user_id]);
// $total_revenue = $stmt->fetch()['total'] ?? 0;

include '../includes/header.php';
?>

<!-- Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Custom CSS -->
<style>
    .chart-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 20px; }
    .filter-bar input, .filter-bar select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .filter-bar input:focus, .filter-bar select:focus { border-color: var(--primary); outline: none; }
    .pagination-controls { display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 15px; }
    .pagination-controls button { padding: 6px 14px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; }
    .pagination-controls button:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .pagination-controls span { font-size: 14px; color: var(--gray); }
    .badge-danger { background: #fee2e2; color: #dc2626; }
    .badge-info { background: #d1ecf1; color: #1a5276; }
</style>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-flag-checkered"></i></div>
        <div class="stat-info">
            <h3><?php echo $completed_tours; ?></h3>
            <p>Completed Tours</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_guests_served; ?></h3>
            <p>Total Guests Served</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon secondary"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($profile['rating'] ?? 0, 1); ?> / 5</h3>
            <p>Rating (<?php echo $profile['total_reviews'] ?? 0; ?> reviews)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo count($upcoming); ?></h3>
            <p>Upcoming Tours</p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px;">
    <!-- Chart 1: Monthly Tours -->
    <div class="chart-container">
        <h4 style="margin-top:0; font-size:16px; color:var(--dark);"><i class="fas fa-calendar-alt"></i> Monthly Tours (Last 6 Months)</h4>
        <canvas id="monthlyToursChart" height="150"></canvas>
    </div>
    <!-- Chart 2: Guests Distribution -->
    <div class="chart-container">
        <h4 style="margin-top:0; font-size:16px; color:var(--dark);"><i class="fas fa-user-friends"></i> Guests Overview</h4>
        <canvas id="guestsChart" height="150"></canvas>
    </div>
    <!-- Chart 3: Average Rating Trend -->
    <div class="chart-container">
        <h4 style="margin-top:0; font-size:16px; color:var(--dark);"><i class="fas fa-chart-line"></i> Rating Trend (Last 6 Months)</h4>
        <canvas id="ratingChart" height="150"></canvas>
    </div>
</div>

<!-- Upcoming Tours -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar"></i> Upcoming Tours</h3>
    </div>
    <div class="card-body">
        <?php if (empty($upcoming)): ?>
            <p style="text-align:center;padding:20px;color:var(--gray);">No upcoming tours.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Booking No</th><th>Customer</th><th>Package</th><th>Destination</th><th>Start Date</th><th>Guests</th><th>Days Left</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $t): 
                            $today = new DateTime();
                            $start = new DateTime($t['start_date']);
                            $days_left = $today->diff($start)->days;
                            $days_left = ($start < $today) ? 0 : $days_left;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['package_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['destination']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($t['start_date'])); ?></td>
                            <td><?php echo $t['adults'] + $t['children']; ?></td>
                            <td><span class="badge <?php echo $days_left <= 3 ? 'badge-danger' : 'badge-info'; ?>"><?php echo $days_left; ?> days</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Completed Tours Report with Search, Pagination, Export -->
<div class="card">
    <div class="card-header" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px;">
        <h3><i class="fas fa-chart-bar"></i> Tour History Report</h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-success btn-sm" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
            <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($completed)): ?>
            <p style="text-align:center;padding:40px;color:var(--gray);">
                <i class="fas fa-flag-checkered" style="font-size:48px;display:block;margin-bottom:15px;"></i>
                No completed tours yet.
            </p>
        <?php else: ?>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍 Search by customer or booking..." onkeyup="filterTable()">
                <span style="font-size:14px;color:var(--gray);">Total: <?php echo count($completed); ?> records</span>
            </div>

            <div class="table-responsive">
                <table class="data-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>Booking No</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Destination</th>
                            <th>Tour Date</th>
                            <th>Guests</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($completed as $t): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($t['booking_no']); ?></td>
                            <td><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['package_name']); ?></td>
                            <td><?php echo htmlspecialchars($t['destination']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($t['start_date'])); ?></td>
                            <td><?php echo $t['adults'] + $t['children']; ?></td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--light);font-weight:bold;">
                            <td colspan="5" style="text-align:right;">Total Tours:</td>
                            <td><?php echo count($completed); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="pagination-controls">
                <button onclick="changePage(-1)"><i class="fas fa-chevron-left"></i></button>
                <span id="pageInfo">Page 1 / <span id="totalPages">1</span></span>
                <button onclick="changePage(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Performance Summary -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-simple"></i> Performance Summary</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
            <div style="background:var(--light);padding:20px;border-radius:10px;text-align:center;">
                <div style="font-size:14px;color:var(--gray);">Experience</div>
                <div style="font-size:24px;font-weight:700;color:var(--primary);"><?php echo $profile['experience_years'] ?? 0; ?> years</div>
            </div>
            <div style="background:var(--light);padding:20px;border-radius:10px;text-align:center;">
                <div style="font-size:14px;color:var(--gray);">Hourly Rate</div>
                <div style="font-size:24px;font-weight:700;color:var(--secondary);"><?php echo formatCurrency($profile['hourly_rate'] ?? 0); ?></div>
            </div>
            <div style="background:var(--light);padding:20px;border-radius:10px;text-align:center;">
                <div style="font-size:14px;color:var(--gray);">Languages</div>
                <div style="font-size:16px;font-weight:500;color:var(--dark);"><?php echo htmlspecialchars($profile['languages'] ?: 'Not specified'); ?></div>
            </div>
            <div style="background:var(--light);padding:20px;border-radius:10px;text-align:center;">
                <div style="font-size:14px;color:var(--gray);">Specialization</div>
                <div style="font-size:16px;font-weight:500;color:var(--dark);"><?php echo htmlspecialchars($profile['specialization'] ?: 'Not specified'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript: Charts, Search, Pagination, Export -->
<script>
// ---------- CHARTS ----------
document.addEventListener('DOMContentLoaded', function() {
    // Monthly tours (bar chart)
    const monthlyToursCtx = document.getElementById('monthlyToursChart').getContext('2d');
    const monthlyToursData = <?php echo json_encode($monthly_tours_data); ?>;
    const tourMonths = monthlyToursData.map(item => item.month);
    const tourCounts = monthlyToursData.map(item => item.tours);

    new Chart(monthlyToursCtx, {
        type: 'bar',
        data: {
            labels: tourMonths.length ? tourMonths : ['No Data'],
            datasets: [{
                label: 'Completed Tours',
                data: tourMonths.length ? tourCounts : [0],
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Guests distribution (pie chart)
    const guestsCtx = document.getElementById('guestsChart').getContext('2d');
    const currentGuests = <?php echo $current_guests_total; ?>;
    const servedGuests = <?php echo $served_guests_total; ?>;
    new Chart(guestsCtx, {
        type: 'pie',
        data: {
            labels: ['Confirmed (Upcoming)', 'Served (Completed)'],
            datasets: [{
                data: [currentGuests || 1, servedGuests || 1],
                backgroundColor: ['#3498db', '#2ecc71'],
                borderColor: ['#2980b9', '#27ae60'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Rating trend (line chart) - FIXED: data from feedback table
    const ratingCtx = document.getElementById('ratingChart').getContext('2d');
    const monthlyRatings = <?php echo json_encode($monthly_ratings_data); ?>;
    const ratingMonths = monthlyRatings.map(item => item.month);
    const avgRatings = monthlyRatings.map(item => parseFloat(item.avg_rating) || 0);
    new Chart(ratingCtx, {
        type: 'line',
        data: {
            labels: ratingMonths.length ? ratingMonths : ['No Data'],
            datasets: [{
                label: 'Avg Rating',
                data: ratingMonths.length ? avgRatings : [0],
                backgroundColor: 'rgba(241, 196, 15, 0.2)',
                borderColor: '#f1c40f',
                borderWidth: 2,
                pointBackgroundColor: '#f39c12',
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { min: 0, max: 5, ticks: { stepSize: 0.5 } } },
            plugins: { legend: { display: false } }
        }
    });
});

// ---------- SEARCH / FILTER ----------
function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    let visibleCount = 0;
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    currentPage = 1;
    updatePagination();
}

// ---------- PAGINATION ----------
let currentPage = 1;
const rowsPerPage = 10;

function updatePagination() {
    const rows = document.querySelectorAll('#tableBody tr');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage) || 1;
    document.getElementById('totalPages').textContent = totalPages;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    visibleRows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('pageInfo').innerHTML = `Page ${currentPage} / <span id="totalPages">${totalPages}</span>`;
}

function changePage(delta) {
    const rows = document.querySelectorAll('#tableBody tr');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage) || 1;
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        updatePagination();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(row => row.style.display = '');
    currentPage = 1;
    updatePagination();
});

// ---------- EXPORT CSV ----------
function exportCSV() {
    const rows = document.querySelectorAll('#tableBody tr');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    if (visibleRows.length === 0) {
        alert('No data to export.');
        return;
    }

    const headers = [];
    document.querySelectorAll('#historyTable thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });

    let csv = headers.join(',') + '\n';
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = Array.from(cells).map(td => {
            let text = td.textContent.trim();
            if (td.querySelector('.badge')) {
                text = td.querySelector('.badge').textContent.trim();
            }
            return `"${text.replace(/"/g, '""')}"`;
        });
        csv += rowData.join(',') + '\n';
    });

    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `tour_history_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include '../includes/footer.php'; ?>