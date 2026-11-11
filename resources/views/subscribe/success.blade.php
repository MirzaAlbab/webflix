@extends('layouts.subscribe')

@section('title', $transaction->payment_status === 'success' ? 'Payment confirmed' : 'Payment status')

@section('content')
    <div class="card mt-4 mx-auto" style="max-width: 680px">
        <div class="card-body p-4 text-center">
            @if($transaction->payment_status === 'success')
                <h3>Your membership is ready</h3>
                <p>Your payment has been verified and the plan is active.</p>
                <a href="{{ route('catalog') }}" class="btn btn-green mt-3">Browse movies</a>
            @elseif($transaction->payment_status === 'pending')
                <h3>Waiting for payment verification</h3>
                <p>Access starts after the payment provider confirms your payment. This page checks for updates automatically.</p>
                <a href="{{ request()->fullUrl() }}" class="btn btn-green mt-3">Check payment status</a>
            @else
                <h3>Payment {{ $transaction->payment_status }}</h3>
                <p>This payment has not activated a membership.</p>
                <a href="{{ route('subscribe.plans') }}" class="btn btn-green mt-3">Choose a plan</a>
            @endif
            <p class="mt-4 mb-0 small text-break">Order: {{ $transaction->transaction_number }}</p>
        </div>
    </div>
@endsection

@section('scripts')
    @if($transaction->payment_status === 'pending')
        <script>
            const timer = setInterval(async () => {
                try {
                    const response = await fetch(@json(route('transactions.status', $transaction->transaction_number)), { headers: { Accept: 'application/json' } });
                    if (!response.ok) { clearInterval(timer); return; }
                    const payment = await response.json();
                    if (payment.payment_status !== 'pending') window.location.reload();
                } catch (_) { /* The manual status link remains available. */ }
            }, 3000);
        </script>
    @endif
@endsection
