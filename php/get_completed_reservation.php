<?php
include "connect.php";

$id = intval($_GET['id']);

$query = "
SELECT fr.id, c.name AS client_name, f.name AS facility_name, 
       fr.reservation_date, fr.status, fr.details,
       cr.completed_at
FROM facilreserve_db fr
JOIN clients c ON fr.client_id = c.id
JOIN facilities f ON fr.facility_id = f.id
JOIN completed_reservations cr ON fr.id = cr.original_id
WHERE fr.id = ?
";

$stmt = $connect->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if ($reservation) {
    echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    echo '<div><strong>Reservation ID:</strong> ' . $reservation['id'] . '</div>';
    echo '<div><strong>Client:</strong> ' . htmlspecialchars($reservation['client_name']) . '</div>';
    echo '<div><strong>Facility:</strong> ' . htmlspecialchars($reservation['facility_name']) . '</div>';
    echo '<div><strong>Reservation Date:</strong> ' . $reservation['reservation_date'] . '</div>';
    echo '<div><strong>Status:</strong> ' . $reservation['status'] . '</div>';
    echo '<div><strong>Completed On:</strong> ' . $reservation['completed_at'] . '</div>';
    
    if (!empty($reservation['details'])) {
        echo '<div class="col-span-2"><strong>Details:</strong><div class="mt-2 p-3 bg-gray-50 rounded">' 
             . nl2br(htmlspecialchars($reservation['details'])) . '</div></div>';
    }
    
    echo '</div>';
} else {
    echo '<p class="text-red-500">Reservation details not found.</p>';
}
?>