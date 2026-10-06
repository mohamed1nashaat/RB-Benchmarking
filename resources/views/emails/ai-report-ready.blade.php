<!DOCTYPE html>
<html lang="{{ $report->language ?? 'en' }}" dir="{{ ($report->language ?? 'en') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your AI Report is Ready</title>
</head>
<body style="margin:0;padding:0;background:#F7F6F3;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1f2937;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F7F6F3;padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

    {{-- Header --}}
    <tr>
        <td style="background:#111827;border-radius:12px 12px 0 0;padding:32px 40px;text-align:{{ ($report->language ?? 'en') === 'ar' ? 'right' : 'left' }};">
            <span style="font-size:22px;font-weight:700;color:#EF4444;letter-spacing:-0.5px;">Red Bananas</span>
            <p style="margin:6px 0 0;font-size:13px;color:#9CA3AF;letter-spacing:0.5px;text-transform:uppercase;">
                @if(($report->language ?? 'en') === 'ar') تقارير الأداء @else Performance Reports @endif
            </p>
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td style="background:#ffffff;padding:40px;border-left:1px solid #E5E7EB;border-right:1px solid #E5E7EB;">

            <p style="margin:0 0 8px;font-size:13px;color:#6B7280;">
                @if(($report->language ?? 'en') === 'ar') مرحباً، @else Hi there, @endif
            </p>

            <h1 style="margin:0 0 24px;font-size:24px;font-weight:700;color:#111827;line-height:1.3;">
                @if(($report->language ?? 'en') === 'ar')
                    تقريرك جاهز للمراجعة
                @else
                    Your AI report is ready
                @endif
            </h1>

            {{-- Report card --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;margin-bottom:32px;">
                <tr>
                    <td style="padding:20px 24px;">
                        <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;">
                            @if(($report->language ?? 'en') === 'ar') اسم التقرير @else Report @endif
                        </p>
                        <p style="margin:0 0 16px;font-size:18px;font-weight:700;color:#111827;">{{ $report->name }}</p>

                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:50%;padding-right:12px;">
                                    <p style="margin:0 0 2px;font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.4px;">
                                        @if(($report->language ?? 'en') === 'ar') الفترة @else Period @endif
                                    </p>
                                    <p style="margin:0;font-size:14px;color:#374151;font-weight:500;">{{ $report->periodLabel() }}</p>
                                </td>
                                <td style="width:50%;">
                                    <p style="margin:0 0 2px;font-size:11px;color:#9CA3AF;text-transform:uppercase;letter-spacing:0.4px;">
                                        @if(($report->language ?? 'en') === 'ar') المنصة @else Platform @endif
                                    </p>
                                    <p style="margin:0;font-size:14px;color:#374151;font-weight:500;">{{ ucfirst($report->platform ?? 'All Platforms') }}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Executive summary snippet --}}
            @if($report->executive_summary)
            <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:0.5px;">
                @if(($report->language ?? 'en') === 'ar') الملخص التنفيذي @else Executive Summary @endif
            </p>
            <p style="margin:0 0 32px;font-size:14px;color:#374151;line-height:1.7;border-left:3px solid #EF4444;padding-left:14px;">
                {{ \Illuminate\Support\Str::limit($report->executive_summary, 300) }}
            </p>
            @endif

            {{-- CTA --}}
            <table cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                <tr>
                    <td style="background:#EF4444;border-radius:8px;">
                        <a href="{{ url(route('report-view', $report->id)) }}"
                           style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;letter-spacing:0.2px;">
                            @if(($report->language ?? 'en') === 'ar') عرض التقرير @else View Report @endif
                            &nbsp;→
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#9CA3AF;line-height:1.6;">
                @if(($report->language ?? 'en') === 'ar')
                    يمكنك أيضاً تنزيل التقرير بصيغة PDF من خلال الرابط أعلاه.
                @else
                    You can also download the report as a PDF from the link above.
                @endif
            </p>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="background:#F9FAFB;border:1px solid #E5E7EB;border-top:none;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#9CA3AF;">
                Powered by <span style="color:#EF4444;font-weight:600;">Red Bananas</span>
                &nbsp;·&nbsp;
                <a href="#" style="color:#9CA3AF;text-decoration:underline;">Unsubscribe</a>
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
