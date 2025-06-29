<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Welcome to Bazinga</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fa;
    }

    .welcome-container {
      height: 100vh;
      display: flex;
    }

    .left-panel {
      background: linear-gradient(135deg, #6C63FF, #4e54c8);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .left-panel h1 {
      font-size: 2.5rem;
      font-weight: 700;
    }

    .form-card {
      width: 100%;
      max-width: 420px;
      background: white;
      border-radius: 16px;
      box-shadow: 0 0 20px rgba(0,0,0,0.08);
      padding: 2rem;
    }

    .form-switch .nav-link {
      color: #6c757d;
      font-weight: 600;
    }

    .form-switch .nav-link.active {
      color: #4e54c8;
      border-bottom: 2px solid #4e54c8;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.25);
    }
  </style>
</head>
<body>

  <div class="container-fluid welcome-container">
    <div class="col-md-6 d-none d-md-flex left-panel">
      <div class="text-center">
        <h1>Welcome to Bazinga</h1>
        <p class="mt-3 fs-5">Connect and share with us</p>
      </div>
    </div>

    <div class="col-md-6 d-flex align-items-center justify-content-center p-4">
      <div class="form-card">
        <ul class="nav nav-tabs form-switch mb-4" id="authTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login"
                    type="button" role="tab">Login</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register"
                    type="button" role="tab">Register</button>
          </li>
        </ul>

        <div class="tab-content" id="authTabsContent">
          <div class="tab-pane fade show active" id="login" role="tabpanel">
            <form id="login-form"> <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" name="email" placeholder="you@example.com">
              </div>
              <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password"  placeholder="********">
              </div>
              <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
          </div>

          <div class="tab-pane fade" id="register" role="tabpanel">
            <form id="register-form"> <div class="mb-3">
                <label>Full Name</label>
                <input type="text" class="form-control" name="name"  placeholder="John Doe">
              </div>
              <div class="mb-3">
                <label>Email</label>
                <input type="email" class="form-control" name="email"  placeholder="you@example.com">
              </div>
              <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password"  placeholder="********">
              </div>
              <div class="mb-3">
                <label>Confirm Password</label>
                <input type="password" class="form-control" name="password_confirmation"  placeholder="********"> </div>
              <button type="submit" class="btn btn-primary w-100">Create Account</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function getCSRF() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    // Get the form elements directly
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) { // Ensure the form exists before adding listener
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Access form fields using this.elements.name or FormData
            const data = {
                email: this.elements.email.value,
                password: this.elements.password.value
            };

            const res = await fetch('http://127.0.0.1:8001/login', { // Using absolute URL for clarity, adjust if needed
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRF()
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            alert(result.message);
            if (res.ok) window.location.href = '/feed';
        });
    }


    if (registerForm) { // Ensure the form exists before adding listener
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Access form fields using this.elements.name or FormData
            const data = {
                name: this.elements.name.value,
                email: this.elements.email.value,
                password: this.elements.password.value,
                password_confirmation: this.elements.password_confirmation.value // Corrected access
            };

            const res = await fetch('http://127.0.0.1:8001/register', { // Using absolute URL for clarity, adjust if needed
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRF()
                },
                body: JSON.stringify(data)
            });

            const result = await res.json();
            alert(result.message);
            if (res.ok) window.location.href = '/feed';
        });
    }
  </script>

</body>
</html>
