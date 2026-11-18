<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

if (sudah_login()) {
    redirect('index.php');
}

$errors = [];
$val = ['nama' => '', 'email' => '', 'no_hp' => '', 'alamat' => '', 'role' => 'pembeli'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    foreach ($val as $k => $_) {
        $val[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($val['nama'] === '' || mb_strlen($val['nama']) > 100) {
        $errors[] = 'Nama wajib diisi (maksimal 100 karakter).';
    }
    if (!filter_var($val['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if (!in_array($val['role'], ['pembeli', 'penjual'], true)) {
        $errors[] = 'Peran tidak valid.';
    }
    if (!preg_match('/^[0-9+\- ]{8,20}$/', $val['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid (8-20 digit).';
    }

    if (!$errors) {
        $cek = $koneksi->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->bind_param('s', $val['email']);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $errors[] = 'Email sudah terdaftar.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("INSERT INTO users (nama, email, password, role, no_hp, alamat) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $val['nama'], $val['email'], $hash, $val['role'], $val['no_hp'], $val['alamat']);
        $stmt->execute();
        flash('success', 'Pendaftaran berhasil. Silakan masuk.');
        redirect('login.php');
    }
}

$judul = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Daftar Akun Baru</h1>
                <?php if ($errors): ?>
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="register.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="nama">Nama Lengkap / Nama Toko</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= e($val['nama']) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($val['email']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="no_hp">No. HP</label>
                            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($val['no_hp']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= e($val['alamat']) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="konfirmasi" name="konfirmasi" minlength="6" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="role">Daftar sebagai</label>
                        <select class="form-select" id="role" name="role">
                            <option value="pembeli" <?= $val['role'] === 'pembeli' ? 'selected' : '' ?>>Pembeli</option>
                            <option value="penjual" <?= $val['role'] === 'penjual' ? 'selected' : '' ?>>Penjual</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-success" type="submit">Daftar</button>
                    </div>
                </form>
                <p class="small text-center mt-3 mb-0">Sudah punya akun? <a href="login.php">Masuk</a></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
