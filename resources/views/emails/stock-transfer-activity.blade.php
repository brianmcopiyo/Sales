<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfer – {{ ucfirst(str_replace('_', ' ', $activity)) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-weight: 500;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #f3f4f6;
            overflow: hidden;
            box-shadow: 0 2px 15px -3px rgba(0, 111, 120, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .email-header {
            background-color: #ffffff;
            padding: 32px 32px 24px;
            text-align: center;
            border-bottom: 1px solid #f3f4f6;
        }

        .email-title {
            color: #006F78;
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .email-subtitle {
            color: #374151;
            font-size: 18px;
            font-weight: 500;
            margin: 0;
        }

        .email-body {
            padding: 32px;
        }

        .email-content {
            color: #4b5563;
            font-size: 16px;
            margin-bottom: 24px;
            line-height: 1.6;
            font-weight: 500;
        }

        .details-box {
            background-color: #f8fafc;
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            font-size: 14px;
            color: #374151;
        }

        .details-box .row {
            margin-bottom: 8px;
        }

        .details-box .row:last-child {
            margin-bottom: 0;
        }

        .details-box .label {
            color: #6b7280;
            font-weight: 500;
        }

        .details-box .value {
            font-weight: 500;
            color: #111827;
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
        }

        .button-container {
            text-align: center;
            margin: 28px 0 16px;
        }

        .button {
            display: inline-block;
            background-color: #006F78;
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 16px;
            transition: background-color 0.2s;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .button:hover {
            background-color: #005a62;
            color: #ffffff !important;
        }

        .info-text {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
        }

        .email-footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f3f4f6;
        }

        .footer-text {
            color: #9ca3af;
            font-size: 12px;
            font-weight: 500;
            margin: 0;
        }

        .extra-block {
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            font-size: 14px;
            color: #92400e;
        }
    </style>
</head>

<body>
    <div style="padding: 20px;">
        <div class="email-container">
            <div class="email-header">
                <h1 class="email-title">Stock Management</h1>
                <h2 class="email-subtitle">
                    @switch($activity)
                        @case('created')
                            New Stock Transfer Created
                        @break

                        @case('in_transit')
                            Transfer Approved & In Transit
                        @break

                        @case('received')
                            Stock Transfer Received
                        @break

                        @case('partial_received')
                            Partially Received (Awaiting Sender Confirmation)
                        @break

                        @case('rejected')
                            Stock Transfer Rejected
                        @break

                        @case('cancelled')
                            Stock Transfer Cancelled
                        @break

                        @case('partial_confirmed')
                            Partial Reception Confirmed
                        @break

                        @case('returned')
                            Partial Reception Returned
                        @break

                        @default
                            Stock Transfer Update
                    @endswitch
                </h2>
            </div>
            <div class="email-body">
                <p class="email-content">Hello,</p>
                <p class="email-content">
                    @switch($activity)
                        @case('created')
                            A new stock transfer has been created. Details are below.
                        @break

                        @case('in_transit')
                            The receiving branch has approved this transfer. It is now in transit.
                        @break

                        @case('received')
                            This transfer has been fully received by the recipient branch.
                        @break

                        @case('partial_received')
                            The recipient has recorded a partial reception. The sender branch must confirm before stock is
                            credited.
                            @if (!empty($payload['quantity_received']))
                                <br><strong>Quantity received: {{ $payload['quantity_received'] }} of
                                    {{ $stockTransfer->quantity }}</strong>
                                @if (!empty($payload['received_notes']))
                                    <br>Notes: {{ $payload['received_notes'] }}
                                @endif
                            @endif
                        @break

                        @case('rejected')
                            This transfer has been rejected by the recipient branch.
                            @if (!empty($payload['rejection_reason']))
                                <br><strong>Reason:</strong> {{ $payload['rejection_reason'] }}
                            @endif
                        @break

                        @case('cancelled')
                            This stock transfer has been cancelled. Stock has been returned to the sender branch.
                        @break

                        @case('partial_confirmed')
                            The sender has confirmed the partial reception.
                            {{ $payload['quantity_received'] ?? $stockTransfer->quantity_received }} units have been credited to
                            the recipient branch.
                        @break

                        @case('returned')
                            The sender has returned or disagreed with the partial reception. The shortfall has been credited
                            back to the sender branch.
                            @if (!empty($payload['return_reason']))
                                <br><strong>Reason:</strong> {{ $payload['return_reason'] }}
                            @endif
                        @break

                        @default
                            There is an update on this stock transfer.
                    @endswitch
                </p>

                <div class="details-box">
                    <div class="row"><span class="label">Transfer number:</span> <span
                            class="value">{{ $stockTransfer->transfer_number ?? 'N/A' }}</span></div>
                    <div class="row"><span class="label">Product:</span> <span
                            class="value">{{ $stockTransfer->product?->name ?? $stockTransfer->items->first()?->product?->name ?? '-' }}
                            ({{ $stockTransfer->product?->sku ?? $stockTransfer->items->first()?->product?->sku ?? '-' }})</span></div>
                    <div class="row"><span class="label">Quantity:</span> <span
                            class="value">{{ $stockTransfer->total_quantity }}</span></div>
                    <div class="row"><span class="label">From:</span> <span
                            class="value">{{ $stockTransfer->fromBranch->name ?? '-' }}</span></div>
                    <div class="row"><span class="label">To:</span> <span
                            class="value">{{ $stockTransfer->toBranch->name ?? '-' }}</span></div>
                    <div class="row"><span class="label">Status:</span> <span
                            class="value">{{ ucfirst(str_replace('_', ' ', $stockTransfer->status)) }}</span></div>
                    @if ($stockTransfer->creator)
                        <div class="row"><span class="label">Created by:</span> <span
                                class="value">{{ $stockTransfer->creator->name }}</span></div>
                    @endif
                </div>

                <div class="button-container">
                    <a href="{{ config('app.url') . route('stock-transfers.show', $stockTransfer, false) }}"
                        class="button" style="color: #ffffff !important;">View Transfer</a>
                </div>
                <p class="info-text">
                    This is an automated notification for both sender and recipient branches.
                </p>
            </div>
            <div class="email-footer">
                <p class="footer-text">© {{ date('Y') }} Stock Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>
