<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Raio-X Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: white;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #6366f1;
            color: white;
            box-shadow: none;
        }
        .btn-primary {
            background: #6366f1;
            border: none;
            border-radius: 12px;
            padding: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-5">
        <div class="bg-primary d-inline-block p-3 rounded-4 mb-3">
            <i class="fa-solid fa-bolt-lightning fa-2x"></i>
        </div>
        <h3 class="fw-bold">Acesso Restrito</h3>
        <p class="text-white-50">Painel de Telemetria Raio-X</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger small rounded-4 mb-4">
            @foreach ($errors->all() as $error)
                <div><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label class="form-label small fw-bold text-white-50">E-MAIL</label>
            <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
        </div>
        <div class="mb-5">
            <label class="form-label small fw-bold text-white-50">SENHA</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">
            ENTRAR NO PAINEL
        </button>
        <div class="text-center text-white-50 small">
            &copy; {{ date('Y') }} Raio-X Inteligência Territorial
        </div>
    </form>
</div>

</body>
</html>
