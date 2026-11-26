<?php
/**
 * Data-access functions shared by several dashboards. Keeping SQL here
 * (instead of copy-pasted into each page) means one fix applies everywhere.
 */

declare(strict_types=1);

/** Pending help requests, optionally only those posted by a given role. */
function pending_requests(?string $postedByRole = null): array
{
    $sql = "SELECT r.id, r.title, r.description, r.location, r.contact_person_name, r.contact_person_phone,
                   r.request_type, r.quantity, r.unit, r.priority, r.created_at, u.role, u.name AS posted_by
            FROM requests r
            JOIN users u ON u.id = r.user_id
            WHERE r.status = 'pending' AND u.status = 'active'";
    $params = [];
    if ($postedByRole !== null) {
        $sql .= ' AND u.role = ?';
        $params[] = $postedByRole;
    }
    $sql .= " ORDER BY FIELD(r.priority, 'High', 'Medium', 'Low'), r.created_at DESC";
    return db_all($sql, $params);
}

/** Requests created by one user. */
function requests_by_user(int $userId): array
{
    return db_all('SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC', [$userId]);
}

/** Campaigns with the organising NGO's name. $status = null for all. */
function campaigns(?string $status = 'active', ?int $ngoId = null): array
{
    $sql = 'SELECT c.*, COALESCE(n.organisation_name, u.name) AS ngo_name
            FROM campaigns c
            JOIN users u ON u.id = c.ngo_id
            LEFT JOIN ngo_details n ON n.id = c.ngo_id
            WHERE 1 = 1';
    $params = [];
    if ($status !== null) {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    if ($ngoId !== null) {
        $sql .= ' AND c.ngo_id = ?';
        $params[] = $ngoId;
    }
    return db_all($sql . ' ORDER BY c.created_at DESC', $params);
}

/** Insert a help request on behalf of $user (role 'need' or 'ngo'). Returns error list. */
function create_request_from_post(array $user): array
{
    $data = [
        'title'                => post_str('title'),
        'description'          => post_str('description', 2000),
        'request_type'         => post_enum('request_type', REQUEST_TYPES),
        'quantity'             => post_int('quantity'),
        'unit'                 => post_str('unit', 50) ?: 'items',
        'location'             => post_str('location'),
        'priority'             => post_enum('priority', PRIORITIES, 'Medium'),
        'contact_person_name'  => post_str('contact_person_name', 100),
        'contact_person_phone' => post_str('contact_person_phone', 20),
    ];
    $errors = [];
    if ($data['title'] === '' || $data['description'] === '') { $errors[] = 'Title and description are required.'; }
    if ($data['request_type'] === null)                         { $errors[] = 'Please choose a valid request type.'; }
    if ($data['quantity'] === null || $data['quantity'] < 1 || $data['quantity'] > 1000000) {
        $errors[] = 'Quantity must be a whole number between 1 and 1,000,000.';
    }
    if ($data['location'] === '')                               { $errors[] = 'Location is required.'; }
    if ($data['contact_person_name'] === '')                    { $errors[] = 'Contact person name is required.'; }
    if (!valid_phone($data['contact_person_phone'], true))      { $errors[] = 'Please enter a valid contact phone number.'; }

    if (!$errors) {
        // user_id, user_name, user_role and status are set server-side, never taken from the form.
        db_exec(
            "INSERT INTO requests (user_id, user_name, user_role, title, description, request_type, quantity, unit,
                                   location, priority, contact_person_name, contact_person_phone, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [(int) $user['id'], $user['name'], $user['role'], $data['title'], $data['description'], $data['request_type'],
             $data['quantity'], $data['unit'], $data['location'], $data['priority'], $data['contact_person_name'],
             $data['contact_person_phone']]
        );
    }
    return $errors;
}

/** Update users.name/email/phone for the logged-in user. Throws on DB error. */
function update_user_core(int $userId, string $name, string $email, string $phone): void
{
    db_exec('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?',
        [$name, mb_strtolower($email), $phone === '' ? null : $phone, $userId]);
}

/** Validate the name/email/phone part of a profile form. */
function validate_user_core(string $name, string $email, string $phone): array
{
    $errors = [];
    if ($name === '')                                { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  { $errors[] = 'Please enter a valid email address.'; }
    if (!valid_phone($phone))                        { $errors[] = 'Phone number is not valid.'; }
    return $errors;
}

/** Self-service deactivation: user row + role details row, then log out. */
function deactivate_self(array $user, ?string $detailsTable): never
{
    $conn = db();
    $conn->begin_transaction();
    try {
        db_exec("UPDATE users SET status = 'inactive' WHERE id = ?", [(int) $user['id']]);
        if ($detailsTable !== null) {   // constant supplied by caller, not user input
            db_exec("UPDATE `$detailsTable` SET status = 'disabled' WHERE id = ?", [(int) $user['id']]);
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        flash(report_error($e, 'Could not deactivate your account.'), 'error');
        redirect(home_for($user['role']));
    }
    logout_user();
    flash('Your account has been deactivated. Contact the Aatman team if you want to reactivate it.');
    redirect('login.php');
}
