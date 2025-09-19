<?php

namespace lib;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\ValidationException;



use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Label\Font\Font;
use Endroid\QrCode\Label\Font\FontInterface;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\Label\Margin\MarginInterface;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\LabelInterface;

class Qr
{
    /**
     * 生成二维码
     * content  title logo name brower
     * 其中 brower=>1时不生成文件
     * create([
     *   'title'=>'',
     *   'logo'=>'', //完整路径
     *   'content'=>'',
     *   'brower'=>1,
     *   'size'=>100,
     *   //不用CDN地址，直接返回本地相对地址 
     *   'no_cdn'=>1,
     * ]);
     */
    public static function create($arr = [])
    {
        $content = $arr['content'];
        $title   = $arr['title'];
        $logo    = $arr['logo'];
        $brower  = $arr['brower'];
        $no_cdn  = $arr['no_cdn'];
        $size    = $arr['size'] ?: 200;
        $key  = md5(json_encode($arr));
        $url  = '/uploads/qr/' . $key . '.png';
        $file = WWW_PATH . $url;
        $dir  = get_dir($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!$brower && file_exists($file)) {
            if ($no_cdn) {
                return $url;
            }
            return cdn() . $url;
        }
        $content = urldecode($content);
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $content,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: $size,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        if ($logo && file_exists($logo)) {
            $logo = new Logo(
                path: $logo,
                resizeToWidth: 50,
                punchoutBackground: true
            );
        } else {
            $logo = null;
        }
        if ($title) {
            $label = new EndroidLabel(
                text: $title,
                textColor: new Color(255, 0, 0),
            );
        } else {
            $label = null;
        }
        $res = $writer->write($qrCode, $logo, $label);
        if ($brower) {
            header('Content-Type: ' . $res->getMimeType());
            echo $res->getString();
            exit;
        }
        $res->saveToFile($file);
        if ($no_cdn) {
            return $url;
        }
        return cdn() . $url;
    }
}


final readonly class EndroidLabel implements LabelInterface
{
    public function __construct(
        private string $text,
        private FontInterface $font = new Font(PATH . '/data/font/NotoSansSC-Regular.ttf', 16),
        private LabelAlignment $alignment = LabelAlignment::Center,
        private MarginInterface $margin = new Margin(0, 10, 10, 10),
        private ColorInterface $textColor = new Color(0, 0, 0),
    ) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function getFont(): FontInterface
    {
        return $this->font;
    }

    public function getAlignment(): LabelAlignment
    {
        return $this->alignment;
    }

    public function getMargin(): MarginInterface
    {
        return $this->margin;
    }

    public function getTextColor(): ColorInterface
    {
        return $this->textColor;
    }
}
