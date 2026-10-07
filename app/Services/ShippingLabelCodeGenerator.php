<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

class ShippingLabelCodeGenerator
{
    public function forTrackingNumber(string $trackingNumber): array
    {
        $barcode = (new TypeCode128())->getBarcode($trackingNumber);
        $barcodeSvg = (new SvgRenderer())->setSvgType(SvgRenderer::TYPE_SVG_INLINE)
            ->render($barcode, $barcode->getWidth() * 2, 120);
        $qrCode = new QrCode(
            data: $trackingNumber,
            encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 320,
            margin: 12,
        );

        return [
            'barcodeDataUri' => 'data:image/svg+xml;base64,'.base64_encode($barcodeSvg),
            'qrDataUri' => (new SvgWriter())->write($qrCode)->getDataUri(),
        ];
    }
}
