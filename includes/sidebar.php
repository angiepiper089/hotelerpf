<?php
$role = $_SESSION['role_name'] ?? '';
$current = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));

/**
 * Nav items map directly to the ERP modules required by the assignment
 * (min. 4 integrated modules). The 'roles' key drives role-based access,
 * mirroring how real ERP suites expose different modules per job function.
 */
$navItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => '/dashboard.php', 'match' => 'dashboard.php', 'roles' => ['Admin','Manager','FrontDesk','Housekeeping','Finance']],
    ['label' => 'Reservations', 'icon' => 'bi-calendar-check', 'url' => '/modules/reservations/list.php', 'match' => 'reservations', 'roles' => ['Admin','Manager','FrontDesk']],
    ['label' => 'Rooms', 'icon' => 'bi-door-open', 'url' => '/modules/rooms/list.php', 'match' => 'rooms', 'roles' => ['Admin','Manager','FrontDesk','Housekeeping']],
    ['label' => 'Guests (CRM)', 'icon' => 'bi-people', 'url' => '/modules/guests/list.php', 'match' => 'guests', 'roles' => ['Admin','Manager','FrontDesk']],
    ['label' => 'Billing', 'icon' => 'bi-receipt', 'url' => '/modules/billing/list.php', 'match' => 'billing', 'roles' => ['Admin','Manager','FrontDesk','Finance']],
    ['label' => 'Inventory', 'icon' => 'bi-box-seam', 'url' => '/modules/inventory/list.php', 'match' => 'inventory', 'files' => ['list.php', 'add.php', 'edit.php'], 'roles' => ['Admin','Manager','Finance']],
    ['label' => 'Suppliers & POs', 'icon' => 'bi-truck', 'url' => '/modules/inventory/suppliers.php', 'match' => 'inventory', 'files' => ['suppliers.php', 'po-create.php', 'po-view.php', 'po-action.php'], 'roles' => ['Admin','Manager','Finance']],
    ['label' => 'Employees', 'icon' => 'bi-person-badge', 'url' => '/modules/employees/list.php', 'match' => 'employees', 'roles' => ['Admin','Manager']],
    ['label' => 'Reports', 'icon' => 'bi-graph-up', 'url' => '/modules/reports/index.php', 'match' => 'reports', 'roles' => ['Admin','Manager','Finance']],
    ['label' => 'Audit Log', 'icon' => 'bi-shield-check', 'url' => '/modules/users/audit-log.php', 'match' => 'users', 'files' => ['audit-log.php'], 'roles' => ['Admin']],
    ['label' => 'User Accounts', 'icon' => 'bi-person-gear', 'url' => '/modules/users/list.php', 'match' => 'users', 'files' => ['list.php', 'toggle-status.php'], 'roles' => ['Admin']],
];
?>
<nav class="app-sidebar" id="appSidebar">
  <div class="app-sidebar-brand">
    <i class="bi bi-building"></i>
    <span>Grand Horizon <small>Hotel ERP</small></span>
  </div>
  <ul class="app-sidebar-nav">
    <?php foreach ($navItems as $item): ?>
      <?php if (!in_array($role, $item['roles'], true)) continue; ?>
      <?php
        $active = ($currentDir === $item['match'] || $current === $item['match']);
        if ($active && isset($item['files'])) {
            $active = in_array($current, $item['files'], true);
        }
      ?>
      <li>
        <a href="<?= basePath() . $item['url'] ?>" class="<?= $active ? 'active' : '' ?>">
          <i class="bi <?= $item['icon'] ?>"></i> <span><?= e($item['label']) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
  <div class="app-sidebar-footer">
    <small>Role-based access &middot; Systems Integration Demo</small>
  </div>
</nav>