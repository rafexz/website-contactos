<?php
require_once __DIR__ . '/../database/db.php';

$sql = "
    SELECT
        c.id,
        c.name,
        c.email,
        c.country_code,
        c.phone,
        c.image_path,
        s.name AS social_name,
        cs.value AS social_value
    FROM contacts c
    LEFT JOIN contact_socials cs
        ON cs.contact_id = c.id
    LEFT JOIN socials s
        ON s.id = cs.social_id
    ORDER BY c.name, s.name
";

$stmt = $pdo->query($sql);
$contactsById = [];

while ($row = $stmt->fetch()) {
    $contactId = $row['id'];

    if (!isset($contactsById[$contactId])) {
        $contactsById[$contactId] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'country_code' => $row['country_code'],
            'phone' => $row['phone'],
            'image_path' => $row['image_path'],
            'socials' => [],
        ];
    }

    if (!empty($row['social_name']) && !empty($row['social_value'])) {
        $contactsById[$contactId]['socials'][] = [
            'name' => $row['social_name'],
            'value' => $row['social_value'],
        ];
    }
}

$contacts = array_values($contactsById);
?>