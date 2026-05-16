@extends('emails.layouts.base')

@section('title', 'Reset your password')

@section('content')
    <h2 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1a1d2e;">Reset your password</h2>

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3d4e5a;">
        We received a request to reset the password for your Price Pulse account.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" style="display:inline-block;">
                    <tr>
                        <td align="center" bgcolor="#3d8a57" style="border-radius:6px;">
                            <a href="{{ $data['reset_url'] }}"
                               style="display:inline-block;padding:12px 28px;font-size:15px;font-weight:600;color:#f0fdf4;text-decoration:none;letter-spacing:0.2px;">
                                Reset password
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#6b8a96;">
        This link can be used only once and expires in 60 minutes.
    </p>

    <p style="margin:0;font-size:13px;line-height:1.6;color:#6b8a96;">
        If you did not request a password reset, you can safely ignore this email.
    </p>
@endsection
