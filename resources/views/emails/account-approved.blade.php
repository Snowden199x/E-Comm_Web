<div style="font-family: sans-serif; max-width: 480px; margin: auto;">
    <h2>Welcome to Vendo, {{ $user->name }}!</h2>
    <p>Good news — your account has been approved by our admin team.</p>
    <p>You can now log in and start using Vendo:</p>
    <p>
        <a href="{{ $loginUrl }}" style="background:#3b1735;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
            Log In Now
        </a>
    </p>
</div>