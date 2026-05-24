@extends('emails.layouts.base')

@section('title', 'Price alert')

@section('content')
    <h2 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#1a1d2e;">
        {{ count($alerts) === 1 ? 'A price alert was triggered' : 'Price alerts were triggered' }}
    </h2>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#3d4e5a;">
        Hi {{ $user->name }},
        {{ count($alerts) === 1 ? 'one product has' : count($alerts) . ' products have' }}
        a price update worth checking.
    </p>

    @foreach ($alerts as $item)
        @php
            $product = $item['product'];
            $alert = $item['alert'];
            $price = $item['price'];
            $isPriceDropAlert = $alert->type === \App\Models\PriceAlert::TYPE_PRICE_DROP;
        @endphp

        <table width="100%" cellpadding="0" cellspacing="0" border="0"
               style="margin-bottom:16px;border:1px solid #e4ebef;border-radius:8px;overflow:hidden;">
            <tr>
                <td style="padding:16px 20px;background-color:#f9fafb;">
                    <p style="margin:0 0 10px;font-size:15px;font-weight:600;color:#1a1d2e;line-height:1.4;">
                        {{ $product->name }}
                    </p>

                    <p style="margin:0 0 14px;font-size:14px;line-height:1.5;color:#3d4e5a;">
                        @if ($isPriceDropAlert)
                            The price dropped. The new price is
                            <strong style="color:#3d8a57;">{{ number_format((float) $price, 2) }} {{ $product->currency }}</strong>.
                        @else
                            Your target price was reached. The new price is
                            <strong style="color:#3d8a57;">{{ number_format((float) $price, 2) }} {{ $product->currency }}</strong>.
                        @endif
                    </p>

                    <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:14px;width:100%;">
                        <tr>
                            <td align="center">
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding-right:24px;">
                                            <span style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#6b8a96;margin-bottom:2px;">Current price</span>
                                            <span style="font-size:22px;font-weight:700;color:#3d8a57;">
                                                {{ number_format((float) $price, 2) }} {{ $product->currency }}
                                            </span>
                                        </td>
                                        <td>
                                            @if (! $isPriceDropAlert)
                                                <span style="display:block;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:#6b8a96;margin-bottom:2px;">Your target</span>
                                                <span style="font-size:22px;font-weight:700;color:#1a1d2e;">
                                                    {{ number_format((float) $alert->target_price, 2) }} {{ $product->currency }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
                        <tr>
                            <td align="center">
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td bgcolor="#3d8a57" style="border-radius:6px;">
                                            <a href="{{ $product->url }}"
                                               style="display:inline-block;padding:10px 22px;font-size:14px;font-weight:600;color:#f0fdf4;text-decoration:none;">
                                                View product
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endforeach

    <p style="margin:24px 0 0;font-size:13px;color:#6b8a96;line-height:1.6;">
        Target price alerts are deactivated after they trigger. Price drop alerts stay active
        and can notify you again on future drops. You can manage both alert types from your wishlist.
    </p>
@endsection
