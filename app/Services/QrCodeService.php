<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /** Inline SVG markup for the given payload (e.g. a check-in URL). */
    public function svg(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'addQuietzone' => true,
            'drawLightModules' => false,
        ]);

        return (new QRCode($options))->render($data);
    }
}
