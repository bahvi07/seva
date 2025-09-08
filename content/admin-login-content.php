<section class="content-section" style="min-height:60vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-seva" style="background: white;">
                    <div class="card-body p-5">
                        <h2 class="text-center fw-bold text-navy mb-4">
                            <i class="bi bi-lock-fill me-2"></i>
                            Admin Login
                        </h2>
                        <?php if (!empty($logoutMessage)): ?>
                            <div class="alert alert-success mb-4">
                                <?php echo htmlspecialchars($logoutMessage); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['login_error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo htmlspecialchars($_SESSION['login_error']); 
                                    unset($_SESSION['login_error']); // Clear the error message after displaying it
                                ?>
                            </div>
                        <?php endif; ?>
                        <form action="admin-login-handler.php" method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control form-seva" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control form-seva" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-seva btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        <div class="mt-4 text-center">
                            <small class="text-muted"><i class="bi bi-info-circle"></i> For authorized admins only.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
