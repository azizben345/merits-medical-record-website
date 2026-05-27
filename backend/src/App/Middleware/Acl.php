// acl.php
final class Roles { const STAFF='staff'; const DOCTOR='doctor'; const ADMIN='admin'; }
final class Status { const DRAFT='draft'; const SUBMITTED='submitted'; const LOCKED='locked'; }

function canSession(string $role, string $status, string $action): bool {
    if ($role === Roles::ADMIN) {
        if ($status === Status::LOCKED && in_array($action, ['edit','upload_report'], true)) return false;
        return true;
    }
    if ($role === Roles::STAFF) {
        if ($action === 'view') return true;
        if ($status === Status::DRAFT && in_array($action,['edit','upload_report'], true)) return true;
        return false;
    }
    if ($role === Roles::DOCTOR) {
        if ($action === 'view') return true;
        if ($status === Status::DRAFT && in_array($action,['edit','upload_report'], true)) return true;
        return false;
    }
    return false;
}

function forbid(Response $res, string $msg='Forbidden'): Response {
    $res->getBody()->write(json_encode(['error'=>$msg]));
    return $res->withHeader('Content-Type','application/json')->withStatus(403);
}
