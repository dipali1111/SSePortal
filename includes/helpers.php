<?php

/** Human-readable relative time, e.g. "Today, 10:42 AM" / "Yesterday, 4:20 PM" / "4 days ago". */
function format_relative_time(string $datetime): string {
    $ts = strtotime($datetime);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    $day = strtotime(date('Y-m-d 00:00:00', $ts));

    if ($day === $today) {
        return 'आज, ' . date('g:i A', $ts);
    }
    if ($day === $yesterday) {
        return 'काल, ' . date('g:i A', $ts);
    }
    $days = (int) floor((strtotime('today') - $day) / 86400);
    if ($days === 1) return '1 दिवसापूर्वी';
    if ($days < 7) return "$days दिवसांपूर्वी";
    $weeks = (int) floor($days / 7);
    return $weeks === 1 ? '1 आठवड्यापूर्वी' : "$weeks आठवड्यांपूर्वी";
}

/** Feed grouping bucket: Today / Yesterday / Earlier. */
function group_label(string $datetime): string {
    $ts = strtotime($datetime);
    $day = strtotime(date('Y-m-d 00:00:00', $ts));
    if ($day === strtotime('today')) return 'आज';
    if ($day === strtotime('yesterday')) return 'काल';
    return 'अगोदर';
}

/** Deadline urgency label from a due date. */
function deadline_label(string $dueDate): string {
    $due = strtotime($dueDate . ' 00:00:00');
    $today = strtotime('today');
    $diffDays = (int) round(($due - $today) / 86400);

    if ($diffDays < 0) return 'मुदत संपली';
    if ($diffDays === 0) return 'आज';
    if ($diffDays === 1) return 'उद्या';
    return "$diffDays दिवस शिल्लक";
}

function deadline_urgency(string $dueDate): string {
    $due = strtotime($dueDate . ' 00:00:00');
    $today = strtotime('today');
    $diffDays = (int) round(($due - $today) / 86400);
    if ($diffDays <= 1) return 'critical'; // overdue, today, or tomorrow
    if ($diffDays <= 4) return 'warning';
    return 'info';
}

/** Short relative time for the activity feed, e.g. "12 min ago" / "2 hr ago" / "3 days ago". */
function relative_short(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'आत्ताच';
    if ($diff < 3600) { $m = (int) floor($diff / 60); return "$m मिनिटांपूर्वी"; }
    if ($diff < 86400) { $h = (int) floor($diff / 3600); return "$h तासांपूर्वी"; }
    $d = (int) floor($diff / 86400);
    return $d === 1 ? '1 दिवसापूर्वी' : "$d दिवसांपूर्वी";
}

function json_out(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_out(['ok' => false, 'error' => "या एंडपॉइंटसाठी $method आवश्यक आहे"], 405);
    }
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}