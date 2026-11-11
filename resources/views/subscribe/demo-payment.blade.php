@extends('layouts.subscribe')

@section('title', 'Simulated payment')

@section('content')
    <div class="card mt-4 mx-auto" style="max-width: 680px">
        <div class="card-body p-4">
            <h3>No money will be charged</h3>
            <p>This local demo simulates a payment outcome. It does not contact Midtrans or process card details.</p>
            <hr>
            <p>{{ $transaction->plan->title }} · {{ $transaction->plan->duration }} days</p>
            <h4>Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</h4>
            <form method="POST" action="{{ route('demo.payment.complete', $transaction->transaction_number) }}" class="mt-4">
                @csrf
                <button type="submit" name="outcome" value="settlement" class="btn btn-green me-2">Simulate successful payment</button>
                <button type="submit" name="outcome" value="cancel" class="btn btn-outline-light">Simulate cancellation</button>
            </form>
        </div>
    </div>
@endsection
