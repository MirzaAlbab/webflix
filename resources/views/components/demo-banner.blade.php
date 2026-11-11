@if(\App\Support\DemoMode::enabled())
    <div role="status" class="alert alert-info text-center rounded-0 mb-0">Webflix local demo · Fictional catalog · {{ \App\Support\DemoMode::simulatesPayments() ? 'Simulated payments' : 'Midtrans sandbox payments' }}</div>
@endif
