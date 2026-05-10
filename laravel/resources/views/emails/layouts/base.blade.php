<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Price Pulse')</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f5f9fa;font-family:'Segoe UI',Arial,sans-serif;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f9fa;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color:#3d8a57;border-radius:8px 8px 0 0;padding:32px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <img src="{{ $message->embed(public_path('images/price-pulse-logo.png')) }}" alt="Price Pulse" width="72" style="display:block;width:72px;height:auto;background-color:transparent;border-radius:8px;padding:8px;margin:0 auto 8px;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="margin:8px 0 0;color:#f0fdf4;font-size:22px;font-weight:700;letter-spacing:-0.5px;">Price Pulse</h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background-color:#ffffff;padding:40px;border-left:1px solid #e4ebef;border-right:1px solid #e4ebef;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#f5f9fa;border:1px solid #e4ebef;border-top:none;border-radius:0 0 8px 8px;padding:24px 40px;">
                            <p style="margin:0 0 8px;font-size:12px;color:#6b8a96;">You received this email because you have an account on Price Pulse.</p>
                            <p style="margin:0;font-size:12px;color:#6b8a96;">
                                &copy; {{ date('Y') }} Price Pulse. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Container -->

            </td>
        </tr>
    </table>
    <!-- /Wrapper -->

</body>
</html>
