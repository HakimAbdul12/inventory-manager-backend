<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Credit Application — {{ $lead->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #1a1a2e; line-height: 1.5; padding: 24px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; color: #2563eb; }
        .header p { font-size: 11px; color: #64748b; margin-top: 4px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 16px; padding: 8px 12px; background: #f1f5f9; border-radius: 4px; }
        .meta-row span { font-size: 10px; color: #475569; }
        .section { margin-bottom: 18px; }
        .section-title { font-size: 13px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 8px; }
        .field-grid { width: 100%; border-collapse: collapse; }
        .field-grid td { padding: 4px 8px; font-size: 10.5px; vertical-align: top; }
        .field-grid td:first-child { font-weight: 600; color: #475569; width: 35%; }
        .field-grid td:last-child { color: #0f172a; }
        .field-grid tr:nth-child(even) td { background: #f8fafc; }
        .signature-box { margin-top: 24px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .signature-box h3 { font-size: 12px; margin-bottom: 6px; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $dealershipName }} — Credit Application</h1>
    <p>Applicant: {{ $lead->full_name }} &bull; Submitted: {{ $application->submitted_at?->format('F j, Y g:i A') }}</p>
</div>

<div class="meta-row">
    <span><strong>Application ID:</strong> {{ substr($application->id, 0, 8) }}</span>
    <span><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $application->status)) }}</span>
    <span><strong>Lead Email:</strong> {{ $lead->email ?? 'N/A' }}</span>
    <span><strong>Lead Phone:</strong> {{ $lead->phone ?? 'N/A' }}</span>
</div>

{{-- Personal Information --}}
@if(!empty($data['personal_info']))
<div class="section">
    <div class="section-title">Personal Information</div>
    <table class="field-grid">
        @foreach($data['personal_info'] as $key => $value)
            @if(!in_array($key, ['ssn_encrypted', 'ssn_last4']))
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
            </tr>
            @endif
        @endforeach
    </table>
</div>
@endif

{{-- Address --}}
@if(!empty($data['address']))
<div class="section">
    <div class="section-title">Address</div>
    <table class="field-grid">
        @foreach($data['address'] as $key => $value)
            @if(!is_array($value))
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endif
        @endforeach
        @if(!empty($data['address']['previous_address']))
        <tr><td colspan="2" style="font-weight:700; padding-top:8px;">Previous Address</td></tr>
        @foreach($data['address']['previous_address'] as $key => $value)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        @endif
    </table>
</div>
@endif

{{-- Employment --}}
@if(!empty($data['employment']))
<div class="section">
    <div class="section-title">Employment</div>
    <table class="field-grid">
        @foreach($data['employment'] as $key => $value)
            @if(!is_array($value))
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endif
        @endforeach
    </table>
</div>
@endif

{{-- Co-Applicant --}}
@if(!empty($data['co_applicant']))
<div class="section">
    <div class="section-title">Co-Applicant</div>
    @foreach($data['co_applicant'] as $section => $fields)
        @if(is_array($fields))
        <p style="font-weight:600; margin: 6px 0 2px; color: #334155;">{{ ucwords(str_replace('_', ' ', $section)) }}</p>
        <table class="field-grid">
            @foreach($fields as $key => $value)
                @if(!in_array($key, ['ssn_encrypted', 'ssn_last4']))
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                </tr>
                @endif
            @endforeach
        </table>
        @else
        <table class="field-grid">
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $section)) }}</td>
                <td>{{ $fields }}</td>
            </tr>
        </table>
        @endif
    @endforeach
</div>
@endif

{{-- Vehicle Interest --}}
@if(!empty($data['vehicle_interest']))
<div class="section">
    <div class="section-title">Vehicle Interest</div>
    <table class="field-grid">
        @foreach($data['vehicle_interest'] as $key => $value)
        <tr>
            <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
            <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

{{-- References --}}
@if(!empty($data['references']))
<div class="section">
    <div class="section-title">References</div>
    @foreach($data['references'] as $idx => $ref)
        @if(is_array($ref))
        <p style="font-weight:600; margin: 6px 0 2px; color: #334155;">Reference {{ $idx + 1 }}</p>
        <table class="field-grid">
            @foreach($ref as $key => $value)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    @endforeach
</div>
@endif

{{-- E-Signature --}}
<div class="signature-box">
    <h3>Electronic Signature</h3>
    <table class="field-grid">
        <tr><td>Signed By</td><td>{{ $application->esignature_name }}</td></tr>
        <tr><td>Date Signed</td><td>{{ $application->esignature_date?->format('F j, Y') }}</td></tr>
    </table>
</div>

<div class="footer">
    Generated by {{ $dealershipName }} &bull; {{ now()->format('F j, Y g:i A') }} &bull; Confidential
</div>

</body>
</html>
