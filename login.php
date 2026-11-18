<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/functions.php';

if (sudah_login()) {
    redirect('index.php');
}

// Halaman tujuan setelah login (hanya nama file sederhana, cegah open redirect)
$next = $_GET['next'] ?? ($_POST['next'] ?? '');
if (!preg_match('/^[a-z_]+\.php$/', $next)) {
    $next = '';
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $koneksi->prepare("SELECT id_user, nama, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int)$u['id_user'],
            'nama'  => $u['nama'],
            'email' => $u['email'],
            'role'  => $u['role'],
        ];
        flash('success', 'Selamat datang, ' . $u['nama'] . '!');
        if ($u['role'] === 'admin') {
            redirect('panel/index.php');
        }
        if ($u['role'] === 'penjual') {
            redirect('admin/index.php');
        }
        redirect($next !== '' ? $next : 'index.php');
    }
    $error = 'Email atau password salah.';
}

$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h4 mb-3 text-center">Masuk</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" action="login.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="next" value="<?= e($next) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-success" type="submit">Masuk</button>
                    </div>
                </form>
                <p class="small text-center mt-3 mb-0">Belum punya akun? <a href="register.php">Daftar</a></p>
                <hr>
                <p class="small text-muted mb-0">
                    <strong>Akun demo:</strong><br>
                    Pembeli: pembeli@contoh.com / pembeli123<br>
                    Penjual: penjual@contoh.com / penjual123
                </p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
