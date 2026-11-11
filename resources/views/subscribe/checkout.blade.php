@extends('layouts.subscribe')

@section('title', 'Review your plan')

@section('content')
    <div class="card mt-4 mx-auto" style="max-width: 680px">
        <div class="card-body p-4">
            <h3>{{ $plan->title }} · {{ $plan->duration }} days</h3>
            <p class="text-green">{{ $plan->resolution }} · {{ $plan->max_devices }} devices</p>
            <hr>
            <div class="d-flex justify-content-between mb-2"><span>Plan price</span><span>Rp{{ number_format($plan->subtotalAmount(), 0, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span>PPN 12%</span><span>Rp{{ number_format($plan->taxAmount(), 0, ',', '.') }}</span></div>
            <div class="d-flex justify-content-between fs-4 my-4"><strong>Total</strong><strong>Rp{{ number_format($plan->totalAmount(), 0, ',', '.') }}</strong></div>
            <form id="checkout-form">
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label" for="terms">I have reviewed the plan and total.</label>
                </div>
                <button type="submit" class="btn btn-green w-100" id="pay-button">Continue to payment</button>
                <p class="text-danger mt-3 mb-0" id="payment-error" role="alert"></p>
            </form>
            <a href="{{ route('subscribe.plans') }}" class="d-inline-block text-green mt-3">Back to plans</a>
        </div>
    </div>
@endsection

@section('scripts')
    @unless(\App\Support\DemoMode::simulatesPayments())
        <script src="{{ config('midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}"></script>
    @endunless
    <script>
        document.getElementById('checkout-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = document.getElementById('pay-button');
            const error = document.getElementById('payment-error');
            button.disabled = true;
            error.textContent = '';
            try {
                const response = await fetch(@json(route('checkout')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ plan_id: @json($plan->id), terms: document.getElementById('terms').checked })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to start payment.');
                if (data.checkout_url) {
                    window.location.assign(data.checkout_url);
                    return;
                }
                if (!window.snap) throw new Error('The payment window could not load. Please try again.');
                window.snap.pay(data.snap_token, {
                    onSuccess: () => window.location.assign(data.status_url),
                    onPending: () => window.location.assign(data.status_url),
                    onError: () => window.location.assign(data.status_url),
                    onClose: () => { button.disabled = false; }
                });
            } catch (failure) {
                error.textContent = failure.message;
                button.disabled = false;
            }
        });
    </script>
@endsection
