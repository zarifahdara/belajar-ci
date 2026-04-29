<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<table class="table table-borderless">
    <tbody>
        <tr>
            <th style="width: 150px;">Username</th>
            <td>
                <?= esc($username ?? '-') ?>
                <span class="badge bg-<?= (($role ?? '') === 'admin') ? 'danger' : 'primary' ?> ms-1">
                    <?= esc($role ?? '-') ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= esc($email ?? '-') ?></td>
        </tr>
        <tr>
            <th>Login Time</th>
            <td><?= esc($login_time ?? '-') ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <?php if (($isLoggedIn ?? false) === true): ?>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i> Sudah Login
                    </span>
                <?php else: ?>
                    <span class="badge bg-secondary">Belum Login</span>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>

<?= $this->endSection() ?>