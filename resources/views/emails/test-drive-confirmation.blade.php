<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Drive Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f9fafb; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background-color: #111827; color: #ffffff; padding: 32px 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #111827; }
        .details-box { background-color: #f3f4f6; border-radius: 6px; padding: 24px; margin: 24px 0; border: 1px solid #e5e7eb; }
        .detail-row { display: flex; margin-bottom: 12px; }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { width: 120px; font-weight: 600; color: #4b5563; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; }
        .detail-value { flex: 1; color: #111827; font-weight: 500; }
        .code-box { text-align: center; margin: 32px 0; }
        .code { display: inline-block; background-color: #eff6ff; color: #1d4ed8; font-family: monospace; font-size: 24px; font-weight: bold; padding: 12px 24px; border-radius: 8px; border: 2px dashed #bfdbfe; letter-spacing: 2px; }
        .footer { padding: 24px 40px; background-color: #f9fafb; text-align: center; color: #6b7280; font-size: 14px; border-top: 1px solid #e5e7eb; }
        .vehicle-name { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test Drive Confirmed!</h1>
        </div>
        <div class="content">
            <div class="greeting">Hi {{ $testDrive->visitor_name ?? 'there' }},</div>
            <p>Your test drive has been successfully scheduled. We're looking forward to seeing you!</p>
            
            <div class="details-box">
                @if($testDrive->vehicle)
                <div class="vehicle-name">{{ $testDrive->vehicle->year }} {{ $testDrive->vehicle->make }} {{ $testDrive->vehicle->model }}</div>
                @endif
                
                <div class="detail-row" style="margin-top: 16px;">
                    <div class="detail-label">Date</div>
                    @php
                        $dateObj = null;
                        if ($testDrive->scheduled_date) {
                            $dateStr = $testDrive->scheduled_date;
                            if (str_contains($dateStr, 'T')) { $dateStr = explode('T', $dateStr)[0]; }
                            else if (str_contains($dateStr, ' ')) { $dateStr = explode(' ', $dateStr)[0]; }
                            $dateObj = \Carbon\Carbon::parse($dateStr);
                        }
                    @endphp
                    <div class="detail-value">{{ $dateObj ? $dateObj->format('l, F j, Y') : 'Pending Date' }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Time</div>
                    <div class="detail-value">
                        {{ $testDrive->scheduled_time ? \Carbon\Carbon::parse($testDrive->scheduled_time)->format('g:i A') : '' }} 
                        @if($testDrive->end_time) - {{ \Carbon\Carbon::parse($testDrive->end_time)->format('g:i A') }} @endif
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value" style="color: #059669; text-transform: uppercase;">{{ str_replace('_', ' ', $testDrive->status) }}</div>
                </div>
            </div>

            <p style="text-align: center; color: #4b5563; font-size: 14px;">Please present this booking code when you arrive:</p>
            <div class="code-box">
                <span class="code">{{ $testDrive->booking_code }}</span>
            </div>
            
            <p>If you need to reschedule or cancel, you can do so at any time by returning to our chat widget and providing your booking code.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $testDrive->tenant->name ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
