<?php
require_once '../config/database.php';
checkAccess('admin');

$page_title = 'Hotel Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'hotel'");
        $stmt->execute([$_POST['id']]);
        toast("Hotel deleted!", 'warning');
        redirect('hotels.php');
    } elseif (isset($_POST['save'])) {
        if ($_POST['id']) {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=? AND role='hotel'");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['status'], $_POST['id']]);
            $stmt = $pdo->prepare("UPDATE hotel_profiles SET hotel_name=?, location=?, stars=?, room_count=?, price_per_night=?, amenities=?, check_in_time=?, check_out_time=? WHERE user_id=?");
            $stmt->execute([$_POST['name'], $_POST['location'], $_POST['stars'], $_POST['room_count'], $_POST['price_per_night'], $_POST['amenities'], $_POST['check_in_time'], $_POST['check_out_time'], $_POST['id']]);
            toast("Hotel updated!");
        } else {
            $password = password_hash('Hotel@123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, 'hotel', ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $password, $_POST['phone'], $_POST['status']]);
            $id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO hotel_profiles (user_id, hotel_name, location, stars, room_count, price_per_night, amenities, check_in_time, check_out_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $_POST['name'], $_POST['location'], $_POST['stars'], $_POST['room_count'], $_POST['price_per_night'], $_POST['amenities'], $_POST['check_in_time'], $_POST['check_out_time']]);
            toast("Hotel added! Default password: Hotel@123");
        }
        redirect('hotels.php');
    }
}

$hotels = $pdo->query("SELECT u.*, hp.location, hp.stars, hp.room_count, hp.price_per_night, hp.amenities 
                       FROM users u 
                       LEFT JOIN hotel_profiles hp ON u.id = hp.user_id 
                       WHERE u.role = 'hotel' 
                       ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-hotel"></i> Hotels</h3>
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Hotel</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>ID</th><th>Hotel Name</th><th>Email</th><th>Location</th><th>Stars</th><th>Rooms</th><th>Price/Night</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($hotels as $h): ?>
                    <tr>
                        <td><?php echo $h['id']; ?></td>
                        <td><?php echo htmlspecialchars($h['name']); ?></td>
                        <td><?php echo htmlspecialchars($h['email']); ?></td>
                        <td><?php echo htmlspecialchars($h['location']); ?></td>
                        <td><?php echo str_repeat('★', $h['stars']); ?></td>
                        <td><?php echo $h['room_count']; ?></td>
                        <td><?php echo formatCurrency($h['price_per_night']); ?></td>
                        <td><span class="badge badge-<?php echo getStatusBadge($h['status']); ?>"><?php echo ucfirst($h['status']); ?></span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick='editHotel(<?php echo json_encode($h); ?>)'><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this hotel?')">
                                <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                <button type="submit" name="delete" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="hotelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Hotel</h3>
            <button class="close-btn" onclick="closeModal('hotelModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="hotelId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group"><label>Hotel Name *</label><input type="text" name="name" id="hName" class="form-control" required></div>
                    <div class="form-group"><label>Email *</label><input type="email" name="email" id="hEmail" class="form-control" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" id="hPhone" class="form-control"></div>
                    <div class="form-group"><label>Location</label><input type="text" name="location" id="hLoc" class="form-control"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Star Rating</label>
                        <select name="stars" id="hStars" class="form-control">
                            <?php for($i=1;$i<=5;$i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> Star<?php echo $i>1?'s':''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Room Count</label><input type="number" name="room_count" id="hRooms" class="form-control" min="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Price Per Night (LKR)</label><input type="number" step="0.01" name="price_per_night" id="hPrice" class="form-control"></div>
                    <div class="form-group"><label>Check-in Time</label><input type="text" name="check_in_time" id="hCheckIn" class="form-control" placeholder="2:00 PM"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Check-out Time</label><input type="text" name="check_out_time" id="hCheckOut" class="form-control" placeholder="12:00 PM"></div>
                </div>
                <div class="form-group"><label>Amenities</label><textarea name="amenities" id="hAmenities" class="form-control" rows="2" placeholder="WiFi, Parking, Pool, Restaurant"></textarea></div>
                <div class="form-group"><label>Status</label>
                    <select name="status" id="hStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('hotelModal')">Cancel</button>
                <button type="submit" name="save" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Hotel';
    document.getElementById('hotelId').value = '';
    document.getElementById('hName').value = '';
    document.getElementById('hEmail').value = '';
    document.getElementById('hPhone').value = '';
    document.getElementById('hLoc').value = '';
    document.getElementById('hStars').value = '3';
    document.getElementById('hRooms').value = '';
    document.getElementById('hPrice').value = '';
    document.getElementById('hCheckIn').value = '';
    document.getElementById('hCheckOut').value = '';
    document.getElementById('hAmenities').value = '';
    document.getElementById('hStatus').value = 'active';
    openModal('hotelModal');
}

function editHotel(h) {
    document.getElementById('modalTitle').innerText = 'Edit Hotel';
    document.getElementById('hotelId').value = h.id;
    document.getElementById('hName').value = h.name;
    document.getElementById('hEmail').value = h.email;
    document.getElementById('hPhone').value = h.phone || '';
    document.getElementById('hLoc').value = h.location || '';
    document.getElementById('hStars').value = h.stars;
    document.getElementById('hRooms').value = h.room_count;
    document.getElementById('hPrice').value = h.price_per_night;
    document.getElementById('hCheckIn').value = h.check_in_time || '';
    document.getElementById('hCheckOut').value = h.check_out_time || '';
    document.getElementById('hAmenities').value = h.amenities || '';
    document.getElementById('hStatus').value = h.status;
    openModal('hotelModal');
}
</script>

<?php include '../includes/footer.php'; ?>